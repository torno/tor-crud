<?php

namespace App\Models;

use CodeIgniter\Model;

class TorEntidadModel extends Model
{
    protected $table = 'tor_entidades';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'nombre_tabla', 'titulo', 'descripcion', 'permite_busqueda',
        'filtro_global', 'eliminacion_logica', 'activo',
        'permite_crear', 'permite_editar', 'permite_eliminar',
        'permite_exportar', 'permite_importar', 'permite_clonar',
        'permite_borrado_masivo', 'usa_paginacion', 'usa_ajax',
        'filtro_personalizado'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}