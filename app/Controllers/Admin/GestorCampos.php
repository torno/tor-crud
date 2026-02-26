<?php

namespace App\Controllers\Admin;

use CodeIgniter\Controller;
use App\Models\TorEntidadModel;
use App\Models\TorCampoModel;
use App\Models\TorReglaModel;
use App\Models\TorPermisoModel;
use App\Models\TorCampoVirtualModel;

class GestorCampos extends Controller
{
    protected $entidadModel;
    protected $campoModel;
    protected $reglaModel;
    protected $permisoModel;
    protected $campoVirtualModel;
    protected $db;

    public function __construct()
    {
        $this->entidadModel = new TorEntidadModel();
        $this->campoModel = new TorCampoModel();
        $this->reglaModel = new TorReglaModel();
        $this->permisoModel = new TorPermisoModel();
        $this->campoVirtualModel = new TorCampoVirtualModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        // Obtener todas las tablas de la base de datos
        $tables = $this->db->listTables();

        // Obtener entidades ya configuradas (activas)
        $configuredEntities = $this->entidadModel
            ->select('nombre_tabla')
            ->where('activo', 1)
            ->findAll();
        
        $configuradas = array_column($configuredEntities, 'nombre_tabla');

        $data = [
            'title' => 'Gestor de Campos',
            'tables' => $tables,
            'configuradas' => $configuradas
        ];

        return view('admin/gestor_campos/list', $data);
    }
    public function configurar($tablaNombre)
    {
        // ===========================================
        // 1. OBTENER CAMPOS REALES DE LA TABLA
        // ===========================================
        $fields = $this->db->getFieldData($tablaNombre);
        
        // ===========================================
        // 2. PROCESAR CAMPOS PARA AÑADIR INFORMACIÓN ADICIONAL
        // ===========================================
        $processedFields = [];
        foreach ($fields as $field) {
            // Clonar el objeto para no modificar el original
            $newField = clone $field;
            
            // Si es un campo ENUM, obtener sus opciones (CORREGIDO: sin comillas)
            if (strtolower($newField->type) === 'enum') {
                $opciones = $this->getEnumOptions($tablaNombre, $newField->name);
                // Limpiar comillas si es necesario (por el bug de str_getcsv)
                $opciones = preg_replace("/'([^']*)'/", '$1', $opciones);
                $newField->enum_options = $opciones;
            }
            
            $processedFields[] = $newField;
        }
        
        // ===========================================
        // 3. FILTRAR CAMPOS NO CONFIGURABLES
        // ===========================================
        // Solo se excluyen:
        // - Campos autoincrement
        // - Campos que son primary key
        // (NO se excluye por nombre)
        $fieldsConfigurables = [];
        foreach ($processedFields as $field) {
            $excluir = false;
            
            // Campo autoincrement
            if (!empty($field->extra) && strpos($field->extra, 'auto_increment') !== false) {
                $excluir = true;
            }
            // Campo primary key
            elseif (!empty($field->primary_key)) {
                $excluir = true;
            }
            
            if (!$excluir) {
                $fieldsConfigurables[] = $field;
            }
        }
        
        // ===========================================
        // 4. VERIFICAR SI YA EXISTE CONFIGURACIÓN
        // ===========================================
        $entity = $this->entidadModel->where('nombre_tabla', $tablaNombre)->first();
        
        $camposConfigurados = [];
        $camposVirtuales = [];
        
        if ($entity) {
            // Cargar campos físicos configurados
            $rawFields = $this->campoModel
                ->where('entidad_id', $entity['id'])
                ->orderBy('orden_visual', 'ASC')
                ->findAll();
            
            // Organizar por nombre_campo para fácil acceso
            foreach ($rawFields as $field) {
                // Cargar reglas para este campo
                $reglas = $this->reglaModel
                    ->where('campo_id', $field['id'])
                    ->findAll();
                
                $reglaStrings = [];
                foreach ($reglas as $regla) {
                    $fullRule = $regla['regla_tipo'];
                    if ($regla['parametro_valor'] !== null) {
                        $fullRule .= '[' . $regla['parametro_valor'] . ']';
                    }
                    $reglaStrings[] = $fullRule;
                }
                $field['reglas_guardadas'] = implode('|', $reglaStrings);
                
                $camposConfigurados[$field['nombre_campo']] = $field;
            }
            
            // Cargar campos virtuales
            $camposVirtuales = $this->campoVirtualModel
                ->where('entidad_id', $entity['id'])
                ->findAll();
        }
        
        // ===========================================
        // 5. DETECTAR CAMBIOS EN LA ESTRUCTURA
        // ===========================================
        $camposReales = array_column($fieldsConfigurables, 'name');
        $camposConfigNombres = array_keys($camposConfigurados);
        
        $faltanEnBD = array_diff($camposConfigNombres, $camposReales);
        $sobranEnBD = array_diff($camposReales, $camposConfigNombres);
        
        if (!empty($faltanEnBD)) {
            session()->setFlashdata('warning', 
                'Estos campos ya NO existen en la tabla y su configuración será eliminada al guardar: ' . 
                implode(', ', $faltanEnBD)
            );
        }
        
        if (!empty($sobranEnBD)) {
            session()->setFlashdata('info', 
                'Nuevos campos detectados (pendientes de configurar): ' . 
                implode(', ', $sobranEnBD)
            );
        }
        
        // ===========================================
        // CALCULAR ORDEN SUGERIDO PARA CAMPOS NUEVOS
        // ===========================================
        $maxOrden = 0;
        foreach ($camposConfigurados as $campo) {
            if ($campo['orden_visual'] > $maxOrden) {
                $maxOrden = $campo['orden_visual'];
            }
        }

        foreach ($fieldsConfigurables as &$field) {
            if (isset($camposConfigurados[$field->name])) {
                $field->orden_sugerido = $camposConfigurados[$field->name]['orden_visual'];
            } else {
                $maxOrden++;
                $field->orden_sugerido = $maxOrden;
            }
        }

        // ===========================================
        // 6. PREPARAR DATOS PARA LA VISTA
        // ===========================================
        $data = [
            'title' => "Configurar: {$tablaNombre}",
            'tablaNombre' => $tablaNombre,
            'fields' => $fieldsConfigurables,        // ← SOLO CAMPOS CONFIGURABLES
            'entity' => $entity,
            'camposConfigurados' => $camposConfigurados,
            'camposVirtuales' => $camposVirtuales
        ];
        
        return view('admin/gestor_campos/configurar', $data);
    }


