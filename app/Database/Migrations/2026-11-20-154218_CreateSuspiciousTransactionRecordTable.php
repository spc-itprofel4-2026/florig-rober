<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSuspiciousTransactionRecordTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'transaction_id' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'user_id' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'risk_score' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false],
            'alert_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'alert_reason' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('transaction_id');
        $this->forge->createTable('fraud_review_records');
    }

    public function down()
    {
        $this->forge->dropTable('fraud_review_records');
    }
}
