<template>
    <form @submit.prevent="saveAsiento">
        <div class="row">
            <!-- Información básica del asiento -->
            <div class="col-md-8">
                <h5>Información del Asiento</h5>

                <div class="form-group">
                    <label>Tipo de Comprobante *</label>
                    <select ref="tipoComprobanteSelect"
                            class="form-control select2-search"
                            required>
                        <option value="">Seleccionar tipo de comprobante</option>
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

            <!-- Resumen Balance -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fa fa-calculator"></i> Resumen Balance</h6>
                    </div>
                    <div class="card-body">
                        <div class="balance-info" :class="balanceClass">
                            <div class="mb-2">
                                <strong>Total Débitos:</strong><br>
                                $<span v-text="formatNumber(totalDebitos)"></span>
                            </div>
                            <div class="mb-2">
                                <strong>Total Créditos:</strong><br>
                                $<span v-text="formatNumber(totalCreditos)"></span>
                            </div>
                            <div class="mb-2">
                                <strong>Diferencia:</strong><br>
                                $<span v-text="formatNumber(diferencia)"></span>
                            </div>
                            <div class="text-center mt-3">
                                <i :class="balanceIcon"></i>
                                <strong v-text="balanceText"></strong>
                            </div>
                        </div>

                        <button type="submit"
                                class="btn btn-primary btn-block mt-3"
                                :disabled="saving || !balanceado">
                            <i v-if="saving" class="fa fa-spinner fa-spin"></i>
                            <i v-else class="fa fa-save"></i>
                            {{ saving ? 'Guardando...' : 'Guardar Asiento' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalles del asiento -->
        <div class="row mt-4">
            <div class="col-md-8">
                <h5>Detalles del Asiento</h5>

                <div class="table-responsive">
                    <table class="table table-sm asientos-table">
                        <thead>
                            <tr>
                                <th width="30%">Cuenta Contable *</th>
                                <th width="20%">Tercero</th>
                                <th width="12%">Débito</th>
                                <th width="12%">Crédito</th>
                                <th width="18%">Concepto</th>
                                <th width="8%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(detalle, index) in form.detalles" :key="index">
                                <td>
                                    <select :ref="'cuentaSelect' + index"
                                            class="form-control form-control-sm cuenta-select"
                                            :data-index="index"
                                            required>
                                        <option value="">Seleccionar cuenta</option>
                                    </select>
                                </td>
                                <td>
                                    <select v-if="detalle.requiere_tercero"
                                            v-model="detalle.tercero_id"
                                            class="form-control form-control-sm">
                                        <option value="">Sin tercero</option>
                                        <option v-for="tercero in terceros"
                                                :key="tercero.id"
                                                :value="tercero.id"
                                                v-text="tercero.name + ' (' + tercero.number + ')'">
                                        </option>
                                    </select>
                                    <span v-else class="text-muted small">N/A</span>
                                </td>
                                <td>
                                    <input type="number"
                                           v-model="detalle.debito"
                                           @input="onDebitoChanged(index)"
                                           @focus="onInputFocus"
                                           class="form-control form-control-sm"
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00">
                                </td>
                                <td>
                                    <input type="number"
                                           v-model="detalle.credito"
                                           @input="onCreditoChanged(index)"
                                           @focus="onInputFocus"
                                           class="form-control form-control-sm"
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00">
                                </td>
                                <td>
                                    <input type="text"
                                           v-model="detalle.concepto"
                                           class="form-control form-control-sm"
                                           placeholder="Concepto del detalle">
                                </td>
                                <td class="text-center">
                                    <button type="button"
                                            @click="removeDetalle(index)"
                                            class="btn btn-danger btn-sm"
                                            v-if="form.detalles.length > 2"
                                            title="Eliminar línea">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-2">
                    <button type="button"
                            @click="addDetalle"
                            class="btn btn-success btn-sm">
                        <i class="fa fa-plus"></i> Agregar Detalle
                    </button>
                </div>
            </div>

            <!-- Archivos Adjuntos -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fa fa-paperclip"></i> Archivos Adjuntos</h6>
                    </div>
                    <div class="card-body">
                        <input type="file"
                               ref="fileInput"
                               @change="onFilesSelected"
                               class="form-control-file"
                               multiple
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                               style="display: none;">
                        <button type="button"
                                @click="$refs.fileInput.click()"
                                class="btn btn-outline-primary btn-block">
                            <i class="fa fa-upload"></i> Seleccionar Archivos
                        </button>
                        <small class="form-text text-muted mt-2">Máximo 5MB por archivo</small>

                        <!-- Lista de archivos seleccionados -->
                        <div v-if="adjuntosFiles.length > 0" class="mt-3">
                            <small class="text-muted">Archivos seleccionados:</small>
                            <div class="mt-2">
                                <div v-for="(file, index) in adjuntosFiles"
                                     :key="index"
                                     class="d-flex justify-content-between align-items-center p-2 border rounded mb-1">
                                    <div class="flex-grow-1">
                                        <small><i class="fa fa-file text-muted mr-1"></i>{{ file.name }}</small><br>
                                        <small class="text-muted">{{ formatFileSize(file.size) }}</small>
                                    </div>
                                    <button type="button"
                                            @click="removeFile(index)"
                                            class="btn btn-danger btn-sm ml-2">
                                        <i class="fa fa-times"></i>
                                    </button>
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
            // Si no hay montos ingresados, considerar como neutral (no mostrar error)
            const hayDatos = this.totalDebitos > 0 || this.totalCreditos > 0;
            if (!hayDatos) return true; // Neutral cuando no hay datos

            return this.diferencia < 0.01;
        },
        balanceClass() {
            const hayDatos = this.totalDebitos > 0 || this.totalCreditos > 0;
            if (!hayDatos) return ''; // Sin clase especial cuando no hay datos

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
        await this.loadTiposComprobantes();
        await this.loadCuentasContables();
        this.loadTerceros();

        // Esperar a que Select2 esté disponible y luego inicializar
        this.$nextTick(() => {
            this.waitForSelect2AndInitialize();
        });
    },
    methods: {
        getCuentaById(id) {
            return this.cuentasContables.find(cuenta => cuenta.id == id) || null;
        },
        async loadTiposComprobantes() {
            try {
                const response = await axios.get('/contabilidad/asientos-contables/tipos-comprobantes');
                if (response.data.success) {
                    this.tiposComprobantes = response.data.data;
                    this.$nextTick(() => {
                        if (this.$refs.tipoComprobanteSelect) {
                            this.initTipoComprobanteSelect2();
                        }
                    });
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
                    this.$nextTick(() => {
                        this.initCuentasContablesSelect2();
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
                }
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
                if (response.data.success) {
                    this.proximoConsecutivo = response.data.data.proximo_consecutivo;
                }
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
            this.updateCuentasContablesSelect2();
        },
        removeDetalle(index) {
            this.form.detalles.splice(index, 1);
        },
        async saveAsiento() {
            const hayDatos = this.totalDebitos > 0 || this.totalCreditos > 0;

            if (!hayDatos) {
                alert('Debe ingresar al menos un monto en débito o crédito.');
                return;
            }

            if (!this.balanceado) {
                alert('El asiento debe estar balanceado para poder guardarlo.');
                return;
            }

            this.saving = true;
            try {
                const response = await axios.post('/contabilidad/asientos-contables', this.form);
                if (response.data.success) {
                    alert('Asiento guardado exitosamente');
                    window.location.href = '/contabilidad/asientos-contables';
                } else {
                    alert('Error: ' + response.data.message);
                }
            } catch (error) {
                console.error('Error saving asiento:', error);
                const message = error.response?.data?.message || 'Error al guardar el asiento';
                alert('Error: ' + message);
            } finally {
                this.saving = false;
            }
        },
        waitForSelect2AndInitialize(attempts = 0) {
            if (typeof $ === 'undefined') {
                if (attempts < 50) {
                    setTimeout(() => {
                        this.waitForSelect2AndInitialize(attempts + 1);
                    }, 100);
                }
                return;
            }

            if (typeof $.fn.select2 === 'undefined') {
                if (attempts < 50) {
                    setTimeout(() => {
                        this.waitForSelect2AndInitialize(attempts + 1);
                    }, 100);
                } else {
                    this.initializeWithoutSelect2();
                }
                return;
            }

            this.initializeSelect2();
        },
        initializeSelect2() {
            this.initTipoComprobanteSelect2();
            this.initCuentasContablesSelect2();
        },
        initializeWithoutSelect2() {
            this.initBasicSelects();
        },
        initBasicSelects() {
            const vm = this;
            const $tipoSelect = $(this.$refs.tipoComprobanteSelect);
            if ($tipoSelect.length) {
                $tipoSelect.empty().append('<option value="">Seleccionar tipo de comprobante</option>');
                this.tiposComprobantes.forEach(tipo => {
                    $tipoSelect.append(new Option(tipo.codigo + ' - ' + tipo.nombre, tipo.id));
                });
                $tipoSelect.on('change', function() {
                    vm.form.tipo_comprobante_id = $(this).val();
                    vm.onTipoComprobanteChanged();
                });
            }

            $('.cuenta-select').each(function() {
                const $select = $(this);
                const index = parseInt($select.data('index'));
                $select.empty().append('<option value="">Seleccionar cuenta</option>');
                vm.cuentasContables.forEach(cuenta => {
                    $select.append(new Option(cuenta.codigo + ' - ' + cuenta.descripcion, cuenta.id));
                });
                $select.on('change', function() {
                    vm.form.detalles[index].cuenta_contable_id = $(this).val();
                    vm.onCuentaChanged(index);
                });
            });
        },
        initTipoComprobanteSelect2() {
            if (typeof $.fn.select2 === 'undefined') return;

            const vm = this;
            const $select = $(this.$refs.tipoComprobanteSelect);
            if (!$select.length) return;

            $select.empty().append('<option value="">Seleccionar tipo de comprobante</option>');
            this.tiposComprobantes.forEach(tipo => {
                $select.append(new Option(tipo.codigo + ' - ' + tipo.nombre, tipo.id));
            });

            $select.select2({
                theme: 'bootstrap',
                placeholder: 'Buscar tipo de comprobante...',
                allowClear: true,
                matcher: function(params, data) {
                    if ($.trim(params.term) === '') return data;
                    if (typeof data.text === 'undefined') return null;
                    if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) return data;
                    return null;
                }
            }).on('change', function() {
                vm.form.tipo_comprobante_id = $(this).val();
                vm.onTipoComprobanteChanged();
            });
        },
        initCuentasContablesSelect2() {
            if (typeof $.fn.select2 === 'undefined') {
                this.initBasicCuentasSelects();
                return;
            }

            const vm = this;
            $('.cuenta-select').each(function() {
                const $select = $(this);
                const index = parseInt($select.data('index'));

                const cuentasUsadas = vm.form.detalles
                    .map((detalle, idx) => idx !== index ? detalle.cuenta_contable_id : null)
                    .filter(id => id && id !== '');

                $select.empty().append('<option value="">Seleccionar cuenta</option>');
                vm.cuentasContables.forEach(cuenta => {
                    if (!cuentasUsadas.includes(cuenta.id.toString())) {
                        $select.append(new Option(cuenta.codigo + ' - ' + cuenta.descripcion, cuenta.id));
                    }
                });

                const currentValue = vm.form.detalles[index].cuenta_contable_id;
                if (currentValue && !$select.find('option[value="' + currentValue + '"]').length) {
                    const currentCuenta = vm.cuentasContables.find(c => c.id.toString() === currentValue.toString());
                    if (currentCuenta) {
                        $select.append(new Option(currentCuenta.codigo + ' - ' + currentCuenta.descripcion, currentCuenta.id));
                    }
                }

                $select.select2({
                    theme: 'bootstrap',
                    placeholder: 'Buscar cuenta contable...',
                    allowClear: true,
                    matcher: function(params, data) {
                        if ($.trim(params.term) === '') return data;
                        if (typeof data.text === 'undefined') return null;
                        if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) return data;
                        return null;
                    }
                }).on('change', function() {
                    vm.form.detalles[index].cuenta_contable_id = $(this).val();
                    vm.onCuentaChanged(index);
                    vm.$nextTick(() => {
                        vm.updateCuentasContablesSelect2();
                    });
                });

                if (currentValue) {
                    $select.val(currentValue).trigger('change.select2');
                }
            });
        },
        updateCuentasContablesSelect2() {
            this.$nextTick(() => {
                if (typeof $.fn.select2 !== 'undefined') {
                    this.initCuentasContablesSelect2();
                } else {
                    this.initBasicCuentasSelects();
                }
            });
        },
        initBasicCuentasSelects() {
            const vm = this;
            $('.cuenta-select').each(function() {
                const $select = $(this);
                const index = parseInt($select.data('index'));

                if ($select.find('option').length <= 1) {
                    const cuentasUsadas = vm.form.detalles
                        .map((detalle, idx) => idx !== index ? detalle.cuenta_contable_id : null)
                        .filter(id => id && id !== '');

                    $select.empty().append('<option value="">Seleccionar cuenta</option>');
                    vm.cuentasContables.forEach(cuenta => {
                        if (!cuentasUsadas.includes(cuenta.id.toString())) {
                            $select.append(new Option(cuenta.codigo + ' - ' + cuenta.descripcion, cuenta.id));
                        }
                    });

                    const currentValue = vm.form.detalles[index].cuenta_contable_id;
                    if (currentValue && !$select.find('option[value="' + currentValue + '"]').length) {
                        const currentCuenta = vm.cuentasContables.find(c => c.id.toString() === currentValue.toString());
                        if (currentCuenta) {
                            $select.append(new Option(currentCuenta.codigo + ' - ' + currentCuenta.descripcion, currentCuenta.id));
                        }
                    }

                    if (currentValue) {
                        $select.val(currentValue);
                    }

                    $select.off('change.cuentaSelect').on('change.cuentaSelect', function() {
                        vm.form.detalles[index].cuenta_contable_id = $(this).val();
                        vm.onCuentaChanged(index);
                        vm.$nextTick(() => {
                            vm.updateCuentasContablesSelect2();
                        });
                    });
                }
            });
        },
        formatNumber(number) {
            return new Intl.NumberFormat('es-CO', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(number);
        },
        onDebitoChanged(index) {
            if (this.form.detalles[index].debito && parseFloat(this.form.detalles[index].debito) > 0) {
                this.form.detalles[index].credito = '0.00';
            }
        },
        onCreditoChanged(index) {
            if (this.form.detalles[index].credito && parseFloat(this.form.detalles[index].credito) > 0) {
                this.form.detalles[index].debito = '0.00';
            }
        },
        onInputFocus(event) {
            event.target.select();
        },
        onFilesSelected(event) {
            const files = Array.from(event.target.files);
            files.forEach(file => {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Tipo de archivo no permitido: ' + file.name);
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    alert('El archivo es muy grande (máximo 5MB): ' + file.name);
                    return;
                }

                this.adjuntosFiles.push(file);
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
