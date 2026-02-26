<?php

namespace App\Models;

use CodeIgniter\Model;

class TorCampoModel extends Model
{
    protected $table = 'tor_campos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
            'entidad_id', 'nombre_campo', 'etiqueta_mostrar', 'tipo_control',
			'tipo_real', 
			'oculto_en_lista', 'oculto_en_form', 'oculto_en_ver', 'orden_visual',
			'relacion_tabla', 'relacion_campo', 'relacion_id',
			'valores_posibles', 'valor_default', 'comportamiento_hidden',
			'es_virtual', 'archivo_tipo_permitido', 'archivo_tamano_maximo',
			'archivo_carpeta_destino', 'archivo_subcarpeta_por_entidad',
			'archivo_mostrar_miniatura'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}