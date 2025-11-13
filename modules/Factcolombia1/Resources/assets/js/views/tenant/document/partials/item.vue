<template>
    <el-dialog :title="titleDialog" :visible="showDialog" @open="create" @close="close" top="3vh" :close-on-click-modal="false" width="92%">
        <form autocomplete="off" @submit.prevent="clickAddItem">
            <div class="form-body">
                <!-- Card de Búsqueda de Producto -->
                <el-card class="mb-3" shadow="hover">
                    <div slot="header" class="clearfix">
                        <i class="el-icon-search"></i> <strong>Búsqueda de Producto/Servicio</strong>
                        <el-tag v-if="form.item_id" type="success" size="small" style="float: right;">
                            <i class="el-icon-check"></i> Producto seleccionado
                        </el-tag>
                    </div>

                    <div class="row">
                        <!-- COLUMNA IZQUIERDA: Buscador de Producto -->
                        <div class="col-md-8 col-lg-8">
                            <div class="form-group" id="custom-select" :class="{'has-danger': errors.item_id}">
                                <label class="control-label">
                                    <strong><i class="el-icon-goods"></i> Producto/Servicio</strong>
                                    <a v-if="typeUser != 'seller'" href="#" @click.prevent="showDialogNewItem = true" class="ml-2">
                                        <el-tag type="success" size="mini"><i class="el-icon-plus"></i> Nuevo</el-tag>
                                    </a>
                                </label>

                                <!-- Botones de modo de búsqueda -->
                                <template v-if="!is_client">
                                    <el-radio-group v-model="search_item_by_barcode" size="small" style="margin-bottom: 10px;" :disabled="recordItem != null">
                                        <el-radio-button :label="false">
                                            <i class="el-icon-search"></i> Búsqueda Normal
                                        </el-radio-button>
                                        <el-radio-button :label="true">
                                            <i class="el-icon-camera"></i> Código de Barras
                                        </el-radio-button>
                                    </el-radio-group>
                                </template>

                                <!-- Input para el escáner de código de barras -->
                                <div v-if="search_item_by_barcode" style="margin-top: 10px;">
                                    <el-input
                                        id="barcodeInput"
                                        v-model="barcodeInput"
                                        @keyup.enter.native="handleBarcodeScan"
                                        placeholder="Escanea o ingresa el código de barras y presiona Enter"
                                        prefix-icon="el-icon-camera"
                                        size="large"
                                        clearable>
                                        <el-button slot="append" icon="el-icon-search" @click="handleBarcodeScan" type="primary">Buscar</el-button>
                                    </el-input>

                                    <!-- Muestra el producto escaneado -->
                                    <el-alert
                                        v-if="currentProduct"
                                        :title="`✓ Producto: ${currentProduct.full_description}`"
                                        type="success"
                                        :closable="false"
                                        show-icon
                                        class="mt-2">
                                    </el-alert>
                                </div>

                                <!-- Búsqueda normal con filtros rápidos -->
                                <template v-if="!search_item_by_barcode" id="select-append">
                                    <!-- Filtros rápidos -->
                                    <div class="mb-2">
                                        <el-button-group size="mini">
                                            <el-button :type="quickFilter === 'all' ? 'primary' : ''" @click="quickFilter = 'all'; applyQuickFilter()">
                                                <i class="el-icon-s-grid"></i> Todos
                                            </el-button>
                                            <el-button :type="quickFilter === 'stock' ? 'primary' : ''" @click="quickFilter = 'stock'; applyQuickFilter()">
                                                <i class="el-icon-goods"></i> Con Stock
                                            </el-button>
                                            <el-button :type="quickFilter === 'recent' ? 'primary' : ''" @click="quickFilter = 'recent'; applyQuickFilter()">
                                                <i class="el-icon-time"></i> Recientes
                                                <el-badge v-if="getRecentItemsFromStorage().length > 0" :value="getRecentItemsFromStorage().length" class="ml-1" type="success"></el-badge>
                                            </el-button>
                                        </el-button-group>
                                        <el-tooltip v-if="quickFilter === 'recent'" content="Los productos recientes se guardan por 90 días y persisten entre sesiones" placement="right">
                                            <i class="el-icon-info ml-2" style="color: #909399; cursor: help;"></i>
                                        </el-tooltip>
                                    </div>

                                    <el-input id="custom-input" size="large">
                                        <el-select :disabled="recordItem != null"
                                                v-model="form.item_id"
                                                @change="changeItem"
                                                filterable
                                                remote
                                                placeholder="🔍 Buscar por nombre, código o descripción..."
                                                popper-class="el-select-items"
                                                @visible-change="focusTotalItem"
                                                slot="prepend"
                                                id="select-width"
                                                :remote-method="searchRemoteItems"
                                                :loading="loading_search">
                                            <el-option
                                                v-for="option in items"
                                                :key="option.id"
                                                :value="option.id"
                                                :label="option.full_description">
                                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                                    <div style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                        <strong>{{ option.internal_id }}</strong> - {{ option.name }}
                                                    </div>
                                                    <div style="margin-left: 10px; white-space: nowrap;">
                                                        <el-tag size="mini" :type="option.stock > 0 ? 'success' : 'danger'" style="margin-right: 3px;">
                                                            <i class="el-icon-goods"></i> {{ option.stock }}
                                                        </el-tag>
                                                        <el-tag size="mini" type="warning">
                                                            {{ option.currency_type_symbol }} {{ option.sale_unit_price }}
                                                        </el-tag>
                                                    </div>
                                                </div>
                                            </el-option>
                                        </el-select>
                                        <el-tooltip slot="append" class="item" effect="dark" content="Ver Stock del Producto en Almacenes" placement="bottom" :disabled="recordItem != null">
                                            <el-button :disabled="isEditItemNote" @click.prevent="clickWarehouseDetail()" icon="el-icon-search" type="info">Stock</el-button>
                                        </el-tooltip>
                                    </el-input>
                                </template>

                                <small class="form-control-feedback" v-if="errors.item_id" v-text="errors.item_id[0]"></small>
                            </div>
                        </div>

                        <!-- COLUMNA DERECHA: Impuesto -->
                        <div class="col-md-4 col-lg-4">
                            <div class="form-group" :class="{'has-danger': errors.tax_id}">
                                <label class="control-label">
                                    <strong><i class="el-icon-s-finance"></i> Impuesto</strong>
                                </label>
                                <el-select v-model="form.tax_id" filterable placeholder="Seleccionar impuesto" style="width: 100%;">
                                    <el-option
                                        v-for="option in itemTaxes"
                                        :key="option.id"
                                        :value="option.id"
                                        :label="option.name">
                                        <span style="float: left">{{ option.name }}</span>
                                        <span style="float: right; color: #8492a6; font-size: 13px">{{ option.rate }}%</span>
                                    </el-option>
                                </el-select>

                                <div class="mt-2">
                                    <el-button @click="form.tax_id = null" size="mini" type="text" icon="el-icon-circle-close">
                                        Excluir impuesto
                                    </el-button>
                                </div>

                                <!-- Checkbox de precio con impuesto incluido -->
                                <template v-if="!is_client">
                                    <el-checkbox
                                        v-model="tax_included_in_price"
                                        :disabled="false"
                                        @change="change_price_tax_included"
                                        class="mt-2">
                                        {{ taxCheckboxLabel }}
                                    </el-checkbox>
                                </template>

                                <small class="form-control-feedback" v-if="errors.tax_id" v-text="errors.tax_id[0]"></small>
                            </div>
                        </div>
                    </div>
                </el-card>

                <!-- Card de Detalles y Cantidades -->
                <el-card class="mb-3" shadow="hover">
                    <div slot="header" class="clearfix">
                        <i class="el-icon-shopping-cart-2"></i> <strong>Detalles del Producto</strong>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="form-group" :class="{'has-danger': errors.notes}">
                                <label class="control-label"><i class="el-icon-edit"></i> Notas / Observaciones</label>
                                <el-input
                                    v-model="form.notes"
                                    type="textarea"
                                    :rows="2"
                                    placeholder="Ingrese notas adicionales sobre el producto...">
                                </el-input>
                                <small class="form-control-feedback" v-if="errors.notes" v-text="errors.notes[0]"></small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group" :class="{'has-danger': errors.quantity}">
                                <label class="control-label">
                                    <i class="el-icon-s-goods"></i> Cantidad
                                    <el-tooltip content="Cantidad de unidades del producto" placement="top">
                                        <i class="el-icon-question" style="cursor: help; color: #909399;"></i>
                                    </el-tooltip>
                                </label>
                                <el-input-number
                                    v-model="form.quantity"
                                    :min="0.01"
                                    :disabled="form.item.calculate_quantity"
                                    controls-position="right"
                                    style="width: 100%;">
                                </el-input-number>
                                <small class="form-control-feedback" v-if="errors.quantity" v-text="errors.quantity[0]"></small>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="form-group" :class="{'has-danger': errors.price}">
                                <label class="control-label">
                                    <i class="el-icon-sell"></i> Precio Unitario
                                    <el-tooltip content="Precio por unidad del producto" placement="top">
                                        <i class="el-icon-question" style="cursor: help; color: #909399;"></i>
                                    </el-tooltip>
                                </label>
                                <el-input
                                    v-model="form.price"
                                    @input="calculateQuantity"
                                    :readonly="typeUser === ''"
                                    type="number"
                                    step="0.01">
                                    <template slot="prepend" v-if="currencyTypeSymbolActive">{{ currencyTypeSymbolActive }}</template>
                                </el-input>
                                <small class="form-control-feedback" v-if="errors.price" v-text="errors.unit_price[0]"></small>
                            </div>
                        </div>

                        <div class="col-md-2 col-sm-4" v-if="form.item_id && form.item.lots_enabled && form.lots_group.length > 0">
                            <div class="form-group">
                                <label class="control-label"><i class="el-icon-box"></i> Lotes</label>
                                <el-button
                                    type="primary"
                                    size="small"
                                    @click.prevent="clickLotGroup"
                                    style="width: 100%;"
                                    plain>
                                    <i class="el-icon-check"></i> Seleccionar
                                </el-button>
                            </div>
                        </div>

                        <div class="col-md-2 col-sm-4" v-if="form.item_id && form.item.series_enabled">
                            <div class="form-group">
                                <label class="control-label"><i class="el-icon-tickets"></i> Series</label>
                                <el-button
                                    type="warning"
                                    size="small"
                                    @click.prevent="clickSelectLots"
                                    style="width: 100%;"
                                    plain>
                                    <i class="el-icon-check"></i> Seleccionar
                                </el-button>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6" v-show="form.item.calculate_quantity">
                            <div class="form-group"  :class="{'has-danger': errors.total_item}">
                                <label class="control-label">
                                    <i class="el-icon-coin"></i> Total venta producto
                                </label>
                                <el-input
                                    v-model="total_item"
                                    @input="calculateQuantity"
                                    :min="0.01"
                                    ref="total_item"
                                    type="number"
                                    step="0.01">
                                    <template slot="prepend" v-if="currencyTypeSymbolActive">{{ currencyTypeSymbolActive }}</template>
                                </el-input>
                                <small class="form-control-feedback" v-if="errors.total_item" v-text="errors.total_item[0]"></small>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="form-group"  :class="{'has-danger': errors.discount}">
                                <label class="control-label">
                                    <i class="el-icon-price-tag"></i> Descuento
                                    <el-tooltip content="Descuento en valor monetario" placement="top">
                                        <i class="el-icon-question" style="cursor: help; color: #909399;"></i>
                                    </el-tooltip>
                                </label>
                                <el-input
                                    v-model="form.discount"
                                    :min="0"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00">
                                    <template slot="prepend" v-if="currencyTypeSymbolActive">{{ currencyTypeSymbolActive }}</template>
                                </el-input>
                                <small class="form-control-feedback" v-if="errors.discount" v-text="errors.discount[0]"></small>
                            </div>
                        </div>

                        <!-- Resumen visual del total -->
                        <div class="col-md-3 col-sm-6" v-if="form.item_id && form.quantity && form.price">
                            <div class="form-group">
                                <label class="control-label">
                                    <i class="el-icon-wallet"></i> Subtotal
                                </label>
                                <el-alert
                                    :title="`${currencyTypeSymbolActive || ''} ${(form.quantity * form.price - (form.discount || 0)).toFixed(2)}`"
                                    type="success"
                                    :closable="false"
                                    style="padding: 10px; font-size: 18px; font-weight: bold;">
                                </el-alert>
                            </div>
                        </div>
                    </div>
                </el-card>

                <!-- Lotes y Series -->
                <div class="row" v-if="form.item_id && (form.item.lots_enabled || form.item.series_enabled)">

                    <template v-if="!is_client">
                        <div class="col-md-12"  v-if="form.item_unit_types.length > 0">
                            <div style="margin:3px" class="table-responsive">
                                <h5 class="separator-title">
                                    Lista de Precios
                                    <el-tooltip class="item" effect="dark" content="Aplica para realizar venta en presentacion de diferentes precios y/o cantidades" placement="top">
                                        <i class="fa fa-info-circle"></i>
                                    </el-tooltip>
                                </h5>
                                <table class="table">
                                <thead>
                                <tr>
                                    <th class="text-center">Unidad</th>
                                    <th class="text-center">Descripción</th>
                                    <th class="text-center">Factor</th>
                                    <th class="text-center">Precio 1</th>
                                    <th class="text-center">Precio 2</th>
                                    <th class="text-center">Precio 3</th>
                                    <th class="text-center">Precio Default</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(row, index) in form.item_unit_types" :key="index">

                                        <td class="text-center">{{row.unit_type.name}}</td>
                                        <td class="text-center">{{row.description}}</td>
                                        <td class="text-center">{{row.quantity_unit}}</td>
                                        <td class="text-center">{{row.price1}}</td>
                                        <td class="text-center">{{row.price2}}</td>
                                        <td class="text-center">{{row.price3}}</td>
                                        <td class="text-center">Precio {{row.price_default}}</td>
                                        <td class="series-table-actions text-right">
                                        <button type="button" class="btn waves-effect waves-light btn-xs btn-success" @click.prevent="selectedPrice(row)">
                                                <i class="el-icon-check"></i>
                                            </button>
                                        </td>
                                </tr>
                                </tbody>
                            </table>
                            </div>
                        </div>

                    </template>
                </div>
            </div>
            <div class="form-actions text-right pt-2">
                <el-button @click.prevent="close()">Cerrar</el-button>
                <el-button class="add" type="primary" native-type="submit" v-if="form.item_id">{{titleAction}}</el-button>
            </div>
        </form>

        <item-form :showDialog.sync="showDialogNewItem"
                   :external="true"></item-form>

        <warehouses-detail
                :showDialog.sync="showWarehousesDetail"
                :isUpdateWarehouseId="isUpdateWarehouseId"
                :warehouses="warehousesDetail">
            </warehouses-detail>

        <lots-group
            :quantity="form.quantity"
            :showDialog.sync="showDialogLots"
            :lots_group="form.lots_group"
            @addRowLotGroup="addRowLotGroup">
        </lots-group>

        <select-lots-form
            :showDialog.sync="showDialogSelectLots"
            :lots="lots"
            @addRowSelectLot="addRowSelectLot">
        </select-lots-form>
    </el-dialog>
