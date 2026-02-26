<?php

namespace App\Models;

use CodeIgniter\Model;

class TorPermisoModel extends Model
{
    protected $table = 'tor_permisos_campos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['campo_id', 'rol_id', 'permiso_tipo', 'activo'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}