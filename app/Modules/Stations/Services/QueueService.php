<?php

namespace App\Modules\Stations\Services;

use Config\Database;

/**
 * Service for managing the capture queues (Photo, Signature, Fingerprint).
 */
class QueueService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Base query builder for the capture queue.
     * 
     * @param string $type The type of capture ('photo', 'signature', 'fingerprint')
     * @return \CodeIgniter\Database\BaseBuilder
     */
    public function getBaseQueueBuilder(string $type)
    {
        $builder = $this->db->table('tickets t')
            ->select([
                's.id_student AS id',
                's.id_student AS student_id',
                't.id_ticket AS ticket_id',
                't.folio AS ticket_folio',
                's.full_name AS name',
                'COALESCE(NULLIF(s.control_number, ""), s.token_number) AS control_number',
                'COALESCE(NULLIF(s.career_name, ""), s.career_key) AS career',
                'NULL AS semester',
                'ts.name AS status',
                'cs.code AS stage_code',
                'cs.name AS stage',
                't.created_at AS created_at',
                's.photo_file_id',
                's.signature_file_id',
                's.fingerprint_file_id',
            ])
            ->join('students s', 's.id_student = t.student_id', 'inner')
            ->join('cat_stages cs', 'cs.id_stage = t.current_stage_id', 'left')
            ->join('cat_ticket_status ts', 'ts.id_status = t.ticket_status_id', 'left')
            ->where('t.is_active', 1)
            ->where('t.expires_at >=', date('Y-m-d H:i:s'));

        // Filtering based on the station type
        if ($type === 'photo') {
            $builder->join('ticket_events te_photo', "te_photo.ticket_id = t.id_ticket AND te_photo.event_type = 'photo_saved'", 'left')
                    ->where('te_photo.ticket_id IS NULL', null, false);
        } elseif ($type === 'signature') {
            // Must have photo, but no signature
            $builder->join('ticket_events te_photo', "te_photo.ticket_id = t.id_ticket AND te_photo.event_type = 'photo_saved'", 'inner')
                    ->join('ticket_events te_signature', "te_signature.ticket_id = t.id_ticket AND te_signature.event_type = 'signature_saved'", 'left')
                    ->where('te_signature.ticket_id IS NULL', null, false);
        } elseif ($type === 'fingerprint') {
            // Must have signature, but no fingerprint
            $builder->join('ticket_events te_signature', "te_signature.ticket_id = t.id_ticket AND te_signature.event_type = 'signature_saved'", 'inner')
                    ->join('ticket_events te_fingerprint', "te_fingerprint.ticket_id = t.id_ticket AND te_fingerprint.event_type = 'fingerprint_saved'", 'left')
                    ->where('te_fingerprint.ticket_id IS NULL', null, false);
        }

        // Exclude completed or cancelled statuses
        $builder->groupStart()
                ->where('ts.code IS NULL', null, false)
                ->orWhereNotIn('ts.code', ['expired', 'cancelled', 'finished', 'COMPLETED', 'REJECTED'])
            ->groupEnd();

        return $builder;
    }

    /**
     * Gets the full queue for a specific station type.
     */
    public function getQueue(string $type, int $limit = 100): array
    {
        return $this->getBaseQueueBuilder($type)
            ->orderBy('t.created_at', 'ASC')
            ->orderBy('t.id_ticket', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * Gets a specific ticket or the next pending one.
     */
    public function getCurrent(string $type, ?int $ticketId = null): ?array
    {
        $builder = $this->getBaseQueueBuilder($type);
        
        if ($ticketId) {
            $builder->where('t.id_ticket', $ticketId);
        }

        $row = $builder->orderBy('t.created_at', 'ASC')
                       ->orderBy('t.id_ticket', 'ASC')
                       ->get(1)
                       ->getRowArray();

        return $row ?: null;
    }

    /**
     * Gets student info by student ID within the context of the queue.
     */
    public function getByStudentId(string $type, int $studentId): ?array
    {
        $row = $this->getBaseQueueBuilder($type)
            ->where('s.id_student', $studentId)
            ->orderBy('t.created_at', 'ASC')
            ->get(1)
            ->getRowArray();

        return $row ?: null;
    }
}
