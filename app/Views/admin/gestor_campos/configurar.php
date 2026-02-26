<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
// DEPURACIÓN: Ver los campos procesados
//log_message('debug', '=== CAMPOS PROCESADOS PARA VISTA ===');
//foreach ($fields as $field) {
 //   $props = get_object_vars($field);
 //   log_message('debug', "Campo {$field->name}: " . json_encode($props));
//}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Configurar: <code><?= esc($tablaNombre) ?></code></h2>
    <a href="<?= base_url('admin/gestor-campos') ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<?= view('App\Views\admin\gestor_campos\_mensajes') ?>

<form method="post" action="<?= base_url('admin/gestor-campos/guardar') ?>" id="formConfiguracion">
    <?= csrf_field() ?>
    <input type="hidden" name="nombre_tabla" value="<?= esc($tablaNombre) ?>">
    <input type="hidden" name="entidad_id" value="<?= esc($entity['id'] ?? '') ?>">

    <!-- SECCIÓN 1: DATOS GENERALES DE LA ENTIDAD -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Datos generales</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Título amigable</label>
                    <input type="text" class="form-control" name="titulo" 
                           value="<?= old('titulo', $entity['titulo'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Filtro global (campo)</label>
                    <input type="text" class="form-control" name="filtro_global" 
                           value="<?= old('filtro_global', $entity['filtro_global'] ?? '') ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea class="form-control" name="descripcion" rows="2"><?= old('descripcion', $entity['descripcion'] ?? '') ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <label class="form-check-label">
                        <input type="checkbox" name="permite_busqueda" value="1" 
                               <?= old('permite_busqueda', $entity['permite_busqueda'] ?? false) ? 'checked' : '' ?>>
                        Permite búsqueda
                    </label>
                </div>
                <div class="col-md-3">
                    <label class="form-check-label">
                        <input type="checkbox" name="eliminacion_logica" value="1" 
                               <?= old('eliminacion_logica', $entity['eliminacion_logica'] ?? false) ? 'checked' : '' ?>>
                        Eliminación lógica
                    </label>
                </div>
                <div class="col-md-3">
                    <label class="form-check-label">
                        <input type="checkbox" name="permite_crear" value="1" 
                               <?= old('permite_crear', $entity['permite_crear'] ?? true) ? 'checked' : '' ?>>
                        Permitir crear
                    </label>
                </div>
                <div class="col-md-3">
                    <label class="form-check-label">
                        <input type="checkbox" name="permite_editar" value="1" 
                               <?= old('permite_editar', $entity['permite_editar'] ?? true) ? 'checked' : '' ?>>
                        Permitir editar
                    </label>
                </div>
                <div class="col-md-3 mt-2">
                    <label class="form-check-label">
                        <input type="checkbox" name="permite_eliminar" value="1" 
                               <?= old('permite_eliminar', $entity['permite_eliminar'] ?? true) ? 'checked' : '' ?>>
                        Permitir eliminar
                    </label>
                </div>
                <div class="col-md-3 mt-2">
                    <label class="form-check-label">
                        <input type="checkbox" name="usa_paginacion" value="1" 
                               <?= old('usa_paginacion', $entity['usa_paginacion'] ?? true) ? 'checked' : '' ?>>
                        Usar paginación
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 2: CAMPOS FÍSICOS DE LA TABLA -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Campos de la tabla</h5>
        </div>
        <div class="card-body">
            <?php 
            $camposMostrados = 0;
            foreach ($fields as $field): 
                // Excluir solo el campo 'id' exacto
                $esId = strtolower($field->name) === 'id';
                
                if ($esId) {
                    continue; // No se muestra como configurable
                }
                
                $camposMostrados++;
                $config = $camposConfigurados[$field->name] ?? [];
            ?>
                <div class="card mb-3 border">
                    <div class="card-header bg-light py-2">
                        <strong><?= esc($field->name) ?></strong> 
                        <small class="text-muted">(<?= esc($field->type) ?>)</small>
                    </div>
                    <div class="card-body">
                        <!-- Fila 1: Etiqueta, Tipo, Orden, Reglas -->
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label class="form-label">Etiqueta a mostrar</label>
                                <input type="text" class="form-control" 
                                       name="campos[<?= $field->name ?>][etiqueta]" 
                                       value="<?= old("campos.{$field->name}.etiqueta", $config['etiqueta_mostrar'] ?? ucfirst(str_replace('_', ' ', $field->name))) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipo de control</label>
                                <select class="form-select campo-tipo" 
                                        name="campos[<?= $field->name ?>][tipo]"
                                        data-campo="<?= $field->name ?>">
                                    <option value="text" <?= old("campos.{$field->name}.tipo", $config['tipo_control'] ?? '') === 'text' ? 'selected' : '' ?>>Texto</option>
                                    <option value="number" <?= old("campos.{$field->name}.tipo", $config['tipo_control'] ?? '') === 'number' ? 'selected' : '' ?>>Número</option>
                                    <option value="email" <?= old("campos.{$field->name}.tipo", $config['tipo_control'] ?? '') === 'email' ? 'selected' : '' ?>>Email</option>
                                    <option value="password" <?= old("campos.{$field->name}.tipo", $config['tipo_control'] ?? '') === 'password' ? 'selected' : '' ?>>Password</option>
                                    <option value="textarea" <?= old("campos.{$field->name}.tipo", $config['tipo_control'] ?? '') === 'textarea' ? 'selected' : '' ?>>Textarea</option>
                                    <option value="select" <?= old("campos.{$field->name}.tipo", $config['tipo_control'] ?? '') === 'select' ? 'selected' : '' ?>>Select (1 a N)</option>
                                    <option value="enum" <?= old("campos.{$field->name}.tipo", $config['tipo_control'] ?? '') === 'enum' ? 'selected' : '' ?>>Enum</option>
                                    <option value="boolean" <?= old("campos.{$field->name}.tipo", $config['tipo_control'] ?? '') === 'boolean' ? 'selected' : '' ?>>Boolean (Sí/No)</option>
                                    <option value="date" <?= old("campos.{$field->name}.tipo", $config['tipo_control'] ?? '') === 'date' ? 'selected' : '' ?>>Fecha</option>
                                    <option value="datetime" <?= old("campos.{$field->name}.tipo", $config['tipo_control'] ?? '') === 'datetime' ? 'selected' : '' ?>>Fecha y hora</option>
                                    <option value="hidden" <?= old("campos.{$field->name}.tipo", $config['tipo_control'] ?? '') === 'hidden' ? 'selected' : '' ?>>Oculto</option>
                                    <option value="file" <?= old("campos.{$field->name}.tipo", $config['tipo_control'] ?? '') === 'file' ? 'selected' : '' ?>>Archivo</option>
                                    <option value="image" <?= old("campos.{$field->name}.tipo", $config['tipo_control'] ?? '') === 'image' ? 'selected' : '' ?>>Imagen</option>
                                    <option value="wysiwyg" <?= old("campos.{$field->name}.tipo", $config['tipo_control'] ?? '') === 'wysiwyg' ? 'selected' : '' ?>>Editor WYSIWYG</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Orden visual</label>
                                <input type="number" class="form-control" 
                                       name="campos[<?= $field->name ?>][orden]" 
                                       value="<?= old("campos.{$field->name}.orden", $field->orden_sugerido) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Reglas</label>
                                <input type="text" class="form-control" 
                                       name="campos[<?= $field->name ?>][reglas_input]" 
                                       value="<?= old("campos.{$field->name}.reglas_input", $config['reglas_guardadas'] ?? '') ?>"
                                       placeholder="Ej: required|min_length[3]|max_length[255]">
                            </div>
                        </div>
                        
                        <!-- Fila 2: Checkboxes -->
                        <div class="row mb-2">
                            <div class="col-md-2">
                                <label class="form-check-label">
                                    <input type="checkbox" name="campos[<?= $field->name ?>][oculto_lista]" value="1"
                                           <?= old("campos.{$field->name}.oculto_lista", $config['oculto_en_lista'] ?? false) ? 'checked' : '' ?>>
                                    Ocultar en lista
                                </label>
                            </div>
                            <div class="col-md-2">
                                <label class="form-check-label">
                                    <input type="checkbox" name="campos[<?= $field->name ?>][oculto_form]" value="1"
                                           <?= old("campos.{$field->name}.oculto_form", $config['oculto_en_form'] ?? false) ? 'checked' : '' ?>>
                                    Ocultar en formulario
                                </label>
                            </div>
                            <div class="col-md-2">
                                <label class="form-check-label">
                                    <input type="checkbox" name="campos[<?= $field->name ?>][oculto_ver]" value="1"
                                           <?= old("campos.{$field->name}.oculto_ver", $config['oculto_en_ver'] ?? false) ? 'checked' : '' ?>>
                                    Ocultar en vista detalle
                                </label>
                            </div>
                        </div>
                        
                        <!-- SECCIONES DINÁMICAS -->
                        
                        <!-- Relación (para select) -->
