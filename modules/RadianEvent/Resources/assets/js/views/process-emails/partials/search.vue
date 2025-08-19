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
                                    varios minutos dependiendo de la cantidad de
                                    correos en el rango de fechas.
                                </li>
                                <li>
                                    Si aparece un error de timeout (504), el
                                    proceso puede estar ejecutándose en segundo
                                    plano.
                                </li>
                                <li>
                                    Recomendamos usar rangos de fechas pequeños
                                    (1-7 días) para mejor rendimiento.
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
            axios.defaults.timeout = 600000; // 10 minutos

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
            } finally {
                // Restaurar timeout original
                axios.defaults.timeout = originalTimeout;
                this.loading_submit = false;
            }
        },
        getQueryParameters() {
            return queryString.stringify({
                ...this.form
            });
        },
        create() {
            this.titleDialog = `Procesar correos por intervalo de fechas`;
        },
        close() {
            this.$emit("update:showDialog", false);
            this.initForm();
        }
    }
};
</script>
