<template>
    <div class="card mb-0 pt-2 pt-md-0">
        <div class="card-header bg-info">
            <h3 class="my-0">R. General por cliente/vendedor</h3>
        </div>
        <div class="card mb-0">
                <div class="card-body">
                    <data-table :resource="resource">
</th>
                            <th class="sorting" :class="getSortClass('number')" @click="sortBy('number')">
                                N° Pedido
                                <i :class="getSortIcon('number')"></i>
                            </th>
                            <th class="sorting" :class="getSortClass('customer_id')" @click="sortBy('customer_id')" v-if="columns.customer.visible">
                                Cliente
                                <i :class="getSortIcon('customer_id')"></i>
                            </th>
                            <th class="sorting" :class="getSortClass('user_id')" @click="sortBy('user_id')" v-if="columns.user.visible">
                                Vendedor
                                <i :class="getSortIcon('user_id')"></i>
                            </th>
                            <th class="text-center sorting" :class="getSortClass('total')" @click="sortBy('total')">
                                Monto
                                <i :class="getSortIcon('total')"></i>
                            </th>
                            <th class="text-center sorting" :class="getSortClass('state_id')" @click="sortBy('state_id')">
                                Estado
                                <i :class="getSortIcon('state_id')"></i>
                            </th>
                        <tr>
                        <tr slot-scope="{ index, row }">
                            <td>{{ index }}</td>
                            <td  class="text-left">{{row.date_of_issue}}</td>
                            <td  class="text-left">{{row.delivery_date}}</td>
                            <td  class="text-left">{{row.number_full}}</td>
                            <td v-if="columns.customer.visible">{{ row.customer_name }}<br/><small v-text="row.customer_number"></small></td>
                            <td  class="text-left" v-if="columns.user.visible">{{row.user_name}}</td>
                            <td  class="text-center">{{row.total}}</td>
                            <td  class="text-center">{{row.state_description}}</td>
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
                resource: 'reports/order-notes-general',
                form: {},
                columns: {
                    customer: {
                        visible: false
                    },
                    user: {
                        visible: false
                    },

                }

            }
        },
        async created() {

            this.$eventHub.$on('changeFilterColumn', (type) => {
                this.changeVisibleColumn(type)
            })

        }