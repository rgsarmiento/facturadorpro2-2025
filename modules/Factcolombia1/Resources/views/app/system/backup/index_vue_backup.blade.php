@extends('system.layouts.app')

@section('content')
<div id="vue-backup-system-app" class="modern-dashboard">
    <!-- Modern Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-title">
                <h1 class="page-title">
                    <i class="fas fa-database mr-3"></i>
                    Backup Completo del Sistema
                </h1>
                <p class="page-subtitle">Gestión y administración de backups del sistema completo</p>
            </div>
            <div class="header-actions">
                <button @click="createBackup" :disabled="isCreating" class="btn btn-primary btn-modern">
                    <i class="fas fa-plus mr-2" v-if="!isCreating"></i>
                    <i class="fas fa-spinner fa-spin mr-2" v-if="isCreating"></i>
                    @{{ isCreating ? 'Creando Backup...' : 'Crear Backup Completo' }}
                </button>
                <button @click="loadBackups" class="btn btn-info btn-modern ml-2">
                    <i class="fas fa-sync mr-2"></i>
                    Actualizar Lista
                </button>
                <button @click="showRestoreDialog" class="btn btn-warning btn-modern ml-2">
                    <i class="fas fa-upload mr-2"></i>
                    Restaurar desde Archivo
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards Row -->
    <div v-if="!isLoading" class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="fas fa-archive"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">@{{ backups ? backups.length : 0 }}</div>
                <div class="stat-label">Backups Disponibles</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">@{{ getLastBackupDate() }}</div>
                <div class="stat-label">Último Backup</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="fas fa-hdd"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">@{{ getTotalSize() }}</div>
                <div class="stat-label">Espacio Total</div>
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="table-card">
        <div class="loading-container">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="loading-text">Cargando backups del sistema...</p>
        </div>
    </div>

    <!-- Backups Table -->
    <div v-if="!isLoading" class="table-card">
        <div class="table-container">
            <table class="table table-modern">
                <thead>
                    <tr class="table-header-row">
                        <th colspan="100%" class="table-title-header">
                            <div class="table-title-content">
                                <h3>
                                    <i class="fas fa-archive mr-2"></i>
                                    Historial de Backups del Sistema
                                </h3>
                                <span class="record-count">@{{ backups ? backups.length : 0 }} Backups Registrados</span>
                            </div>
                        </th>
                    </tr>
                    <tr>
                        <th class="th-modern">#</th>
                        <th class="th-modern">Archivo</th>
                        <th class="th-modern">Fecha de Creación</th>
                        <th class="th-modern">Tamaño</th>
                        <th class="th-modern text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(backup, index) in (backups || [])" :key="backup.filename" class="tr-modern">
                        <td class="td-modern">
                            <span class="row-number">@{{ index + 1 }}</span>
                        </td>
                        <td class="td-modern">
                            <div class="backup-file-info">
                                <i class="fas fa-file-archive text-primary mr-2"></i>
                                <span class="backup-filename">@{{ backup.filename }}</span>
                            </div>
                        </td>
                        <td class="td-modern">
                            <span class="backup-date">@{{ formatDate(backup.created_at) }}</span>
                        </td>
                        <td class="td-modern">
                            <span class="backup-size">@{{ formatFileSize(backup.size) }}</span>
                        </td>
                        <td class="td-modern text-center">
                            <div class="action-buttons-modern">
                                <button @click="downloadBackup(backup.filename)" 
                                        class="btn btn-sm btn-outline-primary mr-1"
                                        title="Descargar">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button @click="confirmDelete(backup.filename)" 
                                        class="btn btn-sm btn-outline-danger"
                                        title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="backups && backups.length === 0" class="tr-modern">
                        <td colspan="5" class="td-modern text-center empty-state">
                            <div class="empty-state-content">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No hay backups disponibles</h4>
                                <p class="text-muted">Crea tu primer backup del sistema haciendo clic en "Crear Backup Completo"</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Restore Modal -->
    <div class="modal fade" id="restoreModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content modern-modal">
                <div class="modal-header modern-modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-upload mr-2"></i>
                        Restaurar Sistema desde Backup
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning modern-alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>¡Advertencia Importante!</strong> 
                        <br>Esta acción restaurará completamente el sistema y todas las bases de datos de los tenants. 
                        <strong>Se perderán todos los datos actuales</strong> y no se podrá deshacer esta operación.
                    </div>
                    <form enctype="multipart/form-data">
                        <div class="form-group modern-form-group">
                            <label for="backupFile" class="modern-label">
                                <i class="fas fa-file-upload mr-2"></i>
                                Seleccionar archivo de backup:
                            </label>
                            <input type="file" 
                                   class="form-control modern-file-input" 
                                   id="backupFile" 
                                   ref="backupFile"
                                   accept=".zip"
                                   required>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i>
                                Solo se aceptan archivos .zip generados por este sistema de backup.
                            </small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer modern-modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-2"></i>
                        Cancelar
                    </button>
                    <button type="button" @click="restoreBackup" :disabled="isRestoring" class="btn btn-warning">
                        <i class="fas fa-upload mr-2" v-if="!isRestoring"></i>
                        <i class="fas fa-spinner fa-spin mr-2" v-if="isRestoring"></i>
                        @{{ isRestoring ? 'Restaurando Sistema...' : 'Confirmar Restauración' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Cargar Vue.js desde CDN específicamente para esta vista -->
<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script>
// Función para verificar si el elemento existe
function waitForElement(selector, callback, maxAttempts = 50) {
    let attempts = 0;
    const checkElement = () => {
        attempts++;
        
        // Debug detallado
        console.log(`Intento ${attempts}: buscando elemento ${selector}`);
        console.log('Elementos disponibles con ID:', Array.from(document.querySelectorAll('[id]')).map(el => `#${el.id}`));
        
        const element = document.getElementById(selector.replace('#', ''));
        const vueAvailable = typeof Vue !== 'undefined';
        
        console.log(`Elemento encontrado: ${element ? 'SÍ' : 'NO'}, Vue disponible: ${vueAvailable ? 'SÍ' : 'NO'}`);
        
        if (element && vueAvailable) {
            console.log(`Elemento ${selector} encontrado después de ${attempts} intentos`);
            callback(element);
        } else if (attempts < maxAttempts) {
            setTimeout(checkElement, 200); // Aumentar tiempo a 200ms
        } else {
            console.error(`Elemento ${selector} no encontrado después de ${maxAttempts} intentos`);
            console.error('DOM completo:', document.documentElement.outerHTML.substring(0, 1000));
        }
    };
    
    checkElement();
}

// Inicialización robusta
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== DEBUG INICIAL ===');
    console.log('DOM cargado, verificando elementos...');
    console.log('document.body:', document.body);
    console.log('Todos los elementos con ID:', Array.from(document.querySelectorAll('[id]')).map(el => `#${el.id}`));
    
    // Verificar si el elemento existe directamente
    const directElement = document.getElementById('vue-backup-system-app');
    console.log('Elemento vue-backup-system-app existe:', directElement !== null);
    
    if (directElement) {
        console.log('¡Elemento encontrado directamente! Inicializando Vue...');
        initializeVue(directElement);
    } else {
        console.log('Elemento no encontrado directamente, esperando...');
        waitForElement('#vue-backup-system-app', waitForElementCallback);
    }
});

