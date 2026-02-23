<?php

namespace App\Models;

use CodeIgniter\Model;

class DashboardModel extends Model
{
public function getWorklist(string $stage = 'hoy', string $q = ''): array
{
    $db = \Config\Database::connect();
    $b  = $db->table('vw_dashboard_worklist');

    $b->select('no_control, curp, nombre, carrera, campus, foto, firma, imprime, updated_at, atendido_at');

    // "Atendidos hoy" = atendido_at hoy
    if ($stage === 'hoy') {
        $today = date('Y-m-d');
        $b->where('atendido_at >=', $today.' 00:00:00')
          ->where('atendido_at <=', $today.' 23:59:59');
    }

    // búsqueda digitito por digitito
    if ($q !== '') {
        $b->groupStart()
          ->like('no_control', $q)
          ->orLike('curp', $q)
          ->orLike('nombre', $q)
          ->groupEnd();
    }

    $b->orderBy('atendido_at', 'DESC');
    $b->limit(50);

    return $b->get()->getResultArray();
}

public function getKpis(): array
{
    $db = \Config\Database::connect();

    $today = date('Y-m-d');

    $atendidos = $db->table('vw_dashboard_worklist')
        ->where('atendido_at >=', $today.' 00:00:00')
        ->where('atendido_at <=', $today.' 23:59:59')
        ->countAllResults();

    return [
        'atendidos_hoy' => $atendidos,
    ];
}

    public function getStationsStatus(): array
    {
        // MOCK (puedes hacerlo real con heartbeat en BD o cache)
        return [
            [
                'key' => 'foto',
                'title' => 'Estación Foto (Webcam)',
                'status' => 'online',
                'hint' => 'Cámara detectada',
            ],
            [
                'key' => 'firma',
                'title' => 'Estación Firma',
                'status' => 'online',
                'hint' => 'Canvas OK',
            ],
            [
                'key' => 'imprimir',
                'title' => 'Estación Impresión',
                'status' => 'warning',
                'hint' => 'Revisar cola / papel',
            ],
        ];
    }
}