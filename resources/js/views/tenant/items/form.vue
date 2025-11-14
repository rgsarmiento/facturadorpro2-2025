<template>
    <el-dialog
        width="70%"
        :title="titleDialog"
        :visible="showDialog"
        :close-on-click-modal="false"
        @close="close"
        @open="create"
        append-to-body
        top="5vh"
        custom-class="items-modal-modern">
        <form autocomplete="off" @submit.prevent="submit">
            <div class="form-body">
                <!-- Información Básica -->
                <div class="section-card">
                    <h4 class="section-title"><i class="fa fa-tag"></i> Información Básica del Producto</h4>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group" :class="{'has-danger': errors.name}">
                                <label class="control-label"><i class="fa fa-cube text-primary"></i> Nombre del Producto <span class="text-danger">*</span></label>
                                <el-input v-model="form.name" dusk="name" placeholder="Ingrese el nombre del producto"></el-input>
                                <small class="form-control-feedback" v-if="errors.name" v-text="errors.name[0]"></small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group" :class="{'has-danger': errors.internal_id}">
                                <label class="control-label"><i class="fa fa-barcode text-info"></i> Código Interno <span class="text-danger">*</span>
                                    <el-tooltip class="item" effect="dark" content="Código interno de la empresa para el control de sus productos" placement="top-start">
                                        <i class="fa fa-info-circle tooltip-icon"></i>
                                    </el-tooltip>
                                </label>
                                <el-input v-model="form.internal_id" dusk="internal_id" placeholder="Ej: PRD-001"></el-input>
                                <small class="form-control-feedback" v-if="errors.internal_id" v-text="errors.internal_id[0]"></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Precios e Impuestos -->
                <div class="section-card">
                    <h4 class="section-title"><i class="fa fa-dollar"></i> Precios e Impuestos</h4>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group" :class="{'has-danger': errors.unit_type_id}">
                                <label class="control-label"><i class="fa fa-balance-scale text-warning"></i> Unidad de Medida <span class="text-danger">*</span></label>
                                <el-select v-model="form.unit_type_id" dusk="unit_type_id" placeholder="Seleccione">
                                    <el-option v-for="option in unit_types" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.unit_type_id" v-text="errors.unit_type_id[0]"></small>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group" :class="{'has-danger': errors.sale_unit_price}">
                                <label class="control-label"><i class="fa fa-money text-success"></i> Precio Unitario (Venta) <span class="text-danger">*</span></label>
                                <el-input class="input-amount" v-model="form.sale_unit_price" dusk="sale_unit_price" @input="calculatePercentageOfProfitBySale" placeholder="0.00">
                                    <template slot="prepend">$</template>
                                </el-input>
                                <small class="form-control-feedback" v-if="errors.sale_unit_price" v-text="errors.sale_unit_price[0]"></small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group" :class="{'has-danger': errors.tax_id}">
                                <label class="control-label"><i class="fa fa-percent text-danger"></i> Impuesto (Venta) <span class="text-danger">*</span>
                                    <a href="#" class="link-excluded" @click.prevent="form.tax_id = null"> [ * Excluido]</a>
                                </label>
                                <el-select v-model="form.tax_id" filterable placeholder="Seleccione el impuesto">
                                    <el-option v-for="option in taxes" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.tax_id" v-text="errors.tax_id[0]"></small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">

                    <!-- Toggle de campos adicionales -->
                    <div class="col-md-12">
                        <div class="toggle-section">
                            <el-checkbox v-model="showAdditionalFields" size="large">
                                <span class="toggle-label">
                                    <i :class="showAdditionalFields ? 'fa fa-chevron-down' : 'fa fa-chevron-right'"></i>
                                    Campos Adicionales
                                </span>
                            </el-checkbox>
                            <small class="toggle-hint">Click para {{ showAdditionalFields ? 'ocultar' : 'mostrar' }} más opciones</small>
                        </div>
                    </div>

                    <!-- Campos adicionales -->
                    <div v-show="showAdditionalFields" class="col-md-12">
                        <!-- Descripción y Moneda -->
                        <div class="section-card additional-section">
                            <h4 class="section-title"><i class="fa fa-file-text-o"></i> Descripción y Detalles</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group" :class="{'has-danger': errors.second_name}">
                                        <label class="control-label"><i class="fa fa-tags"></i> Nombre Secundario</label>
                                        <el-input v-model="form.second_name" dusk="second_name" placeholder="Nombre alternativo del producto"></el-input>
                                        <small class="form-control-feedback" v-if="errors.second_name" v-text="errors.second_name[0]"></small>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group" :class="{'has-danger': errors.currency_type_id}">
                                        <label class="control-label"><i class="fa fa-usd"></i> Moneda</label>
                                        <el-select v-model="form.currency_type_id" dusk="currency_type_id" filterable placeholder="Seleccione">
                                            <el-option v-for="option in currency_types" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                        </el-select>
                                        <small class="form-control-feedback" v-if="errors.currency_type_id" v-text="errors.currency_type_id[0]"></small>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="form-group" :class="{'has-danger': errors.description}">
                                        <label class="control-label"><i class="fa fa-align-left"></i> Descripción Detallada</label>
                                        <el-input type="textarea" :rows="3" v-model="form.description" dusk="description" placeholder="Descripción completa del producto"></el-input>
                                        <small class="form-control-feedback" v-if="errors.description" v-text="errors.description[0]"></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Categorización del Producto -->
                        <div class="section-card additional-section">
                            <h4 class="section-title"><i class="fa fa-sitemap"></i> Categorización</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group" :class="{'has-danger': errors.category_id}">
                                        <label class="control-label"><i class="fa fa-folder-open"></i> Categoría</label>
                                        <div class="quick-add-actions">
                                            <a href="#" v-if="form_category.add == false" class="link-add" @click.prevent="form_category.add = true"> <i class="fa fa-plus-circle"></i> Nuevo</a>
                                            <a href="#" v-if="form_category.add == true" class="link-save" @click.prevent="saveCategory()"> <i class="fa fa-check-circle"></i> Guardar</a>
                                            <a href="#" v-if="form_category.add == true" class="link-cancel" @click.prevent="form_category.add = false"> <i class="fa fa-times-circle"></i> Cancelar</a>
                                        </div>
                                        <el-input v-if="form_category.add == true" v-model="form_category.name" dusk="item_code" placeholder="Nueva categoría" class="mb-2"></el-input>
                                        <el-select v-if="form_category.add == false" v-model="form.category_id" filterable clearable placeholder="Seleccione">
                                            <el-option v-for="option in categories" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                        </el-select>
                                        <small class="form-control-feedback" v-if="errors.category_id" v-text="errors.category_id[0]"></small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group" :class="{'has-danger': errors.brand_id}">
                                        <label class="control-label"><i class="fa fa-certificate"></i> Marca</label>
                                        <div class="quick-add-actions">
                                            <a href="#" v-if="form_brand.add == false" class="link-add" @click.prevent="form_brand.add = true"> <i class="fa fa-plus-circle"></i> Nuevo</a>
                                            <a href="#" v-if="form_brand.add == true" class="link-save" @click.prevent="saveBrand()"> <i class="fa fa-check-circle"></i> Guardar</a>
                                            <a href="#" v-if="form_brand.add == true" class="link-cancel" @click.prevent="form_brand.add = false"> <i class="fa fa-times-circle"></i> Cancelar</a>
                                        </div>
                                        <el-input v-if="form_brand.add == true" v-model="form_brand.name" dusk="item_code" placeholder="Nueva marca" class="mb-2"></el-input>
                                        <el-select v-if="form_brand.add == false" v-model="form.brand_id" filterable clearable placeholder="Seleccione">
                                            <el-option v-for="option in brands" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                        </el-select>
                                        <small class="form-control-feedback" v-if="errors.brand_id" v-text="errors.brand_id[0]"></small>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group" :class="{'has-danger': errors.color_id}">
                                        <label class="control-label"><i class="fa fa-paint-brush"></i> Color</label>
                                        <div class="quick-add-actions">
                                            <a href="#" v-if="form_color.add == false" class="link-add" @click.prevent="form_color.add = true"> <i class="fa fa-plus-circle"></i> Nuevo</a>
                                            <a href="#" v-if="form_color.add == true" class="link-save" @click.prevent="saveEntity('colors', form_color)"> <i class="fa fa-check-circle"></i> Guardar</a>
                                            <a href="#" v-if="form_color.add == true" class="link-cancel" @click.prevent="form_color.add = false"> <i class="fa fa-times-circle"></i> Cancelar</a>
                                        </div>
                                        <el-input v-if="form_color.add == true" v-model="form_color.name" dusk="item_code" placeholder="Nuevo color" class="mb-2"></el-input>
                                        <el-select v-if="form_color.add == false" v-model="form.color_id" filterable clearable placeholder="Seleccione">
                                            <el-option v-for="option in colors" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                        </el-select>
                                        <small class="form-control-feedback" v-if="errors.color_id" v-text="errors.color_id[0]"></small>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group" :class="{'has-danger': errors.size_id}">
                                        <label class="control-label"><i class="fa fa-arrows-v"></i> Talla</label>
                                        <div class="quick-add-actions">
                                            <a href="#" v-if="form_size.add == false" class="link-add" @click.prevent="form_size.add = true"> <i class="fa fa-plus-circle"></i> Nuevo</a>
                                            <a href="#" v-if="form_size.add == true" class="link-save" @click.prevent="saveEntity('sizes', form_size)"> <i class="fa fa-check-circle"></i> Guardar</a>
                                            <a href="#" v-if="form_size.add == true" class="link-cancel" @click.prevent="form_size.add = false"> <i class="fa fa-times-circle"></i> Cancelar</a>
                                        </div>
                                        <el-input v-if="form_size.add == true" v-model="form_size.name" dusk="item_code" placeholder="Nueva talla" class="mb-2"></el-input>
                                        <el-select v-if="form_size.add == false" v-model="form.size_id" filterable clearable placeholder="Seleccione">
                                            <el-option v-for="option in sizes" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                        </el-select>
                                        <small class="form-control-feedback" v-if="errors.size_id" v-text="errors.size_id[0]"></small>
                                    </div>
                                </div>
                                <small class="form-control-feedback" v-if="errors.brand_id" v-text="errors.brand_id[0]"></small>
                            </div>
                        </div>

                        <!-- Configuración de Inventario y Stock -->
                        <div class="section-card additional-section">
                            <h4 class="section-title"><i class="fa fa-cubes"></i> Configuración de Inventario</h4>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group" :class="{'has-danger': errors.model}">
                                        <label class="control-label"><i class="fa fa-tag"></i> Modelo</label>
                                        <el-input v-model="form.model" placeholder="Modelo del producto"></el-input>
                                        <small class="form-control-feedback" v-if="errors.model" v-text="errors.model[0]"></small>
                                    </div>
                                </div>

                                <div class="col-md-3" v-show="recordId == null && form.unit_type_id != 1">
                                    <div class="form-group" :class="{'has-danger': errors.stock}">
                                        <label class="control-label"><i class="fa fa-archive"></i> Stock Inicial</label>
                                        <el-input v-model="form.stock" placeholder="0"></el-input>
                                        <small class="form-control-feedback" v-if="errors.stock" v-text="errors.stock[0]"></small>
                                    </div>
                                </div>

                                <div class="col-md-3" v-show="form.unit_type_id != 1">
                                    <div class="form-group" :class="{'has-danger': errors.stock_min}">
                                        <label class="control-label"><i class="fa fa-exclamation-triangle"></i> Stock Mínimo</label>
                                        <el-input v-model="form.stock_min" placeholder="0"></el-input>
                                        <small class="form-control-feedback" v-if="errors.stock_min" v-text="errors.stock_min[0]"></small>
                                    </div>
                                </div>

                                <div class="col-md-3" v-show="form.unit_type_id != 1">
                                    <div class="form-group" :class="{'has-danger': errors.date_of_due}">
                                        <label class="control-label"><i class="fa fa-calendar"></i> Fec. Vencimiento</label>
                                        <el-date-picker v-model="form.date_of_due" type="date" value-format="yyyy-MM-dd" :clearable="true" placeholder="Seleccione fecha"></el-date-picker>
                                        <small class="form-control-feedback" v-if="errors.date_of_due" v-text="errors.date_of_due[0]"></small>
                                    </div>
                                </div>

                                <div class="col-md-4" v-show="recordId == null" v-if="form.unit_type_id != 1">
                                    <div class="form-group" :class="{'has-danger': errors.warehouse_id}">
                                        <label class="control-label">
                                            <i class="fa fa-warehouse"></i> Almacén
                                            <el-tooltip class="item" effect="dark" content="Si no selecciona almacén, se asignará por defecto el relacionado al establecimiento" placement="top">
                                                <i class="fa fa-info-circle tooltip-icon"></i>
                                            </el-tooltip>
                                        </label>
                                        <el-select v-model="form.warehouse_id" filterable placeholder="Seleccione almacén">
                                            <el-option v-for="option in warehouses" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                        </el-select>
                                        <small class="form-control-feedback" v-if="errors.warehouse_id" v-text="errors.warehouse_id[0]"></small>
                                    </div>
                                </div>

                                <!-- Checkboxes en una fila -->
                                <div class="col-md-12"><div class="checkbox-row">
                                    <div class="checkbox-item" v-show="form.unit_type_id != 1">
                                        <el-checkbox v-model="form.calculate_quantity">Calcular cantidad por precio</el-checkbox>
                                        <small class="form-control-feedback" v-if="errors.calculate_quantity" v-text="errors.calculate_quantity[0]"></small>
                                    </div>
                                    <div class="checkbox-item" v-show="form.unit_type_id != 1">
                                        <el-checkbox v-model="form.lots_enabled" @change="changeLotsEnabled">¿Maneja lotes?</el-checkbox>
                                    </div>
                                    <div class="checkbox-item" v-show="form.unit_type_id != 1">
                                        <el-checkbox v-model="form.series_enabled" @change="changeLotsEnabled">¿Maneja series?</el-checkbox>
                                    </div>
                                    <div class="checkbox-item">
                                        <el-checkbox v-model="form.has_perception" @change="changeHasPerception">Incluye percepción</el-checkbox>
                                    </div>
                                </div></div>

                                <!-- Campos condicionales de lotes, series y percepción -->
                                <div class="col-md-3" v-show="form.unit_type_id != 1 && form.lots_enabled">
                                    <div class="form-group" :class="{'has-danger': errors.lot_code}">
                                        <label class="control-label"><i class="fa fa-qrcode"></i> Código Lote</label>
                                        <el-input v-model="form.lot_code" placeholder="Ingrese código"></el-input>
                                        <small class="form-control-feedback" v-if="errors.lot_code" v-text="errors.lot_code[0]"></small>
                                    </div>
                                </div>

                                <div class="col-md-3" v-show="form.unit_type_id != 1 && form.series_enabled">
                                    <div class="form-group" :class="{'has-danger': errors.lot_code}">
                                        <label class="control-label"><i class="fa fa-list-ol"></i> Series</label>
                                        <el-button type="primary" icon="el-icon-edit-outline" @click.prevent="clickLotcode">Ingresar Series</el-button>
                                        <small class="form-control-feedback" v-if="errors.lot_code" v-text="errors.lot_code[0]"></small>
                                    </div>
                                </div>

                                <div class="col-md-3" v-show="form.has_perception">
                                    <div class="form-group">
                                        <label class="control-label"><i class="fa fa-percent"></i> % Percepción</label>
                                        <el-input v-model="form.percentage_perception" placeholder="0.00">
                                            <template slot="append">%</template>
                                        </el-input>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-show="form.unit_type_id != 1" class="col-md-12">
                            <h5 class="separator-title">
                                Listado de precios
                                <el-tooltip class="item" effect="dark" content="Aplica para realizar compra/venta en presentación de diferentes precios y/o cantidades" placement="top">
                                    <i class="fa fa-info-circle"></i>
                                </el-tooltip>
                                <a href="#" class="control-label" @click="clickAddRow"> [ + Nuevo]</a>
                            </h5>
                        </div>

                        <div v-show="form.unit_type_id != 1" class="col-md-12" v-if="form.item_unit_types.length > 0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th class="text-center">Unidad</th>
                                        <th class="text-center">Descripción</th>
                                        <th class="text-center">
                                            Factor
                                            <el-tooltip class="item" effect="dark" content="Cantidad de unidades" placement="top">
                                                <i class="fa fa-info-circle"></i>
                                            </el-tooltip>
                                        </th>
                                        <th class="text-center">Precio 1</th>
                                        <th class="text-center">Precio 2</th>
                                        <th class="text-center">Precio 3</th>
                                        <th class="text-center">P. Defecto</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="(row, index) in form.item_unit_types" :key="index">
                                        <template v-if="row.id">
                                            <td class="text-center">{{row.unit_type.name}}</td>
                                            <td class="text-center">{{row.description}}</td>
                                            <td class="text-center">{{row.quantity_unit}}</td>
                                            <td class="text-center"><el-input v-model="row.price1"></el-input></td>
                                            <td class="text-center"><el-input v-model="row.price2"></el-input></td>
                                            <td class="text-center"><el-input v-model="row.price3"></el-input></td>
                                            <td class="text-center">Precio {{row.price_default}}</td>
                                            <td class="series-table-actions text-right">
                                                <button type="button" class="btn waves-effect waves-light btn-xs btn-danger" @click.prevent="clickDelete(row.id)">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </td>
                                        </template>
                                        <template v-else>
                                            <td>
                                                <div class="form-group">
                                                    <el-select v-model="row.unit_type_id" dusk="item_unit_type.unit_type_id">
                                                        <el-option v-for="option in unit_types" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                                    </el-select>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="form-group" :class="{'has-danger': !row.description && row.description !== null}">
                                                    <el-input v-model="row.description" placeholder="Descripción *"></el-input>
                                                    <small class="form-control-feedback text-danger" v-if="!row.description && row.description !== null">La descripción es obligatoria</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="form-group" :class="{'has-danger': (!row.quantity_unit || isNaN(row.quantity_unit)) && row.quantity_unit !== null}">
                                                    <el-input v-model="row.quantity_unit" type="number" step="0.01" placeholder="Factor *"></el-input>
                                                    <small class="form-control-feedback text-danger" v-if="!row.quantity_unit && row.quantity_unit !== null">El factor es obligatorio</small>
                                                    <small class="form-control-feedback text-danger" v-else-if="row.quantity_unit && isNaN(row.quantity_unit)">El factor debe ser numérico</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="form-group">
                                                    <el-input v-model="row.price1"></el-input>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="form-group">
                                                    <el-input v-model="row.price2"></el-input>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="form-group">
                                                    <el-input v-model="row.price3"></el-input>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <el-radio-group v-model="row.price_default">
                                                        <el-radio :label="1" class="d-block">Precio 1</el-radio>
                                                        <el-radio :label="2" class="d-block">Precio 2</el-radio>
                                                        <el-radio :label="3" class="d-block">Precio 3</el-radio>
                                                    </el-radio-group>
                                                </div>
                                            </td>
                                            <td class="series-table-actions text-right">
                                                <button type="button" class="btn waves-effect waves-light btn-xs btn-danger" @click.prevent="clickCancel(index)">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </td>
                                        </template>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div v-if="attribute_types.length > 0" class="col-md-12">
                            <h5 class="separator-title">
                                Atributos
                                <el-tooltip class="item" effect="dark" content="Diferentes presentaciones para la venta del producto" placement="top">
                                    <i class="fa fa-info-circle"></i>
                                </el-tooltip>
                                <a href="#" class="control-label" @click.prevent="clickAddAttribute">[+ Agregar]</a>
                            </h5>
                        </div>

                        <div v-if="form.attributes.length > 0" class="col-md-12">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Descripción</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="(row, index) in form.attributes" :key="index">
                                        <td>
                                            <el-select v-model="row.attribute_type_id" filterable @change="changeAttributeType(index)">
                                                <el-option v-for="option in attribute_types" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                            </el-select>
                                        </td>
                                        <td>
                                            <el-input v-model="row.value"></el-input>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger" @click.prevent="clickRemoveAttribute(index)">x</button>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <h5 class="separator-title">Campos adicionales</h5>
                        </div>

                        <div class="row col-md-12">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label">Imágen <span class="text-danger"></span></label>
                                    <el-upload class="avatar-uploader"
                                            :data="{'type': 'items'}"
                                            :headers="headers"
                                            :action="`/${resource}/upload`"
                                            :show-file-list="false"
                                            :on-success="onSuccess">
                                        <img v-if="form.image_url" :src="form.image_url" class="avatar">
                                        <i v-else class="el-icon-plus avatar-uploader-icon"></i>
                                    </el-upload>
                                </div>
                            </div>

                            <div class="col-md-9">
                                <div class="row">
                                    <div class="short-div col-md-8">
                                        <div class="form-group" :class="{'has-danger': errors.purchase_tax_id}">
                                            <label class="control-label">Impuesto (Compra)
                                                <a href="#" @click.prevent="form.purchase_tax_id = null"> [ * Excluido]</a>
                                            </label>
                                            <el-select v-model="form.purchase_tax_id" filterable>
                                                <el-option v-for="option in taxes" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                            </el-select>
                                            <small class="form-control-feedback" v-if="errors.purchase_tax_id" v-text="errors.purchase_tax_id[0]"></small>
                                        </div>
                                    </div>

                                    <div class="short-div col-md-4">
                                        <div class="form-group" :class="{'has-danger': errors.purchase_unit_price}">
                                            <label class="control-label">Precio Unitario (Compra)</label>
                                            <el-input v-model="form.purchase_unit_price" dusk="purchase_unit_price" @input="calculatePercentageOfProfitByPurchase"></el-input>
                                            <small class="form-control-feedback" v-if="errors.purchase_unit_price" v-text="errors.purchase_unit_price[0]"></small>
                                        </div>
                                    </div>

                                    <div class="short-div col-md-4">
                                        <div class="form-group" :class="{'has-danger': errors.percentage_of_profit}">
                                            <label class="control-label">
                                                <el-checkbox v-model="enabled_percentage_of_profit" @change="changeEnabledPercentageOfProfit"></el-checkbox>
                                                Porcentaje de ganancia (%)
                                            </label>
                                            <el-input v-model="form.percentage_of_profit" :disabled="!enabled_percentage_of_profit" @input="calculatePercentageOfProfitByPercentage"></el-input>
                                            <small class="form-control-feedback" v-if="errors.percentage_of_profit" v-text="errors.percentage_of_profit[0]"></small>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="form-actions text-right pt-2">
                <el-button @click.prevent="close()">Cancelar</el-button>
                <el-button type="primary" native-type="submit" :loading="loading_submit">Guardar</el-button>
            </div>
        </form>

        <lots-form
            :showDialog.sync="showDialogLots"
            :stock="form.stock"
            :recordId="recordId"
            :lots="form.lots"
            @addRowLot="addRowLot">
        </lots-form>

    </el-dialog>
