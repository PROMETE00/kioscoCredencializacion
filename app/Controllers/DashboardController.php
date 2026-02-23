<?php

namespace App\Controllers;

use App\Models\DashboardModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $stage = $this->request->getGet('stage') ?? 'hoy';
        $q     = trim((string)($this->request->getGet('q') ?? ''));

        $model = new DashboardModel();

        $data = [
            'stage'     => $stage,
            'q'         => $q,
            'kpis'      => $model->getKpis(),
            'worklist'  => $model->getWorklist($stage, $q),
            'stations'  => $model->getStationsStatus(), // luego lo vuelves real con “ping”
        ];

        return view('admin/dashboard', $data);
    }
}