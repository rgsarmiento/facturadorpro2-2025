<template>
    <div>
        <el-dialog :title="titleDialog" :visible="showDialog" @open="create" width="30%">
            <div class="row">
                <div class="col-lg-4 col-md-4 col-sm-4 text-center font-weight-bold">
                    <p>Imprimir A4</p>
                    <button type="button" class="btn btn-lg btn-info waves-effect waves-light" @click="clickToPrint('a4')" :disabled="dataLoading">
                        <i class="fa fa-print" v-if="!dataLoading"></i>
                        <i class="fa fa-spinner fa-spin" v-if="dataLoading"></i>
                    </button>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 text-center font-weight-bold">
                    <p>Imprimir Ticket</p>
                    <button type="button" class="btn btn-lg btn-info waves-effect waves-light" @click="clickToPrint('ticket')" :disabled="dataLoading">
                        <i class="fa fa-print" v-if="!dataLoading"></i>
                        <i class="fa fa-spinner fa-spin" v-if="dataLoading"></i>
                    </button>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 text-center font-weight-bold">
                    <p>Imprimir A5</p>
                    <button type="button" class="btn btn-lg btn-info waves-effect waves-light" @click="clickToPrint('a5')" :disabled="dataLoading">
                        <i class="fa fa-print" v-if="!dataLoading"></i>
                        <i class="fa fa-spinner fa-spin" v-if="dataLoading"></i>
                    </button>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-6 text-center font-weight-bold">
                    <p>Descargar A4</p>
                    <button type="button" class="btn btn-lg btn-info waves-effect waves-light" @click="clickDownload('a4')" :disabled="dataLoading">
                        <i class="fa fa-download" v-if="!dataLoading"></i>
                        <i class="fa fa-spinner fa-spin" v-if="dataLoading"></i>
                    </button>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 text-center font-weight-bold">
                    <p>Descargar Ticket</p>
                    <button type="button" class="btn btn-lg btn-info waves-effect waves-light" @click="clickDownload('ticket')" :disabled="dataLoading">
                        <i class="fa fa-download" v-if="!dataLoading"></i>
                        <i class="fa fa-spinner fa-spin" v-if="dataLoading"></i>
                    </button>
                </div>
            </div>


            <!-- <span slot="footer" class="dialog-footer row"> -->
            <span slot="footer" class="dialog-footer">
                <!-- <div class="col-md-6">
                    <el-input v-model="form.customer_email">
                        <el-button slot="append" icon="el-icon-message"   @click="clickSendEmail" :loading="loading">Enviar</el-button>
                    </el-input>
                </div> -->
                <!-- <div class="col-md-6">  -->
                    <el-button @click="clickClose">Cerrar</el-button>
                <!-- </div> -->

            </span>
        </el-dialog>


    </div>
</template>

<script>


    export default {

        props: ['showDialog', 'recordId', 'showClose'],
        data() {
            return {
                titleDialog: null,
                resource: 'quotations',
                form: {},
                loading: false,
                dataLoading: true,

            }
        },
        created() {
            this.initForm()
        },
        methods: {
            initForm() {
                this.form = {
                    id: null,
                    external_id: null,
                }
                this.dataLoading = true
            },
            create() {
                this.dataLoading = true
                this.$http.get(`/${this.resource}/record/${this.recordId}`)
                    .then(response => {
                        this.form = response.data.data
                        this.titleDialog = `Cotización registrada: ${this.form.identifier}`
                        this.dataLoading = false
                    })
                    .catch(error => {
                        this.$message.error('Error al cargar los datos de la cotización')
                        this.dataLoading = false
                    })
            },
            clickClose() {
                this.$emit('update:showDialog', false)
                this.initForm()
            },
            clickToPrint(format){
                // Verificar que los datos estén cargados
                if (this.dataLoading) {
                    this.$message.warning('Cargando datos de la cotización. Espere un momento e intente nuevamente.');
                    return;
                }
                
                if (!this.form || !this.form.id) {
                    this.$message.error('Datos de la cotización aún no cargados. Intente nuevamente.');
                    return;
                }
                
                // Usar external_id si existe y no es null, sino usar id regular
                const quotationId = (this.form.external_id && this.form.external_id !== null) ? this.form.external_id : this.form.id;
                
                // Validar que tenemos un ID válido
                if (!quotationId) {
                    this.$message.error('No se puede generar el PDF: ID de cotización no válido');
                    return;
                }
                
                window.open(`/${this.resource}/print/${quotationId}/${format}`, '_blank');
            } ,
            clickDownload(format){
                // Verificar que los datos estén cargados
                if (this.dataLoading) {
                    this.$message.warning('Cargando datos de la cotización. Espere un momento e intente nuevamente.');
                    return;
                }
                
                if (!this.form || !this.form.id) {
                    this.$message.error('Datos de la cotización aún no cargados. Intente nuevamente.');
                    return;
                }
                
                // Usar external_id si existe y no es null, sino usar id regular
                const quotationId = (this.form.external_id && this.form.external_id !== null) ? this.form.external_id : this.form.id;
                
                // Validar que tenemos un ID válido
                if (!quotationId) {
                    this.$message.error('No se puede descargar el PDF: ID de cotización no válido');
                    return;
                }
                
                window.open(`/${this.resource}/download/${quotationId}/${format}`, '_blank');
            } ,

            clickSendEmail()
            {
                this.loading = true
                console.log(this.resource)
                this.$http.post(`/${this.resource}/email`, {

                    customer_email: this.customer_email,
                    id: this.form.id,
                    customer_id: this.form.quotation.customer_id
                })
                .then(response => {
                    if (response.data.success) {
                        this.$message.success('El correo fue enviado satisfactoriamente')
                    } else {
                        this.$message.error('Error al enviar el correo')
                    }
                })
                .catch(error => {
                    this.$message.error('Error al enviar el correo')
                })
                .then(() => {
                    this.loading = false
                })
            }
        }
    }
</script>
