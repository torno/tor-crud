<?php

namespace App\Controllers\Admin;

use App\Models\AuditoriaModel;
use App\Controllers\BaseController;

class Auditoria extends BaseController
{
    protected $auditoriaModel;

    public function __construct()
    {
        $this->auditoriaModel = new AuditoriaModel();
    }

public function index()
{
    // Filtros manuales
    $tabla = $this->request->getGet('tabla');
    $operacion = $this->request->getGet('operacion');
    $usuario = $this->request->getGet('usuario');
    $fecha_desde = $this->request->getGet('fecha_desde');
    $fecha_hasta = $this->request->getGet('fecha_hasta');

    $model = $this->auditoriaModel;

    // Aplicar filtros
    if ($tabla) {
        $model->where('table_name', $tabla);
    }
    if ($operacion) {
        $model->where('operation', $operacion);
    }
    if ($usuario) {
        $model->where('user_id', $usuario);
    }
    if ($fecha_desde) {
        $model->where('created_at >=', $fecha_desde . ' 00:00:00');
    }
    if ($fecha_hasta) {
        $model->where('created_at <=', $fecha_hasta . ' 23:59:59');
    }

    // Paginación
    $data['logs'] = $model->orderBy('created_at', 'DESC')
                          ->paginate(20);
    
    $data['pager'] = $model->pager;
    $data['pager']->setPath('admin/auditoria'); // Esto va aquí
    
    // Datos para filtros
    $tempModel = new \App\Models\AuditoriaModel();
    $data['tablas'] = $tempModel->distinct()->select('table_name')->findAll();
    $data['operaciones'] = ['INSERT', 'UPDATE', 'DELETE'];
    
    return view('admin/auditoria/index', $data);
}

    public function detalle($id)
    {
        $log = $this->auditoriaModel->find($id);
        
        if (!$log) {
            return $this->response->setJSON(['error' => 'No encontrado']);
        }
        
        // Formatear JSON para mostrar bonito
        if ($log['old_data']) {
            $log['old_data'] = json_decode($log['old_data'], true);
        }
        if ($log['new_data']) {
            $log['new_data'] = json_decode($log['new_data'], true);
        }
        
        return $this->response->setJSON($log);
    }
}