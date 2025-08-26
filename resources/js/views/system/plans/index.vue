<template>
    <modern-dashboard-layout>
        <!-- Breadcrumb personalizado con glassmorphism -->
        <div class="custom-page-header">
            <div class="breadcrumb-section">
                <h2><i class="fas fa-credit-card"></i></h2>
                <ol class="custom-breadcrumbs">
                    <li class="active"><span>Planes</span></li>
                </ol>
            </div>
            <div class="actions-section">
                <button
                    type="button"
                    class="btn-nuevo-plan"
                    @click.prevent="clickCreate()"
                >
                    <i class="fas fa-plus-circle"></i> Nuevo Plan
                </button>
            </div>
        </div>

        <div class="plans-container">
            <div class="pricing-table row no-gutters mt-3 mb-3">
                <div
                    v-for="(row, index) in records"
                    :key="index"
                    class="col-lg-3 col-sm-6 text-center"
                    style="padding:10px;"
                >
                    <div class="plan most-popular">
                        <div class="plan-ribbon-wrapper "></div>
                        <h3>
                            {{ row.name }}<span>S/ {{ row.pricing }}</span>
                        </h3>
                        <ul>
                            <li v-if="row.limit_users === 0">
                                <strong>Usuarios</strong> ilimitados
                            </li>
                            <li v-else>
                                <strong>{{ row.limit_users }}</strong> usuarios
                            </li>

                            <li v-if="row.limit_documents === 0">
                                <strong>Comprobantes</strong> ilimitados
                            </li>
                            <li v-else>
                                <strong>{{ row.limit_documents }}</strong>
                                comprobantes
                            </li>
                        </ul>
                        <div v-if="!row.locked">
                            <button
                                type="button"
                                class="btn waves-effect waves-light btn-xs btn-danger float-right"
                                style="margin-left:6px;"
                                @click.prevent="clickDelete(row.id)"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                            <button
                                type="button"
                                class="btn waves-effect waves-light btn-xs btn-primary float-right"
                                @click.prevent="clickCreate(row.id)"
                            >
                                <i class="fas fa-edit"></i></button
                            ><br />
                        </div>
                    </div>
                </div>
            </div>

            <system-plans-form
                :showDialog.sync="showDialog"
                :plan_documents="plan_documents"
                :recordId="recordId"
            ></system-plans-form>
        </div>
    </modern-dashboard-layout>
</template>

<script>
import PlansForm from "./form.vue";
import ModernDashboardLayout from "../../../components/layouts/ModernDashboardLayout.vue";
import { deletable } from "../../../mixins/deletable";

