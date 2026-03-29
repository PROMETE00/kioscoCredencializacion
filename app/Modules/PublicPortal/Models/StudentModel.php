<?php

namespace App\Modules\PublicPortal\Models;

use CodeIgniter\Model;

/**
 * Modelo para la gestión de alumnos.
 * Representa la tabla 'students' en el nuevo esquema.
 */
class StudentModel extends Model
{
    protected $table      = 'students';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'control_number',
        'registration_number',
        'full_name',
        'major_code',
        'major_name',
        'photo_file_id',
        'signature_file_id',
        'fingerprint_file_id',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
