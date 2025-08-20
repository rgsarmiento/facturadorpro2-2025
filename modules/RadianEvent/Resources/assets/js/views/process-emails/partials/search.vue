<template>
    <el-dialog
        :title="titleDialog"
        :visible="showDialog"
        @close="close"
        @open="create"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        :show-close="false"
    >
        <form autocomplete="off" @submit.prevent="submit">
            <div class="form-body">
                <div class="row">
                    <div class="col-md-12">
                        <div
                            class="alert alert-info"
                            style="margin-bottom: 20px;"
                        >
                            <strong>Información importante:</strong>
                            <ul style="margin-bottom: 0; margin-top: 10px;">
                                <li>
                                    El procesamiento de emails puede tardar
                                    varios minutos (hasta 30 minutos)
                                    dependiendo de la cantidad de correos en el
                                    rango de fechas.
                                </li>
                                <li>
                                    Si aparece un error de timeout (504), el
                                    proceso puede estar ejecutándose en segundo
                                    plano y se mostrará el progreso
                                    automáticamente.
                                </li>
                                <li>
                                    Para rangos grandes (más de 7 días), el
                                    sistema mostrará el progreso en tiempo real.
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div
                            class="form-group"
                            :class="{ 'has-danger': errors.search_start_date }"
                        >
                            <label class="control-label">Fecha inicio</label>
                            <el-date-picker
                                v-model="form.search_start_date"
                                type="date"
                                @change="changeDisabledDates"
                                value-format="yyyy-MM-dd"
                                :clearable="false"
                            ></el-date-picker>
                            <small
                                class="form-control-feedback"
                                v-if="errors.search_start_date"
                                v-text="errors.search_start_date[0]"
                            ></small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div
                            class="form-group"
                            :class="{ 'has-danger': errors.search_end_date }"
                        >
                            <label class="control-label">Fecha término</label>
                            <el-date-picker
                                v-model="form.search_end_date"
                                type="date"
                                value-format="yyyy-MM-dd"
                                :clearable="false"
                                :picker-options="pickerOptions"
                            ></el-date-picker>
                            <small
                                class="form-control-feedback"
                                v-if="errors.search_end_date"
                                v-text="errors.search_end_date[0]"
                            ></small>
                        </div>
                    </div>
                </div>

                <!-- Área de progreso -->
                <div class="row" v-if="showProgress">
                    <div class="col-md-12">
                        <div class="alert alert-warning">
                            <h5>
                                <i class="el-icon-loading"></i> Procesamiento en
                                curso
                            </h5>
                            <p><strong>Estado:</strong> {{ progressStatus }}</p>
                            <p v-if="lastProcessingData">
                                <strong>Rango de fechas:</strong>
                                {{ lastProcessingData.search_range.start }} -
                                {{ lastProcessingData.search_range.end }}<br />
                                <strong>Emails procesados:</strong>
                                {{ lastProcessingData.total_emails_processed
                                }}<br />
                                <strong>Exitosos:</strong>
                                {{ lastProcessingData.successful_emails }}<br />
                                <strong>Fallidos:</strong>
                                {{ lastProcessingData.failed_emails }}<br />
                                <strong>Duración:</strong> {{ elapsedTime }}
                            </p>
                            <el-button
                                type="info"
                                size="small"
                                @click="checkStatus"
                                >Actualizar estado</el-button
                            >
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions text-right mt-4">
                <el-button @click.prevent="close()">Cerrar</el-button>
                <el-button
                    type="primary"
                    native-type="submit"
                    :loading="loading_submit"
                    icon="el-icon-check"
                    >Procesar</el-button
                >
            </div>
        </form>
    </el-dialog>
</template>

<script>
import queryString from "query-string";

