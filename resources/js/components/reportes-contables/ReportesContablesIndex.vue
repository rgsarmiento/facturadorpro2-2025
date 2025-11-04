<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a></h2>
            <ol class="breadcrumbs">
                <li><a href="/dashboard">Inicio</a></li>
                <li><a href="#" @click.prevent>Contabilidad</a></li>
                <li class="active"><span>Reportes Contables</span></li>
            </ol>
        </div>

        <div class="card mb-0">
            <div class="card-header bg-info">
                <h3 class="my-0">Reportes Contables</h3>
            </div>
            <div class="card-body">

        <!-- Tabs de reportes -->
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'balance-prueba' }" @click="activeTab = 'balance-prueba'" href="javascript:void(0)">
                    <i class="fas fa-balance-scale"></i> Balance de Prueba
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'balance-general' }" @click="activeTab = 'balance-general'" href="javascript:void(0)">
                    <i class="fas fa-chart-pie"></i> Balance General
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'mayor-auxiliar' }" @click="activeTab = 'mayor-auxiliar'" href="javascript:void(0)">
                    <i class="fas fa-book"></i> Mayor Auxiliar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'libro-diario' }" @click="activeTab = 'libro-diario'" href="javascript:void(0)">
                    <i class="fas fa-book-open"></i> Libro Diario
                </a>
            </li>
        </ul>

        <!-- Tab content -->
        <div class="tab-content">
            <!-- Balance de Prueba -->
            <div v-show="activeTab === 'balance-prueba'">
                <!-- Filtros -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Fecha Inicio</label>
                        <input type="date" v-model="balancePruebaFilters.fecha_inicio" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label>Fecha Fin</label>
                        <input type="date" v-model="balancePruebaFilters.fecha_fin" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label>Nivel</label>
                        <select v-model="balancePruebaFilters.nivel" class="form-control">
                            <option value="">Todos</option>
                            <option value="1">Nivel 1</option>
                            <option value="2">Nivel 2</option>
                            <option value="3">Nivel 3</option>
                            <option value="4">Nivel 4</option>
                            <option value="5">Nivel 5</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>&nbsp;</label><br>
                        <button @click="loadBalancePrueba" class="btn btn-primary" :disabled="loadingBP">
                            <i v-if="loadingBP" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-search"></i>
                            Generar Reporte
                        </button>
                        <button @click="exportBalancePrueba" class="btn btn-success ml-2" :disabled="!balancePruebaData">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                    </div>
                </div>

                        <!-- Resultado -->
                        <div v-if="loadingBP" class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                            <div class="mt-2">Generando reporte...</div>
                        </div>

                        <div v-if="balancePruebaData && !loadingBP">
                            <div class="alert alert-info">
                                <strong>Período:</strong> {{ balancePruebaData.fecha_inicio }} al {{ balancePruebaData.fecha_fin }}
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Código</th>
                                            <th>Cuenta</th>
                                            <th>Naturaleza</th>
                                            <th class="text-right">Débito</th>
                                            <th class="text-right">Crédito</th>
                                            <th class="text-right">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="cuenta in balancePruebaData.cuentas" :key="cuenta.id">
                                            <td>{{ cuenta.codigo }}</td>
                                            <td>{{ cuenta.nombre }}</td>
                                            <td>
                                                <span class="badge" :class="cuenta.naturaleza === 'debito' ? 'badge-primary' : 'badge-info'">
                                                    {{ cuenta.naturaleza }}
                                                </span>
                                            </td>
                                            <td class="text-right">${{ formatNumber(cuenta.debito) }}</td>
                                            <td class="text-right">${{ formatNumber(cuenta.credito) }}</td>
                                            <td class="text-right"><strong>${{ formatNumber(cuenta.saldo) }}</strong></td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="thead-light">
                                        <tr>
                                            <th colspan="3" class="text-right">TOTALES:</th>
                                            <th class="text-right">${{ formatNumber(balancePruebaData.totales.total_debito) }}</th>
                                            <th class="text-right">${{ formatNumber(balancePruebaData.totales.total_credito) }}</th>
                                            <th class="text-right">
                                                <span :class="balancePruebaData.totales.diferencia === 0 ? 'text-success' : 'text-danger'">
                                                    ${{ formatNumber(balancePruebaData.totales.diferencia) }}
                                                </span>
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
            </div>

            <!-- Balance General -->
            <div v-show="activeTab === 'balance-general'">
                <!-- Filtros -->
                <div class="row mb-3">
                    <div class="col-md-3">
                                <label>Fecha de Corte</label>
                                <input type="date" v-model="balanceGeneralFilters.fecha_corte" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <button @click="loadBalanceGeneral" class="btn btn-primary mt-4" :disabled="loadingBG">
                                    <i v-if="loadingBG" class="fas fa-spinner fa-spin"></i>
                                    <i v-else class="fas fa-search"></i>
                                    Generar Reporte
                                </button>
                                <button @click="exportBalanceGeneral" class="btn btn-success mt-4 ml-2" :disabled="!balanceGeneralData">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>

                        <!-- Resultado -->
                        <div v-if="loadingBG" class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                            <div class="mt-2">Generando reporte...</div>
                        </div>

                        <div v-if="balanceGeneralData && !loadingBG">
                            <div class="alert alert-info">
                                <strong>Fecha de Corte:</strong> {{ balanceGeneralData.fecha_corte }}
                            </div>

                            <div class="row">
                                <!-- Activos -->
                                <div class="col-md-6">
                                    <h5 class="text-primary">ACTIVOS</h5>
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr v-for="cuenta in balanceGeneralData.activos.cuentas" :key="cuenta.codigo">
                                                <td>{{ cuenta.codigo }}</td>
                                                <td>{{ cuenta.nombre }}</td>
                                                <td class="text-right">${{ formatNumber(cuenta.saldo) }}</td>
                                            </tr>
                                            <tr class="table-primary">
                                                <th colspan="2">TOTAL ACTIVOS</th>
                                                <th class="text-right">${{ formatNumber(balanceGeneralData.activos.total) }}</th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pasivos y Patrimonio -->
                                <div class="col-md-6">
                                    <h5 class="text-danger">PASIVOS</h5>
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr v-for="cuenta in balanceGeneralData.pasivos.cuentas" :key="cuenta.codigo">
                                                <td>{{ cuenta.codigo }}</td>
                                                <td>{{ cuenta.nombre }}</td>
                                                <td class="text-right">${{ formatNumber(cuenta.saldo) }}</td>
                                            </tr>
                                            <tr class="table-danger">
                                                <th colspan="2">TOTAL PASIVOS</th>
                                                <th class="text-right">${{ formatNumber(balanceGeneralData.pasivos.total) }}</th>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <h5 class="text-success mt-3">PATRIMONIO</h5>
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr v-for="cuenta in balanceGeneralData.patrimonio.cuentas" :key="cuenta.codigo">
                                                <td>{{ cuenta.codigo }}</td>
                                                <td>{{ cuenta.nombre }}</td>
                                                <td class="text-right">${{ formatNumber(cuenta.saldo) }}</td>
                                            </tr>
                                            <tr class="table-success">
                                                <th colspan="2">TOTAL PATRIMONIO</th>
                                                <th class="text-right">${{ formatNumber(balanceGeneralData.patrimonio.total) }}</th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Validación -->
                            <div class="alert" :class="balanceGeneralData.validacion.balanced ? 'alert-success' : 'alert-danger'">
                                <h6>Validación Contable</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Activos:</strong> ${{ formatNumber(balanceGeneralData.validacion.activos) }}
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Pasivos + Patrimonio:</strong> ${{ formatNumber(balanceGeneralData.validacion.pasivos_patrimonio) }}
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Diferencia:</strong>
                                        <span :class="balanceGeneralData.validacion.balanced ? 'text-success' : 'text-danger'">
                                            ${{ formatNumber(balanceGeneralData.validacion.diferencia) }}
                                        </span>
                                        <i v-if="balanceGeneralData.validacion.balanced" class="fas fa-check text-success ml-2"></i>
                                        <i v-else class="fas fa-times text-danger ml-2"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
            </div>

            <!-- Mayor Auxiliar -->
            <div v-show="activeTab === 'mayor-auxiliar'">
                <!-- Filtros -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label>Código de Cuenta <span class="text-danger">*</span></label>
                                <input type="text" v-model="mayorAuxiliarFilters.cuenta_codigo"
                                       class="form-control" placeholder="Ej: 110505">
                            </div>
                            <div class="col-md-2">
                                <label>Fecha Inicio</label>
                                <input type="date" v-model="mayorAuxiliarFilters.fecha_inicio" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label>Fecha Fin</label>
                                <input type="date" v-model="mayorAuxiliarFilters.fecha_fin" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <div class="form-check mt-2">
                                    <input type="checkbox" v-model="mayorAuxiliarFilters.incluir_tercero"
                                           class="form-check-input" id="incluirTercero">
                                    <label class="form-check-label" for="incluirTercero">
                                        Incluir tercero
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button @click="loadMayorAuxiliar" class="btn btn-primary mt-4"
                                        :disabled="loadingMA || !mayorAuxiliarFilters.cuenta_codigo">
                                    <i v-if="loadingMA" class="fas fa-spinner fa-spin"></i>
                                    <i v-else class="fas fa-search"></i>
                                    Generar
                                </button>
                                <button @click="exportMayorAuxiliar" class="btn btn-success mt-4 ml-2" :disabled="!mayorAuxiliarData">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>

                        <!-- Resultado -->
                        <div v-if="loadingMA" class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                            <div class="mt-2">Generando reporte...</div>
                        </div>

                        <div v-if="mayorAuxiliarData && !loadingMA">
                            <div class="alert alert-info">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Cuenta:</strong> {{ mayorAuxiliarData.cuenta.codigo }} - {{ mayorAuxiliarData.cuenta.nombre }}<br>
                                        <strong>Naturaleza:</strong> {{ mayorAuxiliarData.cuenta.naturaleza }}
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Período:</strong> {{ mayorAuxiliarData.fecha_inicio }} al {{ mayorAuxiliarData.fecha_fin }}<br>
                                        <strong>Saldo Inicial:</strong> ${{ formatNumber(mayorAuxiliarData.saldo_inicial) }}
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Comprobante</th>
                                            <th>Concepto</th>
                                            <th v-if="mayorAuxiliarFilters.incluir_tercero">Tercero</th>
                                            <th class="text-right">Débito</th>
                                            <th class="text-right">Crédito</th>
                                            <th class="text-right">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(mov, index) in mayorAuxiliarData.movimientos" :key="index">
                                            <td>{{ mov.fecha }}</td>
                                            <td>{{ mov.comprobante }}</td>
                                            <td>{{ mov.concepto }}</td>
                                            <td v-if="mayorAuxiliarFilters.incluir_tercero">{{ mov.tercero || '—' }}</td>
                                            <td class="text-right">${{ formatNumber(mov.debito) }}</td>
                                            <td class="text-right">${{ formatNumber(mov.credito) }}</td>
                                            <td class="text-right"><strong>${{ formatNumber(mov.saldo) }}</strong></td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="thead-light">
                                        <tr>
                                            <th :colspan="mayorAuxiliarFilters.incluir_tercero ? 4 : 3" class="text-right">TOTALES:</th>
                                            <th class="text-right">${{ formatNumber(mayorAuxiliarData.totales.total_debito) }}</th>
                                            <th class="text-right">${{ formatNumber(mayorAuxiliarData.totales.total_credito) }}</th>
                                            <th class="text-right">${{ formatNumber(mayorAuxiliarData.totales.saldo_final) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
            </div>

            <!-- Libro Diario -->
            <div v-show="activeTab === 'libro-diario'">
                <!-- Filtros -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label>Fecha Inicio</label>
                                <input type="date" v-model="libroDiarioFilters.fecha_inicio" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label>Fecha Fin</label>
                                <input type="date" v-model="libroDiarioFilters.fecha_fin" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label>Tipo de Comprobante</label>
                                <select v-model="libroDiarioFilters.tipo_comprobante_id" class="form-control">
                                    <option value="">Todos</option>
                                    <option v-for="tipo in tiposComprobantes" :key="tipo.id" :value="tipo.id">
                                        {{ tipo.nombre }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button @click="loadLibroDiario" class="btn btn-primary mt-4" :disabled="loadingLD">
                                    <i v-if="loadingLD" class="fas fa-spinner fa-spin"></i>
                                    <i v-else class="fas fa-search"></i>
                                    Generar
                                </button>
                                <button @click="exportLibroDiario" class="btn btn-success mt-4 ml-2" :disabled="!libroDiarioData">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>

                        <!-- Resultado -->
                        <div v-if="loadingLD" class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                            <div class="mt-2">Generando reporte...</div>
                        </div>

                        <div v-if="libroDiarioData && !loadingLD">
                            <div class="alert alert-info">
                                <strong>Período:</strong> {{ libroDiarioData.fecha_inicio }} al {{ libroDiarioData.fecha_fin }}<br>
                                <strong>Total Asientos:</strong> {{ libroDiarioData.totales.total_asientos }}
                            </div>

                            <div v-for="asiento in libroDiarioData.asientos" :key="asiento.numero_comprobante" class="card mb-3">
                                <div class="card-header bg-light">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>{{ asiento.numero_comprobante }}</strong> - {{ asiento.tipo_comprobante }}
                                        </div>
                                        <div class="col-md-6 text-right">
                                            <strong>Fecha:</strong> {{ asiento.fecha }} |
                                            <span class="badge" :class="asiento.estado === 'CONFIRMADO' ? 'badge-success' : 'badge-warning'">
                                                {{ asiento.estado }}
                                            </span>
                                        </div>
                                    </div>
                                    <div><strong>Concepto:</strong> {{ asiento.concepto }}</div>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Cuenta</th>
                                                <th>Concepto</th>
                                                <th class="text-right">Débito</th>
                                                <th class="text-right">Crédito</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(detalle, idx) in asiento.detalles" :key="idx">
                                                <td>
                                                    <strong>{{ detalle.cuenta_codigo }}</strong><br>
                                                    <small>{{ detalle.cuenta_nombre }}</small>
                                                </td>
                                                <td>{{ detalle.concepto }}</td>
                                                <td class="text-right">${{ formatNumber(detalle.debito) }}</td>
                                                <td class="text-right">${{ formatNumber(detalle.credito) }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="thead-light">
                                            <tr>
                                                <th colspan="2" class="text-right">TOTAL ASIENTO:</th>
                                                <th class="text-right">${{ formatNumber(asiento.total_debito) }}</th>
                                                <th class="text-right">${{ formatNumber(asiento.total_credito) }}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- Totales finales -->
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong>Total Débitos:</strong> ${{ formatNumber(libroDiarioData.totales.total_debito) }}
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Total Créditos:</strong> ${{ formatNumber(libroDiarioData.totales.total_credito) }}
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Asientos:</strong> {{ libroDiarioData.totales.total_asientos }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
            </div>
        </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            // Control de tabs
            activeTab: 'balance-prueba',

            // Balance de Prueba
            balancePruebaFilters: {
                fecha_inicio: '',
                fecha_fin: '',
                nivel: ''
            },
            balancePruebaData: null,
            loadingBP: false,

            // Balance General
            balanceGeneralFilters: {
                fecha_corte: new Date().toISOString().split('T')[0]
            },
            balanceGeneralData: null,
            loadingBG: false,

            // Mayor Auxiliar
            mayorAuxiliarFilters: {
                cuenta_codigo: '',
                fecha_inicio: '',
                fecha_fin: '',
                incluir_tercero: false
            },
            mayorAuxiliarData: null,
            loadingMA: false,

            // Libro Diario
            libroDiarioFilters: {
                fecha_inicio: '',
                fecha_fin: '',
                tipo_comprobante_id: ''
            },
            libroDiarioData: null,
            loadingLD: false,

            tiposComprobantes: []
        }
    },
    mounted() {
        this.loadTiposComprobantes();
        this.setDefaultDates();
    },
    methods: {
        setDefaultDates() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);

            this.balancePruebaFilters.fecha_inicio = firstDay.toISOString().split('T')[0];
            this.balancePruebaFilters.fecha_fin = today.toISOString().split('T')[0];

            this.mayorAuxiliarFilters.fecha_inicio = firstDay.toISOString().split('T')[0];
            this.mayorAuxiliarFilters.fecha_fin = today.toISOString().split('T')[0];

            this.libroDiarioFilters.fecha_inicio = firstDay.toISOString().split('T')[0];
            this.libroDiarioFilters.fecha_fin = today.toISOString().split('T')[0];
        },
        loadTiposComprobantes() {
            axios.get('/contabilidad/asientos-contables/tipos-comprobantes')
                .then(response => {
                    this.tiposComprobantes = response.data.data;
                });
        },
        loadBalancePrueba() {
            this.loadingBP = true;
            const params = new URLSearchParams(this.balancePruebaFilters);

            axios.get(`/contabilidad/reportes-contables/balance-prueba?${params.toString()}`)
                .then(response => {
                    this.balancePruebaData = response.data.data;
                })
                .catch(error => {
                    this.$message.error('Error al generar reporte');
                    console.error(error);
                })
                .finally(() => {
                    this.loadingBP = false;
                });
        },
        loadBalanceGeneral() {
            this.loadingBG = true;
            const params = new URLSearchParams(this.balanceGeneralFilters);

            axios.get(`/contabilidad/reportes-contables/balance-general?${params.toString()}`)
                .then(response => {
                    this.balanceGeneralData = response.data.data;
                })
                .catch(error => {
                    this.$message.error('Error al generar reporte');
                    console.error(error);
                })
                .finally(() => {
                    this.loadingBG = false;
                });
        },
        loadMayorAuxiliar() {
            if (!this.mayorAuxiliarFilters.cuenta_codigo) {
                this.$message.warning('Debe especificar el código de cuenta');
                return;
            }

            this.loadingMA = true;
            const params = new URLSearchParams(this.mayorAuxiliarFilters);

            axios.get(`/contabilidad/reportes-contables/mayor-auxiliar?${params.toString()}`)
                .then(response => {
                    this.mayorAuxiliarData = response.data.data;
                })
                .catch(error => {
                    this.$message.error(error.response?.data?.message || 'Error al generar reporte');
                    console.error(error);
                })
                .finally(() => {
                    this.loadingMA = false;
                });
        },
        loadLibroDiario() {
            this.loadingLD = true;
            const params = new URLSearchParams(this.libroDiarioFilters);

            axios.get(`/contabilidad/reportes-contables/libro-diario?${params.toString()}`)
                .then(response => {
                    this.libroDiarioData = response.data.data;
                })
                .catch(error => {
                    this.$message.error('Error al generar reporte');
                    console.error(error);
                })
                .finally(() => {
                    this.loadingLD = false;
                });
        },
        exportBalancePrueba() {
            this.$message.info('Función de exportación pendiente de implementar');
        },
        exportBalanceGeneral() {
            this.$message.info('Función de exportación pendiente de implementar');
        },
        exportMayorAuxiliar() {
            this.$message.info('Función de exportación pendiente de implementar');
        },
        exportLibroDiario() {
            this.$message.info('Función de exportación pendiente de implementar');
        },
        formatNumber(value) {
            return new Intl.NumberFormat('es-CO', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(value || 0);
        }
    }
}
</script>
