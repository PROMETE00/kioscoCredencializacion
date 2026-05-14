<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        $this->db->transStart();

        $students = $db->table('students')->orderBy('id', 'ASC')->limit(10)->get()->getResultArray();
        $tickets = $db->table('tickets')->orderBy('id', 'ASC')->limit(10)->get()->getResultArray();

        $eventTypes = [
            'ticket_created',
            'signature_saved',
            'photo_saved',
            'fingerprint_saved',
            'status_updated_admin',
        ];

        foreach ($tickets as $i => $ticket) {
            $baseTime = strtotime($ticket['created_at'] ?? $now);

            $db->table('ticket_events')->insert([
                'ticket_id'          => (int) $ticket['id'],
                'event_type'         => 'ticket_created',
                'previous_stage_id'  => null,
                'new_stage_id'       => (int) ($ticket['stage_id'] ?? 1),
                'previous_status_id' => null,
                'new_status_id'      => (int) ($ticket['status_id'] ?? 1),
                'user_id'            => null,
                'details_json'       => json_encode(['source' => 'self_service', 'folio' => $ticket['folio']], JSON_UNESCAPED_UNICODE),
                'created_at'         => date('Y-m-d H:i:s', $baseTime),
            ]);

            if ((int) ($ticket['stage_id'] ?? 0) >= 2) {
                $db->table('ticket_events')->insert([
                    'ticket_id'          => (int) $ticket['id'],
                    'event_type'         => 'photo_saved',
                    'previous_stage_id'  => 1,
                    'new_stage_id'       => 2,
                    'previous_status_id' => (int) ($ticket['status_id'] ?? 1),
                    'new_status_id'      => (int) ($ticket['status_id'] ?? 1),
                    'user_id'            => 4,
                    'details_json'       => json_encode(['station' => 'photo', 'operator' => 'photo'], JSON_UNESCAPED_UNICODE),
                    'created_at'         => date('Y-m-d H:i:s', $baseTime + 120),
                ]);
            }

            if ((int) ($ticket['stage_id'] ?? 0) >= 3) {
                $db->table('ticket_events')->insert([
                    'ticket_id'          => (int) $ticket['id'],
                    'event_type'         => 'signature_saved',
                    'previous_stage_id'  => 2,
                    'new_stage_id'       => 3,
                    'previous_status_id' => (int) ($ticket['status_id'] ?? 1),
                    'new_status_id'      => (int) ($ticket['status_id'] ?? 1),
                    'user_id'            => 5,
                    'details_json'       => json_encode(['station' => 'signature', 'operator' => 'signature'], JSON_UNESCAPED_UNICODE),
                    'created_at'         => date('Y-m-d H:i:s', $baseTime + 240),
                ]);
            }

            if ((int) ($ticket['stage_id'] ?? 0) >= 4) {
                $db->table('ticket_events')->insert([
                    'ticket_id'          => (int) $ticket['id'],
                    'event_type'         => 'fingerprint_saved',
                    'previous_stage_id'  => 3,
                    'new_stage_id'       => 4,
                    'previous_status_id' => (int) ($ticket['status_id'] ?? 1),
                    'new_status_id'      => 3,
                    'user_id'            => 6,
                    'details_json'       => json_encode(['station' => 'fingerprint', 'operator' => 'finger'], JSON_UNESCAPED_UNICODE),
                    'created_at'         => date('Y-m-d H:i:s', $baseTime + 360),
                ]);
            }

            if ((int) ($ticket['stage_id'] ?? 0) >= 5) {
                $db->table('ticket_events')->insert([
                    'ticket_id'          => (int) $ticket['id'],
                    'event_type'         => 'credential_delivered',
                    'previous_stage_id'  => 4,
                    'new_stage_id'       => 5,
                    'previous_status_id' => 3,
                    'new_status_id'      => 3,
                    'user_id'            => 1,
                    'details_json'       => json_encode(['delivered_by' => 'admin', 'has_signature' => true], JSON_UNESCAPED_UNICODE),
                    'created_at'         => date('Y-m-d H:i:s', $baseTime + 480),
                ]);
            }
        }

        $photoFiles = [
            ['type' => 'photo', 'path' => 'uploads/photos/photo_1_20260416_214216_78df2b.jpg', 'mime' => 'image/jpeg'],
            ['type' => 'photo', 'path' => 'uploads/photos/photo_3_20260416_214355_9ecb9f.jpg', 'mime' => 'image/jpeg'],
            ['type' => 'photo', 'path' => 'uploads/photos/photo_6_20260311_190512_93e468.png', 'mime' => 'image/png'],
        ];

        foreach ($photoFiles as $i => $file) {
            $dummyBinary = random_bytes(64);
            $db->table('files')->insert([
                'type'       => $file['type'],
                'path'       => $file['path'],
                'sha256'     => hash('sha256', $dummyBinary),
                'mime'       => $file['mime'],
                'size_bytes' => 64,
                'created_at' => date('Y-m-d H:i:s', strtotime($now) - rand(86400, 2592000)),
            ]);
            $fileId = (int) $db->insertID();

            $studentId = $i + 1;
            $db->table('students')
                ->where('id', $studentId)
                ->update(['photo_file_id' => $fileId, 'updated_at' => $now]);
        }

        $sigBinary = random_bytes(64);
        $db->table('files')->insert([
            'type'       => 'signature',
            'path'       => 'uploads/firmas/signature_1_20260416_214300_aabbccdd.png',
            'sha256'     => hash('sha256', $sigBinary),
            'mime'       => 'image/png',
            'size_bytes' => 64,
            'created_at' => date('Y-m-d H:i:s', strtotime($now) - 86400),
        ]);
        $sigFileId = (int) $db->insertID();
        $db->table('students')->where('id', 1)->update(['signature_file_id' => $sigFileId, 'updated_at' => $now]);

        $fpBinary = random_bytes(64);
        $db->table('files')->insert([
            'type'       => 'fingerprint',
            'path'       => 'uploads/huellas/fingerprint_1_20260416_214400_eeff0011.png',
            'sha256'     => hash('sha256', $fpBinary),
            'mime'       => 'image/png',
            'size_bytes' => 64,
            'created_at' => date('Y-m-d H:i:s', strtotime($now) - 3600),
        ]);
        $fpFileId = (int) $db->insertID();
        $db->table('students')->where('id', 1)->update(['fingerprint_file_id' => $fpFileId, 'updated_at' => $now]);

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            throw new \RuntimeException('TestDataSeeder failed');
        }
    }
}
