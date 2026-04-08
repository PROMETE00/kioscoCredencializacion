<?php

namespace App\Controllers;

class Test extends BaseController
{
    public function index()
    {
        return "Hello World from Test Controller";
    }
    
    public function dbTest()
    {
        $db = \Config\Database::connect();
        $query = $db->query("SELECT COUNT(*) as total FROM tickets");
        $result = $query->getRow();
        return "Total tickets: " . $result->total;
    }
}
