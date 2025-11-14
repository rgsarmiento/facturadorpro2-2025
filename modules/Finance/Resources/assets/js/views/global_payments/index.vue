<template>
    <div class="card mb-0 pt-2 pt-md-0">
        <div class="card-header bg-info">
            <h3 class="my-0">Pagos</h3>
        </div>
        <div class="card mb-0">
                <div class="card-body">
                    <data-table :resource="resource">
                        <tr slot="heading" slot-scope="{ sortBy, getSortIcon, getSortClass }">
                            <th class="sorting" :class="getSortClass('id')" @click="sortBy('id')">#  <i :class="'el-icon-' + getSortIcon('id')"></i></th>
                            <th class="sorting" :class="getSortClass('person_name')" @click="sortBy('person_name')">Adquiriente <i :class="'el-icon-' + getSortIcon('person_name')"></i></th>
                            <th class="">Documento</th>
                            <th class="sorting" :class="getSortClass('currency_type_id')" @click="sortBy('currency_type_id')">Moneda <i :class="'el-icon-' + getSortIcon('currency_type_id')"></i></th>
                            <th class="sorting" :class="getSortClass('instance_type_description')" @click="sortBy('instance_type_description')">Tipo <i :class="'el-icon-' + getSortIcon('instance_type_description')"></i></th>
                            <th class="sorting" :class="getSortClass('destination_description')" @click="sortBy('destination_description')">Destino <i :class="'el-icon-' + getSortIcon('destination_description')"></i></th>
                            <th class="sorting" :class="getSortClass('date_of_payment')" @click="sortBy('date_of_payment')">F. Pago <i :class="'el-icon-' + getSortIcon('date_of_payment')"></i></th>
                            <th class="">Método</th>
                            <th class="">Referencia</th>
                            <th class="sorting" :class="getSortClass('total')" @click="sortBy('total')">Pago <i :class="'el-icon-' + getSortIcon('total')"></i></th>

                        <tr>
                        <tr slot-scope="{ index, row }">
                            <td>{{ index }}</td>
                            <td>{{ row.person_name }}<br/><small v-text="row.person_number"></small></td>
                            <td>
                                {{row.number_full}}<br/>
                                <small v-text="row.document_type_description"></small>
                            </td>
                            <td>{{row.currency_type_id}}</td>
                            <td>{{row.instance_type_description}}</td>
                            <td>{{row.destination_description}}</td>
                            <td>{{row.date_of_payment}}</td>
                            <td>{{row.payment_method_type_description}}</td>
                            <td>{{row.reference}}</td>
                            <td>{{row.total}}</td>
                        </tr>
                    </data-table>


                </div>
        </div>

    </div>
</template>

<script>

    import DataTable from '../../components/DataTableGlobalPayment.vue'

    export default {
        components: {DataTable},
        data() {
            return {
                resource: 'finances/global-payments',
                form: {},

            }
        },
        async created() {
        },
        methods: {


        }
    }
</script>
