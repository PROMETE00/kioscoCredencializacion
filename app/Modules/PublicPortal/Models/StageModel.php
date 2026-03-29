<?php

namespace App\Modules\PublicPortal\Models;

use CodeIgniter\Model;

/**
 * Modelo para las etapas de los tickets.
 * Representa la tabla 'cat_stages' en el nuevo esquema.
 */
class StageModel extends Model
{
    protected $table      = 'cat_stages';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = ['code', 'name', 'sort_order', 'is_terminal'];

    /**
     * Obtiene el ID de la primera etapa disponible.
     */
    public function getFirstStageId(): ?int
    {
        $row = $this->orderBy('sort_order', 'ASC')->first();
        return $row ? (int) $row['id'] : null;
    }
}
