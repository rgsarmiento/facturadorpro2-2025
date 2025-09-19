@extends('system.layouts.app')

@section('content')
<div id="backup-system-app" class="modern-dashboard">
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
                <button onclick="createBackup()" id="create-btn" class="btn btn-primary btn-modern">
                    <i class="fas fa-plus mr-2"></i>
                    <span>Crear Backup Completo (Sistema + Tenants)</span>
                </button>
                <button onclick="loadBackups()" class="btn btn-info btn-modern ml-2">
                    <i class="fas fa-sync mr-2"></i>
                    Actualizar Lista
                </button>
                <button onclick="debugTenants()" class="btn btn-warning btn-modern ml-2">
                    <i class="fas fa-bug mr-2"></i>
                    Debug Tenants
                </button>
                <button onclick="showRestoreDialog()" class="btn btn-warning btn-modern ml-2">
                    <i class="fas fa-upload mr-2"></i>
                    Restaurar desde Archivo
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards Row -->
    <div id="stats-section" class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="fas fa-archive"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number" id="backup-count">0</div>
                <div class="stat-label">Backups Disponibles</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number" id="last-backup-date">N/A</div>
                <div class="stat-label">Último Backup</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="fas fa-hdd"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number" id="total-size">0 GB</div>
                <div class="stat-label">Espacio Total</div>
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div id="loading-section" class="table-card" style="display: none;">
        <div class="loading-container">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="loading-text">Cargando backups del sistema...</p>
        </div>
    </div>

    <!-- Backups Table -->
    <div id="backups-table" class="table-card">
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
                                <span class="record-count" id="record-count">0 Backups Registrados</span>
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
                <tbody id="backups-tbody">
                    <tr>
                        <td colspan="5" class="text-center">Cargando datos...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Variables globales
let backups = [];
let isLoading = false;
let isCreating = false;

// Función para mostrar loading
function showLoading(show = true) {
    const loadingSection = document.getElementById('loading-section');
    const backupsTable = document.getElementById('backups-table');

    if (show) {
        loadingSection.style.display = 'block';
        backupsTable.style.display = 'none';
    } else {
        loadingSection.style.display = 'none';
        backupsTable.style.display = 'block';
    }
}

// Función para cargar backups
function loadBackups() {
    isLoading = true;
    showLoading(true);

    fetch('/co-companies/system-backup/list')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                backups = data.data || [];

                // Debug temporal - remover después
                if (backups.length > 0) {
                    console.log('=== DEBUG BACKUP DATA ===');
                    console.log('Primer backup:', backups[0]);
                    console.log('Tamaño raw:', backups[0].size);
                    console.log('Tamaño formateado:', formatFileSize(backups[0].size));
                }

                updateUI();
            } else {
                showError('Error al cargar los backups: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            showError('Error al cargar la lista de backups');
        })
        .finally(() => {
            isLoading = false;
            showLoading(false);
        });
}

// Función para actualizar la UI
function updateUI() {
    // Actualizar contadores
    document.getElementById('backup-count').textContent = backups.length;
    document.getElementById('record-count').textContent = backups.length + ' Backups Registrados';

    // Actualizar última fecha
    const lastDate = getLastBackupDate();
    document.getElementById('last-backup-date').textContent = lastDate;

    // Actualizar tamaño total
    const totalSize = getTotalSize();
    document.getElementById('total-size').textContent = totalSize;

    // Actualizar tabla
    updateTable();
}

// Función para obtener la última fecha de backup
function getLastBackupDate() {
    if (!backups || backups.length === 0) return 'N/A';

    const latest = backups.reduce((latest, current) => {
        const latestDate = new Date(latest.created_at || latest.date || 0);
        const currentDate = new Date(current.created_at || current.date || 0);
        return currentDate > latestDate ? current : latest;
    });

    return formatDate(latest.created_at || latest.date);
}

// Función para obtener tamaño total
function getTotalSize() {
    if (!backups || backups.length === 0) return '0 GB';

    const totalBytes = backups.reduce((total, backup) => {
        // Ahora el servidor envía 'size' como número de bytes
        const size = parseInt(backup.size) || 0;
        return total + size;
    }, 0);

    return formatFileSize(totalBytes);
}

// Función para formatear fechas
function formatDate(dateString) {
    if (!dateString) return 'N/A';

    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'N/A';
        return date.toLocaleString('es-ES');
    } catch (error) {
        return 'N/A';
    }
}

