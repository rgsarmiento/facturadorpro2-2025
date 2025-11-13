<template>
    <div class="card mb-0 pt-2 pt-md-0">
        <div class="card-header bg-info">
            <h3 class="my-0">Documentos POS</h3>
        </div>
        <div class="card mb-0">
            <div class="card-body">
                <data-table :applyCustomer="true" :resource="resource">
                    <tr slot="heading" slot-scope="{ sortBy, getSortIcon, getSortClass }">
                        <th class="">#</th>
                        <th class="" :class="getSortClass('user_name')" @click="sortBy('user_name')">
                            Usuario/Vendedor <i :class="getSortIcon('user_name')"></i>
                        </th>
                        <th class="" :class="getSortClass('document_type_description')" @click="sortBy('document_type_description')">
                            Tipo Documento <i :class="getSortIcon('document_type_description')"></i>
                        </th>
                        <th class="text-center" :class="getSortClass('number_full')" @click="sortBy('number_full')">
                            Documento <i :class="getSortIcon('number_full')"></i>
                        </th>
                        <th class="text-center" :class="getSortClass('date_of_issue')" @click="sortBy('date_of_issue')">
                            Fecha emisión <i :class="getSortIcon('date_of_issue')"></i>
                        </th>
                        <th class="" :class="getSortClass('customer_name')" @click="sortBy('customer_name')">
                            Cliente <i :class="getSortIcon('customer_name')"></i>
                        </th>
                        <th class="text-center" :class="getSortClass('state_type_description')" @click="sortBy('state_type_description')">
                            Estado <i :class="getSortIcon('state_type_description')"></i>
                        </th>
                        <th class="text-center" :class="getSortClass('currency_type_id')" @click="sortBy('currency_type_id')">
                            Moneda <i :class="getSortIcon('currency_type_id')"></i>
                        </th>
                        <th class="text-right" :class="getSortClass('total')" @click="sortBy('total')">
                            Total <i :class="getSortIcon('total')"></i>
                        </th>
                    <tr>
                    <tr slot-scope="{ index, row }">
                        <td>{{ index }}</td>
                        <td>{{row.user_name}}</td>
                        <td>{{row.document_type_description}}</td>
                        <td class="text-center">{{row.number_full}}</td>
                        <td class="text-center">{{row.date_of_issue}}</td>
                        <td>{{ row.customer_name }}<br/><small v-text="row.customer_number"></small></td>
                        <td class="text-center">{{row.state_type_description}}</td>
                        <td class="text-center">{{ row.currency_type_id}}</td>
                        <td class="text-right">{{ row.total }}</td>
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
                resource: 'reports/document-pos',
                form: {},

            }
        },
        async created() {
        },
        methods: {


        }
    }
</script>