// Función separada para inicializar Vue
function initializeVue(element) {
    console.log('=== INICIANDO VUE ===');
    console.log('Vue disponible:', typeof Vue !== 'undefined');
    console.log('Elemento:', element);
    
    if (typeof Vue === 'undefined') {
        console.error('Vue no está disponible');
        return;
    }
    
    try {
        // Crear una nueva instancia de Vue
        const backupApp = new Vue({
            el: '#vue-backup-system-app',
        data() {
            return {
                backups: [],
                isLoading: true,
                isCreating: false,
                isRestoring: false
            };
        },
        created() {
            // Inicializar propiedades adicionales si es necesario
            this.backups = [];
        },
        mounted() {
            this.loadBackups();
        },
        methods: {
            loadBackups() {
                this.isLoading = true;
                
                axios.get('/co-companies/system-backup/list')
                .then(response => {
                    if (response.data.success) {
                        this.backups = response.data.data;
                    } else {
                        this.showError('Error al cargar los backups: ' + response.data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading backups:', error);
                    this.showError('Error al cargar la lista de backups');
                })
                .finally(() => {
                    this.isLoading = false;
                });
            },

            createBackup() {
                if (!confirm('¿Está seguro de que desea crear un backup completo del sistema? Esto puede tomar varios minutos.')) {
                    return;
                }

                this.isCreating = true;
                
                axios.post('/co-companies/system-backup/create')
                    .then(response => {
                        if (response.data.success) {
                            this.showSuccess('Backup creado exitosamente');
                            this.loadBackups();
                        } else {
                            this.showError('Error al crear backup: ' + response.data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error creating backup:', error);
                        this.showError('Error al crear el backup del sistema');
                    })
                    .finally(() => {
                        this.isCreating = false;
                    });
            },

            downloadBackup(filename) {
                window.open('/co-companies/system-backup/download/' + encodeURIComponent(filename));
            },

            confirmDelete(filename) {
                if (confirm('¿Está seguro de que desea eliminar este backup? Esta acción no se puede deshacer.')) {
                    this.deleteBackup(filename);
                }
            },

            deleteBackup(filename) {
                axios.delete('/co-companies/system-backup/delete/' + encodeURIComponent(filename))
                    .then(response => {
                        if (response.data.success) {
                            this.showSuccess('Backup eliminado exitosamente');
                            this.loadBackups();
                        } else {
                            this.showError('Error al eliminar backup: ' + response.data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting backup:', error);
                        this.showError('Error al eliminar el backup');
                    });
            },

            showRestoreDialog() {
                $('#restoreModal').modal('show');
            },

            restoreBackup() {
                const fileInput = this.$refs.backupFile;
                
                if (!fileInput.files || fileInput.files.length === 0) {
                    this.showError('Por favor seleccione un archivo de backup');
                    return;
                }

                if (!confirm('¿Está seguro de que desea restaurar el sistema? Se perderán todos los datos actuales.')) {
                    return;
                }

                const formData = new FormData();
                formData.append('backup_file', fileInput.files[0]);

                this.isRestoring = true;

                axios.post('/co-companies/system-backup/restore', formData, {
                    headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then(response => {
                if (response.data.success) {
                    this.showSuccess('Sistema restaurado exitosamente');
                    $('#restoreModal').modal('hide');
                    this.loadBackups();
                } else {
                    this.showError('Error al restaurar: ' + response.data.message);
                }
            })
            .catch(error => {
                console.error('Error restoring backup:', error);
                this.showError('Error al restaurar el sistema desde el backup');
            })
            .finally(() => {
                this.isRestoring = false;
            });
            },

            getLastBackupDate() {
                if (!this.backups || this.backups.length === 0) return 'N/A';
                
                const latest = this.backups.reduce((latest, current) => {
                    return new Date(current.created_at) > new Date(latest.created_at) ? current : latest;
                });
                
                return this.formatDate(latest.created_at);
            },

            getTotalSize() {
                if (!this.backups || this.backups.length === 0) return '0 GB';
                
                const totalBytes = this.backups.reduce((total, backup) => {
                    return total + (backup.size || 0);
                }, 0);
                
                return this.formatFileSize(totalBytes);
            },

            formatDate(dateString) {
                return new Date(dateString).toLocaleString('es-ES');
            },

            formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            },

            showSuccess(message) {
                if (typeof toastr !== 'undefined') {
                    toastr.success(message);
                } else {
                    alert(message);
                }
            },

            showError(message) {
                if (typeof toastr !== 'undefined') {
                    toastr.error(message);
                } else {
                    alert(message);
                }
            }
        }
    });
    
    console.log('Vue para sistema de backups inicializado correctamente');
    } catch (error) {
        console.error('Error al inicializar Vue:', error);
    }
}

// Llamar también a initializeVue desde waitForElement para compatibilidad
function waitForElementCallback(element) {
    initializeVue(element);
}
});

// Respaldo adicional con window.onload por si DOMContentLoaded no funciona
window.addEventListener('load', function() {
    // Solo ejecutar si Vue no se ha inicializado aún
    if (!document.querySelector('#vue-backup-system-app').__vue__) {
        console.log('Intentando inicialización de respaldo...');
        waitForElement('#vue-backup-system-app', function(element) {
            console.log('Inicializando Vue (respaldo)...');
            // El mismo código de inicialización aquí
        });
    }
});
</script>
@endpush

@push('styles')
<style>
/* Modern Dashboard Styles */
#vue-backup-system-app .modern-dashboard {
    background: #f8fafc;
    min-height: 100vh;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Header Styles */
#vue-backup-system-app .dashboard-header {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}

#vue-backup-system-app .header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

#vue-backup-system-app .page-title {
    font-size: 1.875rem;
    font-weight: 700;
    color: #1a202c;
    margin: 0;
}

#vue-backup-system-app .page-subtitle {
    color: #718096;
    font-size: 1rem;
    margin-top: 4px;
    margin-bottom: 0;
}

#vue-backup-system-app .btn-modern {
    background: #667eea;
    border: none;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s ease;
}

#vue-backup-system-app .btn-modern:hover {
    background: #5a67d8;
    transform: translateY(-1px);
}

#vue-backup-system-app .btn-modern.btn-info {
    background: #4299e1;
}

