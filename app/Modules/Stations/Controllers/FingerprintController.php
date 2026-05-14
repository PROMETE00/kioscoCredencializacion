<?php

namespace App\Modules\Stations\Controllers;

use App\Controllers\BaseController;
use App\Modules\Stations\Services\QueueService;
use App\Services\BiometricService;
use RuntimeException;

/**
 * Controller for handling fingerprint captures at the station.
 */
class FingerprintController extends BaseController
{
    protected QueueService $queueService;
    protected BiometricService $biometricService;

    public function __construct()
    {
        $this->queueService = new QueueService();
        $this->biometricService = new BiometricService();
    }

    public function index($ticketId = 0)
    {
        $current = ($ticketId > 0)
            ? $this->queueService->getCurrent('fingerprint', (int)$ticketId)
            : $this->queueService->getCurrent('fingerprint');

        $queue = $this->queueService->getQueue('fingerprint');

        return view('stations/fingerprint', [
            'title'      => 'Fingerprint Capture',
            'activeMenu' => 'fingerprint',
            'current'    => $current,
            'queue'      => $queue,
        ]);
    }

    /**
     * Returns the fingerprint queue via AJAX.
     */
    public function queue()
    {
        return $this->response->setJSON([
            'ok'    => true,
            'items' => $this->queueService->getQueue('fingerprint'),
        ]);
    }

    /**
     * Gets information for a specific student.
     */
    public function student()
    {
        $ticketId  = (int) ($this->request->getGet('ticketId') ?? 0);
        $studentId = (int) ($this->request->getGet('studentId') ?? 0);

        $row = null;
        if ($ticketId > 0) {
            $row = $this->queueService->getCurrent('fingerprint', $ticketId);
        } elseif ($studentId > 0) {
            $row = $this->queueService->getByStudentId('fingerprint', $studentId);
        }

        return $this->response->setJSON([
            'ok'      => true,
            'student' => $row,
        ]);
    }

    /**
     * Saves the captured fingerprint.
     */
    public function save()
    {
        $studentId      = (int) ($this->request->getPost('student_id') ?? 0);
        $ticketId       = (int) ($this->request->getPost('ticket_id') ?? 0);
        $fingerprintB64 = (string) ($this->request->getPost('fingerprint_b64') ?? '');

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
            $userId = session('auth')['id'] ?? null;
            $result = $this->biometricService->processAndSave('fingerprint', $studentId, $ticketId, $fingerprintB64, $userId);

            return $this->response->setJSON([
                'ok'        => true,
                'message'   => 'Fingerprint saved successfully.',
                'url'       => $result['url'],
                'file_id'   => $result['file_id'],
                'queue'     => $this->queueService->getQueue('fingerprint'),
                'current'   => $this->queueService->getCurrent('fingerprint'),
            ]);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
