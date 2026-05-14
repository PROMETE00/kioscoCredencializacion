<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHuellasCredencialesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'alumno_id'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'credential_id'   => ['type' => 'TEXT'],
            'public_key'      => ['type' => 'TEXT'],
            'sign_count'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('alumno_id');
        $this->forge->createTable('huellas_credenciales');
    }

    public function down()
    {
        $this->forge->dropTable('huellas_credenciales', true);
    }
}
