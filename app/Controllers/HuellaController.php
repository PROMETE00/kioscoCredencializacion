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
            'current'    => $current,
            'queue'      => $queue,
        ]);
    }

    public function cola()
    {
        $model = new HuellaModel();

        return $this->response->setJSON([
            'ok'    => true,
            'items' => $model->getQueue(),
        ]);
    }

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

    public function guardar()
    {
        $model = new HuellaModel();

        $alumnoId = (int)($this->request->getPost('alumnoId') ?? $this->request->getPost('student_id') ?? 0);
        $turnoId  = (int)($this->request->getPost('turnoId') ?? $this->request->getPost('turn_id') ?? 0);
        $template = (string)($this->request->getPost('template') ?? '');
        $imageB64 = (string)($this->request->getPost('image') ?? '');
        $quality  = (int)($this->request->getPost('quality') ?? 0);

        if ($alumnoId <= 0 || $turnoId <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'  => false,
                'msg' => 'Missing student or turn identifier.',
            ]);
        }

        if ($template === '' || $imageB64 === '' || !str_starts_with($imageB64, 'data:image/')) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'  => false,
                'msg' => 'The fingerprint payload is incomplete.',
            ]);
        }

        try {
            $result = $model->saveFingerprint($alumnoId, $turnoId, $template, $imageB64, $quality);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'  => false,
                'msg' => $e->getMessage(),
            ]);
        }

        return $this->response->setJSON([
            'ok'  => true,
            'msg' => 'Fingerprint saved successfully.',
            'url' => $result['url'],
            'queue' => $model->getQueue(),
            'current' => $model->getNextPending(),
        ]);
    }
}
