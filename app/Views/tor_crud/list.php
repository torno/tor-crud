<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $baseUrl = preg_replace('/\/tc\w+(\/\d+)?$/', '', current_url());
    $baseUrl = preg_replace('/\/index$/', '', $baseUrl);
?>
<!-- Configuración para edición en línea -->
<script>
window.inlineConfig = <?= json_encode($data['fieldConfig']) ?>;
</script>
<div class="container-fluid py-4">
    <!-- Título y acciones globales -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0">
            <i class="bi bi-table me-2"></i>
            <?= esc($data['entity']['titulo']) ?>
        </h2>
        <div>
            <?php if (!$data['disableSearch']): ?>
                <button class="btn btn-outline-secondary" title="Buscar" 
                        data-bs-toggle="collapse" data-bs-target="#busquedaCollapse">
                    <i class="bi bi-search"></i>
                </button>
            <?php endif; ?>
            <?php if (!$data['disableExport']): ?>
                <button class="btn btn-outline-secondary" title="Exportar Excel" 
                        onclick="window.location.href='<?= $baseUrl ?>/tcexportcsv'">
                    <i class="bi bi-file-earmark-excel"></i>
                </button>
                <button class="btn btn-outline-secondary" title="Exportar PDF" 
                        onclick="window.location.href='<?= $baseUrl ?>/tcexportpdf'">
                    <i class="bi bi-file-earmark-pdf"></i>
                </button>
                <button class="btn btn-outline-secondary" title="Imprimir" 
                        onclick="window.location.href='<?= $baseUrl ?>/tcprint'" target="_blank">
                    <i class="bi bi-printer"></i>
                </button>
            <?php endif; ?>
            <?php foreach ($data['globalActions'] as $action): ?>
                <a href="<?= $action['url'] ?>" 
                class="btn btn-outline-secondary" 
                <?= !empty($action['tooltip']) ? 'title="' . $action['tooltip'] . '"' : '' ?>
                <?= !empty($action['target']) ? 'target="' . $action['target'] . '"' : '' ?>>
                    <i class="bi <?= $action['icono'] ?>"></i>
                    <?= $action['nombre'] ?>
                </a>
            <?php endforeach; ?>
            
            <?php if (!$data['disableAdd'] && ($data['entity']['permite_crear'] ?? true)): ?>
                <a href="<?= $baseUrl ?>/tcadd" class="btn btn-primary ms-2">
                    <i class="bi bi-plus-circle"></i> Nuevo
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$data['disableSearch']): ?>
        <!-- Búsqueda colapsable -->
        <div class="collapse <?= !empty($data['search']) ? 'show' : '' ?>" id="busquedaCollapse">
            <div class="card card-body">
                <form method="get" action="<?= current_url() ?>" class="d-flex">
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control" name="search" 
                            value="<?= esc($data['search'] ?? '') ?>"
                            placeholder="Buscar en todos los campos...">
                        <button type="submit" class="btn btn-primary">
                            Buscar
                        </button>
                        
                        <?php if (!empty($data['search']) || !empty(array_filter($data['filters']))): ?>
                            <a href="<?= current_url() ?>?reset_filters=1" class="btn btn-outline-secondary">
                                <i class="bi bi-eraser"></i> Limpiar todo
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabla -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <!-- Fila de títulos con ordenamiento -->
                        <tr>
                            <?php 
                            $currentOrderBy = $data['orderBy'][0] ?? '';
                            $currentOrderDir = $data['orderBy'][1] ?? '';
                            ?>
                            <?php foreach ($data['fields'] as $field => $attrs): ?>
                                <th style="min-width: 150px;" 
                                    <?= in_array($attrs['type'], ['precio', 'fecha']) ? 'class="d-none d-md-table-cell"' : '' ?>>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span><?= esc($attrs['label']) ?></span>
                                        
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php
                                            $ascActive = ($currentOrderBy == $field && $currentOrderDir == 'ASC') ? 'btn-primary' : 'btn-outline-secondary';
                                            $descActive = ($currentOrderBy == $field && $currentOrderDir == 'DESC') ? 'btn-primary' : 'btn-outline-secondary';
                                            
                                            // URL para ascendente
                                            $paramsAsc = $_GET;
                                            $paramsAsc['orderBy'] = $field;
                                            $paramsAsc['orderDir'] = 'ASC';
                                            $urlAsc = current_url() . '?' . http_build_query($paramsAsc);
                                            
                                            // URL para descendente
                                            $paramsDesc = $_GET;
                                            $paramsDesc['orderBy'] = $field;
                                            $paramsDesc['orderDir'] = 'DESC';
                                            $urlDesc = current_url() . '?' . http_build_query($paramsDesc);
                                            ?>
                                            
                                            <a href="<?= $urlAsc ?>" class="btn btn-sm <?= $ascActive ?>" title="Orden ascendente">
                                                <i class="bi bi-sort-down-alt"></i>
                                            </a>
                                            <a href="<?= $urlDesc ?>" class="btn btn-sm <?= $descActive ?>" title="Orden descendente">
                                                <i class="bi bi-sort-down"></i>
                                            </a>
                                        </div>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                            <th style="min-width: 180px;" class="text-end">Acciones</th>
                        </tr>
                        
                        <!-- Fila de filtros con formulario único -->
                        <tr>
                            <form method="get" action="<?= current_url() ?>" id="filters-form">
                                <?php foreach ($data['fields'] as $field => $attrs): ?>
                                    <td>
                                    <?php if (!$data['disableSearch']): ?>
                                        <div class="mt-2 position-relative">
                                            <input type="text" class="form-control form-control-sm pe-5" 
                                                name="filter_<?= $field ?>" 
                                                value="<?= esc($data['filters'][$field] ?? '') ?>"
                                                placeholder="Filtrar..."
                                                data-filter-timer>
                                            
                                            <?php if (isset($data['filters'][$field]) && $data['filters'][$field] !== ''): ?>
                                                <?php 
                                                    // Preparar parámetros para el enlace de limpiar filtro
                                                    $params = $_GET;
                                                    unset($params['filter_' . $field]);
                                                    // Eliminar valores que sean arrays (como filtros múltiples)
                                                    foreach ($params as $key => $value) {
                                                        if (is_array($value)) {
                                                            unset($params[$key]);
                                                        }
                                                    }
                                                    $queryString = http_build_query($params);
                                                ?>
                                                    <a href="<?= current_url() ?>?clear_filter=<?= $field ?>" 
                                                    class="position-absolute end-0 top-0 translate-middle-y me-1 text-decoration-none"
                                                    style="margin-top: 0.8rem; color: #dc3545;"
                                                    title="Limpiar filtro">
                                                        <i class="bi bi-x-circle-fill"></i>
                                                    </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>    
                                    </td>
                                <?php endforeach; ?>
                            </form>
                            <?php if (!$data['disableDelete']): ?>
                                <td class="text-end">
                                    <button class="btn btn-outline-danger btn-sm" id="btnBorrarMultiple" style="display: none;">
                                        <i class="bi bi-trash"></i> Borrar
                                    </button>
                                </td>
                            <?php else: ?>
                                <td class="text-end"></td>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data['records'])): ?>
                            <tr>
                                <td colspan="<?= count($data['fields']) + 1 ?>" class="text-center py-4">
                                    No hay registros para mostrar
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data['records'] as $record): ?>
                                <tr>
                                    <?php foreach ($data['fields'] as $field => $attrs): 
                                        $valor = $record[$field . '_texto'] ?? $record[$field] ?? '';
                                        $extraClass = in_array($attrs['type'], ['precio', 'fecha']) ? 'd-none d-md-table-cell' : '';
                                        // ===========================================
                                        // DETERMINAR SI LA CELDA ES EDITABLE EN LÍNEA
                                        // ===========================================
                                        $editable = false;
                                        $editableAttrs = '';
                                        
                                        if (
                                            !$data['disableInline'] &&                                   // No deshabilitado globalmente
                                            !($attrs['hidden_in_form'] ?? false) &&                      // No oculto en formulario
                                            !in_array($attrs['type'], [                                  // Tipos soportados
                                                'virtual_n_a_n', 'virtual_display', 'file', 'image', 
                                                'password', 'hidden', 'wysiwyg'
                                            ]) &&
                                            ($data['entity']['permite_editar'] ?? true) &&               // Permiso de edición
                                            $field !== $data['primaryKey']                               // No es clave primaria
                                        ) {
                                            $editable = true;
                                            
                                            // Construir atributos data-*
                                            $editableAttrs = sprintf(
                                                ' data-field="%s" data-type="%s" data-id="%s" data-value="%s"',
                                                $field,
                                                $attrs['type'],
                                                $record[$data['primaryKey']],
                                                htmlspecialchars($record[$field] ?? '', ENT_QUOTES)
                                            );
                                            
                                            // Para selects y enums, añadir opciones
                                            if (in_array($attrs['type'], ['select', 'enum'])) {
                                                $options = $data['selectOptions'][$field] ?? [];
                                                $editableAttrs .= ' data-options=\'' . json_encode($options) . '\'';
                                            }
                                        }
                                    ?>
                                        <td class="<?= $extraClass ?> <?= $editable ? 'editable' : '' ?>" <?= $editableAttrs ?>>
                                            <?php if ($attrs['type'] === 'image' && !empty($record[$field])): 
                                                $carpeta = $attrs['archivo_carpeta_destino'];
                                                if (empty($carpeta)) {
                                                    $carpeta = 'uploads';
                                                    if (!empty($attrs['archivo_subcarpeta_por_entidad'])) {
                                                        $carpeta .= '/' . $data['entity']['nombre_tabla'];
                                                    }
                                                }
                                                $rutaImagen = $carpeta . '/' . $record[$field];
                                                $miniatura = mostrar_imagen($carpeta, $record[$field], 100, 100);
                                            ?>
                                            <img src="<?= $miniatura ?>" alt="Imagen" style="max-height: 50px; max-width: 50px; cursor: pointer;" class="img-thumbnail"
                                            onclick="mostrarModalImagen('<?= base_url($carpeta . '/' . $record[$field]) ?>')">   

                                            <?php elseif ($attrs['type'] === 'file' && !empty($record[$field])): 
                                                $carpeta = $attrs['archivo_carpeta_destino'];
                                                if (empty($carpeta)) {
                                                    $carpeta = 'uploads';
                                                    if (!empty($attrs['archivo_subcarpeta_por_entidad'])) {
                                                        $carpeta .= '/' . $data['entity']['nombre_tabla'];
                                                    }
                                                }
                                                $rutaArchivo = $carpeta . '/' . $record[$field];
                                            ?>
                                                <a href="<?= base_url($rutaArchivo) ?>" target="_blank">
                                                    <i class="bi bi-file-earmark"></i> <?= basename($record[$field]) ?>
                                                </a>
                                                
                                            <?php elseif (in_array($attrs['type'], ['enum', 'boolean']) && isset($record[$field . '_clase'])): ?>
                                                <span class="badge bg-<?= $record[$field . '_clase'] ?>">
                                                    <?= esc($record[$field . '_texto'] ?? $valor) ?>
                                                </span>
                                                
                                            <?php elseif ($attrs['type'] === 'virtual_n_a_n'): ?>
                                                <span title="<?= esc($valor) ?>">
                                                    <?= strlen($valor) > 30 ? substr($valor, 0, 30) . '...' : $valor ?>
                                                </span>
                                            <?php elseif ($attrs['type'] === 'virtual_display'): ?>
                                                <?= $record[$field . '_texto'] ?? '' ?>  
                                            <?php else: ?>
                                                <?php 
                                                // Truncar texto largo
                                                $display = esc($valor);
                                                if (strlen($display) > 50) {
                                                    $display = substr($display, 0, 50) . '...';
                                                }
                                                echo $display;
                                                ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    
                                    <td class="text-end">
                                        <?php if (!$data['disableDelete']): ?>
                                            <input type="checkbox" class="form-check-input me-2 check-item"
                                               value="<?= $record[$data['primaryKey']] ?>">
                                        <?php endif; ?>       
                                        <?php if (!$data['disableEdit'] && ($data['entity']['permite_editar'] ?? true)): ?>
                                            <a href="<?= $baseUrl ?>/tcedit/<?= $record[$data['primaryKey']] ?>" 
                                            class="btn btn-sm btn-outline-warning me-1" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <?php if (!$data['disableClone'] ): ?>
                                                <li>
                                                    <a class="dropdown-item" href="<?= $baseUrl ?>/tcclone/<?= $record[$data['primaryKey']] ?>">
                                                        <i class="bi bi-clipboard"></i> Duplicar
                                                    </a>
                                                </li>
                                                <?php endif; ?>
                                                <?php if (!$data['disableView']): ?>
                                                <li>
                                                    <a class="dropdown-item" href="<?= $baseUrl ?>/tcview/<?= $record[$data['primaryKey']] ?>">
                                                        <i class="bi bi-eye"></i> Ver
                                                    </a>
                                                </li>
                                                <?php endif; ?>
                                                <?php if (!$data['disableDelete'] && ($data['entity']['permite_eliminar'] ?? true)): ?>
                                                <li>
                                                    <a class="dropdown-item text-danger delete-single" 
                                                    href="<?= $baseUrl ?>/tcdelete/<?= $record[$data['primaryKey']] ?>">
                                                        <i class="bi bi-trash me-2"></i> Eliminar
                                                    </a>
                                                </li>
                                                <?php endif; ?>
                                                <?php if ( !empty ($data['rowActions']) ): ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <?php  foreach ($data['rowActions'] as $action): ?>
                                                        <li>
                                                            <?php if (!empty($action['url'])): 
                                                                // Reemplazar placeholders {campo}
                                                                $url = $action['url'];
                                                                foreach ($record as $campo => $valor) {
                                                                    if (!is_array($valor)) {
                                                                        $url = str_replace('{' . $campo . '}', urlencode($valor), $url);
                                                                    }
                                                                }
                                                            ?>
                                                                <a class="dropdown-item" href="<?= $url ?>" 
                                                                <?= !empty($action['target']) ? 'target="' . $action['target'] . '"' : '' ?>
                                                                <?= !empty($action['tooltip']) ? 'title="' . $action['tooltip'] . '"' : '' ?>>
                                                                    <i class="bi <?= $action['icono'] ?> me-2"></i>
                                                                    <?= $action['nombre'] ?>
                                                                </a>
                                                            <?php else: ?>
                                                                <a class="dropdown-item" href="javascript:void(0)" 
                                                                onclick="<?= str_replace('{id}', $record[$data['primaryKey']], $action['js']) ?>"
                                                                <?= !empty($action['tooltip']) ? 'title="' . $action['tooltip'] . '"' : '' ?>>
                                                                    <i class="bi <?= $action['icono'] ?> me-2"></i>
                                                                    <?= $action['nombre'] ?>
                                                                </a>
                                                            <?php endif; ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- PIE DE PÁGINA - PAGINACIÓN MEJORADA -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3">
        
        <!-- Mostrando X-Y de Z registros -->
        <div class="d-flex align-items-center mb-2 mb-md-0">
            <span class="text-muted">
                Mostrando <strong>
                    <?= (($data['page'] - 1) * $data['perPage']) + 1 ?>-
                    <?= min($data['page'] * $data['perPage'], $data['total']) ?>
                </strong> de <strong><?= $data['total'] ?></strong> registros
            </span>
        </div>
        
        <!-- Paginación con selector de página -->
        <nav aria-label="Paginación" class="d-flex align-items-center">
            
            <!-- Anterior -->
            <ul class="pagination mb-0 me-2">
                <li class="page-item <?= $data['page'] <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= current_url() ?>?page=<?= $data['page'] - 1 ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
            </ul>
            
            <!-- Números de página (solo un rango alrededor de la actual) -->
            <ul class="pagination mb-0">
                <?php for ($i = 1; $i <= $data['totalPages']; $i++): ?>
                    <?php if ($i >= $data['page'] - 2 && $i <= $data['page'] + 2): ?>
                        <li class="page-item <?= $i == $data['page'] ? 'active' : '' ?>">
                            <a class="page-link" href="<?= current_url() ?>?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endif; ?>
                <?php endfor; ?>
            </ul>
            
            <!-- Siguiente -->
            <ul class="pagination mb-0 ms-2">
                <li class="page-item <?= $data['page'] >= $data['totalPages'] ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= current_url() ?>?page=<?= $data['page'] + 1 ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul>

            <!-- Selector de página (para saltos grandes) -->
            <?php if ($data['totalPages'] > 5): ?>
                <div class="mx-2">
                    <select class="form-select form-select-sm" 
                            style="width: auto; display: inline-block;"
                            onchange="window.location.href = '<?= current_url() ?>?page=' + this.value">
                        <?php for ($i = 1; $i <= $data['totalPages']; $i++): ?>
                            <option value="<?= $i ?>" <?= $i == $data['page'] ? 'selected' : '' ?>>
                                Pág. <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <!-- Selector de registros por página (existente) -->
            <select class="form-select form-select-sm ms-3" style="width: auto;" onchange="window.location.href=this.value">
                <option value="<?= current_url() ?>?perPage=10" <?= $data['perPage'] == 10 ? 'selected' : '' ?>>10</option>
                <option value="<?= current_url() ?>?perPage=25" <?= $data['perPage'] == 25 ? 'selected' : '' ?>>25</option>
                <option value="<?= current_url() ?>?perPage=50" <?= $data['perPage'] == 50 ? 'selected' : '' ?>>50</option>
                <option value="<?= current_url() ?>?perPage=100" <?= $data['perPage'] == 100 ? 'selected' : '' ?>>100</option>
            </select>
        </nav>
    </div>
    
    <!-- Botón flotante para móvil -->
    <div class="d-md-none position-fixed top-0 end-0 m-3" style="z-index: 1050;">
        <button class="btn btn-outline-secondary btn-sm rounded-circle p-2" 
                onclick="mostrarTodasColumnas()"
                title="Mostrar todas las columnas">
            <i class="bi bi-arrows-fullscreen"></i>
        </button>
    </div>
</div>

<!-- Modal de confirmación para eliminación -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="deleteMessage">¿Está seguro de eliminar el/los registro(s) seleccionado(s)?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Eliminar</button>
            </div>
        </div>
    </div>
</div>


<?= $this->endSection() ?>