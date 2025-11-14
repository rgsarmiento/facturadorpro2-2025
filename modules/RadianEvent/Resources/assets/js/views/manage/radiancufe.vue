<template>
    <div>
        <div class="page-header pr-0 d-flex align-items-center">
            <h2 class="mr-auto"><a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a></h2>
            <ol class="breadcrumbs mr-auto">
                <li class="active"><span>Documentos Procesados</span></li>
            </ol>
            <div class="right-wrapper">
                <button class="btn btn-primary mr-5" @click="showNewEventModal = true">Registrar nuevo evento</button>
            </div>
        </div>
      <div class="card mb-0">
        <div class="card-header bg-info">
          <h3 class="my-0">Eventos RADIAN - Documentos</h3>
        </div>
        <div class="card-body">
                <!-- Overlay de carga global -->
            <div v-if="isGlobalLoading" class="global-loading-overlay">
            <i class="fas fa-spinner fa-spin"></i> Enviando Evento...
            </div>
          <!-- Tabla de documentos -->
          <data-table ref="dataTable" :resource="resource">
            <template v-slot:heading="{ sortBy, getSortIcon, getSortClass }">
              <th>#</th>
              <th class="sorting" :class="getSortClass('aceptacion')" @click="sortBy('aceptacion')">Estado Actual <i :class="getSortIcon('aceptacion')"></i></th>
              <th class="sorting" :class="getSortClass('type_document_name')" @click="sortBy('type_document_name')">Tipo Documento <i :class="getSortIcon('type_document_name')"></i></th>
              <th class="sorting" :class="getSortClass('date_issue')" @click="sortBy('date_issue')">Fecha <i :class="getSortIcon('date_issue')"></i></th>
              <th class="sorting" :class="getSortClass('identification_number')" @click="sortBy('identification_number')">Nit Empresa <i :class="getSortIcon('identification_number')"></i></th>
              <th class="sorting" :class="getSortClass('name_seller')" @click="sortBy('name_seller')">Nombre <i :class="getSortIcon('name_seller')"></i></th>
              <th class="sorting" :class="getSortClass('prefix')" @click="sortBy('prefix')">Prefijo <i :class="getSortIcon('prefix')"></i></th>
              <th class="sorting" :class="getSortClass('number')" @click="sortBy('number')">Numero <i :class="getSortIcon('number')"></i></th>
              <th class="sorting" :class="getSortClass('total_tax')" @click="sortBy('total_tax')">Impuestos <i :class="getSortIcon('total_tax')"></i></th>
              <th class="sorting" :class="getSortClass('total')" @click="sortBy('total')">Vr. Documento <i :class="getSortIcon('total')"></i></th>
              <th>Attached Document</th>
              <th>Acuse Recibo</th>
              <th>Recepcion Bienes</th>
              <th>Aceptacion Expresa</th>
              <th>Rechazo</th>
            </template>
            <template v-slot:default="{ index, row }">
                <!-- Filtro para cargar facturas solo de eventos cufe se utiliza la columna sale-->
              <tr v-if="row.sale === '88888888.00'">
                <td>{{index}}</td>
                <td>
                            <template v-if="row.aceptacion == 1">
                                <i class="fa fa-circle" style="color: green"></i>
                            </template>
                            <template v-else>
                                <template v-if="row.rechazo == 1">
                                    <i class="fa fa-circle" style="color: red"></i>
                                </template>
                                <template v-else>

                                    <template v-if="row.rec_bienes == 1">
                                        <i class="fa fa-circle" style="color: yellow"></i>
                                    </template>
                                    <template v-else>
                                        <template v-if="row.acu_recibo == 1">
                                            <i class="fa fa-circle" style="color: blue"></i>
                                        </template>
                                        <template v-else>
                                            <i class="fa fa-circle" style="color: black"></i>
                                        </template>
                                    </template>
                                </template>
                            </template>
                        </td>
                <td>{{ row.type_document_name }}</td>
                <td>{{ row.date_issue }}</td>
                <td>{{ row.identification_number }}</td>
                <td>{{ row.name_seller }}</td>
                <td>{{ row.prefix }}</td>
                <td>{{ row.number }}</td>
                <td>{{ row.total_tax }}</td>
                <td>{{ row.total }}</td>
                <td>
                  <button type="button" class="btn btn-success btn-xs" @click="download(row.xml)">
                    <i class="fas fa-download"></i>
                  </button>
                </td>
                <td>
                  <button v-if="row.acu_recibo" type="button" class="btn btn-success btn-xs"> <i class="fas fa-check-circle"></i>  Validado</button>
                  <button v-else type="button" class="btn btn-primary btn-xs"
                    @click="sendEvent(row.id, 1, row.cufe)">
                    Enviar
                  </button>
                </td>
                <td>
                  <button v-if="row.rec_bienes" type="button" class="btn btn-success btn-xs"> <i class="fas fa-check-circle"></i>  Validado</button>
                  <button v-else type="button" class="btn btn-primary btn-xs"
                    @click="sendEvent(row.id, 3, row.cufe)">
                    Enviar
                  </button>
                </td>
                <td>
                <!-- Botón de Aceptación Expresa -->
                <button v-if="row.aceptacion" type="button" class="btn btn-success btn-xs">
                    <i class="fas fa-check-circle"></i> Validado
                </button>
                <button v-else-if="!row.rechazo" type="button" class="btn btn-primary btn-xs" @click="sendEvent(row.id, 4, row.cufe)">
                    Enviar
                </button>
                <button v-else type="button" class="btn btn-secondary btn-xs" disabled>
                    <i class="fas fa-ban"></i> Rechazado
                </button>
                </td>
                <td>
                <button v-if="row.rechazo" type="button" class="btn btn-danger btn-xs">
                    <i class="fas fa-times-circle"></i> Rechazado
                </button>
                <button v-else-if="!row.aceptacion && row.acu_recibo" type="button" class="btn btn-primary btn-xs" @click="sendEvent(row.id, 2, row.cufe)">
                    Enviar
                </button>
                <button v-else-if="row.aceptacion" type="button" class="btn btn-success btn-xs" disabled>
                    <i class="fas fa-check-circle"></i> Aceptación Expresa
                </button>
                </td>
              </tr>
            </template>
          </data-table>


          <el-dialog title="Rechazo de Factura" :visible.sync="showRejectionModal">
            <form class="form-horizontal" style="text-align: center;">
                <div class="form-group">
                    <div class="col-md-12">
                        <p>
                            Documento electrónico mediante el cual el Adquiriente manifiesta que no acepta el documento de conformidad con el artículo 773 del Código de Comercio y en concordancia con el artículo 2.2.2.53.4. del Decreto 1074 de 2015, Único Reglamentario del Sector Comercio, Industria y Turismo. Este documento es para desaveniencias de tipo comercial, dado que el documento sobre el cual manifiesta el desacuerdo fue efectivamente Validado por la DIAN, en el sistema de Validación Previa.
                        </p>
                    </div>
                    <br>
                    <p><b>Motivo de Rechazo</b></p>
                <div class="col-md-6">
                    <select id="type_rejection_id" v-model="selectedRejectionType" class="form-control" required>
                    <option value="" disabled selected>Selecciona un tipo de rechazo</option>
                    <option value="1">Documento con inconsistencias</option>
                    <option value="2">Mercancía no entregada totalmente</option>
                    <option value="3">Mercancía no entregada parcialmente</option>
                    <option value="4">Servicio no prestado</option>
                    </select>
                </div>
                </div>
                <div class="form-group">
                <div class="text-right">
                    <el-button @click="showRejectionModal = false">Cancelar</el-button>
                    <el-button type="primary" @click="submitRejection">Enviar</el-button>
                </div>
                </div>
            </form>
          </el-dialog>
          <template>
            <el-dialog title="Registrar nuevo evento" :visible.sync="showNewEventModal" class="modal-form">
                <form @submit.prevent="sendRadianEvent">
                <div class="form-group">
                    <label for="cufe">CUFE:</label>
                    <input type="text" id="cufe" v-model="cufe" class="form-control" required />
                </div>
                <div class="form-group">
                    <label for="event_id">Tipo de Evento:</label>
                    <select id="event_id" v-model="event_id" class="form-control" required disabled>
                    <option value="1">Acuse de recibo</option>
                    </select>
                </div>
                <div class="form-group text-right">
                    <button type="submit" class="btn btn-primary">Enviar Evento</button>
                </div>
                </form>
            </el-dialog>
            </template>
        </div>
      </div>
      <rejected-form :show-dialog.sync="showDialogRejected" :record="record"></rejected-form>
    </div>
  </template>

