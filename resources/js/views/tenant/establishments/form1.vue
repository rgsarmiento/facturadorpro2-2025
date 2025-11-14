<template>
    <el-dialog 
        width="75%" 
        top="5vh"
        :title="titleDialog" 
        :visible="showDialog" 
        @close="close" 
        @open="create"
        :close-on-click-modal="false"
        append-to-body
        custom-class="establishments-modal-modern">
        <form autocomplete="off" @submit.prevent="submit">
            <div class="form-body">
                <!-- Información Básica -->
                <div class="section-card">
                    <h4 class="section-title"><i class="fa fa-building"></i> Información Básica del Establecimiento</h4>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group" :class="{'has-danger': errors.description}">
                                <label class="control-label"><i class="fa fa-tag text-primary"></i> Descripción</label>
                                <el-input v-model="form.description" placeholder="Nombre o descripción del establecimiento"></el-input>
                                <small class="form-control-feedback" v-if="errors.description" v-text="errors.description[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group" :class="{'has-danger': errors.code}">
                                <label class="control-label"><i class="fa fa-barcode text-info"></i> Código Domicilio Fiscal</label>
                                <el-input v-model="form.code" :maxlength="4" placeholder="Máx. 4 caracteres"></el-input>
                                <small class="form-control-feedback" v-if="errors.code" v-text="errors.code[0]"></small>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Ubicación -->
                <div class="section-card">
                    <h4 class="section-title"><i class="fa fa-map-marker"></i> Ubicación</h4>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group" :class="{'has-danger': errors.country_id}">
                                <label class="control-label"><i class="fa fa-globe text-primary"></i> País</label>
                                <el-select v-model="form.country_id" filterable @change="departmentss()" placeholder="Seleccione">
                                    <el-option v-for="option in countries" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.country_id" v-text="errors.country_id[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group" :class="{'has-danger': errors.department_id}">
                                <label class="control-label"><i class="fa fa-map text-info"></i> Departamento</label>
                                <el-select v-model="form.department_id" filterable @change="citiess()" placeholder="Seleccione">
                                    <el-option v-for="option in departments" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.department_id" v-text="errors.department_id[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group" :class="{'has-danger': errors.city_id}">
                                <label class="control-label"><i class="fa fa-building-o text-warning"></i> Ciudad</label>
                                <el-select v-model="form.city_id" filterable placeholder="Seleccione">
                                    <el-option v-for="option in cities" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.city_id" v-text="errors.city_id[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group" :class="{'has-danger': errors.address}">
                                <label class="control-label"><i class="fa fa-home text-success"></i> Dirección Fiscal</label>
                                <el-input v-model="form.address" placeholder="Dirección completa del domicilio fiscal"></el-input>
                                <small class="form-control-feedback" v-if="errors.address" v-text="errors.address[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group" :class="{'has-danger': errors.telephone}">
                                <label class="control-label"><i class="fa fa-phone text-success"></i> Teléfono</label>
                                <el-input v-model="form.telephone" placeholder="Número de contacto">
                                    <template slot="prepend"><i class="fa fa-mobile"></i></template>
                                </el-input>
                                <small class="form-control-feedback" v-if="errors.telephone" v-text="errors.telephone[0]"></small>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Información de Contacto -->
                <div class="section-card">
                    <h4 class="section-title"><i class="fa fa-address-book"></i> Información de Contacto</h4>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group" :class="{'has-danger': errors.trade_address}">
                                <label class="control-label"><i class="fa fa-map-signs text-primary"></i> Dirección Comercial</label>
                                <el-input v-model="form.trade_address" placeholder="Dirección donde se realizan las operaciones comerciales"></el-input>
                                <small class="form-control-feedback" v-if="errors.trade_address" v-text="errors.trade_address[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group" :class="{'has-danger': errors.email}">
                                <label class="control-label"><i class="fa fa-envelope text-info"></i> Correo de Contacto</label>
                                <el-input v-model="form.email" type="email" placeholder="correo@establecimiento.com">
                                    <template slot="prepend">@</template>
                                </el-input>
                                <small class="form-control-feedback" v-if="errors.email" v-text="errors.email[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group" :class="{'has-danger': errors.web_address}">
                                <label class="control-label"><i class="fa fa-link text-success"></i> Dirección Web</label>
                                <el-input v-model="form.web_address" placeholder="https://www.ejemplo.com">
                                    <template slot="prepend"><i class="fa fa-external-link"></i></template>
                                </el-input>
                                <small class="form-control-feedback" v-if="errors.web_address" v-text="errors.web_address[0]"></small>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Información Adicional y Configuración -->
                <div class="section-card">
                    <h4 class="section-title"><i class="fa fa-cogs"></i> Configuración Adicional</h4>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group" :class="{'has-danger': errors.aditional_information}">
                                <label class="control-label"><i class="fa fa-info-circle text-primary"></i> Información Adicional</label>
                                <el-input v-model="form.aditional_information" type="textarea" :rows="3" placeholder="Cualquier información adicional relevante"></el-input>
                                <small class="form-control-feedback" v-if="errors.aditional_information" v-text="errors.aditional_information[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group" :class="{'has-danger': errors.tables}">
                                <label class="control-label"><i class="fa fa-table text-warning"></i> Cantidad de Mesas</label>
                                <el-input v-model="form.tables" :maxlength="4" placeholder="Ej: 10">
                                    <template slot="prepend">#</template>
                                </el-input>
                                <small class="form-control-feedback" v-if="errors.tables" v-text="errors.tables[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group logo-upload-section">
                                <label class="control-label"><i class="fa fa-image text-info"></i> Logo JPG del Establecimiento</label>
                                <el-input v-model="form.establishment_logo" :readonly="true" placeholder="Sin archivo seleccionado">
                                    <el-upload slot="append"
                                        :headers="headers"
                                        :data="{'type': 'establishment_logo', 'establishment_id': form.id}"
                                        action="/companies/uploads"
                                        :show-file-list="false"
                                        :on-success="successUpload">
                                        <el-button type="primary" icon="el-icon-upload">Subir Logo</el-button>
                                    </el-upload>
                                </el-input>
                                <div class="upload-hint"><i class="fa fa-exclamation-triangle"></i> Debe ser un archivo JPG</div>
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
    export default {
        props: ['showDialog', 'recordId'],
        data() {
            return {
                loading_submit: false,
                titleDialog: null,
                headers: headers_token,
                resource: 'establishments',
                errors: {},
                form: {},
                countries: [],
                departments: [],
                cities: [],
                all_provinces: [],
                all_districts: [],
                provinces: [],
                districts: [],
            }
        },

        async created() {
            await this.initForm()
            await this.$http.get(`/${this.resource}/tables`)
                .then(response => {
                    this.countries = response.data.countries
                    // this.all_departments = response.data.departments
                    // this.all_provinces = response.data.provinces
                    // this.all_districts = response.data.districts
                })
        },
        methods: {
            getDepartment(val) {
                return axios
                            .post(`/departments/${val}`).then(response => {
                                return response.data;
                            })
                            .catch(error => {
                                console.log(error)
                            });
            },

            getCities(val) {
                return axios
                            .post(`/cities/${val}`).then(response => {
                                return response.data;
                            })
                            .catch(error => {
                                console.log(error)
                            });
            },
            initForm() {
                this.errors = {}
                this.form = {
                    id: null,
                    description: null,
                    country_id: null,
                    department_id: null,
                    city_id: null,
                    address: null,
                    telephone: null,
                    email: null,
                    code: null,
                    trade_address: null,
                    web_address: null,
                    aditional_information: null,
                    establishment_logo: null,
                    tables : null,
                }

                this.departmentss();
                this.citiess();
            },
            departmentss(edit = false) {
                if (!edit) {
                    // console.log("s")
                    this.form.department_id = null;
                    this.form.city_id = null;
                    this.departments = [];
                    this.cities = [];
                }

                if (this.form.country_id != null) this.getDepartment(this.form.country_id).then(rows => this.departments = rows);
            },
            citiess(edit = false) {
                if (!edit) {
                    this.form.city_id = null;
                    this.cities = [];
                }

                if (this.form.department_id != null) this.getCities(this.form.department_id).then(rows => this.cities = rows);
            },
            async create() {
                this.titleDialog = (this.recordId)? 'Editar Establecimiento':'Nuevo Establecimiento'
                if (this.recordId) {
                    await this.$http.get(`/${this.resource}/record/${this.recordId}`)
                        .then(response => {
                            if (response.data !== '') {
                                this.form = response.data.data
                                // this.filterProvinces()
                                // this.filterDistricts()

                                this.departmentss(true);
                                this.citiess(true);
                            }
                        })
                }
//                console.log(this.form)
            },
            submit() {
                this.loading_submit = true
                this.$http.post(`/${this.resource}`, this.form)
                    .then(response => {
                        if (response.data.success) {
                            this.$message.success(response.data.message)
                            this.$eventHub.$emit('reloadData')
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
            filterProvince() {
                this.form.province_id = null
                this.form.district_id = null
                this.filterProvinces()
            },
            filterProvinces() {
                this.provinces = this.all_provinces.filter(f => {
                    return f.department_id === this.form.department_id
                })
            },
            filterDistrict() {
                this.form.district_id = null
                this.filterDistricts()
            },
            filterDistricts() {
                this.districts = this.all_districts.filter(f => {
                    return f.province_id === this.form.province_id
                })
            },

            close() {
                this.$emit('update:showDialog', false)
                this.initForm()
            },

            successUpload(response, file, fileList) {
//                console.log(response)
                if (response.success) {
                    this.$message.success(response.message)
                    this.form[response.type] = response.name
                } else {
                    // this.$message({message:'Error al subir el archivo', type: 'error'})
                    this.$message({message: response.message, type: 'error'})
                }
            },

        }
    }
</script>

<style scoped>
/* ==============================================
   MODAL MODERNO DE ESTABLECIMIENTOS
   Diseño profesional con cards y animaciones
   ============================================== */

/* Dialog personalizado */
::v-deep .establishments-modal-modern {
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

::v-deep .establishments-modal-modern .el-dialog__header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 20px 30px;
  margin: 0;
  border-bottom: none;
}

::v-deep .establishments-modal-modern .el-dialog__title {
  color: white;
  font-weight: 600;
  font-size: 20px;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

::v-deep .establishments-modal-modern .el-dialog__close {
  color: white !important;
  font-size: 20px;
  font-weight: bold;
  transition: transform 0.3s ease;
}

::v-deep .establishments-modal-modern .el-dialog__close:hover {
  transform: rotate(90deg);
}

::v-deep .establishments-modal-modern .el-dialog__body {
  padding: 30px;
  background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
  max-height: 75vh;
  overflow-y: auto;
}

/* Scrollbar personalizado */
::v-deep .establishments-modal-modern .el-dialog__body::-webkit-scrollbar {
  width: 8px;
}

::v-deep .establishments-modal-modern .el-dialog__body::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

::v-deep .establishments-modal-modern .el-dialog__body::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 10px;
}

::v-deep .establishments-modal-modern .el-dialog__body::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

/* Cards de secciones */
.section-card {
  background: white;
  border-radius: 10px;
  padding: 25px;
  margin-bottom: 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e8eaf6;
  transition: all 0.3s ease;
  animation: slideIn 0.5s ease;
}

.section-card:hover {
  box-shadow: 0 5px 20px rgba(102, 126, 234, 0.15);
  transform: translateY(-2px);
  border-color: #667eea;
}

/* Títulos de secciones */
.section-title {
  font-size: 18px;
  font-weight: 600;
  color: #2c3e50;
  margin: 0 0 20px 0;
  padding-bottom: 12px;
  border-bottom: 2px solid transparent;
  background: linear-gradient(90deg, #667eea 0%, transparent 100%);
  background-position: 0 100%;
  background-size: 100% 2px;
  background-repeat: no-repeat;
  display: flex;
  align-items: center;
  gap: 10px;
}

.section-title i {
  font-size: 20px;
  color: #667eea;
  animation: pulse 2s ease-in-out infinite;
}

/* Grupo de formulario */
.form-group {
  margin-bottom: 20px;
}

.form-group label {
  font-weight: 500;
  color: #495057;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.form-group label i {
  font-size: 14px;
  opacity: 0.8;
}

/* Inputs y selects */
::v-deep .el-input__inner,
::v-deep .el-select {
  border-radius: 6px;
  transition: all 0.3s ease;
}

::v-deep .el-input__inner:focus,
::v-deep .el-select:hover .el-input__inner {
  border-color: #667eea;
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
}

::v-deep .el-textarea__inner:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
}

::v-deep .el-input-group__prepend {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 6px 0 0 6px;
}

/* Iconos de texto */
.text-primary { color: #667eea !important; }
.text-info { color: #17a2b8 !important; }
.text-success { color: #28a745 !important; }
.text-warning { color: #ffc107 !important; }
.text-danger { color: #dc3545 !important; }

/* Sección de upload de logo */
.logo-upload-section {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 20px;
  border-radius: 8px;
  border: 2px dashed #667eea;
  margin-top: 15px;
}

.upload-hint {
  font-size: 12px;
  color: #dc3545;
  margin-top: 8px;
  display: flex;
  align-items: center;
  gap: 5px;
  font-style: italic;
}

::v-deep .el-input-group__append {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 0 6px 6px 0;
}

::v-deep .el-input-group__append .el-button {
  background: transparent;
  color: white;
  border: none;
  margin: 0;
  padding: 0 15px;
}

::v-deep .el-input-group__append .el-button:hover {
  background: rgba(255, 255, 255, 0.2);
}

/* Mensajes de validación */
.form-control-feedback {
  color: #dc3545;
  font-size: 12px;
  margin-top: 5px;
  display: block;
  animation: shake 0.3s ease;
}

.has-danger .el-input__inner,
.has-danger .el-select .el-input__inner,
.has-danger .el-textarea__inner {
  border-color: #dc3545 !important;
}

/* Footer del formulario */
.form-actions {
  background: #f8f9fa;
  padding: 20px;
  margin: 20px -30px -30px -30px;
  border-top: 1px solid #e9ecef;
  border-radius: 0 0 12px 12px;
}

.form-actions .el-button {
  padding: 12px 30px;
  font-weight: 500;
  border-radius: 6px;
  transition: all 0.3s ease;
}

.form-actions .el-button--primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
}

.form-actions .el-button--primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

/* Animaciones */
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

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}

/* Responsivo */
@media (max-width: 768px) {
  ::v-deep .establishments-modal-modern .el-dialog {
    width: 95% !important;
  }
  
  .section-card {
    padding: 15px;
  }
  
  .section-title {
    font-size: 16px;
  }
}
</style>
