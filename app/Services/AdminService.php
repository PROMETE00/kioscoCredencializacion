<?php

namespace App\Services;

use App\Models\TurnoEventoModel;
use Config\Schema;
use RuntimeException;

class AdminService extends BaseService
{
    protected Schema $schema;

    public function __construct()
    {
        parent::__construct();
        $this->schema = new Schema();
    }

    /**
     * Calculates Key Performance Indicators for today.
     */
    public function getKpis(): array
    {
        $cacheKey = 'dashboard_kpis_today';
        $cache = \Config\Services::cache();

        if ($cachedData = $cache->get($cacheKey)) {
            return $cachedData;
        }

        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd   = date('Y-m-d 23:59:59');

        $tables = $this->schema->tables;

        $totalStudents = $this->db->table($tables['students'])->countAllResults();

        $ticketsToday = $this->db->table($tables['tickets'])
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        $photosToday = $this->db->table($tables['files'])
            ->where('type', 'photo')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        $signaturesToday = $this->db->table($tables['files'])
            ->where('type', 'signature')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        $fingerprintsToday = $this->db->table($tables['files'])
            ->where('type', 'fingerprint')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        $completedToday = $this->db->table($tables['ticket_events'])
            ->where('event_type', 'fingerprint_saved')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        $kpis = [
            'total_students'      => $totalStudents,
            'tickets_today'       => $ticketsToday,
            'photos_today'        => $photosToday,
            'signatures_today'    => $signaturesToday,
            'fingerprints_today'  => $fingerprintsToday,
            'completed_today'     => $completedToday,
        ];

        $cache->save($cacheKey, $kpis, 15);

        return $kpis;
    }

