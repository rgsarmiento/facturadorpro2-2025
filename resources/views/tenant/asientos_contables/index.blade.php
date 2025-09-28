@extends('tenant.layouts.app')

@push('styles')
<style>
    .content-header-left h1 {
        display: flex;
        align-items: center;
    }
    .construction-icon {
        margin-left: 10px;
        color: #ff9800;
        font-size: 20px;
    }
    .btn-group .btn {
        border-radius: 0;
    }
    .btn-group .btn:first-child {
        border-top-left-radius: 4px;
        border-bottom-left-radius: 4px;
    }
    .btn-group .btn:last-child {
        border-top-right-radius: 4px;
        border-bottom-right-radius: 4px;
    }
    .estado-badge {
        font-size: 0.8em;
        padding: 3px 8px;
        border-radius: 12px;
    }
    .estado-borrador {
        background-color: #ffc107;
        color: #000;
    }
    .estado-confirmado {
        background-color: #28a745;
        color: #fff;
    }
    .estado-anulado {
        background-color: #dc3545;
        color: #fff;
    }

    /* Reducir espaciado vertical en toda la vista */
    .form-control {
        padding: 0.25rem 0.5rem;
        margin-bottom: 0.25rem;
    }

    .row {
        margin-bottom: 0.25rem;
    }

    .table td, .table th {
        padding: 0.5rem;
    }

    .col-md-1, .col-md-2, .col-md-3 {
        padding-bottom: 0.25rem;
    }

    /* Asegurar que los botones estén a la derecha */
    .card-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
    }

    .card-tools {
        margin-left: auto !important;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Asientos Contables
                    <i class="fas fa-tools construction-icon" title="Módulo en construcción"></i>
                </h3>
                <div class="card-tools">
                    <a href="{{ route('tenant.asientos_contables.create') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Asiento
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div id="asientos-contables-app">
                    <!-- Filtros -->
                    <div class="row mb-1">
                        <div class="col-md-3">
                            <input type="text"
                                   id="search-input"
                                   class="form-control"
                                   placeholder="Buscar por número o concepto">
                        </div>
                        <div class="col-md-2">
                            <input type="date"
                                   id="fecha-inicio-input"
                                   class="form-control"
                                   placeholder="Fecha inicio">
                        </div>
                        <div class="col-md-2">
                            <input type="date"
                                   id="fecha-fin-input"
                                   class="form-control"
                                   placeholder="Fecha fin">
                        </div>
                        <div class="col-md-2">
                            <select id="tipo-comprobante-select" class="form-control">
                                <option value="">Todos los tipos</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select id="estado-select" class="form-control">
                                <option value="">Todos los estados</option>
                                <option value="BORRADOR">Borrador</option>
                                <option value="CONFIRMADO">Confirmado</option>
                                <option value="ANULADO">Anulado</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button id="search-button" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Loading -->
                    <div id="loading-section" class="text-center" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i> Cargando...
                    </div>

                    <!-- Tabla -->
                    <div id="table-section">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Número</th>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Concepto</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Creado por</th>
                                        <th width="120">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="records-tbody">
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            No se encontraron asientos contables
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div id="pagination-section" class="d-flex justify-content-between align-items-center" style="display: none;">
                            <div id="pagination-info">
                                Mostrando 0 a 0 de 0 registros
                            </div>
                            <nav>
                                <ul class="pagination pagination-sm" id="pagination-controls">
                                    <!-- Pagination buttons will be generated here -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing...');

        // Verificar si Vue está disponible
        if (typeof Vue !== 'undefined') {
            const vueStatus = document.getElementById('vue-status');
            if (vueStatus) vueStatus.textContent = 'Vue disponible - versión: ' + Vue.version;

            // Intentar inicializar Vue
            try {
                window.app = new Vue({
                    el: '#asientos-contables-app',
                    data: {
                        records: [],
                        pagination: {
                            total: 0,
                            current_page: 1,
                            per_page: 10,
                            last_page: 1
                        },
                        loading: false,
                        filters: {
                            search: '',
                            fecha_inicio: '',
                            fecha_fin: '',
                            tipo_comprobante_id: '',
                            estado: ''
                        },
                        tiposComprobantes: [],
                        estados: [
                            { value: '', text: 'Todos los estados' },
                            { value: 'BORRADOR', text: 'Borrador' },
                            { value: 'CONFIRMADO', text: 'Confirmado' },
                            { value: 'ANULADO', text: 'Anulado' }
                        ]
                    },
                    mounted() {
                        this.loadTiposComprobantes();
                        this.loadRecords();
                    },
                    methods: {
                        async loadTiposComprobantes() {
                            try {
                                const response = await axios.get('/contabilidad/asientos-contables/tipos-comprobantes');

                                if (response.data.success && Array.isArray(response.data.data)) {
                                    this.tiposComprobantes = [
                                        { id: '', nombre: 'Todos los tipos' },
                                        ...response.data.data
                                    ];
                                } else {
                                    this.tiposComprobantes = [{ id: '', nombre: 'Todos los tipos' }];
                                }
                            } catch (error) {
                                this.tiposComprobantes = [{ id: '', nombre: 'Todos los tipos' }];
                            }
                        },
                        async loadRecords(page = 1) {
                            this.loading = true;
                            try {
                                const params = new URLSearchParams({
                                    page: page,
                                    ...this.filters
                                });

                                const response = await axios.get('/contabilidad/asientos-contables/records?' + params);

                                if (response.data.success) {
                                    this.records = response.data.data.records;
                                    this.pagination = response.data.data.pagination;
                                    this.renderRecords();
                                    this.renderPagination();
                                }
                            } catch (error) {
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire('Error', 'Error al cargar los asientos contables', 'error');
                                }
                            } finally {
                                this.loading = false;
                            }
                        },
                        searchRecords() {
                            this.loadRecords(1);
                        },
                        renderRecords() {
                            const tbody = document.getElementById('records-tbody');
                            if (!tbody) return;

                            if (this.records.length === 0) {
                                tbody.innerHTML = `
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            No se encontraron asientos contables
                                        </td>
                                    </tr>
                                `;
                                return;
                            }

                            let html = '';
                            this.records.forEach(record => {
                                const estadoBadge = this.getEstadoBadge(record.estado);
                                const tipoComprobante = record.tipo_comprobante ? record.tipo_comprobante.nombre : 'N/A';
                                const usuarioCreacion = record.usuario_creacion ? record.usuario_creacion.name : 'N/A';
                                const fechaFormateada = this.formatDate(record.fecha_asiento);
                                const fechaCreacion = this.formatDateTime(record.fecha_creacion);

                                html += `
                                    <tr>
                                        <td>${record.numero_comprobante}</td>
                                        <td>${fechaFormateada}</td>
                                        <td>${tipoComprobante}</td>
                                        <td>${record.concepto}</td>
                                        <td class="text-right">$${this.formatNumber(record.total_debito)}</td>
                                        <td>${estadoBadge}</td>
                                        <td>${usuarioCreacion}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/contabilidad/asientos-contables/${record.id}" class="btn btn-primary btn-sm" title="Ver">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="/contabilidad/asientos-contables/${record.id}/edit" class="btn btn-warning btn-sm" title="Editar">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                `;
                            });

                            tbody.innerHTML = html;
                        },
                        renderPagination() {
                            const paginationSection = document.getElementById('pagination-section');
                            const paginationInfo = document.getElementById('pagination-info');
                            const paginationControls = document.getElementById('pagination-controls');

                            if (!paginationSection || !paginationInfo || !paginationControls) return;

                            if (this.pagination.total > 0) {
                                paginationSection.style.display = 'flex';

                                // Update info
                                paginationInfo.textContent = `Mostrando ${this.paginationStart} a ${this.paginationEnd} de ${this.pagination.total} registros`;

                                // Update controls
                                let controlsHtml = '';

                                // Previous button
                                if (this.pagination.current_page > 1) {
                                    controlsHtml += `<li class="page-item"><a class="page-link" href="#" onclick="app.loadRecords(${this.pagination.current_page - 1})">Anterior</a></li>`;
                                }

                                // Page numbers
                                for (let i = 1; i <= this.pagination.last_page; i++) {
                                    const activeClass = i === this.pagination.current_page ? 'active' : '';
                                    controlsHtml += `<li class="page-item ${activeClass}"><a class="page-link" href="#" onclick="app.loadRecords(${i})">${i}</a></li>`;
                                }

                                // Next button
                                if (this.pagination.current_page < this.pagination.last_page) {
                                    controlsHtml += `<li class="page-item"><a class="page-link" href="#" onclick="app.loadRecords(${this.pagination.current_page + 1})">Siguiente</a></li>`;
                                }

                                paginationControls.innerHTML = controlsHtml;
                            } else {
                                paginationSection.style.display = 'none';
                            }
                        },
                        getEstadoBadge(estado) {
                            const badges = {
                                'BORRADOR': '<span class="badge badge-secondary">Borrador</span>',
                                'CONFIRMADO': '<span class="badge badge-success">Confirmado</span>',
                                'ANULADO': '<span class="badge badge-danger">Anulado</span>'
                            };
                            return badges[estado] || `<span class="badge badge-light">${estado}</span>`;
                        },
                        formatDate(dateString) {
                            if (!dateString) return 'N/A';
                            const date = new Date(dateString);
                            return date.toLocaleDateString('es-ES');
                        },
                        formatDateTime(dateString) {
                            if (!dateString) return 'N/A';
                            const date = new Date(dateString);
                            return date.toLocaleString('es-ES');
                        },
                        formatNumber(number) {
                            if (!number) return '0.00';
                            return parseFloat(number).toLocaleString('es-ES', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    },
                    computed: {
                        paginationStart() {
                            return ((this.pagination.current_page - 1) * this.pagination.per_page) + 1;
                        },
                        paginationEnd() {
                            return Math.min((this.pagination.current_page * this.pagination.per_page), this.pagination.total);
                        }
                    }
                });
            } catch (error) {
                console.error('Error initializing Vue:', error);
                const vueStatus = document.getElementById('vue-status');
                if (vueStatus) vueStatus.textContent = 'Error en Vue: ' + error.message;
            }
        } else {
            console.error('Vue is not defined!');
            const vueStatus = document.getElementById('vue-status');
            const filtersStatus = document.getElementById('filters-status');
            if (vueStatus) vueStatus.textContent = 'Vue NO está disponible';
            if (filtersStatus) filtersStatus.textContent = 'Usando JavaScript básico';

            // Fallback con JavaScript básico
            document.getElementById('search-button').addEventListener('click', function() {
                console.log('Search clicked (basic JS)');
                var searchValue = document.getElementById('search-input').value;
                console.log('Search value:', searchValue);
            });
        }
    });
</script>
@endpush
