<template>
    <div class="modern-dashboard">
        <!-- Modern Header -->
        <div class="dashboard-header">
            <div class="header-content">
                <div class="header-title">
                    <h1 class="page-title">
                        <i class="fas fa-building mr-3"></i>
                        Panel de Compañías
                    </h1>
                    <p class="page-subtitle">Gestión y administración de empresas registradas</p>
                </div>
                <div class="header-actions">
                    <button
                        type="button"
                        class="btn btn-primary btn-modern"
                        @click.prevent="clickCreate()">
                        <i class="fas fa-plus mr-2"></i>
                        Nueva Compañía
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards Row (sin el de Documentos Totales) -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ records.length }}</div>
                    <div class="stat-label">Total Compañías</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-info">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ users.length }}</div>
                    <div class="stat-label">Usuarios Sistema</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">{{ year }}</div>
                    <div class="stat-label">Año Actual</div>
                </div>
            </div>
        </div>

        <!-- Companies Table -->
        <div class="table-card">
            <div class="table-container">
                <table class="table table-modern">
                    <thead>
                        <tr class="table-header-row">
                            <th colspan="100%" class="table-title-header">
                                <div class="table-title-content">
                                    <h3>
                                        <i class="fas fa-building mr-2"></i>
                                        Listado de Compañías
                                    </h3>
                                    <span class="record-count">{{ records.length }} Empresas Registradas</span>
                                </div>
                            </th>
                        </tr>
                        <tr>
                            <th class="th-modern">#</th>
                            <th class="th-modern">Nro. identificación</th>
                            <th class="th-modern">Empresa</th>
                            <th class="th-modern">Correo</th>
                            <th class="th-modern">Subdominio</th>
                            <th class="th-modern">Plan</th>
                            <th class="th-modern text-center">Lim. docs</th>
                            <th class="th-modern text-center">Lim. usr</th>
                            <th class="th-modern text-center">Docs</th>
                            <th class="th-modern text-center" v-if="currentUserId === 1 || currentUserId === 2">Acciones</th>
                            <th class="th-modern text-center" v-if="currentUserId === 1 || currentUserId === 2">Usuario</th>
                            <th class="th-modern text-center" v-if="currentUserId === 1 || currentUserId === 2">Limitar Docs.</th>
                            <th class="th-modern text-center" v-if="currentUserId === 1 || currentUserId === 2">Bloquear cuenta</th>
                            <th class="th-modern text-center" v-if="currentUserId === 1 || currentUserId === 2">Emis bloq</th>
                            <th class="th-modern text-center" v-if="currentUserId === 1 || currentUserId === 2">Allow Seller Login</th>
                            <th class="th-modern text-center">Inicio ciclo</th>
                            <th class="th-modern text-center" v-if="currentUserId === 1 || currentUserId === 2">Pagos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in records" :key="index" class="tr-modern">
                            <td class="td-modern">
                                <span class="row-number">{{ index + 1 }}</span>
                            </td>
                            <td class="td-modern">
                                <span class="identification-text">{{ row.identification_number }}</span>
                            </td>
                            <td class="td-modern">
                                <strong class="company-name">{{ row.name }}</strong>
                            </td>
                            <td class="td-modern">
                                <span class="email-text">{{ row.email }}</span>
                            </td>
                            <td class="td-modern">
                                <span class="subdomain-text">{{ row.hostname }}</span>
                            </td>
                            <td class="td-modern">
                                <span class="plan-text">{{ row.plan }}</span>
                            </td>
                            <td class="td-modern text-center">
                                <span class="limit-text">{{ row.limit_documents }}</span>
                            </td>
                            <td class="td-modern text-center">
                                <span class="limit-text">{{ row.limit_users }}</span>
                            </td>
                            <td class="td-modern text-center">
                                <span class="doc-count">
                                    {{ row.count_doc ? row.count_doc : 0 }}
                                    /
                                    <template v-if="row.max_documents == 0">
                                        <i class="fas fa-infinity"></i>
                                    </template>
                                    <template v-else>
                                        {{ row.max_documents }}
                                    </template>
                                </span>
                            </td>

                            <!-- Acciones primero -->
                            <td class="td-modern text-center" v-if="currentUserId === 1 || currentUserId === 2">
                                <div class="action-buttons-modern">
                                    <template v-if="!row.locked">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-success mr-1"
                                            @click.prevent="switchToTenant(row.id)"
                                            title="Acceder">
                                            <i class="fas fa-sign-in-alt"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary mr-1"
                                            @click.prevent="clickEdit(row.id)"
                                            v-if="currentUserId === 1 || currentUserId === 2"
                                            title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            @click.prevent="clickDelete(row.id)"
                                            v-if="currentUserId === 1"
                                            title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </template>
                                </div>
                            </td>

                            <td class="td-modern text-center" v-if="currentUserId === 1 || currentUserId === 2">
                                <el-select v-model="row.user_id" @change="assignUser(row)" size="mini" placeholder="Usuario" style="width: 120px;">
                                    <el-option
                                        v-for="user in users"
                                        :key="user.id"
                                        :label="user.name"
                                        :value="user.id">
                                    </el-option>
                                </el-select>
                            </td>

                            <td class="td-modern text-center" v-if="currentUserId === 1 || currentUserId === 2">
                                <el-switch
                                    :value="!!row.locked_user"
                                    @change="(val) => { row.locked_user = val; changeLockedUser(row); }"
                                    active-color="#ff4949"
                                    inactive-color="#dcdfe6">
                                </el-switch>
                            </td>

                            <td class="td-modern text-center" v-if="currentUserId === 1 || currentUserId === 2">
                                <template v-if="!row.locked">
                                    <el-switch
                                        :value="!!row.locked"
                                        @change="(val) => { row.locked = val; changeLockedTenant(row); }"
                                        active-color="#ff4949"
                                        inactive-color="#dcdfe6">
                                    </el-switch>
                                </template>
                            </td>

                            <td class="td-modern text-center" v-if="currentUserId === 1 || currentUserId === 2">
                                <el-switch
                                    :value="!!row.locked_emission"
                                    @change="(val) => { row.locked_emission = val; changeLockedEmission(row); }"
                                    active-color="#ff4949"
                                    inactive-color="#dcdfe6">
                                </el-switch>
                            </td>

                            <td class="td-modern text-center" v-if="currentUserId === 1 || currentUserId === 2">
                                <template v-if="!row.locked">
                                    <el-switch
                                        :value="!!row.allow_seller_login"
                                        @change="(val) => { row.allow_seller_login = val; changeAllowSellerLoginTenant(row); }"
                                        active-color="#13ce66"
                                        inactive-color="#dcdfe6">
                                    </el-switch>
                                </template>
                            </td>

                            <td class="td-modern text-center">
                                <template v-if="row.start_billing_cycle">
                                    <span class="billing-date">{{ row.start_billing_cycle }}</span>
                                </template>
                                <template v-else>
                                    <el-date-picker
                                        @change="setStartBillingCycle($event, row.id)"
                                        v-model="row.select_date_billing"
                                        value-format="yyyy-MM-dd"
                                        type="date"
                                        placeholder="Seleccionar"
                                        size="mini"
                                        style="width: 130px;">
                                    </el-date-picker>
                                </template>
                            </td>

                            <!-- Botones de pagos al final -->
                            <td class="td-modern text-center" v-if="currentUserId === 1 || currentUserId === 2">
                                <div class="action-buttons-modern">
                                    <template v-if="!row.locked">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-warning mr-1"
                                            @click.prevent="clickPayments(row.id)"
                                            title="Pagos">
                                            <i class="fas fa-credit-card"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-info"
                                            @click.prevent="clickAccountStatus(row.id)"
                                            title="Estado cuenta">
                                            <i class="fas fa-chart-line"></i>
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modals and dialogs -->
        <companies-form :showDialog.sync="showDialog" :recordId="recordId"></companies-form>
        <client-payments :showDialog.sync="showDialogPayments" :clientId="recordId"></client-payments>
        <account-status :showDialog.sync="showDialogAccountStatus" :clientId="recordId"></account-status>
    </div>
