<?php

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;
use RuntimeException;

/**
 * Model for the Admin Dashboard, handling KPIs and student/ticket management.
 */
class DashboardModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /**
     * Gets students for the admin dashboard with optional search and limit.
     */
    public function getAdminStudents(string $q = '', int $limit = 8): array
    {
        $limit = max(1, min(25, $limit));
        $builder = $this->baseAdminBuilder();

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

        return array_map(fn (array $row) => $this->normalizeRow($row), $rows);
    }

    /**
     * Gets all available ticket status options.
     */
    public function getStatusOptions(): array
    {
        return $this->db->table('cat_ticket_status')
            ->select('id, code, name')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
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

        $totalStudents = $this->db->table('students')->countAllResults();

        $ticketsToday = $this->db->table('tickets')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        $photosToday = $this->db->table('files')
            ->where('type', 'photo')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        $signaturesToday = $this->db->table('files')
            ->where('type', 'signature')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        $fingerprintsToday = $this->db->table('files')
            ->where('type', 'fingerprint')
            ->where('created_at >=', $todayStart)
            ->where('created_at <=', $todayEnd)
            ->countAllResults();

        // Completed processes today (assuming fingerprint is the last step)
        $completedToday = $this->db->table('ticket_events')
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

        // Guardar en caché por 15 segundos
        $cache->save($cacheKey, $kpis, 15);

        return $kpis;
    }

    /**
     * Updates the status of a ticket and logs the event.
     */
    public function updateTicketStatus(int $ticketId, int $statusId): array
    {
        $ticket = $this->db->table('tickets')
            ->select('id, student_id, status_id, stage_id')
            ->where('id', $ticketId)
            ->get(1)
            ->getRowArray();

        if (!$ticket) {
            throw new RuntimeException('The selected ticket does not exist.');
        }

        $status = $this->db->table('cat_ticket_status')
            ->select('id')
            ->where('id', $statusId)
            ->get(1)
            ->getRowArray();

        if (!$status) {
            throw new RuntimeException('The selected status does not exist.');
        }

        $now = date('Y-m-d H:i:s');

        $this->db->transStart();

        $this->db->table('tickets')
            ->where('id', $ticketId)
            ->update([
                'status_id' => $statusId,
                'updated_at'       => $now,
            ]);

        if ((int) $ticket['status_id'] !== $statusId) {
            $this->db->table('ticket_events')->insert([
                'ticket_id'           => $ticketId,
                'event_type'          => 'status_updated_dashboard',
                'previous_stage_id'   => $ticket['stage_id'] ?? null,
                'new_stage_id'        => $ticket['stage_id'] ?? null,
                'previous_status_id'  => $ticket['status_id'] ?? null,
                'new_status_id'       => $statusId,
                'user_id'             => session('auth')['id'] ?? null,
                'details_json'        => json_encode(['origin' => 'dashboard'], JSON_UNESCAPED_UNICODE),
                'created_at'          => $now,
            ]);
        }

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            throw new RuntimeException('Could not update ticket status.');
        }

        return $this->getRowByStudentId((int) $ticket['student_id']) ?? [];
    }

    /**
     * Clears a biometric file from a student and updates their ticket stage.
     */
    public function clearBiometric(int $studentId, int $ticketId, string $type): array
    {
        $config = [
            'photo'       => ['field' => 'photo_file_id', 'event' => 'photo_saved'],
            'signature'   => ['field' => 'signature_file_id', 'event' => 'signature_saved'],
            'fingerprint' => ['field' => 'fingerprint_file_id', 'event' => 'fingerprint_saved'],
        ];

        if (!isset($config[$type])) {
            throw new RuntimeException('The requested biometric type is invalid.');
        }

        $row = $this->getRowByStudentId($studentId);

        if (!$row || (int) ($row['ticket_id'] ?? 0) !== $ticketId) {
            throw new RuntimeException('The selected student no longer has that ticket active.');
        }

        $field = $config[$type]['field'];
        $event = $config[$type]['event'];
        $now   = date('Y-m-d H:i:s');

        if (empty($row[$field])) {
            return $row;
        }

        $this->db->transStart();

        $this->db->table('students')
            ->where('id', $studentId)
            ->update([
                $field       => null,
                'updated_at' => $now,
            ]);

        $this->db->table('ticket_events')
            ->where('ticket_id', $ticketId)
            ->where('event_type', $event)
            ->delete();

        $updatedArtifacts = [
            'photo'       => $type === 'photo' ? false : !empty($row['photo_file_id']),
            'signature'   => $type === 'signature' ? false : !empty($row['signature_file_id']),
            'fingerprint' => $type === 'fingerprint' ? false : !empty($row['fingerprint_file_id']),
        ];

        $stageId = $this->resolveStageIdFromArtifacts($updatedArtifacts);

        $ticketUpdate = ['updated_at' => $now];
        if ($stageId !== null) {
            $ticketUpdate['stage_id'] = $stageId;
        }

        $this->db->table('tickets')
            ->where('id', $ticketId)
            ->update($ticketUpdate);

        $this->db->table('ticket_events')->insert([
            'ticket_id'           => $ticketId,
            'event_type'          => $type . '_cleared_dashboard',
            'previous_stage_id'   => $row['stage_id'] ?? null,
            'new_stage_id'        => $stageId,
            'previous_status_id'  => $row['status_id'] ?? null,
            'new_status_id'       => $row['status_id'] ?? null,
            'user_id'             => session('auth')['id'] ?? null,
            'details_json'        => json_encode(['origin' => 'dashboard'], JSON_UNESCAPED_UNICODE),
            'created_at'          => $now,
        ]);

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            throw new RuntimeException('Could not clear the selected biometric.');
        }

        return $this->getRowByStudentId($studentId) ?? [];
    }

    private function baseAdminBuilder()
    {
        return $this->db->table('students s')
            ->select([
                's.id AS student_id',
                's.control_number',
                's.registration_number',
                's.full_name AS name',
                'COALESCE(NULLIF(s.major_name, ""), s.major_code, "—") AS career',
                '"OAXACA" AS campus',
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
            ->join('tickets t', 't.student_id = s.id AND t.is_active = 1', 'left')
            ->join('cat_ticket_status ts', 'ts.id = t.status_id', 'left')
            ->join('cat_stages cs', 'cs.id = t.stage_id', 'left');
    }

    private function getRowByStudentId(int $studentId): ?array
    {
        $row = $this->baseAdminBuilder()
            ->where('s.id', $studentId)
            ->orderBy('CASE WHEN t.id IS NULL THEN 1 ELSE 0 END', '', false)
            ->orderBy('COALESCE(t.updated_at, s.updated_at, s.created_at)', 'DESC', false)
            ->get(1)
            ->getRowArray();

        return $row ? $this->normalizeRow($row) : null;
    }

    private function normalizeRow(array $row): array
    {
        $row['identifier']  = $row['control_number'] ?: ($row['registration_number'] ?: '—');
        $row['status_name'] = $row['status_name'] ?: 'No ticket';
        $row['stage_name']  = $row['stage_name'] ?: 'No ticket';
        $row['updated_at']  = $row['ticket_updated_at'] ?: $row['student_updated_at'];
        $row['has_photo']       = !empty($row['photo_file_id']);
        $row['has_signature']   = !empty($row['signature_file_id']);
        $row['has_fingerprint'] = !empty($row['fingerprint_file_id']);

        return $row;
    }

    private function resolveStageIdFromArtifacts(array $artifacts): ?int
    {
        $code = 'TICKET_GENERATED';

        if (!empty($artifacts['fingerprint'])) {
            $code = 'FINGER_CAPTURED';
        } elseif (!empty($artifacts['signature'])) {
            $code = 'SIGNATURE_CAPTURED';
        } elseif (!empty($artifacts['photo'])) {
            $code = 'PHOTO_CAPTURED';
        }

        $row = $this->db->table('cat_stages')
            ->select('id')
            ->where('code', $code)
            ->get(1)
            ->getRowArray();

        return $row ? (int) $row['id'] : null;
    }
}
