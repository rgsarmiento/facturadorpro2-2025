@extends('tenant.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asientos-contables.css') }}">
<style>
    .content-header-left h1 {
        display: flex;
        align-items: center;
    }
    .construction-icon {
        margin-left: 10px;
        color: #ff9800;
        font-size: 20px;
    }
    .detalle-row {
        border-bottom: 1px solid #eee;
        padding: 10px 0;
    }
    .detalle-row:last-child {
        border-bottom: none;
    }
    .balance-info {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .balance-desbalanceado {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }
    .balance-balanceado {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    {{ isset($asiento) ? 'Editar' : 'Crear' }} Asiento Contable
                    <i class="fas fa-tools construction-icon" title="Módulo en construcción"></i>
                </h3>
                <div class="card-tools">
                    <a href="{{ route('tenant.asientos_contables.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div id="asiento-form-app">
                    <asiento-form-vue-component></asiento-form-vue-component>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts-after-vue')
<script>
    // Componente para el formulario de asientos contables
    Vue.component('asiento-form-vue-component', {
        template: `
            <form @submit.prevent="saveAsiento">
                <div class="row">
                    <!-- Información básica del asiento -->
                    <div class="col-md-6">
                        <h5>Información del Asiento</h5>

                        <div class="form-group">
                            <label>Tipo de Comprobante *</label>
                            <select v-model="form.tipo_comprobante_id"
                                    @change="onTipoComprobanteChanged"
                                    class="form-control"
                                    required>
                                <option value="">Seleccionar tipo de comprobante</option>
                                <option v-for="tipo in tiposComprobantes"
                                        :key="tipo.id"
                                        :value="tipo.id"
                                        v-text="tipo.codigo + ' - ' + tipo.nombre">
                                </option>
                            </select>
                            <small v-if="proximoConsecutivo" class="form-text text-muted">
                                Próximo consecutivo: <strong>#<span v-text="proximoConsecutivo"></span></strong>
                            </small>
                        </div>

                        <div class="form-group">
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

                    <!-- Balance Info -->
                    <div class="col-md-6">
                        <h5>Resumen Balance</h5>
                        <div class="balance-info" :class="balanceClass">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Total Débitos:</strong><br>
                                    $<span v-text="formatNumber(totalDebitos)"></span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Total Créditos:</strong><br>
                                    $<span v-text="formatNumber(totalCreditos)"></span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Diferencia:</strong><br>
                                    $<span v-text="formatNumber(diferencia)"></span>
                                </div>
                            </div>
                            <div class="mt-2">
                                <i :class="balanceIcon"></i>
                                <strong v-text="balanceText"></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalles del asiento -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5>Detalles del Asiento</h5>

                        <div v-for="(detalle, index) in form.detalles" :key="index" class="detalle-row">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Cuenta Contable *</label>
                                    <select v-model="detalle.cuenta_contable_id"
                                            @change="onCuentaChanged(index)"
                                            class="form-control"
                                            required>
                                        <option value="">Seleccionar cuenta</option>
                                        <option v-for="cuenta in cuentasContables"
                                                :key="cuenta.id"
                                                :value="cuenta.id"
                                                v-text="cuenta.codigo + ' - ' + cuenta.descripcion">
                                        </option>
                                    </select>
                                </div>

                                <div class="col-md-2" v-if="detalle.requiere_tercero">
                                    <label>Tercero</label>
                                    <select v-model="detalle.tercero_id" class="form-control">
                                        <option value="">Sin tercero</option>
                                        <option v-for="tercero in terceros"
                                                :key="tercero.id"
                                                :value="tercero.id"
                                                v-text="tercero.name + ' (' + tercero.number + ')">
                                        </option>
                                    </select>
                                </div>

                                <div :class="detalle.requiere_tercero ? 'col-md-2' : 'col-md-3'">
                                    <label>Débito</label>
                                    <input type="number"
                                           v-model="detalle.debito"
                                           @input="onMontoChanged(index)"
                                           class="form-control"
                                           step="0.01"
                                           min="0">
                                </div>

                                <div :class="detalle.requiere_tercero ? 'col-md-2' : 'col-md-3'">
                                    <label>Crédito</label>
                                    <input type="number"
                                           v-model="detalle.credito"
                                           @input="onMontoChanged(index)"
                                           class="form-control"
                                           step="0.01"
                                           min="0">
                                </div>

                                <div :class="detalle.requiere_tercero ? 'col-md-2' : 'col-md-2'">
                                    <label>Concepto</label>
                                    <input type="text"
                                           v-model="detalle.concepto"
                                           class="form-control"
                                           placeholder="Concepto del detalle">
                                </div>

                                <div class="col-md-1">
                                    <label>&nbsp;</label><br>
                                    <button type="button"
                                            @click="removeDetalle(index)"
                                            class="btn btn-danger btn-sm"
                                            v-if="form.detalles.length > 2">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="button"
                                    @click="addDetalle"
                                    class="btn btn-success btn-sm">
                                <i class="fa fa-plus"></i> Agregar Detalle
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit"
                                class="btn btn-primary"
                                :disabled="!balanceado || saving">
                            <i class="fa fa-save"></i>
                            <span v-text="saving ? 'Guardando...' : 'Guardar Asiento'"></span>
                        </button>
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
                            debito: '',
                            credito: '',
                            concepto: ''
                        },
                        {
                            cuenta_contable_id: '',
                            cuenta_contable: null,
                            tercero_id: '',
                            tercero: null,
                            requiere_tercero: false,
                            debito: '',
                            credito: '',
                            concepto: ''
                        }
                    ]
                },
                tiposComprobantes: [],
                cuentasContables: [],
                terceros: [],
                proximoConsecutivo: null,
                saving: false
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
                return this.diferencia < 0.01 && (this.totalDebitos > 0 || this.totalCreditos > 0);
            },
            balanceClass() {
                return this.balanceado ? 'balance-balanceado' : 'balance-desbalanceado';
            },
            balanceIcon() {
                return this.balanceado ? 'fa fa-check-circle' : 'fa fa-exclamation-triangle';
            },
            balanceText() {
                return this.balanceado ? 'Asiento Balanceado' : 'Asiento Desbalanceado';
            }
        },
        async mounted() {
            console.log('AsientoFormComponent mounted successfully');
            await this.loadTiposComprobantes();
            this.loadCuentasContables();
            this.loadTerceros();
        },
        methods: {
            async loadTiposComprobantes() {
                try {
                    const response = await axios.get('/contabilidad/asientos-contables/tipos-comprobantes');
                    this.tiposComprobantes = response.data;
                } catch (error) {
                    console.error('Error loading tipos comprobantes:', error);
                }
            },
            async loadCuentasContables() {
                try {
                    const response = await axios.get('/contabilidad/asientos-contables/cuentas-contables');
                    this.cuentasContables = response.data;
                } catch (error) {
                    console.error('Error loading cuentas contables:', error);
                }
            },
            async loadTerceros() {
                try {
                    const response = await axios.get('/contabilidad/asientos-contables/terceros');
                    this.terceros = response.data;
                } catch (error) {
                    console.error('Error loading terceros:', error);
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
                    this.proximoConsecutivo = response.data.proximo_consecutivo;
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
                    debito: '',
                    credito: '',
                    concepto: ''
                });
            },
            removeDetalle(index) {
                this.form.detalles.splice(index, 1);
            },
            async saveAsiento() {
                if (!this.balanceado) {
                    alert('El asiento debe estar balanceado para poder guardarlo.');
                    return;
                }

                this.saving = true;
                try {
                    const response = await axios.post('/contabilidad/asientos-contables', this.form);
                    alert('Asiento guardado exitosamente');
                    // Redireccionar o limpiar formulario
                    window.location.href = '/contabilidad/asientos-contables';
                } catch (error) {
                    console.error('Error saving asiento:', error);
                    alert('Error al guardar el asiento');
                } finally {
                    this.saving = false;
                }
            },
            formatNumber(number) {
                return new Intl.NumberFormat('es-CO', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(number);
            }
        }
    });

    console.log('AsientoFormComponent registered successfully');

    // Inicializar cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM Content Loaded - AsientoFormComponent should be available');
    });
</script>
@endpush
