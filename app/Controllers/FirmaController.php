<?php

namespace App\Controllers;

use App\Models\FirmaModel;

class FirmaController extends BaseController
{
    public function index($turnoId = 0)
    {
        $model = new FirmaModel();

        // 1) Alumno “en captura”: si viene turnoId, trae ese; si no, trae el siguiente pendiente
        $current = ($turnoId > 0)
            ? $model->getCurrentByTurno((int)$turnoId)
            : $model->getNextPending();

        // 2) Cola de pendientes (para el panel derecho)
        $queue = $model->getQueue();

        $data = [
            'title'      => 'Captura de Firma',
            'activeMenu' => 'firma',
            'userName'   => 'Usuario',

            // Para tu vista “tipo foto”:
            'current' => $current,   // null o array con nombre/no_control/carrera/semestre/estatus/id/turno_id
            'queue'   => $queue,     // array de alumnos en cola
        ];

        return view('captura/firma', $data);
    }
}