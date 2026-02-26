<?php

namespace App\Libraries;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Services\AuditoriaService;

class Tor_Crud
{
    protected $table;
    protected $db;
    protected $config;
    protected $filters = [];
    protected $orderBy = [];
    protected $perPage = 25;
    protected $page = 1;
    protected $search = '';
    protected $primaryKey = 'id';
    protected $sessionKey;
    protected $resetRealizado = false;
    protected $globalActions = [];
    protected $rowActions = [];
    protected $viewData = [];
    protected $request;
    protected $disableAdd = false;
    protected $disableEdit = false;
    protected $disableDelete = false;
    protected $disableView = false;
    protected $disableClone = false;
    protected $disableExport = false;
    protected $beforeInsertCallback;
    protected $afterInsertCallback;
    protected $beforeUpdateCallback;
    protected $afterUpdateCallback;
    protected $beforeDeleteCallback;
    protected $afterDeleteCallback;
    protected $beforeBulkDeleteCallback;
    protected $afterBulkDeleteCallback;
    protected $beforeUploadCallback;
    protected $afterUploadCallback;
    protected $disableInline = false;
    protected $response;
    protected $auditoriaService;
    protected $customWheres = [];
    protected $disableSearch = false;


    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->request = service('request');  
        $this->response = service('response');
        $this->auditoriaService = new AuditoriaService();
    }
    
    /**
     * Establece la tabla a utilizar
     */
    public function setTable($table)
    {
        $this->table = $table;
        $this->sessionKey = 'crud_' . $table;
        $this->loadConfig();
        $this->loadSessionFilters();
        return $this;
    }
    
    public function setViewData(array $data)
    {
        $this->viewData = array_merge($this->viewData, $data);
        return $this;
    }

    public function unsetInline()
    {
        $this->disableInline = true;
        return $this;
    }

    /**
     * Deshabilita la creación de nuevos registros.
     * También deshabilita implícitamente la clonación.
     */
    public function unsetAdd()
    {
        $this->disableAdd = true;
        $this->disableClone = true;
        return $this;
    }

    /**
     * Deshabilita la clonación de registros.
     * Puede usarse aunque add esté habilitado.
     */
    public function unsetClone()
    {
        $this->disableClone = true;
        return $this;
    }

    /**
     * Deshabilita la edición de registros.
     */
    public function unsetEdit()
    {
        $this->disableEdit = true;
        return $this;
    }

    /**
     * Deshabilita la eliminación de registros.
     * Afecta tanto a borrado simple como masivo.
     */
    public function unsetDelete()
    {
        $this->disableDelete = true;
        return $this;
    }

    /**
     * Deshabilita la vista de detalle.
     */
    public function unsetView()
    {
        $this->disableView = true;
        return $this;
    }

    /**
     * Deshabilita las acciones de exportación (CSV, PDF, Imprimir).
     */
    public function unsetExport()
    {
        $this->disableExport = true;
        return $this;
    }
    /**
     * Deshabilita la interfaz de búsqueda (filtros y búsqueda global)
     * 
     * @return $this
     */
    public function unsetSearch()
    {
        $this->disableSearch = true;
        return $this;
    }

    /**
     * Establece filtros personalizados para el listado
     * 
     * @param mixed $field Nombre del campo o array de condiciones
     * @param mixed $value Valor (opcional si el primer parámetro es array)
     * @return $this
     */
    public function setWhere($field, $value = null)
    {
        // Si es un array asociativo simple ['campo' => 'valor']
        if (is_array($field) && $value === null) {
            foreach ($field as $key => $val) {
                $this->customWheres[] = [$key, '=', $val];
            }
        }
        // Si es un array de arrays [['campo', 'operador', 'valor']]
        elseif (is_array($field) && is_array($field[0] ?? null)) {
            foreach ($field as $cond) {
                if (count($cond) == 3) {
                    $this->customWheres[] = $cond;
                }
            }
        }
        // Si es campo y valor simple
        else {
            $this->customWheres[] = [$field, '=', $value];
        }
        
        return $this;
    }

    /**
     * Verifica si una acción está deshabilitada
     * 
     * @param string $action Nombre de la acción (tcadd, tcedit, etc.)
     * @return bool True si está deshabilitada, False si está permitida
     */
    protected function isActionDisabled($action)
    {
        switch ($action) {
            case 'tcadd':
            case 'tcclone':
                return $this->disableAdd;
                
            case 'tcedit':
                return $this->disableEdit;
                
            case 'tcdelete':
            case 'tcbulkdelete':
                return $this->disableDelete;
                
            case 'tcview':
                return $this->disableView;
                
            default:
                return false;
        }
    }

    /**
     * Carga la configuración de la tabla
     */
    protected function loadConfig()
    {
        $cache = service('cache');
        $cacheKey = "crud_config_{$this->table}";
        
        // Log para ver si está en cache
        //log_message('debug', "Cache check for {$this->table}: " . ($cache->get($cacheKey) ? 'HIT' : 'MISS'));

        // Intentar obtener del cache
        $cached = $cache->get($cacheKey);
        if ($cached !== null) {
            $this->config = $cached;
            $this->primaryKey = $this->config['primaryKey'] ?? 'id';
            return;
        }

        //log_message('debug', "Cache MISS for {$this->table}, loading from DB");

        $entity = $this->db->table('tor_entidades')
                          ->where('nombre_tabla', $this->table)
                          ->where('activo', 1)
                          ->get()
                          ->getRowArray();
        
        if (!$entity) {
            throw new \Exception("La tabla '{$this->table}' no está configurada");
        }
        
        // Obtener primary key
        $keyQuery = $this->db->query("SHOW KEYS FROM `{$this->table}` WHERE Key_name = 'PRIMARY'");
        $pkRow = $keyQuery->getRow();
        $this->primaryKey = $pkRow ? $pkRow->Column_name : 'id';
        
        // Cargar campos
        $fields = $this->db->table('tor_campos')
                          ->where('entidad_id', $entity['id'])
                          ->orderBy('orden_visual', 'ASC')
                          ->get()
                          ->getResultArray();
        
        $this->config = [
            'entity' => $entity,
            'fields' => [],
            'primaryKey' => $this->primaryKey
        ];
        
        
        foreach ($fields as $field) {
            $rules = $this->loadValidationRulesForField($field['id']);
            //log_message('debug', "Reglas para campo {$field['nombre_campo']} (ID: {$field['id']}): " . $rules);

            $this->config['fields'][$field['nombre_campo']] = [
                'label' => $field['etiqueta_mostrar'],
                'type' => $field['tipo_control'],
                'hidden_in_list' => (bool) $field['oculto_en_lista'],
                'hidden_in_form' => (bool) $field['oculto_en_form'],
                'orden_visual' => $field['orden_visual'],
                'relacion_tabla' => $field['relacion_tabla'],
                'relacion_campo' => $field['relacion_campo'],
                'relacion_id' => $field['relacion_id'],
                'valores_posibles' => $field['valores_posibles'],
                'valor_default' => $field['valor_default'],
                'comportamiento_hidden' => $field['comportamiento_hidden'],
                'archivo_tipo_permitido' => $field['archivo_tipo_permitido'],
                'archivo_tamano_maximo' => $field['archivo_tamano_maximo'],
                'archivo_carpeta_destino' => $field['archivo_carpeta_destino'],
                'archivo_subcarpeta_por_entidad' => (bool) $field['archivo_subcarpeta_por_entidad'],
                'searchable' => !in_array($field['tipo_control'], ['file', 'image']),
                'validation_rules' => $rules, 
                'tipo_real' => $field['tipo_real'],
            ];
        }

        // ===========================================
        // CARGAR CAMPOS VIRTUALES
        // ===========================================
        $virtualFields = $this->db->table('tor_campos_virtuales')
            ->where('entidad_id', $entity['id'])
            ->orderBy('orden_visual', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($virtualFields as $vf) {
            $this->config['fields'][$vf['nombre']] = [
                'label' => $vf['nombre'],
                'type' => 'virtual_' . $vf['tipo'],
                'hidden_in_list' => (bool) $vf['oculto_en_lista'],
                'hidden_in_form' => (bool) $vf['oculto_en_form'],
                'hidden_in_ver' => (bool) $vf['oculto_en_ver'],
                'orden_visual' => (int) $vf['orden_visual'],
                'tabla_intermedia' => $vf['tabla_intermedia'],
                'tabla_fuente' => $vf['tabla_fuente'],
                'campo_local_fk' => $vf['campo_local_fk'],
                'campo_externo_fk' => $vf['campo_externo_fk'],
                'campo_id_fuente' => $vf['campo_id_fuente'],
                'formato_visualizacion' => $vf['formato_visualizacion'],
                'funcion_display' => $vf['funcion_display'],
                'searchable' => false,
            ];
        }

        // Guardar en cache
        $cache->save($cacheKey, $this->config, 3600);
    }
    
    /**
     * Agrega una acción global (botón en la cabecera)
     */
    public function addGlobalAction($action)
    {
        $default = [
            'icono' => '',
            'nombre' => '',
            'url' => '',
            'tooltip' => '',
            'target' => '_self'
        ];
        $this->globalActions[] = array_merge($default, $action);
        return $this;
    }

    /**
     * Agrega una acción por fila (dropdown)
     */
    public function addRowAction($action)
    {
        if (empty($action['icono'])) {
            throw new \Exception('La acción debe tener un icono');
        }
        
        if (empty($action['url']) && empty($action['js'])) {
            throw new \Exception('La acción debe tener url o js');
        }
        
        $default = [
            'icono' => '',
            'nombre' => '',
            'url' => '',
            'js' => '',
            'tooltip' => '',
            'target' => '_self'
        ];
        $this->rowActions[] = array_merge($default, $action);
        return $this;
    }    
    /**
     * Carga filtros guardados en sesión
     */
    protected function loadSessionFilters()
    {
        $session = session();
        if ($session->has($this->sessionKey)) {
            $data = $session->get($this->sessionKey);
            $this->filters = $data['filters'] ?? [];
            $this->orderBy = $data['orderBy'] ?? [];  // ← DEBE ESTAR
            $this->perPage = max(1, (int) ($data['perPage'] ?? 25));
            $this->page = max(1, (int) ($data['page'] ?? 1));
            $this->search = $data['search'] ?? '';
        }
        //log_message('debug', "Cargando de sesión [$this->sessionKey]: orderBy=" . json_encode($this->orderBy));
    }
    
    /**
     * Guarda estado actual en sesión
     */
    protected function saveSessionState()
    {
        $session = session();
        $session->set($this->sessionKey, [
            'filters' => $this->filters,
            'orderBy' => $this->orderBy,
            'perPage' => $this->perPage,
            'page' => $this->page,
            'search' => $this->search
        ]);
        //log_message('debug', "Guardando en sesión [$this->sessionKey]: orderBy=" . json_encode($this->orderBy));
    }
    
    /**
     * Aplica filtros adicionales
     */
    public function setFilter($filters)
    {
        $this->filters = array_merge($this->filters, $filters);
        $this->saveSessionState();
        return $this;
    }
    
    /**
     * Establece ordenamiento
     */
    public function setOrderBy($field, $direction = 'ASC')
    {
        $this->orderBy = [$field, $direction];
        $this->saveSessionState();  // ← ESTO GUARDA
        return $this;
    }
    
    /**
     * Establece registros por página (asegura mínimo 1)
     */
    public function setPerPage($perPage)
    {
        $this->perPage = max(1, (int) $perPage);
        $this->saveSessionState();
        return $this;
    }
    
    /**
     * Establece página actual
     */
    public function setPage($page)
    {
        $this->page = max(1, (int) $page);
        $this->saveSessionState();
        return $this;
    }
    
    /**
     * Establece búsqueda global
     */
    public function setSearch($search)
    {
        $this->search = $search;
        $this->saveSessionState();
        return $this;
    }
    
    /**
     * Obtiene datos para el listado
     */
    protected function getListData()
    {
        $builder = $this->db->table($this->table);
        
        // ===========================================
        // 1. FILTROS GLOBALES DE CONFIGURACIÓN
        // ===========================================
        if (!empty($this->config['entity']['filtro_global'])) {
            $globalFilter = $this->config['entity']['filtro_global'];
            $builder->where($globalFilter, session()->get($globalFilter));
        }
        
        // ===========================================
        // 1.5 FILTROS PERSONALIZADOS (setWhere)
        // ===========================================
        foreach ($this->customWheres as $where) {
            list($campo, $operador, $valor) = $where;
            
            // Manejar operadores especiales
            $operador = strtoupper($operador);
            
            switch ($operador) {
                case 'IN':
                    if (is_array($valor)) {
                        $builder->whereIn($campo, $valor);
                    }
                    break;
                    
                case 'NOT IN':
                    if (is_array($valor)) {
                        $builder->whereNotIn($campo, $valor);
                    }
                    break;
                    
                case 'BETWEEN':
                    if (is_array($valor) && count($valor) == 2) {
                        $builder->where($campo . ' BETWEEN ' . $valor[0] . ' AND ' . $valor[1]);
                    }
                    break;
                    
                case 'LIKE':
                case 'NOT LIKE':
                    $builder->like($campo, $valor, 'both', null, $operador === 'NOT LIKE');
                    break;
                    
                default:
                    // Operadores normales: =, >, <, >=, <=, !=, <>
                    $builder->where($campo . ' ' . $operador, $valor);
                    break;
            }
        }

        // ===========================================
        // 2. PROCESAR FILTROS ESPECIALES (RELACIONES)
        // ===========================================
        $filtrosNormales = [];
        
        foreach ($this->filters as $field => $value) {
            if ($value === null || $value === '') continue;
            
            $fieldConfig = $this->config['fields'][$field] ?? null;
            
            // Si es un campo de relación (select)
            if ($fieldConfig && $fieldConfig['type'] === 'select' && !empty($fieldConfig['relacion_tabla'])) {
                
                $parser = $this->parsearFormatoRelacion($fieldConfig['relacion_campo']);
                
                $relatedQuery = $this->db->table($fieldConfig['relacion_tabla']);
                
                // Buscar en todos los campos del formato
                $relatedQuery->groupStart();
                foreach ($parser['campos'] as $campo) {
                    $relatedQuery->orLike($campo, $value);
                }
                $relatedQuery->groupEnd();
                
                $relatedIds = $relatedQuery->select($fieldConfig['relacion_id'])
                                        ->get()
                                        ->getResultArray();
                
                $ids = array_column($relatedIds, $fieldConfig['relacion_id']);
                
                if (!empty($ids)) {
                    $builder->whereIn($field, $ids);
                } else {
                    // No hay resultados: forzar vacío
                    $builder->where($field, null);
                }
            } else {
                // Guardar para procesar después como filtros normales
                $filtrosNormales[$field] = $value;
            }
        }
        
        // ===========================================
        // 3. FILTROS NORMALES (con LIKE)
        // ===========================================
        foreach ($filtrosNormales as $field => $value) {
            if ($value !== null && $value !== '') {
                $builder->like($field, $value);
            }
        }
        
        // ===========================================
        // 4. BÚSQUEDA GLOBAL
        // ===========================================
        if (!empty($this->search) && $this->config['entity']['permite_busqueda']) {
            $searchableFields = $this->getSearchableFields();
            if (!empty($searchableFields)) {
                $builder->groupStart();
                foreach ($searchableFields as $field) {
                    $builder->orLike($field, $this->search);
                }
                $builder->groupEnd();
            }
        }
        
        // ===========================================
        // 5. ORDENAMIENTO
        // ===========================================
        if (!empty($this->orderBy)) {
            $builder->orderBy($this->orderBy[0], $this->orderBy[1]);
        } else {
            $builder->orderBy($this->primaryKey, 'ASC');
        }
        
        // ===========================================
        // 6. PAGINACIÓN
        // ===========================================
        $total = $builder->countAllResults(false);
        $records = $builder->get($this->perPage, ($this->page - 1) * $this->perPage)->getResultArray();
        
        // ===========================================
        // 7. PROCESAR VALORES ESPECIALES SEGÚN TIPO DE CAMPO
        // ===========================================
        $processedRecords = [];
        foreach ($records as $record) {
            $processedRecord = $record;
            
            foreach ($this->config['fields'] as $field => $attrs) {
                $valorOriginal = $record[$field] ?? '';
                
                switch ($attrs['type']) {
                    case 'select':
                        // Relaciones 1 a N
                        if (!empty($attrs['relacion_tabla']) && !empty($record[$field])) {
                            $parser = $this->parsearFormatoRelacion($attrs['relacion_campo']);
                            
                            $relatedQuery = $this->db->table($attrs['relacion_tabla'])
                                                     ->where($attrs['relacion_id'], $record[$field]);
                            
                            $select = $attrs['relacion_id'];
                            foreach ($parser['campos'] as $campo) {
                                $select .= ", {$campo}";
                            }
                            
                            $related = $relatedQuery->select($select)
                                                    ->get()
                                                    ->getRowArray();

                            if ($related) {
                                $texto = $attrs['relacion_campo'];
                                foreach ($parser['campos'] as $campo) {
                                    $campo = trim($campo, '{}'); 
                                    $buscarConLlaves = '{' . $campo . '}'; 
                                    $reemplazar = $related[$campo] ?? ''; 
                                    $texto = str_replace($buscarConLlaves, $reemplazar, $texto); 
                                    $texto = str_replace($campo, $reemplazar, $texto);
                                    //log_message('debug', "Reemplazando '{$buscar}' con '{$reemplazar}': resultado parcial '{$texto}'");
                                }
                                $processedRecord[$field . '_texto'] = $texto;
                                //log_message('debug', "TEXTO FINAL ASIGNADO: '{$texto}'");
                            } else {
                                $processedRecord[$field . '_texto'] = $record[$field];
                            }
                        } else {
                            $processedRecord[$field . '_texto'] = $record[$field];
                        }
                        break;
                        
                    case 'enum':
                        if (!empty($attrs['valores_posibles'])) {
                            $options = explode(',', $attrs['valores_posibles']);
                            // Limpiar espacios
                            $options = array_map('trim', $options);
                            $index = (int) $valorOriginal - 1;
                            $processedRecord[$field . '_texto'] = $options[$index] ?? $valorOriginal;
                            // Asignar clase según el valor (asumimos que 1 es positivo)
                            $processedRecord[$field . '_clase'] = ($valorOriginal == 1 || $valorOriginal === '1') ? 'success' : 'warning';
                        } else {
                            $processedRecord[$field . '_texto'] = $valorOriginal;
                        }
                        break;
                        
                    case 'boolean':
                        $processedRecord[$field . '_texto'] = $valorOriginal ? 'Sí' : 'No';
                        $processedRecord[$field . '_clase'] = $valorOriginal ? 'success' : 'secondary';
                        break;
                        
                    case 'file':
                        if (!empty($valorOriginal)) {
                            $processedRecord[$field . '_texto'] = basename($valorOriginal);
                            $processedRecord[$field . '_url'] = base_url($valorOriginal);
                        } else {
                            $processedRecord[$field . '_texto'] = '';
                        }
                        break;
                        
                    case 'image':
                        if (!empty($valorOriginal)) {
                            $processedRecord[$field . '_url'] = base_url($valorOriginal);
                            $processedRecord[$field . '_texto'] = basename($valorOriginal);
                        } else {
                            $processedRecord[$field . '_texto'] = '';
                        }
                        break;
                        
                    case 'wysiwyg':
                        $processedRecord[$field . '_texto'] = strip_tags($valorOriginal);
                        if (strlen($processedRecord[$field . '_texto']) > 100) {
                            $processedRecord[$field . '_texto'] = substr($processedRecord[$field . '_texto'], 0, 100) . '...';
                        }
                        break;
                        
                    case 'virtual_n_a_n':
                        // Relaciones N a N con formato múltiple
                        if (!empty($attrs['tabla_intermedia']) && !empty($attrs['tabla_fuente'])) {
                            $parser = $this->parsearFormatoRelacion($attrs['formato_visualizacion'] ?? $attrs['campo_mostrar_destino']);
                            
                            $items = $this->db->table($attrs['tabla_intermedia'] . ' ti')
                                                ->join($attrs['tabla_fuente'] . ' tf', 
                                                    'ti.' . $attrs['campo_externo_fk'] . ' = tf.' . $attrs['campo_id_fuente'])
                                                ->where('ti.' . $attrs['campo_local_fk'], $record[$this->primaryKey])
                                                ->select('tf.*')
                                                ->get()
                                                ->getResultArray();
                            
                            $textos = [];
                            foreach ($items as $item) {
                                $texto = $attrs['formato_visualizacion'] ?? $attrs['campo_mostrar_destino'];
                                foreach ($parser['campos'] as $campo) {
                                    // Normalizar el nombre del campo (quita llaves si las trae)
                                    $nombreCampo = trim($campo, '{}');
                                    $valor = $item[$nombreCampo] ?? '';
                                
                                    // Reemplazar tanto la versión con llaves como la versión sin llaves
                                    $texto = str_replace(
                                        ['{'.$nombreCampo.'}', $nombreCampo],
                                        $valor,
                                        $texto
                                    );
                                }
                                $textos[] = $texto;
                            }
                            $processedRecord[$field . '_texto'] = implode(', ', $textos);
                            //log_message('debug', "VIRTUAL N A N - {$field}_texto: " . $processedRecord[$field . '_texto']);
                            $processedRecord[$field . '_texto'] = implode(', ', $textos);
                        } else {
                            $processedRecord[$field . '_texto'] = '';
                        }
                        break;
                        
                    case 'virtual_display':
                        if (!empty($attrs['funcion_display'])) {
                            $processedRecord[$field . '_texto'] = $this->runCallback(
                                $attrs['funcion_display'],
                                $record
                            ) ?? '';
                        } else {
                            $processedRecord[$field . '_texto'] = '';
                        }
                        break;
                        
                    default:
                        // text, number, email, password, textarea, date, datetime
                        $processedRecord[$field . '_texto'] = $valorOriginal;
                }
            }
            
            $processedRecords[] = $processedRecord;
            //log_message('debug', "Procesado: " . json_encode($processedRecord));
        }
        
        return [
            'records' => $processedRecords,
            'total' => $total,
            'page' => $this->page,
            'perPage' => $this->perPage,
            'totalPages' => $this->perPage > 0 ? ceil($total / $this->perPage) : 1,
            'fields' => $this->getVisibleFields(),
            'primaryKey' => $this->primaryKey,
            'entity' => $this->config['entity'],
            'search' => $this->search,
            'filters' => $this->filters,
            'orderBy' => $this->orderBy,
            'globalActions' => $this->globalActions,
            'rowActions' => $this->rowActions,  
            'disableAdd' => $this->disableAdd,
            'disableEdit' => $this->disableEdit,
            'disableDelete' => $this->disableDelete,
            'disableView' => $this->disableView,
            'disableClone' => $this->disableClone,
            'disableExport' => $this->disableExport,
            'disableInline' => $this->disableInline,
            'fieldConfig' => $this->getFieldConfigForInline(),
            'disableSearch' => $this->disableSearch,
        ];
    }
    
    /**
     * Obtiene campos visibles en listado
     */
    protected function getVisibleFields()
    {
        $visible = [];
        foreach ($this->config['fields'] as $field => $attrs) {
            if (!$attrs['hidden_in_list']) {
                $visible[$field] = $attrs;
            }
        }
        return $visible;
    }
    
    /**
     * Obtiene campos buscables
     */
    protected function getSearchableFields()
    {
        $searchable = [];
        foreach ($this->config['fields'] as $field => $attrs) {
            if ($attrs['searchable']) {
                $searchable[] = $field;
            }
        }
        return $searchable;
    }
    
    /**
     * Renderiza el CRUD según la acción
     */
    public function render()
    {
        $output = new \stdClass();

        // ===========================================
        // CARGAR TEMA GLOBAL (si está configurado)
        // ===========================================
        $config = config('TorCrudConfig');
        
        $output->css = [
            base_url('assets/css/tom-select.min.css'),
            base_url('assets/css/tom-select.bootstrap5.min.css'),
            base_url('assets/css/quill.snow.css'),
            base_url('assets/css/tor-crud.css')
        ];
        $output->js = [
            //'https://code.jquery.com/jquery-3.7.1.min.js',
            base_url('assets/js/quill.min.js'),
            base_url('assets/js/tom-select.complete.min.js'),
            base_url('assets/js/tor-crud-form.js')
        ];
        if (!empty($config->tema)) {
            $temaPath = $config->rutaTemas . $config->tema;
            $output->css[] = base_url($temaPath);
        }
        
        $action = $this->getCurrentAction();
        
        // ===========================================
        // VERIFICAR ACCIONES DESHABILITADAS
        // ===========================================
        if ($this->isActionDisabled($action)) {
            session()->setFlashdata('error', 'Acción no permitida');
            return redirect()->to($this->getBaseUrl());
        }
        
        // Acciones de eliminación
        if ($action === 'tcdelete') {
            $id = $this->getCurrentId();
            $this->processDelete($id);
            session()->setFlashdata('success', 'Registro eliminado');
            return redirect()->to($this->getBaseUrl());
        }
        
        if ($action === 'tcbulkdelete') {
            $ids = $this->request->getPost('ids');
            $this->processBulkDelete($ids);
            session()->setFlashdata('success', 'Registros eliminados');
            return redirect()->to($this->getBaseUrl());
        }

        // ===========================================
        // ACCIONES DE EDICIÓN EN LÍNEA
        // ===========================================
        if ($action === 'tcinlineedit') {
            $id = $this->getCurrentId();
            return $this->processInlineEdit($id);
        }

        // ===========================================
        // NUEVO: EXPORTACIÓN (tampoco renderiza vista)
        // ===========================================
        if ($action === 'tcexportcsv') {
            return $this->exportCSV(); // Devuelve la descarga del archivo
        }

        if ($action === 'tcexportpdf') {
            return $this->exportPDF();
        }
        
        if ($action === 'tcprint') {
            return $this->printView();
        }

        // Capturar parámetros para listado
        $this->capturarParametros();

        switch ($action) {

            case 'list':
                $data = $this->getListData();
                $data = array_merge($data, $this->viewData);
                return view('tor_crud/list', [
                    'data' => $data,
                    'css' => $output->css,
                    'js' => $output->js
                ]);
                break;
                
            case 'tcadd':
                if ($this->request->getMethod() === 'POST') {
                    return $this->processForm();
                }
                $data = $this->getFormData();
                $data['action'] = 'create';
                $data = array_merge($data, $this->viewData);
                return view('tor_crud/form', [
                    'data' => $data,
                    'css' => $output->css,
                    'js' => $output->js
                ]);
                break;
                
            case 'tcedit':
                $id = $this->getCurrentId();
                if ($this->request->getMethod() === 'POST') {
                    return $this->processForm($id);
                }
                $data = $this->getFormData($id);
                $data['action'] = 'edit';
                $data = array_merge($data, $this->viewData);
                return view('tor_crud/form', [
                    'data' => $data,
                    'css' => $output->css,
                    'js' => $output->js
                ]);
                break;
                
            case 'tcclone':
                $id = $this->getCurrentId();
                if ($this->request->getMethod() === 'POST') {
                    return $this->processForm();
                }
                $data = $this->getCloneData($id);
                $data['action'] = 'create';
                $data = array_merge($data, $this->viewData);
                return view('tor_crud/form', [
                    'data' => $data,
                    'css' => $output->css,
                    'js' => $output->js
                ]);
                break;
                
            case 'tcview':
                $id = $this->getCurrentId();
                $data = $this->getViewData($id);
                $data = array_merge($data, $this->viewData);
                return view('tor_crud/view', [
                    'data' => $data,
                    'css' => $output->css,
                    'js' => $output->js
                ]);
                break;

            default:
                $data = $this->getListData();
                $data = array_merge($data, $this->viewData);
                return view('tor_crud/list', [
                    'data' => $data,
                    'css' => $output->css,
                    'js' => $output->js
                ]);
        }
        
        return $output;
    }

    /**
     * Obtiene los datos para clonar un registro existente
     * 
     * @param int $id ID del registro a clonar
     * @return array Datos formateados para la vista del formulario
     */
    protected function getCloneData($id)
    {
        // ===========================================
        // 1. Obtener los datos base desde getFormData (¡REUTILIZACIÓN!)
        // ===========================================
        $data = $this->getFormData($id);
        
        // ===========================================
        // 2. Convertir de edición a creación
        // ===========================================
        $data['action'] = 'create';  // Forzar a creación
        
        // ===========================================
        // 3. ELIMINAR LA CLAVE PRIMARIA
        //    Esto es crucial para que al guardar se haga un INSERT nuevo
        // ===========================================
        if (isset($data['record'][$this->primaryKey])) {
            unset($data['record'][$this->primaryKey]);
        }
        
        // ===========================================
        // 4. Las relaciones N a N YA VIENEN CARGADAS desde getFormData
        //    (gracias al bloque que carga $data['record'][$field] para virtual_n_a_n)
        // ===========================================
        
        return $data;
    }

    protected function getFormData($id = null)
    {
        //log_message('debug', 'CAMPOS EN getFormData: ' . print_r(array_keys($this->config['fields']), true));
        
        $data = [
            'entity' => $this->config['entity'],
            'fields' => $this->config['fields'],
            'primaryKey' => $this->primaryKey,
            'record' => [],
            'errors' => session()->getFlashdata('errors') ?? [],
            'action' => $id ? 'edit' : 'create',
            'baseUrl' => $this->getBaseUrl(),
            'selectOptions' => []
        ];
    
        if ($id) {
            $record = $this->db->table($this->table)
                              ->where($this->primaryKey, $id)
                              ->get()
                              ->getRowArray();
            
            if ($record) {
                $data['record'] = $record;
                
                // Cargar relaciones N a N (virtuales)
                foreach ($this->config['fields'] as $field => $attrs) {
                    if ($attrs['type'] === 'virtual_n_a_n') {
                        $items = $this->db->table($attrs['tabla_intermedia'])
                                         ->where($attrs['campo_local_fk'], $id)
                                         ->select($attrs['campo_externo_fk'])
                                         ->get()
                                         ->getResultArray();
                        $data['record'][$field] = array_column($items, $attrs['campo_externo_fk']);
                    }
                }
            }
        }
    
        // ===========================================
        // CARGAR OPCIONES PARA CAMPOS SELECT Y ENUM
        // ===========================================
        foreach ($this->config['fields'] as $field => $attrs) {
            $options = [];
            
            // ===========================================
            // SELECT (1 a N)
            // ===========================================
            if ($attrs['type'] === 'select' && !empty($attrs['relacion_tabla'])) {
                // Si no tiene llaves, se las agregamos
                $formato = $attrs['relacion_campo'];
                if (strpos($formato, '{') === false) {
                    $formato = '{' . $formato . '}';
                }
                
                $parser = $this->parsearFormatoRelacion($formato);
                
                $select = "{$attrs['relacion_id']} as value";
                foreach ($parser['campos'] as $campo) {
                    $select .= ", {$campo}";
                }
                
                $rows = $this->db->table($attrs['relacion_tabla'])
                                 ->select($select)
                                 ->orderBy($parser['campos'][0] ?? $attrs['relacion_id'], 'ASC')
                                 ->get()
                                 ->getResultArray();
                
                $options = [];
                foreach ($rows as $row) {
                    $texto = $formato;
                    foreach ($parser['campos'] as $campo) {
                        $texto = str_replace('{' . $campo . '}', $row[$campo], $texto);
                    }
                    $options[] = [
                        'value' => $row['value'],
                        'text' => $texto
                    ];
                }
                
                $data['selectOptions'][$field] = $options;
            }
            
            // ===========================================
            // ENUM
            // ===========================================
            if ($attrs['type'] === 'enum') {
                if ($attrs['tipo_real'] === 'enum') {
                    // Enum real
                    $opciones = explode(',', $attrs['valores_posibles']);
                    foreach ($opciones as $texto) {
                        $options[] = [
                            'value' => trim($texto),
                            'text' => trim($texto)
                        ];
                    }
                } else {
                    // Enum fijo
                    $opciones = explode(',', $attrs['valores_posibles']);
                    foreach ($opciones as $index => $texto) {
                        $options[] = [
                            'value' => $index + 1,
                            'text' => trim($texto)
                        ];
                    }
                }
                
                if (!empty($options)) {
                    $data['selectOptions'][$field] = $options;
                }
            }
    
            // ===========================================
            // VIRTUAL N A N
            // ===========================================
            if ($attrs['type'] === 'virtual_n_a_n') {
                // Si no tiene llaves, se las agregamos
                $formato = $attrs['formato_visualizacion'];
                if (strpos($formato, '{') === false) {
                    $formato = '{' . $formato . '}';
                }
                
                $parser = $this->parsearFormatoRelacion($formato);
                
                $select = "{$attrs['campo_id_fuente']} as value";
                foreach ($parser['campos'] as $campo) {
                    $select .= ", {$campo}";
                }
                
                $rows = $this->db->table($attrs['tabla_fuente'])
                                 ->select($select)
                                 ->orderBy($parser['campos'][0] ?? $attrs['campo_id_fuente'], 'ASC')
                                 ->get()
                                 ->getResultArray();
                
                $options = [];
                foreach ($rows as $row) {
                    $texto = $formato;
                    foreach ($parser['campos'] as $campo) {
                        $texto = str_replace('{' . $campo . '}', $row[$campo], $texto);
                    }
                    $options[] = [
                        'value' => $row['value'],
                        'text' => $texto
                    ];
                }
                
                $data['selectOptions'][$field] = $options;
            }
        }
    
        return $data;
    }

    protected function processForm($id = null)
    {
        // ===========================================
        // VERIFICAR QUE LA EDICIÓN/CREACIÓN ESTÉ PERMITIDA
        // ===========================================
        if ($id && $this->disableEdit) {
            session()->setFlashdata('error', 'Edición no permitida');
            return redirect()->to($this->getBaseUrl());
        }
        
        if (!$id && $this->disableAdd) {
            session()->setFlashdata('error', 'Creación no permitida');
            return redirect()->to($this->getBaseUrl());
        }
    
        $data = $this->request->getPost();

        $action = $this->request->getPost('form_action');
        $esClonacion = (strpos(current_url(), '/tcclone/') !== false);

        // ===========================================
        // PROCESAR CAMPOS HIDDEN
        // ===========================================
        foreach ($this->config['fields'] as $field => $attrs) {
            if ($attrs['type'] !== 'hidden') continue;
            
            // Si ya viene un valor en POST (raro, pero por si acaso)
            if (isset($data[$field])) {
                continue;
            }
            
            switch ($attrs['comportamiento_hidden'] ?? null) {
                case 'forzar_valor':
                    $valorDefault = $attrs['valor_default'] ?? '';
    
                    if ($valorDefault === '__NOW__') {
                        $data[$field] = date('Y-m-d H:i:s');
                        //log_message('debug', "Campo hidden {$field}: asignado __NOW__ = " . $data[$field]);
                    } elseif ($valorDefault === '__USER_ID__') {
                        $data[$field] = session()->get('user_id') ?? 0;
                        //log_message('debug', "Campo hidden {$field}: asignado __USER_ID__ = " . $data[$field]);
                    } elseif (strpos($valorDefault, '__CONTROLADOR__:') === 0) {
                        // Extraer el nombre de la propiedad después de '__CONTROLADOR__:'
                        $nombrePropiedad = substr($valorDefault, 16); 
                        $valorPropiedad = $this->getControllerProperty($nombrePropiedad);
                        
                        if ($valorPropiedad !== null) {
                            $data[$field] = $valorPropiedad;
                            //log_message('debug', "Campo hidden {$field}: asignado desde controlador (\${$nombrePropiedad}) = " . print_r($valorPropiedad, true));
                        } else {
                            // Si no se pudo obtener, quizás se deja null o se lanza un error controlado
                            // Podrías decidir no asignar nada (unset) o lanzar una excepción
                            log_message('error', "Campo hidden {$field}: no se pudo obtener el valor de '\$this->{$nombrePropiedad}' desde el controlador.");
                            // Opcional: forzar un error de validación
                            // $validation->setError($field, "No se pudo obtener el valor automático del controlador.");
                        }
                    } else {
                        // Valor fijo
                        $data[$field] = $valorDefault;
                        //log_message('debug', "Campo hidden {$field}: asignado valor fijo = " . $data[$field]);
                    }
                    break;
    
                case 'dejar_null':
                    $data[$field] = null;
                    //log_message('debug', "Campo hidden {$field}: asignado NULL");
                    break;
    
                case 'usar_default_db':
                    // No lo incluimos en el array $data, la BD usará su DEFAULT
                    unset($data[$field]);
                    //log_message('debug', "Campo hidden {$field}: omitido (usará default de BD)");
                    break;
    
                default:
                    // Sin comportamiento definido, se omite
                    unset($data[$field]);
                    break;
            }
        }

        // ===========================================
        // 1. VALIDACIÓN
        // ===========================================
        $validation = \Config\Services::validation();
        $rules = [];
        foreach ($this->config['fields'] as $field => $attrs) {
            if (!empty($attrs['validation_rules'])) {
                $rules[$field] = $attrs['validation_rules'];
            }
        }
        
        //log_message('debug', 'REGLAS: ' . print_r($rules, true));
        
        if (!$validation->setRules($rules)->run($data)) {
            log_message('debug', 'ERRORES DE VALIDACIÓN: ' . print_r($validation->getErrors(), true));
            session()->setFlashdata('errors', $validation->getErrors());
            return redirect()->back()->withInput();
        }

        // ===========================================
        // CALLBACK BEFORE INSERT / BEFORE UPDATE
        // ===========================================
        $callbackName = $id ? $this->beforeUpdateCallback : $this->beforeInsertCallback;
        if ($callbackName) {
            $params = $id ? [$id, &$data] : [&$data];
            $result = $this->runCallback($callbackName, ...$params);
            
            if ($result === false) {
                session()->setFlashdata('error', 'Operación cancelada por callback');
                return redirect()->back()->withInput();
            }
            
            // Si el callback devuelve un array, reemplazar $data
            if (is_array($result)) {
                $data = $result;
            }
        }
            
        // ===========================================
        // PROCESAR ARCHIVOS (file e image por separado)
        // ===========================================
        $files = $this->request->getFiles();

        foreach ($this->config['fields'] as $field => $attrs) {
            // ===========================================
            // CAMPO FILE
            // ===========================================
            if ($attrs['type'] === 'file') {
                // Si se marcó "Quitar archivo"
                if (!empty($data['remove_' . $field])) {
                    // Eliminar archivo físico si existe
                    if (!empty($recordId)) {
                        $oldRecord = $this->db->table($this->table)
                                            ->select($field)
                                            ->where($this->primaryKey, $recordId)
                                            ->get()
                                            ->getRowArray();
                        if (!empty($oldRecord[$field])) {
                            $oldPath = WRITEPATH . $oldRecord[$field];
                            if (file_exists($oldPath)) {
                                unlink($oldPath);
                            }
                        }
                    }
                    $data[$field] = null;
                    continue;
                }
                
                // Si hay archivo nuevo subido
                if (isset($files[$field]) && $files[$field]->isValid()) {
                    $file = $files[$field];
                    
                    // BEFORE UPLOAD
                    if ($this->beforeUploadCallback) {
                        $uploadResult = $this->runCallback($this->beforeUploadCallback, $file, $data);
                        if ($uploadResult === false) {
                            session()->setFlashdata('error', 'Subida cancelada por callback');
                            return redirect()->back()->withInput();
                        }
                    }

                    // Validar tipo (desde BD)
                    $allowedTypes = $attrs['archivo_tipo_permitido'] ?? '';
                    if (!empty($allowedTypes)) {
                        $extension = strtolower($file->getExtension());
                        $allowed = array_map('trim', explode(',', strtolower($allowedTypes)));
                        if (!in_array($extension, $allowed)) {
                            session()->setFlashdata('errors', [$field => "Tipo de archivo no permitido. Permitidos: " . $allowedTypes]);
                            return redirect()->back()->withInput();
                        }
                    }
                    
                    // Validar tamaño (desde BD)
                    $maxSizeMB = (float)($attrs['archivo_tamano_maximo'] ?? 2);
                    $maxSizeBytes = $maxSizeMB * 1024 * 1024;
                    if ($file->getSize() > $maxSizeBytes) {
                        session()->setFlashdata('errors', [$field => "Archivo demasiado grande. Máximo " . $maxSizeMB . " MB"]);
                        return redirect()->back()->withInput();
                    }
                    
                    // Eliminar archivo anterior si existe (en edición)
                    if (!empty($recordId)) {
                        $oldRecord = $this->db->table($this->table)
                                            ->select($field)
                                            ->where($this->primaryKey, $recordId)
                                            ->get()
                                            ->getRowArray();
                        if (!empty($oldRecord[$field])) {
                            $oldPath = WRITEPATH . $oldRecord[$field];
                            if (file_exists($oldPath)) {
                                unlink($oldPath);
                            }
                        }
                    }
                    
                    // Generar nombre único (timestamp + original)
                    $originalName = pathinfo($file->getName(), PATHINFO_FILENAME);
                    $extension = $file->getExtension();
                    $fileName = time() . '_' . uniqid() . '_' . $originalName . '.' . $extension;
                    
                    // Determinar carpeta destino (misma lógica que en image)
                    if (!empty($attrs['archivo_carpeta_destino'])) {
                        $uploadPath = $attrs['archivo_carpeta_destino'];
                    } else {
                        $uploadPath = 'uploads';
                        if (!empty($attrs['archivo_subcarpeta_por_entidad'])) {
                            $uploadPath .= '/' . $this->table;
                        }
                    }

                    // Normalizar y crear carpeta
                    $uploadPath = str_replace('\\', '/', $uploadPath);
                    $physicalPath = FCPATH . $uploadPath;

                    if (!is_dir($physicalPath)) {
                        mkdir($physicalPath, 0755, true);
                    }

                    // Mover archivo
                    $file->move($physicalPath, $fileName);

                    // Guardar SOLO el nombre (la ruta se completa al mostrar)
                    $data[$field] = $fileName;

                    // AFTER UPLOAD
                    if ($this->afterUploadCallback) {
                        $this->runCallback($this->afterUploadCallback, $file, $data, $uploadPath . '/' . $fileName);
                    }
                }
            }
            
            // ===========================================
            // CAMPO IMAGE
            // ===========================================
            if ($attrs['type'] === 'image') {

                // Si se marcó "Quitar archivo"
                if (!empty($data['remove_' . $field])) {
                    // Eliminar archivo físico si existe
                    if (!empty($recordId)) {
                        $oldRecord = $this->db->table($this->table)
                                            ->select($field)
                                            ->where($this->primaryKey, $recordId)
                                            ->get()
                                            ->getRowArray();
                        if (!empty($oldRecord[$field])) {
                            $oldPath = WRITEPATH . $oldRecord[$field];
                            if (file_exists($oldPath)) {
                                unlink($oldPath);
                            }
                        }
                    }
                    $data[$field] = null;
                    continue;
                }
                
                // Si hay archivo nuevo subido
                if (isset($files[$field]) && $files[$field]->isValid()) {
                    $file = $files[$field];
                    
                    // Validar tipo (solo imágenes)
                    $allowedTypes = $attrs['archivo_tipo_permitido'] ?? 'jpg,jpeg,png,gif,webp';
                    $extension = strtolower($file->getExtension());
                    $allowed = array_map('trim', explode(',', strtolower($allowedTypes)));
                    if (!in_array($extension, $allowed)) {
                        session()->setFlashdata('errors', [$field => "Tipo de imagen no permitido. Permitidos: " . $allowedTypes]);
                        return redirect()->back()->withInput();
                    }
                    
                    // Validar tamaño
                    $maxSizeMB = (float)($attrs['archivo_tamano_maximo'] ?? 2);
                    $maxSizeBytes = $maxSizeMB * 1024 * 1024;
                    if ($file->getSize() > $maxSizeBytes) {
                        session()->setFlashdata('errors', [$field => "Imagen demasiado grande. Máximo " . $maxSizeMB . " MB"]);
                        return redirect()->back()->withInput();
                    }
                    
                    // Eliminar imagen anterior si existe (en edición)
                    if (!empty($recordId)) {
                        $oldRecord = $this->db->table($this->table)
                                            ->select($field)
                                            ->where($this->primaryKey, $recordId)
                                            ->get()
                                            ->getRowArray();
                        if (!empty($oldRecord[$field])) {
                            $oldPath = WRITEPATH . $oldRecord[$field];
                            if (file_exists($oldPath)) {
                                unlink($oldPath);
                            }
                        }
                    }
                    
                    // Generar nombre único
                    $originalName = pathinfo($file->getName(), PATHINFO_FILENAME);
                    $extension = $file->getExtension();
                    $fileName = time() . '_' . uniqid() . '_' . $originalName . '.' . $extension;
                    
                    // Determinar carpeta destino
                    if (!empty($attrs['archivo_carpeta_destino'])) {
                        $uploadPath = $attrs['archivo_carpeta_destino'];
                    } else {
                        $uploadPath = 'uploads';
                        if (!empty($attrs['archivo_subcarpeta_por_entidad'])) {
                            $uploadPath .= '/' . $this->table;
                        }
                    }

                    // Normalizar separadores
                    $uploadPath = str_replace('\\', '/', $uploadPath);

                    // Ruta física
                    $physicalPath = FCPATH . $uploadPath;

                    // Crear carpeta si no existe
                    if (!is_dir($physicalPath)) {
                        mkdir($physicalPath, 0755, true);
                    }

                    // Crear carpeta en public/
                    if (!is_dir(FCPATH . $uploadPath)) {
                        mkdir(FCPATH . $uploadPath, 0755, true);
                    }

                    // Mover archivo
                    $file->move(FCPATH . $uploadPath, $fileName);

                    // Guardar solo el nombre (la URL se construirá con base_url())
                    $data[$field] = $fileName;
                }
            }
        }
        
        // Eliminar campos remove_* antes de guardar
        foreach (array_keys($data) as $key) {
            if (strpos($key, 'remove_') === 0) {
                unset($data[$key]);
            }
        }

        // ===========================================
        // 2. GUARDADO
        // ===========================================
        $db = \Config\Database::connect();
        
        // Eliminar campos que no deben ir en la BD (csrf, form_action)
        unset($data['csrf_test_name']);
        unset($data['form_action']);
        
        $originalData = $data;
        // Eliminar campos virtuales del array de datos
        foreach ($this->config['fields'] as $field => $attrs) {
            if (strpos($attrs['type'], 'virtual_') === 0) {
                unset($data[$field]);
            }
        }

        // Antes de actualizar, guardar los datos originales para auditoria
        $datosAnteriores = null;
        if ($id) {
            $datosAnteriores = $this->db->table($this->table)
                                        ->where($this->primaryKey, $id)
                                        ->get()
                                        ->getRowArray();
        }

        if ($id) {
            $db->table($this->table)->where($this->primaryKey, $id)->update($data);
            //log_message('debug', 'UPDATE ejecutado, filas afectadas: ' . $db->affectedRows());
            $recordId = $id;
        } else {
            $db->table($this->table)->insert($data);
            $recordId = $db->insertID();
            //log_message('debug', 'INSERT ejecutado, nuevo ID: ' . $recordId);
        }


        // inicio de auditoria
        if (!$id) {
            $this->auditoriaService->insert(
                $this->table,
                $recordId,
                $data // Los datos que se insertaron
            );
        }

        if ($id && $datosAnteriores) {
            $this->auditoriaService->update(
                $this->table,
                $id,
                $datosAnteriores,
                $data // Los nuevos datos
            );
        }
        //fin de auditoria
        
        if ($db->error()['code'] != 0) {
            //log_message('error', 'ERROR BD: ' . print_r($db->error(), true));
            return redirect()->back()->with('error', 'Error al guardar')->withInput();
        }

        // ===========================================
        // 3. GUARDAR RELACIONES N A N (VIRTUALES)
        // ===========================================
        foreach ($this->config['fields'] as $field => $attrs) {
            if ($attrs['type'] === 'virtual_n_a_n' && isset($originalData[$field])) {
                //log_message('debug', "Datos para {$field}: " . print_r($originalData[$field] ?? [], true));
                $selected = $originalData[$field] ?? [];
                $table = $attrs['tabla_intermedia'];
                $localFk = $attrs['campo_local_fk'];
                $foreignFk = $attrs['campo_externo_fk'];
                
                // Eliminar relaciones anteriores
                $db->table($table)->where($localFk, $recordId)->delete();
                
                // Insertar nuevas
                foreach ($selected as $value) {
                    $db->table($table)->insert([
                        $localFk => $recordId,
                        $foreignFk => $value
                    ]);
                }
            }
        }
        
        // ===========================================
        // CALLBACK AFTER INSERT / AFTER UPDATE
        // ===========================================
        $callbackName = $id ? $this->afterUpdateCallback : $this->afterInsertCallback;
        if ($callbackName) {
            $this->runCallback($callbackName, $recordId, $data);
        }
        
        // ===========================================
        // 3. REDIRECCIÓN
        // ===========================================
        // Redirección según botón
        // Redirección según botón
 
        if ($action === 'save_and_back') {
            return redirect()->to($this->getBaseUrl())->with('success', 'Registro guardado');
        } else {
            if ($id) {
                return redirect()->to($this->getCurrentUrl())->with('success', 'Registro actualizado');
            } else {
                if ($esClonacion && $recordId) {
                    // Clonación: redirigir a la misma URL de clonación con el nuevo ID
                    $baseUrl = $this->getBaseUrl();
                    return redirect()->to($baseUrl . '/tcclone/' . $recordId)->with('success', 'Registro clonado correctamente');
                } else {
                    return redirect()->to($this->getCurrentUrl())->with('success', 'Registro creado');
                }
            }
        }
    }

    /**
     * Obtiene la acción actual desde la URL
     */
    protected function getCurrentAction()
    {
        $uri = service('uri');
        $segments = $uri->getSegments();
        
        foreach ($segments as $segment) {
            if (strpos($segment, 'tc') === 0) {
                return $segment;
            }
        }
        
        return 'list';
    }
    
    protected function loadValidationRulesForField($campoId)
    {
        $reglas = $this->db->table('tor_reglas_validacion')
                           ->where('campo_id', $campoId)
                           ->get()
                           ->getResultArray();
        
        if (empty($reglas)) {
            return '';
        }
        
        $ruleStrings = [];
        foreach ($reglas as $regla) {
            $rule = $regla['regla_tipo'];
            // Verificar si el parámetro existe y no es null
            if (isset($regla['parametro_valor']) && $regla['parametro_valor'] !== null) {
                $rule .= '[' . $regla['parametro_valor'] . ']';
            }
            $ruleStrings[] = $rule;
        }
        
        $result = implode('|', $ruleStrings);
        //log_message('debug', "Reglas encontradas para campo {$campoId}: " . $result);
        return $result;
    }

    /**
     * Obtiene el ID de la URL para acciones como tcedit/123
     */
    protected function getCurrentId()
    {
        $uri = service('uri');
        $segments = $uri->getSegments();
        
        for ($i = 0; $i < count($segments); $i++) {
            if (strpos($segments[$i], 'tc') === 0 && isset($segments[$i + 1])) {
                return $segments[$i + 1];
            }
        }
        
        return null;
    }
    

    /**
     * Resetea el orden personalizado (vuelve a primary key)
     */
    public function resetOrder()
    {
        $this->orderBy = [];
        $this->saveSessionState();  // ← ESTO GUARDA
        return $this;
    }

    /**
     * Captura automáticamente todos los parámetros de la URL
     */
    protected function capturarParametros()
    {
        $request = service('request');
        
        if ($this->disableSearch) {
            // Solo cargar orden y página, pero no filtros
            $page = $request->getGet('page');
            if ($page !== null) {
                $this->page = max(1, (int) $page);
            }
            
            $orderBy = $request->getGet('orderBy');
            $orderDir = $request->getGet('orderDir');
            if (!empty($orderBy) && in_array($orderDir, ['ASC', 'DESC'])) {
                $this->orderBy = [$orderBy, $orderDir];
            }
            
            $this->saveSessionState();
            return;
        }
        // ===========================================
        // Si no hay parámetros GET, restaurar desde sesión
        // ===========================================
        if (empty($_GET)) {
            $this->loadSessionFilters();
            return;
        }

        // Guardar estado anterior de filtros para detectar cambios
        $filtersAnteriores = $this->filters;
        
        // ===========================================
        // RESET GENERAL DE FILTROS
        // ===========================================
        if ($request->getGet('reset_filters')) {
            $this->resetFilters();
            // No continuar procesando, ya se resetearon todos
            return;
        }
        
        // ===========================================
        // BÚSQUEDA GLOBAL
        // ===========================================
        $this->search = $request->getGet('search') ?? $this->search;
        
        // ===========================================
        // PAGINACIÓN
        // ===========================================
        $page = $request->getGet('page');
        if ($page !== null) {
            $this->page = max(1, (int) $page);
        }
        
        $perPage = $request->getGet('perPage');
        if ($perPage !== null) {
            $this->perPage = max(1, (int) $perPage);
        }
        
        // ===========================================
        // ORDENAMIENTO
        // ===========================================
        $orderBy = $request->getGet('orderBy');
        $orderDir = $request->getGet('orderDir');
        if (!empty($orderBy) && in_array($orderDir, ['ASC', 'DESC'])) {
            $this->orderBy = [$orderBy, $orderDir];
        }
        
        // ===========================================
        // FILTROS POR COLUMNA
        // ===========================================
        // Primero, recopilar todos los filtros que vienen en GET
        $filtersInGet = [];
        foreach ($request->getGet() as $key => $value) {
            if (strpos($key, 'filter_') === 0) {
                $field = substr($key, 7);
                $filtersInGet[$field] = true;
                
                if ($value !== null && $value !== '') {
                    $this->filters[$field] = $value;
                } else {
                    unset($this->filters[$field]);
                }
            }
        }
        
        // Eliminar de $this->filters los filtros que ya no están en GET
        foreach (array_keys($this->filters) as $field) {
            if (!isset($filtersInGet[$field])) {
                unset($this->filters[$field]);
            }
        }
        
        // ===========================================
        // RESET DE PÁGINA SI CAMBIARON LOS FILTROS
        // ===========================================
        if ($filtersAnteriores != $this->filters) {
            $this->page = 1;
        }
        
        // ===========================================
        // GUARDAR ESTADO EN SESIÓN
        // ===========================================
        $this->saveSessionState();
    }

    /**
     * Resetea todos los filtros y la búsqueda
     */
    public function resetFilters()
    {
        $this->filters = [];
        $this->search = '';
        $this->orderBy = [];
        $this->page = 1;
        $this->saveSessionState();
        return $this;
    }

    /**
     * Parsea el formato de visualización y devuelve los campos a buscar/mostrar
     * @param string $formato Ej: "{categoria} - {generacion}" o "categoria"
     * @return array ['campos' => ['categoria','generacion'], 'tieneFormato' => bool]
     */
    private function parsearFormatoRelacion($formato)
    {
        // Eliminar cualquier llave suelta
        $formato = preg_replace('/[{}]/', '', $formato);
        
        $campos = [];
        $tieneFormato = false;
        
        // Buscar patrones de campos (si los hay)
        if (preg_match_all('/([a-zA-Z0-9_]+)/', $formato, $matches)) {
            $campos = array_unique($matches[1]);
            $tieneFormato = true;
        } else {
            $campos = [trim($formato)];
        }
        
        return [
            'campos' => $campos,
            'tieneFormato' => $tieneFormato,
            'formato' => $formato
        ];
    }

    /**
     * Procesa la eliminación individual
     */
    protected function processDelete($id)
    {
        // BEFORE DELETE
        if ($this->beforeDeleteCallback) {
            $result = $this->runCallback($this->beforeDeleteCallback, $id);
            if ($result === false) {
                return false;
            }
        }

        // Obtener datos antes de eliminar
        $datosAnteriores = $this->db->table($this->table)
        ->where($this->primaryKey, $id)
        ->get()
        ->getRowArray();
        
        // Ejecutar eliminación
        $this->db->table($this->table)->where($this->primaryKey, $id)->delete();
        
        // Auditar
        $this->auditoriaService->delete(
            $this->table,
            $id,
            $datosAnteriores
        );

        // AFTER DELETE
        if ($this->afterDeleteCallback) {
            $this->runCallback($this->afterDeleteCallback, $id);
        }

        // afterDelete callback
        /*** chequear luego como se ejecutaba antes un callback_
        if (!empty($this->afterDelete)) {
            $controller = service('router')->controllerName();
            $method = $this->afterDelete;
            if (method_exists($controller, $method)) {
                $controllerInstance = new $controller();
                $controllerInstance->$method($id);
            }
        }
        */
        return true;
    }

    /**
     * Procesa la eliminación masiva
     */
    protected function processBulkDelete($ids)
    {
        if (empty($ids)) {
            return false;
        }
        
         // BEFORE BULK DELETE
        if ($this->beforeBulkDeleteCallback) {
            $result = $this->runCallback($this->beforeBulkDeleteCallback, $ids);
            if ($result === false) {
                return false;
            }
        }
        // Obtener datos de todos los registros ANTES de eliminarlos
        $registros = $this->db->table($this->table)
                          ->whereIn($this->primaryKey, $ids)
                          ->get()
                          ->getResultArray();
        
        // Ejecutar eliminación
        $this->db->table($this->table)->whereIn($this->primaryKey, $ids)->delete();
        
        // Auditar cada registro eliminado
        foreach ($registros as $registro) {
            $this->auditoriaService->delete(
                $this->table,
                $registro[$this->primaryKey],
                $registro
            );
        }
        // AFTER BULK DELETE
        if ($this->afterBulkDeleteCallback) {
            $this->runCallback($this->afterBulkDeleteCallback, $ids);
        }

        return true;
    }

    /**
     * Obtiene la URL base sin el segmento 'index' si existe
     */
    protected function getBaseUrl()
    {
        $url = current_url();
        
        // Eliminar /index si está al final
        $url = preg_replace('/\/index$/', '', $url);
        
        // Eliminar cualquier acción tc... al final (con o sin número)
        $url = preg_replace('/\/tc\w+(\/\d+)?$/', '', $url);
        
        return $url;
    }
    /**
     * Obtiene la URL actual sin query string
     */
    protected function getCurrentUrl()
    {
        $url = current_url();
        // Eliminar /index si está al final
        $url = preg_replace('/\/index$/', '', $url);
        return $url;
    }
    
    /**
     * Obtiene el valor de una propiedad desde el controlador actual.
     * 
     * @param string $nombrePropiedad El nombre de la propiedad a obtener (ej: 'id_empresa')
     * @return mixed|null El valor de la propiedad o null si no existe/accesible.
     */
    private function getControllerProperty($nombrePropiedad)
    {
        // Obtener el segmento de la URI que contiene el nombre del controlador
        $uri = service('uri');
        $segments = $uri->getSegments();
        
        // El primer segmento suele ser el controlador (ej: 'mi_categorias')
        if (empty($segments)) {
            //log_message('error', "Tor_Crud: No se pudo determinar el controlador desde la URI.");
            return null;
        }
        
        $controllerSegment = $segments[0];
        
        // Construir el nombre completo de la clase del controlador
        // Asumiendo que tus controladores están en App\Controllers y usan PascalCase
        $controllerName = 'App\\Controllers\\' . ucfirst($controllerSegment);
        
        if (!class_exists($controllerName)) {
            //log_message('error', "Tor_Crud: La clase del controlador '{$controllerName}' no existe.");
            return null;
        }

        try {
            // Instanciar el controlador directamente
            $controller = new $controllerName();
            
            // Verificar si la propiedad existe y es pública
            if (property_exists($controller, $nombrePropiedad)) {
                $reflectionProperty = new \ReflectionProperty($controller, $nombrePropiedad);
                if ($reflectionProperty->isPublic()) {
                    return $controller->$nombrePropiedad;
                } else {
                    //log_message('error', "Tor_Crud: La propiedad '{$nombrePropiedad}' en el controlador '{$controllerName}' no es pública.");
                }
            } else {
                //log_message('error', "Tor_Crud: La propiedad '{$nombrePropiedad}' no existe en el controlador '{$controllerName}'.");
            }
        } catch (\ReflectionException $e) {
            //log_message('error', "Tor_Crud: Error de reflexión al acceder a la propiedad '{$nombrePropiedad}': " . $e->getMessage());
        } catch (\Exception $e) {
            //log_message('error', "Tor_Crud: Error al instanciar el controlador '{$controllerName}': " . $e->getMessage());
        }

        return null;
    }

    /**
     * Obtiene los datos para la vista de detalle de un registro
     * 
     * @param int $id ID del registro a mostrar
     * @return array Datos formateados para la vista
     */
    protected function getViewData($id)
    {
        // ===========================================
        // 1. Obtener el registro
        // ===========================================
        $record = $this->db->table($this->table)
                        ->where($this->primaryKey, $id)
                        ->get()
                        ->getRowArray();
        
        if (!$record) {
            throw new \Exception("Registro no encontrado (ID: {$id})");
        }
        
        // ===========================================
        // 2. Procesar el registro (igual que en getListData)
        // ===========================================
        $processedRecord = $record;
        
        foreach ($this->config['fields'] as $field => $attrs) {
            
            // Excluir campos que no deben mostrarse en vista detalle
            if (($attrs['hidden_in_ver'] ?? false)) {
                continue;
            }
            
            // Excluir tipos que no se muestran
            if (in_array($attrs['type'], ['password', 'hidden', 'virtual_display'])) {
                continue;
            }
            
            $valorOriginal = $record[$field] ?? '';
            
            switch ($attrs['type']) {
                
                case 'select':
                    // Relaciones 1 a N
                    if (!empty($attrs['relacion_tabla']) && !empty($record[$field])) {
                        $parser = $this->parsearFormatoRelacion($attrs['relacion_campo']);
                        
                        $select = $attrs['relacion_id'];
                        foreach ($parser['campos'] as $campo) {
                            $select .= ", {$campo}";
                        }
                        
                        $related = $this->db->table($attrs['relacion_tabla'])
                                        ->select($select)
                                        ->where($attrs['relacion_id'], $record[$field])
                                        ->get()
                                        ->getRowArray();
                        
                        if ($related) {
                            $texto = $attrs['relacion_campo'];
                            foreach ($parser['campos'] as $campo) {
                                $campo = trim($campo, '{}');
                                $buscarConLlaves = '{' . $campo . '}';
                                $reemplazar = $related[$campo] ?? '';
                                $texto = str_replace($buscarConLlaves, $reemplazar, $texto);
                                $texto = str_replace($campo, $reemplazar, $texto);
                            }
                            $processedRecord[$field . '_texto'] = $texto;
                        } else {
                            $processedRecord[$field . '_texto'] = $record[$field];
                        }
                    } else {
                        $processedRecord[$field . '_texto'] = $record[$field];
                    }
                    break;
                    
                case 'enum':
                    if (!empty($attrs['valores_posibles'])) {
                        $options = explode(',', $attrs['valores_posibles']);
                        $options = array_map('trim', $options);
                        $index = (int) $valorOriginal - 1;
                        $processedRecord[$field . '_texto'] = $options[$index] ?? $valorOriginal;
                        $processedRecord[$field . '_clase'] = ($valorOriginal == 1 || $valorOriginal === '1') ? 'success' : 'warning';
                    } else {
                        $processedRecord[$field . '_texto'] = $valorOriginal;
                    }
                    break;
                    
                case 'boolean':
                    $processedRecord[$field . '_texto'] = $valorOriginal ? 'Sí' : 'No';
                    $processedRecord[$field . '_clase'] = $valorOriginal ? 'success' : 'secondary';
                    break;
                    
                case 'file':
                    if (!empty($valorOriginal)) {
                        $processedRecord[$field . '_texto'] = basename($valorOriginal);
                        $processedRecord[$field . '_url'] = base_url($this->getFileUrl($field, $valorOriginal));
                        $processedRecord[$field . '_extension'] = strtolower(pathinfo($valorOriginal, PATHINFO_EXTENSION));
                    } else {
                        $processedRecord[$field . '_texto'] = '';
                    }
                    break;
                    
                case 'image':
                    if (!empty($valorOriginal)) {
                        $processedRecord[$field . '_url'] = base_url($this->getFileUrl($field, $valorOriginal));
                        $processedRecord[$field . '_texto'] = basename($valorOriginal);
                    } else {
                        $processedRecord[$field . '_texto'] = '';
                    }
                    break;
                    
                case 'wysiwyg':
                    $processedRecord[$field . '_texto'] = $valorOriginal; // No aplicar strip_tags
                    break;
                    
                case 'virtual_n_a_n':
                    if (!empty($attrs['tabla_intermedia']) && !empty($attrs['tabla_fuente'])) {
                        $parser = $this->parsearFormatoRelacion($attrs['formato_visualizacion']);
                        
                        $items = $this->db->table($attrs['tabla_intermedia'] . ' ti')
                                        ->join($attrs['tabla_fuente'] . ' tf', 
                                                'ti.' . $attrs['campo_externo_fk'] . ' = tf.' . $attrs['campo_id_fuente'])
                                        ->where('ti.' . $attrs['campo_local_fk'], $id)
                                        ->select('tf.*')
                                        ->get()
                                        ->getResultArray();
                        
                        $textos = [];
                        foreach ($items as $item) {
                            $texto = $attrs['formato_visualizacion'];
                            foreach ($parser['campos'] as $campo) {
                                $nombreCampo = trim($campo, '{}');
                                $valor = $item[$nombreCampo] ?? '';
                                $texto = str_replace(
                                    ['{' . $nombreCampo . '}', $nombreCampo],
                                    $valor,
                                    $texto
                                );
                            }
                            $textos[] = $texto;
                        }
                        $processedRecord[$field . '_tags'] = $textos;
                    }
                    break;
                    
                default:
                    // text, number, email, textarea, date, datetime
                    $processedRecord[$field . '_texto'] = $valorOriginal;
            }
        }
        
        // ===========================================
        // 3. Preparar datos para la vista
        // ===========================================
        $visibleFields = [];
        foreach ($this->config['fields'] as $field => $attrs) {
            // Excluir campos no visibles
            if (($attrs['hidden_in_ver'] ?? false)) {
                continue;
            }
            if (in_array($attrs['type'], ['password', 'hidden', 'virtual_display'])) {
                continue;
            }
            $visibleFields[$field] = $attrs;
        }
        
        // Ordenar por orden_visual
        uasort($visibleFields, function($a, $b) {
            return ($a['orden_visual'] ?? 0) <=> ($b['orden_visual'] ?? 0);
        });
        
        return [
            'entity' => $this->config['entity'],
            'fields' => $visibleFields,
            'record' => $processedRecord,
            'primaryKey' => $this->primaryKey,
            'baseUrl' => $this->getBaseUrl(),
            'id' => $id,
            'disableAdd' => $this->disableAdd,
            'disableEdit' => $this->disableEdit,
            'disableDelete' => $this->disableDelete,
            'disableView' => $this->disableView,
            'disableClone' => $this->disableClone,
            'disableExport' => $this->disableExport,
        ];
    }

    /**
     * Construye la URL completa para un archivo
     * 
     * @param string $field Nombre del campo
     * @param string $fileName Nombre del archivo guardado en BD
     * @return string Ruta completa para base_url()
     */
    private function getFileUrl($field, $fileName)
    {
        $attrs = $this->config['fields'][$field];
        
        $carpeta = $attrs['archivo_carpeta_destino'] ?? '';
        if (empty($carpeta)) {
            $carpeta = 'uploads';
            if (!empty($attrs['archivo_subcarpeta_por_entidad'])) {
                $carpeta .= '/' . $this->table;
            }
        }
        
        return $carpeta . '/' . $fileName;
    }

    /**
     * Escapa un campo para CSV (maneja comillas y saltos de línea)
     * 
     * @param mixed $field Valor del campo
     * @return string Valor escapado para CSV
     */
    private function escapeCsvField($field)
    {
        // Convertir a string y limpiar HTML
        $field = strip_tags((string)($field ?? ''));
        
        // Si contiene comillas, comas o saltos de línea, envolver en comillas dobles
        if (preg_match('/[,"\n\r]/', $field)) {
            $field = '"' . str_replace('"', '""', $field) . '"';
        }
        return $field;
    }


    /**
     * Exporta los datos a CSV
     * 
     * @param array $listData Datos ya procesados por getListData()
     * @return \CodeIgniter\HTTP\Response
     */
    public function exportCSV()
    {
        // ===========================================
        // 1. Capturar parámetros (filtros, orden, etc.)
        // ===========================================
        $this->capturarParametros();
        
        // ===========================================
        // 2. Guardar configuración de paginación original
        // ===========================================
        $originalPerPage = $this->perPage;
        
        // ===========================================
        // 3. Obtener TODOS los registros (sin paginación)
        // ===========================================
        $this->perPage = 0; // 0 = sin límite
        $listData = $this->getListData();
        $records = $listData['records'];
        $fields = $listData['fields'];
        
        // ===========================================
        // 4. Restaurar paginación original
        // ===========================================
        $this->perPage = $originalPerPage;
        
        // ===========================================
        // 5. Generar CSV
        // ===========================================
        $csvContent = '';
        
        // Cabeceras
        $headers = [];
        foreach ($fields as $field => $attrs) {
            $headers[] = $attrs['label'];
        }
        $csvContent .= implode(',', array_map([$this, 'escapeCsvField'], $headers)) . "\n";
        
        // Datos (usar los valores ya procesados por getListData)
        foreach ($records as $record) {
            $row = [];
            foreach (array_keys($fields) as $field) {
                $value = $record[$field . '_texto'] ?? $record[$field] ?? '';
                
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                
                $row[] = $value;
            }
            $csvContent .= implode(',', array_map([$this, 'escapeCsvField'], $row)) . "\n";
        }
        
        // ===========================================
        // 6. Forzar descarga
        // ===========================================
        $filename = $this->config['entity']['nombre_tabla'] . '_' . date('Y-m-d_His') . '.csv';
        
        $response = service('response');
        return $response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csvContent);
    }

    /**
     * Obtiene una instancia del controlador actual
     * 
     * @return object|null Instancia del controlador o null
     */
    private function getCurrentController()
    {
        $uri = service('uri');
        $segments = $uri->getSegments();
        
        if (empty($segments)) {
            return null;
        }
        
        $controllerSegment = $segments[0];
        $controllerName = 'App\\Controllers\\' . ucfirst($controllerSegment);
        
        if (!class_exists($controllerName)) {
            return null;
        }
        
        return new $controllerName();
    }


    /**
     * Registra un callback a ejecutar antes de insertar un nuevo registro
     * 
     * @param string $callback Nombre del método en el controlador
     * @return $this
     */
    public function beforeInsert($callback)
    {
        $this->beforeInsertCallback = $callback;
        return $this;
    }

    /**
     * Registra un callback a ejecutar después de insertar un nuevo registro
     * 
     * @param string $callback Nombre del método en el controlador
     * @return $this
     */
    public function afterInsert($callback)
    {
        $this->afterInsertCallback = $callback;
        return $this;
    }

    /**
     * Registra un callback a ejecutar antes de actualizar un registro
     * 
     * @param string $callback Nombre del método en el controlador
     * @return $this
     */
    public function beforeUpdate($callback)
    {
        $this->beforeUpdateCallback = $callback;
        return $this;
    }

    /**
     * Registra un callback a ejecutar después de actualizar un registro
     * 
     * @param string $callback Nombre del método en el controlador
     * @return $this
     */
    public function afterUpdate($callback)
    {
        $this->afterUpdateCallback = $callback;
        return $this;
    }

    /**
     * Registra un callback a ejecutar antes de eliminar un registro
     * 
     * @param string $callback Nombre del método en el controlador
     * @return $this
     */
    public function beforeDelete($callback)
    {
        $this->beforeDeleteCallback = $callback;
        return $this;
    }

    /**
     * Registra un callback a ejecutar después de eliminar un registro
     * 
     * @param string $callback Nombre del método en el controlador
     * @return $this
     */
    public function afterDelete($callback)
    {
        $this->afterDeleteCallback = $callback;
        return $this;
    }

    /**
     * Registra un callback a ejecutar antes de eliminar múltiples registros
     * 
     * @param string $callback Nombre del método en el controlador
     * @return $this
     */
    public function beforeBulkDelete($callback)
    {
        $this->beforeBulkDeleteCallback = $callback;
        return $this;
    }

    /**
     * Registra un callback a ejecutar después de eliminar múltiples registros
     * 
     * @param string $callback Nombre del método en el controlador
     * @return $this
     */
    public function afterBulkDelete($callback)
    {
        $this->afterBulkDeleteCallback = $callback;
        return $this;
    }

    /**
     * Registra un callback a ejecutar antes de subir un archivo
     * 
     * @param string $callback Nombre del método en el controlador
     * @return $this
     */
    public function beforeUpload($callback)
    {
        $this->beforeUploadCallback = $callback;
        return $this;
    }

    /**
     * Registra un callback a ejecutar después de subir un archivo
     * 
     * @param string $callback Nombre del método en el controlador
     * @return $this
     */
    public function afterUpload($callback)
    {
        $this->afterUploadCallback = $callback;
        return $this;
    }

    /**
     * Ejecuta un callback del controlador
     * 
     * @param string $callbackName Nombre del método callback
     * @param array ...$params Parámetros a pasar al método
     * @return mixed Resultado del callback o null si no existe
     */
    private function runCallback($callbackName, ...$params)
    {
        if (empty($callbackName)) {
            return null;
        }
        
        // Obtener instancia del controlador actual
        $controller = $this->getCurrentController();
        
        if (!$controller) {
            log_message('error', "Tor_Crud: No se pudo obtener el controlador para ejecutar callback '{$callbackName}'");
            return null;
        }
        
        if (!method_exists($controller, $callbackName)) {
            log_message('error', "Tor_Crud: El método '{$callbackName}' no existe en el controlador " . get_class($controller));
            return null;
        }
        
        try {
            return $controller->$callbackName(...$params);
        } catch (\Exception $e) {
            log_message('error', "Tor_Crud: Error ejecutando callback '{$callbackName}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Exporta los datos actuales a PDF usando DomPDF
     */
    public function exportPDF()
    {
        // Cargar DomPDF
       // require_once ROOTPATH . 'vendor/autoload.php'; // Ajusta la ruta según tu instalación
        
        // Obtener datos
        $this->capturarParametros();
        $originalPerPage = $this->perPage;
        $this->perPage = 0;
        $listData = $this->getListData();
        $this->perPage = $originalPerPage;
        
        // Generar HTML para el PDF
        $html = view('tor_crud/export_pdf', [
            'data' => $listData,
            'entity' => $this->config['entity'],
            'fecha' => date('d/m/Y H:i:s')
        ]);
        
        // Generar PDF
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false); // Seguridad
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        // Descargar
        $filename = $this->config['entity']['nombre_tabla'] . '_' . date('Y-m-d_His') . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    /**
     * Muestra una versión optimizada para impresión
     */
    public function printView()
    {
        // Obtener datos
        $this->capturarParametros();
        $originalPerPage = $this->perPage;
        $this->perPage = 0;
        $listData = $this->getListData();
        $this->perPage = $originalPerPage;
        
        // Mostrar vista de impresión
        return view('tor_crud/print', [
            'data' => $listData,
            'entity' => $this->config['entity'],
            'fecha' => date('d/m/Y H:i:s')
        ]);
    }

    /**
     * Procesa una edición en línea
     * 
     * @param int $id ID del registro a actualizar
     * @return \CodeIgniter\HTTP\Response (JSON)
     */
/**
 * Procesa una edición en línea
 * 
 * @param int $id ID del registro a actualizar
 * @return \CodeIgniter\HTTP\Response (JSON)
 */
public function processInlineEdit($id)
{
    // Asegurar que la respuesta sea JSON
    $this->response->setHeader('Content-Type', 'application/json');
    
    try {
        // ===========================================
        // 1. Verificar que la edición en línea no esté deshabilitada
        // ===========================================
        if ($this->disableInline) {
            return $this->jsonResponse(false, 'Edición en línea deshabilitada');
        }
        
        // ===========================================
        // 2. Obtener datos del POST
        // ===========================================
        $json = $this->request->getJSON(true);
        $field = $json['field'] ?? null;
        $value = $json['value'] ?? null;
        
        if (!$field) {
            return $this->jsonResponse(false, 'Campo no especificado');
        }
        
        // ===========================================
        // 3. Verificar que el campo existe en la configuración
        // ===========================================
        $fieldConfig = $this->config['fields'][$field] ?? null;
        if (!$fieldConfig) {
            return $this->jsonResponse(false, 'Campo no válido');
        }
        
        // ===========================================
        // 4. Verificar que el campo sea editable en línea
        // ===========================================
        $esEditable = $this->isInlineEditable($field, $fieldConfig);
        if (!$esEditable) {
            return $this->jsonResponse(false, 'Este campo no es editable en línea');
        }
        
        // ===========================================
        // 5. Obtener valor anterior (para auditoría)
        // ===========================================
        $valorAnterior = $this->db->table($this->table)
                                  ->select($field)
                                  ->where($this->primaryKey, $id)
                                  ->get()
                                  ->getRowArray()[$field] ?? null;
        
        // ===========================================
        // 6. Validar el valor (usando reglas de validación)
        // ===========================================
        $validation = \Config\Services::validation();
        $rules = [];
        if (!empty($fieldConfig['validation_rules'])) {
            $rules[$field] = $fieldConfig['validation_rules'];
        }


        
        if (!empty($rules) && !$validation->setRules($rules)->run([$field => $value])) {
            $errors = $validation->getErrors();
            return $this->jsonResponse(false, reset($errors)); // Primer error
        }
        
        // ===========================================
        // 7. Procesar según el tipo de campo
        // ===========================================
        $valorGuardar = $value;
        
        // Para selects, enums, etc., asegurar que el valor es válido
        if ($fieldConfig['type'] === 'select' || $fieldConfig['type'] === 'enum') {
            // Obtener opciones válidas
            $opcionesValidas = $this->getInlineOptions($field, $fieldConfig);
            $valoresValidos = array_column($opcionesValidas, 'value');
            
            if (!in_array($value, $valoresValidos)) {
                return $this->jsonResponse(false, 'Valor no válido para este campo');
            }
        }
        
        // ===========================================
        // 8. Ejecutar callback beforeUpdate si existe
        // ===========================================
        if ($this->beforeUpdateCallback) {
            $data = [$field => $valorGuardar];
            $result = $this->runCallback($this->beforeUpdateCallback, $id, $data);
            if ($result === false) {
                return $this->jsonResponse(false, 'Actualización cancelada por callback');
            }
            if (is_array($result) && isset($result[$field])) {
                $valorGuardar = $result[$field];
            }
        }
        
        // ===========================================
        // 9. Actualizar en BD
        // ===========================================
        $db = \Config\Database::connect();
        $db->table($this->table)
           ->where($this->primaryKey, $id)
           ->update([$field => $valorGuardar]);
        
        if ($db->error()['code'] != 0) {
            return $this->jsonResponse(false, 'Error al actualizar en BD');
        }
        
        // ===========================================
        // 10. AUDITORÍA
        // ===========================================
        $datosAnteriores = [$field => $valorAnterior];
        $datosNuevos = [$field => $valorGuardar];
        
        $this->auditoriaService->update(
            $this->table,
            $id,
            $datosAnteriores,
            $datosNuevos
        );
        
        // ===========================================
        // 11. Obtener el valor formateado para mostrar
        // ===========================================
        $valorDisplay = $this->formatInlineValue($field, $fieldConfig, $valorGuardar, $id);
        
        // ===========================================
        // 12. Ejecutar callback afterUpdate si existe
        // ===========================================
        if ($this->afterUpdateCallback) {
            $this->runCallback($this->afterUpdateCallback, $id, [$field => $valorGuardar]);
        }
        
        // ===========================================
        // 13. Respuesta exitosa
        // ===========================================
        return $this->jsonResponse(true, 'OK', [
            'new_value' => $valorGuardar,
            'new_value_display' => $valorDisplay['text'],
            'new_value_class' => $valorDisplay['class'] ?? null
        ]);
        
    } catch (\Exception $e) {
        log_message('error', 'Error en processInlineEdit: ' . $e->getMessage());
        return $this->jsonResponse(false, 'Error interno: ' . $e->getMessage());
    }
}


    /**
     * Verifica si un campo es editable en línea
     */
    private function isInlineEditable($field, $fieldConfig)
    {
        // No editable si está deshabilitado globalmente
        if ($this->disableInline) return false;
        
        // No editable si está oculto en formulario
        if ($fieldConfig['hidden_in_form'] ?? false) return false;
        
        // Tipos no soportados
        if (in_array($fieldConfig['type'], [
            'virtual_n_a_n', 'virtual_display', 'file', 'image', 'password', 'hidden'
        ])) return false;
        
        // No es la clave primaria
        if ($field === $this->primaryKey) return false;
        
        // La entidad debe permitir edición
        if (!($this->config['entity']['permite_editar'] ?? true)) return false;
        
        return true;
    }


    /**
     * Formatea un valor para mostrarlo después de edición en línea
     * 
     * @return array Con 'text' y 'class' para campos especiales
     */
    private function formatInlineValue($field, $fieldConfig, $value, $id)
    {
        $result = [
            'text' => $value,
            'class' => null
        ];
        
        switch ($fieldConfig['type']) {
            case 'select':
                $opciones = $this->getInlineOptions($field, $fieldConfig);
                foreach ($opciones as $opt) {
                    if ($opt['value'] == $value) {
                        $result['text'] = $opt['text'];
                        break;
                    }
                }
                break;
                
            case 'enum':
                $opciones = $this->getInlineOptions($field, $fieldConfig);
                foreach ($opciones as $opt) {
                    if ($opt['value'] == $value) {
                        $result['text'] = $opt['text'];
                        break;
                    }
                }
                // Determinar clase del badge (asumiendo que 1 es éxito)
                $result['class'] = ($value == 1 || $value === '1') ? 'success' : 'warning';
                break;
                
            case 'boolean':
                $result['text'] = $value ? 'Sí' : 'No';
                $result['class'] = $value ? 'success' : 'secondary';
                break;
                
            default:
                $result['text'] = $value;
        }
        
        return $result;
    }

    /**
     * Respuesta JSON unificada
     */
    private function jsonResponse($success, $message = '', $data = [])
    {
        return $this->response
            ->setStatusCode($success ? 200 : 400)
            ->setJSON(array_merge([
                'success' => $success,
                'message' => $message
            ], $data));
    }


    /**
     * Prepara la configuración de campos necesaria para edición en línea
     * Se envía una sola vez al inicio
     */
    protected function getFieldConfigForInline()
    {
        $config = [];
        
        foreach ($this->config['fields'] as $field => $attrs) {
            // Solo para campos que pueden ser editados en línea
            if ($this->isInlineEditable($field, $attrs)) {
                $config[$field] = [
                    'type' => $attrs['type'],
                    'options' => $this->getInlineOptions($field, $attrs)
                ];
            }
        }
        
        return $config;
    }

    /**
     * Obtiene las opciones para un campo (selects, enums)
     */
    protected function getInlineOptions($field, $attrs)
    {
        $cache = service('cache');
        $cacheKey = "crud_options_{$this->table}_{$field}";
        
        $cached = $cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $options = [];
        
        if ($attrs['type'] === 'select' && !empty($attrs['relacion_tabla'])) {
            // Relación 1 a N
            $parser = $this->parsearFormatoRelacion($attrs['relacion_campo']);
            
            $select = "{$attrs['relacion_id']} as value";
            foreach ($parser['campos'] as $campo) {
                $select .= ", {$campo}";
            }
            
            $rows = $this->db->table($attrs['relacion_tabla'])
                            ->select($select)
                            ->orderBy($parser['campos'][0] ?? $attrs['relacion_id'], 'ASC')
                            ->get()
                            ->getResultArray();
            
            foreach ($rows as $row) {
                $texto = $attrs['relacion_campo'];
                foreach ($parser['campos'] as $campo) {
                    $campo = trim($campo, '{}');
                    $texto = str_replace(
                        ['{' . $campo . '}', $campo],
                        $row[$campo] ?? '',
                        $texto
                    );
                }
                $options[] = [
                    'value' => $row['value'],
                    'text' => $texto
                ];
            }
        }
        
        if ($attrs['type'] === 'enum') {
            $opciones = explode(',', $attrs['valores_posibles'] ?? '');
            foreach ($opciones as $index => $texto) {
                $options[] = [
                    'value' => $index + 1,
                    'text' => trim($texto)
                ];
            }
        }
        
        if ($attrs['type'] === 'boolean') {
            $options = [
                ['value' => '1', 'text' => 'Sí'],
                ['value' => '0', 'text' => 'No']
            ];
        }
        
        $cache->save($cacheKey, $options, 3600);
        return $options;
    }
}