export default {
    mixins: [deletable],
    components: {
        "system-plans-form": PlansForm,
        ModernDashboardLayout
    },
    data() {
        return {
            showDialog: false,
            resource: "plans",
            recordId: null,
            records: [],
            plan_documents: [],
            aux: []
        };
    },
    created() {
        this.$eventHub.$on("reloadData", () => {
            this.getData();
        });
        this.getData();
        this.getPlanDocuments();
    },
    mounted() {
        // Agregar clase específica al body para identificar la página de planes
        document.body.classList.add("plans-page");
        // Aplicar estilos de sidebar tradicional cuando estamos en plans
        this.applySidebarStyles();
    },
    beforeDestroy() {
        // Remover clase específica del body
        document.body.classList.remove("plans-page");
        // Restaurar estilos cuando salimos del componente
        this.restoreSidebarStyles();
    },
    methods: {
        applySidebarStyles() {
            // Primero agregar clase específica al body
            document.body.classList.add("plans-page");

            // Inspeccionar las clases del HTML para debug
            console.log("HTML classes:", document.documentElement.className);
            console.log("Body classes:", document.body.className);

            const sidebar = document.querySelector(".sidebar-left");
            if (sidebar) {
                console.log("Sidebar found, current styles:", {
                    background: getComputedStyle(sidebar).background,
                    backdropFilter: getComputedStyle(sidebar).backdropFilter
                });

                // Remover clases que causan el glassmorphism
                document.documentElement.classList.remove(
                    "sidebar-left-big-icons"
                );
                document.body.classList.remove("sidebar-left-big-icons");

                // Forzar estilos directamente con máxima especificidad
                const style = document.createElement("style");
                style.innerHTML = `
                    body.plans-page .sidebar-left {
                        background: #f4f4f4 !important;
                        backdrop-filter: none !important;
                        -webkit-backdrop-filter: none !important;
                        border-right: 1px solid #e0e0e0 !important;
                    }
                    body.plans-page .sidebar-left .sidebar-nav li a {
                        color: #333 !important;
                        background: transparent !important;
                        display: flex !important;
                        align-items: center !important;
                        flex-direction: row !important;
                        padding: 12px 15px !important;
                        font-size: 14px !important;
                    }
                    body.plans-page .sidebar-left .sidebar-nav li a i {
                        font-size: 16px !important;
                        margin-right: 10px !important;
                        width: 20px !important;
                        text-align: center !important;
                    }
                `;
                document.head.appendChild(style);

                console.log(
                    "Styles applied, new background:",
                    getComputedStyle(sidebar).background
                );
            } else {
                console.log("Sidebar not found");
            }
        },
        restoreSidebarStyles() {
            // Remover clase específica del body
            document.body.classList.remove("plans-page");

            // Restaurar clase si era necesaria
            if (
                !document.documentElement.classList.contains(
                    "sidebar-left-big-icons"
                )
            ) {
                document.documentElement.classList.add(
                    "sidebar-left-big-icons"
                );
            }

            // Remover estilos inline si los agregamos
            const customStyle = document.querySelector(
                "style[data-plans-sidebar]"
            );
            if (customStyle) {
                customStyle.remove();
            }
        },
        getPlanDocuments() {
            this.$http.get(`/${this.resource}/tables`).then(response => {
                this.plan_documents = response.data.plan_documents;
            });
        },
        getData() {
            this.$http.get(`/${this.resource}/records`).then(response => {
                this.records = response.data.data;
            });
        },
        getDescriptions(plan_documents) {
            let descriptions = [];
            Object.values(plan_documents).forEach((itm, i) => {
                descriptions.push(this.plan_documents[itm - 1]);
            });
            return descriptions;
        },
        clickCreate(recordId = null) {
            this.recordId = recordId;
            this.showDialog = true;
        },
        clickDelete(id) {
            this.destroy(`/${this.resource}/${id}`).then(() =>
                this.$eventHub.$emit("reloadData")
            );
        }
    }
};
</script>

<style scoped>
/* Contenedor principal */
.plans-container {
    padding: 0;
}

/* Forzar sidebar igual que en dashboard /co-companies */
.sidebar-left {
    background: #f4f4f4 !important;
    backdrop-filter: none !important;
    border-right: 1px solid #e0e0e0 !important;
}

.sidebar-left .sidebar-nav li a {
    color: #333 !important;
    background: transparent !important;
    border-radius: 0 !important;
    margin: 0 !important;
    padding: 12px 15px !important;
    display: flex !important;
    align-items: center !important;
    font-size: 14px !important;
}

.sidebar-left .sidebar-nav li a:hover,
.sidebar-left .sidebar-nav li.active > a {
    background: #e9ecef !important;
    color: #333 !important;
    border-radius: 0 !important;
    margin: 0 !important;
}

.sidebar-left .sidebar-nav li a i {
    width: 20px !important;
    height: 20px !important;
    font-size: 16px !important;
    margin-right: 10px !important;
    margin-bottom: 0 !important;
    display: inline-block !important;
    text-align: center !important;
    line-height: 20px !important;
}

.sidebar-left .sidebar-nav li a span {
    margin-left: 0 !important;
    display: inline-block !important;
    vertical-align: middle !important;
}

/* Asegurar que los iconos no se vean grandes */
.sidebar-left .sidebar-nav li a .fa,
.sidebar-left .sidebar-nav li a .fas,
.sidebar-left .sidebar-nav li a .far {
    font-size: 16px !important;
    width: auto !important;
    height: auto !important;
}

