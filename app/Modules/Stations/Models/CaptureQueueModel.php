<?php

namespace App\Modules\Stations\Models;

use CodeIgniter\Model;
use App\Modules\Stations\Services\QueueService;
use RuntimeException;

/**
 * Model for handling photo captures and their associated queue.
 */
class CaptureQueueModel extends Model
{
    protected $db;
    private $queueService;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
        $this->queueService = new QueueService();
    }

    public function getQueue(int $limit = 100): array
    {
        return $this->queueService->getQueue('photo', $limit);
    }

    public function getCurrent(?int $ticketId = null): ?array
    {
        return $this->queueService->getCurrent('photo', $ticketId);
    }

    public function getByStudentId(int $studentId): ?array
    {
        return $this->queueService->getByStudentId('photo', $studentId);
    }

    /**
     * Marks a photo as captured, saves the file record and updates the student and ticket.
     */
    public function markCaptured(int $studentId, int $ticketId, string $relativePath, string $mime, int $size, string $sha256): array
    {
        $current = $this->getCurrent($ticketId);

        if (!$current || $current['student_id'] != $studentId) {
            throw new RuntimeException('El turno seleccionado ya no está disponible para captura de foto.');
        }

        $now = date('Y-m-d H:i:s');
        $this->db->transStart();

        $this->db->table('files')->insert([
            'type'       => 'photo',
            'path'       => $relativePath,
            'sha256'     => $sha256,
            'mime'       => $mime,
            'size_bytes' => $size,
            'created_at' => $now,
        ]);

        $fileId = (int) $this->db->insertID();

        $this->db->table('students')
            ->where('id', $studentId)
            ->update([
                'photo_file_id' => $fileId,
                'updated_at'    => $now,
            ]);

        $currentTicket = $this->db->table('tickets')
            ->select('stage_id, status_id')
            ->where('id', $ticketId)
            ->get()
            ->getRowArray();

        $nextStageId = $this->getStageIdByCode('PHOTO_CAPTURED') ?? $this->getStageIdByCode('photo_saved') ?? $this->getStageIdByCode('captured');
        $statusId = $this->getStatusIdByCode('WAITING') ?? $this->getStatusIdByCode('active') ?? $this->getStatusIdByCode('IN_PROGRESS');

        $ticketUpdate = ['updated_at' => $now];
        if ($nextStageId) {
            $ticketUpdate['stage_id'] = $nextStageId;
        }
        if ($statusId) {
            $ticketUpdate['status_id'] = $statusId;
        }

        $this->db->table('tickets')
            ->where('id', $ticketId)
            ->update($ticketUpdate);

        $this->db->table('ticket_events')->insert([
            'ticket_id'          => $ticketId,
            'event_type'         => 'photo_saved',
            'previous_stage_id'  => $currentTicket['stage_id'] ?? null,
            'new_stage_id'       => $nextStageId,
            'previous_status_id' => $currentTicket['status_id'] ?? null,
            'new_status_id'      => $statusId,
            'user_id'            => session('auth')['id'] ?? null,
            'details_json'       => json_encode(['file_id' => $fileId, 'path' => $relativePath], JSON_UNESCAPED_UNICODE),
            'created_at'         => $now,
        ]);

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            throw new RuntimeException('No fue posible guardar la fotografia en la base de datos.');
        }

        return [
            'file_id' => $fileId,
            'path'    => $relativePath,
        ];
    }

    private function getStageIdByCode(string $code): ?int
    {
        $row = $this->db->table('cat_stages')
            ->select('id')
            ->where('code', $code)
            ->get(1)->getRowArray();

        return $row ? (int)$row['id'] : null;
    }

    private function getStatusIdByCode(string $code): ?int
    {
        $row = $this->db->table('cat_ticket_status')
            ->select('id')
            ->where('code', $code)
            ->get(1)->getRowArray();

        return $row ? (int)$row['id'] : null;
    }
}
