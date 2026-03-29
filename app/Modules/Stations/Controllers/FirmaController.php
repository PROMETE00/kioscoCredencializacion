<?php

namespace App\Modules\Stations\Controllers;

use App\Controllers\BaseController;
use App\Modules\Stations\Models\FirmaModel;
use RuntimeException;

/**
 * Controller for handling signature captures at the station.
 */
class FirmaController extends BaseController
{
    public function index($ticketId = 0)
    {
        $model = new FirmaModel();

        // 1) Alumno “en captura”: si viene ticketId, trae ese; si no, trae el siguiente pendiente
        $current = ($ticketId > 0)
            ? $model->getCurrentByTurno((int)$ticketId)
            : $model->getNextPending();

        // 2) Cola de pendientes (para el panel derecho)
        $queue = $model->getQueue();

        $data = [
            'title'      => 'Captura de Firma',
            'activeMenu' => 'firma',
            'current'    => $current,
            'queue'      => $queue,
        ];

        return view('captura/firma', $data);
    }

    /**
     * Devuelve la cola de firmas vía AJAX.
     */
    public function queue()
    {
        $model = new FirmaModel();

        return $this->response->setJSON([
            'ok'    => true,
            'items' => $model->getQueue(),
        ]);
    }

    /**
     * Trae información de un alumno específico.
     */
    public function student()
    {
        $ticketId  = (int) ($this->request->getGet('turnoId') ?? 0);
        $studentId = (int) ($this->request->getGet('alumnoId') ?? 0);

        $model = new FirmaModel();

        $row = null;
        if ($ticketId > 0) {
            $row = $model->getCurrentByTurno($ticketId);
        } elseif ($studentId > 0) {
            $row = $model->getByAlumnoId($studentId);
        }

        return $this->response->setJSON([
            'ok'     => true,
            'alumno' => $row,
        ]);
    }

    /**
     * Guarda la firma capturada.
     */
    public function save()
    {
        $model = new FirmaModel();

        $studentId     = (int) ($this->request->getPost('alumno_id') ?? 0);
        $ticketId      = (int) ($this->request->getPost('turno_id') ?? 0);
        $signaturePng  = (string) ($this->request->getPost('firma_png') ?? '');

        if ($studentId <= 0 || $ticketId <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => 'Falta alumno_id o turno_id.',
            ]);
        }

        if ($signaturePng === '' || !str_starts_with($signaturePng, 'data:image/')) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => 'La firma recibida no es válida.',
            ]);
        }

        try {
            $result = $model->saveFirma($studentId, $ticketId, $signaturePng);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->response->setJSON([
            'ok'        => true,
            'message'   => 'Firma guardada correctamente.',
            'url'       => $result['url'],
            'archivoId' => $result['file_id'],
            'queue'     => $model->getQueue(),
            'current'   => $model->getNextPending(),
        ]);
    }
}
