<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Dashboard extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Dashboard - Universal Meta-CRUD'
        ];

        return view('dashboard/index', $data);
    }
}