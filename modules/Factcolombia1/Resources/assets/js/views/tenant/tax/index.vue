<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Impuestos</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <!-- <template v-if="typeUser === 'admin'">
                    <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickImportListPrice()"><i class="fa fa-upload"></i> Importar L. Precios</button>
                    <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickImport()"><i class="fa fa-upload"></i> Importar</button>
                </template> -->
                <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickCreate()"><i class="fa fa-plus-circle"></i> Nuevo</button>
                <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click="downloadExport"><i class="fas fa-file-download"></i> Exportar</button>
            </div>
        </div>
        <div class="card mb-0">
            <div class="card-header bg-info">
                <h3 class="my-0">Listado de impuestos</h3>
            </div>
            <div class="card-body">
                <data-table :resource="resource" :loading="loadDataTable">
                    <tr slot="heading" width="100%" slot-scope="{ sortBy, getSortIcon, getSortClass }">
                        <th>#</th>
                        <th :class="getSortClass('name')" @click="sortBy('name')">
                            Nombre <i :class="getSortIcon('name')"></i>
                        </th>
                        <th :class="getSortClass('code')" @click="sortBy('code')">
                            Código <i :class="getSortIcon('code')"></i>
                        </th>
                        <th :class="getSortClass('rate')" @click="sortBy('rate')">
                            Tasa <i :class="getSortIcon('rate')"></i>
                        </th>
                        <th :class="getSortClass('conversion')" @click="sortBy('conversion')">
                            Conversión <i :class="getSortIcon('conversion')"></i>
                        </th>
                        <th :class="getSortClass('is_percentaje')" @click="sortBy('is_percentaje')">
                            Tasa porcentaje <i :class="getSortIcon('is_percentaje')"></i>
                        </th>
                        <th :class="getSortClass('is_fixed_value')" @click="sortBy('is_fixed_value')">
                            Tasa valor fijo <i :class="getSortIcon('is_fixed_value')"></i>
                        </th>
                        <th :class="getSortClass('is_retention')" @click="sortBy('is_retention')">
                            Impuesto de retención <i :class="getSortIcon('is_retention')"></i>
                        </th>
                        <th :class="getSortClass('in_base')" @click="sortBy('in_base')">
                            Retención en base <i :class="getSortIcon('in_base')"></i>
                        </th>
                        <th :class="getSortClass('in_tax')" @click="sortBy('in_tax')">
                            Retención en impuesto <i :class="getSortIcon('in_tax')"></i>
                        </th>
                        <th :class="getSortClass('type_tax_id')" @click="sortBy('type_tax_id')">
                            Tipo impuesto <i :class="getSortIcon('type_tax_id')"></i>
                        </th>
                        <th class="text-right">Acciones</th>
                    <tr>
                    <tr slot-scope="{ index, row }">
                        <td>{{ index }}</td>
                        <td>{{ row.name }}</td>
                        <td>{{ row.code }}</td>
                        <td>{{ row.rate }}</td>
                        <td>{{ row.conversion }}</td>
                        <td>{{ row.is_percentaje }}</td>
                        <td>{{ row.is_fixed_value }}</td>
                        <td>{{ row.is_retention }}</td>
                        <td>{{ row.in_base }}</td>
                        <td>{{ row.in_tax }}</td>
                        <td>{{ row.type_tax_id_name }}</td>
                        <td class="text-right">
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click.prevent="clickCreate(row.id)">Editar</button>
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-danger" @click.prevent="clickDelete(row.id)">Eliminar</button>
                        </td>
                    </tr>
                </data-table>
            </div>
                <tenant-taxes-form :showDialog.sync="showDialog" :recordId="recordId"></tenant-taxes-form>  -->
            </div>
    </div>
</template>
<script>

    import TaxesForm from './form.vue'
    // import WarehousesDetail from './partials/warehouses.vue'
    // import ItemsImport from './import.vue'
    // import ItemsImportListPrice from './partials/import_list_price.vue'
    import DataTable from '@components/DataTable.vue'
    import {deletable} from '@mixins/deletable'

    export default {
        props: {
            route: {
                required: true
            },
        },
        mixins: [deletable],
        components: {TaxesForm, DataTable},
        // components: {ItemsForm, ItemsImport, DataTable, WarehousesDetail, ItemsImportListPrice},
        data() {
            return {
                showDialog: false,
                showImportDialog: false,
                showImportListPriceDialog: false,
                showWarehousesDetail: false,
                loadDataTable: false,
                resource: 'co-taxes',
                recordId: null,
                warehousesDetail:[],
                config: {}
            }
        },
        created() {
            // this.$http.get(`/configurations/record`) .then(response => {
            //     this.config = response.data.data
            // })
        },
        methods: {
            duplicate(id)
            {
                this.$http.post(`${this.resource}/duplicate`, {id})
                .then(response => {
                    if (response.data.success) {
                        this.$message.success('Se guardaron los cambios correctamente.')
                        this.$eventHub.$emit('reloadData')
                    } else {
                        this.$message.error('No se guardaron los cambios')
                    }
                })
                .catch(error => {

                })
                this.$eventHub.$emit('reloadData')
            },
            clickWarehouseDetail(warehouses){
                this.warehousesDetail = warehouses
                this.showWarehousesDetail = true
            },
            clickCreate(recordId = null) {
                this.recordId = recordId
                this.showDialog = true
            },
            clickImportListPrice() {
                this.showImportListPriceDialog = true
            },
            clickDelete(id) {
                this.destroy(`/${this.resource}/${id}`).then(() =>
                    this.$eventHub.$emit('reloadData')
                )
            },
            clickDisable(id)
            {
                this.disable(`/${this.resource}/disable/${id}`).then(() =>
                    this.$eventHub.$emit('reloadData')
                )
            },
            clickEnable(id){
                this.enable(`/${this.resource}/enable/${id}`).then(() =>
                    this.$eventHub.$emit('reloadData')
                )
            },
            clickBarcode(row) {

                if(!row.internal_id){
                    return this.$message.error('Para generar el código de barras debe registrar el código interno.')
                }

                window.open(`/${this.resource}/barcode/${row.id}`)
            },

            downloadExport() {
                window.open(`${this.route}/export`, '_blank');
            },

            refreshData() {
                this.loadDataTable = true;
                this.$eventHub.$emit('reloadData')
                this.loadDataTable = false;
            },
        }
    }
</script>
