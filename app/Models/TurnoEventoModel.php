<?php

namespace App\Models;

use CodeIgniter\Model;

class TurnoEventoModel extends Model
{
    protected $table      = 'turno_eventos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'turno_id','tipo_evento',
        'etapa_anterior_id','etapa_nueva_id',
        'estatus_anterior_id','estatus_nuevo_id',
        'usuario_id','detalle_json',
        'created_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = ''; // no hay
}
