<template>
    <div class="card mb-0 pt-2 pt-md-0">
        <div class="card-header bg-info">
            <h3 class="my-0 text-white">Generar Bloque de Nominas</h3>
        </div>
        <div class="card-body">
            <div class="invoice">
                <form autocomplete="off" @submit.prevent="submit">
                    <div class="form-body">
                        <el-tabs v-model="activeName">
                            <el-tab-pane label="Trabajadores Seleccionados" name="active-workers">
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
                                        <div class="form-group">
                                            <label>Fecha de inicio de periodo</label>
                                            <el-date-picker
                                                v-model="form.period_start"
                                                type="date"
                                                placeholder="Seleccione fecha"
                                                value-format="yyyy-MM-dd"
                                                format="yyyy-MM-dd"
                                                class="w-100"
                                                @change="validatePeriodDates"
                                            ></el-date-picker>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Fecha de fin de periodo</label>
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
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="control-label">Resolución</label>
                                            <el-select @change="changeResolution" v-model="form.type_document_id" class="border-left rounded-left border-info">
                                                <el-option v-for="option in form.tables.resolutions" :key="option.id" :value="option.id" :label="`${option.prefix} / ${option.resolution_number ? option.resolution_number : ''} / ${option.from ? option.from : ''} / ${option.to ? option.to : ''}`"></el-option>
                                            </el-select>
                                            <small class="form-control-feedback" v-if="errors.type_document_id" v-text="errors.type_document_id[0]"></small>
                                        </div>
                                    </div>
                                </div>
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
                                            <el-date-picker v-model="form.period.admision_date" type="date" value-format="yyyy-MM-dd" :clearable="false"></el-date-picker>
                                            <small class="form-control-feedback" v-if="errors['period.admision_date']" v-text="errors['period.admision_date'][0]"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group" :class="{'has-danger': errors['period.worked_time']}">
                                            <label class="control-label">Tiempo trabajado<span class="text-danger"> *</span></label>
                                            <el-input-number v-model="form.period.worked_time" :min="0" controls-position="right"></el-input-number>
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
                                            <el-select v-model="form.payroll_period_id"   filterable class="border-left rounded-left border-info">
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
                    period: {
                        admision_date: moment().format('YYYY-MM-DD'),
                        settlement_start_date: null,
                        settlement_end_date: null,
                        worked_time: 0,
                        issue_date: moment().format('YYYY-MM-DD'),
                    },
                    workers_quantity: 0,
                    accrued_total: 0,
                    deductions_total: 0,
                    period_start: '',
                    period_end: '',
                    period_type: 'mensual',
                    items: [],
                    tables: { resolutions: [] }, // Inicialización para evitar errores
                },
                activeName: 'active-workers',
                selectedWorkerId: null,
                globalGenerateProvisions: false,
                periodDateError: '',
            };
        },

        async created() {
            this.setCurrentDateTime()
            await this.getTables()
            await this.getActiveWorkers()
        },

        computed: {
        },

        methods: {
            cancelForm() {
                window.location.href = '/payroll/block-payrolls';
            },

            saveForm(generate) {
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
                // Implementa la lógica de guardado según el action
                // Puedes usar this.$http.post(...) o lo que corresponda
                // action será 'generate' o 'save'
                // ...
                this.$message.success('Acción: ' + action);
            },

            validatePeriodDates() {
                this.periodDateError = '';
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
                    console.log('Tables loaded:', this.form.tables)
                    this.loading = false
                }).catch((error) => {
                    this.loading = false
                    this.$message.error(getValueIfNull(error.response.data.message, 'Error al cargar las tablas'))
                })
            },

            changeResolution() {
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
                    // En caso de que la conversión no sea exitosa, maneja el error como desees
                    console.error('No se pudo convertir la cadena a un número.');
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
