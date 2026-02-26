<?php

namespace App\Services;

use App\Models\AuditoriaModel;

class AuditoriaService
{
    protected $auditoriaModel;
    protected $habilitado;

    public function __construct()
    {
        $this->auditoriaModel = new AuditoriaModel();
        $this->habilitado = env('auditoria.enabled', false);
    }

    /**
     * Registrar una operación INSERT
     */
    public function insert($tabla, $recordId, $nuevosDatos)
    {
        if (!$this->habilitado) {
            return false;
        }

        return $this->auditoriaModel->insert([
            'table_name' => $tabla,
            'record_id' => $recordId,
            'operation' => 'INSERT',
            'user_id' => $this->getCurrentUserId(),
            'new_data' => $nuevosDatos ? json_encode($nuevosDatos) : null,
            'ip_address' => $this->getClientIp(),
            'user_agent' => $this->getUserAgent()
        ]);
    }

    /**
     * Registrar una operación UPDATE
     */
    public function update($tabla, $recordId, $datosAnteriores, $nuevosDatos)
    {
        if (!$this->habilitado) {
            return false;
        }

        return $this->auditoriaModel->insert([
            'table_name' => $tabla,
            'record_id' => $recordId,
            'operation' => 'UPDATE',
            'user_id' => $this->getCurrentUserId(),
            'old_data' => $datosAnteriores ? json_encode($datosAnteriores) : null,
            'new_data' => $nuevosDatos ? json_encode($nuevosDatos) : null,
            'ip_address' => $this->getClientIp(),
            'user_agent' => $this->getUserAgent()
        ]);
    }

    /**
     * Registrar una operación DELETE
     */
    public function delete($tabla, $recordId, $datosAnteriores)
    {
        if (!$this->habilitado) {
            return false;
        }

        return $this->auditoriaModel->insert([
            'table_name' => $tabla,
            'record_id' => $recordId,
            'operation' => 'DELETE',
            'user_id' => $this->getCurrentUserId(),
            'old_data' => $datosAnteriores ? json_encode($datosAnteriores) : null,
            'ip_address' => $this->getClientIp(),
            'user_agent' => $this->getUserAgent()
        ]);
    }

    /**
     * Obtener ID del usuario actual
     */
    protected function getCurrentUserId()
    {
        // Ajusta según tu sistema de autenticación
        $session = session();
        return $session->get('user_id') ?? null;
    }

    /**
     * Obtener IP del cliente
     */
    protected function getClientIp()
    {
        $request = service('request');
        return $request->getIPAddress();
    }

    /**
     * Obtener User Agent
     */
    protected function getUserAgent()
    {
        $request = service('request');
        return $request->getUserAgent()->getAgentString();
    }
}