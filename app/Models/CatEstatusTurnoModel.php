<?php

namespace App\Models;

use CodeIgniter\Model;

class CatEstatusTurnoModel extends Model
{
    protected $table      = 'cat_estatus_turno';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = ['codigo','nombre'];

    public function estatusInicialId(): ?int
    {
        // intenta por códigos típicos; si no existen, agarra el primero
        $candidatos = ['EN_COLA', 'EN_ESPERA', 'CREADO'];

        foreach ($candidatos as $c) {
            $row = $this->where('codigo', $c)->first();
            if ($row) return (int)$row['id'];
        }

        $row = $this->orderBy('id', 'ASC')->first();
        return $row ? (int)$row['id'] : null;
    }
}