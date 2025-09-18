<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">Copias de Seguridad</h1>
      <div class="page-options d-flex">
        <el-button
          type="success"
          icon="el-icon-download"
          @click="createBackup"
          :loading="creatingBackup"
        >
          {{ creatingBackup ? 'Creando...' : 'Crear Backup' }}
        </el-button>

        <el-button
          type="primary"
          icon="el-icon-refresh"
          @click="loadBackups"
          :loading="loading"
        >
          Actualizar Lista
        </el-button>

        <el-button
          type="warning"
          icon="el-icon-upload2"
          @click="showRestoreDialog = true"
        >
          Restaurar Backup
        </el-button>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              <i class="fa fa-database mr-2"></i>
              Lista de Copias de Seguridad
            </h3>
          </div>

          <div class="card-body">
            <div v-if="loading" class="text-center py-4">
              <i class="fa fa-spinner fa-spin fa-2x"></i>
              <p>Cargando backups...</p>
            </div>

            <div v-else-if="backups.length === 0" class="text-center py-5">
              <i class="fa fa-database fa-4x text-muted mb-3"></i>
              <h4>No hay copias de seguridad</h4>
              <p class="text-muted">Haz clic en "Crear Backup" para crear tu primera copia de seguridad</p>
            </div>

            <div v-else>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th><i class="fa fa-file-o mr-2"></i>Archivo</th>
                      <th class="text-center"><i class="fa fa-hdd-o mr-2"></i>Tamaño</th>
                      <th class="text-center"><i class="fa fa-calendar mr-2"></i>Fecha</th>
                      <th class="text-center"><i class="fa fa-cogs mr-2"></i>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="backup in backups" :key="backup.filename">
                      <td>
                        <strong>{{ backup.filename }}</strong>
                      </td>
                      <td class="text-center">
                        <span class="badge badge-info">{{ backup.size }}</span>
                      </td>
                      <td class="text-center">
                        {{ formatDate(backup.date) }}
                      </td>
                      <td class="text-center">
                        <div class="btn-group">
                          <button
                            @click="downloadBackup(backup.filename)"
                            class="btn btn-success btn-sm"
                            title="Descargar"
                          >
                            <i class="fa fa-download"></i>
                          </button>
                          <button
                            @click="confirmDelete(backup.filename)"
                            class="btn btn-danger btn-sm"
                            title="Eliminar"
                          >
                            <i class="fa fa-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dialog de Confirmacion de Eliminacion -->
    <el-dialog
      title="Confirmar Eliminacion"
      :visible.sync="showDeleteDialog"
      width="400px"
    >
      <div class="text-center">
        <i class="fa fa-exclamation-triangle fa-3x text-warning mb-3"></i>
        <h4>Estas seguro?</h4>
        <p>Esta accion eliminara permanentemente la copia de seguridad:</p>
        <strong>{{ selectedBackup && selectedBackup.filename }}</strong>
      </div>

      <span slot="footer" class="dialog-footer">
        <el-button @click="showDeleteDialog = false">Cancelar</el-button>
        <el-button
          type="danger"
          @click="deleteBackup"
          :loading="deleting"
        >
          {{ deleting ? 'Eliminando...' : 'Eliminar' }}
        </el-button>
      </span>
    </el-dialog>

    <!-- Dialog de Restauracion -->
    <el-dialog
      title="Restaurar Copia de Seguridad"
      :visible.sync="showRestoreDialog"
      width="500px"
    >
      <div>
        <div class="alert alert-warning">
          <i class="fa fa-exclamation-triangle mr-2"></i>
          <strong>Atencion!</strong> Esta accion reemplazara completamente todos los datos actuales de la base de datos.
        </div>

        <div class="form-group">
          <label>Seleccionar archivo de backup (.sql):</label>
          <input
            type="file"
            ref="fileInput"
            accept=".sql"
            @change="handleFileSelect"
            class="form-control-file mt-2"
          />
        </div>

        <div v-if="selectedFile" class="alert alert-info">
          <strong>Archivo seleccionado:</strong> {{ selectedFile.name }}
          <br>
          <strong>Tamano:</strong> {{ formatFileSize(selectedFile.size) }}
        </div>
      </div>

      <span slot="footer" class="dialog-footer">
        <el-button @click="cancelRestore">Cancelar</el-button>
        <el-button
          type="warning"
          @click="restoreBackup"
          :loading="restoring"
          :disabled="!selectedFile"
        >
          {{ restoring ? 'Restaurando...' : 'Restaurar' }}
        </el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      backups: [],
      loading: false,
      creatingBackup: false,
      deleting: false,
      restoring: false,
      showDeleteDialog: false,
      showRestoreDialog: false,
      selectedBackup: null,
      selectedFile: null
    };
  },

  mounted() {
    this.loadBackups();
  },

  methods: {
    async loadBackups() {
      this.loading = true;
      try {
        const response = await axios.get('/backup/list');
        this.backups = response.data.backups || [];
      } catch (error) {
        console.error('Error loading backups:', error);
        this.$message.error('Error al cargar la lista de backups');
      } finally {
        this.loading = false;
      }
    },

    async createBackup() {
      this.creatingBackup = true;
      try {
        const response = await axios.post('/backup/create');
        if (response.data.success) {
          this.$message.success('Backup creado exitosamente');
          await this.loadBackups();
        } else {
          this.$message.error(response.data.message || 'Error al crear el backup');
        }
      } catch (error) {
        console.error('Error creating backup:', error);
        this.$message.error('Error al crear el backup');
      } finally {
        this.creatingBackup = false;
      }
    },

    downloadBackup(filename) {
      const url = `/backup/download/${filename}`;
      window.open(url, '_blank');
    },

    confirmDelete(filename) {
      this.selectedBackup = this.backups.find(b => b.filename === filename);
      this.showDeleteDialog = true;
    },

    async deleteBackup() {
      if (!this.selectedBackup) return;

      this.deleting = true;
      try {
        const response = await axios.delete(`/backup/delete/${this.selectedBackup.filename}`);
        if (response.data.success) {
          this.$message.success('Backup eliminado exitosamente');
          await this.loadBackups();
        } else {
          this.$message.error(response.data.message || 'Error al eliminar el backup');
        }
      } catch (error) {
        console.error('Error deleting backup:', error);
        this.$message.error('Error al eliminar el backup');
      } finally {
        this.deleting = false;
        this.showDeleteDialog = false;
        this.selectedBackup = null;
      }
    },

    handleFileSelect(event) {
      const file = event.target.files[0];
      if (file) {
        if (file.name.endsWith('.sql')) {
          this.selectedFile = file;
        } else {
          this.$message.error('Solo se permiten archivos .sql');
          event.target.value = '';
        }
      }
    },

    async restoreBackup() {
      if (!this.selectedFile) return;

      this.restoring = true;
      try {
        const formData = new FormData();
        formData.append('backup_file', this.selectedFile);

        const response = await axios.post('/backup/restore', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });

        if (response.data.success) {
          this.$message.success('Base de datos restaurada exitosamente');
          this.cancelRestore();
        } else {
          this.$message.error(response.data.message || 'Error al restaurar la base de datos');
        }
      } catch (error) {
        console.error('Error restoring backup:', error);
        this.$message.error('Error al restaurar la base de datos');
      } finally {
        this.restoring = false;
      }
    },

    cancelRestore() {
      this.showRestoreDialog = false;
      this.selectedFile = null;
      if (this.$refs.fileInput) {
        this.$refs.fileInput.value = '';
      }
    },

    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
      });
    },

    formatFileSize(bytes) {
      if (bytes === 0) return '0 B';
      const k = 1024;
      const sizes = ['B', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
  }
};
</script>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 1px solid #eee;
}

.page-title {
  margin: 0;
  color: #333;
}

.page-options .el-button {
  margin-left: 10px;
}

.card-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}

.btn-group {
  display: flex;
  gap: 5px;
}

.alert {
  padding: 10px 15px;
  border: 1px solid;
  border-radius: 4px;
}

.alert-warning {
  color: #856404;
  background-color: #fff3cd;
  border-color: #ffeaa7;
}

.alert-info {
  color: #0c5460;
  background-color: #d1ecf1;
  border-color: #bee5eb;
}
</style>
