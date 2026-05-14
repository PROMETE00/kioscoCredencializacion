<?php

namespace App\Repositories;

class StudentRepository extends BaseRepository
{
    public function findByIdentifier(string $identifier): ?array
    {
        $table = $this->t('students');
        
        return $this->db->table($table)
            ->groupStart()
                ->where('control_number', $identifier)
                ->orWhere('registration_number', $identifier)
            ->groupEnd()
            ->get()
            ->getRowArray();
    }

    public function findById(int $id): ?array
    {
        return $this->db->table($this->t('students'))
            ->where('id', $id)
            ->get()
            ->getRowArray();
    }

    public function updateBiometric(int $studentId, string $type, ?int $fileId): bool
    {
        $field = $type . '_file_id'; // logical field matches physical for now
        
        return $this->db->table($this->t('students'))
            ->where('id', $studentId)
            ->update([
                $field       => $fileId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }
}
