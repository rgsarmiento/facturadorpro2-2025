<template>
    <form @submit.prevent="saveAsiento">
        <div class="row">
            <!-- Información básica del asiento -->
            <div class="col-md-8">
                <h5>Información del Asiento</h5>

                <div class="form-group">
                    <label>Tipo de Comprobante *</label>
                    <select ref="tipoComprobanteSelect"
                            v-model="form.tipo_comprobante_id"
                            @change="onTipoComprobanteChanged"
                            class="form-control"
                            required>
                        <option value="">Seleccionar tipo de comprobante</option>
                        <option v-for="tipo in tiposComprobantes"
                                :key="tipo.id"
                                :value="tipo.id">
                            {{ tipo.codigo }} - {{ tipo.nombre }}
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

            <!-- Panel de balance -->
            <div class="col-md-4">
                <div class="balance-info" :class="balanceClass">
                    <h6>Resumen del Asiento</h6>
                    <div class="row">
                        <div class="col-6">
                            <strong>Débitos:</strong><br>
                            $<span v-text="formatNumber(totalDebitos)"></span>
                        </div>
                        <div class="col-6">
                            <strong>Créditos:</strong><br>
                            $<span v-text="formatNumber(totalCreditos)"></span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <strong>Diferencia:</strong> $<span v-text="formatNumber(diferencia)"></span>
                    </div>
                    <div class="mt-2">
                        <i :class="balanceIcon"></i>
                        <strong v-text="balanceText"></strong>
                    </div>
                    <div class="mt-3">
                        <button type="submit"
                                class="btn btn-primary btn-block mb-2"
                                :disabled="saving || !balanceado">
                            <i v-if="saving" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-save"></i>
                            {{ saving ? 'Guardando...' : 'Guardar Asiento' }}
                        </button>
                        <a href="/contabilidad/asientos-contables"
                           class="btn btn-secondary btn-block"
                           :class="{ 'disabled': saving }">
                            <i class="fa fa-arrow-left"></i>
                            Cancelar / Volver al Listado
                        </a>

                        <!-- Debug button (development only) -->
                        <button type="button"
                                class="btn btn-warning btn-block mt-2"
                                @click="debugAsientoData()">
                            <i class="fa fa-bug"></i>
                            Debug Estado
                        </button>

                        <!-- Botón de debug temporal -->
                        <button v-if="isEditing"
                                type="button"
                                @click="debugAsientoData"
                                class="btn btn-info btn-block btn-sm mt-2">
                            <i class="fa fa-bug"></i>
                            Debug: Ver datos del asiento
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de detalles -->
        <div class="row mt-4">
            <div class="col-12">
                <h5>Detalles del Asiento</h5>
                <div class="table-responsive">
                    <table class="table table-bordered asientos-table">
                        <thead class="thead-light">
                            <tr>
                                <th width="25%">Cuenta Contable</th>
                                <th width="20%">Tercero</th>
                                <th width="25%">Concepto</th>
                                <th width="12%">Débito</th>
                                <th width="12%">Crédito</th>
                                <th width="6%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(detalle, index) in form.detalles" :key="index">
                                <!-- Cuenta Contable -->
                                <td>
                                    <select :ref="'cuentaSelect' + index"
                                            v-model="detalle.cuenta_contable_id"
                                            @change="onCuentaChanged(index)"
                                            class="form-control"
                                            required>
                                        <option value="">Seleccionar cuenta</option>
                                        <option v-for="cuenta in cuentasContables"
                                                :key="cuenta.id"
                                                :value="cuenta.id">
                                            {{ getCuentaDisplayText(cuenta) }}
                                        </option>
                                    </select>
                                </td>

                                <!-- Tercero -->
                                <!-- Tercero -->
                                <td>
                                    <select v-if="detalle.requiere_tercero"
                                            :ref="`terceroSelect${index}`"
                                            v-model="detalle.tercero_id"
                                            class="form-control"
                                            required>
                                        <option value="">Seleccionar tercero</option>
                                        <option v-for="tercero in terceros"
                                                :key="tercero.id"
                                                :value="tercero.id">
                                            {{ getTerceroDisplayText(tercero) }}
                                        </option>
                                    </select>
                                    <span v-else class="text-muted">N/A</span>
                                </td>                                <!-- Concepto -->
                                <td>
                                    <input type="text"
                                           v-model="detalle.concepto"
                                           class="form-control"
                                           placeholder="Concepto específico">
                                </td>

                                <!-- Débito -->
                                <td>
                                    <input type="number"
                                           v-model="detalle.debito"
                                           @input="onDebitoChanged(index, $event)"
                                           class="form-control text-right"
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00">
                                </td>

                                <!-- Crédito -->
                                <td>
                                    <input type="number"
                                           v-model="detalle.credito"
                                           @input="onCreditoChanged(index, $event)"
                                           class="form-control text-right"
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00">
                                </td>

                                <!-- Acciones -->
                                <td class="text-center">
                                    <button type="button"
                                            @click="removeDetalle(index)"
                                            class="btn btn-sm btn-danger"
                                            :disabled="form.detalles.length <= 2">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="text-center mt-3">
                    <button type="button"
                            @click="addDetalle"
                            class="btn btn-secondary">
                        <i class="fa fa-plus"></i> Agregar Línea
                    </button>
                </div>
            </div>
        </div>

        <!-- Sección de adjuntos -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fa fa-paperclip mr-2"></i>
                            Adjuntos (Opcional)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <div class="custom-file">
                                <input type="file"
                                       @change="onFilesSelected"
                                       class="custom-file-input"
                                       id="adjuntosInput"
                                       multiple
                                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                                <label class="custom-file-label" for="adjuntosInput">
                                    <i class="fa fa-upload mr-2"></i>
                                    Seleccionar archivos...
                                </label>
                            </div>
                            <small class="form-text text-muted mt-2">
                                <i class="fa fa-info-circle mr-1"></i>
                                Formatos permitidos: PDF, imágenes, documentos de Word/Excel. Máximo 5MB por archivo.
                            </small>
                        </div>

                        <div v-if="adjuntosFiles.length > 0" class="mt-3">
                            <h6 class="text-success">
                                <i class="fa fa-check-circle mr-2"></i>
                                Archivos nuevos a subir ({{ adjuntosFiles.length }}):
                            </h6>
                            <div class="list-group">
                                <div v-for="(file, index) in adjuntosFiles"
                                     :key="index"
                                     class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <i class="fa fa-file-o mr-3 text-primary"></i>
                                        <div>
                                            <div class="font-weight-medium">{{ file.name }}</div>
                                            <small class="text-muted">{{ formatFileSize(file.size) }}</small>
                                        </div>
                                    </div>
                                    <button type="button"
                                            @click="removeFile(index)"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Remover archivo">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Adjuntos existentes (modo edición) -->
                        <div v-if="adjuntosExistentes.length > 0" class="mt-3">
                            <h6 class="text-info">
                                <i class="fa fa-cloud mr-2"></i>
                                Archivos ya guardados ({{ adjuntosExistentes.length }}):
                            </h6>
                            <div class="list-group">
                                <div v-for="(adjunto, index) in adjuntosExistentes"
                                     :key="'existing-' + adjunto.id"
                                     class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <i class="fa fa-cloud-download mr-3 text-info"></i>
                                        <div>
                                            <div class="font-weight-medium">{{ adjunto.nombre }}</div>
                                            <small class="text-muted">{{ formatFileSize(adjunto.tamaño) }} - Ya guardado</small>
                                        </div>
                                    </div>
                                    <div>
                                        <a :href="adjunto.url"
                                           class="btn btn-sm btn-outline-primary mr-2"
                                           title="Descargar archivo">
                                            <i class="fa fa-download"></i>
                                        </a>
                                        <button type="button"
                                                @click="removeExistingAdjunto(adjunto.id, index)"
                                                class="btn btn-sm btn-outline-danger"
                                                title="Eliminar archivo">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>

