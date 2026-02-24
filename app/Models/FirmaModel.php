<?php

namespace App\Models;

use CodeIgniter\Model;

class FirmaModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /**
     * Debe regresar:
     * [
     *  'id' => alumno_id,
     *  'turno_id' => turno_id,
     *  'nombre' => '',
     *  'no_control' => '',
     *  'carrera' => '',
     *  'semestre' => '',
     *  'estatus' => '',
     * ]
     */
    public function getCurrentByTurno(int $turnoId): ?array
    {
        // TODO: remplaza por tu query real
        // Ejemplo (placeholder):
        // return $this->db->query("SELECT ... WHERE turno_id = ?", [$turnoId])->getRowArray();

        return null;
    }

    /**
     * Regresa el siguiente alumno pendiente de firma (por fecha, prioridad, etc.)
     */
    public function getNextPending(): ?array
    {
        // TODO: remplaza por tu query real
        return null;
    }

    /**
     * Cola completa de pendientes (para panel derecho).
     * Cada item mínimo: id, turno_id, nombre, no_control
     */
    public function getQueue(): array
    {
        // TODO: remplaza por tu query real
        return [];
    }
}