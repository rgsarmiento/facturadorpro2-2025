
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');
import Vue from 'vue'
import ElementUI from 'element-ui'
import Axios from 'axios'

import lang from 'element-ui/lib/locale/lang/es'
import locale from 'element-ui/lib/locale'
locale.use(lang)




//Vue.use(ElementUI)
Vue.use(ElementUI, {size: 'small'})
Vue.prototype.$eventHub = new Vue()
Vue.prototype.$http = Axios


Vue.prototype.$setStorage =   function(name, obj){
    localStorage.setItem(name, JSON.stringify(obj))
}

Vue.prototype.$getStorage = function(name){
    return JSON.parse(localStorage.getItem(name))
}

Vue.prototype.$removeStorage = function(name){
    localStorage.removeItem(name)
}

// Vuetify es
// Vue.use(Vuetify, {
//     lang: {
//         locales: {es},
//         current: 'es'
//     }
// });


// Vue.use(VeeValidate);


// require('./factcolombia');
// Vue.prototype.$setLaravelValidationErrorsFromResponse = function(errorResponse) {
//     if (!this.hasOwnProperty('$validator')) return;

//     this.$validator.errors.clear();

//     if (!errorResponse.hasOwnProperty('errors')) return;

//     let errorFields = Object.keys(errorResponse.errors);
//     let form_error = '';

//     if (errorFields.includes('form_error')) form_error += `${errorResponse.errors['form_error'].join()}.`;

//     for (let i = 0; i < errorFields.length; i++) {
//         let field = errorFields[i];
//         let errorString = errorResponse.errors[field].join(', ');

//         this.$validator.errors.add({
//             field: `${form_error}${field}`,
//             msg: errorString
//         });
//     }
// };

// // Add message request
// Vue.prototype.$setLaravelMessage = function(response) {


//     if ((response.hasOwnProperty('success')) && (response.hasOwnProperty('message')) && (!response.success)) this.$root.$emit('addSnackbarNotification', {text: response.message, color: 'error'});

//     if ((response.hasOwnProperty('success')) && (response.hasOwnProperty('message')) && (response.success)) this.$root.$emit('addSnackbarNotification', {text: response.message, color: 'success'});

//     if (response.hasOwnProperty('message') && (!response.hasOwnProperty('success'))) this.$root.$emit('addSnackbarNotification', {text: response.message, color: 'info'});
// };

// // Add errors server
// Vue.prototype.$setLaravelErrors = function(errorResponse) {


//     if ((errorResponse.hasOwnProperty('message')) && (errorResponse.message != '')) this.$root.$emit('addSnackbarNotification', {text: errorResponse.message, color: 'error'});

//components colombia

Vue.component('tenant-note-form', require('@viewsModuleProColombia/tenant/document/note.vue'));
Vue.component('tenant-document-form', require('@viewsModuleProColombia/tenant/document/Form2.vue'));
Vue.component('tenant-document-form-aiu', require('@viewsModuleProColombia/tenant/document/FormAiu.vue'));
Vue.component('tenant-document-index', require('@viewsModuleProColombia/tenant/document/index.vue'));
Vue.component('system-company-company', require('@viewsModuleProColombia/system/company/index.vue'));
Vue.component('tenant-item-item', require('@viewsModuleProColombia/tenant/item/index.vue'));
Vue.component('tenant-tax-tax-co', require('@viewsModuleProColombia/tenant/tax/index.vue'));
Vue.component('tenant-taxes-form', require('@viewsModuleProColombia/tenant/tax/form.vue'));
Vue.component('tenant-client-client', require('@viewsModuleProColombia/tenant/client/index.vue'));
Vue.component('tenant-import-import', require('@viewsModuleProColombia/tenant/import/Import.vue'));


//components colombia
// Vue.component('tenant-document-form', require('@viewsModuleProColombia/tenant/configuration/Configuration.vue'));
// Vue.component('tenant-document-form', require('@viewsModuleProColombia/tenant/document/Form.vue'));

//colombia
Vue.component('tenant-document-form', require('@viewsModuleProColombia/tenant/document/Form2.vue'));
//Vue.component('tenant-configuration-configuration', require('@viewsModuleProColombia/tenant/configuration/Configuration.vue'));
Vue.component('tenant-configuration-general-data', require('@viewsModuleProColombia/tenant/configuration/GeneralData.vue'));
Vue.component('tenant-configuration-software', require('@viewsModuleProColombia/tenant/configuration/Software.vue'));
Vue.component('tenant-configuration-certificate', require('@viewsModuleProColombia/tenant/configuration/Certificate.vue'));
Vue.component('tenant-configuration-resolution', require('@viewsModuleProColombia/tenant/configuration/Resolution.vue'));
Vue.component('tenant-configuration-documents', require('@viewsModuleProColombia/tenant/configuration/Documents.vue'));
Vue.component('tenant-configuration-change-ambient', require('@viewsModuleProColombia/tenant/configuration/Production.vue'))
Vue.component('tenant-configuration-software-payroll', require('@viewsModuleProColombia/tenant/configuration/SoftwarePayroll.vue'));
Vue.component('tenant-configuration-software-eqdocs', require('@viewsModuleProColombia/tenant/configuration/SoftwareEqDocs.vue'));

//colombia

