@extends('system.layouts.app')

@section('content')
<header class="page-header">
    <h2><i class="fas fa-tools mr-2"></i>Modo de Mantenimiento</h2>
    <div class="right-wrapper text-right">
        <ol class="breadcrumbs">
            <li>
                <a href="/dashboard">
                    <i class="fas fa-home"></i>
                </a>
            </li>
            <li><span>Configuración</span></li>
            <li><span>Modo de Mantenimiento</span></li>
        </ol>
    </div>
</header>

<div class="row">
    <div class="col">
        <section class="card">
            <header class="card-header">
                <div class="card-actions">
                    <a href="#" class="card-action card-action-toggle" data-card-toggle></a>
                    <a href="#" class="card-action card-action-dismiss" data-card-dismiss></a>
                </div>
                <h2 class="card-title">
                    <i class="fas fa-cog mr-2"></i>
                    Control de Mantenimiento del Sistema
                </h2>
                <p class="card-subtitle">
                    Gestiona el acceso de las empresas durante tareas de mantenimiento programado
                </p>
            </header>
    
            <div class="card-body">
                <!-- Estado actual del mantenimiento -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div id="maintenance-status-card" class="alert alert-info">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle mr-3" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <h5 class="mb-1">Estado del Sistema</h5>
                                        <span id="maintenance-status-text" class="mb-0">Cargando estado...</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="refresh-status">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulario de control -->
                <form id="maintenance-form">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="control-label text-primary font-weight-semibold">
                                    <i class="fas fa-power-off mr-2"></i>
                                    Estado del Mantenimiento
                                </label>
                                <div class="mt-2">
                                    <label class="switch switch-success">
                                        <input type="checkbox" id="maintenance-mode">
                                        <span class="slider"></span>
                                    </label>
                                    <span class="ml-3" id="maintenance-switch-label">Activar Modo Mantenimiento</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label text-primary font-weight-semibold">
                                    <i class="fas fa-building mr-2"></i>
                                    Empresas Permitidas
                                </label>
                                <div class="mt-2">
                                    <span id="allowed-companies-count" class="badge badge-primary badge-lg">0</span>
                                    <small class="text-muted ml-2">empresas activas</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="maintenance-message" class="control-label text-primary font-weight-semibold">
                                    <i class="fas fa-comment-alt mr-2"></i>
                                    Mensaje de Mantenimiento
                                </label>
                                <textarea 
                                    class="form-control" 
                                    id="maintenance-message" 
                                    rows="4" 
                                    maxlength="1000"
                                    placeholder="Mensaje que verán las empresas bloqueadas durante el mantenimiento">estamos realizando un manteniemiento gracias por su panciencia pronto estaremos en linea</textarea>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Máximo 1000 caracteres. Este mensaje se mostrará a las empresas que no tengan acceso durante el mantenimiento.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="allowed-companies" class="control-label text-primary font-weight-semibold">
                                    <i class="fas fa-users mr-2"></i>
                                    Empresas Permitidas Durante Mantenimiento
                                </label>
                                <div class="alert alert-warning alert-sm mb-2">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    <strong>Importante:</strong> Si no seleccionas ninguna empresa, TODAS quedarán bloqueadas excepto el panel de administración.
                                </div>
                                <select 
                                    class="form-control select2" 
                                    id="allowed-companies" 
                                    multiple="multiple" 
                                    data-placeholder="Seleccionar empresas que podrán acceder durante mantenimiento...">
                                </select>
                                <small class="form-text text-muted">
                                    <i class="fas fa-search mr-1"></i>
                                    Puedes buscar empresas por nombre o número. Las empresas seleccionadas mantendrán acceso normal al sistema.
                                </small>
                            </div>
                        </div>
                    </div>

                    <hr class="solid mt-4 mb-4">
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div class="mb-2 mb-md-0">
                                    <button type="button" class="btn btn-primary btn-lg" id="save-maintenance">
                                        <i class="fas fa-save mr-2"></i>
                                        Guardar Configuración
                                    </button>
                                </div>
                                
                                <div id="maintenance-info" class="text-muted">
                                    <div id="started-info" style="display: none;" class="text-right">
                                        <small>
                                            <i class="fas fa-clock mr-1 text-primary"></i>
                                            <strong>Iniciado:</strong> <span id="started-at"></span><br>
                                            <i class="fas fa-user mr-1 text-primary"></i>
                                            <strong>Por:</strong> <span id="started-by"></span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="confirmation-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
                    Confirmar Cambio de Estado
                </h5>
            </div>
            <div class="modal-body">
                <p id="confirmation-message"></p>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle mr-2"></i>
                    Esta acción afectará el acceso de las empresas al sistema inmediatamente.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirm-action">Confirmar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Switch personalizado mejorado */
