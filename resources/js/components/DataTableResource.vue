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
                        <template v-if="search.column=='date_of_issue' || search.column=='date_of_due' || search.column=='date_of_payment' || search.column=='delivery_date'">
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
                </div>
            </div>
            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <slot name="heading" :sortBy="sortBy" :getSortIcon="getSortIcon" :getSortClass="getSortClass"></slot>
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

<style scoped>
/* Sorting styles */
th.sorting {
    cursor: pointer;
    user-select: none;
    position: relative;
    transition: background-color 0.2s;
}

th.sorting:hover {
    background-color: #f5f5f5;
}

th.sorting-active {
    background-color: #e8f4f8;
    font-weight: 600;
}

th.sorting i {
    margin-left: 4px;
    font-size: 12px;
    color: #999;
}

th.sorting-active i {
    color: #409eff;
}
</style>

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
                sort: {
                    column: null,
                    direction: 'asc'
                }
            }
        },

        computed: {
        },

        created() {
            this.$eventHub.$on('reloadData', () => {
                this.getRecords()
            })
        },

        async mounted () {
           // console.log(column_resource)
            await this.$http.get(`/${this.resource}/columns`).then((response) => {
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
                    sort_column: this.sort.column,
                    sort_direction: this.sort.direction,
                    ...this.search
                })
            },

            sortBy(column) {
                if (this.sort.column === column) {
                    this.sort.direction = this.sort.direction === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sort.column = column;
                    this.sort.direction = 'asc';
                }
                this.pagination.current_page = 1;
                this.getRecords();
            },

            getSortIcon(column) {
                if (this.sort.column !== column) {
                    return 'el-icon-d-caret';
                }
                return this.sort.direction === 'asc' ? 'el-icon-caret-top' : 'el-icon-caret-bottom';
            },

            getSortClass(column) {
                return this.sort.column === column ? 'sorting sorting-active' : 'sorting';
            },

            changeClearInput(){
                this.search.value = ''
                this.getRecords()
            }
        }
    }
</script>
