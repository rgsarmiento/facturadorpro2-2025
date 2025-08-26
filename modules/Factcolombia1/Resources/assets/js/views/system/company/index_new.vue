<template>
    <div class="clean-dashboard">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-content">
                <div>
                    <h1 class="page-title">Panel de Control</h1>
                    <p class="page-subtitle">Gestión de empresas y monitoreo del sistema</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary btn-clean" @click="clickCreate()">
                        <i class="fa fa-plus"></i> Nueva Empresa
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Table -->
        <div class="table-card">
            <div class="table-container">
                <table class="table table-clean">
                    <!-- Table Title Header -->
                    <thead>
                        <tr>
                            <th colspan="16" class="table-title-header">
                                <div class="table-title-content">
                                    <h3>Listado de Compañías</h3>
                                    <span class="record-count">{{ records.length }} registros</span>
                                </div>
                            </th>
                        </tr>
                        <!-- Column Headers -->
                        <tr>
                            <th class="th-clean">#</th>
                            <th class="th-clean">identificación</th>
                            <th class="th-clean">empresa</th>
                            <th class="th-clean">email</th>
                            <th class="th-clean">subdominio</th>
                            <th class="th-clean">plan</th>
                            <th class="th-clean">límite docs</th>
                            <th class="th-clean">límite usuarios</th>
                            <th class="th-clean">docs</th>
                            <th class="th-clean">acciones</th>
                            <th class="th-clean">usuario</th>
                            <th class="th-clean">usuario bloqueado</th>
                            <th class="th-clean">tenant bloqueado</th>
                            <th class="th-clean">emisión bloqueada</th>
                            <th class="th-clean">vendedor puede ingresar</th>
                            <th class="th-clean">facturación</th>
                            <th class="th-clean">pagos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in records" :key="row.id" class="tr-clean">
                            <td class="td-clean">
                                <span class="row-number">{{ index + 1 }}</span>
                            </td>
                            <td class="td-clean">
                                <span class="identification-text">{{ row.identification_number }}</span>
                            </td>
                            <td class="td-clean">
                                <span class="company-name">{{ row.name }}</span>
                            </td>
                            <td class="td-clean">
                                <span class="email-text">{{ row.email }}</span>
                            </td>
                            <td class="td-clean">
                                <span class="subdomain-text">{{ row.hostname }}</span>
                            </td>
                            <td class="td-clean">
                                <span class="plan-text">{{ row.plan }}</span>
                            </td>
                            <td class="td-clean">
                                <span class="limit-text">{{ row.limit_documents }}</span>
                            </td>
                            <td class="td-clean">
                                <span class="limit-text">{{ row.limit_users }}</span>
                            </td>
                            <td class="td-clean">
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
                            <td class="td-clean">
                                <div class="action-buttons-simple">
                                    <button class="btn btn-outline-success btn-sm" @click="switchToTenant(row.id)" title="Cambiar empresa">
                                        <i class="fa fa-exchange"></i>
                                    </button>
                                    <button class="btn btn-outline-primary btn-sm" @click="clickEdit(row.id)" title="Editar">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-warning btn-sm" @click="clickPassword(row.id)" title="Cambiar contraseña">
                                        <i class="fa fa-key"></i>
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" @click="clickDelete(row.id)" title="Eliminar">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="td-clean">
                                <el-select v-model="row.user_id" placeholder="Seleccionar" size="mini" @change="assignUser(row)">
                                    <el-option label="Sin asignar" :value="null"></el-option>
                                    <el-option v-for="user in users" :key="user.id" :label="`${user.name} ${user.email}`" :value="user.id"></el-option>
                                </el-select>
                            </td>
                            <td class="td-clean">
                                <el-switch v-model="row.locked_user" @change="changeLockedUser(row)" active-color="#E74C3C" inactive-color="#2ECC71" :active-value="1" :inactive-value="0"></el-switch>
                            </td>
                            <td class="td-clean">
                                <el-switch v-model="row.locked_tenant" @change="changeLockedTenant(row)" active-color="#E74C3C" inactive-color="#2ECC71" :active-value="1" :inactive-value="0"></el-switch>
                            </td>
                            <td class="td-clean">
                                <el-switch v-model="row.locked_emission" @change="changeLockedEmission(row)" active-color="#E74C3C" inactive-color="#2ECC71" :active-value="1" :inactive-value="0"></el-switch>
                            </td>
                            <td class="td-clean">
                                <el-switch v-model="row.allow_seller_login_tenant" @change="changeAllowSellerLoginTenant(row)" active-color="#2ECC71" inactive-color="#E74C3C" :active-value="1" :inactive-value="0"></el-switch>
                            </td>
                            <td class="td-clean">
                                <el-date-picker v-model="row.start_billing_cycle" type="date" size="mini" placeholder="Seleccionar" format="dd/MM/yyyy" value-format="yyyy-MM-dd" @change="setStartBillingCycle($event, row.id)"></el-date-picker>
                                <div v-if="row.start_billing_cycle" class="billing-date">{{ row.start_billing_cycle }}</div>
                            </td>
                            <!-- Pagos al final -->
                            <td class="td-clean">
                                <div class="action-buttons-simple">
                                    <button class="btn btn-outline-info btn-sm" @click="clickPayments(row.id)" title="Pagos">
                                        <i class="fa fa-credit-card"></i>
                                    </button>
                                    <button class="btn btn-outline-info btn-sm" @click="clickAccountStatus(row.id)" title="Estado de cuenta">
                                        <i class="fa fa-file-text"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Dialogs -->
        <companies-form v-model="showDialog" :record-id="recordId"></companies-form>
        <client-payments v-model="showDialogPayments" :record-id="recordId"></client-payments>
        <account-status v-model="showDialogAccountStatus" :record-id="recordId"></account-status>
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
                    this.records = response.data.data.map(company => {
                    const serviceCompany = serviceMap[String(company.identification_number)];
                    return {
                        ...company,
                        user_id: serviceCompany ? serviceCompany.user_id : null
                    };
                });
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
/* Clean Dashboard Styles */
.clean-dashboard {
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

.btn-clean {
    background: #667eea;
    border: none;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-clean:hover {
    background: #5a67d8;
    transform: translateY(-1px);
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

.table-clean {
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
.th-clean {
    background: #f7fafc;
    color: #4a5568;
    font-weight: 600;
    padding: 12px 16px;
    text-align: left;
    font-size: 0.875rem;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}

/* Table Cells */
.td-clean {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    font-size: 0.875rem;
}

.tr-clean:hover {
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
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
}

.company-name {
    color: #2d3748;
    font-weight: 600;
}

.email-text {
    color: #4a5568;
}

.subdomain-text {
    color: #667eea;
    font-weight: 500;
}

.plan-text {
    background: #e6fffa;
    color: #234e52;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.limit-text {
    font-weight: 600;
    color: #2d3748;
}

.doc-count {
    font-weight: 600;
    color: #2d3748;
}

.billing-date {
    background: #e6fffa;
    color: #234e52;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.8rem;
}

/* Action Buttons */
.action-buttons-simple {
    display: flex;
    gap: 4px;
    justify-content: center;
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
    .clean-dashboard {
        padding: 10px;
    }

    .header-content {
        flex-direction: column;
        text-align: center;
    }

    .table-title-content {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }

    .th-clean,
    .td-clean {
        padding: 8px 12px;
        font-size: 0.8rem;
    }

    .action-buttons-simple {
        flex-direction: column;
        gap: 2px;
    }
}
</style>
