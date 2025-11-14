<template>
    <div class="card users-card-modern">
        <div class="card-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fa fa-user-circle"></i>
                </div>
                <div class="header-text">
                    <h3 class="header-title">Datos del Usuario</h3>
                    <small class="header-subtitle">Configuración de acceso al sistema</small>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form autocomplete="off" @submit.prevent="submit">
                <div class="form-body">
                    <!-- Información Personal -->
                    <div class="section-card">
                        <h4 class="section-title"><i class="fa fa-user"></i> Información Personal</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group" :class="{'has-danger': errors.name}">
                                    <label class="control-label"><i class="fa fa-id-badge text-primary"></i> Nombre</label>
                                    <el-input v-model="form.name" placeholder="Nombre completo del usuario"></el-input>
                                    <small class="form-control-feedback" v-if="errors.name" v-text="errors.name[0]"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" :class="{'has-danger': errors.email}">
                                    <label class="control-label"><i class="fa fa-envelope text-info"></i> Correo Electrónico</label>
                                    <el-input v-model="form.email" :disabled="true" placeholder="correo@ejemplo.com">
                                        <template slot="prepend">@</template>
                                    </el-input>
                                    <small class="form-control-feedback" v-if="errors.email" v-text="errors.email[0]"></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- API Token -->
                    <div class="section-card">
                        <h4 class="section-title"><i class="fa fa-key"></i> Token de API</h4>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group" :class="{'has-danger': errors.api_token}">
                                    <label class="control-label"><i class="fa fa-lock text-warning"></i> Api Token</label>
                                    <el-input v-model="form.api_token" readonly>
                                        <template slot="prepend"><i class="fa fa-shield"></i></template>
                                    </el-input>
                                    <div class="token-hint"><i class="fa fa-info-circle"></i> Este token es único y no puede modificarse manualmente</div>
                                    <small class="form-control-feedback" v-if="errors.api_token" v-text="errors.api_token[0]"></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seguridad -->
                    <div class="section-card">
                        <h4 class="section-title"><i class="fa fa-shield"></i> Seguridad y Contraseña</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group" :class="{'has-danger': errors.password}">
                                    <label class="control-label"><i class="fa fa-lock text-danger"></i> Contraseña</label>
                                    <el-input v-model="form.password" type="password" placeholder="Ingrese nueva contraseña">
                                        <template slot="prepend"><i class="fa fa-key"></i></template>
                                    </el-input>
                                    <small class="form-control-feedback" v-if="errors.password" v-text="errors.password[0]"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" :class="{'has-danger': errors.password_confirmation}">
                                    <label class="control-label"><i class="fa fa-check-circle text-success"></i> Confirmar Contraseña</label>
                                    <el-input v-model="form.password_confirmation" type="password" placeholder="Repita la contraseña">
                                        <template slot="prepend"><i class="fa fa-check"></i></template>
                                    </el-input>
                                    <small class="form-control-feedback" v-if="errors.password_confirmation" v-text="errors.password_confirmation[0]"></small>
                                </div>
                            </div>
                        </div>
                        <div class="password-hint">
                            <i class="fa fa-lightbulb-o"></i>
                            <span>Las contraseñas deben coincidir y tener al menos 8 caracteres</span>
                        </div>
                    </div>
                </div>
                <div class="form-actions text-right pt-2">
                    <el-button type="primary" native-type="submit" :loading="loading_submit">Guardar</el-button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>

    export default {
        data() {
            return {
                loading_submit: false,
                headers: null,
                resource: 'users',
                errors: {},
                form: {},
            }
        },
        created() {
            this.initForm()
            this.$http.get(`/${this.resource}/record/1`)
                .then(response => {
                    if (response.data !== '') {
                        this.form = response.data.data
                    }
                })
        },
        methods: {
            initForm() {
                this.errors = {}
                this.form = {
                    id: null,
                    name: null,
                    email: null,
                    api_token: null,
                    password: null,
                    password_confirmation: null
                }
            },
            submit() {
                this.loading_submit = true
                this.$http.post(`/${this.resource}`, this.form)
                    .then(response => {
                        if (response.data.success) {
                            this.form.password = null
                            this.form.password_confirmation = null
                            this.$message.success(response.data.message)
                        } else {
                            this.$message.error(response.data.message)
                        }
                    })
                    .catch(error => {
                        if (error.response.status === 422) {
                            this.errors = error.response.data.errors
                        } else {
                            console.log(error)
                        }
                    })
                    .then(() => {
                        this.loading_submit = false
                    })
            },
        }
    }
</script>

<style scoped>
/* ==============================================
   FORMULARIO MODERNO DE USUARIOS
   Diseño profesional con cards y animaciones
   ============================================== */

/* Card principal */
.users-card-modern {
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
  border: none;
  animation: slideIn 0.5s ease;
}

/* Header personalizado */
.users-card-modern .card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  padding: 25px 30px;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 20px;
}

.header-icon {
  width: 60px;
  height: 60px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30px;
  color: white;
  animation: pulse 2s ease-in-out infinite;
}

.header-text {
  flex: 1;
}

.header-title {
  color: white;
  font-size: 24px;
  font-weight: 600;
  margin: 0;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.header-subtitle {
  color: rgba(255, 255, 255, 0.9);
  font-size: 14px;
  margin-top: 5px;
  display: block;
}

/* Body del card */
.users-card-modern .card-body {
  padding: 30px;
  background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
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

/* Inputs */
::v-deep .el-input__inner {
  border-radius: 6px;
  transition: all 0.3s ease;
}

::v-deep .el-input__inner:focus {
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

/* Mensajes de validación */
.form-control-feedback {
  color: #dc3545;
  font-size: 12px;
  margin-top: 5px;
  display: block;
  animation: shake 0.3s ease;
}

.has-danger .el-input__inner {
  border-color: #dc3545 !important;
}

/* Botones del formulario */
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
  .users-card-modern .card-body {
    padding: 20px;
  }
  
  .section-card {
    padding: 15px;
  }
  
  .section-title {
    font-size: 16px;
  }
  
  .header-content {
    gap: 15px;
  }
  
  .header-icon {
    width: 50px;
    height: 50px;
    font-size: 24px;
  }
  
  .header-title {
    font-size: 20px;
  }
}
</style>
