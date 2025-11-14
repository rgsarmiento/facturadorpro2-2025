<template>
    <div v-loading="loading">
        <div class="page-header pr-0">
            <h2>
                <a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a>
            </h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Bloques de Nóminas</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <a
                    :href="`/${resource}/create`"
                    class="btn btn-custom btn-sm  mt-2 mr-2"
                    ><i class="fa fa-plus-circle"></i> Nuevo</a
                >
            </div>
        </div>
        <div class="card mb-0">
            <div class="card-header bg-info">
                <h3 class="my-0">Listado de Bloques de Nóminas</h3>
            </div>
            <div class="card-body">
                <data-table :resource="resource">
                    <tr slot="heading" slot-scope="{ sortBy, getSortIcon, getSortClass }" width="100%">
                        <th>#</th>
                        <th class="sorting" :class="getSortClass('date_of_issue')" @click="sortBy('date_of_issue')">Fecha Emision <i :class="getSortIcon('date_of_issue')"></i></th>
                        <th class="sorting" :class="getSortClass('state_block_id')" @click="sortBy('state_block_id')">Estado <i :class="getSortIcon('state_block_id')"></i></th>
                        <th class="sorting" :class="getSortClass('workers_quantity')" @click="sortBy('workers_quantity')">Cantidad Empleados <i :class="getSortIcon('workers_quantity')"></i></th>
                        <th class="sorting" :class="getSortClass('payroll_period_id')" @click="sortBy('payroll_period_id')">Periodo <i :class="getSortIcon('payroll_period_id')"></i></th>
                        <th class="text-center sorting" :class="getSortClass('accrued_total')" @click="sortBy('accrued_total')">T. Devengados <i :class="getSortIcon('accrued_total')"></i></th>
                        <th class="text-center sorting" :class="getSortClass('deductions_total')" @click="sortBy('deductions_total')">T. Deducciones <i :class="getSortIcon('deductions_total')"></i></th>
                        <th class="text-center sorting" :class="getSortClass('net_total')" @click="sortBy('net_total')">Vr. Bloque <i :class="getSortIcon('net_total')"></i></th>
                        <th class="text-center">Opciones</th>
                    </tr>
                    <tr slot-scope="{ index, row }">
                        <td>{{ index }}</td>
                        <td>{{ row.date_of_issue }}</td>
                        <td class>
                            <span
                                class="badge bg-secondary text-white"
                                :class="{
                                    'bg-secondary': row.state_block_id === 1,
                                    'bg-success': row.state_block_id === 5,
                                    'bg-danger': row.state_block_id === 6
                                }"
                            >
                                {{ row.state_block_name }}
                            </span>
                        </td>
                        <td class="text-center">{{ row.workers_quantity }}</td>
                        <td>
                            {{ row.period_start_date }} -
                            {{ row.period_end_date }}
                        </td>
                        <td class="text-center">
                            {{ getFormatDecimal(row.accrued_total) }}
                        </td>
                        <td class="text-center">
                            {{ getFormatDecimal(row.deductions_total) }}
                        </td>
                        <td class="text-center">
                            {{
                                getFormatDecimal(
                                    calculateBlockTotal(
                                        row.accrued_total,
                                        row.deductions_total
                                    )
                                )
                            }}
                        </td>
                        <td class="text-center">
                            <template v-if="row.state_block_id == 1">
                                <a
                                    :href="`/${resource}/edit-block/${row.id}`"
                                    class="btn waves-effect waves-light btn-xs btn-info m-1__2"
                                    >Editar</a
                                >
                            </template>
                            <button
                                type="button"
                                class="btn waves-effect waves-light btn-xs btn-info"
                                @click.prevent="clickOptions(row.id)"
                            >
                                Opciones
                            </button>
                        </td>
                    </tr>
                </data-table>
            </div>
        </div>

        <!-- Dialog de opciones -->
        <block-payroll-options
            :showDialog.sync="showDialogBlockPayrollsOptions"
            :recordId="recordId"
        />
    </div>
</template>

<script>
import DataTable from "@components/DataTableResource.vue";
import BlockPayrollOptions from "./partials/options.vue";
import { deletable } from "@mixins/deletable";

export default {
    mixins: [deletable],

    components: {
        DataTable,
        BlockPayrollOptions
    },

    data() {
        return {
            showDialog: false,
            resource: "payroll/block-payrolls",
            recordId: null,
            recordNumberFull: null,
            loading: false,
            showDialogBlockPayrollsOptions: false
        };
    },

    created() {},

    methods: {
        clickOptions(recordId = null) {
            this.recordId = recordId;
            this.showDialogBlockPayrollsOptions = true;
        },

        // Formatear números con separadores de miles
        getFormatDecimal(value) {
            if (!value || isNaN(value)) return "0.00";

            const num = parseFloat(value);
            return num.toLocaleString("es-CO", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        // Calcular total del bloque (devengados - deducciones)
        calculateBlockTotal(accruedTotal, deductionsTotal) {
            const accrued = parseFloat(accruedTotal) || 0;
            const deductions = parseFloat(deductionsTotal) || 0;
            return accrued - deductions;
        }
    }
};
</script>
