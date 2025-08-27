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
</style>
