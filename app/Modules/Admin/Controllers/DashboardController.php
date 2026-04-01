<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\DashboardModel;
use RuntimeException;

class DashboardController extends BaseController
{
    public function index()
    {
        $model = new DashboardModel();

        $data = [
            'kpis'      => $model->getKpis(),
            'worklist'  => $model->getAdminStudents('', 8),
            'statusOptions' => $model->getStatusOptions(),
        ];

        return view('admin/dashboard', $data);
    }

    public function students()
    {
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $limit = (int) ($this->request->getGet('limit') ?? 8);

        $model = new DashboardModel();

        return $this->response->setJSON([
            'ok'    => true,
            'items' => $model->getAdminStudents($q, $limit),
            'kpis'  => $model->getKpis(),
        ]);
    }

    public function changeStatus()
    {
        $ticketId = (int) ($this->request->getPost('turno_id') ?? 0);
        $statusId = (int) ($this->request->getPost('estatus_id') ?? 0);

        if ($ticketId <= 0 || $statusId <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => 'Falta el turno o el estatus a actualizar.',
                'csrfHash'=> csrf_hash(),
            ]);
        }

        $model = new DashboardModel();

        try {
            $row = $model->updateTicketStatus($ticketId, $statusId);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => $e->getMessage(),
                'csrfHash'=> csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'ok'       => true,
            'message'  => 'Estatus actualizado correctamente.',
            'row'      => $row,
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function clearBiometric()
    {
        $studentId = (int) ($this->request->getPost('alumno_id') ?? 0);
        $ticketId = (int) ($this->request->getPost('turno_id') ?? 0);
        $type = trim((string) ($this->request->getPost('tipo') ?? ''));

        if ($studentId <= 0 || $ticketId <= 0 || $type === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => 'Faltan datos para borrar el biométrico.',
                'csrfHash'=> csrf_hash(),
            ]);
        }

        $model = new DashboardModel();

        try {
            $row = $model->clearBiometric($studentId, $ticketId, $type);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => $e->getMessage(),
                'csrfHash'=> csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'ok'       => true,
            'message'  => ucfirst($type) . ' borrada correctamente.',
            'row'      => $row,
            'csrfHash' => csrf_hash(),
        ]);
    }
}
