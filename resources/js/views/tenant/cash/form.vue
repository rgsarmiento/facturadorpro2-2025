<template>
    <el-dialog
        :title="titleDialog"
        :visible="showDialog"
        @close="close"
        @open="create"
    >
        <form autocomplete="off" @submit.prevent="submit">
            <div class="form-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label">Vendedor</label>
                            <!--<el-input v-model="form.user" readonly></el-input> -->
                            <el-select
                                :disabled="disableUser"
                                v-model="form.user_id"
                            >
                                <el-option
                                    v-for="option in users"
                                    :key="option.id"
                                    :value="option.id"
                                    :label="option.name"
                                ></el-option>
                            </el-select>
                            <small
                                class="form-control-feedback"
                                v-if="errors.user"
                                v-text="errors.user[0]"
                            ></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div
                            class="form-group"
                            :class="{ 'has-danger': errors.beginning_balance }"
                        >
                            <label class="control-label">Saldo inicial</label>
                            <el-input
                                v-model="formattedBeginningBalance"
                            ></el-input>
                            <small
                                class="form-control-feedback"
                                v-if="errors.beginning_balance"
                                v-text="errors.beginning_balance[0]"
                            ></small>
                        </div>
                    </div>
                    <!--<div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.reference_number}">
                            <label class="control-label">Número de Referencia</label>
                            <el-input :maxlength="10" v-model="form.reference_number"></el-input>
                            <small class="form-control-feedback" v-if="errors.reference_number" v-text="errors.reference_number[0]"></small>
                        </div>
                    </div>-->
                    <div class="col-md-6" v-if="!blindCash">
                        <!-- Solo muestra esto si no se especifico caja ciega -->
                        <div
                            class="form-group"
                            :class="{ 'has-danger': errors.resolution_id }"
                        >
                            <label class="control-label">Resolución</label>
                            <el-select
                                v-model="form.resolution_id"
                                placeholder="Seleccione una resolución"
                                filterable
                                @change="onResolutionChange"
                            >
                                <el-option
                                    v-for="option in resolutions"
                                    :key="`res-${option.id}`"
                                    :value="option.id"
                                    :label="getDisplayLabel(option)"
                                >
                                    {{ getDisplayLabel(option) }}
                                </el-option>
                            </el-select>
                            <small
                                class="form-control-feedback"
                                v-if="errors.resolution_id"
                                v-text="errors.resolution_id[0]"
                            ></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions text-right mt-4">
                <el-button @click.prevent="close()">Cancelar</el-button>
                <el-button
                    type="primary"
                    native-type="submit"
                    :loading="loading_submit"
                    >Guardar</el-button
                >
            </div>
        </form>
    </el-dialog>
</template>
                            <el-select
                                :disabled="disableUser"
                                v-model="form.user_id"
                            >
                                <el-option
                                    v-for="option in users"
                                    :key="option.id"
                                    :value="option.id"
                                    :label="option.name"
                                ></el-option>
                            </el-select>
                            <small
                                class="form-control-feedback"
                                v-if="errors.user"
                                v-text="errors.user[0]"
                            ></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div
                            class="form-group"
                            :class="{ 'has-danger': errors.beginning_balance }"
                        >
                            <label class="control-label">Saldo inicial</label>
                            <el-input
                                v-model="formattedBeginningBalance"
                            ></el-input>
                            <small
                                class="form-control-feedback"
                                v-if="errors.beginning_balance"
                                v-text="errors.beginning_balance[0]"
                            ></small>
                        </div>
                    </div>
                    <!--<div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.reference_number}">
                            <label class="control-label">Número de Referencia</label>
                            <el-input :maxlength="10" v-model="form.reference_number"></el-input>
                            <small class="form-control-feedback" v-if="errors.reference_number" v-text="errors.reference_number[0]"></small>
                        </div>
                    </div>-->
                    <div class="col-md-6" v-if="!blindCash">
                        <!-- Solo muestra esto si no se especifico caja ciega -->
                        <div
                            class="form-group"
                            :class="{ 'has-danger': errors.resolution_id }"
                        >
                            <label class="control-label">Resolución</label>
                            <el-select 
                                v-model="form.resolution_id" 
                                placeholder="Seleccione una resolución"
                                filterable
                                @change="onResolutionChange"
                            >
                                <el-option
                                    v-for="option in resolutions"
                                    :key="`res-${option.id}`"
                                    :value="option.id"
                                    :label="getDisplayLabel(option)"
                                >
                                    {{ getDisplayLabel(option) }}
                                </el-option>
                            </el-select>
                            <small
                                class="form-control-feedback"
                                v-if="errors.resolution_id"
                                v-text="errors.resolution_id[0]"
                            ></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions text-right mt-4">
                <el-button @click.prevent="close()">Cancelar</el-button>
                <el-button
                    type="primary"
                    native-type="submit"
                    :loading="loading_submit"
                    >Guardar</el-button
                >
            </div>
        </form>
    </el-dialog>
</template>

