<template>
    <div class="card mb-0 pt-2 pt-md-0">
        <div class="card-header bg-info">
            <h3 class="my-0">Cambiar Ambiente de Operación - (HABILITACIÓN - PRODUCCIÓN)</h3>
        </div>
        <div class="card-body">
            <div class="invoice">
                <form>
                    <div class="form-body">
                        <!-- Sección de ResponseDian -->
                        <div class="row">
                            <div class="col-lg-6 col-md-12">
                                <div class="form-group">
                                    <label class="control-label font-weight-bold">
                                        ResponseDian
                                        <small class="text-muted">(Seleccione de aquí la llave técnica)</small>
                                    </label>
                                    <el-input
                                        type="textarea"
                                        class="custom-textarea"
                                        v-model="production.technicalkey">
                                    </el-input>
                                </div>
                            </div>

                            <!-- Sección de Acciones en tabla -->
                            <div class="col-lg-6 col-md-12">
                                <label class="control-label font-weight-bold mb-3 d-block">
                                    Acciones de Cambio de Ambiente
                                </label>
                                <table class="table table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th width="40%">Módulo</th>
                                            <th width="30%" class="text-center">Habilitación</th>
                                            <th width="30%" class="text-center">Producción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="align-middle font-weight-bold">
                                                <i class="fas fa-file-invoice text-primary mr-2"></i>
                                                Facturación
                                            </td>
                                            <td class="text-center">
                                                <el-button
                                                    :loading="loadingCompany"
                                                    size="small"
                                                    type="warning"
                                                    @click="validateProduction('H')">
                                                    <i class="el-icon-setting"></i> Activar
                                                </el-button>
                                            </td>
                                            <td class="text-center">
                                                <el-button
                                                    :loading="loadingCompany"
                                                    size="small"
                                                    type="success"
                                                    @click="validateProduction('P')">
                                                    <i class="el-icon-check"></i> Activar
                                                </el-button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="align-middle font-weight-bold">
                                                <i class="fas fa-money-check-alt text-success mr-2"></i>
                                                Nómina
                                            </td>
                                            <td class="text-center">
                                                <el-button
                                                    :loading="loadingPayroll"
                                                    size="small"
                                                    type="warning"
                                                    @click="validateProduction('payrollH')">
                                                    <i class="el-icon-setting"></i> Activar
                                                </el-button>
                                            </td>
                                            <td class="text-center">
                                                <el-button
                                                    :loading="loadingPayroll"
                                                    size="small"
                                                    type="success"
                                                    @click="validateProduction('payrollP')">
                                                    <i class="el-icon-check"></i> Activar
                                                </el-button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="align-middle font-weight-bold">
                                                <i class="fas fa-file-alt text-info mr-2"></i>
                                                Docs. Equivalentes
                                            </td>
                                            <td class="text-center">
                                                <el-button
                                                    :loading="loadingEqDocs"
                                                    size="small"
                                                    type="warning"
                                                    @click="validateProduction('eqdocsH')">
                                                    <i class="el-icon-setting"></i> Activar
                                                </el-button>
                                            </td>
                                            <td class="text-center">
                                                <el-button
                                                    :loading="loadingEqDocs"
                                                    size="small"
                                                    type="success"
                                                    @click="validateProduction('eqdocsP')">
                                                    <i class="el-icon-check"></i> Activar
                                                </el-button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
    .custom-textarea >>> .el-textarea__inner {
        min-height: 400px !important;
        font-family: 'Courier New', Courier, monospace;
        font-size: 13px;
        line-height: 1.5;
    }

    .table {
        margin-bottom: 0;
    }

    .table thead th {
        background-color: #f8f9fa;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    @media (max-width: 991px) {
        .custom-textarea >>> .el-textarea__inner {
            min-height: 300px !important;
        }
    }

    .btn-group {
        display: flex;
        gap: 8px;
    }

    @media (max-width: 768px) {
        .d-flex.flex-wrap {
            flex-direction: column;
            align-items: flex-start !important;
        }

        .btn-group {
            width: 100%;
            margin-top: 10px;
        }

        .btn-group .el-button {
            flex: 1;
        }
    }

    .alert-info {
        border-left: 4px solid #17a2b8;
    }
</style>

<script>
    import Helper from "../../../mixins/Helper";

    export default {
        mixins: [Helper],
        data: () => ({
            loadingCompany: false,
            production: { technicalkey: ''},
            route: 'co-configuration/production',
            loadingPayroll: false,
            loadingEqDocs: false
        }),

        methods: {
            validateProduction(environment) {
                if(['P', 'H'].includes(environment))
                    this.loadingCompany = true;
                else
                    if(['payrollP', 'payrollH'].includes(environment))
                        this.loadingPayroll = true;
                    else
                        this.loadingEqDocs = true;

                axios
                    .post(`${this.route}/changeEnvironmentProduction/${environment}`)
                    .then(response => {
                        this.$message.success(response.data)
                    })
                    .catch(error => {
                        this.$message.error(error.response.data)
                    })
                    .then(() => {
                        this.loadingCompany = false;
                        this.loadingPayroll = false;
                        this.loadingEqDocs = false;
                    });

                if(environment == 'P'){
                    this.loadingCompany = true;
                    axios
                        .post(`${this.route}/queryTechnicalKey`)
                        .then(response => {
    //                        this.$setLaravelMessage(response.data);
                            if(response.data.success){
                                this.production.technicalkey = JSON.stringify(response.data, null, 2)
                            }
                            else{
                                this.$message.error(response.data.message)
                            }
                        })
                        .catch(error => {
                        // this.$setLaravelValidationErrorsFromResponse(error.response.data);
                            //this.$setLaravelErrors(error.response.data);
                            this.$message.error(error.response.data)
                        })
                        .then(() => {
                            this.loadingCompany = false;
                        });
                }
                else
                    this.production.technicalkey = "No se pueden consultar claves tecnicas para ambiente de HABILITACION."
            }
        }
    };
</script>
