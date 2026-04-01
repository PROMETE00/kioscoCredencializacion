<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class KioscoSeeder extends Seeder
{
    public function run()
    {
        // 1. Catálogo de Etapas (Stages)
        $stages = [
            ['code' => 'TICKET_GENERATED',   'name' => 'Turno Generado', 'sort_order' => 1, 'is_terminal' => 0],
            ['code' => 'PHOTO_CAPTURED',     'name' => 'Fotografía Capturada', 'sort_order' => 2, 'is_terminal' => 0],
            ['code' => 'SIGNATURE_CAPTURED', 'name' => 'Firma Registrada', 'sort_order' => 3, 'is_terminal' => 0],
            ['code' => 'FINGER_CAPTURED',    'name' => 'Huella Capturada', 'sort_order' => 4, 'is_terminal' => 0],
            ['code' => 'COMPLETED',          'name' => 'Trámite Completado', 'sort_order' => 5, 'is_terminal' => 1],
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
            ['code' => 'IN_PROGRESS','name' => 'En Proceso'],
            ['code' => 'FINISHED',  'name' => 'Finalizado'],
            ['code' => 'CANCELLED', 'name' => 'Cancelado'],
        ];

        foreach ($statuses as $s) {
            $exists = $this->db->table('cat_ticket_status')->where('code', $s['code'])->get()->getRowArray();
            if (!$exists) {
                $s['created_at'] = date('Y-m-d H:i:s');
                $this->db->table('cat_ticket_status')->insert($s);
            }
        }

        // 3. Generación Masiva de Datos (Alumnos y Turnos)
        $firstNames = ['JUAN', 'MARIA', 'CARLOS', 'ANA', 'LUIS', 'CARMEN', 'JOSE', 'LAURA', 'PEDRO', 'SOFIA', 'MIGUEL', 'LUCIA', 'JESUS', 'ELENA', 'DAVID', 'MARTINA', 'DANIEL', 'PAULA', 'ALEJANDRO', 'VALERIA'];
        $lastNames = ['GARCIA', 'MARTINEZ', 'LOPEZ', 'GONZALEZ', 'PEREZ', 'RODRIGUEZ', 'SANCHEZ', 'FERNANDEZ', 'GOMEZ', 'MARTIN', 'RUIZ', 'DIAZ', 'HERNANDEZ', 'ALVAREZ', 'JIMENEZ', 'MORENO', 'MUÑOZ', 'ALONSO', 'ROMERO', 'NAVARRO'];
        $careers = [
            'INGENIERIA EN SISTEMAS COMPUTACIONALES',
            'INGENIERIA INDUSTRIAL',
            'LICENCIATURA EN ADMINISTRACION',
            'INGENIERIA CIVIL',
            'INGENIERIA ELECTRONICA',
            'ARQUITECTURA'
        ];

        $stageRows = $this->db->table('cat_stages')->get()->getResultArray();
        $statusRows = $this->db->table('cat_ticket_status')->get()->getResultArray();

        $getStageId = fn($code) => array_values(array_filter($stageRows, fn($s) => $s['code'] === $code))[0]['id'] ?? null;
        $getStatusId = fn($code) => array_values(array_filter($statusRows, fn($s) => $s['code'] === $code))[0]['id'] ?? null;

        $now = time();
        
        $this->db->transStart();

        for ($i = 1; $i <= 100; $i++) {
            $name = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)] . ' ' . $lastNames[array_rand($lastNames)];
            $controlNumber = '20' . str_pad((string)rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            $student = [
                'control_number' => $controlNumber,
                'full_name'      => $name,
                'major_name'     => $careers[array_rand($careers)],
                'created_at'     => date('Y-m-d H:i:s', $now - rand(0, 86400 * 30)), // Random within last 30 days
            ];

            $this->db->table('students')->insert($student);
            $studentId = $this->db->insertID();

            // 70% chance of having a ticket
            if (rand(1, 100) <= 70) {
                $ticketCreatedAt = strtotime($student['created_at']) + rand(60, 3600); // Ticket created shortly after student
                $isCompleted = rand(1, 100) <= 20; // 20% are completed
                $isActive = !$isCompleted;
                
                $stageCode = 'TICKET_GENERATED';
                $statusCode = 'WAITING';

                if ($isCompleted) {
                    $stageCode = 'COMPLETED';
                    $statusCode = 'FINISHED';
                } else {
                    // Random stage for active tickets
                    $r = rand(1, 100);
                    if ($r <= 25) { $stageCode = 'PHOTO_CAPTURED'; $statusCode = 'IN_PROGRESS'; }
                    elseif ($r <= 50) { $stageCode = 'SIGNATURE_CAPTURED'; $statusCode = 'IN_PROGRESS'; }
                    elseif ($r <= 75) { $stageCode = 'FINGER_CAPTURED'; $statusCode = 'IN_PROGRESS'; }
                }

                $ticket = [
                    'student_id'    => $studentId,
                    'folio'         => 'FOL-' . str_pad((string)$studentId, 8, '0', STR_PAD_LEFT),
                    'qr_token_hash' => hash('sha256', 'token_' . $studentId . '_' . rand(1000, 9999)),
                    'stage_id'      => $getStageId($stageCode),
                    'status_id'     => $getStatusId($statusCode),
                    'is_active'     => $isActive ? 1 : 0,
                    'expires_at'    => date('Y-m-d 23:59:59', $ticketCreatedAt),
                    'created_at'    => date('Y-m-d H:i:s', $ticketCreatedAt),
                    'updated_at'    => date('Y-m-d H:i:s', $ticketCreatedAt + rand(0, 3600)),
                ];

                $this->db->table('tickets')->insert($ticket);
            }
        }

        $this->db->transComplete();
    }
}
