<template>
    <div class="card mb-0 pt-2 pt-md-0">
        <div class="card-header bg-info">
            <h3 class="my-0">Reporte de Remisiones</h3>
        </div>
        <div class="card mb-0">
                <div class="card-body">
                    <data-table :resource="resource">
                        <tr slot="heading" slot-scope="{ sortBy, getSortIcon, getSortClass }">
                            <th>#</th>
                            <th class="sorting" :class="getSortClass('date_of_issue')" @click="sortBy('date_of_issue')">
                                Fecha Emisión
                                <i :class="getSortIcon('date_of_issue')"></i>
                            </th>
                            <th class="sorting" :class="getSortClass('user_id')" @click="sortBy('user_id')">
                                Usuario/Vendedor
                                <i :class="getSortIcon('user_id')"></i>
                            </th>
                            <th class="sorting" :class="getSortClass('customer_id')" @click="sortBy('customer_id')">
                                Cliente
                                <i :class="getSortIcon('customer_id')"></i>
                            </th>
                            <th class="sorting" :class="getSortClass('state_type_id')" @click="sortBy('state_type_id')">
                                Estado
                                <i :class="getSortIcon('state_type_id')"></i>
                            </th>
                            <th class="sorting" :class="getSortClass('number')" @click="sortBy('number')">
                                Remisión
                                <i :class="getSortIcon('number')"></i>
                            </th>
                            <th>Cotización</th>
                            <th class="text-center sorting" :class="getSortClass('currency_type_id')" @click="sortBy('currency_type_id')">
                                Moneda
                                <i :class="getSortIcon('currency_type_id')"></i>
                            </th>
                            <th class="text-center sorting" :class="getSortClass('total')" @click="sortBy('total')">
                                Total
                                <i :class="getSortIcon('total')"></i>
                            </th>
                        <tr>
                        <tr slot-scope="{ index, row }">
                            <td>{{ index }}</td>
                            <td>{{row.date_of_issue}}</td>
                            <td>{{row.user_name}}</td>
                            <td>{{ row.customer_name }}<br/><small v-text="row.customer_number"></small></td>
                            <td>{{row.state_type_description}}</td>
                            <td>{{row.number_full}}</td>
                            <td>{{row.quotation_number_full}}</td>

                            <td class="text-center">{{row.currency_name}}</td>
                            <td class="text-center">{{ row.total}}</td>

                        </tr>

                    </data-table>


                </div>
        </div>

    </div>
</template>

<script>

    import DataTable from '../../components/DataTableReports.vue'

    export default {
        components: {DataTable},
        data() {
            return {
                resource: 'reports/co-remissions',
                form: {},

            }
        },
        async created() {
        },
        methods: {


        }
    }
</script>
