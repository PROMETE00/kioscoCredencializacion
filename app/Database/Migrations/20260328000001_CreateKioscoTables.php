<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKioscoTables extends Migration
{
    public function up()
    {
        // 0. Roles
        $this->forge->addField([
            'id_role'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code'       => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id_role', true);
        $this->forge->createTable('roles');

        // 0.1 Users
        $this->forge->addField([
            'id_user'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'username'      => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'full_name'     => ['type' => 'VARCHAR', 'constraint' => 150],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'role_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id_user', true);
        $this->forge->addForeignKey('role_id', 'roles', 'id_role', 'SET NULL', 'SET NULL');
        $this->forge->createTable('users');

        // 1. Catálogo de Etapas (Stages)
        $this->forge->addField([
            'id_stage'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code'       => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id_stage', true);
        $this->forge->createTable('cat_stages');

        // 2. Catálogo de Estatus de Turno (Ticket Status)
        $this->forge->addField([
            'id_status'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code'       => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id_status', true);
        $this->forge->createTable('cat_ticket_status');

        // 3. Tabla de Archivos / Biometría (Files)
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'type'         => ['type' => 'ENUM("photo", "signature", "fingerprint")', 'default' => 'photo'],
            'path'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'sha256'       => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'mime'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'size_bytes'   => ['type' => 'BIGINT', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('files');

        // 4. Alumnos (Students)
        $this->forge->addField([
            'id_student'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'control_number'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'token_number'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true], // numero_ficha
            'full_name'             => ['type' => 'VARCHAR', 'constraint' => 255],
            'career_key'            => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'career_name'           => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'photo_file_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'signature_file_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'fingerprint_file_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id_student', true);
        $this->forge->addForeignKey('photo_file_id', 'files', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('signature_file_id', 'files', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('fingerprint_file_id', 'files', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('students');

        // 5. Turnos (Tickets)
        $this->forge->addField([
            'id_ticket'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'student_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'folio'            => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'qr_token_hash'    => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'current_stage_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'ticket_status_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'is_active'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'expires_at'       => ['type' => 'DATETIME', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id_ticket', true);
        $this->forge->addForeignKey('student_id', 'students', 'id_student', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('current_stage_id', 'cat_stages', 'id_stage', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('ticket_status_id', 'cat_ticket_status', 'id_status', 'SET NULL', 'SET NULL');
        $this->forge->createTable('tickets');

        // 6. Eventos de Turno (Ticket Events / History)
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ticket_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'event_type'          => ['type' => 'VARCHAR', 'constraint' => 50],
            'previous_stage_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'new_stage_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'previous_status_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'new_status_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'user_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true], // Refers to the operator
            'details_json'        => ['type' => 'TEXT', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('ticket_id', 'tickets', 'id_ticket', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ticket_events');
    }

    public function down()
    {
        $this->forge->dropTable('ticket_events', true);
        $this->forge->dropTable('tickets', true);
        $this->forge->dropTable('students', true);
        $this->forge->dropTable('files', true);
        $this->forge->dropTable('cat_ticket_status', true);
        $this->forge->dropTable('cat_stages', true);
        $this->forge->dropTable('users', true);
        $this->forge->dropTable('roles', true);
    }
}
