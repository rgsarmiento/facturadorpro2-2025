<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <i class="fas fa-calendar-alt"></i> Períodos Contables
            </h2>
            <div class="actions">
                <button @click="showCreateModal" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nuevo Período
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row mb-3">
            <div class="col-md-3">
                <select v-model="filters.year" @change="loadRecords" class="form-control">
                    <option value="">Todos los años</option>
                    <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <select v-model="filters.status" @change="loadRecords" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="open">Abierto</option>
                    <option value="closed">Cerrado</option>
                    <option value="locked">Bloqueado</option>
                </select>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Cargando...</span>
            </div>
            <div class="mt-2">Cargando períodos...</div>
        </div>

        <!-- Tabla -->
        <div v-show="!loading" class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Período</th>
                        <th>Rango de Fechas</th>
                        <th>Estado</th>
                        <th>Saldos de Cierre</th>
                        <th>Cerrado Por</th>
                        <th>Fecha Cierre</th>
                        <th width="200">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="records.length === 0">
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="fas fa-info-circle"></i> No hay períodos registrados
                        </td>
                    </tr>
                    <tr v-for="record in records" :key="record.id">
                        <td>
                            <strong>{{ getMonthName(record.month) }} {{ record.year }}</strong>
                        </td>
                        <td>
                            {{ formatDate(record.start_date) }} - {{ formatDate(record.end_date) }}
                        </td>
                        <td>
                            <span v-if="record.status === 'open'" class="badge badge-success">
                                <i class="fas fa-unlock"></i> Abierto
                            </span>
                            <span v-else-if="record.status === 'closed'" class="badge badge-warning">
                                <i class="fas fa-lock"></i> Cerrado
                            </span>
                            <span v-else-if="record.status === 'locked'" class="badge badge-danger">
                                <i class="fas fa-lock"></i> Bloqueado
                            </span>
                        </td>
                        <td>
                            <div v-if="record.closing_debit_balance || record.closing_credit_balance">
                                <small>
                                    Débito: ${{ formatNumber(record.closing_debit_balance || 0) }}<br>
                                    Crédito: ${{ formatNumber(record.closing_credit_balance || 0) }}
                                </small>
                            </div>
                            <span v-else class="text-muted">—</span>
                        </td>
                        <td>
                            {{ record.usuario_cierre ? record.usuario_cierre.name : '—' }}
                        </td>
                        <td>
                            {{ record.closed_at ? formatDate(record.closed_at) : '—' }}
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <!-- Ver detalles -->
                                <button @click="showDetails(record)" class="btn btn-info" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </button>

                                <!-- Cerrar período (solo si está abierto) -->
                                <button v-if="record.status === 'open'"
                                        @click="closePeriod(record)"
                                        class="btn btn-warning"
                                        title="Cerrar período">
                                    <i class="fas fa-lock"></i>
                                </button>

                                <!-- Reabrir período (solo si está cerrado, no bloqueado) -->
                                <button v-if="record.status === 'closed'"
                                        @click="reopenPeriod(record)"
                                        class="btn btn-success"
                                        title="Reabrir período">
                                    <i class="fas fa-unlock"></i>
                                </button>

                                <!-- Bloquear permanentemente (solo si está cerrado) -->
                                <button v-if="record.status === 'closed'"
                                        @click="lockPeriod(record)"
                                        class="btn btn-danger"
                                        title="Bloquear permanentemente">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal: Crear Período -->
        <div class="modal fade" id="modalCreatePeriod" tabindex="-1" role="dialog" aria-labelledby="modalCreatePeriodLabel" aria-hidden="true" data-backdrop="static">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCreatePeriodLabel">
                            <i class="fas fa-plus"></i> Crear Nuevo Período
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Año <span class="text-danger">*</span></label>
                            <input type="number" v-model="form.year" class="form-control"
                                   min="2000" max="2100" placeholder="2025">
                        </div>
                        <div class="form-group">
                            <label>Mes <span class="text-danger">*</span></label>
                            <select v-model="form.month" class="form-control">
                                <option value="">Seleccione...</option>
                                <option v-for="(name, index) in monthNames" :key="index" :value="index + 1">
                                    {{ name }}
                                </option>
                            </select>
                        </div>
                        <div v-if="formErrors.length > 0" class="alert alert-danger">
                            <ul class="mb-0">
                                <li v-for="(error, index) in formErrors" :key="index">{{ error }}</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" @click="createPeriod" class="btn btn-primary" :disabled="saving">
                            <i v-if="saving" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-save"></i>
                            {{ saving ? 'Guardando...' : 'Crear Período' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Detalles del Período -->
        <div class="modal fade" id="modalDetails" tabindex="-1" role="dialog" aria-labelledby="modalDetailsLabel" aria-hidden="true" data-backdrop="static">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalDetailsLabel">
                            <i class="fas fa-info-circle"></i> Detalles del Período
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" v-if="selectedRecord">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Información General</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th>Período:</th>
                                        <td>{{ getMonthName(selectedRecord.month) }} {{ selectedRecord.year }}</td>
                                    </tr>
                                    <tr>
                                        <th>Fecha Inicio:</th>
                                        <td>{{ formatDate(selectedRecord.start_date) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Fecha Fin:</th>
                                        <td>{{ formatDate(selectedRecord.end_date) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Estado:</th>
                                        <td>
                                            <span v-if="selectedRecord.status === 'open'" class="badge badge-success">Abierto</span>
                                            <span v-else-if="selectedRecord.status === 'closed'" class="badge badge-warning">Cerrado</span>
                                            <span v-else class="badge badge-danger">Bloqueado</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Permite Modificaciones:</th>
                                        <td>
                                            <span v-if="selectedRecord.allow_modifications" class="badge badge-success">Sí</span>
                                            <span v-else class="badge badge-danger">No</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Información de Cierre</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th>Cerrado Por:</th>
                                        <td>{{ selectedRecord.usuario_cierre ? selectedRecord.usuario_cierre.name : '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Fecha Cierre:</th>
                                        <td>{{ selectedRecord.closed_at ? formatDate(selectedRecord.closed_at) : '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Saldo Débito:</th>
                                        <td>${{ formatNumber(selectedRecord.closing_debit_balance || 0) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Saldo Crédito:</th>
                                        <td>${{ formatNumber(selectedRecord.closing_credit_balance || 0) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Notas de Cierre:</th>
                                        <td>{{ selectedRecord.closing_notes || '—' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            records: [],
            loading: false,
            saving: false,
            filters: {
                year: new Date().getFullYear(),
                status: ''
            },
            form: {
                year: new Date().getFullYear(),
                month: new Date().getMonth() + 1
            },
            formErrors: [],
            selectedRecord: null,
            monthNames: [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ]
        }
    },
    computed: {
        years() {
            const currentYear = new Date().getFullYear();
            const years = [];
            for (let i = currentYear - 5; i <= currentYear + 2; i++) {
                years.push(i);
            }
            return years;
        }
    },
    mounted() {
        this.loadRecords();
    },
    methods: {
        loadRecords() {
            this.loading = true;
            const params = new URLSearchParams();
            if (this.filters.year) params.append('year', this.filters.year);
            if (this.filters.status) params.append('status', this.filters.status);

            axios.get(`/contabilidad/periodos-contables/records?${params.toString()}`)
                .then(response => {
                    this.records = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                    this.$message.error('Error al cargar períodos');
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        showCreateModal() {
            this.form = {
                year: new Date().getFullYear(),
                month: new Date().getMonth() + 1
            };
            this.formErrors = [];
            $('#modalCreatePeriod').modal('show');
        },
        createPeriod() {
            this.formErrors = [];

            if (!this.form.year || !this.form.month) {
                this.formErrors.push('Año y mes son obligatorios');
                return;
            }

            this.saving = true;
            axios.post('/contabilidad/periodos-contables', this.form)
                .then(response => {
                    this.$message.success('Período creado exitosamente');
                    $('#modalCreatePeriod').modal('hide');
                    this.loadRecords();
                })
                .catch(error => {
                    if (error.response && error.response.data.message) {
                        this.formErrors.push(error.response.data.message);
                    } else {
                        this.formErrors.push('Error al crear período');
                    }
                })
                .finally(() => {
                    this.saving = false;
                });
        },
        closePeriod(record) {
            this.$confirm('¿Está seguro de cerrar este período? No se podrán crear más asientos en BORRADOR.', 'Cerrar Período', {
                confirmButtonText: 'Sí, cerrar',
                cancelButtonText: 'Cancelar',
                type: 'warning'
            }).then(() => {
                const notes = prompt('Notas de cierre (opcional):');
                axios.post(`/contabilidad/periodos-contables/${record.id}/close`, {
                    closing_notes: notes
                }).then(response => {
                    this.$message.success('Período cerrado exitosamente');
                    this.loadRecords();
                }).catch(error => {
                    this.$message.error(error.response?.data?.message || 'Error al cerrar período');
                });
            });
        },
        reopenPeriod(record) {
            this.$confirm('¿Está seguro de reabrir este período?', 'Reabrir Período', {
                confirmButtonText: 'Sí, reabrir',
                cancelButtonText: 'Cancelar',
                type: 'info'
            }).then(() => {
                axios.post(`/contabilidad/periodos-contables/${record.id}/reopen`)
                    .then(response => {
                        this.$message.success('Período reabierto exitosamente');
                        this.loadRecords();
                    })
                    .catch(error => {
                        this.$message.error(error.response?.data?.message || 'Error al reabrir período');
                    });
            });
        },
        lockPeriod(record) {
            this.$confirm('⚠️ ADVERTENCIA: Esta acción es IRREVERSIBLE. El período no podrá reabrirse nunca. ¿Continuar?', 'Bloquear Período', {
                confirmButtonText: 'Sí, bloquear permanentemente',
                cancelButtonText: 'Cancelar',
                type: 'error'
            }).then(() => {
                axios.post(`/contabilidad/periodos-contables/${record.id}/lock`)
                    .then(response => {
                        this.$message.success('Período bloqueado permanentemente');
                        this.loadRecords();
                    })
                    .catch(error => {
                        this.$message.error(error.response?.data?.message || 'Error al bloquear período');
                    });
            });
        },
        showDetails(record) {
            this.selectedRecord = record;
            $('#modalDetails').modal('show');
        },
        getMonthName(month) {
            return this.monthNames[month - 1];
        },
        formatDate(date) {
            if (!date) return '—';
            return new Date(date).toLocaleDateString('es-CO', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        },
        formatNumber(value) {
            return new Intl.NumberFormat('es-CO', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(value || 0);
        }
    }
}
</script>

<style scoped>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}
.page-header h2 {
    margin: 0;
    color: #495057;
}
</style>
