<?php

namespace App\Repositories;

class TicketRepository extends BaseRepository
{
    public function findActiveByStudentId(int $studentId): ?array
    {
        return $this->db->table($this->t('tickets'))
            ->where('student_id', $studentId)
            ->where('is_active', 1)
            ->get()
            ->getRowArray();
    }

    public function create(array $data): int
    {
        $this->db->table($this->t('tickets'))->insert($data);
        return (int) $this->db->insertID();
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->table($this->t('tickets'))
            ->where('id', $id)
            ->update($data);
    }

    public function logEvent(array $eventData): void
    {
        $this->db->table($this->t('ticket_events'))->insert($eventData);
    }

    public function getStageIdByCode(string $code): ?int
    {
        $row = $this->db->table($this->t('cat_stages'))
            ->where('code', $code)
            ->get()
            ->getRowArray();
        return $row ? (int) $row['id'] : null;
    }

    public function getStatusIdByCode(string $code): ?int
    {
        $row = $this->db->table($this->t('cat_ticket_status'))
            ->where('code', $code)
            ->get()
            ->getRowArray();
        return $row ? (int) $row['id'] : null;
    }

    public function findActiveWithDetails(int $studentId): ?array
    {
        $now = date('Y-m-d H:i:s');

        return $this->db->table($this->t('tickets') . ' t')
            ->select('
                t.id,
                t.folio,
                t.created_at,
                t.expires_at,
                e.name AS stage_name,
                s.name AS status_name
            ')
            ->join($this->t('cat_stages') . ' e', 'e.id = t.stage_id', 'left')
            ->join($this->t('cat_ticket_status') . ' s', 's.id = t.status_id', 'left')
            ->where('t.student_id', $studentId)
            ->where('t.is_active', 1)
            ->where('t.expires_at >=', $now)
            ->groupStart()
                ->where('s.code IS NULL', null, false)
                ->orWhereNotIn('s.code', ['EXPIRED', 'CANCELLED', 'FINISHED', 'COMPLETED', 'REJECTED', 'vencido', 'cancelado', 'finalizado', 'COMPLETADO', 'RECHAZADO'])
            ->groupEnd()
            ->orderBy('t.id', 'DESC')
            ->get()
            ->getRowArray();
    }

    public function getInitialStageAndStatus(): ?array
    {
        $stage = $this->db->table($this->t('cat_stages'))
            ->where('code', 'TICKET_GENERATED')
            ->orWhere('code', 'turno_generado')
            ->get()
            ->getRowArray();

        if (!$stage) {
            $stage = $this->db->table($this->t('cat_stages'))
                ->orderBy('sort_order', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()
                ->getRowArray();
        }

        $status = $this->db->table($this->t('cat_ticket_status'))
            ->where('code', 'WAITING')
            ->orWhere('code', 'EN_ESPERA')
            ->get()
            ->getRowArray();

        if (!$status) {
            $status = $this->db->table($this->t('cat_ticket_status'))
                ->groupStart()
                    ->where('code', 'WAITING')
                    ->orWhere('code', 'IN_PROGRESS')
                ->groupEnd()
                ->orderBy('id', 'ASC')
                ->get()
                ->getRowArray();
        }

        if (!$stage || !$status) {
            return null;
        }

        return [
            'stage_id'  => (int) $stage['id'],
            'status_id' => (int) $status['id'],
        ];
    }

    public function deactivateExpiredForStudent(int $studentId): void
    {
        $now = date('Y-m-d H:i:s');

        $this->db->query(
            'UPDATE tickets t
             LEFT JOIN cat_ticket_status s ON s.id = t.status_id
             LEFT JOIN cat_stages e ON e.id = t.stage_id
             SET t.is_active = NULL, t.updated_at = ?
             WHERE t.student_id = ?
               AND t.is_active = 1
               AND (
                    t.expires_at < ?
                    OR s.code IN ("EXPIRED", "CANCELLED", "FINISHED", "COMPLETED", "REJECTED", "vencido", "cancelado", "finalizado", "COMPLETADO", "RECHAZADO")
                    OR e.is_terminal = 1
               )',
            [$now, $studentId, $now]
        );
    }

    public function generateFolio(int $ticketId): string
    {
        $baseFolio = 'FOL-' . str_pad((string) $ticketId, 8, '0', STR_PAD_LEFT);

        $exists = $this->db->table($this->t('tickets'))
            ->where('folio', $baseFolio)
            ->where('id !=', $ticketId)
            ->countAllResults();

        if ($exists == 0) {
            return $baseFolio;
        }

        $timestamp = date('ymdHis');
        return 'FOL-' . $timestamp . '-' . str_pad((string) $ticketId, 4, '0', STR_PAD_LEFT);
    }

    public function findById(int $id): ?array
    {
        return $this->db->table($this->t('tickets'))
            ->where('id', $id)
            ->get()
            ->getRowArray();
    }
}
