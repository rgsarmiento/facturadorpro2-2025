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
                            <div class="form-group" :class="{'has-danger': errors.item_id}">
                                <label class="control-label">
                                    <strong><i class="el-icon-goods"></i> Producto/Servicio</strong>
                                    <a href="#" @click.prevent="showDialogNewItem = true" class="ml-2">
                                        <el-tag type="success" size="mini"><i class="el-icon-plus"></i> Nuevo</el-tag>
                                    </a>
                                </label>

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

                                <el-select
                                    v-model="form.item_id"
                                    @change="changeItem"
                                    @focus="onSelectFocus"
                                    filterable
                                    remote
                                    :remote-method="searchItems"
                                    :loading="loadingItems"
                                    placeholder="🔍 Buscar por nombre, código o descripción..."
                                    clearable
                                    reserve-keyword
                                    style="width: 100%;">
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
                                <small class="form-control-feedback" v-if="errors.tax_id" v-text="errors.tax_id[0]"></small>
                            </div>
                        </div>
                    </div>
                </el-card>

                <!-- Card de Detalles del Producto -->
                <el-card class="mb-3" shadow="hover">
                    <div slot="header" class="clearfix">
                        <i class="el-icon-shopping-cart-2"></i> <strong>Detalles de Compra</strong>
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
                                    controls-position="right"
                                    style="width: 100%;">
                                </el-input-number>
                                <small class="form-control-feedback" v-if="errors.quantity" v-text="errors.quantity[0]"></small>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="form-group" :class="{'has-danger': errors.unit_price}">
                                <label class="control-label">
                                    <i class="el-icon-sell"></i> Precio Unitario
                                    <el-tooltip content="Precio de compra por unidad" placement="top">
                                        <i class="el-icon-question" style="cursor: help; color: #909399;"></i>
                                    </el-tooltip>
                                </label>
                                <el-input
                                    v-model="form.unit_price"
                                    type="number"
                                    step="0.01">
                                    <template slot="prepend" v-if="form.item && form.item.currency_type_symbol">{{ form.item.currency_type_symbol }}</template>
                                </el-input>
                                <small class="form-control-feedback" v-if="errors.unit_price" v-text="errors.unit_price[0]"></small>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="form-group" :class="{'has-danger': errors.discount}">
                                <label class="control-label">
                                    <i class="el-icon-price-tag"></i> Descuento
                                    <el-tooltip content="Descuento aplicado a la compra" placement="top">
                                        <i class="el-icon-question" style="cursor: help; color: #909399;"></i>
                                    </el-tooltip>
                                </label>
                                <el-input v-model="form.discount"
                                    min="0"
                                    class="input-with-select"
                                    :disabled="!form.item_id"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00">
                                    <el-select v-model="form.discount_type"
                                        slot="prepend"
                                        :disabled="!form.item_id">
                                        <el-option label="%" value="percentage"></el-option>
                                        <el-option :label="form.item && form.item.currency_type_symbol ? form.item.currency_type_symbol : '$'" value="amount"></el-option>
                                    </el-select>
                                </el-input>
                                <small class="form-control-feedback" v-if="errors.discount" v-text="errors.discount[0]"></small>
                            </div>
                        </div>

                        <!-- Resumen visual del subtotal -->
                        <div class="col-md-3 col-sm-6" v-if="form.item_id && form.quantity && form.unit_price">
                            <div class="form-group">
                                <label class="control-label">
                                    <i class="el-icon-wallet"></i> Subtotal
                                </label>
                                <el-alert
                                    :title="`${form.item && form.item.currency_type_symbol || ''} ${calculateSubtotal().toFixed(2)}`"
                                    type="success"
                                    :closable="false"
                                    style="padding: 10px; font-size: 18px; font-weight: bold;">
                                </el-alert>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group" :class="{'has-danger': errors.warehouse_id}">
                                <label class="control-label">
                                    <i class="el-icon-box"></i> Almacén de destino
                                </label>
                                <el-select v-model="form.warehouse_id" filterable placeholder="Seleccionar almacén" style="width: 100%;">
                                    <el-option v-for="option in warehouses" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.warehouse_id" v-text="errors.warehouse_id[0]"></small>
                            </div>
                        </div>

                        <!-- Lotes y Series -->
                        <div class="col-md-6" v-if="form.item_id && form.item && form.item.lots_enabled">
                            <div class="form-group" :class="{'has-danger': errors.lot_code}">
                                <label class="control-label">
                                    <i class="el-icon-tickets"></i> Código de lote
                                </label>
                                <el-input v-model="lot_code" placeholder="Ingrese código de lote"></el-input>
                                <small class="form-control-feedback" v-if="errors.lot_code" v-text="errors.lot_code[0]"></small>
                            </div>
                        </div>
                    </div>

                    <div class="row" v-if="form.item_id && form.item">
                        <div class="col-md-4" v-if="form.item.lots_enabled">
                            <div class="form-group" :class="{'has-danger': errors.date_of_due}">
                                <label class="control-label">
                                    <i class="el-icon-date"></i> Fecha de vencimiento
                                </label>
                                <el-date-picker
                                    v-model="form.date_of_due"
                                    type="date"
                                    value-format="yyyy-MM-dd"
                                    :clearable="true"
                                    placeholder="Seleccionar fecha"
                                    style="width: 100%;">
                                </el-date-picker>
                                <small class="form-control-feedback" v-if="errors.date_of_due" v-text="errors.date_of_due[0]"></small>
                            </div>
                        </div>

                        <div class="col-md-4" v-if="form.item.series_enabled">
                            <div class="form-group" :class="{'has-danger': errors.lot_code}">
                                <label class="control-label">
                                    <i class="el-icon-document"></i> Series
                                </label>
                                <el-button
                                    type="warning"
                                    icon="el-icon-edit-outline"
                                    @click.prevent="clickLotcode"
                                    style="width: 100%;"
                                    plain>
                                    Ingresar Series
                                </el-button>
                                <small class="form-control-feedback" v-if="errors.lot_code" v-text="errors.lot_code[0]"></small>
                            </div>
                        </div>
                    </div>
                </el-card>

                    <template v-if="form.item_unit_types.length > 0">
                        <div style="margin:3px" class="table-responsive">
                            <h5 class="separator-title">
                                Listado de Precios
                                <el-tooltip class="item" effect="dark" content="Aplica para realizar compra/venta en presentacion de diferentes precios y/o cantidades" placement="top">
                                    <i class="fa fa-info-circle"></i>
                                </el-tooltip>
                            </h5>
                            <table class="table">
                            <thead>
                            <tr>
                                <th class="text-center">Unidad</th>
                                <th class="text-center">Descripción</th>
                                <th class="text-center">Factor</th>

                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(row, index) in form.item_unit_types" :key="index">

                                    <td class="text-center">{{row.unit_type && row.unit_type.name ? row.unit_type.name : 'N/A'}}</td>
                                    <td class="text-center">{{row.description}}</td>
                                    <td class="text-center">{{row.quantity_unit}}</td>

                                    <td class="series-table-actions text-right">
                                       <button type="button" class="btn waves-effect waves-light btn-xs btn-success" @click.prevent="selectedPrice(row)">
                                            <i class="el-icon-check"></i>
                                        </button>
                                    </td>


                            </tr>
                            </tbody>
                        </table>

                        </div>
                    </template>
            </div>

            <div class="form-actions text-right pt-2">
                <el-button @click.prevent="close()">Cerrar</el-button>
                <el-button type="primary" native-type="submit" :disabled="!form.item_id">{{titleAction}}</el-button>
            </div>
        </form>
        <item-form :showDialog.sync="showDialogNewItem"
                   :external="true"></item-form>

        <lots-form
            :showDialog.sync="showDialogLots"
            :stock="form.quantity"
            :lots="lots"
            @addRowLot="addRowLot">
        </lots-form>

    </el-dialog>