.switch {
  position: relative;
  display: inline-block;
  width: 70px;
  height: 34px;
}

.switch input { 
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #dc3545;
  -webkit-transition: .4s;
  transition: .4s;
  box-shadow: inset 0 1px 3px rgba(0,0,0,0.3);
  border-radius: 4px;
}

.slider:before {
  position: absolute;
  content: "";
  height: 26px;
  width: 30px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  -webkit-transition: .4s;
  transition: .4s;
  box-shadow: 0 2px 5px rgba(0,0,0,0.2);
  border-radius: 2px;
}

input:checked + .slider {
  background-color: #28a745;
}

input:focus + .slider {
  box-shadow: 0 0 5px #28a745;
}

input:checked + .slider:before {
  -webkit-transform: translateX(32px);
  -ms-transform: translateX(32px);
  transform: translateX(32px);
}

/* Estado visual del switch */
.slider:after {
  content: 'OFF';
  color: white;
  display: block;
  position: absolute;
  transform: translate(-50%,-50%);
  top: 50%;
  left: 70%;
  font-size: 10px;
  font-weight: bold;
}

input:checked + .slider:after {
  content: 'ON';
  left: 30%;
}

/* Card mejorado */
.card {
  border: none;
  box-shadow: 0 0 20px rgba(0,0,0,0.08);
  border-radius: 10px;
}

.card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 10px 10px 0 0 !important;
  padding: 1.5rem;
}

.card-title {
  color: #2c3e50 !important;
  font-size: 1.3rem;
  margin-bottom: 0.5rem;
}

.card-subtitle {
  color: #495057 !important;
  font-size: 0.9rem;
}

/* Status card mejorado */
#maintenance-status-card {
  border-radius: 10px;
  border-left: 4px solid;
  padding: 1rem 1.5rem;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

#maintenance-status-card.alert-success {
  border-left-color: #28a745;
  background: linear-gradient(to right, rgba(40,167,69,0.1), rgba(40,167,69,0.05));
}

#maintenance-status-card.alert-warning {
  border-left-color: #ffc107;
  background: linear-gradient(to right, rgba(255,193,7,0.1), rgba(255,193,7,0.05));
}

#maintenance-status-card.alert-info {
  border-left-color: #17a2b8;
  background: linear-gradient(to right, rgba(23,162,184,0.1), rgba(23,162,184,0.05));
}

/* Badge mejorado */
.badge-lg {
  font-size: 1.2rem;
  padding: 0.6rem 1rem;
  border-radius: 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 2px 10px rgba(102,126,234,0.3);
}

/* Alert pequeño mejorado */
.alert-sm {
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  border-radius: 8px;
  border-left: 3px solid #ffc107;
}

/* Labels mejorados */
.control-label {
  font-weight: 600;
  font-size: 0.95rem;
  color: #495057;
  margin-bottom: 0.5rem;
  display: block;
}

.control-label i {
  color: #667eea;
}

/* Textarea mejorada */
textarea.form-control {
  border-radius: 8px;
  border: 1px solid #dee2e6;
  transition: all 0.3s;
}

textarea.form-control:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25);
}

/* Select2 personalizado */
.select2-container--default .select2-selection--multiple {
  border-radius: 8px;
  border: 1px solid #dee2e6;
  min-height: 45px;
  padding: 0.375rem;
}

.select2-container--default.select2-container--focus .select2-selection--multiple {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25);
}

.select2-container--default .select2-selection--multiple .select2-selection__choice {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  color: white;
  border-radius: 5px;
  padding: 5px 10px;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
  color: white;
  margin-right: 5px;
}

/* Botones mejorados */
.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 25px;
  padding: 0.75rem 2rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 0 4px 15px rgba(102,126,234,0.3);
  transition: all 0.3s;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102,126,234,0.4);
}

.btn-secondary {
  border-radius: 25px;
  padding: 0.5rem 1.5rem;
}

