<template>
    <el-dialog :title="titleDialog" :visible="showDialog" @open="create" @close="close">
        <form autocomplete="off" @submit.prevent="clickAddItem">
            <div class="form-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.item_id}">
                            <label class="control-label">
                                Producto/Servicio
                                <a href="#" @click.prevent="showDialogNewItem = true">[+ Nuevo]</a>
                            </label>
                            <el-select
                                v-model="form.item_id"
                                @change="changeItem"
                                @focus="onSelectFocus"
                                filterable
                                remote
                                :remote-method="searchItems"
                                :loading="loadingItems"
                                placeholder="Buscar productos (escriba al menos 2 caracteres)..."
                                clearable
                                reserve-keyword>
                                <el-option v-for="option in items" :key="option.id" :value="option.id" :label="option.full_description"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.item_id" v-text="errors.item_id[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.tax_id}">
                            <label class="control-label">Impuesto</label>
                            <el-select v-model="form.tax_id"  filterable>
                                <el-option v-for="option in itemTaxes" :key="option.id" :value="option.id" :label="option.name"></el-option>
                            </el-select>
                            <!-- <el-checkbox :disabled="recordItem != null" v-model="change_tax_id">Editar</el-checkbox> -->
                            <small class="form-control-feedback" v-if="errors.tax_id" v-text="errors.tax_id[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group" :class="{'has-danger': errors.quantity}">
                            <label class="control-label">Cantidad</label>
                            <el-input-number v-model="form.quantity" :min="0.01"></el-input-number>
                            <small class="form-control-feedback" v-if="errors.quantity" v-text="errors.quantity[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group" :class="{'has-danger': errors.unit_price}">
                            <label class="control-label">Precio Unitario</label>
                            <el-input v-model="form.unit_price">
                                <template slot="prepend" v-if="form.item && form.item.currency_type_symbol">{{ form.item.currency_type_symbol }}</template>
                            </el-input>
                            <small class="form-control-feedback" v-if="errors.unit_price" v-text="errors.unit_price[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.warehouse_id}">
                            <label class="control-label">Almacén de destino</label>
                            <el-select v-model="form.warehouse_id"   filterable  >
                                <el-option v-for="option in warehouses" :key="option.id" :value="option.id" :label="option.description"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.warehouse_id" v-text="errors.warehouse_id[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-6 mt-2" v-if="form.item_id">
                        <div class="form-group" :class="{'has-danger': errors.lot_code}" v-if="form.item && form.item.lots_enabled">
                            <label class="control-label">
                                Código lote
                            </label>
                            <el-input v-model="lot_code" >
                                <!--<el-button slot="append" icon="el-icon-edit-outline"  @click.prevent="clickLotcode"></el-button> -->
                            </el-input>
                            <small class="form-control-feedback" v-if="errors.lot_code" v-text="errors.lot_code[0]"></small>
                        </div>
                    </div>
                    <div style="padding-top: 1%;" class="col-md-3" v-show="form.item_id">
                        <div class="form-group" :class="{'has-danger': errors.date_of_due}" v-if="form.item && form.item.lots_enabled">
                            <label class="control-label">Fec. Vencimiento</label>
                            <el-date-picker v-model="form.date_of_due" type="date" value-format="yyyy-MM-dd" :clearable="true"></el-date-picker>
                            <small class="form-control-feedback" v-if="errors.date_of_due" v-text="errors.date_of_due[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-3" v-show="form.item_id">  <br>
                        <div class="form-group" :class="{'has-danger': errors.lot_code}" v-if="form.item && form.item.series_enabled">
                            <label class="control-label">
                                <!-- <el-checkbox v-model="enabled_lots"  @change="changeEnabledPercentageOfProfit">Código lote</el-checkbox> -->
                                Ingrese series
                            </label>

                            <el-button style="margin-top:2%;" type="primary" icon="el-icon-edit-outline"  @click.prevent="clickLotcode"></el-button>

                            <small class="form-control-feedback" v-if="errors.lot_code" v-text="errors.lot_code[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="form-group"  :class="{'has-danger': errors.discount}">
                            <label class="control-label">Descuento</label>
                            <el-input v-model="form.discount"
                                min="0"
                                class="input-with-select"
                                :disabled="!form.item_id">
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

                    <div class="col-md-12"  v-if="form.item_unit_types.length > 0">
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

                    </div>
                </div>
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
<style>
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
                loadingItems: false,
                warehouses: [],
                lots: [],
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
                // Si la query es muy corta, limpiar items
                if (query && query.length < 2) {
                    this.items = [];
                    return;
                }

                this.loadingItems = true;

                // Intentamos usar el endpoint de búsqueda optimizada
                this.$http.get(`/${this.resource}/item/search`, {
                    params: {
                        q: query || '', // Permitir query vacío para items iniciales
                        limit: query ? 50 : 20 // Más resultados si hay búsqueda, menos para carga inicial
                    }
                }).then(response => {
                    this.items = response.data.items || response.data;
                    this.loadingItems = false;
                }).catch((error) => {
                    this.loadingItems = false;
                    console.error('Error en búsqueda de items:', error);

                    // Fallback solo si no hay query (para evitar cargar 6000+ items)
                    if (!query) {
                        this.items = [];
                    }
                });
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

                // Validar que se encontró el item
                if (!this.form.item) {
                    console.warn('Producto no encontrado en la lista de items');
                    this.form.item = {};
                    return;
                }

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
                // Cerrar el diálogo después de agregar/editar el item
                this.$emit('update:showDialog', false)
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
