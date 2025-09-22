<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a></h2>
            <ol class="breadcrumbs">
                <li><a href="/dashboard">Inicio</a></li>
                <li><a href="/contabilidad/cuentas-contables">Contabilidad</a></li>
                <li><a href="/contabilidad/cuentas-contables">Plan de Cuentas</a></li>
                <li class="active"><span>{{ isEdit ? 'Editar' : 'Nueva' }} Cuenta</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <button type="button" class="btn btn-custom btn-sm mt-2 mr-2" @click="goBack">
                    <i class="fa fa-arrow-left"></i> Volver
                </button>
            </div>
        </div>

        <div class="card mb-0">
            <div class="card-header bg-info">
                <h3 class="my-0">{{ isEdit ? 'Editar' : 'Nueva' }} Cuenta Contable</h3>
            </div>
            <div class="card-body">
                <form @submit.prevent="submit">
                    <div class="row">
                        <!-- Información Básica -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required">Código:</label>
                                <input type="text"
                                       class="form-control"
                                       v-model="form.codigo"
                                       :class="{ 'is-invalid': errors.codigo }"
                                       maxlength="20"
                                       required>
                                <div v-if="errors.codigo" class="invalid-feedback">
                                    {{ errors.codigo[0] }}
                                </div>
                                <small class="form-text text-muted">
                                    Código único identificador de la cuenta
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required">Nombre:</label>
                                <input type="text"
                                       class="form-control"
                                       v-model="form.nombre"
                                       :class="{ 'is-invalid': errors.nombre }"
                                       maxlength="255"
                                       required>
                                <div v-if="errors.nombre" class="invalid-feedback">
                                    {{ errors.nombre[0] }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required">Tipo de Cuenta:</label>
                                <select class="form-control"
                                        v-model="form.tipo_cuenta"
                                        :class="{ 'is-invalid': errors.tipo_cuenta }"
                                        @change="onTipoCuentaChange"
                                        required>
                                    <option value="">Seleccione...</option>
                                    <option v-for="tipo in tipos_cuenta" :key="tipo" :value="tipo">
                                        {{ tipo | capitalize }}
                                    </option>
                                </select>
                                <div v-if="errors.tipo_cuenta" class="invalid-feedback">
                                    {{ errors.tipo_cuenta[0] }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required">Naturaleza:</label>
                                <select class="form-control"
                                        v-model="form.naturaleza"
                                        :class="{ 'is-invalid': errors.naturaleza }"
                                        :disabled="naturalezaDisabled"
                                        required>
                                    <option value="">Seleccione...</option>
                                    <option v-for="naturaleza in naturalezas" :key="naturaleza" :value="naturaleza">
                                        {{ naturaleza | capitalize }}
                                    </option>
                                </select>
                                <div v-if="errors.naturaleza" class="invalid-feedback">
                                    {{ errors.naturaleza[0] }}
                                </div>
                                <small v-if="naturalezaDisabled" class="form-text text-muted">
                                    La naturaleza se asigna automáticamente según el tipo de cuenta
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required">Nivel:</label>
                                <input type="number"
                                       class="form-control"
                                       v-model.number="form.nivel"
                                       :class="{ 'is-invalid': errors.nivel }"
                                       min="1"
                                       max="10"
                                       :readonly="nivelReadonly"
                                       required>
                                <div v-if="errors.nivel" class="invalid-feedback">
                                    {{ errors.nivel[0] }}
                                </div>
                                <small v-if="nivelReadonly" class="form-text text-muted">
                                    El nivel se calcula automáticamente según la cuenta padre
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Cuenta Padre:</label>
                                <select class="form-control"
                                        v-model="form.cuenta_padre_id"
                                        :class="{ 'is-invalid': errors.cuenta_padre_id }"
                                        @change="onCuentaPadreChange">
                                    <option value="">Sin cuenta padre (Cuenta raíz)</option>
                                    <option v-for="cuenta in cuentasPadreOptions"
                                            :key="cuenta.id"
                                            :value="cuenta.id">
                                        {{ cuenta.codigo_nombre }}
                                    </option>
                                </select>
                                <div v-if="errors.cuenta_padre_id" class="invalid-feedback">
                                    {{ errors.cuenta_padre_id[0] }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Descripción:</label>
                                <textarea class="form-control"
                                          v-model="form.descripcion"
                                          :class="{ 'is-invalid': errors.descripcion }"
                                          rows="3"></textarea>
                                <div v-if="errors.descripcion" class="invalid-feedback">
                                    {{ errors.descripcion[0] }}
                                </div>
                            </div>
                        </div>

                        <!-- Configuraciones -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox"
                                           class="form-check-input"
                                           v-model="form.activa"
                                           id="activa">
                                    <label class="form-check-label" for="activa">
                                        Cuenta Activa
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox"
                                           class="form-check-input"
                                           v-model="form.permite_movimiento"
                                           id="permite_movimiento">
                                    <label class="form-check-label" for="permite_movimiento">
                                        Permite Movimientos Contables
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Solo las cuentas sin subcuentas pueden tener movimientos
                                </small>
                            </div>
                        </div>

                        <!-- Saldos -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Saldo Inicial:</label>
                                <input type="number"
                                       class="form-control"
                                       v-model.number="form.saldo_inicial"
                                       :class="{ 'is-invalid': errors.saldo_inicial }"
                                       step="0.01">
                                <div v-if="errors.saldo_inicial" class="invalid-feedback">
                                    {{ errors.saldo_inicial[0] }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Código NIIF (Opcional):</label>
                                <input type="text"
                                       class="form-control"
                                       v-model="form.codigo_niif"
                                       :class="{ 'is-invalid': errors.codigo_niif }"
                                       maxlength="20">
                                <div v-if="errors.codigo_niif" class="invalid-feedback">
                                    {{ errors.codigo_niif[0] }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit"
                                    class="btn btn-primary"
                                    :disabled="loading">
                                <i v-if="loading" class="fa fa-spinner fa-spin"></i>
                                {{ loading ? 'Guardando...' : (isEdit ? 'Actualizar' : 'Crear') }} Cuenta
                            </button>
                            <button type="button"
                                    class="btn btn-secondary ml-2"
                                    @click="goBack">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ['cuenta', 'typeUser'],
    data() {
        return {
            form: {
                codigo: '',
                nombre: '',
                tipo_cuenta: '',
                naturaleza: '',
                nivel: 1,
                cuenta_padre_id: '',
                descripcion: '',
                activa: true,
                permite_movimiento: true,
                saldo_inicial: 0,
                codigo_niif: ''
            },
            errors: {},
            loading: false,
            tipos_cuenta: [],
            naturalezas: [],
            naturaleza_por_tipo: {},
            cuentasPadreOptions: []
        }
    },
    computed: {
        isEdit() {
            return this.cuenta !== null
        },
        naturalezaDisabled() {
            return this.form.tipo_cuenta !== ''
        },
        nivelReadonly() {
            return this.form.cuenta_padre_id !== ''
        }
    },
    async created() {
        await this.loadTables()
        this.initializeForm()
        this.loadCuentasPadre()

        // Si viene un padre en la URL (para crear subcuenta)
        const urlParams = new URLSearchParams(window.location.search)
        const padreId = urlParams.get('padre_id')
        if (padreId) {
            this.form.cuenta_padre_id = padreId
            this.onCuentaPadreChange()
        }
    },
    methods: {
        async loadTables() {
            try {
                const response = await axios.get('/contabilidad/cuentas-contables/tables')
                const data = response.data.data
                this.tipos_cuenta = data.tipos_cuenta
                this.naturalezas = data.naturalezas
                this.naturaleza_por_tipo = data.naturaleza_por_tipo
            } catch (error) {
                console.error('Error loading tables:', error)
            }
        },
        initializeForm() {
            if (this.isEdit) {
                Object.keys(this.form).forEach(key => {
                    if (this.cuenta[key] !== undefined) {
                        this.form[key] = this.cuenta[key]
                    }
                })
            }
        },
        async loadCuentasPadre() {
            try {
                const params = {
                    nivel: this.form.nivel || 1,
                    tipo_cuenta: this.form.tipo_cuenta || ''
                }
                const response = await axios.get('/contabilidad/cuentas-contables/padres', { params })
                this.cuentasPadreOptions = response.data.data
            } catch (error) {
                console.error('Error loading parent accounts:', error)
            }
        },
        onTipoCuentaChange() {
            // Auto-asignar naturaleza según tipo de cuenta
            if (this.form.tipo_cuenta && this.naturaleza_por_tipo[this.form.tipo_cuenta]) {
                this.form.naturaleza = this.naturaleza_por_tipo[this.form.tipo_cuenta]
            }

            // Recargar opciones de cuentas padre
            this.loadCuentasPadre()
        },
        async onCuentaPadreChange() {
            if (this.form.cuenta_padre_id) {
                try {
                    const response = await axios.get(`/contabilidad/cuentas-contables/${this.form.cuenta_padre_id}`)
                    const cuentaPadre = response.data.data.cuenta

                    // Auto-asignar nivel y tipo
                    this.form.nivel = cuentaPadre.nivel + 1
                    this.form.tipo_cuenta = cuentaPadre.tipo_cuenta

                    // Auto-asignar naturaleza
                    this.onTipoCuentaChange()
                } catch (error) {
                    console.error('Error loading parent account:', error)
                }
            } else {
                this.form.nivel = 1
            }
        },
        async submit() {
            this.loading = true
            this.errors = {}

            try {
                const url = this.isEdit
                    ? `/contabilidad/cuentas-contables/${this.cuenta.id}`
                    : '/contabilidad/cuentas-contables'

                const method = this.isEdit ? 'put' : 'post'

                const response = await axios[method](url, this.form)

                this.$message.success(response.data.message)

                // Redirigir al listado
                setTimeout(() => {
                    window.location.href = '/contabilidad/cuentas-contables'
                }, 1000)

            } catch (error) {
                if (error.response && error.response.status === 422) {
                    this.errors = error.response.data.errors || {}
                }
                this.$message.error(error.response?.data?.message || 'Error al guardar la cuenta')
            } finally {
                this.loading = false
            }
        },
        goBack() {
            window.location.href = '/contabilidad/cuentas-contables'
        }
    },
    filters: {
        capitalize(value) {
            if (!value) return ''
            return value.charAt(0).toUpperCase() + value.slice(1)
        }
    }
}
</script>

<style scoped>
.required::after {
    content: ' *';
    color: red;
}

.form-control:disabled,
.form-control[readonly] {
    background-color: #e9ecef;
    opacity: 1;
}

.invalid-feedback {
    display: block;
}
</style>
