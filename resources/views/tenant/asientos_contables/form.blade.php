@extends('tenant.layouts.app')

@push('styles')
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
        border-bottom: non        async mounted() {
            await this.loadTiposComprobantes();
            this.loadCuentasContables();
            this.loadTerceros();

            if (this.asientoData) {
                this.loadAsientoData();
            }
        }, .balance-info {
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
    .adjuntos-preview {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #ddd;
        padding: 10px;
        border-radius: 5px;
    }
    .archivo-item {
        display: flex;
        justify-content: between;
        align-items: center;
        padding: 5px 0;
        border-bottom: 1px solid #eee;
    }

    /* Reducir espaciado vertical */
    .form-group {
        margin-bottom: 0.5rem;
    }

    .detalle-row {
        padding: 4px 0;
        margin-bottom: 0.25rem;
    }

    .balance-info {
        padding: 6px 8px;
        margin-bottom: 8px;
    }

    /* Reducir padding en campos de formulario */
    .form-control {
        padding: 0.25rem 0.5rem;
        margin-bottom: 0.25rem;
    }

    /* Reducir espaciado entre elementos */
    .row {
        margin-bottom: 0.25rem;
    }

    .col-md-3, .col-md-2, .col-md-1, .col-md-4, .col-md-6 {
        padding-bottom: 0.25rem;
    }
    .archivo-item:last-child {
        border-bottom: none;
    }

    /* Asegurar que los botones estén a la derecha */
    .card-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
    }

    .card-tools {
        margin-left: auto !important;
    }
</style>
@endpush

