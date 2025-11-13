<template>
    <div class="card mb-0 pt-2 pt-md-0">
        <div class="card-header bg-info">
            <h3 class="my-0">Comisiones vendedores - ventas</h3>
        </div>
        <div class="card mb-0">
                <div class="card-body">
<th class="text-center sorting" :class="getSortClass('total_transactions')" @click="sortBy('total_transactions')">
                                Cantidad transacciones
                                <i :class="getSortIcon('total_transactions')"></i>
                            </th>
                            <th class="text-center sorting" :class="getSortClass('acum_sales')" @click="sortBy('acum_sales')">
                                Ventas acumuladas
                                <i :class="getSortIcon('acum_sales')"></i>
                            </th>
                            <th class="text-center sorting" :class="getSortClass('total_commision')" @click="sortBy('total_commision')">
                                Total comisiones
                                <i :class="getSortIcon('total_commision')"></i>
                            </th>
                        <tr>
                        <tr slot-scope="{ index, row }">
                            <td>{{ index }}</td>
                            <!-- <td>{{row.user_name}}</td> -->
                            <td>{{row.user_name}}</td>
                            <td class="text-center">{{row.total_transactions}}</td>
                            <td class="text-center">{{row.acum_sales}}</td>
                            <td class="text-center">{{row.total_commision}}</td>
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
         