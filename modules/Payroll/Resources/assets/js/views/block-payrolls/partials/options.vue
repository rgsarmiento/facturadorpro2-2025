<template>
    <el-dialog
        :title="titleDialog"
        :visible="showDialog"
        @open="loadData"
        width="40%"
        append-to-body
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        :show-close="false"
    >
        <div v-loading="loading">
            <div class="row" v-if="blockData">
                <div class="col-md-12">
                    <h5 class="mb-3">Información del Bloque</h5>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <strong>Fecha de Emisión:</strong> {{ blockData.date_of_issue }}
                        </div>
                        <div class="col-md-6">
                            <strong>Estado:</strong>
                            <span
                                class="badge ml-2"
                                :class="{
                                    'bg-secondary text-white': blockData.state_block_id === 1,
                                    'bg-success text-white': blockData.state_block_id === 5,
                                    'bg-danger text-white': blockData.state_block_id === 6
                                }"
                            >
                                {{ blockData.state_block_name || 'Sin estado' }}
                            </span>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <strong>Cantidad Empleados:</strong> {{ blockData.workers_quantity }}
                        </div>
                        <div class="col-md-6">
                            <strong>Período:</strong>
                            {{ formatPeriod(blockData.period_start_date, blockData.period_end_date) }}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Total Devengados:</strong><br>
                            ${{ getFormatDecimal(blockData.accrued_total) }}
                        </div>
                        <div class="col-md-4">
                            <strong>Total Deducciones:</strong><br>
                            ${{ getFormatDecimal(blockData.deductions_total) }}
                        </div>
                        <div class="col-md-4">
                            <strong>Valor Bloque:</strong><br>
                            ${{ getFormatDecimal(calculateBlockTotal(blockData.accrued_total, blockData.deductions_total)) }}
                        </div>
                    </div>

                    <!-- Sección de validaciones de empleados -->
                    <div v-if="blockData.block_payroll_json_responses && Object.keys(blockData.block_payroll_json_responses).length > 0" class="mt-4">
                        <h5 class="mb-3">Estado de Validación por Empleado</h5>
                        <div class="validation-summary mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <span class="badge bg-success">{{ getValidCount() }} Válidos</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="badge bg-danger">{{ getInvalidCount() }} Inválidos</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="badge bg-warning">{{ getPendingCount() }} Pendientes</span>
                                </div>
                            </div>
                        </div>
                        <div class="validation-details" style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Estado</th>
                                        <th>Método</th>
                                        <th>Procesado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(response, workerId) in blockData.block_payroll_json_responses" :key="workerId">
                                        <td>{{ response.worker_name }}</td>
                                        <td>
                                            <span
                                                class="badge"
                                                :class="{
                                                    'bg-success': response.is_valid === true,
                                                    'bg-danger': response.is_valid === false,
                                                    'bg-warning': response.is_valid === null || response.is_valid === undefined
                                                }"
                                            >
                                                {{ response.is_valid === true ? 'Válido' : response.is_valid === false ? 'Inválido' : 'Pendiente' }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ response.validation_method === 'direct_isValid' ? 'Directo' :
                                                   response.validation_method === 'zipkey_query' ? 'ZipKey' :
                                                   'Sin método' }}
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ formatDateTime(response.processed_at) }}
                                            </small>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <span slot="footer" class="dialog-footer">
            <el-button @click="clickClose">Cerrar</el-button>
            <el-button
                v-if="blockData && blockData.state_block_id !== 5"
                type="warning"
                icon="el-icon-upload"
                @click="sendPending"
                :loading="sendingPending"
            >
                Enviar faltantes
            </el-button>
            <el-button
                type="primary"
                icon="el-icon-download"
                @click="generatePDF"
                :loading="generatingPDF"
            >
                Generar PDF
            </el-button>
        </span>
    </el-dialog>
</template>

