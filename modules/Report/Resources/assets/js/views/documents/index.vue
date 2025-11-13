<template>
    <div class="card mb-0 pt-2 pt-md-0">
        <div class="card-header bg-info">
            <h3 class="my-0">Consulta de Documentos</h3>
        </div>
        <div class="card mb-0">
            <div class="card-body">
                <data-table
                    :applyCustomer="true"
                    :resource="resource"
                    :colspan="11"
                >
                    <tr slot="heading" slot-scope="{ sortBy, getSortIcon, getSortClass }">
                        <th class="">#</th>
                        <th class="sorting" :class="getSortClass('user_id')" @click="sortBy('user_id')">
                            Usuario/Vendedor
                            <i :class="getSortIcon('user_id')"></i>
                        </th>
                        <th class="sorting" :class="getSortClass('document_type_id')" @click="sortBy('document_type_id')">
                            Tipo Documento
                            <i :class="getSortIcon('document_type_id')"></i>
                        </th>
                        <th class="sorting" :class="getSortClass('number')" @click="sortBy('number')">
                            Comprobante
                            <i :class="getSortIcon('number')"></i>
                        </th>
                        <th class="sorting" :class="getSortClass('date_of_issue')" @click="sortBy('date_of_issue')">
                            Fecha emisión
                            <i :class="getSortIcon('date_of_issue')"></i>
                        </th>
                        <th class="sorting" :class="getSortClass('affected_document')" @click="sortBy('affected_document')">
                            Doc. Afectado
                            <i :class="getSortIcon('affected_document')"></i>
                        </th>
                        <th class="">Cotización</th>
                        <th class="">Caso</th>
                        <th class="sorting" :class="getSortClass('customer_id')" @click="sortBy('customer_id')">
                            Cliente
                            <i :class="getSortIcon('customer_id')"></i>
                        </th>
                        <th class="sorting" :class="getSortClass('state_type_id')" @click="sortBy('state_type_id')">
                            Estado
                            <i :class="getSortIcon('state_type_id')"></i>
                        </th>
                        <th class="sorting" :class="getSortClass('currency_type_id')" @click="sortBy('currency_type_id')">
                            Moneda
                            <i :class="getSortIcon('currency_type_id')"></i>
                        </th>
                        <th class="text-right sorting" :class="getSortClass('total')" @click="sortBy('total')">
                            Total
                            <i :class="getSortIcon('total')"></i>
                        </th>
                    </tr>
                    <tr slot-scope="{ index, row }">
                        <td>{{ index }}</td>
                        <td>{{ row.user_name }}</td>
                        <td>{{ row.document_type_description }}</td>
                        <td>{{ row.number }}</td>
                        <td>{{ row.date_of_issue }}</td>
                        <td>{{ row.affected_document }}</td>
                        <td>{{ row.quotation_number_full }}</td>
                        <td>{{ row.sale_opportunity_number_full }}</td>
                        <td>
                            {{ row.customer_name }}<br /><small
                                v-text="row.customer_number"
                            ></small>
                        </td>
                        <td>{{ row.state_type_description }}</td>
                        <td>{{ row.currency_type_id }}</td>
                        <td class="text-right">
                            {{ row.total }}
                            <!-- {{ (row.document_type_id == '07') ? ( (row.total == 0) ? '0.00': '-'+row.total) : ((row.document_type_id!='07' && (row.state_type_id =='11'||row.state_type_id =='09')) ? '0.00':row.total) }} -->
                        </td>
                    </tr>
                </data-table>
            </div>
        </div>
    </div>
</template>

<script>
import DataTable from "../../components/DataTableReports.vue";
export default {
    components: { DataTable },
    data() {
        return {
            resource: "reports/sales",
            form: {}
        };
    },
    created() {},
    methods: {}
};
</script>
