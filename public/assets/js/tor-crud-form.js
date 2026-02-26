    // Variables globales
    let deleteUrl = '';
    let deleteType = 'single'; // 'single' o 'bulk'

    // ===========================================
    // ELIMINACIÓN INDIVIDUAL
    // ===========================================
    document.querySelectorAll('.delete-single').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            deleteUrl = this.href;
            deleteType = 'single';
            document.getElementById('deleteMessage').innerText = '¿Está seguro de eliminar este registro?';
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        });
    });

// ===========================================
// ELIMINACIÓN MASIVA
// ===========================================
const btnBorrarMultiple = document.getElementById('btnBorrarMultiple');
if (btnBorrarMultiple) {
    btnBorrarMultiple.addEventListener('click', function(e) {
        e.preventDefault();
        
        const selected = document.querySelectorAll('.check-item:checked');
        if (selected.length === 0) return;
        
        deleteType = 'bulk';
        document.getElementById('deleteMessage').innerText = 
            `¿Está seguro de eliminar ${selected.length} registro(s)?`;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
        
        window.selectedIds = Array.from(selected).map(cb => cb.value);
    });
}

    // ===========================================
    // CONFIRMAR ELIMINACIÓN
    // ===========================================
    const btnconfirmDelete = document.getElementById('confirmDelete');
    if (btnconfirmDelete) {
        btnconfirmDelete.addEventListener('click', function() {
            
            // Cerrar el modal
            let modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            if (modal) modal.hide();
            
            // ===========================================
            // BORRADO INDIVIDUAL
            // ===========================================
            if (deleteType === 'single') {
                if (deleteUrl) {
                    window.location.href = deleteUrl;
                    deleteUrl = '';
                }
                return;
            }
            
            // ===========================================
            // BORRADO MASIVO
            // ===========================================
            if (deleteType === 'bulk') {
                if (!window.selectedIds || window.selectedIds.length === 0) return;
                
                const baseUrl = window.location.protocol + '//' + window.location.host + window.location.pathname;
                const cleanBaseUrl = baseUrl.replace(/\/index$/, '');
                
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = cleanBaseUrl + '/tcbulkdelete';
                
                window.selectedIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    form.appendChild(input);
                });
                
                document.body.appendChild(form);
                form.submit();
                
                window.selectedIds = [];
            }
            
            // Resetear tipo después de la operación
            deleteType = 'single';
        });
    }
    //=====
    let filterTimers = {};

    document.querySelectorAll('input[data-filter-timer]').forEach(input => {
        input.addEventListener('input', function() {
            const field = this.name.replace('filter_', '');
            
            if (filterTimers[field]) {
                clearTimeout(filterTimers[field]);
            }
            
            filterTimers[field] = setTimeout(() => {
                document.getElementById('filters-form').submit();
            }, 2000);
        });
    });

    // Mostrar/ocultar botón de borrado múltiple
    document.querySelectorAll('.check-item').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const seleccionados = document.querySelectorAll('.check-item:checked').length;
            document.getElementById('btnBorrarMultiple').style.display = 
                seleccionados > 0 ? 'inline-block' : 'none';
        });
    });
    
    function mostrarTodasColumnas() {
        document.querySelectorAll('.d-none').forEach(el => {
            el.classList.remove('d-none');
        });
        document.querySelector('.position-fixed').style.display = 'none';
    }


document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Tom Select en selects simples con búsqueda
    document.querySelectorAll('select.select-search:not([multiple])').forEach(select => {
        new TomSelect(select, {
            plugins: ['dropdown_input'],
            create: false,
            sortField: 'text',
            maxOptions: null,
            placeholder: 'Seleccione...'
        });
    });
    
    // Inicializar Tom Select en selects múltiples
    document.querySelectorAll('select.select-search-multiple').forEach(select => {
        new TomSelect(select, {
            plugins: ['remove_button', 'dropdown_input'],
            create: false,
            sortField: 'text',
            maxOptions: null,

        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Quill en todos los campos wysiwyg
    document.querySelectorAll('div[id^="editor-"]').forEach(editorDiv => {
        const field = editorDiv.id.replace('editor-', '');
        const hiddenTextarea = document.getElementById('hidden-' + field);
        
        const quill = new Quill(editorDiv, {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'header': 1 }, { 'header': 2 }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    [{ 'direction': 'rtl' }],
                    [{ 'size': ['small', false, 'large', 'huge'] }],
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'font': [] }],
                    [{ 'align': [] }],
                    ['clean'],
                    ['link', 'image', 'video']
                ]
            }
        });
        
        // Cargar contenido existente
        quill.root.innerHTML = hiddenTextarea.value;
        
        // Actualizar textarea antes de enviar
        editorDiv.closest('form').addEventListener('submit', function() {
            hiddenTextarea.value = quill.root.innerHTML;
        });
    });
});

