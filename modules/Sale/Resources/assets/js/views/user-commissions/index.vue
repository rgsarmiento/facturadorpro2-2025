<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Comisiones</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickCreate()"><i class="fa fa-plus-circle"></i> Nuevo</button>
            </div>
        </div>
        <div class="card mb-0">
            <div class="card-header bg-info">
                <h3 class="my-0">Comisiones por vendedor</h3>
            </div>
            <div class="card-body">
                <data-table :resource="resource">
                    <tr slot="heading" slot-scope="{ sortBy, getSortIcon, getSortClass }" width="100%">
                        <th>#</th>
                        <th :class="getSortClass('user_name')" @click="sortBy('user_name')">
                            Vendedor <i :class="getSortIcon('user_name')"></i>
                        </th>
                        <th :class="getSortClass('type')" @click="sortBy('type')">
                            Tipo <i :class="getSortIcon('type')"></i>
                        </th>
                        <th :class="getSortClass('amount')" @click="sortBy('amount')">
                            Comisión <i :class="getSortIcon('amount')"></i>
                        </th>
                        <th class="text-right">Acciones</th>
                    <tr>
                    <tr slot-scope="{ index, row }">
                        <td>{{ index }}</td>
                        <td>{{ row.user_name }}</td>
                        <td>{{ row.type }}</td>
                        <td>{{ row.amount }}</td>
                        <td class="text-right">
                            <template v-if="typeUser === 'admin'">
                                <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click.prevent="clickCreate(row.id)">Editar</button>
                                <button type="button" class="btn waves-effect waves-light btn-xs btn-danger" @click.prevent="clickDelete(row.id)">Eliminar</button>
                            </template>
                        </td>
                    </tr>
                </data-table>
            </div>

            <user-commissions-form :showDialog.sync="showDialog"
                        :recordId="recordId"></user-commissions-form>


        </div>
    </div>
</template>
<script>

    import UserCommissionsForm from './form.vue'
    import DataTable from '@components/DataTable.vue'
    import {deletable} from '@mixins/deletable'

    export default {
        mixins: [deletable],
        props:['typeUser'],
        components: {UserCommissionsForm,  DataTable},
        data() {
            return {
                showDialog: false,
                showImportDialog: false,
                showWarehousesDetail: false,
                resource: 'user-commissions',
                recordId: null,
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
            }
        }
    }
</script>
