<?php

namespace App\Controllers;

use App\Models\HuellaModel;
use RuntimeException;

class HuellaController extends BaseController
{
    public function index($turnoId = 0)
    {
        $model = new HuellaModel();

        // Si llega turnoId (ej: desde dashboard/cola), carga ese; si no, trae el siguiente pendiente
        $current = ($turnoId > 0)
            ? $model->getCurrentByTurno((int)$turnoId)
            : $model->getNextPending();

        $queue = $model->getQueue();

        return view('capture/huella', [
            'title'      => 'Captura de Huella',
            'activeMenu' => 'huella',
            'userName'   => 'Usuario',

            // Para el panel izquierdo (mismo shape que Foto/Firma)
            'current'    => $current,
            // Para el panel derecho
            'queue'      => $queue,
        ]);
    }

    /**
     * Devuelve la cola en JSON para pintar con JS.
     */
    public function cola()
    {
        $model = new HuellaModel();

        return $this->response->setJSON([
            'ok'    => true,
            'items' => $model->getQueue(),
        ]);
    }

    /**
     * Trae el alumno por turnoId o por alumnoId para rellenar panel izquierdo.
     * Soporta: /captura/huella/alumno?turnoId=123  o  ?alumnoId=5
     */
    public function alumno()
    {
        $turnoId  = (int)($this->request->getGet('turnoId') ?? 0);
        $alumnoId = (int)($this->request->getGet('alumnoId') ?? 0);

        $model = new HuellaModel();

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

    /**
     * Guardar huella (imagen base64).
     */
    public function guardar()
    {
        $model = new HuellaModel();

        $alumnoId = (int)($this->request->getPost('alumno_id') ?? 0);
        $turnoId  = (int)($this->request->getPost('turno_id') ?? 0);
        $huellaB64 = (string)($this->request->getPost('huella_b64') ?? '');

        if ($alumnoId <= 0 || $turnoId <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => 'Falta alumno_id o turno_id.',
            ]);
        }

        if ($huellaB64 === '' || !str_starts_with($huellaB64, 'data:image/')) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => 'La huella recibida no es válida.',
            ]);
        }

        try {
            $resultado = $model->saveHuella($alumnoId, $turnoId, $huellaB64);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->response->setJSON([
            'ok'       => true,
            'message'  => 'Huella guardada correctamente.',
            'url'      => $resultado['url'],
            'archivoId'=> $resultado['archivo_id'],
            'queue'    => $model->getQueue(),
            'current'  => $model->getNextPending(),
        ]);
    }
}