function mostrarModalImagen(url) {
    // Crear modal si no existe
    if (!document.getElementById('modalImagen')) {
        const modalHTML = `
            <div class="modal fade" id="modalImagen" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body text-center p-0">
                            <img src="" id="imagenModal" style="max-width: 100%; max-height: 80vh;">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
    
    document.getElementById('imagenModal').src = url;
    new bootstrap.Modal(document.getElementById('modalImagen')).show();
}


/**
 * Meta-CRUD - Edición en línea
 * Soporta doble clic (desktop) y long press (móvil)
 */

(function() {
    'use strict';

    // Variables globales
    let longPressTimer;
    const LONG_PRESS_DURATION = 500; // 500ms

    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        initInlineEditing();
    });

    /**
     * Inicializa la edición en línea en todas las celdas editables
     */
    function initInlineEditing() {
        document.querySelectorAll('.editable').forEach(cell => {
            // Desktop: doble clic
            cell.addEventListener('dblclick', handleDoubleClick);
            
            // Móvil: long press
            cell.addEventListener('touchstart', handleTouchStart);
            cell.addEventListener('touchend', handleTouchEnd);
            cell.addEventListener('touchmove', handleTouchMove);
        });
    }

    /**
     * Maneja el doble clic (desktop)
     */
    function handleDoubleClick(e) {
        e.preventDefault();
        startEditing(this);
    }

    /**
     * Maneja el inicio del toque (móvil)
     */
    function handleTouchStart(e) {
        const cell = this;
        longPressTimer = setTimeout(() => {
            e.preventDefault();
            startEditing(cell);
        }, LONG_PRESS_DURATION);
    }

    /**
     * Maneja el fin del toque (móvil)
     */
    function handleTouchEnd() {
        clearTimeout(longPressTimer);
    }

    /**
     * Maneja el movimiento durante el toque (cancela el long press)
     */
    function handleTouchMove() {
        clearTimeout(longPressTimer);
    }


    /**
     * Crea un editor de tipo select con las opciones proporcionadas
     */
    function createSelectEditor(options, currentValue) {
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm';
        
        // Opción por defecto
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Seleccione...';
        select.appendChild(defaultOption);
        
        // Opciones del select
        options.forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.value;
            option.textContent = opt.text;
            if (opt.value == currentValue) {
                option.selected = true;
            }
            select.appendChild(option);
        });
        
        return select;
    }

    /**
     * Crea un editor simple (input) según el tipo de campo
     */
    function createSimpleEditor(type, currentValue) {
        let editor;
        
        switch (type) {
            case 'textarea':
                editor = document.createElement('textarea');
                editor.className = 'form-control form-control-sm';
                editor.value = currentValue;
                editor.rows = 3;
                break;
                
            case 'number':
                editor = document.createElement('input');
                editor.type = 'number';
                editor.className = 'form-control form-control-sm';
                editor.value = currentValue;
                editor.step = 'any';
                break;
                
            case 'date':
                editor = document.createElement('input');
                editor.type = 'date';
                editor.className = 'form-control form-control-sm';
                editor.value = currentValue;
                break;
                
            case 'datetime':
                editor = document.createElement('input');
                editor.type = 'datetime-local';
                editor.className = 'form-control form-control-sm';
                editor.value = currentValue.replace(' ', 'T');
                break;
                
            default: // text, email, etc.
                editor = document.createElement('input');
                editor.type = type === 'email' ? 'email' : 'text';
                editor.className = 'form-control form-control-sm';
                editor.value = currentValue;
                break;
        }
        
        return editor;
    }

    /**
     * Reemplaza el contenido de la celda con el editor
     */
    function replaceCellWithEditor(cell, editor) {
        // Guardar referencia para poder cancelar
        cell._originalHTML = cell.innerHTML;
        
        // Limpiar celda y añadir editor
        cell.innerHTML = '';
        cell.appendChild(editor);
        editor.focus();
        
        // Eventos del editor
        editor.addEventListener('blur', function() {
            saveEdit(cell, editor);
        });
        
        editor.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                editor.blur();
            }
            if (e.key === 'Escape') {
                cancelEdit(cell);
            }
        });
    }

    /**
     * Inicia la edición de una celda
     */
    function startEditing(cell) {
        const field = cell.dataset.field;
        const type = cell.dataset.type;
        const id = cell.dataset.id;
        const currentValue = cell.dataset.value;
        
        // Obtener configuración del campo
        const fieldConfig = window.inlineConfig[field];
        
        if (!fieldConfig) {
            console.error('No hay configuración para el campo', field);
            return;
        }
        
        let editor;
        
        if (fieldConfig.type === 'select' || fieldConfig.type === 'enum' || fieldConfig.type === 'boolean') {
            editor = createSelectEditor(fieldConfig.options, currentValue);
        } else {
            editor = createSimpleEditor(fieldConfig.type, currentValue);
        }
        
        replaceCellWithEditor(cell, editor);
    }

    /**
     * Cancela la edición y restaura el contenido original
     */
    function cancelEdit(cell) {
        if (cell._originalHTML) {
            cell.innerHTML = cell._originalHTML;
            delete cell._originalHTML;
        }
    }

    /**
     * Crea el editor apropiado según el tipo de campo
     */
    function createEditor(type, currentValue, optionsJson) {
        let editor;

        switch (type) {
            case 'select':
            case 'enum':
                editor = document.createElement('select');
                editor.className = 'form-select form-select-sm';
                
                try {
                    const options = JSON.parse(optionsJson || '[]');
                    options.forEach(opt => {
                        const option = document.createElement('option');
                        option.value = opt.value;
                        option.textContent = opt.text;
                        if (opt.value == currentValue) {
                            option.selected = true;
                        }
                        editor.appendChild(option);
                    });
                } catch (e) {
                    console.error('Error parsing options:', e);
                    return null;
                }
                break;

            case 'textarea':
                editor = document.createElement('textarea');
                editor.className = 'form-control form-control-sm';
                editor.value = currentValue;
                editor.rows = 3;
                break;

            case 'number':
                editor = document.createElement('input');
                editor.type = 'number';
                editor.className = 'form-control form-control-sm';
                editor.value = currentValue;
                editor.step = 'any';
                break;

            case 'date':
                editor = document.createElement('input');
                editor.type = 'date';
                editor.className = 'form-control form-control-sm';
                editor.value = currentValue;
                break;

            case 'datetime':
                editor = document.createElement('input');
                editor.type = 'datetime-local';
                editor.className = 'form-control form-control-sm';
                editor.value = currentValue.replace(' ', 'T');
                break;

            case 'boolean':
                editor = document.createElement('select');
                editor.className = 'form-select form-select-sm';
                
                const si = document.createElement('option');
                si.value = '1';
                si.textContent = 'Sí';
                if (currentValue == '1') si.selected = true;
                
                const no = document.createElement('option');
                no.value = '0';
                no.textContent = 'No';
                if (currentValue == '0') no.selected = true;
                
                editor.appendChild(si);
                editor.appendChild(no);
                break;

            default: // text, email, etc.
                editor = document.createElement('input');
                editor.type = type === 'email' ? 'email' : 'text';
                editor.className = 'form-control form-control-sm';
                editor.value = currentValue;
                break;
        }

        return editor;
    }

    /**
     * Guarda la edición vía AJAX
     */
    function saveEdit(cell, editor) {
        const field = cell.dataset.field;
        const id = cell.dataset.id;
        const newValue = editor.value;
        const originalHTML = cell._originalHTML;

        // Mostrar indicador de carga
        cell.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        cell.classList.add('editing');

        // Obtener URL base
        const baseUrl = window.location.protocol + '//' + window.location.host + window.location.pathname;
        const cleanBaseUrl = baseUrl.replace(/\/index$/, '');

        // Enviar petición
        fetch(`${cleanBaseUrl}/tcinlineedit/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                field: field,
                value: newValue
            })
        })
        .then(response => response.json())
        .then(data => {
            cell.classList.remove('editing');
            
            if (data.success) {
                if (data.new_value_class) {
                    // Es un badge (enum, boolean)
                    cell.innerHTML = `<span class="badge bg-${data.new_value_class}">${data.new_value_display}</span>`;
                } else {
                    // Texto normal
                    cell.innerHTML = data.new_value_display || data.new_value;
                }
                cell.dataset.value = data.new_value;
                delete cell._originalHTML;
                showNotification('success', 'Actualizado correctamente');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            cell.classList.remove('editing');
            showNotification('error', 'Error de conexión');
            cancelEdit(cell);
        });
    }

    /**
     * Cancela la edición y restaura el contenido original
     */
    function cancelEdit(cell, originalHTML) {
        cell.innerHTML = originalHTML;
    }

    /**
     * Muestra una notificación temporal
     */
    function showNotification(type, message) {
        // Crear elemento de notificación si no existe
        let notification = document.getElementById('inline-notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'inline-notification';
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '9999';
            notification.style.padding = '10px 20px';
            notification.style.borderRadius = '4px';
            notification.style.color = '#fff';
            notification.style.fontWeight = 'bold';
            notification.style.transition = 'opacity 0.3s';
            document.body.appendChild(notification);
        }

        // Configurar según tipo
        if (type === 'success') {
            notification.style.backgroundColor = '#28a745';
        } else {
            notification.style.backgroundColor = '#dc3545';
        }

        notification.textContent = message;
        notification.style.opacity = '1';

        // Ocultar después de 3 segundos
        setTimeout(() => {
            notification.style.opacity = '0';
        }, 3000);
    }

})();