<?php

namespace App\Controllers;

use App\Libraries\Tor_Crud;

class Mi_categorias extends BaseController
{
    public $id_empresa;  

    public function __construct()
    {
        // El constructor se ejecuta en cada petición
        $this->id_empresa = session()->get('user')->id_empresa ?? 1;
    }

    public function index()
    {
        $crud = new Tor_Crud();
        $crud->setTable('mc_categorias');
        
        // Datos extra que quieres pasar a la vista
        $crud->setViewData([
            'usuario' => 1,
            'empresa' => 1
        ]);
        
        return $crud->render();
    }
}