Vue.component('tenant-dashboard-index', require('../../modules/Dashboard/Resources/assets/js/views/index.vue'));
Vue.component('x-graph', require('./components/graph/src/Graph.vue'));
Vue.component('x-graph-line', require('./components/graph/src/GraphLine.vue'));
Vue.component('tenant-companies-form', require('./views/tenant/companies/form.vue'));
Vue.component('tenant-companies-logo', require('./views/tenant/companies/logo.vue'));
Vue.component('tenant-certificates-index', require('./views/tenant/certificates/index.vue'));
Vue.component('tenant-certificates-form', require('./views/tenant/certificates/form.vue'));
Vue.component('tenant-configurations-form', require('./views/tenant/configurations/form.vue'));
Vue.component('tenant-configurations-visual', require('./views/tenant/configurations/visual.vue'));
Vue.component('tenant-configurations-pdf', require('./views/tenant/configurations/pdf_templates.vue'));
// Vue.component('tenant-establishments-form', require('./views/tenant/establishments/form.vue'));
// Vue.component('tenant-series-form', require('./views/tenant/series/form.vue'));
Vue.component('tenant-bank_accounts-index', require('./views/tenant/bank_accounts/index.vue'));
Vue.component('tenant-backup-index', require('../../modules/Backup/Resources/assets/js/views/index.vue'));
Vue.component('tenant-items-index', require('./views/tenant/items/index.vue'));
Vue.component('tenant-persons-index', require('./views/tenant/persons/index.vue'));
// Vue.component('tenant-customers-index', require('./views/tenant/customers/index.vue'));
// Vue.component('tenant-suppliers-index', require('./views/tenant/suppliers/index.vue'));
Vue.component('tenant-users-form', require('./views/tenant/users/form.vue'));
Vue.component('tenant-documents-index', require('./views/tenant/documents/index.vue'));
Vue.component('tenant-documents-invoice', require('./views/tenant/documents/invoice.vue'));
Vue.component('tenant-documents-invoicetensu', require('./views/tenant/documents/invoicetensu.vue'));
Vue.component('tenant-documents-note', require('./views/tenant/documents/note.vue'));
Vue.component('tenant-summaries-index', require('./views/tenant/summaries/index.vue'));
Vue.component('tenant-voided-index', require('./views/tenant/voided/index.vue'));
Vue.component('tenant-search-index', require('./views/tenant/search/index.vue'));
Vue.component('tenant-options-form', require('./views/tenant/options/form.vue'));
Vue.component('tenant-unit_types-index', require('./views/tenant/unit_types/index.vue'));
Vue.component('tenant-detraction_types-index', require('./views/tenant/detraction_types/index.vue'));
Vue.component('tenant-users-index', require('./views/tenant/users/index.vue'));
Vue.component('tenant-establishments-index', require('./views/tenant/establishments/index.vue'));
Vue.component('tenant-charge_discounts-index', require('./views/tenant/charge_discounts/index.vue'));
Vue.component('tenant-banks-index', require('./views/tenant/banks/index.vue'));
Vue.component('tenant-exchange_rates-index', require('./views/tenant/exchange_rates/index.vue'));
Vue.component('tenant-currency-types-index', require('./views/tenant/currency_types/index.vue'));
Vue.component('tenant-retentions-index', require('./views/tenant/retentions/index.vue'));
Vue.component('tenant-retentions-form', require('./views/tenant/retentions/form.vue'));
Vue.component('tenant-perceptions-index', require('./views/tenant/perceptions/index.vue'));
Vue.component('tenant-perceptions-form', require('./views/tenant/perceptions/form.vue'));
Vue.component('tenant-dispatches-index', require('./views/tenant/dispatches/index.vue'));
Vue.component('tenant-dispatches-form', require('./views/tenant/dispatches/form.vue'));
Vue.component('tenant-dispatches-create', require('./views/tenant/dispatches/create.vue'));
Vue.component('tenant-purchases-index', require('./views/tenant/purchases/index.vue'));
Vue.component('tenant-purchases-form', require('./views/tenant/purchases/form.vue'));
Vue.component('tenant-purchases-edit', require('./views/tenant/purchases/form_edit.vue'));
Vue.component('tenant-purchases-note-form', require('./views/tenant/purchases/form_note.vue'));

Vue.component('tenant-purchases-items', require('./views/tenant/dispatches/items.vue'));
Vue.component('tenant-attribute_types-index', require('./views/tenant/attribute_types/index.vue'));
Vue.component('tenant-calendar', require('./views/tenant/components/calendar.vue'));
Vue.component('tenant-warehouses', require('./views/tenant/components/warehouses.vue'));
Vue.component('tenant-calendar-quotation', require('./views/tenant/components/calendarquotations.vue'));

//Vue.component('tenant-calendar', require('./views/tenant/components/calendar.vue'));
Vue.component('tenant-product', require('./views/tenant/components/products.vue'));


Vue.component('tenant-tasks-lists', require('./views/tenant/tasks/lists.vue'));
Vue.component('tenant-tasks-form', require('./views/tenant/tasks/form.vue'));
Vue.component('tenant-reports-consistency-documents-lists', require('./views/tenant/reports/consistency-documents/lists.vue'));
Vue.component('tenant-contingencies-index', require('./views/tenant/contingencies/index.vue'));

Vue.component('tenant-quotations-index', require('./views/tenant/quotations/index.vue'));
Vue.component('tenant-quotations-form', require('./views/tenant/quotations/form.vue'));
Vue.component('tenant-quotations-edit', require('./views/tenant/quotations/form_edit.vue'));

Vue.component('tenant-sale-notes-index', require('./views/tenant/sale_notes/index.vue'));
Vue.component('tenant-sale-notes-form', require('./views/tenant/sale_notes/form.vue'));
Vue.component('tenant-pos-note-form', require('./views/tenant/pos/partials/note.vue'));
Vue.component('tenant-pos-index', require('./views/tenant/pos/index.vue'));
Vue.component('tenant-pos-configuration', require('./views/tenant/pos/configuration.vue'));
Vue.component('tenant-pos-documents', require('./views/tenant/pos/documents.vue'));
Vue.component('tenant-pos-refund', require('./views/tenant/pos/refund.vue'));

Vue.component('cash-index', require('./views/tenant/cash/index.vue'));
Vue.component('tenant-card-brands-index', require('./views/tenant/card_brands/index.vue'));

Vue.component('tenant-payment-method-index', require('./views/tenant/payment_method/index.vue'));
Vue.component('tenant-payment-method-index', require('./views/tenant/payment_method/index.vue'));



// Modules
Vue.component('inventory-index', require('../../modules/Inventory/Resources/assets/js/inventory/index.vue'));
Vue.component('inventory-transfers-index', require('../../modules/Inventory/Resources/assets/js/transfers/index.vue'));
Vue.component('warehouses-index', require('../../modules/Inventory/Resources/assets/js/warehouses/index.vue'));
Vue.component('tenant-report-kardex-index', require('../../modules/Inventory/Resources/assets/js/kardex/index.vue'));
Vue.component('tenant-inventories-form', require('../../modules/Inventory/Resources/assets/js/config/form.vue'));
Vue.component('tenant-expenses-index', require('../../modules/Expense/Resources/assets/js/views/expenses/index.vue'));
Vue.component('tenant-expenses-form', require('../../modules/Expense/Resources/assets/js/views/expenses/form.vue'));
Vue.component('tenant-account-export', require('../../modules/Account/Resources/assets/js/views/account/export.vue'));
Vue.component('tenant-account-summary-report', require('../../modules/Account/Resources/assets/js/views/summary_report/index.vue'));
Vue.component('tenant-account-format', require('../../modules/Account/Resources/assets/js/views/account/format.vue'));
Vue.component('tenant-company-accounts', require('../../modules/Account/Resources/assets/js/views/company_accounts/form.vue'));

