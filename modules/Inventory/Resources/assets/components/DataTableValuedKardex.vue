<template>
    <div class="valued-kardex-report">
        <!-- Sección 1: Filtros de Búsqueda -->
        <div class="form-section">
            <h5 class="section-header"><i class="fas fa-filter"></i> Filtros de Búsqueda</h5>
            <div class="row">
                <div class="col-md-3">
                    <label class="control-label">Periodo</label>
                    <el-select v-model="form.period" @change="changePeriod">
                        <el-option key="month" value="month" label="Por mes"></el-option>
                        <el-option key="between_months" value="between_months" label="Entre meses"></el-option>
                        <el-option key="date" value="date" label="Por fecha"></el-option>
                        <el-option key="between_dates" value="between_dates" label="Entre fechas"></el-option>
                    </el-select>
                </div>
                <template v-if="form.period === 'month' || form.period === 'between_months'">
                    <div class="col-md-3">
                        <label class="control-label">Mes de</label>
                        <el-date-picker v-model="form.month_start" type="month"
                                        @change="changeDisabledMonths"
                                        value-format="yyyy-MM" format="MM/yyyy" :clearable="false"></el-date-picker>
                    </div>
                </template>
                <template v-if="form.period === 'between_months'">
                    <div class="col-md-3">
                        <label class="control-label">Mes al</label>
                        <el-date-picker v-model="form.month_end" type="month"
                                        :picker-options="pickerOptionsMonths"
                                        value-format="yyyy-MM" format="MM/yyyy" :clearable="false"></el-date-picker>
                    </div>
                </template>
                <template v-if="form.period === 'date' || form.period === 'between_dates'">
                    <div class="col-md-3">
                        <label class="control-label">Fecha del</label>
                        <el-date-picker v-model="form.date_start" type="date"
                                        @change="changeDisabledDates"
                                        value-format="yyyy-MM-dd" format="dd/MM/yyyy" :clearable="false"></el-date-picker>
                    </div>
                </template>
                <template v-if="form.period === 'between_dates'">
                    <div class="col-md-3">
                        <label class="control-label">Fecha al</label>
                        <el-date-picker v-model="form.date_end" type="date"
                                        :picker-options="pickerOptionsDates"
                                        value-format="yyyy-MM-dd" format="dd/MM/yyyy" :clearable="false"></el-date-picker>
                    </div>
                </template>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="control-label">Establecimiento</label>
                        <el-select v-model="form.establishment_id" clearable filterable>
                            <el-option v-for="option in establishments" :key="option.id" :value="option.id" :label="option.name"></el-option>
                        </el-select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección 2: Acciones y Reportes -->
        <div class="form-section">
            <h5 class="section-header"><i class="fas fa-file-export"></i> Acciones y Reportes</h5>
            <div class="row">
                <div class="col-md-12">
                    <el-button class="submit" type="primary" @click.prevent="getRecordsByFilter" :loading="loading_submit" icon="el-icon-search">
                        <i class="fas fa-search"></i> Buscar
                    </el-button>
                    <template v-if="records.length>0">
                        <el-button class="submit" type="success" @click.prevent="clickDownload('excel')">
                            <i class="fas fa-file-excel"></i> Exportar Excel
                        </el-button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Sección 3: Resultados del Kardex Valorizado -->
        <div class="form-section">
            <h5 class="section-header"><i class="fas fa-table"></i> Resultados del Kardex Valorizado</h5>
            <div class="row">
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
    </div>
</template>
<style scoped>
/* Contenedor principal */
.valued-kardex-report {
    background: #f8f9fa;
}

/* Secciones del formulario */
.form-section {
    background: white;
    padding: 25px;
    margin-bottom: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-left: 4px solid #409EFF;
}

/* Encabezados de sección */
.section-header {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0 0 20px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid #e9ecef;
    display: flex;
    align-items: center;
}

.section-header i {
    margin-right: 10px;
    font-size: 20px;
    color: #409EFF;
}

/* Colores por sección */
.form-section:nth-child(1) {
    border-left-color: #409EFF; /* Azul - Filtros */
}

.form-section:nth-child(1) .section-header i {
    color: #409EFF;
}

.form-section:nth-child(2) {
    border-left-color: #67C23A; /* Verde - Acciones y Reportes */
}

.form-section:nth-child(2) .section-header i {
    color: #67C23A;
}

.form-section:nth-child(3) {
    border-left-color: #E6A23C; /* Naranja - Resultados */
}

