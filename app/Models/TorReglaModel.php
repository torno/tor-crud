<?php

namespace App\Models;

use CodeIgniter\Model;

class TorReglaModel extends Model
{
    protected $table = 'tor_reglas_validacion';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['campo_id', 'regla_tipo', 'parametro_valor'];
    
    // DESACTIVAR TIMESTAMPS COMPLETAMENTE
    protected $useTimestamps = false;
}