/* Breadcrumb personalizado con glassmorphism */
.custom-page-header {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.breadcrumb-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.breadcrumb-section h2 {
    color: white;
    margin: 0;
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
}

.breadcrumb-section i {
    color: #4299e1;
}

.custom-breadcrumbs {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    align-items: center;
}

.custom-breadcrumbs li {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
}

.custom-breadcrumbs li.active {
    color: white;
    font-weight: 600;
}

.custom-breadcrumbs li.active span {
    color: white;
    font-weight: 600;
    font-size: 1.1rem;
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
}

.actions-section {
    display: flex;
    align-items: center;
}

.btn-nuevo-plan {
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
    border: none;
    border-radius: 10px;
    color: white;
    padding: 0.75rem 1.5rem;
    font-size: 0.9rem;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(66, 153, 225, 0.3);
    transition: all 0.3s ease;
    cursor: pointer;
}

.btn-nuevo-plan:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(66, 153, 225, 0.4);
    color: white;
}

.btn-nuevo-plan i {
    margin-right: 0.5rem;
}

/* Tabla de precios */
.pricing-table {
    margin: 0;
}

/* Cards de planes */
.plan {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(15px);
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    padding: 2rem 1.5rem;
    margin: 0.5rem;
    color: white;
    transition: all 0.3s ease;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    position: relative;
    overflow: hidden;
}

.plan:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
    border-color: rgba(255, 255, 255, 0.4);
}

.plan.most-popular {
    border-color: rgba(102, 126, 234, 0.6);
    background: rgba(102, 126, 234, 0.1);
}

.plan.most-popular:hover {
    border-color: rgba(79, 172, 254, 0.8);
    background: rgba(79, 172, 254, 0.15);
}

/* Títulos de planes */
.plan h3 {
    color: white;
    font-weight: 700;
    font-size: 1.4rem;
    margin-bottom: 1.5rem;
    text-align: center;
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
}

.plan h3 span {
    display: block;
    font-size: 2rem;
    font-weight: 800;
    color: #4299e1;
    margin-top: 0.5rem;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.4);
}

/* Lista de características */
.plan ul {
    list-style: none;
    padding: 0;
    margin: 1.5rem 0 2rem 0;
}

.plan ul li {
    color: white;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    font-weight: 500;
    text-align: center;
    font-size: 0.95rem;
}

.plan ul li:last-child {
    border-bottom: none;
}

.plan ul li strong {
    color: #667eea;
    font-weight: 700;
}

/* Botones de acción */
.plan .btn {
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    padding: 0.5rem 1rem;
    margin: 0.25rem;
}

.plan .btn-primary {
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
    color: white;
    box-shadow: 0 3px 10px rgba(66, 153, 225, 0.3);
}

.plan .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(66, 153, 225, 0.4);
    color: white;
}

.plan .btn-danger {
    background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
    color: white;
    box-shadow: 0 3px 10px rgba(245, 101, 101, 0.3);
}

.plan .btn-danger:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(245, 101, 101, 0.4);
    color: white;
}

/* Ribbon para plan destacado */
.plan-ribbon-wrapper {
    position: absolute;
    top: 15px;
    right: -10px;
    width: 80px;
    height: 80px;
    overflow: hidden;
}

.plan-ribbon-wrapper::before {
    content: "POPULAR";
    position: absolute;
    top: 25px;
    right: -25px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 5px 30px;
    transform: rotate(45deg);
    font-size: 0.75rem;
    font-weight: 700;
    text-align: center;
    box-shadow: 0 3px 10px rgba(102, 126, 234, 0.4);
}

/* Responsivo */
@media (max-width: 768px) {
    .plan {
        margin: 0.25rem;
        padding: 1.5rem 1rem;
    }

    .plan h3 {
        font-size: 1.2rem;
    }

    .plan h3 span {
        font-size: 1.6rem;
    }
}

@media (max-width: 576px) {
    .col-lg-3.col-sm-6 {
        padding: 5px;
    }

    .plan {
        margin: 0.1rem;
        padding: 1rem 0.75rem;
    }
}

/* Animaciones */
.plan {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
