<template>
    <el-dialog 
        width="75%" 
        :title="titleDialog" 
        :visible="showDialog" 
        @close="close" 
        @open="create" 
        @opened="opened" 
        :close-on-click-modal="false" 
        append-to-body
        top="5vh"
        custom-class="persons-modal-modern">
      <form autocomplete="off" @submit.prevent="submit">
        <div class="form-body">
          <!-- Información de Identificación -->
          <div class="section-card">
            <h4 class="section-title"><i class="fa fa-id-card"></i> Información de Identificación</h4>
            <div class="row">
              <div class="col-md-3">
                <div class="form-group" :class="{'has-danger': errors.identity_document_type_id}">
                  <label class="control-label"><i class="fa fa-file-text text-primary"></i> Tipo de Documento</label>
                  <el-select v-model="form.identity_document_type_id" filterable placeholder="Seleccione tipo">
                    <el-option
                      v-for="option in identity_document_types"
                      :key="option.id"
                      :value="option.id"
                      :label="option.name">
                    </el-option>
                  </el-select>
                  <small class="form-control-feedback" v-if="errors.identity_document_type_id" v-text="errors.identity_document_type_id[0]"></small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group" :class="{'has-danger': errors.number}">
                  <label class="control-label"><i class="fa fa-hashtag text-info"></i> N° Identificación</label>
                  <el-input
                  v-model="form.number"
                  :maxlength="maxLength"
                  dusk="number"
                  placeholder="Ingrese número de identificación"
                  @keydown.enter.native.stop.prevent="changeNumberIdentification">
                  <el-button type="primary" slot="append" :loading="loading_search" icon="el-icon-search" @click.prevent="changeNumberIdentification">
                  </el-button>
                  </el-input>
                  <small class="form-control-feedback" v-if="errors.number" v-text="errors.number[0]"></small>
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group" :class="{'has-danger': errors.dv}">
                  <label class="control-label"><i class="fa fa-check-circle text-success"></i> DV</label>
                  <el-input v-model="form.dv" placeholder="Auto" readonly></el-input>
                  <small class="form-control-feedback" v-if="errors.dv" v-text="errors.dv[0]"></small>
                </div>
              </div>
              <div class="col-md-3"></div>
            </div>
          </div>

          <!-- Información General -->
          <div class="section-card">
            <h4 class="section-title"><i class="fa fa-user"></i> Información General</h4>
            <div class="row">
              <div class="col-md-8">
                <div class="form-group" :class="{'has-danger': errors.name}">
                  <label class="control-label"><i class="fa fa-user-circle text-success"></i> Nombre / Razón Social</label>
                  <el-input v-model="form.name" placeholder="Nombre completo o razón social"></el-input>
                  <small class="form-control-feedback" v-if="errors.name" v-text="errors.name[0]"></small>
                </div>
              </div>
              <div class="col-md-4"></div>
            </div>
          </div>

          <!-- Información de Contacto -->
          <div class="section-card">
            <h4 class="section-title"><i class="fa fa-envelope"></i> Información de Contacto</h4>
            <div class="row">
              <div class="col-md-8">
                <div class="form-group" :class="{'has-danger': errors.email}">
                  <label class="control-label"><i class="fa fa-at text-primary"></i> Correo Electrónico</label>
                  <el-input v-model="form.email" dusk="email" type="email" placeholder="correo@ejemplo.com">
                    <template slot="prepend">@</template>
                  </el-input>
                  <small class="form-control-feedback" v-if="errors.email" v-text="errors.email[0]"></small>
                </div>
              </div>
              <div class="col-md-4 toggle-wrapper">
                <div class="form-group">
                  <div class="toggle-section">
                    <el-checkbox v-model="showAdditionalFields" size="large">
                      <span class="toggle-label">
                        <i :class="showAdditionalFields ? 'fa fa-chevron-down' : 'fa fa-chevron-right'"></i>
                        Información Adicional
                      </span>
                    </el-checkbox>
                    <small class="toggle-hint">Click para {{ showAdditionalFields ? 'ocultar' : 'mostrar' }} más campos</small>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Campos adicionales -->
          <div v-if="showAdditionalFields">
            <!-- Información Tributaria -->
            <div class="section-card additional-section">
              <h4 class="section-title"><i class="fa fa-file-text-o"></i> Información Tributaria</h4>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group" :class="{'has-danger': errors.type_person_id}">
                    <label class="control-label"><i class="fa fa-user-o"></i> Tipo de Persona</label>
                    <el-select v-model="form.type_person_id" filterable placeholder="Seleccione">
                      <el-option
                        v-for="option in type_persons"
                        :key="option.id"
                        :value="option.id"
                        :label="option.name">
                      </el-option>
                    </el-select>
                    <small class="form-control-feedback" v-if="errors.type_person_id" v-text="errors.type_person_id[0]"></small>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group" :class="{'has-danger': errors.type_regime_id}">
                    <label class="control-label"><i class="fa fa-book"></i> Tipo de Régimen</label>
                    <el-select v-model="form.type_regime_id" filterable placeholder="Seleccione">
                      <el-option
                        v-for="option in type_regimes"
                        :key="option.id"
                        :value="option.id"
                        :label="option.name">
                      </el-option>
                    </el-select>
                    <small class="form-control-feedback" v-if="errors.type_regime_id" v-text="errors.type_regime_id[0]"></small>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group" :class="{'has-danger': errors.type_obligation_id}">
                    <label class="control-label"><i class="fa fa-balance-scale"></i> Tipo de Obligación</label>
                    <el-select v-model="form.type_obligation_id" filterable placeholder="Seleccione">
                      <el-option
                        v-for="option in type_obligations"
                        :key="option.id"
                        :value="option.id"
                        :label="option.name">
                      </el-option>
                    </el-select>
                    <small class="form-control-feedback" v-if="errors.type_obligation_id" v-text="errors.type_obligation_id[0]"></small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Ubicación -->
            <div class="section-card additional-section">
              <h4 class="section-title"><i class="fa fa-map-marker"></i> Ubicación</h4>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group" :class="{'has-danger': errors.country_id}">
                    <label class="control-label"><i class="fa fa-globe text-primary"></i> País</label>
                    <el-select v-model="form.country_id" filterable @change="departmentss()" placeholder="Seleccione">
                      <el-option
                        v-for="option in countries"
                        :key="option.id"
                        :value="option.id"
                        :label="option.name">
                      </el-option>
                    </el-select>
                    <small class="form-control-feedback" v-if="errors.country_id" v-text="errors.country_id[0]"></small>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group" :class="{'has-danger': errors.department_id}">
                    <label class="control-label"><i class="fa fa-map text-info"></i> Departamento</label>
                    <el-select v-model="form.department_id" filterable @change="citiess()" placeholder="Seleccione">
                      <el-option
                        v-for="option in departments"
                        :key="option.id"
                        :value="option.id"
                        :label="option.name">
                      </el-option>
                    </el-select>
                    <small class="form-control-feedback" v-if="errors.department_id" v-text="errors.department_id[0]"></small>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group" :class="{'has-danger': errors.city_id}">
                    <label class="control-label"><i class="fa fa-building text-warning"></i> Ciudad</label>
                    <el-select v-model="form.city_id" filterable placeholder="Seleccione">
                      <el-option
                        v-for="option in cities"
                        :key="option.id"
                        :value="option.id"
                        :label="option.name">
                      </el-option>
                    </el-select>
                    <small class="form-control-feedback" v-if="errors.city_id" v-text="errors.city_id[0]"></small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group" :class="{'has-danger': errors.address}">
                    <label class="control-label"><i class="fa fa-home text-success"></i> Dirección</label>
                    <el-input v-model="form.address" dusk="address" placeholder="Av/Calle, número, barrio"></el-input>
                    <small class="form-control-feedback" v-if="errors.address" v-text="errors.address[0]"></small>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group" :class="{'has-danger': errors.telephone}">
                    <label class="control-label"><i class="fa fa-phone text-success"></i> Teléfono</label>
                    <el-input type="tel" maxlength="10" v-model="form.telephone" placeholder="Teléfono de contacto"
                              onkeydown="return ( event.ctrlKey || event.altKey || (47<event.keyCode && event.keyCode<58 && event.shiftKey==false) || (95<event.keyCode && event.keyCode<106) || (event.keyCode==8) || (event.keyCode==9) || (event.keyCode>34 && event.keyCode<40) || (event.keyCode==46) )">
                      <template slot="prepend"><i class="fa fa-mobile"></i></template>
                    </el-input>
                    <small class="form-control-feedback" v-if="errors.telephone" v-text="errors.telephone[0]"></small>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group" :class="{'has-danger': errors.code}">
                    <label class="control-label"><i class="fa fa-barcode text-info"></i> Código Interno</label>
                    <el-input v-model="form.code" placeholder="Código opcional"></el-input>
                    <small class="form-control-feedback" v-if="errors.code" v-text="errors.code[0]"></small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Contacto -->
            <div class="section-card additional-section">
              <h4 class="section-title"><i class="fa fa-address-book"></i> Persona de Contacto</h4>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="control-label"><i class="fa fa-user text-primary"></i> Nombre y Apellido</label>
                    <el-input v-model="form.contact_name" placeholder="Nombre del contacto"></el-input>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="control-label"><i class="fa fa-mobile text-success"></i> Teléfono</label>
                    <el-input v-model="form.contact_phone" placeholder="Teléfono del contacto">
                      <template slot="prepend"><i class="fa fa-phone"></i></template>
                    </el-input>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- Fin de campos adicionales -->
        </div>
        <div class="form-actions text-right mt-4">
          <el-button @click.prevent="close()">Cancelar</el-button>
          <el-button type="primary" native-type="submit" :loading="loading_submit">Guardar</el-button>
        </div>
      </form>
    </el-dialog>
  </template>

  <style>
  .aling-div {
    margin-top: 25px;
  }
  .custom-checkbox-label {
    font-size: 15pt; /* Ajusta el tamaño de la letra */
  }
  .el-checkbox .el-checkbox__input {
    transform: scale(1.3); /* Ajusta el tamaño del checkbox */
  }
  </style>

  <script>
  import { calcularDv } from '../../../functions/Nit';

  export default {
    props: ['showDialog', 'type', 'recordId', 'external', 'document_type_id', 'input_person'],
    data() {
      return {
        loading_submit: false,
        titleDialog: null,
        resource: 'persons',
        errors: {},
        api_service_token: false,
        form: {},
        countries: [],
        departments: [],
        cities: [],
        identity_document_types: [],
        person_types: [],
        type_regimes: [],
        type_obligations: [],
        type_persons: [],
        loading_search: false,
        showAdditionalFields: false // Estado del checkbox para mostrar campos adicionales
      }
    },

    async created() {
      await this.initForm();
      await this.$http.get(`/${this.resource}/tables`)
        .then(response => {
          this.api_service_token = response.data.api_service_token;
          this.countries = response.data.countries;
          this.identity_document_types = response.data.identity_document_types;
          this.person_types = response.data.person_types;
          this.type_persons = response.data.typePeople;
          this.type_regimes = response.data.typeRegimes;
          this.type_obligations = response.data.typeObligations;
          // Se sobrescribe identity_document_types con la información de documentos desde 'tables'
          this.identity_document_types = response.data.typeIdentityDocuments;
        });
    },

    computed: {
      maxLength() {
        // Mapeo: clave numérica => cantidad máxima de dígitos permitidos.
        const mapping = {
        1: 10,    // Registro civil
        2: 10,   // Tarjeta de identidad
        3: 10,   // Cédula de ciudadanía
        4: 10,   // Tarjeta de extranjería
        5: 10,   // Cédula de extranjería
        6: 11,   // NIT
        7: 10,   // Pasaporte
        8: 22,   // Documento de identificación extranjero
        9: 22,   // NIT de otro país
        10: 10,  // NUIP *
        };
        // Se fuerza la conversión a Number para que la llave coincida correctamente en el mapeo.
        return mapping[Number(this.form.identity_document_type_id)] || 10;
      }
    },

    watch: {
      // Al cambiar el tipo de documento, se asigna automáticamente el tipo de persona:
      // Si es NIT (id == 6) se selecciona "Persona Jurídica" (se asume id 1),
      // de lo contrario se asigna "Persona Natural" (se asume id 2).
      'form.identity_document_type_id'(newVal) {
        if(newVal == 6) {
          this.form.type_person_id = 1;  // Persona Jurídica
        } else {
          this.form.type_person_id = 2;  // Persona Natural
        }
      }
    },

    methods: {
      /**
       * Calcula el dígito verificador y consulta el API DIAN a través del backend.
       * Los datos devueltos se mapean a los campos del formulario.
       */
      async changeNumberIdentification() {
        if (this.form.number) {
          this.loading_search = true;
          // Calcula el DV
          this.form.dv = await calcularDv(this.form.number);

          try {
            // Llama al endpoint que consulta DIAN
            const response = await this.$http.post('/persons/query-dian', {
              identification_number: this.form.number,
              type_document_id: this.form.identity_document_type_id
            });
//console.log(response.data);
            if (response.data.success) {
              let data = response.data.data;
              // Mapea la respuesta DIAN al formulario
              this.form.name = data.business_name;
              this.form.dv = data.dv;
              this.form.email = data.email;
              // Se pueden mapear otros campos según la respuesta
            } else {
              this.$message.error(response.data.message || "No se encontró información en DIAN");
            }
          } catch (error) {
            console.error("Error al consultar DIAN:", error);
            this.$message.error("Error al consultar DIAN");
          } finally {
            this.loading_search = false;
          }
        }
      },

      // Método original que consultaba el nombre del cliente (si se requiere conservar)
      async searchNameClient() {
        if (this.form.number.length < 8) return;
        await this.$http.get(`/${this.resource}/searchName/${this.form.number}`)
          .then(response => {
            if (response.data.data) {
              this.form.name = response.data.data;
            }
          })
          .catch(error => {})
          .then(() => {});
      },

      getDepartment(val) {
        return axios.post(`/departments/${val}`)
          .then(response => response.data)
          .catch(error => console.log(error));
      },

      getCities(val) {
        return axios.post(`/cities/${val}`)
          .then(response => response.data)
          .catch(error => console.log(error));
      },

      initForm() {
        this.errors = {};
        this.$http.get('/companies/record').then(response => {
          this.idIdentification = response.data.data.logo;
          let address = null;
          let telephone = null;

          if (this.idIdentification === 'logo_7715537.jpg') {
            address = 'CR 14 15 70 BRR CENTRO';
            telephone = '3203468640';
          }

          if (!this.recordId) {
            this.form = {
              id: null,
              type: this.type,
              number: '',
              name: null,
              trade_name: null,
              country_id: 47,
              department_id: null,
              address: address,
              telephone: telephone,
              email: null,
              perception_agent: false,
              percentage_perception: 0,
              person_type_id: 2, // Valor por defecto: Persona Natural
              comment: null,
              type_person_id: 2,
              type_regime_id: 2,
              identity_document_type_id: 3, // Valor inicial, puede modificarse mediante el select
              type_obligation_id: 117,
              addresses: [],
              city_id: null,
              code: null,
              dv: null,
              contact_phone: null,
              contact_name: null,
              postal_code: null,
            };
          }
        }).catch(error => {
          console.error('Error al obtener los datos de la compañía:', error);
        });
        this.departmentss();
        this.citiess();
      },

      departmentss(edit = false) {
        if (!edit) {
          this.form.department_id = null;
          this.form.city_id = null;
          this.departments = [];
          this.cities = [];
        }

        this.$http.get('/companies/record').then(response => {
          this.idIdentification = response.data.data.logo;

          if (this.form.country_id != null) {
            this.getDepartment(this.form.country_id).then(departmentRows => {
              this.departments = departmentRows;

              if (!edit) {
                if (this.idIdentification === 'logo_7715537.jpg') {
                  let valorPorDefecto = 779;
                  if (this.departments.some(dept => dept.id === valorPorDefecto)) {
                    this.form.department_id = valorPorDefecto;
                  }
                } else {
                  this.form.department_id = null;
                }
              }

              this.citiess(edit);
            });
          }
        }).catch(error => {
          console.error('Error al obtener información de la compañía:', error);
        });
      },

      citiess(edit = false) {
        if (!edit) {
          this.form.city_id = null;
          this.cities = [];
        }

        if (this.form.department_id != null) {
          this.getCities(this.form.department_id).then(cityRows => {
            this.cities = cityRows;

            if (!edit) {
              if (this.idIdentification === 'logo_7715537.jpg') {
                let valorPorDefectoCiudad = 12688;
                if (this.cities.some(city => city.id === valorPorDefectoCiudad)) {
                  this.form.city_id = valorPorDefectoCiudad;
                }
              } else {
                this.form.city_id = null;
              }
            }
          }).catch(error => {
            console.error('Error al cargar ciudades:', error);
            this.cities = [];
          });
        }
      },

      async opened() {
        if (this.external && this.input_person) {
          if (this.form.number.length === 8 || this.form.number.length === 11) {
            if (this.api_service_token != false) {
              await this.$eventHub.$emit('enableClickSearch');
            } else {
              this.searchCustomer();
            }
          }
        }
      },

      create() {
        if (this.external) {
          if (this.document_type_id === '01') {
            this.form.identity_document_type_id = '6';
          }
          if (this.document_type_id === '03') {
            this.form.identity_document_type_id = '1';
          }

          if (this.input_person) {
            this.form.identity_document_type_id = (this.input_person.identity_document_type_id)
              ? this.input_person.identity_document_type_id
              : this.form.identity_document_type_id;
            this.form.number = (this.input_person.number) ? this.input_person.number : '';
          }
        }
        if (this.type === 'customers') {
          this.titleDialog = (this.recordId) ? 'Editar Cliente' : 'Nuevo Cliente';
        }
        if (this.type === 'suppliers') {
          this.titleDialog = (this.recordId) ? 'Editar Proveedor' : 'Nuevo Proveedor';
        }
        if (this.recordId) {
          this.$http.get(`/${this.resource}/record/${this.recordId}`).then(response => {
            this.form = response.data.data;
            this.departmentss(true);
            this.citiess(true);
          });
        } else {
          this.initForm();
        }
      },

      clickAddAddress() {
        this.form.addresses.push({
          'id': null,
          'country_id': 'PE',
          'location_id': [],
          'address': null,
          'email': null,
          'phone': null,
          'main': false,
        });
      },

      submit() {
            // Si no se muestran campos adicionales se asignan valores predeterminados.
            if (!this.showAdditionalFields) {
                this.form.country_id = 47;
                this.form.department_id = 779;
                this.form.city_id = 12688;
                this.form.telephone = '9999999999';
                this.form.address = 'CR 00 00 00 BRR N/A';
                this.form.code = this.form.number;
                this.form.contact_phone = null;
                this.form.contact_name = null;
            }

            // Agregamos el flag que indica que la request proviene de la factura
            this.form.from_invoice = true;

            this.loading_submit = true;
            this.$http.post(`/${this.resource}`, this.form)
            .then(response => {
                if (response.data.success) {
                    // La respuesta exitosa contendrá el ID del cliente ya existente o el creado
                    this.$message.success(response.data.message);
                    // Emitir el evento para que el componente de factura cargue el cliente
                    if (this.external) {
                        this.$eventHub.$emit('reloadDataPersons', response.data.id);
                    } else {
                        this.$eventHub.$emit('reloadData');
                    }
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

      changeIdentityDocType() {
        (this.recordId == null) ? this.setDataDefaultCustomer() : null;
      },

      setDataDefaultCustomer() {
        if (this.form.identity_document_type_id == '0') {
          this.form.number = '99999999';
          this.form.name = "Clientes - Varios";
        } else {
          this.form.number = '';
          this.form.name = null;
        }
      },

      close() {
        this.$eventHub.$emit('initInputPerson');
        this.$emit('update:showDialog', false);
      },

      searchCustomer() {
        this.searchServiceNumberByType();
      },

      searchNumber(data) {
        this.form.name = (this.form.identity_document_type_id === '1') ? data.nombre_completo : data.nombre_o_razon_social;
        this.form.trade_name = (this.form.identity_document_type_id === '6') ? data.nombre_o_razon_social : '';
        this.form.location_id = data.ubigeo;
        this.form.address = data.direccion;
        this.form.department_id = (data.ubigeo) ? data.ubigeo[0] : null;
        this.form.province_id = (data.ubigeo) ? data.ubigeo[1] : null;
        this.form.district_id = (data.ubigeo) ? data.ubigeo[2] : null;
        this.form.condition = data.condicion;
        this.form.state = data.estado;

        this.filterProvinces();
        this.filterDistricts();
      },

      clickRemoveAddress(index) {
        this.form.addresses.splice(index, 1);
      }
    }
  }
</script>

<style scoped>
/* ==============================================
   MODAL MODERNO DE PERSONAS/CLIENTES
   Diseño profesional con cards y animaciones
   ============================================== */

/* Dialog personalizado */
::v-deep .persons-modal-modern {
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

::v-deep .persons-modal-modern .el-dialog__header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 20px 30px;
  margin: 0;
  border-bottom: none;
}

::v-deep .persons-modal-modern .el-dialog__title {
  color: white;
  font-weight: 600;
  font-size: 20px;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

::v-deep .persons-modal-modern .el-dialog__close {
  color: white !important;
  font-size: 20px;
  font-weight: bold;
  transition: transform 0.3s ease;
}

::v-deep .persons-modal-modern .el-dialog__close:hover {
  transform: rotate(90deg);
}

::v-deep .persons-modal-modern .el-dialog__body {
  padding: 30px;
  background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
  max-height: 75vh;
  overflow-y: auto;
}

/* Scrollbar personalizado */
::v-deep .persons-modal-modern .el-dialog__body::-webkit-scrollbar {
  width: 8px;
}

::v-deep .persons-modal-modern .el-dialog__body::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

::v-deep .persons-modal-modern .el-dialog__body::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 10px;
}

::v-deep .persons-modal-modern .el-dialog__body::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

/* Cards de secciones */
.section-card {
  background: white;
  border-radius: 10px;
  padding: 25px;
  margin-bottom: 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e8eaf6;
  transition: all 0.3s ease;
  animation: slideIn 0.5s ease;
}

.section-card:hover {
  box-shadow: 0 5px 20px rgba(102, 126, 234, 0.15);
  transform: translateY(-2px);
  border-color: #667eea;
}

/* Secciones adicionales con color especial */
.additional-section {
  border-left: 4px solid #667eea;
  animation: slideInLeft 0.6s ease;
}

.additional-section:hover {
  border-left-color: #764ba2;
}

/* Títulos de secciones */
.section-title {
  font-size: 18px;
  font-weight: 600;
  color: #2c3e50;
  margin: 0 0 20px 0;
  padding-bottom: 12px;
  border-bottom: 2px solid transparent;
  background: linear-gradient(90deg, #667eea 0%, transparent 100%);
  background-position: 0 100%;
  background-size: 100% 2px;
  background-repeat: no-repeat;
  display: flex;
  align-items: center;
  gap: 10px;
}

.section-title i {
  font-size: 20px;
  color: #667eea;
  animation: pulse 2s ease-in-out infinite;
}

/* Grupo de formulario */
.form-group {
  margin-bottom: 20px;
}

.form-group label {
  font-weight: 500;
  color: #495057;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.form-group label i {
  font-size: 14px;
  opacity: 0.8;
}

/* Inputs y selects */
::v-deep .el-input__inner,
::v-deep .el-select {
  border-radius: 6px;
  transition: all 0.3s ease;
}

::v-deep .el-input__inner:focus,
::v-deep .el-select:hover .el-input__inner {
  border-color: #667eea;
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
}

::v-deep .el-input-group__prepend {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 6px 0 0 6px;
}

/* Iconos de texto */
.text-primary { color: #667eea !important; }
.text-info { color: #17a2b8 !important; }
.text-success { color: #28a745 !important; }
.text-warning { color: #ffc107 !important; }
.text-danger { color: #dc3545 !important; }

/* Toggle de campos adicionales */
.toggle-wrapper {
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.toggle-section {
  text-align: center;
  padding: 15px;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 8px;
  border: 2px dashed #667eea;
  transition: all 0.3s ease;
  cursor: pointer;
}

.toggle-section:hover {
  background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
  border-color: #764ba2;
  transform: scale(1.05);
}

.toggle-label {
  font-size: 15px;
  font-weight: 500;
  color: #495057;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.toggle-label i {
  transition: transform 0.3s ease;
  color: #667eea;
}

.toggle-hint {
  display: block;
  font-size: 11px;
  color: #6c757d;
  margin-top: 5px;
  font-style: italic;
}

::v-deep .toggle-section .el-checkbox {
  transform: scale(1.3);
}

::v-deep .toggle-section .el-checkbox__label {
  font-size: 15px;
}

/* Botón de búsqueda en número de identificación */
::v-deep .el-input-group__append {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 0 6px 6px 0;
}

::v-deep .el-input-group__append .el-button {
  background: transparent;
  color: white;
  border: none;
  margin: 0;
  padding: 0 15px;
}

::v-deep .el-input-group__append .el-button:hover {
  background: rgba(255, 255, 255, 0.2);
}

/* Mensajes de validación */
.form-control-feedback {
  color: #dc3545;
  font-size: 12px;
  margin-top: 5px;
  display: block;
  animation: shake 0.3s ease;
}

.has-danger .el-input__inner,
.has-danger .el-select .el-input__inner {
  border-color: #dc3545 !important;
}

/* Footer del formulario */
::v-deep .el-dialog__footer {
  padding: 20px 30px;
  background: #f8f9fa;
  border-top: 1px solid #e9ecef;
}

::v-deep .el-dialog__footer .el-button {
  padding: 12px 30px;
  font-weight: 500;
  border-radius: 6px;
  transition: all 0.3s ease;
}

::v-deep .el-dialog__footer .el-button--primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
}

::v-deep .el-dialog__footer .el-button--primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

/* Animaciones */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideInLeft {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}

/* Responsivo */
@media (max-width: 768px) {
  ::v-deep .persons-modal-modern .el-dialog {
    width: 95% !important;
  }
  
  .section-card {
    padding: 15px;
  }
  
  .section-title {
    font-size: 16px;
  }
  
  .toggle-wrapper {
    margin-top: 15px;
    align-items: center;
  }
}
</style>
