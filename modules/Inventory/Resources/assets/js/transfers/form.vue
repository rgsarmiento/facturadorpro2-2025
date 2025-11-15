<template>
  <el-dialog :title="titleDialog" :visible="showDialog" @close="close" @open="create" width="70%" class="transfer-dialog">
    <form autocomplete="off" @submit.prevent="submit">
      <div class="form-body invoice-form">

        <!-- Información del Producto -->
        <div class="form-section">
          <h5 class="section-header"><i class="fas fa-box"></i> Información del Producto</h5>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Producto</label>
                <el-input v-model="form.item_description" :readonly="true"></el-input>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="control-label">Cantidad Actual</label>
                <el-input v-model="form.stock" :readonly="true"></el-input>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label class="control-label">Cant. Trasladada</label>
                <el-input v-model="form.quantity"></el-input>
              </div>
            </div>
          </div>
        </div>

        <!-- Almacenes -->
        <div class="form-section">
          <h5 class="section-header"><i class="fas fa-warehouse"></i> Almacenes</h5>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="control-label">Almacén Inicial</label>
                <el-input v-model="form.warehouse_description" :readonly="true"></el-input>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group" :class="{'has-danger': errors.warehouse_destination_id}">
                <label class="control-label">Almacén Final</label>
                <el-select v-model="form.warehouse_destination_id">
                  <el-option
                    v-for="option in warehouses"
                    :key="option.id"
                    :value="option.id"
                    :label="option.description"
                  ></el-option>
                </el-select>
                <small
                  class="form-control-feedback"
                  v-if="errors.warehouse_destination_id"
                  v-text="errors.warehouse_destination_id[0]"
                ></small>
              </div>
            </div>
          </div>
        </div>

        <!-- Motivo de Traslado -->
        <div class="form-section">
          <h5 class="section-header"><i class="fas fa-comment"></i> Motivo de Traslado</h5>
          <div class="row">
            <div class="col-md-12">
              <div class="form-group" :class="{'has-danger': errors.detail}">
                <label class="control-label">Motivo de Traslado</label>
                <el-input v-model="form.detail" type="textarea" :rows="2"></el-input>
                <small class="form-control-feedback" v-if="errors.detail" v-text="errors.detail[0]"></small>
              </div>
            </div>
          </div>
        </div>

      </div>
      <div class="form-actions text-right mt-4">
        <el-button @click.prevent="close()">Cancelar</el-button>
        <el-button type="primary" native-type="submit" :loading="loading_submit">
          <i class="fas fa-save"></i> Guardar
        </el-button>
      </div>
    </form>
  </el-dialog>
</template>

<script>
export default {
  props: ["showDialog", "recordId"],
  data() {
    return {
      loading_submit: false,
      titleDialog: null,
      showDialogLotsOutput: false,
      resource: "transfers",
      errors: {},
      form: {},
      warehouses: []
    };
  },
  created() {
    this.initForm();
    this.$http.get(`/${this.resource}/tables`).then(response => {
      this.warehouses = response.data.warehouses;
    });
  },
  methods: {
    addRowOutputLot(lots) {
      this.form.lots = lots;
    },
    clickLotcodeOutput() {
      this.showDialogLotsOutput = true;
    },
    initForm() {
      this.errors = {};
      this.form = {
        id: null,
        item_id: null,
        item_description: null,
        warehouse_id: null,
        warehouse_destination_id: null,
        warehouse_description: null,
        stock: 0,
        quantity: 0,
        // lots:[],
        detail: null
      };
    },
    create() {
      this.titleDialog = "Editar traslado";
      this.$http
        .get(`/${this.resource}/record/${this.recordId}`)
        .then(response => {
          this.form = response.data.data;
          // this.form.lots = Object.values(response.data.data.lots)
        });
    },
    async submit() {
      // if(this.form.lots_enabled){
      //     let select_lots = await _.filter(this.form.lots, {'has_sale':true})
      //     if(select_lots.length != this.form.quantity_move){
      //         return this.$message.error('La cantidad ingresada es diferente a las series seleccionadas');
      //     }
      // }

      this.loading_submit = true;
      await this.$http
        .post(`/${this.resource}`, this.form)
        .then(response => {
          if (response.data.success) {
            this.$message.success(response.data.message);
            this.$eventHub.$emit("reloadData");
            this.close();
          } else {
            this.$message.error(response.data.message);
          }
        })
        .catch(error => {
          if (error.response.status === 422) {
            this.errors = error.response.data.errors;
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
    }
  }
};
</script>

<style scoped>
/* Diseño simple y limpio */
.invoice-form {
    background: #f8f9fa;
}

.form-section {
    background: white;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-left: 4px solid #409EFF;
}

.section-header {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #e9ecef;
}

.section-header i {
    color: #409EFF;
    margin-right: 10px;
}

.form-section:nth-child(2) { border-left-color: #67C23A; }
.form-section:nth-child(3) { border-left-color: #E6A23C; }

.transfer-dialog .el-dialog__body {
    padding: 20px;
}
</style>
