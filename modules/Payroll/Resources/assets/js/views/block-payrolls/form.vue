<template>
    <div class="card mb-0 pt-2 pt-md-0">
        <div class="card-header bg-info">
            <h3 class="my-0 text-white">Generar Bloque de Nominas</h3>
        </div>
        <div class="card-body">
            <div class="invoice">
                <form autocomplete="off" @submit.prevent="submit">
                    <div class="form-body">
                        <!-- Formulario para datos de period y campos automáticos -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Fecha de emisión</label>
                                    <input type="text" class="form-control" :value="form.date_of_issue" disabled>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Hora de emisión</label>
                                    <input type="text" class="form-control" :value="form.time_of_issue" disabled>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Cantidad de trabajadores</label>
                                    <input type="number" class="form-control" :value="form.workers_quantity" disabled>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Total devengados</label>
                                    <input type="number" class="form-control" :value="form.accrued_total" disabled>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Total deducciones</label>
                                    <input type="number" class="form-control" :value="form.deductions_total" disabled>
                                </div>
                            </div>
                            <!-- Campos para period -->
                            <div class="col-md-3">
                                <div class="form-group" :class="{'has-danger': errors.period_start}">
                                    <label>Fecha de inicio de periodo<span class="text-danger"> *</span>
                                        <small v-if="editMode" class="text-muted">(No editable)</small>
                                    </label>
                                    <el-date-picker
                                        v-model="form.period_start"
                                        type="date"
                                        placeholder="Seleccione fecha"
                                        value-format="yyyy-MM-dd"
                                        format="yyyy-MM-dd"
                                        class="w-100"
                                        :disabled="editMode"
                                        @change="validatePeriodDates"
                                    ></el-date-picker>
                                    <small class="form-control-feedback" v-if="errors.period_start" v-text="errors.period_start[0]"></small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group" :class="{'has-danger': errors.period_end}">
                                    <label>Fecha de fin de periodo<span class="text-danger"> *</span>
                                        <small v-if="editMode" class="text-muted">(No editable)</small>
                                    </label>
                                    <el-date-picker
                                        v-model="form.period_end"
                                        type="date"
                                        placeholder="Seleccione fecha"
                                        value-format="yyyy-MM-dd"
                                        format="yyyy-MM-dd"
                                        class="w-100"
                                        :disabled="editMode"
                                        @change="validatePeriodDates"
                                    ></el-date-picker>
                                    <small v-if="periodDateError" class="text-danger">{{ periodDateError }}</small>
                                    <small class="form-control-feedback" v-if="errors.period_end" v-text="errors.period_end[0]"></small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group" :class="{'has-danger': errors.type_document_id}">
                                    <label class="control-label">Resolución<span class="text-danger"> *</span></label>
                                    <el-select @change="changeResolution" v-model="form.type_document_id" class="border-left rounded-left border-info">
                                        <el-option v-for="option in form.tables.resolutions" :key="option.id" :value="option.id" :label="`${option.prefix} / ${option.resolution_number ? option.resolution_number : ''} / ${option.from ? option.from : ''} / ${option.to ? option.to : ''}`"></el-option>
                                    </el-select>
                                    <small class="form-control-feedback" v-if="errors.type_document_id" v-text="errors.type_document_id[0]"></small>
                                </div>
                            </div>
                        </div>
                        <el-tabs v-model="activeName" @tab-click="handleTabChange">
                            <el-tab-pane label="Trabajadores Seleccionados" name="active-workers">
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center" style="width: 40px;"></th>
                                                        <th class="text-center" style="width: 50px;">#</th>
                                                        <th class="text-center" style="width: 150px;">Doc. Identidad</th>
                                                        <th class="text-center" style="width: 350px;">Nombre</th>
                                                        <th class="text-center" style="width: 250px;">Tipo Documento</th>
                                                        <th class="text-center" style="width: 120px;">Salario Basico</th>
                                                        <th class="text-center" style="width: 120px;">Telefono</th>
                                                        <th class="text-center" style="width: 200px;">Cargo</th>
                                                        <th class="text-right" style="width: 180px;">
                                                            Generar Provisiones
                                                            <el-switch
                                                                v-model="globalGenerateProvisions"
                                                                active-text=""
                                                                inactive-text=""
                                                                @change="handleGlobalSwitch"
                                                                style="margin-left: 8px;"
                                                            />
                                                        </th>
                                                        <th class="text-right" style="width: 120px;">Operaciones</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody v-if="form.items.length > 0">
                                                    <tr v-for="(row, index) in form.items" :key="index">
                                                        <td class="text-center">
                                                            <input type="radio"
                                                                   name="selectedWorker"
                                                                   :value="row.id"
                                                                   v-model="selectedWorkerId"
                                                                   @change="handleWorkerSelection(row.id)"
                                                            />
                                                        </td>
                                                        <td>{{index + 1}}</td>
                                                        <td>{{row.code}}</td>
                                                        <td class="text-left">{{row.search_fullname || row.fullname}}</td>
                                                        <td class="text-right">{{row.payroll_type_document_identification_name || 'N/A'}}</td>
                                                        <td class="text-right">{{getFormatDecimal(row.salary)}}</td>
                                                        <td class="text-right">{{row.cellphone}}</td>
                                                        <td class="text-right">{{row.position}}</td>
                                                        <td class="text-right">
                                                            <el-switch
                                                                v-model="row.generate_provisions"
                                                                :disabled="globalGenerateProvisions"
                                                            ></el-switch>
                                                        </td>
                                                        <td class="text-right">
                                                            <button type="button" class="btn waves-effect waves-light btn-xs btn-danger" @click.prevent="removeItem(index)">
                                                                x
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </el-tab-pane>
                            <el-tab-pane label="Periodo" name="period">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group" :class="{'has-danger': errors['period.admision_date']}">
                                            <label class="control-label">Fecha de admisión<span class="text-danger"> *</span>
                                                <el-tooltip class="item" effect="dark" content="Fecha de inicio de labores del empleado" placement="top-start">
                                                    <i class="fa fa-info-circle"></i>
                                                </el-tooltip>
                                            </label>
                                            <el-date-picker
                                                v-model="form.period.admision_date"
                                                type="date"
                                                value-format="yyyy-MM-dd"
                                                :clearable="false"
                                                :key="'date-' + selectedWorkerId"
                                                @change="handleAdmisionDateChange"
                                            ></el-date-picker>
                                            <small class="form-control-feedback" v-if="errors['period.admision_date']" v-text="errors['period.admision_date'][0]"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group" :class="{'has-danger': errors['period.worked_time']}">
                                            <label class="control-label">Días trabajados<span class="text-danger"> *</span></label>
                                            <el-input-number
                                                v-model="form.period.worked_time"
                                                :min="0"
                                                controls-position="right"
                                                :key="'number-' + selectedWorkerId"
                                                @change="handleWorkedTimeChange"
                                            ></el-input-number>
                                            <small class="form-control-feedback" v-if="errors['period.worked_time']" v-text="errors['period.worked_time'][0]"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group" :class="{'has-danger': errors.payroll_period_id}">
                                            <label class="control-label">Periodo de nómina<span class="text-danger"> *</span>
                                                <el-tooltip class="item" effect="dark" content="Frecuencia de pago" placement="top-start">
                                                    <i class="fa fa-info-circle"></i>
                                                </el-tooltip>
                                            </label>
                                            <el-select
                                                v-model="form.payroll_period_id"
                                                filterable
                                                class="border-left rounded-left border-info"
                                                @change="handlePayrollPeriodChange"
                                                placeholder="Seleccione periodo"
                                                :key="'select-' + selectedWorkerId"
                                            >
                                                <el-option v-for="option in form.tables.payroll_periods" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                            </el-select>
                                            <small class="form-control-feedback" v-if="errors.payroll_period_id" v-text="errors.payroll_period_id[0]"></small>
                                        </div>
                                    </div>
                                </div>
                            </el-tab-pane>
                            <el-tab-pane label="Pagos" name="payments">
                                <div class="row" v-show="selectedWorkerId">
                                    <div class="col-md-3">
                                        <div class="form-group" :class="{'has-danger': errors['payment.payment_method_id']}">
                                            <label class="control-label">Métodos de pago<span class="text-danger"> *</span></label>
                                            <el-select
                                                v-model="form.payment.payment_method_id"
                                                filterable
                                                @change="changePaymentMethod"
                                                :key="'payment-method-' + selectedWorkerId"
                                            >
                                                <el-option v-for="option in form.tables.payment_methods" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                            </el-select>
                                            <small class="form-control-feedback" v-if="errors['payment.payment_method_id']" v-text="errors['payment.payment_method_id'][0]"></small>
                                        </div>
                                    </div>

                                    <template v-if="show_inputs_payment_method">
                                        <div class="col-md-3">
                                            <div class="form-group" :class="{'has-danger': errors['payment.bank_name']}">
                                                <label class="control-label">Nombre del banco</label>
                                                <el-input
                                                    v-model="form.payment.bank_name"
                                                    :key="'bank-' + selectedWorkerId"
                                                    @input="saveCurrentEmployeePaymentData"
                                                    @blur="saveCurrentEmployeePaymentData"
                                                ></el-input>
                                                <small class="form-control-feedback" v-if="errors['payment.bank_name']" v-text="errors['payment.bank_name'][0]"></small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group" :class="{'has-danger': errors['payment.account_type']}">
                                                <label class="control-label">Tipo de cuenta</label>
                                                <el-input
                                                    v-model="form.payment.account_type"
                                                    :key="'account-type-' + selectedWorkerId"
                                                    @input="saveCurrentEmployeePaymentData"
                                                    @blur="saveCurrentEmployeePaymentData"
                                                ></el-input>
                                                <small class="form-control-feedback" v-if="errors['payment.account_type']" v-text="errors['payment.account_type'][0]"></small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group" :class="{'has-danger': errors['payment.account_number']}">
                                                <label class="control-label">Número de cuenta</label>
                                                <el-input
                                                    v-model="form.payment.account_number"
                                                    :key="'account-number-' + selectedWorkerId"
                                                    @input="saveCurrentEmployeePaymentData"
                                                    @blur="saveCurrentEmployeePaymentData"
                                                ></el-input>
                                                <small class="form-control-feedback" v-if="errors['payment.account_number']" v-text="errors['payment.account_number'][0]"></small>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <div class="form-group" :class="{'has-danger': errors['payment_dates']}">
                                            <h4>Fechas de pago<span class="text-danger"> *</span></h4>
                                            <small class="form-control-feedback" v-if="errors['payment_dates']" v-text="errors['payment_dates'][0]"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <table>
                                            <thead>
                                                <tr width="100%">
                                                    <th v-if="form.payment_dates.length>0" class="pb-2">Fecha<span class="text-danger"> *</span></th>
                                                    <th width="30%"><a href="#" @click.prevent="clickAddPaymentDate()" class="text-center font-weight-bold text-info pb-1 mt-1">[+ Agregar]</a></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(row, index) in form.payment_dates" :key="index">
                                                    <td>
                                                        <div class="form-group mb-2 mr-2">
                                                            <el-date-picker
                                                                v-model="row.payment_date"
                                                                type="date"
                                                                value-format="yyyy-MM-dd"
                                                                :clearable="false"
                                                                @change="handlePaymentDateChange"
                                                            ></el-date-picker>
                                                        </div>
                                                    </td>
                                                    <td class="series-table-actions text-center">
                                                        <button type="button" class="btn waves-effect waves-light btn-xs btn-danger" @click.prevent="clickCancelPaymentDate(index)">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </td>
                                                    <br>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </el-tab-pane>
                        </el-tabs>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-secondary mr-2" @click="cancelForm">
                                Cancelar
                            </button>
                            <button type="button" class="btn btn-warning mr-2" @click="saveForm(false)">
                                {{ editMode ? 'Editar sin generar' : 'Guardar sin generar' }}
                            </button>
                            <button type="button" class="btn btn-primary" @click="saveForm(true)">
                                {{ editMode ? 'Editar y generar' : 'Guardar y generar' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
    import {documentPayrollMixin} from '../../mixins/document_payroll'
    import {getValueIfNull} from '../../helpers/functions'

    export default {
        props: [],

        mixins: [documentPayrollMixin],

        components: {
        },

        data() {
            return {
                resource: 'payroll/block-payrolls',
                loading: false,
                loading_form: false,
                loading_submit: false,
                errors: {},
                editMode: false, // Nuevo flag para modo edición
                blockPayrollId: null, // ID del bloque en edición
                form: {
                    date_of_issue: '',
                    time_of_issue: '',
                    workers_quantity: 0,
                    accrued_total: 0,
                    deductions_total: 0,
                    period_start: '',
                    period_end: '',
                    period_type: 'mensual',
                    establishment_id: null,
                    establishment: null,
                    items: [],
                    tables: { resolutions: [] },
                    period: {
                        admision_date: '',
                        worked_time: 0,
                        issue_date: '',
                    },
                    payment: {
                        payment_method_id: null,
                        bank_name: '',
                        account_type: '',
                        account_number: ''
                    },
                    payment_dates: [],
                },
                activeName: 'active-workers',
                selectedWorkerId: null,
                globalGenerateProvisions: false,
                periodDateError: '',
                employeesArray: [], // Array que contendrá un JSON por cada empleado con sus datos de periodo
                employeePeriodData: {}, // Objeto que almacena los datos de periodo por ID de empleado
                employeePaymentData: {}, // Objeto que almacena los datos de pago por ID de empleado
                show_inputs_payment_method: false,
            };
        },

        async created() {
            this.setCurrentDateTime();
            await this.getTables();

            // Detectar modo edición basado en la URL
            this.detectEditMode();

            if (this.editMode) {
                await this.loadBlockPayrollData();
            } else {
                await this.getActiveWorkers();
            }
        },

        computed: {
        },

        methods: {
            // Función helper para calcular días entre fechas
            calculateWorkedDays(admisionDate) {
                if (!admisionDate) return 30; // Valor por defecto si no hay fecha

                const today = new Date();
                const admissionDate = new Date(admisionDate);

                // Calcular la diferencia en milisegundos
                const diffInMs = today - admissionDate;

                // Convertir a días
                const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24));

                // Retornar al menos 1 día si la fecha de admisión es hoy o en el futuro
                return Math.max(diffInDays, 1);
            },

            cancelForm() {
                window.location.href = '/payroll/block-payrolls';
            },

            saveForm(generate) {
                // Limpiar errores previos
                this.errors = {};
                let hasErrors = false;

                // Validar que la resolución sea obligatoria
                if (!this.form.type_document_id) {
                    this.errors.type_document_id = ['La resolución es obligatoria'];
                    hasErrors = true;
                }

                // Validar que la fecha de inicio de periodo sea obligatoria
                if (!this.form.period_start) {
                    this.errors.period_start = ['La fecha de inicio de periodo es obligatoria'];
                    hasErrors = true;
                }

                // Validar que la fecha de fin de periodo sea obligatoria
                if (!this.form.period_end) {
                    this.errors.period_end = ['La fecha de fin de periodo es obligatoria'];
                    hasErrors = true;
                }

                // Si hay errores, mostrar mensaje y detener
                if (hasErrors) {
                    this.$message.error('Debe completar todos los campos obligatorios');
                    return;
                }

                // Aquí puedes agregar la lógica para guardar el formulario
                // generate = true para "Guardar y generar", false para "Guardar sin generar"
                // Ejemplo:
                if (generate) {
                    // Guardar y generar
                    this.submitForm('generate');
                } else {
                    // Guardar sin generar
                    this.submitForm('save');
                }
                window.location.href = '/payroll/block-payrolls';
            },

            submitForm(action) {
                // Validación adicional antes de enviar
                let hasErrors = false;

                if (!this.form.type_document_id) {
                    this.errors.type_document_id = ['La resolución es obligatoria'];
                    hasErrors = true;
                }

                if (!this.form.period_start) {
                    this.errors.period_start = ['La fecha de inicio de periodo es obligatoria'];
                    hasErrors = true;
                }

                if (!this.form.period_end) {
                    this.errors.period_end = ['La fecha de fin de periodo es obligatoria'];
                    hasErrors = true;
                }

                if (hasErrors) {
                    this.$message.error('Debe completar todos los campos obligatorios');
                    return;
                }

                // Guardar datos del empleado actual antes de enviar
                this.saveCurrentEmployeeData();

                if (action === 'save') {
                    // Guardar sin generar
                    this.saveWithoutGenerate();
                } else if (action === 'generate') {
                    // Guardar y generar (funcionalidad original)
                    this.saveAndGenerate();
                }
            },

            async saveWithoutGenerate() {
                // Guardar datos del empleado actual antes de procesar
                if (this.selectedWorkerId) {
                    this.saveCurrentEmployeeData();
                    this.saveCurrentEmployeePaymentData();
                }

                // Validar unicidad del período en modo crear
                if (!this.editMode) {
                    const isUniquePeriod = await this.validatePeriodUniqueness();
                    if (!isUniquePeriod) {
                        return; // No continuar si el período ya existe
                    }
                }

                this.loading_submit = true;

                // Obtener todos los trabajadores cargados (no hay selección individual, se procesan todos)
                const selectedWorkers = this.form.items.map(worker => worker.id);

                if (selectedWorkers.length === 0) {
                    this.$message.error('No hay trabajadores disponibles para procesar');
                    this.loading_submit = false;
                    return;
                }

                // Preparar datos para enviar (excluir explícitamente el campo notes)
                const formData = {
                    selected_workers: selectedWorkers,
                    resolution_id: this.form.type_document_id,
                    date_of_issue: this.form.date_of_issue,
                    time_of_issue: this.form.time_of_issue,
                    general_period_start: this.form.period_start,
                    general_period_end: this.form.period_end,
                    establishment_id: this.form.establishment_id,
                    establishment_data: this.form.establishment,
                };

                // Agregar datos de periodo de cada empleado usando estructura anidada
                const employeePeriodData = {};
                selectedWorkers.forEach(workerId => {
                    const periodData = this.employeePeriodData[workerId] || {};
                    const currentWorker = this.form.items.find(item => item.id === workerId);

                    employeePeriodData[workerId] = {
                        worker_id: workerId,
                        salary: periodData.salary || (currentWorker ? currentWorker.salary : 0),
                        worked_days: periodData.worked_days || 30, // Valor del formulario "Días trabajados"
                        admision_date: periodData.admision_date || '',
                        worked_time: this.calculateWorkedDays(periodData.admision_date || ''), // Cálculo automático entre fechas
                        payroll_period: periodData.payroll_period_id || 5,  // Siempre usar "Mensual" como defecto
                        generate_provisions: currentWorker ? currentWorker.generate_provisions : false
                    };
                });

                // Agregar datos de pago de cada empleado
                const employeePaymentData = {};
                selectedWorkers.forEach(workerId => {
                    const paymentData = this.employeePaymentData[workerId] || {};

                    employeePaymentData[workerId] = {
                        worker_id: workerId,
                        payment_method_id: paymentData.payment_method_id || null,
                        bank_name: paymentData.bank_name || '',
                        account_type: paymentData.account_type || '',
                        account_number: paymentData.account_number || '',
                        payment_dates: paymentData.payment_dates || []
                    };
                });

                // Agregar los objetos completos al formData
                formData.employee_period_data = employeePeriodData;
                formData.employee_payment_data = employeePaymentData;

                // Asegurar que no se incluya el campo notes
                if (formData.hasOwnProperty('notes')) {
                    delete formData.notes;
                }

                // Determinar endpoint según modo
                const endpoint = this.editMode
                    ? `/${this.resource}/update-block/${this.blockPayrollId}`
                    : `/${this.resource}/store-without-generate`;

                const method = this.editMode ? 'put' : 'post';

                // Enviar al backend
                this.$http[method](endpoint, formData)
                    .then(response => {
                        this.loading_submit = false;
                        if (response.data.success) {
                            this.$message.success(response.data.message);
                            // Redirigir al listado después de guardar/editar
                            setTimeout(() => {
                                window.location.href = '/payroll/block-payrolls';
                            }, 1500);
                        } else {
                            this.$message.error(response.data.message);
                        }
                    })
                    .catch(error => {
                        this.loading_submit = false;
                        this.$message.error(this.editMode ? 'Error al editar el bloque de nómina' : 'Error al guardar el bloque de nómina');
                    });
            },            saveAndGenerate() {
                // Implementar la funcionalidad original de guardar y generar
                // Por ahora solo mostramos un mensaje
                this.$message.info('Funcionalidad de "Guardar y generar" por implementar');
            },

            validatePeriodDates() {
                this.periodDateError = '';

                // Limpiar errores de campos obligatorios cuando se completan
                if (this.form.period_start && this.errors.period_start) {
                    this.$delete(this.errors, 'period_start');
                }
                if (this.form.period_end && this.errors.period_end) {
                    this.$delete(this.errors, 'period_end');
                }

                if (this.form.period_start && this.form.period_end) {
                    if (this.form.period_end < this.form.period_start) {
                        this.periodDateError = 'La fecha final no puede ser menor que la fecha inicial.';
                        this.form.period_end = '';
                    } else if (this.form.period_start > this.form.period_end) {
                        this.periodDateError = 'La fecha inicial no puede ser mayor que la fecha final.';
                        this.form.period_start = '';
                    }
                }
            },

            // Validar que no exista un período duplicado (solo en modo crear)
            async validatePeriodUniqueness() {
                if (this.editMode) {
                    return true; // En modo edición, no validar unicidad
                }

                if (!this.form.period_start || !this.form.period_end) {
                    return true; // No validar si las fechas no están completas
                }

                try {
                    const response = await this.$http.post(`/${this.resource}/check-period-exists`, {
                        period_start: this.form.period_start,
                        period_end: this.form.period_end
                    });

                    if (response.data.exists) {
                        this.periodDateError = 'Ya existe un bloque de nómina registrado para este período.';
                        return false;
                    }

                    this.periodDateError = '';
                    return true;
                } catch (error) {
                    // Si hay error en la validación, permitir continuar
                    console.warn('Error validando unicidad del período:', error);
                    return true;
                }
            },

            setCurrentDateTime() {
                const now = new Date();
                this.form.date_of_issue = now.toISOString().slice(0, 10);
                this.form.time_of_issue = now.toTimeString().slice(0, 8);
            },

            getActiveWorkers() {
                this.loading = true
                this.$http.get(`/${this.resource}/active-workers`).then((response) => {
                    this.form.items = response.data.data
                    this.form.workers_quantity = this.form.items.length
                    this.selectedWorkerId = this.form.items.length > 0 ? this.form.items[0].id : null

                    // Inicializar el array de empleados con sus datos
                    this.initializeEmployeesArray()

                    this.loading = false
                }).catch((error) => {
                    this.loading = false
                    this.$message.error(getValueIfNull(error.response.data.message, 'Error al cargar los trabajadores activos'))
                })
            },

            getTables() {
                this.loading = true
                this.$http.get(`/${this.resource}/tables`).then((response) => {
                    this.form.tables = response.data || { resolutions: [] }; // Asegurar estructura
                    // Asignar establishment_id desde las tables si viene
                    if (response.data.establishment_id) {
                        this.form.establishment_id = response.data.establishment_id;
                    }
                    // Asignar datos del establecimiento si vienen
                    if (response.data.establishment) {
                        this.form.establishment = response.data.establishment;
                    }

                    // Establecer "Mensual" (ID: 5) como valor por defecto para el período de nómina
                    this.form.payroll_period_id = 5;

                    this.loading = false
                }).catch((error) => {
                    this.loading = false
                    this.$message.error(getValueIfNull(error.response.data.message, 'Error al cargar las tablas'))
                })
            },

            changeResolution() {
                // Limpiar errores cuando se selecciona una resolución
                if (this.form.type_document_id && this.errors.type_document_id) {
                    this.$delete(this.errors, 'type_document_id');
                }

                if (this.form.tables && this.form.tables.resolutions) { // Verificación añadida
                    let resolution = _.find(this.form.tables.resolutions, { id: this.form.type_document_id });
                    if (resolution) {
                        this.form.prefix = resolution.prefix;
                        this.form.resolution_number = resolution.resolution_number;
                    }
                }
            },

            removeItem(index) {
                this.form.items.splice(index, 1)
                this.form.workers_quantity = this.form.items.length
            },

            handleGlobalSwitch(value) {
                if (value) {
                    // ON: todos los switches en true y deshabilitados
                    this.form.items.forEach(item => item.generate_provisions = true)
                }
                // OFF: los switches pueden ser editados individualmente
                // No se necesita acción extra, el :disabled se encarga
            },

            getFormatDecimal(value) {
                // Convierte la cadena a un número (si es posible)
                const numericPrice = parseFloat(value);
                if (isNaN(numericPrice)) {
                    // En caso de que la conversión no sea exitosa, devolver el valor original
                    return value;
                }
                // Asumiendo que numericPrice es un número
                const formattedPrice = numericPrice.toLocaleString('en-US', {
                    style: 'decimal',  // Estilo 'decimal' para separadores de mil y dos decimales
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                return formattedPrice;
            },

            handleWorkerSelection(workerId) {
                // Guardar datos del empleado actual si existe
                if (this.selectedWorkerId && this.selectedWorkerId !== workerId) {
                    this.saveCurrentEmployeeData();
                    this.saveCurrentEmployeePaymentData();
                }

                // Cambiar empleado seleccionado
                this.selectedWorkerId = workerId;

                // Cargar datos del nuevo empleado
                this.loadEmployeeData(workerId);
                this.loadEmployeePaymentData(workerId);

                // Forzar actualización completa del componente
                this.$nextTick(() => {
                    this.$forceUpdate();
                });
            },

            initializeEmployeesArray() {
                const currentYear = new Date().getFullYear();
                const defaultDate = `${currentYear}-01-01`;

                // Inicializar datos por defecto para cada empleado
                this.form.items.forEach(worker => {
                    // Inicializar generate_provisions si no existe (solo establecer valor por defecto)
                    if (worker.generate_provisions === undefined || worker.generate_provisions === null) {
                        this.$set(worker, 'generate_provisions', false);
                    }

                    if (!this.employeePeriodData[worker.id]) {
                        const admisionDate = worker.work_start_date || defaultDate;

                        this.$set(this.employeePeriodData, worker.id, {
                            admision_date: admisionDate,
                            worked_time: this.calculateWorkedDays(admisionDate), // Cálculo automático entre fechas
                            issue_date: '',
                            payroll_period_id: worker.payroll_period_id || 5,  // Usar período del empleado o "Mensual" como defecto
                            // Agregar campos que espera el backend
                            salary: worker.salary || 0,
                            period_start: this.form.period_start || '',
                            period_end: this.form.period_end || '',
                            worked_days: 30 // Valor fijo por defecto para el formulario
                        });
                    }

                    // Inicializar datos de pago para cada empleado
                    if (!this.employeePaymentData[worker.id]) {
                        const workerPayment = worker.payment;
                        this.$set(this.employeePaymentData, worker.id, {
                            payment_method_id: workerPayment?.payment_method_id || null,
                            bank_name: workerPayment?.bank_name || '',
                            account_type: workerPayment?.account_type || '',
                            account_number: workerPayment?.account_number || '',
                            payment_dates: []
                        });
                    }
                });

                // Cargar datos del primer empleado con delay para asegurar renderizado
                if (this.selectedWorkerId) {
                    this.$nextTick(() => {
                        this.loadEmployeeData(this.selectedWorkerId);
                        this.loadEmployeePaymentData(this.selectedWorkerId);
                    });
                }
            },

            saveCurrentEmployeeData() {
                if (!this.selectedWorkerId) return;

                // Obtener datos actuales del empleado seleccionado
                const currentWorker = this.form.items.find(item => item.id === this.selectedWorkerId);

                // Guardar datos actuales del formulario
                this.employeePeriodData[this.selectedWorkerId] = {
                    admision_date: this.form.period.admision_date || '',
                    worked_time: this.calculateWorkedDays(this.form.period.admision_date || ''), // Cálculo automático
                    issue_date: this.form.period.issue_date || '',
                    payroll_period_id: this.form.payroll_period_id || 5,
                    // Agregar campos que espera el backend
                    salary: currentWorker ? currentWorker.salary : 0,
                    period_start: this.form.period_start || '',
                    period_end: this.form.period_end || '',
                    worked_days: this.form.period.worked_time || 30 // Valor del control "Días trabajados"
                };
            },

            loadEmployeeData(workerId) {
                // Obtener datos del empleado o usar valores por defecto
                const data = this.employeePeriodData[workerId];
                const currentWorker = this.form.items.find(item => item.id === workerId);

                if (data) {
                    // Cargar datos existentes haciendo copia para evitar referencias
                    this.form.period.admision_date = data.admision_date || '';
                    this.form.period.worked_time = data.worked_days || 30; // Mostrar worked_days en el formulario
                    this.form.period.issue_date = data.issue_date || '';
                    this.form.payroll_period_id = data.payroll_period_id || (currentWorker ? currentWorker.payroll_period_id : 5);
                } else {
                    // Usar datos del trabajador como valores por defecto
                    const currentYear = new Date().getFullYear();
                    const defaultDate = `${currentYear}-01-01`;
                    const admisionDate = currentWorker ? currentWorker.work_start_date : defaultDate;

                    this.form.period.admision_date = admisionDate;
                    this.form.period.worked_time = 30; // Valor fijo por defecto para el formulario
                    this.form.period.issue_date = '';
                    this.form.payroll_period_id = currentWorker ? currentWorker.payroll_period_id || 5 : 5;
                }
            },

            handlePayrollPeriodChange(newValue) {
                // Actualizar inmediatamente el formulario para mostrar el cambio
                this.form.payroll_period_id = newValue;

                // Actualizar inmediatamente en el storage del empleado actual
                if (this.selectedWorkerId) {
                    // Asegurar que el objeto existe
                    if (!this.employeePeriodData[this.selectedWorkerId]) {
                        this.employeePeriodData[this.selectedWorkerId] = {};
                    }

                    // Actualizar el valor usando Vue.set para reactividad
                    this.$set(this.employeePeriodData[this.selectedWorkerId], 'payroll_period_id', newValue);

                    // Forzar actualización del componente para asegurar que se muestre
                    this.$nextTick(() => {
                        this.$forceUpdate();
                    });
                }
            },

            handleAdmisionDateChange(newValue) {
                // Actualizar el formulario inmediatamente
                this.form.period.admision_date = newValue;

                if (this.selectedWorkerId) {
                    if (!this.employeePeriodData[this.selectedWorkerId]) {
                        this.employeePeriodData[this.selectedWorkerId] = {};
                    }

                    // Calcular automáticamente worked_time basado en la nueva fecha
                    const calculatedWorkedTime = this.calculateWorkedDays(newValue);

                    this.$set(this.employeePeriodData[this.selectedWorkerId], 'admision_date', newValue);
                    this.$set(this.employeePeriodData[this.selectedWorkerId], 'worked_time', calculatedWorkedTime); // Cálculo automático

                    // Solo establecer worked_days si no hay valor previo
                    if (!this.employeePeriodData[this.selectedWorkerId].worked_days) {
                        this.$set(this.employeePeriodData[this.selectedWorkerId], 'worked_days', 30);
                        this.form.period.worked_time = 30;
                    }
                }
            },

            handleWorkedTimeChange(newValue) {
                // Actualizar el formulario inmediatamente
                this.form.period.worked_time = newValue;

                if (this.selectedWorkerId) {
                    if (!this.employeePeriodData[this.selectedWorkerId]) {
                        this.employeePeriodData[this.selectedWorkerId] = {};
                    }

                    // Solo actualizar worked_days (el valor del formulario), worked_time se calcula automáticamente
                    this.$set(this.employeePeriodData[this.selectedWorkerId], 'worked_days', newValue);
                }
            },

            // Método para manejar el cambio de tabs
            handleTabChange(tab) {
                // Guardar datos al salir del tab periodo
                if (this.activeName === 'period' && tab.name !== 'period') {
                    this.saveCurrentEmployeeData();
                }
                // Guardar datos al salir del tab pagos
                if (this.activeName === 'payments' && tab.name !== 'payments') {
                    this.saveCurrentEmployeePaymentData();
                }
            },

            // Métodos para el manejo de datos de pago
            changePaymentMethod() {
                this.show_inputs_payment_method = [2,3,4,5,6,7,21,22,30,31,42,45,46,47].includes(this.form.payment.payment_method_id);

                // Guardar inmediatamente en el almacenamiento del empleado actual
                if (this.selectedWorkerId) {
                    if (!this.employeePaymentData[this.selectedWorkerId]) {
                        this.$set(this.employeePaymentData, this.selectedWorkerId, {});
                    }
                    this.$set(this.employeePaymentData[this.selectedWorkerId], 'payment_method_id', this.form.payment.payment_method_id);

                    // Guardar todos los datos inmediatamente
                    this.saveCurrentEmployeePaymentData();
                }
            },

            clickAddPaymentDate() {
                this.form.payment_dates.push({
                    payment_date: ''
                });

                // Guardar inmediatamente en el almacenamiento del empleado actual
                if (this.selectedWorkerId) {
                    this.saveCurrentEmployeePaymentData();
                }
            },

            clickCancelPaymentDate(index) {
                this.form.payment_dates.splice(index, 1);

                // Guardar inmediatamente en el almacenamiento del empleado actual
                if (this.selectedWorkerId) {
                    this.saveCurrentEmployeePaymentData();
                }
            },

            handlePaymentDateChange() {
                // Guardar automáticamente cuando se cambie una fecha de pago
                if (this.selectedWorkerId) {
                    this.saveCurrentEmployeePaymentData();
                }
            },

            saveCurrentEmployeePaymentData() {
                if (!this.selectedWorkerId) return;

                // Asegurar que el objeto del empleado existe
                if (!this.employeePaymentData[this.selectedWorkerId]) {
                    this.$set(this.employeePaymentData, this.selectedWorkerId, {});
                }

                // Crear una copia profunda de las fechas para evitar referencias
                const paymentDatesCopy = JSON.parse(JSON.stringify(this.form.payment_dates || []));

                this.$set(this.employeePaymentData, this.selectedWorkerId, {
                    payment_method_id: this.form.payment.payment_method_id,
                    bank_name: this.form.payment.bank_name || '',
                    account_type: this.form.payment.account_type || '',
                    account_number: this.form.payment.account_number || '',
                    payment_dates: paymentDatesCopy
                });
            },

            loadEmployeePaymentData(workerId) {
                const data = this.employeePaymentData[workerId];
                const currentWorker = this.form.items.find(item => item.id === workerId);

                if (data) {
                    // Cargar datos existentes
                    this.form.payment.payment_method_id = data.payment_method_id || null;
                    this.form.payment.bank_name = data.bank_name || '';
                    this.form.payment.account_type = data.account_type || '';
                    this.form.payment.account_number = data.account_number || '';
                    this.form.payment_dates = data.payment_dates ? JSON.parse(JSON.stringify(data.payment_dates)) : [];
                } else {
                    // Usar datos del trabajador desde el backend si existen
                    const workerPayment = currentWorker?.payment;
                    this.form.payment.payment_method_id = workerPayment?.payment_method_id || null;
                    this.form.payment.bank_name = workerPayment?.bank_name || '';
                    this.form.payment.account_type = workerPayment?.account_type || '';
                    this.form.payment.account_number = workerPayment?.account_number || '';
                    this.form.payment_dates = [];
                }

                // Actualizar visibilidad de campos adicionales
                this.changePaymentMethod();

                // Forzar actualización del DOM
                this.$nextTick(() => {
                    this.$forceUpdate();
                });
            },

            detectEditMode() {
                // Detectar si estamos en modo edición basado en la URL
                const currentPath = window.location.pathname;
                const editMatch = currentPath.match(/\/payroll\/block-payrolls\/edit-block\/(\d+)/);

                if (editMatch) {
                    this.editMode = true;
                    this.blockPayrollId = parseInt(editMatch[1]);
                }
            },

            async loadBlockPayrollData() {
                this.loading = true;
                try {
                    const response = await this.$http.get(`/${this.resource}/edit-block/${this.blockPayrollId}`);
                    const blockPayroll = response.data;

                    // Cargar datos básicos del formulario
                    this.form.date_of_issue = blockPayroll.date_of_issue;
                    this.form.time_of_issue = blockPayroll.time_of_issue;
                    this.form.period_start = blockPayroll.period?.period_start || '';
                    this.form.period_end = blockPayroll.period?.period_end || '';
                    this.form.type_document_id = blockPayroll.resolution_id;
                    this.form.establishment_id = blockPayroll.establishment_id;
                    this.form.establishment = blockPayroll.establishment;
                    this.form.workers_quantity = blockPayroll.workers_quantity;
                    this.form.accrued_total = blockPayroll.accrued_total;
                    this.form.deductions_total = blockPayroll.deductions_total;

                    // Cargar trabajadores desde el payload
                    if (blockPayroll.payload && blockPayroll.payload.selected_workers) {
                        const workerIds = blockPayroll.payload.selected_workers;
                        await this.loadWorkersForEdit(workerIds);

                        // Inicializar datos por defecto para todos los empleados
                        this.initializeEmployeesArray();

                        // Cargar datos de período de cada empleado (sobrescribir los por defecto)
                        if (blockPayroll.payload.employee_period_data) {
                            this.employeePeriodData = blockPayroll.payload.employee_period_data;
                        }

                        // Cargar datos de pago de cada empleado (sobrescribir los por defecto)
                        if (blockPayroll.payload.employee_payment_data) {
                            this.employeePaymentData = blockPayroll.payload.employee_payment_data;
                        }

                        // Restaurar valores de generate_provisions desde el payload
                        if (blockPayroll.payload.employee_period_data) {
                            this.form.items.forEach(worker => {
                                const periodData = blockPayroll.payload.employee_period_data[worker.id];
                                if (periodData && periodData.generate_provisions !== undefined) {
                                    console.log(`Restaurando generate_provisions para worker ${worker.id}:`, periodData.generate_provisions);
                                    this.$set(worker, 'generate_provisions', periodData.generate_provisions);
                                }
                            });
                        }

                        // Seleccionar el primer trabajador
                        this.selectedWorkerId = this.form.items.length > 0 ? this.form.items[0].id : null;

                        // Cargar datos del primer empleado
                        if (this.selectedWorkerId) {
                            this.$nextTick(() => {
                                this.loadEmployeeData(this.selectedWorkerId);
                                this.loadEmployeePaymentData(this.selectedWorkerId);
                            });
                        }
                    }

                    this.loading = false;
                } catch (error) {
                    this.loading = false;
                    this.$message.error('Error al cargar los datos del bloque de nómina');
                    console.error('Error loading block payroll data:', error);
                }
            },

            async loadWorkersForEdit(workerIds) {
                try {
                    // Obtener datos completos de los trabajadores
                    const workersPromises = workerIds.map(id =>
                        this.$http.get(`/payroll/workers/search-by-id/${id}`)
                    );

                    const workersResponses = await Promise.all(workersPromises);
                    // El endpoint devuelve {workers: [...]} así que necesitamos extraer workers[0]
                    this.form.items = workersResponses.map(response => response.data.workers[0]).filter(worker => worker);
                    this.form.workers_quantity = this.form.items.length;
                } catch (error) {
                    console.error('Error loading workers for edit:', error);
                    // Fallback: cargar todos los trabajadores activos
                    await this.getActiveWorkers();
                }
            },
        },

        watch: {
            selectedWorkerId: {
                handler(newWorkerId, oldWorkerId) {
                    if (newWorkerId && newWorkerId !== oldWorkerId) {
                        this.$nextTick(() => {
                            this.loadEmployeeData(newWorkerId);
                            this.loadEmployeePaymentData(newWorkerId);
                        });
                    }
                },
                immediate: false
            }
        }
    }
</script>

<style scoped>
    input[type="radio"] {
        display: inline-block !important;
        opacity: 1 !important;
        width: 16px !important;
        height: 16px !important;
        position: static !important;
    }
</style>