<script>
export default {
    name: 'AsientoForm',
    props: {
        asientoData: {
            type: [Object, String],
            default: null
        },
        isEditing: {
            type: [Boolean, String],
            default: false
        }
    },
    data() {
        return {
            form: {
                id: null,
                tipo_comprobante_id: '',
                fecha_asiento: new Date().toISOString().substr(0, 10),
                concepto: '',
                estado: 'borrador', // Estado por defecto
                detalles: [
                    {
                        cuenta_contable_id: '',
                        cuenta_contable: null,
                        tercero_id: '',
                        tercero: null,
                        requiere_tercero: false,
                        debito: '0.00',
                        credito: '0.00',
                        concepto: ''
                    },
                    {
                        cuenta_contable_id: '',
                        cuenta_contable: null,
                        tercero_id: '',
                        tercero: null,
                        requiere_tercero: false,
                        debito: '0.00',
                        credito: '0.00',
                        concepto: ''
                    }
                ]
            },
            tiposComprobantes: [],
            cuentasContables: [],
            terceros: [],
            proximoConsecutivo: null,
            saving: false,
            adjuntosFiles: [],
            adjuntosExistentes: []
        }
    },
    computed: {
        parsedAsientoData() {
            if (!this.asientoData) return null;
            if (typeof this.asientoData === 'string') {
                try {
                    return JSON.parse(this.asientoData);
                } catch (e) {
                    console.error('Error parsing asientoData:', e);
                    return null;
                }
            }
            return this.asientoData;
        },
        isEditingMode() {
            return this.isEditing === true || this.isEditing === 'true' || this.isEditing === '1';
        },
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
            const hayDatos = this.totalDebitos > 0 || this.totalCreditos > 0;
            if (!hayDatos) return true;
            return this.diferencia < 0.01;
        },
        balanceClass() {
            const hayDatos = this.totalDebitos > 0 || this.totalCreditos > 0;
            if (!hayDatos) return '';
            return this.balanceado ? 'balance-balanceado' : 'balance-desbalanceado';
        },
        balanceIcon() {
            const hayDatos = this.totalDebitos > 0 || this.totalCreditos > 0;
            if (!hayDatos) return 'fa fa-info-circle';
            return this.balanceado ? 'fa fa-check-circle' : 'fa fa-exclamation-triangle';
        },
        balanceText() {
            const hayDatos = this.totalDebitos > 0 || this.totalCreditos > 0;
            if (!hayDatos) return 'Ingrese los montos';
            return this.balanceado ? 'Asiento Balanceado' : 'Asiento Desbalanceado';
        }
    },
    async mounted() {
        console.log('Component mounted');
        console.log('Props received:');
        console.log('- asientoData:', this.asientoData);
        console.log('- isEditing:', this.isEditing);
        console.log('- parsedAsientoData:', this.parsedAsientoData);
        console.log('- isEditingMode:', this.isEditingMode);

        console.log('Loading data in mounted...');
        await this.loadTiposComprobantes();
        await this.loadCuentasContables();
        await this.loadTerceros();

        if (this.isEditingMode && this.parsedAsientoData) {
            console.log('Loading asiento data in edit mode...');
            this.loadAsientoData();
        } else {
            console.log('Not in edit mode, form is ready for creation');
            // Ya no necesitamos inicializar Select2, Vue manejará los selects automáticamente
        }
    },
    methods: {
        formatNumber(value) {
            return new Intl.NumberFormat('es-CO', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(value || 0);
        },
        getCuentaDisplayText(cuenta) {
            if (!cuenta) return '';

            // Intentar diferentes propiedades comunes para cuentas contables
            const codigo = cuenta.codigo || cuenta.code || cuenta.numero || cuenta.cuenta || '';
            const nombre = cuenta.nombre || cuenta.name || cuenta.descripcion || cuenta.description || '';

            if (codigo && nombre) {
                return `${codigo} - ${nombre}`;
            } else if (nombre) {
                return nombre;
            } else if (codigo) {
                return codigo;
            } else {
                // Si no encontramos propiedades reconocidas, mostrar las claves del objeto
                const keys = Object.keys(cuenta);
                console.warn('Estructura no reconocida para cuenta:', cuenta);
                return `ID: ${cuenta.id} (${keys.join(', ')})`;
            }
        },
        getTerceroDisplayText(tercero) {
            if (!tercero) return '';

            // Intentar diferentes propiedades comunes para terceros
            const documento = tercero.numero_documento || tercero.document || tercero.documento || tercero.nit || tercero.cedula || '';
            const nombre = tercero.nombre || tercero.name || tercero.razon_social || tercero.razonsocial || '';

            if (documento && nombre) {
                return `${documento} - ${nombre}`;
            } else if (nombre) {
                return nombre;
            } else if (documento) {
                return documento;
            } else {
                // Si no encontramos propiedades reconocidas, mostrar las claves del objeto
                const keys = Object.keys(tercero);
                console.warn('Estructura no reconocida para tercero:', tercero);
                return `ID: ${tercero.id} (${keys.join(', ')})`;
            }
        },
        async saveAsiento() {
            if (this.saving) return; // Prevenir múltiples envíos

            this.saving = true;

            try {
                // Validar que el asiento esté balanceado
                if (this.diferencia > 0.01) {
                    alert('El asiento debe estar balanceado. La diferencia entre débitos y créditos debe ser cero.');
                    return;
                }

                // Validar que haya al menos un detalle con valores
                const hayDetallesConValor = this.form.detalles.some(detalle =>
                    (parseFloat(detalle.debito) > 0 || parseFloat(detalle.credito) > 0) &&
                    detalle.cuenta_contable_id
                );

                if (!hayDetallesConValor) {
                    alert('Debe agregar al menos un detalle con cuenta contable y valor mayor a cero.');
                    return;
                }

                // Validar datos básicos
                if (!this.form.tipo_comprobante_id) {
                    alert('Debe seleccionar un tipo de comprobante.');
                    return;
                }

                if (!this.form.fecha_asiento) {
                    alert('Debe ingresar la fecha del asiento.');
                    return;
                }

                if (!this.form.concepto || this.form.concepto.trim() === '') {
                    alert('Debe ingresar el concepto del asiento.');
                    return;
                }

                // Validar que el asiento esté en estado borrador si estamos editando
                if (this.isEditing) {
                    console.log('Validando estado del asiento:');
                    console.log('- Estado actual:', this.form.estado);
                    console.log('- Tipo de estado:', typeof this.form.estado);
                    console.log('- Es borrador?:', this.form.estado === 'borrador');

                    const estadosPermitidos = ['borrador', 'draft', 'BORRADOR', 'DRAFT', null, undefined, ''];
                    const puedeEditar = estadosPermitidos.includes(this.form.estado);

                    console.log('- Puede editar?:', puedeEditar);

                    if (!puedeEditar) {
                        alert(`Solo se pueden modificar asientos en estado borrador. Estado actual: "${this.form.estado}"`);
                        return;
                    }
                }                // Preparar FormData para enviar archivos
                const formData = new FormData();

                // Agregar datos básicos del asiento
                formData.append('tipo_comprobante_id', this.form.tipo_comprobante_id);
                formData.append('fecha_asiento', this.form.fecha_asiento);
                formData.append('concepto', this.form.concepto);

                // Normalizar estado a minúsculas para el servidor
                const estadoParaServidor = this.form.estado ? this.form.estado.toLowerCase() : 'borrador';
                formData.append('estado', estadoParaServidor);

                // También agregar con nombres alternativos que el backend podría esperar
                formData.append('fecha', this.form.fecha_asiento); // Backend might expect 'fecha'
                formData.append('descripcion', this.form.concepto); // Backend might expect 'descripcion'

                console.log('=== FORMDATA DEBUG ===');
                console.log('estado original:', this.form.estado);
                console.log('estado normalizado:', estadoParaServidor);
                console.log('isEditing:', this.isEditing);

                // Si estamos editando, agregar el ID
                if (this.isEditing && this.form.id) {
                    formData.append('id', this.form.id);
                    formData.append('_method', 'PUT');

                    // Asegurar que los totales sean números válidos
                    const totalDebitoNum = parseFloat(this.totalDebito) || 0;
                    const totalCreditoNum = parseFloat(this.totalCredito) || 0;

                    console.log('Totales calculados:', {
                        totalDebito: totalDebitoNum,
                        totalCredito: totalCreditoNum,
                        totalDebito_type: typeof totalDebitoNum,
                        totalCredito_type: typeof totalCreditoNum
                    });

                    // Para el update, el servidor espera total_debito y total_credito
                    formData.append('total_debito', totalDebitoNum.toFixed(2));
                    formData.append('total_credito', totalCreditoNum.toFixed(2));

                    // También enviar con nombres alternativos por compatibilidad
                    formData.append('total_debe', totalDebitoNum.toFixed(2));
                    formData.append('total_haber', totalCreditoNum.toFixed(2));
                }

                // Agregar detalles válidos (que tengan cuenta y valor)
                const detallesValidos = this.form.detalles.filter(detalle =>
                    detalle.cuenta_contable_id &&
                    (parseFloat(detalle.debito) > 0 || parseFloat(detalle.credito) > 0)
                );

                console.log('Detalles válidos a enviar:', detallesValidos.map(d => ({
                    cuenta_contable_id: d.cuenta_contable_id,
                    tercero_id: d.tercero_id,
                    concepto: d.concepto,
                    debito: d.debito,
                    credito: d.credito,
                    requiere_tercero: d.requiere_tercero
                })));

                // Validación: verificar que todos los conceptos estén completos
                const conceptosVacios = detallesValidos.filter(detalle =>
                    !detalle.concepto || detalle.concepto.trim() === ''
                );

                if (conceptosVacios.length > 0) {
                    alert('Error: Todos los detalles deben tener un concepto. Por favor complete todos los campos de concepto.');
                    this.saving = false;
                    return;
                }

                // Enviar detalles de forma estructurada para Laravel
                detallesValidos.forEach((detalle, index) => {
                    formData.append(`detalles[${index}][cuenta_contable_id]`, detalle.cuenta_contable_id);

                    // Solo enviar tercero_id si tiene valor válido
                    if (detalle.tercero_id && detalle.tercero_id !== '' && detalle.tercero_id !== null) {
                        formData.append(`detalles[${index}][tercero_id]`, detalle.tercero_id);
                    }

                    // Asegurar que siempre enviamos valores numéricos válidos
                    const debitoValue = parseFloat(detalle.debito) || 0;
                    const creditoValue = parseFloat(detalle.credito) || 0;

                    // El servidor espera 'debe' y 'haber' en lugar de 'debito' y 'credito'
                    formData.append(`detalles[${index}][debe]`, debitoValue.toFixed(2));
                    formData.append(`detalles[${index}][haber]`, creditoValue.toFixed(2));

                    formData.append(`detalles[${index}][concepto]`, detalle.concepto.trim());

                    // También mantener los nombres originales por compatibilidad
                    formData.append(`detalles[${index}][debito]`, debitoValue.toFixed(2));
                    formData.append(`detalles[${index}][credito]`, creditoValue.toFixed(2));

                    // Log individual de cada detalle que se envía
                    console.log(`Detalle ${index}:`, {
                        cuenta_contable_id: detalle.cuenta_contable_id,
                        tercero_id: detalle.tercero_id,
                        debe: debitoValue.toFixed(2),
                        haber: creditoValue.toFixed(2),
                        concepto: detalle.concepto.trim()
                    });
                });

                // Agregar archivos adjuntos
                this.adjuntosFiles.forEach((file, index) => {
                    formData.append(`adjuntos[${index}]`, file);
                });                console.log('Enviando datos del asiento:', {
                    id: this.form.id,
                    tipo_comprobante_id: this.form.tipo_comprobante_id,
                    fecha_asiento: this.form.fecha_asiento,
                    concepto: this.form.concepto,
                    estado: this.form.estado,
                    isEditing: this.isEditing,
                    detalles: detallesValidos,
                    adjuntos: this.adjuntosFiles.length
                });

                // Debug: Mostrar qué se está enviando al servidor
                console.log('=== SENDING TO SERVER ===');
                console.log('URL:', this.isEditing
                    ? `/contabilidad/asientos-contables/${this.form.id}`
                    : '/contabilidad/asientos-contables');
                console.log('Method:', this.isEditing ? 'POST (with _method=PUT)' : 'POST');
                console.log('Estado being sent:', estadoParaServidor);

                // Log completo del FormData para debugging
                console.log('FormData contents:');
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + (pair[1] instanceof File ? `FILE: ${pair[1].name}` : pair[1]));
                }
                console.log('========================');

                // Determinar URL según si estamos creando o editando
                const url = this.isEditing
                    ? `/contabilidad/asientos-contables/${this.form.id}`
                    : '/contabilidad/asientos-contables';

                // Enviar petición
                const response = await axios.post(url, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });

                if (response.data.success) {
                    // Mostrar mensaje de éxito
                    alert(response.data.message || 'Asiento contable guardado exitosamente');

                    // Redireccionar al index
                    window.location.href = '/contabilidad/asientos-contables';
                } else {
                    alert('Error al guardar: ' + (response.data.message || 'Error desconocido'));
                }

            } catch (error) {
                console.error('=== ERROR SAVING ASIENTO ===');
                console.error('Error object:', error);
                console.error('Error response:', error.response);
                console.error('Error response data:', error.response?.data);
                console.error('Error response status:', error.response?.status);
                console.error('Error message:', error.message);
                console.error('========================');

                if (error.response && error.response.data) {
                    // Mostrar errores de validación del servidor
                    const errorData = error.response.data;
                    let errorMessage = 'Error al guardar el asiento:\n';

                    if (errorData.errors) {
                        Object.keys(errorData.errors).forEach(field => {
                            errorMessage += `• ${errorData.errors[field].join(', ')}\n`;
                        });
                    } else if (errorData.message) {
                        errorMessage += errorData.message;
                    }

                    alert(errorMessage);
                } else {
                    alert('Error de conexión. Por favor, intente nuevamente.');
                }
            } finally {
                this.saving = false;
            }
        },
        addDetalle() {
            const newIndex = this.form.detalles.length;
            this.form.detalles.push({
                cuenta_contable_id: '',
                cuenta_contable: null,
                tercero_id: '',
                tercero: null,
                requiere_tercero: false,
                debito: '0.00',
                credito: '0.00',
                concepto: ''
            });

            // Solo popular el select de la nueva fila, sin afectar las existentes
            this.$nextTick(() => {
                this.populateSelectForNewRow(newIndex);
            });
        },
        removeDetalle(index) {
            this.form.detalles.splice(index, 1);
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
        async loadTiposComprobantes() {
            try {
                console.log('Loading tipos comprobantes...');
                const response = await axios.get('/contabilidad/asientos-contables/tipos-comprobantes');
                if (response.data.success) {
                    this.tiposComprobantes = response.data.data;
                    console.log('Tipos comprobantes loaded:', this.tiposComprobantes.length, 'items');

                    // Como ahora usamos v-model en el template, Vue automáticamente populará las opciones
                    console.log('Tipos comprobantes are now available for Vue rendering');
                }
            } catch (error) {
                console.error('Error loading tipos comprobantes:', error);
            }
        },
        async loadCuentasContables() {
            try {
                const response = await axios.get('/contabilidad/asientos-contables/cuentas-contables');
                if (response.data.success) {
                    this.cuentasContables = response.data.data;
                    console.log('Cuentas contables loaded:', this.cuentasContables.length, 'items');
                    console.log('Sample cuenta contable:', JSON.stringify(this.cuentasContables[0], null, 2));

                    // Popular los selects nativos después de cargar los datos
                    this.$nextTick(() => {
                        this.populateCuentasContablesOptions();
                    });
                }
            } catch (error) {
                console.error('Error loading cuentas contables:', error);
            }
        },
        async loadTerceros() {
            try {
                const response = await axios.get('/contabilidad/asientos-contables/terceros');
                if (response.data.success) {
                    this.terceros = response.data.data;
                    console.log('Terceros loaded:', this.terceros.length, 'items');
                    console.log('Sample tercero:', JSON.stringify(this.terceros[0], null, 2));

                    // Popular los selects nativos después de cargar los datos
                    this.$nextTick(() => {
                        this.populateTercerosOptions();
                    });
                }
            } catch (error) {
                console.error('Error loading terceros:', error);
            }
        },
        loadAsientoData() {
            const asiento = this.parsedAsientoData;
            if (!asiento) {
                console.log('No asiento data to load');
                return;
            }

            console.log('=== LOADING ASIENTO DATA ===');
            console.log('Asiento completo:', asiento);
            console.log('Estado del asiento:', asiento.estado);

            // Cargar datos básicos del asiento
            this.form.id = asiento.id;
            this.form.tipo_comprobante_id = asiento.tipo_comprobante_id || '';
            this.form.estado = asiento.estado || 'borrador'; // Cargar el estado

            console.log('Estado cargado:', {
                original: asiento.estado,
                asignado: this.form.estado,
                tipo: typeof this.form.estado
            });

            let fechaAsiento = asiento.fecha_asiento || new Date().toISOString().substr(0, 10);
            if (fechaAsiento.includes(' ')) {
                fechaAsiento = fechaAsiento.split(' ')[0];
            }
            this.form.fecha_asiento = fechaAsiento;
            this.form.concepto = asiento.concepto || '';

            // Cargar detalles del asiento
            if (asiento.detalles && asiento.detalles.length > 0) {
                console.log('Loading detalles:', asiento.detalles);
                this.form.detalles = asiento.detalles.map((detalle) => {
                    let cuentaContable = null;
                    let requiereTercero = false;

                    if (detalle.cuenta_contable) {
                        cuentaContable = detalle.cuenta_contable;
                        requiereTercero = detalle.cuenta_contable.requiere_tercero || false;
                    }

                    console.log(`Detalle ${detalle.id}:`, {
                        cuenta_contable_id: detalle.cuenta_contable_id,
                        tercero_id: detalle.tercero_id,
                        tercero: detalle.tercero,
                        requiere_tercero: requiereTercero
                    });

                    // Obtener el tercero_id correctamente
                    let terceroId = '';
                    if (detalle.tercero_id) {
                        terceroId = detalle.tercero_id;
                    } else if (detalle.tercero && detalle.tercero.id) {
                        terceroId = detalle.tercero.id;
                    }

                    return {
                        cuenta_contable_id: detalle.cuenta_contable_id || '',
                        cuenta_contable: cuentaContable,
                        tercero_id: terceroId,
                        tercero: detalle.tercero || null,
                        requiere_tercero: requiereTercero,
                        debito: detalle.debito || '0.00',
                        credito: detalle.credito || '0.00',
                        concepto: detalle.concepto || ''
                    };
                });
            }

            // Cargar adjuntos del asiento
            if (asiento.adjuntos && asiento.adjuntos.length > 0) {
                console.log('Loading adjuntos:', asiento.adjuntos);
                // Los adjuntos ya están guardados en el servidor, solo mostramos la información
                // No los agregamos a adjuntosFiles porque son archivos ya existentes
                this.adjuntosExistentes = asiento.adjuntos.map(adjunto => ({
                    id: adjunto.id,
                    nombre: adjunto.nombre_archivo,
                    tamaño: adjunto.tamaño_archivo,
                    url: `/contabilidad/asientos-contables/adjuntos/${adjunto.id}/descargar`
                }));
            }

            // Inicializar selects nativos después de cargar los datos
            this.$nextTick(() => {
                console.log('Populating selects after loading asiento data...');
                // Esperar un poco más para asegurar que el DOM esté completamente renderizado
                setTimeout(() => {
                    this.populateCuentasContablesOptions();
                    this.populateTercerosOptions();
                }, 100);
            });
        },
        waitForSelect2(callback, maxAttempts = 50) {
            let attempts = 0;
            const checkSelect2 = () => {
                attempts++;
                if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
                    console.log('Select2 is now available, proceeding with initialization...');
                    callback();
                } else if (attempts < maxAttempts) {
                    console.log(`Waiting for Select2... attempt ${attempts}/${maxAttempts}`);
                    setTimeout(checkSelect2, 100); // Esperar 100ms antes del siguiente intento
                } else {
                    console.warn('Select2 could not be loaded after maximum attempts. Using native select fallback.');
                    // Llamar el callback de todos modos para que el formulario funcione con selects nativos
                    this.initializeNativeSelects();
                }
            };
            checkSelect2();
        },
        initializeNativeSelects() {
            console.log('Initializing native select elements as fallback...');
            // Simplemente agregar las opciones a los selects nativos
            this.populateTipoComprobanteOptions();
        },
        populateTipoComprobanteOptions() {
            const select = this.$refs.tipoComprobanteSelect;
            if (!select) {
                console.error('tipoComprobanteSelect ref not found');
                return;
            }

            // Limpiar opciones existentes excepto la primera
            select.innerHTML = '<option value="">Seleccionar tipo de comprobante</option>';

            // Agregar tipos de comprobantes
            this.tiposComprobantes.forEach(tipo => {
                const option = document.createElement('option');
                option.value = tipo.id;
                option.textContent = `${tipo.codigo} - ${tipo.nombre}`;
                select.appendChild(option);
            });

            // Agregar event listener para cambios
            select.addEventListener('change', (e) => {
                this.form.tipo_comprobante_id = e.target.value;
                this.onTipoComprobanteChanged();
            });

            console.log('Native select populated with', this.tiposComprobantes.length, 'options');
        },
        initTipoComprobanteSelect2() {
            if (typeof $.fn.select2 === 'undefined') {
                console.warn('Select2 is not available when initTipoComprobanteSelect2 was called. Using native select.');
                this.populateTipoComprobanteOptions();
                return;
            }

            const vm = this;
            const $select = $(this.$refs.tipoComprobanteSelect);
            if (!$select.length) {
                console.error('tipoComprobanteSelect element not found');
                return;
            }

            console.log('Initializing tipo comprobante select2...');

            if (this.tiposComprobantes.length === 0) {
                console.warn('No tipos comprobantes available to populate select');
                return;
            }

            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            $select.empty().append('<option value="">Seleccionar tipo de comprobante</option>');

            this.tiposComprobantes.forEach(tipo => {
                $select.append(new Option(tipo.codigo + ' - ' + tipo.nombre, tipo.id));
            });

            $select.select2({
                theme: 'bootstrap',
                placeholder: 'Buscar tipo de comprobante...',
                allowClear: true
            }).on('change', function() {
                const selectedValue = $(this).val();
                console.log('Tipo comprobante changed to:', selectedValue);
                vm.form.tipo_comprobante_id = selectedValue;
                vm.onTipoComprobanteChanged();
            });

            console.log('Tipo comprobante select2 initialized successfully');
        },
        initCuentasContablesSelect2() {
            console.log('Initializing cuentas contables select2...');
            if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
                console.warn('Select2 not available, using native selects for cuentas contables');
                this.populateCuentasContablesOptions();
                return;
            }

            this.waitForSelect2(() => {
                console.log('Select2 available, initializing cuentas contables selects...');
                // Aquí podríamos implementar Select2 para las cuentas contables si fuera necesario
                this.populateCuentasContablesOptions();
            });
        },
        populateCuentasContablesOptions() {
            console.log('Populating cuentas contables native selects...');
            console.log('Form detalles length:', this.form.detalles.length);
            console.log('Available refs:', Object.keys(this.$refs));

            // Esperar al siguiente tick para asegurar que los elementos estén en el DOM
            this.$nextTick(() => {
                this.form.detalles.forEach((detalle, index) => {
                    const selectRef = `cuentaSelect${index}`;
                    console.log(`Looking for ref: ${selectRef}`);

                    // Los refs dinámicos en Vue pueden estar como array o elemento único
                    let selectElement = this.$refs[selectRef];

                    if (Array.isArray(selectElement)) {
                        selectElement = selectElement[0];
                    }

                    if (selectElement) {
                        console.log(`Found select element for index ${index}:`, selectElement);

                        // Limpiar opciones existentes excepto la primera
                        selectElement.innerHTML = '<option value="">Seleccionar cuenta</option>';

                        // Agregar opciones de cuentas contables
                        this.cuentasContables.forEach(cuenta => {
                            const option = document.createElement('option');
                            option.value = cuenta.id;
                            option.textContent = this.getCuentaDisplayText(cuenta);
                            selectElement.appendChild(option);
                        });

                        // Establecer el valor seleccionado si existe
                        if (detalle.cuenta_contable_id) {
                            selectElement.value = detalle.cuenta_contable_id;
                            console.log(`Set cuenta value for index ${index}: ${detalle.cuenta_contable_id}`);
                        }

                        console.log(`Populated select ${index} with ${this.cuentasContables.length} options`);
                    } else {
                        console.warn(`Select element not found for index ${index}, ref: ${selectRef}`);
                        console.log('Available refs at this moment:', Object.keys(this.$refs));
                    }
                });
            });
        },
        populateTercerosOptions() {
            console.log('Populating terceros native selects...');
            console.log('Form detalles length:', this.form.detalles.length);
            console.log('Available refs:', Object.keys(this.$refs));

            // Esperar al siguiente tick para asegurar que los elementos estén en el DOM
            this.$nextTick(() => {
                this.form.detalles.forEach((detalle, index) => {
                    // Solo procesar si el detalle requiere tercero
                    if (!detalle.requiere_tercero) {
                        console.log(`Detalle ${index} no requiere tercero, skipping...`);
                        return;
                    }

                    const selectRef = `terceroSelect${index}`;
                    console.log(`Looking for ref: ${selectRef} (requiere_tercero: ${detalle.requiere_tercero})`);

                    // Los refs dinámicos en Vue pueden estar como array o elemento único
                    let selectElement = this.$refs[selectRef];

                    if (Array.isArray(selectElement)) {
                        selectElement = selectElement[0];
                    }

                    if (selectElement) {
                        console.log(`Found tercero select element for index ${index}:`, selectElement);

                        // Limpiar opciones existentes excepto la primera
                        selectElement.innerHTML = '<option value="">Seleccionar tercero</option>';

                        // Agregar opciones de terceros
                        this.terceros.forEach(tercero => {
                            const option = document.createElement('option');
                            option.value = tercero.id;
                            option.textContent = this.getTerceroDisplayText(tercero);
                            selectElement.appendChild(option);
                        });

                        // Establecer el valor seleccionado si existe
                        if (detalle.tercero_id) {
                            selectElement.value = detalle.tercero_id;
                            console.log(`Set tercero value for index ${index}: ${detalle.tercero_id}`);
                        }

                        console.log(`Populated tercero select ${index} with ${this.terceros.length} options`);
                    } else {
                        console.warn(`Tercero select element not found for index ${index}, ref: ${selectRef}`);
                        console.log('Available refs at this moment:', Object.keys(this.$refs));
                    }
                });
            });
        },
        setAccountValuesAfterInit() {
            // Implementar configuración de valores de cuentas después de la inicialización
            console.log('Setting account values after init...');
        },
        waitForSelect2AndInitialize() {
            console.log('Waiting for Select2 and initializing...');
            this.waitForSelect2(() => {
                this.initTipoComprobanteSelect2();
            });
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
                if (response.data.success) {
                    this.proximoConsecutivo = response.data.data.proximo_consecutivo;
                }
            } catch (error) {
                console.error('Error loading proximo consecutivo:', error);
            }
        },
        onFilesSelected(event) {
            const files = Array.from(event.target.files);
            files.forEach(file => {
                if (file.size <= 5 * 1024 * 1024) { // 5MB máximo
                    this.adjuntosFiles.push(file);
                } else {
                    alert(`El archivo ${file.name} es demasiado grande. Máximo 5MB.`);
                }
            });
            event.target.value = '';
        },
        removeFile(index) {
            this.adjuntosFiles.splice(index, 1);
        },
        removeExistingAdjunto(adjuntoId, index) {
            if (confirm('¿Está seguro de que desea eliminar este archivo? Esta acción no se puede deshacer.')) {
                // Eliminar del servidor
                axios.delete(`/contabilidad/asientos-adjuntos/${adjuntoId}`)
                    .then(response => {
                        if (response.data.success) {
                            // Eliminar de la lista local
                            this.adjuntosExistentes.splice(index, 1);
                            alert('Archivo eliminado exitosamente');
                        } else {
                            alert('Error al eliminar el archivo: ' + (response.data.message || 'Error desconocido'));
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting adjunto:', error);
                        alert('Error al eliminar el archivo');
                    });
            }
        },
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        onDebitoChanged(index, event) {
            const value = parseFloat(event.target.value) || 0;
            if (value > 0) {
                // Si débito > 0, poner crédito en 0
                this.form.detalles[index].credito = '0.00';
            }
        },
        onCreditoChanged(index, event) {
            const value = parseFloat(event.target.value) || 0;
            if (value > 0) {
                // Si crédito > 0, poner débito en 0
                this.form.detalles[index].debito = '0.00';
            }
        },
        populateSelectForNewRow(index) {
            console.log(`Populating selects for new row ${index}...`);

            this.$nextTick(() => {
                // Popular select de cuenta contable para la nueva fila
                const cuentaSelectRef = `cuentaSelect${index}`;
                let cuentaSelect = this.$refs[cuentaSelectRef];

                if (Array.isArray(cuentaSelect)) {
                    cuentaSelect = cuentaSelect[0];
                }

                if (cuentaSelect) {
                    cuentaSelect.innerHTML = '<option value="">Seleccionar cuenta</option>';
                    this.cuentasContables.forEach(cuenta => {
                        const option = document.createElement('option');
                        option.value = cuenta.id;
                        option.textContent = this.getCuentaDisplayText(cuenta);
                        cuentaSelect.appendChild(option);
                    });
                }

                // Popular select de tercero para la nueva fila
                const terceroSelectRef = `terceroSelect${index}`;
                let terceroSelect = this.$refs[terceroSelectRef];

                if (Array.isArray(terceroSelect)) {
                    terceroSelect = terceroSelect[0];
                }

                if (terceroSelect) {
                    terceroSelect.innerHTML = '<option value="">Seleccionar tercero</option>';
                    this.terceros.forEach(tercero => {
                        const option = document.createElement('option');
                        option.value = tercero.id;
                        option.textContent = this.getTerceroDisplayText(tercero);
                        terceroSelect.appendChild(option);
                    });
                }
            });
        },
        debugAsientoData() {
            console.log('=== DEBUG ASIENTO DATA ===');
            console.log('Estado actual:', this.form.estado);
            console.log('Estado type:', typeof this.form.estado);
            console.log('ID del asiento:', this.form.id);
            console.log('Is estado acceptable?', ['borrador', 'draft', 'BORRADOR', 'DRAFT', null, undefined, ''].includes(this.form.estado));
            console.log('=== END DEBUG ===');

            // También mostrar alerta para que sea visible sin consola
            alert(`Estado: "${this.form.estado}" (${typeof this.form.estado})\nID: ${this.form.id}\nEs acceptable: ${['borrador', 'draft', 'BORRADOR', 'DRAFT', null, undefined, ''].includes(this.form.estado)}`);
        }
    }
}
</script>

<style scoped>
.asientos-table tbody tr td {
    border-top: none !important;
    border-bottom: none !important;
}

.asientos-table thead th {
    border-bottom: 1px solid #dee2e6;
}

.balance-info {
    padding: 15px;
    border-radius: 5px;
    background-color: #f8f9fa;
}

.balance-info.balance-balanceado {
    background-color: #d4edda;
    color: #155724;
}

.balance-info.balance-desbalanceado {
    background-color: #f8d7da;
    color: #721c24;
}

/* Estilos para la sección de adjuntos */
.custom-file-label {
    cursor: pointer;
    border: 2px dashed #007bff;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.custom-file-label:hover {
    border-color: #0056b3;
    background-color: #e9ecef;
}

.custom-file-input:focus + .custom-file-label {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.font-weight-medium {
    font-weight: 500;
}

.list-group-item {
    transition: background-color 0.2s ease;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}
</style>
