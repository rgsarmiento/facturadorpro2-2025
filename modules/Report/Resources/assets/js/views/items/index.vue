<template>
    <div class="card mb-0 pt-2 pt-md-0">
        <div class="card-header bg-info">
            <h3 class="my-0">Consulta de documentos por producto</h3>
        </div>
        <div class="card mb-0">
                <div class="card-body">
                    <data-table :resource="resource">
                        <tr slot="heading" slot-scope="{ sortBy, getSortIcon, getSortClass }">
                            <th class="">#</th>
                            <th class="sorting" :class="getSortClass('date_of_issue')" @click="sortBy('date_of_issue')">
                                Fecha
                                <i :class="getSortIcon('date_of_issue')"></i>
                            </th>
                            <th class="sorting" :class="getSortClass('document_type_id')" @click="sortBy('document_type_id')">
                                Tipo Documento
                                <i :class="getSortIcon('document_type_id')"></i>
                            </th>
                            <th class="sorting" :class="getSortClass('series')" @click="sortBy('series')">
                                Prefijo
                                <i :class="getSortIcon('series')"></i>
                            </th>
                            <th class="sorting" :class="getSortClass('number')" @click="sortBy('number')">
                                Número
                                <i :class="getSortIcon('number')"></i>
                            </th>
                            <th class="">N° Documento</th>
                            <th class="">Cliente</th>
                            <th class="sorting" :class="getSortClass('quantity')" @click="sortBy('quantity')">
                                Cantidad
                                <i :class="getSortIcon('quantity')"></i>
                            </th>
                            <th class="sorting" :class="getSortClass('total')" @click="sortBy('total')">
                                Monto
                                <i :class="getSortIcon('total')"></i>
                            </th>
                        <tr>
                        <tr slot-scope="{ index, row }">
                            <td>{{ index }}</td>
                            <td>{{row.date_of_issue}}</td>
                            <td>{{row.document_type_description}}</td>
                            <td>{{row.series}}</td>
                            <td>{{row.alone_number}}</td>
                            <td>{{row.customer_number}}</td>
                            <td>{{row.customer_name}}</td>
                            <td>{{row.quantity}}</td>
                            <td>{{ row.total }}</td>
                        </tr>

                    </data-table>


                </div>
        </div>

    </div>
</template>

<script>

    import DataTable from '../../components/DataTableItems.vue'

    export default {
        components: {DataTable},
        data() {
            return {
                resource: 'reports/items',
                form: {},

            }
        },
        async created() {
        },
        methods: {


        }
    }
</script>
