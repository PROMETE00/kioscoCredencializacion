<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Controllers\BaseController;
use App\Services\BiometricService;
use App\Services\TicketService;
use RuntimeException;

class SelfServiceController extends BaseController
{
    protected TicketService $ticketService;
    protected BiometricService $biometricService;
    protected string $viewBase = 'public/self_service';

    public function __construct()
    {
        $this->ticketService = new TicketService();
        $this->biometricService = new BiometricService();
    }

    public function index()
    {
        return view($this->viewBase . '/index');
    }

    public function identify()
    {
        $identifier = trim((string) $this->request->getPost('identifier'));

        if (empty($identifier)) {
            return redirect()->back()->with('error', 'Please enter your control number.');
        }

        $student = $this->ticketService->findStudentByIdentifier($identifier);

        if (!$student) {
            return view($this->viewBase . '/index', [
                'identifier' => $identifier,
                'notFound'   => true
            ]);
        }

        $ticket = $this->ticketService->createTicket((int)$student['id']);

        session()->set('self_service', [
            'student' => $student,
            'ticket'  => $ticket
        ]);

        return redirect()->to(base_url('self-service/confirm'));
    }

    public function confirm()
    {
        $data = session()->get('self_service');
        if (!$data) return redirect()->to(base_url('self-service'));

        return view($this->viewBase . '/confirm', $data);
    }

    public function signature()
    {
        $data = session()->get('self_service');
        if (!$data) return redirect()->to(base_url('self-service'));

        return view($this->viewBase . '/signature', $data);
    }

    public function saveSignature()
    {
        $data = session()->get('self_service');
        if (!$data) return $this->response->setStatusCode(403);

        $signatureB64 = (string) $this->request->getPost('signature');
        
        try {
            $this->biometricService->processAndSave(
                'signature',
                (int)$data['student']['id'],
                (int)$data['ticket']['id'],
                $signatureB64
            );
            return redirect()->to(base_url('self-service/fingerprint'));
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function fingerprint()
    {
        $data = session()->get('self_service');
        if (!$data) return redirect()->to(base_url('self-service'));

        return view($this->viewBase . '/fingerprint', $data);
    }

    public function photo()
    {
        $data = session()->get('self_service');
        if (!$data) return redirect()->to(base_url('self-service'));

        return view($this->viewBase . '/photo', $data);
    }

    public function savePhoto()
    {
        $data = session()->get('self_service');
        if (!$data) return $this->response->setStatusCode(403);

        $photoB64 = (string) $this->request->getPost('photo');

        try {
            $this->biometricService->processAndSave(
                'photo',
                (int)$data['student']['id'],
                (int)$data['ticket']['id'],
                $photoB64
            );
            return redirect()->to(base_url('self-service/success'));
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function success()
    {
        $data = session()->get('self_service');
        session()->remove('self_service');
        return view($this->viewBase . '/success', $data);
    }
}