<script>
export default {
    props: ['showDialog', 'recordId'],

    data() {
        return {
            titleDialog: 'Opciones del Bloque de Nómina',
            loading: false,
            generatingPDF: false,
            sendingPending: false,
            resource: 'payroll/block-payrolls',
            blockData: null,
            errors: {}
        }
    },

    methods: {
        loadData() {
            if (!this.recordId) return;

            this.loading = true;
            this.$http.get(`/${this.resource}/${this.recordId}`)
                .then(response => {
                    this.blockData = response.data.data;
                    this.titleDialog = `Opciones - Bloque #${this.blockData.id}`;
                })
                .catch(error => {
                    console.error('Error loading block data:', error);
                    this.$message.error('Error al cargar los datos del bloque');
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        generatePDF() {
            if (!this.recordId) {
                this.$message.error('No se ha seleccionado un registro válido');
                return;
            }

            this.generatingPDF = true;

            this.$http.get(`/${this.resource}/generate-pdf/${this.recordId}`, {
                responseType: 'blob'
            })
            .then(response => {
                // Crear un blob con la respuesta
                const blob = new Blob([response.data], { type: 'application/pdf' });

                // Crear un enlace temporal para descargar
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = `bloque_nomina_${this.recordId}.pdf`;

                // Agregar al DOM, hacer click y remover
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                // Limpiar el objeto URL
                window.URL.revokeObjectURL(link.href);

                this.$message.success('PDF generado exitosamente');
            })
            .catch(error => {
                console.error('Error generando PDF:', error);
                if (error.response && error.response.data) {
                    // Si la respuesta es JSON, manejar el error
                    const reader = new FileReader();
                    reader.onload = () => {
                        try {
                            const errorData = JSON.parse(reader.result);
                            this.$message.error(errorData.message || 'Error al generar el PDF');
                        } catch (e) {
                            this.$message.error('Error al generar el PDF');
                        }
                    };
                    reader.readAsText(error.response.data);
                } else {
                    this.$message.error('Error al generar el PDF');
                }
            })
            .finally(() => {
                this.generatingPDF = false;
            });
        },

        sendPending() {
            if (!this.recordId) {
                this.$message.error('No se ha seleccionado un registro válido');
                return;
            }

            // Placeholder para la funcionalidad futura
            this.$message.info('Funcionalidad "Enviar faltantes" será implementada próximamente');

            // TODO: Implementar la lógica para enviar documentos faltantes
            // this.sendingPending = true;
            // this.$http.post(`/${this.resource}/send-pending/${this.recordId}`)
            //     .then(response => {
            //         this.$message.success('Documentos faltantes enviados exitosamente');
            //         // Recargar datos del bloque para actualizar estado
            //         this.loadData();
            //     })
            //     .catch(error => {
            //         console.error('Error enviando documentos faltantes:', error);
            //         this.$message.error('Error al enviar documentos faltantes');
            //     })
            //     .finally(() => {
            //         this.sendingPending = false;
            //     });
        },

        getFormatDecimal(value) {
            if (!value || isNaN(value)) return "0.00";
            const num = parseFloat(value);
            return num.toLocaleString("es-CO", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        calculateBlockTotal(accruedTotal, deductionsTotal) {
            const accrued = parseFloat(accruedTotal) || 0;
            const deductions = parseFloat(deductionsTotal) || 0;
            return accrued - deductions;
        },

        formatPeriod(startDate, endDate) {
            if (!startDate && !endDate) {
                return 'Sin período definido';
            }
            if (!startDate) {
                return `- ${endDate}`;
            }
            if (!endDate) {
                return `${startDate} -`;
            }
            return `${startDate} - ${endDate}`;
        },

        getValidCount() {
            if (!this.blockData || !this.blockData.block_payroll_json_responses) {
                return 0;
            }
            return Object.values(this.blockData.block_payroll_json_responses)
                .filter(response => response.is_valid === true).length;
        },

        getInvalidCount() {
            if (!this.blockData || !this.blockData.block_payroll_json_responses) {
                return 0;
            }
            return Object.values(this.blockData.block_payroll_json_responses)
                .filter(response => response.is_valid === false).length;
        },

        getPendingCount() {
            if (!this.blockData || !this.blockData.block_payroll_json_responses) {
                return 0;
            }
            return Object.values(this.blockData.block_payroll_json_responses)
                .filter(response => response.is_valid === null || response.is_valid === undefined).length;
        },

        formatDateTime(dateTimeString) {
            if (!dateTimeString) return 'N/A';
            try {
                const date = new Date(dateTimeString);
                return date.toLocaleString('es-CO', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (e) {
                return 'N/A';
            }
        },

        clickClose() {
            this.$emit('update:showDialog', false);
            this.blockData = null;
            this.titleDialog = 'Opciones del Bloque de Nómina';
        }
    }
}
</script>

<style scoped>
.badge {
    font-size: 0.875rem;
    padding: 0.375rem 0.5rem;
}

.bg-secondary {
    background-color: #6c757d !important;
}

.bg-success {
    background-color: #28a745 !important;
}

.bg-danger {
    background-color: #dc3545 !important;
}

.bg-warning {
    background-color: #ffc107 !important;
    color: #212529 !important;
}

.validation-summary .badge {
    margin-right: 10px;
}

.validation-details {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

.table-sm td, .table-sm th {
    padding: 0.3rem;
}
</style>