</template>

<style scoped>
.el-select-dropdown {
    max-width: 80% !important;
    margin-right: 5% !important;
}

/* Estilos para cards con hover effect */
.el-card {
    transition: all 0.3s ease;
}

.el-card:hover {
    box-shadow: 0 2px 12px 0 rgba(0,0,0,.15);
}

/* Mejora visual para los botones de grupo */
.el-button-group {
    display: flex;
    width: 100%;
}

.el-button-group .el-button {
    flex: 1;
}

/* Estilo para el resumen de subtotal */
.el-alert {
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Mejora de los iconos de ayuda */
.el-icon-question {
    font-size: 14px;
    margin-left: 5px;
}

/* Mejora visual de las etiquetas */
.control-label {
    font-weight: 600;
    color: #606266;
    margin-bottom: 8px;
}

.control-label i {
    margin-right: 5px;
    color: #409EFF;
}

/* Animación para el producto seleccionado */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.el-tag {
    animation: fadeIn 0.3s ease;
}

/* Mejora del input number */
.el-input-number {
    width: 100%;
}

/* Card headers más destacados */
.el-card__header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom: none;
}

.el-card__header strong {
    color: white;
}

.el-card__header i {
    color: white;
}

/* Botones con mejor espaciado */
.form-actions {
    border-top: 1px solid #EBEEF5;
    margin-top: 20px;
    padding-top: 20px;
}

