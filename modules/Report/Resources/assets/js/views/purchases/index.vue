<template>
    <div class="card mb-0 pt-2 pt-md-0">
        <div class="card-header bg-info">
            <h3 class="my-0">Consulta de Compras</h3>
        </div>
        <div class="card mb-0">
                <div class="card-body">
                    <data-table :resource="resource"  :applyCustomer="true" :colspan="8">
                        <tr slot="heading" slot-scope="{ sortBy, getSortIcon, getSortClass }">
                            <th class="">#</th>
                            <th class="sorting" :class="getSortClass('date_of_issue')" @click="sortBy('date_of_issue')">
                                F. Emisión
                                <i :class="getSortIcon('date_of_issue')"></i>
                            </th>
                            <th class="sorting" :class="getSortClass('date_of_due')" @click="sortBy('date_of_due')">
                                F. Vencimiento
                                <i :class="getSortIcon('date_of_due')"></i>
                            </th>
                            <th class="sorting" :class="getSortClass('supplier_id')" @click="sortBy('supplier_id')">
                                Proveedor
                                <i :class="getSortIcon('supplier_id')"></i>
                            </th>
                            <th class="sorting" :class="getSortClass('state_type_id')" @click="sortBy('state_type_id')">
                                Estado
                                <i :class="getSortIcon('state_type_id')"></i>
                            </th>
                            <th class="sorting" :class="getSortClass('number')" @click="sortBy('number')">
                                Número
                                <i :class="getSortIcon('number')"></i>
                            </th>

                            <th class="">F. Pago</th>
                            <th class="text-center sorting" :class="getSortClass('currency_type_id')" @click="sortBy('currency_type_id')">
                                Moneda
                                <i :class="getSortIcon('currency_type_id')"></i>
                            </th>
                            <!-- <th>Percepcion</th> -->
                            <th class="sorting" :class="getSortClass('total')" @click="sortBy('total')">
                                Total
                                <i :class="getSortIcon('total')"></i>
                            </th>
                        <tr>
                        <tr slot-scope="{ index, row }">
                            <td>{{ index }}</td>
                            <td>{{row.date_of_issue}}</td>
                            <td>{{row.date_of_due}}</td>
                            <td>{{ row.supplier_name }}<br/><small v-text="row.supplier_number"></small></td>
                            <td>{{row.state_type_description}}</td>
                            <td>{{row.number}}
                                <br/>
                                <small v-text="row.document_type_description"></small>

                            </td>
                            <td>{{row.payment_method_type_description}}</td>
                            <td class="text-center">{{ row.currency_type_id }}</td>
                            <!-- <td class="text-right">{{ (row.total_perception && row.state_type_id != '11') ? row.total_perception : '0.00' }}</td> -->

                            <td>{{ row.state_type_id == '11' ? '0.00' : row.total}}</td>

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
                resource: 'reports/purchases',
                form: {},

            }
        },
        async created() {
        },
        methods: {


        }
    }
</script>