// Función para formatear tamaños de archivo
function formatFileSize(bytes) {
    const numBytes = parseInt(bytes) || 0;
    if (numBytes === 0) return '0 Bytes';

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(numBytes) / Math.log(k));
    return parseFloat((numBytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Función para actualizar la tabla
function updateTable() {
    const tbody = document.getElementById('backups-tbody');

    if (backups.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center">No hay backups disponibles</td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = backups.map((backup, index) => {
        const filename = backup.filename || backup.name || `backup_${index + 1}.sql`;
        const createdAt = backup.created_at || backup.date || backup.created_date;
        const size = backup.size || backup.file_size || 0;

        return `
        <tr class="tr-modern">
            <td class="td-modern">${index + 1}</td>
            <td class="td-modern">
                <div class="file-info">
                    <i class="fas fa-file-archive text-primary mr-2"></i>
                    <span class="file-name">${filename}</span>
                </div>
            </td>
            <td class="td-modern">
                <div class="date-info">
                    <i class="fas fa-calendar text-info mr-2"></i>
                    ${formatDate(createdAt)}
                </div>
            </td>
            <td class="td-modern">
                <span class="badge badge-info">${formatFileSize(size)}</span>
            </td>
            <td class="td-modern text-center">
                <div class="action-buttons">
                    <button onclick="downloadBackup('${filename}')" class="btn btn-sm btn-success mr-1" title="Descargar">
                        <i class="fas fa-download"></i>
                    </button>
                    <button onclick="confirmDelete('${filename}')" class="btn btn-sm btn-danger" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

// Función para crear backup
function createBackup() {
    if (!confirm('¿Está seguro de que desea crear un backup completo del sistema? Esto puede tomar varios minutos.')) {
        return;
    }

    isCreating = true;
    const btn = document.getElementById('create-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i><span>Creando Backup (Sistema + Tenants)...</span>';

    fetch('/co-companies/system-backup/create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Backup creado exitosamente');

            // Mostrar información de debug si está disponible
            if (data.details && data.details.debug_info) {
                console.log('=== DEBUG BACKUP ===');
                console.log('Tenant count:', data.details.tenant_count);
                console.log('ZIP size:', data.details.zip_size);
                console.log('ZIP exists:', data.details.debug_info.zip_path_exists);
                console.log('ZIP size bytes:', data.details.debug_info.zip_size_bytes);
                console.log('Tenants dir existed:', data.details.debug_info.tenants_dir_existed);
            }

            loadBackups();
        } else {
            showError('Error al crear backup: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        showError('Error al crear el backup del sistema');
    })
    .finally(() => {
        isCreating = false;
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus mr-2"></i><span>Crear Backup Completo (Sistema + Tenants)</span>';
    });
}

// Función para descargar backup
function downloadBackup(filename) {
    window.open('/co-companies/system-backup/download/' + encodeURIComponent(filename));
}

// Función para confirmar eliminación
function confirmDelete(filename) {
    if (confirm('¿Está seguro de que desea eliminar este backup? Esta acción no se puede deshacer.')) {
        deleteBackup(filename);
    }
}

// Función para eliminar backup
function deleteBackup(filename) {
    fetch('/co-companies/system-backup/delete/' + encodeURIComponent(filename), {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Backup eliminado exitosamente');
            loadBackups();
        } else {
            showError('Error al eliminar backup: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        showError('Error al eliminar el backup');
    });
}

// Función para mostrar diálogo de restauración
function showRestoreDialog() {
    // Implementar modal o lógica de restauración
    showError('Funcionalidad de restauración pendiente de implementar');
}

// Función de debug para verificar tenants
function debugTenants() {
    fetch('/co-companies/system-backup/debug-tenants')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('=== DEBUG TENANTS ===');
                console.log('Total websites:', data.debug.total_websites);
                console.log('Prefix database:', data.debug.prefix_database);
                console.log('Available databases:', data.debug.available_databases);
                console.log('Websites details:', data.debug.websites);
                console.log('Mysqldump test:', data.debug.mysqldump_test);

                // Mostrar información resumida
                const mysqldumpStatus = data.debug.mysqldump_test ?
                    (data.debug.mysqldump_test.success ? '✅ OK' : '❌ FALLA') : '⚠️ No probado';

                const summary = `
DEBUG TENANTS:
- Total websites: ${data.debug.total_websites}
- Prefix BD: ${data.debug.prefix_database}
- BD disponibles: ${data.debug.available_databases.length}
- Tenants con BD válida: ${data.debug.websites.filter(w => w.database_exists).length}
- Test mysqldump: ${mysqldumpStatus}

Ver detalles completos en la consola (F12)
                `.trim();

                alert(summary);
            } else {
                showError('Error en debug: ' + data.message);
            }
        })
        .catch(error => {
            showError('Error al ejecutar debug de tenants');
        });
}

// Funciones para mostrar mensajes
function showSuccess(message) {
    if (typeof toastr !== 'undefined') {
        toastr.success(message);
    }
}

function showError(message) {
    if (typeof toastr !== 'undefined') {
        toastr.error(message);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    loadBackups();
});
</script>
@endpush

@push('styles')
<style>
/* Modern Dashboard Styles */
#backup-system-app .modern-dashboard {
    background: #f8fafc;
    min-height: 100vh;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Header Styles */
#backup-system-app .dashboard-header {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}

#backup-system-app .header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

#backup-system-app .page-title {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
    color: #2d3748;
}

#backup-system-app .page-subtitle {
    font-size: 1rem;
    color: #718096;
    margin-top: 8px;
    font-weight: 400;
}

#backup-system-app .btn-modern {
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}

#backup-system-app .btn-modern:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

#backup-system-app .btn-modern.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

#backup-system-app .btn-modern.btn-primary:hover {
    background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

#backup-system-app .btn-modern.btn-info {
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
    color: white;
}

#backup-system-app .btn-modern.btn-info:hover {
    background: linear-gradient(135deg, #3182ce 0%, #2c5282 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(66, 153, 225, 0.4);
}

#backup-system-app .btn-modern.btn-warning {
    background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
    color: white;
}

#backup-system-app .btn-modern.btn-warning:hover {
    background: linear-gradient(135deg, #dd6b20 0%, #c05621 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(237, 137, 54, 0.4);
}

#backup-system-app .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

#backup-system-app .stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.3s ease;
    cursor: pointer;
}

#backup-system-app .stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    border-color: #cbd5e0;
}

#backup-system-app .stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

#backup-system-app .stat-card:hover .stat-icon {
    transform: scale(1.05);
}

#backup-system-app .stat-icon.bg-primary { background: #667eea; }
#backup-system-app .stat-icon.bg-info { background: #4299e1; }
#backup-system-app .stat-icon.bg-warning { background: #ed8936; }

#backup-system-app .stat-content .stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #2d3748;
    line-height: 1.2;
    margin-bottom: 4px;
}

#backup-system-app .stat-content .stat-label {
    font-size: 0.875rem;
    color: #718096;
    font-weight: 500;
}

#backup-system-app .table-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: all 0.3s ease;
}

#backup-system-app .table-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

#backup-system-app .loading-container {
    text-align: center;
    padding: 60px 20px;
    color: #718096;
}

#backup-system-app .loading-text {
    margin-top: 16px;
    font-size: 1rem;
}

#backup-system-app .table-modern {
    width: 100%;
    margin: 0;
    border: none;
}

#backup-system-app .table-header-row {
    background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
}

#backup-system-app .table-title-header {
    padding: 20px 24px;
    border: none;
}

#backup-system-app .table-title-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

#backup-system-app .table-title-content h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #2d3748;
}

#backup-system-app .record-count {
    background: #e2e8f0;
    color: #4a5568;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

#backup-system-app .th-modern {
    background: #f8fafc;
    padding: 16px 20px;
    border: none;
    border-top: 1px solid #e2e8f0;
    font-weight: 600;
    font-size: 0.875rem;
    color: #4a5568;
    letter-spacing: 0.025em;
}

#backup-system-app .td-modern {
    padding: 16px 20px;
    border: none;
    border-top: 1px solid #f1f5f9;
    color: #4a5568;
    vertical-align: middle;
}

#backup-system-app .tr-modern {
    transition: all 0.2s ease;
}

#backup-system-app .tr-modern:hover {
    background-color: #f8fafc;
    transform: translateX(2px);
}

#backup-system-app .file-info {
    display: flex;
    align-items: center;
}

#backup-system-app .file-name {
    font-weight: 500;
    color: #2d3748;
}

#backup-system-app .date-info {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
}

#backup-system-app .action-buttons {
    display: flex;
    justify-content: center;
    gap: 8px;
}

#backup-system-app .btn-sm {
    padding: 6px 12px;
    font-size: 0.75rem;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

#backup-system-app .btn-success {
    background-color: #48bb78;
    color: white;
}

#backup-system-app .btn-success:hover {
    background-color: #38a169;
}

#backup-system-app .btn-danger {
    background-color: #f56565;
    color: white;
}

#backup-system-app .btn-danger:hover {
    background-color: #e53e3e;
}

/* Responsive */
@media (max-width: 768px) {
    #backup-system-app .modern-dashboard {
        padding: 16px;
    }

    #backup-system-app .header-content {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }

    #backup-system-app .header-actions {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    #backup-system-app .stats-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    #backup-system-app .page-title {
        font-size: 1.5rem;
    }
}
</style>
@endpush
