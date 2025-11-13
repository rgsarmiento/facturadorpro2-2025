<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Usuarios</span></li>
            </ol>
            <div class="right-wrapper pull-right">

                <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" v-if="typeUser != 'integrator'" @click.prevent="clickCreate()"><i class="fa fa-plus-circle"></i> Nuevo</button>

                <!--<button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickImport()"><i class="fa fa-upload"></i> Importar</button>-->
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="my-0">Listado de usuarios</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th class="sorting" @click="sortBy('email')" style="cursor: pointer;">Email <i :class="getSortIcon('email')"></i></th>
                            <th class="sorting" @click="sortBy('name')" style="cursor: pointer;">Nombre <i :class="getSortIcon('name')"></i></th>
                            <th class="sorting" @click="sortBy('type')" style="cursor: pointer;">Perfil <i :class="getSortIcon('type')"></i></th>
                            <th class="sorting" @click="sortBy('prefix')" style="cursor: pointer;">Prefijo <i :class="getSortIcon('prefix')"></i></th>
                            <th>Api Token</th>
                            <th class="sorting" @click="sortBy('establishment_description')" style="cursor: pointer;">Establecimiento <i :class="getSortIcon('establishment_description')"></i></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(row, index) in records">
                            <td>{{ index + 1 }}</td>
                            <td>{{ row.email }}</td>
                            <td>{{ row.name }}</td>
                            <td>{{ row.type }}</td>
                            <td>{{ row.prefix }}</td>
                            <td>{{ row.api_token }}</td>
                            <td>{{ row.establishment_description }}</td>
                            <td class="text-right">
                                <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click.prevent="clickCreate(row.id)">Editar</button>
                                <button type="button" class="btn waves-effect waves-light btn-xs btn-danger"  @click.prevent="clickDelete(row.id)" v-if="row.id != 1">Eliminar</button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <users-form :showDialog.sync="showDialog"
                        :typeUser="typeUser"
                        :recordId="recordId"></users-form>
        </div>
    </div>
</template>

<script>

    import UsersForm from './form1.vue'
    import {deletable} from '../../../mixins/deletable'

    export default {
        props: ['typeUser'],
        mixins: [deletable],
        components: {UsersForm},
        data() {
            return {
                showDialog: false,
                resource: 'users',
                recordId: null,
                records: [],
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