    /**
     * Obtiene las opciones de un campo ENUM de MySQL
     */
    public function getEnumOptions($tabla, $campo)
    {
        $query = $this->db->query("SHOW COLUMNS FROM `{$tabla}` WHERE Field = '{$campo}'");
        $row = $query->getRow();
        
        if ($row && preg_match("/^enum\(\'(.*)\'\)$/", $row->Type, $matches)) {
            // Explode cuidadosamente respetando comillas
            $options = str_getcsv($matches[1], ',', "'");
            return implode(',', $options);
        }

        return '';
    }

    public function guardar()
    {
        //log_message('debug', '=== INICIO GUARDAR ===');
        log_message('debug', 'POST: ' . print_r($this->request->getPost(), true));
        
        $input = $this->request->getPost();
        
        $db = \Config\Database::connect();
        $db->transStart();
        
        try {
            //log_message('debug', '=== PASO 1: Iniciando transacción ===');
            
            // ===========================================
            // 1. GUARDAR ENTIDAD
            // ===========================================
            //log_message('debug', '=== PASO 2: Guardando entidad ===');
            
            $entityData = [
                'nombre_tabla' => $input['nombre_tabla'],
                'titulo' => $input['titulo'],
                'descripcion' => $input['descripcion'] ?? null,
                'permite_busqueda' => (bool) ($input['permite_busqueda'] ?? false),
                'filtro_global' => $input['filtro_global'] ?? null,
                'eliminacion_logica' => (bool) ($input['eliminacion_logica'] ?? false),
                'activo' => 1,
                'permite_crear' => (bool) ($input['permite_crear'] ?? true),
                'permite_editar' => (bool) ($input['permite_editar'] ?? true),
                'permite_eliminar' => (bool) ($input['permite_eliminar'] ?? true),
                'permite_exportar' => (bool) ($input['permite_exportar'] ?? false),
                'permite_importar' => (bool) ($input['permite_importar'] ?? false),
                'permite_clonar' => (bool) ($input['permite_clonar'] ?? false),
                'permite_borrado_masivo' => (bool) ($input['permite_borrado_masivo'] ?? false),
                'usa_paginacion' => (bool) ($input['usa_paginacion'] ?? true),
                'usa_ajax' => (bool) ($input['usa_ajax'] ?? false),
                'filtro_personalizado' => $input['filtro_personalizado'] ?? null,
            ];
            
            if (empty($input['entidad_id'])) {
                $this->entidadModel->insert($entityData);
                $entidadId = $this->entidadModel->getInsertID();
                //log_message('debug', "Entidad insertada, ID: {$entidadId}");
            } else {
                $this->entidadModel->update($input['entidad_id'], $entityData);
                $entidadId = $input['entidad_id'];
                //log_message('debug', "Entidad actualizada, ID: {$entidadId}");
            }
            
            // ===========================================
            // 2. OBTENER CAMPOS REALES DE LA TABLA
            // ===========================================
            //log_message('debug', '=== PASO 3: Obteniendo campos reales ===');
            
            $realFields = [];
            $dbFields = $this->db->getFieldData($input['nombre_tabla']);
            
            foreach ($dbFields as $field) {
                $excluir = false;
                
                if (!empty($field->extra) && strpos($field->extra, 'auto_increment') !== false) {
                    $excluir = true;
                }
                elseif (!empty($field->primary_key)) {
                    $excluir = true;
                }
                
                if (!$excluir) {
                    $realFields[] = $field->name;
                }
            }
            //log_message('debug', "Campos reales: " . implode(', ', $realFields));
            
            // ===========================================
            // 3. ELIMINAR CAMPOS VIRTUALES ANTERIORES
            // ===========================================
            $this->campoVirtualModel->where('entidad_id', $entidadId)->delete();
            
            // ===========================================
            // 4. PROCESAR CAMPOS ENVIADOS
            // ===========================================
            //log_message('debug', '=== PASO 4: Procesando campos enviados ===');
            
            $camposEnviados = $input['campos'] ?? [];
            
            foreach ($camposEnviados as $nombreCampo => $datos) {
                // Solo procesar si el campo existe en la BD
                if (!in_array($nombreCampo, $realFields)) {
                    //log_message('debug', "Campo {$nombreCampo} ignorado (no existe en BD)");
                    continue;
                }
                
                // Procesar valor default para hidden
                $valorDefault = null;
                if (!empty($datos['comportamiento_hidden']) && $datos['comportamiento_hidden'] === 'forzar_valor') {
                    
                    if (!empty($datos['valor_default_tipo'])) {
                        // Caso 1: Se seleccionó una opción del dropdown
                        
                        if ($datos['valor_default_tipo'] === '__CONTROLADOR__') {
                            // Si es CONTROLADOR, necesitamos el valor del campo de texto (ej: __CONTROLADOR__:id_empresa)
                            $valorDefault = $datos['valor_default'] ?? '__CONTROLADOR__:propiedad';
                        } else {
                            // Para NOW, USER_ID, etc., guardamos solo el marcador
                            $valorDefault = $datos['valor_default_tipo'];
                        }
                    } else {
                        // Caso 2: No se seleccionó dropdown, se usó el valor fijo directamente
                        $valorDefault = $datos['valor_default'] ?? null;
                    }
                }

                // Obtener tipo real de la columna
                $tipoReal = $this->getColumnType($input['nombre_tabla'], $nombreCampo);
//log_message('debug', "Campo {$nombreCampo}: tipo_real = " . ($tipoReal ?? 'NULL'));
     
                $campoData = [
                    'entidad_id' => $entidadId,
                    'nombre_campo' => $nombreCampo,
                    'etiqueta_mostrar' => $datos['etiqueta'],
                    'tipo_control' => $datos['tipo'],
                    'oculto_en_lista' => (bool) ($datos['oculto_lista'] ?? false),
                    'oculto_en_form' => (bool) ($datos['oculto_form'] ?? false),
                    'oculto_en_ver' => (bool) ($datos['oculto_ver'] ?? false),
                    'orden_visual' => (int) ($datos['orden'] ?? 0),
                    'relacion_tabla' => $datos['relacion_tabla'] ?? null,
                    'relacion_campo' => $datos['relacion_campo'] ?? null,
                    'relacion_id' => $datos['relacion_id'] ?? null,
                    'valores_posibles' => $datos['valores_posibles'] ?? null,
                    'valor_default' => $valorDefault,
                    'comportamiento_hidden' => $datos['comportamiento_hidden'] ?? null,
                    'es_virtual' => 0,
                    'archivo_tipo_permitido' => $datos['archivo_tipo_permitido'] ?? null,
                    'archivo_tamano_maximo' => $datos['archivo_tamano_maximo'] ?? null,
                    'archivo_carpeta_destino' => $datos['archivo_carpeta_destino'] ?? null,
                    'archivo_subcarpeta_por_entidad' => (bool) ($datos['archivo_subcarpeta_por_entidad'] ?? false),
                    'archivo_mostrar_miniatura' => 0,
                    'tipo_real' => $tipoReal,
                ];
                
                // Buscar si ya existe
                $existente = $this->campoModel
                    ->where('entidad_id', $entidadId)
                    ->where('nombre_campo', $nombreCampo)
                    ->first();
                
                if ($existente) {
                    $this->campoModel->update($existente['id'], $campoData);
                    $campoId = $existente['id'];
                    //log_message('debug', "Campo {$nombreCampo} actualizado, ID: {$campoId}");
                } else {
                    $this->campoModel->insert($campoData);
                    $campoId = $this->campoModel->getInsertID();
                    //log_message('debug', "Campo {$nombreCampo} insertado, nuevo ID: {$campoId}");
                }
                
                // Guardar reglas de validación usando el método auxiliar
                $this->guardarReglas($campoId, $datos['reglas_input'] ?? '');
            }
            
            // ===========================================
            // 5. ELIMINAR CAMPOS HUÉRFANOS
            // ===========================================
            //log_message('debug', '=== PASO 5: Procesando campos huérfanos ===');
            
            $configurados = $this->campoModel
                ->where('entidad_id', $entidadId)
                ->findAll();
            
            foreach ($configurados as $campo) {
                if (!in_array($campo['nombre_campo'], $realFields)) {
                    $this->campoModel->delete($campo['id']);
                    //log_message('debug', "Campo huérfano eliminado: {$campo['nombre_campo']} (ID: {$campo['id']})");
                }
            }
            
            // ===========================================
            // 6. PROCESAR CAMPOS VIRTUALES
            // ===========================================
            //log_message('debug', '=== PASO 6: Procesando campos virtuales ===');
            
            $virtuales = $input['virtual_fields'] ?? [];
            foreach ($virtuales as $vf) {
                if (empty($vf['nombre']) || empty($vf['tipo'])) continue;
                
                $vfData = [
                    'entidad_id' => $entidadId,
                    'nombre' => $vf['nombre'],
                    'tipo' => $vf['tipo'],
                    'funcion_display' => $vf['funcion_display'] ?? null,
                    'tabla_intermedia' => $vf['tabla_intermedia'] ?? null,
                    'tabla_fuente' => $vf['tabla_fuente'] ?? null,
                    'campo_local_fk' => $vf['campo_local_fk'] ?? null,
                    'campo_externo_fk' => $vf['campo_externo_fk'] ?? null,
                    'campo_id_fuente' => $vf['campo_id_fuente'] ?? null,
                    'formato_visualizacion' => $vf['formato_visualizacion'] ?? null,
                    'oculto_en_lista' => (bool) ($vf['oculto_lista'] ?? false),
                    'oculto_en_form' => (bool) ($vf['oculto_form'] ?? false),
                    'oculto_en_ver' => (bool) ($vf['oculto_ver'] ?? false),
                    'orden_visual' => (int) ($vf['orden_visual'] ?? 0),
                ];
                
                $this->campoVirtualModel->insert($vfData);
                //log_message('debug', "Campo virtual insertado: {$vf['nombre']}");
            }
            
            $db->transComplete();
            
            //log_message('debug', '=== PASO 7: Transacción completada, status: ' . ($db->transStatus() ? 'OK' : 'FAIL'));
            
            if ($db->transStatus() === false) {
                $error = $db->error();
                //log_message('error', '=== ERROR TRANSACCIÓN ===');
                //log_message('error', print_r($error, true));
                return redirect()->back()->with('error', 'Error al guardar: ' . ($error['message'] ?? 'Desconocido'));
            }
            //invalido el caché para que se vuelva a leer
            $cache = service('cache');
            $cache->delete("crud_config_{$input['nombre_tabla']}");
            //log_message('debug', '=== PASO 8: Todo OK, redirigiendo con éxito ===');
            return redirect()->to('/admin/gestor-campos')->with('success', 'Configuración guardada correctamente');
            
        } catch (\Exception $e) {
            $db->transRollback();
            //log_message('error', '=== EXCEPCIÓN ===');
            //log_message('error', $e->getMessage());
            return redirect()->back()->with('error', 'Error al guardar: ' . $e->getMessage());
        }
    }