#vue-backup-system-app .btn-modern.btn-info:hover {
    background: #3182ce;
}

#vue-backup-system-app .btn-modern.btn-warning {
    background: #ed8936;
}

#vue-backup-system-app .btn-modern.btn-warning:hover {
    background: #dd6b20;
}

/* Stats Cards */
#vue-backup-system-app .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

#vue-backup-system-app .stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 16px;
}

#vue-backup-system-app .stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
}

#vue-backup-system-app .stat-icon.bg-primary { background: #667eea; }
#vue-backup-system-app .stat-icon.bg-info { background: #4299e1; }
#vue-backup-system-app .stat-icon.bg-warning { background: #ed8936; }

#vue-backup-system-app .stat-content .stat-number {
    font-size: 1.875rem;
    font-weight: 700;
    color: #1a202c;
    line-height: 1;
}

#vue-backup-system-app .stat-content .stat-label {
    font-size: 0.875rem;
    color: #718096;
    margin-top: 4px;
}

/* Table Styles */
#vue-backup-system-app .table-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

#vue-backup-system-app .table-container {
    overflow-x: auto;
}

#vue-backup-system-app .table-modern {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}

/* Loading */
#vue-backup-system-app .loading-container {
    text-align: center;
    padding: 60px 20px;
}

#vue-backup-system-app .loading-text {
    margin-top: 16px;
    color: #718096;
    font-size: 1.1rem;
}

