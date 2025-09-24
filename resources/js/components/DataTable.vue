<template>
    <div>
        <div class="row ">
            <div class="col-md-12 col-lg-12 col-xl-12 ">
                <div class="row" v-if="applyFilter">
                    <div class="col-lg-4 col-md-4 col-sm-12 pb-2">
                        <div class="d-flex">
                            <div style="width:100px">
                                Filtrar por:
                            </div>
                            <el-select v-model="search.column"  placeholder="Select" @change="changeClearInput">
                                <el-option v-for="(label, key) in columns" :key="key" :value="key" :label="label"></el-option>
                            </el-select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-12 pb-2">
                        <template v-if="search.column == 'date_of_issue' || search.column == 'date_of_due' || search.column == 'date_of_payment' || search.column == 'delivery_date' || search.column == 'date_opening' || search.column == 'date_closed' || search.column=='date_issue'">
                            <el-date-picker
                                v-model="search.value"
                                type="date"
                                style="width: 100%;"
                                placeholder="Buscar"
                                value-format="yyyy-MM-dd"
                                @change="getRecords">
                            </el-date-picker>
                        </template>
                        <template v-else>
                            <el-input placeholder="Buscar"
                                v-model="search.value"
                                style="width: 100%;"
                                prefix-icon="el-icon-search"
                                @input="getRecords">
                            </el-input>
                        </template>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-12 pb-2" v-if="resource.includes('co-documents')">
                        <el-button 
                            type="info" 
                            size="small" 
                            @click="toggleLoadAll"
                            :loading="loadingAll">
                            {{ loadAll ? 'Ver últimos 3 meses' : 'Cargar todos los registros' }}
                        </el-button>
                        <small class="d-block text-muted" v-if="!loadAll">
                            Mostrando solo últimos 3 meses para mayor velocidad
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <slot name="heading"></slot>
                        </thead>
                        <tbody>
                        <slot v-for="(row, index) in records" :row="row" :index="customIndex(index)"></slot>
                        </tbody>
                    </table>
                    <div>
                        <el-pagination
                                @current-change="getRecords"
                                layout="total, prev, pager, next"
                                :total="pagination.total"
                                :current-page.sync="pagination.current_page"
                                :page-size="pagination.per_page">
                        </el-pagination>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import moment from 'moment'
    import queryString from 'query-string'

    export default {
        props: {
            resource: String,
            applyFilter:{
                type: Boolean,
                default: true,
                required: false
            },
            externalFilters: {
                type: Object,
                default: () => ({})
            }
        },

        data () {
            return {
                search: {
                    column: null,
                    value: null
                },
                columns: [],
                records: [],
                pagination: {},
                loadAll: false,
                loadingAll: false
            }
        },

        computed: {
        },

        watch: {
            externalFilters: {
                handler() {
                    // Resetear a la primera página cuando cambien los filtros
                    this.pagination.current_page = 1
                    this.getRecords()
                },
                deep: true
            }
        },

        created() {
            this.$eventHub.$on('reloadData', () => {
                this.getRecords()
            })
        },

        async mounted () {
            let column_resource = _.split(this.resource, '/')
            // console.log(column_resource)
            console.log(`/${_.head(column_resource)}/columns`)
            await this.$http.get(`/${_.head(column_resource)}/columns`).then((response) => {
                this.columns = response.data
                this.search.column = _.head(Object.keys(this.columns))
            });
            await this.getRecords()
        },

        methods: {
            customIndex(index) {
                return (this.pagination.per_page * (this.pagination.current_page - 1)) + index + 1
            },

            getRecords() {
                return this.$http.get(`/${this.resource}/records?${this.getQueryParameters()}`).then((response) => {
                    this.records = response.data.data
                    this.pagination = response.data.meta
                    this.pagination.per_page = parseInt(response.data.meta.per_page)
                });
            },

            getQueryParameters() {
                return queryString.stringify({
                    page: this.pagination.current_page,
                    limit: this.limit,
                    load_all: this.loadAll,
                    ...this.search,
                    ...this.externalFilters
                })
            },

            changeClearInput(){
                this.search.value = ''
                this.getRecords()
            },

            toggleLoadAll() {
                this.loadingAll = true
                this.loadAll = !this.loadAll
                this.pagination.current_page = 1
                this.getRecords().finally(() => {
                    this.loadingAll = false
                })
            }
        }
    }
</script>
