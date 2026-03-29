<?php

namespace App\Modules\Stations\Controllers;

use App\Controllers\BaseController;
use App\Modules\Stations\Models\CaptureQueueModel;
use RuntimeException;

/**
 * Controller for handling photo captures at the station.
 */
class CameraController extends BaseController
{
    public function index()
    {
        $model = new CaptureQueueModel();

        $selectedId = $this->request->getGet('id');
        $selectedId = $selectedId ? (int)$selectedId : null;

        $queue   = $model->getQueue();
        $current = $model->getCurrent($selectedId);

        return view('camera/capture_queue', [
            'title'      => 'Captura de fotografía',
            'activeMenu' => 'fotografia',
            'queue'      => $queue,
            'current'    => $current,
        ]);
    }

    /**
     * Refresca la cola de captura vía AJAX.
     */
    public function queue()
    {
        $model = new CaptureQueueModel();
        return $this->response->setJSON([
            'ok'    => true,
            'queue' => $model->getQueue(),
        ]);
    }

    /**
     * Guarda la fotografía capturada.
     */
    public function save()
    {
        $dataUrl   = $this->request->getPost('image');
        $studentId = (int) $this->request->getPost('student_id');
        $ticketId  = (int) $this->request->getPost('turno_id');

        if (!$studentId || !$ticketId) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => false, 'msg' => 'Falta student_id o turno_id'
            ]);
        }

        if (!$dataUrl || !str_starts_with($dataUrl, 'data:image/')) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => false, 'msg' => 'Imagen inválida'
            ]);
        }

        if (!preg_match('#^data:(image/[a-zA-Z0-9.+-]+);base64,(.+)$#', $dataUrl, $matches)) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => false, 'msg' => 'La imagen no tiene un formato compatible'
            ]);
        }

        $mime = strtolower($matches[1]);
        $content = $matches[2];

        $ext = match ($mime) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };

        $binary = base64_decode($content, true);
        if ($binary === false) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => false, 'msg' => 'Base64 inválido'
            ]);
        }

        $dir = FCPATH . 'uploads/photos/';
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $filename = "photo_{$studentId}_" . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
        $path = $dir . $filename;

        if (file_put_contents($path, $binary) === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false, 'msg' => 'No se pudo guardar el archivo de la fotografia'
            ]);
        }

        $model = new CaptureQueueModel();
        try {
            $model->markCaptured(
                $studentId,
                $ticketId,
                'uploads/photos/' . $filename,
                $mime,
                filesize($path) ?: 0,
                hash('sha256', $binary)
            );
        } catch (RuntimeException $e) {
            @unlink($path);
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => false, 'msg' => $e->getMessage()
            ]);
        }

        return $this->response->setJSON([
            'ok'       => true,
            'filename' => $filename,
            'url'      => base_url('uploads/photos/' . $filename),
            'queue'    => $model->getQueue(),
            'current'  => $model->getCurrent(),
        ]);
    }
}
