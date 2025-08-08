<template>
    <div  v-loading="loading">
        <div class="page-header pr-0">
            <h2><a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Bloques de Nóminas</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <a :href="`/${resource}/create`" class="btn btn-custom btn-sm  mt-2 mr-2"><i class="fa fa-plus-circle"></i> Nuevo</a>
            </div>
        </div>
        <div class="card mb-0">
            <div class="card-header bg-info">
                <h3 class="my-0">Listado de Bloques de Nóminas</h3>
            </div>
            <div class="card-body">
                <data-table :resource="resource">
                    <tr slot="heading" width="100%">
                        <th>#</th>
                        <th>Fecha Emision</th>
                        <th>Estado</th>
                        <th>Cantidad Empleados</th>
                        <th>Periodo</th>
                        <th class="text-center">T. Devengados</th>
                        <th class="text-center">T. Deducciones</th>
                        <th class="text-center">Opciones</th>
                    </tr>
                    <tr slot-scope="{ index, row }">
                        <td>{{ index }}</td>
                        <td>{{ row.date_of_issue }}</td>
                        <td>{{ row.state_block_id }}</td>
                        <td>{{ row.workers_quantity }}</td>
                        <td class="text-center">{{ row.period }}</td>
                        <td class="text-center">{{ row.accrued_total }}</td>
                        <td class="text-center">{{ row.deductions_total }}</td>
                        <td class="text-right">
                            <template v-if="row.state_block_id==1">
                                <a :href="`/${resource}/edit-block/${row.id}`" class="btn waves-effect waves-light btn-xs btn-info m-1__2">Editar</a>
                            </template>
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click.prevent="clickOptions(row.id)">Opciones</button>
                        </td>
                    </tr>
                </data-table>
            </div>
        </div>
    </div>
</template>

<script>
    import DataTable from '@components/DataTableResource.vue'
    import {deletable} from '@mixins/deletable'

    export default {
        mixins: [deletable],

        components: { DataTable },

        data() {
            return {
                showDialog: false,
                resource: 'payroll/block-payrolls',
                recordId: null,
                recordNumberFull: null,
                loading: false,
                showDialogBlockPayrollsOptions: false,
            }
        },

        created() {
        },

        methods: {
            clickOptions(recordId = null) {
                this.recordId = recordId
                this.showDialogBlockPayrollsOptions = true
            },
        }
    }
</script>