    /**
     * Gets students for the worklist with optional search and limit.
     */
    public function getWorklist(string $q = '', int $limit = 8): array
    {
        $limit = max(1, min(50, $limit));
        $tables = $this->schema->tables;

        $builder = $this->db->table($tables['students'] . ' s')
            ->select([
                's.id AS student_id',
                's.control_number',
                's.registration_number',
                's.full_name AS name',
                'COALESCE(NULLIF(s.major_name, ""), s.major_code, "—") AS career',
                's.photo_file_id',
                's.signature_file_id',
                's.fingerprint_file_id',
                's.updated_at AS student_updated_at',
                't.id AS ticket_id',
                't.folio',
                't.expires_at',
                't.updated_at AS ticket_updated_at',
                'ts.id AS status_id',
                'ts.code AS status_code',
                'ts.name AS status_name',
                'cs.id AS stage_id',
                'cs.code AS stage_code',
                'cs.name AS stage_name',
            ])
            ->join($tables['tickets'] . ' t', 't.student_id = s.id AND t.is_active = 1', 'left')
            ->join($tables['cat_ticket_status'] . ' ts', 'ts.id = t.status_id', 'left')
            ->join($tables['cat_stages'] . ' cs', 'cs.id = t.stage_id', 'left');

        if ($q !== '') {
            $builder->groupStart()
                ->like('s.control_number', $q)
                ->orLike('s.registration_number', $q)
                ->orLike('s.full_name', $q)
                ->orLike('s.major_name', $q)
                ->orLike('t.folio', $q)
            ->groupEnd();
        }

        $rows = $builder
            ->orderBy('CASE WHEN t.id IS NULL THEN 1 ELSE 0 END', '', false)
            ->orderBy('COALESCE(t.updated_at, s.updated_at, s.created_at)', 'DESC', false)
            ->orderBy('s.id', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        return array_map(function (array $row) {
            $row['identifier']  = $row['control_number'] ?: ($row['registration_number'] ?: '—');
            $row['status_name'] = $row['status_name'] ?: 'No ticket';
            $row['stage_name']  = $row['stage_name'] ?: 'No ticket';
            $row['updated_at']  = $row['ticket_updated_at'] ?: $row['student_updated_at'];
            $row['has_photo']       = !empty($row['photo_file_id']);
            $row['has_signature']   = !empty($row['signature_file_id']);
            $row['has_fingerprint'] = !empty($row['fingerprint_file_id']);
            return $row;
        }, $rows);
    }

    /**
     * Gets all available ticket status options.
     */
    public function getStatusOptions(): array
    {
        return $this->db->table($this->schema->tables['cat_ticket_status'])
            ->select('id, code, name')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Updates the status of a ticket and logs the event.
     */
    public function updateTicketStatus(int $ticketId, int $statusId): array
    {
        $tables = $this->schema->tables;

        $ticket = $this->db->table($tables['tickets'])
            ->select('id, student_id, status_id, stage_id')
            ->where('id', $ticketId)
            ->get(1)
            ->getRowArray();

        if (!$ticket) {
            throw new RuntimeException('The selected ticket does not exist.');
        }

        $now = date('Y-m-d H:i:s');

        $this->db->transStart();

        $this->db->table($tables['tickets'])
            ->where('id', $ticketId)
            ->update([
                'status_id'  => $statusId,
                'updated_at' => $now,
            ]);

        if ((int) $ticket['status_id'] !== $statusId) {
            $this->db->table($tables['ticket_events'])->insert([
                'ticket_id'           => $ticketId,
                'event_type'          => 'status_updated_admin',
                'previous_stage_id'   => $ticket['stage_id'] ?? null,
                'new_stage_id'        => $ticket['stage_id'] ?? null,
                'previous_status_id'  => $ticket['status_id'] ?? null,
                'new_status_id'       => $statusId,
                'user_id'             => session('auth')['id'] ?? null,
                'details_json'        => json_encode(['origin' => 'admin_service'], JSON_UNESCAPED_UNICODE),
                'created_at'          => $now,
            ]);
        }

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            throw new RuntimeException('Could not update ticket status.');
        }

        return $this->getWorklistByStudentId((int) $ticket['student_id']);
    }

    /**
     * Clears a biometric file from a student and updates their ticket stage.
     */
    public function clearBiometric(int $studentId, int $ticketId, string $type): array
    {
        $tables = $this->schema->tables;
        $config = [
            'photo'       => ['field' => 'photo_file_id', 'event' => 'photo_saved'],
            'signature'   => ['field' => 'signature_file_id', 'event' => 'signature_saved'],
            'fingerprint' => ['field' => 'fingerprint_file_id', 'event' => 'fingerprint_saved'],
        ];

        if (!isset($config[$type])) {
            throw new RuntimeException('Invalid biometric type.');
        }

        $student = $this->db->table($tables['students'])
            ->where('id', $studentId)
            ->get(1)
            ->getRowArray();

        if (!$student) {
            throw new RuntimeException('Student not found.');
        }

        $field = $config[$type]['field'];
        $event = $config[$type]['event'];
        $now   = date('Y-m-d H:i:s');

        $this->db->transStart();

        // Nullify the field in students table
        $this->db->table($tables['students'])
            ->where('id', $studentId)
            ->update([
                $field       => null,
                'updated_at' => $now,
            ]);

        // Delete the original 'saved' event to allow re-capture
        $this->db->table($tables['ticket_events'])
            ->where('ticket_id', $ticketId)
            ->where('event_type', $event)
            ->delete();

        // Log the clearance
        $this->db->table($tables['ticket_events'])->insert([
            'ticket_id'           => $ticketId,
            'event_type'          => $type . '_cleared_admin',
            'user_id'             => session('auth')['id'] ?? null,
            'details_json'        => json_encode(['origin' => 'admin_service_clear'], JSON_UNESCAPED_UNICODE),
            'created_at'          => $now,
        ]);

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            throw new RuntimeException('Could not clear biometric.');
        }

        return $this->getWorklistByStudentId($studentId);
    }

    /**
     * Records that a credential was delivered.
     */
    public function recordDelivery(int $studentId, int $ticketId, string $signatureB64): array
    {
        $tables = $this->schema->tables;
        $now = date('Y-m-d H:i:s');

        $biometricService = new BiometricService();
        
        $this->db->transStart();

        // Save delivery signature as a special file type or just a log
        // For now, let's log it in ticket_events with the data
        $this->db->table($tables['ticket_events'])->insert([
            'ticket_id'    => $ticketId,
            'event_type'   => 'credential_delivered',
            'user_id'      => session('auth')['id'] ?? null,
            'details_json' => json_encode([
                'delivered_at' => $now,
                'has_signature' => !empty($signatureB64)
            ]),
            'created_at'   => $now,
        ]);

        // Update ticket status to something like 'DELIVERED' or 'COMPLETED'
        // We'll search for a status code 'DELIVERED'
        $status = $this->db->table($tables['cat_ticket_status'])
            ->where('code', 'DELIVERED')
            ->get(1)
            ->getRowArray();

        if ($status) {
            $this->db->table($tables['tickets'])
                ->where('id', $ticketId)
                ->update(['status_id' => $status['id'], 'updated_at' => $now]);
        }

        $this->db->transComplete();

        return $this->getWorklistByStudentId($studentId);
    }

    protected function getWorklistByStudentId(int $studentId): array
    {
        $list = $this->getWorklist('', 50);
        foreach ($list as $item) {
            if ((int)$item['student_id'] === $studentId) {
                return $item;
            }
        }
        return [];
    }
}