/* Separador de título en lista de precios */
.separator-title {
    font-weight: 600;
    color: #409EFF;
    border-bottom: 2px solid #409EFF;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

/* Mejora de la tabla de precios */
.table {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-radius: 4px;
    overflow: hidden;
}

.table thead th {
    background-color: #409EFF;
    color: white;
    font-weight: 600;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .el-button-group {
        flex-direction: column;
    }

    .el-button-group .el-button {
        margin-bottom: 5px;
    }
}

/* Badge en botón de recientes */
.el-button .el-badge {
    vertical-align: middle;
    margin-left: 5px;
}

/* Mejora del icono de información */
.ml-1 {
    margin-left: 4px;
}

.ml-2 {
    margin-left: 8px;
}
</style>

<script>
    import ItemForm from '@views/items/form.vue'
    import LotsGroup from './inventory/lots_group.vue'
    import WarehousesDetail from './inventory/select_warehouses.vue'
    import SelectLotsForm from './inventory/lots.vue'

    export default {
        props: ['recordItem','showDialog', 'operationTypeId', 'currencyTypeIdActive', 'currencyTypeSymbolActive', 'exchangeRateSale', 'typeUser', 'isEditItemNote', 'configuration'],
        components: {ItemForm, LotsGroup, WarehousesDetail, SelectLotsForm},
        data() {
            return {
                loading_items: true,
                loading_search:false,
                titleAction: '',
                is_client:false,
                titleDialog: '',
                resource: 'co-documents',
                showDialogNewItem: false,
                has_list_prices: false,
                errors: {},
                form: {},
                all_items: [],
                items: [],
                operation_types: [],
                all_affectation_igv_types: [],
                affectation_igv_types: [],
                system_isc_types: [],
                discount_types: [],
                charge_types: [],
                attribute_types: [],
                use_price: 1,
                change_affectation_igv_type_id: false,
                activePanel: 0,
                total_item: 0,
                item_unit_types: [],
                showWarehousesDetail: false,
                warehousesDetail:[],
                showListStock:false,
                search_item_by_barcode:false,
                tax_included_in_price: false,
                isUpdateWarehouseId:null,
                showDialogLots: false,
                showDialogSelectLots: false,
                quickFilter: 'all', // Filtro rápido: all, stock, recent
                lots:[],
                all_taxes:[],
                taxes:[],
                items_aiu: [],
                barcodeInput: '', // Agrega esto para almacenar la entrada del código de barras
                currentSearchItems: [], //carga productos actuales en la busqueda
                localConfiguration: null,
            }
        },

        computed: {
            //mostrar el nombre del producto cuando se busca por codigo de barras
            currentProduct() {
                return this.items.find(item => item.id === this.form.item_id);
            },

            itemTaxes() {
                return this.taxes.filter(tax => !tax.is_retention);
            },

            retentiontaxes() {
                return this.taxes.filter(tax => tax.is_retention);
            },

            retentionSelected() {
            if (this.retention.retention_id == null) return { rate: 0 };

                return this.taxes.find(row => row.id == this.retention.retention_id);
            },

            showTotalAndSave() {
                return (
                    this.document.hasOwnProperty("items") && this.document.items.length > 0
                );
            },

            typeNoteDocuments() {
                return this.typeDocuments.filter(row => row.id != 1);
            },

            taxCheckboxLabel() {
                // Si la configuración ya está cargada...
                if (this.localConfiguration && typeof this.localConfiguration.item_tax_included !== 'undefined') {
                // Si item_tax_included es false: el precio actual ya trae IVA incluido.
                // Si es true: el precio base no incluye IVA (se sumará IVA en otro proceso).
                return this.localConfiguration.item_tax_included
                        ? "Precio más IVA"
                        : "Impuesto incluido en el precio.";
                }
                // Valor por defecto en caso de no tener la configuración (puedes ajustar este fallback)
                return "Impuesto incluido en el precio.";
            }
        },

        created() {
            this.initForm()
            this.$http.get(`/${this.resource}/item/tables`).then(response => {
               // console.log('tablas new edit')
                this.taxes = response.data.taxes;
                this.all_items = response.data.items
                this.loadingItems = false;
                this.$emit('items-loaded'); // Notifica al padre que ya terminó
                this.items_aiu = response.data.items_aiu
                this.filterItems()
            })

            this.$eventHub.$on('reloadDataItems', (item_id) => {
                this.reloadDataItems(item_id)
            })

            this.$eventHub.$on('selectWarehouseId', (warehouse_id) => {
                // console.log(warehouse_id)
                this.form.warehouse_id = warehouse_id
            })

              // Petición a configuración avanzada usando .then() en lugar de async/await
            this.$http.get('/co-advanced-configuration/record')
                .then(response => {
                this.localConfiguration = response.data.data;
                })
                .catch(error => {
                console.error("Error al obtener la configuración avanzada:", error);
                });
        },

        methods: {
            // Método que se llama cuando se escanea un código de barras
            async handleBarcodeScan() {
                    if (this.barcodeInput) {
                        await this.searchRemoteItems(this.barcodeInput);
                        const foundItem = this.items.find(item => item.internal_id === this.barcodeInput);

                        if (foundItem) {
                            // Si encuentra un ítem, actualiza form.item_id y lo añade a currentSearchItems si es necesario
                            this.form.item_id = foundItem.id;
                            if (!this.currentSearchItems.some(item => item.id === foundItem.id)) {
                                this.currentSearchItems.push(foundItem);
                            }
                            this.changeItem();
                        } else {
                            // Si no encuentra el producto, abre el modal para crear un nuevo producto
                            this.showDialogNewItem = true;
                        }

                        this.barcodeInput = ''; // Limpia el campo después del escaneo
                    }
            },

            async searchRemoteItems(input) {
                if (input.length > 2) {
                    this.loading_search = true;
                    let parameters = `input=${input}`;

                    await this.$http.get(`/${this.resource}/search-items/?${parameters}`)
                        .then(response => {
                            // CORRECCIÓN: Solo mostrar los resultados de la búsqueda, no mezclar con items anteriores
                            this.items = response.data.items;
                            this.currentSearchItems = response.data.items;
                            this.loading_search = false;
                        });
                } else if (input.length === 0) {
                    // Si el campo está vacío, restaurar según el filtro activo
                    this.applyQuickFilter();
                } else {
                    // Si hay menos de 3 caracteres, mantener items actuales
                    this.currentSearchItems = [...this.items];
                }
            },

            applyQuickFilter() {
                // Aplica filtro rápido a los items
                if (this.quickFilter === 'stock') {
                    // Mostrar solo productos con stock
                    this.items = this.all_items.filter(item => item.stock > 0);
                } else if (this.quickFilter === 'recent') {
                    // Mostrar productos recientes desde localStorage (persistente entre sesiones)
                    const recentItems = this.getRecentItemsFromStorage();
                    if (recentItems.length > 0) {
                        // Filtrar los items que están en la lista de recientes
                        const recentIds = recentItems.map(item => item.id);
                        this.items = this.all_items.filter(item => recentIds.includes(item.id));

                        // Ordenar según el orden en localStorage (más reciente primero)
                        this.items.sort((a, b) => {
                            return recentIds.indexOf(a.id) - recentIds.indexOf(b.id);
                        });
                    } else {
                        // Si no hay recientes guardados, mostrar los últimos 20 del sistema
                        this.items = [...this.all_items].slice(0, 20);
                    }
                } else {
                    // Mostrar todos
                    this.items = [...this.all_items];
                }
            },

            // Método para guardar un producto como reciente en localStorage
            saveRecentItem(item) {
                try {
                    // Obtener productos recientes actuales
                    let recentItems = this.getRecentItemsFromStorage();

                    // Crear objeto simplificado del item (solo datos necesarios)
                    const itemToSave = {
                        id: item.id,
                        internal_id: item.internal_id,
                        name: item.name,
                        full_description: item.full_description,
                        stock: item.stock,
                        sale_unit_price: item.sale_unit_price,
                        currency_type_symbol: item.currency_type_symbol,
                        timestamp: new Date().getTime() // Agregar timestamp
                    };

                    // Eliminar el item si ya existe (para moverlo al inicio)
                    recentItems = recentItems.filter(i => i.id !== item.id);

                    // Agregar el item al inicio
                    recentItems.unshift(itemToSave);

                    // Limitar a los últimos 50 productos
                    recentItems = recentItems.slice(0, 50);

                    // Guardar en localStorage
                    localStorage.setItem('recent_products', JSON.stringify(recentItems));
                } catch (error) {
                    console.error('Error guardando producto reciente:', error);
                }
            },

            // Método para obtener productos recientes desde localStorage
            getRecentItemsFromStorage() {
                try {
                    const stored = localStorage.getItem('recent_products');
                    if (stored) {
                        const recentItems = JSON.parse(stored);

                        // Filtrar items que tengan más de 90 días (limpieza automática)
                        const ninetyDaysAgo = new Date().getTime() - (90 * 24 * 60 * 60 * 1000);
                        const filteredItems = recentItems.filter(item => {
                            return !item.timestamp || item.timestamp > ninetyDaysAgo;
                        });

                        // Si se filtraron algunos, actualizar el localStorage
                        if (filteredItems.length !== recentItems.length) {
                            localStorage.setItem('recent_products', JSON.stringify(filteredItems));
                        }

                        return filteredItems;
                    }
                    return [];
                } catch (error) {
                    console.error('Error obteniendo productos recientes:', error);
                    return [];
                }
            },

            filterItems() {
                this.items = this.all_items
            },

            RateSelectedTax(tax_id) {
                if(tax_id != null)
                    return this.taxes.find(row => row.id == tax_id).rate;
                else
                    return 0
            },

            enabledSearchItemsBarcode(){

                if(this.search_item_by_barcode){

                    if (this.items.length == 1){

                        this.form.item_id = this.items[0].id
                        this.changeItem()
                    }
                }
            },
            filterMethod(query){

                let item = _.find(this.items, {'internal_id': query});

                if(item){
                    this.form.item_id = item.id
                    this.changeItem()
                }
                // console.log(item)
            },
            clickWarehouseDetail(){

                if(!this.form.item_id){
                    return this.$message.error('Seleccione un item');
                }

                let item = _.find(this.items, {'id': this.form.item_id});

                this.warehousesDetail = item.warehouses
                this.showWarehousesDetail = true
            },
            // filterItems(){
            //     this.items = this.items.filter(item => item.warehouses.length >0)
            // },
            initForm() {
                this.errors = {};

                this.form = {
                    id: null,
                    item_id: null,
                    item: {},
                    code: null,
                    discount: 0,
                    name: null,
                    price: null,
                    quantity: null,
                    notes: null,
                    subtotal: null,
                    tax: {},
                    tax_id: null,
                    total: 0,
                    total_tax: 0,
                    type_unit: {},
                    unit_type_id: null,
                    item_unit_types: [],
                    IdLoteSelected: null,
                };

                this.activePanel = 0;
                this.total_item = 0;
                this.item_unit_type = {};
                this.has_list_prices = false;
                this.tax_included_in_price = true;
            },

            async create() {
                console.log(this.recordItem)
                this.titleDialog = (this.recordItem) ? ' Editar Producto o Servicio' : ' Agregar Producto o Servicio';
                this.titleAction = (this.recordItem) ? ' Editar' : ' Agregar';
                // let operation_type = await _.find(this.operation_types, {id: this.operationTypeId})
                // this.affectation_igv_types = await _.filter(this.all_affectation_igv_types, {exportation: operation_type.exportation})
                if (this.recordItem) {
                    // Aquí asignas el ID del ítem a editar en form.item_id
                    this.form.item_id = this.recordItem.item_id;
                    await this.changeItem()
                    this.form.tax_id = this.recordItem.tax_id
                    this.form.quantity = this.recordItem.quantity
                    this.form.notes = this.recordItem.notes
                    this.form.price = this.recordItem.price
                    this.form.discount = this.recordItem.discount
                    this.form.warehouse_id = this.recordItem.warehouse_id
                    this.isUpdateWarehouseId = this.recordItem.warehouse_id
                    if(this.isEditItemNote){
                        this.form.item.currency_type_id = this.currencyTypeIdActive
                        this.form.item.currency_type_symbol = this.currencyTypeSymbolActive
                    }
                    this.calculateQuantity()
                }else{
                    this.isUpdateWarehouseId = null
                }
            },

            close() {
                this.initForm()
                this.$emit('update:showDialog', false)
            },

            async changeItem() {
                // Busca el ítem en la lista de ítems disponibles por su ID
                this.form.item = this.items.find(item => item.id === this.form.item_id);

                // Si no está en items actuales, buscar en all_items
                if (!this.form.item) {
                    this.form.item = this.all_items.find(item => item.id === this.form.item_id);
                }

                // Añade el ítem a editar a currentSearchItems si no está presente
                if (!this.currentSearchItems.some(item => item.id === this.form.item_id)) {
                    const itemToEdit = this.form.item || this.items.find(item => item.id === this.form.item_id);
                    if (itemToEdit) {
                        this.currentSearchItems.push(itemToEdit);
                    }
                }

                if (this.form.item) {
                    // NUEVO: Guardar producto como reciente en localStorage
                    this.saveRecentItem(this.form.item);

                    // Si se encuentra el ítem, actualiza los detalles en el formulario
                    this.form.item_unit_types = this.form.item.item_unit_types;
                    this.form.id = this.form.item_id;
                    this.form.unit_type_id = this.form.item.unit_type_id;
                    this.lots = this.form.item.lots;
                    // Validar si tax existe antes de acceder a sus propiedades
                    this.form.tax_id = (this.taxes.length > 0 && this.form.item.tax && this.form.item.tax.id) ? this.form.item.tax.id : null;
                    this.form.price = this.form.item.sale_unit_price;
                    this.form.quantity = 1;

                    // Cargar automáticamente la descripción del item en el campo notas
                    if (this.form.item.description) {
                        this.form.notes = this.form.item.description;
                    }

                    this.cleanTotalItem();
                    this.showListStock = true;
                    this.form.lots_group = this.form.item.lots_group;
                } else {
                    // Maneja el caso en que el ítem no se encuentra
                    console.log("Ítem no encontrado para la edición");
                }
            },

            getItemsAiu(detailAiu)
            {
                const context = this

                let items_a = this.items_aiu.filter( row => row.internal_id == 'aiu00001' || row.internal_id == 'aiu00002' || row.internal_id == 'aiu00003' )

                let data = items_a.map(row => {

                    let formaiu = context.getFormAiu()
                    const price = context.getPriceAiu(row.internal_id, detailAiu)

                    formaiu.item_id = row.id
                    formaiu.item = row

                    formaiu.unit_type_id = formaiu.item.unit_type_id
                    formaiu.item.sale_unit_price = price
                    formaiu.item_unit_types = _.find(context.items_aiu, {'id': formaiu.item_id}).item_unit_types
                    formaiu.id = formaiu.item_id
                    // Validar si tax existe antes de acceder a sus propiedades
                    formaiu.tax_id = (context.taxes.length > 0 && formaiu.item.tax && formaiu.item.tax.id) ? formaiu.item.tax.id: null
                    formaiu.price = price
                    formaiu.quantity = 1
                    formaiu.item.presentation = {};

                    formaiu.tax = _.find(context.taxes, {'id': formaiu.tax_id})

                    return formaiu

                })

                return data
            },
            getPriceAiu(internal_id, detailAiu)
            {
                const context = this
                let price = 0

                switch(internal_id)
                {
                    case 'aiu00001':
                        price = detailAiu.value_administartion
                    break;
                    case 'aiu00002':
                        price = detailAiu.value_sudden
                    break;
                    case 'aiu00003':
                        price = detailAiu.value_utility
                    break;
                }
                return price
            },
            getFormAiu()
            {
                return {
                    id: null,
                    item_id: null,
                    item: {},
                    code: null,
                    discount: 0,
                    name: null,
                    price: null,
                    quantity: null,
                    notes: null,
                    subtotal: null,
                    tax: {},
                    tax_id: null,
                    total: 0,
                    total_tax: 0,
                    type_unit: {},
                    unit_type_id: null,
                    item_unit_types: [],
                };
            },

            focusTotalItem(change) {
                if(!change && this.form.item.calculate_quantity) {
                    this.$refs.total_item.$el.getElementsByTagName('input')[0].focus()
                    this.total_item = this.form.unit_price_value
                }
            },

            calculateQuantity() {
                // debugger
                if(this.form.item.calculate_quantity) {
                    //console.log('entro')
                    this.form.quantity = _.round((this.total_item / this.form.price), 4)
                }
            },

            cleanTotalItem(){
                this.total_item = null
            },

            async clickAddItem() {
                if(this.form.item.lots_enabled){
                    if(!this.form.IdLoteSelected)
                        return this.$message.error('Debe seleccionar un lote.');
                }
                if (this.validateTotalItem().total_item)
                    return;
                if(null === this.form.tax_id)
                    this.form.tax = {'code': "ZZ", 'conversion': "100.00", 'id': 0, 'in_base': false, 'in_tax': null, 'is_fixed_value': false, 'is_percentage': true, 'is_retention': false, 'name': "EXCLUIDO", 'rate': "0.00", 'retention': 0, 'total': 0, 'type_tax': {'code': "ZZ", 'description': "Articulos Excluidos de Impuesto", 'id': 99, 'name': "EXCLUIDO"}}
                else
                    this.form.tax = _.find(this.taxes, {'id': this.form.tax_id})
                this.form.type_unit = this.form.item.type_unit
                this.form.item.presentation = this.item_unit_type;
                if (this.recordItem){
                    this.form.indexi = this.recordItem.indexi
                }
                let IdLoteSelected = this.form.IdLoteSelected
                let select_lots = await _.filter(this.form.item.lots, {'has_sale':true})
                let un_select_lots = await _.filter(this.form.item.lots, {'has_sale':false})
                if(this.form.item.series_enabled){
                    if(select_lots.length != this.form.quantity)
                        return this.$message.error('La cantidad de series seleccionadas son diferentes a la cantidad a vender');
                }
                this.form.IdLoteSelected = IdLoteSelected
//                console.log(this.form)
                this.$emit('add', this.form);
                if (this.recordItem){
                    this.close()
                }
                // Agrega estas líneas al final del método clickAddItem
                this.initForm(); // Reinicia el formulario
                this.$nextTick(() => {
                    if (this.$refs.barcodeInput) {
                    this.$refs.barcodeInput.focus(); // Mueve el foco al campo de entrada del código de barras
                    }
                });
                // let unit_price = (this.form.has_igv)?this.form.unit_price_value:this.form.unit_price_value*1.18;
                // this.form.input_unit_price_value = this.form.unit_price_value;
                // this.form.unit_price = unit_price;
                // this.form.item.unit_price = unit_price;
                // this.row = calculateRowItem(this.form, this.currencyTypeIdActive, this.exchangeRateSale);
                // this.row.edit = false;
                //this.initializeFields()
            },

            validateTotalItem(){
                this.errors = {}
                if(this.form.item.calculate_quantity){
                    if(this.total_item < 0.01)
                        this.$set(this.errors, 'total_item', ['total venta item debe ser mayor a 0.01']);
                }
                return this.errors
            },

            reloadDataItems(item_id) {
                if(!item_id){
                    this.$http.get(`/${this.resource}/table/items`).then((response) => {
                        this.items = response.data
                        this.form.item_id = item_id
                    })
                }
                else{
                    this.$http.get(`/${this.resource}/search/item/${item_id}`).then((response) => {
                        this.items = response.data.items
                        this.form.item_id = item_id
                        this.changeItem()
                    })
                }
            },

            change_price_tax_included()
            {
                if(parseFloat(this.form.price) == 0){
                    if(this.tax_included_in_price)
                        this.form.price = this.form.item.sale_unit_price * (1 + (this.RateSelectedTax(this.form.tax_id) / 100))
                    else
                        this.form.price = this.form.item.sale_unit_price
                }
                else{
                    if(parseFloat(this.form.price) > 0){

                        if(this.tax_included_in_price)
                            this.form.price = this.form.price * (1 + (this.RateSelectedTax(this.form.tax_id) / 100))
                        else
                            this.form.price = this.form.price / (1 + (this.RateSelectedTax(this.form.tax_id) / 100))
                    }
                }
            },
            selectedPrice(row)
            {

                // console.log(row)
                let valor = 0
                switch(row.price_default)
                {
                    case 1:
                        valor = row.price1
                        break
                    case 2:
                         valor = row.price2
                        break
                    case 3:
                         valor = row.price3
                        break

                }
                this.form.item_unit_type_id = row.id
                this.item_unit_type = row

                this.form.price = valor
                this.form.item.unit_type_id = row.unit_type_id
                // this.form.quantity = row.quantity_unit
                this.calculateQuantity()
                // console.log(this.form)
            },
            addRowLotGroup(id)
            {
                this.form.IdLoteSelected =  id
            },
            clickLotGroup()
            {
                this.showDialogLots = true
            },
            async clickSelectLots(){
                this.showDialogSelectLots = true
            },
            addRowSelectLot(lots){
                this.lots = lots
            },
        }
    }

</script>
