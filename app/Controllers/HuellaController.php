<?php

namespace App\Controllers;

use App\Models\HuellaModel;

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
     * Guardar huella (plantilla/base64/archivo/etc.).
     * Por ahora solo valida que venga alumnoId/turnoId, y deja el hook para tu implementación real.
     */
    public function guardar()
    {
        $model = new HuellaModel();

        $alumnoId = (int)($this->request->getPost('alumnoId') ?? 0);
        $turnoId  = (int)($this->request->getPost('turnoId') ?? 0);

        // Puede venir template/imagen desde tu servicio/SDK:
        $template = (string)($this->request->getPost('template') ?? '');
        $imageB64 = (string)($this->request->getPost('image') ?? '');

        if ($alumnoId <= 0 && $turnoId <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'  => false,
                'msg' => 'Falta alumnoId o turnoId',
            ]);
        }

        // TODO: aquí llamarías a $model->saveHuella(...)
        // Ejemplo:
        // $ok = $model->saveHuella($alumnoId, $turnoId, $template, $imageB64);

        return $this->response->setJSON([
            'ok'  => true,
            'msg' => 'Guardado pendiente de integrar con lector/SDK',
        ]);
    }
}