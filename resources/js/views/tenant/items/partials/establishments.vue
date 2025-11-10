<template>
    <el-dialog
        title="Asignar Establecimientos"
        :visible="showDialog"
        @close="close"
        width="600px"
        :close-on-click-modal="false">

        <div v-if="loading" class="text-center">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Cargando establecimientos...</p>
        </div>

        <div v-else>
            <!-- Información del artículo -->
            <div class="mb-3" v-if="itemInfo">
                <p><strong>Artículo:</strong> {{ itemInfo.name }}</p>
                <p><strong>Código:</strong> {{ itemInfo.internal_id || 'Sin código' }}</p>
                <hr>
            </div>

            <!-- Selector de establecimientos -->
            <div class="form-group">
                <label class="control-label">
                    <strong>Selecciona los establecimientos donde será visible este artículo:</strong>
                </label>

                <div v-if="establishments.length === 0" class="alert alert-warning">
                    No hay establecimientos disponibles
                </div>

                <div v-else class="establishments-list">
                    <el-checkbox
                        v-for="establishment in establishments"
                        :key="establishment.id"
                        v-model="establishment.assigned"
                        class="mb-2 d-block">
                        <span>{{ establishment.name }}</span>
                        <small v-if="establishment.description" class="text-muted ml-2">
                            ({{ establishment.description }})
                        </small>
                    </el-checkbox>
                </div>
            </div>

            <!-- Información de ayuda -->
            <div class="alert alert-info mt-3">
                <small>
                    <i class="fas fa-info-circle"></i>
                    Al asignar establecimientos, el artículo será visible para usuarios de esos establecimientos.
                    Se crearán automáticamente los registros en los almacenes correspondientes.
                </small>
            </div>
        </div>

        <!-- Acciones -->
        <span slot="footer" class="dialog-footer">
            <el-button @click="close">Cancelar</el-button>
            <el-button type="primary" @click="save" :loading="saving">
                Guardar Cambios
            </el-button>
        </span>
    </el-dialog>
</template>

<script>
export default {
    props: {
        showDialog: {
            type: Boolean,
            default: false
        },
        itemId: {
            type: [Number, String],
            default: null
        }
    },
    data() {
        return {
            loading: false,
            saving: false,
            establishments: [],
            itemInfo: null,
            resource: 'items'
        }
    },
    watch: {
        showDialog(newVal) {
            if (newVal && this.itemId) {
                this.loadEstablishments()
            }
        }
    },
    methods: {
        async loadEstablishments() {
            this.loading = true
            try {
                const response = await this.$http.get(`/${this.resource}/establishments/${this.itemId}`)

                if (response.data.success) {
                    this.itemInfo = response.data.item
                    this.establishments = response.data.establishments
                } else {
                    this.$message.error(response.data.message || 'Error al cargar los establecimientos')
                }
            } catch (error) {
                this.$message.error('Error al cargar los establecimientos')
            } finally {
                this.loading = false
            }
        },
        async save() {
            this.saving = true
            try {
                const establishmentIds = this.establishments
                    .filter(e => e.assigned)
                    .map(e => e.id)

                if (establishmentIds.length === 0) {
                    this.$message.warning('Debes seleccionar al menos un establecimiento')
                    this.saving = false
                    return
                }

                const response = await this.$http.post(
                    `/${this.resource}/establishments/${this.itemId}`,
                    { establishment_ids: establishmentIds }
                )

                if (response.data.success) {
                    this.$message.success(response.data.message)
                    this.$eventHub.$emit('reloadData')
                    this.close()
                } else {
                    this.$message.error(response.data.message || 'Error al guardar')
                }
            } catch (error) {
                this.$message.error('Error al guardar los cambios')
            } finally {
                this.saving = false
            }
        },
        close() {
            this.$emit('update:showDialog', false)
            this.establishments = []
            this.itemInfo = null
        }
    }
}
</script>

<style scoped>
.establishments-list {
    max-height: 400px;
    overflow-y: auto;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.mb-2 {
    margin-bottom: 10px;
}

.d-block {
    display: block;
}

.text-muted {
    color: #999;
}

.ml-2 {
    margin-left: 8px;
}
</style>