Vue.component('tenant-documents-not-sent', require('../../modules/Document/Resources/assets/js/views/documents/not_sent.vue'));
Vue.component('tenant-report-purchases-index', require('../../modules/Report/Resources/assets/js/views/purchases/index.vue'));
Vue.component('tenant-report-documents-index', require('../../modules/Report/Resources/assets/js/views/documents/index.vue'));

Vue.component('tenant-report-document-pos-index', require('../../modules/Report/Resources/assets/js/views/document_pos/index.vue'));

Vue.component('tenant-report-customers-index', require('../../modules/Report/Resources/assets/js/views/customers/index.vue'));
Vue.component('tenant-report-items-index', require('../../modules/Report/Resources/assets/js/views/items/index.vue'));
Vue.component('tenant-report-sale_notes-index', require('../../modules/Report/Resources/assets/js/views/sale_notes/index.vue'));
Vue.component('tenant-report-quotations-index', require('../../modules/Report/Resources/assets/js/views/quotations/index.vue'));
Vue.component('tenant-report-cash-index', require('../../modules/Report/Resources/assets/js/views/cash/index.vue'));
Vue.component('tenant-index-configuration', require('../../modules/BusinessTurn/Resources/assets/js/views/configurations/index.vue'));
Vue.component('tenant-report-document_hotels-index', require('../../modules/Report/Resources/assets/js/views/document_hotels/index.vue'));
Vue.component('tenant-report-commercial_analysis-index', require('../../modules/Report/Resources/assets/js/views/commercial_analysis/index.vue'));
Vue.component('tenant-offline-configurations-index', require('../../modules/Offline/Resources/assets/js/views/offline_configurations/index.vue'));
Vue.component('tenant-series-configurations-index', require('../../modules/Document/Resources/assets/js/views/series_configurations/index.vue'));
Vue.component('tenant-validate-documents-index', require('../../modules/Document/Resources/assets/js/views/validate_documents/index.vue'));
Vue.component('tenant-report-document-detractions-index', require('../../modules/Report/Resources/assets/js/views/document-detractions/index.vue'));
Vue.component('tenant-report-commissions-index', require('../../modules/Report/Resources/assets/js/views/commissions/index.vue'));
Vue.component('tenant-report-order-notes-consolidated-index', require('../../modules/Report/Resources/assets/js/views/order_notes_consolidated/index.vue'));
Vue.component('tenant-report-general-items-index', require('../../modules/Report/Resources/assets/js/views/general_items/index.vue'));
Vue.component('tenant-report-order-notes-general-index', require('../../modules/Report/Resources/assets/js/views/order_notes_general/index.vue'));
Vue.component('tenant-report-sales-consolidated-index', require('../../modules/Report/Resources/assets/js/views/sales_consolidated/index.vue'));

Vue.component('tenant-report-user-commissions-index', require('../../modules/Report/Resources/assets/js/views/user_commissions/index.vue'));

Vue.component('tenant-report-tax-index', require('../../modules/Report/Resources/assets/js/views/taxes/index.vue'));

Vue.component('tenant-report-co-remissions-index', require('../../modules/Report/Resources/assets/js/views/co-remissions/index.vue'));
Vue.component('tenant-report-co-items-sold-index', require('@viewsModuleReport/co-items-sold/index.vue'));
Vue.component('tenant-report-co-sales-book-index', require('@viewsModuleReport/co-sales-book/index.vue'));


Vue.component('tenant-categories-index', require('../../modules/Item/Resources/assets/js/views/categories/index.vue'));
Vue.component('tenant-brands-index', require('../../modules/Item/Resources/assets/js/views/brands/index.vue'));
Vue.component('tenant-incentives-index', require('../../modules/Item/Resources/assets/js/views/incentives/index.vue'));

Vue.component('tenant-ecommerce-configuration-info', require('../../modules/Ecommerce/Resources/assets/js/views/configuration/index.vue'));
Vue.component('tenant-ecommerce-configuration-culqi', require('../../modules/Ecommerce/Resources/assets/js/views/configuration_culqi/index.vue'));
Vue.component('tenant-ecommerce-configuration-paypal', require('../../modules/Ecommerce/Resources/assets/js/views/configuration_paypal/index.vue'));
Vue.component('tenant-ecommerce-configuration-logo', require('../../modules/Ecommerce/Resources/assets/js/views/configuration_logo/index.vue'));
Vue.component('tenant-ecommerce-configuration-social', require('../../modules/Ecommerce/Resources/assets/js/views/configuration_social/index.vue'));
Vue.component('tenant-ecommerce-configuration-tag', require('../../modules/Ecommerce/Resources/assets/js/views/configuration_tags/index.vue'));

Vue.component('tenant-purchase-quotations-index', require('../../modules/Purchase/Resources/assets/js/views/purchase-quotations/index.vue'));
Vue.component('tenant-purchase-quotations-form', require('../../modules/Purchase/Resources/assets/js/views/purchase-quotations/form.vue'));

Vue.component('tenant-purchase-orders-index', require('../../modules/Purchase/Resources/assets/js/views/purchase-orders/index.vue'));
Vue.component('tenant-purchase-orders-form', require('../../modules/Purchase/Resources/assets/js/views/purchase-orders/form.vue'));
Vue.component('tenant-purchase-orders-generate', require('../../modules/Purchase/Resources/assets/js/views/purchase-orders/generate.vue'));

Vue.component('moves-index', require('../../modules/Inventory/Resources/assets/js/moves/index.vue'));
Vue.component('inventory-form-masive', require('../../modules/Inventory/Resources/assets/js/transfers/form_masive.vue'));

Vue.component('tenant-report-kardex-master', require('../../modules/Inventory/Resources/assets/js/kardex_master/index.vue'));
Vue.component('tenant-report-kardex-lots', require('../../modules/Inventory/Resources/assets/js/kardex/lots.vue'));
Vue.component('tenant-report-kardex-series', require('../../modules/Inventory/Resources/assets/js/kardex/series.vue'));

