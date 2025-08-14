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
                                    <input
                                        type="text"
                                        class="form-control"
                                        :value="form.date_of_issue"
                                        disabled
                                    />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Hora de emisión</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        :value="form.time_of_issue"
                                        disabled
                                    />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Cantidad de trabajadores</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        :value="form.workers_quantity"
                                        disabled
                                    />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Total devengados</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        :value="
                                            getFormatDecimal(form.accrued_total)
                                        "
                                        disabled
                                    />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Total deducciones</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        :value="form.deductions_total"
                                        disabled
                                    />
                                </div>
                            </div>
                            <!-- Campos para period -->
                            <div class="col-md-3">
                                <div
                                    class="form-group"
                                    :class="{
                                        'has-danger': errors.period_start
                                    }"
                                >
                                    <label
                                        >Fecha de inicio de periodo<span
                                            class="text-danger"
                                        >
                                            *</span
                                        >
                                        <small
                                            v-if="editMode"
                                            class="text-muted"
                                            >(No editable)</small
                                        >
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
                                    <small
                                        class="form-control-feedback"
                                        v-if="errors.period_start"
                                        v-text="errors.period_start[0]"
                                    ></small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div
                                    class="form-group"
                                    :class="{ 'has-danger': errors.period_end }"
                                >
                                    <label
                                        >Fecha de fin de periodo<span
                                            class="text-danger"
                                        >
                                            *</span
                                        >
                                        <small
                                            v-if="editMode"
                                            class="text-muted"
                                            >(No editable)</small
                                        >
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
                                    <small
                                        v-if="periodDateError"
                                        class="text-danger"
                                        >{{ periodDateError }}</small
                                    >
                                    <small
                                        class="form-control-feedback"
                                        v-if="errors.period_end"
                                        v-text="errors.period_end[0]"
                                    ></small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div
                                    class="form-group"
                                    :class="{
                                        'has-danger': errors.type_document_id
                                    }"
                                >
                                    <label class="control-label"
                                        >Resolución<span class="text-danger">
                                            *</span
                                        ></label
                                    >
                                    <el-select
                                        @change="changeResolution"
                                        v-model="form.type_document_id"
                                        class="border-left rounded-left border-info"
                                    >
                                        <el-option
                                            v-for="option in form.tables
                                                .resolutions"
                                            :key="option.id"
                                            :value="option.id"
                                            :label="
                                                `${option.prefix} / ${
                                                    option.resolution_number
                                                        ? option.resolution_number
                                                        : ''
                                                } / ${
                                                    option.from
                                                        ? option.from
                                                        : ''
                                                } / ${
                                                    option.to ? option.to : ''
                                                }`
                                            "
                                        ></el-option>
                                    </el-select>
                                    <small
                                        class="form-control-feedback"
                                        v-if="errors.type_document_id"
                                        v-text="errors.type_document_id[0]"
                                    ></small>
                                </div>
                            </div>
                        </div>
                        <el-tabs
                            v-model="activeName"
                            @tab-click="handleTabChange"
                        >
                            <el-tab-pane
                                label="Trabajadores Seleccionados"
                                name="active-workers"
                            >
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th
                                                            class="text-center"
                                                            style="width: 40px;"
                                                        ></th>
                                                        <th
                                                            class="text-center"
                                                            style="width: 50px;"
                                                        >
                                                            #
                                                        </th>
                                                        <th
                                                            class="text-center"
                                                            style="width: 150px;"
                                                        >
                                                            Doc. Identidad
                                                        </th>
                                                        <th
                                                            class="text-center"
                                                            style="width: 350px;"
                                                        >
                                                            Nombre
                                                        </th>
                                                        <th
                                                            class="text-center"
                                                            style="width: 250px;"
                                                        >
                                                            Tipo Documento
                                                        </th>
                                                        <th
                                                            class="text-center"
                                                            style="width: 120px;"
                                                        >
                                                            Salario Basico
                                                        </th>
                                                        <th
                                                            class="text-center"
                                                            style="width: 120px;"
                                                        >
                                                            Telefono
                                                        </th>
                                                        <th
                                                            class="text-center"
                                                            style="width: 200px;"
                                                        >
                                                            Cargo
                                                        </th>
                                                        <th
                                                            class="text-right"
                                                            style="width: 180px;"
                                                        >
                                                            Generar Provisiones
                                                            <el-switch
                                                                v-model="
                                                                    globalGenerateProvisions
                                                                "
                                                                active-text=""
                                                                inactive-text=""
                                                                @change="
                                                                    handleGlobalSwitch
                                                                "
                                                                style="margin-left: 8px;"
                                                            />
                                                        </th>
                                                        <th
                                                            class="text-right"
                                                            style="width: 120px;"
                                                        >
                                                            Operaciones
                                                        </th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody
                                                    v-if="form.items.length > 0"
                                                >
                                                    <tr
                                                        v-for="(row,
                                                        index) in form.items"
                                                        :key="index"
                                                    >
                                                        <td class="text-center">
                                                            <input
                                                                type="radio"
                                                                name="selectedWorker"
                                                                :value="row.id"
                                                                v-model="
                                                                    selectedWorkerId
                                                                "
                                                                @change="
                                                                    handleWorkerSelection(
                                                                        row.id
                                                                    )
                                                                "
                                                            />
                                                        </td>
                                                        <td>{{ index + 1 }}</td>
                                                        <td>{{ row.code }}</td>
                                                        <td class="text-left">
                                                            {{
                                                                row.search_fullname ||
                                                                    row.fullname
                                                            }}
                                                        </td>
                                                        <td class="text-right">
                                                            {{
                                                                row.payroll_type_document_identification_name ||
                                                                    "N/A"
                                                            }}
                                                        </td>
                                                        <td class="text-right">
                                                            {{
                                                                getFormatDecimal(
                                                                    row.salary
                                                                )
                                                            }}
                                                        </td>
                                                        <td class="text-right">
                                                            {{ row.cellphone }}
                                                        </td>
                                                        <td class="text-right">
                                                            {{ row.position }}
                                                        </td>
                                                        <td class="text-right">
                                                            <el-switch
                                                                v-model="
                                                                    row.generate_provisions
                                                                "
                                                                :disabled="
                                                                    globalGenerateProvisions
                                                                "
                                                                @change="
                                                                    onGenerateProvisionsChange(
                                                                        row.id,
                                                                        $event
                                                                    )
                                                                "
                                                            ></el-switch>
                                                        </td>
                                                        <td class="text-right">
                                                            <button
                                                                type="button"
                                                                class="btn waves-effect waves-light btn-xs btn-danger"
                                                                @click.prevent="
                                                                    removeItem(
                                                                        index
                                                                    )
                                                                "
                                                            >
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
                                        <div
                                            class="form-group"
                                            :class="{
                                                'has-danger':
                                                    errors[
                                                        'period.admision_date'
                                                    ]
                                            }"
                                        >
                                            <label class="control-label"
                                                >Fecha de admisión<span
                                                    class="text-danger"
                                                >
                                                    *</span
                                                >
                                                <el-tooltip
                                                    class="item"
                                                    effect="dark"
                                                    content="Fecha de inicio de labores del empleado"
                                                    placement="top-start"
                                                >
                                                    <i
                                                        class="fa fa-info-circle"
                                                    ></i>
                                                </el-tooltip>
                                            </label>
                                            <el-date-picker
                                                v-model="
                                                    form.period.admision_date
                                                "
                                                type="date"
                                                value-format="yyyy-MM-dd"
                                                :clearable="false"
                                                :key="
                                                    'date-' + selectedWorkerId
                                                "
                                                @change="
                                                    handleAdmisionDateChange
                                                "
                                            ></el-date-picker>
                                            <small
                                                class="form-control-feedback"
                                                v-if="
                                                    errors[
                                                        'period.admision_date'
                                                    ]
                                                "
                                                v-text="
                                                    errors[
                                                        'period.admision_date'
                                                    ][0]
                                                "
                                            ></small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div
                                            class="form-group"
                                            :class="{
                                                'has-danger':
                                                    errors['period.worked_time']
                                            }"
                                        >
                                            <label class="control-label"
                                                >Días trabajados<span
                                                    class="text-danger"
                                                >
                                                    *</span
                                                ></label
                                            >
                                            <el-input-number
                                                v-model="
                                                    form.period.worked_time
                                                "
                                                :min="0"
                                                controls-position="right"
                                                :key="
                                                    'number-' + selectedWorkerId
                                                "
                                                @change="handleWorkedTimeChange"
                                            ></el-input-number>
                                            <small
                                                class="form-control-feedback"
                                                v-if="
                                                    errors['period.worked_time']
                                                "
                                                v-text="
                                                    errors[
                                                        'period.worked_time'
                                                    ][0]
                                                "
                                            ></small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div
                                            class="form-group"
                                            :class="{
                                                'has-danger':
                                                    errors.payroll_period_id
                                            }"
                                        >
                                            <label class="control-label"
                                                >Periodo de nómina<span
                                                    class="text-danger"
                                                >
                                                    *</span
                                                >
                                                <el-tooltip
                                                    class="item"
                                                    effect="dark"
                                                    content="Frecuencia de pago"
                                                    placement="top-start"
                                                >
                                                    <i
                                                        class="fa fa-info-circle"
                                                    ></i>
                                                </el-tooltip>
                                            </label>
                                            <el-select
                                                v-model="form.payroll_period_id"
                                                filterable
                                                class="border-left rounded-left border-info"
                                                @change="
                                                    handlePayrollPeriodChange
                                                "
                                                placeholder="Seleccione periodo"
                                                :key="
                                                    'select-' + selectedWorkerId
                                                "
                                            >
                                                <el-option
                                                    v-for="option in form.tables
                                                        .payroll_periods"
                                                    :key="option.id"
                                                    :value="option.id"
                                                    :label="option.name"
                                                ></el-option>
                                            </el-select>
                                            <small
                                                class="form-control-feedback"
                                                v-if="errors.payroll_period_id"
                                                v-text="
                                                    errors.payroll_period_id[0]
                                                "
                                            ></small>
                                        </div>
                                    </div>
                                </div>
                            </el-tab-pane>
                            <el-tab-pane label="Pagos" name="payments">
                                <div class="row" v-show="selectedWorkerId">
                                    <div class="col-md-3">
                                        <div
                                            class="form-group"
                                            :class="{
                                                'has-danger':
                                                    errors[
                                                        'payment.payment_method_id'
                                                    ]
                                            }"
                                        >
                                            <label class="control-label"
                                                >Métodos de pago<span
                                                    class="text-danger"
                                                >
                                                    *</span
                                                ></label
                                            >
                                            <el-select
                                                v-model="
                                                    form.payment
                                                        .payment_method_id
                                                "
                                                filterable
                                                @change="changePaymentMethod"
                                                :key="
                                                    'payment-method-' +
                                                        selectedWorkerId
                                                "
                                            >
                                                <el-option
                                                    v-for="option in form.tables
                                                        .payment_methods"
                                                    :key="option.id"
                                                    :value="option.id"
                                                    :label="option.name"
                                                ></el-option>
                                            </el-select>
                                            <small
                                                class="form-control-feedback"
                                                v-if="
                                                    errors[
                                                        'payment.payment_method_id'
                                                    ]
                                                "
                                                v-text="
                                                    errors[
                                                        'payment.payment_method_id'
                                                    ][0]
                                                "
                                            ></small>
                                        </div>
                                    </div>

                                    <template v-if="show_inputs_payment_method">
                                        <div class="col-md-3">
                                            <div
                                                class="form-group"
                                                :class="{
                                                    'has-danger':
                                                        errors[
                                                            'payment.bank_name'
                                                        ]
                                                }"
                                            >
                                                <label class="control-label"
                                                    >Nombre del banco</label
                                                >
                                                <el-input
                                                    v-model="
                                                        form.payment.bank_name
                                                    "
                                                    :key="
                                                        'bank-' +
                                                            selectedWorkerId
                                                    "
                                                    @input="
                                                        saveCurrentEmployeePaymentData
                                                    "
                                                    @blur="
                                                        saveCurrentEmployeePaymentData
                                                    "
                                                ></el-input>
                                                <small
                                                    class="form-control-feedback"
                                                    v-if="
                                                        errors[
                                                            'payment.bank_name'
                                                        ]
                                                    "
                                                    v-text="
                                                        errors[
                                                            'payment.bank_name'
                                                        ][0]
                                                    "
                                                ></small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div
                                                class="form-group"
                                                :class="{
                                                    'has-danger':
                                                        errors[
                                                            'payment.account_type'
                                                        ]
                                                }"
                                            >
                                                <label class="control-label"
                                                    >Tipo de cuenta</label
                                                >
                                                <el-input
                                                    v-model="
                                                        form.payment
                                                            .account_type
                                                    "
                                                    :key="
                                                        'account-type-' +
                                                            selectedWorkerId
                                                    "
                                                    @input="
                                                        saveCurrentEmployeePaymentData
                                                    "
                                                    @blur="
                                                        saveCurrentEmployeePaymentData
                                                    "
                                                ></el-input>
                                                <small
                                                    class="form-control-feedback"
                                                    v-if="
                                                        errors[
                                                            'payment.account_type'
                                                        ]
                                                    "
                                                    v-text="
                                                        errors[
                                                            'payment.account_type'
                                                        ][0]
                                                    "
                                                ></small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div
                                                class="form-group"
                                                :class="{
                                                    'has-danger':
                                                        errors[
                                                            'payment.account_number'
                                                        ]
                                                }"
                                            >
                                                <label class="control-label"
                                                    >Número de cuenta</label
                                                >
                                                <el-input
                                                    v-model="
                                                        form.payment
                                                            .account_number
                                                    "
                                                    :key="
                                                        'account-number-' +
                                                            selectedWorkerId
                                                    "
                                                    @input="
                                                        saveCurrentEmployeePaymentData
                                                    "
                                                    @blur="
                                                        saveCurrentEmployeePaymentData
                                                    "
                                                ></el-input>
                                                <small
                                                    class="form-control-feedback"
                                                    v-if="
                                                        errors[
                                                            'payment.account_number'
                                                        ]
                                                    "
                                                    v-text="
                                                        errors[
                                                            'payment.account_number'
                                                        ][0]
                                                    "
                                                ></small>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <div
                                            class="form-group"
                                            :class="{
                                                'has-danger':
                                                    errors['payment_dates']
                                            }"
                                        >
                                            <h4>
                                                Fechas de pago<span
                                                    class="text-danger"
                                                >
                                                    *</span
                                                >
                                            </h4>
                                            <small
                                                class="form-control-feedback"
                                                v-if="errors['payment_dates']"
                                                v-text="
                                                    errors['payment_dates'][0]
                                                "
                                            ></small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <table>
                                            <thead>
                                                <tr width="100%">
                                                    <th
                                                        v-if="
                                                            form.payment_dates
                                                                .length > 0
                                                        "
                                                        class="pb-2"
                                                    >
                                                        Fecha<span
                                                            class="text-danger"
                                                        >
                                                            *</span
                                                        >
                                                    </th>
                                                    <th width="30%">
                                                        <a
                                                            href="#"
                                                            @click.prevent="
                                                                clickAddPaymentDate()
                                                            "
                                                            class="text-center font-weight-bold text-info pb-1 mt-1"
                                                            >[+ Agregar]</a
                                                        >
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr
                                                    v-for="(row,
                                                    index) in form.payment_dates"
                                                    :key="index"
                                                >
                                                    <td>
                                                        <div
                                                            class="form-group mb-2 mr-2"
                                                        >
                                                            <el-date-picker
                                                                v-model="
                                                                    row.payment_date
                                                                "
                                                                type="date"
                                                                value-format="yyyy-MM-dd"
                                                                :clearable="
                                                                    false
                                                                "
                                                                @change="
                                                                    handlePaymentDateChange
                                                                "
                                                            ></el-date-picker>
                                                        </div>
                                                    </td>
                                                    <td
                                                        class="series-table-actions text-center"
                                                    >
                                                        <button
                                                            type="button"
                                                            class="btn waves-effect waves-light btn-xs btn-danger"
                                                            @click.prevent="
                                                                clickCancelPaymentDate(
                                                                    index
                                                                )
                                                            "
                                                        >
                                                            <i
                                                                class="fa fa-trash"
                                                            ></i>
                                                        </button>
                                                    </td>
                                                    <br />
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </el-tab-pane>
                            <el-tab-pane label="Devengados" name="accrued">
                                <div class="row">
                                    <template v-if="isAdjustNote">
                                        <div class="col-md-3">
                                            <div
                                                class="form-group"
                                                :class="{
                                                    'has-danger':
                                                        errors[
                                                            'accrued.total_base_salary'
                                                        ]
                                                }"
                                            >
                                                <label class="control-label"
                                                    >Salario base
                                                    <span class="text-danger">
                                                        *</span
                                                    >
                                                    <el-tooltip
                                                        class="item"
                                                        effect="dark"
                                                        content="Salario base del empleado (equivalente a 30 días), no se afecta por los días trabajados"
                                                        placement="top-start"
                                                    >
                                                        <i
                                                            class="fa fa-info-circle"
                                                        ></i>
                                                    </el-tooltip>
                                                </label>
                                                <el-input-number
                                                    v-model="
                                                        form.accrued
                                                            .total_base_salary
                                                    "
                                                    controls-position="right"
                                                    @change="
                                                        changeTotalBaseSalary
                                                    "
                                                ></el-input-number>
                                                <small
                                                    class="form-control-feedback"
                                                    v-if="
                                                        errors[
                                                            'accrued.total_base_salary'
                                                        ]
                                                    "
                                                    v-text="
                                                        errors[
                                                            'accrued.total_base_salary'
                                                        ][0]
                                                    "
                                                ></small>
                                            </div>
                                        </div>
                                    </template>

                                    <div class="col-md-3">
                                        <div
                                            class="form-group"
                                            :class="{
                                                'has-danger':
                                                    errors[
                                                        'accrued.worked_days'
                                                    ]
                                            }"
                                        >
                                            <label class="control-label"
                                                >Días trabajados<span
                                                    class="text-danger"
                                                >
                                                    *</span
                                                ></label
                                            >
                                            <el-input-number
                                                v-model="
                                                    form.accrued.worked_days
                                                "
                                                :min="0"
                                                :max="30"
                                                :precision="0"
                                                controls-position="right"
                                                @change="changeWorkedDays"
                                            ></el-input-number>
                                            <small
                                                class="form-control-feedback"
                                                v-if="
                                                    errors[
                                                        'accrued.worked_days'
                                                    ]
                                                "
                                                v-text="
                                                    errors[
                                                        'accrued.worked_days'
                                                    ][0]
                                                "
                                            ></small>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div
                                            class="form-group"
                                            :class="{
                                                'has-danger':
                                                    errors['accrued.salary']
                                            }"
                                        >
                                            <label class="control-label"
                                                >Salario<span
                                                    class="text-danger"
                                                >
                                                    *</span
                                                ></label
                                            >
                                            <el-input-number
                                                v-model="form.accrued.salary"
                                                :min="0"
                                                controls-position="right"
                                                disabled
                                            ></el-input-number>
                                            <small
                                                class="form-control-feedback"
                                                v-if="errors['accrued.salary']"
                                                v-text="
                                                    errors['accrued.salary'][0]
                                                "
                                            ></small>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div
                                            class="form-group"
                                            :class="{
                                                'has-danger':
                                                    errors[
                                                        'accrued.transportation_allowance'
                                                    ]
                                            }"
                                        >
                                            <label class="control-label"
                                                >Subsidio de transporte</label
                                            >
                                            <el-input-number
                                                v-model="
                                                    form.accrued
                                                        .transportation_allowance
                                                "
                                                :min="0"
                                                :disabled="
                                                    form_disabled.inputs_type_worker_sena
                                                "
                                                controls-position="right"
                                                @change="
                                                    changeTransportationAllowance
                                                "
                                            ></el-input-number>
                                            <small
                                                class="form-control-feedback"
                                                v-if="
                                                    errors[
                                                        'accrued.transportation_allowance'
                                                    ]
                                                "
                                                v-text="
                                                    errors[
                                                        'accrued.transportation_allowance'
                                                    ][0]
                                                "
                                            ></small>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div
                                            class="form-group"
                                            :class="{
                                                'has-danger':
                                                    errors[
                                                        'accrued.accrued_total'
                                                    ]
                                            }"
                                        >
                                            <label class="control-label"
                                                >Total devengados<span
                                                    class="text-danger"
                                                >
                                                    *</span
                                                ></label
                                            >
                                            <el-input-number
                                                v-model="
                                                    form.accrued.accrued_total
                                                "
                                                :min="0"
                                                controls-position="right"
                                                disabled
                                            ></el-input-number>
                                            <small
                                                class="form-control-feedback"
                                                v-if="
                                                    errors[
                                                        'accrued.accrued_total'
                                                    ]
                                                "
                                                v-text="
                                                    errors[
                                                        'accrued.accrued_total'
                                                    ][0]
                                                "
                                            ></small>
                                        </div>
                                    </div>

                                    <div class="col-md-12 mt-3">
                                        <div class="form-group">
                                            <button
                                                type="button"
                                                class="btn btn-md waves-effect waves-light btn-primary"
                                                @click.prevent="
                                                    clickAddExtraHours
                                                "
                                            >
                                                Agregar Horas Extras
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <el-tabs
                                    type="border-card"
                                    v-model="activeNameAccrued"
                                    class="mt-4"
                                >
                                    <el-tab-pane
                                        label="Vacaciones"
                                        name="accrued-vacations"
                                    >
                                        <!-- Vacaciones disfrutadas -->
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <div
                                                    class="form-group"
                                                    :class="{
                                                        'has-danger':
                                                            errors[
                                                                'accrued.common_vacation'
                                                            ]
                                                    }"
                                                >
                                                    <h4>
                                                        Vacaciones disfrutadas
                                                    </h4>
                                                    <small
                                                        class="form-control-feedback"
                                                        v-if="
                                                            errors[
                                                                'accrued.common_vacation'
                                                            ]
                                                        "
                                                        v-text="
                                                            errors[
                                                                'accrued.common_vacation'
                                                            ][0]
                                                        "
                                                    ></small>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <table>
                                                    <thead>
                                                        <tr width="100%">
                                                            <template
                                                                v-if="
                                                                    form.accrued
                                                                        .common_vacation
                                                                        .length >
                                                                        0
                                                                "
                                                            >
                                                                <th
                                                                    class="pb-2"
                                                                >
                                                                    Fecha inicio
                                                                    - Fecha
                                                                    término
                                                                </th>
                                                                <th
                                                                    class="pb-2"
                                                                >
                                                                    N° de días
                                                                </th>
                                                                <th
                                                                    class="pb-2"
                                                                >
                                                                    Pago
                                                                </th>
                                                            </template>
                                                            <th width="10%">
                                                                <a
                                                                    href="#"
                                                                    @click.prevent="
                                                                        clickAddCommonVacation
                                                                    "
                                                                    class="text-center font-weight-bold text-info pb-1 mt-1"
                                                                    >[+
                                                                    Agregar]</a
                                                                >
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="(row,
                                                            index) in form
                                                                .accrued
                                                                .common_vacation"
                                                            :key="index"
                                                        >
                                                            <td>
                                                                <div
                                                                    class="form-group mb-2 mr-2"
                                                                >
                                                                    <el-date-picker
                                                                        v-model="
                                                                            row.start_end_date
                                                                        "
                                                                        type="daterange"
                                                                        format="yyyy-MM-dd"
                                                                        value-format="yyyy-MM-dd"
                                                                        range-separator="H"
                                                                        :clearable="
                                                                            false
                                                                        "
                                                                        @change="
                                                                            changeCommonVacationStartEndDate(
                                                                                index
                                                                            )
                                                                        "
                                                                    >
                                                                    </el-date-picker>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-group"
                                                                    v-if="
                                                                        errors[
                                                                            `accrued.common_vacation.${index}.quantity`
                                                                        ]
                                                                    "
                                                                    :class="{
                                                                        'has-danger':
                                                                            errors[
                                                                                `accrued.common_vacation.${index}.quantity`
                                                                            ]
                                                                    }"
                                                                >
                                                                    <small
                                                                        class="form-control-feedback"
                                                                        v-text="
                                                                            errors[
                                                                                `accrued.common_vacation.${index}.quantity`
                                                                            ][0]
                                                                        "
                                                                    ></small>
                                                                </div>
                                                                <div
                                                                    class="form-group mb-2 mr-2"
                                                                >
                                                                    <el-input-number
                                                                        v-model="
                                                                            row.quantity
                                                                        "
                                                                        :min="0"
                                                                        controls-position="right"
                                                                        disabled
                                                                    ></el-input-number>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-group"
                                                                    v-if="
                                                                        errors[
                                                                            `accrued.common_vacation.${index}.payment`
                                                                        ]
                                                                    "
                                                                    :class="{
                                                                        'has-danger':
                                                                            errors[
                                                                                `accrued.common_vacation.${index}.payment`
                                                                            ]
                                                                    }"
                                                                >
                                                                    <small
                                                                        class="form-control-feedback"
                                                                        v-text="
                                                                            errors[
                                                                                `accrued.common_vacation.${index}.payment`
                                                                            ][0]
                                                                        "
                                                                    ></small>
                                                                </div>
                                                                <div
                                                                    class="form-group mb-2 mr-2"
                                                                >
                                                                    <el-input-number
                                                                        v-model="
                                                                            row.payment
                                                                        "
                                                                        :min="0"
                                                                        controls-position="right"
                                                                        @change="
                                                                            changePaymentCommonVacation(
                                                                                index
                                                                            )
                                                                        "
                                                                    ></el-input-number>
                                                                </div>
                                                            </td>

                                                            <td
                                                                class="series-table-actions text-center"
                                                            >
                                                                <button
                                                                    type="button"
                                                                    class="btn waves-effect waves-light btn-xs btn-danger"
                                                                    @click.prevent="
                                                                        clickCancelCommonVacation(
                                                                            index
                                                                        )
                                                                    "
                                                                >
                                                                    <i
                                                                        class="fa fa-trash"
                                                                    ></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <!-- Vacaciones disfrutadas -->

                                        <!-- Vacaciones compensadas -->
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <div
                                                    class="form-group"
                                                    :class="{
                                                        'has-danger':
                                                            errors[
                                                                'accrued.paid_vacation'
                                                            ]
                                                    }"
                                                >
                                                    <h4>
                                                        Vacaciones compensadas
                                                    </h4>
                                                    <small
                                                        class="form-control-feedback"
                                                        v-if="
                                                            errors[
                                                                'accrued.paid_vacation'
                                                            ]
                                                        "
                                                        v-text="
                                                            errors[
                                                                'accrued.paid_vacation'
                                                            ][0]
                                                        "
                                                    ></small>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <table>
                                                    <thead>
                                                        <tr width="100%">
                                                            <template
                                                                v-if="
                                                                    form.accrued
                                                                        .paid_vacation
                                                                        .length >
                                                                        0
                                                                "
                                                            >
                                                                <th
                                                                    class="pb-2"
                                                                >
                                                                    Fecha inicio
                                                                    - Fecha
                                                                    término
                                                                </th>
                                                                <th
                                                                    class="pb-2"
                                                                >
                                                                    N° de días
                                                                </th>
                                                                <th
                                                                    class="pb-2"
                                                                >
                                                                    Pago
                                                                </th>
                                                            </template>
                                                            <th width="10%">
                                                                <a
                                                                    href="#"
                                                                    @click.prevent="
                                                                        clickAddPaidVacation
                                                                    "
                                                                    class="text-center font-weight-bold text-info pb-1 mt-1"
                                                                    >[+
                                                                    Agregar]</a
                                                                >
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="(row,
                                                            index) in form
                                                                .accrued
                                                                .paid_vacation"
                                                            :key="index"
                                                        >
                                                            <td>
                                                                <div
                                                                    class="form-group mb-2 mr-2"
                                                                >
                                                                    <el-date-picker
                                                                        v-model="
                                                                            row.start_end_date
                                                                        "
                                                                        type="daterange"
                                                                        format="yyyy-MM-dd"
                                                                        value-format="yyyy-MM-dd"
                                                                        range-separator="H"
                                                                        :clearable="
                                                                            false
                                                                        "
                                                                        @change="
                                                                            changePaidVacationStartEndDate(
                                                                                index
                                                                            )
                                                                        "
                                                                    >
                                                                    </el-date-picker>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-group"
                                                                    v-if="
                                                                        errors[
                                                                            `accrued.paid_vacation.${index}.quantity`
                                                                        ]
                                                                    "
                                                                    :class="{
                                                                        'has-danger':
                                                                            errors[
                                                                                `accrued.paid_vacation.${index}.quantity`
                                                                            ]
                                                                    }"
                                                                >
                                                                    <small
                                                                        class="form-control-feedback"
                                                                        v-text="
                                                                            errors[
                                                                                `accrued.paid_vacation.${index}.quantity`
                                                                            ][0]
                                                                        "
                                                                    ></small>
                                                                </div>
                                                                <div
                                                                    class="form-group mb-2 mr-2"
                                                                >
                                                                    <el-input-number
                                                                        v-model="
                                                                            row.quantity
                                                                        "
                                                                        :min="0"
                                                                        controls-position="right"
                                                                    ></el-input-number>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-group"
                                                                    v-if="
                                                                        errors[
                                                                            `accrued.paid_vacation.${index}.payment`
                                                                        ]
                                                                    "
                                                                    :class="{
                                                                        'has-danger':
                                                                            errors[
                                                                                `accrued.paid_vacation.${index}.payment`
                                                                            ]
                                                                    }"
                                                                >
                                                                    <small
                                                                        class="form-control-feedback"
                                                                        v-text="
                                                                            errors[
                                                                                `accrued.paid_vacation.${index}.payment`
                                                                            ][0]
                                                                        "
                                                                    ></small>
                                                                </div>
                                                                <div
                                                                    class="form-group mb-2 mr-2"
                                                                >
                                                                    <el-input-number
                                                                        v-model="
                                                                            row.payment
                                                                        "
                                                                        :min="0"
                                                                        controls-position="right"
                                                                        @change="
                                                                            changePaymentPaidVacation(
                                                                                index
                                                                            )
                                                                        "
                                                                    ></el-input-number>
                                                                </div>
                                                            </td>

                                                            <td
                                                                class="series-table-actions text-center"
                                                            >
                                                                <button
                                                                    type="button"
                                                                    class="btn waves-effect waves-light btn-xs btn-danger"
                                                                    @click.prevent="
                                                                        clickCancelPaidVacation(
                                                                            index
                                                                        )
                                                                    "
                                                                >
                                                                    <i
                                                                        class="fa fa-trash"
                                                                    ></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <!-- Vacaciones compensadas -->
                                    </el-tab-pane>

                                    <el-tab-pane
                                        label="Prestación social"
                                        name="accrued-social"
                                    >
                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <div
                                                    class="form-group"
                                                    :class="{
                                                        'has-danger':
                                                            errors[
                                                                'accrued.service_bonus'
                                                            ]
                                                    }"
                                                >
                                                    <h4>Prima de servicio</h4>
                                                    <small
                                                        class="form-control-feedback"
                                                        v-if="
                                                            errors[
                                                                'accrued.service_bonus'
                                                            ]
                                                        "
                                                        v-text="
                                                            errors[
                                                                'accrued.service_bonus'
                                                            ][0]
                                                        "
                                                    ></small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div
                                                    class="form-group"
                                                    :class="{
                                                        'has-danger':
                                                            errors[
                                                                'accrued.severance'
                                                            ]
                                                    }"
                                                >
                                                    <h4>Cesantías</h4>
                                                    <small
                                                        class="form-control-feedback"
                                                        v-if="
                                                            errors[
                                                                'accrued.severance'
                                                            ]
                                                        "
                                                        v-text="
                                                            errors[
                                                                'accrued.severance'
                                                            ][0]
                                                        "
                                                    ></small>
                                                </div>
                                            </div>

                                            <!-- Prima de servicio -->
                                            <div class="col-md-6">
                                                <table>
                                                    <thead>
                                                        <tr width="100%">
                                                            <template
                                                                v-if="
                                                                    form.accrued
                                                                        .service_bonus
                                                                        .length >
                                                                        0
                                                                "
                                                            >
                                                                <th
                                                                    class="pb-2"
                                                                >
                                                                    N° de días
                                                                </th>
                                                                <th
                                                                    class="pb-2"
                                                                >
                                                                    Prima
                                                                    salarial
                                                                </th>
                                                                <th
                                                                    class="pb-2"
                                                                >
                                                                    Prima no
                                                                    salarial
                                                                </th>
                                                            </template>
                                                            <th width="15%">
                                                                <a
                                                                    href="#"
                                                                    @click.prevent="
                                                                        clickAddServiceBonus
                                                                    "
                                                                    class="text-center font-weight-bold text-info pb-1 mt-1"
                                                                    >[+
                                                                    Agregar]</a
                                                                >
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="(row,
                                                            index) in form
                                                                .accrued
                                                                .service_bonus"
                                                            :key="index"
                                                        >
                                                            <td>
                                                                <div
                                                                    class="form-group"
                                                                    v-if="
                                                                        errors[
                                                                            `accrued.service_bonus.${index}.quantity`
                                                                        ]
                                                                    "
                                                                    :class="{
                                                                        'has-danger':
                                                                            errors[
                                                                                `accrued.service_bonus.${index}.quantity`
                                                                            ]
                                                                    }"
                                                                >
                                                                    <small
                                                                        class="form-control-feedback"
                                                                        v-text="
                                                                            errors[
                                                                                `accrued.service_bonus.${index}.quantity`
                                                                            ][0]
                                                                        "
                                                                    ></small>
                                                                </div>
                                                                <div
                                                                    class="form-group mb-2 mr-2"
                                                                >
                                                                    <el-input-number
                                                                        v-model="
                                                                            row.quantity
                                                                        "
                                                                        :min="0"
                                                                        controls-position="right"
                                                                        @change="
                                                                            changeQuantityServiceBonus(
                                                                                index
                                                                            )
                                                                        "
                                                                    ></el-input-number>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-group"
                                                                    v-if="
                                                                        errors[
                                                                            `accrued.service_bonus.${index}.payment`
                                                                        ]
                                                                    "
                                                                    :class="{
                                                                        'has-danger':
                                                                            errors[
                                                                                `accrued.service_bonus.${index}.payment`
                                                                            ]
                                                                    }"
                                                                >
                                                                    <small
                                                                        class="form-control-feedback"
                                                                        v-text="
                                                                            errors[
                                                                                `accrued.service_bonus.${index}.payment`
                                                                            ][0]
                                                                        "
                                                                    ></small>
                                                                </div>
                                                                <div
                                                                    class="form-group mb-2 mr-2"
                                                                >
                                                                    <el-input-number
                                                                        v-model="
                                                                            row.payment
                                                                        "
                                                                        :min="0"
                                                                        controls-position="right"
                                                                        @change="
                                                                            changePaymentServiceBonus(
                                                                                index
                                                                            )
                                                                        "
                                                                    ></el-input-number>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-group"
                                                                    v-if="
                                                                        errors[
                                                                            `accrued.service_bonus.${index}.paymentNS`
                                                                        ]
                                                                    "
                                                                    :class="{
                                                                        'has-danger':
                                                                            errors[
                                                                                `accrued.service_bonus.${index}.paymentNS`
                                                                            ]
                                                                    }"
                                                                >
                                                                    <small
                                                                        class="form-control-feedback"
                                                                        v-text="
                                                                            errors[
                                                                                `accrued.service_bonus.${index}.paymentNS`
                                                                            ][0]
                                                                        "
                                                                    ></small>
                                                                </div>
                                                                <div
                                                                    class="form-group mb-2 mr-2"
                                                                >
                                                                    <el-input-number
                                                                        v-model="
                                                                            row.paymentNS
                                                                        "
                                                                        :min="0"
                                                                        controls-position="right"
                                                                        @change="
                                                                            changePaymentNSServiceBonus(
                                                                                index
                                                                            )
                                                                        "
                                                                    ></el-input-number>
                                                                </div>
                                                            </td>

                                                            <td
                                                                class="series-table-actions text-center"
                                                            >
                                                                <button
                                                                    type="button"
                                                                    class="btn waves-effect waves-light btn-xs btn-danger"
                                                                    @click.prevent="
                                                                        clickCancelServiceBonus(
                                                                            index
                                                                        )
                                                                    "
                                                                >
                                                                    <i
                                                                        class="fa fa-trash"
                                                                    ></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <!-- Prima de servicio -->

                                            <!-- Cesantías -->
                                            <div class="col-md-6">
                                                <table>
                                                    <thead>
                                                        <tr width="100%">
                                                            <template
                                                                v-if="
                                                                    form.accrued
                                                                        .severance
                                                                        .length >
                                                                        0
                                                                "
                                                            >
                                                                <th
                                                                    class="pb-2"
                                                                >
                                                                    N° de días
                                                                </th>
                                                                <th
                                                                    class="pb-2"
                                                                >
                                                                    Pago
                                                                    cesantías
                                                                </th>
                                                                <th
                                                                    class="pb-2"
                                                                >
                                                                    % Interes
                                                                </th>
                                                                <th
                                                                    class="pb-2"
                                                                >
                                                                    Pago
                                                                    intereses
                                                                </th>
                                                            </template>
                                                            <th width="15%">
                                                                <a
                                                                    href="#"
                                                                    @click.prevent="
                                                                        clickAddSeverance
                                                                    "
                                                                    class="text-center font-weight-bold text-info pb-1 mt-1"
                                                                    >[+
                                                                    Agregar]</a
                                                                >
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="(row,
                                                            index) in form
                                                                .accrued
                                                                .severance"
                                                            :key="index"
                                                        >
                                                            <td>
                                                                <div
                                                                    class="form-group"
                                                                    v-if="
                                                                        errors[
                                                                            `accrued.severance.${index}.quantity`
                                                                        ]
                                                                    "
                                                                    :class="{
                                                                        'has-danger':
                                                                            errors[
                                                                                `accrued.severance.${index}.quantity`
                                                                            ]
                                                                    }"
                                                                >
                                                                    <small
                                                                        class="form-control-feedback"
                                                                        v-text="
                                                                            errors[
                                                                                `accrued.severance.${index}.quantity`
                                                                            ][0]
                                                                        "
                                                                    ></small>
                                                                </div>
                                                                <div
                                                                    class="form-group mb-2 mr-2"
                                                                >
                                                                    <el-input-number
                                                                        v-model="
                                                                            row.quantity
                                                                        "
                                                                        :min="0"
                                                                        controls-position="right"
                                                                        @change="
                                                                            changeQuantitySeverance(
                                                                                index
                                                                            )
                                                                        "
                                                                    ></el-input-number>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-group"
                                                                    v-if="
                                                                        errors[
                                                                            `accrued.severance.${index}.payment`
                                                                        ]
                                                                    "
                                                                    :class="{
                                                                        'has-danger':
                                                                            errors[
                                                                                `accrued.severance.${index}.payment`
                                                                            ]
                                                                    }"
                                                                >
                                                                    <small
                                                                        class="form-control-feedback"
                                                                        v-text="
                                                                            errors[
                                                                                `accrued.severance.${index}.payment`
                                                                            ][0]
                                                                        "
                                                                    ></small>
                                                                </div>
                                                                <div
                                                                    class="form-group mb-2 mr-2"
                                                                >
                                                                    <el-input-number
                                                                        v-model="
                                                                            row.payment
                                                                        "
                                                                        :min="0"
                                                                        controls-position="right"
                                                                        @change="
                                                                            calculateInterestPayment(
                                                                                index
                                                                            )
                                                                        "
                                                                    ></el-input-number>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-group"
                                                                    v-if="
                                                                        errors[
                                                                            `accrued.severance.${index}.percentage`
                                                                        ]
                                                                    "
                                                                    :class="{
                                                                        'has-danger':
                                                                            errors[
                                                                                `accrued.severance.${index}.percentage`
                                                                            ]
                                                                    }"
                                                                >
                                                                    <small
                                                                        class="form-control-feedback"
                                                                        v-text="
                                                                            errors[
                                                                                `accrued.severance.${index}.percentage`
                                                                            ][0]
                                                                        "
                                                                    ></small>
                                                                </div>
                                                                <div
                                                                    class="form-group mb-2 mr-2"
                                                                >
                                                                    <el-input-number
                                                                        v-model="
                                                                            row.percentage
                                                                        "
                                                                        :min="0"
                                                                        controls-position="right"
                                                                        @change="
                                                                            calculateInterestPayment(
                                                                                index
                                                                            )
                                                                        "
                                                                    ></el-input-number>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-group"
                                                                    v-if="
                                                                        errors[
                                                                            `accrued.severance.${index}.interest_payment`
                                                                        ]
                                                                    "
                                                                    :class="{
                                                                        'has-danger':
                                                                            errors[
                                                                                `accrued.severance.${index}.interest_payment`
                                                                            ]
                                                                    }"
                                                                >
                                                                    <small
                                                                        class="form-control-feedback"
                                                                        v-text="
                                                                            errors[
                                                                                `accrued.severance.${index}.interest_payment`
                                                                            ][0]
                                                                        "
                                                                    ></small>
                                                                </div>
                                                                <div
                                                                    class="form-group mb-2 mr-2"
                                                                >
                                                                    <el-input-number
                                                                        v-model="
                                                                            row.interest_payment
                                                                        "
                                                                        :min="0"
                                                                        controls-position="right"
                                                                    ></el-input-number>
                                                                </div>
                                                            </td>

                                                            <td
                                                                class="series-table-actions text-center"
                                                            >
                                                                <button
                                                                    type="button"
                                                                    class="btn waves-effect waves-light btn-xs btn-danger"
                                                                    @click.prevent="
                                                                        clickCancelSeverance(
                                                                            index
                                                                        )
                                                                    "
                                                                >
                                                                    <i
                                                                        class="fa fa-trash"
                                                                    ></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <!-- Cesantías -->
                                        </div>
                                    </el-tab-pane>

                                    <el-tab-pane
                                        label="Otros"
                                        name="accrued-others"
                                    >
                                        <div class="row mt-2">
                                            <div class="col-md-6">
                                                <div
                                                    class="form-group"
                                                    :class="{
                                                        'has-danger':
                                                            errors[
                                                                'accrued.bonuses'
                                                            ]
                                                    }"
                                                >
                                                    <h4>Bonificaciones</h4>
                                                    <small
                                                        class="form-control-feedback"
                                                        v-if="
                                                            errors[
                                                                'accrued.bonuses'
                                                            ]
                                                        "
                                                        v-text="
                                                            errors[
                                                                'accrued.bonuses'
                                                            ][0]
                                                        "
                                                    ></small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div
                                                    class="form-group"
                                                    :class="{
                                                        'has-danger':
                                                            errors[
                                                                'accrued.aid'
                                                            ]
                                                    }"
                                                >
                                                    <h4>Ayudas</h4>
                                                    <small
                                                        class="form-control-feedback"
                                                        v-if="
                                                            errors[
                                                                'accrued.aid'
                                                            ]
                                                        "
                                                        v-text="
                                                            errors[
                                                                'accrued.aid'
                                                            ][0]
                                                        "
                                                    ></small>
                                                </div>
                                            </div>

                                            <!-- Bonificaciones -->
                                            <div class="col-md-6">
                                                <table>
                                                    <thead>
                                                        <tr width="100%">
                                                            <template
                                                                v-if="
                                                                    form.accrued
                                                                        .bonuses
                                                                        .length >
                                                                        0
                                                                "
                                                            >
                                                                <th
                                                                    class="pb-2"
                                                                >
                                                                    Bonificación
                                                                    salarial
                                                                </th>
                                                                <th
                                                                    class="pb-2"
                                                                >
                                                                    Bonificación
                                                                    no salarial
                                                                </th>
                                                            </template>
                                                            <th width="15%">
                                                                <a
                                                                    href="#"
                                                                    @click.prevent="
                                                                        clickAddBonuses
                                                                    "
                                                                    class="text-center font-weight-bold text-info pb-1 mt-1"
                                                                    >[+
                                                                    Agregar]</a
                                                                >
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="(row,
                                                            index) in form
                                                                .accrued
                                                                .bonuses"
                                                            :key="index"
                                                        >
                                                            <td>
                                                                <div
                                                                    class="form-group"
                                                                    v-if="
                                                                        errors[
                                                                            `accrued.bonuses.${index}.salary_bonus`
                                                                        ]
                                                                    "
                                                                    :class="{
                                                                        'has-danger':
                                                                            errors[
                                                                                `accrued.bonuses.${index}.salary_bonus`
                                                                            ]
                                                                    }"
                                                                >
                                                                    <small
                                                                        class="form-control-feedback"
                                                                        v-text="
                                                                            errors[
                                                                                `accrued.bonuses.${index}.salary_bonus`
                                                                            ][0]
                                                                        "
                                                                    ></small>
                                                                </div>
                                                                <div
                                                                    class="form-group mb-2 mr-2"
                                                                >
                                                                    <el-input-number
                                                                        v-model="
                                                                            row.salary_bonus
                                                                        "
                                                                        :min="
                                                                            0.01
                                                                        "
                                                                        controls-position="right"
                                                                        @change="
                                                                            changeSalaryBonus(
                                                                                index
                                                                            )
                                                                        "
                                                                    ></el-input-number>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-group"
                                                                    v-if="
                                                                        errors[
                                                                            `accrued.bonuses.${index}.non_salary_bonus`
                                                                        ]
                                                                    "
                                                                    :class="{
                                                                        'has-danger':
                                                                            errors[
                                                                                `accrued.bonuses.${index}.non_salary_bonus`
                                                                            ]
                                                                    }"
                                                                >
                                                                    <small
                                                                        class="form-control-feedback"
                                                                        v-text="
                                                                            errors[
                                                                                `accrued.bonuses.${index}.non_salary_bonus`
                                                                            ][0]
                                                                        "
                                                                    ></small>
                                                                </div>
                                                                <div
                                                                    class="form-group mb-2 mr-2"
                                                                >
                                                                    <el-input-number
                                                                        v-model="
                                                                            row.non_salary_bonus
                                                                        "
                                                                        :min="
                                                                            0.01
                                                                        "
                                                                        controls-position="right"
                                                                        @change="
                                                                            changeSalaryBonus(
                                                                                index
                                                                            )
                                                                        "
                                                                    ></el-input-number>
                                                                </div>
                                                            </td>

                                                            <td
                                                                class="series-table-actions text-center"
                                                            >
                                                                <button
                                                                    type="button"
                                                                    class="btn waves-effect waves-light btn-xs btn-danger"
                                                                    @click.prevent="
                                                                        clickCancelBonuses(
                                                                            index
                                                                        )
                                                                    "
                                                                >
                                                                    <i
                                                                        class="fa fa-trash"
                                                                    ></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <!-- Bonificaciones -->

                                            <!-- Ayudas -->
                                            <div class="col-md-6">
                                                <table>
                                                    <thead>
                                                        <tr width="100%">
                                                            <template
                                                                v-if="
                                                                    form.accrued
                                                                        .aid
                                                                        .length >
                                                                        0
                                                                "
                                                            >
                                                                <th
                                                                    class="pb-2"
                                                                >
                                                                    Ayuda
                                                                    salarial
                                                                </th>
                                                                <th
                                                                    class="pb-2"
                                                                >
                                                                    Ayuda no
                                                                    salarial
                                                                </th>
                                                            </template>
                                                            <th width="15%">
                                                                <a
                                                                    href="#"
                                                                    @click.prevent="
                                                                        clickAddAid
                                                                    "
                                                                    class="text-center font-weight-bold text-info pb-1 mt-1"
                                                                    >[+
                                                                    Agregar]</a
                                                                >
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="(row,
                                                            index) in form
                                                                .accrued.aid"
                                                            :key="index"
                                                        >
                                                            <td>
                                                                <div
                                                                    class="form-group"
                                                                    v-if="
                                                                        errors[
                                                                            `accrued.aid.${index}.salary_assistance`
                                                                        ]
                                                                    "
                                                                    :class="{
                                                                        'has-danger':
                                                                            errors[
                                                                                `accrued.aid.${index}.salary_assistance`
                                                                            ]
                                                                    }"
                                                                >
                                                                    <small
                                                                        class="form-control-feedback"
                                                                        v-text="
                                                                            errors[
                                                                                `accrued.aid.${index}.salary_assistance`
                                                                            ][0]
                                                                        "
                                                                    ></small>
                                                                </div>
                                                                <div
                                                                    class="form-group mb-2 mr-2"
                                                                >
                                                                    <el-input-number
                                                                        v-model="
                                                                            row.salary_assistance
                                                                        "
                                                                        :min="
                                                                            0.01
                                                                        "
                                                                        controls-position="right"
                                                                        @change="
                                                                            changeSalaryAid(
                                                                                index
                                                                            )
                                                                        "
                                                                    ></el-input-number>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div
                                                                    class="form-group"
                                                                    v-if="
                                                                        errors[
                                                                            `accrued.aid.${index}.non_salary_assistance`
                                                                        ]
                                                                    "
                                                                    :class="{
                                                                        'has-danger':
                                                                            errors[
                                                                                `accrued.aid.${index}.non_salary_assistance`
                                                                            ]
                                                                    }"
                                                                >
                                                                    <small
                                                                        class="form-control-feedback"
                                                                        v-text="
                                                                            errors[
                                                                                `accrued.aid.${index}.non_salary_assistance`
                                                                            ][0]
                                                                        "
                                                                    ></small>
                                                                </div>
                                                                <div
                                                                    class="form-group mb-2 mr-2"
                                                                >
                                                                    <el-input-number
                                                                        v-model="
                                                                            row.non_salary_assistance
                                                                        "
                                                                        :min="
                                                                            0.01
                                                                        "
                                                                        controls-position="right"
                                                                        @change="
                                                                            changeSalaryAid(
                                                                                index
                                                                            )
                                                                        "
                                                                    ></el-input-number>
                                                                </div>
                                                            </td>

                                                            <td
                                                                class="series-table-actions text-center"
                                                            >
                                                                <button
                                                                    type="button"
                                                                    class="btn waves-effect waves-light btn-xs btn-danger"
                                                                    @click.prevent="
                                                                        clickCancelAid(
                                                                            index
                                                                        )
                                                                    "
                                                                >
                                                                    <i
                                                                        class="fa fa-trash"
                                                                    ></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <!-- Ayudas -->
                                        </div>
                                    </el-tab-pane>

                                    <el-tab-pane
                                        label="Opcionales"
                                        name="accrued-optional"
                                    >
                                        <div class="row mt-2 mb-4">
                                            <div class="col-md-3">
                                                <div
                                                    class="form-group"
                                                    :class="{
                                                        'has-danger':
                                                            errors[
                                                                'accrued.telecommuting'
                                                            ]
                                                    }"
                                                >
                                                    <label class="control-label"
                                                        >Teletrabajo</label
                                                    >
                                                    <el-input-number
                                                        v-model="
                                                            form.accrued
                                                                .telecommuting
                                                        "
                                                        :min="0"
                                                        controls-position="right"
                                                        @change="
                                                            changeOptionalInputs
                                                        "
                                                    ></el-input-number>
                                                    <small
                                                        class="form-control-feedback"
                                                        v-if="
                                                            errors[
                                                                'accrued.telecommuting'
                                                            ]
                                                        "
                                                        v-text="
                                                            errors[
                                                                'accrued.telecommuting'
                                                            ][0]
                                                        "
                                                    ></small>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div
                                                    class="form-group"
                                                    :class="{
                                                        'has-danger':
                                                            errors[
                                                                'accrued.endowment'
                                                            ]
                                                    }"
                                                >
                                                    <label class="control-label"
                                                        >Dotación</label
                                                    >
                                                    <el-input-number
                                                        v-model="
                                                            form.accrued
                                                                .endowment
                                                        "
                                                        :min="0"
                                                        controls-position="right"
                                                        @change="
                                                            changeOptionalInputs
                                                        "
                                                    ></el-input-number>
                                                    <small
                                                        class="form-control-feedback"
                                                        v-if="
                                                            errors[
                                                                'accrued.endowment'
                                                            ]
                                                        "
                                                        v-text="
                                                            errors[
                                                                'accrued.endowment'
                                                            ][0]
                                                        "
                                                    ></small>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div
                                                    class="form-group"
                                                    :class="{
                                                        'has-danger':
                                                            errors[
                                                                'accrued.sustenance_support'
                                                            ]
                                                    }"
                                                >
                                                    <label class="control-label"
                                                        >Apoyo de
                                                        sustento</label
                                                    >
                                                    <el-input-number
                                                        v-model="
                                                            form.accrued
                                                                .sustenance_support
                                                        "
                                                        :min="0"
                                                        controls-position="right"
                                                        @change="
                                                            changeOptionalInputs
                                                        "
                                                    ></el-input-number>
                                                    <small
                                                        class="form-control-feedback"
                                                        v-if="
                                                            errors[
                                                                'accrued.sustenance_support'
                                                            ]
                                                        "
                                                        v-text="
                                                            errors[
                                                                'accrued.sustenance_support'
                                                            ][0]
                                                        "
                                                    ></small>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div
                                                    class="form-group"
                                                    :class="{
                                                        'has-danger':
                                                            errors[
                                                                'accrued.withdrawal_bonus'
                                                            ]
                                                    }"
                                                >
                                                    <label class="control-label"
                                                        >Bono de retiro</label
                                                    >
                                                    <el-input-number
                                                        v-model="
                                                            form.accrued
                                                                .withdrawal_bonus
                                                        "
                                                        :min="0"
                                                        controls-position="right"
                                                        @change="
                                                            changeOptionalInputs
                                                        "
                                                    ></el-input-number>
                                                    <small
                                                        class="form-control-feedback"
                                                        v-if="
                                                            errors[
                                                                'accrued.withdrawal_bonus'
                                                            ]
                                                        "
                                                        v-text="
                                                            errors[
                                                                'accrued.withdrawal_bonus'
                                                            ][0]
                                                        "
                                                    ></small>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div
                                                    class="form-group"
                                                    :class="{
                                                        'has-danger':
                                                            errors[
                                                                'accrued.compensation'
                                                            ]
                                                    }"
                                                >
                                                    <label class="control-label"
                                                        >Indemnización</label
                                                    >
                                                    <el-input-number
                                                        v-model="
                                                            form.accrued
                                                                .compensation
                                                        "
                                                        :min="0"
                                                        controls-position="right"
                                                        @change="
                                                            changeOptionalInputs
                                                        "
                                                    ></el-input-number>
                                                    <small
                                                        class="form-control-feedback"
                                                        v-if="
                                                            errors[
                                                                'accrued.compensation'
                                                            ]
                                                        "
                                                        v-text="
                                                            errors[
                                                                'accrued.compensation'
                                                            ][0]
                                                        "
                                                    ></small>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div
                                                    class="form-group"
                                                    :class="{
                                                        'has-danger':
                                                            errors[
                                                                'accrued.salary_viatics'
                                                            ]
                                                    }"
                                                >
                                                    <label class="control-label"
                                                        >Manutención y/o
                                                        alojamiento</label
                                                    >
                                                    <el-input-number
                                                        v-model="
                                                            form.accrued
                                                                .salary_viatics
                                                        "
                                                        :min="0"
                                                        controls-position="right"
                                                        @change="
                                                            changeOptionalInputs
                                                        "
                                                    ></el-input-number>
                                                    <small
                                                        class="form-control-feedback"
                                                        v-if="
                                                            errors[
                                                                'accrued.salary_viatics'
                                                            ]
                                                        "
                                                        v-text="
                                                            errors[
                                                                'accrued.salary_viatics'
                                                            ][0]
                                                        "
                                                    ></small>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div
                                                    class="form-group"
                                                    :class="{
                                                        'has-danger':
                                                            errors[
                                                                'accrued.non_salary_viatics'
                                                            ]
                                                    }"
                                                >
                                                    <label class="control-label"
                                                        >Manutención y/o
                                                        alojamiento no
                                                        salariales</label
                                                    >
                                                    <el-input-number
                                                        v-model="
                                                            form.accrued
                                                                .non_salary_viatics
                                                        "
                                                        :min="0"
                                                        controls-position="right"
                                                        @change="
                                                            changeOptionalInputs
                                                        "
                                                    ></el-input-number>
                                                    <small
                                                        class="form-control-feedback"
                                                        v-if="
                                                            errors[
                                                                'accrued.non_salary_viatics'
                                                            ]
                                                        "
                                                        v-text="
                                                            errors[
                                                                'accrued.non_salary_viatics'
                                                            ][0]
                                                        "
                                                    ></small>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div
                                                    class="form-group"
                                                    :class="{
                                                        'has-danger':
                                                            errors[
                                                                'accrued.refund'
                                                            ]
                                                    }"
                                                >
                                                    <label class="control-label"
                                                        >Reintegro</label
                                                    >
                                                    <el-input-number
                                                        v-model="
                                                            form.accrued.refund
                                                        "
                                                        :min="0"
                                                        controls-position="right"
                                                        @change="
                                                            changeOptionalInputs
                                                        "
                                                    ></el-input-number>
                                                    <small
                                                        class="form-control-feedback"
                                                        v-if="
                                                            errors[
                                                                'accrued.refund'
                                                            ]
                                                        "
                                                        v-text="
                                                            errors[
                                                                'accrued.refund'
                                                            ][0]
                                                        "
                                                    ></small>
                                                </div>
                                            </div>
                                        </div>
                                    </el-tab-pane>
                                </el-tabs>
                            </el-tab-pane>
                        </el-tabs>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-12 text-right">
                            <button
                                type="button"
                                class="btn btn-secondary mr-2"
                                @click="cancelForm"
                                :disabled="loading_submit"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                class="btn btn-warning mr-2"
                                @click="saveForm(false)"
                                :disabled="loading_submit"
                            >
                                <i
                                    v-if="loading_submit"
                                    class="fa fa-spinner fa-spin mr-1"
                                ></i>
                                {{
                                    editMode
                                        ? "Editar sin generar"
                                        : "Guardar sin generar"
                                }}
                            </button>
                            <button
                                type="button"
                                class="btn btn-primary"
                                @click="saveForm(true)"
                                :disabled="loading_submit"
                            >
                                <i
                                    v-if="loading_submit"
                                    class="fa fa-spinner fa-spin mr-1"
                                ></i>
                                {{
                                    editMode
                                        ? "Editar y generar"
                                        : "Guardar y generar"
                                }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Dialog para horas extras -->
        <document-payroll-extra-hours
            :showDialog.sync="showDialogDocumentPayrollExtraHours"
            :form="form"
            :errors="errors"
            @sumTotalsExtraHoursForm="sumTotalsExtraHoursForm"
            ref="componentDocumentPayrollExtraHours"
        >
        </document-payroll-extra-hours>
    </div>
</template>

<script>
import { documentPayrollMixin } from "../../mixins/document_payroll";
import { getValueIfNull } from "../../helpers/functions";
import DocumentPayrollExtraHours from "./partials/extra_hours.vue";

export default {
    props: [],

    mixins: [documentPayrollMixin],

    components: {
        DocumentPayrollExtraHours
    },

    data() {
        return {
            resource: "payroll/block-payrolls",
            loading: false,
            loading_form: false,
            loading_submit: false,
            errors: {},
            editMode: false, // Nuevo flag para modo edición
            blockPayrollId: null, // ID del bloque en edición
            form: {
                date_of_issue: "",
                time_of_issue: "",
                workers_quantity: 0,
                accrued_total: 0,
                deductions_total: 0,
                period_start: "",
                period_end: "",
                period_type: "mensual",
                establishment_id: null,
                establishment: null,
                items: [],
                tables: { resolutions: [] },
                period: {
                    admision_date: "",
                    worked_time: 0,
                    issue_date: ""
                },
                payment: {
                    payment_method_id: null,
                    bank_name: "",
                    account_type: "",
                    account_number: ""
                },
                payment_dates: [],
                accrued: {
                    total_base_salary: 0,
                    worked_days: 0,
                    salary: 0,
                    transportation_allowance: 0,
                    accrued_total: 0,
                    common_vacation: [],
                    paid_vacation: [],
                    service_bonus: [],
                    severance: [],
                    work_disabilities: [],
                    bonuses: [],
                    aid: [],
                    telecommuting: 0,
                    endowment: 0,
                    sustenance_support: 0,
                    withdrawal_bonus: 0,
                    compensation: 0,
                    salary_viatics: 0,
                    non_salary_viatics: 0,
                    refund: 0,
                    // Horas extras
                    heds: [], // Horas extras diurnas
                    hens: [], // Horas extras nocturnas
                    hrns: [], // Horas recargo nocturno
                    heddfs: [], // Horas extras diurnas dominical y festivos
                    hrddfs: [], // Horas recargo diurno dominical y festivos
                    hendfs: [], // Horas extras nocturnas dominical y festivos
                    hrndfs: [], // Horas recargo nocturno dominical y festivos
                    total_extra_hours: 0 // Total de horas extras
                }
            },
            activeName: "active-workers",
            activeNameAccrued: "accrued-vacations",
            isAdjustNote: false, // Variable para controlar si es una nota de ajuste
            form_disabled: {
                inputs_type_worker_sena: false
            },
            selectedWorkerId: null,
            globalGenerateProvisions: false,
            periodDateError: "",
            employeesArray: [], // Array que contendrá un JSON por cada empleado con sus datos de periodo
            employeePeriodData: {}, // Objeto que almacena los datos de periodo por ID de empleado
            employeePaymentData: {}, // Objeto que almacena los datos de pago por ID de empleado
            employeeAccruedData: {}, // Objeto que almacena los datos de devengados por ID de empleado
            employeeTransportationManuallyEdited: {}, // Track which employees have manually edited transportation allowance
            show_inputs_payment_method: false,
            type_disabilities: [], // Array para los tipos de incapacidades
            advancedConfiguration: null, // Configuración avanzada para salario mínimo y subsidio
            calculatingGlobalTotal: false, // Protección contra recursión en cálculo global

            // Constantes para cálculos de prestaciones sociales
            quantity_days_year: 360, // Días del año para cálculos laborales (360 días comerciales)

            // Dialog para horas extras
            showDialogDocumentPayrollExtraHours: false
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
            // Asegurar que la configuración avanzada esté disponible antes de cargar empleados
            await this.getActiveWorkers();

            // Aplicar subsidio de transporte después de que todo esté inicializado
            this.$nextTick(() => {
                if (this.selectedWorkerId && this.advancedConfiguration) {
                    this.applyTransportationAllowance();
                }
            });
        }
    },

    computed: {
        total_base_salary() {
            return parseFloat(this.form.accrued.total_base_salary) || 0;
        }
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
            window.location.href = "/payroll/block-payrolls";
        },

        saveForm(generate) {
            // Limpiar errores previos
            this.errors = {};
            let hasErrors = false;

            // Validar que la resolución sea obligatoria
            if (!this.form.type_document_id) {
                this.errors.type_document_id = ["La resolución es obligatoria"];
                hasErrors = true;
            }

            // Validar que la fecha de inicio de periodo sea obligatoria
            if (!this.form.period_start) {
                this.errors.period_start = [
                    "La fecha de inicio de periodo es obligatoria"
                ];
                hasErrors = true;
            }

            // Validar que la fecha de fin de periodo sea obligatoria
            if (!this.form.period_end) {
                this.errors.period_end = [
                    "La fecha de fin de periodo es obligatoria"
                ];
                hasErrors = true;
            }

            // Si hay errores, mostrar mensaje y detener
            if (hasErrors) {
                this.$message.error(
                    "Debe completar todos los campos obligatorios"
                );
                return;
            }

            // Aquí puedes agregar la lógica para guardar el formulario
            // generate = true para "Guardar y generar", false para "Guardar sin generar"
            // Ejemplo:
            if (generate) {
                // Guardar y generar
                this.submitForm("generate");
            } else {
                // Guardar sin generar
                this.submitForm("save");
            }
            //                window.location.href = '/payroll/block-payrolls';
        },

        submitForm(action) {
            // Validación adicional antes de enviar
            let hasErrors = false;

            if (!this.form.type_document_id) {
                this.errors.type_document_id = ["La resolución es obligatoria"];
                hasErrors = true;
            }

            if (!this.form.period_start) {
                this.errors.period_start = [
                    "La fecha de inicio de periodo es obligatoria"
                ];
                hasErrors = true;
            }

            if (!this.form.period_end) {
                this.errors.period_end = [
                    "La fecha de fin de periodo es obligatoria"
                ];
                hasErrors = true;
            }

            if (hasErrors) {
                this.$message.error(
                    "Debe completar todos los campos obligatorios"
                );
                return;
            }

            // COMENTADO: No guardar datos del empleado actual porque pueden sobrescribir cálculos correctos
            // this.saveCurrentEmployeeData();
            // this.saveCurrentEmployeePaymentData();
            // this.saveCurrentEmployeeAccruedData();

            // Calcular y actualizar el total global antes de cualquier envío
            this.calculateGlobalAccruedTotal();

            if (action === "save") {
                // Guardar sin generar
                this.saveWithoutGenerate();
            } else if (action === "generate") {
                // Guardar y generar (funcionalidad original)
                this.saveAndGenerate();
            }
        },

        async saveWithoutGenerate() {
            // COMENTADO: No guardar datos del empleado actual porque pueden sobrescribir cálculos correctos
            // if (this.selectedWorkerId) {
            //     this.saveCurrentEmployeeData();
            //     this.saveCurrentEmployeePaymentData();
            //     this.saveCurrentEmployeeAccruedData();
            // }

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
                this.$message.error(
                    "No hay trabajadores disponibles para procesar"
                );
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
                establishment_data: this.form.establishment
            };

            // Agregar datos de periodo de cada empleado usando estructura anidada
            const employeePeriodData = {};
            selectedWorkers.forEach(workerId => {
                const periodData = this.employeePeriodData[workerId] || {};
                const currentWorker = this.form.items.find(
                    item => item.id === workerId
                );

                employeePeriodData[workerId] = {
                    worker_id: workerId,
                    salary:
                        periodData.salary ||
                        (currentWorker ? currentWorker.salary : 0),
                    worked_days: periodData.worked_days || 30, // Valor del formulario "Días trabajados"
                    admision_date: periodData.admision_date || "",
                    worked_time: this.calculateWorkedDays(
                        periodData.admision_date || ""
                    ), // Cálculo automático entre fechas
                    payroll_period: periodData.payroll_period_id || 5, // Siempre usar "Mensual" como defecto
                    generate_provisions: currentWorker
                        ? currentWorker.generate_provisions
                        : false
                };
            });

            // Agregar datos de pago de cada empleado
            const employeePaymentData = {};
            selectedWorkers.forEach(workerId => {
                const paymentData = this.employeePaymentData[workerId] || {};

                // Si el empleado no tiene fechas de pago, generar fechas por defecto
                let paymentDates = paymentData.payment_dates || [];
                if (paymentDates.length === 0 && !this.editMode) {
                    paymentDates = this.generateDefaultPaymentDatesForEmployee(
                        workerId
                    );
                }

                employeePaymentData[workerId] = {
                    worker_id: workerId,
                    payment_method_id: paymentData.payment_method_id || null,
                    bank_name: paymentData.bank_name || "",
                    account_type: paymentData.account_type || "",
                    account_number: paymentData.account_number || "",
                    payment_dates: paymentDates
                };
            });

            // Agregar datos de devengados de cada empleado
            const employeeAccruedData = {};
            selectedWorkers.forEach(workerId => {
                // Asegurar que todos los empleados tengan datos de devengados inicializados
                if (!this.employeeAccruedData[workerId]) {
                    // Buscar el empleado
                    const currentWorker = this.form.items.find(
                        item => item.id === workerId
                    );
                    const workerSalary =
                        parseFloat(currentWorker ? currentWorker.salary : 0) ||
                        0;
                    const transportationAllowance = this.calculateTransportationAllowanceForWorker(
                        workerSalary
                    );

                    // Inicializar con datos básicos
                    this.$set(this.employeeAccruedData, workerId, {
                        total_base_salary: workerSalary,
                        worked_days: 30,
                        salary: workerSalary,
                        transportation_allowance: transportationAllowance,
                        accrued_total: workerSalary + transportationAllowance, // Sumar correctamente
                        common_vacation: [],
                        paid_vacation: [],
                        service_bonus: [],
                        severance: [],
                        work_disabilities: [],
                        bonuses: [],
                        aid: [],
                        telecommuting: 0,
                        endowment: 0,
                        sustenance_support: 0,
                        withdrawal_bonus: 0,
                        compensation: 0,
                        salary_viatics: 0,
                        non_salary_viatics: 0,
                        refund: 0,
                        // Horas extras
                        heds: [], // Horas extras diurnas
                        hens: [], // Horas extras nocturnas
                        hrns: [], // Horas recargo nocturno
                        heddfs: [], // Horas extras diurnas dominical y festivos
                        hrddfs: [], // Horas recargo diurno dominical y festivos
                        hendfs: [], // Horas extras nocturnas dominical y festivos
                        hrndfs: [], // Horas recargo nocturno dominical y festivos
                        total_extra_hours: 0 // Total de horas extras
                    });
                }

                const accruedData = this.employeeAccruedData[workerId] || {};

                // Función helper para convertir a número de forma segura
                const toNumber = value => {
                    if (value === null || value === undefined || value === "")
                        return 0;
                    const num = parseFloat(value);
                    return isNaN(num) ? 0 : num;
                };

                employeeAccruedData[workerId] = {
                    worker_id: workerId,
                    total_base_salary: toNumber(accruedData.total_base_salary),
                    worked_days: parseInt(accruedData.worked_days) || 0,
                    salary: toNumber(accruedData.salary),
                    transportation_allowance: toNumber(
                        accruedData.transportation_allowance
                    ),
                    accrued_total: toNumber(accruedData.accrued_total),
                    common_vacation: accruedData.common_vacation || [],
                    paid_vacation: accruedData.paid_vacation || [],
                    service_bonus: accruedData.service_bonus || [],
                    severance: accruedData.severance || [],
                    work_disabilities: accruedData.work_disabilities || [],
                    bonuses: accruedData.bonuses || [],
                    aid: accruedData.aid || [],
                    telecommuting: toNumber(accruedData.telecommuting),
                    endowment: toNumber(accruedData.endowment),
                    sustenance_support: toNumber(
                        accruedData.sustenance_support
                    ),
                    withdrawal_bonus: toNumber(accruedData.withdrawal_bonus),
                    compensation: toNumber(accruedData.compensation),
                    salary_viatics: toNumber(accruedData.salary_viatics),
                    non_salary_viatics: toNumber(
                        accruedData.non_salary_viatics
                    ),
                    refund: toNumber(accruedData.refund),
                    // Horas extras
                    heds: accruedData.heds || [],
                    hens: accruedData.hens || [],
                    hrns: accruedData.hrns || [],
                    heddfs: accruedData.heddfs || [],
                    hrddfs: accruedData.hrddfs || [],
                    hendfs: accruedData.hendfs || [],
                    hrndfs: accruedData.hrndfs || [],
                    total_extra_hours: toNumber(accruedData.total_extra_hours)
                };
            });

            // Agregar los objetos completos al formData
            formData.employee_period_data = employeePeriodData;
            formData.employee_payment_data = employeePaymentData;
            formData.employee_accrued_data = employeeAccruedData;

            // Calcular el total global de devengados (suma de todos los empleados)
            let globalAccruedTotal = 0;
            selectedWorkers.forEach(workerId => {
                const employeeAccrued = employeeAccruedData[workerId];
                if (employeeAccrued && employeeAccrued.accrued_total) {
                    const employeeTotal =
                        parseFloat(employeeAccrued.accrued_total) || 0;
                    globalAccruedTotal += employeeTotal;
                }
            });

            console.log("🎯 Total global calculado:", globalAccruedTotal);

            // Agregar el total global al formData
            formData.accrued_total = globalAccruedTotal; // Asegurar que no se incluya el campo notes
            if (formData.hasOwnProperty("notes")) {
                delete formData.notes;
            }

            // Determinar endpoint según modo
            const endpoint = this.editMode
                ? `/${this.resource}/update-block/${this.blockPayrollId}`
                : `/${this.resource}/store-without-generate`;

            const method = this.editMode ? "put" : "post";

            // Enviar al backend
            this.$http[method](endpoint, formData)
                .then(response => {
                    this.loading_submit = false;
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                        // Redirigir al listado después de guardar/editar
                        setTimeout(() => {
                            window.location.href = "/payroll/block-payrolls";
                        }, 1500);
                    } else {
                        this.$message.error(response.data.message);
                    }
                })
                .catch(error => {
                    this.loading_submit = false;
                    this.$message.error(
                        this.editMode
                            ? "Error al editar el bloque de nómina"
                            : "Error al guardar el bloque de nómina"
                    );
                });
        },
        saveAndGenerate() {
            this.loading_submit = true;

            this.saveCurrentEmployeeAccruedData();

            // Calcular y actualizar el total global antes de enviar
            this.calculateGlobalAccruedTotal();

            this.validate();

            if (this.hasErrors()) {
                this.loading_submit = false;
                this.$message.error(
                    "Por favor corrige los errores antes de continuar"
                );
                return;
            }

            const url = this.editMode
                ? `/${this.resource}/${this.form.id}/update-and-generate`
                : `/${this.resource}/store-and-generate`;

            const method = this.editMode ? "PUT" : "POST";

            this.$http[method.toLowerCase()](url, this.form)
                .then(response => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                        setTimeout(() => {
                            window.location.href = "/payroll/block-payrolls";
                        }, 1500);
                    } else {
                        this.$message.error(response.data.message);
                    }
                })
                .catch(error => {
                    this.loading_submit = false;
                    this.$message.error(
                        "Error al guardar y generar el bloque de nómina"
                    );
                });
        },

        validatePeriodDates() {
            this.periodDateError = "";

            // Limpiar errores de campos obligatorios cuando se completan
            if (this.form.period_start && this.errors.period_start) {
                this.$delete(this.errors, "period_start");
            }
            if (this.form.period_end && this.errors.period_end) {
                this.$delete(this.errors, "period_end");
            }

            if (this.form.period_start && this.form.period_end) {
                if (this.form.period_end < this.form.period_start) {
                    this.periodDateError =
                        "La fecha final no puede ser menor que la fecha inicial.";
                    this.form.period_end = "";
                } else if (this.form.period_start > this.form.period_end) {
                    this.periodDateError =
                        "La fecha inicial no puede ser mayor que la fecha final.";
                    this.form.period_start = "";
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
                const response = await this.$http.post(
                    `/${this.resource}/check-period-exists`,
                    {
                        period_start: this.form.period_start,
                        period_end: this.form.period_end
                    }
                );

                if (response.data.exists) {
                    this.periodDateError =
                        "Ya existe un bloque de nómina registrado para este período.";
                    return false;
                }

                this.periodDateError = "";
                return true;
            } catch (error) {
                // Si hay error en la validación, permitir continuar
                console.warn("Error validando unicidad del período:", error);
                return true;
            }
        },

        setCurrentDateTime() {
            const now = new Date();
            this.form.date_of_issue = now.toISOString().slice(0, 10);
            this.form.time_of_issue = now.toTimeString().slice(0, 8);

            // Solo establecer fechas de periodo si no estamos en modo edición
            if (!this.editMode) {
                // Obtener primer día del mes anterior
                const firstDayLastMonth = new Date(
                    now.getFullYear(),
                    now.getMonth() - 1,
                    1
                );

                // Obtener último día del mes anterior
                const lastDayLastMonth = new Date(
                    now.getFullYear(),
                    now.getMonth(),
                    0
                );

                // Formatear las fechas
                this.form.period_start = firstDayLastMonth
                    .toISOString()
                    .slice(0, 10);
                this.form.period_end = lastDayLastMonth
                    .toISOString()
                    .slice(0, 10);
            }
        },

        getActiveWorkers() {
            this.loading = true;
            this.$http
                .get(`/${this.resource}/active-workers`)
                .then(response => {
                    this.form.items = response.data.data;
                    this.form.workers_quantity = this.form.items.length;
                    this.selectedWorkerId =
                        this.form.items.length > 0
                            ? this.form.items[0].id
                            : null;

                    // Inicializar el array de empleados con sus datos
                    this.initializeEmployeesArray();

                    // Inicializar datos de devengados para TODOS los empleados
                    this.form.items.forEach(worker => {
                        this.initializeWorkerAccruedData(worker.id);
                    });

                    // Calcular total global después de inicializar todos los empleados
                    this.$nextTick(() => {
                        this.calculateGlobalAccruedTotal();
                    });

                    // Forzar la carga de datos del primer empleado seleccionado
                    if (this.selectedWorkerId) {
                        this.$nextTick(() => {
                            this.handleWorkerSelection(this.selectedWorkerId);
                        });
                    }

                    this.loading = false;
                })
                .catch(error => {
                    this.loading = false;
                    this.$message.error(
                        getValueIfNull(
                            error.response.data.message,
                            "Error al cargar los trabajadores activos"
                        )
                    );
                });
        },

        // Método para manejar cambios en el switch de "Generar Provisiones"
        onGenerateProvisionsChange(workerId, isEnabled) {
            console.log(
                `Cambio generar provisiones para worker ${workerId}:`,
                isEnabled
            );

            // Guardar el estado en employeePeriodData
            if (!this.employeePeriodData[workerId]) {
                this.employeePeriodData[workerId] = {};
            }
            this.$set(
                this.employeePeriodData[workerId],
                "generate_provisions",
                isEnabled
            );

            // Si el empleado seleccionado actualmente es el que cambió, actualizar la prima de servicios
            if (this.selectedWorkerId == workerId) {
                if (isEnabled) {
                    this.generateServiceBonusProvision();
                } else {
                    this.removeServiceBonusProvision();
                }
            } else {
                // Si no es el empleado actual, trabajar con los datos guardados
                if (isEnabled) {
                    this.generateServiceBonusProvisionForWorker(workerId);
                } else {
                    this.removeServiceBonusProvisionForWorker(workerId);
                }
            }
        },

        // Generar prima de servicios para el empleado actual
        generateServiceBonusProvision() {
            // Verificar si ya existe una prima de servicios generada automáticamente
            const existingProvisionIndex = this.form.accrued.service_bonus.findIndex(
                bonus => bonus.is_automatic_provision === true
            );

            // Si ya existe, no agregar otra
            if (existingProvisionIndex !== -1) {
                return;
            }

            // Calcular el valor de la prima de servicios
            const workedDays = parseInt(this.form.accrued.worked_days) || 30;
            const totalBaseSalary =
                parseFloat(this.form.accrued.total_base_salary) || 0;

            if (totalBaseSalary > 0) {
                const dailyBase = totalBaseSalary / this.quantity_days_year; // 360 días
                const provisionAmount = this.roundNumber(
                    dailyBase * workedDays
                );

                // Agregar la prima de servicios
                this.form.accrued.service_bonus.push({
                    quantity: this.roundNumber(workedDays / 12), // Días trabajados dividido entre 12
                    payment: provisionAmount,
                    paymentNS: 0,
                    is_automatic_provision: true // Marcar como generada automáticamente
                });

                // También generar cesantías automáticamente
                this.generateSeveranceProvision(workedDays, totalBaseSalary);

                // Recalcular totales
                this.calculateAccruedTotal();
                this.saveCurrentEmployeeAccruedData();
                this.calculateGlobalAccruedTotal();
            }
        },

        // Generar cesantías para el empleado actual
        generateSeveranceProvision(workedDays = null, totalBaseSalary = null) {
            // Verificar si ya existe una cesantía generada automáticamente
            const existingSeveranceIndex = this.form.accrued.severance.findIndex(
                severance => severance.is_automatic_provision === true
            );

            // Si ya existe, no agregar otra
            if (existingSeveranceIndex !== -1) {
                return;
            }

            // Usar los valores pasados como parámetros o calcularlos
            const days =
                workedDays || parseInt(this.form.accrued.worked_days) || 30;
            const baseSalary =
                totalBaseSalary ||
                parseFloat(this.form.accrued.total_base_salary) ||
                0;

            if (baseSalary > 0) {
                const dailyBase = baseSalary / this.quantity_days_year; // 360 días
                const severanceAmount = this.roundNumber(dailyBase * days);

                // Calcular intereses sobre cesantías (12% directo del pago de cesantías)
                const interestRate = 0.12; // 12%
                const interestAmount = this.roundNumber(
                    severanceAmount * interestRate
                );

                // Agregar la cesantía
                this.form.accrued.severance.push({
                    quantity: this.roundNumber(days / 12), // Días trabajados dividido entre 12
                    payment: severanceAmount,
                    percentage: 12, // Campo "Interés" debe ser 12
                    interest_payment: interestAmount,
                    is_automatic_provision: true // Marcar como generada automáticamente
                });
            }
        },

        // Remover prima de servicios del empleado actual
        removeServiceBonusProvision() {
            // Encontrar y remover la prima de servicios generada automáticamente
            const provisionIndex = this.form.accrued.service_bonus.findIndex(
                bonus => bonus.is_automatic_provision === true
            );

            if (provisionIndex !== -1) {
                this.form.accrued.service_bonus.splice(provisionIndex, 1);
            }

            // También remover cesantías automáticas
            this.removeSeveranceProvision();

            // Recalcular totales
            this.calculateAccruedTotal();
            this.saveCurrentEmployeeAccruedData();
            this.calculateGlobalAccruedTotal();
        },

        // Remover cesantías del empleado actual
        removeSeveranceProvision() {
            // Encontrar y remover la cesantía generada automáticamente
            const severanceIndex = this.form.accrued.severance.findIndex(
                severance => severance.is_automatic_provision === true
            );

            if (severanceIndex !== -1) {
                this.form.accrued.severance.splice(severanceIndex, 1);
            }
        },

        // Generar prima de servicios para un empleado específico (no actual)
        generateServiceBonusProvisionForWorker(workerId) {
            // Inicializar datos del empleado si no existen
            if (!this.employeeAccruedData[workerId]) {
                this.initializeWorkerAccruedData(workerId);
            }

            const accruedData = this.employeeAccruedData[workerId];
            if (!accruedData) return;

            // Verificar si ya existe una prima de servicios generada automáticamente
            const existingProvisionIndex = accruedData.service_bonus?.findIndex(
                bonus => bonus.is_automatic_provision === true
            );

            // Si ya existe, no agregar otra
            if (existingProvisionIndex !== -1) {
                return;
            }

            // Obtener datos del trabajador
            const currentWorker = this.form.items.find(
                item => item.id == workerId
            );
            const periodData = this.employeePeriodData[workerId];

            if (currentWorker) {
                const workedDays = parseInt(periodData?.worked_days || 30);
                const totalBaseSalary = parseFloat(currentWorker.salary) || 0;

                if (totalBaseSalary > 0) {
                    const dailyBase = totalBaseSalary / this.quantity_days_year; // 360 días
                    const provisionAmount = this.roundNumber(
                        dailyBase * workedDays
                    );

                    // Inicializar service_bonus si no existe
                    if (!accruedData.service_bonus) {
                        this.$set(accruedData, "service_bonus", []);
                    }

                    // Agregar la prima de servicios
                    accruedData.service_bonus.push({
                        quantity: this.roundNumber(workedDays / 12), // Días trabajados dividido entre 12
                        payment: provisionAmount,
                        paymentNS: 0,
                        is_automatic_provision: true // Marcar como generada automáticamente
                    });

                    // También generar cesantías automáticamente
                    this.generateSeveranceProvisionForWorker(
                        workerId,
                        workedDays,
                        totalBaseSalary
                    );

                    // Actualizar total_base_salary en los datos acumulados
                    accruedData.total_base_salary = totalBaseSalary;
                    accruedData.worked_days = workedDays;

                    // Recalcular el total devengado para este empleado
                    this.calculateAccruedTotalForWorker(workerId);
                    this.calculateGlobalAccruedTotal();
                }
            }
        },

        // Generar cesantías para un empleado específico (no actual)
        generateSeveranceProvisionForWorker(
            workerId,
            workedDays = null,
            totalBaseSalary = null
        ) {
            const accruedData = this.employeeAccruedData[workerId];
            if (!accruedData) return;

            // Verificar si ya existe una cesantía generada automáticamente
            const existingSeveranceIndex = accruedData.severance?.findIndex(
                severance => severance.is_automatic_provision === true
            );

            // Si ya existe, no agregar otra
            if (existingSeveranceIndex !== -1) {
                return;
            }

            // Obtener datos del trabajador si no se pasaron como parámetros
            const currentWorker = this.form.items.find(
                item => item.id == workerId
            );
            const periodData = this.employeePeriodData[workerId];

            if (currentWorker) {
                const days =
                    workedDays || parseInt(periodData?.worked_days || 30);
                const baseSalary =
                    totalBaseSalary || parseFloat(currentWorker.salary) || 0;

                if (baseSalary > 0) {
                    const dailyBase = baseSalary / this.quantity_days_year; // 360 días
                    const severanceAmount = this.roundNumber(dailyBase * days);

                    // Calcular intereses sobre cesantías (12% directo del pago de cesantías)
                    const interestRate = 0.12; // 12%
                    const interestAmount = this.roundNumber(
                        severanceAmount * interestRate
                    );

                    // Inicializar severance si no existe
                    if (!accruedData.severance) {
                        this.$set(accruedData, "severance", []);
                    }

                    // Agregar la cesantía
                    accruedData.severance.push({
                        quantity: this.roundNumber(days / 12), // Días trabajados dividido entre 12
                        payment: severanceAmount,
                        percentage: 12, // Campo "Interés" debe ser 12
                        interest_payment: interestAmount,
                        is_automatic_provision: true // Marcar como generada automáticamente
                    });

                    // Recalcular el total devengado para este empleado
                    this.calculateAccruedTotalForWorker(workerId);
                }
            }
        },

        // Remover prima de servicios para un empleado específico (no actual)
        removeServiceBonusProvisionForWorker(workerId) {
            const accruedData = this.employeeAccruedData[workerId];
            if (!accruedData) return;

            // Encontrar y remover la prima de servicios generada automáticamente
            if (accruedData.service_bonus) {
                const provisionIndex = accruedData.service_bonus.findIndex(
                    bonus => bonus.is_automatic_provision === true
                );

                if (provisionIndex !== -1) {
                    accruedData.service_bonus.splice(provisionIndex, 1);
                }
            }

            // También remover cesantías automáticas
            this.removeSeveranceProvisionForWorker(workerId);

            // Recalcular el total devengado para este empleado
            this.calculateAccruedTotalForWorker(workerId);
            this.calculateGlobalAccruedTotal();
        },

        // Remover cesantías para un empleado específico (no actual)
        removeSeveranceProvisionForWorker(workerId) {
            const accruedData = this.employeeAccruedData[workerId];
            if (!accruedData || !accruedData.severance) return;

            // Encontrar y remover la cesantía generada automáticamente
            const severanceIndex = accruedData.severance.findIndex(
                severance => severance.is_automatic_provision === true
            );

            if (severanceIndex !== -1) {
                accruedData.severance.splice(severanceIndex, 1);
            }
        },

        // Calcular total devengado para un empleado específico
        calculateAccruedTotalForWorker(workerId) {
            const accruedData = this.employeeAccruedData[workerId];
            if (!accruedData) return;

            let total = 0;

            // Función helper para convertir a número de forma segura
            const toNumber = value => {
                if (value === null || value === undefined || value === "")
                    return 0;
                const num = parseFloat(value);
                return isNaN(num) ? 0 : num;
            };

            total += toNumber(accruedData.salary);
            total += toNumber(accruedData.transportation_allowance);
            total += toNumber(accruedData.telecommuting);
            total += toNumber(accruedData.endowment);
            total += toNumber(accruedData.sustenance_support);
            total += toNumber(accruedData.withdrawal_bonus);
            total += toNumber(accruedData.compensation);
            total += toNumber(accruedData.salary_viatics);
            total += toNumber(accruedData.non_salary_viatics);
            total += toNumber(accruedData.refund);
            total += toNumber(accruedData.total_extra_hours);

            // Sumar vacaciones
            if (accruedData.common_vacation) {
                accruedData.common_vacation.forEach(vacation => {
                    total += toNumber(vacation.payment);
                });
            }
            if (accruedData.paid_vacation) {
                accruedData.paid_vacation.forEach(vacation => {
                    total += toNumber(vacation.payment);
                });
            }

            // Sumar bonificaciones y ayudas
            if (accruedData.bonuses) {
                accruedData.bonuses.forEach(bonus => {
                    total += toNumber(bonus.salary_bonus);
                    total += toNumber(bonus.non_salary_bonus);
                });
            }
            if (accruedData.aid) {
                accruedData.aid.forEach(aid => {
                    total += toNumber(aid.salary_assistance);
                    total += toNumber(aid.non_salary_assistance);
                });
            }

            // Sumar prestaciones sociales
            if (accruedData.service_bonus) {
                accruedData.service_bonus.forEach(serviceBonus => {
                    total += toNumber(serviceBonus.payment);
                    total += toNumber(serviceBonus.paymentNS);
                });
            }
            if (accruedData.severance) {
                accruedData.severance.forEach(severance => {
                    total += toNumber(severance.payment);
                    total += toNumber(severance.interest_payment);
                });
            }

            accruedData.accrued_total = total;
        },

        getTables() {
            this.loading = true;
            this.$http
                .get(`/${this.resource}/tables`)
                .then(response => {
                    this.form.tables = response.data || { resolutions: [] }; // Asegurar estructura

                    // Cargar type_disabilities si está disponible
                    if (response.data.type_disabilities) {
                        this.type_disabilities =
                            response.data.type_disabilities;
                    }

                    // Cargar configuración avanzada para salario mínimo y subsidio
                    if (response.data.advanced_configuration) {
                        this.advancedConfiguration =
                            response.data.advanced_configuration;

                        // Si ya hay empleados cargados, reinicializar con subsidio de transporte
                        if (this.form.items.length > 0) {
                            this.form.items.forEach(worker => {
                                this.reinitializeWorkerWithTransportation(
                                    worker.id
                                );
                            });

                            this.$nextTick(() => {
                                this.calculateGlobalAccruedTotal();
                            });
                        }

                        // Si ya hay empleados cargados, recalcular subsidios de transporte
                        if (this.form.items.length > 0) {
                            this.$nextTick(() => {
                                this.recalculateAllTransportationAllowances();
                                if (this.selectedWorkerId) {
                                    this.applyTransportationAllowance();
                                }
                            });
                        }
                    }

                    // Asignar establishment_id desde las tables si viene
                    if (response.data.establishment_id) {
                        this.form.establishment_id =
                            response.data.establishment_id;
                    }
                    // Asignar datos del establecimiento si vienen
                    if (response.data.establishment) {
                        this.form.establishment = response.data.establishment;
                    }

                    // Establecer "Mensual" (ID: 5) como valor por defecto para el período de nómina
                    this.form.payroll_period_id = 5;

                    // Generar fechas de pago por defecto para el periodo mensual
                    if (!this.editMode) {
                        this.generateDefaultPaymentDates(5);
                    }

                    // Seleccionar automáticamente la primera resolución disponible si no estamos en modo edición
                    if (
                        !this.editMode &&
                        this.form.tables.resolutions &&
                        this.form.tables.resolutions.length > 0
                    ) {
                        this.form.type_document_id = this.form.tables.resolutions[0].id;
                        // Ejecutar changeResolution para establecer prefix y resolution_number
                        this.changeResolution();
                    }

                    this.loading = false;
                })
                .catch(error => {
                    this.loading = false;
                    this.$message.error(
                        getValueIfNull(
                            error.response.data.message,
                            "Error al cargar las tablas"
                        )
                    );
                });
        },

        changeResolution() {
            // Limpiar errores cuando se selecciona una resolución
            if (this.form.type_document_id && this.errors.type_document_id) {
                this.$delete(this.errors, "type_document_id");
            }

            if (this.form.tables && this.form.tables.resolutions) {
                // Verificación añadida
                let resolution = _.find(this.form.tables.resolutions, {
                    id: this.form.type_document_id
                });
                if (resolution) {
                    this.form.prefix = resolution.prefix;
                    this.form.resolution_number = resolution.resolution_number;
                }
            }
        },

        removeItem(index) {
            this.form.items.splice(index, 1);
            this.form.workers_quantity = this.form.items.length;
        },

        handleGlobalSwitch(value) {
            console.log("Global switch changed to:", value);

            if (value) {
                // ON: todos los switches en true y generar prima de servicios para todos
                this.form.items.forEach(item => {
                    item.generate_provisions = true;

                    // Si es el empleado actualmente seleccionado, generar su prima directamente
                    if (
                        this.selectedWorkerId &&
                        this.selectedWorkerId == item.id
                    ) {
                        this.generateServiceBonusProvision();
                    } else {
                        // Para otros empleados, generar en sus datos guardados
                        this.generateServiceBonusProvisionForWorker(item.id);
                    }
                });
            } else {
                // OFF: todos los switches en false y eliminar prima de servicios para todos
                this.form.items.forEach(item => {
                    item.generate_provisions = false;

                    // Si es el empleado actualmente seleccionado, eliminar su prima directamente
                    if (
                        this.selectedWorkerId &&
                        this.selectedWorkerId == item.id
                    ) {
                        this.removeServiceBonusProvision();
                    } else {
                        // Para otros empleados, eliminar de sus datos guardados
                        this.removeServiceBonusProvisionForWorker(item.id);
                    }
                });
            }

            // Actualizar los datos del período para todos los empleados
            this.form.items.forEach(item => {
                if (!this.employeePeriodData[item.id]) {
                    this.employeePeriodData[item.id] = {};
                }
                this.$set(
                    this.employeePeriodData[item.id],
                    "generate_provisions",
                    value
                );
            });

            // Recalcular el total global después de todos los cambios
            this.$nextTick(() => {
                this.calculateGlobalAccruedTotal();
            });
        },

        getFormatDecimal(value) {
            // Convierte la cadena a un número (si es posible)
            const numericPrice = parseFloat(value);
            if (isNaN(numericPrice)) {
                // En caso de que la conversión no sea exitosa, devolver el valor original
                return value;
            }
            // Asumiendo que numericPrice es un número
            const formattedPrice = numericPrice.toLocaleString("en-US", {
                style: "decimal", // Estilo 'decimal' para separadores de mil y dos decimales
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            return formattedPrice;
        },

        // Inicializar datos de devengados para un empleado específico
        initializeWorkerAccruedData(workerId) {
            const currentWorker = this.form.items.find(
                item => item.id === workerId
            );
            if (!currentWorker) {
                return;
            }

            // Si ya tiene datos, no los sobrescribas - se reinicializará más tarde con subsidio
            if (this.employeeAccruedData[workerId]) {
                return;
            }

            // Calcular días trabajados
            const workedDays =
                this.calculateWorkedDays(currentWorker.date_of_admission) || 30;

            // Obtener salario básico
            const basicSalary = parseFloat(currentWorker.salary) || 0;

            // Calcular salario proporcional
            const proportionalSalary = (basicSalary * workedDays) / 30;

            // Calcular subsidio de transporte (será 0 si no hay configuración aún)
            let transportationAllowance = 0;
            if (this.advancedConfiguration) {
                transportationAllowance = this.calculateTransportationAllowanceForWorker(
                    basicSalary
                );
            }

            // Calcular total inicial (sin subsidio por ahora si no hay configuración)
            const initialTotal = proportionalSalary + transportationAllowance;

            // Crear datos iniciales
            this.employeeAccruedData[workerId] = {
                total_base_salary: basicSalary,
                worked_days: workedDays,
                salary: proportionalSalary,
                transportation_allowance: transportationAllowance,
                accrued_total: initialTotal,
                common_vacation: [],
                paid_vacation: [],
                service_bonus: [],
                severance: [],
                work_disabilities: [],
                bonuses: [],
                aid: [],
                telecommuting: 0,
                endowment: 0,
                sustenance_support: 0,
                withdrawal_bonus: 0,
                compensation: 0,
                salary_viatics: 0,
                non_salary_viatics: 0,
                refund: 0
            };
        },

        // Reinicializar empleado con subsidio de transporte (cuando ya tiene datos)
        reinitializeWorkerWithTransportation(workerId) {
            const currentWorker = this.form.items.find(
                item => item.id === workerId
            );
            if (!currentWorker) {
                return;
            }

            // Obtener datos existentes o crear nuevos
            let existingData = this.employeeAccruedData[workerId] || {};

            // Calcular días trabajados
            const workedDays =
                existingData.worked_days ||
                this.calculateWorkedDays(currentWorker.date_of_admission) ||
                30;

            // Obtener salario básico
            const basicSalary = parseFloat(currentWorker.salary) || 0;

            // Calcular salario proporcional
            const proportionalSalary = (basicSalary * workedDays) / 30;

            // Calcular subsidio de transporte con configuración disponible (asegurar que sea número)
            const transportationAllowance =
                parseFloat(
                    this.calculateTransportationAllowanceForWorker(basicSalary)
                ) || 0;

            // Suma matemática (no concatenación)
            const initialTotal = proportionalSalary + transportationAllowance;

            // Actualizar solo los campos básicos, manteniendo otros datos existentes
            this.employeeAccruedData[workerId] = {
                ...existingData, // Mantener datos existentes
                total_base_salary: basicSalary,
                worked_days: workedDays,
                salary: proportionalSalary,
                transportation_allowance: transportationAllowance, // Guardar como número
                accrued_total: initialTotal // Guardar como número
            };
        },

        handleWorkerSelection(workerId) {
            // Guardar datos del empleado actual si existe
            if (this.selectedWorkerId && this.selectedWorkerId !== workerId) {
                this.saveCurrentEmployeeData();
                this.saveCurrentEmployeePaymentData();
                this.saveCurrentEmployeeAccruedData();
            }

            // Cambiar empleado seleccionado
            this.selectedWorkerId = workerId;

            // Cargar datos del nuevo empleado
            this.loadEmployeeData(workerId);
            this.loadEmployeePaymentData(workerId);
            this.loadEmployeeAccruedData(workerId);

            // Aplicar subsidio de transporte y sincronizar datos después de cargar
            this.$nextTick(() => {
                // Solo aplicar subsidio automático si no fue editado manualmente
                if (!this.employeeTransportationManuallyEdited[workerId]) {
                    this.applyTransportationAllowance();
                }
                this.syncAccruedDataWithOtherTabs(workerId);

                // Recalcular total global después de cambiar de empleado
                this.calculateGlobalAccruedTotal();

                this.$forceUpdate();
            });
        },

        initializeEmployeesArray() {
            const currentYear = new Date().getFullYear();
            const defaultDate = `${currentYear}-01-01`;

            // Inicializar datos por defecto para cada empleado
            this.form.items.forEach(worker => {
                // Inicializar generate_provisions si no existe (solo establecer valor por defecto)
                if (
                    worker.generate_provisions === undefined ||
                    worker.generate_provisions === null
                ) {
                    this.$set(worker, "generate_provisions", false);
                }

                if (!this.employeePeriodData[worker.id]) {
                    const admisionDate = worker.work_start_date || defaultDate;

                    this.$set(this.employeePeriodData, worker.id, {
                        admision_date: admisionDate,
                        worked_time: this.calculateWorkedDays(admisionDate), // Cálculo automático entre fechas
                        issue_date: "",
                        payroll_period_id: worker.payroll_period_id || 5, // Usar período del empleado o "Mensual" como defecto
                        // Agregar campos que espera el backend
                        salary: worker.salary || 0,
                        period_start: this.form.period_start || "",
                        period_end: this.form.period_end || "",
                        worked_days: 30 // Valor fijo por defecto para el formulario
                    });
                }

                // Inicializar datos de pago para cada empleado
                if (!this.employeePaymentData[worker.id]) {
                    const workerPayment = worker.payment;

                    this.$set(this.employeePaymentData, worker.id, {
                        payment_method_id:
                            workerPayment?.payment_method_id || null,
                        bank_name: workerPayment?.bank_name || "",
                        account_type: workerPayment?.account_type || "",
                        account_number: workerPayment?.account_number || ""
                        // No inicializar payment_dates aquí - dejar que se genere en loadEmployeePaymentData
                    });
                }

                // Inicializar datos de devengados para cada empleado
                if (!this.employeeAccruedData[worker.id]) {
                    const workerSalary = parseFloat(worker.salary) || 0;
                    const transportationAllowance = this.calculateTransportationAllowanceForWorker(
                        workerSalary
                    );

                    this.$set(this.employeeAccruedData, worker.id, {
                        total_base_salary: workerSalary,
                        worked_days: 30,
                        salary: workerSalary,
                        transportation_allowance: transportationAllowance,
                        accrued_total: workerSalary + transportationAllowance, // Calcular el total inicial
                        common_vacation: [],
                        paid_vacation: [],
                        service_bonus: [],
                        severance: [],
                        work_disabilities: [],
                        bonuses: [],
                        aid: [],
                        telecommuting: 0,
                        endowment: 0,
                        sustenance_support: 0,
                        withdrawal_bonus: 0,
                        compensation: 0,
                        salary_viatics: 0,
                        non_salary_viatics: 0,
                        refund: 0
                    });
                }
            });

            // Recalcular subsidios de transporte para todos los empleados (por si la configuración no estaba disponible inicialmente)
            this.recalculateAllTransportationAllowances();

            // Cargar datos del primer empleado con delay para asegurar renderizado
            if (this.selectedWorkerId) {
                this.$nextTick(() => {
                    this.loadEmployeeData(this.selectedWorkerId);
                    this.loadEmployeePaymentData(this.selectedWorkerId);

                    // Cargar datos de devengados del empleado actual
                    const currentWorkerData = this.employeeAccruedData[
                        this.selectedWorkerId
                    ];
                    if (currentWorkerData) {
                        // Cargar los datos inicializados en el formulario
                        Object.keys(this.form.accrued).forEach(key => {
                            if (currentWorkerData.hasOwnProperty(key)) {
                                this.form.accrued[key] = currentWorkerData[key];
                            }
                        });

                        // Asegurar que el subsidio de transporte se aplique correctamente
                        this.applyTransportationAllowance();
                    }
                });
            }

            // Calcular el total global de devengados después de inicializar todos los empleados
            this.$nextTick(() => {
                this.calculateGlobalAccruedTotal();
            });
        },

        // Método para recalcular subsidios de transporte para todos los empleados
        recalculateAllTransportationAllowances() {
            if (!this.advancedConfiguration) return;

            this.form.items.forEach(worker => {
                if (this.employeeAccruedData[worker.id]) {
                    const transportationAllowance = this.calculateTransportationAllowanceForWorker(
                        worker.salary || 0
                    );
                    this.employeeAccruedData[
                        worker.id
                    ].transportation_allowance = transportationAllowance;
                }
            });
        },

        saveCurrentEmployeeData() {
            if (!this.selectedWorkerId) return;

            // Obtener datos actuales del empleado seleccionado
            const currentWorker = this.form.items.find(
                item => item.id === this.selectedWorkerId
            );

            // Guardar datos actuales del formulario
            this.employeePeriodData[this.selectedWorkerId] = {
                admision_date: this.form.period.admision_date || "",
                worked_time: this.calculateWorkedDays(
                    this.form.period.admision_date || ""
                ), // Cálculo automático
                issue_date: this.form.period.issue_date || "",
                payroll_period_id: this.form.payroll_period_id || 5,
                // Agregar campos que espera el backend
                salary: currentWorker ? currentWorker.salary : 0,
                period_start: this.form.period_start || "",
                period_end: this.form.period_end || "",
                worked_days: this.form.period.worked_time || 30 // Valor del control "Días trabajados"
            };
        },

        loadEmployeeData(workerId) {
            // Obtener datos del empleado o usar valores por defecto
            const data = this.employeePeriodData[workerId];
            const currentWorker = this.form.items.find(
                item => item.id === workerId
            );

            if (data) {
                // Cargar datos existentes haciendo copia para evitar referencias
                this.form.period.admision_date = data.admision_date || "";
                this.form.period.worked_time = data.worked_days || 30; // Mostrar worked_days en el formulario
                this.form.period.issue_date = data.issue_date || "";
                this.form.payroll_period_id =
                    data.payroll_period_id ||
                    (currentWorker ? currentWorker.payroll_period_id : 5);
            } else {
                // Usar datos del trabajador como valores por defecto
                const currentYear = new Date().getFullYear();
                const defaultDate = `${currentYear}-01-01`;
                const admisionDate = currentWorker
                    ? currentWorker.work_start_date
                    : defaultDate;

                this.form.period.admision_date = admisionDate;
                this.form.period.worked_time = 30; // Valor fijo por defecto para el formulario
                this.form.period.issue_date = "";
                this.form.payroll_period_id = currentWorker
                    ? currentWorker.payroll_period_id || 5
                    : 5;

                // Guardar el periodo por defecto en el storage del empleado
                if (!this.employeePeriodData[workerId]) {
                    this.employeePeriodData[workerId] = {};
                }
                this.$set(
                    this.employeePeriodData[workerId],
                    "payroll_period_id",
                    this.form.payroll_period_id
                );
            }

            // Cargar datos de pago y devengados
            this.loadEmployeePaymentData(workerId);
            this.loadEmployeeAccruedData(workerId);
        },

        handlePayrollPeriodChange(newValue) {
            // Actualizar inmediatamente el formulario para mostrar el cambio
            this.form.payroll_period_id = newValue;

            // Generar fechas de pago por defecto según el periodo seleccionado para el empleado actual
            if (this.selectedWorkerId) {
                const generatedDates = this.generateDefaultPaymentDatesForEmployee(
                    this.selectedWorkerId
                );
                this.form.payment_dates = JSON.parse(
                    JSON.stringify(generatedDates)
                );
            } else {
                // Fallback al método original si no hay empleado seleccionado
                this.generateDefaultPaymentDates(newValue);
            }

            // Actualizar inmediatamente en el storage del empleado actual
            if (this.selectedWorkerId) {
                // Asegurar que el objeto existe
                if (!this.employeePeriodData[this.selectedWorkerId]) {
                    this.employeePeriodData[this.selectedWorkerId] = {};
                }

                // Actualizar el valor usando Vue.set para reactividad
                this.$set(
                    this.employeePeriodData[this.selectedWorkerId],
                    "payroll_period_id",
                    newValue
                );

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

                this.$set(
                    this.employeePeriodData[this.selectedWorkerId],
                    "admision_date",
                    newValue
                );
                this.$set(
                    this.employeePeriodData[this.selectedWorkerId],
                    "worked_time",
                    calculatedWorkedTime
                ); // Cálculo automático

                // Solo establecer worked_days si no hay valor previo
                if (
                    !this.employeePeriodData[this.selectedWorkerId].worked_days
                ) {
                    this.$set(
                        this.employeePeriodData[this.selectedWorkerId],
                        "worked_days",
                        30
                    );
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
                this.$set(
                    this.employeePeriodData[this.selectedWorkerId],
                    "worked_days",
                    newValue
                );

                // Sincronizar con el tab de devengados
                if (this.form.accrued) {
                    this.form.accrued.worked_days = newValue;
                    this.calculateAccruedTotal();
                    this.saveCurrentEmployeeAccruedData();
                    this.calculateGlobalAccruedTotal();
                }
            }
        },

        // Generar fechas de pago por defecto según el periodo seleccionado
        generateDefaultPaymentDates(payrollPeriodId) {
            // Solo generar fechas por defecto si no estamos en modo edición
            if (this.editMode) {
                return;
            }

            // Limpiar fechas de pago existentes
            this.form.payment_dates = [];

            // Obtener el nombre del periodo de nómina para determinar el tipo
            const payrollPeriod = this.form.tables.payroll_periods
                ? this.form.tables.payroll_periods.find(
                      period => period.id === payrollPeriodId
                  )
                : null;

            if (!payrollPeriod) {
                return; // No se encontró el periodo
            }

            // Obtener fecha actual para calcular el mes anterior
            const now = new Date();
            const lastMonth = new Date(
                now.getFullYear(),
                now.getMonth() - 1,
                1
            );
            const lastDayOfLastMonth = new Date(
                now.getFullYear(),
                now.getMonth(),
                0
            );

            // Generar fechas según el tipo de periodo
            if (payrollPeriod.name.toLowerCase().includes("mensual")) {
                // Para periodo mensual: un pago el último día del mes anterior
                this.form.payment_dates.push({
                    payment_date: lastDayOfLastMonth.toISOString().slice(0, 10)
                });
            } else if (payrollPeriod.name.toLowerCase().includes("quincenal")) {
                // Para periodo quincenal: dos pagos (día 15 y último día del mes anterior)
                const fifteenthOfLastMonth = new Date(
                    lastMonth.getFullYear(),
                    lastMonth.getMonth(),
                    15
                );

                this.form.payment_dates.push({
                    payment_date: fifteenthOfLastMonth
                        .toISOString()
                        .slice(0, 10)
                });
                this.form.payment_dates.push({
                    payment_date: lastDayOfLastMonth.toISOString().slice(0, 10)
                });
            }
            // Para otros tipos de periodo (semanal, diario, etc.), no agregar fechas por defecto

            // Guardar las fechas en el almacenamiento del empleado actual si hay un empleado seleccionado
            if (this.selectedWorkerId) {
                this.saveCurrentEmployeePaymentData();
            }
        },

        // Generar fechas de pago por defecto para un empleado específico
        generateDefaultPaymentDatesForEmployee(workerId) {
            // Solo generar fechas por defecto si no estamos en modo edición
            if (this.editMode) {
                return [];
            }

            // Obtener el periodo de nómina del empleado específico
            const employeePeriodData = this.employeePeriodData[workerId];
            const payrollPeriodId = employeePeriodData
                ? employeePeriodData.payroll_period_id
                : this.form.payroll_period_id;

            if (!payrollPeriodId) {
                return []; // No hay periodo definido
            }

            // Obtener el nombre del periodo de nómina para determinar el tipo
            const payrollPeriod = this.form.tables.payroll_periods
                ? this.form.tables.payroll_periods.find(
                      period => period.id === payrollPeriodId
                  )
                : null;

            if (!payrollPeriod) {
                return []; // No se encontró el periodo
            }

            const generatedDates = [];

            // Obtener fecha actual para calcular el mes anterior
            const now = new Date();
            const lastMonth = new Date(
                now.getFullYear(),
                now.getMonth() - 1,
                1
            );
            const lastDayOfLastMonth = new Date(
                now.getFullYear(),
                now.getMonth(),
                0
            );

            // Generar fechas según el tipo de periodo
            if (payrollPeriod.name.toLowerCase().includes("mensual")) {
                // Para periodo mensual: un pago el último día del mes anterior
                generatedDates.push({
                    payment_date: lastDayOfLastMonth.toISOString().slice(0, 10)
                });
            } else if (payrollPeriod.name.toLowerCase().includes("quincenal")) {
                // Para periodo quincenal: dos pagos (día 15 y último día del mes anterior)
                const fifteenthOfLastMonth = new Date(
                    lastMonth.getFullYear(),
                    lastMonth.getMonth(),
                    15
                );

                generatedDates.push({
                    payment_date: fifteenthOfLastMonth
                        .toISOString()
                        .slice(0, 10)
                });
                generatedDates.push({
                    payment_date: lastDayOfLastMonth.toISOString().slice(0, 10)
                });
            }
            // Para otros tipos de periodo (semanal, diario, etc.), no agregar fechas por defecto

            console.log(
                `Fechas de pago generadas para empleado ${workerId}:`,
                generatedDates
            );

            // También actualizar this.form.payment_dates si es el empleado actual para mantener compatibilidad
            if (workerId === this.selectedWorkerId) {
                this.form.payment_dates = JSON.parse(
                    JSON.stringify(generatedDates)
                );
            }

            return generatedDates;
        },

        // Método para manejar el cambio de tabs
        handleTabChange(tab) {
            // Guardar datos al salir del tab periodo
            if (this.activeName === "period" && tab.name !== "period") {
                this.saveCurrentEmployeeData();
            }
            // Guardar datos al salir del tab pagos
            if (this.activeName === "payments" && tab.name !== "payments") {
                this.saveCurrentEmployeePaymentData();
            }
        },

        // Métodos para el manejo de datos de pago
        changePaymentMethod() {
            this.show_inputs_payment_method = [
                2,
                3,
                4,
                5,
                6,
                7,
                21,
                22,
                30,
                31,
                42,
                45,
                46,
                47
            ].includes(this.form.payment.payment_method_id);

            // Guardar inmediatamente en el almacenamiento del empleado actual
            if (this.selectedWorkerId) {
                if (!this.employeePaymentData[this.selectedWorkerId]) {
                    this.$set(
                        this.employeePaymentData,
                        this.selectedWorkerId,
                        {}
                    );
                }
                this.$set(
                    this.employeePaymentData[this.selectedWorkerId],
                    "payment_method_id",
                    this.form.payment.payment_method_id
                );

                // Guardar todos los datos inmediatamente
                this.saveCurrentEmployeePaymentData();
            }
        },

        clickAddPaymentDate() {
            this.form.payment_dates.push({
                payment_date: ""
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
            const paymentDatesCopy = JSON.parse(
                JSON.stringify(this.form.payment_dates || [])
            );

            this.$set(this.employeePaymentData, this.selectedWorkerId, {
                payment_method_id: this.form.payment.payment_method_id,
                bank_name: this.form.payment.bank_name || "",
                account_type: this.form.payment.account_type || "",
                account_number: this.form.payment.account_number || "",
                payment_dates: paymentDatesCopy
            });
        },

        loadEmployeePaymentData(workerId) {
            const data = this.employeePaymentData[workerId];
            const currentWorker = this.form.items.find(
                item => item.id === workerId
            );

            if (data) {
                // Cargar datos existentes
                this.form.payment.payment_method_id =
                    data.payment_method_id || null;
                this.form.payment.bank_name = data.bank_name || "";
                this.form.payment.account_type = data.account_type || "";
                this.form.payment.account_number = data.account_number || "";

                // Verificar si tiene fechas de pago definidas
                if (data.payment_dates && data.payment_dates.length > 0) {
                    this.form.payment_dates = JSON.parse(
                        JSON.stringify(data.payment_dates)
                    );
                } else {
                    // No tiene fechas de pago, generar por defecto
                    if (!this.editMode) {
                        const generatedDates = this.generateDefaultPaymentDatesForEmployee(
                            workerId
                        );
                        this.form.payment_dates = JSON.parse(
                            JSON.stringify(generatedDates)
                        );
                    } else {
                        this.form.payment_dates = [];
                    }
                }
            } else {
                // Usar datos del trabajador desde el backend si existen
                const workerPayment = currentWorker?.payment;
                this.form.payment.payment_method_id =
                    workerPayment?.payment_method_id || null;
                this.form.payment.bank_name = workerPayment?.bank_name || "";
                this.form.payment.account_type =
                    workerPayment?.account_type || "";
                this.form.payment.account_number =
                    workerPayment?.account_number || "";
                this.form.payment_dates = [];

                // Generar fechas de pago por defecto para este empleado
                if (!this.editMode) {
                    const generatedDates = this.generateDefaultPaymentDatesForEmployee(
                        workerId
                    );
                    this.form.payment_dates = JSON.parse(
                        JSON.stringify(generatedDates)
                    );
                } else {
                    this.form.payment_dates = [];
                }
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
            const editMatch = currentPath.match(
                /\/payroll\/block-payrolls\/edit-block\/(\d+)/
            );

            if (editMatch) {
                this.editMode = true;
                this.blockPayrollId = parseInt(editMatch[1]);
            }
        },

        async loadBlockPayrollData() {
            this.loading = true;
            try {
                const response = await this.$http.get(
                    `/${this.resource}/edit-block/${this.blockPayrollId}`
                );
                const blockPayroll = response.data;

                // Cargar datos básicos del formulario
                this.form.date_of_issue = blockPayroll.date_of_issue;
                this.form.time_of_issue = blockPayroll.time_of_issue;
                this.form.period_start =
                    blockPayroll.period?.period_start || "";
                this.form.period_end = blockPayroll.period?.period_end || "";
                this.form.type_document_id = blockPayroll.resolution_id;
                this.form.establishment_id = blockPayroll.establishment_id;
                this.form.establishment = blockPayroll.establishment;
                this.form.workers_quantity = blockPayroll.workers_quantity;
                this.form.accrued_total = blockPayroll.accrued_total;
                this.form.deductions_total = blockPayroll.deductions_total;

                // Cargar trabajadores desde el payload
                if (
                    blockPayroll.payload &&
                    blockPayroll.payload.selected_workers
                ) {
                    const workerIds = blockPayroll.payload.selected_workers;
                    await this.loadWorkersForEdit(workerIds);

                    // Inicializar datos por defecto para todos los empleados
                    this.initializeEmployeesArray();

                    // Cargar datos de período de cada empleado (sobrescribir los por defecto)
                    if (blockPayroll.payload.employee_period_data) {
                        this.employeePeriodData =
                            blockPayroll.payload.employee_period_data;
                    }

                    // Cargar datos de pago de cada empleado (sobrescribir los por defecto)
                    if (blockPayroll.payload.employee_payment_data) {
                        this.employeePaymentData =
                            blockPayroll.payload.employee_payment_data;
                    }

                    // Cargar datos de devengados de cada empleado
                    if (blockPayroll.payload.employee_accrued_data) {
                        this.employeeAccruedData =
                            blockPayroll.payload.employee_accrued_data;

                        // Detectar si el subsidio de transporte fue editado manualmente
                        // comparando con el valor automático que se calcularía
                        Object.keys(this.employeeAccruedData).forEach(
                            workerId => {
                                const accruedData = this.employeeAccruedData[
                                    workerId
                                ];
                                const currentWorker = this.form.items.find(
                                    item => item.id == workerId
                                );

                                if (
                                    currentWorker &&
                                    accruedData.transportation_allowance !==
                                        undefined
                                ) {
                                    const autoCalculatedAllowance = this.calculateTransportationAllowanceForWorker(
                                        currentWorker.salary
                                    );

                                    // Si el valor guardado es diferente al calculado automáticamente, marcarlo como editado manualmente
                                    if (
                                        parseFloat(
                                            accruedData.transportation_allowance
                                        ) !== autoCalculatedAllowance
                                    ) {
                                        this.$set(
                                            this
                                                .employeeTransportationManuallyEdited,
                                            workerId,
                                            true
                                        );
                                        console.log(
                                            `Worker ${workerId} transportation allowance was manually edited. Saved: ${accruedData.transportation_allowance}, Auto: ${autoCalculatedAllowance}`
                                        );
                                    }
                                }
                            }
                        );
                    }

                    // Restaurar valores de generate_provisions desde el payload
                    if (blockPayroll.payload.employee_period_data) {
                        this.form.items.forEach(worker => {
                            const periodData =
                                blockPayroll.payload.employee_period_data[
                                    worker.id
                                ];
                            if (
                                periodData &&
                                periodData.generate_provisions !== undefined
                            ) {
                                console.log(
                                    `Restaurando generate_provisions para worker ${worker.id}:`,
                                    periodData.generate_provisions
                                );
                                this.$set(
                                    worker,
                                    "generate_provisions",
                                    periodData.generate_provisions
                                );
                            }
                        });
                    }

                    // Seleccionar el primer trabajador
                    this.selectedWorkerId =
                        this.form.items.length > 0
                            ? this.form.items[0].id
                            : null;

                    // Cargar datos del primer empleado
                    if (this.selectedWorkerId) {
                        this.$nextTick(() => {
                            this.loadEmployeeData(this.selectedWorkerId);
                            this.loadEmployeePaymentData(this.selectedWorkerId);
                            this.loadEmployeeAccruedData(this.selectedWorkerId);
                        });
                    }
                }

                this.loading = false;
            } catch (error) {
                this.loading = false;
                this.$message.error(
                    "Error al cargar los datos del bloque de nómina"
                );
                console.error("Error loading block payroll data:", error);
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
                this.form.items = workersResponses
                    .map(response => response.data.workers[0])
                    .filter(worker => worker);
                this.form.workers_quantity = this.form.items.length;
            } catch (error) {
                console.error("Error loading workers for edit:", error);
                // Fallback: cargar todos los trabajadores activos
                await this.getActiveWorkers();
            }
        },

        // ================== MÉTODOS PARA EL TAB DE DEVENGADOS ==================

        // Método para cambiar el salario base total
        changeTotalBaseSalary() {
            this.calculateSalary();
            this.calculateAccruedTotal();

            // Recalculate all service bonuses
            this.form.accrued.service_bonus.forEach((item, index) => {
                if (item.quantity > 0) {
                    this.setPaymentServiceBonus(index);
                }
            });

            // Recalculate all severances
            this.form.accrued.severance.forEach((item, index) => {
                if (item.quantity > 0) {
                    this.setPaymentSeverance(index);
                }
            });

            // Recalculate extra hours if component is available
            if (this.$refs.componentDocumentPayrollExtraHours) {
                this.recalculateExtraHours();
            }

            this.saveCurrentEmployeeAccruedData();
            this.calculateGlobalAccruedTotal();
        },

        // Método para cambiar los días trabajados
        changeWorkedDays() {
            // Sincronizar con el tab de período
            this.form.period.worked_time = this.form.accrued.worked_days;

            // Actualizar en employeePeriodData si hay un empleado seleccionado
            if (this.selectedWorkerId) {
                if (!this.employeePeriodData[this.selectedWorkerId]) {
                    this.employeePeriodData[this.selectedWorkerId] = {};
                }
                this.$set(
                    this.employeePeriodData[this.selectedWorkerId],
                    "worked_days",
                    this.form.accrued.worked_days
                );
            }

            this.calculateSalary();
            this.calculateAccruedTotal();
            this.saveCurrentEmployeeAccruedData();
            this.calculateGlobalAccruedTotal();
        },

        // Método para cambiar el subsidio de transporte
        changeTransportationAllowance() {
            // Marcar que este empleado tiene subsidio editado manualmente
            if (this.selectedWorkerId) {
                this.$set(
                    this.employeeTransportationManuallyEdited,
                    this.selectedWorkerId,
                    true
                );
            }

            this.calculateAccruedTotal();
            this.saveCurrentEmployeeAccruedData();
            this.calculateGlobalAccruedTotal();
        },

        // Método para resetear el subsidio de transporte al valor automático
        resetTransportationAllowanceToAuto() {
            if (this.selectedWorkerId) {
                // Remover la marca de edición manual
                this.$delete(
                    this.employeeTransportationManuallyEdited,
                    this.selectedWorkerId
                );

                // Recalcular automáticamente
                this.applyTransportationAllowance();
                this.calculateAccruedTotal();
                this.saveCurrentEmployeeAccruedData();
                this.calculateGlobalAccruedTotal();
            }
        },

        // Calcular salario basado en días trabajados
        calculateSalary() {
            const baseSalary =
                parseFloat(this.form.accrued.total_base_salary) || 0;
            const workedDays = parseFloat(this.form.accrued.worked_days) || 0;

            if (baseSalary > 0 && workedDays > 0) {
                this.form.accrued.salary = (baseSalary / 30) * workedDays;
                // Aplicar subsidio de transporte después de calcular el salario
                this.applyTransportationAllowance();
            }
        },

        // Calcular total devengados
        calculateAccruedTotal() {
            let total = 0;

            // Función helper para convertir a número de forma segura
            const toNumber = value => {
                if (value === null || value === undefined || value === "")
                    return 0;
                const num = parseFloat(value);
                return isNaN(num) ? 0 : num;
            };

            total += toNumber(this.form.accrued.salary);
            total += toNumber(this.form.accrued.transportation_allowance);
            total += toNumber(this.form.accrued.telecommuting);
            total += toNumber(this.form.accrued.endowment);
            total += toNumber(this.form.accrued.sustenance_support);
            total += toNumber(this.form.accrued.withdrawal_bonus);
            total += toNumber(this.form.accrued.compensation);
            total += toNumber(this.form.accrued.salary_viatics);
            total += toNumber(this.form.accrued.non_salary_viatics);
            total += toNumber(this.form.accrued.refund);
            total += toNumber(this.form.accrued.total_extra_hours);

            // Sumar vacaciones
            this.form.accrued.common_vacation.forEach(vacation => {
                total += toNumber(vacation.payment);
            });
            this.form.accrued.paid_vacation.forEach(vacation => {
                total += toNumber(vacation.payment);
            });

            // Sumar bonificaciones y ayudas
            this.form.accrued.bonuses.forEach(bonus => {
                total += toNumber(bonus.salary_bonus);
                total += toNumber(bonus.non_salary_bonus);
            });
            this.form.accrued.aid.forEach(aid => {
                total += toNumber(aid.salary_assistance);
                total += toNumber(aid.non_salary_assistance);
            });

            // Sumar prestaciones sociales
            this.form.accrued.service_bonus.forEach(serviceBonus => {
                total += toNumber(serviceBonus.payment);
                total += toNumber(serviceBonus.paymentNS);
            });
            this.form.accrued.severance.forEach(severance => {
                total += toNumber(severance.payment);
                total += toNumber(severance.interest_payment);
            });

            this.form.accrued.accrued_total = total;

            // Guardar automáticamente después de calcular
            this.saveCurrentEmployeeAccruedData();

            // NO llamar calculateGlobalAccruedTotal() automáticamente aquí
            // Se llamará manualmente cuando sea necesario
        },

        // Calcular el total devengados global (suma de todos los empleados)
        calculateGlobalAccruedTotal() {
            // Protección contra recursión
            if (this.calculatingGlobalTotal) {
                return;
            }

            this.calculatingGlobalTotal = true;

            try {
                let globalTotal = 0;
                let employeeCount = 0;

                // Función helper para convertir a número de forma segura
                const toNumber = value => {
                    if (value === null || value === undefined || value === "")
                        return 0;
                    const num = parseFloat(value);
                    return isNaN(num) ? 0 : num;
                };

                // Sumar los totales devengados de todos los empleados
                Object.keys(this.employeeAccruedData).forEach(workerId => {
                    const employeeData = this.employeeAccruedData[workerId];

                    if (employeeData && employeeData.accrued_total) {
                        const employeeTotal = toNumber(
                            employeeData.accrued_total
                        );

                        // Validar que el valor no sea demasiado grande (posible corrupción)
                        if (employeeTotal > 10000000) {
                            // Más de 10 millones parece sospechoso
                            // Valor sospechoso, no incluir en la suma
                        } else {
                            globalTotal += employeeTotal;
                            employeeCount++;
                        }
                    }
                });

                // Actualizar el total global
                this.form.accrued_total = globalTotal;
            } finally {
                this.calculatingGlobalTotal = false;
            }
        },

        // Método para limpiar datos corruptos y recalcular totales
        resetAndRecalculateGlobalTotal() {
            console.log("🔄 Limpiando y recalculando totales...");

            // Resetear total global
            this.form.accrued_total = 0;

            // Recalcular cada empleado individualmente
            Object.keys(this.employeeAccruedData).forEach(workerId => {
                console.log(`🔄 Recalculando empleado ${workerId}...`);

                // Seleccionar temporalmente el empleado para recalcular
                const currentSelected = this.selectedWorkerId;
                this.selectedWorkerId = workerId;

                // Cargar datos del empleado
                this.loadEmployeeAccruedData(workerId);

                // Recalcular su total
                this.calculateAccruedTotal();

                // Restaurar selección original
                this.selectedWorkerId = currentSelected;

                // Cargar datos del empleado original
                if (currentSelected) {
                    this.loadEmployeeAccruedData(currentSelected);
                }
            });

            console.log("✅ Limpieza completada");
        },

        // Método de emergencia para resetear datos corruptos (ejecutar desde consola)
        emergencyReset() {
            console.log("🚨 RESETEO DE EMERGENCIA");

            // 1. Resetear total global
            this.form.accrued_total = 0;

            // 2. Limpiar datos corruptos de employeeAccruedData
            Object.keys(this.employeeAccruedData).forEach(workerId => {
                const data = this.employeeAccruedData[workerId];
                if (data && data.accrued_total > 10000000) {
                    console.log(
                        `🧹 Limpiando datos corruptos del empleado ${workerId}`
                    );
                    data.accrued_total = 0;
                }
            });

            // 3. Recalcular total del empleado actual si existe
            if (this.selectedWorkerId) {
                this.calculateAccruedTotal();
            }

            console.log("🟢 Reseteo completado");
        },

        // Método de debug para ver el estado actual
        debugTotals() {
            console.log("🔍 DEBUG TOTALS");
            console.log("================");
            console.log("📊 Total principal:", this.form.accrued_total);
            console.log("👤 Empleado seleccionado:", this.selectedWorkerId);
            console.log(
                "💰 Total del empleado actual:",
                this.form.accrued.accrued_total
            );
            console.log("📋 Todos los empleados:");

            Object.keys(this.employeeAccruedData).forEach(workerId => {
                const data = this.employeeAccruedData[workerId];
                console.log(
                    `  - Empleado ${workerId}: ${data?.accrued_total ||
                        "sin datos"}`
                );
            });
        },

        // Guardar datos de devengados del empleado actual
        saveCurrentEmployeeAccruedData() {
            if (this.selectedWorkerId && this.form.accrued) {
                // Asegurar que el objeto del empleado existe
                if (!this.employeeAccruedData[this.selectedWorkerId]) {
                    this.$set(
                        this.employeeAccruedData,
                        this.selectedWorkerId,
                        {}
                    );
                }

                // Guardar usando Vue.set para garantizar reactividad
                this.$set(this.employeeAccruedData, this.selectedWorkerId, {
                    ...this.form.accrued
                });
            }
        },

        // Cargar datos de devengados del empleado
        loadEmployeeAccruedData(workerId) {
            const currentWorker = this.form.items.find(
                item => item.id === workerId
            );
            const periodData = this.employeePeriodData[workerId];

            if (this.employeeAccruedData[workerId]) {
                this.form.accrued = { ...this.employeeAccruedData[workerId] };
            } else {
                // Inicializar datos vacíos
                this.form.accrued = {
                    total_base_salary: 0,
                    worked_days: 0,
                    salary: 0,
                    transportation_allowance: 0,
                    accrued_total: 0,
                    common_vacation: [],
                    paid_vacation: [],
                    service_bonus: [],
                    severance: [],
                    work_disabilities: [],
                    bonuses: [],
                    aid: [],
                    telecommuting: 0,
                    endowment: 0,
                    sustenance_support: 0,
                    withdrawal_bonus: 0,
                    compensation: 0,
                    salary_viatics: 0,
                    non_salary_viatics: 0,
                    refund: 0,
                    // Horas extras
                    heds: [], // Horas extras diurnas
                    hens: [], // Horas extras nocturnas
                    hrns: [], // Horas recargo nocturno
                    heddfs: [], // Horas extras diurnas dominical y festivos
                    hrddfs: [], // Horas recargo diurno dominical y festivos
                    hendfs: [], // Horas extras nocturnas dominical y festivos
                    hrndfs: [], // Horas recargo nocturno dominical y festivos
                    total_extra_hours: 0 // Total de horas extras
                };
            }

            // Sincronizar datos con otros tabs
            this.syncAccruedDataWithOtherTabs(workerId);

            // Aplicar subsidio de transporte después de cargar los datos SOLO si no fue editado manualmente
            this.$nextTick(() => {
                if (!this.employeeTransportationManuallyEdited[workerId]) {
                    this.applyTransportationAllowance();
                }
            });
        },

        // Método para sincronizar datos de devengados con otros tabs
        syncAccruedDataWithOtherTabs(workerId) {
            const currentWorker = this.form.items.find(
                item => item.id === workerId
            );
            const periodData = this.employeePeriodData[workerId];

            if (currentWorker) {
                // 1. Sincronizar días trabajados desde el tab Período
                const workedDays = periodData
                    ? periodData.worked_days || 30
                    : this.form.period.worked_time || 30;
                this.form.accrued.worked_days = parseInt(workedDays) || 30;

                // 2. Cargar salario básico desde el tab Trabajadores Seleccionados
                const basicSalary = parseFloat(currentWorker.salary) || 0;
                this.form.accrued.total_base_salary = basicSalary;

                // 3. Calcular el salario proporcional según días trabajados
                this.calculateSalary();

                // 4. Aplicar subsidio de transporte automáticamente SOLO si no fue editado manualmente
                if (!this.employeeTransportationManuallyEdited[workerId]) {
                    this.form.accrued.transportation_allowance = this.calculateTransportationAllowanceForWorker(
                        basicSalary
                    );
                }

                // 5. Calcular total devengados
                this.calculateAccruedTotal();

                // 6. Guardar en los datos del empleado
                if (this.employeeAccruedData[workerId]) {
                    this.employeeAccruedData[
                        workerId
                    ].transportation_allowance = this.form.accrued.transportation_allowance;
                }

                // 7. Asegurar que se guarden todos los datos del empleado actual
                this.saveCurrentEmployeeAccruedData();

                // 8. Calcular total global después de guardar todos los datos
                this.$nextTick(() => {
                    this.calculateGlobalAccruedTotal();
                });

                // 9. Auto-generar prima de servicios si está habilitada la opción
                const workerData = this.form.items.find(
                    item => item.id === workerId
                );
                const periodData = this.employeePeriodData[workerId];

                if (
                    workerData &&
                    (workerData.generate_provisions ||
                        (periodData && periodData.generate_provisions))
                ) {
                    // Verificar si ya existe una prima de servicios generada automáticamente
                    const hasAutomaticProvision = this.form.accrued.service_bonus.some(
                        bonus => bonus.is_automatic_provision === true
                    );

                    if (!hasAutomaticProvision && basicSalary > 0) {
                        this.generateServiceBonusProvision();
                    }
                }
            }
        },

        // Método para calcular el subsidio de transporte para un trabajador específico
        calculateTransportationAllowanceForWorker(baseSalary) {
            if (!this.advancedConfiguration) return 0;

            const minimumSalary =
                parseFloat(this.advancedConfiguration.minimum_salary) || 0;
            const transportationAllowance =
                parseFloat(
                    this.advancedConfiguration.transportation_allowance
                ) || 0;

            // Aplicar subsidio si el salario básico es menor o igual a 2 salarios mínimos
            if (baseSalary <= minimumSalary * 2 && baseSalary > 0) {
                return transportationAllowance; // Asegurar que sea número
            } else {
                return 0;
            }
        },

        // Método para aplicar el subsidio de transporte según las reglas
        applyTransportationAllowance() {
            if (!this.advancedConfiguration) return;

            // Si el subsidio fue editado manualmente por el usuario, no sobrescribir
            if (
                this.selectedWorkerId &&
                this.employeeTransportationManuallyEdited[this.selectedWorkerId]
            ) {
                console.log(
                    `Transportation allowance for worker ${this.selectedWorkerId} was manually edited. Skipping automatic calculation.`
                );
                return;
            }

            // Obtener el salario básico del empleado seleccionado
            const currentWorker = this.form.items.find(
                item => item.id === this.selectedWorkerId
            );
            const baseSalary =
                parseFloat(
                    currentWorker
                        ? currentWorker.salary
                        : this.form.accrued.salary ||
                              this.form.accrued.total_base_salary ||
                              0
                ) || 0;

            const minimumSalary =
                parseFloat(this.advancedConfiguration.minimum_salary) || 0;
            const transportationAllowance =
                parseFloat(
                    this.advancedConfiguration.transportation_allowance
                ) || 0;

            // Aplicar subsidio si el salario básico es menor o igual a 2 salarios mínimos
            if (baseSalary <= minimumSalary * 2 && baseSalary > 0) {
                this.form.accrued.transportation_allowance = transportationAllowance;
            } else {
                this.form.accrued.transportation_allowance = 0;
            }

            // Guardar en los datos del empleado
            if (
                this.selectedWorkerId &&
                this.employeeAccruedData[this.selectedWorkerId]
            ) {
                this.employeeAccruedData[
                    this.selectedWorkerId
                ].transportation_allowance = this.form.accrued.transportation_allowance;
            }
        },

        // Agregar horas extras (placeholder)
        clickAddExtraHours() {
            if (
                parseFloat(this.form.accrued.salary) <= 0 ||
                parseFloat(this.form.accrued.total_base_salary) <= 0
            ) {
                return this.$message.warning(
                    "El campo Salario debe ser mayor a 0"
                );
            }
            this.showDialogDocumentPayrollExtraHours = true;
        },

        sumTotalsExtraHoursForm() {
            // this.calculateTotalAccrued()
            this.calculateAccruedTotal();
            this.saveCurrentEmployeeAccruedData();
            this.calculateGlobalAccruedTotal();
        },

        recalculateExtraHours() {
            this.$refs.componentDocumentPayrollExtraHours.recalculateDataExtraHours();
        },

        // === MÉTODOS PARA VACACIONES ===
        clickAddCommonVacation() {
            this.form.accrued.common_vacation.push({
                start_end_date: [],
                quantity: 0,
                payment: 0
            });
        },

        clickCancelCommonVacation(index) {
            this.form.accrued.common_vacation.splice(index, 1);
            this.calculateAccruedTotal();
            this.saveCurrentEmployeeAccruedData();
            this.calculateGlobalAccruedTotal();
        },

        changeCommonVacationStartEndDate(index) {
            const vacation = this.form.accrued.common_vacation[index];
            if (
                vacation.start_end_date &&
                vacation.start_end_date.length === 2
            ) {
                const startDate = new Date(vacation.start_end_date[0]);
                const endDate = new Date(vacation.start_end_date[1]);
                const diffTime = Math.abs(endDate - startDate);
                vacation.quantity =
                    Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            }
            this.saveCurrentEmployeeAccruedData();
        },

        changePaymentCommonVacation(index) {
            this.calculateAccruedTotal();
            this.saveCurrentEmployeeAccruedData();
            this.calculateGlobalAccruedTotal();
        },

        clickAddPaidVacation() {
            this.form.accrued.paid_vacation.push({
                start_end_date: [],
                quantity: 0,
                payment: 0
            });
        },

        clickCancelPaidVacation(index) {
            this.form.accrued.paid_vacation.splice(index, 1);
            this.calculateAccruedTotal();
            this.saveCurrentEmployeeAccruedData();
            this.calculateGlobalAccruedTotal();
        },

        changePaidVacationStartEndDate(index) {
            const vacation = this.form.accrued.paid_vacation[index];
            if (
                vacation.start_end_date &&
                vacation.start_end_date.length === 2
            ) {
                const startDate = new Date(vacation.start_end_date[0]);
                const endDate = new Date(vacation.start_end_date[1]);
                const diffTime = Math.abs(endDate - startDate);
                vacation.quantity =
                    Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            }
            this.saveCurrentEmployeeAccruedData();
        },

        changePaymentPaidVacation(index) {
            this.calculateAccruedTotal();
            this.saveCurrentEmployeeAccruedData();
            this.calculateGlobalAccruedTotal();
        },

        // === HELPER METHODS FOR CALCULATIONS ===
        roundNumber(number, decimals = 2) {
            return (
                Math.round(number * Math.pow(10, decimals)) /
                Math.pow(10, decimals)
            );
        },

        percentageToFactor(percentage) {
            return percentage / 100;
        },

        // === MÉTODOS PARA PRIMA DE SERVICIO ===
        clickAddServiceBonus() {
            this.form.accrued.service_bonus.push({
                quantity: 0,
                payment: 0,
                paymentNS: 0
            });
        },

        clickCancelServiceBonus(index) {
            this.form.accrued.service_bonus.splice(index, 1);
            this.saveCurrentEmployeeAccruedData();
        },

        changeQuantityServiceBonus(index) {
            this.setPaymentServiceBonus(index);
            this.saveCurrentEmployeeAccruedData();
        },

        setPaymentServiceBonus(index) {
            const serviceBonus = this.form.accrued.service_bonus[index];
            if (
                serviceBonus &&
                serviceBonus.quantity > 0 &&
                this.total_base_salary > 0
            ) {
                const dailyBase =
                    this.total_base_salary / this.quantity_days_year;
                serviceBonus.payment = this.roundNumber(
                    dailyBase * serviceBonus.quantity
                );
            }
        },

        changePaymentServiceBonus(index) {
            this.saveCurrentEmployeeAccruedData();
        },

        changePaymentNSServiceBonus(index) {
            this.saveCurrentEmployeeAccruedData();
        },

        // === MÉTODOS PARA CESANTÍAS ===
        clickAddSeverance() {
            this.form.accrued.severance.push({
                quantity: 0,
                payment: 0,
                percentage: 12, // 12% por defecto
                interest_payment: 0
            });
        },

        clickCancelSeverance(index) {
            this.form.accrued.severance.splice(index, 1);
            this.saveCurrentEmployeeAccruedData();
        },

        changeQuantitySeverance(index) {
            this.setPaymentSeverance(index);
            this.saveCurrentEmployeeAccruedData();
        },

        setPaymentSeverance(index) {
            const severance = this.form.accrued.severance[index];
            if (
                severance &&
                severance.quantity > 0 &&
                this.total_base_salary > 0
            ) {
                const dailyBase =
                    this.total_base_salary / this.quantity_days_year;
                severance.payment = this.roundNumber(
                    dailyBase * severance.quantity
                );
                // Automatically calculate interest payment if percentage is set
                if (severance.percentage > 0) {
                    severance.interest_payment = this.roundNumber(
                        severance.payment *
                            this.percentageToFactor(severance.percentage)
                    );
                }
            }
        },

        calculateInterestPayment(index) {
            const severance = this.form.accrued.severance[index];
            if (severance.payment && severance.percentage) {
                severance.interest_payment = this.roundNumber(
                    severance.payment *
                        this.percentageToFactor(severance.percentage)
                );
            }
            this.saveCurrentEmployeeAccruedData();
        },

        // === MÉTODOS PARA BONIFICACIONES ===
        clickAddBonuses() {
            this.form.accrued.bonuses.push({
                salary_bonus: 0,
                non_salary_bonus: 0
            });
        },

        clickCancelBonuses(index) {
            this.form.accrued.bonuses.splice(index, 1);
            this.calculateAccruedTotal();
            this.saveCurrentEmployeeAccruedData();
        },

        changeSalaryBonus(index) {
            this.calculateAccruedTotal();
            this.saveCurrentEmployeeAccruedData();
            this.calculateGlobalAccruedTotal();
        },

        // === MÉTODOS PARA AYUDAS ===
        clickAddAid() {
            this.form.accrued.aid.push({
                salary_assistance: 0,
                non_salary_assistance: 0
            });
        },

        clickCancelAid(index) {
            this.form.accrued.aid.splice(index, 1);
            this.calculateAccruedTotal();
            this.saveCurrentEmployeeAccruedData();
            this.calculateGlobalAccruedTotal();
        },

        changeSalaryAid(index) {
            this.calculateAccruedTotal();
            this.saveCurrentEmployeeAccruedData();
            this.calculateGlobalAccruedTotal();
        },

        // === MÉTODOS PARA CAMPOS OPCIONALES ===
        changeOptionalInputs() {
            this.calculateAccruedTotal();
            this.saveCurrentEmployeeAccruedData();
            this.calculateGlobalAccruedTotal();
        },

        // === MÉTODOS PARA INCAPACIDADES ===
        clickAddWorkDisability() {
            if (!this.form.accrued.work_disabilities) {
                this.form.accrued.work_disabilities = [];
            }
            this.form.accrued.work_disabilities.push({
                start_end_date: [],
                type: null,
                quantity: 0,
                payment: 0,
                is_complete: false
            });
        },

        clickCancelWorkDisability(index) {
            this.form.accrued.work_disabilities.splice(index, 1);
            this.saveCurrentEmployeeAccruedData();
        },

        changeWDisabilityStartEndDate(index) {
            const disability = this.form.accrued.work_disabilities[index];
            if (
                disability.start_end_date &&
                disability.start_end_date.length === 2
            ) {
                const startDate = new Date(disability.start_end_date[0]);
                const endDate = new Date(disability.start_end_date[1]);
                const diffTime = Math.abs(endDate - startDate);
                disability.quantity =
                    Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            }
            this.saveCurrentEmployeeAccruedData();
        },

        changeCompleteWorkDisability(index) {
            this.saveCurrentEmployeeAccruedData();
        }
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
};
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