</template>

<style>
.field-margin {
    padding-left: 15px;
    padding-right: 15px;
}
.input-amount .el-input__inner {
    text-align: right;
}

</style>

<script>
    // import PercentagePerception from './partials/percentage_perception.vue'
    import LotsForm from './partials/lots.vue'

    export default {
        props: ['showDialog', 'recordId', 'external'],
        components: {LotsForm},

        data() {
            return {
                showAdditionalFields: false, // Nueva propiedad para manejar la visibilidad de los campos adicionales
                showDialogLots:false,
                form_category:{ add: false, name: null, id: null },
                form_brand:{ add: false, name: null, id: null },
                form_color:{ add: false, name: null, id: null },
                form_size:{ add: false, name: null, id: null },
                warehouses: [],
                loading_submit: false,
                showPercentagePerception: false,
                has_percentage_perception: false,
                percentage_perception:null,
                enabled_percentage_of_profit:false,
                titleDialog: null,
                resource: 'items',
                errors: {},
                headers: headers_token,
                form: {},
                configuration: {},
                unit_types: [],
                taxes: [],
                currency_types: [],
                system_isc_types: [],
                affectation_igv_types: [],
                categories: [],
                brands: [],
                sizes: [],
                colors: [],
                accounts: [],
                show_has_igv:true,
                have_account:false,
                item_unit_type:{
                    id:null,
                    unit_type_id:null,
                    quantity_unit:0,
                    price1:0,
                    price2:0,
                    price3:0,
                    price_default:2,

                },
                attribute_types:  []
            }
        },
        async created() {
            await this.initForm()
            await this.$http.get(`/${this.resource}/tables`)
                .then(response => {
                    this.taxes = response.data.taxes
                    this.unit_types = response.data.unit_types
                    this.accounts = response.data.accounts
                    this.currency_types = response.data.currency_types
                    // this.system_isc_types = response.data.system_isc_types
                    // this.affectation_igv_types = response.data.affectation_igv_types
                    this.warehouses = response.data.warehouses
                    this.categories = response.data.categories
                    this.brands = response.data.brands
                    this.colors = response.data.colors
                    this.sizes = response.data.sizes
                    this.attribute_types = response.data.attribute_types
                    this.configuration = response.data.configuration

                    // this.form.sale_affectation_igv_type_id = (this.affectation_igv_types.length > 0)?this.affectation_igv_types[0].id:null
                    // this.form.purchase_affectation_igv_type_id = (this.affectation_igv_types.length > 0)?this.affectation_igv_types[0].id:null
                })

            this.$eventHub.$on('submitPercentagePerception', (data)=>{
                this.form.percentage_perception = data
                if(!this.form.percentage_perception) this.has_percentage_perception = false
            })

            this.$eventHub.$on('reloadTables', ()=>{
                this.reloadTables()
            })

            await this.setDefaultConfiguration()

        },

        methods: {
            setDefaultConfiguration(){
                // this.form.sale_affectation_igv_type_id = (this.configuration) ? this.configuration.affectation_igv_type_id : '10'

                // this.$http.get(`/configurations/record`) .then(response => {
                //     this.form.has_igv = response.data.data.include_igv
                // })
            },
            clickAddAttribute() {
                this.form.attributes.push({
                    attribute_type_id: null,
                    description: null,
                    value: null,
                    start_date: null,
                    end_date: null,
                    duration: null,
                })
            },
            async reloadTables(){

                await this.$http.get(`/${this.resource}/tables`)
                    .then(response => {
                        this.unit_types = response.data.unit_types
                        this.accounts = response.data.accounts
                        this.currency_types = response.data.currency_types
                        this.system_isc_types = response.data.system_isc_types
                        this.affectation_igv_types = response.data.affectation_igv_types
                        this.warehouses = response.data.warehouses
                        this.categories = response.data.categories
                        this.brands = response.data.brands

                        // this.form.sale_affectation_igv_type_id = (this.affectation_igv_types.length > 0)?this.affectation_igv_types[0].id:null
                        // this.form.purchase_affectation_igv_type_id = (this.affectation_igv_types.length > 0)?this.affectation_igv_types[0].id:null
                    })
            },
            changeLotsEnabled(){

                // if(!this.form.lots_enabled){
                //     this.form.lot_code = null
                //     this.form.lots = []
                // }

            },
            addRowLot(lots){
                this.form.lots = lots
            },
            clickLotcode(){
                // if(this.form.stock <= 0)
                //     return this.$message.error('El stock debe ser mayor a 0')

                this.showDialogLots = true
            },
            changeHaveAccount(){
                if(!this.have_account) this.form.account_id = null
            },
            changeEnabledPercentageOfProfit(){
                // if(!this.enabled_percentage_of_profit) this.form.percentage_of_profit = 0
            },
            clickDelete(id) {

                this.$http.delete(`/${this.resource}/item-unit-type/${id}`)
                        .then(res => {
                            if(res.data.success) {
                                this.loadRecord()
                                this.$message.success('Se eliminó correctamente el registro')
                            }
                        })
                        .catch(error => {
                            if (error.response.status === 500) {
                                this.$message.error('Error al intentar eliminar');
                            } else {
                                console.log(error.response.data.message)
                            }
                        })

            },
            changeHasPerception(){
                if(!this.form.has_perception){
                    this.form.percentage_perception = null
                }

            },
            clickAddRow() {
                this.form.item_unit_types.push({
                    id: null,
                    description: null,
                    unit_type_id: 10,
                    quantity_unit: 0,
                    price1: 0,
                    price2: 0,
                    price3: 0,
                    price_default: 2
                })
            },
            clickCancel(index) {
                this.form.item_unit_types.splice(index, 1)
                // this.initDocumentTypes()
                // this.showAddButton = true
            },
            initForm() {
                this.loading_submit = false,
                this.errors = {}
                this.form = {
                    id: null,
                    item_type_id: '01',
                    internal_id: null,
                    // item_code: null,
                    // item_code_gs1: null,
                    description: null,
                    name: null,
                    second_name: null,
                    unit_type_id: 10,
                    // currency_type_id: 'PEN',
                    sale_unit_price: 0,
                    purchase_unit_price: 0,
                    // has_isc: false,
                    // system_isc_type_id: null,
                    // percentage_isc: 0,
                    // suggested_price: 0,
                    // sale_affectation_igv_type_id: null,
                    // purchase_affectation_igv_type_id: null,
                    calculate_quantity: false,
                    stock: 0,
                    stock_min: 1,
                    // has_igv: true,
                    has_perception: false,
                    item_unit_types:[],
                    percentage_of_profit: 0,
                    percentage_perception: 0,
                    image: null,
                    image_url: null,
                    temp_path: null,
                    is_set: false,
                    account_id: null,
                    category_id: null,
                    brand_id: null,
                    date_of_due:null,
                    lot_code:null,
                    lots_enabled:false,
                    lots:[],
                    attributes: [],
                    series_enabled: false,
                    tax_id: 1,
                    purchase_tax_id: 1,
                    currency_type_id: 170,
                    model: null,
                }
                this.show_has_igv = true
                this.enabled_percentage_of_profit = false
            },
            onSuccess(response, file, fileList) {
                if (response.success) {
                    this.form.image = response.data.filename
                    this.form.image_url = response.data.temp_image
                    this.form.temp_path = response.data.temp_path
                } else {
                    this.$message.error(response.message)
                }
            },
            changeAffectationIgvType(){

                let affectation_igv_type_exonerated = [20,21,30,31,32,33,34,35,36,37]
                let is_exonerated = affectation_igv_type_exonerated.includes((parseInt(this.form.sale_affectation_igv_type_id)));

                if(is_exonerated){
                    this.show_has_igv = false
                    this.form.has_igv = true
                }else{
                    this.show_has_igv = true
                }

            },
            resetForm() {
                this.initForm()
                this.form.sale_affectation_igv_type_id = (this.affectation_igv_types.length > 0)?this.affectation_igv_types[0].id:null
                this.form.purchase_affectation_igv_type_id = (this.affectation_igv_types.length > 0)?this.affectation_igv_types[0].id:null
                this.setDefaultConfiguration()
            },
            create() {


                this.titleDialog = (this.recordId)? 'Editar Producto':'Nuevo Producto'
                if (this.recordId) {
                    this.$http.get(`/${this.resource}/record/${this.recordId}`)
                    .then(response => {
                            let data = response.data.data;
                            // Formatear sale_unit_price a dos decimales
                            if (data.sale_unit_price) {
                                data.sale_unit_price = parseFloat(data.sale_unit_price).toFixed(0);
                            }
                            // Formatear purchase_unit_price a dos decimales
                            if (data.purchase_unit_price) {
                                data.purchase_unit_price = parseFloat(data.purchase_unit_price).toFixed(0);
                            }
                            // Opcional: Formatear stock y stock_min si también deseas que tengan un formato específico
                            if (data.stock) {
                                data.stock = parseFloat(data.stock).toFixed(0); // Redondeo sin decimales para el stock
                            }
                            if (data.stock_min) {
                                data.stock_min = parseFloat(data.stock_min).toFixed(0); // Redondeo sin decimales para el stock mínimo
                            }
                            // Asignar los datos formateados al modelo del formulario
                            this.form = data;
                            this.has_percentage_perception = (this.form.percentage_perception) ? true : false;
                        });
                }

            },
            loadRecord(){
                if (this.recordId) {
                    this.$http.get(`/${this.resource}/record/${this.recordId}`)
                        .then(response => {
                            this.form = response.data.data
                            // this.changeAffectationIgvType()
                        })
                }
            },
            calculatePercentageOfProfitBySale() {
                let difference = parseFloat(this.form.sale_unit_price) - parseFloat(this.form.purchase_unit_price);

                if(parseFloat(this.form.purchase_unit_price) === 0) {
                    this.form.percentage_of_profit = 0;
                } else {
                    if(this.enabled_percentage_of_profit) this.form.percentage_of_profit = difference / parseFloat(this.form.purchase_unit_price) * 100;
                }
            },
            calculatePercentageOfProfitByPurchase() {
                if(this.form.percentage_of_profit === '') {
                    this.form.percentage_of_profit = 0;
                }

                if(this.enabled_percentage_of_profit) this.form.sale_unit_price = (this.form.purchase_unit_price * (100 + parseFloat(this.form.percentage_of_profit))) / 100
            },
            calculatePercentageOfProfitByPercentage() {
                if(this.form.percentage_of_profit === '') {
                    this.form.percentage_of_profit = 0;
                }

                if(this.enabled_percentage_of_profit) this.form.sale_unit_price = (this.form.purchase_unit_price * (100 + parseFloat(this.form.percentage_of_profit))) / 100
            },
            async submit() {
                if(this.form.has_perception && !this.form.percentage_perception) return this.$message.error('Ingrese un porcentaje');
                // if(!this.has_percentage_perception) this.form.percentage_perception = null

                // Validar listas de precios
                if(this.form.item_unit_types && this.form.item_unit_types.length > 0) {
                    for(let i = 0; i < this.form.item_unit_types.length; i++) {
                        const row = this.form.item_unit_types[i];

                        // Validar descripción obligatoria
                        if(!row.description || row.description.trim() === '') {
                            return this.$message.error('La descripción es obligatoria en todas las listas de precios');
                        }

                        // Validar factor obligatorio y numérico
                        if(!row.quantity_unit || row.quantity_unit === '' || row.quantity_unit === null) {
                            return this.$message.error('El factor es obligatorio en todas las listas de precios');
                        }

                        if(isNaN(row.quantity_unit) || parseFloat(row.quantity_unit) <= 0) {
                            return this.$message.error('El factor debe ser un número mayor a cero');
                        }
                    }
                }

                /*if(!this.recordId && this.form.lots_enabled){

                    if(this.form.lots.length > this.form.stock)
                        return this.$message.error('La cantidad de series registradas es superior al stock');

                    if(!this.form.lot_code)
                        return this.$message.error('Código de lote es requerido');

                    if(this.form.lots.length != this.form.stock)
                        return this.$message.error('La cantidad de series registradas son diferentes al stock');

                }*/

                if(!this.recordId && this.form.lots_enabled){

                    if(!this.form.lot_code)
                        return this.$message.error('Código de lote es requerido');

                    if(!this.form.date_of_due)
                        return this.$message.error('Fecha de vencimiento es requerido si lotes esta habilitado.');
                }

                if(!this.recordId && this.form.series_enabled)
                {

                    if(this.form.lots.length > this.form.stock)
                        return this.$message.error('La cantidad de series registradas es superior al stock');

                    if(this.form.lots.length != this.form.stock)
                        return this.$message.error('La cantidad de series registradas son diferentes al stock');
                }

                this.loading_submit = true
                if(!this.showAdditionalFields) {
                    if(this.form.description == null) {
                        this.form.description = this.form.name
                    }
                }
                await this.$http.post(`/${this.resource}`, this.form)
                    .then(response => {
                        if (response.data.success) {
                            this.$message.success(response.data.message)
                            if (this.external) {
                                this.$eventHub.$emit('reloadDataItems', response.data.id)
                            } else {
                                this.$eventHub.$emit('reloadData')
                            }
                            this.close()
                        } else {
                            this.$message.error(response.data.message)
                        }
                    })
                    .catch(error => {
                        if (error.response.status === 422) {
                            this.errors = error.response.data
                        } else {
                            console.log(error)
                        }
                    })
                    .then(() => {
                        this.loading_submit = false
                    })
            },
            close() {
                this.$emit('update:showDialog', false)
                this.resetForm()
            },
            changeHasIsc() {
                this.form.system_isc_type_id = null
                this.form.percentage_isc = 0
                this.form.suggested_price = 0
            },
            changeSystemIscType() {
                if (this.form.system_isc_type_id !== '03') {
                    this.form.suggested_price = 0
                }
            },
            saveEntity(entity, form) {
                form.add = false;

                this.$http.post(`/${entity}`, form)
                .then(response => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                        this[`${entity}`].push(response.data.data);
                        form.name = null;
                    } else {
                        this.$message.error('No se guardaron los cambios');
                    }
                })
                .catch(error => {
                    console.error(error)
                });
            },
            saveCategory()
            {
                this.form_category.add = false

                this.$http.post(`/categories`,  this.form_category)
                .then(response => {
                    if (response.data.success) {
                        this.$message.success(response.data.message)
                        this.categories.push(response.data.data)
                        this.form_category.name = null
                    } else {
                        this.$message.error('No se guardaron los cambios')
                    }
                })
                .catch(error => {

                })
            },
            saveBrand()
            {
                this.form_brand.add = false

                this.$http.post(`/brands`,  this.form_brand)
                .then(response => {
                    if (response.data.success) {
                        this.$message.success(response.data.message)
                        this.brands.push(response.data.data)
                        this.form_brand.name = null

                    } else {
                        this.$message.error('No se guardaron los cambios')
                    }
                })
                .catch(error => {

                })
            },
            changeAttributeType(index) {
                let attribute_type_id = this.form.attributes[index].attribute_type_id
                let attribute_type = _.find(this.attribute_types, {id: attribute_type_id})
                this.form.attributes[index].description = attribute_type.description
            },
            clickRemoveAttribute(index) {
                this.form.attributes.splice(index, 1)
            },
        }
    }
