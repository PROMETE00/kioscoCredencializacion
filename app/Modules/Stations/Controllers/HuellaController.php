<?php

namespace App\Modules\Stations\Controllers;

use App\Controllers\BaseController;
use App\Modules\Stations\Models\HuellaModel;
use RuntimeException;

/**
 * Controller for handling fingerprint captures at the station.
 */
class HuellaController extends BaseController
{
    public function index($ticketId = 0)
    {
        $model = new HuellaModel();

        // 1) Current student for capture: if ticketId comes, load that; otherwise, get next pending
        $current = ($ticketId > 0)
            ? $model->getCurrentByTurno((int)$ticketId)
            : $model->getNextPending();

        // 2) Pending queue (for the right panel)
        $queue = $model->getQueue();

        $data = [
            'title'      => 'Fingerprint Capture',
            'activeMenu' => 'huella',
            'current'    => $current,
            'queue'      => $queue,
        ];

        return view('capture/huella', $data);
    }

    /**
     * Returns the fingerprint queue via AJAX.
     */
    public function queue()
    {
        $model = new HuellaModel();

        return $this->response->setJSON([
            'ok'    => true,
            'items' => $model->getQueue(),
        ]);
    }

    /**
     * Gets information for a specific student.
     */
    public function student()
    {
        $ticketId  = (int) ($this->request->getGet('turnoId') ?? 0);
        $studentId = (int) ($this->request->getGet('alumnoId') ?? 0);

        $model = new HuellaModel();

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
     * Saves the captured fingerprint.
     */
    public function save()
    {
        $model = new HuellaModel();

        $studentId      = (int) ($this->request->getPost('alumno_id') ?? 0);
        $ticketId       = (int) ($this->request->getPost('turno_id') ?? 0);
        $fingerprintB64 = (string) ($this->request->getPost('huella_b64') ?? '');

        if ($studentId <= 0 || $ticketId <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => 'Missing student_id or ticket_id.',
            ]);
        }

        if ($fingerprintB64 === '' || !str_starts_with($fingerprintB64, 'data:image/')) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => 'The received fingerprint is invalid.',
            ]);
        }

        try {
            $result = $model->saveHuella($studentId, $ticketId, $fingerprintB64);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->response->setJSON([
            'ok'        => true,
            'message'   => 'Fingerprint saved successfully.',
            'url'       => $result['url'],
            'archivoId' => $result['file_id'],
            'queue'     => $model->getQueue(),
            'current'   => $model->getNextPending(),
        ]);
    }
}