<div class="row mt-2 relacion-options" id="relacion-<?= $field->name ?>" style="display: none;">
    <div class="col-md-12 mb-2">
        <button type="button" class="btn btn-sm btn-outline-primary asistente-relacion" 
                data-campo="<?= $field->name ?>">
            <i class="bi bi-magic"></i> Asistente de relación
        </button>
    </div>
    <div class="col-md-4">
        <label class="form-label">Tabla relación</label>
        <input type="text" class="form-control" 
               name="campos[<?= $field->name ?>][relacion_tabla]" 
               id="relacion_tabla_<?= $field->name ?>"
               value="<?= old("campos.{$field->name}.relacion_tabla", $config['relacion_tabla'] ?? '') ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Campo a mostrar</label>
        <input type="text" class="form-control" 
               name="campos[<?= $field->name ?>][relacion_campo]" 
               id="relacion_campo_<?= $field->name ?>"
               value="<?= old("campos.{$field->name}.relacion_campo", $config['relacion_campo'] ?? '') ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Campo ID</label>
        <input type="text" class="form-control" 
               name="campos[<?= $field->name ?>][relacion_id]" 
               id="relacion_id_<?= $field->name ?>"
               value="<?= old("campos.{$field->name}.relacion_id", $config['relacion_id'] ?? '') ?>">
    </div>
