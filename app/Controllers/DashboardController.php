<?php

namespace App\Controllers;

use App\Models\DashboardModel;
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

    public function alumnos()
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

    public function cambiarEstatus()
    {
        $turnoId = (int) ($this->request->getPost('turno_id') ?? 0);
        $estatusId = (int) ($this->request->getPost('estatus_id') ?? 0);

        if ($turnoId <= 0 || $estatusId <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => 'Falta el turno o el estatus a actualizar.',
                'csrfHash'=> csrf_hash(),
            ]);
        }

        $model = new DashboardModel();

        try {
            $row = $model->updateTurnStatus($turnoId, $estatusId);
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

    public function borrarBiometrico()
    {
        $alumnoId = (int) ($this->request->getPost('alumno_id') ?? 0);
        $turnoId = (int) ($this->request->getPost('turno_id') ?? 0);
        $tipo = trim((string) ($this->request->getPost('tipo') ?? ''));

        if ($alumnoId <= 0 || $turnoId <= 0 || $tipo === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => 'Faltan datos para borrar el biométrico.',
                'csrfHash'=> csrf_hash(),
            ]);
        }

        $model = new DashboardModel();

        try {
            $row = $model->clearBiometric($alumnoId, $turnoId, $tipo);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'      => false,
                'message' => $e->getMessage(),
                'csrfHash'=> csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'ok'       => true,
            'message'  => ucfirst($tipo) . ' borrada correctamente.',
            'row'      => $row,
            'csrfHash' => csrf_hash(),
        ]);
    }
}
