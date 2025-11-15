<template>
    <div class="card mb-0 pt-2 pt-md-0 invoice-form">
        <div class="card-header bg-info d-flex justify-content-between align-items-center">
            <h4 class="mb-0 text-white"><i class="fas fa-shopping-bag"></i> Nueva Compra</h4>
            <el-button type="primary" size="medium" @click="showDialogXMLDian = true" class="bg-white text-info border-white">
                <i class="fas fa-file-import mr-2"></i>
                Causar Compra Desde XML DIAN
            </el-button>
        </div>
        <div class="card-body">
            <form autocomplete="off" @submit.prevent="submit">
                <div class="form-body">

                    <!-- Sección 1: Datos del Comprobante -->
                    <div class="form-section">
                        <h5 class="section-header"><i class="fa fa-file-invoice"></i> Datos del Comprobante</h5>
                        <div class="row">
                         <div class="col-lg-4">
                            <div class="form-group" :class="{'has-danger': errors.document_type_id}">
                                <label class="control-label">Tipo comprobante</label>
                                <el-select v-model="form.document_type_id" @change="changeDocumentType">
                                    <el-option v-for="option in document_types" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.document_type_id" v-text="errors.document_type_id[0]"></small>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group" :class="{'has-danger': errors.series}">
                                <label class="control-label">Serie <span class="text-danger">*</span></label>
                                <el-input v-model="form.series" :maxlength="4"   @input="inputSeries"></el-input>

                                <small class="form-control-feedback" v-if="errors.series" v-text="errors.series[0]"></small>
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <div class="form-group" :class="{'has-danger': errors.number}">
                                <label class="control-label">Número <span class="text-danger">*</span></label>
                                <el-input v-model="form.number"></el-input>

                                <small class="form-control-feedback" v-if="errors.number" v-text="errors.number[0]"></small>
                            </div>
                        </div>



                        <div class="col-lg-2">
                            <div class="form-group" :class="{'has-danger': errors.date_of_issue}">
                                <label class="control-label">Fec Emisión</label>
                                <el-date-picker v-model="form.date_of_issue" type="date" value-format="yyyy-MM-dd" :clearable="false" @change="changeDateOfIssue"></el-date-picker>
                                <small class="form-control-feedback" v-if="errors.date_of_issue" v-text="errors.date_of_issue[0]"></small>
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <div class="form-group" :class="{'has-danger': errors.date_of_due}">
                                <label class="control-label">Fec. Vencimiento</label>
                                <el-date-picker v-model="form.date_of_due" type="date" value-format="yyyy-MM-dd" :clearable="false"></el-date-picker>
                                <small class="form-control-feedback" v-if="errors.date_of_due" v-text="errors.date_of_due[0]"></small>
                            </div>
                        </div>
                        </div>
                    </div>

                    <!-- Sección 2: Proveedor y Configuración -->
                    <div class="form-section">
                        <h5 class="section-header"><i class="fa fa-truck"></i> Proveedor y Configuración</h5>
                        <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group" :class="{'has-danger': errors.supplier_id}">
                                <label class="control-label">
                                    Proveedor
                                    <el-tooltip class="item" effect="dark" content="Buscar proveedor existente o crear uno nuevo" placement="top">
                                        <a href="#" @click.prevent="showDialogNewPerson = true" class="cliente-link">
                                            <i class="fas fa-search-plus search-icon"></i> [+ Buscar o Crear Proveedor]
                                        </a>
                                    </el-tooltip>
                                </label>
                                <el-select v-model="form.supplier_id" filterable @change="changeSupplier" ref="select_person" @keyup.native="keyupSupplier" @keyup.enter.native="keyupEnterSupplier">
                                    <el-option v-for="option in suppliers" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.supplier_id" v-text="errors.supplier_id[0]"></small>
                            </div>
                        </div>
                        <!-- <div class="col-lg-3">
                            <div class="form-group" :class="{'has-danger': errors.payment_method_type_id}">
                                <label class="control-label">
                                    Forma de pago
                                </label>
                                <el-select v-model="form.payment_method_type_id" filterable @change="changePaymentMethodType">
                                    <el-option v-for="option in payment_method_types" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.payment_method_type_id" v-text="errors.payment_method_type_id[0]"></small>
                            </div>
                        </div> -->
                        <div class="col-lg-2">
                            <div class="form-group" :class="{'has-danger': errors.currency_id}">
                                <label class="control-label">Moneda</label>
                                <el-select v-model="form.currency_id" @change="changeCurrencyType" filterable>
                                    <el-option v-for="option in currencies" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.currency_id" v-text="errors.currency_id[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-8 mt-4">
                            <div class="form-group" >
                                <el-checkbox v-model="form.has_client" @change="changeHasClient">¿Desea agregar el cliente para esta compra?</el-checkbox>
                            </div>
                        </div>

                        <div class="col-md-8 mt-2 mb-2">
                            <div class="form-group" >
                                <el-checkbox v-model="form.has_payment" @change="changeHasPayment">¿Desea agregar pagos a esta compra?</el-checkbox>
                            </div>
                        </div>

                        <div class="col-lg-6 col-md-6" v-if="form.has_client">
                            <div class="form-group">
                                <label class="control-label">
                                    Clientes
                                </label>

                                <el-select v-model="form.customer_id" filterable remote  popper-class="el-select-customers"  clearable
                                    placeholder="Nombre o número de documento"
                                    :remote-method="searchRemotePersons"
                                    :loading="loading_search">
                                    <el-option v-for="option in customers" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                </el-select>

                            </div>
                        </div>
                        </div>
                    </div>

                    <!-- Sección 3: Pagos -->
                    <div class="form-section" v-if="form.has_payment">
                        <h5 class="section-header"><i class="fa fa-credit-card"></i> Información de Pagos</h5>
                        <div class="row">
                        <div class="col-md-12 col-lg-12">

                            <table>
                                <thead>
                                    <tr width="100%">
                                        <th v-if="form.payments.length>0" class="pb-2">Forma de pago</th>
                                        <th v-if="form.payments.length>0" class="pb-2">Destino</th>
                                        <th v-if="form.payments.length>0" class="pb-2">Referencia</th>
                                        <th v-if="form.payments.length>0" class="pb-2">Monto</th>
                                        <th width="15%"><a href="#" @click.prevent="clickAddPayment" class="text-center font-weight-bold text-info">[+ Agregar]</a></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, index) in form.payments" :key="index">
                                        <td>
                                            <div class="form-group mb-2 mr-2">
                                                <el-select v-model="row.payment_method_type_id" @change="changePaymentMethodType(true,index)">
                                                    <el-option v-for="option in payment_method_types" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                                </el-select>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-group mb-2 mr-2">
                                                <el-select v-model="row.payment_destination_id" filterable >
                                                    <el-option v-for="option in payment_destinations" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                                </el-select>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-group mb-2 mr-2"  >
                                                <el-input v-model="row.reference"></el-input>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-group mb-2 mr-2" >
                                                <el-input v-model="row.payment"></el-input>
                                            </div>
                                        </td>
                                        <td class="series-table-actions text-center">
                                            <button  type="button" class="btn waves-effect waves-light btn-xs btn-danger"  @click.prevent="clickCancel(index)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                        <br>
                                    </tr>
                                </tbody>
                            </table>


                        </div>
                        </div>
                    </div>

                    <!-- Sección 4: Productos y Servicios -->
                    <div class="form-section">
                        <h5 class="section-header"><i class="fa fa-shopping-cart"></i> Productos y Servicios</h5>
                        <div class="row">
                        <div class="col-lg-12 col-md-6 d-flex align-items-end">
                            <div class="form-group">
                                <button type="button" class="btn waves-effect waves-light btn-primary" @click.prevent="clickAddNewItem">
                                    <i class="fas fa-shopping-cart"></i> Agregar Producto
                                </button>
                                <button type="button" class="ml-3 btn waves-effect waves-light btn-primary" @click.prevent="dialogRetention = !dialogRetention">
                                    <i class="fas fa-hand-holding-usd"></i> Agregar Retención
                                </button>
                            </div>
                        </div>
                        </div>
                        <div class="row" v-if="form.items.length > 0">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Descripción</th>
                                        <th>Almacén</th>
                                        <th class="text-center">Unidad</th>
                                        <th class="text-right">Cantidad</th>
                                        <th class="text-right">Precio Unitario</th>
                                        <th class="text-right">Descuento</th>
                                        <th class="text-right">Impuesto</th>
                                        <th class="text-right">Total</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="(row, index) in form.items" :key="index">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ row.item && row.item.name ? row.item.name : 'Producto no disponible' }}<br/>
                                            <small>{{row.tax && row.tax.name ? row.tax.name : 'Sin impuesto'}}</small>
                                        </td>
                                        <td class="text-left">{{ row.warehouse_description ? row.warehouse_description : (row.item && row.item.warehouse_description ? row.item.warehouse_description : 'N/A') }}</td>
                                        <td class="text-center">{{ row.item && row.item.unit_type && row.item.unit_type.name ? row.item.unit_type.name : 'N/A' }}</td>
                                        <td class="text-right">{{ row.quantity }}</td>
                                        <td class="text-right">{{ ratePrefix() }} {{ getFormatUnitPriceRow(row.unit_price) }}</td>
                                        <td class="text-right">{{ ratePrefix() }} {{ row.discount }}</td>
                                        <td class="text-right">{{ ratePrefix() }} {{ row.total_tax ? Number(row.total_tax).toFixed(2) : '0.00' }}</td>
                                        <td class="text-right">{{ ratePrefix() }} {{ row.total }}</td>
                                        <td class="text-right">
                                            <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click="ediItem(row, index)" ><span style='font-size:10px;'>&#9998;</span> </button>
                                            <button type="button" class="btn waves-effect waves-light btn-xs btn-danger" @click.prevent="clickRemoveItem(index)">x</button>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-md-12" style="display: flex; flex-direction: column; align-items: flex-end;" v-if="form.items.length > 0">
                            <table>

                                <tr>
                                    <td>TOTAL VENTA</td>
                                    <td>:</td>
                                    <td class="text-right">{{ratePrefix()}} {{ form.sale }}</td>
                                </tr>
                                <tr >
                                    <td>TOTAL DESCUENTO (-)</td>
                                    <td>:</td>
                                    <td class="text-right">{{ratePrefix()}} {{ form.total_discount }}</td>
                                </tr>
                                <template v-for="(tax, index) in form.taxes">
                                    <tr v-if="((tax.total > 0) && (!tax.is_retention))" :key="index">
                                        <td >
                                            {{tax.name}}(+)
                                        </td>
                                        <td>:</td>
                                        <td class="text-right">{{ratePrefix()}} {{Number(tax.total).toFixed(2)}}</td>
                                    </tr>
                                </template>
                                <tr>
                                    <td>SUBTOTAL</td>
                                    <td>:</td>
                                    <td class="text-right">{{ratePrefix()}} {{ form.subtotal }}</td>
                                </tr>
                                <template v-for="(tax, index) in form.taxes">
                                    <tr v-if="tax.is_retention && tax.retention > 0" :key="index">
                                        <td>{{tax.name}}(-) </td>
                                        <td>:</td>
                                        <td class="text-right">
                                            <el-input :value="tax.retention" readonly>
                                                <i slot="suffix" class="el-input__icon el-icon-delete pointer"  @click="deleteRetention(tax.id)"></i>
                                            </el-input>
                                        </td>
                                    </tr>
                                </template>

                            </table>

                        </div>

                        <div class="col-md-12">

                            <h3 class="text-right" v-if="form.total > 0"><b>TOTAL COMPRAS: </b>{{ ratePrefix() }} {{ form.total }}</h3>

                            <template v-if="is_perception_agent">
                                <hr>
                                <div class="row mt-1">
                                    <div class="col-lg-10 float-right">
                                        <label class="float-right control-label">NÚMERO PERCEPCIÓN: </label>
                                    </div>
                                    <div class="col-lg-2 float-right">
                                        <div class="form-group" :class="{'has-danger': errors.perception_number}">
                                            <el-input v-model="form.perception_number"></el-input>

                                            <small class="form-control-feedback" v-if="errors.perception_number" v-text="errors.perception_number[0]"></small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-1">
                                    <div class="col-lg-10 float-right">
                                        <label class="float-right control-label">FEC EMISIÓN PERCEPCIÓN: </label>
                                    </div>
                                    <div class="col-lg-2 float-right">
                                        <div class="form-group" :class="{'has-danger': errors.perception_date}">
                                            <el-date-picker v-model="form.perception_date" type="date" value-format="yyyy-MM-dd" :clearable="false" @change="changeDateOfIssue"></el-date-picker>
                                            <small class="form-control-feedback" v-if="errors.perception_date" v-text="errors.perception_date[0]"></small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-1">
                                    <div class="col-lg-10 float-right">
                                        <label class="float-right control-label">IMPORTE PERCEPCIÓN: </label>
                                    </div>
                                    <div class="col-lg-2 float-right">
                                        <div class="form-group" :class="{'has-danger': errors.total_perception}">
                                            <el-input v-model="form.total_perception" @input="inputTotalPerception" :readonly="true"></el-input>

                                            <small class="form-control-feedback" v-if="errors.total_perception" v-text="errors.total_perception[0]"></small>
                                        </div>
                                    </div>
                                </div>
                                <h3 class="text-right" v-if="form.total > 0 && !hide_button"><b>MONTO TOTAL : </b>{{ ratePrefix() }} {{ total_amount }}</h3>


                            </template>
                        </div>
                        </div>
                    </div>
                </div>
                <div class="form-actions text-right mt-4">
                    <el-button @click.prevent="close()">Cancelar</el-button>
                    <el-button type="primary" native-type="submit" :loading="loading_submit" v-if="form.items.length > 0 && !hide_button">Generar</el-button>
                </div>
            </form>
        </div>

        <el-dialog
            title="Retención"
            :visible.sync="dialogRetention"
            width="600px">
            <el-select v-model="retention_selected" placeholder="Select">
                <el-option
                    v-for="(item, index) in retention_taxes"
                    :key="index"
                    :label="item.name+' '+item.rate+'%'"
                    :value="item.id">
                </el-option>
            </el-select>
            <span slot="footer" class="dialog-footer">
                <el-button @click="dialogRetention = false">Cancel</el-button>
                <el-button type="primary" @click="validateRetention">Confirm</el-button>
            </span>
        </el-dialog>

        <purchase-form-item :showDialog.sync="showDialogAddItem"
                           :currency-type-id-active="form.currency_type_id"
                           :exchange-rate-sale="form.exchange_rate_sale"
                           :taxes="taxesForModal"
                           :recordItem="recordItem"
                           :key="recordItem ? recordItem.id + '_' + (recordItem.tax_percent || 0) : 'default'"
                           @add="addRow"></purchase-form-item>

        <person-form :showDialog.sync="showDialogNewPerson"
                       type="suppliers"
                        :input_person="input_person"
                       :external="true"></person-form>

        <purchase-options :showDialog.sync="showDialogOptions"
                          :recordId="purchaseNewId"
                          :showClose="false"></purchase-options>

        <!-- Modal para XML DIAN -->
        <el-dialog
            title="Leer XML desde la DIAN"
            :visible.sync="showDialogXMLDian"
            width="1000px"
            :close-on-click-modal="false">
            <div class="form-body">
                <div class="form-group">
                    <label class="control-label">
                        <i class="fas fa-file-code mr-2"></i>
                        Ingrese el identificador del documento
                    </label>
                    <el-input
                        v-model="xmlDianForm.identifier"
                        placeholder="Identificador de 96 caracteres alfanuméricos"
                        @input="formatXMLIdentifier"
                        @paste.native="handlePaste"
                        maxlength="96"
                        show-word-limit
                        style="width: 100%;">
                    </el-input>
                    <small class="form-control-feedback text-muted mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Debe contener exactamente 96 caracteres alfanuméricos (letras y números)
                    </small>
                </div>
            </div>
            <span slot="footer" class="dialog-footer">
                <el-button @click="cancelXMLDian" size="medium">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </el-button>
                <el-button type="primary" @click="readXMLFromDian" size="medium" :loading="xmlDianForm.loading" :disabled="!xmlDianForm.identifier.trim() || xmlDianForm.identifier.length !== 96">
                    <i class="fas fa-cloud-download-alt mr-2"></i>
                    Leer XML Desde La DIAN
                </el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script>

    import PurchaseFormItem from './partials/item.vue'
    import PersonForm from '../persons/form.vue'
    import PurchaseOptions from './partials/options.vue'
    import {functions, exchangeRate} from '../../../mixins/functions'
    import {calculateRowItem} from '../../../helpers/functions'

    export default {
        props:['purchase_order_id'],
        components: {PurchaseFormItem, PersonForm, PurchaseOptions},
        mixins: [functions, exchangeRate],
        data() {
            return {
                currencies: [],
                input_person:{},
                resource: 'purchases',
                showDialogAddItem: false,
                showDialogNewPerson: false,
                showDialogOptions: false,
                showDialogXMLDian: false,
                loading_submit: false,
                hide_button: false,
                is_perception_agent: false,
                errors: {},
                form: {},
                aux_supplier_id:null,
                total_amount:0,
                document_types: [],
                currency_types: [],
                discount_types: [],
                charges_types: [],
                payment_method_types: [],
                all_suppliers: [],
                suppliers: [],
                all_customers: [],
                customers: [],
                company: {},
                operation_types: [],
                establishment: {},
                all_series: [],
                series: [],
                payment_destinations:  [],
                currency_type: {},
                loading_search: false,
                taxes:  [],
                purchaseNewId: null,
                retention_selected: null,
                dialogRetention: false,
                retention_taxes: [],
                recordItem: null,
                xmlDianForm: {
                    identifier: '',
                    loading: false
                }
            }
        },
        async created() {
            await this.initForm()
            await this.$http.get(`/${this.resource}/tables`)
                .then(response => {

                    this.taxes = response.data.taxes
                    this.document_types = response.data.document_types_invoice
                    this.currencies = response.data.currencies
                    this.establishment = response.data.establishment
                    this.all_suppliers = response.data.suppliers
                    // this.discount_types = response.data.discount_types
                    this.payment_method_types = response.data.payment_method_types
                    this.payment_destinations = response.data.payment_destinations
                    this.all_customers = response.data.customers

                    // this.charges_types = response.data.charges_types

                    let find_currency = _.find(this.currencies, {id:170})
                    this.form.currency_id = find_currency ? find_currency.id: null
                    this.form.establishment_id = (this.establishment.id) ? this.establishment.id:null
                    this.form.document_type_id = (this.document_types.length > 0)?this.document_types[0].id:null

                    this.changeDateOfIssue()
                    this.changeDocumentType()
                    this.changeCurrencyType()
                })

            this.$eventHub.$on('reloadDataPersons', (supplier_id) => {
                this.reloadDataSuppliers(supplier_id)
           })

            this.$eventHub.$on('initInputPerson', () => {
                this.initInputPerson()
            })

            await this.filterCustomers()
            await this.isGeneratePurchaseOrder()
            await this.changeHasPayment()
            await this.changeHasClient()
            this.retentiontaxes()
        },
        computed: {
            taxesForModal() {
                // Crear una copia de los impuestos base
                let modalTaxes = [...this.taxes];

                // Si hay un item siendo editado que tiene impuesto personalizado del XML
                if (this.recordItem && this.recordItem.tax && this.recordItem.tax_percent !== undefined && this.recordItem.tax_percent > 0) {
                    const taxIndex = modalTaxes.findIndex(tax => tax.id === this.recordItem.tax_id);
                    if (taxIndex >= 0) {
                        // Crear impuesto personalizado con el porcentaje del XML
                        const customTax = { ...modalTaxes[taxIndex] };
                        customTax.rate = this.recordItem.tax_percent;
                        const baseName = customTax.name.replace(/\s*\d+%?$/, '');
                        customTax.name = `${baseName} ${this.recordItem.tax_percent}%`;

                        // Reemplazar en la lista
                        modalTaxes[taxIndex] = customTax;
                    }
                }

                return modalTaxes;
            }
        },
        watch: {
            // Observar cambios en recordItem para forzar actualización del computed taxesForModal
            recordItem: {
                handler(newValue, oldValue) {
                    if (newValue && newValue !== oldValue) {
                        // Forzar re-evaluación del computed taxesForModal
                        this.$nextTick(() => {
                            this.$forceUpdate();
                        });
                    }
                },
                deep: true
            }
        },
        methods: {
            setDataTotals() {

                // console.log(val)
                let val = this.form
                val.taxes = JSON.parse(JSON.stringify(this.taxes));

                val.items.forEach(item => {
                    item.tax = this.taxes.find(tax => tax.id == item.tax_id);

                    // Si tenemos un porcentaje de impuesto del XML, usarlo en lugar del de la base de datos
                    if (item.tax && item.tax_percent !== undefined && item.tax_percent > 0) {
                        // Crear una copia del objeto tax para no modificar el original
                        item.tax = { ...item.tax };
                        // Usar el porcentaje del XML en lugar del de la base de datos
                        const originalRate = this.taxes.find(tax => tax.id == item.tax_id)?.rate;
                        item.tax.rate = item.tax_percent;
                        // Actualizar también el nombre para reflejar el porcentaje correcto
                        const baseName = item.tax.name.replace(/\s*\d+%?$/, ''); // Remover porcentaje existente del nombre
                        item.tax.name = `${baseName} ${item.tax_percent}%`;
                        console.log(`Usando porcentaje de impuesto del XML para ${item.description || 'item'}: ${item.tax_percent}% en lugar de ${originalRate}%`);
                    }

                    // seteo de descuento en caso no posea o sea superior al precio por cantidad
                    if (item.discount == null || item.discount == "" || item.discount > (item.unit_price * item.quantity)) {
                        this.$set(item, "discount", 0);
                    }

                    // DEBUG: Log valores antes del cálculo de descuento
                    if (item.from_xml) {
                        console.log(`setDataTotals - ${item.description}:`, {
                            discount: item.discount,
                            discount_type: item.discount_type,
                            discount_percentage: item.discount_percentage,
                            has_discount: item.has_discount,
                            unit_price: item.unit_price,
                            quantity: item.quantity
                        });
                    }

                    // defino el total de descuento
                    let total_discount = 0;
                    if(item.discount_type === 'percentage') {
                        total_discount = ((item.unit_price * item.quantity) * item.discount_percentage) / 100;
                    } else {
                        total_discount = item.discount
                    }

                    this.$set( item, "discount", Number(total_discount).toFixed(2));

                    item.total_tax = 0;

                    if (item.tax != null) {
                        let tax = val.taxes.find(tax => tax.id == item.tax.id);

                        if (item.tax.is_fixed_value)

                            item.total_tax = (
                                item.tax.rate * item.quantity -
                                (total_discount < item.unit_price * item.quantity ? total_discount : 0)
                            ).toFixed(2);

                        if (item.tax.is_percentage) {
                            // Si el item viene del XML, usar SIEMPRE el monto de impuesto del XML (puede ser 0)
                            if (item.from_xml && item.tax_amount !== undefined) {
                                item.total_tax = Number(item.tax_amount).toFixed(2);
                            } else {
                                // Para items normales, calcular impuestos al precio base
                                item.total_tax = (
                                    (item.unit_price * item.quantity -
                                    (total_discount < item.unit_price * item.quantity
                                        ? total_discount
                                        : 0)) *
                                    (item.tax.rate / item.tax.conversion)
                                ).toFixed(2);
                            }
                        }

                        if (!tax.hasOwnProperty("total"))
                            tax.total = Number(0).toFixed(2);

                        tax.total = (Number(tax.total) + Number(item.total_tax)).toFixed(2);
                    }

                    // Calcular subtotal y total
                    if (item.from_xml) {
                        // Para items del XML:
                        // - total = precio base sin impuestos (después de descuentos)
                        // - subtotal = precio final con impuestos incluidos
                        item.total = (Number(item.unit_price * item.quantity) - Number(total_discount)).toFixed(2);
                        item.subtotal = (Number(item.total) + Number(item.total_tax)).toFixed(2);
                    } else {
                        // Para items normales, agregar impuestos al subtotal
                        item.subtotal = (
                            Number(item.unit_price * item.quantity) + Number(item.total_tax)
                        ).toFixed(2);

                        item.total = (Number(item.subtotal) - Number(total_discount)).toFixed(2);
                    }

                });

                val.subtotal = val.items
                    .reduce(
                        (p, c) => Number(p) + Number(c.unit_price * c.quantity) - Number(c.discount),
                        0
                    )
                    .toFixed(2);
                    val.sale = val.items
                    .reduce(
                        (p, c) =>
                        Number(p) + Number(c.unit_price * c.quantity) - Number(c.discount),
                        0
                    )
                    .toFixed(2);
                    val.total_discount = val.items
                    .reduce((p, c) => Number(p) + Number(c.discount), 0)
                    .toFixed(2);
                    val.total_tax = val.items
                    .reduce((p, c) => Number(p) + Number(c.total_tax), 0)
                    .toFixed(2);

                let total = Number(val.subtotal) + Number(val.total_tax);

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

                val.total = Number(total).toFixed(2)

            },
            ratePrefix(tax = null) {
                if ((tax != null) && (!tax.is_fixed_value)) return null;

                return (this.company.currency != null) ? this.company.currency.symbol : '$';
            },
            changeHasPayment(){

                if(!this.form.has_payment){
                    this.form.payments = []
                }
            },
            changeHasClient(){

                if(!this.form.has_client){
                    this.form.customer_id = null
                }
            },
            searchRemotePersons(input) {

                if (input.length > 1) {

                    this.loading_search = true
                    let parameters = `input=${input}`

                    this.$http.get(`/reports/data-table/persons/customers?${parameters}`)
                            .then(response => {
                                this.customers = response.data.persons
                                this.loading_search = false

                                if(this.customers.length == 0){
                                    this.filterCustomers()
                                }
                            })
                } else {
                    this.filterCustomers()
                }

            },
            filterCustomers() {
                this.customers = this.all_customers
            },
            getFormatUnitPriceRow(unit_price){
                return _.round(unit_price, 6)
                // return unit_price.toFixed(6)
            },
            async isGeneratePurchaseOrder(){

                // console.log(this.purchase_order_id)
                if(this.purchase_order_id){

                    await this.$http.get(`/purchase-orders/record/${this.purchase_order_id}`)
                        .then(response => {

                            // console.log(response)

                            let purchase_order = response.data.data.purchase_order
                            let warehouse = response.data.data.warehouse
                            let supp = purchase_order.supplier

                            this.form.document_type_id = "01"
                            // console.log(purchase_order.supplier_id)

                            this.form.items = response.data.data.purchase_order.items
                            this.form.supplier_id = purchase_order.supplier_id
                            this.form.currency_id = purchase_order.currency_id
                            this.form.purchase_order_id = purchase_order.id
                            // this.form.payments[0].payment_method_type_id = purchase_order.payment_method_type_id
                            // this.form.payments[0].payment = purchase_order.total
                            this.form.total = purchase_order.total
                            this.form.total_tax = purchase_order.total_tax
                            this.form.sale = purchase_order.sale
                            this.form.subtotal = purchase_order.subtotal
                            this.form.total_discount = purchase_order.total_discount
                            this.form.taxes = purchase_order.taxes
                            this.currency_type = _.find(this.currencies, {'id': this.form.currency_id})

                            this.form.items.forEach((it)=>{
                                it.warehouse_id = warehouse.id
                            })
                            // this.changeDocumentType()

                        })

                }
            },
            async validate_payments(){

                let error_by_item = 0
                let acum_total = 0
                let q_affectation_free = 0

                await this.form.payments.forEach((item)=>{
                    acum_total += parseFloat(item.payment)
                    if(item.payment <= 0 || item.payment == null) error_by_item++;
                })


                if(this.form.has_client && !this.form.customer_id){
                    return  {
                        success : false,
                        message : 'Debe seleccionar un cliente'
                    }
                }

                if(this.form.has_payment && this.form.payments.length == 0){
                    return  {
                        success : false,
                        message : 'Debe registrar al menos un pago'
                    }
                }

                return  {
                    success : true,
                    message : null
                }
            },
            clickCancel(index) {
                this.form.payments.splice(index, 1);
            },
            clickAddPayment() {
                this.form.payments.push({
                    id: null,
                    purchase_id: null,
                    date_of_payment:  moment().format('YYYY-MM-DD'),
                    payment_method_type_id: '01',
                    reference: null,
                    payment_destination_id:'cash',
                    payment: 0,
                });
            },
            initInputPerson(){
                this.input_person = {
                    number:'',
                    identity_document_type_id:''
                }
            },
            keyupEnterSupplier(){

                if(this.input_person.number){

                    if(!isNaN(parseInt(this.input_person.number))){

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
            keyupSupplier(e){

                if(e.key !== "Enter"){

                    this.input_person.number = this.$refs.select_person.$el.getElementsByTagName('input')[0].value
                    let exist_persons = this.suppliers.filter((supplier)=>{
                        let pos = supplier.description.search(this.input_person.number);
                        return (pos >- 1)
                    })

                    this.input_person.number = (exist_persons.length == 0) ? this.input_person.number : null
                }

            },
            inputSeries(){

                const pattern = new RegExp('^[A-Z0-9]+$', 'i');
                if(!pattern.test(this.form.series)){
                    this.form.series = this.form.series.substring(0, this.form.series.length - 1);
                } else {
                    this.form.series = this.form.series.toUpperCase()
                }

            },
            changePaymentMethodType(flag_submit = true, index = null){
                let payment_method_type = _.find(this.payment_method_types, {'id':this.form.payments[index].payment_method_type_id})
                if(payment_method_type.number_days){
                    this.form.date_of_issue =  moment().add(payment_method_type.number_days,'days').format('YYYY-MM-DD');
                    this.changeDateOfIssue()
                }else{
                    if(flag_submit){
                        this.form.date_of_issue = moment().format('YYYY-MM-DD')
                        this.changeDateOfIssue()
                    }
                }
            },
            inputTotalPerception(){
                this.total_amount = parseFloat(this.form.total) + parseFloat(this.form.total_perception)
                if(isNaN(this.total_amount)){
                    this.hide_button = true
                }else{
                    this.hide_button = false

                }
            },
            changeSupplier(){
                this.calculatePerception()
            },
            filterSuppliers() {

                // if(this.form.document_type_id === '01') {
                //     this.suppliers = _.filter(this.all_suppliers, {'identity_document_type_id': '6'})
                //     this.selectSupplier()

                // } else {
                    this.suppliers =  this.all_suppliers  //_.filter(this.all_suppliers, (c) => { return c.identity_document_type_id !== '6' })
                    this.selectSupplier()
                // }
            },
            async selectSupplier(){

                let supplier = await _.find(this.suppliers, {'id': this.aux_supplier_id})
                // console.log(supplier)
                this.form.supplier_id = (supplier) ? supplier.id : null
                this.aux_supplier_id = null

            },
            initForm() {
                this.errors = {}
                this.form = {
                    establishment_id: null,
                    document_type_id: null,
                    series: null,
                    number: null,
                    date_of_issue: moment().format('YYYY-MM-DD'),
                    time_of_issue: moment().format('HH:mm:ss'),
                    supplier_id: null,
                    payment_method_type_id:'01',
                    currency_id: null,
                    purchase_order: null,
                    exchange_rate_sale: 0,
                    total_prepayment: 0,
                    total_charge: 0,
                    total_discount: 0,
                    total_exportation: 0,
                    total_free: 0,
                    total_taxed: 0,
                    total_unaffected: 0,
                    total_exonerated: 0,
                    total_igv: 0,
                    total_base_isc: 0,
                    total_isc: 0,
                    total_base_other_taxes: 0,
                    total_other_taxes: 0,
                    total_taxes: 0,
                    total_value: 0,
                    total: 0,
                    perception_date: null,
                    perception_number: null,
                    total_perception: 0,
                    date_of_due: moment().format('YYYY-MM-DD'),
                    items: [],
                    charges: [],
                    discounts: [],
                    attributes: [],
                    guides: [],
                    payments: [],
                    customer_id: null,
                    has_client: false,
                    has_payment: false,
                    taxes: [],

                }
                // this.clickAddPayment()

                this.initInputPerson()

            },
            resetForm() {
                this.initForm()

                let find_currency = _.find(this.currencies, {id:170})
                this.form.currency_id = find_currency ? find_currency.id: null
                this.form.establishment_id = this.establishment.id
                this.form.document_type_id = (this.document_types.length > 0)?this.document_types[0].id:null

                this.changeDateOfIssue()
                this.changeDocumentType()
                this.changeCurrencyType()
            },
            changeDateOfIssue() {
                this.form.date_of_due = this.form.date_of_issue
                // this.searchExchangeRateByDate(this.form.date_of_issue).then(response => {
                //     this.form.exchange_rate_sale = response
                // })
            },
            changeDocumentType() {
                this.filterSuppliers()
            },
            addRow(row) {
                if(this.recordItem)
                {
                    this.form.items[this.recordItem.indexi] = row
                    this.recordItem = null
                }
                else{
                    this.form.items.push(row)
                }

                this.calculateTotal()
            },
            clickRemoveItem(index) {
                this.form.items.splice(index, 1)
                this.calculateTotal()
            },
            changeCurrencyType() {
                // this.currency_type = _.find(this.currency_types, {'id': this.form.currency_type_id})
                // let items = []
                // this.form.items.forEach((row) => {
                //     items.push(calculateRowItem(row, this.form.currency_type_id, this.form.exchange_rate_sale))
                // });
                // this.form.items = items
                // this.calculateTotal()
            },
            calculateTotal() {

                this.setDataTotals()

                this.calculatePerception()

                // this.form.payments[0].payment = this.form.total
                this.setTotalDefaultPayment()

            },
            setTotalDefaultPayment(){

                if(this.form.payments.length > 0){

                    this.form.payments[0].payment = this.form.total
                }
            },
            calculatePerception(){

                let supplier = _.find(this.all_suppliers,{'id':this.form.supplier_id})

                if(supplier){

                    if(supplier.perception_agent) {

                        let total_perception = 0
                        let quantity_item_perception = 0
                        let total_amount = 0
                        this.form.total_perception = 0

                        this.form.perception_date = moment().format('YYYY-MM-DD')

                        this.form.items.forEach((row) => {
                            quantity_item_perception += (row.item.has_perception) ? 1:0
                            total_perception += (row.item.has_perception) ? (parseFloat(row.unit_price) * parseFloat(row.quantity) * (parseFloat(row.item.percentage_perception)/100)) : 0
                        });

                        this.is_perception_agent = (quantity_item_perception > 0) ? true : false
                        this.form.total_perception = _.round(total_perception,2)
                        total_amount = this.form.total + parseFloat(this.form.total_perception)
                        this.total_amount = _.round(total_amount, 2)

                    }else{

                        this.is_perception_agent = false
                        this.form.perception_date = null
                        this.form.perception_number = null
                        this.form.total_perception = null

                    }

                }


            },
            async submit() {

                let validate = await this.validate_payments()
                if(!validate.success) {
                    return this.$message.error(validate.message);
                }

                this.loading_submit = true
                // await this.changePaymentMethodType(false)
                await this.$http.post(`/${this.resource}`, this.form)
                    .then(response => {

                        if (response.data.success) {

                            if(this.purchase_order_id){

                                this.$message({
                                    showClose: true,
                                    message: `Compra registrada : ${response.data.data.number_full}`,
                                    duration: 2 * 3000,
                                    type: "success"
                                });

                                this.close()

                            }else{

                                this.resetForm()
                                this.purchaseNewId = response.data.data.id
                                this.showDialogOptions = true

                            }

                        } else {
                            this.$message.error(response.data.message)
                        }
                    })
                    .catch(error => {
                        if (error.response.status === 422) {
                            this.errors = error.response.data
                        } else {
                            this.$message.error(error.response.data.message)
                        }
                    })
                    .then(() => {
                        this.loading_submit = false
                    })
            },
            close() {
                location.href = '/purchases'
            },
            reloadDataSuppliers(supplier_id) {
                this.$http.get(`/${this.resource}/table/suppliers`).then((response) => {

                    this.aux_supplier_id = supplier_id
                    this.all_suppliers = response.data
                    this.filterSuppliers()

                })
            },
            retentiontaxes() {
                this.retention_taxes = this.taxes.filter(tax => tax.is_retention);
            },
            validateRetention() {
                var current_tax = this.form.taxes.find(tax => tax.id === this.retention_selected);
                current_tax.retention = (Number(current_tax.in_tax ? this.form.total : this.form.sale) * (current_tax.rate / current_tax.conversion)).toFixed(2);

                var totalRetentionBase = 0;
                totalRetentionBase += Number(current_tax.retention);

                if (Number(totalRetentionBase) >= Number(this.form.total)) {
                    current_tax.retention = Number(0).toFixed(2);
                }

                this.form.total -= Number(current_tax.retention).toFixed(2);
                this.dialogRetention = false;
                this.retention_selected = null; // se puede añadir otro ¿?
            },
            deleteRetention(id) {
                var current_tax = this.form.taxes.find(tax => tax.id === id);
                current_tax.retention = 0;
                this.calculateTotal()
            },
            clickAddNewItem() {
                // Limpiar recordItem para asegurar que el modal se abra en modo agregar
                this.recordItem = null
                this.$nextTick(() => {
                    this.showDialogAddItem = true
                })
            },
            ediItem(row, index)
            {
                row.indexi = index
                this.recordItem = row
                // Esperar a que Vue procese el cambio de recordItem antes de abrir el modal
                this.$nextTick(() => {
                    this.showDialogAddItem = true
                })
            },
            // Métodos para modal XML DIAN
            formatXMLIdentifier(value) {
                // Remover caracteres que no sean letras o números
                const cleanValue = value.replace(/[^a-zA-Z0-9]/g, '');

                // Limitar a máximo 96 caracteres
                const limitedValue = cleanValue.slice(0, 96);

                // Convertir a minúsculas
                this.xmlDianForm.identifier = limitedValue.toLowerCase();
            },
            handlePaste(event) {
                // Prevenir el pegado por defecto
                event.preventDefault();

                // Obtener el texto del portapapeles
                const pastedText = (event.clipboardData || window.clipboardData).getData('text');

                // Limpiar, limitar a 96 caracteres y convertir a minúsculas
                const cleanValue = pastedText.replace(/[^a-zA-Z0-9]/g, '').slice(0, 96).toLowerCase();

                // Asignar el valor limpio
                this.xmlDianForm.identifier = cleanValue;
            },
            cancelXMLDian() {
                this.showDialogXMLDian = false;
                this.xmlDianForm.identifier = '';
                this.xmlDianForm.loading = false;
            },
            async readXMLFromDian() {
                const identifier = this.xmlDianForm.identifier.trim();

                if (!identifier) {
                    this.$message.warning('Por favor ingrese un identificador válido');
                    return;
                }

                if (identifier.length !== 96) {
                    this.$message.error(`El identificador debe tener exactamente 96 caracteres. Actualmente tiene ${identifier.length} caracteres.`);
                    return;
                }

                // Validar que solo contenga caracteres alfanuméricos
                if (!/^[a-zA-Z0-9]{96}$/.test(identifier)) {
                    this.$message.error('El identificador solo debe contener letras y números.');
                    return;
                }

                this.xmlDianForm.loading = true;

                try {
                    const response = await this.$http.post('/purchases/read-xml-dian', {
                        identifier: this.xmlDianForm.identifier
                    });

                    if (response.data.success) {
                        this.$message.success('XML leído exitosamente desde la DIAN');

                        // Procesar los datos del XML y cargarlos en el formulario
                        this.loadPurchaseDataFromXML(response.data.data.purchase_data);

                        this.cancelXMLDian();
                    } else {
                        // Mostrar mensaje de error específico de la DIAN
                        if (response.data.dian_response) {
                            this.$message.error(`Error de la DIAN: ${response.data.dian_response}`);
                        } else {
                            this.$message.error(response.data.message || 'Error al leer el XML desde la DIAN');
                        }
                    }
                } catch (error) {
                    console.error('Error al leer XML desde DIAN:', error);

                    // Manejar errores de validación del servidor
                    if (error.response && error.response.status === 422) {
                        const errors = error.response.data.errors;
                        let errorMessage = 'Errores de validación:\n';
                        Object.keys(errors).forEach(key => {
                            errorMessage += `- ${errors[key].join('\n- ')}\n`;
                        });
                        this.$message.error(errorMessage);
                    } else if (error.response && error.response.data && error.response.data.message) {
                        this.$message.error(error.response.data.message);
                    } else {
                        this.$message.error('Error de conexión al procesar la solicitud');
                    }
                } finally {
                    this.xmlDianForm.loading = false;
                }
            },

            /**
             * Cargar los datos de compra extraídos del XML de la DIAN al formulario
             */
            loadPurchaseDataFromXML(purchaseData) {
                try {

                    // DEBUG: Revisar datos de descuentos en detail
                    if (purchaseData.items && purchaseData.items.length > 0) {
                        console.log('=== DEBUG DESCUENTOS ===');
                        purchaseData.items.forEach((xmlItem, index) => {
                            console.log(`Item ${index + 1}:`, {
                                description: xmlItem.item_description,
                                has_discount: xmlItem.has_discount,
                                discount_amount: xmlItem.discount_amount,
                                discount_percentage: xmlItem.discount_percentage,
                                line_extension_amount: xmlItem.line_extension_amount,
                                price_amount: xmlItem.price_amount,
                                tax_amount: xmlItem.tax_amount
                            });
                        });

                        if (purchaseData.monetary_totals) {
                            console.log('Totales monetarios:', {
                                allowance_total_amount: purchaseData.monetary_totals.allowance_total_amount,
                                line_extension_amount: purchaseData.monetary_totals.line_extension_amount,
                                tax_inclusive_amount: purchaseData.monetary_totals.tax_inclusive_amount,
                                payable_amount: purchaseData.monetary_totals.payable_amount
                            });
                        }
                        console.log('=== FIN DEBUG DESCUENTOS ===');
                    }

                    // Cargar campos específicos solicitados
                    // Serie
                    if (purchaseData.series) {
                        this.form.series = purchaseData.series;
                    }

                    // Número (sin la serie)
                    if (purchaseData.number) {
                        this.form.number = purchaseData.number;
                    }

                    // Fecha de Emisión
                    if (purchaseData.issue_date) {
                        this.form.date_of_issue = purchaseData.issue_date;
                    }

                    // Fecha de Vencimiento
                    if (purchaseData.due_date) {
                        this.form.date_of_due = purchaseData.due_date;
                    }

                    // Cargar otros datos básicos del documento
                    if (purchaseData.note) {
                        this.form.observation = purchaseData.note;
                    }

                    // Buscar o crear el proveedor
                    if (purchaseData.supplier && purchaseData.supplier.identification_number) {
                        this.loadSupplierFromXML(purchaseData.supplier);
                    }

                    // Cargar totales monetarios
                    if (purchaseData.monetary_totals) {
                        const totals = purchaseData.monetary_totals;
                        this.form.total = parseFloat(totals.payable_amount) || 0;
                        this.form.subtotal = parseFloat(totals.line_extension_amount) || 0;
                        this.form.total_taxes = parseFloat(totals.tax_inclusive_amount) - parseFloat(totals.line_extension_amount) || 0;

                        // FORZAR: NO usar allowance_total_amount del XML, calcular desde items
                        this.form.total_discount = 0; // Siempre iniciar en 0, se calculará después
                        console.log(`Totales iniciales - Ignorando allowance_total_amount: ${totals.allowance_total_amount}`);
                    }

                    // Cargar items del documento
                    if (purchaseData.items && purchaseData.items.length > 0) {
                        this.loadItemsFromXML(purchaseData.items);
                    }

                    // Mostrar información adicional
                    this.$message.success({
                        message: `Compra cargada exitosamente con ${purchaseData.items?.length || 0} items`,
                        duration: 3000
                    });

                } catch (error) {
                    console.error('Error al cargar datos del XML:', error);
                    this.$message.warning('XML procesado pero hubo errores al cargar algunos datos al formulario');
                }
            },

            /**
             * Cargar información del proveedor desde el XML
             */
            async loadSupplierFromXML(supplierData) {
                try {
                    if (!supplierData || !supplierData.identification_number) {
                        return;
                    }

                    // Buscar si el proveedor ya existe
                    const searchResponse = await this.$http.get('/persons-search-suppliers', {
                        params: {
                            input: supplierData.identification_number
                        }
                    });

                    if (searchResponse.data && searchResponse.data.suppliers && searchResponse.data.suppliers.length > 0) {
                        // Proveedor existe, seleccionarlo
                        const supplier = searchResponse.data.suppliers[0];
                        this.form.supplier_id = supplier.id;
                        this.form.supplier = supplier;
                        console.log(`Proveedor existente seleccionado: ${supplier.name} (${supplier.number})`);

                        // Recargar lista de proveedores para refrescar la selección
                        await this.reloadDataSuppliers(supplier.id);

                    } else {
                        // Proveedor no existe, crearlo automáticamente
                        console.log(`Creando nuevo proveedor: ${supplierData.name} (${supplierData.identification_number})`);

                        // Calcular dígito de verificación para NIT
                        const calculateDV = (nit) => {
                            const nitString = nit.toString();
                            const factors = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
                            let sum = 0;

                            for (let i = 0; i < nitString.length; i++) {
                                sum += parseInt(nitString[i]) * factors[nitString.length - 1 - i];
                            }

                            const remainder = sum % 11;
                            return remainder < 2 ? remainder : 11 - remainder;
                        };

                        const dv = calculateDV(supplierData.identification_number);
                        console.log(`Dígito de verificación calculado para ${supplierData.identification_number}: ${dv}`);

                        const newSupplierData = {
                            type: 'suppliers',
                            identity_document_type_id: 6, // NIT (ID correcto, no código)
                            number: supplierData.identification_number,
                            name: (supplierData.name || 'Proveedor Importado').substring(0, 100), // Limitar longitud
                            code: supplierData.identification_number, // Usar el NIT como código también
                            dv: dv, // Dígito de verificación calculado
                            type_obligation_id: 117, // No responsable (ID válido por defecto)
                            country_id: 47, // Colombia por defecto (ID numérico)
                            address: (supplierData.address || 'Dir no especificada').substring(0, 100), // Limitar longitud
                            email: 'contacto@empresa.com', // Email genérico válido
                            telephone: '3000000000', // Teléfono genérico válido (10 dígitos)
                            from_invoice: true, // Para evitar validaciones de unicidad estrictas
                            // Eliminar addresses para evitar problemas de clave foránea
                            addresses: []
                        };

                        try {
                            const createResponse = await this.$http.post('/persons', newSupplierData);

                            if (createResponse.data.success) {
                                const newSupplierId = createResponse.data.id;
                                console.log(`Proveedor creado exitosamente con ID: ${newSupplierId}`);

                                // Seleccionar el nuevo proveedor
                                this.form.supplier_id = newSupplierId;

                                // Recargar lista de proveedores
                                await this.reloadDataSuppliers(newSupplierId);

                                this.$message.success(`Proveedor ${supplierData.name} creado y seleccionado automáticamente`);
                            } else {
                                throw new Error('Error en la respuesta del servidor');
                            }
                        } catch (createError) {
                            console.error('Error al crear proveedor:', createError);
                            console.error('Detalles del error:', createError.response?.data);
                            console.error('Datos enviados:', newSupplierData);

                            let errorMessage = 'Error desconocido';
                            if (createError.response?.data?.errors) {
                                const errors = createError.response.data.errors;
                                errorMessage = Object.keys(errors).map(key => `${key}: ${errors[key].join(', ')}`).join('; ');

                                // Si es error de unicidad, sugerir búsqueda manual
                                if (errorMessage.includes('already been taken') || errorMessage.includes('ya ha sido tomado')) {
                                    errorMessage += '. Es posible que el proveedor ya exista con otro nombre.';
                                }
                            } else if (createError.response?.data?.message) {
                                errorMessage = createError.response.data.message;
                            }

                            this.$message.error({
                                message: `No se pudo crear automáticamente el proveedor ${supplierData.name} (${supplierData.identification_number}). Error: ${errorMessage}`,
                                duration: 8000
                            });
                        }
                    }
                } catch (error) {
                    console.error('Error al procesar proveedor del XML:', error);
                    this.$message.error({
                        message: 'Error al procesar la información del proveedor del XML',
                        duration: 5000
                    });
                }
            },

            /**
             * Cargar items desde el XML al formulario
             */
            async loadItemsFromXML(xmlItems) {
                try {
                    // Limpiar items actuales
                    this.form.items = [];

                    for (const [index, xmlItem] of xmlItems.entries()) {
                        const item = {
                            id: null, // Se asignará al buscar/crear el producto
                            item: null,
                            quantity: parseFloat(xmlItem.quantity) || 1,
                            unit_price: parseFloat(xmlItem.price_amount) || 0,
                            total: parseFloat(xmlItem.line_extension_amount) || 0,
                            description: xmlItem.item_description || '',
                            sellers_item_id: xmlItem.sellers_item_identification || '',
                            unit_type_id: 10, // Siempre "Unidad" como especificaste
                            warehouse_id: 1, // Siempre "Oficina Principal" como especificaste
                            // Agregar información de impuestos del XML
                            tax_id: xmlItem.tax_id_mapped || 1, // Usar el impuesto mapeado del XML o IVA por defecto
                            tax_percent: parseFloat(xmlItem.tax_percent) || 0,
                            tax_amount: parseFloat(xmlItem.tax_amount) || 0,
                            // Información adicional de impuestos para debugging
                            tax_id_xml: xmlItem.tax_id_xml || '',
                            tax_name_xml: xmlItem.tax_name_xml || '',
                            // Agregar información de descuentos del XML - Solo si realmente hay descuentos
                            discount: 0, // Siempre iniciar en 0
                            discount_percentage: 0, // Siempre iniciar en 0
                            discount_type: 'fixed', // Por defecto usar fijo
                            discount_reason: xmlItem.discount_reason || '',
                            has_discount: xmlItem.has_discount || false,
                            // Solo establecer valores temporales si realmente hay descuentos
                            discount_fixed_from_xml: (xmlItem.has_discount && parseFloat(xmlItem.discount_amount) > 0.01) ? parseFloat(xmlItem.discount_amount) : 0,
                            discount_multiplier_from_xml: (xmlItem.has_discount && parseFloat(xmlItem.discount_percentage) > 0.001) ? parseFloat(xmlItem.discount_percentage) : 0,
                            // Flag para identificar que este item viene del XML y tiene impuestos incluidos
                            from_xml: true,
                            prices_include_tax: false // El LineExtensionAmount en DIAN es SIN impuestos
                        };

                        // Usar el precio del XML directamente, NO calcularlo desde el total
                        // El LineExtensionAmount en DIAN es el precio base SIN impuestos y CON descuentos aplicados
                        // Por lo tanto, debemos usar este valor directamente como precio unitario base
                        const lineExtensionAmount = parseFloat(xmlItem.line_extension_amount) || 0;
                        const quantity = parseFloat(xmlItem.quantity) || 1;
                        const priceAmount = parseFloat(xmlItem.price_amount) || 0;

                        // Calcular el precio unitario sin impuestos basado en LineExtensionAmount
                        if (lineExtensionAmount > 0 && quantity > 0) {
                            item.unit_price = lineExtensionAmount / quantity;

                            // Solo detectar descuento si hay información explícita de descuento en el XML
                            if (xmlItem.has_discount && (xmlItem.discount_amount > 0 || xmlItem.discount_percentage > 0)) {
                                const discountAmount = parseFloat(xmlItem.discount_amount) || 0;
                                const discountPercentage = parseFloat(xmlItem.discount_percentage) || 0;

                                if (discountAmount > 0) {
                                    item.discount_type = 'fixed';
                                    item.discount = discountAmount;
                                    item.has_discount = true;
                                } else if (discountPercentage > 0) {
                                    item.discount_type = 'percentage';
                                    item.discount_percentage = discountPercentage;
                                    item.has_discount = true;
                                }
                            } else {
                                // No hay descuentos - PriceAmount vs LineExtensionAmount puede ser solo diferencia de estructura DIAN
                                item.has_discount = false;
                                item.discount = 0;
                            }
                        } else {
                            // Fallback al método anterior si no hay LineExtensionAmount válido
                            let xmlPrice = parseFloat(xmlItem.price_amount) || 0;
                            let xmlPriceAlt = parseFloat(xmlItem.price_amount_alt) || 0;

                            if (xmlPriceAlt > 0 && (xmlPriceAlt > xmlPrice || xmlPrice === 0)) {
                                const taxRate = parseFloat(xmlItem.tax_percent) || 0;
                                const priceWithTax = xmlPriceAlt;
                                item.unit_price = taxRate > 0 ? priceWithTax / (1 + (taxRate / 100)) : priceWithTax;
                            } else if (xmlPrice > 0) {
                                const taxRate = parseFloat(xmlItem.tax_percent) || 0;
                                const priceWithTax = xmlPrice;
                                item.unit_price = taxRate > 0 ? priceWithTax / (1 + (taxRate / 100)) : priceWithTax;
                            }
                        }

                        // Buscar o crear el producto automáticamente
                        await this.findOrCreateItemForXML(item, xmlItem);

                        // IMPORTANTE: Esperar un poco después de crear/buscar para asegurar sincronización
                        await new Promise(resolve => setTimeout(resolve, 100));

                        // Log del estado final del item después del procesamiento
                        console.log(`Item ${index + 1} procesado:`, {
                            description: item.description,
                            has_item_object: !!item.item,
                            item_id: item.id,
                            item_name: item.item?.name,
                            item_full_description: item.item?.full_description,
                            warehouse_description: item.warehouse_description,
                            item_warehouse_description: item.item?.warehouse_description,
                            unit_type_name: item.item?.unit_type?.name,
                            complete_item_object: JSON.stringify(item.item, null, 2)
                        });

                        // Usar Vue.set para agregar el item al array de forma reactiva
                        this.form.items.push(item);
                    }

                    // Ajustar descuentos después de cargar todos los items
                    this.form.items.forEach(item => {
                        // FORZAR: Solo procesar descuentos si hay valores reales significativos
                        const hasSignificantDiscountAmount = item.discount_fixed_from_xml > 0.01;
                        const hasSignificantDiscountPercentage = item.discount_multiplier_from_xml > 0.001;

                        console.log(`Evaluando descuentos para ${item.description}:`, {
                            has_discount_flag: item.has_discount,
                            discount_amount: item.discount_fixed_from_xml,
                            discount_percentage: item.discount_multiplier_from_xml,
                            hasSignificantDiscountAmount,
                            hasSignificantDiscountPercentage
                        });

                        if (hasSignificantDiscountAmount || hasSignificantDiscountPercentage) {
                            // Solo si hay valores significativos, aplicar descuentos
                            if (hasSignificantDiscountAmount) {
                                item.discount_type = 'fixed';
                                item.discount = item.discount_fixed_from_xml;
                                item.has_discount = true;
                                console.log(`✓ Descuento fijo aplicado a ${item.description}: ${item.discount}`);
                            } else if (hasSignificantDiscountPercentage) {
                                item.discount_type = 'percentage';
                                item.discount_percentage = item.discount_multiplier_from_xml * 100;
                                item.has_discount = true;
                                console.log(`✓ Descuento porcentual aplicado a ${item.description}: ${item.discount_percentage}%`);
                            }
                        } else {
                            // FORZAR: Eliminar cualquier descuento, sin importar flags del XML
                            item.discount = 0;
                            item.discount_percentage = 0;
                            item.discount_type = 'fixed';
                            item.has_discount = false;
                            console.log(`✗ SIN descuentos para ${item.description} - valores forzados a 0`);
                        }
                    });

                    // Limpiar campos temporales
                    this.form.items.forEach(item => {
                        delete item.discount_fixed_from_xml;
                        delete item.discount_multiplier_from_xml;
                    });

                    // Usar $nextTick para asegurar que todos los cambios reactivos se procesen
                    this.$nextTick(async () => {
                        // Esperar un poco más para que la sincronización de items sea completa
                        await new Promise(resolve => setTimeout(resolve, 200));

                        // Recalcular totales después de que todo esté sincronizado
                        this.calculateTotal();

                        // Forzar actualización de la vista
                        this.$forceUpdate();


                        // Emitir evento global para asegurar que todos los modales estén actualizados
                        this.$eventHub.$emit('itemsUpdated');
                    });

                } catch (error) {
                    console.error('Error al cargar items:', error);
                    this.$message.error('Error al procesar los items del XML');
                }
            },

            /**
             * Buscar o crear producto automáticamente para item del XML
             */
            async findOrCreateItemForXML(item, xmlItem) {
                try {
                    // Buscar por código de vendedor o descripción
                    let searchTerm = xmlItem.sellers_item_identification || xmlItem.item_description || '';

                    if (!searchTerm) {
                        console.log('No hay información suficiente para buscar el producto');
                        return;
                    }

                    // Buscar producto existente
                    const searchResponse = await this.$http.get('/main-items/search', {
                        params: {
                            input: searchTerm
                        }
                    });

                    if (searchResponse.data && searchResponse.data.items && searchResponse.data.items.length > 0) {
                        // Producto existe, seleccionarlo
                        const existingItem = searchResponse.data.items[0];
                        item.id = existingItem.id;
                        item.item_id = existingItem.id;

                        // Usar Vue.set para asegurar reactividad
                        this.$set(item, 'item', existingItem);
                        this.$set(item, 'warehouse_description', 'Oficina Principal');
                        this.$set(item, 'unit_type_description', existingItem.unit_type?.description || existingItem.unit_type?.name || 'Unidad');

                        // Asegurar que el item tenga warehouse_description
                        if (!existingItem.warehouse_description) {
                            this.$set(existingItem, 'warehouse_description', 'Oficina Principal');
                        }

                        // Asegurar que tenga información de unidad
                        if (!existingItem.unit_type) {
                            this.$set(existingItem, 'unit_type', {
                                id: 10,
                                name: 'Unidad',
                                description: 'Unidad'
                            });
                        }

                        // IMPORTANTE: Forzar Vue a procesar cambios reactivos
                        this.$forceUpdate();

                        // Esperar que Vue procese los cambios
                        await this.$nextTick();

                        // Emitir evento para asegurar que el producto esté en la lista del modal
                        this.$eventHub.$emit('reloadDataItems', existingItem.id);
                        return;
                    }

                    // Producto no existe, crearlo automáticamente

                    // Usar el mejor precio disponible (priorizar alternativo si es mayor)
                    let bestPrice = parseFloat(xmlItem.price_amount) || 0;
                    let altPrice = parseFloat(xmlItem.price_amount_alt) || 0;
                    if (altPrice > bestPrice) {
                        bestPrice = altPrice;
                    }

                    // Para productos del XML, extraer el precio SIN impuestos para la base de datos
                    const taxRate = parseFloat(xmlItem.tax_percent) || 0;
                    const priceWithoutTax = taxRate > 0 ? bestPrice / (1 + (taxRate / 100)) : bestPrice;

                    // Redondear el precio a 2 decimales para evitar errores SQL de precisión
                    const roundedPrice = Math.round(priceWithoutTax * 100) / 100;

                    // Asegurar que el precio esté dentro del rango válido para decimal(12,4)
                    // Máximo: 99999999.9999 (8 dígitos enteros, 4 decimales)
                    const safePrice = Math.min(roundedPrice, 99999999.99);

                    // Generar un internal_id único y seguro
                    const timestamp = Date.now();
                    const randomSuffix = Math.floor(Math.random() * 1000);
                    const safeInternalId = xmlItem.sellers_item_identification || `XML-${timestamp}-${randomSuffix}`;

                    // Limpiar y validar nombre y descripción
                    const cleanName = (xmlItem.item_description || 'Producto Importado')
                        .replace(/[^\w\s\-\.]/g, '') // Remover caracteres especiales
                        .substring(0, 80) // Límite más conservador
                        .trim();

                    const cleanDescription = (xmlItem.item_description || 'Producto importado desde XML DIAN')
                        .replace(/[^\w\s\-\.]/g, '')
                        .substring(0, 200) // Límite más conservador
                        .trim();

                    const newItemData = {
                        name: cleanName,
                        description: cleanDescription,
                        item_type_id: '01', // Producto
                        internal_id: safeInternalId.substring(0, 50), // Asegurar límite
                        currency_type_id: 170, // COP - Peso Colombiano
                        sale_unit_price: safePrice,
                        purchase_unit_price: safePrice,
                        unit_type_id: 10, // Unidad
                        stock: 100, // Stock inicial por defecto
                        stock_min: 1, // Stock mínimo
                        // Impuestos básicos (IVA e Impuesto de compra requeridos)
                        tax_id: 1, // IVA por defecto (puede ser null)
                        purchase_tax_id: 1, // IVA requerido para compras
                        calculate_quantity: false,
                        has_igv: true,
                        amount_plastic_bag_taxes: 0,
                        percentage_isc: 0,
                        suggested_price: safePrice,
                        // Item unit types (requerido por el controlador)
                        item_unit_types: [
                            {
                                id: null,
                                description: cleanName,
                                unit_type_id: 10, // Unidad
                                quantity_unit: 1,
                                price1: safePrice,
                                price2: safePrice,
                                price3: safePrice,
                                price_default: 1 // Campo boolean: 1 = true (este es el precio por defecto)
                            }
                        ],
                        // Warehouses
                        warehouses: [
                            {
                                warehouse_id: 1, // Oficina Principal
                                stock: 100
                            }
                        ]
                    };

                    try {
                        console.log('Datos enviados para crear producto:', JSON.stringify(newItemData, null, 2));
                        const createResponse = await this.$http.post('/items', newItemData);

                        if (createResponse.data.success) {
                            const newItemId = createResponse.data.id;
                            console.log(`Producto creado exitosamente con ID: ${newItemId}`);

                            // Asignar directamente toda la información necesaria para la tabla
                            item.id = newItemId;
                            item.item_id = newItemId;

                            // Crear objeto completo del item con toda la información necesaria
                            const completeItem = {
                                id: newItemId,
                                name: newItemData.name, // Nombre para la tabla
                                description: newItemData.description,
                                internal_id: newItemData.internal_id,
                                full_description: `${newItemData.internal_id} - ${newItemData.name}`,
                                currency_type_id: newItemData.currency_type_id,
                                currency_type_symbol: '$',
                                sale_unit_price: newItemData.sale_unit_price,
                                purchase_unit_price: newItemData.purchase_unit_price,
                                unit_type_id: newItemData.unit_type_id,
                                purchase_tax_id: newItemData.purchase_tax_id,
                                // Información de warehouse
                                warehouse_description: 'Oficina Principal',
                                // Información de unit_type - MUY IMPORTANTE para la tabla
                                unit_type: {
                                    id: 10,
                                    name: 'Unidad',
                                    description: 'Unidad',
                                    code: '70'
                                },
                                // Otras propiedades necesarias
                                lots_enabled: false,
                                series_enabled: false,
                                has_perception: false,
                                percentage_perception: 0
                            };

                            // Usar Vue.set para asegurar reactividad
                            this.$set(item, 'item', completeItem);
                            this.$set(item, 'warehouse_description', 'Oficina Principal');
                            this.$set(item, 'unit_type_description', 'Unidad');

                            // IMPORTANTE: Forzar Vue a procesar todos los cambios reactivos inmediatamente
                            this.$forceUpdate();

                            // Esperar que Vue procese todos los cambios
                            await this.$nextTick();

                            console.log(`Producto configurado con información completa:`, {
                                item_name: item.item.name,
                                item_unit_type_name: item.item.unit_type.name,
                                item_warehouse_description: item.item.warehouse_description,
                                warehouse_description: item.warehouse_description
                            });

                            // Emitir evento para recargar la lista de productos en el modal item
                            this.$eventHub.$emit('reloadDataItems', newItemId);
                            // También recargar toda la lista para asegurar disponibilidad
                            this.$eventHub.$emit('reloadAllItems');
                            console.log(`Eventos reloadDataItems y reloadAllItems emitidos para producto ID: ${newItemId}`);

                        } else {
                            throw new Error('Error en la respuesta del servidor al crear producto');
                        }
                    } catch (createError) {
                        console.error('Error al crear producto:', createError);

                        // Capturar más información del error
                        if (createError.response) {
                            console.error('Error response:', createError.response.data);
                            console.error('Status:', createError.response.status);
                            console.error('Headers:', createError.response.headers);
                        }

                        // El item se mantendrá sin ID, mostrando "Producto no disponible"
                    }

                } catch (error) {
                    console.error('Error al buscar/crear producto:', error);
                }
            }
        }
    }
</script>

<style scoped>
/* Diseño simple y limpio */
.invoice-form {
    background: #f8f9fa;
}

.form-section {
    background: white;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-left: 4px solid #409EFF;
}

.section-header {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #e9ecef;
}

.section-header i {
    color: #409EFF;
    margin-right: 10px;
}

.form-section:nth-child(2) { border-left-color: #67C23A; }
.form-section:nth-child(3) { border-left-color: #E6A23C; }
.form-section:nth-child(4) { border-left-color: #F56C6C; }

.cliente-link {
    color: #409EFF;
    font-weight: bold;
    font-size: 12px;
    text-decoration: none;
    transition: color 0.2s;
}

.cliente-link:hover { color: #66b1ff; }
.search-icon { font-size: 20px; margin-right: 5px; }

/* Estilos para el modal XML DIAN */
.el-dialog__body .form-group {
    margin-bottom: 20px;
}

/* Mejorar el espaciado del input */
.el-dialog[aria-label="Leer XML desde la DIAN"] .el-input__inner {
    font-family: 'Courier New', monospace; /* Fuente monoespaciada para mejor visualización de 96 caracteres */
    font-size: 14px;
    min-height: 45px;
    line-height: 45px;
}

/* Ajustar el contador de caracteres */
.el-input__count {
    right: 8px;
    bottom: 3px;
    font-size: 12px;
}

/* Asegurar que el input sea suficientemente ancho */
.el-input {
    width: 100% !important;
}

/* Mejorar el espaciado del modal XML DIAN */
.el-dialog[aria-label="Leer XML desde la DIAN"] .el-dialog__body {
    padding: 30px 40px;
}

.el-dialog[aria-label="Leer XML desde la DIAN"] .el-input__inner {
    min-height: 45px;
    line-height: 45px;
}
</style>