Vue.component('tenant-order-notes-index', require('../../modules/Order/Resources/assets/js/views/order_notes/index.vue'));
Vue.component('tenant-order-notes-form', require('../../modules/Order/Resources/assets/js/views/order_notes/form.vue'));
Vue.component('tenant-order-notes-edit', require('../../modules/Order/Resources/assets/js/views/order_notes/form_edit.vue'));
Vue.component('tenant-report-valued-kardex', require('../../modules/Inventory/Resources/assets/js/valued_kardex/index.vue'));

//Finance
Vue.component('tenant-finance-global-payments-index', require('../../modules/Finance/Resources/assets/js/views/global_payments/index.vue'));
Vue.component('tenant-finance-balance-index', require('../../modules/Finance/Resources/assets/js/views/balance/index.vue'));
Vue.component('tenant-finance-payment-method-types-index', require('../../modules/Finance/Resources/assets/js/views/payment_method_types/index.vue'));
Vue.component('tenant-finance-unpaid-index', require('@viewsModuleFinance/unpaid/index.vue'));
Vue.component('tenant-finance-to-pay-index', require('@viewsModuleFinance/to_pay/index.vue'));
Vue.component('tenant-finance-income-index', require('@viewsModuleFinance/income/index.vue'));
Vue.component('tenant-finance-income-form', require('@viewsModuleFinance/income/form.vue'));
Vue.component('tenant-income-types-index', require('@viewsModuleFinance/income_types/index.vue'));
Vue.component('tenant-income-reasons-index', require('@viewsModuleFinance/income_reasons/index.vue'));


//Sale
Vue.component('tenant-sale-opportunities-index', require('@viewsModuleSale/sale_opportunities/index.vue'));
Vue.component('tenant-sale-opportunities-form', require('@viewsModuleSale/sale_opportunities/form.vue'));
Vue.component('tenant-payment-method-types-index', require('@viewsModuleSale/payment_method_types/index.vue'));
Vue.component('tenant-contracts-index', require('@viewsModuleSale/contracts/index.vue'));
Vue.component('tenant-contracts-form', require('@viewsModuleSale/contracts/form.vue'));
Vue.component('tenant-production-orders-index', require('@viewsModuleSale/production_orders/index.vue'));

//Purchase

Vue.component('tenant-fixed-asset-items-index', require('@viewsModulePurchase/fixed_asset_items/index.vue'));
Vue.component('tenant-fixed-asset-purchases-index', require('@viewsModulePurchase/fixed_asset_purchases/index.vue'));
Vue.component('tenant-fixed-asset-purchases-form', require('@viewsModulePurchase/fixed_asset_purchases/form.vue'));

//Expense

Vue.component('tenant-expense-types-index', require('@viewsModuleExpense/expense_types/index.vue'));
Vue.component('tenant-expense-reasons-index', require('@viewsModuleExpense/expense_reasons/index.vue'));
Vue.component('tenant-expense-method-types-index', require('@viewsModuleExpense/expense_method_types/index.vue'));

//technical Services
Vue.component('tenant-technical-services-index', require('@viewsModuleSale/technical-services/index.vue'));
Vue.component('tenant-user-commissions-index', require('@viewsModuleSale/user-commissions/index.vue'));

//payroll
Vue.component('tenant-workers-index', require('@viewsModulePayroll/workers/index.vue'));
Vue.component('tenant-document-payrolls-index', require('@viewsModulePayroll/document-payrolls/index.vue'));
Vue.component('tenant-document-payrolls-form', require('@viewsModulePayroll/document-payrolls/form.vue'));
Vue.component('tenant-block-payrolls-index', require('@viewsModulePayroll/block-payrolls/index.vue'));
Vue.component('tenant-block-payrolls-form', require('@viewsModulePayroll/block-payrolls/form.vue'));

// Vue.component('tenant-type-workers-index', require('@viewsModulePayroll/type-workers/index.vue'));
// Vue.component('tenant-sub-type-workers-index', require('@viewsModulePayroll/sub-type-workers/index.vue'));

// documento soporte
Vue.component('tenant-support-documents-index', require('@viewsModulePurchase/support-documents/index.vue'));
Vue.component('tenant-support-documents-form', require('@viewsModulePurchase/support-documents/form.vue'));
Vue.component('tenant-support-documents-form-adjust-note', require('@viewsModulePurchase/support-documents/form_adjust_note.vue'));

// evento radian
Vue.component('tenant-co-radian-event-reception-index', require('@viewsModuleRadianEvent/reception/index.vue'));
Vue.component('tenant-co-radian-event-manage-index', require('@viewsModuleRadianEvent/manage/index.vue'));
Vue.component('tenant-co-radian-event-process-emails-index', require('@viewsModuleRadianEvent/process-emails/index.vue'));
Vue.component('tenant-co-radian-event-radiancufe-index', require('@viewsModuleRadianEvent/manage/radiancufe.vue'));


// advanced-configuration
Vue.component('tenant-advanced-configuration-index', require('@viewsModuleProColombia/tenant/advanced-configuration/index.vue'));

// Remissions
Vue.component('tenant-co-remissions-index', require('@viewsModuleSale/co-remissions/index.vue'));
Vue.component('tenant-co-remissions-form', require('@viewsModuleSale/co-remissions/form.vue'));


// System
Vue.component('system-clients-index', require('./views/system/clients/index.vue'));
Vue.component('system-clients-form', require('./views/system/clients/form.vue'));
Vue.component('system-users-form', require('./views/system/users/form.vue'));

Vue.component('system-certificate-index', require('./views/system/certificate/index.vue'));
Vue.component('system-companies-form', require('./views/system/companies/form.vue'));



Vue.component('system-plans-index', require('./views/system/plans/index.vue'));
Vue.component('system-plans-form', require('./views/system/plans/form.vue'));

Vue.component('x-input-service', require('./components/InputService.vue'));

Vue.component('tenant-items-ecommerce-index', require('./views/tenant/items_ecommerce/index.vue'));
Vue.component('tenant-ecommerce-cart', require('./views/tenant/ecommerce/cart_dropdown.vue'));
Vue.component('tenant-tags-index', require('./views/tenant/tags/index.vue'));
Vue.component('tenant-promotions-index', require('./views/tenant/promotions/index.vue'));

Vue.component('tenant-item-sets-index', require('./views/tenant/item_sets/index.vue'));
Vue.component('tenant-person-types-index', require('./views/tenant/person_types/index.vue'));

Vue.component('tenant-orders-index', require('./views/tenant/orders/index.vue'));

