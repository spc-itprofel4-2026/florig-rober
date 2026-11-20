<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWeatherObservationTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'city' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'temperature' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false],
            'humidity' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'condition' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'observed_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('weather_observations');
    }

    public function down()
    {
        $this->forge->dropTable('weather_observations');
    }
}
