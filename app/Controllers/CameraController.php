<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\CaptureQueueModel;

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

        if (!$studentId) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => false, 'msg' => 'Falta student_id'
            ]);
        }

        if (!$dataUrl || !str_starts_with($dataUrl, 'data:image/')) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => false, 'msg' => 'Imagen inválida'
            ]);
        }

        [$meta, $content] = explode(',', $dataUrl, 2);

        $ext = 'jpg';
        if (str_contains($meta, 'image/png'))  $ext = 'png';
        if (str_contains($meta, 'image/webp')) $ext = 'webp';

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

        file_put_contents($path, $binary);

        // actualiza BD: foto + estado siguiente
        $m = new CaptureQueueModel();
        $m->markCaptured($studentId, 'uploads/photos/' . $filename);

        return $this->response->setJSON([
            'ok'       => true,
            'filename' => $filename,
            'url'      => base_url('uploads/photos/' . $filename),
        ]);
    }
}