</div>
                        
                        <!-- Valores posibles (para enum) -->
                        <div class="row mt-2 enum-options" id="enum-<?= $field->name ?>" style="display: none;">
                            <div class="col-md-12">
                                <label class="form-label">Valores posibles</label>
                                <input type="text" class="form-control" 
                                    name="campos[<?= $field->name ?>][valores_posibles]" 
                                    value="<?= old("campos.{$field->name}.valores_posibles", 
                                        $config['valores_posibles'] ?? 
                                        (strtolower($field->type) === 'enum' ? str_replace("'", '', $field->enum_options ?? '') : '')
                                    ) ?>"
                                    placeholder="Ej: Activo/Inactivo, Si/No, Aprobado/Rechazado">
                                <?php if (property_exists($field, 'enum_options') && !empty($field->enum_options)): ?>
                                    <small class="text-muted">Original en BD: <?= $field->enum_options ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Opciones de archivo -->
                        <div class="row mt-2 file-options" id="file-<?= $field->name ?>" style="display: none;">
                            <div class="col-md-3">
                                <label class="form-label">Tipos permitidos</label>
                                <input type="text" class="form-control" 
                                       name="campos[<?= $field->name ?>][archivo_tipo_permitido]" 
                                       value="<?= old("campos.{$field->name}.archivo_tipo_permitido", $config['archivo_tipo_permitido'] ?? '') ?>"
                                       placeholder="Ej: jpg,png,pdf">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tamaño máximo (MB)</label>
                                <input type="text" class="form-control" 
                                       name="campos[<?= $field->name ?>][archivo_tamano_maximo]" 
                                       value="<?= old("campos.{$field->name}.archivo_tamano_maximo", $config['archivo_tamano_maximo'] ?? '') ?>"
                                       placeholder="Ej: 2">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Carpeta destino</label>
                                <input type="text" class="form-control" 
                                       name="campos[<?= $field->name ?>][archivo_carpeta_destino]" 
                                       value="<?= old("campos.{$field->name}.archivo_carpeta_destino", $config['archivo_carpeta_destino'] ?? '') ?>"
                                       placeholder="Ej: uploads/productos">
                            </div>
                            <div class="col-md-3">
                                <label class="form-check-label mt-4">
                                    <input type="checkbox" name="campos[<?= $field->name ?>][archivo_subcarpeta_por_entidad]" value="1"
                                           <?= old("campos.{$field->name}.archivo_subcarpeta_por_entidad", $config['archivo_subcarpeta_por_entidad'] ?? false) ? 'checked' : '' ?>>
                                    Subcarpeta por entidad
                                </label>
                            </div>
                        </div>
                        
                        <!-- Comportamiento hidden -->
                        <div class="row mt-2 hidden-options" id="hidden-<?= $field->name ?>" style="display: none;">
                            <div class="col-md-4">
                                <label class="form-label">Comportamiento</label>
                                <select class="form-select" name="campos[<?= $field->name ?>][comportamiento_hidden]">
                                    <option value="usar_default_db" <?= old("campos.{$field->name}.comportamiento_hidden", $config['comportamiento_hidden'] ?? '') === 'usar_default_db' ? 'selected' : '' ?>>Usar default de BD</option>
                                    <option value="dejar_null" <?= old("campos.{$field->name}.comportamiento_hidden", $config['comportamiento_hidden'] ?? '') === 'dejar_null' ? 'selected' : '' ?>>Dejar NULL</option>
                                    <option value="forzar_valor" <?= old("campos.{$field->name}.comportamiento_hidden", $config['comportamiento_hidden'] ?? '') === 'forzar_valor' ? 'selected' : '' ?>>Forzar valor</option>
                                </select>
                            </div>
                            <div class="col-md-8 hidden-valor" id="hidden-valor-<?= $field->name ?>" style="display: none;">
                                <label class="form-label">Valor a forzar</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <select class="form-select" name="campos[<?= $field->name ?>][valor_default_tipo]">
                                            <option value="">Valor fijo</option>
                                            <option value="__NOW__" <?= old("campos.{$field->name}.valor_default_tipo", $config['valor_default'] ?? '') === '__NOW__' ? 'selected' : '' ?>>Fecha/hora actual</option>
                                            <option value="__USER_ID__" <?= old("campos.{$field->name}.valor_default_tipo", $config['valor_default'] ?? '') === '__USER_ID__' ? 'selected' : '' ?>>ID de usuario</option>
                                            <option value="__CONTROLADOR__" <?= old("campos.{$field->name}.valor_default_tipo", $config['valor_default'] ?? '') === '__CONTROLADOR__' ? 'selected' : '' ?>>Desde controlador</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" 
                                               name="campos[<?= $field->name ?>][valor_default]" 
                                               value="<?= old("campos.{$field->name}.valor_default", $config['valor_default'] ?? '') ?>"
                                               placeholder="Ej: Valor fijo o __CONTROLADOR__:campo">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if ($camposMostrados === 0): ?>
                <div class="alert alert-info">
                    No hay campos configurables en esta tabla (todos son IDs).
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECCIÓN 3: CAMPOS VIRTUALES -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Campos virtuales</h5>
    </div>
    <div class="card-body">
        <button type="button" class="btn btn-success mb-3" onclick="agregarCampoVirtual()">
            <i class="bi bi-plus-circle"></i> Agregar campo virtual
        </button>
        
        <div id="virtual-fields-container">
            <?php if (!empty($camposVirtuales)): ?>
                <?php foreach ($camposVirtuales as $index => $vf): ?>
                    <div class="card mb-2 virtual-field" id="virtual-<?= $index ?>">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control" 
                                           name="virtual_fields[<?= $index ?>][nombre]" 
                                           value="<?= esc($vf['nombre']) ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Tipo</label>
                                    <select class="form-select virtual-tipo" 
                                            name="virtual_fields[<?= $index ?>][tipo]"
                                            onchange="toggleVirtualOptions(this)">
                                        <option value="display" <?= $vf['tipo'] === 'display' ? 'selected' : '' ?>>Visualización</option>
                                        <option value="n_a_n" <?= $vf['tipo'] === 'n_a_n' ? 'selected' : '' ?>>N a N</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">Orden</label>
                                    <input type="number" class="form-control" 
                                           name="virtual_fields[<?= $index ?>][orden_visual]" 
                                           value="<?= esc($vf['orden_visual'] ?? 0) ?>">
                                </div>
                                <div class="col-md-6">
                                    <!-- Checkboxes comunes -->
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="form-check-label">
                                                <input type="checkbox" name="virtual_fields[<?= $index ?>][oculto_lista]" value="1"
                                                       <?= !empty($vf['oculto_en_lista']) ? 'checked' : '' ?>>
                                                Ocultar en lista
                                            </label>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-check-label">
                                                <input type="checkbox" name="virtual_fields[<?= $index ?>][oculto_form]" value="1"
                                                       <?= !empty($vf['oculto_en_form']) ? 'checked' : '' ?>>
                                                Ocultar en formulario
                                            </label>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-check-label">
                                                <input type="checkbox" name="virtual_fields[<?= $index ?>][oculto_ver]" value="1"
                                                       <?= !empty($vf['oculto_en_ver']) ? 'checked' : '' ?>>
                                                Ocultar en vista detalle
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- Opciones para display -->
                                    <div class="virtual-display-options mt-2" style="<?= $vf['tipo'] === 'display' ? 'display:block' : 'display:none' ?>">
                                        <label class="form-label">Función</label>
                                        <input type="text" class="form-control" 
                                               name="virtual_fields[<?= $index ?>][funcion_display]" 
                                               value="<?= esc($vf['funcion_display'] ?? '') ?>"
                                               placeholder="getPerfil">
                                    </div>
                                    
                                    <!-- Opciones para N a N -->
                                    <div class="virtual-nan-options mt-2" style="<?= $vf['tipo'] === 'n_a_n' ? 'display:block' : 'display:none' ?>">
                                        <div class="row mb-2">
                                            <div class="col-12">
                                                <button type="button" class="btn btn-sm btn-outline-primary asistente-nan" 
                                                        onclick="abrirAsistenteNan(this)" data-index="<?= $index ?>">
                                                    <i class="bi bi-magic"></i> Asistente N a N
                                                </button>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Tabla intermedia</label>
                                                <input type="text" class="form-control" 
                                                        id="tabla_intermedia_<?= $index ?>"
                                                        name="virtual_fields[<?= $index ?>][tabla_intermedia]" 
                                                        value="<?= esc($vf['tabla_intermedia'] ?? '') ?>"
                                                        placeholder="ej: productos_categorias">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Tabla fuente</label>
                                                <input type="text" class="form-control" 
                                                       name="virtual_fields[<?= $index ?>][tabla_fuente]" 
                                                       value="<?= esc($vf['tabla_fuente'] ?? '') ?>"
                                                       placeholder="ej: categorias">
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-4">
                                                <label class="form-label">Campo local FK</label>
                                                <input type="text" class="form-control" 
                                                       name="virtual_fields[<?= $index ?>][campo_local_fk]" 
                                                       value="<?= esc($vf['campo_local_fk'] ?? '') ?>"
                                                       placeholder="ej: producto_id">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Campo externo FK</label>
                                                <input type="text" class="form-control" 
                                                       name="virtual_fields[<?= $index ?>][campo_externo_fk]" 
                                                       value="<?= esc($vf['campo_externo_fk'] ?? '') ?>"
                                                       placeholder="ej: categoria_id">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Campo ID fuente</label>
                                                <input type="text" class="form-control" 
                                                       name="virtual_fields[<?= $index ?>][campo_id_fuente]" 
                                                       value="<?= esc($vf['campo_id_fuente'] ?? '') ?>"
                                                       placeholder="ej: id_categoria">
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <label class="form-label">Formato visualización</label>
                                                <input type="text" class="form-control" 
                                                       name="virtual_fields[<?= $index ?>][formato_visualizacion]" 
                                                       value="<?= esc($vf['formato_visualizacion'] ?? '') ?>"
                                                       placeholder="ej: {nombre} - {descripcion}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="button" class="btn btn-danger w-100" onclick="this.closest('.virtual-field').remove()">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

    <div class="row mb-4">
        <div class="col-12">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Guardar configuración
            </button>
            <a href="<?= base_url('admin/gestor-campos') ?>" class="btn btn-secondary">
                Cancelar
            </a>
        </div>
    </div>
