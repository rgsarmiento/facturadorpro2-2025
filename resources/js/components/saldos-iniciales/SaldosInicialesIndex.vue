<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <i class="fas fa-balance-scale"></i> Saldos Iniciales
            </h2>
            <div class="actions">
                <button @click="showCreateModal" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Cargar Saldos
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row mb-3">
            <div class="col-md-4">
                <select v-model="filters.period_id" @change="loadRecords" class="form-control">
                    <option value="">Todos los períodos</option>
                    <option v-for="period in periods" :key="period.id" :value="period.id">
                        {{ getMonthName(period.month) }} {{ period.year }}
                    </option>
                </select>
            </div>
            <div class="col-md-3">
                <select v-model="filters.status" @change="loadRecords" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="draft">Borrador</option>
                    <option value="posted">Contabilizado</option>
                </select>
            </div>
        </div>

        <!-- Alert de validación -->
        <div v-if="records.length > 0 && !loading" class="alert"
             :class="{'alert-success': isBalanced, 'alert-danger': !isBalanced}">
            <div class="row">
                <div class="col-md-3">
                    <strong>Total Débito:</strong> ${{ formatNumber(totalDebito) }}
                </div>
                <div class="col-md-3">
                    <strong>Total Crédito:</strong> ${{ formatNumber(totalCredito) }}
                </div>
                <div class="col-md-3">
                    <strong>Diferencia:</strong> ${{ formatNumber(diferencia) }}
                </div>
                <div class="col-md-3 text-right">
                    <span v-if="isBalanced" class="badge badge-success">
                        <i class="fas fa-check"></i> Balanceado
                    </span>
                    <span v-else class="badge badge-danger">
                        <i class="fas fa-times"></i> No Balanceado
                    </span>
                </div>
            </div>
        </div>

        <!-- Botón de contabilizar -->
        <div v-if="hasDraftBalances" class="mb-3 text-right">
            <button @click="showPostModal" class="btn btn-success" :disabled="!isBalanced">
                <i class="fas fa-check-circle"></i> Contabilizar Saldos
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Cargando...</span>
            </div>
            <div class="mt-2">Cargando saldos...</div>
        </div>

        <!-- Tabla -->
        <div v-show="!loading" class="table-responsive">
            <table class="table table-striped table-sm">
                <thead class="thead-light">
                    <tr>
                        <th>Cuenta</th>
                        <th>Tercero</th>
                        <th>Período</th>
                        <th class="text-right">Débito</th>
                        <th class="text-right">Crédito</th>
                        <th class="text-right">Balance</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th width="100">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="records.length === 0">
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="fas fa-info-circle"></i> No hay saldos iniciales registrados
                        </td>
                    </tr>
                    <tr v-for="record in records" :key="record.id">
                        <td>
                            <strong>{{ record.cuenta_contable.codigo }}</strong><br>
                            <small>{{ record.cuenta_contable.nombre }}</small>
                        </td>
                        <td>
                            <span v-if="record.tercero">
                                {{ record.tercero.number }}<br>
                                <small>{{ record.tercero.name }}</small>
                            </span>
                            <span v-else class="text-muted">—</span>
                        </td>
                        <td>
                            <span v-if="record.period">
                                {{ getMonthName(record.period.month) }} {{ record.period.year }}
                            </span>
                        </td>
                        <td class="text-right">${{ formatNumber(record.debito) }}</td>
                        <td class="text-right">${{ formatNumber(record.credito) }}</td>
                        <td class="text-right">
                            <strong>${{ formatNumber(record.balance) }}</strong>
                        </td>
                        <td>
                            <span v-if="record.status === 'draft'" class="badge badge-warning">Borrador</span>
                            <span v-else-if="record.status === 'posted'" class="badge badge-success">Contabilizado</span>
                            <span v-else class="badge badge-secondary">{{ record.status }}</span>
                        </td>
                        <td>{{ formatDate(record.balance_date) }}</td>
                        <td>
                            <button v-if="record.status === 'draft'"
                                    @click="deleteBalance(record)"
                                    class="btn btn-danger btn-sm"
                                    title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                            <button v-if="record.asiento_contable_id"
                                    @click="viewVoucher(record.asiento_contable_id)"
                                    class="btn btn-info btn-sm"
                                    title="Ver asiento">
                                <i class="fas fa-file-invoice"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
                <tfoot v-if="records.length > 0" class="thead-light">
                    <tr>
                        <th colspan="3" class="text-right">TOTALES:</th>
                        <th class="text-right">${{ formatNumber(totalDebito) }}</th>
                        <th class="text-right">${{ formatNumber(totalCredito) }}</th>
                        <th class="text-right">${{ formatNumber(Math.abs(totalDebito - totalCredito)) }}</th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Modal: Cargar Saldos -->
        <div class="modal fade" id="modalCreateBalances" tabindex="-1" role="dialog" aria-labelledby="modalCreateBalancesLabel" aria-hidden="true" data-backdrop="static">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCreateBalancesLabel">
                            <i class="fas fa-plus"></i> Cargar Saldos Iniciales
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Información general -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label>Período <span class="text-danger">*</span></label>
                                <select v-model="form.period_id" class="form-control">
                                    <option value="">Seleccione...</option>
                                    <option v-for="period in periods" :key="period.id" :value="period.id">
                                        {{ getMonthName(period.month) }} {{ period.year }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Fecha de Saldos <span class="text-danger">*</span></label>
                                <input type="date" v-model="form.balance_date" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <button @click="addBalanceLine" class="btn btn-success btn-sm mt-4">
                                    <i class="fas fa-plus"></i> Agregar Línea
                                </button>
                            </div>
                        </div>

                        <!-- Validación en tiempo real -->
                        <div class="alert" :class="{'alert-success': formIsBalanced, 'alert-danger': !formIsBalanced}">
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Débito:</strong> ${{ formatNumber(formTotalDebito) }}
                                </div>
                                <div class="col-md-3">
                                    <strong>Crédito:</strong> ${{ formatNumber(formTotalCredito) }}
                                </div>
                                <div class="col-md-3">
                                    <strong>Diferencia:</strong> ${{ formatNumber(formDiferencia) }}
                                </div>
                                <div class="col-md-3">
                                    <span v-if="formIsBalanced" class="badge badge-success">
                                        <i class="fas fa-check"></i> Balanceado
                                    </span>
                                    <span v-else class="badge badge-danger">
                                        <i class="fas fa-times"></i> No Balanceado
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de saldos -->
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light" style="position: sticky; top: 0;">
                                    <tr>
                                        <th width="150">Cuenta *</th>
                                        <th width="150">Tercero</th>
                                        <th width="120">Débito *</th>
                                        <th width="120">Crédito *</th>
                                        <th>Notas</th>
                                        <th width="50"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(line, index) in form.balances" :key="index">
                                        <td>
                                            <input type="text"
                                                   v-model="line.cuenta_contable_codigo"
                                                   @blur="searchCuenta(index)"
                                                   class="form-control form-control-sm"
                                                   placeholder="Código cuenta">
                                            <small v-if="line.cuenta_nombre" class="text-muted">{{ line.cuenta_nombre }}</small>
                                        </td>
                                        <td>
                                            <input type="text"
                                                   v-model="line.person_number"
                                                   @blur="searchTercero(index)"
                                                   class="form-control form-control-sm"
                                                   placeholder="NIT/CC">
                                            <small v-if="line.person_name" class="text-muted">{{ line.person_name }}</small>
                                        </td>
                                        <td>
                                            <input type="number"
                                                   v-model.number="line.debito"
                                                   @input="calculateTotals"
                                                   class="form-control form-control-sm text-right"
                                                   step="0.01"
                                                   min="0">
                                        </td>
                                        <td>
                                            <input type="number"
                                                   v-model.number="line.credito"
                                                   @input="calculateTotals"
                                                   class="form-control form-control-sm text-right"
                                                   step="0.01"
                                                   min="0">
                                        </td>
                                        <td>
                                            <input type="text"
                                                   v-model="line.notes"
                                                   class="form-control form-control-sm"
                                                   placeholder="Notas">
                                        </td>
                                        <td>
                                            <button @click="removeBalanceLine(index)"
                                                    class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="formErrors.length > 0" class="alert alert-danger mt-3">
                            <ul class="mb-0">
                                <li v-for="(error, index) in formErrors" :key="index">{{ error }}</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" @click="saveBalances" class="btn btn-primary" :disabled="saving || !formIsBalanced">
                            <i v-if="saving" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-save"></i>
                            {{ saving ? 'Guardando...' : 'Guardar Saldos' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Contabilizar Saldos -->
        <div class="modal fade" id="modalPostBalances" tabindex="-1" role="dialog" aria-labelledby="modalPostBalancesLabel" aria-hidden="true" data-backdrop="static">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="modalPostBalancesLabel">
                            <i class="fas fa-check-circle"></i> Contabilizar Saldos Iniciales
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Importante:</strong> Esta acción generará un asiento contable de apertura automáticamente y actualizará los saldos de todas las cuentas.
                        </div>
                        <div class="form-group">
                            <label>Período <span class="text-danger">*</span></label>
                            <select v-model="postForm.period_id" class="form-control">
                                <option value="">Seleccione...</option>
                                <option v-for="period in periods" :key="period.id" :value="period.id">
                                    {{ getMonthName(period.month) }} {{ period.year }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fecha del Asiento <span class="text-danger">*</span></label>
                            <input type="date" v-model="postForm.fecha_asiento" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Concepto <span class="text-danger">*</span></label>
                            <textarea v-model="postForm.concepto"
                                      class="form-control"
                                      rows="3"
                                      placeholder="Asiento de apertura - Saldos iniciales"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" @click="postBalances" class="btn btn-success" :disabled="posting">
                            <i v-if="posting" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-check"></i>
                            {{ posting ? 'Contabilizando...' : 'Contabilizar' }}
                        </button>
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
            periods: [],
            loading: false,
            saving: false,
            posting: false,
            filters: {
                period_id: '',
                status: ''
            },
            form: {
                period_id: '',
                balance_date: new Date().toISOString().split('T')[0],
                balances: []
            },
            postForm: {
                period_id: '',
                fecha_asiento: new Date().toISOString().split('T')[0],
                concepto: 'Asiento de apertura - Saldos iniciales'
            },
            formErrors: [],
            monthNames: [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ]
        }
    },
    computed: {
        totalDebito() {
            return this.records.reduce((sum, r) => sum + parseFloat(r.debito || 0), 0);
        },
        totalCredito() {
            return this.records.reduce((sum, r) => sum + parseFloat(r.credito || 0), 0);
        },
        diferencia() {
            return Math.abs(this.totalDebito - this.totalCredito);
        },
        isBalanced() {
            return this.diferencia < 0.01;
        },
        hasDraftBalances() {
            return this.records.some(r => r.status === 'draft');
        },
        formTotalDebito() {
            return this.form.balances.reduce((sum, b) => sum + parseFloat(b.debito || 0), 0);
        },
        formTotalCredito() {
            return this.form.balances.reduce((sum, b) => sum + parseFloat(b.credito || 0), 0);
        },
        formDiferencia() {
            return Math.abs(this.formTotalDebito - this.formTotalCredito);
        },
        formIsBalanced() {
            return this.formDiferencia < 0.01 && this.form.balances.length > 0;
        }
    },
    mounted() {
        this.loadPeriods();
        this.loadRecords();
    },
    methods: {
        loadPeriods() {
            axios.get('/contabilidad/periodos-contables/records')
                .then(response => {
                    this.periods = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        loadRecords() {
            this.loading = true;
            const params = new URLSearchParams();
            if (this.filters.period_id) params.append('period_id', this.filters.period_id);
            if (this.filters.status) params.append('status', this.filters.status);

            axios.get(`/contabilidad/saldos-iniciales/records?${params.toString()}`)
                .then(response => {
                    this.records = response.data.data;
                })
                .catch(error => {
                    console.error(error);
                    this.$message.error('Error al cargar saldos');
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        showCreateModal() {
            this.form = {
                period_id: '',
                balance_date: new Date().toISOString().split('T')[0],
                balances: [this.createEmptyLine()]
            };
            this.formErrors = [];
            $('#modalCreateBalances').modal('show');
        },
        createEmptyLine() {
            return {
                cuenta_contable_codigo: '',
                cuenta_nombre: '',
                person_number: '',
                person_name: '',
                debito: 0,
                credito: 0,
                notes: ''
            };
        },
        addBalanceLine() {
            this.form.balances.push(this.createEmptyLine());
        },
        removeBalanceLine(index) {
            this.form.balances.splice(index, 1);
            this.calculateTotals();
        },
        calculateTotals() {
            // Trigger reactivity
            this.$forceUpdate();
        },
        searchCuenta(index) {
            const codigo = this.form.balances[index].cuenta_contable_codigo;
            if (!codigo) return;

            axios.get(`/contabilidad/cuentas-contables/${codigo}`)
                .then(response => {
                    this.form.balances[index].cuenta_nombre = response.data.data.nombre;
                })
                .catch(() => {
                    this.form.balances[index].cuenta_nombre = '';
                });
        },
        searchTercero(index) {
            const number = this.form.balances[index].person_number;
            if (!number) {
                this.form.balances[index].person_name = '';
                return;
            }

            axios.get(`/contabilidad/terceros/${number}`)
                .then(response => {
                    this.form.balances[index].person_name = response.data.data.name;
                })
                .catch(() => {
                    this.form.balances[index].person_name = '';
                });
        },
        saveBalances() {
            this.formErrors = [];

            if (!this.form.period_id) {
                this.formErrors.push('Debe seleccionar un período');
                return;
            }

            if (!this.form.balance_date) {
                this.formErrors.push('Debe especificar la fecha de saldos');
                return;
            }

            if (this.form.balances.length === 0) {
                this.formErrors.push('Debe agregar al menos un saldo');
                return;
            }

            if (!this.formIsBalanced) {
                this.formErrors.push('Los saldos no están balanceados (Débito ≠ Crédito)');
                return;
            }

            this.saving = true;
            axios.post('/contabilidad/saldos-iniciales', this.form)
                .then(response => {
                    this.$message.success('Saldos guardados exitosamente');
                    $('#modalCreateBalances').modal('hide');
                    this.loadRecords();
                })
                .catch(error => {
                    if (error.response && error.response.data.message) {
                        this.formErrors.push(error.response.data.message);
                    } else {
                        this.formErrors.push('Error al guardar saldos');
                    }
                })
                .finally(() => {
                    this.saving = false;
                });
        },
        showPostModal() {
            if (!this.isBalanced) {
                this.$message.warning('Los saldos deben estar balanceados antes de contabilizar');
                return;
            }

            this.postForm = {
                period_id: this.filters.period_id || '',
                fecha_asiento: new Date().toISOString().split('T')[0],
                concepto: 'Asiento de apertura - Saldos iniciales ' + new Date().getFullYear()
            };
            $('#modalPostBalances').modal('show');
        },
        postBalances() {
            if (!this.postForm.period_id || !this.postForm.fecha_asiento || !this.postForm.concepto) {
                this.$message.error('Todos los campos son obligatorios');
                return;
            }

            this.posting = true;
            axios.post('/contabilidad/saldos-iniciales/post', this.postForm)
                .then(response => {
                    this.$message.success('Saldos contabilizados exitosamente');
                    $('#modalPostBalances').modal('hide');
                    this.loadRecords();
                })
                .catch(error => {
                    this.$message.error(error.response?.data?.message || 'Error al contabilizar');
                })
                .finally(() => {
                    this.posting = false;
                });
        },
        deleteBalance(record) {
            this.$confirm('¿Está seguro de eliminar este saldo inicial?', 'Confirmar', {
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                type: 'warning'
            }).then(() => {
                axios.delete(`/contabilidad/saldos-iniciales/${record.id}`)
                    .then(() => {
                        this.$message.success('Saldo eliminado exitosamente');
                        this.loadRecords();
                    })
                    .catch(error => {
                        this.$message.error(error.response?.data?.message || 'Error al eliminar');
                    });
            });
        },
        viewVoucher(asientoId) {
            // Redirigir a la vista del asiento
            window.location.href = `/contabilidad/asientos-contables/${asientoId}`;
        },
        getMonthName(month) {
            return this.monthNames[month - 1];
        },
        formatDate(date) {
            if (!date) return '—';
            return new Date(date).toLocaleDateString('es-CO');
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
