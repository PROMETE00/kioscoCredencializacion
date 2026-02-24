<?php

namespace App\Models;

use CodeIgniter\Model;

class HuellaModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /**
     * Igual que Foto/Firma: devuelve alumno + turno (si aplica)
     * keys esperadas:
     * id, turno_id, nombre, no_control, carrera, semestre, estatus
     */
    public function getCurrentByTurno(int $turnoId): ?array
    {
        // TODO: reemplaza por tu query real
        return null;
    }

    public function getNextPending(): ?array
    {
        // TODO: reemplaza por tu query real
        return null;
    }

    public function getQueue(): array
    {
        // TODO: reemplaza por tu query real
        return [];
    }

    public function getByAlumnoId(int $alumnoId): ?array
    {
        // TODO: reemplaza por tu query real
        return null;
    }

    // public function saveHuella(int $alumnoId, int $turnoId, string $template, string $imageB64): bool
    // {
    //     // TODO: inserta/actualiza huella en tu tabla
    // }
}