</form>

<!-- Modal Asistente de Relación 1 a N -->
<div class="modal fade" id="modalAsistenteRelacion" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Asistente de Relación 1 a N</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="asistenteCampoActual">
                
                <!-- Paso 1: Seleccionar tabla -->
                <div id="paso1-seleccion-tabla">
                    <div class="mb-3">
                        <label class="form-label">Selecciona la tabla relacionada:</label>
                        <select class="form-select" id="asistenteTabla">
                            <option value="">Cargando tablas...</option>
                        </select>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-primary" id="btnSiguientePaso2" disabled>
                            Siguiente <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Paso 2: Seleccionar campos (inicialmente oculto) -->
                <div id="paso2-seleccion-campos" style="display: none;">
                    <div class="alert alert-info" id="camposCargando" style="display: none;">
                        <i class="bi bi-hourglass-split"></i> Cargando campos...
                    </div>
                    
                    <div id="contenedorCampos">
                        <!-- Se cargará vía AJAX -->
                    </div>
                    
                    <div class="mt-3 text-end">
                        <button type="button" class="btn btn-secondary" id="btnVolverPaso1">
                            <i class="bi bi-arrow-left"></i> Volver
                        </button>
                        <button type="button" class="btn btn-success" id="btnAplicarRelacion">
                            <i class="bi bi-check-lg"></i> Aplicar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal Asistente de Relación N a N -->
<div class="modal fade" id="modalAsistenteNan" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Asistente de Relación N a N</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="nanCampoIndex">
                
                <!-- Paso 1: Seleccionar tablas -->
                <div id="nan-paso1">
                    <div class="mb-3">
                        <label class="form-label">Tabla intermedia (puente):</label>
                        <select class="form-select" id="nanTablaIntermedia">
                            <option value="">Cargando tablas...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tabla fuente (datos a mostrar):</label>
                        <select class="form-select" id="nanTablaFuente" disabled>
                            <option value="">Primero selecciona tabla intermedia</option>
                        </select>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-primary" id="btnNanSiguiente" disabled>
                            Siguiente <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Paso 2: Configurar campos -->
                <div id="nan-paso2" style="display: none;">
                    <div class="alert alert-info" id="nanCargando" style="display: none;">
                        <i class="bi bi-hourglass-split"></i> Analizando estructura...
                    </div>
                    
                    <div id="nanContenedorCampos">
                        <!-- Se cargará vía AJAX -->
                    </div>
                    
                    <div class="mt-3 text-end">
                        <button type="button" class="btn btn-secondary" id="btnNanVolver">
                            <i class="bi bi-arrow-left"></i> Volver
                        </button>
                        <button type="button" class="btn btn-success" id="btnNanAplicar">
                            <i class="bi bi-check-lg"></i> Aplicar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
window.baseUrl = '<?= base_url() ?>';
window.adminUrl = '<?= site_url('admin/gestor-campos') ?>';
window.tablaActual = '<?= $tablaNombre ?>';
let virtualCounter = <?= count($camposVirtuales ?? []) ?>;

//relaioneas N a N

// Variable global para el índice del campo virtual actual
let nanIndiceActual = null;

function abrirAsistenteNan(boton) {
    // Obtener el índice del campo virtual (viene del onclick)
    const virtualField = boton.closest('.virtual-field');
    const index = virtualField.id.replace('virtual-', '');
    nanIndiceActual = index;
    
    // Resetear modal
    document.getElementById('nan-paso1').style.display = 'block';
    document.getElementById('nan-paso2').style.display = 'none';
    document.getElementById('btnNanSiguiente').disabled = true;
    
    // Cargar tablas
    cargarTablasNan();
    
    new bootstrap.Modal(document.getElementById('modalAsistenteNan')).show();
}

