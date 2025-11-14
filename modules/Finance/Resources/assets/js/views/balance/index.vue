<template>
    <div class="card mb-0 pt-2 pt-md-0">
        <div class="card-header bg-info">
            <h3 class="my-0">Balance</h3>
        </div>
        <div class="card mb-0">
                <div class="card-body">
                    <data-table :resource="resource">
                        <tr slot="heading" slot-scope="{ sortBy, getSortIcon, getSortClass }">
                            <th class="">#</th>
                            <th class="sorting" :class="getSortClass('description')" @click="sortBy('description')"><strong>Nombre de la cuenta / Total pagos</strong> <i :class="'el-icon-' + getSortIcon('description')"></i></th>
                            <th class="text-center sorting" :class="getSortClass('document_payment')" @click="sortBy('document_payment')"> <strong>Factura Electrónica</strong> <i :class="'el-icon-' + getSortIcon('document_payment')"></i></th>
                            <th class="text-center sorting" :class="getSortClass('remission_payment')" @click="sortBy('remission_payment')"> <strong>Remisión</strong> <i :class="'el-icon-' + getSortIcon('remission_payment')"></i></th>
                            <th class="text-center sorting" :class="getSortClass('document_pos_payment')" @click="sortBy('document_pos_payment')"> <strong>Documento POS</strong> <i :class="'el-icon-' + getSortIcon('document_pos_payment')"></i></th>
                            <!-- <th class="text-center"> <strong>N. Venta</strong></th> -->
                            <th class="text-center sorting" :class="getSortClass('quotation_payment')" @click="sortBy('quotation_payment')"> <strong>Cotización</strong> <i :class="'el-icon-' + getSortIcon('quotation_payment')"></i></th>
                            <!-- <th class="text-center"> <strong>Contrato</strong></th> -->
                            <th class="text-center sorting" :class="getSortClass('income_payment')" @click="sortBy('income_payment')"> <strong>Ingresos</strong> <i :class="'el-icon-' + getSortIcon('income_payment')"></i></th>
                            <th class="text-center sorting" :class="getSortClass('purchase_payment')" @click="sortBy('purchase_payment')"> <strong>Compras</strong> <i :class="'el-icon-' + getSortIcon('purchase_payment')"></i></th>
                            <th class="text-center sorting" :class="getSortClass('expense_payment')" @click="sortBy('expense_payment')"> <strong>Gastos</strong> <i :class="'el-icon-' + getSortIcon('expense_payment')"></i></th>
                            <th class="text-center sorting" :class="getSortClass('balance')" @click="sortBy('balance')"> <strong>Saldo</strong> <i :class="'el-icon-' + getSortIcon('balance')"></i></th>
                        <tr>
                        <tr slot-scope="{ index, row }">
                            <td>{{ index }}</td>
                            <td>{{row.description}}</td>
                            <td class="text-center">{{row.document_payment}}</td>
                            <td class="text-center">{{row.remission_payment}}</td>
                            <td class="text-center">{{row.document_pos_payment}}</td>
                            <!-- <td class="text-center">{{row.sale_note_payment}}</td> -->
                            <td class="text-center">{{row.quotation_payment}}</td>
                            <!-- <td class="text-center">{{row.contract_payment}}</td> -->
                            <td class="text-center">{{row.income_payment}}</td>
                            <td class="text-center"> {{row.purchase_payment}}</td>
                            <td class="text-center">{{row.expense_payment}}</td>
                            <td class="text-center">{{row.balance}}</td>
                        </tr>
                    </data-table>
                </div>
        </div>

    </div>
</template>

<script>

    import DataTable from '../../components/DataTableWithoutPaging.vue'

    export default {
        components: {DataTable},
        data() {
            return {
                resource: 'finances/balance',
                form: {},

            }
        },
        async created() {
        },
        methods: {


        }
    }
</script>