<script>
export default {
    props: ["showDialog", "recordId", "typeUser"],

    data() {
        return {
            blindCash: false, // Esta propiedad se puede pasar como prop si es necesario
            loading_submit: false,
            titleDialog: null,
            resource: "cash",
            errors: {},
            form: {},
            user: {},
            all_departments: [],
            all_provinces: [],
            all_districts: [],
            provinces: [],
            districts: [],
            identity_document_types: [],
            users: [],
            resolutions: []
        };
    },

    async created() {
        await this.$http.get(`/${this.resource}/tables`).then(response => {
            this.users = response.data.users;
            (this.user = response.data.user),
                (this.resolutions = response.data.resolutions);
            this.blindCash = response.data.blindCash;
        });
        this.initForm();
    },

    computed: {
        disableUser() {
            if (this.typeUser == "admin") {
                return false;
            }
            return true;
        },
        // Propiedad computada para manejar el saldo inicial
        formattedBeginningBalance: {
            get() {
                // Esto se activará cuando necesites mostrar el valor en el input
                // Convierte el valor a un número flotante y lo formatea a dos decimales
                return parseFloat(this.form.beginning_balance).toFixed(0);
            },
            set(newValue) {
                // Esto se activará cuando el input cambie
                // Elimina cualquier cosa que no sea un número o punto decimal para evitar entradas inválidas
                const value = parseFloat(newValue.replace(/[^\d.]/g, ""));
                this.form.beginning_balance = isNaN(value) ? 0 : value;
            }
        }
    },

    methods: {
        initForm() {
            this.errors = {};
            this.form = {
                id: null,
                user_id: this.user.id,
                // user: null,
                date_opening: null,
                time_opening: null,
                date_closed: null,
                time_closed: null,
                beginning_balance: 0,
                final_balance: 0,
                income: 0,
                state: true,
                reference_number: null,
                resolution_id: null
            };
        },

        create() {
            this.titleDialog = this.recordId
                ? "Editar Caja chica"
                : "Aperturar Caja chica";

            if (this.recordId) {
                this.$http
                    .get(`/${this.resource}/record/${this.recordId}`)
                    .then(response => {
                        this.form = response.data.data;
                        // Asegurar que resolution_id sea un número
                        if (this.form.resolution_id) {
                            this.form.resolution_id = parseInt(
                                this.form.resolution_id
                            );
                        }

                        // Después de cargar los datos del formulario, actualizar las resoluciones
                        // para incluir la resolución actual en la lista
                        this.updateResolutions();
                    });
            } else {
                this.form.user_id = this.user.id; // Sesión
                // Para nuevos registros, cargar resoluciones normalmente
                this.updateResolutions();
            }
        },

        // Método para actualizar las resoluciones
        updateResolutions() {
            // Construir la URL con el parámetro current_resolution_id si estamos editando
            let url = `/${this.resource}/tables`;
            if (this.recordId && this.form && this.form.resolution_id) {
                url += `?current_resolution_id=${this.form.resolution_id}`;
            }

            this.$http
                .get(url)
                .then(response => {
                    this.resolutions = response.data.resolutions.map(res => ({
                        ...res,
                        id: parseInt(res.id)
                    }));

                    // Asegurar que el usuario actual y las opciones estén también actualizadas si es necesario
                    this.users = response.data.users;
                    this.user = response.data.user;

                    // Forzar actualización del select después de cargar las resoluciones
                    this.$nextTick(() => {
                        if (this.form && this.form.resolution_id) {
                            const currentResolutionId = this.form.resolution_id;
                            const foundResolution = this.resolutions.find(
                                res => res.id === currentResolutionId
                            );
                            if (foundResolution) {
                                // Limpiar y reasignar para forzar actualización
                                this.form.resolution_id = null;
                                this.$nextTick(() => {
                                    this.form.resolution_id = currentResolutionId;
                                });
                            } else {
                                // Si la resolución no existe, seleccionar la primera disponible
                                if (this.resolutions.length > 0) {
                                    this.form.resolution_id = this.resolutions[0].id;
                                }
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error("Error al cargar las resoluciones:", error);
                });
        },

        async openingCashCkeck() {
            let response = await this.$http
                .get(
                    `/${this.resource}/opening_cash_check/${this.form.user_id}`
                )
                .then(response => {
                    let cash = response.data.cash;
                    return cash ? true : false;
                });
            return response;
        },

        async submit() {
            this.loading_submit = true;
            if (!this.recordId) {
                if (await this.openingCashCkeck()) {
                    this.$message({
                        message:
                            "No puede crear caja chica, porfavor cierre caja chica para el usuario definido",
                        type: "warning",
                        duration: 5000
                    });
                    this.loading_submit = false;
                    return false;
                }
            }

            this.$http
                .post(`/${this.resource}`, this.form)
                .then(response => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                        if (this.form.user_id === this.user.id)
                            this.$eventHub.$emit("openCash");
                        this.$eventHub.$emit("reloadData");
                        // window.open('/pos/init')
                        this.close();
                    } else {
                        this.$message.error(response.data.message);
                    }
                })
                .catch(error => {
                    if (error.response.status === 422) {
                        this.errors = error.response.data;
                    } else {
                        console.log(error);
                    }
                })
                .then(() => {
                    this.loading_submit = false;
                });
        },

        close() {
            this.$emit("update:showDialog", false);
            this.initForm();
        },

        // Método para obtener el texto de visualización de las resoluciones
        getDisplayLabel(resolution) {
            return `${resolution.prefix ||
                "N/A"} / ${resolution.resolution_number || "N/A"}`;
        },

        // Método para manejar cambios en la selección de resolución
        onResolutionChange(value) {
            // Manejo de cambios en la selección de resolución si es necesario
        }
    }
};
</script>