</template>

<script>
import CompaniesForm from "./form.vue";
//   import CompaniesFormEdit from './form_edit.vue'
import { deletable } from "@mixins/deletable";
import { changeable } from "@mixins/changeable";
import ClientPayments from "@viewsSystem/clients/partials/payments";
import AccountStatus from "@viewsSystem/clients/partials/account_status";
// import ChartLine from "./charts/Line";
// import ClientPayments from "./partials/payments.vue";
// import AccountStatus from "./partials/account_status.vue";

export default {
    mixins: [deletable, changeable],
    components: { CompaniesForm, ClientPayments, AccountStatus },
    data() {
        return {
            selectBillingDate: "",
            showDialogEdit: false,
            showDialog: false,
            showDialogPayments: false,
            showDialogAccountStatus: false,
            resource: "co-companies",
            resource_users: 'users',
            currentUserId: null,
            recordId: null,
            records: [],
            users: [],
            allcompany:[],
            text_limit_doc: null,
            text_limit_users: null,
            loaded: false,
            year: moment().format("YYYY"),
            total_documents: 0,
            dataChartLine: {
                labels: null,
                datasets: [
                    {
                        // label: 'Data One',
                        // backgroundColor: '#f87979',
                        data: null
                    }
                ]
            },
        };
    },
    async mounted() {
        this.getCurrentUser();
        this.loaded = false;
        // await this.$http.get(`/${this.resource}/charts`).then(response => {
        //   let line = response.data.line;
        //   this.dataChartLine.labels = line.labels;
        //   this.dataChartLine.datasets[0].data = line.data;
        //   this.total_documents = response.data.total_documents;
        // });
        this.loaded = true;
    },

    created() {
        this.$eventHub.$on("reloadData", () => {
            this.getData();
        });
        this.getUsers();
        this.getServiceCompany();
        this.text_limit_doc = "El límite de comprobantes fue superado";
        this.text_limit_users = "El límite de usuarios fue superado";
    },

//    watch: {
//        records(newVal, oldVal) {
//            console.log('[WATCH] records ha cambiado');
//            console.log('Anterior:', oldVal);
//            console.log('Nuevo:', newVal);
//            console.trace('¿Quién modificó `records`?');
//        }
//    },

    methods: {
        // Convertir valores a booleanos para los switches
        convertToBoolean(value) {
            if (typeof value === 'boolean') return value;
            if (typeof value === 'string') return value === '1' || value.toLowerCase() === 'true';
            if (typeof value === 'number') return value === 1;
            return false;
        },

        // Procesar datos para convertir valores de switches a booleanos
        processRecordsData(records) {
            return records.map(record => ({
                ...record,
                locked_user: this.convertToBoolean(record.locked_user),
                locked: this.convertToBoolean(record.locked),
                locked_emission: this.convertToBoolean(record.locked_emission),
                allow_seller_login: this.convertToBoolean(record.allow_seller_login)
            }));
        },

        //obtener el id del ususario de la session activa
        getCurrentUser() {
            axios.get(`/${this.resource}/current-user`)
                .then(response => {
                    this.currentUserId = response.data.currentUserId;
                })
                .catch(error => {
                    console.error('Error al obtener el ID del usuario actual:', error);
                });
        },

        //cambio de empresa tenancy por Cristian
        switchToTenant(companyId) {
            window.open(`/switch-tenant/${companyId}`, '_blank');
        },

        changeAllowSellerLoginTenant(row) {
            this.$http
                .post(`${this.resource}/change_allow_seller_login`, row)
                .then(response => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                        this.$eventHub.$emit("reloadData");
                    } else {
                        this.$message.error(response.data.message);
                    }
                })
                .catch(error => {
                    if (error.response.status === 500) {
                        this.$message.error(error.response.data.message);
                    } else {
                        console.log(error.response);
                    }
                })
                .then(() => {});
        },

        changeLockedTenant(row) {
            this.$http
                .post(`${this.resource}/locked_tenant`, row)
                .then(response => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                        this.$eventHub.$emit("reloadData");
                    } else {
                        this.$message.error(response.data.message);
                    }
                })
                .catch(error => {
                    if (error.response.status === 500) {
                        this.$message.error(error.response.data.message);
                    } else {
                        console.log(error.response);
                    }
                })
                .then(() => {});
        },

        getUsers() {
            this.$http.get(`/${this.resource_users}/records`).then(response => {
//                console.log(response.data.data)
                this.users = response.data.data;
            });
        },

        getServiceCompany() {
            this.$http.get(`/${this.resource}/all`).then(response => {
//                console.log(response.data)
                this.servicecompany = response.data.servicecompany;
//                console.log(this.servicecompany)
                this.getData(); // Llama a getData para actualizar records con los user_id correctos
            });
        },

        assignUser(row) {
            this.$http.post(`/${this.resource}/update-user`, {
                identification_number: row.identification_number,
                user_id: row.user_id
            }).then(response => {
                if (response.data.success) {
                    this.$message.success('Usuario asignado correctamente');
                } else {
                    this.$message.error('Error al asignar usuario');
                }
            }).catch(error => {
                console.log(error);
                this.$message.error('Error al asignar usuario');
            });
        },

        changeLockedUser(row) {
            this.$http
                .post(`${this.resource}/locked_user`, row)
                .then(response => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                        this.$eventHub.$emit("reloadData");
                    } else {
                        this.$message.error(response.data.message);
                    }
                })
                .catch(error => {
                    if (error.response.status === 500) {
                        this.$message.error(error.response.data.message);
                    } else {
                        console.log(error.response);
                    }
                })
                .then(() => {});
        },

        setStartBillingCycle(event, id) {
            this.$http
                .post(`${this.resource}/set_billing_cycle`, {
                    id: id,
                    start_billing_cycle: event
                })
                .then(response => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                    } else {
                        this.$message.error(response.data.message);
                    }
                })
                .catch(error => {
                    if (error.response.status === 500) {
                        this.$message.error(error.response.data.message);
                    } else {
                        console.log(error.response);
                    }
                })
                .then(() => {
                    this.$eventHub.$emit("reloadData");
                });
        },

        changeLockedEmission(row) {
            this.$http
                .post(`${this.resource}/locked_emission`, row)
                .then(response => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                        this.$eventHub.$emit("reloadData");
                    } else {
                        this.$message.error(response.data.message);
                    }
                })
                .catch(error => {
                    if (error.response.status === 500) {
                        this.$message.error(error.response.data.message);
                    } else {
                        console.log(error.response);
                    }
                })
                .then(() => {});
        },

        getData() {
            if (!this.servicecompany || this.servicecompany.length === 0) {
                console.warn('⚠️ No se ha cargado servicecompany aún. Aborting getData().');
                return;
            }
//            console.log(`/${this.resource}/records`)
            this.$http.get(`/${this.resource}/records`).then(response => {
                    // Convertimos servicecompany en un diccionario para acceso rápido
//                    console.log(response.data.data)
                    const serviceMap = this.servicecompany.reduce((map, sc) => {
                        map[String(sc.identification_number)] = sc;
                        return map;
                    }, {});
                    // Construimos el array de records usando el mapa
                    const rawRecords = response.data.data.map(company => {
                    const serviceCompany = serviceMap[String(company.identification_number)];
                    return {
                        ...company,
                        user_id: serviceCompany ? serviceCompany.user_id : null
                    };
                });

                // Procesamos los datos para convertir valores de switches a booleanos
                this.records = this.processRecordsData(rawRecords);

                // Debug: Verificar los valores de los switches
                console.log('🔍 Valores de switches después del procesamiento:',
                    this.records.slice(0, 3).map(r => ({
                        id: r.id,
                        locked_user: r.locked_user,
                        locked: r.locked,
                        locked_emission: r.locked_emission,
                        allow_seller_login: r.allow_seller_login,
                        tipos: {
                            locked_user: typeof r.locked_user,
                            locked: typeof r.locked,
                            locked_emission: typeof r.locked_emission,
                            allow_seller_login: typeof r.allow_seller_login
                        }
                    }))
                );
            });
//            console.log(this.records)
        },

        clickCreate(recordId = null) {
            this.recordId = recordId;
            this.showDialog = true;
        },
        clickPayments(recordId = null) {
            this.recordId = recordId;
            this.showDialogPayments = true;
        },
        clickAccountStatus(recordId = null) {
            this.recordId = recordId;
            this.showDialogAccountStatus = true;
        },
        clickPassword(id) {
            this.change(`/${this.resource}/password/${id}`);
        },
        clickDelete(id) {
            this.destroy(`/${this.resource}/${id}`).then(() =>
                this.$eventHub.$emit("reloadData")
            );
        },
        clickEdit(recordId) {
            this.recordId = recordId;
            this.showDialog = true;
        }
    }
};
</script>