export default {
    props: ["showDialog", "recordId"],
    data() {
        return {
            loading_submit: false,
            titleDialog: null,
            resource: "co-email-reading",
            form: {},
            errors: {},
            showProgress: false,
            progressStatus: "",
            lastProcessingData: null,
            elapsedTime: "",
            statusInterval: null,
            pickerOptions: {
                disabledDate: time => {
                    time = moment(time).format("YYYY-MM-DD");
                    return this.form.search_start_date > time;
                }
            }
        };
    },
    created() {
        this.initForm();
    },
    beforeDestroy() {
        this.stopStatusPolling();
    },
    methods: {
        changeDisabledDates() {
            if (this.form.search_date_end < this.form.search_date_start) {
                this.form.search_date_end = this.form.date_start;
            }
        },
        initForm() {
            this.form = {
                search_start_date: moment().format("YYYY-MM-DD"),
                search_end_date: moment().format("YYYY-MM-DD")
            };
        },
        async submit() {
            this.loading_submit = true;

            // Configurar timeout más largo para esta operación específica
            const axios = this.$http;
            const originalTimeout = axios.defaults.timeout;
            axios.defaults.timeout = 1800000; // 30 minutos

            try {
                const response = await axios.get(
                    `/co-radian-events/search-imap-emails?${this.getQueryParameters()}`
                );

                this.$eventHub.$emit("reloadData");

                if (response.data.success) {
                    this.$message.success(response.data.message);
                    this.close();
                } else {
                    this.$message.error(response.data.message);
                }
            } catch (error) {
                console.error("Error en procesamiento de emails:", error);

                // Manejar diferentes tipos de errores
                if (
                    error.code === "ECONNABORTED" ||
                    error.message.includes("timeout")
                ) {
                    this.$message.error(
                        "La operación está tardando más de lo esperado. El proceso puede estar ejecutándose en segundo plano. Por favor, verifique los resultados en unos minutos."
                    );
                } else if (error.response) {
                    // Error del servidor
                    if (error.response.status === 504) {
                        this.$message.warning(
                            "Timeout del servidor. El proceso puede estar ejecutándose en segundo plano. Verifique los resultados posteriormente."
                        );
                    } else if (
                        error.response.data &&
                        error.response.data.message
                    ) {
                        this.$message.error(error.response.data.message);
                    } else {
                        this.$message.error(
                            `Error del servidor: ${error.response.status}`
                        );
                    }
                } else {
                    this.$message.error(
                        "Error de conexión. Verifique su conexión a internet."
                    );
                }

                // En caso de timeout, iniciar polling para verificar estado
                if (error.response && error.response.status === 504) {
                    this.startStatusPolling();
                }
            } finally {
                // Restaurar timeout original
                axios.defaults.timeout = originalTimeout;
                this.loading_submit = false;
            }
        },

        async checkStatus() {
            try {
                const response = await this.$http.get(
                    "/co-radian-events/search-imap-emails-status"
                );

                if (response.data.success) {
                    this.lastProcessingData = response.data.data;
                    this.progressStatus = response.data.message;

                    // Calcular tiempo transcurrido
                    if (this.lastProcessingData.last_processing) {
                        const startTime = moment(
                            this.lastProcessingData.processing_date +
                                " " +
                                this.lastProcessingData.processing_time
                        );
                        const now = moment();
                        const duration = moment.duration(now.diff(startTime));
                        this.elapsedTime = this.formatDuration(duration);
                    }

                    // Si está completado, detener polling
                    if (this.lastProcessingData.is_completed) {
                        this.stopStatusPolling();
                        this.$message.success("Procesamiento completado");
                        this.$eventHub.$emit("reloadData");
                    }
                } else {
                    this.$message.error(response.data.message);
                }
            } catch (error) {
                console.error("Error verificando estado:", error);
                this.$message.error(
                    "Error al verificar el estado del procesamiento"
                );
            }
        },

        startStatusPolling() {
            this.showProgress = true;
            this.progressStatus = "Verificando estado del procesamiento...";

            // Verificar estado inmediatamente
            this.checkStatus();

            // Configurar polling cada 10 segundos
            this.statusInterval = setInterval(() => {
                this.checkStatus();
            }, 10000);
        },

        stopStatusPolling() {
            if (this.statusInterval) {
                clearInterval(this.statusInterval);
                this.statusInterval = null;
            }
            this.showProgress = false;
        },

        formatDuration(duration) {
            const hours = Math.floor(duration.asHours());
            const minutes = duration.minutes();
            const seconds = duration.seconds();

            if (hours > 0) {
                return `${hours}h ${minutes}m ${seconds}s`;
            } else if (minutes > 0) {
                return `${minutes}m ${seconds}s`;
            } else {
                return `${seconds}s`;
            }
        },
        getQueryParameters() {
            return queryString.stringify({
                ...this.form
            });
        },
        create() {
            this.titleDialog = `Procesar correos por intervalo de fechas`;
            // Verificar si hay un procesamiento en curso al abrir el diálogo
            this.checkStatus();
        },
        close() {
            this.$emit("update:showDialog", false);
            this.stopStatusPolling();
            this.initForm();
        }
    }
};
</script>
