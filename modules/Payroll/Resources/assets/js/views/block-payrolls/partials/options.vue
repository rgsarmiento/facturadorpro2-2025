<template>
    <el-dialog
        :title="titleDialog"
        :visible="showDialog"
        @open="loadData"
        width="70%"
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
                    
                    <!-- Sección de nóminas individuales -->
                    <div v-if="blockData.block_payroll_json && Object.keys(blockData.block_payroll_json).length > 0" class="mt-4">
                        <h5 class="mb-3">Nóminas Individuales Generadas</h5>
                        <div class="payroll-documents-table" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-striped">
                                <thead class="table-header-fixed">
                                    <tr>
                                        <th style="width: 10%;">Cédula</th>
                                        <th style="width: 35%;">Nombre</th>
                                        <th style="width: 15%;">Total Nómina</th>
                                        <th style="width: 8%;">Prefijo</th>
                                        <th style="width: 12%;">Consecutivo</th>
                                        <th style="width: 12%;">Estado</th>
                                        <th style="width: 8%;">PDF</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(payrollData, workerId) in blockData.block_payroll_json" :key="workerId">
                                        <td>{{ payrollData.worker ? payrollData.worker.identification_number : 'N/A' }}</td>
                                        <td>{{ getWorkerFullName(payrollData.worker) }}</td>
                                        <td class="text-right">
                                            ${{ getFormatDecimal(getPayrollTotal(payrollData)) }}
                                        </td>
                                        <td class="text-center">{{ payrollData.prefix || 'N/A' }}</td>
                                        <td class="text-center">{{ getPayrollConsecutive(payrollData, workerId) }}</td>
                                        <td class="text-center">
                                            <span 
                                                class="badge"
                                                :class="{
                                                    'bg-success': getValidationStatus(workerId) === true,
                                                    'bg-danger': getValidationStatus(workerId) === false,
                                                    'bg-warning': getValidationStatus(workerId) === null
                                                }"
                                            >
                                                {{ getValidationStatusText(workerId) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <el-button
                                                v-if="getValidationStatus(workerId) === true"
                                                type="primary"
                                                size="mini"
                                                icon="el-icon-download"
                                                @click="downloadPayrollPDF(workerId, payrollData)"
                                                :loading="loadingPDFs[workerId]"
                                                title="Descargar PDF de nómina individual"
                                            >
                                                PDF
                                            </el-button>
                                            <span v-else class="text-muted">-</span>
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
            loadingConsecutives: false,
            resource: 'payroll/block-payrolls',
            blockData: null,
            errors: {},
            loadingPDFs: {} // Para tracking del loading de PDFs individuales
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
                    
                    // Cargar consecutivos automáticamente al cargar los datos
                    this.loadAllConsecutives();
                })
                .catch(error => {
                    console.error('Error loading block data:', error);
                    this.$message.error('Error al cargar los datos del bloque');
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        async loadConsecutives() {
            if (!this.blockData?.block_payroll_json_responses) return;
            
            for (const [workerId, response] of Object.entries(this.blockData.block_payroll_json_responses)) {
                if (response.validation_method === 'zipkey_query_message' && response.zip_key) {
                    try {
                        const consecutive = await this.getConsecutiveFromZipKey(response.zip_key, workerId);
                        if (consecutive) {
                            // Agregar el consecutivo al response para usarlo en la tabla
                            response.consecutive_number = consecutive;
                        }
                    } catch (error) {
                        console.error(`Error loading consecutive for worker ${workerId}:`, error);
                    }
                }
            }
            
            // Forzar actualización de la vista
            this.$forceUpdate();
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

        getConsecutiveFromZipKey(row, index) {
                // Si ya tiene un consecutivo, no hacer nada
                if (row.consecutive_number) {
                    return;
                }

                this.$set(this.tableData[index], 'consecutive_loading', true);
                
                const params = {
                    block_payroll_id: this.id,
                    worker_id: row.worker_id
                };

                this.$http.post(`${this.server_path}/payroll/block-payrolls/query-individual-zipkey`, params)
                .then(response => {
                    if (response.data.success && response.data.data) {
                        const responseData = response.data.data;
                        
                        // Actualizar la fila con los datos obtenidos
                        this.$set(this.tableData[index], 'consecutive_number', responseData.consecutive_number || 'N/A');
                        this.$set(this.tableData[index], 'zip_key', responseData.zip_key || '');
                        this.$set(this.tableData[index], 'status_message', responseData.status_message || '');
                        this.$set(this.tableData[index], 'is_valid', responseData.is_valid || false);
                        
                        console.log(`Consecutivo extraído para trabajador ${row.worker_id}:`, responseData.consecutive_number);
                    } else {
                        console.error('Error al obtener consecutivo:', response.data.message);
                        this.$set(this.tableData[index], 'consecutive_number', 'Error');
                    }
                })
                .catch(error => {
                    console.error('Error al consultar consecutivo:', error);
                    this.$set(this.tableData[index], 'consecutive_number', 'Error');
                })
                .finally(() => {
                    this.$set(this.tableData[index], 'consecutive_loading', false);
                });
            },

        async loadAllConsecutives() {
            if (!this.blockData?.block_payroll_json) {
                return; // No hay nóminas para procesar
            }

            this.loadingConsecutives = true;
            
            try {
                const response = await this.$http.post(`/${this.resource}/query-all-consecutives`, {
                    block_payroll_id: this.recordId
                });
                
                if (response.data.success && response.data.data) {
                    const consecutivesData = response.data.data;
                    
                    // Actualizar los datos del bloque con los consecutivos obtenidos
                    for (const [workerId, consecutiveInfo] of Object.entries(consecutivesData)) {
                        if (this.blockData.block_payroll_json[workerId]) {
                            this.$set(this.blockData.block_payroll_json[workerId], 'consecutive_number', consecutiveInfo.consecutive_number);
                            this.$set(this.blockData.block_payroll_json[workerId], 'zip_key', consecutiveInfo.zip_key);
                            this.$set(this.blockData.block_payroll_json[workerId], 'is_valid', consecutiveInfo.is_valid);
                            this.$set(this.blockData.block_payroll_json[workerId], 'status_message', consecutiveInfo.status_message);
                        }
                    }
                }
            } catch (error) {
                // No mostrar error al usuario ya que es un proceso automático
            } finally {
                this.loadingConsecutives = false;
            }
        },

        getPayrollConsecutive(payrollData, workerId) {
            // Primero buscar en los datos que se actualizan con el botón
            if (payrollData?.consecutive_number) {
                return payrollData.consecutive_number;
            }
            
            // Buscar el consecutivo en las respuestas cargadas
            if (this.blockData?.block_payroll_json_responses?.[workerId]?.consecutive_number) {
                return this.blockData.block_payroll_json_responses[workerId].consecutive_number;
            }
            
            // Buscar en mensaje de autorización directo
            if (this.blockData?.block_payroll_json_responses?.[workerId]) {
                const apiResponse = this.blockData.block_payroll_json_responses[workerId];
                const authMessage = this.getAuthorizationMessageFromResponse(apiResponse);
                if (authMessage) {
                    const consecutiveMatch = authMessage.match(/([A-Z]{1,3})-(\d+)/);
                    if (consecutiveMatch) {
                        return consecutiveMatch[2];
                    }
                }
            }
            
            // Fallback: buscar en el payrollData
            const authMessage = this.getAuthorizationMessage(payrollData);
            if (authMessage) {
                const consecutiveMatch = authMessage.match(/([A-Z]{1,3})-(\d+)/);
                if (consecutiveMatch) {
                    return consecutiveMatch[2];
                }
            }
            
            // Último fallback: usar consecutivo del payrollData si existe y no es 0
            if (payrollData.consecutive && payrollData.consecutive !== 0) {
                return payrollData.consecutive.toString();
            }
            
            return 'N/A';
        },

        getAuthorizationMessageFromResponse(apiResponse) {
            // Solo mostrar debug para análisis inicial
            if (apiResponse.worker_name && apiResponse.worker_name.includes('ALEXANDER')) {
                console.log('API Response FULL STRUCTURE (ALEXANDER):', JSON.stringify(apiResponse, null, 2));
            }
            
            // Buscar en la respuesta original del API si existe
            if (apiResponse.original_api_response) {
                // Si hay un mensaje directo en la respuesta original
                if (apiResponse.original_api_response.message) {
                    return apiResponse.original_api_response.message;
                }
                if (apiResponse.original_api_response.authorization_message) {
                    return apiResponse.original_api_response.authorization_message;
                }
                if (apiResponse.original_api_response.response_message) {
                    return apiResponse.original_api_response.response_message;
                }
            }
            
            // Buscar en campos directos de la respuesta
            if (apiResponse.authorization_message) {
                return apiResponse.authorization_message;
            }
            if (apiResponse.response_message) {
                return apiResponse.response_message;
            }
            if (apiResponse.api_response_message) {
                return apiResponse.api_response_message;
            }
            if (apiResponse.message) {
                return apiResponse.message;
            }
            
            // Si el método de validación es zipkey_query_message, 
            // probablemente necesitamos hacer una consulta al ZipKey
            if (apiResponse.validation_method === 'zipkey_query_message' && apiResponse.zip_key) {
                // Por ahora, intentaremos construir un consecutivo basado en algún patrón
                // pero probablemente necesitemos hacer una consulta al backend
                console.log('ZipKey available:', apiResponse.zip_key);
                return null; // Retornamos null para que se maneje en getPayrollConsecutive
            }
            
            return null;
        },

        getPayrollPrefix(payrollData) {
            // Buscar el prefijo en el mensaje de autorización
            const authMessage = this.getAuthorizationMessage(payrollData);
            if (authMessage) {
                const prefixMatch = authMessage.match(/([A-Z]{1,3})-(\d+)/);
                if (prefixMatch) {
                    return prefixMatch[1]; // Solo el prefijo
                }
            }
            return 'N/A';
        },

        getAuthorizationMessage(payrollData) {
            // Solo mostrar debug una vez
            if (payrollData.worker && payrollData.worker.identification_number && payrollData.worker.identification_number.includes('1')) {
                console.log('PayrollData FULL STRUCTURE (Worker with 1 in cedula):', JSON.stringify(payrollData, null, 2));
            }
            
            // Buscar el mensaje de autorización en diferentes ubicaciones posibles
            const fields = [
                'response_api_message',
                'authorization_message', 
                'message',
                'response.message',
                'api_response.message'
            ];
            
            for (let field of fields) {
                let value = this.getNestedProperty(payrollData, field);
                if (value) {
                    return value;
                }
            }
            
            return null;
        },

        getNestedProperty(obj, path) {
            return path.split('.').reduce((current, key) => current && current[key], obj);
        },

        downloadPayrollPDF(workerId, payrollData) {
            // Inicializar el loading para este worker específico
            this.$set(this.loadingPDFs, workerId, true);

            // Buscar el filename_pdf en las respuestas almacenadas
            let filename = null;
            
            // Explorar la estructura completa de block_payroll_json_responses
            if (this.blockData?.block_payroll_json_responses?.[workerId]) {
                const workerResponse = this.blockData.block_payroll_json_responses[workerId];
                
                // Estrategia 1: urlpayrollpdf directo
                if (workerResponse.urlpayrollpdf) {
                    filename = workerResponse.urlpayrollpdf;
                }
                // Estrategia 2: Buscar en original_api_response
                else if (workerResponse.original_api_response) {
                    // Buscar urlpayrollpdf en original_api_response
                    if (workerResponse.original_api_response.urlpayrollpdf) {
                        filename = workerResponse.original_api_response.urlpayrollpdf;
                    }
                    // Buscar en ResponseDian si existe
                    else if (workerResponse.original_api_response.ResponseDian?.urlpayrollpdf) {
                        filename = workerResponse.original_api_response.ResponseDian.urlpayrollpdf;
                    }
                    // Buscar filename_pdf en original_api_response
                    else if (workerResponse.original_api_response.filename_pdf) {
                        filename = workerResponse.original_api_response.filename_pdf;
                    }
                }
                // Estrategia 3: Buscar filename_pdf en el root del worker response
                else if (workerResponse.filename_pdf) {
                    filename = workerResponse.filename_pdf;
                }
            }
            
            // Fallback: Buscar en payrollData directamente
            if (!filename && payrollData.filename_pdf) {
                filename = payrollData.filename_pdf;
            }

            if (!filename) {
                this.$message.error('No se encontró el archivo PDF para esta nómina');
                this.$set(this.loadingPDFs, workerId, false);
                return;
            }

            // Usar la misma lógica que document-payrolls
            this.$http.get(`/${this.resource}/downloadFile/${filename}`)
                .then((response) => {
                    let res_data = response.data;
                    if (!res_data.success) {
                        this.$message.error(res_data.message);
                        return;
                    }

                    var byteCharacters = atob(response.data.filebase64);
                    var byteNumbers = new Array(byteCharacters.length);
                    for (var i = 0; i < byteCharacters.length; i++) {
                        byteNumbers[i] = byteCharacters.charCodeAt(i);
                    }
                    var byteArray = new Uint8Array(byteNumbers);
                    var file = new Blob([byteArray], { type: 'application/pdf;base64' });
                    var fileURL = URL.createObjectURL(file);
                    window.open(fileURL, '_blank');
                })
                .catch(error => {
                    console.error('Error downloading PDF:', error);
                    this.$message.error('Error al descargar el PDF');
                })
                .finally(() => {
                    this.$set(this.loadingPDFs, workerId, false);
                });
        },

        getWorkerFullName(worker) {
            if (!worker) return 'N/A';
            const firstName = worker.first_name || '';
            const surname = worker.surname || '';
            const secondSurname = worker.second_surname || '';
            return `${firstName} ${surname} ${secondSurname}`.trim() || 'N/A';
        },

        getPayrollTotal(payrollData) {
            if (!payrollData || !payrollData.accrued) return 0;
            
            // Intentar obtener el total de devengados
            const accruedTotal = payrollData.accrued.accrued_total || 0;
            const deductionsTotal = payrollData.deductions ? payrollData.deductions.deductions_total || 0 : 0;
            
            return parseFloat(accruedTotal) - parseFloat(deductionsTotal);
        },

        getValidationStatus(workerId) {
            if (!this.blockData || !this.blockData.block_payroll_json_responses) {
                return null;
            }
            
            const response = this.blockData.block_payroll_json_responses[workerId];
            if (!response) return null;
            
            return response.is_valid;
        },

        getValidationStatusText(workerId) {
            const status = this.getValidationStatus(workerId);
            
            if (status === true) return 'Válida';
            if (status === false) return 'Inválida';
            return 'Pendiente';
        },

        clickClose() {
            this.$emit('update:showDialog', false);
            this.blockData = null;
            this.titleDialog = 'Opciones del Bloque de Nómina';
            this.loadingPDFs = {}; // Limpiar estados de loading
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

.payroll-documents-table {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    background-color: #fff;
}

.payroll-documents-table .table {
    margin-bottom: 0;
}

.table-header-fixed th {
    background-color: #f8f9fa;
    position: sticky;
    top: 0;
    z-index: 10;
    border-bottom: 2px solid #dee2e6;
}

.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}
</style>
