<?php

namespace App\Controllers;

class DebugController extends BaseController
{
    public function db()
    {
        $db = \Config\Database::connect();
        $row = $db->query("SELECT COUNT(*) AS c FROM usuarios")->getRowArray();
        return $this->response->setJSON($row);
    }
}