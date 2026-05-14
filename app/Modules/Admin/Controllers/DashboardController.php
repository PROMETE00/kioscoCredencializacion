<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Services\AdminService;
use RuntimeException;

class DashboardController extends BaseController
{
    protected AdminService $adminService;

    public function __construct()
    {
        $this->adminService = new AdminService();
    }

    public function index()
    {
        $data = [
            'kpis'          => $this->adminService->getKpis(),
            'worklist'      => $this->adminService->getWorklist('', 8),
            'statusOptions' => $this->adminService->getStatusOptions(),
        ];

        return view('admin/dashboard', $data);
    }

    public function getWorklist()
    {
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $limit = (int) ($this->request->getGet('limit') ?? 8);

        return $this->response->setJSON([
            'ok'    => true,
            'items' => $this->adminService->getWorklist($q, $limit),
            'kpis'  => $this->adminService->getKpis(),
        ]);
    }

    public function updateStatus()
    {
        $ticketId = (int) ($this->request->getPost('ticket_id') ?? $this->request->getPost('turno_id') ?? 0);
        $statusId = (int) ($this->request->getPost('status_id') ?? $this->request->getPost('estatus_id') ?? 0);

        if ($ticketId <= 0 || $statusId <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => 'Missing ticket ID or status ID.',
                'csrfHash'=> csrf_hash(),
            ]);
        }

        try {
            $row = $this->adminService->updateTicketStatus($ticketId, $statusId);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => $e->getMessage(),
                'csrfHash'=> csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'ok'       => true,
            'message'  => 'Status updated successfully.',
            'row'      => $row,
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function clearBiometric()
    {
        $studentId = (int) ($this->request->getPost('student_id') ?? $this->request->getPost('alumno_id') ?? 0);
        $ticketId = (int) ($this->request->getPost('ticket_id') ?? $this->request->getPost('turno_id') ?? 0);
        $type = trim((string) ($this->request->getPost('type') ?? $this->request->getPost('tipo') ?? ''));

        if ($studentId <= 0 || $ticketId <= 0 || $type === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => 'Missing data to clear biometric.',
                'csrfHash'=> csrf_hash(),
            ]);
        }

        try {
            $row = $this->adminService->clearBiometric($studentId, $ticketId, $type);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => $e->getMessage(),
                'csrfHash'=> csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'ok'       => true,
            'message'  => ucfirst($type) . ' cleared successfully.',
            'row'      => $row,
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function recordDelivery()
    {
        $studentId    = (int) ($this->request->getPost('student_id') ?? 0);
        $ticketId     = (int) ($this->request->getPost('ticket_id') ?? 0);
        $signatureB64 = (string) ($this->request->getPost('signature') ?? '');

        if ($studentId <= 0 || $ticketId <= 0) {
             return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => 'Missing data for delivery record.',
                'csrfHash'=> csrf_hash(),
            ]);
        }

        try {
            $row = $this->adminService->recordDelivery($studentId, $ticketId, $signatureB64);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => $e->getMessage(),
                'csrfHash'=> csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'ok'       => true,
            'message'  => 'Delivery recorded successfully.',
            'row'      => $row,
            'csrfHash' => csrf_hash(),
        ]);
    }
}
