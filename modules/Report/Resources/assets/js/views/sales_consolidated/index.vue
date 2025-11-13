<template>
    <div class="card mb-0 pt-2 pt-md-0">
        <div class="card-header bg-info">
            <h3 class="my-0">Consolidado de items por cliente/vendedor - ventas</h3>
        </div>
        <div class="card mb-0">
                <div class="card-body">
    <i :class="getSortIcon('item_description')"></i>
                            </th>
                            <th class="text-center sorting" :class="getSortClass('item_quantity')" @click="sortBy('item_quantity')">
                                Cantidad
                                <i :class="getSortIcon('item_quantity')"></i>
                            </th>
                        <tr>
                        <tr slot-scope="{ index, row }">
                            <td>{{ index }}</td>
                            <td  class="text-left">{{row.item_description}}</td>
                            <td  class="text-center">{{row.item_quantity}}</td>
                        </tr>
                    </data-table>

                </div>
        </div>

    </div>
</template>

<script>

    import DataTable from '../../components/DataTableOrderNotesConsolidated.vue'

    export default {
        components: {DataTable},
        data() {
            return {
                resource: 'reports/sales-consolidated',
                form: {},

            }
        },
        async created() {
        }