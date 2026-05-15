<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Controllers\BaseController;
use App\Services\WebAuthnService;
use RuntimeException;

class FingerprintController extends BaseController
{
    protected WebAuthnService $webAuthnService;

    public function __construct()
    {
        $this->webAuthnService = new WebAuthnService();
    }

    public function index()
    {
        $pending = session()->get('pending_huella');
        if (!$pending) {
            return redirect()->to(base_url('turno'));
        }

        return view('public/autoservicio_huella', [
            'turno'     => $pending['turno'],
            'alumno'    => [
                'nombre' => $pending['turno']['nombre_completo'],
                'identificador' => $pending['turno']['identificador'],
                'carrera' => $pending['turno']['carrera'],
                'campus' => 'Instituto Tecnológico de Oaxaca',
            ],
            'studentId' => $pending['student_id'],
            'ticketId'  => $pending['ticket_id'],
        ]);
    }

    public function finishFlow()
    {
        $pending = session()->get('pending_huella');
        if (!$pending) {
            return redirect()->to(base_url('turno'));
        }

        $studentId    = (int) ($this->request->getPost('alumno_id') ?? 0);
        $ticketId     = (int) ($this->request->getPost('turno_id') ?? 0);
        $signatureB64 = (string) ($this->request->getPost('firma_png') ?? '');

        if ($studentId !== (int) $pending['student_id'] || $ticketId !== (int) $pending['ticket_id']) {
            return redirect()->to(base_url('turno'));
        }

        if ($signatureB64 !== '' && str_starts_with($signatureB64, 'data:image/')) {
            try {
                $this->webAuthnService->saveFinalSignature($studentId, $ticketId, $signatureB64);
            } catch (RuntimeException $e) {
                log_message('error', 'Final signature save failed: ' . $e->getMessage());
            }
        }

        session()->set('pending_photo', [
            'student_id' => $studentId,
            'ticket_id'  => $ticketId,
            'turno'      => $pending['turno'],
        ]);
        session()->remove('pending_huella');

        return redirect()->to(base_url('foto'));
    }

    public function registerChallenge()
    {
        $alumnoId   = $this->request->getPost('alumno_id');
        $alumnoNombre = $this->request->getPost('nombre');

        try {
            $createArgs = $this->webAuthnService->createRegistrationChallenge($alumnoId, $alumnoNombre);
            return $this->response->setJSON($createArgs);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON(['error' => $e->getMessage()]);
        }
    }

    public function Verifyregister()
    {
        $data = $this->request->getJSON(true);

        try {
            $this->webAuthnService->verifyAndStoreCredential($data);
            return $this->response->setJSON(['success' => true]);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON(['error' => $e->getMessage()]);
        }
    }

    public function authChallenge()
    {
        $alumnoId = $this->request->getPost('alumno_id');

        try {
            $getArgs = $this->webAuthnService->createAuthChallenge($alumnoId);
            return $this->response
                ->setHeader('Content-Type', 'application/json')
                ->setBody(json_encode($getArgs));
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(404)->setJSON(['error' => $e->getMessage()]);
        }
    }

    public function Verifyauth()
    {
        $data = $this->request->getJSON(true);

        try {
            $this->webAuthnService->verifyAuthentication($data);
            return $this->response->setJSON(['success' => true, 'verified' => true]);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON(['error' => $e->getMessage()]);
        }
    }

    public function existFingerprint()
    {
        $alumnoId = $this->request->getPost('alumno_id');
        return $this->response->setJSON(['tiene_huella' => $this->webAuthnService->hasFingerprint($alumnoId)]);
    }
}