.form-section:nth-child(3) .section-header i {
    color: #E6A23C;
}

/* Estilos de tabla */
.font-custom {
    font-size: 15px !important;
}

th.sorting {
    cursor: pointer;
}

th.sorting:hover {
    background-color: #f5f5f5;
}

th.sorting-active {
    background-color: #e8f4f8;
    font-weight: bold;
}

.table-responsive {
    border-radius: 4px;
    overflow: hidden;
}

.table thead th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #495057;
    padding: 12px;
}

.table tbody td {
    padding: 12px;
    vertical-align: middle;
}
</style>
<script>

    import moment from 'moment'
    import queryString from 'query-string'

    export default {
        props: {
            resource: String,
        },
        data () {
            return {
                loading_submit:false,
                loading_search:false,
                columns: [],
                records: [],
                pagination: {},
                search: {},
                totals: {},
                establishment: null,
                establishments: [],
                form: {},
                sort: {
                    column: null,
                    direction: 'asc'
                },
                pickerOptionsDates: {
                    disabledDate: (time) => {
                        time = moment(time).format('YYYY-MM-DD')
                        return this.form.date_start > time
                    }
                },
                pickerOptionsMonths: {
                    disabledDate: (time) => {
                        time = moment(time).format('YYYY-MM')
                        return this.form.month_start > time
                    }
                },
            }
        },
        computed: {
        },
        created() {
            this.initForm()
            this.$eventHub.$on('reloadData', () => {
                this.getRecords()
            })
        },
        async mounted () {

            await this.$http.get(`/${this.resource}/filter`)
                .then(response => {
                    this.establishments = response.data.establishments;
                });


        },
        methods: {
            clickDownload(type) {
                let query = queryString.stringify({
                    ...this.form
                });
                window.open(`/${this.resource}/${type}/?${query}`, '_blank');
            },
            initForm(){

                this.form = {
                    establishment_id: null,
                    period: 'month',
                    date_start: moment().format('YYYY-MM-DD'),
                    date_end: moment().format('YYYY-MM-DD'),
                    month_start: moment().format('YYYY-MM'),
                    month_end: moment().format('YYYY-MM'),
                }

            },
            customIndex(index) {
                return (this.pagination.per_page * (this.pagination.current_page - 1)) + index + 1
            },
            async getRecordsByFilter(){

                this.loading_submit = await true
                await this.getRecords()
                this.loading_submit = await false

            },
            getRecords() {
                return this.$http.get(`/${this.resource}/records?${this.getQueryParameters()}`).then((response) => {
                    this.records = response.data.data
                    this.pagination = response.data.meta
                    this.pagination.per_page = parseInt(response.data.meta.per_page)
                    this.loading_submit = false
                    // this.initTotals()
                });


            },
            getQueryParameters() {
                return queryString.stringify({
                    page: this.pagination.current_page,
                    limit: this.limit,
                    sort_column: this.sort.column,
                    sort_direction: this.sort.direction,
                    ...this.form
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
                if (this.sort.column !== column) return 'el-icon-d-caret';
                return this.sort.direction === 'asc' ? 'el-icon-caret-top' : 'el-icon-caret-bottom';
            },
            getSortClass(column) {
                return this.sort.column === column ? 'sorting sorting-active' : 'sorting';
            },

            changeDisabledDates() {
                if (this.form.date_end < this.form.date_start) {
                    this.form.date_end = this.form.date_start
                }
                // this.loadAll();
            },
            changeDisabledMonths() {
                if (this.form.month_end < this.form.month_start) {
                    this.form.month_end = this.form.month_start
                }
                // this.loadAll();
            },
            changePeriod() {
                if(this.form.period === 'month') {
                    this.form.month_start = moment().format('YYYY-MM');
                    this.form.month_end = moment().format('YYYY-MM');
                }
                if(this.form.period === 'between_months') {
                    this.form.month_start = moment().startOf('year').format('YYYY-MM'); //'2019-01';
                    this.form.month_end = moment().endOf('year').format('YYYY-MM');;
                }
                if(this.form.period === 'date') {
                    this.form.date_start = moment().format('YYYY-MM-DD');
                    this.form.date_end = moment().format('YYYY-MM-DD');
                }
                if(this.form.period === 'between_dates') {
                    this.form.date_start = moment().startOf('month').format('YYYY-MM-DD');
                    this.form.date_end = moment().endOf('month').format('YYYY-MM-DD');
                }
                // this.loadAll();
            },
        }
    }
</script>
