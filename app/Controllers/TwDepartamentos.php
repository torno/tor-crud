<?php

namespace App\Controllers;

use App\Libraries\Tor_Crud;

class TwDepartamentos extends BaseController
{
    public function index()
    {
        $crud = new Tor_Crud();
        $crud->setTable('tw_departamentos');

        // ===========================================
        // CONFIGURACIÓN DEL CRUD
        // ===========================================

        // Descomentar para deshabilitar acciones
        // $crud->unsetAdd();
        // $crud->unsetEdit();
        // $crud->unsetDelete();

        // Acciones globales de ejemplo
        $crud->addGlobalAction([
            'icono' => 'bi-printer',
            'nombre' => '',
            'url' => '/twdepartamentos/print',
            'tooltip' => 'Imprimir'
        ]);

        // Acciones por fila de ejemplo
        $crud->addRowAction([
            'icono' => 'bi-calculator',
            'nombre' => 'Calcular',
            'js' => 'calcularGanancias({id})'
        ]);

        // ===========================================
        // CALLBACKS DE EJEMPLO
        // ===========================================
        // $crud->beforeInsert('procesarAntesDeInsertar');
        // $crud->afterInsert('notificarDespuesDeInsertar');
        // $crud->beforeDelete('verificarAntesDeEliminar');

        return $crud->render();
    }

    // ===========================================
    // CALLBACKS DE EJEMPLO (descomentar para usar)
    // ===========================================
    /*
    public function procesarAntesDeInsertar(&$data)
    {
        // Ejemplo: calcular campos automáticos
        $data['fecha_creacion'] = date('Y-m-d H:i:s');
        return $data;
    }

    public function notificarDespuesDeInsertar($id, $data)
    {
        log_message('info', "Nuevo registro ID {$id} creado");
    }

    public function verificarAntesDeEliminar($id)
    {
        // Retornar false para cancelar la eliminación
        return true;
    }
    */
}
