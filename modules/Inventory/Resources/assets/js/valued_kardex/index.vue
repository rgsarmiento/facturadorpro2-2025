<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>{{ title }}</span></li>
            </ol>
            <div class="right-wrapper pull-right">
            </div>
        </div>
        <div class="card mb-0">
            <div class="card-header bg-info">
                <h3 class="my-0">{{ title }}</h3>
            </div>
            <div class="card-body">

                <data-table :resource="resource">
                    <tr slot="heading" slot-scope="{ sortBy, getSortIcon, getSortClass }">
                        <th>#</th>
                        <th :class="getSortClass('item_description')" @click="sortBy('item_description')">Producto <i :class="getSortIcon('item_description')"></i></th>
                        <th :class="getSortClass('category_description')" @click="sortBy('category_description')">Categoría <i :class="getSortIcon('category_description')"></i></th>
                        <th class="text-center" :class="getSortClass('unit_type_id')" @click="sortBy('unit_type_id')">Unidad <i :class="getSortIcon('unit_type_id')"></i></th>
                        <th class="text-center" :class="getSortClass('quantity_sale')" @click="sortBy('quantity_sale')">Unidades físicas vendidas <i :class="getSortIcon('quantity_sale')"></i></th>
                        <th class="text-center" :class="getSortClass('purchase_unit_price')" @click="sortBy('purchase_unit_price')">Costo unitario <i :class="getSortIcon('purchase_unit_price')"></i></th>
                        <th class="text-center" :class="getSortClass('total_sales')" @click="sortBy('total_sales')">Valor de ventas <i :class="getSortIcon('total_sales')"></i></th>
                        <th class="text-center" :class="getSortClass('item_cost')" @click="sortBy('item_cost')">Costo de producto <i :class="getSortIcon('item_cost')"></i></th>
                        <th class="text-center" :class="getSortClass('valued_unit')" @click="sortBy('valued_unit')">Unidad valorizada <i :class="getSortIcon('valued_unit')"></i></th>
                    <tr>
                    <tr slot-scope="{ index, row }">
                        <td>{{ index }}</td>
                        <td>{{ row.item_description }}</td>
                        <td>{{ row.category_description }}</td>
                        <td  class="text-center">{{ row.unit_type_id }}</td>
                        <td  class="text-center">{{ row.quantity_sale }}</td>
                        <td class="text-center">{{ row.purchase_unit_price }}</td>
                        <td class="text-center">{{ row.total_sales }}</td>
                        <td class="text-center">{{ row.item_cost }}</td>
                        <td class="text-center">{{ row.valued_unit }}</td>
                    </tr>
                </data-table>

            </div>


        </div>
    </div>
</template>

<script>


    import DataTable from '../../components/DataTableValuedKardex.vue'

    export default {
          components: {DataTable},
        data() {
            return {
                title: null,
                resource: 'reports/valued-kardex',
            }
        },
        created() {
            this.title = 'Kardex valorizado'
        },
        methods: {
        }
    }
</script>