/* Animación de carga */
@keyframes pulse {
  0% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.05);
    opacity: 0.8;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

.fas.fa-spinner.fa-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Info box mejorado */
#started-info {
  background: rgba(102,126,234,0.1);
  padding: 0.75rem;
  border-radius: 8px;
  border-left: 3px solid #667eea;
}

/* Mejoras responsive */
@media (max-width: 768px) {
  .card-header {
    padding: 1rem;
  }
  
  .btn-primary {
    padding: 0.5rem 1.5rem;
    font-size: 0.9rem;
  }
  
  .badge-lg {
    font-size: 1rem;
  }
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Inicializar Select2 con un pequeño delay para asegurar que todo esté cargado
    setTimeout(function() {
        // Cargar todas las empresas disponibles primero
        $.ajax({
            url: '/maintenance/companies',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    // Limpiar select actual
                    $('#allowed-companies').empty();
                    
                    // Agregar todas las opciones
                    response.data.forEach(function(company) {
                        var option = new Option(
                            company.name + ' - ' + company.identification_number + ' (' + company.subdomain + ')',
                            company.id,
                            false,
                            false
                        );
                        $('#allowed-companies').append(option);
                    });
                    
                    // Inicializar Select2 después de cargar las opciones
                    if (typeof $.fn.select2 !== 'undefined') {
                        $('#allowed-companies').select2({
                            placeholder: "Seleccionar empresas que mantendrán acceso...",
                            allowClear: true,
                            width: '100%',
                            multiple: true,
                            closeOnSelect: false
                        });
                    } else {
                        console.log('Select2 no disponible, usando select múltiple nativo');
                    }
                    
                    // Cargar estado después de inicializar select
                    loadMaintenanceStatus();
                }
            },
            error: function() {
                console.error('Error cargando empresas');
                // Cargar estado de todos modos
                loadMaintenanceStatus();
            }
        });
    }, 500); // Delay de 500ms para asegurar que todo esté cargado

    // Event listeners
    $('#refresh-status').click(loadMaintenanceStatus);
    $('#save-maintenance').click(showConfirmation);
    $('#confirm-action').click(saveMaintenanceConfig);
    
    // Actualizar contador al cambiar selección
    $('#allowed-companies').on('change', function() {
        const count = $(this).val() ? $(this).val().length : 0;
        $('#allowed-companies-count').text(count);
    });

    function loadMaintenanceStatus() {
        $.ajax({
            url: '/maintenance/status',
            method: 'GET',
            dataType: 'json'
        })
            .done(function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // Actualizar switch
                    $('#maintenance-mode').prop('checked', data.maintenance_mode);
                    updateSwitchLabel(data.maintenance_mode);
                    
                    // Actualizar mensaje
                    $('#maintenance-message').val(data.maintenance_message || 'estamos realizando un manteniemiento gracias por su panciencia pronto estaremos en linea');
                    
                    // Actualizar contador
                    $('#allowed-companies-count').text(data.active_companies_count || 0);
                    
                    // Actualizar estado visual
                    updateStatusCard(data.maintenance_mode, data.active_companies_count);
                    
                    // Actualizar info de inicio
                    if (data.maintenance_mode && data.maintenance_started_at) {
                        $('#started-at').text(new Date(data.maintenance_started_at).toLocaleString());
                        $('#started-by').text(data.maintenance_started_by || 'Sistema');
                        $('#started-info').show();
                    } else {
                        $('#started-info').hide();
                    }
                    
                    // Cargar empresas seleccionadas si existen
                    if (data.maintenance_allowed_companies && data.maintenance_allowed_companies.length > 0) {
                        loadSelectedCompanies(data.maintenance_allowed_companies);
                    }
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Error cargando estado:', xhr.status, error);
                
                // Si es error 503, probablemente el mantenimiento está activo
                if (xhr.status === 503) {
                    $('#maintenance-status-text').html('<strong>MANTENIMIENTO ACTIVO</strong> - Estado no disponible durante mantenimiento');
                    $('#maintenance-status-card').removeClass('alert-info').addClass('alert-warning');
                    showAlert('El mantenimiento está activo. No se puede obtener el estado actual.', 'warning');
                } else {
                    showAlert('Error al cargar el estado de mantenimiento: ' + error, 'danger');
                }
            });
    }

    function loadSelectedCompanies(companyIds) {
        // Cargar los datos de las empresas seleccionadas
        $.ajax({
            url: '/maintenance/companies',
            method: 'GET',
            dataType: 'json'
        })
            .done(function(response) {
                if (response.success) {
                    const selectedCompanies = response.data.filter(company => 
                        companyIds.includes(company.id)
                    );
                    
                    // Agregar opciones seleccionadas al select2
                    selectedCompanies.forEach(company => {
                        const option = new Option(
                            company.name + ' - ' + company.identification_number + ' (' + company.subdomain + ')', 
                            company.id, 
                            true, 
                            true
                        );
                        $('#allowed-companies').append(option);
                    });
                    
                    $('#allowed-companies').trigger('change');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Error cargando empresas:', error);
            });
    }

    function updateSwitchLabel(isActive) {
        const label = isActive ? 'Desactivar Modo Mantenimiento' : 'Activar Modo Mantenimiento';
        $('#maintenance-switch-label').text(label);
    }

    function updateStatusCard(isActive, allowedCount) {
        const card = $('#maintenance-status-card');
        const text = $('#maintenance-status-text');
        
        if (isActive) {
            card.removeClass('alert-info alert-success').addClass('alert-warning');
            text.html(`
                <strong>MANTENIMIENTO ACTIVO</strong> - 
                ${allowedCount > 0 ? allowedCount + ' empresas pueden acceder' : 'Todas las empresas bloqueadas'}
            `);
        } else {
            card.removeClass('alert-warning alert-info').addClass('alert-success');
            text.html('<strong>SISTEMA OPERATIVO</strong> - Todas las empresas tienen acceso normal');
        }
    }

    function showConfirmation() {
        const isActive = $('#maintenance-mode').is(':checked');
        const allowedCount = $('#allowed-companies').val() ? $('#allowed-companies').val().length : 0;
        
        let message;
        if (isActive) {
            message = allowedCount > 0 
                ? `¿Activar modo mantenimiento permitiendo acceso a ${allowedCount} empresas seleccionadas?`
                : '¿Activar modo mantenimiento bloqueando TODAS las empresas?';
        } else {
            message = '¿Desactivar modo mantenimiento y restaurar acceso normal a todas las empresas?';
        }
        
        $('#confirmation-message').text(message);
        $('#confirmation-modal').modal('show');
    }

    function saveMaintenanceConfig() {
        const formData = {
            maintenance_mode: $('#maintenance-mode').is(':checked') ? 1 : 0,
            maintenance_message: $('#maintenance-message').val(),
            maintenance_allowed_companies: $('#allowed-companies').val() || []
        };
        
        console.log('Enviando datos:', formData);

        $.ajax({
            url: '/maintenance/toggle',
            method: 'POST',
            data: formData,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function() {
                $('#save-maintenance').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...');
            }
        })
        .done(function(response) {
            console.log('Respuesta:', response);
            if (response.success) {
                $('#confirmation-modal').modal('hide');
                showAlert(response.message, 'success');
                
                // Actualizar interfaz inmediatamente sin hacer petición AJAX
                const isActive = $('#maintenance-mode').is(':checked');
                const allowedCount = $('#allowed-companies').val() ? $('#allowed-companies').val().length : 0;
                updateSwitchLabel(isActive);
                updateStatusCard(isActive, allowedCount);
                
                // Si se activó el mantenimiento, mostrar info de inicio
                if (isActive) {
                    const now = new Date().toLocaleString();
                    $('#started-at').text(now);
                    $('#started-by').text('Administrador');
                    $('#started-info').show();
                } else {
                    $('#started-info').hide();
                }
            } else {
                showAlert(response.message || 'Error al guardar configuración', 'danger');
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Error en la petición:', xhr.responseText);
            let message = 'Error de conexión al guardar configuración';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            } else if (xhr.responseText) {
                message = 'Error: ' + xhr.status + ' - ' + error;
            }
            
            showAlert(message, 'danger');
        })
        .always(function() {
            $('#save-maintenance').prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Guardar Configuración');
        });
    }

    function showAlert(message, type) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;
        
        // Remover alertas anteriores
        $('.alert').remove();
        
        // Agregar nueva alerta al principio del card-body
        $('.card-body').prepend(alertHtml);
        
        // Auto-dismiss después de 5 segundos
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }
});
</script>
@endpush