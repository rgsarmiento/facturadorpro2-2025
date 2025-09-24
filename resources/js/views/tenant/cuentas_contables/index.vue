<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a></h2>
            <ol class="breadcrumbs">
                <li><a href="/dashboard">Inicio</a></li>
                <li><a href="#" @click.prevent>Contabilidad</a></li>
                <li class="active"><span>Plan de Cuentas</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <template v-if="typeUser === 'admin'">
                    <button type="button" class="btn btn-custom btn-sm mt-2 mr-2" @click.prevent="clickExport()">
                        <i class="fa fa-download"></i> Exportar
                    </button>
                    <button type="button" class="btn btn-custom btn-sm mt-2 mr-2" @click.prevent="clickImportPuc()">
                        <i class="fa fa-upload"></i> Importar PUC
                    </button>
                    <!-- Botón temporalmente oculto - Vista Lista deshabilitada por ahora -->
                    <!--
                    <button type="button" class="btn btn-custom btn-sm mt-2 mr-2" @click.prevent="toggleViewMode()">
                        <i :class="viewMode === 'tree' ? 'fa fa-list' : 'fa fa-sitemap'"></i>
                        {{ viewMode === 'tree' ? 'Vista Lista' : 'Vista Árbol' }}
                    </button>
                    -->
                    <button type="button" class="btn btn-custom btn-sm mt-2 mr-2" @click.prevent="clickCreate()">
                        <i class="fa fa-plus-circle"></i> Nueva Cuenta
                    </button>
                </template>
            </div>
        </div>

        <div class="card mb-0">
            <div class="card-header bg-info">
                <h3 class="my-0">Plan de Cuentas Contables</h3>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Tipo de Cuenta:</label>
                        <select class="form-control" v-model="filters.tipo_cuenta" @change="loadRecords">
                            <option value="">Todos</option>
                            <option v-for="tipo in tipos_cuenta" :key="tipo" :value="tipo">{{ tipo | capitalize }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Naturaleza:</label>
                        <select class="form-control" v-model="filters.naturaleza" @change="loadRecords">
                            <option value="">Todas</option>
                            <option v-for="naturaleza in naturalezas" :key="naturaleza" :value="naturaleza">{{ naturaleza | capitalize }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Estado:</label>
                        <select class="form-control" v-model="filters.activa" @change="loadRecords">
                            <option value="">Todos</option>
                            <option value="1">Activas</option>
                            <option value="0">Inactivas</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Búsqueda:</label>
                        <input type="text" class="form-control" v-model="filters.search" @keyup="debounceSearch" placeholder="Código o nombre...">
                    </div>
                </div>

                <!-- Botón para limpiar filtros -->
                <div class="row mb-3">
                    <div class="col-md-12 text-right">
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="clearFilters">
                            <i class="fa fa-times"></i> Limpiar Filtros
                        </button>
                        <small class="ml-2 text-muted" v-if="hasActiveFilters">
                            Filtros activos
                        </small>
                    </div>
                </div>

                <!-- Vista Árbol -->
                <div v-if="viewMode === 'tree'" class="tree-view">
                    <div v-if="loading" class="text-center">
                        <i class="fa fa-spinner fa-spin"></i> Cargando...
                    </div>
                    <div v-else>
                        <tree-node
                            v-for="cuenta in treeData"
                            :key="cuenta.id"
                            :node="cuenta"
                            @edit="editCuenta"
                            @delete="deleteCuenta"
                        ></tree-node>
                    </div>
                </div>

                <!-- Vista Lista -->
                <div v-else>
                    <data-table ref="dataTable" :resource="resource" :external-filters="filters" @clicked="clickActions">
                        <tr slot="heading">
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Naturaleza</th>
                            <th>Nivel</th>
                            <th>Cuenta Padre</th>
                            <th class="text-right">Saldo Actual</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Movimiento</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                        <tr slot-scope="{ row }" :class="{ 'table-warning': !row.activa }">
                            <td><strong>{{ row.codigo }}</strong></td>
                            <td>{{ row.nombre }}</td>
                            <td>
                                <span class="badge" :class="getBadgeClass(row.tipo_cuenta)">
                                    {{ row.tipo_cuenta | capitalize }}
                                </span>
                            </td>
                            <td>
                                <span class="badge" :class="row.naturaleza === 'debito' ? 'badge-primary' : 'badge-info'">
                                    {{ row.naturaleza | capitalize }}
                                </span>
                            </td>
                            <td>{{ row.nivel }}</td>
                            <td>{{ row.cuenta_padre ? row.cuenta_padre.codigo + ' - ' + row.cuenta_padre.nombre : '-' }}</td>
                            <td class="text-right">{{ formatCurrency(row.saldo_actual) }}</td>
                            <td class="text-center">
                                <span :class="row.activa ? 'badge badge-success' : 'badge badge-secondary'">
                                    {{ row.activa ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span :class="row.permite_movimiento ? 'badge badge-success' : 'badge badge-warning'">
                                    {{ row.permite_movimiento ? 'Sí' : 'No' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <button class="btn btn-outline-info btn-sm" @click="editCuenta(row.id)" title="Editar">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm ml-1" @click="deleteCuenta(row.id)" title="Eliminar">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </data-table>
                </div>
            </div>
        </div>

        <!-- Modal de Importación PUC usando Element UI -->
        <el-dialog
            title="Importar Plan Único de Cuentas (PUC)"
            :visible.sync="showImportModal"
            width="500px"
            :close-on-click-modal="false">

            <el-form @submit.native.prevent="importPuc">
                <el-form-item label="Archivo PUC:">
                    <el-upload
                        ref="fileUpload"
                        class="upload-demo"
                        action=""
                        :auto-upload="false"
                        :file-list="fileList"
                        :on-change="handleFileChange"
                        :on-remove="handleFileRemove"
                        accept=".csv,.xlsx,.xls">
                        <el-button size="small" type="primary">Seleccionar archivo</el-button>
                        <div slot="tip" class="el-upload__tip">
                            Formato esperado: Código, Nombre, Tipo, Naturaleza, Nivel, Código Padre<br>
                            <strong>Descargar plantilla de ejemplo:</strong><br>
                            <a href="/contabilidad/cuentas-contables/download-template?format=csv" target="_blank" style="color: #409EFF; text-decoration: underline; margin-right: 15px;">
                                <i class="el-icon-download"></i> CSV
                            </a>
                            <a href="/contabilidad/cuentas-contables/download-template?format=excel" target="_blank" style="color: #409EFF; text-decoration: underline;">
                                <i class="el-icon-download"></i> Excel
                            </a>
                        </div>
                    </el-upload>
                </el-form-item>

                <el-form-item>
                    <el-checkbox v-model="importOptions.sobreescribir">
                        Sobreescribir cuentas existentes
                    </el-checkbox>
                </el-form-item>
            </el-form>

            <div slot="footer" class="dialog-footer">
                <el-button @click="closeImportModal">Cancelar</el-button>
                <el-button type="primary" @click="importPuc" :loading="importing">
                    {{ importing ? 'Importando...' : 'Importar' }}
                </el-button>
            </div>
        </el-dialog>
    </div>
</template>

<script>
import TreeNode from './TreeNode.vue'
import DataTable from '../../../components/DataTable.vue'

export default {
    components: {
        TreeNode,
        DataTable
    },
    props: ['typeUser'],
    data() {
        return {
            resource: 'contabilidad/cuentas-contables',
            viewMode: 'tree', // 'list' o 'tree' - Vista árbol por defecto
            treeData: [],
            loading: false,
            importing: false,
            showImportModal: false,
            fileList: [],
            filters: {
                tipo_cuenta: '',
                naturaleza: '',
                activa: '',
                search: ''
            },
            searchTimeout: null,
            tipos_cuenta: [],
            naturalezas: [],
            importOptions: {
                sobreescribir: false
            }
        }
    },
    computed: {
        hasActiveFilters() {
            return this.filters.tipo_cuenta ||
                   this.filters.naturaleza ||
                   this.filters.activa !== '' ||
                   this.filters.search
        }
    },
    async created() {
        await this.loadTables()
    },
    async mounted() {
        // Cargar datos iniciales cuando se monta el componente
        await this.refreshData()

        // Si estábamos editando y regresamos, recargar datos
        if (sessionStorage.getItem('editing_cuenta') === 'true') {
            sessionStorage.removeItem('editing_cuenta')
            await this.refreshData()
        }
    },
    beforeRouteEnter(to, from, next) {
        next(vm => {
            // Si venimos de una página de edición, recargar datos
            if (from.path && from.path.includes('/edit')) {
                vm.refreshData()
            }
        })
    },
    async beforeRouteUpdate(to, from, next) {
        // Si actualizamos la ruta (ej: regresamos de edición), recargar datos
        if (from.path && from.path.includes('/edit')) {
            await this.refreshData()
        }
        next()
    },
    methods: {
        async loadTables() {
            try {
                const response = await axios.get('/contabilidad/cuentas-contables/tables')
                this.tipos_cuenta = response.data.data.tipos_cuenta
                this.naturalezas = response.data.data.naturalezas
            } catch (error) {
                console.error('Error loading tables:', error)
            }
        },
        async loadRecords() {
            // Para vista lista, refrescar el data-table con los filtros actuales
            if (this.viewMode === 'tree') {
                await this.loadTreeData()
            } else {
                if (this.$refs.dataTable) {
                    await this.$refs.dataTable.getRecords()
                }
            }
        },
        async refreshData() {
            // Método para forzar actualización de todas las vistas
            if (this.viewMode === 'tree') {
                await this.loadTreeData()
            } else {
                // Para vista de lista, probar múltiples estrategias
                try {
                    // Estrategia 1: Event hub
                    if (this.$eventHub) {
                        this.$eventHub.$emit('reloadData')
                    }

                    // Estrategia 2: Acceso directo por referencia
                    if (this.$refs.dataTable && this.$refs.dataTable.getRecords) {
                        await this.$refs.dataTable.getRecords()
                    }

                    // Estrategia 3: Buscar en componentes hijos
                    const dataTableComponent = this.$children.find(child =>
                        child.$options.name === 'DataTable' ||
                        child.$el.classList.contains('data-table') ||
                        child.getRecords
                    )
                    if (dataTableComponent && dataTableComponent.getRecords) {
                        await dataTableComponent.getRecords()
                    }

                    // Estrategia 4: Emit evento a todos los hijos
                    this.$children.forEach(child => {
                        if (child.getRecords) {
                            child.getRecords()
                        }
                    })

                } catch (error) {
                    console.error('Error refreshing data:', error)
                    // Como último recurso, recargar la página
                    window.location.reload()
                }
            }
        },
        async loadTreeData() {
            this.loading = true
            try {
                // Construir parámetros de filtro
                const params = new URLSearchParams()

                if (this.filters.tipo_cuenta) {
                    params.append('tipo_cuenta', this.filters.tipo_cuenta)
                }
                if (this.filters.naturaleza) {
                    params.append('naturaleza', this.filters.naturaleza)
                }
                if (this.filters.activa !== '') {
                    params.append('activa', this.filters.activa)
                }
                if (this.filters.search) {
                    params.append('search', this.filters.search)
                }

                const url = '/contabilidad/cuentas-contables/tree' + (params.toString() ? '?' + params.toString() : '')
                const response = await axios.get(url)
                this.treeData = response.data.data
            } catch (error) {
                console.error('Error loading tree data:', error)
                this.$message.error('Error al cargar la estructura del árbol')
            } finally {
                this.loading = false
            }
        },
        toggleViewMode() {
            this.viewMode = this.viewMode === 'tree' ? 'list' : 'tree'
            if (this.viewMode === 'tree') {
                this.loadTreeData()
            }
        },
        debounceSearch() {
            clearTimeout(this.searchTimeout)
            this.searchTimeout = setTimeout(() => {
                this.loadRecords()
            }, 500)
        },
        clearFilters() {
            this.filters = {
                tipo_cuenta: '',
                naturaleza: '',
                activa: '',
                search: ''
            }
            this.loadRecords()
        },
        clickCreate() {
            window.location.href = '/contabilidad/cuentas-contables/create'
        },
        editCuenta(id) {
            // Antes de redirigir, guardar que estamos editando
            sessionStorage.setItem('editing_cuenta', 'true')
            window.location.href = `/contabilidad/cuentas-contables/${id}/edit`
        },
        async deleteCuenta(id) {
            this.$confirm('¿Está seguro de eliminar esta cuenta contable?', 'Confirmar Eliminación', {
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar',
                type: 'warning'
            }).then(async () => {
                try {
                    await axios.delete(`/contabilidad/cuentas-contables/${id}`)
                    this.$message.success('Cuenta eliminada exitosamente')
                    // Usar refreshData en lugar de loadRecords
                    await this.refreshData()
                } catch (error) {
                    this.$message.error(error.response?.data?.message || 'Error al eliminar la cuenta')
                }
            }).catch(() => {
                // Usuario canceló la eliminación
            })
        },
        clickExport() {
            window.open('/contabilidad/cuentas-contables/export', '_blank')
        },
        clickImportPuc() {
            this.showImportModal = true
            this.fileList = []
        },
        closeImportModal() {
            this.showImportModal = false
            this.fileList = []
        },
        handleFileChange(file, fileList) {
            this.fileList = fileList
        },
        handleFileRemove(file, fileList) {
            this.fileList = fileList
        },
        async importPuc() {
            if (!this.fileList.length) {
                this.$message.error('Seleccione un archivo')
                return
            }

            this.importing = true
            const formData = new FormData()
            formData.append('archivo', this.fileList[0].raw)
            // Enviar como string '1' o '0' en lugar de boolean true/false
            formData.append('sobreescribir', this.importOptions.sobreescribir ? '1' : '0')

            // Añadir token CSRF manualmente para FormData
            const token = document.head.querySelector('meta[name="csrf-token"]')
            if (token) {
                formData.append('_token', token.content)
            }

            try {
                const response = await axios.post('/contabilidad/cuentas-contables/import-puc', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })

                // Mostrar mensaje de éxito con detalles si los hay
                if (response.data.data) {
                    const { importadas, actualizadas, errores } = response.data.data
                    let message = `PUC importado exitosamente: ${importadas} cuentas importadas`
                    if (actualizadas > 0) {
                        message += `, ${actualizadas} cuentas actualizadas`
                    }
                    if (errores.length > 0) {
                        message += `. ${errores.length} errores encontrados`
                    }
                    this.$message.success(message)
                } else {
                    this.$message.success('PUC importado exitosamente')
                }

                this.closeImportModal()

                // Forzar actualización de ambas vistas
                await this.refreshData()

            } catch (error) {
                console.error('Error importing PUC:', error)
                this.$message.error(error.response?.data?.message || 'Error al importar PUC')
            } finally {
                this.importing = false
            }
        },
        getBadgeClass(tipo) {
            const classes = {
                'activo': 'badge-success',
                'pasivo': 'badge-danger',
                'patrimonio': 'badge-primary',
                'ingreso': 'badge-info',
                'gasto': 'badge-warning',
                'costo': 'badge-dark'
            }
            return classes[tipo] || 'badge-secondary'
        },
        formatCurrency(amount) {
            return new Intl.NumberFormat('es-CO', {
                style: 'currency',
                currency: 'COP'
            }).format(amount || 0)
        },
        clickActions(row) {
            // Manejar clicks en acciones específicas si es necesario
        }
    },
    filters: {
        capitalize(value) {
            if (!value) return ''
            return value.charAt(0).toUpperCase() + value.slice(1)
        }
    }
}
</script>

<style scoped>
.tree-view {
    max-height: 70vh;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 10px;
}

.disable_color {
    opacity: 0.6;
}

.table-warning {
    background-color: rgba(255, 193, 7, 0.1);
}

/* Estilos para la tabla con scroll */
.table-responsive {
    max-height: 70vh;
    overflow-y: auto;
    overflow-x: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    position: relative;
}

.table-responsive .table {
    margin-bottom: 0;
    min-width: 100%;
}

.table-responsive .table thead th {
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 10;
    border-bottom: 2px solid #dee2e6;
    box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
}

/* Mejorar la apariencia de la barra de desplazamiento */
.table-responsive::-webkit-scrollbar {
    width: 12px;
    height: 12px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 6px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #c0c0c0;
    border-radius: 6px;
    border: 2px solid #f1f1f1;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #a0a0a0;
}

.table-responsive::-webkit-scrollbar-corner {
    background: #f1f1f1;
}

/* Mejorar filtros */
.mb-3 .form-control {
    border-radius: 0.25rem;
    border: 1px solid #ced4da;
}

.mb-3 label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 5px;
}
</style>
