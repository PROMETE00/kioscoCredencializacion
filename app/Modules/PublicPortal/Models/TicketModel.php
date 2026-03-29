<?php

namespace App\Modules\PublicPortal\Models;

use CodeIgniter\Model;

/**
 * Modelo para la gestión de turnos/tickets.
 * Representa la tabla 'tickets' en el nuevo esquema.
 */
class TicketModel extends Model
{
    protected $table      = 'tickets';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'folio',
        'student_id',
        'status_id',
        'stage_id',
        'is_active',
        'expires_at',
        'qr_token_hash',
        'called_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
