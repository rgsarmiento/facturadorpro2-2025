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
                                class="btn btn-primary btn-block"
                                :disabled="saving || !balanceado">
                            <i v-if="saving" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-save"></i>
                            {{ saving ? 'Guardando...' : 'Guardar Asiento' }}
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
                                <td>
                                    <select v-if="detalle.requiere_tercero"
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
                                    <span v-else class="text-muted">No aplica</span>
                                </td>

                                <!-- Concepto -->
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
                                           class="form-control text-right"
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00">
                                </td>

                                <!-- Crédito -->
                                <td>
                                    <input type="number"
                                           v-model="detalle.credito"
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
                <h5>Adjuntos (Opcional)</h5>
                <div class="form-group">
                    <input type="file"
                           @change="onFilesSelected"
                           class="form-control-file"
                           multiple
                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                    <small class="form-text text-muted">
                        Formatos permitidos: PDF, imágenes, documentos de Word/Excel. Máximo 5MB por archivo.
                    </small>
                </div>

                <div v-if="adjuntosFiles.length > 0" class="mt-3">
                    <h6>Archivos seleccionados:</h6>
                    <div class="list-group">
                        <div v-for="(file, index) in adjuntosFiles"
                             :key="index"
                             class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-file"></i>
                                {{ file.name }} ({{ formatFileSize(file.size) }})
                            </div>
                            <button type="button"
                                    @click="removeFile(index)"
                                    class="btn btn-sm btn-outline-danger">
                                <i class="fa fa-times"></i>
                            </button>
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
            adjuntosFiles: []
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
            // Implementar lógica de guardado
            console.log('Saving asiento:', this.form);
        },
        addDetalle() {
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

            // Reinicializar selects después de agregar nueva fila
            this.$nextTick(() => {
                this.populateCuentasContablesOptions();
                this.populateTercerosOptions();
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

            // Cargar datos básicos del asiento
            this.form.id = asiento.id;
            this.form.tipo_comprobante_id = asiento.tipo_comprobante_id || '';

            let fechaAsiento = asiento.fecha_asiento || new Date().toISOString().substr(0, 10);
            if (fechaAsiento.includes(' ')) {
                fechaAsiento = fechaAsiento.split(' ')[0];
            }
            this.form.fecha_asiento = fechaAsiento;
            this.form.concepto = asiento.concepto || '';

            // Cargar detalles del asiento
            if (asiento.detalles && asiento.detalles.length > 0) {
                this.form.detalles = asiento.detalles.map((detalle) => {
                    let cuentaContable = null;
                    let requiereTercero = false;

                    if (detalle.cuenta_contable) {
                        cuentaContable = detalle.cuenta_contable;
                        requiereTercero = detalle.cuenta_contable.requiere_tercero || false;
                    }

                    return {
                        cuenta_contable_id: detalle.cuenta_contable_id || '',
                        cuenta_contable: cuentaContable,
                        tercero_id: detalle.tercero_id || '',
                        tercero: detalle.tercero || null,
                        requiere_tercero: requiereTercero,
                        debito: detalle.debito || '0.00',
                        credito: detalle.credito || '0.00',
                        concepto: detalle.concepto || ''
                    };
                });
            }

            // Inicializar Select2 después de cargar los datos
            this.$nextTick(() => {
                this.initializeSelect2AfterDataLoad();
            });
        },
        initializeSelect2AfterDataLoad() {
            console.log('initializeSelect2AfterDataLoad called');

            if (this.tiposComprobantes.length === 0) {
                console.log('Tipos comprobantes not loaded yet, waiting...');
                setTimeout(() => {
                    this.initializeSelect2AfterDataLoad();
                }, 300);
                return;
            }

            setTimeout(() => {
                console.log('Initializing select2 components for edit mode...');
                this.waitForSelect2(() => {
                    this.initTipoComprobanteSelect2();

                    setTimeout(() => {
                        if (this.form.tipo_comprobante_id) {
                            console.log('Setting tipo_comprobante_id after init:', this.form.tipo_comprobante_id);
                            const $tipoSelect = $(this.$refs.tipoComprobanteSelect);
                            if ($tipoSelect.length && $tipoSelect.hasClass('select2-hidden-accessible')) {
                                $tipoSelect.val(this.form.tipo_comprobante_id).trigger('change');
                            }
                        }
                    }, 200);

                    setTimeout(() => {
                        this.setAccountValuesAfterInit();
                    }, 400);
                });
            }, 200);
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
                    const selectRef = `terceroSelect${index}`;
                    console.log(`Looking for ref: ${selectRef}`);

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
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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
</style>
