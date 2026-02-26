<?php

namespace App\Controllers;

use App\Libraries\Meta_Crud;

class Mi_tabla extends BaseController
{
    public function calculos()
    {
        $crud = new Meta_Crud();
        
        // Cargar filtros de GET
        $search = $this->request->getGet('search');
        $page = $this->request->getGet('page');
        $perPage = $this->request->getGet('perPage');
        
        $crud->setTable('prueba_tabla')
             ->setSearch($search)
             ->setPage($page)
             ->setPerPage($perPage);
        
        // Filtros por columna
        foreach ($this->request->getGet() as $key => $value) {
            if (strpos($key, 'filter_') === 0 && !empty($value)) {
                $field = substr($key, 7);
                $crud->setFilter([$field => $value]);
            }
        }
        
        $resultado = $crud->render();
        
        return view($resultado->view, [
            'data' => $resultado->data,
            'css' => $resultado->css,
            'js' => $resultado->js
        ]);
    }
}