<?php

namespace App\Models;

use CodeIgniter\Model;

class TurnoModel extends Model
{
    protected $table      = 'turnos';
    protected $primaryKey = 'id_turno';
    protected $returnType = 'array';

    protected $allowedFields = [
        'folio',
        'alumno_id',
        'estatus_turno_id',
        'etapa_actual_id',
        'es_activo',
        'fecha_expira',
        'qr_token_hash',
        'llamado_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'creado_at';
    protected $updatedField  = 'updated_at';
}