@section('content')
<!-- DEBUG: Sección content iniciada -->
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
                    <asiento-form-vue-component
                        :asiento-data="{{ isset($asiento) ? $asiento->toJson() : 'null' }}"
                        :is-editing="{{ isset($asiento) ? 'true' : 'false' }}">
                    </asiento-form-vue-component>
                </div>

                <!-- Template para el componente -->
                <script type="text/x-template" id="asiento-form-template">
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
                                            required
                                            :disabled="isEditing">
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
                                    <label>Concepto General *</label>
                                    <textarea v-model="form.concepto"
                                              class="form-control"
                                              rows="3"
                                              required
                                              placeholder="Descripción general del asiento contable"></textarea>
                                </div>
                            </div>

                            <!-- Resumen y totales -->
                            <div class="col-md-6">
                                <h5>Resumen</h5>

                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label>Total Débito:</label>
                                                <div class="h5 text-success" v-text="'$' + totalDebito.toLocaleString()"></div>
                                            </div>
                                            <div class="col-sm-6">
                                                <label>Total Crédito:</label>
                                                <div class="h5 text-info" v-text="'$' + totalCredito.toLocaleString()"></div>
                                            </div>
                                        </div>

                                        <hr class="my-2">

                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label>Diferencia:</label>
                                                <div class="h5" :class="estaBalanceado ? 'text-success' : 'text-danger'"
                                                     v-text="'$' + diferencia.toLocaleString()"></div>
                                            </div>
                                            <div class="col-sm-6">
                                                <label>Estado:</label>
                                                <div class="h6">
                                                    <span v-if="estaBalanceado" class="badge badge-success">Balanceado</span>
                                                    <span v-else class="badge badge-warning">No Balanceado</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Archivos adjuntos -->
                                <div class="form-group mt-3">
                                    <label>Archivos Adjuntos</label>
                                    <input type="file"
                                           @change="onFilesSelected"
                                           class="form-control-file"
                                           multiple
                                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                                    <small class="form-text text-muted">Máximo 5MB por archivo</small>

                                    <!-- Mostrar archivos seleccionados -->
                                    <div v-if="adjuntosFiles.length > 0" class="mt-2">
                                        <label>Archivos a subir:</label>
                                        <ul class="list-unstyled">
                                            <li v-for="(file, index) in adjuntosFiles" :key="index" class="small">
                                                <i class="fa fa-file"></i> <span v-text="file.name"></span>
                                                (<span v-text="formatFileSize(file.size)"></span>)
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Mostrar archivos existentes (solo en edición) -->
                                    <div v-if="adjuntosExistentes.length > 0" class="mt-2">
                                        <label>Archivos existentes:</label>
                                        <ul class="list-unstyled">
                                            <li v-for="adjunto in adjuntosExistentes" :key="adjunto.id" class="small d-flex justify-content-between align-items-center">
                                                <span>
                                                    <i class="fa fa-file"></i> <span v-text="adjunto.nombre_archivo"></span>
                                                </span>
                                                <button type="button"
                                                        @click="eliminarAdjuntoExistente(adjunto.id)"
                                                        class="btn btn-sm btn-outline-danger">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Detalles del asiento -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5>Detalles del Asiento</h5>
                                    <button type="button"
                                            @click="addDetalle"
                                            class="btn btn-sm btn-primary">
                                        <i class="fa fa-plus"></i> Agregar Línea
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead class="thead-light">
                                            <tr>
                                                <th width="25%">Cuenta Contable *</th>
                                                <th width="20%">Tercero</th>
                                                <th width="15%">Débito</th>
                                                <th width="15%">Crédito</th>
                                                <th width="20%">Concepto *</th>
                                                <th width="5%">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(detalle, index) in form.detalles" :key="index">
                                                <td>
                                                    <select v-model="detalle.cuenta_contable_id"
                                                            @change="onCuentaSelected(detalle, cuentasContables.find(c => c.id == detalle.cuenta_contable_id))"
                                                            class="form-control form-control-sm"
                                                            required>
                                                        <option value="">Seleccionar cuenta</option>
                                                        <option v-for="cuenta in cuentasContables"
                                                                :key="cuenta.id"
                                                                :value="cuenta.id"
                                                                v-text="cuenta.codigo + ' - ' + cuenta.descripcion">
                                                        </option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select v-if="detalle.requiere_tercero"
                                                            v-model="detalle.tercero_id"
                                                            @change="onTerceroSelected(detalle, terceros.find(t => t.id == detalle.tercero_id))"
                                                            class="form-control form-control-sm">
                                                        <option value="">Seleccionar tercero</option>
                                                        <option v-for="tercero in terceros"
                                                                :key="tercero.id"
                                                                :value="tercero.id"
                                                                v-text="tercero.number + ' - ' + tercero.name">
                                                        </option>
                                                    </select>
                                                    <span v-else class="text-muted small">No requerido</span>
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           v-model="detalle.debito"
                                                           class="form-control form-control-sm"
                                                           step="0.01"
                                                           min="0"
                                                           placeholder="0.00">
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           v-model="detalle.credito"
                                                           class="form-control form-control-sm"
                                                           step="0.01"
                                                           min="0"
                                                           placeholder="0.00">
                                                </td>
                                                <td>
                                                    <input type="text"
                                                           v-model="detalle.concepto"
                                                           class="form-control form-control-sm"
                                                           required
                                                           placeholder="Concepto del movimiento">
                                                </td>
                                                <td>
                                                    <button type="button"
                                                            @click="removeDetalle(index)"
                                                            class="btn btn-sm btn-outline-danger"
                                                            :disabled="form.detalles.length <= 2">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit"
                                        class="btn btn-success"
                                        :disabled="!formularioValido || saving">
                                    <i class="fa fa-save"></i>
                                    <span v-text="saving ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Guardar')"></span> Asiento
                                </button>
                            </div>
                        </div>
                    </form>
                </script>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Registrar el componente globalmente para evitar conflictos con el Vue principal
    Vue.component('asiento-form-component', {
        template: '#asiento-form-template',
        props: {
            asientoData: {
                type: Object,
                default: null
            },
            isEditing: {
                type: Boolean,
                default: false
            }
        },
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
                loading: false,
                saving: false,
                adjuntosFiles: [],
                adjuntosExistentes: []
            };
        },
        computed: {
            totalDebito() {
                return this.form.detalles.reduce((sum, detalle) => {
                    return sum + (parseFloat(detalle.debito) || 0);
                }, 0);
            },
            totalCredito() {
                return this.form.detalles.reduce((sum, detalle) => {
                    return sum + (parseFloat(detalle.credito) || 0);
                }, 0);
            },
                    diferencia() {
                        return Math.abs(this.totalDebito - this.totalCredito);
                    },
                    estaBalanceado() {
                        return this.diferencia < 0.01; // Tolerancia para decimales
                    },
            detallesValidos() {
                return this.form.detalles.filter(detalle => {
                    return detalle.cuenta_contable_id &&
                           detalle.concepto &&
                           ((parseFloat(detalle.debito) || 0) > 0 ||
                            (parseFloat(detalle.credito) || 0) > 0);
                }).length >= 2;
            },
                    puedeGuardar() {
                        return this.form.tipo_comprobante_id &&
                               this.form.fecha_asiento &&
                               this.form.concepto &&
                               this.detallesValidos &&
                               this.estaBalanceado;
                    }
                },
        async mounted() {
                    await this.loadTiposComprobantes();
                    this.loadCuentasContables();
                    this.loadTerceros();

                    if (this.asientoData) {
                        this.loadAsientoData();
                    } else {
                        // Para nuevos asientos, cargar el consecutivo si hay un tipo seleccionado por defecto
                        if (this.form.tipo_comprobante_id) {
                            await this.loadProximoConsecutivo();
                        }
                    }
        },
        methods: {
                    createEmptyDetalle() {
                        return {
                            cuenta_contable_id: '',
                            cuenta_contable: null,
                            tercero_id: '',
                            tercero: null,
                            requiere_tercero: false,
                            debito: '',
                            credito: '',
                            concepto: ''
                        };
                    },
                    loadAsientoData() {
                        this.form.tipo_comprobante_id = this.asientoData.tipo_comprobante_id;
                        this.form.fecha_asiento = this.asientoData.fecha_asiento;
                        this.form.concepto = this.asientoData.concepto;

                        this.form.detalles = this.asientoData.detalles.map(detalle => ({
                            cuenta_contable_id: detalle.cuenta_contable_id,
                            cuenta_contable: detalle.cuenta_contable,
                            tercero_id: detalle.tercero_id || detalle.person_id, // Compatibilidad
                            tercero: detalle.tercero,
                            requiere_tercero: detalle.cuenta_contable ? detalle.cuenta_contable.requiere_tercero : false,
                            debito: detalle.debito > 0 ? detalle.debito : '',
                            credito: detalle.credito > 0 ? detalle.credito : '',
                            concepto: detalle.concepto
                        }));

                        this.adjuntosExistentes = this.asientoData.adjuntos || [];
                    },
                    async loadTiposComprobantes() {
                        try {
                            const response = await axios.get('/contabilidad/asientos-contables/tipos-comprobantes');
                            this.tiposComprobantes = response.data.data;
                        } catch (error) {
                            console.error('Error loading tipos comprobantes:', error);
                        }
                    },
                    async loadProximoConsecutivo() {
                        if (!this.form.tipo_comprobante_id) {
                            this.proximoConsecutivo = null;
                            return;
                        }

                        try {
                            const response = await axios.get('/contabilidad/asientos-contables/proximo-consecutivo', {
                                params: { tipo_comprobante_id: this.form.tipo_comprobante_id }
                            });
                            this.proximoConsecutivo = response.data.data.proximo_consecutivo;
                        } catch (error) {
                            console.error('Error loading próximo consecutivo:', error);
                            this.proximoConsecutivo = null;
                        }
                    },
                    async onTipoComprobanteChanged() {
                        await this.loadProximoConsecutivo();
                    },
                    async loadCuentasContables(search = '') {
                        try {
                            const response = await axios.get('/contabilidad/asientos-contables/cuentas-contables', {
                                params: { search }
                            });
                            this.cuentasContables = response.data.data;
                        } catch (error) {
                            console.error('Error loading cuentas contables:', error);
                        }
                    },
                    async loadTerceros(search = '') {
                        try {
                            const response = await axios.get('/contabilidad/asientos-contables/terceros', {
                                params: { search }
                            });
                            this.terceros = response.data.data;
                        } catch (error) {
                            console.error('Error loading terceros:', error);
                        }
                    },
                    onCuentaSelected(detalle, cuenta) {
                        detalle.cuenta_contable_id = cuenta.id;
                        detalle.cuenta_contable = cuenta;
                        detalle.requiere_tercero = cuenta.requiere_tercero;

                        // Limpiar tercero si la cuenta no lo requiere
                        if (!cuenta.requiere_tercero) {
                            detalle.tercero_id = '';
                            detalle.tercero = null;
                        }
                    },
                    onTerceroSelected(detalle, tercero) {
                        detalle.tercero_id = tercero.id;
                        detalle.tercero = tercero;
                    },
                    onDebitoChange(detalle) {
                        if (parseFloat(detalle.debito) > 0) {
                            detalle.credito = '';
                        }
                    },
                    onCreditoChange(detalle) {
                        if (parseFloat(detalle.credito) > 0) {
                            detalle.debito = '';
                        }
                    },
                    addDetalle() {
                        this.form.detalles.push(this.createEmptyDetalle());
                    },
                    removeDetalle(index) {
                        if (this.form.detalles.length > 2) {
                            this.form.detalles.splice(index, 1);
                        } else {
                            Swal.fire('Atención', 'Un asiento debe tener al menos 2 líneas de detalle', 'warning');
                        }
                    },
                    onFilesSelected(event) {
                        this.adjuntosFiles = Array.from(event.target.files);
                    },
                    async eliminarAdjuntoExistente(adjuntoId) {
                        const result = await Swal.fire({
                            title: '¿Eliminar archivo?',
                            text: 'Esta acción no se puede deshacer',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar'
                        });

                        if (result.isConfirmed) {
                            try {
                                const response = await axios.delete(`/contabilidad/asientos-adjuntos/${adjuntoId}`);

                                if (response.data.success) {
                                    this.adjuntosExistentes = this.adjuntosExistentes.filter(a => a.id !== adjuntoId);
                                    Swal.fire('Éxito', 'Archivo eliminado exitosamente', 'success');
                                } else {
                                    Swal.fire('Error', response.data.message, 'error');
                                }
                            } catch (error) {
                                Swal.fire('Error', 'Error al eliminar el archivo', 'error');
                            }
                        }
                    },
                    async saveAsiento() {
                        if (!this.puedeGuardar) {
                            Swal.fire('Atención', 'Complete todos los campos requeridos y asegúrese de que el asiento esté balanceado', 'warning');
                            return;
                        }

                        this.saving = true;

                        try {
                            const formData = new FormData();

                            // Datos del asiento
                            formData.append('tipo_comprobante_id', this.form.tipo_comprobante_id);
                            formData.append('fecha_asiento', this.form.fecha_asiento);
                            formData.append('concepto', this.form.concepto);

                            // Detalles
                            this.form.detalles.forEach((detalle, index) => {
                                if (detalle.cuenta_contable_id && detalle.concepto &&
                                    ((parseFloat(detalle.debito) || 0) > 0 || (parseFloat(detalle.credito) || 0) > 0)) {

                                    formData.append(`detalles[${index}][cuenta_contable_id]`, detalle.cuenta_contable_id);
                                    formData.append(`detalles[${index}][tercero_id]`, detalle.tercero_id || '');
                                    formData.append(`detalles[${index}][debito]`, detalle.debito || '0');
                                    formData.append(`detalles[${index}][credito]`, detalle.credito || '0');
                                    formData.append(`detalles[${index}][concepto]`, detalle.concepto);
                                }
                            });

                            // Archivos adjuntos
                            this.adjuntosFiles.forEach((file, index) => {
                                formData.append(`adjuntos[${index}]`, file);
                            });

                            let response;
                            if (this.isEditing) {
                                response = await axios.post(`/contabilidad/asientos-contables/${this.asientoData.id}`, formData, {
                                    headers: {
                                        'Content-Type': 'multipart/form-data',
                                        'X-HTTP-Method-Override': 'PUT'
                                    }
                                });
                            } else {
                                response = await axios.post('/contabilidad/asientos-contables', formData, {
                                    headers: {
                                        'Content-Type': 'multipart/form-data'
                                    }
                                });
                            }

                            if (response.data.success) {
                                Swal.fire({
                                    title: 'Éxito',
                                    text: response.data.message,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = '/contabilidad/asientos-contables';
                                });
                            } else {
                                Swal.fire('Error', response.data.message, 'error');
                            }
                        } catch (error) {
                            console.error('Error saving asiento:', error);
                            let errorMessage = 'Error al guardar el asiento';

                            if (error.response && error.response.data && error.response.data.message) {
                                errorMessage = error.response.data.message;
                            }

                            Swal.fire('Error', errorMessage, 'error');
                        } finally {
                            this.saving = false;
                        }
                    },
                    formatCurrency(amount) {
                        return new Intl.NumberFormat('es-CO', {
                            style: 'currency',
                            currency: 'COP',
                            minimumFractionDigits: 0
                        }).format(amount);
                    },
                    formatFileSize(bytes) {
                        const units = ['B', 'KB', 'MB', 'GB'];
                        let size = bytes;
                        let unitIndex = 0;

                        while (size >= 1024 && unitIndex < units.length - 1) {
                            size /= 1024;
                            unitIndex++;
                        }

                        return Math.round(size * 100) / 100 + ' ' + units[unitIndex];
                    }
                }
        });

        console.log('Vue app initialized successfully:', app);

    } catch (error) {
        console.error('Error initializing Vue app:', error);
    }
    }

    // Inicializar cuando todo esté completamente cargado
    if (document.readyState === 'complete') {
        // La página ya está completamente cargada
        setTimeout(initializeVue, 100);
    } else {
        // Esperar a que la página se cargue completamente
        window.addEventListener('load', function() {
            setTimeout(initializeVue, 100);
        });
        // También escuchar DOMContentLoaded como respaldo
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initializeVue, 300); // Dar más tiempo para que se renderice
        });
    }
</script>
@endpush
