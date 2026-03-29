<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class KioscoSeeder extends Seeder
{
    public function run()
    {
        // 1. Catálogo de Etapas (Stages)
        $stages = [
            ['code' => 'TICKET_GENERATED',   'name' => 'Turno Generado'],
            ['code' => 'PHOTO_CAPTURED',     'name' => 'Fotografía Capturada'],
            ['code' => 'SIGNATURE_CAPTURED', 'name' => 'Firma Registrada'],
            ['code' => 'FINGER_CAPTURED',    'name' => 'Huella Capturada'],
            ['code' => 'COMPLETED',          'name' => 'Trámite Completado'],
        ];

        foreach ($stages as $e) {
            $exists = $this->db->table('cat_stages')->where('code', $e['code'])->get()->getRowArray();
            if (!$exists) {
                $e['created_at'] = date('Y-m-d H:i:s');
                $this->db->table('cat_stages')->insert($e);
            }
        }

        // 2. Catálogo de Estatus (Status)
        $statuses = [
            ['code' => 'WAITING',   'name' => 'En Espera'],
            ['code' => 'IN_PROCESS','name' => 'En Proceso'],
            ['code' => 'FINISHED',  'name' => 'Finalizado'],
            ['code' => 'CANCELED',  'name' => 'Cancelado'],
        ];

        foreach ($statuses as $s) {
            $exists = $this->db->table('cat_ticket_status')->where('code', $s['code'])->get()->getRowArray();
            if (!$exists) {
                $s['created_at'] = date('Y-m-d H:i:s');
                $this->db->table('cat_ticket_status')->insert($s);
            }
        }

        // 3. Datos de prueba: Alumnos y Turnos (Students and Tickets)
        $students = [
            [
                'control_number' => '20160001',
                'full_name'      => 'JUAN PEREZ LOPEZ',
                'career_name'    => 'INGENIERIA EN SISTEMAS COMPUTACIONALES',
            ],
            [
                'control_number' => '20160002',
                'full_name'      => 'MARIA GARCIA HERNANDEZ',
                'career_name'    => 'INGENIERIA INDUSTRIAL',
            ],
        ];

        $stageRow = $this->db->table('cat_stages')->where('code', 'TICKET_GENERATED')->get()->getRowArray();
        $stageId = $stageRow ? $stageRow['id_stage'] : null;

        $statusRow = $this->db->table('cat_ticket_status')->where('code', 'WAITING')->get()->getRowArray();
        $statusId = $statusRow ? $statusRow['id_status'] : null;

        foreach ($students as $a) {
            $exists = $this->db->table('students')->where('control_number', $a['control_number'])->get()->getRowArray();
            if (!$exists) {
                $a['created_at'] = date('Y-m-d H:i:s');
                $this->db->table('students')->insert($a);
                $studentId = $this->db->insertID();

                // Generate ticket for this student
                $this->db->table('tickets')->insert([
                    'student_id'       => $studentId,
                    'folio'            => 'T-' . str_pad($studentId, 4, '0', STR_PAD_LEFT),
                    'current_stage_id' => $stageId,
                    'ticket_status_id' => $statusId,
                    'is_active'        => 1,
                    'expires_at'       => date('Y-m-d 23:59:59'),
                    'created_at'       => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
