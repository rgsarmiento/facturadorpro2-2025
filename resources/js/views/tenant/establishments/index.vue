<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Establecimientos</span></li>
            </ol>
            <div class="right-wrapper pull-right">

                <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" v-if="typeUser != 'integrator'" @click.prevent="clickCreate()"><i class="fa fa-plus-circle"></i> Nuevo</button>

                <!--<button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickImport()"><i class="fa fa-upload"></i> Importar</button>-->
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="my-0">Listado de establecimientos</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th class="sorting" @click="sortBy('description')" style="cursor: pointer;">Descripción <i :class="getSortIcon('description')"></i></th>
                            <th class="text-right sorting" @click="sortBy('code')" style="cursor: pointer;">Código <i :class="getSortIcon('code')"></i></th>
                            <th class="text-right">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(row, index) in records" :key="index">
                            <td>{{ index + 1 }}</td>
                            <td>{{ row.description }}</td>
                            <td class="text-right">{{ row.code }}</td>
                            <td class="text-right">
                                <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click.prevent="clickCreate(row.id)">Editar</button>
                                <button type="button" class="btn waves-effect waves-light btn-xs btn-danger" v-if="typeUser != 'integrator'" @click.prevent="clickDelete(row.id)">Eliminar</button>
<!--                                <button type="button" class="btn waves-effect waves-light btn-xs btn-warning" @click.prevent="clickSeries(row.id)">Series</button>  -->
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <!-- <div class="row">
                    <div class="col">
                        <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" v-if="typeUser != 'integrator'" @click.prevent="clickCreate()"><i class="fa fa-plus-circle"></i> Nuevo</button>
                    </div>
                </div> -->
            </div>
            <establishments-form :showDialog.sync="showDialog"
                                :recordId="recordId"></establishments-form>
            <establishment-series :showDialog.sync="showDialogSeries"
                                :establishmentId="recordId"></establishment-series>
        </div>
    </div>
</template>

<script>

    import EstablishmentsForm from './form1.vue'
    import {deletable} from '../../../mixins/deletable'
    import EstablishmentSeries from './partials/series.vue'

    export default {
        props:['typeUser'],
        mixins: [deletable],
        components: {EstablishmentsForm,EstablishmentSeries},
        data() {
            return {
                showDialog: false,
                resource: 'establishments',
                recordId: null,
                records: [],
                showDialogSeries: false,
                sort: {
                    column: null,
                    direction: 'asc'
                }
            }
        },
        created() {
            this.$eventHub.$on('reloadData', () => {
                this.getData()
            })
            this.getData()
        },
        methods: {
            getData() {
                const params = new URLSearchParams();
                if (this.sort.column) {
                    params.append('sort_column', this.sort.column);
                    params.append('sort_direction', this.sort.direction);
                }
                this.$http.get(`/${this.resource}/records?${params.toString()}`)
                    .then(response => {
                        this.records = response.data.data
                    })
            },
            sortBy(column) {
                if (this.sort.column === column) {
                    this.sort.direction = this.sort.direction === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sort.column = column;
                    this.sort.direction = 'asc';
                }
                this.getData();
            },
            getSortIcon(column) {
                if (this.sort.column !== column) return 'el-icon-d-caret';
                return this.sort.direction === 'asc' ? 'el-icon-caret-top' : 'el-icon-caret-bottom';
            },
            clickCreate(recordId = null) {
                this.recordId = recordId
                this.showDialog = true
            },
            clickSeries(recordId = null) {
                this.recordId = recordId
                this.showDialogSeries = true
            },
            clickDelete(id) {
                this.destroy(`/${this.resource}/${id}`).then(() =>
                    this.$eventHub.$emit('reloadData')
                )
            }
        }
    }
</script>

<style>
th.sorting {
    cursor: pointer;
}

th.sorting:hover {
    background-color: #f5f5f5;
}
</style>
