<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid py-4">
    <!-- Título y acciones -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0">
            <i class="bi bi-eye me-2"></i>
            Detalle de <?= esc($data['entity']['titulo']) ?>
        </h2>
        <!-- Botones de acción en vista detalle -->
        <div>
            <a href="<?= $data['baseUrl'] ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            
            <?php if (!$data['disableEdit'] && ($data['entity']['permite_editar'] ?? true)): ?>
                <a href="<?= $data['baseUrl'] ?>/mcedit/<?= $data['id'] ?>" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Editar
                </a>
            <?php endif; ?>
            
            <?php if (!$data['disableClone'] ): ?>
                <a href="<?= $data['baseUrl'] ?>/mcclone/<?= $data['id'] ?>" class="btn btn-info">
                    <i class="bi bi-copy"></i> Clonar
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Campos en dos columnas -->
    <div class="card shadow-sm border-0">  <!-- AÑADIDO: border-0 para más estilo -->
        <div class="card-body bg-light">   <!-- AÑADIDO: bg-light para fondo suave -->
            <div class="row g-4">
                <?php 
                $count = 0;
                foreach ($data['fields'] as $field => $attrs): 
                    $valor = $data['record'][$field . '_texto'] ?? $data['record'][$field] ?? '';
                    $tags = $data['record'][$field . '_tags'] ?? [];
                ?>
                    <?php if ($count % 2 == 0): ?>
                        <div class="col-md-6">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <strong class="text-muted small text-uppercase"><?= esc($attrs['label']) ?></strong>
                        <div class="mt-1 p-2 bg-white rounded border">  <!-- AÑADIDO: p-2 bg-white rounded border para destacar valores -->
                            <?php if ($attrs['type'] === 'enum' || $attrs['type'] === 'boolean'): ?>
                                <span class="badge bg-<?= $data['record'][$field . '_clase'] ?? 'secondary' ?>">
                                    <?= $valor ?>
                                </span>
                                
                            <?php elseif ($attrs['type'] === 'file'): ?>
                                <?php if (!empty($valor)): 
                                    $icono = 'bi-file-earmark';
                                    $ext = $data['record'][$field . '_extension'] ?? '';
                                    if ($ext === 'pdf') $icono = 'bi-file-pdf';
                                    elseif (in_array($ext, ['doc', 'docx'])) $icono = 'bi-file-word';
                                    elseif (in_array($ext, ['xls', 'xlsx', 'csv'])) $icono = 'bi-file-excel';
                                    elseif (in_array($ext, ['zip', 'rar', '7z'])) $icono = 'bi-file-zip';
                                    elseif (in_array($ext, ['txt', 'log'])) $icono = 'bi-file-text';
                                ?>
                                    <a href="<?= $data['record'][$field . '_url'] ?>" target="_blank">
                                        <i class="bi <?= $icono ?> me-1"></i> <?= $valor ?>
                                    </a>
                                <?php endif; ?>
                                
                            <?php elseif ($attrs['type'] === 'image'): ?>
                                <?php if (!empty($data['record'][$field . '_url'])): ?>
                                    <img src="<?= $data['record'][$field . '_url'] ?>" 
                                         alt="Imagen" 
                                         style="max-height: 100px; max-width: 100px; cursor: pointer;"
                                         class="img-thumbnail"
                                         onclick="mostrarModalImagenView('<?= $data['record'][$field . '_url'] ?>')">
                                <?php endif; ?>
                                
                            <?php elseif ($attrs['type'] === 'wysiwyg'): ?>
                                <div class="p-2 bg-white rounded">  <!-- AÑADIDO: bg-white en lugar de bg-light -->
                                    <?= $valor ?>
                                </div>
                                
                            <?php elseif ($attrs['type'] === 'virtual_n_a_n'): ?>
                                <?php if (!empty($tags)): ?>
                                    <?php foreach ($tags as $tag): ?>
                                        <span class="badge bg-secondary me-1"><?= esc($tag) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                                
                            <?php else: ?>
                                <?= nl2br(esc($valor)) ?: '<span class="text-muted">-</span>' ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($count % 2 == 1 || $count == count($data['fields']) - 1): ?>
                        </div>
                    <?php endif; ?>
                    
                <?php $count++; endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para imágenes (propio de esta vista) -->
<div class="modal fade" id="modalImagenView" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-0">
                <img src="" id="imagenModalView" style="max-width: 100%; max-height: 80vh;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function mostrarModalImagenView(url) {
    document.getElementById('imagenModalView').src = url;
    new bootstrap.Modal(document.getElementById('modalImagenView')).show();
}
</script>

<?= $this->endSection() ?>