<template>
    <div v-loading="loading">
        <div class="page-header pr-0">
            <h2><a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Correos procesados</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <!-- <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickRunProcess()"><i class="fa fa-check"></i> Procesar correos</button> -->
                <el-button class="submit mt-2 mr-2" type="primary"  :loading="loading" @click.prevent="clickRunProcess()"  icon="el-icon-check"> Procesar correos</el-button>
            </div>
        </div>
        <div class="card mb-0">
            <div class="card-header bg-info">
                <h3 class="my-0">Correos procesados</h3>
            </div>
            <div class="card-body">

                <data-table :resource="resource">
                    <tr slot="heading" slot-scope="{ sortBy, getSortIcon, getSortClass }">
                        <th>#</th>
                        <th class="sorting" :class="getSortClass('email_user')" @click="sortBy('email_user')">Usuario <i :class="getSortIcon('email_user')"></i></th>
                        <th class="sorting" :class="getSortClass('start_date')" @click="sortBy('start_date')">Fecha y hora inicio <i :class="getSortIcon('start_date')"></i></th>
                        <th class="sorting" :class="getSortClass('end_date')" @click="sortBy('end_date')">Fecha y hora término <i :class="getSortIcon('end_date')"></i></th>
                        <th class="sorting" :class="getSortClass('search_start_date')" @click="sortBy('search_start_date')">Fechas de búsqueda <i :class="getSortIcon('search_start_date')"></i></th>
                        <th class="text-center sorting" :class="getSortClass('success')" @click="sortBy('success')">Procesado <i :class="getSortIcon('success')"></i></th>
                        <th class="sorting" :class="getSortClass('errors')" @click="sortBy('errors')">Error <i :class="getSortIcon('errors')"></i></th>
                        <th class="text-right">Acciones</th>
                    <tr>
                    <tr slot-scope="{ index, row }">
                        <td>{{ index }}</td>
                        <td>{{ row.email_user }}</td>
                        <td>{{ row.start_date }} {{ row.start_time }} </td>
                        <td>{{ row.end_date }} {{ row.end_time }} </td>
                        <td>Desde <b>{{ row.search_start_date }}</b> - Hasta <b>{{ row.search_end_date }}</b> </td>
                        <td class="text-center">
                            <template v-if="row.success">
                                <i class="fas fa-check" style="color:red"></i>
                            </template>
                            <template v-else>
                                <i class="fas fa-times"  style="color:green"></i>
                            </template>
                        </td>
                        <td>{{ row.errors }}</td>

                        <td class="text-right">
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click.prevent="clickCreate(row.id)">Detalle</button>
                        </td>
                    </tr>
                </data-table>
            </div>

            <detail-form :showDialog.sync="showDialog"
                            :recordId="recordId"></detail-form>

            <search-form :showDialog.sync="showDialogSearch"></search-form>
        </div>
    </div>
</template>
<script>

    import DetailForm from './detail.vue'
    import SearchForm from './partials/search.vue'
    import DataTable from '@components/DataTable.vue'

    export default {
        components: {DataTable, DetailForm, SearchForm},
        data() {
            return {
                resource: 'co-email-reading',
                showDialog: false,
                showDialogSearch: false,
                loading: false,
                recordId: null,
            }
        },
        created() {
        },
        methods: {
            async clickRunProcess(){

                this.showDialogSearch = true

                // this.loading = true

                // await this.$http.get(`/co-radian-events/search-imap-emails`)
                //     .then(response => {

                //         if(response.data.success)
                //         {
                //             this.$message.success(response.data.message)
                //         }
                //         else
                //         {
                //             this.$message.error(response.data.message)
                //         }

                //         this.$eventHub.$emit('reloadData')
                //     })
                //     .catch(error => {
                //         console.log(error)
                //         this.$message.error(error.response.data.message)
                //     })
                //     .then(() => {
                //         this.loading = false
                //     })
            },
            clickCreate(recordId) {
                this.recordId = recordId
                this.showDialog = true
            },
        }
    }
</script>
