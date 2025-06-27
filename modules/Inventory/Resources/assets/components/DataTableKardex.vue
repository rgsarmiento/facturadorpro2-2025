<template>
    <div>
        <div class="row">
            <div class="col-md-12 col-lg-12 col-xl-12 ">
                <div class="row mt-2">
                        <div class="col-md-6">
                            <label class="control-label">Producto</label>
                            <el-select ref="itemSelect" v-model="form.item_id" filterable clearable @visible-change="handleDropdownVisible">
                                <el-option v-for="option in items" :key="option.id" :value="option.id" :label="option.full_description"></el-option>
                            </el-select>
                        </div>
                        <div class="col-md-3">
                            <label class="control-label">Fecha inicio</label>
                            <el-date-picker v-model="form.date_start" type="date"
                                            @change="changeDisabledDates"
                                            value-format="yyyy-MM-dd" format="dd/MM/yyyy" :clearable="true"></el-date-picker>
                        </div>
                        <div class="col-md-3">
                            <label class="control-label">Fecha término</label>
                            <el-date-picker v-model="form.date_end" type="date"
                                            :picker-options="pickerOptionsDates"
                                            value-format="yyyy-MM-dd" format="dd/MM/yyyy" :clearable="true"></el-date-picker>
                        </div>
                        <div class="col-md-6" style="margin-top:29px">
                            <el-button class="submit" type="primary" @click.prevent="getRecordsByFilter" :loading="loading_submit" icon="el-icon-search" >Buscar</el-button>
                            <template v-if="records.length>0">

                                <el-button class="submit" type="danger"  icon="el-icon-tickets" @click.prevent="clickDownload('pdf')" >Exportar PDF</el-button>

                                <el-button class="submit" type="success" @click.prevent="clickDownload('excel')"><i class="fa fa-file-excel" ></i>  Exportal Excel</el-button>

                            </template>
                        </div>
                </div>
                <div class="row mt-1 mb-4">
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

<style>
.font-custom{
    font-size:15px !important
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
                columns: [],
                records: [],
                headers: headers_token,
                document_types: [],
                pagination: {},
                search: {},
                totals: {},
                establishment: null,
                items: [],
                itemsPagination: { current_page: 1, last_page: 1, per_page: 50, total: 0 },
                loadingItems: false,
                form: {},
                pickerOptionsDates: {
                    disabledDate: (time) => {
                        time = moment(time).format('YYYY-MM-DD')
                        return this.form.date_start > time
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
            this.loadItems();
/*            await this.$http.get(`/${this.resource}/filter`)
                .then(response => {
                    this.items = response.data.items;
                });
            // await this.getRecords()  */
        },

        methods: {
            async loadItems(page = 1) {
                if (this.loadingItems) {
                    return;
                }
                this.loadingItems = true;
                try {
                    const response = await this.$http.get(`/${this.resource}/filter?per_page=${this.itemsPagination.per_page}&page=${page}`);
                    const data = response.data;
                    if (page === 1) {
                        this.items = data.data;
                    } else {
                        this.items = [...this.items, ...data.data];
                    }
                    this.itemsPagination = {
                        current_page: data.current_page,
                        last_page: data.last_page,
                        per_page: data.per_page,
                        total: data.total,
                  };
                } finally {
                    this.loadingItems = false;
                }
            },

            handleDropdownVisible(visible) {
                if (visible) {
                    this.$nextTick(() => {
                        // Encuentra el dropdown correcto usando el aria-controls del select
                        const selectRef = this.$refs.itemSelect;
                        if (!selectRef) return;
                        // El popupId es el id del dropdown
                        const popupId = selectRef.$el.getAttribute('aria-controls');
                        if (!popupId) {
                            return;
                        }
                        const dropdown = document.getElementById(popupId)?.querySelector('.el-select-dropdown__wrap');
                        if (dropdown) {
                            dropdown.addEventListener('scroll', this.handleDropdownScroll);
                        }
                    });
                }
                else {
                    // Limpia el listener al cerrar
                    const selectRef = this.$refs.itemSelect;
                    if (!selectRef) {
                        return;
                    }
                    const popupId = selectRef.$el.getAttribute('aria-controls');
                    if (!popupId) {
                        return;
                    }
                    const dropdown = document.getElementById(popupId)?.querySelector('.el-select-dropdown__wrap');
                    if (dropdown) {
                        dropdown.removeEventListener('scroll', this.handleDropdownScroll);
                    }
                }
            },

            handleDropdownScroll(e) {
                const dropdown = e.target;
                if (
                    dropdown.scrollTop + dropdown.clientHeight >= dropdown.scrollHeight - 10 &&
                    !this.loadingItems && this.itemsPagination.current_page < this.itemsPagination.last_page
                ) {
                    this.loadItems(this.itemsPagination.current_page + 1);
                }
            },

            changeDisabledDates() {
                if (this.form.date_end < this.form.date_start) {
                    this.form.date_end = this.form.date_start
                }
                // this.loadAll();
            },

            clickDownload(type) {
                let query = queryString.stringify({
                    ...this.form
                });
                window.open(`/${this.resource}/${type}/?${query}`, '_blank');
            },

            initForm(){
                this.form = {
                    item_id:null,
                    date_start:null,
                    date_end:null,
                }
            },

            customIndex(index) {
                return (this.pagination.per_page * (this.pagination.current_page - 1)) + index + 1
            },

            async getRecordsByFilter(){
                if(!this.form.item_id){
                    return this.$message.error('El producto es obligatorio')
                }
                this.loading_submit = await true
                await this.getRecords()
                this.loading_submit = await false
            },

            getRecords() {
                this.$eventHub.$emit('emitItemID', this.form.item_id)
                return this.$http.get(`/${this.resource}/records?${this.getQueryParameters()}`).then((response) => {
                    this.records = response.data.data
                    this.pagination = response.data.meta
                    this.pagination.per_page = parseInt(response.data.meta.per_page)
                    this.loading_submit = false
                });
            },

            getQueryParameters() {
                return queryString.stringify({
                    page: this.pagination.current_page,
                    limit: this.limit,
                    ...this.form
                })
            },
        }
    }
</script>