    public function clearcache($tabla)
    {
        $cache = service('cache');
        $cache->delete("crud_config_{$tabla}");
        return redirect()->to('/admin/gestor-campos')->with('success', 'Cache limpiado');
    }

    /**
     * Obtiene el tipo real de una columna en la tabla
     */
    protected function getColumnType($tabla, $columna)
    {
        $query = $this->db->query("SHOW COLUMNS FROM `{$tabla}` WHERE Field = '{$columna}'");
        $row = $query->getRow();
        
        if ($row) {
            // Extraer solo el tipo base (sin longitud)
            $type = $row->Type;
            if (strpos($type, '(') !== false) {
                $type = substr($type, 0, strpos($type, '('));
            }
            return strtolower($type);
        }
        
        return null;
    }

    private function guardarReglas($campoId, $reglasInput)
    {
        // Eliminar reglas anteriores
        $this->reglaModel->where('campo_id', $campoId)->delete();
        
        if (empty($reglasInput)) {
            //log_message('debug', "No hay reglas para campo {$campoId}");
            return;
        }
        
        //log_message('debug', "Guardando reglas para campo {$campoId}: {$reglasInput}");
        
        $reglas = explode('|', $reglasInput);
        foreach ($reglas as $regla) {
            $regla = trim($regla);
            if (empty($regla)) continue;
            
            if (preg_match('/([a-zA-Z_]+)\[(.+)\]/', $regla, $matches)) {
                $this->reglaModel->insert([
                    'campo_id' => $campoId,
                    'regla_tipo' => $matches[1],
                    'parametro_valor' => $matches[2]
                ]);
                //log_message('debug', "Regla con parámetro: {$matches[1]}[{$matches[2]}]");
            } else {
                $this->reglaModel->insert([
                    'campo_id' => $campoId,
                    'regla_tipo' => $regla,
                    'parametro_valor' => null
                ]);
                //log_message('debug', "Regla simple: {$regla}");
            }
        }
    }