/* Table Title Row */
#vue-backup-system-app .table-title-header {
    background: #f7fafc;
    border-bottom: 2px solid #e2e8f0;
    padding: 20px 24px;
}

#vue-backup-system-app .table-title-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

#vue-backup-system-app .table-title-content h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #2d3748;
}

#vue-backup-system-app .record-count {
    background: #667eea;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 500;
}

/* Table Headers */
#vue-backup-system-app .th-modern {
    background: #f7fafc;
    color: #4a5568;
    font-weight: 600;
    padding: 8px 6px;
    text-align: left;
    font-size: 0.8rem;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
    line-height: 1.2;
}

/* Table Cells */
#vue-backup-system-app .td-modern {
    padding: 8px 6px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    font-size: 0.8rem;
    line-height: 1.2;
}

#vue-backup-system-app .tr-modern:hover {
    background: #f8fafc;
}

/* Content Styles */
#vue-backup-system-app .row-number {
    font-weight: 600;
    color: #718096;
}

#vue-backup-system-app .backup-file-info {
    display: flex;
    align-items: center;
}

#vue-backup-system-app .backup-filename {
    font-family: monospace;
    font-size: 0.8rem;
    color: #2d3748;
}

#vue-backup-system-app .backup-date {
    color: #4a5568;
    font-size: 0.8rem;
}

#vue-backup-system-app .backup-size {
    color: #4a5568;
    font-weight: 600;
    font-size: 0.8rem;
}

/* Action Buttons */
#vue-backup-system-app .action-buttons-modern {
    display: flex;
    justify-content: center;
    gap: 4px;
}

/* Empty State */
#vue-backup-system-app .empty-state {
    padding: 40px 20px !important;
}

#vue-backup-system-app .empty-state-content {
    text-align: center;
}

#vue-backup-system-app .empty-state-content h4 {
    margin: 16px 0 8px 0;
    font-size: 1.1rem;
}

#vue-backup-system-app .empty-state-content p {
    margin: 0;
    font-size: 0.95rem;
}

/* Modern Modal */
#vue-backup-system-app .modern-modal {
    border-radius: 12px;
    border: none;
}

#vue-backup-system-app .modern-modal-header {
    background: #f7fafc;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 12px 12px 0 0;
    padding: 20px 24px;
}

#vue-backup-system-app .modern-modal-footer {
    border-top: 1px solid #e2e8f0;
    padding: 20px 24px;
}

#vue-backup-system-app .modern-alert {
    border-radius: 8px;
    padding: 16px;
}

#vue-backup-system-app .modern-form-group {
    margin-bottom: 20px;
}

#vue-backup-system-app .modern-label {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 8px;
    display: block;
}

#vue-backup-system-app .modern-file-input {
    border-radius: 8px;
    border: 2px dashed #e2e8f0;
    padding: 12px;
    transition: border-color 0.2s ease;
}

#vue-backup-system-app .modern-file-input:hover {
    border-color: #667eea;
}

/* Responsive Design */
@media (max-width: 768px) {
    #vue-backup-system-app .header-content {
        flex-direction: column;
        align-items: stretch;
    }
    
    #vue-backup-system-app .header-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    #vue-backup-system-app .stats-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    #vue-backup-system-app .page-title {
        font-size: 1.5rem;
    }
}
</style>
@endpush
