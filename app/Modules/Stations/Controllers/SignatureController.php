<?php

namespace App\Modules\Stations\Controllers;

use App\Controllers\BaseController;
use App\Modules\Stations\Services\QueueService;
use App\Services\BiometricService;
use RuntimeException;

/**
 * Controller for handling signature captures at the station.
 */
class SignatureController extends BaseController
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
            ? $this->queueService->getCurrent('signature', (int)$ticketId)
            : $this->queueService->getCurrent('signature');

        $queue = $this->queueService->getQueue('signature');

        return view('stations/signature', [
            'title'      => 'Signature Capture',
            'activeMenu' => 'signature',
            'current'    => $current,
            'queue'      => $queue,
        ]);
    }

    /**
     * Returns the signature queue via AJAX.
     */
    public function queue()
    {
        return $this->response->setJSON([
            'ok'    => true,
            'items' => $this->queueService->getQueue('signature'),
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
            $row = $this->queueService->getCurrent('signature', $ticketId);
        } elseif ($studentId > 0) {
            $row = $this->queueService->getByStudentId('signature', $studentId);
        }

        return $this->response->setJSON([
            'ok'      => true,
            'student' => $row,
        ]);
    }

    /**
     * Saves the captured signature.
     */
    public function save()
    {
        $studentId    = (int) ($this->request->getPost('student_id') ?? 0);
        $ticketId     = (int) ($this->request->getPost('ticket_id') ?? 0);
        $signatureB64 = (string) ($this->request->getPost('signature_b64') ?? '');

        if ($studentId <= 0 || $ticketId <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => 'Missing student_id or ticket_id.',
            ]);
        }

        if ($signatureB64 === '' || !str_starts_with($signatureB64, 'data:image/')) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => 'The received signature is invalid.',
            ]);
        }

        try {
            $userId = session('auth')['id'] ?? null;
            $result = $this->biometricService->processAndSave('signature', $studentId, $ticketId, $signatureB64, $userId);

            return $this->response->setJSON([
                'ok'        => true,
                'message'   => 'Signature saved successfully.',
                'url'       => $result['url'],
                'file_id'   => $result['file_id'],
                'queue'     => $this->queueService->getQueue('signature'),
                'current'   => $this->queueService->getCurrent('signature'),
            ]);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
