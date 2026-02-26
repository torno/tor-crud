<?php

namespace App\Models;

use CodeIgniter\Model;

class TorCampoVirtualModel extends Model
{
    protected $table = 'tor_campos_virtuales';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'entidad_id', 'nombre', 'tipo', 'funcion_display',
        'tabla_intermedia', 'tabla_fuente', 'campo_local_fk',
        'campo_externo_fk', 'campo_id_fuente', 'formato_visualizacion',
        'oculto_en_lista', 'oculto_en_form', 'oculto_en_ver', 'orden_visual'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}