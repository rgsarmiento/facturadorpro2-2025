<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a></h2>
            <ol class="breadcrumbs">
                <li><a href="/dashboard">Inicio</a></li>
                <li><a href="#" @click.prevent>Contabilidad</a></li>
                <li class="active"><span>Períodos Contables</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <button type="button" class="btn btn-custom btn-sm mt-2 mr-2" @click.prevent="prepareCreateModal">
                    <i class="fa fa-plus-circle"></i> Nuevo Período
                </button>
            </div>
        </div>

        <div class="card mb-0">
            <div class="card-header bg-info">
                <h3 class="my-0">Gestión de Períodos Contables</h3>
            </div>
            <div class="card-body">

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
        </div>
        </div>

        <!-- Modal: Crear Período -->
        <el-dialog
            title="Crear Nuevo Período"
            :visible.sync="showCreateModal"
            width="500px"
            :close-on-click-modal="false">
            <el-form label-position="top">
                <el-form-item label="Año" required>
                    <input type="number" v-model="form.year" class="form-control"
                           min="2000" max="2100" placeholder="2025">
                </el-form-item>
                <el-form-item label="Mes" required>
                    <select v-model="form.month" class="form-control">
                        <option value="">Seleccione...</option>
                        <option v-for="(name, index) in monthNames" :key="index" :value="index + 1">
                            {{ name }}
                        </option>
                    </select>
                </el-form-item>
                <div v-if="formErrors.length > 0" class="alert alert-danger">
                    <ul class="mb-0">
                        <li v-for="(error, index) in formErrors" :key="index">{{ error }}</li>
                    </ul>
                </div>
            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button @click="showCreateModal = false">Cancelar</el-button>
                <el-button type="primary" @click="createPeriod" :loading="saving">
                    {{ saving ? 'Guardando...' : 'Crear Período' }}
                </el-button>
            </div>
        </el-dialog>

        <!-- Modal: Detalles del Período -->
        <el-dialog
            title="Detalles del Período"
            :visible.sync="showDetailsModal"
            width="800px"
            :close-on-click-modal="false">
            <div v-if="selectedRecord">
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
            <div slot="footer" class="dialog-footer">
                <el-button @click="showDetailsModal = false">Cerrar</el-button>
            </div>
        </el-dialog>
    </div>
</template>

<script>
export default {
    data() {
        return {
            records: [],
            loading: false,
            saving: false,
            showCreateModal: false,
            showDetailsModal: false,
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
        prepareCreateModal() {
            this.form = {
                year: new Date().getFullYear(),
                month: new Date().getMonth() + 1
            };
            this.formErrors = [];
            this.showCreateModal = true;
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
                    this.showCreateModal = false;
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
            this.showDetailsModal = true;
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
