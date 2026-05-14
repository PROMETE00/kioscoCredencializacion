<?php

namespace App\Repositories;

class FileRepository extends BaseRepository
{
    public function create(array $data): int
    {
        $this->db->table($this->t('files'))->insert($data);
        return (int) $this->db->insertID();
    }

    public function delete(int $id): bool
    {
        return $this->db->table($this->t('files'))->delete(['id' => $id]);
    }

    public function findById(int $id): ?array
    {
        return $this->db->table($this->t('files'))
            ->where('id', $id)
            ->get()
            ->getRowArray();
    }
}
