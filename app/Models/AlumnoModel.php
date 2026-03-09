<?php

namespace App\Models;

use CodeIgniter\Model;

class AlumnoModel extends Model
{
    protected $table      = 'alumnos';
    protected $primaryKey = 'id_alumno';
    protected $returnType = 'array';

    protected $allowedFields = [
        'numero_control',
        'numero_ficha',
        'nombre_completo',
        'carrera_clave',
        'carrera_nombre',
        'foto_archivo_id',
        'firma_archivo_id',
        'huella_archivo_id',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'creado_at';
    protected $updatedField  = 'updated_at';
}