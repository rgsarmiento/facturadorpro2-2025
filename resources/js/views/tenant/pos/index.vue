<template>
<div>
    <header class="page-header pr-0">
        <!-- <h2 class="text-sm">POS</h2>
      <div class="right-wrapper pull-right">
        <h2 class="text-sm pr-5">T/C 3.321</h2>
        <h2 class="text-sm">{{user.name}}</h2>
      </div> -->
        <div class="row">
            <div class="col-md-6">
                <!-- <h2 class="text-sm">POS</h2> -->
                <h2>
                    <el-switch v-model="search_item_by_barcode" active-text="Buscar por código de barras" @change="changeSearchItemBarcode"></el-switch>
                </h2>

                <template v-if="!electronic">
                    <h2>
                        <el-switch v-model="type_refund" active-text="Devolución"></el-switch>
                    </h2>
                </template>

                <h2>
                    <el-button @click="showExpenseFormModal = true" class="btn btn-custom btn-sm  mt-2 mr-2">Registrar Gasto</el-button>
                </h2>
                <!-- Modal para el formulario de gastos -->
                <!-- Modal para el formulario de gastos -->
                <el-dialog :visible.sync="showExpenseFormModal" :modal="false" title="Nuevo Gasto" @close="handleCloseExpenseForm">
                    <ExpenseForm
                    @close="handleCloseExpenseForm"
                    @expenseAdded="handleExpenseAdded"
                    :isModal="true"
                    />
                </el-dialog>


            </div>
            <div class="col-md-4">
                <button v-if="tables_quantity > 0" ref="mesas" title="Cuentas" type="button" :data-quantity="tables_quantity" class="btn btn-custom btn-sm mt-2 mr-2" @click="cambiarContenido"><i class="fa fa-receipt"></i></button>
                <button type="button" @click="place = 'cat'" class="btn btn-custom btn-sm  mt-2 mr-2"><i class="fa fa-border-all"></i></button>
                <button type="button" :disabled="place == 'cat2'" @click="setView" class="btn btn-custom btn-sm  mt-2 mr-2"><i class="fa fa-bars"></i></button>
                <button type="button" :disabled="place== 'cat'" @click="back()" class="btn btn-custom btn-sm  mt-2 mr-2"><i class="fa fa-undo"></i></button>
            </div>
            <div class="col-md-2">
                <div class="right-wrapper">
                    <!-- <h2 class="text-sm pr-5">T/C  {{form.exchange_rate_sale}}</h2> -->
                    <h2 class="text-sm  pull-right">{{user.name}}</h2>
                </div>
            </div>
        </div>
    </header>

    <div v-if="plate_number_valid">
        <div v-if="!is_payment" class="row col-lg-12 m-0 p-0" v-loading="loading">
            <div v-if="botones.length === 0" class="col-lg-8 col-md-6 px-4 pt-3 hyo">
                <template v-if="!search_item_by_barcode && type != 'comand'">
                    <el-input v-show="place  == 'prod' || place == 'cat2'" placeholder="Buscar productos" size="medium" v-model="input_item" @input="searchItems" autofocus class="m-bottom">
                        <el-button slot="append" icon="el-icon-plus" @click.prevent="showDialogNewItem = true"></el-button>
                    </el-input>
                </template>
                <template v-else-if="type != 'comand'">
                    <el-input v-show="place  == 'prod' || place == 'cat2'" placeholder="Buscar productos" size="medium" v-model="input_item" @change="searchItemsBarcode" autofocus class="m-bottom">
                        <el-button slot="append" icon="el-icon-plus" @click.prevent="showDialogNewItem = true"></el-button>
                    </el-input>
                </template>

                <div v-if="place == 'cat2' && type != 'comand'" class="container testimonial-group">
                    <div class="row text-center flex-nowrap">
                        <div v-for="(item, index) in categories" @click="filterCategorie(item.id, true)" :style="{ backgroundColor: item.color}" :key="index" class="col-sm-3 pointer">{{item.name}}</div>
                    </div>
                </div> <br>

                <div v-if="place == 'cat' && type != 'comand'" class="row no-gutters">
                    <div v-for="(item, index) in categories" class="col" :key="index">
                        <div @click="filterCategorie(item.id)" class="card p-0 m-0 mb-1 mr-1 text-center">
                            <div :style="{ backgroundColor: item.color}" class="card-body pointer rounded-0" style="font-weight: bold;color: white;font-size: 18px;">
                                {{item.name}}
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="type == 'comand'" class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-ban fa-3x text-warning mb-3"></i>
                                <h4 class="card-title text-warning">Acceso Restringido</h4>
                                <p class="card-text">Este usuario no tiene permisos para realizar facturación.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="(place == 'prod' || place == 'cat2')" class="row pos-items">
                    <div v-for="(item,index) in items" v-bind:class="classObjectCol" :key="index">
                        <section class="card ">
                            <div class="card-body pointer px-2 pt-2" @click="clickAddItem(item,index)">
                                <el-tooltip class="item" effect="dark" :content="item.name" placement="bottom-end">
                                    <p class="font-weight-semibold mb-0 truncate-text">
                                        {{item.name}}
                                    </p>
                                </el-tooltip>
                                <!-- <p class="font-weight-semibold mb-0" v-if="item.name.length < 50">{{item.name}}</p> -->
                                <img :src="item.image_url" class="img-thumbail img-custom" />
                                <p class="text-muted font-weight-lighter mb-0">
                                    <small>{{item.internal_id}}</small>
                                    <template v-if="item.sets.length  > 0">
                                        <br>
                                        <small> {{ item.sets.join('-') }} </small>
                                    </template>
                                </p>
                            </div>
                            <div class="card-footer pointer text-center bg-primary">
                                <template v-if="!item.edit_unit_price">
                                    <h5 class="font-weight-semibold text-right text-white">
                                        <button
                                            type="button"
                                            class="btn btn-xs btn-primary-pos"
                                            @click="clickOpenInputEditUP(index)">
                                            <span style="font-size:16px;">&#9998;</span>
                                        </button>
                                        {{currency.symbol}} {{ getFormatDecimal(item.sale_unit_price_with_tax) }}
                                    </h5>
                                </template>
                                <template v-else>
                                    <el-input min="0" v-model="item.edit_sale_unit_price" class="mt-3 mb-3" size="mini">
                                        <el-button slot="append" icon="el-icon-check" type="primary" @click="clickEditUnitPriceItem(index)"></el-button>
                                        <el-button slot="append" icon="el-icon-close" type="danger" @click="clickCancelUnitPriceItem(index)"></el-button>
                                    </el-input>
                                </template>
                            </div>

                            <div v-if="configuration.options_pos" class=" card-footer  bg-primary btn-group flex-wrap" style="width:100% !important; padding:0 !important; ">
                                <el-row style="width:100%">
                                    <el-col :span="6">
                                        <el-tooltip class="item" effect="dark" content="Visualizar stock" placement="bottom-end">
                                            <button type="button" style="width:100% !important;" class="btn btn-xs btn-primary-pos" @click="clickWarehouseDetail(item)">
                                                <i class="fa fa-search"></i>
                                            </button>
                                        </el-tooltip>
                                    </el-col>
                                    <el-col :span="6">
                                        <el-tooltip class="item" effect="dark" content="Visualizar historial de ventas del producto (precio venta) y cliente" placement="bottom-end">
                                            <button type="button" style="width:100% !important;" class="btn btn-xs btn-primary-pos" @click="clickHistorySales(item.item_id)"><i class="fa fa-list"></i></button>
                                        </el-tooltip>
                                    </el-col>
                                    <el-col :span="6">
                                        <el-tooltip class="item" effect="dark" content="Visualizar historial de compras del producto (precio compra)" placement="bottom-end">
                                            <button type="button" style="width:100% !important;" class="btn btn-xs btn-primary-pos" @click="clickHistoryPurchases(item.item_id)"><i class="fas fa-cart-plus"></i></button>
                                        </el-tooltip>
                                    </el-col>
                                    <el-col :span="6">
                                        <el-tooltip class="item" effect="dark" content="Visualizar lista de precios disponibles" placement="bottom-end">
                                            <el-popover placement="top" title="Precios" width="400" trigger="click">
                                                <el-table v-if="item.item_unit_types" :data="item.item_unit_types">
                                                    <el-table-column width="140" label="Descripción" property="description"></el-table-column>
                                                    <el-table-column width="80" label="Unidad" property="unit_type_name"></el-table-column>
                                                    <el-table-column width="80" label="Precio">
                                                        <template slot-scope="{row}">
                                                            <span v-if="row.price_default == 1">{{ row.price1 }}</span>
                                                            <span v-else-if="row.price_default == 2">{{ row.price2 }}</span>
                                                            <span v-else-if="row.price_default == 3">{{ row.price3 }}</span>
                                                        </template>
                                                    </el-table-column>
                                                    <el-table-column width="70" label="">
                                                        <template slot-scope="{row}">
                                                            <button @click="setListPriceItem(row,index)" type="button" class="btn btn-custom btn-xs">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </template>
                                                    </el-table-column>
                                                </el-table>
                                                <button type="button" slot="reference" style="width:100% !important;" class="btn btn-xs btn-primary-pos"><i class="fas fa-money-bill-alt"></i></button>
                                            </el-popover>
                                        </el-tooltip>
                                    </el-col>
                                </el-row>
                            </div>

                            <!-- <div v-if="configuration.options_pos" class=" card-footer  bg-primary btn-group flex-wrap" style="width:100% !important; padding:0 !important; ">

                                <el-tooltip class="item" effect="dark" content="Visualizar stock" placement="bottom-end">
                                    <button type="button" style="width:25% !important;" class="btn btn-xs btn-primary-pos" @click="clickWarehouseDetail(item)">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </el-tooltip>

                                <el-tooltip class="item" effect="dark" content="Visualizar historial de ventas del producto (precio venta) y cliente" placement="bottom-end">
                                    <button type="button" style="width:25% !important;" class="btn btn-xs btn-primary-pos" @click="clickHistorySales(item.item_id)"><i class="fa fa-list"></i></button>
                                </el-tooltip>

                                <el-tooltip class="item" effect="dark" content="Visualizar historial de compras del producto (precio compra)" placement="bottom-end">
                                    <button type="button" style="width:25% !important;" class="btn btn-xs btn-primary-pos" @click="clickHistoryPurchases(item.item_id)"><i class="fas fa-cart-plus"></i></button>
                                </el-tooltip>

                                <el-tooltip class="item" effect="dark" content="Visualizar lista de precios disponibles" placement="bottom-end">
                                    <el-popover placement="top" title="Precios" width="370" trigger="click">
                                        <el-table v-if="item.item_unit_types" :data="item.item_unit_types">
                                            <el-table-column width="90" label="Precio">
                                                <template slot-scope="{row}">
                                                    <span v-if="row.price_default == 1">{{row.price1}}</span>
                                                    <span v-else-if="row.price_default == 2">{{row.price2}}</span>
                                                    <span v-else-if="row.price_default == 3">{{row.price3}}</span>
                                                </template>
                                            </el-table-column>
                                            <el-table-column width="80" label="Unidad" property="unit_type_id"></el-table-column>
                                            <el-table-column width="120" label="Descripción" property="description"></el-table-column>

                                            <el-table-column width="80" label="">
                                                <template slot-scope="{row}">
                                                    <button @click="setListPriceItem(row,index)" type="button" class="btn btn-custom btn-xs">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </template>
                                            </el-table-column>
                                        </el-table>
                                        <button type="button" slot="reference" style="width:100% !important;" class="btn btn-xs btn-primary-pos"><i class="fas fa-money-bill-alt"></i></button>
                                    </el-popover>
                                </el-tooltip>
                            </div> -->
                        </section>
                    </div>
                </div>
                <div v-if="place == 'prod' || place == 'cat2'" class="row">
                    <div class="col-md-12 text-center">
                        <el-pagination
                            @current-change="getRecords"
                            layout="total, prev, pager, next"
                            :total="pagination.total"
                            :current-page.sync="pagination.current_page"
                            :page-size="pagination.per_page"
                        >
                        </el-pagination>
                    </div>
                </div>
            </div>
            <div v-else class="col-lg-8 col-md-6 px-4 hyo d-flex flex-wrap justify-content-center" style="margin-top: 4%;">
                <button
                    v-for="(boton, index) in botones"
                    :key="boton.id"
                    @click="abrirModal(boton.db_id, boton.id)"
                    :class="['mesa-btn', { 'mesa-activa': boton.state === 1 }]">
                    <i class="fa fa-receipt"></i>
                    <span class="texto-boton">{{ boton.id }}</span>
                </button>
            </div>
            <div class="col-lg-4 col-md-6 bg-white m-0 p-0" style="height: calc(100vh - 110px)">
                <div class="h-75 bg-light" style="overflow-y: auto">
                    <div class="row py-3 border-bottom m-0 p-0">
                        <div class="col-8">
                            <el-select ref="select_person" v-model="form.customer_id" filterable placeholder="Cliente" @change="changeCustomer" @keyup.native="keyupCustomer" @keyup.enter.native="keyupEnterCustomer">
                                <el-option v-for="option in all_customers" :key="option.id" :label="option.description" :value="option.id"></el-option>
                            </el-select>
                        </div>
                        <div class="col-4">
                            <div class="btn-group d-flex" role="group">
                                <el-tooltip content="Buscar o Agregar Cliente" placement="top">
                                    <a class="btn btn-sm btn-default w-100" @click.prevent="showDialogNewPerson = true">
                                        <i class="fas fa-plus fa-wf"></i>
                                    </a>
                                </el-tooltip>
                                <a class="btn btn-sm btn-default w-100" @click="clickDeleteCustomer">
                                    <i class="fas fa-trash fa-wf"></i>
                                </a>
                                <!-- <a class="btn btn-sm btn-default w-100" @click="selectCurrencyType"> -->
                                <!-- <template v-if="form.currency_id == 'PEN'">
                        <strong>S/</strong>
                      </template>
                      <template v-else>
                        <strong>$</strong>
                      </template> -->
                                <!-- <i class="fa fa-usd" aria-hidden="true"></i> -->
                                <!-- </a> -->
                            </div>
                        </div>
                    </div>
                    <div class="row py-1 border-bottom m-0 p-0">
                        <div class="col-12">
                            <table class="table table-sm table-borderless mb-0">
                                <tr v-for="(item,index) in form.items" :key="index">
                                    <td width="20%">
                                        <el-input v-model="item.item.aux_quantity" :readonly="item.item.calculate_quantity" class @input="clickAddItem(item,index,true)"></el-input>
                                    </td>
                                    <td width="20%">
                                        <p class="m-0" style="line-height: 1em;">
                                            <span v-html="clearText(item.item.name)"></span><br>
                                            <small v-if="item.unit_type">{{ item.unit_type.name }}</small>
                                        </p>
                                        <small> {{nameSets(item.item_id)}} </small>
                                    </td>
                                    <td width="20%">
                                        <p class="font-weight-semibold m-0 text-center">
                                            <el-input v-model="item.sale_unit_price_with_tax" class="input-text-right" @input="clickAddItem(item,index,true)" :readonly="item.item.calculate_quantity">
                                            </el-input>
                                        </p>
                                    </td>
                                    <td width="30%">
                                        <p class="font-weight-semibold m-0 text-center">
                                            <el-input v-model="item.total" @input="calculateQuantity(index)" class="input-text-right" :readonly="!item.item.calculate_quantity">
                                            </el-input>
                                        </p>
                                    </td>
                                    <td class="text-right">
                                        <a class="btn btn-sm btn-default" @click="clickDeleteItem(index)">
                                            <i class="fas fa-trash fa-wf"></i>
                                        </a>
                                    </td>
                                </tr>
                                <tr v-for="(item,index) in items_refund" :key="index + 'R'">
                                    <td width="5%" style="text-align: center;" class="pos-list-label" v-if="item.unit_type">
                                        {{ item.unit_type.name }}
                                    </td>
                                    <td width="20%">
                                        <el-input :value=" '-' +item.quantity" :readonly="true" class></el-input>
                                    </td>
                                    <td width="20%">
                                        <p class="m-0">{{item.item.name}}</p>
                                        <small> {{nameSets(item.item_id)}} </small>
                                    </td>
                                    <td>
                                        <p class="font-weight-semibold m-0 text-center">{{currency.symbol}}</p>
                                    </td>
                                    <td width="20%">
                                        <p class="font-weight-semibold m-0 text-center">
                                            <el-input v-model="item.sale_unit_price_with_tax" class @input="clickAddItem(item,index,true)" :readonly="item.item.calculate_quantity">
                                            </el-input>
                                        </p>
                                    </td>
                                    <td width="30%">
                                        <p class="font-weight-semibold m-0 text-center">
                                            <el-input :value="'-' + item.total" :readonly="true">
                                            </el-input>
                                        </p>
                                    </td>
                                    <td class="text-right">
                                        <a class="btn btn-sm btn-default" @click="clickDeleteItemRefund(index)">
                                            <i class="fas fa-trash fa-wf"></i>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="h-25 bg-light" style="overflow-y: auto">
                    <div class="row border-top bg-light m-0 p-0 h-50 d-flex align-items-right pr-3 pt-2">

                        <div class="col-md-12" style="display: flex; flex-direction: column; align-items: flex-end;">
                            <table>
                                <tr class="font-weight-semibold  m-0" v-if="form.sale > 0">
                                    <td class="font-weight-semibold">SUBTOTAL</td>
                                    <td class="font-weight-semibold">:</td>
                                    <td class="text-right text-blue">{{currency.symbol}} {{ getFormatDecimal(form.sale) }}</td>
                                </tr>
                                <tr class="font-weight-semibold  m-0" v-if="form.total_discount > 0">
                                    <td class="font-weight-semibold">TOTAL DESCUENTO (-)</td>
                                    <td class="font-weight-semibold">:</td>
                                    <td class="text-right text-blue">{{currency.symbol}} {{ getFormatDecimal(form.total_discount) }}</td>
                                </tr>
                                <template v-for="(tax, index) in form.taxes">
                                    <tr v-if="((tax.total > 0) && (!tax.is_retention))" :key="index" class="font-weight-semibold  m-0">
                                        <td class="font-weight-semibold">
                                            {{tax.name}}[+]
                                        </td>
                                        <td class="font-weight-semibold">:</td>
                                        <td class="text-right text-blue">{{currency.symbol}} {{ getFormatDecimal(tax.total) }}</td>
                                    </tr>
                                </template>
                                <tr class="font-weight-semibold  m-0" v-if="form.subtotal > 0">
                                    <td class="font-weight-semibold">TOTAL VENTA</td>
                                    <td class="font-weight-semibold">:</td>
                                    <td class="text-right text-blue">{{currency.symbol}} {{ getFormatDecimal(form.subtotal) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="row text-white m-0 p-0 h-50 d-flex align-items-center" @click="clickPayment" v-bind:class="[form.total > 0 ? 'bg-info pointer' : 'bg-dark']">
                        <div class="col-6 text-center h5">
                            <i class="fa fa-chevron-circle-right"></i>
                            <span class="font-weight-semibold">PAGO</span>
                        </div>
                        <div class="col-6 text-center">
                            <h5 class="font-weight-semibold h5">{{currency.symbol}} {{ getFormatDecimal(form.total) }}</h5>
                        </div>
                    </div>
                </div>
            </div>

            <person-form :showDialog.sync="showDialogNewPerson" type="customers" :input_person="input_person" :external="true" :document_type_id="form.document_type_id"></person-form>

            <item-form :showDialog.sync="showDialogNewItem" :external="true"></item-form>
        </div>
        <template v-else>
            <payment-form :is_payment.sync="is_payment" :form="form" :items_refund="items_refund" :currency-type-id-active="form.currency_id" :currency-type-active="currency" :exchange-rate-sale="form.exchange_rate_sale" :customer="customer" :soapCompany="soapCompany"></payment-form>
        </template>

        <history-sales-form :showDialog.sync="showDialogHistorySales" :item_id="history_item_id" :customer_id="form.customer_id"></history-sales-form>

        <history-purchases-form :showDialog.sync="showDialogHistoryPurchases" :item_id="history_item_id"></history-purchases-form>

        <warehouses-detail :showDialog.sync="showWarehousesDetail" :warehouses="warehousesDetail" :unit_type="unittypeDetail"></warehouses-detail>
    </div>
    <div v-else>
        <div class="text-center">
            <br>
            <br>
            <br>
            <br>
            <br>
            <i class="fas fa-chevron-circle-right fa fw h5"></i>
            <span class="font-weight-semibold h5">USUARIO NO VALIDO PARA ESTA CAJA</span>
            <i class="fas fa-chevron-circle-left fa fw h5"></i>
        </div>
    </div>
    <div class="modal fade" id="modal_order" tabindex="-1" role="dialog"
        data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Información Cuenta {{ selected_table }}</h4>
                    <button type="button" class="btn btn-danger" data-dismiss="modal" aria-label="Cerrar">X</button>
                </div>
                <div class="modal-body d-flex flex-column align-items-center">
                    <div class="d-flex flex-column" style="width: 220px;">
                        <button class="btn btn-custom btn-lg mb-3" type="button" @click="abrirModalCategorias(selected_table, dbId)">
                            <i class="fa fa-plus"></i> Agregar
                        </button>

                        <button class="btn btn-custom btn-lg mb-3" @click="abrirModalCuenta(selected_table, dbId)" id="btnModalCuenta">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                <i class="fa fa-list"></i> Ver
                        </button>

                        <button class="btn btn-custom btn-lg mb-3" type="button" @click="abrirModalTraslado(selected_table, dbId)">
                            <i class="fa fa-expand-arrows-alt"></i> Trasladar
                        </button>

                        <button class="btn btn-custom btn-lg mb-3" type="button" @click="abrirModalFactura(selected_table, dbId)">
                            <i class="fa fa-receipt"></i> Resumen
                        </button>
                        <button class="btn btn-custom btn-lg mb-3" type="button" @click="eliminarCuenta(dbId)" id="btnEliminarCuenta">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                <i class="fa fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
                <button id="closeModalBtn" type="button" data-dismiss="modal" style="display: none;"></button>
            </div>
        </div>
    </div>

    <!-- Modal de CATEGORÍAS -->
    <div class="modal fade" id="modal_cuenta_categorias" tabindex="-1" role="dialog" data-backdrop="static"
        data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Categorías</h4>
                    <button type="button" class="btn btn-danger" data-dismiss="modal" aria-label="Cerrar">X</button>
                </div>
                <div class="modal-body modal-body-scrollable">
                    <div v-if="categories.length === 0" class="text-center">
                        <p>No hay categorías disponibles</p>
                    </div>
                    <div v-else class="row p-2">
                        <div v-for="(item, index) in categories" class="col-6 col-md-4 col-lg-3 mb-2" :key="index">
                            <button
                                class="btn btn-custom btn-lg w-100"
                                type="button"
                                :style="{ backgroundColor: item.color, color: 'white', fontWeight: 'bold', fontSize: '16px' }"
                                @click="abrirModalProductos(item.id, selected_table)">
                                {{ item.name }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Productos -->
    <div class="modal fade" id="modal_cuenta_productos" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    Productos
                    <button title="Carrito" class="btn btn-custom btn-sm" @click="verCarrito(selected_table)" id="btnVerCarrito">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                        <i class="fa fa-shopping-cart"></i>
                    </button>
                </h4>
                <button type="button" class="btn btn-danger" data-dismiss="modal" aria-label="Cerrar">X</button>
            </div>

            <div class="modal-body modal-body-scrollable">
                <div v-if="loadingModalProductos" class="text-center">
                    <i class="fa fa-spinner fa-spin"></i> Cargando...
                </div>
                <div v-else>
                    <input
                        type="text"
                        v-model="searchQueryProductos"
                        class="form-control mb-2"
                        placeholder="Buscar productos..."
                    >
                    <div v-if="filteredItemsProductos.length > 0" class="mt-3">
                        <div class="row">
                            <div v-for="item in filteredItemsProductos" :key="item.id" class="col-6 col-md-4 col-lg-3 mb-2">
                                <div class="card-body pointer px-1 pt-1"
                                     @click="abrirModalAgregar(item, selected_table)"
                                     style="cursor: pointer;">
                                    <img :src="item.image_url" class="img-thumbnail img-custom" style="width: 80%;">
                                    <div class="card-body p-1">
                                        <p class="product-name">{{ item.name }}</p>
                                        <p class="product-price">${{ item.sale_unit_price_with_tax }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="mt-3">
                        <h5>No hay productos disponibles.</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


    <!-- Modal: Agregar Cantidad -->
    <div class="modal fade" id="modal_agregar_producto" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Producto</h5>
                    <button type="button" class="btn btn-danger" data-dismiss="modal" aria-label="Cerrar">X</button>
                </div>
                <div class="modal-body text-center modal-body-scrollable">
                    <p><strong>{{ selectedProduct ? selectedProduct.name : '' }}</strong></p>

                    <div class="d-flex justify-content-center mb-3">
                        <div class="input-group" style="width: 140px;">
                            <button class="btn btn-outline-secondary" type="button" @click="decrementQuantity">−</button>
                                <input
                                    type="number"
                                    class="form-control text-center no-spinner"
                                    :value="selectedQuantity"
                                    min = 1
                                />
                            <button class="btn btn-outline-secondary" type="button" @click="incrementQuantity">+</button>
                        </div>
                    </div>
                    <button class="btn btn-primary w-100" @click="agregarProducto(selected_table)" id="btnAgregarProducto">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                        Agregar
                    </button>
                    <button id="closeModalBtnAgregar" type="button" data-dismiss="modal" style="display: none;"></button>
                </div>
            </div>
        </div>
    </div>

     <!-- Modal: Traslado -->
    <div class="modal fade" id="modal_traslado" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Traslado - Cuenta {{ mesaSeleccionada }}</h5>
                    <button type="button" class="btn btn-danger" data-dismiss="modal" aria-label="Cerrar">X</button>
                </div>
                <div class="modal-body text-center modal-body-scrollable">
                     <div class="d-flex justify-content-center mb-3">
                        <input
                            type="number"
                            class="form-control text-center"
                            placeholder="Nueva Cuenta"
                            v-model="selectedTableChange"
                            min="1"
                            style="width: 150px;"
                        />
                    </div>
                     <button title="Carrito" class="btn btn-primary w-100" @click="trasladarMesa(mesaSeleccionada, dbId)" id="btnTrasladarCuenta">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                        Trasladar
                    </button>
                    <button id="closeModalBtnTraslado" type="button" data-dismiss="modal" style="display: none;"></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Cuenta -->
    <div class="modal fade" id="modal_cuenta" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Productos en la Cuenta {{ selected_table }}</h5>
                    <button type="button" class="btn btn-danger" data-dismiss="modal" aria-label="Cerrar">X</button>
                </div>
                <div class="modal-body modal-body-scrollable">
                    <table class="table table-bordered table-sm text-center">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cant</th>
                                <th>Precio</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(producto, index) in productosCuenta" :key="producto.id">
                                <td>{{ producto.item_description }}</td>
                                <td>{{ producto.quantity }}</td>
                                <td>{{ formatearPrecio(producto.price) }}</td>
                                <td>
                                    <template v-if="producto.state !== 'R'">
                                        <button type="button" style="margin-bottom: 5px;" title="Eliminar Producto" class="btn btn-sm btn-danger" @click="eliminarProducto(producto.id, dbId)" id="btnEliminarProducto">
                                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                            <i class="fa fa-trash"></i>
                                        </button>
                                        <button v-if="type !== 'comand'" type="button" style="margin-bottom: 5px;" title="Facturar Producto" class="btn btn-sm btn-success" @click="clickAddItemAccount(producto.item_id, index, producto.quantity, producto.id)" id="btnAddItemAccount">
                                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-danger" @click="eliminarCuenta(dbId)" id="btnEliminarCuenta">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                            Borrar Cuenta
                    </button>
                    <button v-if="type !== 'comand'" class="btn btn-success"  @click="agregarCuentaCaja(dbId)" id="btnFacturarCuenta">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                            Facturar Cuenta
                    </button>
                </div>
                <button id="closeModalBtnCuenta" type="button" data-dismiss="modal" style="display: none;"></button>
            </div>
        </div>
    </div>

    <!-- Modal: Carrito de compra -->
    <div class="modal fade" id="modal_carrito_compra" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Carrito Cuenta {{ selected_table }}</h5>
                     <button type="button" class="btn btn-danger" data-dismiss="modal" aria-label="Cerrar">X</button>
                </div>
                <div class="modal-body modal-body-scrollable">
                    <table class="table table-bordered table-sm text-center">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(producto, index) in productosCuenta" :key="index">
                                <td>{{ producto.item_description }}</td>
                                <td>{{ producto.quantity }}</td>
                                <td>{{ formatearPrecio(producto.price) }}</td>
                                <td>
                                    <button title="Eliminar Producto" class="btn btn-sm btn-danger" @click="eliminarProductoCarrito(producto.id, dbId)" id="btnEliminarProductoCarrito">
                                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button title="Eliminar Producto" class="btn btn-danger" @click="eliminarCuentaCarrito(dbId)" id="btnEliminarCuentaCarrito">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                        Vaciar Carrito
                    </button>
                </div>
                <button id="closeModalBtnCuentaCarrito" type="button" data-dismiss="modal" style="display: none;"></button>
            </div>
        </div>
    </div>

    <!-- Modal: Factura Pdf -->
    <div class="modal fade" id="modal_pdf_cuenta" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de la Cuenta</h5>
                    <button type="button" class="btn btn-danger" data-dismiss="modal" aria-label="Cerrar">X</button>
                </div>
                <div class="modal-body">
                    <div v-if="pdfUrl">
                        <iframe
                            :src="pdfUrl"
                            width="100%"
                            height="600px"
                            style="border: none;"
                        ></iframe>
                    </div>
                    <div v-else class="text-center">
                        <p>Cargando documento...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<style>
.testimonial-group>.row {
    overflow-x: auto;
    white-space: nowrap;
    overflow-y: hidden;
}

.testimonial-group>.row>.col-sm-3 {
    display: inline-block;
    float: none;
}

/* Decorations */
.col-sm-3 {
    height: 70px;
    margin-right: 0.5%;
    color: white;
    font-size: 18px;
    padding-bottom: 20px;
    padding-top: 18px;
    font-weight: bold
}

.card-block {
    min-height: 220px;
}

.ex1 {
    overflow-x: scroll;
}

.cat_c {
    width: 100px;
    margin: 1%;
    padding: 3px;
    font-weight: bold;
    color: white;
    min-height: 90px;
}

.cat_c p {
    color: white;
}

.c-width {
    width: 80px !important;
    padding: 0 !important;
    margin-right: 0 !important;
}

.el-select-dropdown {
    max-width: 80% !important;
    margin-right: 1% !important;
}

.el-input-group__append {
    padding: 0 10px !important;
}

.input-text-right {
    text-align: right;
}

.truncate-text {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.mesa-btn {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background-color: #67C23A;
  border: 2px solid #67C23A;
  color: #ffffff;
  font-size: 14px;
  font-weight: bold;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
  transition: all 0.3s;
  gap: 2px;
  margin: 8px;
  padding: 5px;
}

.mesa-btn i {
  font-size: 20px;
  line-height: 1;
}

.mesa-btn .texto-boton {
  font-size: 18px;
  font-weight: bold;
  line-height: 1.5;
  text-align: center;
}

.mesa-activa {
  background-color: #ff006c !important;
  border-color: #ff006c;
  color: #ffffff;
}

.modal-body{
    text-align: center;
}

.product-card {
    width: 100%;
    min-height: 200px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    padding: 10px;
}

.product-img {
    width: 80px;
    height: 80px;
    object-fit: contain;
    margin-bottom: 5px;
}

.product-name {
    font-size: 11px;
    font-weight: bold;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    max-width: 90%;
    text-align: center;
}

.product-price {
    font-size: 12px;
    color: #007bff;
    margin: 0;
}

.no-spinner::-webkit-outer-spin-button,
.no-spinner::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.no-spinner {
  -moz-appearance: textfield;
}

.modal-body-scrollable {
    max-height: 70vh;
    overflow-y: auto;
}
</style>

<script>
// import { calculateRowItem } from "../../../helpers/functions";
import PaymentForm from "./partials/payment.vue";
import ItemForm from "./partials/form.vue";
import HistorySalesForm from "../../../../../modules/Pos/Resources/assets/js/views/history/sales.vue";
import HistoryPurchasesForm from "../../../../../modules/Pos/Resources/assets/js/views/history/purchases.vue";
import PersonForm from "../persons/form.vue";
import WarehousesDetail from '../items/partials/warehouses.vue'
import queryString from "query-string";
import ExpenseForm from '../../../../../modules/Expense/Resources/assets/js/views/expenses/form.vue';
import {functions} from '@mixins/functions'
import { Modal } from 'bootstrap';

export default {
    props: ['configuration', 'soapCompany', 'tables_quantity', 'cuentas', 'type'],
    components: {
        PaymentForm,
        ItemForm,
        HistorySalesForm,
        HistoryPurchasesForm,
        PersonForm,
        WarehousesDetail,
        ExpenseForm,
    },
    mixins: [functions],
    data() {
        return {
            place: 'cat',
            history_item_id: null,
            search_item_by_barcode: false,
            warehousesDetail: [],
            unittypeDetail: [],
            input_person: {},
            contenidoCambiado: false,
            botones: [],
            mesaActiva: [],
            selected_table: null,
            dbId: null,
            category_selected_productos: null,
            showDialogHistoryPurchases: false,
            showDialogHistorySales: false,
            showDialogNewPerson: false,
            showDialogNewItem: false,
            loading: false,
            loadingModalProductos: false,
            is_payment: false, //aq
            // is_payment: true,//aq
            showWarehousesDetail: false,
            resource: "pos",
            recordId: null,
            input_item: "",
            items: [],
            all_items: [],
            customers: [],
            currencies: [],
            taxes: [],
            all_customers: [],
            establishment: null,
            currency: {},
            form_item: {},
            customer: {},
            row: {},
            user: {},
            form: {},
            categories: [],
            colors: ['#1cb973', '#bf7ae6', '#fc6304', '#9b4db4', '#77c1f3'],
            type_refund: false,
            items_refund: [],
            pagination: {},
            category_selected: "",
            plate_number_valid: true,
            electronic: false,
            selectedProduct: null,
            selectedQuantity: 1,
            searchQueryProductos: '',
            mesaSeleccionada: null,
            selectedTableChange: null,
            productosCuenta: [],
            productosSeleccionados: [],
            pdfUrl: null,
            vistaCambiada: false,
            showExpenseFormModal: false,
        };
    },

    mounted(){
    },

    async created() {
        this.electronic = this.configuration.configuration_pos.electronic
//        console.log(localStorage.getItem("plate_number"))
//        console.log(this.configuration.configuration_pos.plate_number)
//        console.log(this.electronic)
        if(localStorage.getItem("plate_number") == this.configuration.configuration_pos.plate_number || this.electronic == false || this.configuration.configuration_pos.type_resolution == 'Factura Electronica de Venta'){
            this.plate_number_valid = true
            await this.initForm();
            await this.getTables();
            this.events();
            await this.getFormPosLocalStorage()
            // await this.initCurrencyType()
            this.customer = await this.getLocalStorageIndex('customer')
            // if (document.querySelector('.sidebar-toggle')) {
            //     document.querySelector('.sidebar-toggle').click()
            // }
        }
        else
            this.plate_number_valid = false
    },

    computed: {
        classObjectCol() {

            let cols = this.configuration.colums_grid_item

            let clase = 'c3'
            switch (cols) {
                case 2:
                    clase = '6'

                    break;
                case 3:
                    clase = '4'

                    break;
                case 4:
                    clase = '3'

                    break;
                case 5:
                    clase = '2'

                    break;
                case 6:
                    clase = '2'
                    break;
                default:

            }
            return {
                [`col-md-${clase}`]: true
            }
        },
        filteredItemsProductos() {
        if (!this.searchQueryProductos) {
            return this.items;
        }
        return this.items.filter(item =>
            item.name.toLowerCase().includes(this.searchQueryProductos.toLowerCase())
        );
    }
    },
    methods: {
        handleCloseExpenseForm() {
            this.showExpenseFormModal = false;
            // Cualquier otra acción que necesites realizar al cerrar el modal
        },
        handleExpenseAdded(expenseData) {
          //  console.log('Gasto añadido:', expenseData);
            // Realizar acciones después de añadir un gasto, como actualizar una lista de gastos.
        },
        getQueryParameters() {
            return queryString.stringify({
                page: this.pagination.current_page
                    ? this.pagination.current_page
                    : 1,
                input_item: this.input_item,
                cat: this.category_selected,
                limit: this.limit
            });
        },
        decrementQuantity() {
            if (this.selectedQuantity > 1) {
                this.selectedQuantity--;
            }
        },
        incrementQuantity() {
            this.selectedQuantity++;
        },
        abrirModalFactura(selected_table, dbId) {
            document.body.style.cursor = 'wait';
            const mesaId = dbId;
            const establecimiento = this.establishment.id;
            const customerId = this.form.customer_id;

            const timestamp = new Date().getTime();

            this.pdfUrl = `/${this.resource}/record_detalle?mesaId=${mesaId}&establecimiento=${establecimiento}&customer=${customerId}&_=${timestamp}`;

            const modalElement = document.getElementById('modal_pdf_cuenta');
            const modal = new Modal(modalElement);
            modal.show();
            document.body.style.cursor = 'default';
        },
        cambiarContenido() {
            if (this.vistaCambiada || this.tables_quantity <= 0) return;

                this.botones = Array.from({ length: this.tables_quantity }, (_, i) => {
                const mesaId = i + 1;
                const cuenta = this.cuentas.find(c => c.table_number === mesaId);

                return {
                    id: mesaId,
                    db_id: cuenta ? cuenta.id : null,
                    state: cuenta ? cuenta.state : 0,
                };
            });

            this.vistaCambiada = true;
        },
        abrirModal(idBd,index) {
          this.selected_table = index;
          this.dbId = idBd;
          const modalElement = document.getElementById('modal_order');
          const modal = new Modal(modalElement);
          modal.show();
        },
        abrirModalCategorias(selected_table, idBd) {
            this.selected_table = selected_table;
            this.dbId = idBd;
            const modalElement = document.getElementById('modal_cuenta_categorias');
            const modal = new Modal(modalElement);
            modal.show();
        },
        abrirModalTraslado(table, idBd) {
            this.mesaSeleccionada = table;
            this.dbId = idBd;
            const modalElement = document.getElementById('modal_traslado');
            const modal = new Modal(modalElement);
            modal.show();
        },
        abrirModalCuenta(selected_table, dbId) {
            const btn = document.getElementById('btnModalCuenta');
            const spinner = btn.querySelector('.spinner-border');

            btn.disabled = true;
            spinner.classList.remove('d-none');
            let establishment = this.establishment.id;
            this.$http.get(`/${this.resource}/account_list`, {
                params: {
                    mesa: selected_table,
                    mesaId: dbId,
                    establecimiento: establishment
                }
            })
            .then(response => {
                const productos = response.data.data;

                this.productosCuenta = productos;
                this.productosSeleccionados = [];
                this.selected_table = selected_table;
                this.dbId = dbId;
                const modal = new Modal(document.getElementById('modal_cuenta'));
                modal.show();
                btn.disabled = false;
                spinner.classList.add('d-none');
            })
            .catch(error => {
                const errorMsg = error.response?.data?.message || 'Ocurrió un error al trasladar la cuenta.';
                this.$message.error(errorMsg, 3);
                btn.disabled = false;
                spinner.classList.add('d-none');
             })
            .finally(() => {
                btn.disabled = false;
                spinner.classList.add('d-none');
            });
        },
        abrirModalProductos(idCategoria, selected_table) {
            this.getRecords2(idCategoria);
            this.selected_table = selected_table;
            const modalElement = document.getElementById('modal_cuenta_productos');
            const modal = new Modal(modalElement);
            modal.show();
        },
        abrirModalAgregar(producto, selected_table) {
            this.selected_table = selected_table;
            this.selectedProduct = producto;
            this.selectedQuantity = 1;
            const modal = new Modal(document.getElementById('modal_agregar_producto'));
            modal.show();
        },
        agregarProducto(selected_table) {
            const btn = document.getElementById('btnAgregarProducto');
            const spinner = btn.querySelector('.spinner-border');

            btn.disabled = true;
            spinner.classList.remove('d-none');
            let establishment = this.establishment.id;

            const payload = {
                id: this.selectedProduct.item_id,
                nombre: this.selectedProduct.description,
                precio: this.selectedProduct.sale_unit_price_with_tax,
                cantidad: this.selectedQuantity,
                mesa: selected_table,
                establecimiento: establishment
            };

            return this.$http
                .post(`/${this.resource}/account`, payload)
                .then(response => {
                    const mesaIndex = this.botones.findIndex(b => b.id === selected_table);
                    if (mesaIndex !== -1) {
                        this.botones[mesaIndex].state = 1;
                    }
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                    document.getElementById('closeModalBtnAgregar').click();
                    this.$message.success(response.data.message, 3)
                })
                .catch(error => {
                    console.error('Error al agregar producto:', error);
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                })
                .finally(() => {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                });
        },
        trasladarMesa(mesa, dbId) {
            const btn = document.getElementById('btnTrasladarCuenta');
            const spinner = btn.querySelector('.spinner-border');

            btn.disabled = true;
            spinner.classList.remove('d-none');
            let establishment = this.establishment.id;

            const payload = {
                mesa_nueva: this.selectedTableChange,
                mesa: mesa,
                mesa_id : dbId,
                establecimiento: establishment
            };

            return this.$http
                .post(`/${this.resource}/transfer`, payload)
                .then(response => {
                    const mesaIndexAnterior = this.botones.findIndex(b => b.id === payload.mesa);
                    const mesaIndexNueva = this.botones.findIndex(b => b.id === Number(payload.mesa_nueva));

                    if (mesaIndexAnterior !== -1) {
                      this.botones[mesaIndexAnterior].state = 0;
                    }

                    if (mesaIndexNueva !== -1) {
                        this.botones[mesaIndexNueva].state = 1;
                    }
                    this.$message.success(response.data.message, 3)
                    document.getElementById('closeModalBtnTraslado').click();
                    document.getElementById('closeModalBtn').click();
                })
                .catch(error => {
                    const errorMsg = error.response?.data?.message || 'Ocurrió un error al trasladar la cuenta.';
                    this.$message.error(errorMsg, 3);
                })
                .finally(() => {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                });
        },
        eliminarProducto(id, dbId){
            const btn = document.getElementById('btnEliminarProducto');
            const spinner = btn.querySelector('.spinner-border');

            btn.disabled = true;
            spinner.classList.remove('d-none');
            let establishment = this.establishment.id;

            const payload = {
                cuenta_id: id,
                mesa_id : dbId,
                establecimiento: establishment
            };

            return this.$http
                .post(`/${this.resource}/delete_product`, payload)
                .then(response => {
                    const mesaIndexAnterior = this.botones.findIndex(b => b.id === payload.mesa);
                    const mesaIndexNueva = this.botones.findIndex(b => b.id === Number(payload.mesa_nueva));

                    if (mesaIndexAnterior !== -1) {
                      this.botones[mesaIndexAnterior].state = 0;
                    }

                    if (mesaIndexNueva !== -1) {
                        this.botones[mesaIndexNueva].state = 1;
                    }
                    this.$message.success(response.data.message, 3)
                    document.getElementById('closeModalBtnCuenta').click();
                })
                .catch(error => {
                    const errorMsg = error.response?.data?.message || 'Ocurrió un error al eliminar el producto.';
                    this.$message.error(errorMsg, 3);
                })
                .finally(() => {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                });
        },
        eliminarProductoCarrito(id, dbId){
            const btn = document.getElementById('btnEliminarProductoCarrito');
            const spinner = btn.querySelector('.spinner-border');

            btn.disabled = true;
            spinner.classList.remove('d-none');
            let establishment = this.establishment.id;

            const payload = {
                cuenta_id: id,
                mesa_id : dbId,
                establecimiento: establishment
            };

            return this.$http
                .post(`/${this.resource}/delete_product`, payload)
                .then(response => {
                    const mesaIndexAnterior = this.botones.findIndex(b => b.id === payload.mesa);
                    const mesaIndexNueva = this.botones.findIndex(b => b.id === Number(payload.mesa_nueva));

                    if (mesaIndexAnterior !== -1) {
                      this.botones[mesaIndexAnterior].state = 0;
                    }

                    if (mesaIndexNueva !== -1) {
                        this.botones[mesaIndexNueva].state = 1;
                    }
                    this.$message.success(response.data.message, 3)
                    document.getElementById('closeModalBtnCuentaCarrito').click();
                })
                .catch(error => {
                    const errorMsg = error.response?.data?.message || 'Ocurrió un error al eliminar el producto.';
                    this.$message.error(errorMsg, 3);
                })
                .finally(() => {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                });
        },
        eliminarCuenta(dbId){
            const btn = document.getElementById('btnEliminarCuenta');
            const spinner = btn.querySelector('.spinner-border');

            btn.disabled = true;
            spinner.classList.remove('d-none');
            let establishment = this.establishment.id;

            const payload = {
                mesa_id : dbId,
                establecimiento: establishment
            };

            return this.$http
                .post(`/${this.resource}/delete_account`, payload)
                .then(response => {
                    const mesaIndexAnterior = this.botones.findIndex(b => b.db_id === payload.mesa_id);

                    if (mesaIndexAnterior !== -1) {
                      this.botones[mesaIndexAnterior].state = 0;
                    }

                    this.$message.success(response.data.message, 3)
                    document.getElementById('closeModalBtnCuenta').click();
                    document.getElementById('closeModalBtn').click();
                })
                .catch(error => {
                    const errorMsg = error.response?.data?.message || 'Ocurrió un error al eliminar la cuenta.';
                    this.$message.error(errorMsg, 3);
                })
                .finally(() => {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                });
        },
        eliminarCuentaCarrito(dbId){
            const btn = document.getElementById('btnEliminarCuentaCarrito');
            const spinner = btn.querySelector('.spinner-border');

            btn.disabled = true;
            spinner.classList.remove('d-none');
            let establishment = this.establishment.id;

            const payload = {
                mesa_id : dbId,
                establecimiento: establishment
            };

            return this.$http
                .post(`/${this.resource}/delete_account`, payload)
                .then(response => {
                    const mesaIndexAnterior = this.botones.findIndex(b => b.db_id === payload.mesa_id);

                    if (mesaIndexAnterior !== -1) {
                      this.botones[mesaIndexAnterior].state = 0;
                    }

                    this.$message.success(response.data.message, 3)
                    document.getElementById('closeModalBtnCuentaCarrito').click();
                })
                .catch(error => {
                    const errorMsg = error.response?.data?.message || 'Ocurrió un error al eliminar la cuenta.';
                    this.$message.error(errorMsg, 3);
                })
                .finally(() => {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                });
        },
        async agregarCuentaCaja(dbId) {
           if (!this.productosCuenta || this.productosCuenta.length === 0) {
                return this.$message.warning('No hay productos en la cuenta para facturar.');
            }

            const btn = document.getElementById('btnFacturarCuenta');
            const spinner = btn.querySelector('.spinner-border');

            btn.disabled = true;
            spinner.classList.remove('d-none');

            for (let i = 0; i < this.productosCuenta.length; i++) {
                const producto = this.productosCuenta[i];

                if (producto.state === 'R') continue;

                await this.clickAddItemAccount2(
                    producto.item_id,
                    i,
                    producto.quantity,
                    producto.id,
                    true
                );
            }
                btn.disabled = false;
                spinner.classList.add('d-none');

            this.$notify({
                title: '',
                message: 'Cuenta facturada con éxito.',
                type: 'success',
                duration: 1000
            });

            document.getElementById('closeModalBtnCuenta').click();
        },
        formatearPrecio(precio) {
            return Number(precio).toLocaleString('es-CO', { style: 'currency', currency: 'COP' });
        },
        verCarrito(selected_table){
            const btn = document.getElementById('btnVerCarrito');
            const spinner = btn.querySelector('.spinner-border');

            btn.disabled = true;
            spinner.classList.remove('d-none');
            let establishment = this.establishment.id;
            this.$http.get(`/${this.resource}/shopping_car`, {
                params: {
                    mesa: selected_table,
                    establecimiento: establishment
                }
            })
            .then(response => {
                const productos = response.data.data;

                this.productosCuenta = productos;
                this.productosSeleccionados = [];
                this.selected_table = selected_table;
                const modal = new Modal(document.getElementById('modal_carrito_compra'));
                modal.show();
                btn.disabled = false;
                spinner.classList.add('d-none');
            })
            .catch(error => {
                const errorMsg = error.response?.data?.message || 'Ocurrió un error al mostrar el carrito.';
                this.$message.error(errorMsg, 3);
                btn.disabled = false;
                spinner.classList.add('d-none');
             })
            .finally(() => {
                btn.disabled = false;
                spinner.classList.add('d-none');
            });
        },
        getRecords() {
            this.loading = true;
//            console.log(`/${this.resource}/search_items?${this.getQueryParameters()}&cat=${this.category_selected}`)
            return this.$http
                .get(
                    `/${this.resource}/search_items?${this.getQueryParameters()}&cat=${
                        this.category_selected
                    }`
                )
                .then(response => {
                    this.all_items = response.data.data;
                    this.items = response.data.data;
//                    // Convertir sale_unit_price a string en cada item para evitar truncamiento
//                    this.all_items = response.data.data.map(item => ({
//                        ...item,
//                        sale_unit_price: parseFloat(item.sale_unit_price).toFixed(6) // Mantiene 6 decimales
//                    }));
//                    this.items = [...this.all_items]; // Copia con la conversión aplicada
//                    console.log(this.items)
                    this.filterItems();
                    this.pagination = response.data.meta;
                    this.pagination.per_page = parseInt(
                        response.data.meta.per_page
                    );
                    this.loading = false;
                    if (response.data.meta.total > 0) {
                        this.pagination.total = response.data.meta.total;
                    } else {
                        this.pagination.total = 0;
                    }
                });
        },
        getRecords2(id) {
            this.category_selected = id;

            if (id === undefined || id === null) {
                id = null; // Opcional, solo para mantener claridad
             }

            this.loadingModalProductos = true;

            let url = `/${this.resource}/search_items?${this.getQueryParameters()}`;

            if (id !== null) {
                url += `&cat=${id}`;
            }

            return this.$http.get(url)
                .then(response => {
                    this.all_items = response.data.data;
                    this.items = response.data.data;
                    this.filterItems();
                    this.pagination = response.data.meta;
                    this.pagination.per_page = parseInt(response.data.meta.per_page);
                    this.loadingModalProductos = false;
                    this.pagination.total = response.data.meta.total || 0;
                })
                .catch(error => {
                    console.error("Error cargando productos:", error);
                    this.loadingModalProductos = false;
                });
        },
        setDefaultImage(event) {
            event.target.src = 'http://pc.facturadorpro.test/logo/imagen-no-disponible.jpg';
        },
        setListPriceItem(item_unit_type, index) {

            let list_price = 0

            switch (item_unit_type.price_default) {
                case 1:
                    list_price = item_unit_type.price1
                    break
                case 2:
                    list_price = item_unit_type.price2
                    break
                case 3:
                    list_price = item_unit_type.price3
                    break
            }

            this.items[index].sale_unit_price = parseFloat(list_price)
            this.items[index].unit_type_id = item_unit_type.unit_type_id
            this.items[index].unit_type = item_unit_type.unit_type
            this.items[index].presentation = item_unit_type

            this.$message.success("Precio seleccionado")
        },
        filterCategorie(id, mod = false) {

            if (id) {
                this.category_selected = id;
                this.getRecords();
            } else {
                this.category_selected = "";
                this.getRecords();
            }

            if (mod) {
                this.place = 'cat2'
            } else {
                this.place = 'prod'
            }

        },
        getColor(i) {
            return this.colors[(i % this.colors.length)]
        },
        initCurrencyType() {
            this.currency = _.find(this.currencies, {
                'id': this.form.currency_id
            })
        },
        getFormPosLocalStorage() {
            let form_pos = localStorage.getItem('form_pos');
            form_pos = JSON.parse(form_pos)
            if (form_pos) {
                this.form = form_pos
                // this.calculateTotal()
            }

            if (!this.form.customer_id) {
                const customer_default = _.find(this.all_customers, {'number': '222222222222'}) ?? null
                if (customer_default) {
                    this.form.customer_id = customer_default.id
                    this.changeCustomer()
                }
            }

        },
        setFormPosLocalStorage(form_param = null) {

            if (form_param) {

                localStorage.setItem('form_pos', JSON.stringify(form_param));

            } else {

                localStorage.setItem('form_pos', JSON.stringify(this.form));
            }

        },
        cancelFormPosLocalStorage() {

            localStorage.setItem('form_pos', JSON.stringify(null));
            this.setLocalStorageIndex('customer', null)

        },
        clickOpenInputEditUP(index) {
            this.items[index].edit_unit_price = true
        },

        clickEditUnitPriceItem(index) {
            // console.log(index)
            let price_with_tax = this.items[index].edit_sale_unit_price //price with tax
            this.items[index].sale_unit_price_with_tax = price_with_tax
            this.items[index].sale_unit_price = price_with_tax / (1 + (this.items[index].tax.rate / this.items[index].tax.conversion))
//            console.log(this.items[index].sale_unit_price)
            this.items[index].edit_unit_price = false
            // console.log(item_search)
        },

        clickCancelUnitPriceItem(index) {
            // console.log(index)
            this.items[index].edit_unit_price = false

        },
        clickWarehouseDetail(item) {
            this.unittypeDetail = item.unit_type
            this.warehousesDetail = item.warehouses
            this.showWarehousesDetail = true
        },
        clickHistoryPurchases(item_id) {

            this.history_item_id = item_id
            this.showDialogHistoryPurchases = true
            // console.log(item)
        },
        clickHistorySales(item_id) {
            if (!this.form.customer_id)
                return this.$message.error("Debe seleccionar el cliente")

            this.history_item_id = item_id
            this.showDialogHistorySales = true
            // console.log(item)
        },
        keyupEnterCustomer() {

            if (this.input_person.number) {

                if (!isNaN(parseInt(this.input_person.number))) {

                    switch (this.input_person.number.length) {
                        case 8:
                            this.input_person.identity_document_type_id = '1'
                            this.showDialogNewPerson = true
                            break;

                        case 11:
                            this.input_person.identity_document_type_id = '6'
                            this.showDialogNewPerson = true
                            break;
                        default:
                            this.input_person.identity_document_type_id = '6'
                            this.showDialogNewPerson = true
                            break;
                    }
                }
            }
        },
        keyupCustomer(e) {

            if (e.key !== "Enter") {

                this.input_person.number = this.$refs.select_person.$el.getElementsByTagName('input')[0].value
                let exist_persons = this.all_customers.filter((customer) => {
                    let pos = customer.description.search(this.input_person.number);
                    return (pos > -1)
                })

                this.input_person.number = (exist_persons.length == 0) ? this.input_person.number : null

            }

        },
        calculateQuantity(index) {
            // console.log(this.form.items[index])
            if (this.form.items[index].item.calculate_quantity) {
                let quantity = _.round(
                    parseFloat(this.form.items[index].total) /
                    parseFloat(this.form.items[index].unit_price),
                    4
                );

                if (quantity) {
                    this.form.items[index].quantity = quantity;
                    this.form.items[index].item.aux_quantity = quantity;
                } else {
                    this.form.items[index].quantity = 0;
                    this.form.items[index].item.aux_quantity = 0;
                }
            }

        },

        changeCustomer() {
            let customer = _.find(this.all_customers, {
                id: this.form.customer_id
            });
            this.customer = customer;
            this.form.document_type_id = '90';
            this.setLocalStorageIndex('customer', this.customer)
            this.setFormPosLocalStorage()
        },

        getLocalStorageIndex(key, re_default = null) {

            let ls_obj = localStorage.getItem(key);
            ls_obj = JSON.parse(ls_obj)

            if (ls_obj) {
                return ls_obj
            }

            return re_default
        },
        setLocalStorageIndex(key, obj) {
            localStorage.setItem(key, JSON.stringify(obj));
        },
        async events() {

            await this.$eventHub.$on('initInputPerson', () => {
                this.initInputPerson()
            })

            await this.$eventHub.$on('eventSetFormPosLocalStorage', (form_param) => {
                this.setFormPosLocalStorage(form_param)
            })

            await this.$eventHub.$on("cancelSale", () => {
                this.is_payment = false;
                this.initForm();
                this.changeExchangeRate()
                this.cancelFormPosLocalStorage()
            });

            await this.$eventHub.$on("reloadDataPersons", customer_id => {
                this.reloadDataCustomers(customer_id);
                this.setFormPosLocalStorage()
            });

            await this.$eventHub.$on("reloadDataItems", item_id => {
                this.reloadDataItems(item_id);
            });

            await this.$eventHub.$on("saleSuccess", () => {
                // this.is_payment = false
                this.initForm();
                this.getTables();
                this.setFormPosLocalStorage()
                this.items_refund = []
            });
        },

        initForm() {
            this.form = {
                customer_id: null,
                document_type_id: '01',
                series_id: null,
                establishment_id: null,
                type_document_id: 1,
                currency_id: 170,
                date_issue: moment().format('YYYY-MM-DD'),
                date_of_issue: moment().format('YYYY-MM-DD'),
                time_of_issue: moment().format('HH:mm:ss'),
                exchange_rate_sale: 0,
                date_expiration: null,
                type_invoice_id: 1,
                total_discount: 0,
                total_tax: 0,
                watch: false,
                subtotal: 0,
                items: [],
                taxes: [],
                total: 0,
                sale: 0,
                time_days_credit: 0,
                service_invoice: {},
                payment_form_id: 1,
                payment_method_id: 1,
                payments: [],
                electronic: false,
            }
            this.initFormItem();
            this.changeDateOfIssue();
            this.initInputPerson()
        },

        initInputPerson() {
            this.input_person = {
                number: '',
                identity_document_type_id: ''
            }
        },

        initFormItem() {
            this.form_item = {
                id: null,
                item_id: null,
                item: {},
                code: null,
                discount: 0,
                name: null,
                unit_price_value: 0,
                unit_price: 0,
                quantity: 1,
                aux_quantity: 1,
                subtotal: null,
                tax: {},
                tax_id: null,
                total: 0,
                total_tax: 0,
                edited_price: false,
                type_unit: {},
                unit_type_id: null,
                item_unit_types: [],
                IdLoteSelected: null,
                sale_unit_price_with_tax: 0,
                refund: false,
                db_Id: 0,
            };
            //this.items_refund = []
        },

        async clickPayment() {
            let flag = 0;
            this.form.type_resolution = this.configuration.configuration_pos.type_resolution
            this.form.items.forEach(row => {
                if (row.aux_quantity < 0 || row.total < 0 || isNaN(row.total)) {
                    flag++;
                }
            });

            if (flag > 0)
                return this.$message.error("Cantidad negativa o incorrecta");
            if (!this.form.customer_id)
                return this.$message.error("Seleccione un cliente");
            if (!this.form.items[0])
                return this.$message.error("Seleccione un producto");

            this.form.establishment_id = this.establishment.id;
            this.loading = true;
            await this.sleep(800);
            this.is_payment = true;
            this.loading = false;
        },

        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        clickDeleteCustomer() {
            this.form.customer_id = null;
            this.setFormPosLocalStorage()
        },

        async clickAddItem(item, index, input = false) {
            const presentation = item.presentation
//            console.log(item)
            if (this.type_refund) {
//                console.log("Aqui devolucion...")
                this.form_item.item = item;
                this.form_item.unit_price_value = this.form_item.item.sale_unit_price;
                this.form_item.quantity = 1;
                this.form_item.aux_quantity = 1;

                try {
                    // Realiza la petición directamente a la ruta de configuración avanzada
                    const response = await this.$http.get('/co-advanced-configuration/record');
                    // Guarda la configuración en la propiedad local (no en el prop directamente)
                    this.localConfiguration = response.data.data;
                    // Si item_tax_included es false, significa que el precio actual ya trae IVA incluido,
                    // por lo tanto se debe calcular el precio base (sin IVA) y asignarlo.
                    if (!this.localConfiguration.item_tax_included) {
                        // Verifica que existan los datos del impuesto
                        if (this.form_item.item.tax &&
                            this.form_item.item.tax.rate &&
                            this.form_item.item.tax.conversion) {

                            const taxRate = parseFloat(this.form_item.item.tax.rate);
                            const conversion = parseFloat(this.form_item.item.tax.conversion);
                            // Calcula el precio base quitando el IVA: precio_con_iva / (1 + tasa efectiva)
                            unit_price = unit_price / (1 + (taxRate / conversion));
                        } else {
                            console.warn('Datos de impuesto no encontrados.');
                        }
                        // Se asigna el precio calculado sin IVA al campo sale_unit_price
                        this.form_item.item.sale_unit_price = unit_price;
                    }
                    // Si item_tax_included es true, se deja el precio tal cual (porque significa que el precio base no incluye IVA y se supone que se le sumará IVA en otro proceso)
                } catch (error) {
                    console.error("Error al obtener la configuración avanzada:", error);
                }

                this.form_item.unit_price = unit_price;
                this.form_item.item.unit_price = unit_price;
                this.form_item.item.presentation = null;

                // this.form_item.id = this.form_item.item.item_id
                this.form_item.item_id = this.form_item.item.item_id
                this.form_item.unit_type_id = this.form_item.item.unit_type_id
                this.form_item.tax_id = (this.taxes.length > 0) ? this.form_item.item.tax.id : null
                this.form_item.tax = _.find(this.taxes, {
                    'id': this.form_item.tax_id
                })
                this.form_item.unit_type = this.form_item.item.unit_type
                this.form_item.refund = true
                this.form_item.sale_unit_price_with_tax = -1 * item.sale_unit_price_with_tax
                this.items_refund.push(this.form_item);
                formItem.db_id = 0;
                //item.aux_quantity = 1;
            } else {
//                console.log("Aqui no devolucion...")
                this.loading = true;
                // let exchangeRateSale = this.form.exchange_rate_sale;
                // let exist_item = _.find(this.form.items, {
                //     item_id: item.item_id
                // });
                let exist_item = null

                if(!presentation) {
//                    console.log("No presentacion...")
                    exist_item = _.find(this.form.items, {
                        item_id: item.item_id,
                        unit_type_id: item.unit_type_id
                    })
                }else{
//                    console.log("Presentacion...")
                    exist_item = _.find(this.form.items, {
                        item_id: item.item_id,
                        presentation: presentation,
                        unit_type_id: item.unit_type_id
                    })
                }

                let pos = this.form.items.indexOf(exist_item);
                let response = null;

                if (exist_item) {
                    item.edited_price = input
                    if (input) {
                        response = await this.getStatusStock(item.item_id, exist_item.item.aux_quantity);
                        if (!response.success) {
                            item.item.aux_quantity = item.quantity;
                            this.loading = false;
                            return this.$message.error(response.message);
                        }
                        exist_item.quantity = exist_item.item.aux_quantity;
                    } else {
                        response = await this.getStatusStock(item.item_id, parseFloat(exist_item.item.aux_quantity) + 1);
                        if (!response.success) {
                            this.loading = false;
                            return this.$message.error(response.message);
                        }
                        exist_item.quantity++;
                        exist_item.item.aux_quantity++;
                    }

                    let search_item_bd = await _.find(this.items, {
                        item_id: item.item_id
                    });

                    if (search_item_bd) {
                        exist_item.item.unit_price = parseFloat(search_item_bd.sale_unit_price)
                    }

                    let unit_price = exist_item.item.sale_unit_price
//                    console.log(unit_price)
                    exist_item.item.unit_price = unit_price
                    exist_item.unit_type_id = item.unit_type_id
                    this.form.items[pos] = exist_item;
                } else {
                    response = await this.getStatusStock(item.item_id, 1);
                    if (!response.success) {
                        this.loading = false;
                        return this.$message.error(response.message);
                    }
                    this.form_item.item = { ...item }
                    // this.form_item.item = item;
                    this.form_item.unit_price_value = this.form_item.item.sale_unit_price;
                    this.form_item.quantity = 1;
                    this.form_item.aux_quantity = 1;


                    let unit_price = this.form_item.unit_price_value;

                    try {
                        // Realiza la petición directamente a la ruta de configuración avanzada
                        const response = await this.$http.get('/co-advanced-configuration/record');
                        // Guarda la configuración en la propiedad local (no en el prop directamente)
                        this.localConfiguration = response.data.data;
                        // Si item_tax_included es false, significa que el precio actual ya trae IVA incluido,
                        // por lo tanto se debe calcular el precio base (sin IVA) y asignarlo.
                        if (!this.localConfiguration.item_tax_included) {
                            // Verifica que existan los datos del impuesto
                            if (this.form_item.item.tax &&
                                this.form_item.item.tax.rate &&
                                this.form_item.item.tax.conversion) {

                                const taxRate = parseFloat(this.form_item.item.tax.rate);
                                const conversion = parseFloat(this.form_item.item.tax.conversion);
                                // Calcula el precio base quitando el IVA: precio_con_iva / (1 + tasa efectiva)
                                unit_price = unit_price / (1 + (taxRate / conversion));
                            } else {
                                console.warn('Datos de impuesto no encontrados.');
                            }
                            // Se asigna el precio calculado sin IVA al campo sale_unit_price
                            this.form_item.item.sale_unit_price = unit_price;
                        }
                        // Si item_tax_included es true, se deja el precio tal cual (porque significa que el precio base no incluye IVA y se supone que se le sumará IVA en otro proceso)
                    } catch (error) {
                        console.error("Error al obtener la configuración avanzada:", error);
                    }

                    this.form_item.unit_price = unit_price;
                    this.form_item.item.unit_price = unit_price;
                    // this.form_item.item.presentation = null;
                    // this.form_item.id = this.form_item.item.item_id
                    this.form_item.item_id = this.form_item.item.item_id
                    this.form_item.tax_id = (this.taxes.length > 0) ? (this.form_item.item.tax !== null ? this.form_item.item.tax.id : null) : null
                    this.form_item.tax = _.find(this.taxes, {
                        'id': this.form_item.tax_id
                    })

                    // lista precios
                    if(presentation)
                    {
                        this.form_item.presentation = presentation
                        this.form_item.unit_type_id = presentation.unit_type_id
                        this.form_item.unit_type = presentation.unit_type

                    }else
                    {
                        this.form_item.presentation = null
                        this.form_item.unit_type_id = this.form_item.item.unit_type_id
                        this.form_item.unit_type = this.form_item.item.unit_type
                    }
                    this.form.items.push(this.form_item);
                    item.aux_quantity = 1;
                }

                if(!input)
    	                this.$notify({
                        title: "",
                        message: "Producto añadido!",
                        type: "success",
                        duration: 700
                    });
            }

//            console.log(this.form.items)
            await this.calculateTotal();
            this.loading = false;
            await this.setFormPosLocalStorage()
            await this.initFormItem()
        },
        async clickAddItemAccount(itemId, index, quantity, dbId, input = false) {
            const btn = document.getElementById('btnAddItemAccount');
            const spinner = btn.querySelector('.spinner-border');

            btn.disabled = true;
            spinner.classList.remove('d-none');
            let itemResponse;

            try {
                itemResponse = await this.$http.get(`/${this.resource}/get-item/${itemId}/${dbId}`);
            } catch (error) {
                 btn.disabled = false;
                    spinner.classList.add('d-none');
                if (error.response && error.response.status === 422) {
                    this.$message.error(error.response.data.message);
                } else {
                    this.$message.error('Error al obtener los datos del producto.');
                }
            }

            const item = itemResponse.data.data;
            const presentation = item.presentation;
            if (this.type_refund) {
                let formItem = JSON.parse(JSON.stringify(this.form_item));

                formItem.id = itemId;
                formItem.item = itemId;
                formItem.unit_price_value = item.sale_unit_price;
                formItem.unit_price = item.sale_unit_price;
                formItem.quantity = quantity;
                formItem.aux_quantity = quantity;

                formItem.item = { ...item };
                formItem.item.unit_price = item.sale_unit_price;
                formItem.item.presentation = null;

                formItem.item_id = item.item_id;
                formItem.unit_type_id = item.unit_type_id;
                formItem.tax_id = this.taxes.length > 0 ? (item.tax ? item.tax.id : null) : null;
                formItem.tax = _.find(this.taxes, { id: formItem.tax_id });
                formItem.unit_type = item.unit_type;
                formItem.refund = true;
                formItem.sale_unit_price_with_tax = -1 * item.sale_unit_price_with_tax;
                formItem.db_Id = dbId;

                this.items_refund.push(formItem);
            } else {
                const response = await this.getStatusStock(itemId, quantity);
                if (!response.success) {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                    return this.$message.error(response.message);
                }

                let formItem = JSON.parse(JSON.stringify(this.form_item));
                formItem.item = { ...item };
                formItem.id = itemId;
                formItem.unit_price_value = item.sale_unit_price;
                formItem.item.edit_sale_unit_price = item.sale_unit_price;
                formItem.unit_price = item.sale_unit_price_with_tax;
                formItem.item.unit_price = item.sale_unit_price_with_tax;

                formItem.quantity = quantity;
                formItem.aux_quantity = quantity;
                formItem.item.aux_quantity = quantity;

                formItem.item_id = item.item_id;
                formItem.tax_id = this.taxes.length > 0 ? (item.tax ? item.tax.id : null) : null;
                formItem.tax = _.find(this.taxes, { id: formItem.tax_id });
                formItem.db_Id = dbId;

                if (presentation) {
                    formItem.presentation = { ...presentation };
                    formItem.unit_type_id = presentation.unit_type_id;
                    formItem.unit_type = presentation.unit_type;
                } else {
                    formItem.presentation = null;
                    formItem.unit_type_id = item.unit_type_id;
                    formItem.unit_type = item.unit_type;
                }

                 this.form.items.push(formItem);
                if (!input) {
                    this.$notify({
                        title: "",
                        message: "Producto añadido!",
                        type: "success",
                        duration: 700
                    });
                }
            }

            await this.calculateTotal();
            btn.disabled = false;
            spinner.classList.add('d-none');
            await this.setFormPosLocalStorage();
            await this.initFormItem();
            document.getElementById('closeModalBtnCuenta').click();
        },
        async clickAddItemAccount2(itemId, index, quantity, dbId, input = false) {
            let itemResponse;

            try {
                itemResponse = await this.$http.get(`/${this.resource}/get-item/${itemId}/${dbId}`);
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    this.$message.error(error.response.data.message);
                } else {
                    this.$message.error('Error al obtener los datos del producto.');
                }
            }

            const item = itemResponse.data.data;
            const presentation = item.presentation;
            if (this.type_refund) {
                let formItem = JSON.parse(JSON.stringify(this.form_item));

                formItem.id = itemId;
                formItem.item = itemId;
                formItem.unit_price_value = item.sale_unit_price;
                formItem.unit_price = item.sale_unit_price;
                formItem.quantity = quantity;
                formItem.aux_quantity = quantity;

                formItem.item = { ...item };
                formItem.item.unit_price = item.sale_unit_price;
                formItem.item.presentation = null;

                formItem.item_id = item.item_id;
                formItem.unit_type_id = item.unit_type_id;
                formItem.tax_id = this.taxes.length > 0 ? (item.tax ? item.tax.id : null) : null;
                formItem.tax = _.find(this.taxes, { id: formItem.tax_id });
                formItem.unit_type = item.unit_type;
                formItem.refund = true;
                formItem.sale_unit_price_with_tax = -1 * item.sale_unit_price_with_tax;
                formItem.db_Id = dbId;

                this.items_refund.push(formItem);
            } else {
                const response = await this.getStatusStock(itemId, quantity);
                if (!response.success) {
                    return this.$message.error(response.message);
                }

                let formItem = JSON.parse(JSON.stringify(this.form_item));
                formItem.item = { ...item };
                formItem.id = itemId;
                formItem.unit_price_value = item.sale_unit_price;
                formItem.item.edit_sale_unit_price = item.sale_unit_price;
                formItem.unit_price = item.sale_unit_price_with_tax;
                formItem.item.unit_price = item.sale_unit_price_with_tax;

                formItem.quantity = quantity;
                formItem.aux_quantity = quantity;
                formItem.item.aux_quantity = quantity;

                formItem.item_id = item.item_id;
                formItem.tax_id = this.taxes.length > 0 ? (item.tax ? item.tax.id : null) : null;
                formItem.tax = _.find(this.taxes, { id: formItem.tax_id });
                formItem.db_Id = dbId;

                if (presentation) {
                    formItem.presentation = { ...presentation };
                    formItem.unit_type_id = presentation.unit_type_id;
                    formItem.unit_type = presentation.unit_type;
                } else {
                    formItem.presentation = null;
                    formItem.unit_type_id = item.unit_type_id;
                    formItem.unit_type = item.unit_type;
                }

                 this.form.items.push(formItem);
                if (!input) {
                    this.$notify({
                        title: "",
                        message: "Producto añadido!",
                        type: "success",
                        duration: 700
                    });
                }
            }

            await this.calculateTotal();
            await this.setFormPosLocalStorage();
            await this.initFormItem();
            document.getElementById('closeModalBtnCuenta').click();
        },
        async getStatusStock(item_id, quantity) {
            let data = {};
            if (!quantity) quantity = 0;
            await this.$http
                .get(`/${this.resource}/validate_stock/${item_id}/${quantity}`)
                .then(response => {
                    data = response.data;
                });
            return data;
        },
        async clickDeleteItem(index) {
            const item = this.form.items[index];
            if (item.db_Id && item.db_Id !== 0) {
                try {
                    const response = await this.$http.post(`/${this.resource}/actualizar_estado_item/${item.db_Id}`);
                    if (response.data.success) {
                        this.form.items.splice(index, 1);
                    }
                } catch (error) {
                    this.$message.error('Error en la petición al servidor');
                }
            } else {
                this.form.items.splice(index, 1);
            }
            this.calculateTotal();
            await this.setFormPosLocalStorage();
        },
        async clickDeleteItemRefund(index) {
            this.items_refund.splice(index, 1);
            this.calculateTotal();
            await this.setFormPosLocalStorage()
        },

        calculateTotal() {
            this.setDataTotals()
        },
        changeDateOfIssue() {
            // this.searchExchangeRateByDate(this.form.date_of_issue).then(response => {
            //     this.form.exchange_rate_sale = response
            // })

        },
        setDataTotals() {
            let val = this.form
            val.taxes = this.taxes;
            val.taxes.forEach(tax => {
                tax.total = 0
            });

            val.items.forEach(item => {
                item.tax = this.taxes.find(tax => tax.id == item.tax_id);

                if (
                    item.discount == null ||
                    item.discount == "" ||
                    item.discount > item.unit_price * item.quantity
                ) {
                    this.$set(item, "discount", 0);
                }

                if (item.tax != null) {

                    let tax = val.taxes.find(tax => tax.id == item.tax.id);
//                    tax.total = 0

                    if (item.tax.is_fixed_value)
                        item.total_tax = (
                            item.tax.rate * item.quantity -
                            (item.discount < item.unit_price * item.quantity ? item.discount : 0)
                        ).toFixed(2);

//                    console.log(item)
                    if (item.tax.is_percentage) {
                        if(!item.edited_price){
                            item.total_tax = (
                                (item.unit_price * item.quantity -
                                    (item.discount < item.unit_price * item.quantity ?
                                        item.discount :
                                        0)) *
                                (item.tax.rate / item.tax.conversion)
                            ).toFixed(2);
                        }
                        else{
//                            console.log("Aquui 2");
//                            console.log(item.unit_price)
//                            console.log(item.sale_unit_price_with_tax)
//                            console.log(item.tax.rate)
//                            console.log(item.tax.conversion)
//                            console.log(1 + (item.tax.rate / item.tax.conversion))
                            item.unit_price = (item.sale_unit_price_with_tax / (1 + (item.tax.rate / item.tax.conversion)))
                            console.log("Aqui...")
                            console.log(item.sale_unit_price_with_tax)
                            console.log(item.tax.rate)
                            console.log(item.tax.conversion)
                            console.log(item.unit_price)
                            item.total_tax = (
                                (item.unit_price * item.quantity -
                                    (item.discount < item.sale_unit_price_with_tax * item.quantity ?
                                        item.discount :
                                        0)) *
                                (item.tax.rate / item.tax.conversion)
                            ).toFixed(2);
                        }
                    }

                    if (!tax.hasOwnProperty("total")) {
                        tax.total = Number(0).toFixed(2);
                    }
//                    console.log(tax.total)
//                    console.log(item.total_tax)
                    tax.total = (Number(tax.total) + Number(item.total_tax)).toFixed(2);
//                    console.log(tax.total)
                }
                if(!item.edited_price){
                    item.subtotal = (
                        Number(item.unit_price * item.quantity) + Number(item.total_tax)
                    ).toFixed(2);
                }
                else{
                    item.subtotal = (
                        Number(item.sale_unit_price_with_tax * item.quantity)
                    ).toFixed(2);
                }

                this.$set(
                    item,
                    "total",
                    (Number(item.subtotal) - Number(item.discount)).toFixed(2)
                );

                if(!item.edited_price){
                    this.$set(
                        item,
                        "sale_unit_price_with_tax",
                        (Number(item.subtotal) / Number(item.quantity)).toFixed(2)
                    );
                }
            });

            this.items_refund.forEach(item => {
                item.tax = this.taxes.find(tax => tax.id == item.tax_id);
                this.$set(item, "discount", 0);
                item.total_tax = 0;
                if (item.tax != null) {
                    let tax = val.taxes.find(tax => tax.id == item.tax.id);
                    if (item.tax.is_fixed_value) {
                        item.total_tax = (
                            item.tax.rate * item.quantity -
                            (item.discount < item.unit_price * item.quantity ? item.discount : 0)
                        ).toFixed(2);
                    }

                    if (item.tax.is_percentage) {
                        item.total_tax = (
                            (item.unit_price * item.quantity -
                                (item.discount < item.unit_price * item.quantity ?
                                    item.discount :
                                    0)) *
                            (item.tax.rate / item.tax.conversion)
                        ).toFixed(2);
                    }

                    if (!tax.hasOwnProperty("total")) {
                        tax.total = Number(0).toFixed(2);
                    }

                    tax.total = (Number(tax.total) - Number(item.total_tax)).toFixed(2);
                }

                item.subtotal = (
                    Number(item.unit_price * item.quantity) + Number(item.total_tax)
                ).toFixed(2);

                this.$set(
                    item,
                    "total",
                    ((Number(item.subtotal) - Number(item.discount))).toFixed(2)
                );

            })
            const subtotal = val.items.reduce((p, c) => Number(p) + (Number(c.subtotal) - Number(c.discount)), 0);
            const subtotal_refund = this.items_refund.reduce((p, c) => Number(p) + (Number(c.subtotal) - Number(c.discount)), 0);

            val.subtotal = (subtotal - subtotal_refund).toFixed(2)
//            console.log(val.items)
//            console.log(val.items.reduce((p, c) => Number(p) + ((Number(c.sale_unit_price_with_tax) *  Number(c.quantity))) - Number(c.total_tax) - Number(c.discount), 0))
            const sale = !val.items.edited_price ? val.items.reduce((p, c) => Number(p) + Number((c.sale_unit_price_with_tax * c.quantity) - c.total_tax) - Number(c.discount), 0) : val.items.reduce((p, c) => Number(p) + Number(c.unit_price * c.quantity) - Number(c.discount), 0);
//            console.log(this.items_refund)
//            console.log(!val.items.edited_price)

//            const sale_refund = !val.items.edited_price ? this.items_refund.reduce((p, c) => Number(p) - Number((c.sale_unit_price_with_tax  * c.quantity) + c.total_tax) - Number(c.discount), 0) : this.items_refund.reduce((p, c) => Number(p) - Number(c.unit_price * c.quantity) - Number(c.discount), 0);
//            const s1 = this.items_refund.reduce((p, c) => Number(p) - Number((c.sale_unit_price_with_tax  * c.quantity) + c.total_tax) - Number(c.discount), 0)
            const sale_refund = this.items_refund.reduce((p, c) => Number(p) - Number(c.unit_price * c.quantity) - Number(c.discount), 0)
//            console.log(sale)
//            console.log(sale_refund)
            val.sale = (sale + sale_refund).toFixed(2)
//            console.log(val.sale)
            val.total_discount = val.items
                .reduce((p, c) => Number(p) + Number(c.discount), 0)
                .toFixed(2);
//            console.log(val.items.reduce((p, c) => Number(p) + Number(c.total_tax), 0))
            val.total_tax = val.items
                .reduce((p, c) => Number(p) + Number(c.total_tax), 0)
                .toFixed(2);

            let total = val.items
                .reduce((p, c) => Number(p) + Number(c.total), 0);

            let total_refund = this.items_refund
                .reduce((p, c) => Number(p) + Number(c.total), 0);

            let totalRetentionBase = Number(0);

            // this.taxes.forEach(tax => {
            val.taxes.forEach(tax => {
                if (tax.is_retention && tax.in_base && tax.apply) {
                    tax.retention = (
                        Number(val.sale) *
                        (tax.rate / tax.conversion)
                    ).toFixed(2);

                    totalRetentionBase =
                        Number(totalRetentionBase) + Number(tax.retention);

                    if (Number(totalRetentionBase) >= Number(val.sale))
                        this.$set(tax, "retention", Number(0).toFixed(2));

                    total -= Number(tax.retention).toFixed(2);
                }

                if (
                    tax.is_retention &&
                    !tax.in_base &&
                    tax.in_tax != null &&
                    tax.apply
                ) {
                    let row = val.taxes.find(row => row.id == tax.in_tax);

                    tax.retention = Number(
                        Number(row.total) * (tax.rate / tax.conversion)
                    ).toFixed(2);

                    if (Number(tax.retention) > Number(row.total))
                        this.$set(tax, "retention", Number(0).toFixed(2));

                    row.retention = Number(tax.retention).toFixed(2);
                    total -= Number(tax.retention).toFixed(2);
                }
            });

            val.total = (Number(total) - Number(total_refund)).toFixed(2)

        },

        changeExchangeRate() {
            // this.searchExchangeRateByDate(this.form.date_of_issue).then(response => {
            //     this.form.exchange_rate_sale = response
            // })
        },

        async getTables() {
            await this.$http.get(`/${this.resource}/tables`).then(response => {
                this.all_items = response.data.items;
                this.all_customers = response.data.customers;
                this.currencies = response.data.currencies;
                this.establishment = response.data.establishment;
                this.user = response.data.user;
                this.form.currency_id = this.currencies.length > 0 ? this.currencies[0].id : null;
                // console.log(this.form.currency_id)
                this.taxes = response.data.taxes
                this.renderCategories(response.data.categories)
                // this.currency = _.find(this.currencys, {'id': this.form.currency_id})
                // this.changeCurrencyType();
                this.initCurrencyType()
                this.filterItems();
                this.changeDateOfIssue();
                this.changeExchangeRate()
            });
//            console.log(this.electronic);
        },

        renderCategories(source) {
            const contex = this
            this.categories = source.map((obj, index) => {
                return {
                    id: obj.id,
                    name: obj.name,
                    color: contex.getColor(index)
                }
            })

            this.categories.unshift({
                id: null,
                name: 'Todos',
                color: '#2C8DE3'
            })
        },
        searchItems() {
            if (this.input_item.length > 3) {
                this.loading = true;
                let parameters = `input_item=${this.input_item}`;

                this.$http
                    .get(`/${this.resource}/search_items?${parameters}`)
                    .then(response => {
                        // console.log(response)
                        this.items = response.data.data;

                        this.pagination = response.data.meta;
                        this.pagination.per_page = parseInt(
                            response.data.meta.per_page
                        );

                        this.loading = false;
                        if (this.items.length == 0) {
                            this.filterItems();
                        }
                    });
            } else {
                // this.customers = []
                this.filterItems();
            }

        },
        async searchItemsBarcode() {

            // console.log(query)
            // console.log("in:" + this.input_item)

            if (this.input_item.length > 1) {

                this.loading = true;
                let parameters = `input_item=${this.input_item}`;

                await this.$http.get(`/${this.resource}/search_items?${parameters}`)
                    .then(response => {

                        this.items = response.data.data;

                        this.pagination = response.data.meta;
                        this.pagination.per_page = parseInt(
                            response.data.meta.per_page
                        );

                        this.enabledSearchItemsBarcode()
                        this.loading = false;
                        if (this.items.length == 0) {
                            this.filterItems();
                        }

                    });

            } else {

                await this.filterItems();

            }

        },
        enabledSearchItemsBarcode() {

            if (this.search_item_by_barcode) {
                console.log(this.items)
                if (this.items.length == 1) {

                    // console.log(this.items)
                    this.clickAddItem(this.items[0], 0);
                    this.filterItems();

                }

                this.cleanInput();

            }

        },
        changeSearchItemBarcode() {
            this.cleanInput()
        },
        cleanInput() {
            this.input_item = null;
        },
        filterItems() {
            this.items = this.all_items;
        },
        reloadDataCustomers(customer_id) {
            this.$http.get(`/${this.resource}/table/customers`).then(response => {
                this.all_customers = response.data;
                this.form.customer_id = customer_id;
                this.changeCustomer();
            });
        },
        reloadDataItems(item_id) {
            this.$http.get(`/${this.resource}/table/items`).then(response => {
                this.all_items = response.data;
                this.filterItems();
            });
        },
        selectCurrencyType() {
            // this.form.currency_id = (this.form.currency_id === 'PEN') ? 'USD':'PEN'
            // this.changeCurrencyType()
        },
        async changeCurrencyType() {

            // console.log(this.form.currency_id)
            // this.currency = await _.find(this.currencys, {'id': this.form.currency_id})
            // let items = []
            // this.form.items.forEach((row) => {
            //     items.push(calculateRowItem(row, this.form.currency_id, this.form.exchange_rate_sale))
            // });
            // this.form.items = items
            // this.calculateTotal()

            // await this.setFormPosLocalStorage()

        },
        openFullWindow() {
            location.href = `/${this.resource}/pos_full`
        },
        back() {
            this.place = 'cat'
        },
        setView() {
            this.place = 'cat2'
        },
        nameSets(id) {
            let row = this.items.find(x => x.item_id == id)
            if (row) {

                if (row.sets.length > 0) {
                    return row.sets.join('-')
                } else {
                    return ''
                }

            }
        },
        clearText(texto) {
            return texto.replace(/&nbsp;/g, ' ').replace(/\s{2,}/g, ' ').trim();
        }
    }
};
</script>
