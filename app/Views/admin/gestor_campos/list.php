<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Gestor de Campos</h2>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Tablas disponibles</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre de la tabla</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tables)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted">
                                No hay tablas en la base de datos
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tables as $table): ?>
                            <tr>
                                <td>
                                    <code><?= esc($table) ?></code>
                                </td>
                                <td>
                                    <?php if (in_array($table, $configuradas)): ?>
                                        <span class="badge bg-success">Configurada</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Sin configurar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= base_url("admin/gestor-campos/configurar/{$table}") ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="bi bi-gear"></i> Configurar
                                    </a>
                                </td>
                                <td>
                                    <a href="<?= base_url("admin/gestor-campos/clearcache/{$table}") ?>" 
                                    class="btn btn-sm btn-warning">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </a>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-success generar-controlador" 
                                            data-tabla="<?= $table ?>"
                                            data-titulo="<?= $entity['titulo'] ?? $table ?>">
                                        <i class="bi bi-file-code"></i> Generar Controlador
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

<div class="modal fade" id="modalGenerarControlador" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generar Controlador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="tablaSeleccionada">
                
                <div class="mb-3">
                    <label class="form-label">Nombre del controlador:</label>
                    <input type="text" class="form-control" id="nombreControlador" 
                           placeholder="Ej: Tor_Productos">
                    <small class="text-muted">Se creará en app/Controllers/</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Opciones:</label>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="incluirAcciones" checked>
                        <label class="form-check-label">Incluir acciones globales de ejemplo</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="incluirCallbacks" checked>
                        <label class="form-check-label">Incluir callbacks de ejemplo (comentados)</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnDescargarControlador">
                    <i class="bi bi-download"></i> Descargar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.generar-controlador').forEach(btn => {
        btn.addEventListener('click', function() {
            const tabla = this.dataset.tabla;
            const titulo = this.dataset.titulo;
            
            // Sugerir nombre de controlador basado en la tabla
            const nombreSugerido = 'Tor_' + tabla.replace('mc_', '').replace(/_/g, ' ');
            document.getElementById('nombreControlador').value = toPascalCase(nombreSugerido);
            document.getElementById('tablaSeleccionada').value = tabla;
            
            new bootstrap.Modal(document.getElementById('modalGenerarControlador')).show();
        });
    });

    function toPascalCase(str) {
        return str.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()).join('');
    }

    document.getElementById('btnDescargarControlador').addEventListener('click', function() {
        const tabla = document.getElementById('tablaSeleccionada').value;
        const nombre = document.getElementById('nombreControlador').value;
        const incluirAcciones = document.getElementById('incluirAcciones').checked ? 1 : 0;
        const incluirCallbacks = document.getElementById('incluirCallbacks').checked ? 1 : 0;
        
        if (!nombre.trim()) {
            alert('Debes ingresar un nombre para el controlador');
            return;
        }
        
        // ===========================================
        // CERRAR EL MODAL
        // ===========================================
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalGenerarControlador'));
        modal.hide();
        
        // ===========================================
        // MOSTRAR NOTIFICACIÓN DE DESCARGA
        // ===========================================
        mostrarNotificacion('Preparando descarga...y descargando', 'info');
        
        // Crear formulario para enviar por POST
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href + '/generar-controlador';
        
        const csrf = document.querySelector('input[name="csrf_test_name"]');
        if (csrf) {
            const inputCsrf = document.createElement('input');
            inputCsrf.type = 'hidden';
            inputCsrf.name = csrf.name;
            inputCsrf.value = csrf.value;
            form.appendChild(inputCsrf);
        }
        
        const inputTabla = document.createElement('input');
        inputTabla.type = 'hidden';
        inputTabla.name = 'tabla';
        inputTabla.value = tabla;
        form.appendChild(inputTabla);
        
        const inputNombre = document.createElement('input');
        inputNombre.type = 'hidden';
        inputNombre.name = 'nombre';
        inputNombre.value = nombre;
        form.appendChild(inputNombre);
        
        const inputAcciones = document.createElement('input');
        inputAcciones.type = 'hidden';
        inputAcciones.name = 'incluir_acciones';
        inputAcciones.value = incluirAcciones;
        form.appendChild(inputAcciones);
        
        const inputCallbacks = document.createElement('input');
        inputCallbacks.type = 'hidden';
        inputCallbacks.name = 'incluir_callbacks';
        inputCallbacks.value = incluirCallbacks;
        form.appendChild(inputCallbacks);
        
        document.body.appendChild(form);
        form.submit();
    });


    function mostrarNotificacion(mensaje, tipo = 'success') {
        // Crear elemento de notificación si no existe
        let notificacion = document.getElementById('notificacion-tor');
        if (!notificacion) {
            notificacion = document.createElement('div');
            notificacion.id = 'notificacion-tor';
            notificacion.style.position = 'fixed';
            notificacion.style.top = '20px';
            notificacion.style.right = '20px';
            notificacion.style.zIndex = '9999';
            notificacion.style.padding = '12px 24px';
            notificacion.style.borderRadius = '4px';
            notificacion.style.color = '#fff';
            notificacion.style.fontWeight = 'bold';
            notificacion.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
            notificacion.style.transition = 'opacity 0.3s';
            document.body.appendChild(notificacion);
        }
        
        // Color según tipo
        if (tipo === 'success') notificacion.style.backgroundColor = '#28a745';
        else if (tipo === 'error') notificacion.style.backgroundColor = '#dc3545';
        else if (tipo === 'info') notificacion.style.backgroundColor = '#17a2b8';
        else notificacion.style.backgroundColor = '#6c757d';
        
        notificacion.textContent = mensaje;
        notificacion.style.opacity = '1';
        
        // Ocultar después de 3 segundos
        setTimeout(() => {
            notificacion.style.opacity = '0';
        }, 3000);
    }

</script>
<?= $this->endSection() ?>