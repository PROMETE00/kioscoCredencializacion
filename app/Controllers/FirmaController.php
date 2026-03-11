<?php

namespace App\Controllers;

use App\Models\FirmaModel;
use RuntimeException;

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

    public function cola()
    {
        $model = new FirmaModel();

        return $this->response->setJSON([
            'ok'    => true,
            'items' => $model->getQueue(),
        ]);
    }

    public function alumno()
    {
        $turnoId  = (int) ($this->request->getGet('turnoId') ?? 0);
        $alumnoId = (int) ($this->request->getGet('alumnoId') ?? 0);

        $model = new FirmaModel();

        $row = null;
        if ($turnoId > 0) {
            $row = $model->getCurrentByTurno($turnoId);
        } elseif ($alumnoId > 0) {
            $row = $model->getByAlumnoId($alumnoId);
        }

        return $this->response->setJSON([
            'ok'     => true,
            'alumno' => $row,
        ]);
    }

    public function guardar()
    {
        $model = new FirmaModel();

        $alumnoId = (int) ($this->request->getPost('alumno_id') ?? 0);
        $turnoId  = (int) ($this->request->getPost('turno_id') ?? 0);
        $firmaPng = (string) ($this->request->getPost('firma_png') ?? '');

        if ($alumnoId <= 0 || $turnoId <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => 'Falta alumno_id o turno_id.',
            ]);
        }

        if ($firmaPng === '' || !str_starts_with($firmaPng, 'data:image/')) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => 'La firma recibida no es válida.',
            ]);
        }

        try {
            $resultado = $model->saveFirma($alumnoId, $turnoId, $firmaPng);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->response->setJSON([
            'ok'       => true,
            'message'  => 'Firma guardada correctamente.',
            'url'      => $resultado['url'],
            'archivoId'=> $resultado['archivo_id'],
            'queue'    => $model->getQueue(),
            'current'  => $model->getNextPending(),
        ]);
    }
}
