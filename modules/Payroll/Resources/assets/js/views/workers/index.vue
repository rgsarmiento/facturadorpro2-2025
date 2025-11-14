<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Empleados</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickImport()"><i class="fa fa-plus-circle"></i> Importar Empleados</button>
                <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickCreate()"><i class="fa fa-plus-circle"></i> Nuevo</button>
            </div>
        </div>
        <div class="card mb-0">
            <div class="card-header bg-info">
                <h3 class="my-0">Listado de empleados</h3>
            </div>
            <div class="card-body">
                <data-table :resource="resource">
                    <tr slot="heading" slot-scope="{ sortBy, getSortIcon, getSortClass }" width="100%">
                        <th>#</th>
                        <th class="sorting" :class="getSortClass('code')" @click="sortBy('code')">Código <i :class="getSortIcon('code')"></i></th>
                        <th class="sorting" :class="getSortClass('fullname')" @click="sortBy('fullname')">Empleado <i :class="getSortIcon('fullname')"></i></th>
                        <!-- <th>Nombre</th> -->
                        <th class="sorting" :class="getSortClass('identification_number')" @click="sortBy('identification_number')">Número <i :class="getSortIcon('identification_number')"></i></th>
                        <th class="text-right">Acciones</th>
                    </tr>
                    <tr slot-scope="{ index, row }">
                        <td>{{ index }}</td>
                        <td>{{ row.code }}</td>
                        <td>{{ row.fullname }}</td>
                        <!-- <td>{{ row.first_name }}</td>   -->
                        <td>{{ row.identification_number }}</td>
                        <td class="text-right">
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click.prevent="clickCreate(row.id)">Editar</button>
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-danger" @click.prevent="clickDelete(row.id)">Eliminar</button>
                        </td>
                    </tr>
                </data-table>
            </div>
        </div>
        <workers-form :showDialog.sync="showDialog"
            :recordId="recordId"></workers-form>
        <workers-import :showDialog.sync="showImportDialog"></workers-import>
    </div>
</template>
<script>

    import WorkersForm from './form.vue'
    import DataTable from '@components/DataTableResource.vue'
    import {deletable} from '@mixins/deletable'
    import WorkersImport from './import.vue'

    export default {
        mixins: [deletable],
        components: {WorkersForm, DataTable, WorkersImport},
        data() {
            return {
                showDialog: false,
                resource: 'payroll/workers',
                recordId: null,
                showImportDialog: false,
            }
        },
        created() {
        },
        methods: {
            clickCreate(recordId = null) {
                this.recordId = recordId
                this.showDialog = true
            },
            clickDelete(id) {
                this.destroy(`/${this.resource}/${id}`).then(() =>
                    this.$eventHub.$emit('reloadData')
                )
            },
            clickImport() {
                this.showImportDialog = true
            }
        }
    }
</script>
