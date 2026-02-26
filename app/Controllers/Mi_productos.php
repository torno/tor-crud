<?php

namespace App\Controllers;

use App\Libraries\Tor_Crud;

class Mi_productos extends BaseController
{
    public function index()
    {
        $crud = new Tor_Crud();
        $crud->setTable('mc_productos');
        
        // Acciones globales
        $crud->addGlobalAction([
            'icono' => 'bi-upload',
            'nombre' => '',
            'url' => '/importar/excel',
            'tooltip' => 'Importar CVS',
            'target' => '_blank'
        ]);
        
        // Acciones por fila
        $crud->addRowAction([
            'icono' => 'bi-calculator',
            'nombre' => 'Calcular',
            'js' => 'calcularGanancias({id})',
            'tooltip' => 'Calcular ganancias'
        ]);
        
        $crud->addRowAction([
            'icono' => 'bi-printer',
            'nombre' => 'Imprimir',
            'url' => '/productos/ver/{id}',
            'tooltip' => 'Imprimir'
        ]);

        // Datos extra que quieres pasar a la vista
        $crud->setViewData([
            'usuario' => 1,
            'empresa' => 1
        ]);
        
        $crud->beforeInsert('procesarPrecio'); 
        $crud->afterUpdate('registrarLog');  

        $crud->setWhere([
            'activo' => 1,
            'id_categoria' => 2
        ]);

        /*
        $crud->setWhere('activo', 1);
        // Múltiples condiciones
        

        // Con operadores
        $crud->setWhere([
            'precio >' => 100,
            'fecha_creacion >=' => '2024-01-01',
            'nombre LIKE' => '%laptop%'
        ]);

        // Array de arrays (máximo control)
        $crud->setWhere([
            ['activo', '=', 1],
            ['precio', '>', 100],
            ['categoria_id', 'IN', [1,2,3]]
        ]);
        */

        $crud->unsetSearch();
        return $crud->render();
    }

    public function getEmpresa($record)
    {
        return '<a href="#">Salada</a>';
    }

    public function procesarPrecio(&$data)
    {

        return $data;
    }

    public function registrarLog($id, $data)
    {
        log_message('info', "Producto ID {$id} actualizado. Nuevos datos: " . json_encode($data));
    }
}