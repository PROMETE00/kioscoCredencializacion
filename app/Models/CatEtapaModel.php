<?php

namespace App\Models;

use CodeIgniter\Model;

class CatEtapaModel extends Model
{
    protected $table      = 'cat_etapas';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = ['codigo','nombre','orden','es_terminal'];
    public function primeraEtapaId(): ?int
    {
        $row = $this->orderBy('orden', 'ASC')->first();
        return $row ? (int)$row['id'] : null;
    }
}