<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a></h2>
            <ol class="breadcrumbs">
                <li><a href="/dashboard">Inicio</a></li>
                <li><a href="#" @click.prevent>Contabilidad</a></li>
                <li class="active"><span>Saldos Iniciales</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <button type="button" class="btn btn-custom btn-sm mt-2 mr-2" @click.prevent="prepareCreateModal">
                    <i class="fa fa-plus-circle"></i> Cargar Saldos
                </button>
            </div>
        </div>

        <div class="card mb-0">
            <div class="card-header bg-info">
                <h3 class="my-0">Gestión de Saldos Iniciales</h3>
            </div>
            <div class="card-body">

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
            <button @click="preparePostModal" class="btn btn-success" :disabled="!isBalanced">
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
        </div>
        </div>

        <!-- Modal: Cargar Saldos usando Element UI -->
        <el-dialog
            title="Cargar Saldos Iniciales"
            :visible.sync="showCreateModal"
            width="90%"
            :close-on-click-modal="false">

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
                            <th width="250">Cuenta *</th>
                            <th width="250">Tercero</th>
                            <th width="150">Débito *</th>
                            <th width="150">Crédito *</th>
                            <th width="150">Notas</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(line, index) in form.balances" :key="index">
                            <td>
                                <el-select
                                    v-model="line.cuenta_contable_codigo"
                                    filterable
                                    remote
                                    reserve-keyword
                                    placeholder="Buscar cuenta..."
                                    :remote-method="(query) => searchCuentasRemote(query, index)"
                                    :loading="line.loadingCuentas"
                                    @change="onCuentaSelected(index)"
                                    @focus="onCuentaFocus(index)"
                                    size="small"
                                    style="width: 100%;">
                                    <el-option
                                        v-for="(cuenta, cuentaIdx) in line.cuentasOptions"
                                        :key="`cuenta-${index}-${cuentaIdx}`"
                                        :label="`${cuenta.codigo} - ${cuenta.nombre}`"
                                        :value="cuenta.codigo">
                                        <span style="float: left">{{ cuenta.codigo }}</span>
                                        <span style="float: right; color: #8492a6; font-size: 13px">{{ cuenta.nombre }}</span>
                                    </el-option>
                                </el-select>
                                <small v-if="line.cuenta_nombre" class="text-muted d-block mt-1">{{ line.cuenta_nombre }}</small>
                            </td>
                            <td>
                                <div v-if="line.requiere_tercero">
                                    <el-select
                                        v-model="line.person_number"
                                        filterable
                                        remote
                                        reserve-keyword
                                        placeholder="Buscar tercero..."
                                        :remote-method="(query) => searchTercerosRemote(query, index)"
                                        :loading="line.loadingTerceros"
                                        @change="onTerceroSelected(index)"
                                        @focus="onTerceroFocus(index)"
                                        size="small"
                                        clearable
                                        style="width: 100%;">
                                        <el-option
                                            v-for="(tercero, terceroIdx) in line.tercerosOptions"
                                            :key="`tercero-${index}-${terceroIdx}`"
                                            :label="`${tercero.number} - ${tercero.name}`"
                                            :value="tercero.number">
                                            <span style="float: left">{{ tercero.number }}</span>
                                            <span style="float: right; color: #8492a6; font-size: 13px">{{ tercero.name }}</span>
                                        </el-option>
                                    </el-select>
                                    <small v-if="line.person_name" class="text-muted d-block mt-1">
                                        {{ line.person_name }}
                                    </small>
                                    <small v-else class="text-danger d-block mt-1">
                                        <i class="fas fa-exclamation-circle"></i> Requerido
                                    </small>
                                </div>
                                <div v-else class="text-center text-muted py-2">
                                    <small>—</small>
                                </div>
                            </td>
                            <td>
                                <input type="number"
                                       v-model.number="line.debito"
                                       @input="onDebitoChange(index)"
                                       class="form-control form-control-sm text-right"
                                       step="0.01"
                                       min="0"
                                       placeholder="0.00">
                            </td>
                            <td>
                                <input type="number"
                                       v-model.number="line.credito"
                                       @input="onCreditoChange(index)"
                                       class="form-control form-control-sm text-right"
                                       step="0.01"
                                       min="0"
                                       placeholder="0.00">
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

            <div slot="footer" class="dialog-footer">
                <el-button @click="showCreateModal = false">Cancelar</el-button>
                <el-button type="primary" @click="saveBalances" :loading="saving" :disabled="!formIsBalanced">
                    {{ saving ? 'Guardando...' : 'Guardar Saldos' }}
                </el-button>
            </div>
        </el-dialog>

        <!-- Modal: Contabilizar Saldos usando Element UI -->
        <el-dialog
            title="Contabilizar Saldos Iniciales"
            :visible.sync="showPostModal"
            width="500px"
            :close-on-click-modal="false">

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

            <div slot="footer" class="dialog-footer">
                <el-button @click="showPostModal = false">Cancelar</el-button>
                <el-button type="success" @click="postBalances" :loading="posting">
                    {{ posting ? 'Contabilizando...' : 'Contabilizar' }}
                </el-button>
            </div>
        </el-dialog>
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
            showCreateModal: false,
            showPostModal: false,
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
                    console.log('Respuesta de records:', response.data);
                    console.log('Primer registro:', response.data.data[0]);
                    this.records = response.data.data;
                })
                .catch(error => {
                    console.error('Error al cargar records:', error);
                    this.$message.error('Error al cargar saldos');
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        prepareCreateModal() {
            this.form = {
                period_id: '',
                balance_date: new Date().toISOString().split('T')[0],
                balances: [this.createEmptyLine()]
            };
            this.formErrors = [];
            // Abrir modal usando Element UI
            this.showCreateModal = true;
        },
        createEmptyLine() {
            return {
                cuenta_contable_codigo: '',
                cuenta_nombre: '',
                requiere_tercero: false,
                person_number: '',
                person_name: '',
                debito: 0,
                credito: 0,
                notes: '',
                cuentasOptions: [],
                tercerosOptions: [],
                loadingCuentas: false,
                loadingTerceros: false
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
        onDebitoChange(index) {
            // Si se escribe en Débito, Crédito debe ser 0
            if (this.form.balances[index].debito && this.form.balances[index].debito > 0) {
                this.form.balances[index].credito = 0;
            }
            this.calculateTotals();
        },
        onCreditoChange(index) {
            // Si se escribe en Crédito, Débito debe ser 0
            if (this.form.balances[index].credito && this.form.balances[index].credito > 0) {
                this.form.balances[index].debito = 0;
            }
            this.calculateTotals();
        },
        searchCuentasRemote(query, index) {
            this.form.balances[index].loadingCuentas = true;
            axios.get('/contabilidad/cuentas-contables/search', {
                params: {
                    q: query || '',
                    limit: 20
                }
            })
            .then(response => {
                this.form.balances[index].cuentasOptions = response.data.data || [];
            })
            .catch(error => {
                console.error('Error al buscar cuentas:', error);
                this.form.balances[index].cuentasOptions = [];
            })
            .finally(() => {
                this.form.balances[index].loadingCuentas = false;
            });
        },
        onCuentaSelected(index) {
            const codigo = this.form.balances[index].cuenta_contable_codigo;
            if (codigo) {
                const cuenta = this.form.balances[index].cuentasOptions.find(c => c.codigo === codigo);
                if (cuenta) {
                    console.log('Cuenta seleccionada:', cuenta);
                    console.log('Requiere tercero:', cuenta.requiere_tercero);
                    
                    this.$set(this.form.balances[index], 'cuenta_nombre', cuenta.nombre);
                    this.$set(this.form.balances[index], 'requiere_tercero', cuenta.requiere_tercero || false);

                    // Si la cuenta no requiere tercero, limpiar el campo
                    if (!cuenta.requiere_tercero) {
                        this.$set(this.form.balances[index], 'person_number', '');
                        this.$set(this.form.balances[index], 'person_name', '');
                    }
                }
            }
        },
        searchTercerosRemote(query, index) {
            this.form.balances[index].loadingTerceros = true;
            axios.get('/contabilidad/terceros/search', {
                params: {
                    q: query || '',
                    limit: 20
                }
            })
            .then(response => {
                this.form.balances[index].tercerosOptions = response.data.data || [];
            })
            .catch(error => {
                console.error('Error al buscar terceros:', error);
                this.form.balances[index].tercerosOptions = [];
            })
            .finally(() => {
                this.form.balances[index].loadingTerceros = false;
            });
        },
        onTerceroSelected(index) {
            const number = this.form.balances[index].person_number;
            if (number) {
                const tercero = this.form.balances[index].tercerosOptions.find(t => t.number === number);
                if (tercero) {
                    this.form.balances[index].person_name = tercero.name;
                }
            } else {
                this.form.balances[index].person_name = '';
            }
        },
        onCuentaFocus(index) {
            // Cargar las primeras cuentas al hacer foco si no hay opciones cargadas
            if (this.form.balances[index].cuentasOptions.length === 0) {
                this.searchCuentasRemote('', index);
            }
        },
        onTerceroFocus(index) {
            // Cargar los primeros terceros al hacer foco si no hay opciones cargadas
            if (this.form.balances[index].tercerosOptions.length === 0) {
                this.searchTercerosRemote('', index);
            }
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

            // Validar que las cuentas que requieren tercero lo tengan
            for (let i = 0; i < this.form.balances.length; i++) {
                const line = this.form.balances[i];
                if (line.requiere_tercero && !line.person_number) {
                    this.formErrors.push(`La línea ${i + 1} (${line.cuenta_contable_codigo}) requiere un tercero`);
                }
                if (!line.cuenta_contable_codigo) {
                    this.formErrors.push(`La línea ${i + 1} requiere una cuenta contable`);
                }
            }

            if (this.formErrors.length > 0) {
                return;
            }

            if (!this.formIsBalanced) {
                this.formErrors.push('Los saldos no están balanceados (Débito ≠ Crédito)');
                return;
            }

            // Transformar datos para enviar al backend
            const dataToSend = {
                period_id: this.form.period_id,
                balance_date: this.form.balance_date,
                balances: this.form.balances.map(line => ({
                    cuenta_contable_codigo: line.cuenta_contable_codigo,
                    person_number: line.person_number || null,
                    debito: parseFloat(line.debito) || 0,
                    credito: parseFloat(line.credito) || 0,
                    notes: line.notes || null
                }))
            };

            console.log('Datos a enviar:', dataToSend);

            this.saving = true;
            axios.post('/contabilidad/saldos-iniciales', dataToSend)
                .then(response => {
                    console.log('Respuesta de guardar:', response.data);
                    this.$message.success('Saldos guardados exitosamente');
                    this.showCreateModal = false;
                    this.loadRecords();
                })
                .catch(error => {
                    console.error('Error al guardar:', error);
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
        preparePostModal() {
            if (!this.isBalanced) {
                this.$message.warning('Los saldos deben estar balanceados antes de contabilizar');
                return;
            }

            this.postForm = {
                period_id: this.filters.period_id || '',
                fecha_asiento: new Date().toISOString().split('T')[0],
                concepto: 'Asiento de apertura - Saldos iniciales ' + new Date().getFullYear()
            };

            // Abrir modal usando Element UI
            this.showPostModal = true;
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
                    this.showPostModal = false;
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