function cargarTablasNan() {
    const selectIntermedia = document.getElementById('nanTablaIntermedia');
    selectIntermedia.innerHTML = '<option value="">Cargando tablas...</option>';
    selectIntermedia.disabled = true;
    
    fetch('<?= site_url('admin/gestor-campos/getTablas') ?>')
        .then(response => response.json())
        .then(data => {
            selectIntermedia.disabled = false;
            selectIntermedia.innerHTML = '<option value="">Seleccione tabla intermedia...</option>';
            data.forEach(tabla => {
                const option = document.createElement('option');
                option.value = tabla;
                option.textContent = tabla;
                selectIntermedia.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            selectIntermedia.disabled = false;
            selectIntermedia.innerHTML = '<option value="">Error al cargar tablas</option>';
        });
}

document.getElementById('nanTablaIntermedia').addEventListener('change', function() {
    const intermedia = this.value;
    const selectFuente = document.getElementById('nanTablaFuente');
    
    if (!intermedia) {
        selectFuente.innerHTML = '<option value="">Primero selecciona tabla intermedia</option>';
        selectFuente.disabled = true;
        document.getElementById('btnNanSiguiente').disabled = true;
        return;
    }
    
    // Cargar tablas candidatas para tabla fuente (excluir la intermedia y las del sistema)
    selectFuente.innerHTML = '<option value="">Cargando...</option>';
    selectFuente.disabled = true;
    
    fetch('<?= site_url('admin/gestor-campos/getTablas') ?>')
        .then(response => response.json())
        .then(data => {
            selectFuente.disabled = false;
            selectFuente.innerHTML = '<option value="">Seleccione tabla fuente...</option>';
            
            // Filtrar para no mostrar la intermedia
            const tablas = data.filter(t => t !== intermedia);
            tablas.forEach(tabla => {
                const option = document.createElement('option');
                option.value = tabla;
                option.textContent = tabla;
                selectFuente.appendChild(option);
            });
        });
});

document.getElementById('nanTablaFuente').addEventListener('change', function() {
    document.getElementById('btnNanSiguiente').disabled = !this.value;
});

document.getElementById('btnNanSiguiente').addEventListener('click', function() {
    const intermedia = document.getElementById('nanTablaIntermedia').value;
    const fuente = document.getElementById('nanTablaFuente').value;
    
    if (!intermedia || !fuente) return;
    
    // Mostrar paso 2
    document.getElementById('nan-paso1').style.display = 'none';
    document.getElementById('nan-paso2').style.display = 'block';
    document.getElementById('nanCargando').style.display = 'block';
    document.getElementById('nanContenedorCampos').innerHTML = '';
    
    // Petición al servidor
    const url = '<?= site_url('admin/gestor-campos/get-selector-nan') ?>' +
                '?intermedia=' + encodeURIComponent(intermedia) +
                '&fuente=' + encodeURIComponent(fuente) +
                '&actual=' + encodeURIComponent(window.tablaActual);
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
    document.getElementById('nanCargando').style.display = 'none';
    
    if (data.success) {
        document.getElementById('nanContenedorCampos').innerHTML = data.html;
        
        // Asignar eventos a los checkboxes de campos a mostrar
        document.querySelectorAll('.campo-mostrar-nan').forEach(cb => {
            cb.addEventListener('change', actualizarFormatoManualNan);
        });
        
        // Eventos para botones seleccionar/deseleccionar todos
        const btnSeleccionar = document.getElementById('seleccionarTodosNan');
        const btnDeseleccionar = document.getElementById('deseleccionarTodosNan');
        
        if (btnSeleccionar) {
            btnSeleccionar.addEventListener('click', function() {
                document.querySelectorAll('.campo-mostrar-nan').forEach(cb => cb.checked = true);
                actualizarFormatoManualNan();
            });
        }
        
        if (btnDeseleccionar) {
            btnDeseleccionar.addEventListener('click', function() {
                document.querySelectorAll('.campo-mostrar-nan').forEach(cb => cb.checked = false);
                actualizarFormatoManualNan();
            });
        }
        
        // Generar formato inicial
        actualizarFormatoManualNan();
    } else {
        document.getElementById('nanContenedorCampos').innerHTML = 
            '<div class="alert alert-danger">' + data.error + '</div>';
    }
})
        .catch(error => {
            document.getElementById('nanCargando').style.display = 'none';
            document.getElementById('nanContenedorCampos').innerHTML = 
                '<div class="alert alert-danger">Error de conexión</div>';
            console.error(error);
        });
});


// Botón Volver
const btnVolver = document.getElementById('btnNanVolver');
if (btnVolver) {
    btnVolver.addEventListener('click', function() {
        document.getElementById('nan-paso2').style.display = 'none';
        document.getElementById('nan-paso1').style.display = 'block';
        document.getElementById('nanContenedorCampos').innerHTML = '';
    });
}

function actualizarFormatoManualNan() {
    const seleccionados = [];
    document.querySelectorAll('.campo-mostrar-nan:checked').forEach(cb => {
        seleccionados.push(cb.value);
    });
    
    const formatoInput = document.getElementById('formatoManualNan');
    if (!formatoInput) return;
    
    if (seleccionados.length === 0) {
        formatoInput.value = '';
        return;
    }
    
    const formato = seleccionados.map(c => `{${c}}`).join(' - ');
    formatoInput.value = formato;
}

