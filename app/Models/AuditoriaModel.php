<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditoriaModel extends Model
{
    protected $DBGroup = 'auditoria'; // Conexión externa
    protected $table = 'audit_log';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'table_name', 'record_id', 'operation', 'user_id',
        'old_data', 'new_data', 'ip_address', 'user_agent'
    ];
    
    // Fechas
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';
    protected $dateFormat = 'datetime';
    
    // Paginación
    protected $perPage = 20;
}