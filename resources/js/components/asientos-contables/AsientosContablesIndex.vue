<template>
    <div>
        <!-- Filtros -->
        <div class="row mb-1">
            <div class="col-md-3">
                <input type="text"
                       v-model="filters.search"
                       @input="searchRecords"
                       class="form-control"
                       placeholder="Buscar por número o concepto">
            </div>
            <div class="col-md-2">
                <input type="date"
                       v-model="filters.fecha_inicio"
                       @change="searchRecords"
                       class="form-control"
                       placeholder="Fecha inicio">
            </div>
            <div class="col-md-2">
                <input type="date"
                       v-model="filters.fecha_fin"
                       @change="searchRecords"
                       class="form-control"
                       placeholder="Fecha fin">
            </div>
            <div class="col-md-2">
                <select v-model="filters.tipo_comprobante_id" @change="searchRecords" class="form-control">
                    <option value="">Todos los tipos</option>
                    <option v-for="tipo in tiposComprobantes" :key="tipo.id" :value="tipo.id">
                        {{ tipo.nombre }}
                    </option>
                </select>
            </div>
            <div class="col-md-2">
                <select v-model="filters.estado" @change="searchRecords" class="form-control">
                    <option value="">Todos los estados</option>
                    <option v-for="estado in estados" :key="estado.value" :value="estado.value">
                        {{ estado.text }}
                    </option>
                </select>
            </div>
            <div class="col-md-1">
                <button @click="searchRecords" class="btn btn-primary btn-block" :disabled="loading">
                    <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                    <i v-else class="fas fa-search"></i>
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-4">
            <div class="spinner-border" role="status">
                <span class="sr-only">Cargando...</span>
            </div>
            <div class="mt-2">Cargando asientos contables...</div>
        </div>

        <!-- Tabla -->
        <div v-show="!loading">
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
                            <th>Eliminado</th>
                            <th>Creado por</th>
                            <th width="120">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="records.length === 0">
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-info-circle text-muted"></i>
                                No se encontraron asientos contables
                            </td>
                        </tr>
                        <tr v-for="record in records" :key="record.id">
                            <td>{{ record.numero_comprobante }}</td>
                            <td>{{ formatDate(record.fecha_asiento) }}</td>
                            <td>{{ record.tipo_comprobante ? record.tipo_comprobante.nombre : 'N/A' }}</td>
                            <td>{{ record.concepto }}</td>
                            <td class="text-right">${{ formatNumber(record.total_debito) }}</td>
                            <td>
                                <span v-if="record.estado === 'BORRADOR'" class="badge" style="background-color:#ffc107;color:#000">Borrador</span>
                                <span v-else-if="record.estado === 'CONFIRMADO'" class="badge badge-success">Confirmado</span>
                                <span v-else-if="record.estado === 'ANULADO'" class="badge badge-danger">Anulado</span>
                                <span v-else class="badge badge-light">{{ record.estado }}</span>
                            </td>
                            <td>
                                <span v-if="record.deleted_at" class="badge badge-danger">Eliminado</span>
                                <span v-else class="badge badge-light">—</span>
                            </td>
                            <td>{{ record.usuario_creacion ? record.usuario_creacion.name : 'N/A' }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a :href="'/contabilidad/asientos-contables/' + record.id" class="btn btn-primary btn-sm" title="Ver">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <a :href="`/contabilidad/asientos-contables/${record.id}/imprimir`" target="_blank" class="btn btn-info btn-sm" title="Imprimir">
                                        <i class="fa fa-print"></i>
                                    </a>
                                    <a :href="'/contabilidad/asientos-contables/' + record.id + '/edit'"
                                       class="btn btn-warning btn-sm"
                                       :class="{ disabled: (record.estado !== 'BORRADOR') || !!record.deleted_at }"
                                       :title="(record.estado !== 'BORRADOR' || record.deleted_at) ? 'Edición no permitida' : 'Editar'">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div v-if="pagination.total > 0" class="d-flex justify-content-between align-items-center">
                <div>
                    Mostrando {{ paginationStart }} a {{ paginationEnd }} de {{ pagination.total }} registros
                </div>
                <nav>
                    <ul class="pagination pagination-sm">
                        <li class="page-item" :class="{ disabled: pagination.current_page <= 1 }">
                            <a class="page-link" href="#" @click.prevent="loadRecords(pagination.current_page - 1)">Anterior</a>
                        </li>
                        <li v-for="(page, index) in visiblePages" :key="'page-' + index" class="page-item" :class="{ active: page === pagination.current_page }">
                            <a class="page-link" href="#" @click.prevent="loadRecords(page)">{{ page }}</a>
                        </li>
                        <li class="page-item" :class="{ disabled: pagination.current_page >= pagination.last_page }">
                            <a class="page-link" href="#" @click.prevent="loadRecords(pagination.current_page + 1)">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'AsientosContablesIndex',
    data() {
        return {
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
            ],
            searchTimeout: null
        };
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
                    this.tiposComprobantes = response.data.data;
                } else {
                    this.tiposComprobantes = [];
                }
            } catch (error) {
                this.tiposComprobantes = [];
            }
        },
        async loadRecords(page = 1) {
            if (page < 1) page = 1;
            if (page > this.pagination.last_page && this.pagination.last_page > 0) page = this.pagination.last_page;

            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    ...this.filters
                });

                const response = await axios.get('/contabilidad/asientos-contables/records?' + params);

                if (response.data.success) {
                    this.records = response.data.data.records || [];
                    this.pagination = response.data.data.pagination || {
                        total: 0,
                        current_page: 1,
                        per_page: 10,
                        last_page: 1
                    };
                } else {
                    this.records = [];
                    this.pagination = {
                        total: 0,
                        current_page: 1,
                        per_page: 10,
                        last_page: 1
                    };
                }
            } catch (error) {
                this.records = [];
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', 'Error al cargar los asientos contables', 'error');
                } else {
                    alert('Error al cargar los asientos contables');
                }
            } finally {
                this.loading = false;
            }
        },
        searchRecords() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.loadRecords(1);
            }, 300);
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
            if (this.pagination.total === 0) return 0;
            return ((this.pagination.current_page - 1) * this.pagination.per_page) + 1;
        },
        paginationEnd() {
            return Math.min((this.pagination.current_page * this.pagination.per_page), this.pagination.total);
        },
        visiblePages() {
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;

            if (last <= 1) return [1];

            const delta = 2;
            const pages = new Set();

            // Siempre mostrar la primera página
            pages.add(1);

            // Añadir páginas alrededor de la actual
            for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
                pages.add(i);
            }

            // Siempre mostrar la última página
            if (last > 1) {
                pages.add(last);
            }

            return Array.from(pages).sort((a, b) => a - b);
        }
    }
};
</script>