<style scoped>
/* Modern Dashboard Styles */
.modern-dashboard {
    background: #f8fafc;
    min-height: 100vh;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Header Styles */
.dashboard-header {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.page-title {
    font-size: 1.875rem;
    font-weight: 700;
    color: #1a202c;
    margin: 0;
}

.page-subtitle {
    color: #718096;
    font-size: 1rem;
    margin-top: 4px;
    margin-bottom: 0;
}

.btn-modern {
    background: #667eea;
    border: none;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-modern:hover {
    background: #5a67d8;
    transform: translateY(-1px);
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
}

.stat-icon.bg-primary { background: #667eea; }
.stat-icon.bg-info { background: #4299e1; }
.stat-icon.bg-warning { background: #ed8936; }

.stat-content .stat-number {
    font-size: 1.875rem;
    font-weight: 700;
    color: #1a202c;
    line-height: 1;
}

.stat-content .stat-label {
    font-size: 0.875rem;
    color: #718096;
    margin-top: 4px;
}

/* Table Styles */
.table-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.table-container {
    overflow-x: auto;
}

.table-modern {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}

/* Table Title Row */
.table-title-header {
    background: #f7fafc;
    border-bottom: 2px solid #e2e8f0;
    padding: 20px 24px;
}

.table-title-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-title-content h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #2d3748;
}

.record-count {
    background: #667eea;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 500;
}

/* Table Headers */
.th-modern {
    background: #f7fafc;
    color: #4a5568;
    font-weight: 600;
    padding: 8px 6px;
    text-align: left;
    font-size: 0.8rem;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
    line-height: 1.2;
}

/* Table Cells */
.td-modern {
    padding: 8px 6px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    font-size: 0.8rem;
    line-height: 1.2;
}

.tr-modern:hover {
    background: #f8fafc;
}

/* Content Styles */
.row-number {
    font-weight: 600;
    color: #718096;
}

.identification-text {
    font-family: monospace;
    background: #edf2f7;
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 0.75rem;
}

.company-name {
    color: #2d3748;
    font-weight: 600;
    font-size: 0.8rem;
}

.email-text {
    color: #4a5568;
    font-size: 0.75rem;
}

.subdomain-text {
    color: #667eea;
    font-weight: 500;
    font-size: 0.75rem;
}

.plan-text {
    background: #e6fffa;
    color: #234e52;
    padding: 1px 4px;
    border-radius: 8px;
    font-size: 0.7rem;
    font-weight: 500;
}

.limit-text {
    font-weight: 600;
    color: #2d3748;
    font-size: 0.75rem;
}

.doc-count {
    font-weight: 600;
    color: #2d3748;
    font-size: 0.75rem;
}

.billing-date {
    background: #e6fffa;
    color: #234e52;
    padding: 2px 4px;
    border-radius: 4px;
    font-size: 0.7rem;
}

/* Action Buttons */
.action-buttons-modern {
    display: flex;
    gap: 2px;
    justify-content: center;
    flex-wrap: wrap;
}

.action-buttons-modern .btn {
    padding: 4px 6px;
    font-size: 0.75rem;
    line-height: 1;
    min-width: auto;
}

.btn-outline-success {
    color: #38a169;
    border-color: #38a169;
}

.btn-outline-success:hover {
    background: #38a169;
    color: white;
}

.btn-outline-primary {
    color: #667eea;
    border-color: #667eea;
}

.btn-outline-primary:hover {
    background: #667eea;
    color: white;
}

.btn-outline-danger {
    color: #e53e3e;
    border-color: #e53e3e;
}

.btn-outline-danger:hover {
    background: #e53e3e;
    color: white;
}

.btn-outline-warning {
    color: #d69e2e;
    border-color: #d69e2e;
}

.btn-outline-warning:hover {
    background: #d69e2e;
    color: white;
}

.btn-outline-info {
    color: #3182ce;
    border-color: #3182ce;
}

.btn-outline-info:hover {
    background: #3182ce;
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .modern-dashboard {
        padding: 10px;
    }

    .header-content {
        flex-direction: column;
        text-align: center;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .table-title-content {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }

    .th-modern,
    .td-modern {
        padding: 6px 4px;
        font-size: 0.7rem;
    }

    .action-buttons-modern {
        flex-direction: column;
        gap: 1px;
    }

    .action-buttons-modern .btn {
        padding: 3px 5px;
        font-size: 0.65rem;
    }
}
</style>
