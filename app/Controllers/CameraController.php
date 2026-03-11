<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\CaptureQueueModel;
use RuntimeException;

class CameraController extends Controller
{
    public function index()
    {
        $m = new CaptureQueueModel();

        $selectedId = $this->request->getGet('id');
        $selectedId = $selectedId ? (int)$selectedId : null;

        $queue   = $m->getQueue();
        $current = $m->getCurrent($selectedId);

        return view('camera/capture_queue', [
            'title'      => 'Captura de fotografía',
            'activeMenu' => 'credenciales',
            'queue'      => $queue,
            'current'    => $current,
        ]);
    }

    // opcional: refrescar cola via AJAX
    public function queue()
    {
        $m = new CaptureQueueModel();
        return $this->response->setJSON([
            'ok'    => true,
            'queue' => $m->getQueue(),
        ]);
    }

    public function save()
    {
        $dataUrl   = $this->request->getPost('image');
        $studentId = (int) $this->request->getPost('student_id');
        $turnoId   = (int) $this->request->getPost('turno_id');

        if (!$studentId || !$turnoId) {
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
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $binary = base64_decode($content, true);
        if ($binary === false) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => false, 'msg' => 'Base64 inválido'
            ]);
        }

        $dir = FCPATH . 'uploads/photos/';
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $filename = "foto_{$studentId}_" . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
        $path = $dir . $filename;

        if (file_put_contents($path, $binary) === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false, 'msg' => 'No se pudo guardar el archivo de la fotografia'
            ]);
        }

        $m = new CaptureQueueModel();
        try {
            $m->markCaptured(
                $studentId,
                $turnoId,
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
            'queue'    => $m->getQueue(),
            'current'  => $m->getCurrent(),
        ]);
    }
}