</script>

<style scoped>
    /* ====================================
       ESTILOS PROFESIONALES PARA EL MODAL
       ==================================== */

    /* HEADER DEL MODAL */
    .items-modal-modern .el-dialog {
        border-radius: 12px;
        box-shadow: 0 12px 48px rgba(0, 0, 0, 0.15);
        overflow: hidden;
    }

    .items-modal-modern .el-dialog__header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px 12px 0 0;
        padding: 24px 30px;
        border-bottom: 3px solid rgba(255, 255, 255, 0.2);
    }

    .items-modal-modern .el-dialog__title {
        color: white;
        font-weight: 700;
        font-size: 20px;
        letter-spacing: 0.5px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .items-modal-modern .el-dialog__headerbtn .el-dialog__close {
        color: white;
        font-size: 24px;
        font-weight: bold;
    }

    .items-modal-modern .el-dialog__headerbtn .el-dialog__close:hover {
        color: #fff;
        transform: rotate(90deg);
        transition: transform 0.3s ease;
    }

    .items-modal-modern .el-dialog__body {
        padding: 30px;
        background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
        max-height: 75vh;
        overflow-y: auto;
    }

    /* SCROLLBAR PERSONALIZADO */
    .items-modal-modern .el-dialog__body::-webkit-scrollbar {
        width: 8px;
    }

    .items-modal-modern .el-dialog__body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .items-modal-modern .el-dialog__body::-webkit-scrollbar-thumb {
        background: #667eea;
        border-radius: 10px;
    }

    .items-modal-modern .el-dialog__body::-webkit-scrollbar-thumb:hover {
        background: #5568d3;
    }

    /* TARJETAS DE SECCIÓN */
    .section-card {
        background: white;
        border-radius: 10px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(102, 126, 234, 0.1);
        transition: all 0.3s ease;
    }

    .section-card:hover {
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.15);
        transform: translateY(-2px);
        border-color: rgba(102, 126, 234, 0.3);
    }

    .additional-section {
        animation: slideIn 0.4s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* TÍTULOS DE SECCIÓN */
    .section-title {
        color: #2c3e50;
        font-size: 17px;
        font-weight: 700;
        margin: 0 0 20px 0;
        padding-bottom: 12px;
        border-bottom: 3px solid #667eea;
        display: flex;
        align-items: center;
        letter-spacing: 0.3px;
    }

    .section-title i {
        color: #667eea;
        margin-right: 10px;
        font-size: 20px;
        background: rgba(102, 126, 234, 0.1);
        padding: 8px;
        border-radius: 6px;
    }

    /* TOGGLE DE CAMPOS ADICIONALES */
    .toggle-section {
        background: white;
        padding: 16px 20px;
        border-radius: 8px;
        margin: 20px 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        border-left: 4px solid #667eea;
    }

    .toggle-label {
        font-size: 16px;
        font-weight: 600;
        color: #2c3e50;
        margin-left: 8px;
    }

    .toggle-label i {
        color: #667eea;
        transition: transform 0.3s ease;
    }

    .toggle-hint {
        display: block;
        margin-top: 4px;
        margin-left: 30px;
        color: #6c757d;
        font-size: 12px;
        font-style: italic;
    }

    /* LABELS DE FORMULARIO CON ICONOS */
    .form-group label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        font-size: 14px;
    }

    .form-group label i {
        margin-right: 6px;
        font-size: 14px;
    }

    .tooltip-icon {
        cursor: help;
        opacity: 0.7;
        transition: opacity 0.2s;
    }

    .tooltip-icon:hover {
        opacity: 1;
    }

    /* INPUTS Y SELECTS */
    .el-input__inner,
    .el-textarea__inner {
        border-radius: 6px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .el-input__inner:hover,
    .el-textarea__inner:hover {
        border-color: #c5cfe3;
    }

    .el-input__inner:focus,
    .el-textarea__inner:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
    }

    /* INPUT CON PREPEND */
    .el-input-group__prepend {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        font-weight: 600;
    }

    /* INPUTS CON CANTIDAD */
    .input-amount .el-input__inner {
        text-align: right;
        font-weight: 600;
        font-size: 16px;
        color: #28a745;
    }

    /* ENLACES DE ACCIÓN */
    .link-excluded {
        color: #dc3545;
        font-size: 13px;
        margin-left: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s ease;
        padding: 2px 8px;
        border-radius: 4px;
        background: rgba(220, 53, 69, 0.1);
    }

    .link-excluded:hover {
        background: #dc3545;
        color: white;
        text-decoration: none;
    }

    /* ACCIONES RÁPIDAS DE AÑADIR */
    .quick-add-actions {
        float: right;
        margin-left: 10px;
    }

    .link-add, .link-save, .link-cancel {
        font-size: 12px;
        text-decoration: none;
        margin-left: 8px;
        padding: 4px 10px;
        border-radius: 4px;
        font-weight: 600;
        transition: all 0.2s ease;
        display: inline-block;
    }

    .link-add {
        color: #28a745;
        background: rgba(40, 167, 69, 0.1);
    }

    .link-add:hover {
        background: #28a745;
        color: white;
    }

    .link-save {
        color: #007bff;
        background: rgba(0, 123, 255, 0.1);
    }

    .link-save:hover {
        background: #007bff;
        color: white;
    }

    .link-cancel {
        color: #dc3545;
        background: rgba(220, 53, 69, 0.1);
    }

    .link-cancel:hover {
        background: #dc3545;
        color: white;
    }

    .link-add i, .link-save i, .link-cancel i {
        margin-right: 4px;
    }

    .mb-2 {
        margin-bottom: 8px !important;
    }

    /* CHECKBOX */
    .el-checkbox {
        margin: 0;
        font-weight: 600;
    }

    .el-checkbox__label {
        color: #495057;
        font-size: 15px;
    }

    .el-checkbox__inner {
        width: 18px;
        height: 18px;
        border: 2px solid #667eea;
    }

    .el-checkbox__input.is-checked .el-checkbox__inner {
        background-color: #667eea;
        border-color: #667eea;
    }

    /* SEPARADORES DE TÍTULO */
    .separator-title {
        color: #2c3e50;
        font-size: 16px;
        font-weight: 700;
        margin: 30px 0 20px 0;
        padding: 12px 16px;
        border-left: 4px solid #667eea;
        background: rgba(102, 126, 234, 0.05);
        border-radius: 4px;
    }

    /* UPLOAD DE IMÁGENES */
    .avatar-uploader {
        border: 3px dashed #d9d9d9;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #fafafa;
    }

    .avatar-uploader:hover {
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.05);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
    }

    .avatar-uploader-icon {
        font-size: 32px;
        color: #8c939d;
        width: 140px;
        height: 140px;
        line-height: 140px;
        text-align: center;
        transition: all 0.3s ease;
    }

    .avatar-uploader:hover .avatar-uploader-icon {
        color: #667eea;
        transform: scale(1.1);
    }

    .avatar {
        width: 140px;
        height: 140px;
        display: block;
        border-radius: 10px;
        object-fit: cover;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    /* TABLAS DENTRO DEL FORMULARIO */
    .table {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .table thead th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 700;
        border: none;
        padding: 14px 10px;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
    }

    .table tbody tr {
        transition: background-color 0.2s ease;
    }

    .table tbody tr:hover {
        background-color: rgba(102, 126, 234, 0.05);
    }

    .table tbody td {
        padding: 12px 10px;
        border-bottom: 1px solid #e9ecef;
    }

    /* BOTONES */
    .btn-search {
        height: 38px;
        padding: 0 20px;
        border-radius: 6px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-search:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    /* TOOLTIPS */
    .el-tooltip__popper {
        max-width: 350px;
        font-size: 13px;
        padding: 10px 14px;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    /* MENSAJES DE ERROR */
    .form-control-feedback {
        display: block;
        margin-top: 6px;
        font-size: 12px;
        font-weight: 600;
        color: #dc3545;
    }

    .has-danger .el-input__inner,
    .has-danger .el-textarea__inner {
        border-color: #dc3545;
        background-color: rgba(220, 53, 69, 0.05);
    }

    .has-danger label {
        color: #dc3545;
    }

    /* ESPACIADO */
    .field-margin {
        margin-top: 20px;
    }

    /* FILA DE CHECKBOXES */
    .checkbox-row {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        padding: 16px 20px;
        background: rgba(102, 126, 234, 0.05);
        border-radius: 8px;
        border-left: 4px solid #667eea;
        margin: 10px 0;
    }

    .checkbox-item {
        flex: 0 1 auto;
        display: flex;
        flex-direction: column;
    }

    .checkbox-item .el-checkbox {
        font-size: 14px;
        font-weight: 600;
    }

    .checkbox-item .form-control-feedback {
        margin-top: 4px;
        margin-left: 24px;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        .section-card {
            padding: 16px;
        }

        .section-title {
            font-size: 15px;
        }

        .items-modal-modern .el-dialog {
            width: 95% !important;
            margin-top: 20px !important;
        }

        .items-modal-modern .el-dialog__body {
            padding: 20px 15px;
        }

        .quick-add-actions {
            float: none;
            display: block;
            margin: 8px 0;
        }

        .checkbox-row {
            flex-direction: column;
            gap: 12px;
        }
    }

    /* ANIMACIONES */
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    .form-body {
        animation: fadeIn 0.3s ease-in;
    }
</style>
