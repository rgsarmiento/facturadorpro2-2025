<template>
    <div class="card mb-0 pt-2 pt-md-0">
        <!--<div class="card-header bg-info">
            <h3 class="my-0">Consulta kardex</h3>
        </div> -->
        <div class="card mb-0">
                <div class="card-body">
                    <data-table :resource="resource">
                        <tr slot="heading" slot-scope="{ sortBy, getSortIcon, getSortClass }">
                            <th>#</th>
                            <th v-if="!item_id" :class="getSortClass('item_name')" @click="sortBy('item_name')">
                                Producto <i :class="getSortIcon('item_name')"></i>
                            </th>
                            <th :class="getSortClass('date_time')" @click="sortBy('date_time')">
                                Fecha y hora transacción <i :class="getSortIcon('date_time')"></i>
                            </th>
                            <th :class="getSortClass('type_transaction')" @click="sortBy('type_transaction')">
                                Tipo transacción <i :class="getSortIcon('type_transaction')"></i>
                            </th>
                            <th :class="getSortClass('number')" @click="sortBy('number')">
                                Número <i :class="getSortIcon('number')"></i>
                            </th>
                            <th :class="getSortClass('sale_note_asoc')" @click="sortBy('sale_note_asoc')">
                                NV. Asociada <i :class="getSortIcon('sale_note_asoc')"></i>
                            </th>
                            <th :class="getSortClass('date_of_issue')" @click="sortBy('date_of_issue')">
                                Feha emisión <i :class="getSortIcon('date_of_issue')"></i>
                            </th>
                            <th :class="getSortClass('input')" @click="sortBy('input')">
                                Entrada <i :class="getSortIcon('input')"></i>
                            </th>
                            <th :class="getSortClass('output')" @click="sortBy('output')">
                                Salida <i :class="getSortIcon('output')"></i>
                            </th>
                            <th v-if="item_id" :class="getSortClass('balance')" @click="sortBy('balance')">
                                Saldo <i :class="getSortIcon('balance')"></i>
                            </th>

                        <tr>
                        <tr slot-scope="{ index, row }">
                            <td>{{ index }}</td>
                            <td v-if="!item_id">{{row.item_name}}</td>
                            <td>{{row.date_time}}</td>
                            <td>{{row.type_transaction}}</td>
                            <td>{{row.number}}</td>
                            <td>{{row.sale_note_asoc}}</td>
                            <td>{{row.date_of_issue}}</td>
                            <td>{{row.input}}</td>
                            <td>{{row.output}}</td>
                            <td v-if="item_id">{{row.balance}}</td>
                        </tr>

                    </data-table>


                </div>
        </div>

    </div>
</template>

<script>

    import DataTable from '../../components/DataTableKardex.vue'

    export default {
        components: {DataTable},
        data() {
            return {
                resource: 'reports/kardex',
                form: {},
                item_id:null
            }
        },
        created() {
            this.$eventHub.$on('emitItemID', (item_id) => {
                // console.log(item_id)
                this.item_id = item_id
            })
        },
        methods: {


        }
    }
</script>
