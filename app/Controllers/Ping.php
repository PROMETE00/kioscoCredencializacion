<?php

namespace App\Controllers;

class Ping extends BaseController
{
    public function index()
    {
        echo "PONG - CodeIgniter is working!";
        exit;
    }
}
