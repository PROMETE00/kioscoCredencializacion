<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKioscoTables extends Migration
{
    public function up()
    {
        // 1. Catálogo de Etapas
        $this->forge->addField([
            'id_etapa'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'codigo'     => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'nombre'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id_etapa', true);
        $this->forge->createTable('cat_etapas');

        // 2. Catálogo de Estatus de Turno
        $this->forge->addField([
            'id_estatus' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'codigo'     => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'nombre'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id_estatus', true);
        $this->forge->createTable('cat_estatus_turno');

        // 3. Tabla de Archivos (Fotos, Firmas, Huellas)
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tipo'         => ['type' => 'ENUM("foto", "firma", "huella")', 'default' => 'foto'],
            'ruta'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'sha256'       => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'mime'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'tamano_bytes' => ['type' => 'BIGINT', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('archivos');

        // 4. Alumnos
        $this->forge->addField([
            'id_alumno'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'numero_control'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'numero_ficha'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'nombre_completo'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'carrera_clave'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'carrera_nombre'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'foto_archivo_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'firma_archivo_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'huella_archivo_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id_alumno', true);
        $this->forge->addForeignKey('foto_archivo_id', 'archivos', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('firma_archivo_id', 'archivos', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('huella_archivo_id', 'archivos', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('alumnos');

        // 5. Turnos
        $this->forge->addField([
            'id_turno'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'alumno_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'folio'            => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'qr_token_hash'    => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'etapa_actual_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'estatus_turno_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'es_activo'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'fecha_expira'     => ['type' => 'DATETIME', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id_turno', true);
        $this->forge->addForeignKey('alumno_id', 'alumnos', 'id_alumno', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('etapa_actual_id', 'cat_etapas', 'id_etapa', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('estatus_turno_id', 'cat_estatus_turno', 'id_estatus', 'SET NULL', 'SET NULL');
        $this->forge->createTable('turnos');

        // 6. Eventos de Turno (Historial)
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'turno_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tipo_evento'         => ['type' => 'VARCHAR', 'constraint' => 50],
            'etapa_anterior_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'etapa_nueva_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'estatus_anterior_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'estatus_nuevo_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'usuario_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'detalle_json'        => ['type' => 'TEXT', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('turno_id', 'turnos', 'id_turno', 'CASCADE', 'CASCADE');
        $this->forge->createTable('turno_eventos');
    }

    public function down()
    {
        $this->forge->dropTable('turno_eventos', true);
        $this->forge->dropTable('turnos', true);
        $this->forge->dropTable('alumnos', true);
        $this->forge->dropTable('archivos', true);
        $this->forge->dropTable('cat_estatus_turno', true);
        $this->forge->dropTable('cat_etapas', true);
    }
}
