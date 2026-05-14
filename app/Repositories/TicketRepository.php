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
}