document.getElementById('btnNanAplicar').addEventListener('click', function() {
    // Obtener valores seleccionados
    const campoLocal = document.querySelector('input[name="campoLocal"]:checked')?.value;
    const campoExterno = document.querySelector('input[name="campoExterno"]:checked')?.value;
    const campoIdFuente = document.getElementById('campoIdFuente')?.value;
    const formato = document.getElementById('formatoManualNan')?.value;
    const intermedia = document.getElementById('nanTablaIntermedia').value;
    const fuente = document.getElementById('nanTablaFuente').value;
    
    // Validaciones
    if (!campoLocal) {
        alert('Debes seleccionar un campo local FK');
        return;
    }
    
    if (!campoExterno) {
        alert('Debes seleccionar un campo externo FK');
        return;
    }
    
    if (campoLocal === campoExterno) {
        alert('El campo local y el campo externo no pueden ser el mismo');
        return;
    }
    
    if (!campoIdFuente) {
        alert('No se pudo determinar el campo ID de la tabla fuente');
        return;
    }
    
    if (!formato) {
        alert('Debes especificar un formato de visualización');
        return;
    }
    
    // Asignar valores a los campos del virtual
    const virtualField = document.getElementById('virtual-' + nanIndiceActual);
    
    if (virtualField) {
        const inputTablaIntermedia = virtualField.querySelector('input[name*="[tabla_intermedia]"]');
        const inputTablaFuente = virtualField.querySelector('input[name*="[tabla_fuente]"]');
        const inputCampoLocal = virtualField.querySelector('input[name*="[campo_local_fk]"]');
        const inputCampoExterno = virtualField.querySelector('input[name*="[campo_externo_fk]"]');
        const inputCampoIdFuente = virtualField.querySelector('input[name*="[campo_id_fuente]"]');
        const inputFormato = virtualField.querySelector('input[name*="[formato_visualizacion]"]');
        
        if (inputTablaIntermedia) inputTablaIntermedia.value = intermedia;
        if (inputTablaFuente) inputTablaFuente.value = fuente;
        if (inputCampoLocal) inputCampoLocal.value = campoLocal;
        if (inputCampoExterno) inputCampoExterno.value = campoExterno;
        if (inputCampoIdFuente) inputCampoIdFuente.value = campoIdFuente;
        if (inputFormato) inputFormato.value = formato;
    }
    
    // Cerrar modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalAsistenteNan'));
    if (modal) modal.hide();
    
    // Mostrar notificación
    if (typeof mostrarNotificacion === 'function') {
        mostrarNotificacion('Relación N a N configurada correctamente', 'success');
    }
});


</script>




<script>
// para asistenete 1 a N
let camposTablaSeleccionada = [];

// Abrir modal al hacer clic en asistente
document.querySelectorAll('.asistente-relacion').forEach(btn => {
    btn.addEventListener('click', function() {
        const campo = this.dataset.campo;
        document.getElementById('asistenteCampoActual').value = campo;
        
        // Resetear modal
        document.getElementById('paso1-seleccion-tabla').style.display = 'block';
        document.getElementById('paso2-seleccion-campos').style.display = 'none';
        document.getElementById('btnSiguientePaso2').disabled = true;
        
        // Cargar lista de tablas
        cargarTablas();
        
        new bootstrap.Modal(document.getElementById('modalAsistenteRelacion')).show();
    });
});

