<?php

namespace App\Services;

use App\Repositories\StudentRepository;
use App\Repositories\TicketRepository;
use RuntimeException;

class TicketService extends BaseService
{
    protected StudentRepository $studentRepo;
    protected TicketRepository $ticketRepo;

    public function __construct()
    {
        $this->studentRepo = new StudentRepository();
        $this->ticketRepo = new TicketRepository();
    }

    public function createTicket(int $studentId): array
    {
        $existing = $this->ticketRepo->findActiveByStudentId($studentId);
        if ($existing) {
            return $existing;
        }

        $stageId = $this->ticketRepo->getStageIdByCode('TICKET_GENERATED');
        $statusId = $this->ticketRepo->getStatusIdByCode('WAITING');

        if (!$stageId || !$statusId) {
            throw new RuntimeException('Initial catalogs (TICKET_GENERATED / WAITING) not found.');
        }

        $now = date('Y-m-d H:i:s');
        $expires = date('Y-m-d 23:59:59');

        $ticketId = $this->ticketRepo->create([
            'folio'         => 'PEND', // Will be updated by a proper folio generator
            'student_id'    => $studentId,
            'status_id'     => $statusId,
            'stage_id'      => $stageId,
            'is_active'     => 1,
            'expires_at'    => $expires,
            'qr_token_hash' => hash('sha256', bin2hex(random_bytes(16))),
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        // Generate folio (simple implementation for now)
        $folio = 'FOL-' . str_pad((string)$ticketId, 8, '0', STR_PAD_LEFT);
        $this->ticketRepo->update($ticketId, ['folio' => $folio]);

        $this->ticketRepo->logEvent([
            'ticket_id'          => $ticketId,
            'event_type'         => 'ticket_created',
            'new_stage_id'       => $stageId,
            'new_status_id'      => $statusId,
            'created_at'         => $now,
        ]);

        $ticket = $this->ticketRepo->findActiveByStudentId($studentId);
        return $ticket ?: [];
    }

    public function findStudentByIdentifier(string $identifier): ?array
    {
        return $this->studentRepo->findByIdentifier($identifier);
    }
}
