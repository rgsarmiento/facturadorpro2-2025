<template>
    <el-dialog
        :title="titleDialog"
        :visible="showDialog"
        @close="close"
        @open="create"
        :close-on-click-modal="false">
        <form autocomplete="off" @submit.prevent="submit">
            <div class="form-body">
                <div class="row">
                    <div class="col-md-12 mb-2">
                        <h4><b>Datos de la empresa</b></h4>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.identification_number}">
                            <label class="control-label">Número de identificación</label>
                            <el-input  v-model="form.identification_number" :maxlength="15" :disabled="form.is_update">
                            </el-input>
                            <small class="form-control-feedback" v-if="errors.identification_number" v-text="errors.identification_number[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.dv}">
                            <label class="control-label">Dv</label>
                            <el-input  v-model="form.dv" :disabled="form.is_update"></el-input>
                            <small class="form-control-feedback" v-if="errors.dv" v-text="errors.dv[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.name}">
                            <label class="control-label">Nombre de la Empresa</label>
                            <el-input  v-model="form.name" :maxlength="120" :disabled="form.is_update"></el-input>
                            <small class="form-control-feedback" v-if="errors.name" v-text="errors.name[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': (errors.subdomain || errors.uuid)}">
                            <label class="control-label">Nombre de Subdominio</label>
                            <el-input v-model="form.subdomain" :disabled="form.is_update">
                                <template slot="append">{{ url_base }}</template>
                            </el-input>
                            <small class="form-control-feedback" v-if="errors.subdomain" v-text="errors.subdomain[0]"></small>
                            <small class="form-control-feedback" v-if="errors.uuid" v-text="errors.uuid[0]"></small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-2">
                        <label class="control-label">
                            API Token
                            <el-tooltip class="item" effect="dark" content="Código de acceso que permite a las aplicaciones o usuarios ejecutar funciones de API" placement="top-start">
                                <i class="fa fa-info-circle"></i>
                            </el-tooltip>
                        </label>
                        <el-input v-model="form.api_token" :disabled="true"></el-input>
                        <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click.prevent="copyToClipboard">Copiar</button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-2">
                        <h4><b>Datos usuario</b></h4>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.email}">
                            <label class="control-label">Correo de Acceso</label>
                            <el-input  v-model="form.email" :disabled="form.is_update"></el-input>
                            <small class="form-control-feedback" v-if="errors.email" v-text="errors.email[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': (errors.password)}">
                            <label class="control-label">Contraseña</label>
                            <el-input type="password"  v-model="form.password"></el-input>
                            <small class="form-control-feedback" v-if="errors.password" v-text="errors.password[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': (errors.password_confirmation)}">
                            <label class="control-label">Confirmar contraseña</label>
                            <el-input type="password"  v-model="form.password_confirmation"></el-input>
                            <small class="form-control-feedback" v-if="errors.password_confirmation" v-text="errors.password_confirmation[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div  class="form-group" :class="{'has-danger': errors.type_liability_id}">
                            <label class="control-label">Tipo de responsabilidad</label>
                            <el-select filterable  v-model="form.type_liability_id">
                                <el-option v-for="option in type_liabilities" :key="option.id" :value="option.id" :label="option.name"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.type_liability_id" v-text="errors.type_liability_id[0]"></small>
                        </div>
                    </div>

                </div>


                <div class="row">

                    <div class="col-md-12 mb-2">
                        <h4><b>Datos generales</b></h4>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group" :class="{'has-danger': (errors.limit_documents)}">
                            <label class="control-label">Límite de documentos</label>
                            <el-input  v-model="form.limit_documents"></el-input>
                            <small class="form-control-feedback" v-if="errors.limit_documents" v-text="errors.limit_documents[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group" :class="{'has-danger': (errors.limit_users)}">
                            <label class="control-label">Límite de usuarios</label>
                            <el-input  v-model="form.limit_users"></el-input>
                            <small class="form-control-feedback" v-if="errors.limit_users" v-text="errors.limit_users[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group" :class="{'has-danger': (errors.economic_activity_code)}">
                            <label class="control-label">Actividad económica</label>
                            <el-input  v-model="form.economic_activity_code"></el-input>
                            <small class="form-control-feedback" v-if="errors.economic_activity_code" v-text="errors.economic_activity_code[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group" :class="{'has-danger': (errors.ica_rate)}">
                            <label class="control-label">Tasa ICA</label>
                            <el-input  v-model="form.ica_rate"></el-input>
                            <small class="form-control-feedback" v-if="errors.ica_rate" v-text="errors.ica_rate[0]"></small>
                        </div>
                    </div>


                    <div class="col-md-6">
                        <div  class="form-group" :class="{'has-danger': errors.type_document_identification_id}">
                            <label class="control-label">Seleccionar Tipo Documento</label>
                            <el-select filterable  v-model="form.type_document_identification_id">
                                <el-option v-for="option in type_document_identifications" :key="option.id" :value="option.id" :label="option.name"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.type_document_identification_id" v-text="errors.type_document_identification_id[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div  class="form-group" :class="{'has-danger': errors.department_id}">
                            <label class="control-label">Seleccionar Departamento</label>
                            <el-select filterable  v-model="form.department_id" @change="cascade">
                                <el-option v-for="option in departments" :key="option.id" :value="option.id" :label="option.name"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.department_id" v-text="errors.department_id[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div  class="form-group" :class="{'has-danger': errors.municipality_id}">
                            <label class="control-label">Seleccionar Municipio</label>
                            <el-select filterable  v-model="form.municipality_id">
                                <el-option v-for="option in municipalities" :key="option.id" :value="option.id" :label="option.name"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.municipality_id" v-text="errors.municipality_id[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div  class="form-group" :class="{'has-danger': errors.type_organization_id}">
                            <label class="control-label">Seleccionar Tipo Organizacion</label>
                            <el-select filterable  v-model="form.type_organization_id">
                                <el-option v-for="option in type_organizations" :key="option.id" :value="option.id" :label="option.name"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.type_organization_id" v-text="errors.type_organization_id[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div  class="form-group" :class="{'has-danger': errors.type_regime_id}">
                            <label class="control-label">Seleccionar Regimen</label>
                            <el-select filterable  v-model="form.type_regime_id">
                                <el-option v-for="option in type_regimes" :key="option.id" :value="option.id" :label="option.name"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.type_regime_id" v-text="errors.type_regime_id[0]"></small>
                        </div>
                    </div>


                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': (errors.merchant_registration)}">
                            <label class="control-label">Registro mercantil</label>
                            <el-input  v-model="form.merchant_registration"></el-input>
                            <small class="form-control-feedback" v-if="errors.merchant_registration" v-text="errors.merchant_registration[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': (errors.address)}">
                            <label class="control-label">Dirección</label>
                            <el-input  v-model="form.address"></el-input>
                            <small class="form-control-feedback" v-if="errors.address" v-text="errors.address[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': (errors.phone)}">
                            <label class="control-label">Teléfono</label>
                            <el-input  v-model="form.phone"></el-input>
                            <small class="form-control-feedback" v-if="errors.phone" v-text="errors.phone[0]"></small>
                        </div>
                    </div>

                </div>
                <div class="row mt-2">
                    <div class="col-md-12" >
                        <div class="form-group">
                            <label class="control-label">Módulos</label>
                            <div class="row">
                                <div class="col-4" v-for="(module,ind) in form.modules" :key="ind">
                                    <div class="d-flex align-items-center">
                                        <el-checkbox v-model="module.checked">{{ module.description }}</el-checkbox>
                                        <span v-if="module.id === 9" class="badge badge-warning ml-2" style="font-size: 10px;">En construcción</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions text-right pt-2">
                <el-button @click.prevent="close()">Cancelar</el-button>
                <el-button type="primary" native-type="submit" :loading="loading_submit" dusk="submit">
                    <template v-if="loading_submit">
                        {{button_text}}
                    </template>
                    <template v-else>
                        Guardar
                    </template>
                </el-button>
            </div>
        </form>
    </el-dialog>
</template>

<style scoped>
/* Windows-style Modal Styles */
.el-dialog__wrapper {
    backdrop-filter: blur(2px);
    background-color: rgba(0, 0, 0, 0.3);
}

.el-dialog {
    background: linear-gradient(180deg, #f0f0f0 0%, #e8e8e8 100%);
    border: 1px solid #999;
    border-radius: 8px 8px 0 0;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), 0 2px 8px rgba(0, 0, 0, 0.2);
    margin-top: 5vh !important;
    margin-bottom: 5vh !important;
    overflow: hidden;
}

.el-dialog__header {
    background: linear-gradient(180deg, #ffffff 0%, #e5e5e5 100%);
    border-bottom: 1px solid #ccc;
    padding: 12px 20px;
    position: relative;
    cursor: move;
}

.el-dialog__title {
    color: #333;
    font-size: 14px;
    font-weight: 600;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
    text-shadow: 0 1px 0 rgba(255, 255, 255, 0.8);
}

.el-dialog__headerbtn {
    top: 50%;
    right: 15px;
    transform: translateY(-50%);
}

.el-dialog__close {
    color: #666;
    font-size: 16px;
    background: linear-gradient(180deg, #ffffff 0%, #e5e5e5 100%);
    border: 1px solid #999;
    border-radius: 3px;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.el-dialog__close:hover {
    background: linear-gradient(180deg, #ff6b6b 0%, #e74c3c 100%);
    color: white;
    border-color: #c0392b;
}

.el-dialog__body {
    background: #f5f5f5;
    padding: 20px;
    color: #333;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
    max-height: 70vh;
    overflow-y: auto;
}

/* Form styling dentro del modal */
.form-body {
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
}

.form-body h4 {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 2px solid #3498db;
}

.control-label {
    color: #555;
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 6px;
}

.form-actions {
    background: #f8f9fa;
    border-top: 1px solid #ddd;
    padding: 15px 20px;
    margin: 0 -20px -20px -20px;
}

/* Element UI component overrides */
.el-input__inner {
    border: 1px solid #ccc;
    border-radius: 3px;
    background: white;
    transition: border-color 0.2s ease;
}

.el-input__inner:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

.el-select .el-input__inner {
    background: white;
}

.el-button {
    border-radius: 3px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.el-button--primary {
    background: linear-gradient(180deg, #3498db 0%, #2980b9 100%);
    border-color: #2980b9;
}

.el-button--primary:hover {
    background: linear-gradient(180deg, #5dade2 0%, #3498db 100%);
    border-color: #3498db;
    transform: translateY(-1px);
}

.el-button:not(.el-button--primary) {
    background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);
    border-color: #ced4da;
    color: #495057;
}

.el-button:not(.el-button--primary):hover {
    background: linear-gradient(180deg, #e9ecef 0%, #dee2e6 100%);
    border-color: #adb5bd;
    transform: translateY(-1px);
}

/* Checkbox styling */
.el-checkbox {
    margin-bottom: 8px;
}

.el-checkbox__label {
    color: #555;
    font-size: 13px;
}

/* Scrollbar styling para el modal */
.el-dialog__body::-webkit-scrollbar {
    width: 12px;
}

.el-dialog__body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 6px;
}

.el-dialog__body::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #c1c1c1 0%, #a8a8a8 100%);
    border-radius: 6px;
    border: 1px solid #999;
}

.el-dialog__body::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #a8a8a8 0%, #909090 100%);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .el-dialog {
        margin: 20px;
        width: calc(100% - 40px) !important;
    }

    .el-dialog__title {
        font-size: 13px;
    }

    .form-body {
        padding: 15px;
    }
}

/* Badge de construcción para módulos */
.badge-warning {
    background-color: #f39c12 !important;
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.d-flex {
    display: flex;
}

.align-items-center {
    align-items: center;
}

.ml-2 {
    margin-left: 8px;
}
</style>

<script>

    export default {
        props: ['showDialog', 'recordId'],
        data() {
            return {
                headers: headers_token,
                loading_submit: false,
                loading_search: false,
                titleDialog: null,
                button_text:null,
                resource: 'co-companies',
                error: {},
                errors: {},
                form: {},
                url_base: null,
                departments:[],
                municipalities:[],
                all_municipalities:[],
                type_document_identifications: [],
                type_organizations: [],
                modules: [],
                type_regimes: [],
                toggle: false,
                type_liabilities: [],
            }
        },
        async created() {
            await this.$http.get(`/${this.resource}/tables`)
                .then(response => {
                    this.modules = response.data.modules
                    this.departments = response.data.departments
                    this.all_municipalities = response.data.municipalities
                    this.type_document_identifications = response.data.type_document_identifications
                    this.type_organizations = response.data.type_organizations
                    this.type_regimes = response.data.type_regimes
                    this.url_base = response.data.url_base
                    this.type_liabilities = response.data.type_liabilities
                })

            await this.initForm()
        },

        methods: {
            copyToClipboard() {
                const el = document.createElement('textarea');
                el.value = this.form.api_token;
                el.setAttribute('readonly', '');
                el.style.position = 'absolute';
                el.style.left = '-9999px';
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
                console.log('Texto copiado al portapapeles:', this.form.api_token);
            },

            cascade() {
                this.form.municipality_id = null
                this.municipalities = this.all_municipalities.filter(
                    x => x.department_id == this.form.department_id
                )
            },

            filterMunicipalities() {
                this.municipalities = this.all_municipalities.filter(
                    x => x.department_id == this.form.department_id
                )
            },
            initForm() {

                this.errors = {}
                this.form = {
                    id: null,
                    is_update: false,
                    identification_number: null,
                    name: null,
                    subdomain: null,
                    email: null,
                    password: null,
                    password_confirmation: null,
                    api_token: null,
                    department_id: null,
                    municipality_id: null,
                    type_organization_id: null,
                    type_regime_id: null,
                    merchant_registration: null,
                    address: null,
                    phone: null,
                    economic_activity_code: null,
                    ica_rate: null,
                    limit_documents: null,
                    limit_users: 1,
                    type_document_identification_id: null,
                    dv: 1,
                    language_id: 79,
                    country_id: 46,
                    type_liability_id: 117,
                    id_service: null,
                    modules: []
                }

                this.modules.forEach(module => {
                    this.form.modules.push({
                        id: module.id,
                        description: module.description,
                        checked: true
                    })
                })
            },
            create() {
                this.titleDialog = (this.recordId)? 'Editar compañia':'Nuevo compañia'
                if (this.recordId) {
                    this.$http.get(`/${this.resource}/record/${this.recordId}`)
                        .then(response => {
                                this.form = response.data.data
                                this.form.is_update = true
                                this.filterMunicipalities()
                            })
                }
            },
            hasModules(){

                let modules_checked = 0
                this.form.modules.forEach(module =>{
                    if(module.checked){
                        modules_checked++
                    }
                })

                return (modules_checked > 0) ? true:false

            },
            async submit() {
                // console.log(this.form)
                if(!this.form.is_update){
                    let has_modules = await this.hasModules()
                    if(!has_modules)
                        return this.$message.error('Debe seleccionar al menos un módulo')
                }

                this.button_text = (this.form.is_update) ? 'Actualizando compañia...':'Creando base de datos...'
                this.loading_submit = true
                console.log(`${this.resource}${(this.form.is_update ? '/update' : '')}`)
                await this.$http.post(`${this.resource}${(this.form.is_update ? '/update' : '')}`, this.form)
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
                        }else if(error.response.status === 500){
                            this.$message.error(error.response.data.message);
                        }
                         else {
                            console.log(error.response)
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
            errorUpload(r)
            {
                console.log(r)
            },
            successUpload(response)
            {
                if (response.success) {
                    this.form.certificate = response.data.filename
                   // this.form.image_url = response.data.temp_image
                    this.form.temp_path = response.data.temp_path
                } else {
                    this.$message.error(response.message)
                }
            }
        },
    }
</script>

<style>
/* Windows 10/11 Style Modal usando clase personalizada */

/* Backdrop del modal con blur de Windows */
.windows-modal .el-dialog__wrapper,
.el-dialog__wrapper:has(.windows-modal) {
    -webkit-backdrop-filter: blur(8px) saturate(180%);
    backdrop-filter: blur(8px) saturate(180%);
    background-color: rgba(32, 32, 32, 0.5) !important;
}

/* Contenedor principal del modal - Ventana de Windows auténtica */
.windows-modal {
    background: #ffffff !important;
    border: 2px solid #0078d4 !important;
    border-radius: 0 !important;
    box-shadow:
        0 16px 32px rgba(0, 0, 0, 0.35),
        0 4px 8px rgba(0, 0, 0, 0.15) !important;
    overflow: hidden !important;
    position: relative !important;
    min-height: 70vh !important;
    max-height: 85vh !important;
}

/* Borde exterior más definido estilo Windows */
.windows-modal::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(145deg, #f0f0f0 0%, #d0d0d0 100%);
    border-radius: 0;
    z-index: -1;
    border: 1px solid #999;
}

/* Header estilo barra de título de Windows 10/11 */
.windows-modal .el-dialog__header {
    background: linear-gradient(180deg, #0078d4 0%, #106ebe 100%) !important;
    border-bottom: 1px solid #005a9e !important;
    padding: 0 !important;
    position: relative !important;
    cursor: move !important;
    height: 32px !important;
    display: flex !important;
    align-items: center !important;
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
    user-select: none !important;
}

/* Borde superior destacado para simular la barra de título activa */
.windows-modal .el-dialog__header::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: rgba(255, 255, 255, 0.3);
    z-index: 1;
}

/* Título del modal estilo Windows con icono */
.windows-modal .el-dialog__title {
    color: #ffffff !important;
    font-size: 13px !important;
    font-weight: 400 !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    flex: 1 !important;
    padding-left: 12px !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    line-height: 32px !important;
    margin: 0 !important;
    display: flex !important;
    align-items: center !important;
}

/* Icono de aplicación más realista */
.windows-modal .el-dialog__title::before {
    content: '';
    width: 16px;
    height: 16px;
    margin-right: 8px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 2px;
    display: inline-block;
    vertical-align: middle;
    position: relative;
}

/* Simulamos un icono de documento/app */
.windows-modal .el-dialog__title::before::after {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    right: 3px;
    bottom: 3px;
    background: #0078d4;
    border-radius: 1px;
}

/* Contenedor del botón de cerrar estilo Windows */
.windows-modal .el-dialog__headerbtn {
    position: absolute !important;
    top: 0 !important;
    right: 0 !important;
    width: 46px !important;
    height: 32px !important;
    padding: 0 !important;
    margin: 0 !important;
    background: transparent !important;
    border: none !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: background-color 0.15s ease !important;
    z-index: 10 !important;
}

/* Hover effect con fondo rojo de Windows */
.windows-modal .el-dialog__headerbtn:hover {
    background-color: #e81123 !important;
}

.windows-modal .el-dialog__headerbtn:active {
    background-color: #c50e1f !important;
}

/* Botón de cerrar X estilo Windows con fondo rojo */
.windows-modal .el-dialog__close {
    color: rgba(255, 255, 255, 0.9) !important;
    font-size: 16px !important;
    font-weight: 400 !important;
    background: transparent !important;
    border: none !important;
    width: 46px !important;
    height: 32px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: all 0.15s ease !important;
    margin: 0 !important;
    padding: 0 !important;
    cursor: pointer !important;
    position: relative !important;
}

/* Icono X de Windows más auténtico */
.windows-modal .el-dialog__close::before {
    content: '✕';
    position: absolute;
    font-size: 14px;
    line-height: 1;
}

/* Estado hover con fondo rojo y texto blanco */
.windows-modal .el-dialog__close:hover {
    color: #ffffff !important;
    background-color: #e81123 !important;
}

.windows-modal .el-dialog__close:active {
    background-color: #c50e1f !important;
    transform: none !important;
}

/* Ocultar cualquier icono por defecto de Element UI */
.windows-modal .el-dialog__close i,
.windows-modal .el-dialog__close span {
    display: none !important;
}

/* Focus state */
.windows-modal .el-dialog__close:focus {
    outline: 1px solid rgba(255, 255, 255, 0.6) !important;
    outline-offset: -2px !important;
}

/* Cuerpo del modal estilo Windows */
.windows-modal .el-dialog__body {
    background: #ffffff !important;
    padding: 24px !important;
    color: #1a1a1a !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    font-size: 14px !important;
    line-height: 1.4 !important;
    min-height: calc(70vh - 80px) !important;
    max-height: calc(85vh - 80px) !important;
    overflow-y: auto !important;
    border: none !important;
}

/* Estilos para formularios dentro del modal */
.windows-modal .form-body {
    background: transparent !important;
    border: none !important;
    padding: 0 !important;
    box-shadow: none !important;
}

.windows-modal .form-body h4 {
    color: #1a1a1a !important;
    font-weight: 600 !important;
    margin-bottom: 16px !important;
    padding-bottom: 8px !important;
    border-bottom: 1px solid #e1e1e1 !important;
    font-size: 16px !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
}

.windows-modal .control-label {
    color: #323130 !important;
    font-weight: 400 !important;
    font-size: 14px !important;
    margin-bottom: 4px !important;
    display: block !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
}

/* Botones estilo Windows Fluent Design */
.windows-modal .el-button {
    border-radius: 2px !important;
    font-weight: 400 !important;
    transition: all 0.2s cubic-bezier(0.1, 0.9, 0.2, 1) !important;
    font-size: 14px !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    padding: 8px 16px !important;
    height: 36px !important;
    min-width: 80px !important;
}

.windows-modal .el-button--primary {
    background: #0078d4 !important;
    border-color: #0078d4 !important;
    color: #ffffff !important;
}

.windows-modal .el-button--primary:hover {
    background: #106ebe !important;
    border-color: #106ebe !important;
    box-shadow: 0 4px 8px rgba(0, 120, 212, 0.3) !important;
    transform: translateY(-1px) !important;
}

.windows-modal .el-button:not(.el-button--primary):not(.el-button--danger):not(.el-button--success):not(.el-button--warning) {
    background: #ffffff !important;
    border-color: #8a8886 !important;
    color: #323130 !important;
}

.windows-modal .el-button:not(.el-button--primary):not(.el-button--danger):not(.el-button--success):not(.el-button--warning):hover {
    background: #f3f2f1 !important;
    border-color: #323130 !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
    transform: translateY(-1px) !important;
}

/* Inputs estilo Windows */
.windows-modal .el-input__inner {
    border: 1px solid #8a8886 !important;
    border-radius: 2px !important;
    background: #ffffff !important;
    transition: all 0.2s cubic-bezier(0.1, 0.9, 0.2, 1) !important;
    font-size: 14px !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    padding: 8px 12px !important;
    height: 36px !important;
    color: #323130 !important;
}

.windows-modal .el-input__inner:hover {
    border-color: #323130 !important;
}

.windows-modal .el-input__inner:focus {
    border-color: #0078d4 !important;
    box-shadow: inset 0 0 0 1px #0078d4 !important;
    outline: none !important;
}

/* Textarea estilo Windows */
.windows-modal .el-textarea__inner {
    border: 1px solid #8a8886 !important;
    border-radius: 2px !important;
    background: #ffffff !important;
    font-size: 14px !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    color: #323130 !important;
    resize: vertical !important;
}

.windows-modal .el-textarea__inner:hover {
    border-color: #323130 !important;
}

.windows-modal .el-textarea__inner:focus {
    border-color: #0078d4 !important;
    box-shadow: inset 0 0 0 1px #0078d4 !important;
    outline: none !important;
}

/* Select estilo Windows */
.windows-modal .el-select .el-input__inner {
    background: #ffffff !important;
    cursor: pointer !important;
}

.windows-modal .el-select .el-input__inner:hover {
    border-color: #323130 !important;
}

/* Checkboxes estilo Windows */
.windows-modal .el-checkbox .el-checkbox__label {
    color: #323130 !important;
    font-size: 14px !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
}

.windows-modal .el-checkbox .el-checkbox__inner {
    border-radius: 2px !important;
    border: 1px solid #8a8886 !important;
    background: #ffffff !important;
    width: 16px !important;
    height: 16px !important;
}

.windows-modal .el-checkbox .el-checkbox__inner:hover {
    border-color: #323130 !important;
}

.windows-modal .el-checkbox.is-checked .el-checkbox__inner {
    background-color: #0078d4 !important;
    border-color: #0078d4 !important;
}

/* Responsivo */
@media (max-width: 768px) {
    .windows-modal {
        margin: 16px !important;
        width: calc(100% - 32px) !important;
    }

    .windows-modal .el-dialog__title {
        font-size: 12px !important;
        padding-left: 8px !important;
    }

    .windows-modal .el-dialog__body {
        padding: 16px !important;
    }
}
</style>

/* Borde exterior más definido estilo Windows */
::v-deep .el-dialog::before {
    content: '';
    position: absolute;
    top: -1px;
    left: -1px;
    right: -1px;
    bottom: -1px;
    background: linear-gradient(145deg, #f0f0f0 0%, #d0d0d0 100%);
    border-radius: 0;
    z-index: -1;
    border: 1px solid #999;
}

/* Header estilo barra de título de Windows 10/11 */
::v-deep .el-dialog__header {
    background: linear-gradient(180deg, #ffffff 0%, #f0f0f0 100%);
    border-bottom: 1px solid #d0d0d0;
    padding: 0;
    position: relative;
    cursor: move;
    height: 32px !important;
    display: flex;
    align-items: center;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

/* Borde superior destacado para simular la barra de título activa */
::v-deep .el-dialog__header::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, #0078d4 0%, #106ebe 100%);
    z-index: 1;
}

/* Botones de control simulados (minimizar, maximizar) */
::v-deep .el-dialog__header::before {
    content: '⎯ ⧄';
    position: absolute;
    top: 0;
    right: 46px;
    height: 32px;
    width: 92px;
    color: #666;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: space-around;
    background: transparent;
    z-index: 4;
    pointer-events: none;
    letter-spacing: 20px;
    padding-left: 10px;
}

/* Título del modal estilo Windows con icono */
::v-deep .el-dialog__title {
    color: #1a1a1a !important;
    font-size: 13px !important;
    font-weight: 400 !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    flex: 1;
    padding-left: 12px !important;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 32px !important;
    margin: 0 !important;
    display: flex;
    align-items: center;
}

/* Icono de aplicación más realista */
::v-deep .el-dialog__title::before {
    content: '';
    width: 16px;
    height: 16px;
    margin-right: 8px;
    background: linear-gradient(135deg, #0078d4 0%, #106ebe 100%);
    border-radius: 2px;
    display: inline-block;
    vertical-align: middle;
    position: relative;
}

/* Simulamos un icono de documento/app */
::v-deep .el-dialog__title::before::after {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    right: 3px;
    bottom: 3px;
    background: white;
    border-radius: 1px;
}

/* Contenedor del botón de cerrar estilo Windows */
::v-deep .el-dialog__headerbtn {
    position: absolute !important;
    top: 0 !important;
    right: 0 !important;
    width: 46px !important;
    height: 32px !important;
    padding: 0 !important;
    margin: 0 !important;
    background: transparent !important;
    border: none !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: background-color 0.15s ease !important;
    z-index: 10 !important;
}

/* Hover effect con fondo rojo de Windows */
::v-deep .el-dialog__headerbtn:hover {
    background-color: #e81123 !important;
}

::v-deep .el-dialog__headerbtn:active {
    background-color: #c50e1f !important;
}

/* Botón de cerrar X estilo Windows con fondo rojo */
::v-deep .el-dialog__close {
    color: #1a1a1a !important;
    font-size: 10px !important;
    font-weight: 400 !important;
    background: transparent !important;
    border: none !important;
    width: 46px !important;
    height: 32px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: all 0.15s ease !important;
    margin: 0 !important;
    padding: 0 !important;
    cursor: pointer !important;
    position: relative !important;
}

/* Icono X de Windows más auténtico */
::v-deep .el-dialog__close::before {
    content: '';
    position: absolute;
    width: 12px;
    height: 12px;
    background-image:
        linear-gradient(45deg, transparent 35%, currentColor 35%, currentColor 65%, transparent 65%),
        linear-gradient(-45deg, transparent 35%, currentColor 35%, currentColor 65%, transparent 65%);
    background-size: 12px 2px, 12px 2px;
    background-position: center, center;
    background-repeat: no-repeat;
}

/* Estado hover con fondo rojo y texto blanco */
::v-deep .el-dialog__close:hover {
    color: #ffffff !important;
    background-color: #e81123 !important;
}

::v-deep .el-dialog__close:active {
    background-color: #c50e1f !important;
    transform: none !important;
}

/* Ocultar cualquier icono por defecto de Element UI */
::v-deep .el-dialog__close i,
::v-deep .el-dialog__close span {
    display: none !important;
}

/* Focus state */
::v-deep .el-dialog__close:focus {
    outline: 1px solid rgba(255, 255, 255, 0.6) !important;
    outline-offset: -2px !important;
}

/* Cuerpo del modal estilo Windows */
::v-deep .el-dialog__body {
    background: #ffffff !important;
    padding: 24px !important;
    color: #1a1a1a !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    font-size: 14px !important;
    line-height: 1.4 !important;
    max-height: calc(80vh - 120px) !important;
    overflow-y: auto !important;
    border: none !important;
}

/* Estilos para formularios dentro del modal */
.form-body {
    background: transparent !important;
    border: none !important;
    padding: 0 !important;
    box-shadow: none !important;
}

.form-body h4 {
    color: #1a1a1a !important;
    font-weight: 600 !important;
    margin-bottom: 16px !important;
    padding-bottom: 8px !important;
    border-bottom: 1px solid #e1e1e1 !important;
    font-size: 16px !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
}

.control-label {
    color: #323130 !important;
    font-weight: 400 !important;
    font-size: 14px !important;
    margin-bottom: 4px !important;
    display: block !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
}

/* Botones estilo Windows Fluent Design */
::v-deep .el-button {
    border-radius: 2px !important;
    font-weight: 400 !important;
    transition: all 0.2s cubic-bezier(0.1, 0.9, 0.2, 1) !important;
    font-size: 14px !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    padding: 8px 16px !important;
    height: 36px !important;
    min-width: 80px !important;
}

::v-deep .el-button--primary {
    background: #0078d4 !important;
    border-color: #0078d4 !important;
    color: #ffffff !important;
}

::v-deep .el-button--primary:hover {
    background: #106ebe !important;
    border-color: #106ebe !important;
    box-shadow: 0 4px 8px rgba(0, 120, 212, 0.3) !important;
    transform: translateY(-1px) !important;
}

::v-deep .el-button:not(.el-button--primary):not(.el-button--danger):not(.el-button--success):not(.el-button--warning) {
    background: #ffffff !important;
    border-color: #8a8886 !important;
    color: #323130 !important;
}

::v-deep .el-button:not(.el-button--primary):not(.el-button--danger):not(.el-button--success):not(.el-button--warning):hover {
    background: #f3f2f1 !important;
    border-color: #323130 !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
    transform: translateY(-1px) !important;
}

/* Inputs estilo Windows */
::v-deep .el-input__inner {
    border: 1px solid #8a8886 !important;
    border-radius: 2px !important;
    background: #ffffff !important;
    transition: all 0.2s cubic-bezier(0.1, 0.9, 0.2, 1) !important;
    font-size: 14px !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    padding: 8px 12px !important;
    height: 36px !important;
    color: #323130 !important;
}

::v-deep .el-input__inner:hover {
    border-color: #323130 !important;
}

::v-deep .el-input__inner:focus {
    border-color: #0078d4 !important;
    box-shadow: inset 0 0 0 1px #0078d4 !important;
    outline: none !important;
}

/* Textarea estilo Windows */
::v-deep .el-textarea__inner {
    border: 1px solid #8a8886 !important;
    border-radius: 2px !important;
    background: #ffffff !important;
    font-size: 14px !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    color: #323130 !important;
    resize: vertical !important;
}

::v-deep .el-textarea__inner:hover {
    border-color: #323130 !important;
}

::v-deep .el-textarea__inner:focus {
    border-color: #0078d4 !important;
    box-shadow: inset 0 0 0 1px #0078d4 !important;
    outline: none !important;
}

/* Select estilo Windows */
::v-deep .el-select .el-input__inner {
    background: #ffffff !important;
    cursor: pointer !important;
}

::v-deep .el-select .el-input__inner:hover {
    border-color: #323130 !important;
}

/* Checkboxes estilo Windows */
::v-deep .el-checkbox .el-checkbox__label {
    color: #323130 !important;
    font-size: 14px !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
}

::v-deep .el-checkbox .el-checkbox__inner {
    border-radius: 2px !important;
    border: 1px solid #8a8886 !important;
    background: #ffffff !important;
    width: 16px !important;
    height: 16px !important;
}

::v-deep .el-checkbox .el-checkbox__inner:hover {
    border-color: #323130 !important;
}

::v-deep .el-checkbox.is-checked .el-checkbox__inner {
    background-color: #0078d4 !important;
    border-color: #0078d4 !important;
}

/* Responsivo */
@media (max-width: 768px) {
    ::v-deep .el-dialog {
        margin: 16px !important;
        width: calc(100% - 32px) !important;
    }

    ::v-deep .el-dialog__title {
        font-size: 12px !important;
    }

    /* Forzar tamaño adaptativo para modal de empresa */
    ::v-deep .windows-modal-company {
        width: 70% !important;
        min-width: 600px !important;
        max-width: 900px !important;
    }

    /* Responsive para pantallas pequeñas */
    @media (max-width: 768px) {
        ::v-deep .windows-modal-company {
            width: 95% !important;
            min-width: 300px !important;
        }
    }
