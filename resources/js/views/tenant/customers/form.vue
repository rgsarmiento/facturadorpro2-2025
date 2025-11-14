<template>
    <el-dialog 
        width="75%" 
        :title="titleDialog" 
        :visible="showDialog" 
        :close-on-click-modal="false" 
        @close="close" 
        @open="create" 
        append-to-body 
        top="5vh"
        custom-class="customers-modal-modern">
        <form autocomplete="off" @submit.prevent="submit">
            <div class="form-body">
                <!-- Información de Identificación -->
                <div class="section-card">
                    <h4 class="section-title"><i class="fa fa-id-card"></i> Información de Identificación</h4>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group" :class="{'has-danger': errors.identity_document_type_id}">
                                <label class="control-label"><i class="fa fa-file-text text-primary"></i> Tipo de Documento</label>
                                <el-select v-model="form.identity_document_type_id" filterable placeholder="Seleccione tipo">
                                    <el-option v-for="option in identity_document_types" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.identity_document_type_id" v-text="errors.identity_document_type_id[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group" :class="{'has-danger': errors.number}">
                                <label class="control-label"><i class="fa fa-hashtag text-info"></i> Número de Documento</label>
                                <el-input v-model="form.number" :maxlength="maxLength" placeholder="Ingrese número">
                                    <template v-if="form.identity_document_type_id === '6' || form.identity_document_type_id === '1'">
                                        <el-button type="primary" slot="append" :loading="loading_search" icon="el-icon-search" @click.prevent="searchCustomer"></el-button>
                                    </template>
                                </el-input>
                                <small class="form-control-feedback" v-if="errors.number" v-text="errors.number[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-4"></div>
                    </div>
                </div>

                <!-- Información General -->
                <div class="section-card">
                    <h4 class="section-title"><i class="fa fa-user"></i> Información General</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group" :class="{'has-danger': errors.name}">
                                <label class="control-label"><i class="fa fa-user-circle text-success"></i> Nombre / Razón Social</label>
                                <el-input v-model="form.name" placeholder="Nombre completo o razón social"></el-input>
                                <small class="form-control-feedback" v-if="errors.name" v-text="errors.name[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group" :class="{'has-danger': errors.trade_name}">
                                <label class="control-label"><i class="fa fa-briefcase text-warning"></i> Nombre Comercial</label>
                                <el-input v-model="form.trade_name" placeholder="Nombre comercial (opcional)"></el-input>
                                <small class="form-control-feedback" v-if="errors.trade_name" v-text="errors.trade_name[0]"></small>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Ubicación -->
                <div class="section-card">
                    <h4 class="section-title"><i class="fa fa-map-marker"></i> Ubicación</h4>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group" :class="{'has-danger': errors.country_id}">
                                <label class="control-label"><i class="fa fa-globe text-primary"></i> País</label>
                                <el-select v-model="form.country_id" filterable placeholder="Seleccione">
                                    <el-option v-for="option in countries" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.country_id" v-text="errors.country_id[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group" :class="{'has-danger': errors.department_id}">
                                <label class="control-label"><i class="fa fa-map text-info"></i> Departamento</label>
                                <el-select v-model="form.department_id" filterable @change="filterProvince" placeholder="Seleccione">
                                    <el-option v-for="option in all_departments" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.department_id" v-text="errors.department_id[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group" :class="{'has-danger': errors.province_id}">
                                <label class="control-label"><i class="fa fa-map-signs text-warning"></i> Provincia</label>
                                <el-select v-model="form.province_id" filterable @change="filterDistrict" placeholder="Seleccione">
                                    <el-option v-for="option in provinces" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.province_id" v-text="errors.province_id[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group" :class="{'has-danger': errors.district_id}">
                                <label class="control-label"><i class="fa fa-location-arrow text-danger"></i> Distrito</label>
                                <el-select v-model="form.district_id" filterable placeholder="Seleccione">
                                    <el-option v-for="option in districts" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.district_id" v-text="errors.district_id[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group" :class="{'has-danger': errors.address}">
                                <label class="control-label"><i class="fa fa-home text-success"></i> Dirección Completa</label>
                                <el-input v-model="form.address" placeholder="Av/Jr/Calle, número, urbanización, referencia"></el-input>
                                <small class="form-control-feedback" v-if="errors.address" v-text="errors.address[0]"></small>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Información de Contacto -->
                <div class="section-card">
                    <h4 class="section-title"><i class="fa fa-address-book"></i> Información de Contacto</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group" :class="{'has-danger': errors.telephone}">
                                <label class="control-label"><i class="fa fa-phone text-success"></i> Teléfono</label>
                                <el-input v-model="form.telephone" placeholder="Número de teléfono o celular">
                                    <template slot="prepend"><i class="fa fa-mobile"></i></template>
                                </el-input>
                                <small class="form-control-feedback" v-if="errors.telephone" v-text="errors.telephone[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group" :class="{'has-danger': errors.email}">
                                <label class="control-label"><i class="fa fa-envelope text-primary"></i> Correo Electrónico</label>
                                <el-input v-model="form.email" type="email" placeholder="correo@ejemplo.com">
                                    <template slot="prepend">@</template>
                                </el-input>
                                <small class="form-control-feedback" v-if="errors.email" v-text="errors.email[0]"></small>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="form-actions text-right mt-4">
                <el-button @click.prevent="close()">Cancelar</el-button>
                <el-button type="primary" native-type="submit" :loading="loading_submit">Guardar</el-button>
            </div>
        </form>
    </el-dialog>
</template>

<script>

    import {serviceNumber} from '../../../mixins/functions'

    export default {
        mixins: [serviceNumber],
        props: ['showDialog', 'recordId', 'external'],
        data() {
            return {
                loading_submit: false,
                titleDialog: null,
                resource: 'customers',
                errors: {},
                form: {},
                countries: [],
                all_departments: [],
                all_provinces: [],
                all_districts: [],
                provinces: [],
                districts: [],
                identity_document_types: []
            }
        },
        created() {
            this.initForm()
            this.$http.get(`/${this.resource}/tables`)
                .then(response => {
                    this.countries = response.data.countries
                    this.all_departments = response.data.departments
                    this.all_provinces = response.data.provinces
                    this.all_districts = response.data.districts
                    this.identity_document_types = response.data.identity_document_types
                })
        },
        computed: {
            maxLength: function () {
                if (this.form.identity_document_type_id === '066') {
                    return 11
                }
                if (this.form.identity_document_type_id === '061') {
                    return 8
                }
            }
        },
        methods: {
            initForm() {
                this.errors = {}
                this.form = {
                    id: null,
                    identity_document_type_id: '6',
                    number: null,
                    name: null,
                    trade_name: null,
                    country_id: 'PE',
                    department_id: null,
                    province_id: null,
                    district_id: null,
                    address: null,
                    telephone: null,
                    email: null,
                    more_address: [],
                }
            },
            create() {
                this.titleDialog = (this.recordId)? 'Editar Cliente':'Nuevo Cliente'
                if (this.recordId) {
                    this.$http.get(`/${this.resource}/record/${this.recordId}`)
                        .then(response => {
                            this.form = response.data.data
                            this.filterProvinces()
                            this.filterDistricts()
                        })
                }
            },

            submit() {
                this.loading_submit = true
                this.$http.post(`/${this.resource}`, this.form)
                    .then(response => {
                        if (response.data.success) {
                            this.$message.success(response.data.message)
                            if (this.external) {
                                this.$eventHub.$emit('reloadDataCustomers', response.data.id)
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
                this.initForm()
            },
            searchCustomer() {
                this.searchServiceNumberByType()
            },
            filterProvince() {
                this.provinces = this.all_provinces.filter(province => {
                    return province.department_id === this.form.department_id
                })
                this.form.province_id = null
                this.form.district_id = null
                this.districts = []
            },
            filterDistrict() {
                this.districts = this.all_districts.filter(district => {
                    return district.province_id === this.form.province_id
                })
                this.form.district_id = null
            },
            filterProvinces() {
                if (this.form.department_id) {
                    this.provinces = this.all_provinces.filter(province => {
                        return province.department_id === this.form.department_id
                    })
                }
            },
            filterDistricts() {
                if (this.form.province_id) {
                    this.districts = this.all_districts.filter(district => {
                        return district.province_id === this.form.province_id
                    })
                }
            }
        }
    }
</script>

<style scoped>
    /* ====================================
       ESTILOS PROFESIONALES PARA EL MODAL CUSTOMERS
       ==================================== */

    /* HEADER DEL MODAL */
    .customers-modal-modern .el-dialog {
        border-radius: 12px;
        box-shadow: 0 12px 48px rgba(0, 0, 0, 0.15);
        overflow: hidden;
    }

    .customers-modal-modern .el-dialog__header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px 12px 0 0;
        padding: 24px 30px;
        border-bottom: 3px solid rgba(255, 255, 255, 0.2);
    }

    .customers-modal-modern .el-dialog__title {
        color: white;
        font-weight: 700;
        font-size: 20px;
        letter-spacing: 0.5px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .customers-modal-modern .el-dialog__headerbtn .el-dialog__close {
        color: white;
        font-size: 24px;
        font-weight: bold;
    }

    .customers-modal-modern .el-dialog__headerbtn .el-dialog__close:hover {
        color: #fff;
        transform: rotate(90deg);
        transition: transform 0.3s ease;
    }

    .customers-modal-modern .el-dialog__body {
        padding: 30px;
        background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
        max-height: 75vh;
        overflow-y: auto;
    }

    /* SCROLLBAR PERSONALIZADO */
    .customers-modal-modern .el-dialog__body::-webkit-scrollbar {
        width: 8px;
    }

    .customers-modal-modern .el-dialog__body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .customers-modal-modern .el-dialog__body::-webkit-scrollbar-thumb {
        background: #667eea;
        border-radius: 10px;
    }

    .customers-modal-modern .el-dialog__body::-webkit-scrollbar-thumb:hover {
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

    /* BOTONES */
    .form-actions {
        padding: 20px 30px;
        background: white;
        border-top: 2px solid #e9ecef;
        border-radius: 0 0 12px 12px;
        margin: 0 -30px -30px -30px;
    }

    .el-button {
        border-radius: 6px;
        font-weight: 600;
        padding: 12px 24px;
        transition: all 0.3s ease;
    }

    .el-button--primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
    }

    .el-button--primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .el-button:not(.el-button--primary) {
        border: 2px solid #e9ecef;
    }

    .el-button:not(.el-button--primary):hover {
        border-color: #667eea;
        color: #667eea;
    }

    /* BOTÓN DE BÚSQUEDA EN INPUT */
    .el-input-group__append .el-button {
        background: #667eea;
        border: none;
        color: white;
    }

    .el-input-group__append .el-button:hover {
        background: #5568d3;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        .section-card {
            padding: 16px;
        }

        .section-title {
            font-size: 15px;
        }

        .customers-modal-modern .el-dialog {
            width: 95% !important;
            margin-top: 20px !important;
        }

        .customers-modal-modern .el-dialog__body {
            padding: 20px 15px;
        }

        .form-actions {
            padding: 15px 20px;
            margin: 0 -15px -20px -15px;
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
