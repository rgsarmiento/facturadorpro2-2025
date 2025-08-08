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
                                    <label>Fecha de inicio de periodo<span class="text-danger"> *</span></label>
                                    <el-date-picker
                                        v-model="form.period_start"
                                        type="date"
                                        placeholder="Seleccione fecha"
                                        value-format="yyyy-MM-dd"
                                        format="yyyy-MM-dd"
                                        class="w-100"
                                        @change="validatePeriodDates"
                                    ></el-date-picker>
                                    <small class="form-control-feedback" v-if="errors.period_start" v-text="errors.period_start[0]"></small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group" :class="{'has-danger': errors.period_end}">
                                    <label>Fecha de fin de periodo<span class="text-danger"> *</span></label>
                                    <el-date-picker
                                        v-model="form.period_end"
                                        type="date"
                                        placeholder="Seleccione fecha"
                                        value-format="yyyy-MM-dd"
                                        format="yyyy-MM-dd"
                                        class="w-100"
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
                                                        <td class="text-left">{{row.fullname}}</td>
                                                        <td class="text-right">{{row.payroll_type_document_identification_name}}</td>
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
                                            <label class="control-label">Tiempo trabajado<span class="text-danger"> *</span></label>
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
                        </el-tabs>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-secondary mr-2" @click="cancelForm">
                                Cancelar
                            </button>
                            <button type="button" class="btn btn-warning mr-2" @click="saveForm(false)">
                                Guardar sin generar
                            </button>
                            <button type="button" class="btn btn-primary" @click="saveForm(true)">
                                Guardar y generar
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
                        settlement_start_date: '',
                        settlement_end_date: '',
                        worked_time: 0,
                        issue_date: '',
                    },
                },
                activeName: 'active-workers',
                selectedWorkerId: null,
                globalGenerateProvisions: false,
                periodDateError: '',
                employeesArray: [], // Array que contendrá un JSON por cada empleado con sus datos de periodo
                employeePeriodData: {}, // Objeto que almacena los datos de periodo por ID de empleado
            };
        },

        async created() {
            this.setCurrentDateTime();
            await this.getTables();
            await this.getActiveWorkers();
        },

        computed: {
        },

        methods: {
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

            saveWithoutGenerate() {
                this.loading_submit = true;

                // Obtener todos los trabajadores cargados (no hay selección individual, se procesan todos)
                const selectedWorkers = this.form.items.map(worker => worker.id);

                if (selectedWorkers.length === 0) {
                    this.$message.error('No hay trabajadores disponibles para procesar');
                    this.loading_submit = false;
                    return;
                }

                // Preparar datos para enviar
                const formData = {
                    selected_workers: selectedWorkers,
                    resolution_id: this.form.type_document_id,
                    date_of_issue: this.form.date_of_issue,
                    time_of_issue: this.form.time_of_issue,
                    general_period_start: this.form.period_start,
                    general_period_end: this.form.period_end,
                    notes: this.form.notes || '',
                    establishment_id: this.form.establishment_id,
                    establishment_data: this.form.establishment,
                };

                // Agregar datos de periodo de cada empleado
                selectedWorkers.forEach(workerId => {
                    const periodData = this.employeePeriodData[workerId] || {};
                    formData[`employee_period_data.${workerId}.period_start`] = periodData.period_start || this.form.period_start;
                    formData[`employee_period_data.${workerId}.period_end`] = periodData.period_end || this.form.period_end;
                    formData[`employee_period_data.${workerId}.salary`] = periodData.salary || 0;
                    formData[`employee_period_data.${workerId}.worked_days`] = periodData.worked_days || 30;
                    formData[`employee_period_data.${workerId}.admision_date`] = periodData.admision_date || '';
                    formData[`employee_period_data.${workerId}.settlement_start_date`] = periodData.settlement_start_date || '';
                    formData[`employee_period_data.${workerId}.settlement_end_date`] = periodData.settlement_end_date || '';
                    formData[`employee_period_data.${workerId}.worked_time`] = periodData.worked_time || 0;
                    formData[`employee_period_data.${workerId}.issue_date`] = periodData.issue_date || '';
                });

                // Enviar al backend
                this.$http.post(`/${this.resource}/store-without-generate`, formData)
                    .then(response => {
                        this.loading_submit = false;
                        if (response.data.success) {
                            this.$message.success(response.data.message);
                            // Opcionalmente redirigir o limpiar formulario
                            // this.$router.push(`/${this.resource}`);
                        } else {
                            this.$message.error(response.data.message);
                        }
                    })
                    .catch(error => {
                        this.loading_submit = false;
                        this.$message.error('Error al guardar el bloque de nómina');
                    });
            },

            saveAndGenerate() {
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
                }

                // Cambiar empleado seleccionado
                this.selectedWorkerId = workerId;

                // Cargar datos del nuevo empleado
                this.loadEmployeeData(workerId);

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
                    if (!this.employeePeriodData[worker.id]) {
                        this.$set(this.employeePeriodData, worker.id, {
                            admision_date: defaultDate,
                            settlement_start_date: '',
                            settlement_end_date: '',
                            worked_time: 30,
                            issue_date: '',
                            payroll_period_id: 5
                        });
                    }
                });

                // Cargar datos del primer empleado con delay para asegurar renderizado
                if (this.selectedWorkerId) {
                    this.$nextTick(() => {
                        this.loadEmployeeData(this.selectedWorkerId);
                    });
                }
            },

            saveCurrentEmployeeData() {
                if (!this.selectedWorkerId) return;

                // Guardar datos actuales del formulario
                this.employeePeriodData[this.selectedWorkerId] = {
                    admision_date: this.form.period.admision_date || '',
                    settlement_start_date: this.form.period.settlement_start_date || '',
                    settlement_end_date: this.form.period.settlement_end_date || '',
                    worked_time: this.form.period.worked_time || 30,
                    issue_date: this.form.period.issue_date || '',
                    payroll_period_id: this.form.payroll_period_id || 5
                };
            },

            loadEmployeeData(workerId) {
                // Obtener datos del empleado o usar valores por defecto
                const data = this.employeePeriodData[workerId];

                if (data) {
                    // Cargar datos existentes haciendo copia para evitar referencias
                    this.form.period.admision_date = data.admision_date || '';
                    this.form.period.settlement_start_date = data.settlement_start_date || '';
                    this.form.period.settlement_end_date = data.settlement_end_date || '';
                    this.form.period.worked_time = data.worked_time || 30;
                    this.form.period.issue_date = data.issue_date || '';
                    this.form.payroll_period_id = data.payroll_period_id || 5;
                } else {
                    const currentYear = new Date().getFullYear();
                    const defaultDate = `${currentYear}-01-01`;

                    this.form.period.admision_date = defaultDate;
                    this.form.period.settlement_start_date = '';
                    this.form.period.settlement_end_date = '';
                    this.form.period.worked_time = 30;
                    this.form.period.issue_date = '';
                    this.form.payroll_period_id = 5;
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

                    this.$set(this.employeePeriodData[this.selectedWorkerId], 'admision_date', newValue);
                }
            },

            handleWorkedTimeChange(newValue) {
                // Actualizar el formulario inmediatamente
                this.form.period.worked_time = newValue;

                if (this.selectedWorkerId) {
                    if (!this.employeePeriodData[this.selectedWorkerId]) {
                        this.employeePeriodData[this.selectedWorkerId] = {};
                    }

                    this.$set(this.employeePeriodData[this.selectedWorkerId], 'worked_time', newValue);
                }
            },

            // Método para manejar el cambio de tabs
            handleTabChange(tab) {
                // Guardar datos al salir del tab periodo
                if (this.activeName === 'period' && tab.name !== 'period') {
                    this.saveCurrentEmployeeData();
                }
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
