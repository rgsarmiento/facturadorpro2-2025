<template>
  <div class="invoice-form">
    <div class="card mb-0 pt-2 pt-md-0">
      <div class="card-header bg-info">
        <h3 class="my-0">Nuevo Traslado</h3>
      </div>
      <div class="card-body">
        <form autocomplete="off" @submit.prevent="submit">
          <div class="form-body">
            <!-- Sección 1: Almacenes -->
            <div class="form-section">
              <div class="section-header">
                <i class="fas fa-warehouse"></i>
                <span>Almacenes de Origen y Destino</span>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="control-label">Almacén Inicial</label>
                    <el-select v-model="form.warehouse_id" @change="changeWarehouseInit">
                      <el-option
                        v-for="option in warehouses"
                        :key="option.id"
                        :value="option.id"
                        :label="option.description"
                      ></el-option>
                    </el-select>
                    <small
                      class="form-control-feedback"
                      v-if="errors.warehouse_id"
                      v-text="errors.warehouse_id[0]"
                    ></small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group" :class="{'has-danger': errors.warehouse_destination_id}">
                    <label class="control-label">Almacén Final</label>
                    <el-select v-model="form.warehouse_destination_id">
                      <el-option
                        :disabled="option.id == form.warehouse_id"
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

            <!-- Sección 2: Motivo -->
            <div class="form-section">
              <div class="section-header">
                <i class="fas fa-comment-alt"></i>
                <span>Motivo del Traslado</span>
              </div>
              <div class="row">
                <div class="col-md-12">
                  <div class="form-group" :class="{'has-danger': errors.description}">
                    <label class="control-label">Descripción del Motivo</label>
                    <el-input type="textarea" :rows="3" v-model="form.description"></el-input>
                    <small
                      class="form-control-feedback"
                      v-if="errors.description"
                      v-text="errors.description[0]"
                    ></small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sección 3: Agregar Productos -->
            <div class="form-section">
              <div class="section-header">
                <i class="fas fa-boxes"></i>
                <span>Agregar Productos al Traslado</span>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="control-label">Producto</label>
                    <el-select
                      @change="changeItem"
                      v-model="form_add.item_id"
                      filterable
                      popper-class="el-select-document_type"
                      class="border-left rounded-left border-info"
                    >
                      <el-option
                        v-for="option in items"
                        :key="option.id"
                        :value="option.id"
                        :label="option.description"
                      ></el-option>
                    </el-select>

                    <a
                      v-if="form_add.item_id  && form_add.lots_enabled"
                      href="#"
                      class="text-center font-weight-bold text-info"
                      @click.prevent="clickLotcodeOutput"
                    >[&#10004; Seleccionar series]</a>
                  </div>
                </div>
                <div class="col-md-2">
                  <div class="form-group">
                    <label class="control-label">Cantidad Actual</label>
                    <el-input v-model="form_add.stock" :readonly="true"></el-input>
                  </div>
                </div>
                <div class="col-md-2">
                  <div class="form-group">
                    <label class="control-label">Cantidad a Trasladar</label>
                    <el-input type="number" v-model="form_add.quantity"></el-input>
                  </div>
                </div>
                <div class="col-md-2">
                  <div class="form-group">
                    <el-button
                      :disabled="form_add.item_id == null"
                      style="margin-top:10%;"
                      @click.prevent="clickAddItem"
                      type="primary"
                      :loading="loading_item"
                    >Agregar Producto</el-button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sección 4: Lista de Productos -->
            <div class="form-section">
              <div class="section-header">
                <i class="fas fa-list-ul"></i>
                <span>Lista de Productos a Trasladar</span>
              </div>
              <div class="row">
                <div class="col-lg-12">
                  <table class="table" width="100%">
                    <thead>
                      <tr width="100%">
                        <th>#</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th class="text-center">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(row, index) in form.items" :key="index" width="100%">
                        <td>{{index + 1}}</td>
                        <td>{{row.description}}</td>
                        <td>{{row.quantity}}</td>
                        <td class="series-table-actions text-center">
                          <button
                            type="button"
                            class="btn waves-effect waves-light btn-xs btn-danger"
                            @click.prevent="clickCancel(index)"
                          >
                            <i class="fa fa-trash"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <div class="form-actions text-right mt-4">
            <el-button @click.prevent="close()">Cancelar</el-button>
            <el-button type="primary" native-type="submit" :loading="loading_submit">Guardar</el-button>
          </div>
        </form>
      </div>
    </div>

    <output-lots-form
      :showDialog.sync="showDialogLotsOutput"
      :lots="form_add.lots"
      @addRowOutputLot="addRowOutputLot"
    ></output-lots-form>
  </div>
</template>

<script>
import OutputLotsForm from "./partials/lots.vue";
import queryString from 'query-string';

export default {
  props: [],
  components: { OutputLotsForm },
  data() {
    return {
      loading_item: false,
      loading_submit: false,
      titleDialog: null,
      showDialogLotsOutput: false,
      resource: "transfers",
      errors: {},
      form: {},
      warehouses: [],
      items: [],
      form_add: {}
    };
  },
  async created() {
    await this.$http.get(`/${this.resource}/tables`).then(response => {
      this.warehouses = response.data.warehouses;
      this.items = response.data.items;
    });

    await this.initForm();
    this.initFormAdd();
  },
  methods: {
    changeWarehouseInit() {
      this.form.warehouse_destination_id = null;
      this.form.items = [];

      this.$http
        .get(`/${this.resource}/items/${this.form.warehouse_id}`)
        .then(response => {
          this.items = response.data.items;
        });
    },
    addRowOutputLot(lots) {
      let row = this.items.find(x => x.id == this.form_add.item_id);
      row.lots = lots;
    },
    clickCancel(index) {
      this.form.items.splice(index, 1);
    },
    async changeItem() {
      this.loading_item = true;
      await this.$http
        .get(
          `/${this.resource}/stock/${this.form_add.item_id}/${this.form.warehouse_id}`
        )
        .then(response => {
          this.form_add.stock = response.data.stock;
          this.loading_item = false;
        });

      let row = this.items.find(x => x.id == this.form_add.item_id);
      this.form_add.lots = row.lots;
      this.form_add.lots_enabled = row.lots_enabled;
    },
    initFormAdd() {
      this.form_add = {
        item_id: null,
        stock: 0,
        quantity: 0,
        lots: [],
        lots_enabled: false
      };
    },
    clickAddItem() {
      /* if (!this.form_add.item_id) {
        return;
      }*/

      if (parseFloat(this.form_add.stock) < 1) {
        return;
      }

      if (this.form_add.quantity < 1) {
        return;
      }

      if (parseFloat(this.form_add.stock) < this.form_add.quantity) {
        return;
      }

      if (this.form_add.lots.length > 0) {
        let selected_lots = this.form_add.lots.filter(x => x.has_sale == true)
          .length;

        if (this.form_add.quantity != selected_lots) {
          return;
        }
      }

      let dup = this.form.items.find(x => x.id == this.form_add.item_id);
      if (dup) {
        return;
      }

      let row = this.items.find(x => x.id == this.form_add.item_id);
      this.form.items.push({
        id: row.id,
        description: row.description,
        quantity: this.form_add.quantity,
        lots: this.form_add.lots
      });

      this.initFormAdd();
    },

    clickLotcodeOutput() {
      this.showDialogLotsOutput = true;
    },
    initForm() {
      this.errors = {};
      this.form = {
        warehouse_id: null,
        warehouse_destination_id: null,
        description: null,
        items: []
      };
    },
    async submit() {
      if (this.form.items.length == 0) {
        return this.$message.error("Debe agregar productos.");
      }

      this.loading_submit = true;
      await this.$http
        .post(`/${this.resource}`, this.form)
        .then(response => {
          if (response.data.success) {
            this.$message.success(response.data.message);
            this.downloadConstancy(response.data.data)
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
        location.href = '/transfers'
    },
    downloadConstancy(id)
    {

        const warehouse_init = this.warehouses.find(x=>x.id == this.form.warehouse_id)
        const warehouse_end = this.warehouses.find(x=>x.id == this.form.warehouse_destination_id)

        let query = queryString.stringify({
                        reason: this.form.description,
                        warehouse_init: warehouse_init.description,
                        warehouse_end: warehouse_end.description,
                        id:id
        })

        window.open(`/${this.resource}/download?${query}`, '_blank');
    },
  }
};
</script>

<style scoped>
/* Contenedor principal */
.invoice-form {
    background: #f8f9fa;
}

/* Secciones del formulario */
.form-section {
    background: white;
    padding: 25px;
    margin-bottom: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-left: 4px solid #409EFF;
}

/* Encabezados de sección */
.section-header {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0 0 20px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid #e9ecef;
    display: flex;
    align-items: center;
}

.section-header i {
    margin-right: 10px;
    font-size: 20px;
}

/* Colores por sección */
.form-section:nth-child(1) {
    border-left-color: #67C23A; /* Verde - Almacenes */
}

.form-section:nth-child(1) .section-header i {
    color: #67C23A;
}

.form-section:nth-child(2) {
    border-left-color: #E6A23C; /* Naranja - Motivo */
}

.form-section:nth-child(2) .section-header i {
    color: #E6A23C;
}

.form-section:nth-child(3) {
    border-left-color: #409EFF; /* Azul - Agregar Productos */
}

.form-section:nth-child(3) .section-header i {
    color: #409EFF;
}

.form-section:nth-child(4) {
    border-left-color: #F56C6C; /* Rojo - Lista de Productos */
}

.form-section:nth-child(4) .section-header i {
    color: #F56C6C;
}

/* Mejorar apariencia de la tabla */
.table {
    border-collapse: separate;
    border-spacing: 0;
}

.table thead th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #495057;
    padding: 12px;
}

.table tbody td {
    padding: 12px;
    vertical-align: middle;
}

/* Botones de acción */
.form-actions {
    padding-top: 20px;
    border-top: 2px solid #e9ecef;
}
</style>
