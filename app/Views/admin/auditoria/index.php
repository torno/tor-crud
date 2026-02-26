<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid py-4">
    <!-- Título -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0">
            <i class="bi bi-journal-text me-2"></i>
            Auditoría de Cambios
        </h2>
    </div>

    <!-- Filtros manuales (estilo gestor de campos) -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tabla</label>
                    <select name="tabla" class="form-select">
                        <option value="">Todas las tablas</option>
                        <?php foreach ($tablas as $t): ?>
                            <option value="<?= $t['table_name'] ?>" 
                                <?= ($t['table_name'] == ($_GET['tabla'] ?? '')) ? 'selected' : '' ?>>
                                <?= $t['table_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Operación</label>
                    <select name="operacion" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach ($operaciones as $op): ?>
                            <option value="<?= $op ?>" 
                                <?= ($op == ($_GET['operacion'] ?? '')) ? 'selected' : '' ?>>
                                <?= $op ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Usuario</label>
                    <input type="text" name="usuario" class="form-control" 
                           value="<?= $_GET['usuario'] ?? '' ?>" placeholder="ID de usuario">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fecha desde</label>
                    <input type="date" name="fecha_desde" class="form-control" 
                           value="<?= $_GET['fecha_desde'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fecha hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" 
                           value="<?= $_GET['fecha_hasta'] ?? '' ?>">
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                    <a href="<?= site_url('admin/auditoria') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-eraser"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de resultados -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Tabla</th>
                            <th>Registro</th>
                            <th>Usuario</th>
                            <th>Operación</th>
                            <th>Fecha</th>
                            <th>IP</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    No hay registros de auditoría
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= $log['id'] ?></td>
                                    <td><code><?= $log['table_name'] ?></code></td>
                                    <td><?= $log['record_id'] ?></td>
                                    <td><?= $log['user_id'] ?? 'Sistema' ?></td>
                                    <td>
                                        <?php
                                        $badgeClass = match($log['operation']) {
                                            'INSERT' => 'success',
                                            'UPDATE' => 'warning',
                                            'DELETE' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>">
                                            <?= $log['operation'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                                    <td><small><?= $log['ip_address'] ?? '-' ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info ver-detalle" 
                                                data-id="<?= $log['id'] ?>">
                                            <i class="bi bi-eye"></i> Ver JSON
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Paginación -->
    <div class="d-flex justify-content-end mt-3">
        <?= $pager->links('default', 'bootstrap_pagination') ?>
    </div>
</div>

<!-- Modal para ver JSON -->
<div class="modal fade" id="modalJson" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle del cambio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="jsonContent" style="max-height: 500px; overflow: auto;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.ver-detalle').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        
        fetch('<?= site_url('admin/auditoria/detalle') ?>/' + id)
            .then(response => response.json())
            .then(data => {
                const formatted = JSON.stringify(data, null, 2);
                document.getElementById('jsonContent').textContent = formatted;
                new bootstrap.Modal(document.getElementById('modalJson')).show();
            });
    });
});
</script>

<?= $this->endSection() ?>