</template>
<style scoped>
/* Estilos profesionales para el modal de compras */
.el-card {
    border-radius: 10px;
    transition: all 0.3s ease;
    animation: fadeIn 0.4s ease-in;
}

.el-card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
    transform: translateY(-2px);
}

.el-card__header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    padding: 12px 20px;
    border-radius: 10px 10px 0 0;
}

.el-card__header i {
    margin-right: 8px;
    font-size: 18px;
}

.el-button-group {
    display: flex;
    gap: 0;
    margin-bottom: 12px;
}

.el-button-group .el-button {
    flex: 1;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.el-button-group .el-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.el-button-group .el-button i {
    margin-right: 6px;
}

.control-label {
    font-weight: 600;
    color: #606266;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.control-label i {
    color: #909399;
    cursor: help;
}

.el-alert {
    border-radius: 8px;
    margin-top: 15px;
    animation: fadeIn 0.3s ease-in;
}

.el-badge__content {
    background-color: #f56c6c;
    border-radius: 10px;
    color: #fff;
    display: inline-block;
    font-size: 11px;
    height: 16px;
    line-height: 16px;
    padding: 0 5px;
    text-align: center;
    white-space: nowrap;
    border: 1px solid #fff;
}

.el-select-dropdown {
    max-width: 80% !important;
    margin-right: 5% !important;
}

.input-with-select .el-select .el-input {
    width: 50px;
}

.input-with-select .el-select .el-input .el-input__inner {
    padding-right: 10px;
}

.el-tag {
    margin-left: 8px;
    font-size: 11px;
    padding: 0 8px;
    border-radius: 4px;
}

.el-option strong {
    color: #409EFF;
    font-weight: 600;
}

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

/* Responsive */
@media (max-width: 768px) {
    .el-dialog {
        width: 98% !important;
        margin-top: 2vh !important;
    }

    .el-button-group {
        flex-direction: column;
    }

    .el-card {
        margin-bottom: 15px;
    }
}
</style>
<script>

    import itemForm from '../../items/form.vue'
    import {calculateRowItem} from '../../../../helpers/functions'
    import LotsForm from '../../items/partials/lots.vue'

    export default {
        props: ['showDialog', 'currencyTypeIdActive', 'exchangeRateSale', 'recordItem', 'taxes'],
        components: {itemForm, LotsForm},
        data() {
            return {
                titleDialog: 'Agregar Producto o Servicio',
                showDialogLots:false,
                resource: 'purchases',
                showDialogNewItem: false,
                errors: {},
                form: {},
                items: [],
                all_items: [], // Para almacenar todos los items
                loadingItems: false,
                warehouses: [],
                lots: [],
                quickFilter: 'all', // Filtro rápido: all, stock, recent
                affectation_igv_types: [],
                system_isc_types: [],
                discount_types: [],
                charge_types: [],
                attribute_types: [],
                use_price: 1,
                lot_code: null,
                change_affectation_igv_type_id: false,
                all_taxes:[],
                localTaxes:[],
                titleAction: '',
            }
        },
        computed: {
            itemTaxes() {
                console.log('itemTaxes computed called');
                console.log('itemTaxes - Props taxes:', this.taxes);
                console.log('itemTaxes - Local taxes:', this.localTaxes);

                // Priorizar los impuestos pasados como prop (del componente padre)
                let taxesSource = [];

                if (this.taxes && Array.isArray(this.taxes) && this.taxes.length > 0) {
                    taxesSource = this.taxes;
                    console.log('itemTaxes - Using props taxes');
                } else if (this.localTaxes && Array.isArray(this.localTaxes) && this.localTaxes.length > 0) {
                    taxesSource = this.localTaxes;
                    console.log('itemTaxes - Using local taxes');
                } else {
                    console.log('itemTaxes - No taxes available');
                    return [];
                }

                const filteredTaxes = taxesSource.filter(tax => !tax.is_retention);
                console.log('itemTaxes - Filtered taxes:', filteredTaxes);
                return filteredTaxes;
            },
        },
        watch: {
            // Detectar cuando se abre el modal
            showDialog(newVal, oldVal) {
                if (newVal && !oldVal) {
                    console.log('Modal opened, taxes from props:', this.taxes);
                    console.log('Modal opened, recordItem:', this.recordItem);
                    // Forzar recálculo del computed
                    this.$nextTick(() => {
                        this.$forceUpdate();
                    });
                }
            },
            // Detectar cuando cambian los impuestos pasados como prop
            taxes: {
                handler(newTaxes, oldTaxes) {
                    console.log('Taxes prop changed:', newTaxes);
                    if (newTaxes && newTaxes.length > 0) {
                        this.$nextTick(() => {
                            this.$forceUpdate();
                        });
                    }
                },
                immediate: true,
                deep: true
            },
            'form.tax_id': function(newVal, oldVal) {
                console.log('form.tax_id changed from', oldVal, 'to', newVal);
                console.log('itemTaxes after tax_id change:', this.itemTaxes);
            }
        },
        created() {
            this.initForm()
            // Solo cargamos warehouses y taxes para mejorar performance
            this.$http.get(`/${this.resource}/warehouses-taxes`).then(response => {
                this.warehouses = response.data.warehouses
                this.localTaxes = response.data.taxes;
            }).catch(() => {
                // Fallback: si no existe el nuevo endpoint, usar el anterior sin cargar items
                console.warn('Usando fallback para cargar warehouses y taxes');
                this.$http.get(`/${this.resource}/item/tables`).then(response => {
                    this.warehouses = response.data.warehouses
                    this.localTaxes = response.data.taxes;
                    // NO cargamos items para evitar timeout
                })
            })

            this.$eventHub.$on('reloadDataItems', (item_id) => {
                this.reloadDataItems(item_id)
            });

            // Escuchar evento para recargar toda la lista de items
            this.$eventHub.$on('reloadAllItems', () => {
                console.log('Recargando lista completa de items');
                this.loadPopularItems();
            });
        },
        methods: {
            addRowLot(lots){
                this.lots = lots
            },
            clickLotcode(){
                // if(this.form.stock <= 0)
                //     return this.$message.error('El stock debe ser mayor a 0')

                this.showDialogLots = true
            },
            filterItems(){
                this.items = this.items.filter(item => item.warehouses.length >0)
            },
            searchItems(query) {
                if (query && query.length >= 2) {
                    // Búsqueda activa
                    this.loadingItems = true;

                    this.$http.get(`/${this.resource}/item/search`, {
                        params: {
                            q: query,
                            limit: 50
                        }
                    }).then(response => {
                        // CORRECCIÓN: Solo mostrar resultados de búsqueda
                        this.items = response.data.items || response.data;
                        // Actualizar all_items con los resultados para que los filtros funcionen
                        if (this.items.length > 0) {
                            // Combinar con all_items existentes sin duplicados
                            const newItems = this.items.filter(item =>
                                !this.all_items.some(existing => existing.id === item.id)
                            );
                            this.all_items = [...this.all_items, ...newItems];
                        }
                        this.loadingItems = false;
                    }).catch((error) => {
                        this.loadingItems = false;
                        console.error('Error en búsqueda de items:', error);
                    });
                } else if (!query || query.length === 0) {
                    // Si está vacío, restaurar según filtro activo
                    this.applyQuickFilter();
                } else {
                    // Menos de 2 caracteres, mantener items actuales
                    this.loadingItems = false;
                }
            },

            applyQuickFilter() {
                // Aplica filtro rápido a los items
                if (this.quickFilter === 'stock') {
                    // Mostrar solo productos con stock
                    this.items = this.all_items.filter(item => item.stock > 0);
                } else if (this.quickFilter === 'recent') {
                    // Mostrar productos recientes desde localStorage
                    const recentItems = this.getRecentItemsFromStorage();
                    if (recentItems.length > 0) {
                        const recentIds = recentItems.map(item => item.id);
                        this.items = this.all_items.filter(item => recentIds.includes(item.id));

                        // Ordenar según el orden en localStorage (más reciente primero)
                        this.items.sort((a, b) => {
                            return recentIds.indexOf(a.id) - recentIds.indexOf(b.id);
                        });
                    } else {
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
                    let recentItems = this.getRecentItemsFromStorage();

                    const itemToSave = {
                        id: item.id,
                        internal_id: item.internal_id,
                        name: item.name,
                        full_description: item.full_description,
                        stock: item.stock,
                        sale_unit_price: item.sale_unit_price,
                        currency_type_symbol: item.currency_type_symbol,
                        timestamp: new Date().getTime()
                    };

                    recentItems = recentItems.filter(i => i.id !== item.id);
                    recentItems.unshift(itemToSave);
                    recentItems = recentItems.slice(0, 50);

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
                        const ninetyDaysAgo = new Date().getTime() - (90 * 24 * 60 * 60 * 1000);
                        const filteredItems = recentItems.filter(item => {
                            return !item.timestamp || item.timestamp > ninetyDaysAgo;
                        });

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

            calculateSubtotal() {
                if (!this.form.quantity || !this.form.unit_price) return 0;

                let subtotal = this.form.quantity * this.form.unit_price;

                // Aplicar descuento
                if (this.form.discount) {
                    const discount = parseFloat(this.form.discount) || 0;
                    if (this.form.discount_type === 'percentage') {
                        subtotal = subtotal - (subtotal * discount / 100);
                    } else {
                        subtotal = subtotal - discount;
                    }
                }

                return subtotal;
            },

            onSelectFocus() {
                // Cargar algunos items populares cuando se hace focus
                if (this.items.length === 0) {
                    this.loadPopularItems();
                }
            },
            onSelectClear() {
                // Limpiar items cuando se borra la selección
                this.items = [];
            },
            loadPopularItems() {
                // Cargar los primeros 20 items usando el endpoint de búsqueda con query vacío
                this.loadingItems = true;
                this.$http.get(`/${this.resource}/item/search`, {
                    params: {
                        q: '', // Query vacío para obtener los primeros items
                        limit: 20
                    }
                }).then(response => {
                    this.items = response.data.items || [];
                    this.all_items = [...this.items]; // Guardar copia para filtros
                    this.loadingItems = false;
                }).catch((error) => {
                    this.loadingItems = false;
                    console.warn('Error cargando items:', error);
                });
            },
            loadAllItems() {
                // Método para cargar todos los items cuando sea necesario
                this.loadingItems = true;
                this.$http.get(`/${this.resource}/item/tables`).then(response => {
                    this.items = response.data.items;
                    this.loadingItems = false;
                });
            },
            initForm() {
                this.errors = {}
                this.form = {
                    item_id: null,
                    warehouse_id: 1,
                    warehouse_description: null,
                    item: {},
                    quantity: 1,
                    unit_price: 0,
                    item_unit_types: [],
                    lot_code:null,
                    date_of_due: null,
                    subtotal: null,
                    tax: {},
                    tax_id: null,
                    total: 0,
                    total_tax: 0,
                    type_unit: {},
                    discount: 0,
                    unit_type_id: null,
                    lots: [],
                    discount_type: 'percentage',
                    discount_percentage: 0,
                }

                this.item_unit_type = {};
                this.lots = []
                this.lot_code = null
            },
            async create() {
                this.titleDialog = (this.recordItem) ? ' Editar Producto o Servicio' : ' Agregar Producto o Servicio';
                this.titleAction = (this.recordItem) ? ' Editar' : ' Agregar';

                if (this.recordItem) {
                    try {
                        // Obtener el item_id de recordItem
                        this.form.item_id = this.recordItem.item_id;

                        // Esperar un tick para que Vue procese el cambio en el v-model del select
                        await this.$nextTick();

                        // Si hay un item_id válido, asegurar que esté disponible
                        if (this.form.item_id) {
                            // Verificar si el item está en la lista actual
                            let existingItem = this.items.find(item => item.id === this.form.item_id);
                            if (!existingItem) {
                                console.log(`Item ID ${this.form.item_id} no encontrado en lista, recargando...`);
                                // Si no está, intentar recargarlo específicamente
                                await this.reloadDataItems(this.form.item_id);
                                // Esperar un tick para asegurar que la lista se actualice
                                await this.$nextTick();
                                // Verificar de nuevo después de recargar
                                existingItem = this.items.find(item => item.id === this.form.item_id);
                            }

                            // Si después de recargar aún no existe, usar los datos directamente del recordItem
                            if (!existingItem && this.recordItem.item) {
                                console.log('Item no encontrado después de recargar, usando datos del recordItem');
                                this.form.item = this.recordItem.item;
                            }
                        }

                        // Llamar changeItem para procesar el item seleccionado
                        this.changeItem();

                        // Esperar a que Vue procese los cambios en changeItem
                        await this.$nextTick();

                        // Ahora cargar los datos del recordItem
                        this.form.quantity = this.recordItem.quantity || 0;
                        this.form.unit_price = this.recordItem.unit_price || 0;
                        this.form.discount_type = this.recordItem.discount_type || 'percentage';
                        this.form.warehouse_id = this.recordItem.warehouse_id || null;

                        if(this.form.discount_type == 'percentage') {
                            this.form.discount = this.recordItem.discount_percentage || 0;
                        } else {
                            this.form.discount = this.recordItem.discount || 0;
                        }

                        // Asignar tax_id desde el recordItem
                        if (this.recordItem.tax_id_mapped) {
                            this.form.tax_id = this.recordItem.tax_id_mapped;
                            console.log('Tax ID assigned from recordItem:', this.recordItem.tax_id_mapped);
                        } else if (this.recordItem.tax_id) {
                            this.form.tax_id = this.recordItem.tax_id;
                            console.log('Tax ID assigned from recordItem (direct):', this.recordItem.tax_id);
                        }

                        // Si recordItem tiene lotes, cargarlos
                        if (this.recordItem.lots) {
                            this.lots = this.recordItem.lots;
                        }

                        // Si recordItem tiene lot_code, cargarlo
                        if (this.recordItem.lot_code) {
                            this.lot_code = this.recordItem.lot_code;
                        }

                        // Si recordItem tiene date_of_due, cargarlo
                        if (this.recordItem.date_of_due) {
                            this.form.date_of_due = this.recordItem.date_of_due;
                        }

                    } catch (error) {
                        console.error('Error al cargar datos del item para edición:', error);
                        this.$message.error('Error al cargar los datos del item');
                    }
                }
            },
            close() {
                this.initForm()
                // Limpiar la lista de items para forzar nueva búsqueda al abrir de nuevo
                this.items = []
                this.$emit('update:showDialog', false)
            },
            selectedPrice(row)
            {

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

                this.form.unit_price = valor
                this.form.item.unit_type_id = row.unit_type_id
            },
            changeItem() {
                if (!this.form.item_id) {
                    this.form.item = {};
                    return;
                }

                this.form.item = _.find(this.items, {'id': this.form.item_id})

                // Si no está en items actuales, buscar en all_items
                if (!this.form.item) {
                    this.form.item = _.find(this.all_items, {'id': this.form.item_id})
                }

                // Validar que se encontró el item
                if (!this.form.item) {
                    console.warn('Producto no encontrado en la lista de items');
                    this.form.item = {};
                    return;
                }

                // NUEVO: Guardar producto como reciente en localStorage
                this.saveRecentItem(this.form.item);

                // Asegurar que el item tenga unit_type con propiedades por defecto
                if (!this.form.item.unit_type) {
                    this.form.item.unit_type = {name: 'Unidad', id: null};
                }

                this.form.unit_price = this.form.item.purchase_unit_price || 0
                // this.form.affectation_igv_type_id = this.form.item.purchase_affectation_igv_type_id
                this.form.item_unit_types = this.form.item.item_unit_types || []

                this.form.unit_type_id = this.form.item.unit_type_id
                this.form.tax_id = (this.taxes && this.taxes.length > 0) ? this.form.item.purchase_tax_id: null

            },
            async clickAddItem() {
                // Validar que se ha seleccionado un producto
                if (!this.form.item_id) {
                    return this.$message.error('Por favor seleccione un producto');
                }

                if (!this.form.item || Object.keys(this.form.item).length === 0) {
                    return this.$message.error('Información del producto no disponible');
                }

                if(this.form.item.lots_enabled){

                    if(!this.lot_code)
                        return this.$message.error('Código de lote es requerido');

                    if(!this.form.date_of_due)
                        return this.$message.error('Fecha de vencimiento es requerido si lotes esta habilitado.');

                }

                if(this.form.item.series_enabled)
                {

                    if(this.lots.length > this.form.quantity)
                        return this.$message.error('La cantidad de series registradas es superior al stock');

                    if(this.lots.length != this.form.quantity)
                        return this.$message.error('La cantidad de series registradas son diferentes al stock');
                }

                let date_of_due = this.form.date_of_due

                this.form.tax = _.find(this.taxes, {'id': this.form.tax_id}) || {name: 'Sin impuesto', id: null}
                this.form.type_unit = (this.form.item && this.form.item.type_unit) ? this.form.item.type_unit : {}

                if (this.form.item) {
                    this.form.item.unit_price = this.form.unit_price
                    this.form.item.presentation = this.item_unit_type;
                }


                this.form.lot_code = await this.lot_code
                this.form.lots = await this.lots

                this.form = this.changeWarehouse(this.form)

                this.form.date_of_due = date_of_due
                // console.log(this.form)

                // Determinar si estamos en modo edición
                const isEditMode = this.recordItem !== null && this.recordItem !== undefined;

                if (this.recordItem)
                {
                    this.form.indexi = this.recordItem.indexi
                }

                if(this.form.discount_type == 'percentage') {
                    this.form.discount_percentage = this.form.discount
                }

                // this.initializeFields()
                this.$emit('add', this.form)
                this.initForm()

                // Solo cerrar el diálogo si estamos en modo edición
                if (isEditMode) {
                    this.$emit('update:showDialog', false)
                }
            },
            changeWarehouse(form){
                let warehouse = _.find(this.warehouses,{'id':this.form.warehouse_id})
                if (warehouse) {
                    form.warehouse_id = warehouse.id
                    form.warehouse_description = warehouse.description
                } else {
                    console.warn('Warehouse no encontrado con ID:', this.form.warehouse_id);
                    // Mantener el ID pero sin descripción si no se encuentra
                    form.warehouse_id = this.form.warehouse_id
                    form.warehouse_description = null
                }
                return form
            },
            async reloadDataItems(item_id) {
                // Buscar solo el item específico para evitar cargar todos
                try {
                    const response = await this.$http.get(`/${this.resource}/item/search`, {
                        params: {
                            item_id: item_id,
                            limit: 1
                        }
                    });

                    if (response.data.items && response.data.items.length > 0) {
                        // Agregar el item específico si no existe ya
                        const existingItem = this.items.find(item => item.id === item_id);
                        if (!existingItem) {
                            this.items.push(response.data.items[0]);
                            console.log(`Item agregado a la lista: ${response.data.items[0].description}`);
                        } else {
                            // Actualizar el item existente con datos frescos
                            const index = this.items.findIndex(item => item.id === item_id);
                            if (index !== -1) {
                                this.items.splice(index, 1, response.data.items[0]);
                                console.log(`Item actualizado en la lista: ${response.data.items[0].description}`);
                            }
                        }

                        // Si el modal está abierto y este es el item actual, actualizar el form
                        if (this.showDialog && this.recordItem && this.recordItem.id === item_id) {
                            this.form.item_id = item_id;
                            this.changeItem();
                            console.log(`Form actualizado con item ID: ${item_id}`);
                        }

                        return true; // Éxito
                    }
                } catch (error) {
                    // Fallback: recargar todos los items populares para asegurar que esté disponible
                    console.log('Error buscando item específico, recargando lista completa');
                    this.loadPopularItems();
                    return false; // Error
                }
            },
        }
    }

</script>
