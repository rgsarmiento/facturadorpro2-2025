<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Configuración</span> </li>
                <li><span class="text-muted">Documentos</span></li>
            </ol>
            <div class="right-wrapper pull-right">
            </div>
        </div>
        <div class="card mb-0">
            <div class="card-header bg-info">
                <h3 class="my-0">Listado</h3>
            </div>
            <div class="card-body table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                                <th>#</th>
                                <th class="sorting" :class="getSortClass('name')" @click="sortBy('name')">Nombre <i :class="getSortIcon('name')"></i></th>
                                <th class="sorting" :class="getSortClass('prefix')" @click="sortBy('prefix')">Prefijo <i :class="getSortIcon('prefix')"></i></th>
                                <th class="sorting" :class="getSortClass('from')" @click="sortBy('from')">Desde <i :class="getSortIcon('from')"></i></th>
                                <th class="sorting" :class="getSortClass('to')" @click="sortBy('to')">Hasta <i :class="getSortIcon('to')"></i></th>
                                <th class="sorting" :class="getSortClass('generated')" @click="sortBy('generated')">Generadas <i :class="getSortIcon('generated')"></i></th>
                                <th class="sorting" :class="getSortClass('resolution_number')" @click="sortBy('resolution_number')">Número de resolución <i :class="getSortIcon('resolution_number')"></i></th>
                                <th class="sorting" :class="getSortClass('resolution_date')" @click="sortBy('resolution_date')">Fecha resolución <i :class="getSortIcon('resolution_date')"></i></th>
                                <th class="sorting" :class="getSortClass('resolution_date_end')" @click="sortBy('resolution_date_end')">Fecha resolución hasta <i :class="getSortIcon('resolution_date_end')"></i></th>
                                <th class="sorting" :class="getSortClass('technical_key')" @click="sortBy('technical_key')">Clave técnica <i :class="getSortIcon('technical_key')"></i></th>
                                <th class="sorting" :class="getSortClass('description')" @click="sortBy('description')">Estado <i :class="getSortIcon('description')"></i></th>
                                <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in typeDocuments" :key="index">
                            <td>{{ index + 1 }}</td>
                            <td>{{ row.name }}</td>
                            <td>{{ row.prefix }}</td>
                            <td>{{ row.from }}</td>
                            <td>{{ row.to }}</td>
                            <td>{{ row.generated }}</td>
                            <td>{{ row.resolution_number }}</td>
                            <td>{{ row.resolution_date }}</td>
                            <td>{{ row.resolution_date_end }}</td>
                            <td>{{ row.technical_key }}</td>
                            <td>{{ row.description ? (row.description == 1 ? 'Activa' : 'Inactiva') : 'N/A' }}</td>
                            <td class="text-right">
                                <template>
                                    <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click.prevent="editItem(row)">Editar</button>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <edit-form @refresh="refresh" :showDialog.sync="dialog" :record="item" ></edit-form>
    </div>
</template>

<script>
    //import Helper from '../../../mixins/Helper';
    //import DataTable from '../../../components/DataTableConfigurationDocuments'
    import EditForm from './partial/edit'

    export default {
      //  mixins: [Helper],
        components:{ EditForm },

        props: {
            route: {
                required: true
            }
        },

        data: () => ({
            loadingCompany: false,
            typeDocuments: [],
            dialog: false,
            item: {},
            loadDataTable: false,
            items: [],
            item:null,
            sort: { column: null, direction: 'asc' }
        }),

        computed: {

        },

        mounted() {
            this.refresh();
        },

        methods: {
            refresh() {
                let params = {};
                if (this.sort.column) {
                    params.sort_column = this.sort.column;
                    params.sort_direction = this.sort.direction;
                }
                axios.get(`/co-configuration-all`, { params }).then(response => {
                    this.typeDocuments = response.data.typeDocuments;
                }).catch(error => {
                   // this.$setLaravelValidationErrorsFromResponse(error.response.data);
                   // this.$setLaravelErrors(error.response.data);
                }).then(() => {});
            },

            editItem(item) {
                this.item = JSON.parse(JSON.stringify(item));
                this.dialog = true;
            },

            validate(scope, model = null, models = null, modelObject = null) {
                debugger
                this.$validator.validateAll(scope).then(valid => {
                    if (valid) {
                        modelObject.prefix = modelObject.prefix.toUpperCase()
                        this.loadingCompany = true;
                        this.loadDataTable = true;
                        axios.post(`/client/configuration/type_document/${modelObject.id}`, modelObject).then(response => {
                            if (response.data.success) this.refresh();
                            //this.$setLaravelMessage(response.data);
                        }).catch(error => {
                            //this.$setLaravelValidationErrorsFromResponse(error.response.data);
                            //this.$setLaravelErrors(error.response.data);
                        }).then(() => {
                            this.loadingCompany = false;
                            this.dialog = false;
                            this.loadDataTable = false;
                        });
                    }
                });
            },

            sortBy(column) {
                if (this.sort.column === column) {
                    this.sort.direction = this.sort.direction === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sort.column = column;
                    this.sort.direction = 'asc';
                }
                this.refresh();
            },

            getSortIcon(column) {
                if (this.sort.column !== column) return 'el-icon-d-caret';
                return this.sort.direction === 'asc' ? 'el-icon-caret-top' : 'el-icon-caret-bottom';
            },

            getSortClass(column) {
                return this.sort.column === column ? 'sorting sorting-active' : 'sorting';
            }
        }
    }
</script>

<style lang="scss">
    .input-uppercase input {
        text-transform: uppercase
    }
</style>
