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
        custom-class="users-modal-modern">
        <form autocomplete="off" @submit.prevent="submit">
            <div class="form-body">
                <!-- Información Básica -->
                <div class="section-card">
                    <h4 class="section-title"><i class="fa fa-user"></i> Información Básica del Usuario</h4>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group" :class="{'has-danger': errors.name}">
                                <label class="control-label"><i class="fa fa-id-badge text-primary"></i> Nombre</label>
                                <el-input v-model="form.name" placeholder="Nombre completo"></el-input>
                                <small class="form-control-feedback" v-if="errors.name" v-text="errors.name[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group" :class="{'has-danger': errors.email}">
                                <label class="control-label"><i class="fa fa-envelope text-info"></i> Correo Electrónico</label>
                                <el-input v-model="form.email" :disabled="form.id!=null" placeholder="correo@ejemplo.com">
                                    <template slot="prepend">@</template>
                                </el-input>
                                <small class="form-control-feedback" v-if="errors.email" v-text="errors.email[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group" :class="{'has-danger': errors.establishment_id}">
                                <label class="control-label"><i class="fa fa-building text-success"></i> Establecimiento</label>
                                <el-select v-model="form.establishment_id" filterable placeholder="Seleccione">
                                    <el-option v-for="option in establishments" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.establishment_id" v-text="errors.establishment_id[0]"></small>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- API Token y Seguridad -->
                <div class="section-card" v-show="form.id">
                    <h4 class="section-title"><i class="fa fa-key"></i> Token de API</h4>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group" :class="{'has-danger': errors.api_token}">
                                <label class="control-label"><i class="fa fa-shield text-warning"></i> Api Token</label>
                                <el-input v-model="form.api_token" :readonly="form.id!=null">
                                    <template slot="prepend"><i class="fa fa-lock"></i></template>
                                </el-input>
                                <div class="token-hint"><i class="fa fa-info-circle"></i> Este token es único y de solo lectura</div>
                                <small class="form-control-feedback" v-if="errors.api_token" v-text="errors.api_token[0]"></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contraseña -->
                <div class="section-card">
                    <h4 class="section-title"><i class="fa fa-lock"></i> Contraseña y Seguridad</h4>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group" :class="{'has-danger': errors.password}">
                                <label class="control-label"><i class="fa fa-key text-danger"></i> Contraseña</label>
                                <el-input v-model="form.password" type="password" placeholder="Nueva contraseña">
                                    <template slot="prepend"><i class="fa fa-lock"></i></template>
                                </el-input>
                                <small class="form-control-feedback" v-if="errors.password" v-text="errors.password[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group" :class="{'has-danger': errors.password_confirmation}">
                                <label class="control-label"><i class="fa fa-check-circle text-success"></i> Confirmar Contraseña</label>
                                <el-input v-model="form.password_confirmation" type="password" placeholder="Repetir contraseña">
                                    <template slot="prepend"><i class="fa fa-check"></i></template>
                                </el-input>
                                <small class="form-control-feedback" v-if="errors.password_confirmation" v-text="errors.password_confirmation[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-4" v-if="typeUser != 'integrator'">
                            <div class="form-group" :class="{'has-danger': errors.type}">
                                <label class="control-label"><i class="fa fa-user-tag text-primary"></i> Perfil</label>
                                <el-select v-model="form.type" :disabled="form.id===1" placeholder="Seleccione">
                                    <el-option v-for="option in types" :key="option.type" :value="option.type" :label="option.description"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.type" v-text="errors.type[0]"></small>
                            </div>
                        </div>
                    </div>
                    <div class="password-hint">
                        <i class="fa fa-lightbulb-o"></i>
                        <span>Las contraseñas deben coincidir y tener al menos 8 caracteres</span>
                    </div>
                </div>
                <!-- Resoluciones -->
                <div class="section-card" v-if="typeUser != 'integrator'">
                    <h4 class="section-title"><i class="fa fa-file-text"></i> Resoluciones</h4>
                    <div class="row">
                        <div class="col-md-3" v-if="form.modules.some(m => m.description === 'Ventas' && m.checked)">
                            <div class="form-group" :class="{'has-danger': errors.fe_resolution_id}">
                                <label class="control-label"><i class="fa fa-file-invoice text-primary"></i> Resolución FE</label>
                                <el-select v-model="form.fe_resolution_id" clearable placeholder="Seleccione">
                                    <el-option v-for="option in fe_resolutions" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.fe_resolution_id" v-text="errors.fe_resolution_id[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-3" v-if="form.modules.some(m => m.description === 'Ventas' && m.checked)">
                            <div class="form-group" :class="{'has-danger': errors.nc_resolution_id}">
                                <label class="control-label"><i class="fa fa-file-text-o text-info"></i> Resolución NC</label>
                                <el-select v-model="form.nc_resolution_id" clearable placeholder="Seleccione">
                                    <el-option v-for="option in nc_resolutions" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.nc_resolution_id" v-text="errors.nc_resolution_id[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-3" v-if="form.modules.some(m => m.description === 'Ventas' && m.checked)">
                            <div class="form-group" :class="{'has-danger': errors.nd_resolution_id}">
                                <label class="control-label"><i class="fa fa-sticky-note text-warning"></i> Resolución ND</label>
                                <el-select v-model="form.nd_resolution_id" clearable placeholder="Seleccione">
                                    <el-option v-for="option in nd_resolutions" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.nd_resolution_id" v-text="errors.nd_resolution_id[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-3" v-if="form.modules.some(m => m.description === 'Nóminas' && m.checked)">
                            <div class="form-group" :class="{'has-danger': errors.ni_resolution_id}">
                                <label class="control-label"><i class="fa fa-money text-success"></i> Resolución Nómina</label>
                                <el-select v-model="form.ni_resolution_id" clearable placeholder="Seleccione">
                                    <el-option v-for="option in ni_resolutions" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.ni_resolution_id" v-text="errors.ni_resolution_id[0]"></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group" :class="{'has-danger': errors.prefix}">
                                <label class="control-label"><i class="fa fa-hashtag text-danger"></i> Prefijo</label>
                                <el-select v-model="form.prefix" filterable placeholder="Seleccione">
                                    <el-option v-for="option in prefixs" :key="option.prefix" :value="option.prefix" :label="option.prefix"></el-option>
                                </el-select>
                                <small class="form-control-feedback" v-if="errors.prefix" v-text="errors.prefix[0]"></small>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Módulos -->
                <div class="section-card" v-if="typeUser != 'integrator'">
                    <h4 class="section-title"><i class="fa fa-cubes"></i> Módulos del Sistema</h4>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="modules-grid">
                                    <div class="module-item" v-for="module in form.modules" :key="module.id">
                                        <el-checkbox v-model="module.checked" :disabled="form.locked" @change="changeModule(module.id, module.checked)">
                                            <span class="module-label">
                                                <i class="fa fa-cube"></i> {{ module.description }}
                                            </span>
                                        </el-checkbox>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Niveles de Acceso -->
                <div class="section-card levels-card" v-if="typeUser != 'integrator' && show_levels">
                    <h4 class="section-title"><i class="fa fa-unlock-alt"></i> Nivel de Acceso del Módulo Ventas</h4>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="modules-grid">
                                    <div class="module-item" v-for="level in form.levels" :key="level.id">
                                        <el-checkbox v-model="level.checked" :disabled="form.locked">
                                            <span class="module-label">
                                                <i class="fa fa-shield"></i> {{ level.description }}
                                            </span>
                                        </el-checkbox>
                                    </div>
                                </div>
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
    import {EventBus} from '../../../helpers/bus'

    export default {
        props: ['showDialog', 'recordId','typeUser'],
        data() {
            return {
                loading_submit: false,
                titleDialog: null,
                resource: 'users',
                errors: {},
                form: {
                    modules: [],
                },
                modules: [],
                fe_resolutions: [],
                nc_resolutions: [],
                nd_resolutions: [],
                ni_resolutions: [],
                establishments: [],
                prefixs : [],
                types: [],
                show_levels:false
            }
        },

        async created() {
            await this.$http.get(`/${this.resource}/tables`)
                .then(response => {
                    this.modules = response.data.modules
                    this.establishments = response.data.establishments
                    this.types = response.data.types
                    this.prefixs = response.data.prefixs
                    this.fe_resolutions = response.data.fe_resolutions
                    this.nc_resolutions = response.data.nc_resolutions
                    this.nd_resolutions = response.data.nd_resolutions
                    this.ni_resolutions = response.data.ni_resolutions
                })
            await this.initForm()
        },

        methods: {
            initForm() {
                this.errors = {}
                this.form = {
                    id: null,
                    name: null,
                    email: null,
                    api_token: null,
                    establishment_id: null,
                    password: null,
                    password_confirmation: null,
                    locked:false,
                    type:null,
                    fe_resolution_id: null,
                    nc_resolution_id: null,
                    nd_resolution_id: null,
                    ni_resolution_id: null,
                    prefix: null,
                    modules: [],
                    levels: [],
                }

                this.modules.forEach(module => {
                    this.form.modules.push({
                        id: module.id,
                        description: module.description,
                        checked: false
                    })
                })
                this.show_levels = false
                // console.log(this.form.levels)
            },

            create() {
                this.titleDialog = (this.recordId) ? 'Editar Usuario' : 'Nuevo Usuario'
                if (this.recordId) {
                    this.$http.get(`/${this.resource}/record/${this.recordId}`)
                        .then(response => {
                            this.form = response.data.data
                            this.show_levels = (this.form.levels.length > 0) ? true : false
                        })
                }
            },

            async changeModule(module_id, checked){
                if(module_id == 1){
                    if(checked){
                        // console.log(mdl)
                        if(this.form.levels.length == 0 ){
                            let mdl = await _.find(this.modules, {'id':module_id})
                            mdl.levels.forEach(level => {
                                this.form.levels.push({
                                    id: level.id,
                                    level_id: level.id,
                                    module_id: level.module_id,
                                    description: level.description,
                                    checked: false
                                })
                            })
                            this.show_levels = true
                        }
                    }else{
                        this.form.levels = []
                        this.show_levels = false
                    }
                }
            },

            submit() {
                // console.log(this.form)
                this.loading_submit = true
                // Buscar la resolución fe seleccionada
                const selectedResolutionfe = this.fe_resolutions.find(
                    r => r.id === this.form.fe_resolution_id
                );
                // Validar si resolucion fe si está vencida
                if (selectedResolutionfe && selectedResolutionfe.vencida) {
                    this.$message.warning('No puede seleccionar una resolución FE vencida.');
                    this.loading_submit = false;
                    return;
                }
                // Buscar la resolución nc seleccionada
                const selectedResolutionnc = this.nc_resolutions.find(
                    r => r.id === this.form.nc_resolution_id
                );
                // Validar si resolucion nc si está vencida
                if (selectedResolutionnc && selectedResolutionnc.vencida) {
                    this.$message.warning('No puede seleccionar una resolución NC vencida.');
                    this.loading_submit = false;
                    return;
                }
                // Buscar la resolución nc seleccionada
                const selectedResolutionnd = this.nd_resolutions.find(
                    r => r.id === this.form.nd_resolution_id
                );
                // Validar si resolucion nc si está vencida
                if (selectedResolutionnd && selectedResolutionnd.vencida) {
                    this.$message.warning('No puede seleccionar una resolución ND vencida.');
                    this.loading_submit = false;
                    return;
                }
                // Buscar la resolución ni seleccionada
                const selectedResolutionni = this.ni_resolutions.find(
                    r => r.id === this.form.ni_resolution_id
                );
                // Validar si resolucion ni si está vencida
                if (selectedResolutionni && selectedResolutionni.vencida) {
                    this.$message.warning('No puede seleccionar una resolución Nomina vencida.');
                    this.loading_submit = false;
                    return;
                }
                this.$http.post(`/${this.resource}`, this.form)
                    .then(response => {
                        if (response.data.success) {
                            this.form.password = null
                            this.form.password_confirmation = null
                            this.form.fe_resolution_id = null
                            this.form.nc_resolution_id = null
                            this.form.nd_resolution_id = null
                            this.form.ni_resolution_id = null
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
                            this.$message.error(error.response.data.message);
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
        }
    }
</script>

<style scoped>
/* ==============================================
   MODAL MODERNO DE USUARIOS
   Diseño profesional con cards y animaciones
   ============================================== */

/* Dialog personalizado */
::v-deep .users-modal-modern {
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

::v-deep .users-modal-modern .el-dialog__header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 20px 30px;
  margin: 0;
  border-bottom: none;
}

::v-deep .users-modal-modern .el-dialog__title {
  color: white;
  font-weight: 600;
  font-size: 20px;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

::v-deep .users-modal-modern .el-dialog__close {
  color: white !important;
  font-size: 20px;
  font-weight: bold;
  transition: transform 0.3s ease;
}

::v-deep .users-modal-modern .el-dialog__close:hover {
  transform: rotate(90deg);
}

::v-deep .users-modal-modern .el-dialog__body {
  padding: 30px;
  background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
  max-height: 75vh;
  overflow-y: auto;
}

/* Scrollbar personalizado */
::v-deep .users-modal-modern .el-dialog__body::-webkit-scrollbar {
  width: 8px;
}

::v-deep .users-modal-modern .el-dialog__body::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

::v-deep .users-modal-modern .el-dialog__body::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 10px;
}

::v-deep .users-modal-modern .el-dialog__body::-webkit-scrollbar-thumb:hover {
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

.levels-card {
  border-left: 4px solid #28a745;
  background: linear-gradient(135deg, #ffffff 0%, #f0fff4 100%);
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

::v-deep .el-input__inner[readonly] {
  background-color: #f8f9fa;
  cursor: not-allowed;
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

/* Hints especiales */
.token-hint {
  font-size: 12px;
  color: #6c757d;
  margin-top: 8px;
  display: flex;
  align-items: center;
  gap: 5px;
  font-style: italic;
  padding: 10px;
  background: #f8f9fa;
  border-radius: 4px;
  border-left: 3px solid #ffc107;
}

.password-hint {
  background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
  padding: 15px;
  border-radius: 6px;
  margin-top: 15px;
  display: flex;
  align-items: center;
  gap: 10px;
  border-left: 4px solid #667eea;
  font-size: 13px;
  color: #495057;
}

.password-hint i {
  font-size: 18px;
  color: #667eea;
}

/* Grid de módulos */
.modules-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 15px;
  padding: 10px;
  background: #f8f9fa;
  border-radius: 8px;
}

.module-item {
  background: white;
  padding: 12px 15px;
  border-radius: 6px;
  border: 2px solid #e9ecef;
  transition: all 0.3s ease;
}

.module-item:hover {
  border-color: #667eea;
  box-shadow: 0 3px 10px rgba(102, 126, 234, 0.1);
  transform: translateY(-2px);
}

.module-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #495057;
}

.module-label i {
  color: #667eea;
  font-size: 16px;
}

::v-deep .module-item .el-checkbox {
  width: 100%;
}

::v-deep .module-item .el-checkbox__label {
  width: 100%;
  font-weight: 500;
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
.has-danger .el-select .el-input__inner {
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
  ::v-deep .users-modal-modern .el-dialog {
    width: 95% !important;
  }
  
  .section-card {
    padding: 15px;
  }
  
  .section-title {
    font-size: 16px;
  }
  
  .modules-grid {
    grid-template-columns: 1fr;
  }
}
</style>
