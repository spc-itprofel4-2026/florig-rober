<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ModelGenerate extends BaseCommand
{
    protected $group       = 'Model Driven';
    protected $name        = 'model:generate';
    protected $description = 'Generates code from a platform independent model file with optional marks.';
    protected $usage       = 'model:generate <modelfile> [target] [marksfile]';

    protected $arguments = [
        'modelfile' => 'Path of the model file.',
        'target'    => 'ci4 or sql. Default: ci4',
        'marksfile' => 'Optional path of the marking model JSON file.',
    ];

    private array $ci4Types = [
        'text'        => ['VARCHAR', 255],
        'longtext'    => ['TEXT', null],
        'wholenumber' => ['INT', 11],
        'decimal'     => ['DECIMAL', '10,2'],
        'yesno'       => ['TINYINT', 1],
        'date'        => ['DATE', null],
        'datetime'    => ['DATETIME', null],
    ];

    private array $sqlTypes = [
        'text'        => 'VARCHAR(255)',
        'longtext'    => 'TEXT',
        'wholenumber' => 'INT',
        'decimal'     => 'DECIMAL(10,2)',
        'yesno'       => 'TINYINT(1)',
        'date'        => 'DATE',
        'datetime'    => 'DATETIME',
    ];

    public function run(array $params)
    {
        $file      = $params[0] ?? null;
        $target    = $params[1] ?? 'ci4';
        $marksFile = $params[2] ?? null;

        if ($file === null || ! is_file(ROOTPATH . $file)) {
            CLI::error('Model file not found.');
            return;
        }

        if (! in_array($target, ['ci4', 'sql'], true)) {
            CLI::error('The target must be ci4 or sql.');
            return;
        }

        $model = json_decode(
            (string) file_get_contents(ROOTPATH . $file),
            true
        );

        $error = $this->check($model);

        if ($error !== null) {
            CLI::error($error);
            return;
        }

        $marks = [];

        if ($marksFile !== null) {
            if (! is_file(ROOTPATH . $marksFile)) {
                CLI::error('Marking model file not found.');
                return;
            }

            $marks = json_decode(
                (string) file_get_contents(ROOTPATH . $marksFile),
                true
            );

            $error = $this->checkMarks($model, $marks);

            if ($error !== null) {
                CLI::error($error);
                return;
            }
        }

        $entity = $model['entity'];
        $table  = $marks['marks']['tableName']
            ?? $this->snake($entity) . 's';

        $fields = [];

        foreach ($model['fields'] as $f) {
            $fieldMarks = $marks['fields'][$f['name']] ?? [];

            $fields[] = [
                'name'     => $f['name'],
                'column'   => $this->snake($f['name']),
                'type'     => $f['type'],
                'required' => ! empty($f['required']),
                'unique'   => $fieldMarks['unique'] ?? false,
                'size'     => $fieldMarks['size'] ?? null,
            ];
        }

        if ($target === 'sql') {
            $this->makeSql($table, $fields);
        } else {
            $this->makeMigration($entity, $table, $fields);
            $this->makeModelClass($entity, $table, $fields);
        }
    }

    private function check($model): ?string
    {
        if (! is_array($model)) {
            return 'The model file is not valid JSON.';
        }

        $entity = $model['entity'] ?? '';

        if (! preg_match('/^[A-Z][A-Za-z]*$/', $entity)) {
            return 'entity must start with a capital letter and contain letters only.';
        }

        if (empty($model['fields']) || ! is_array($model['fields'])) {
            return 'The model needs a fields list.';
        }

        foreach ($model['fields'] as $f) {
            $name = $f['name'] ?? '';

            if (! preg_match('/^[a-z][A-Za-z]*$/', $name) || $name === 'id') {
                return "Invalid field name: {$name}";
            }

            if (! isset($this->ci4Types[$f['type'] ?? ''])) {
                return "Unknown field type for {$name}.";
            }
        }

        return null;
    }

    private function checkMarks($model, $marks): ?string
    {
        if (! is_array($marks)) {
            return 'The marking model is not valid JSON.';
        }

        if (($marks['entity'] ?? null) !== $model['entity']) {
            return 'The marking model entity does not match the PIM entity.';
        }

        $table = $marks['marks']['tableName'] ?? null;

        if ($table !== null && ! preg_match('/^[a-z][a-z0-9_]*$/', $table)) {
            return 'tableName must contain lowercase letters, numbers, or underscores.';
        }

        $knownFields = array_column($model['fields'], 'name');

        foreach (($marks['fields'] ?? []) as $name => $fieldMarks) {
            if (! in_array($name, $knownFields, true)) {
                return "Unknown field in marking model: {$name}";
            }

            if (! is_array($fieldMarks)) {
                return "Marks for {$name} must be an object.";
            }

            if (isset($fieldMarks['unique']) && ! is_bool($fieldMarks['unique'])) {
                return "The unique mark for {$name} must be true or false.";
            }

            if (isset($fieldMarks['size'])) {
                if (
                    ! is_int($fieldMarks['size'])
                    || $fieldMarks['size'] < 1
                    || $fieldMarks['size'] > 500
                ) {
                    return "The size mark for {$name} must be an integer from 1 to 500.";
                }

                $fieldType = null;

                foreach ($model['fields'] as $field) {
                    if ($field['name'] === $name) {
                        $fieldType = $field['type'];
                        break;
                    }
                }

                if ($fieldType !== 'text') {
                    return "The size mark can only be applied to text fields: {$name}.";
                }
            }
        }

        return null;
    }

    private function snake(string $name): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }

    private function makeMigration(string $entity, string $table, array $fields): void
    {
        $lines = "            'id' => ['type' => 'INT', 'constraint' => 11, "
            . "'unsigned' => true, 'auto_increment' => true],\n";

        foreach ($fields as $f) {
            [$dbType, $defaultSize] = $this->ci4Types[$f['type']];
            $size = $f['size'] ?? $defaultSize;

            $part = "'type' => '{$dbType}'";

            if ($size !== null) {
                $part .= ", 'constraint' => "
                    . (is_int($size) ? $size : "'{$size}'");
            }

            $part .= ", 'null' => " . ($f['required'] ? 'false' : 'true');

            $lines .= "            '{$f['column']}' => [{$part}],\n";
        }

        $uniqueKeys = '';

        foreach ($fields as $f) {
            if ($f['unique']) {
                $uniqueKeys .= "        \$this->forge->addUniqueKey('{$f['column']}');\n";
            }
        }

        $class = "Create{$entity}Table";

        $code = "<?php\n\nnamespace App\\Database\\Migrations;\n\n"
            . "use CodeIgniter\\Database\\Migration;\n\n"
            . "class {$class} extends Migration\n{\n"
            . "    public function up()\n    {\n"
            . "        \$this->forge->addField([\n{$lines}        ]);\n"
            . "        \$this->forge->addKey('id', true);\n"
            . $uniqueKeys
            . "        \$this->forge->createTable('{$table}');\n"
            . "    }\n\n"
            . "    public function down()\n    {\n"
            . "        \$this->forge->dropTable('{$table}');\n"
            . "    }\n"
            . "}\n";

        $folder = APPPATH . 'Database/Migrations/';
        $old    = glob($folder . "*_{$class}.php");

        $path = $old
            ? $old[0]
            : $folder . date('Y-m-d-His') . "_{$class}.php";

        file_put_contents($path, $code);

        CLI::write('Wrote ' . str_replace(ROOTPATH, '', $path), 'green');
    }

    private function makeModelClass(string $entity, string $table, array $fields): void
    {
        $names   = array_map(fn ($f) => "'" . $f['column'] . "'", $fields);
        $allowed = implode(', ', $names);

        $code = "<?php\n\nnamespace App\\Models;\n\n"
            . "use CodeIgniter\\Model;\n\n"
            . "class {$entity}Model extends Model\n{\n"
            . "    protected \$table         = '{$table}';\n"
            . "    protected \$primaryKey    = 'id';\n"
            . "    protected \$allowedFields = [{$allowed}];\n"
            . "}\n";

        $path = APPPATH . "Models/{$entity}Model.php";

        file_put_contents($path, $code);

        CLI::write('Wrote ' . str_replace(ROOTPATH, '', $path), 'green');
    }

    private function makeSql(string $table, array $fields): void
    {
        $rows = ["        id INT AUTO_INCREMENT PRIMARY KEY"];

        foreach ($fields as $f) {
            $type = $this->sqlTypes[$f['type']];

            if ($f['size'] !== null) {
                $type = "VARCHAR({$f['size']})";
            }

            $row = "        {$f['column']} {$type}";

            if ($f['required']) {
                $row .= ' NOT NULL';
            }

            if ($f['unique']) {
                $row .= ' UNIQUE';
            }

            $rows[] = $row;
        }

        $folder = WRITEPATH . 'generated/';

        if (! is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $path = $folder . "{$table}.sql";
        $body = implode(",\n", $rows);

        file_put_contents(
            $path,
            "CREATE TABLE {$table} (\n{$body}\n);\n"
        );

        CLI::write('Wrote ' . str_replace(ROOTPATH, '', $path), 'green');
    }
}