    public function generarControlador()
    {
        $tabla = $this->request->getPost('tabla');
        $nombre = $this->request->getPost('nombre');
        $incluirAcciones = $this->request->getPost('incluir_acciones');
        $incluirCallbacks = $this->request->getPost('incluir_callbacks');
        
        // Obtener configuración de la entidad
        $entidad = $this->entidadModel->where('nombre_tabla', $tabla)->first();
        
        // Generar contenido del controlador
        $contenido = $this->generarCodigoControlador($entidad, $nombre, $incluirAcciones, $incluirCallbacks);
        
        // Devolver como archivo descargable
        return $this->response
            ->setHeader('Content-Type', 'application/octet-stream')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $nombre . '.php"')
            ->setBody($contenido);
    }

    private function generarCodigoControlador($entidad, $nombre, $incluirAcciones, $incluirCallbacks)
    {
        $codigo = "<?php\n\n";
        $codigo .= "namespace App\Controllers;\n\n";
        $codigo .= "use App\Libraries\Tor_Crud;\n\n";
        $codigo .= "class {$nombre} extends BaseController\n";
        $codigo .= "{\n";
        $codigo .= "    public function index()\n";
        $codigo .= "    {\n";
        $codigo .= "        \$crud = new Tor_Crud();\n";
        $codigo .= "        \$crud->setTable('{$entidad['nombre_tabla']}');\n\n";
        
        if ($incluirAcciones) {
            $codigo .= "        // ===========================================\n";
            $codigo .= "        // CONFIGURACIÓN DEL CRUD\n";
            $codigo .= "        // ===========================================\n\n";
            $codigo .= "        // Descomentar para deshabilitar acciones\n";
            $codigo .= "        // \$crud->unsetAdd();\n";
            $codigo .= "        // \$crud->unsetEdit();\n";
            $codigo .= "        // \$crud->unsetDelete();\n\n";
            
            $codigo .= "        // Acciones globales de ejemplo\n";
            $codigo .= "        \$crud->addGlobalAction([\n";
            $codigo .= "            'icono' => 'bi-printer',\n";
            $codigo .= "            'nombre' => '',\n";
            $codigo .= "            'url' => '/" . strtolower($nombre) . "/print',\n";
            $codigo .= "            'tooltip' => 'Imprimir'\n";
            $codigo .= "        ]);\n\n";
            
            $codigo .= "        // Acciones por fila de ejemplo\n";
            $codigo .= "        \$crud->addRowAction([\n";
            $codigo .= "            'icono' => 'bi-calculator',\n";
            $codigo .= "            'nombre' => 'Calcular',\n";
            $codigo .= "            'js' => 'calcularGanancias({id})'\n";
            $codigo .= "        ]);\n\n";
        }
        
        if ($incluirCallbacks) {
            $codigo .= "        // ===========================================\n";
            $codigo .= "        // CALLBACKS DE EJEMPLO\n";
            $codigo .= "        // ===========================================\n";
            $codigo .= "        // \$crud->beforeInsert('procesarAntesDeInsertar');\n";
            $codigo .= "        // \$crud->afterInsert('notificarDespuesDeInsertar');\n";
            $codigo .= "        // \$crud->beforeDelete('verificarAntesDeEliminar');\n\n";
        }
        
        $codigo .= "        return \$crud->render();\n";
        $codigo .= "    }\n";
        
        if ($incluirCallbacks) {
            $codigo .= "\n";
            $codigo .= "    // ===========================================\n";
            $codigo .= "    // CALLBACKS DE EJEMPLO (descomentar para usar)\n";
            $codigo .= "    // ===========================================\n";
            $codigo .= "    /*\n";
            $codigo .= "    public function procesarAntesDeInsertar(&\$data)\n";
            $codigo .= "    {\n";
            $codigo .= "        // Ejemplo: calcular campos automáticos\n";
            $codigo .= "        \$data['fecha_creacion'] = date('Y-m-d H:i:s');\n";
            $codigo .= "        return \$data;\n";
            $codigo .= "    }\n\n";
            
            $codigo .= "    public function notificarDespuesDeInsertar(\$id, \$data)\n";
            $codigo .= "    {\n";
            $codigo .= "        log_message('info', \"Nuevo registro ID {\$id} creado\");\n";
            $codigo .= "    }\n\n";
            
            $codigo .= "    public function verificarAntesDeEliminar(\$id)\n";
            $codigo .= "    {\n";
            $codigo .= "        // Retornar false para cancelar la eliminación\n";
            $codigo .= "        return true;\n";
            $codigo .= "    }\n";
            $codigo .= "    */\n";
        }
        
        $codigo .= "}\n";
        
        return $codigo;
    }

    public function getTablas()
    {
        $db = \Config\Database::connect();
        $tablas = $db->listTables();
        
        // Excluir tablas del sistema
        $excluir = ['tor_entidades', 'tor_campos', 'tor_reglas_validacion', 
                    'tor_permisos_campos', 'tor_campos_virtuales', 'roles'];
        
        $resultado = array_values(array_diff($tablas, $excluir));
        
        return $this->response->setJSON($resultado);
    }

    public function getCamposDeTabla()
    {
        $tabla = $this->request->getGet('tabla');
        
        if (!$tabla) {
            return $this->response->setJSON(['error' => 'Tabla no especificada']);
        }
        
        try {
            $db = \Config\Database::connect();
            $campos = $db->getFieldData($tabla);
            
            // Obtener información de primary key
            $primaryKey = '';
            $keyQuery = $db->query("SHOW KEYS FROM `{$tabla}` WHERE Key_name = 'PRIMARY'");
            $pkRow = $keyQuery->getRow();
            if ($pkRow) {
                $primaryKey = $pkRow->Column_name;
            }
            
            $resultado = [];
            foreach ($campos as $campo) {
                $tipo = $campo->type;
                if (strpos($tipo, '(') !== false) {
                    $tipo = substr($tipo, 0, strpos($tipo, '('));
                }
                
                $resultado[] = [
                    'name' => $campo->name,
                    'type' => strtolower($tipo),
                    'is_primary' => ($campo->name === $primaryKey),
                    'is_auto_increment' => !empty($campo->extra) && strpos($campo->extra, 'auto_increment') !== false
                ];
            }
            
            return $this->response->setJSON($resultado);
            
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)
                                ->setJSON(['error' => $e->getMessage()]);
        }
    }

    public function analizarRelacionNan()
    {
        try {
            $tablaIntermedia = $this->request->getGet('intermedia');
            $tablaFuente = $this->request->getGet('fuente');
            
            if (!$tablaIntermedia || !$tablaFuente) {
                return $this->response->setJSON([
                    'error' => 'Faltan parámetros',
                    'debug' => compact('tablaIntermedia', 'tablaFuente')
                ]);
            }
            
            $db = \Config\Database::connect();
            
            // Verificar que las tablas existen
            $tablas = $db->listTables();
            if (!in_array($tablaIntermedia, $tablas)) {
                return $this->response->setJSON(['error' => "La tabla intermedia '{$tablaIntermedia}' no existe"]);
            }
            if (!in_array($tablaFuente, $tablas)) {
                return $this->response->setJSON(['error' => "La tabla fuente '{$tablaFuente}' no existe"]);
            }
            
            // Obtener campos de la tabla intermedia
            $camposIntermedia = $db->getFieldData($tablaIntermedia);
            
            // Detectar posibles campos FK
            $fkLocales = [];   // Hacia la tabla principal (la que se está configurando)
            $fkExternos = [];   // Hacia la tabla fuente
            
            foreach ($camposIntermedia as $campo) {
                $nombre = $campo->name;
                
                // Detectar por nombre (terminación _id)
                if (strpos($nombre, '_id') !== false) {
                    // Si contiene el nombre de la tabla fuente
                    if (strpos($nombre, str_replace('mc_', '', $tablaFuente)) !== false) {
                        $fkExternos[] = $nombre;
                    } else {
                        // Asumimos que es el FK local (hacia la tabla principal)
                        $fkLocales[] = $nombre;
                    }
                }
            }
            
            // Si no se detectaron, agregamos sugerencias por defecto
            if (empty($fkLocales)) {
                $fkLocales[] = str_replace('mc_', '', $tablaIntermedia) . '_id';
            }
            
            if (empty($fkExternos)) {
                $fkExternos[] = str_replace('mc_', '', $tablaFuente) . '_id';
            }
            
            // Obtener campos de la tabla fuente
            $camposFuente = $db->getFieldData($tablaFuente);
            
            // Detectar primary key de la tabla fuente
            $primaryKeyFuente = '';
            $keyQuery = $db->query("SHOW KEYS FROM `{$tablaFuente}` WHERE Key_name = 'PRIMARY'");
            $pkRow = $keyQuery->getRow();
            if ($pkRow) {
                $primaryKeyFuente = $pkRow->Column_name;
            } else {
                $primaryKeyFuente = 'id'; // Valor por defecto
            }
            
            // Preparar campos para mostrar
            $camposMostrar = [];
            foreach ($camposFuente as $campo) {
                // Excluir campos largos o binarios
                if (!in_array(strtolower($campo->type), ['blob', 'text', 'mediumtext', 'longtext', 'binary'])) {
                    $camposMostrar[] = [
                        'name' => $campo->name,
                        'type' => $campo->type
                    ];
                }
            }
            
            return $this->response->setJSON([
                'success' => true,
                'fk_locales' => array_unique($fkLocales),
                'fk_externos' => array_unique($fkExternos),
                'pk_fuente' => $primaryKeyFuente,
                'campos_mostrar' => $camposMostrar
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => 'Excepción: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    public function getSelectorNan()
{
    $intermedia = $this->request->getGet('intermedia');
    $fuente = $this->request->getGet('fuente');
    $tablaActual = $this->request->getGet('actual');
    
    if (!$intermedia || !$fuente) {
        return $this->response->setJSON([
            'success' => false,
            'error' => 'Faltan parámetros'
        ]);
    }
    
    try {
        $db = \Config\Database::connect();
        
        // Obtener campos de ambas tablas
        $camposIntermedia = $db->getFieldData($intermedia);
        $camposFuente = $db->getFieldData($fuente);
        
        // Obtener primary key de la tabla fuente
        $primaryKeyFuente = '';
        $keyQuery = $db->query("SHOW KEYS FROM `{$fuente}` WHERE Key_name = 'PRIMARY'");
        $pkRow = $keyQuery->getRow();
        if ($pkRow) {
            $primaryKeyFuente = $pkRow->Column_name;
        }
        
        // Aquí generaremos el HTML
        $html = $this->generarHtmlSelectorNan(
            $camposIntermedia, 
            $camposFuente, 
            $intermedia, 
            $fuente, 
            $tablaActual,
            $primaryKeyFuente
        );
        
        return $this->response->setJSON([
            'success' => true,
            'html' => $html
        ]);
        
    } catch (\Exception $e) {
        return $this->response->setJSON([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

private function generarHtmlSelectorNan($camposIntermedia, $camposFuente, $intermedia, $fuente, $tablaActual, $primaryKeyFuente)
{
    // Preparar nombres base
    $nombreBaseActual = str_replace('mc_', '', $tablaActual);
    $nombreBaseFuente = str_replace('mc_', '', $fuente);
    
    // ===========================================
    // 1. Construir array de campos de la intermedia
    // ===========================================
    $camposIntermediaArray = [];
    foreach ($camposIntermedia as $campo) {
        $camposIntermediaArray[$campo->name] = $campo;
    }
    
    // ===========================================
    // 2. Detectar posibles FK según patrones
    // ===========================================
    $patrones = [
        'id_{campo}',
        '{campo}_id',
        'fk_{campo}',
        '{campo}'
    ];
    
    $sugerenciasLocal = [];
    $sugerenciasExterno = [];
    $todosLosCampos = array_keys($camposIntermediaArray);
    
    // Buscar coincidencias para campo local
    foreach ($todosLosCampos as $campo) {
        // Probar cada patrón
        if ($campo === 'id_' . $nombreBaseActual || 
            $campo === $nombreBaseActual . '_id' ||
            $campo === 'fk_' . $nombreBaseActual ||
            $campo === $nombreBaseActual) {
            $sugerenciasLocal[] = $campo;
        }
    }
    
    // Buscar coincidencias para campo externo
    foreach ($todosLosCampos as $campo) {
        if ($campo === 'id_' . $nombreBaseFuente || 
            $campo === $nombreBaseFuente . '_id' ||
            $campo === 'fk_' . $nombreBaseFuente ||
            $campo === $nombreBaseFuente) {
            $sugerenciasExterno[] = $campo;
        }
    }
    
    // ===========================================
    // 3. Iniciar HTML
    // ===========================================
    $html = '<div class="alert alert-info">';
    $html .= '<i class="bi bi-info-circle"></i> ';
    $html .= "Selecciona los campos que relacionan <strong>{$intermedia}</strong> y <strong>{$fuente}</strong>";
    $html .= '</div>';
    
    // ===========================================
    // 4. CAMPO LOCAL FK
    // ===========================================
    $html .= '<div class="card mb-3">';
    $html .= '<div class="card-header bg-light"><strong>Campo local FK (hacia ' . $tablaActual . ')</strong></div>';
    $html .= '<div class="card-body">';
    
    if (!empty($sugerenciasLocal)) {
        $html .= '<p class="text-muted mb-2">Sugerencias:</p>';
        $checked = 'checked';
        foreach ($sugerenciasLocal as $campo) {
            $html .= '<div class="form-check">';
            $html .= '<input class="form-check-input" type="radio" name="campoLocal" ';
            $html .= 'value="' . $campo . '" id="local_' . $campo . '" ' . $checked . '>';
            $html .= '<label class="form-check-label" for="local_' . $campo . '">';
            $html .= '<code>' . $campo . '</code>';
            if (isset($camposIntermediaArray[$campo]->type)) {
                $html .= ' <small class="text-muted">(' . $camposIntermediaArray[$campo]->type . ')</small>';
            }
            $html .= '</label>';
            $html .= '</div>';
            $checked = '';
        }
    }
    
    // Mostrar TODOS los campos disponibles para que el usuario elija
    $html .= '<p class="text-muted mt-2 mb-2">Todos los campos disponibles:</p>';
    foreach ($todosLosCampos as $campo) {
        // Si ya está en sugerencias, no repetir
        if (in_array($campo, $sugerenciasLocal)) {
            continue;
        }
        $html .= '<div class="form-check">';
        $html .= '<input class="form-check-input" type="radio" name="campoLocal" ';
        $html .= 'value="' . $campo . '" id="local_' . $campo . '">';
        $html .= '<label class="form-check-label" for="local_' . $campo . '">';
        $html .= '<code>' . $campo . '</code>';
        if (isset($camposIntermediaArray[$campo]->type)) {
            $html .= ' <small class="text-muted">(' . $camposIntermediaArray[$campo]->type . ')</small>';
        }
        $html .= '</label>';
        $html .= '</div>';
    }
    
    $html .= '</div></div>';
    
    // ===========================================
    // 5. CAMPO EXTERNO FK
    // ===========================================
    $html .= '<div class="card mb-3">';
    $html .= '<div class="card-header bg-light"><strong>Campo externo FK (hacia ' . $fuente . ')</strong></div>';
    $html .= '<div class="card-body">';
    
    if (!empty($sugerenciasExterno)) {
        $html .= '<p class="text-muted mb-2">Sugerencias:</p>';
        $checked = 'checked';
        foreach ($sugerenciasExterno as $campo) {
            $html .= '<div class="form-check">';
            $html .= '<input class="form-check-input" type="radio" name="campoExterno" ';
            $html .= 'value="' . $campo . '" id="externo_' . $campo . '" ' . $checked . '>';
            $html .= '<label class="form-check-label" for="externo_' . $campo . '">';
            $html .= '<code>' . $campo . '</code>';
            if (isset($camposIntermediaArray[$campo]->type)) {
                $html .= ' <small class="text-muted">(' . $camposIntermediaArray[$campo]->type . ')</small>';
            }
            $html .= '</label>';
            $html .= '</div>';
            $checked = '';
        }
    }
    
    // Mostrar TODOS los campos disponibles (excluyendo sugerencias)
    $html .= '<p class="text-muted mt-2 mb-2">Todos los campos disponibles:</p>';
    foreach ($todosLosCampos as $campo) {
        if (in_array($campo, $sugerenciasExterno)) {
            continue;
        }
        $html .= '<div class="form-check">';
        $html .= '<input class="form-check-input" type="radio" name="campoExterno" ';
        $html .= 'value="' . $campo . '" id="externo_' . $campo . '">';
        $html .= '<label class="form-check-label" for="externo_' . $campo . '">';
        $html .= '<code>' . $campo . '</code>';
        if (isset($camposIntermediaArray[$campo]->type)) {
            $html .= ' <small class="text-muted">(' . $camposIntermediaArray[$campo]->type . ')</small>';
        }
        $html .= '</label>';
        $html .= '</div>';
    }
    
    $html .= '</div></div>';
    
    // ===========================================
    // 6. CAMPO ID EN TABLA FUENTE
    // ===========================================
    $pkValue = $primaryKeyFuente ?: 'id';
    $html .= '<div class="card mb-3">';
    $html .= '<div class="card-header bg-light"><strong>Campo ID en tabla fuente</strong></div>';
    $html .= '<div class="card-body">';
    $html .= '<input type="text" class="form-control" id="campoIdFuente" value="' . $pkValue . '" readonly>';
    $html .= '<small class="text-muted">Clave primaria detectada</small>';
    $html .= '</div></div>';
    
    // ===========================================
    // 7. CAMPOS A MOSTRAR
    // ===========================================
    $html .= '<div class="card mb-3">';
    $html .= '<div class="card-header bg-light"><strong>Campos a mostrar (de ' . $fuente . ')</strong></div>';
    $html .= '<div class="card-body">';
    $html .= '<div class="mb-2">';
    $html .= '<button type="button" class="btn btn-sm btn-outline-primary" id="seleccionarTodosNan">Seleccionar todos</button>';
    $html .= '<button type="button" class="btn btn-sm btn-outline-secondary" id="deseleccionarTodosNan">Deseleccionar todos</button>';
    $html .= '</div>';
    
    $idx = 0;
    foreach ($camposFuente as $campo) {
        if ($idx % 2 == 0) {
            $html .= '<div class="row mb-2">';
        }
        $html .= '<div class="col-md-6">';
        $html .= '<div class="form-check">';
        $html .= '<input class="form-check-input campo-mostrar-nan" type="checkbox" ';
        $html .= 'value="' . $campo->name . '" id="mostrar_' . $campo->name . '" checked>';
        $html .= '<label class="form-check-label" for="mostrar_' . $campo->name . '">';
        $html .= '<code>' . $campo->name . '</code>';
        if (!empty($campo->type)) {
            $tipo = $campo->type;
            if (($pos = strpos($tipo, '(')) !== false) {
                $tipo = substr($tipo, 0, $pos);
            }
            $html .= ' <small class="text-muted">(' . $tipo . ')</small>';
        }
        $html .= '</label>';
        $html .= '</div>';
        $html .= '</div>';
        
        if ($idx % 2 == 1 || $idx == count($camposFuente) - 1) {
            $html .= '</div>';
        }
        $idx++;
    }
    
    $html .= '</div></div>';
    
    // ===========================================
    // 8. FORMATO DE VISUALIZACIÓN
    // ===========================================
    $html .= '<div class="card">';
    $html .= '<div class="card-header bg-light"><strong>Formato de visualización</strong></div>';
    $html .= '<div class="card-body">';
    $html .= '<input type="text" class="form-control" id="formatoManualNan" ';
    $html .= 'placeholder="Ej: {nombre} - {codigo}">';
    $html .= '<small class="text-muted">Usa {nombre_campo} para cada campo</small>';
    $html .= '</div></div>';
    
    return $html;
}

}