// Cargar tablas disponibles
function cargarTablas() {
    const select = document.getElementById('asistenteTabla');
    select.innerHTML = '<option value="">Cargando tablas...</option>';
    select.disabled = true;
    
    // Usar site_url de CodeIgniter
    fetch('<?= site_url('admin/gestor-campos/getTablas') ?>')
        .then(response => {
            if (!response.ok) {
                throw new Error('Error HTTP: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            select.disabled = false;
            select.innerHTML = '<option value="">Seleccione una tabla...</option>';
            data.forEach(tabla => {
                const option = document.createElement('option');
                option.value = tabla;
                option.textContent = tabla;
                select.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error cargando tablas:', error);
            select.disabled = false;
            select.innerHTML = '<option value="">Error al cargar tablas</option>';
        });
}

// Variable global para almacenar campos
let camposTablaActual = [];

// Evento al seleccionar tabla
document.getElementById('asistenteTabla').addEventListener('change', function() {
    const tabla = this.value;
    document.getElementById('btnSiguientePaso2').disabled = !tabla;
    
    if (tabla) {
        // Precargar campos para cuando se haga clic en Siguiente
        precargarCampos(tabla);
    }
});

function precargarCampos(tabla) {
    const select = document.getElementById('asistenteTabla');
    select.disabled = true;
    
    fetch('<?= site_url('admin/gestor-campos/getCamposDeTabla') ?>?tabla=' + encodeURIComponent(tabla))
        .then(response => response.json())
        .then(data => {
            select.disabled = false;
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            camposTablaActual = data;
        })
        .catch(error => {
            console.error('Error:', error);
            select.disabled = false;
        });
}

// Evento para el botón Siguiente
document.getElementById('btnSiguientePaso2').addEventListener('click', function() {
    const tabla = document.getElementById('asistenteTabla').value;
    if (!tabla) return;
    
    // Mostrar paso 2
    document.getElementById('paso1-seleccion-tabla').style.display = 'none';
    document.getElementById('paso2-seleccion-campos').style.display = 'block';
    
    // Mostrar cargando
    document.getElementById('camposCargando').style.display = 'block';
    document.getElementById('contenedorCampos').innerHTML = '';
    
    // Si ya tenemos los campos precargados, mostrarlos
    if (camposTablaActual.length > 0) {
        mostrarCampos(camposTablaActual);
    } else {
        // Si no, cargarlos ahora
        fetch('<?= site_url('admin/gestor-campos/getCamposDeTabla') ?>?tabla=' + encodeURIComponent(tabla))
            .then(response => response.json())
            .then(data => {
                document.getElementById('camposCargando').style.display = 'none';
                if (data.error) {
                    document.getElementById('contenedorCampos').innerHTML = 
                        '<div class="alert alert-danger">Error: ' + data.error + '</div>';
                    return;
                }
                mostrarCampos(data);
            })
            .catch(error => {
                document.getElementById('camposCargando').style.display = 'none';
                document.getElementById('contenedorCampos').innerHTML = 
                    '<div class="alert alert-danger">Error al cargar campos</div>';
            });
    }
});


function mostrarCampos(campos) {
    document.getElementById('camposCargando').style.display = 'none';
    
    let html = `
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> 
            Selecciona el campo ID (clave primaria) y los campos a mostrar.
        </div>
    `;
    
    // Separar campos ID (primary key) del resto
    const camposId = campos.filter(c => c.is_primary);
    const otrosCampos = campos.filter(c => !c.is_primary);
    
    // Sección para campo ID
    html += `<div class="card mb-3">
        <div class="card-header bg-light">
            <strong>Campo ID (clave primaria)</strong>
        </div>
        <div class="card-body">`;
    
    if (camposId.length === 0) {
        html += `<p class="text-muted">No se detectó clave primaria. Selecciona manualmente:</p>`;
        // Si no hay primary key, mostrar todos como opciones
        campos.forEach(c => {
            html += `
            <div class="form-check">
                <input class="form-check-input campo-id-radio" type="radio" name="campoIdSeleccionado" 
                       value="${c.name}" id="id_${c.name}" ${c.is_auto_increment ? 'checked' : ''}>
                <label class="form-check-label" for="id_${c.name}">
                    <code>${c.name}</code> <small class="text-muted">(${c.type})</small>
                    ${c.is_auto_increment ? '<span class="badge bg-success">auto</span>' : ''}
                </label>
            </div>`;
        });
    } else {
        // Mostrar las primary keys detectadas
        camposId.forEach(c => {
            html += `
            <div class="form-check">
                <input class="form-check-input campo-id-radio" type="radio" name="campoIdSeleccionado" 
                       value="${c.name}" id="id_${c.name}" checked>
                <label class="form-check-label" for="id_${c.name}">
                    <code>${c.name}</code> <small class="text-muted">(${c.type})</small>
                    ${c.is_auto_increment ? '<span class="badge bg-success">auto</span>' : ''}
                </label>
            </div>`;
        });
    }
    
    html += `</div></div>`;
    
    // Sección para campos a mostrar
    html += `<div class="card mb-3">
        <div class="card-header bg-light">
            <strong>Campos a mostrar (puedes seleccionar varios)</strong>
        </div>
        <div class="card-body">`;
    
    // Botones para seleccionar/deseleccionar todos
    html += `
        <div class="mb-2">
            <button type="button" class="btn btn-sm btn-outline-primary" id="seleccionarTodos">Seleccionar todos</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="deseleccionarTodos">Deseleccionar todos</button>
        </div>
        <div class="row">`;
    
    // Mostrar campos en dos columnas
    otrosCampos.forEach((c, index) => {
        if (index % 2 === 0) html += '<div class="row mb-2">';
        html += `
            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input campo-mostrar" type="checkbox" 
                           value="${c.name}" id="mostrar_${c.name}" checked>
                    <label class="form-check-label" for="mostrar_${c.name}">
                        <code>${c.name}</code> <small class="text-muted">(${c.type})</small>
                    </label>
                </div>
            </div>`;
        if (index % 2 === 1 || index === otrosCampos.length - 1) html += '</div>';
    });
    
    html += `</div></div></div>`;
    
    // Sección para formato de visualización
    html += `<div class="card">
        <div class="card-header bg-light">
            <strong>Formato de visualización</strong>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Formato generado automáticamente:</label>
                <input type="text" class="form-control" id="formatoGenerado" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">O puedes editarlo manualmente:</label>
                <input type="text" class="form-control" id="formatoManual" 
                       placeholder="Ej: {nombre} - {codigo}">
                <small class="text-muted">Usa {nombre_campo} para cada campo</small>
            </div>
        </div>
    </div>`;
    
    document.getElementById('contenedorCampos').innerHTML = html;
    
    // Generar formato inicial
    actualizarFormato();
    
    // Eventos para checkboxes
    document.querySelectorAll('.campo-mostrar').forEach(cb => {
        cb.addEventListener('change', actualizarFormato);
    });
    
    document.getElementById('seleccionarTodos').addEventListener('click', function() {
        document.querySelectorAll('.campo-mostrar').forEach(cb => cb.checked = true);
        actualizarFormato();
    });
    
    document.getElementById('deseleccionarTodos').addEventListener('click', function() {
        document.querySelectorAll('.campo-mostrar').forEach(cb => cb.checked = false);
        actualizarFormato();
    });
    
    document.getElementById('formatoManual').addEventListener('input', function() {
        // Cuando el usuario edita manualmente, no actualizamos el automático
    });
}

function actualizarFormato() {
    const seleccionados = [];
    document.querySelectorAll('.campo-mostrar:checked').forEach(cb => {
        seleccionados.push(cb.value);
    });
    
    if (seleccionados.length === 0) {
        document.getElementById('formatoGenerado').value = '';
        document.getElementById('formatoManual').value = '';
        return;
    }
    
    // Generar formato: {campo1} - {campo2} - {campo3}
    let formato = seleccionados.map(c => `{${c}}`).join(' - ');
    document.getElementById('formatoGenerado').value = formato;
    
    // Si el manual está vacío, copiar el generado
    if (!document.getElementById('formatoManual').value) {
        document.getElementById('formatoManual').value = formato;
    }
}

// Botón Volver al paso 1
document.getElementById('btnVolverPaso1').addEventListener('click', function() {
    document.getElementById('paso2-seleccion-campos').style.display = 'none';
    document.getElementById('paso1-seleccion-tabla').style.display = 'block';
});

// Botón Aplicar relación
document.getElementById('btnAplicarRelacion').addEventListener('click', function() {
    const campo = document.getElementById('asistenteCampoActual').value;
    const tabla = document.getElementById('asistenteTabla').value;
    const campoId = document.querySelector('input[name="campoIdSeleccionado"]:checked')?.value;
    const formato = document.getElementById('formatoManual').value;
    
    if (!tabla || !campoId || !formato) {
        alert('Debes seleccionar tabla, campo ID y formato');
        return;
    }
    
    // Asignar valores a los campos del formulario principal
    document.getElementById('relacion_tabla_' + campo).value = tabla;
    document.getElementById('relacion_id_' + campo).value = campoId;
    document.getElementById('relacion_campo_' + campo).value = formato;
    
    // Cerrar modal
    bootstrap.Modal.getInstance(document.getElementById('modalAsistenteRelacion')).hide();
    
    // Mostrar notificación
    mostrarNotificacion('Relación configurada correctamente', 'success');
});

</script>

<script>
//procesamiento estandar
//let virtualCounter = <?= count($camposVirtuales ?? []) ?>;

function agregarCampoVirtual() {
    const container = document.getElementById('virtual-fields-container');
    const index = virtualCounter++;
    
    const div = document.createElement('div');
    div.className = 'card mb-2 virtual-field';
    div.id = 'virtual-' + index;
    div.innerHTML = `
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" class="form-control" 
                           name="virtual_fields[${index}][nombre]" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo</label>
                    <select class="form-select virtual-tipo" 
                            name="virtual_fields[${index}][tipo]"
                            onchange="toggleVirtualOptions(this)">
                        <option value="display">Visualización</option>
                        <option value="n_a_n">N a N</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Orden</label>
                    <input type="number" class="form-control" 
                           name="virtual_fields[${index}][orden_visual]" value="0">
                </div>
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-check-label">
                                <input type="checkbox" name="virtual_fields[${index}][oculto_lista]" value="1">
                                Ocultar en lista
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="form-check-label">
                                <input type="checkbox" name="virtual_fields[${index}][oculto_form]" value="1">
                                Ocultar en formulario
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="form-check-label">
                                <input type="checkbox" name="virtual_fields[${index}][oculto_ver]" value="1">
                                Ocultar en vista detalle
                            </label>
                        </div>
                    </div>
                    
                    <div class="virtual-display-options mt-2" style="display:none">
                        <label class="form-label">Función</label>
                        <input type="text" class="form-control" 
                               name="virtual_fields[${index}][funcion_display]" 
                               placeholder="getPerfil">
                    </div>
                    
                    <div class="virtual-nan-options mt-2" style="display:none">
                        <div class="row mb-2">
                            <div class="col-12">
                                <button type="button" class="btn btn-sm btn-outline-primary asistente-nan" 
                                        onclick="abrirAsistenteNan(this)" 
                                        data-index="${index}">
                                    <i class="bi bi-magic"></i> Asistente N a N
                                </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Tabla intermedia</label>
                                <input type="text" class="form-control" 
                                       name="virtual_fields[${index}][tabla_intermedia]" 
                                       placeholder="ej: productos_categorias">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tabla fuente</label>
                                <input type="text" class="form-control" 
                                       name="virtual_fields[${index}][tabla_fuente]" 
                                       placeholder="ej: categorias">
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-4">
                                <label class="form-label">Campo local FK</label>
                                <input type="text" class="form-control" 
                                       name="virtual_fields[${index}][campo_local_fk]" 
                                       placeholder="ej: producto_id">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Campo externo FK</label>
                                <input type="text" class="form-control" 
                                       name="virtual_fields[${index}][campo_externo_fk]" 
                                       placeholder="ej: categoria_id">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Campo ID fuente</label>
                                <input type="text" class="form-control" 
                                       name="virtual_fields[${index}][campo_id_fuente]" 
                                       placeholder="ej: id_categoria">
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <label class="form-label">Formato visualización</label>
                                <input type="text" class="form-control" 
                                       name="virtual_fields[${index}][formato_visualizacion]" 
                                       placeholder="ej: {nombre} - {descripcion}">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger w-100" onclick="this.closest('.virtual-field').remove()">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(div);
}

function toggleVirtualOptions(select) {
    const row = select.closest('.virtual-field');
    const displayOptions = row.querySelector('.virtual-display-options');
    const nanOptions = row.querySelector('.virtual-nan-options');
    
    if (select.value === 'display') {
        displayOptions.style.display = 'block';
        nanOptions.style.display = 'none';
    } else {
        displayOptions.style.display = 'none';
        nanOptions.style.display = 'block';
    }
}

function mostrarOpcionesPorTipo(select) {
    const campo = select.dataset.campo;
    const valor = select.value;
    
    // Ocultar todas las opciones primero
    document.querySelectorAll(`#relacion-${campo}, #enum-${campo}, #file-${campo}, #hidden-${campo}`).forEach(function(div) {
        if (div) div.style.display = 'none';
    });
    
    // Mostrar según el tipo
    if (valor === 'select') {
        const div = document.getElementById(`relacion-${campo}`);
        if (div) div.style.display = 'flex';
    } else if (valor === 'enum') {
        const div = document.getElementById(`enum-${campo}`);
        if (div) div.style.display = 'flex';
    } else if (valor === 'file' || valor === 'image') {
        const div = document.getElementById(`file-${campo}`);
        if (div) div.style.display = 'flex';
    } else if (valor === 'hidden') {
        const div = document.getElementById(`hidden-${campo}`);
        if (div) {
            div.style.display = 'flex';
            // Inicializar visibilidad del valor forzado
            const comportamiento = document.querySelector(`select[name="campos[${campo}][comportamiento_hidden]"]`);
            if (comportamiento) {
                toggleValorForzado(comportamiento);
            }
        }
    }
}

function toggleValorForzado(select) {
    const match = select.name.match(/\[([^\]]+)\]/);
    if (!match) return;
    
    const campo = match[1];
    const valorDiv = document.getElementById(`hidden-valor-${campo}`);
    if (valorDiv) {
        if (select.value === 'forzar_valor') {
            valorDiv.style.display = 'block';
        } else {
            valorDiv.style.display = 'none';
        }
    }
}

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar todos los selects de tipo
    document.querySelectorAll('.campo-tipo').forEach(function(select) {
        mostrarOpcionesPorTipo(select);
        select.addEventListener('change', function() {
            mostrarOpcionesPorTipo(this);
        });
    });
    
    // Inicializar todos los selects de comportamiento hidden
    document.querySelectorAll('select[name*="[comportamiento_hidden]"]').forEach(function(select) {
        toggleValorForzado(select);
        select.addEventListener('change', function() {
            toggleValorForzado(this);
        });
    });
});
</script>

<?= $this->endSection() ?>