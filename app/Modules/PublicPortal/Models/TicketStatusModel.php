<?php

namespace App\Modules\PublicPortal\Models;

use CodeIgniter\Model;

/**
 * Modelo para los catálogos de estatus de tickets.
 * Representa la tabla 'cat_ticket_status' en el nuevo esquema.
 */
class TicketStatusModel extends Model
{
    protected $table      = 'cat_ticket_status';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = ['code', 'name'];

    /**
     * Obtiene el ID del estatus inicial para un ticket.
     */
    public function getInitialStatusId(): ?int
    {
        $candidates = ['EN_COLA', 'EN_ESPERA', 'CREADO', 'ACTIVE'];

        foreach ($candidates as $c) {
            $row = $this->where('code', $c)->first();
            if ($row) {
                return (int) $row['id'];
            }
        }

        $row = $this->orderBy('id', 'ASC')->first();
        return $row ? (int) $row['id'] : null;
    }
}
