<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php //var_dump(session()->getFlashdata('_ci_old_input')); ?>
<div class="container-fluid py-4">
    <!-- Título -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0">
            <i class="bi bi-<?= $data['action'] === 'create' ? 'plus-circle' : 'pencil' ?> me-2"></i>
            <?= $data['action'] === 'create' ? 'Crear' : 'Editar' ?> <?= esc($data['entity']['titulo']) ?>
        </h2>
        <a href="<?= $data['baseUrl'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Errores generales -->
    <?php if (!empty($data['errors'])): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($data['errors'] as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Formulario -->
    <form method="post" class="needs-validation" novalidate enctype="multipart/form-data">
        <?= csrf_field() ?>
        
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($data['fields'] as $field => $attrs): 
                        if (($attrs['hidden_in_form'] ?? false)) continue;
                        if ($attrs['type'] === 'hidden') continue;
                        
                        $value = $data['record'][$field] ?? $attrs['valor_default'] ?? '';
                        $errorClass = isset($data['errors'][$field]) ? 'is-invalid' : '';
                        $required = strpos($attrs['validation_rules'] ?? '', 'required') !== false;
                    ?>
                        <div class="col-md-6">
                            <label class="form-label <?= $required ? 'required' : '' ?>">
                                <?= esc($attrs['label']) ?>
                            </label>
                            
                            <?php if ($attrs['type'] === 'text'): ?>
                                <input type="text" 
                                       class="form-control <?= $errorClass ?>" 
                                       name="<?= $field ?>" 
                                       value="<?= esc(old($field, $value)) ?>"
                                       <?= $required ? 'required' : '' ?>>
                                       
                            <?php elseif ($attrs['type'] === 'number'): ?>
                                <input type="number" 
                                       class="form-control <?= $errorClass ?>" 
                                       name="<?= $field ?>" 
                                       value="<?= esc(old($field, $value)) ?>"
                                       <?= $required ? 'required' : '' ?>
                                       step="any">

                            <?php elseif ($attrs['type'] === 'enum'): ?>
                                <select class="form-select <?= $errorClass ?> select-search" 
                                        name="<?= $field ?>" 
                                        <?= $required ? 'required' : '' ?>>
                                    <option value="">Seleccione...</option>
                                    <?php if (!empty($data['selectOptions'][$field])): ?>
                                        <?php foreach ($data['selectOptions'][$field] as $option): ?>
                                            <option value="<?= $option['value'] ?>" 
                                                <?= $option['value'] == old($field, $value) ? 'selected' : '' ?>>
                                                <?= esc($option['text']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            <?php elseif ($attrs['type'] === 'boolean'): ?>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="<?= $field ?>" value="0">
                                    <input type="checkbox" 
                                        class="form-check-input <?= $errorClass ?>" 
                                        name="<?= $field ?>" 
                                        id="<?= $field ?>" 
                                        value="1"
                                        <?= old($field, $value) ? 'checked' : '' ?>>
                                        <?= $required ? 'required' : '' ?>>
                                    <label class="form-check-label" for="<?= $field ?>">
                                        <?= '&nbsp;Si/No'; //esc($attrs['label']) ?>
                                    </label>
                                </div>
                            <?php elseif ($attrs['type'] === 'textarea'): ?>
                                <textarea class="form-control <?= $errorClass ?>" 
                                        name="<?= $field ?>" 
                                        rows="4"
                                        <?= $required ? 'required' : '' ?>><?= esc(old($field, $value)) ?></textarea>    
                            <?php elseif ($attrs['type'] === 'email'): ?>
                                <input type="email" 
                                    class="form-control <?= $errorClass ?>" 
                                    name="<?= $field ?>" 
                                    value="<?= esc(old($field, $value)) ?>"
                                    <?= $required ? 'required' : '' ?>>       
                                    
                            <?php elseif ($attrs['type'] === 'password'): ?>
                                <input type="password" 
                                    class="form-control <?= $errorClass ?>" 
                                    name="<?= $field ?>" 
                                    value=""
                                    <?= $required ? 'required' : '' ?>>        

                            <?php elseif ($attrs['type'] === 'date'): ?>
                                <input type="date" 
                                    class="form-control <?= $errorClass ?>" 
                                    name="<?= $field ?>" 
                                    value="<?= esc(old($field, $value)) ?>"
                                    <?= $required ? 'required' : '' ?>>     
                            <?php elseif ($attrs['type'] === 'datetime'): ?>
                                <input type="datetime-local" 
                                    class="form-control <?= $errorClass ?>" 
                                    name="<?= $field ?>" 
                                    value="<?= str_replace(' ', 'T', $value) ?>"
                                    <?= $required ? 'required' : '' ?>>    
                            <?php elseif ($attrs['type'] === 'select'): ?>
                                <select class="form-control select-search" 
                                        name="<?= $field ?>" 
                                        <?= $required ? 'required' : '' ?>>
                                    <option value="">Seleccione...</option>
                                    <?php if (!empty($data['selectOptions'][$field])): ?>
                                        <?php foreach ($data['selectOptions'][$field] as $option): ?>
                                            <option value="<?= $option['value'] ?>" 
                                                <?= $option['value'] == old($field, $value) ? 'selected' : '' ?>>
                                                <?= esc($option['text']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>     
                            <?php elseif ($attrs['type'] === 'wysiwyg'): ?>
                                <div class="mb-3">
                                    <!-- Contenedor para Quill -->
                                    <div id="editor-<?= $field ?>" style="height: 300px;"></div>
                                    
                                    <!-- Textarea oculto que guardará el HTML -->
                                    <textarea name="<?= $field ?>" 
                                            id="hidden-<?= $field ?>" 
                                            class="d-none"><?= esc(old($field, $value)) ?></textarea> 
                                </div>            
                            <?php elseif ($attrs['type'] === 'virtual_n_a_n'): ?>
                                <select class="form-control select-search-multiple" 
                                        name="<?= $field ?>[]" 
                                        multiple
                                        <?= $required ? 'required' : '' ?>>
                                    <?php if (!empty($data['selectOptions'][$field])): ?>
                                        <?php foreach ($data['selectOptions'][$field] as $option): ?>
                                            <option value="<?= $option['value'] ?>" 
                                                <?= in_array($option['value'], (array) old($field, $value ?? [])) ? 'selected' : '' ?>>
                                                <?= esc($option['text']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>    
                                
                            <?php elseif ($attrs['type'] === 'file'): ?>
                                <div class="mb-3">
                                    <?php if (!empty($value) && $data['action'] === 'edit'): ?>
                                        <div class="mb-2">
                                            <a href="<?= base_url($attrs['archivo_carpeta_destino'] . '/' . $value) ?>" target="_blank">
                                                <i class="bi bi-file-earmark"></i> <?= basename($value) ?>
                                            </a>
                                        </div>
                                        <?php if (!$required): ?>
                                            <div class="form-check mb-2">
                                                <input type="checkbox" class="form-check-input" name="remove_<?= $field ?>" id="remove_<?= $field ?>" value="1">
                                                <label class="form-check-label" for="remove_<?= $field ?>">Quitar archivo actual</label>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if (empty($value) || !$required): ?>
                                        <input type="file" class="form-control <?= $errorClass ?>" 
                                            name="<?= $field ?>" 
                                            accept="<?= str_replace(',', ',', $attrs['archivo_tipo_permitido'] ?? '') ?>">
                                    <?php endif; ?>
                                </div> 
                                
                            <?php elseif ($attrs['type'] === 'image'): ?>
                                <div class="mb-3">
                                    <?php if (!empty($value) && $data['action'] === 'edit'): ?>
                                        <div class="mb-2">
                                            <?php
                                                $carpeta = $attrs['archivo_carpeta_destino'];
                                                if (empty($carpeta)) {
                                                    $carpeta = 'uploads';
                                                    if (!empty($attrs['archivo_subcarpeta_por_entidad'])) {
                                                        $carpeta .= '/' . $data['entity']['nombre_tabla'];
                                                    }
                                                }
                                                $rutaCompleta = $carpeta . '/' . $value;
                                            ?>
                                            <img src="<?= base_url($rutaCompleta) ?>"
                                                    alt="Vista previa" style="max-height: 100px; cursor: pointer;" class="img-thumbnail"
                                                    onclick="mostrarModalImagen('<?= base_url($rutaCompleta) ?>')">
                                        </div>
                                        <?php if (!$required): ?>
                                            <div class="form-check mb-2">
                                                <input type="checkbox" class="form-check-input" name="remove_<?= $field ?>" id="remove_<?= $field ?>" value="1">
                                                <label class="form-check-label" for="remove_<?= $field ?>">Quitar imagen actual</label>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if (empty($value) || !$required): ?>
                                        <input type="file" class="form-control <?= $errorClass ?>" 
                                            name="<?= $field ?>" accept="image/*">
                                    <?php endif; ?>
                                </div>        
                            <?php endif; ?>

                            <?php if (isset($data['errors'][$field])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= esc($data['errors'][$field]) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Botones -->
        <div class="row">
            <div class="col-12">
                <button type="submit" name="form_action" value="save" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> 
                    <?= $data['action'] === 'create' ? 'Guardar' : 'Actualizar' ?>
                </button>
                
                <button type="submit" name="form_action" value="save_and_back" class="btn btn-outline-primary">
                    <i class="bi bi-save"></i> 
                    <?= $data['action'] === 'create' ? 'Guardar y volver' : 'Actualizar y volver' ?>
                </button>
                
                <a href="<?= $data['baseUrl'] ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Cancelar
                </a>
            </div>
        </div>
    </form>
</div>

<?= $this->endSection() ?>