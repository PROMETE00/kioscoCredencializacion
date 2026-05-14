<?php

namespace App\Modules\Stations\Controllers;

use App\Controllers\BaseController;
use App\Modules\Stations\Services\QueueService;
use App\Services\BiometricService;
use RuntimeException;

/**
 * Controller for handling photo captures at the station.
 */
class PhotoController extends BaseController
{
    protected QueueService $queueService;
    protected BiometricService $biometricService;

    public function __construct()
    {
        $this->queueService = new QueueService();
        $this->biometricService = new BiometricService();
    }

    public function index()
    {
        $selectedId = $this->request->getGet('id');
        $selectedId = $selectedId ? (int)$selectedId : null;

        $queue   = $this->queueService->getQueue('photo');
        $current = $this->queueService->getCurrent('photo', $selectedId);

        return view('stations/photo', [
            'title'      => 'Photo Capture',
            'activeMenu' => 'photo',
            'queue'      => $queue,
            'current'    => $current,
        ]);
    }

    /**
     * Refreshes the capture queue via AJAX.
     */
    public function queue()
    {
        return $this->response->setJSON([
            'ok'    => true,
            'queue' => $this->queueService->getQueue('photo'),
        ]);
    }

    /**
     * Saves the captured photo.
     */
    public function save()
    {
        $dataUrl   = $this->request->getPost('image');
        $studentId = (int) $this->request->getPost('student_id');
        $ticketId  = (int) $this->request->getPost('ticket_id');

        if (!$studentId || !$ticketId) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => false, 'message' => 'Missing student_id or ticket_id'
            ]);
        }

        if (!$dataUrl || !str_starts_with($dataUrl, 'data:image/')) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => false, 'message' => 'Invalid image'
            ]);
        }

        try {
            $userId = session('auth')['id'] ?? null;
            $result = $this->biometricService->processAndSave('photo', $studentId, $ticketId, $dataUrl, $userId);

            return $this->response->setJSON([
                'ok'       => true,
                'filename' => basename($result['path']),
                'url'      => $result['url'],
                'queue'    => $this->queueService->getQueue('photo'),
                'current'  => $this->queueService->getCurrent('photo'),
            ]);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => false, 'message' => $e->getMessage()
            ]);
        }
    }
}