<style>
.global-loading-overlay {
  font-size: 25px;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(255, 255, 255, 0.7);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000; /* Un z-index alto para asegurar que se muestre sobre otros elementos */
  color: #000;
}
</style>



  <script>
  import DataTable from "@components/DataTable.vue";
  import RejectedForm from "./partials/rejected.vue";

  export default {
    components: { DataTable, RejectedForm },
    data() {
      return {
        resource: "co-radian-events",
        loading: false,
        showDialogRejected: false,
        record: null,
        cufe: "",
        event_id: "1",
        showRejectionModal: false,
        showNewEventModal: false,
        isGlobalLoading: false, // Estado de carga global
        selectedRejectionType: ''
      };
    },
    methods: {
    sendRadianEvent() {
      this.isGlobalLoading = true; // Comienza la carga global
      this.showNewEventModal = false; // Cierra el modal después de enviar el evento
      const params = {
        cufe: this.cufe,
        event_id: this.event_id,
      };
      this.$http.post(`/${this.resource}/send-radian-event`, params).then((response) => {
        this.isGlobalLoading = false; // Aquí faltaba detener la animación de carga
        if (response.data.success) {
          this.$message.success(response.data.message);
          this.$eventHub.$emit('reloadData')
        } else {
          this.$message.error(response.data.message);
        }
      });
    },
    sendEvent(documentId, eventId, cufe) {
    if (eventId === 2) {
        this.prepareRejection(documentId, cufe);
    } else {
        this.sendStandardEvent(documentId, eventId, cufe);
    }
    },

    prepareRejection(documentId, cufe) {
    // Guardar los datos necesarios y mostrar el modal
    this.selectedDocumentId = documentId;
    this.selectedCufe = cufe;
    this.showRejectionModal = true;
    },

    sendStandardEvent(documentId, eventId, cufe) {
    this.isGlobalLoading = true;
    const params = { cufe, event_id: eventId };
    this.$http.post(`/${this.resource}/send-radian-event`, params)
        .then(response => {
        this.handleResponse(response, documentId);
        this.$eventHub.$emit('reloadData')
        })
        .catch(error => {
        this.handleError(error);
        });
    },

    handleResponse(response, documentId) {
    this.isGlobalLoading = false;
    if (response.data.success) {
        this.$message.success(response.data.message);
    } else {
        this.$message.error(response.data.message);
    }
    },

    handleError(error) {
    this.isGlobalLoading = false;
    this.$message.error('Error en la petición: ' + error.message);
    },

    submitRejection() {
        this.isGlobalLoading = true; // Comienza la carga global
        this.showRejectionModal = false; // Cierra el modal de rechazo inmediatamente después de enviar
        const params = {
            cufe: this.selectedCufe,
            event_id: 2,
            type_rejection_id: this.selectedRejectionType
        };
        this.$http.post(`/${this.resource}/send-radian-event`, params).then((response) => {
            if (response.data.success) {
                this.$message.success(response.data.message);
                this.$eventHub.$emit('reloadData'); // Recarga los datos de la tabla
            } else {
                this.$message.error(response.data.message);
            }
            this.selectedRejectionType = ''; // Limpia el tipo de rechazo seleccionado después de recibir la respuesta
        }).catch(error => {
            this.$message.error('Error en la petición: ' + error.message);
        }).finally(() => {
            this.isGlobalLoading = false; // Detén la animación de carga global aquí
        });
    },

      download(filename) {
        window.open(`/${this.resource}/download/${filename}`, "_blank");
      },
    },
  };
  </script>
