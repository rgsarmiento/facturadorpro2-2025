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

        <div class="card mb-0 cuentas-contables-form">
            <div class="card-header bg-info">
                <h4 class="my-0"><i class="fas fa-book"></i> {{ isEdit ? 'Editar' : 'Nueva' }} Cuenta Contable</h4>
            </div>
            <div class="card-body">
                <form @submit.prevent="submit">
                    <!-- Sección 1: Información Básica -->
                    <div class="form-section">
                        <h5 class="section-header"><i class="fas fa-info-circle"></i> Información Básica</h5>
                        <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required">Código:</label>
                                <div class="position-relative">
                                    <input type="text"
                                           class="form-control"
                                           v-model="form.codigo"
                                           :class="{ 'is-invalid': errors.codigo }"
                                           @input="onCodigoInput"
                                           maxlength="20"
                                           :style="restriccionCodigo ? 'padding-left: ' + ((codigoPadre.length + 3) * 8 + 10) + 'px;' : ''"
                                           required>
                                    <div v-if="restriccionCodigo && codigoPadre"
                                         class="position-absolute"
                                         style="top: 50%; left: 10px; transform: translateY(-50%); font-weight: bold; color: #495057; pointer-events: none; z-index: 1;">
                                        {{ codigoPadre }} -
                                    </div>
                                </div>
                                <div v-if="errors.codigo" class="invalid-feedback">
                                    {{ errors.codigo[0] }}
                                </div>
                                <small class="form-text text-muted">
                                    <span v-if="restriccionCodigo">
                                        <strong>{{ codigoPadre }}</strong> + {{ digitosPermitidos }} dígito(s) adicional(es)
                                    </span>
                                    <span v-else>
                                        Código único identificador de la cuenta
                                    </span>
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
                        </div>
                    </div>
                    <!-- Fin Sección 1 -->

                    <!-- Sección 2: Clasificación -->
                    <div class="form-section">
                        <h5 class="section-header"><i class="fas fa-sitemap"></i> Clasificación y Jerarquía</h5>
                        <div class="row">
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
                                        :disabled="isEdit || restriccionCodigo"
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
                                <small v-if="isEdit || restriccionCodigo" class="form-text text-muted">
                                    La cuenta padre no puede modificarse en este contexto
                                </small>
                            </div>
                        </div>
                        </div>
                    </div>
                    <!-- Fin Sección 2 -->

                    <!-- Sección 3: Descripción y Configuraciones -->
                    <div class="form-section">
                        <h5 class="section-header"><i class="fas fa-cogs"></i> Descripción y Configuraciones</h5>
                        <div class="row">
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

                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox"
                                           class="form-check-input"
                                           v-model="form.requiere_tercero"
                                           id="requiere_tercero">
                                    <label class="form-check-label" for="requiere_tercero">
                                        Requiere Tercero
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Define si la cuenta requiere información de terceros (clientes, proveedores, etc.)
                                </small>
                            </div>
                        </div>
                        </div>
                    </div>
                    <!-- Fin Sección 3 -->

                    <!-- Sección 4: Información Contable Adicional -->
                    <div class="form-section">
                        <h5 class="section-header"><i class="fas fa-calculator"></i> Información Contable Adicional</h5>
                        <div class="row">
                        <!-- Código NIIF -->
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
                        </div>
                    </div>
                    <!-- Fin Sección 4 -->

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
                requiere_tercero: false,
                saldo_inicial: 0,
                codigo_niif: ''
            },
            errors: {},
            loading: false,
            tipos_cuenta: [],
            naturalezas: [],
            naturaleza_por_tipo: {},
            cuentasPadreOptions: [],
            // Propiedades para manejo de códigos jerárquicos
            codigoPadre: null,
            restriccionCodigo: false
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
        },
        digitosPermitidos() {
            const nivelHijo = this.form.nivel || (this.cuentaPadreData ? this.cuentaPadreData.nivel + 1 : 1)
            return (nivelHijo === 1 || nivelHijo === 2) ? 1 : 2
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
                // Error silencioso para producción
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
                // Error silencioso para producción
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

                    // Guardar datos de la cuenta padre
                    this.cuentaPadreData = cuentaPadre

                    // Auto-asignar nivel y tipo
                    this.form.nivel = cuentaPadre.nivel + 1
                    this.form.tipo_cuenta = cuentaPadre.tipo_cuenta

                    // Generar código base para subcuenta
                    if (!this.isEdit) {
                        await this.generarSiguienteCodigo(cuentaPadre.codigo)
                        this.codigoPadre = cuentaPadre.codigo
                        this.restriccionCodigo = true
                    }

                    // Auto-asignar naturaleza
                    this.onTipoCuentaChange()
                } catch (error) {
                    // Error silencioso para producción
                }
            } else {
                this.form.nivel = 1
                this.form.codigo = ''
                this.codigoPadre = null
                this.restriccionCodigo = false
            }
        },
        async generarSiguienteCodigo(codigoPadre) {
            try {
                // Determinar cuántos dígitos agregar según el nivel del hijo
                // Niveles 1 y 2: agregar 1 dígito
                // Niveles 3+: agregar 2 dígitos
                const nivelHijo = this.form.nivel || (this.cuentaPadreData ? this.cuentaPadreData.nivel + 1 : 1)
                const digitosAAgregar = (nivelHijo === 1 || nivelHijo === 2) ? 1 : 2
                const maxNumero = digitosAAgregar === 1 ? 9 : 99

                // Buscar todas las cuentas hijas de esta cuenta padre
                const response = await axios.get('/contabilidad/cuentas-contables/records')
                const todasLasCuentas = response.data.data || []

                // Filtrar solo las cuentas que empiecen con el código padre y tengan exactamente los dígitos esperados
                const cuentasHijas = todasLasCuentas.filter(cuenta =>
                    cuenta.codigo.startsWith(codigoPadre) &&
                    cuenta.codigo.length === codigoPadre.length + digitosAAgregar
                )

                // Extraer los sufijos numéricos
                const sufijos = cuentasHijas
                    .map(cuenta => parseInt(cuenta.codigo.substring(codigoPadre.length)))
                    .filter(num => !isNaN(num) && num >= 1 && num <= maxNumero)
                    .sort((a, b) => a - b)

                // Encontrar el siguiente número disponible
                let siguienteNumero = 1
                for (let i = 0; i < sufijos.length; i++) {
                    if (sufijos[i] === siguienteNumero) {
                        siguienteNumero++
                    } else {
                        break
                    }
                }

                // Asegurar que no exceda el máximo
                if (siguienteNumero > maxNumero) {
                    siguienteNumero = maxNumero
                }

                // Formatear según el número de dígitos requeridos
                const sufijo = digitosAAgregar === 1
                    ? siguienteNumero.toString()
                    : siguienteNumero.toString().padStart(2, '0')

                // Generar el código completo sin separador para la base de datos
                const codigoCompleto = codigoPadre + sufijo
                // Mostrar con separador visual
                this.form.codigo = codigoPadre + ' - ' + sufijo

            } catch (error) {
                // Error silencioso para producción - fallback
                const nivelHijo = this.form.nivel || (this.cuentaPadreData ? this.cuentaPadreData.nivel + 1 : 1)
                const sufijo = (nivelHijo === 1 || nivelHijo === 2) ? '1' : '01'
                this.form.codigo = codigoPadre + ' - ' + sufijo
            }
        },
        onCodigoInput() {
            // Si hay restricción de código (es subcuenta), validar el formato
            if (this.restriccionCodigo && this.codigoPadre) {
                const codigoIngresado = this.form.codigo
                const codigoPadreConSeparador = this.codigoPadre + ' - '

                // Determinar cuántos dígitos se pueden agregar según el nivel del hijo
                const nivelHijo = this.form.nivel || (this.cuentaPadreData ? this.cuentaPadreData.nivel + 1 : 1)
                const maxDigitos = (nivelHijo === 1 || nivelHijo === 2) ? 1 : 2

                // Verificar que empiece con el código padre + separador
                if (!codigoIngresado.startsWith(codigoPadreConSeparador)) {
                    this.form.codigo = codigoPadreConSeparador
                } else {
                    // Extraer solo la parte después del separador
                    const suffix = codigoIngresado.substring(codigoPadreConSeparador.length)
                    const digitsOnly = suffix.replace(/\D/g, '') // Solo dígitos

                    if (digitsOnly.length <= maxDigitos) {
                        this.form.codigo = codigoPadreConSeparador + digitsOnly
                    } else {
                        // Limitar al número máximo de dígitos
                        this.form.codigo = codigoPadreConSeparador + digitsOnly.substring(0, maxDigitos)
                    }
                }
            }
        },
        async submit() {
            this.loading = true
            this.errors = {}

            try {
                // Limpiar el separador del código antes de enviar
                const formData = { ...this.form }
                if (formData.codigo && formData.codigo.includes(' - ')) {
                    formData.codigo = formData.codigo.replace(' - ', '')
                }

                const url = this.isEdit
                    ? `/contabilidad/cuentas-contables/${this.cuenta.id}`
                    : '/contabilidad/cuentas-contables'

                const method = this.isEdit ? 'put' : 'post'

                const response = await axios[method](url, formData)

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

/* Estilos profesionales */
.cuentas-contables-form {
    background: #f8f9fa;
}

.form-section {
    background: white;
    padding: 25px;
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
    display: flex;
    align-items: center;
}

.section-header i {
    margin-right: 10px;
    color: #409EFF;
}

/* Colores diferentes para cada sección */
.form-section:nth-child(1) {
    border-left-color: #409EFF;
}

.form-section:nth-child(1) .section-header i {
    color: #409EFF;
}

.form-section:nth-child(2) {
    border-left-color: #67C23A;
}

.form-section:nth-child(2) .section-header i {
    color: #67C23A;
}

.form-section:nth-child(3) {
    border-left-color: #E6A23C;
}

.form-section:nth-child(3) .section-header i {
    color: #E6A23C;
}

.form-section:nth-child(4) {
    border-left-color: #F56C6C;
}

.form-section:nth-child(4) .section-header i {
    color: #F56C6C;
}
</style>