//Cuenta
Vue.component('tenant-account-payment-index', require('./views/tenant/account/payment_index.vue'));
Vue.component('tenant-account-configuration-index', require('./views/tenant/account/configuration.vue'));

//Cuentas Contables
Vue.component('tenant-cuentas-contables-index', require('./views/tenant/cuentas_contables/index.vue'));
Vue.component('tenant-cuentas-contables-form', require('./views/tenant/cuentas_contables/form.vue'));

//auto update
Vue.component('system-update', require('./views/system/update/index.vue'));

// Componente de Asientos Contables
Vue.component('asiento-form-component', {
    template: `
        <form @submit.prevent="saveAsiento">
            <div class="row">
                <!-- Información básica del asiento -->
                <div class="col-md-8">
                    <h5>Información del Asiento</h5>

                        <div class="form-group">
                            <label>Tipo de Comprobante *</label>
                            <select ref="tipoComprobanteSelect"
                                    class="form-control select2-search"
                                    required>
                                <option value="">Seleccionar tipo de comprobante</option>
                            </select>
                            <small v-if="proximoConsecutivo" class="form-text text-muted">
                                Próximo consecutivo: <strong>#<span v-text="proximoConsecutivo"></span></strong>
                            </small>
                        </div>                    <div class="form-group">
                        <label>Fecha del Asiento *</label>
                        <input type="date"
                               v-model="form.fecha_asiento"
                               class="form-control"
                               required>
                    </div>

                    <div class="form-group">
                        <label>Concepto *</label>
                        <textarea v-model="form.concepto"
                                  class="form-control"
                                  rows="3"
                                  placeholder="Descripción del asiento contable"
                                  required></textarea>
                    </div>
                </div>

                <!-- Archivos Adjuntos -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fa fa-paperclip"></i> Archivos Adjuntos</h6>
                        </div>
                        <div class="card-body">
                            <input type="file"
                                   ref="fileInput"
                                   @change="onFilesSelected"
                                   class="form-control-file"
                                   multiple
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                                   style="display: none;">
                            <button type="button"
                                    @click="$refs.fileInput.click()"
                                    class="btn btn-outline-primary btn-block">
                                <i class="fa fa-upload"></i> Seleccionar Archivos
                            </button>
                            <small class="form-text text-muted mt-2">Máximo 5MB por archivo</small>

                            <!-- Lista de archivos seleccionados -->
                            <div v-if="adjuntosFiles.length > 0" class="mt-3">
                                <small class="text-muted">Archivos seleccionados:</small>
                                <div class="mt-2">
                                    <div v-for="(file, index) in adjuntosFiles"
                                         :key="index"
                                         class="d-flex justify-content-between align-items-center p-2 border rounded mb-1">
                                        <div class="flex-grow-1">
                                            <small><i class="fa fa-file text-muted mr-1"></i>{{ file.name }}</small><br>
                                            <small class="text-muted">{{ formatFileSize(file.size) }}</small>
                                        </div>
                                        <button type="button"
                                                @click="removeFile(index)"
                                                class="btn btn-danger btn-sm ml-2">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detalles del asiento -->
            <div class="row mt-4">
                <div class="col-md-8">
                    <h5>Detalles del Asiento</h5>

                    <div class="table-responsive">
                        <table class="table table-sm asientos-table" style="border-collapse: separate; border-spacing: 0;">
                            <thead>
                                <tr>
                                    <th width="30%">Cuenta Contable *</th>
                                    <th width="20%">Tercero</th>
                                    <th width="12%">Débito</th>
                                    <th width="12%">Crédito</th>
                                    <th width="18%">Concepto</th>
                                    <th width="8%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(detalle, index) in form.detalles" :key="index" style="border-bottom: none;">
                                    <td>
                                        <select :ref="'cuentaSelect' + index"
                                                class="form-control form-control-sm cuenta-select"
                                                :data-index="index"
                                                required>
                                            <option value="">Seleccionar cuenta</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select v-if="detalle.requiere_tercero"
                                                v-model="detalle.tercero_id"
                                                class="form-control form-control-sm">
                                            <option value="">Sin tercero</option>
                                            <option v-for="tercero in terceros"
                                                    :key="tercero.id"
                                                    :value="tercero.id"
                                                    v-text="tercero.name + ' (' + tercero.number + ')'">
                                            </option>
                                        </select>
                                        <span v-else class="text-muted small">N/A</span>
                                    </td>
                                    <td>
                                        <input type="number"
                                               v-model="detalle.debito"
                                               @input="onDebitoChanged(index)"
                                               @focus="onInputFocus"
                                               class="form-control form-control-sm"
                                               step="0.01"
                                               min="0"
                                               placeholder="0.00">
                                    </td>
                                    <td>
                                        <input type="number"
                                               v-model="detalle.credito"
                                               @input="onCreditoChanged(index)"
                                               @focus="onInputFocus"
                                               class="form-control form-control-sm"
                                               step="0.01"
                                               min="0"
                                               placeholder="0.00">
                                    </td>
                                    <td>
                                        <input type="text"
                                               v-model="detalle.concepto"
                                               class="form-control form-control-sm"
                                               placeholder="Concepto del detalle">
                                    </td>
                                    <td class="text-center">
                                        <button type="button"
                                                @click="removeDetalle(index)"
                                                class="btn btn-danger btn-sm"
                                                v-if="form.detalles.length > 2"
                                                title="Eliminar línea">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-2">
                        <button type="button"
                                @click="addDetalle"
                                class="btn btn-success btn-sm">
                            <i class="fa fa-plus"></i> Agregar Detalle
                        </button>
                    </div>
                </div>

                <!-- Columna derecha para resumen balance -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fa fa-calculator"></i> Resumen Balance</h6>
                        </div>
                        <div class="card-body">
                            <div class="balance-info" :class="balanceClass">
                                <div class="mb-2">
                                    <strong>Total Débitos:</strong><br>
                                    $<span v-text="formatNumber(totalDebitos)"></span>
                                </div>
                                <div class="mb-2">
                                    <strong>Total Créditos:</strong><br>
                                    $<span v-text="formatNumber(totalCreditos)"></span>
                                </div>
                                <div class="mb-2">
                                    <strong>Diferencia:</strong><br>
                                    $<span v-text="formatNumber(diferencia)"></span>
                                </div>
                                <div class="text-center mt-3">
                                    <i :class="balanceIcon"></i>
                                    <strong v-text="balanceText"></strong>
                                </div>
                            </div>

                            <button type="submit"
                                    class="btn btn-primary btn-block mt-3"
                                    :disabled="saving || !balanceado">
                                <i v-if="saving" class="fa fa-spinner fa-spin"></i>
                                <i v-else class="fa fa-save"></i>
                                {{ saving ? 'Guardando...' : 'Guardar Asiento' }}
                            </button>
                        </div>
                    </div>
                        <div class="card-body">
                            <input type="file"
                                   ref="fileInput"
                                   @change="onFilesSelected"
                                   class="form-control-file"
                                   multiple
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                                   style="display: none;">
                            <button type="button"
                                    @click="$refs.fileInput.click()"
                                    class="btn btn-outline-primary btn-block">
                                <i class="fa fa-upload"></i> Seleccionar Archivos
                            </button>
                            <small class="form-text text-muted mt-2">Máximo 5MB por archivo</small>

                            <!-- Lista de archivos seleccionados -->
                            <div v-if="adjuntosFiles.length > 0" class="mt-3">
                                <small class="text-muted">Archivos seleccionados:</small>
                                <div class="mt-2">
                                    <div v-for="(file, index) in adjuntosFiles"
                                         :key="index"
                                         class="d-flex justify-content-between align-items-center p-2 border rounded mb-1">
                                        <div class="flex-grow-1">
                                            <small><i class="fa fa-file text-muted mr-1"></i>{{ file.name }}</small><br>
                                            <small class="text-muted">{{ formatFileSize(file.size) }}</small>
                                        </div>
                                        <button type="button"
                                                @click="removeFile(index)"
                                                class="btn btn-danger btn-sm ml-2">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button type="submit"
                                    class="btn btn-primary btn-block mt-3"
                                    :disabled="saving || !isBalanced">
                                <i v-if="saving" class="fa fa-spinner fa-spin"></i>
                                <i v-else class="fa fa-save"></i>
                                {{ saving ? 'Guardando...' : 'Guardar Asiento' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    `,
    data() {
        return {
            form: {
                tipo_comprobante_id: '',
                fecha_asiento: new Date().toISOString().substr(0, 10),
                concepto: '',
                detalles: [
                    {
                        cuenta_contable_id: '',
                        cuenta_contable: null,
                        tercero_id: '',
                        tercero: null,
                        requiere_tercero: false,
                        debito: '0.00',
                        credito: '0.00',
                        concepto: ''
                    },
                    {
                        cuenta_contable_id: '',
                        cuenta_contable: null,
                        tercero_id: '',
                        tercero: null,
                        requiere_tercero: false,
                        debito: '0.00',
                        credito: '0.00',
                        concepto: ''
                    }
                ]
            },
            tiposComprobantes: [],
            cuentasContables: [],
            terceros: [],
            proximoConsecutivo: null,
            saving: false,
            adjuntosFiles: []
        }
    },
    computed: {
        totalDebitos() {
            return this.form.detalles.reduce((sum, detalle) => {
                return sum + (parseFloat(detalle.debito) || 0);
            }, 0);
        },
        totalCreditos() {
            return this.form.detalles.reduce((sum, detalle) => {
                return sum + (parseFloat(detalle.credito) || 0);
            }, 0);
        },
        diferencia() {
            return Math.abs(this.totalDebitos - this.totalCreditos);
        },
        balanceado() {
            // Si no hay montos ingresados, considerar como neutral (no mostrar error)
            const hayDatos = this.totalDebitos > 0 || this.totalCreditos > 0;
            if (!hayDatos) return true; // Neutral cuando no hay datos

            return this.diferencia < 0.01;
        },
        balanceClass() {
            const hayDatos = this.totalDebitos > 0 || this.totalCreditos > 0;
            if (!hayDatos) return ''; // Sin clase especial cuando no hay datos

            return this.balanceado ? 'balance-balanceado' : 'balance-desbalanceado';
        },
        balanceIcon() {
            const hayDatos = this.totalDebitos > 0 || this.totalCreditos > 0;
            if (!hayDatos) return 'fa fa-info-circle';

            return this.balanceado ? 'fa fa-check-circle' : 'fa fa-exclamation-triangle';
        },
        balanceText() {
            const hayDatos = this.totalDebitos > 0 || this.totalCreditos > 0;
            if (!hayDatos) return 'Ingrese los montos';

            return this.balanceado ? 'Asiento Balanceado' : 'Asiento Desbalanceado';
        },
        isBalanced() {
            return this.balanceado;
        }
    },
    async mounted() {
        await this.loadTiposComprobantes();
        await this.loadCuentasContables();
        this.loadTerceros();

        // Esperar a que Select2 esté disponible y luego inicializar
        this.$nextTick(() => {
            this.waitForSelect2AndInitialize();
        });
    },
    methods: {
        getCuentaById(id) {
            return this.cuentasContables.find(cuenta => cuenta.id == id) || null;
        },
        async loadTiposComprobantes() {
            try {
                console.log('Loading tipos comprobantes...');
                const response = await axios.get('/contabilidad/asientos-contables/tipos-comprobantes');
                console.log('Tipos comprobantes response:', response.data);

                if (response.data.success) {
                    this.tiposComprobantes = response.data.data;
                    console.log('Loaded tipos comprobantes:', this.tiposComprobantes);

                    // Reinicializar Select2 después de cargar los datos
                    this.$nextTick(() => {
                        if (this.$refs.tipoComprobanteSelect) {
                            this.initTipoComprobanteSelect2();
                        }
                    });
                } else {
                    console.error('Error from API:', response.data.message);
                }
            } catch (error) {
                console.error('Error loading tipos comprobantes:', error);
                console.error('Error response:', error.response);
            }
        },
        async loadCuentasContables() {
            try {
                console.log('Loading cuentas contables...');
                const response = await axios.get('/contabilidad/asientos-contables/cuentas-contables');
                console.log('Cuentas contables response:', response.data);

                if (response.data.success) {
                    this.cuentasContables = response.data.data;
                    console.log('Loaded cuentas contables:', this.cuentasContables);

                    // Reinicializar Select2 después de cargar los datos
                    this.$nextTick(() => {
                        this.initCuentasContablesSelect2();
                    });
                } else {
                    console.error('Error from API:', response.data.message);
                }
            } catch (error) {
                console.error('Error loading cuentas contables:', error);
                console.error('Error response:', error.response);
            }
        },
        async loadTerceros() {
            try {
                console.log('Loading terceros...');
                const response = await axios.get('/contabilidad/asientos-contables/terceros');
                console.log('Terceros response:', response.data);

                if (response.data.success) {
                    this.terceros = response.data.data;
                    console.log('Loaded terceros:', this.terceros);
                } else {
                    console.error('Error from API:', response.data.message);
                }
            } catch (error) {
                console.error('Error loading terceros:', error);
                console.error('Error response:', error.response);
            }
        },
        onTipoComprobanteChanged() {
            if (this.form.tipo_comprobante_id) {
                this.loadProximoConsecutivo();
            }
        },
        async loadProximoConsecutivo() {
            try {
                const response = await axios.get('/contabilidad/asientos-contables/proximo-consecutivo', {
                    params: { tipo_comprobante_id: this.form.tipo_comprobante_id }
                });
                console.log('Proximo consecutivo response:', response.data);

                if (response.data.success) {
                    this.proximoConsecutivo = response.data.data.proximo_consecutivo;
                } else {
                    console.error('Error from API:', response.data.message);
                }
            } catch (error) {
                console.error('Error loading proximo consecutivo:', error);
            }
        },
        onCuentaChanged(index) {
            const cuenta = this.cuentasContables.find(c => c.id == this.form.detalles[index].cuenta_contable_id);
            if (cuenta) {
                this.form.detalles[index].cuenta_contable = cuenta;
                this.form.detalles[index].requiere_tercero = cuenta.requiere_tercero;
                if (!cuenta.requiere_tercero) {
                    this.form.detalles[index].tercero_id = '';
                    this.form.detalles[index].tercero = null;
                }
            }
        },
        onMontoChanged(index) {
            // Lógica adicional si es necesaria
        },
        addDetalle() {
            this.form.detalles.push({
                cuenta_contable_id: '',
                cuenta_contable: null,
                tercero_id: '',
                tercero: null,
                requiere_tercero: false,
                debito: '0.00',
                credito: '0.00',
                concepto: ''
            });

            // Actualizar Select2 después de agregar el detalle
            this.updateCuentasContablesSelect2();
        },
        removeDetalle(index) {
            this.form.detalles.splice(index, 1);
        },
        async saveAsiento() {
            const hayDatos = this.totalDebitos > 0 || this.totalCreditos > 0;

            if (!hayDatos) {
                alert('Debe ingresar al menos un monto en débito o crédito.');
                return;
            }

            if (!this.balanceado) {
                alert('El asiento debe estar balanceado para poder guardarlo.');
                return;
            }

            this.saving = true;
            try {
                const response = await axios.post('/contabilidad/asientos-contables', this.form);
                console.log('Save response:', response.data);

                if (response.data.success) {
                    alert('Asiento guardado exitosamente');
                    // Redireccionar o limpiar formulario
                    window.location.href = '/contabilidad/asientos-contables';
                } else {
                    alert('Error: ' + response.data.message);
                }
            } catch (error) {
                console.error('Error saving asiento:', error);
                const message = error.response?.data?.message || 'Error al guardar el asiento';
                alert('Error: ' + message);
            } finally {
                this.saving = false;
            }
        },
        initializeSelect2() {
            this.initTipoComprobanteSelect2();
            this.initCuentasContablesSelect2();
        },
        waitForSelect2AndInitialize(attempts = 0) {
            // Verificar si jQuery está disponible
            if (typeof $ === 'undefined') {
                if (attempts < 50) {
                    setTimeout(() => {
                        this.waitForSelect2AndInitialize(attempts + 1);
                    }, 100);
                }
                return;
            }

            // Verificar si Select2 está disponible
            if (typeof $.fn.select2 === 'undefined') {
                if (attempts < 50) {
                    setTimeout(() => {
                        this.waitForSelect2AndInitialize(attempts + 1);
                    }, 100);
                } else {
                    this.initializeWithoutSelect2();
                }
                return;
            }

            this.initializeSelect2();
        },
        initializeWithoutSelect2() {
            this.initBasicSelects();
        },
        initBasicSelects() {
            // Inicializar selects básicos sin Select2
            const vm = this;

            // Inicializar select de tipo de comprobante
            const $tipoSelect = $(this.$refs.tipoComprobanteSelect);
            if ($tipoSelect.length) {
                $tipoSelect.empty().append('<option value="">Seleccionar tipo de comprobante</option>');
                this.tiposComprobantes.forEach(tipo => {
                    $tipoSelect.append(new Option(tipo.codigo + ' - ' + tipo.nombre, tipo.id));
                });
                $tipoSelect.on('change', function() {
                    vm.form.tipo_comprobante_id = $(this).val();
                    vm.onTipoComprobanteChanged();
                });
            }

            // Inicializar selects de cuentas contables
            $('.cuenta-select').each(function() {
                const $select = $(this);
                const index = parseInt($select.data('index'));

                $select.empty().append('<option value="">Seleccionar cuenta</option>');
                vm.cuentasContables.forEach(cuenta => {
                    $select.append(new Option(cuenta.codigo + ' - ' + cuenta.descripcion, cuenta.id));
                });

                $select.on('change', function() {
                    vm.form.detalles[index].cuenta_contable_id = $(this).val();
                    vm.onCuentaChanged(index);
                });
            });
        },
        initTipoComprobanteSelect2() {
            if (typeof $.fn.select2 === 'undefined') {
                return;
            }

            const vm = this;
            const $select = $(this.$refs.tipoComprobanteSelect);

            if (!$select.length) {
                return;
            }

            // Limpiar opciones existentes
            $select.empty().append('<option value="">Seleccionar tipo de comprobante</option>');

            // Agregar opciones
            this.tiposComprobantes.forEach(tipo => {
                $select.append(new Option(tipo.codigo + ' - ' + tipo.nombre, tipo.id));
            });

            // Inicializar Select2
            $select.select2({
                theme: 'bootstrap',
                placeholder: 'Buscar tipo de comprobante...',
                allowClear: true,
                matcher: function(params, data) {
                    // Si no hay término de búsqueda, mostrar todo
                    if ($.trim(params.term) === '') {
                        return data;
                    }

                    // Si no hay texto, null
                    if (typeof data.text === 'undefined') {
                        return null;
                    }

                    // Buscar en el texto completo (código + nombre)
                    if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                        return data;
                    }

                    return null;
                }
            }).on('change', function() {
                vm.form.tipo_comprobante_id = $(this).val();
                vm.onTipoComprobanteChanged();
            });
        },
        initCuentasContablesSelect2() {
            if (typeof $.fn.select2 === 'undefined') {
                return;
            }

            const vm = this;

            $('.cuenta-select').each(function() {
                const $select = $(this);
                const index = parseInt($select.data('index'));

                // Obtener cuentas ya utilizadas en otros detalles (excluyendo el actual)
                const cuentasUsadas = vm.form.detalles
                    .map((detalle, idx) => idx !== index ? detalle.cuenta_contable_id : null)
                    .filter(id => id && id !== '');

                // Limpiar opciones existentes
                $select.empty().append('<option value="">Seleccionar cuenta</option>');

                // Agregar opciones disponibles (excluyendo las ya usadas)
                vm.cuentasContables.forEach(cuenta => {
                    if (!cuentasUsadas.includes(cuenta.id.toString())) {
                        $select.append(new Option(cuenta.codigo + ' - ' + cuenta.descripcion, cuenta.id));
                    }
                });

                // Si la cuenta actual ya está seleccionada, mantenerla disponible
                const currentValue = vm.form.detalles[index].cuenta_contable_id;
                if (currentValue && !$select.find('option[value="' + currentValue + '"]').length) {
                    const currentCuenta = vm.cuentasContables.find(c => c.id.toString() === currentValue.toString());
                    if (currentCuenta) {
                        $select.append(new Option(currentCuenta.codigo + ' - ' + currentCuenta.descripcion, currentCuenta.id));
                    }
                }

                // Inicializar Select2
                $select.select2({
                    theme: 'bootstrap',
                    placeholder: 'Buscar cuenta contable...',
                    allowClear: true,
                    matcher: function(params, data) {
                        // Si no hay término de búsqueda, mostrar todo
                        if ($.trim(params.term) === '') {
                            return data;
                        }

                        // Si no hay texto, null
                        if (typeof data.text === 'undefined') {
                            return null;
                        }

                        // Buscar en el texto completo (código + descripción)
                        if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                            return data;
                        }

                        return null;
                    }
                }).on('change', function() {
                    vm.form.detalles[index].cuenta_contable_id = $(this).val();
                    vm.onCuentaChanged(index);
                    // Actualizar otros selects para reflejar el cambio
                    vm.$nextTick(() => {
                        vm.updateCuentasContablesSelect2();
                    });
                });

                // Establecer el valor seleccionado si existe
                if (currentValue) {
                    $select.val(currentValue).trigger('change.select2');
                }
            });
        },
        updateCuentasContablesSelect2() {
            // Actualizar todos los selects de cuentas cuando se agregue un nuevo detalle
            this.$nextTick(() => {
                if (typeof $.fn.select2 !== 'undefined') {
                    this.initCuentasContablesSelect2();
                } else {
                    this.initBasicCuentasSelects();
                }
            });
        },
        initBasicCuentasSelects() {
            // Inicializar selects básicos para cuentas contables
            const vm = this;

            $('.cuenta-select').each(function() {
                const $select = $(this);
                const index = parseInt($select.data('index'));

                // Solo procesar si no tiene opciones o necesita actualización
                if ($select.find('option').length <= 1) {
                    // Obtener cuentas ya utilizadas en otros detalles (excluyendo el actual)
                    const cuentasUsadas = vm.form.detalles
                        .map((detalle, idx) => idx !== index ? detalle.cuenta_contable_id : null)
                        .filter(id => id && id !== '');

                    // Limpiar y agregar opción por defecto
                    $select.empty().append('<option value="">Seleccionar cuenta</option>');

                    // Agregar opciones disponibles (excluyendo las ya usadas)
                    vm.cuentasContables.forEach(cuenta => {
                        if (!cuentasUsadas.includes(cuenta.id.toString())) {
                            $select.append(new Option(cuenta.codigo + ' - ' + cuenta.descripcion, cuenta.id));
                        }
                    });

                    // Si la cuenta actual ya está seleccionada, mantenerla disponible
                    const currentValue = vm.form.detalles[index].cuenta_contable_id;
                    if (currentValue && !$select.find('option[value="' + currentValue + '"]').length) {
                        const currentCuenta = vm.cuentasContables.find(c => c.id.toString() === currentValue.toString());
                        if (currentCuenta) {
                            $select.append(new Option(currentCuenta.codigo + ' - ' + currentCuenta.descripcion, currentCuenta.id));
                        }
                    }

                    // Establecer el valor seleccionado si existe
                    if (currentValue) {
                        $select.val(currentValue);
                    }

                    // Añadir evento change si no lo tiene
                    $select.off('change.cuentaSelect').on('change.cuentaSelect', function() {
                        vm.form.detalles[index].cuenta_contable_id = $(this).val();
                        vm.onCuentaChanged(index);
                        // Actualizar otros selects para reflejar el cambio
                        vm.$nextTick(() => {
                            vm.updateCuentasContablesSelect2();
                        });
                    });
                }
            });
        },
        formatNumber(number) {
            return new Intl.NumberFormat('es-CO', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(number);
        },
        // Métodos para manejo de inputs débito/crédito
        onDebitoChanged(index) {
            if (this.form.detalles[index].debito && parseFloat(this.form.detalles[index].debito) > 0) {
                this.form.detalles[index].credito = '0.00';
            }
        },
        onCreditoChanged(index) {
            if (this.form.detalles[index].credito && parseFloat(this.form.detalles[index].credito) > 0) {
                this.form.detalles[index].debito = '0.00';
            }
        },
        onInputFocus(event) {
            event.target.select();
        },
        // Métodos para manejo de archivos adjuntos
        onFilesSelected(event) {
            const files = Array.from(event.target.files);
            files.forEach(file => {
                // Validar tipo de archivo
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Tipo de archivo no permitido: ' + file.name);
                    return;
                }

                // Validar tamaño (5MB máximo)
                if (file.size > 5 * 1024 * 1024) {
                    alert('El archivo es muy grande (máximo 5MB): ' + file.name);
                    return;
                }

                this.adjuntosFiles.push(file);
            });
            // Limpiar el input
            event.target.value = '';
        },
        removeFile(index) {
            this.adjuntosFiles.splice(index, 1);
        },
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    }
});

const app = new Vue({
    el: '#main-wrapper',
    // mixins: [SnackbarNotificationQueue],
});
