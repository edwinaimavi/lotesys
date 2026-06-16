<!-- ===================================================== -->
<!-- MODAL CONFIGURACIÓN DE MORA -->
<!-- ===================================================== -->

<div class="modal fade" id="lateFeeSettingModal" tabindex="-1" role="dialog" aria-labelledby="lateFeeSettingModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

        <div class="modal-content shadow-lg border-0 rounded-lg overflow-hidden">

            <!-- ========================================= -->
            <!-- HEADER -->
            <!-- ========================================= -->

            <div class="modal-header align-items-center"
                style="
                    background:
                    linear-gradient(
                        90deg,
                        #ffffff,
                        #f8f4f4
                    );
                    border-bottom:1px solid #e6eaee;
                ">

                <div class="d-flex align-items-center">

                    <div class="icon-circle bg-light mr-3 icon_modal">

                        <i class="fas fa-exclamation-triangle text-danger"></i>

                    </div>

                    <div>

                        <h5 class="modal-title mb-0" id="lateFeeSettingModalLabel">

                            Nueva Configuración de Mora

                        </h5>

                        <small class="text-muted">

                            Parámetros financieros para cálculo automático de mora

                        </small>

                    </div>

                </div>

                <button type="button" class="close ml-3" data-dismiss="modal" aria-label="Close" style="opacity:.9;">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <!-- ========================================= -->
            <!-- BODY -->
            <!-- ========================================= -->

            <div class="modal-body p-3" style="background: #f8fbfc;">

                <form id="lateFeeSettingForm" autocomplete="off" class="row">

                    @csrf

                    <!-- ===================================== -->
                    <!-- PANEL IZQUIERDO -->
                    <!-- ===================================== -->

                    <div class="col-lg-4 mb-3">

                        <div class="card border-0 rounded-lg shadow-sm h-100">

                            <div class="card-body text-center py-4">

                                <div class="mb-3">

                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                                        style="
                                            width:120px;
                                            height:120px;
                                            background:
                                            linear-gradient(
                                                135deg,
                                                #dc3545,
                                                #b02a37
                                            );
                                            color:white;
                                            font-size:45px;
                                            box-shadow:
                                            0 6px 18px rgba(0,0,0,.1);
                                        ">

                                        <i class="fas fa-exclamation-triangle"></i>

                                    </div>

                                </div>

                                <h5 class="font-weight-bold text-dark mb-1">

                                    Configuración Mora

                                </h5>

                                <small class="text-muted">

                                    Gestión financiera automática

                                </small>

                                <hr>

                                <div class="text-left">

                                    <small class="text-muted">

                                        Fecha de registro

                                    </small>

                                    <div class="font-weight-600">

                                        {{ now()->format('d/m/Y') }}

                                    </div>

                                    <small class="text-muted d-block mt-3">

                                        Estado inicial

                                    </small>

                                    <div class="badge badge-success py-2 px-3 mt-1">

                                        Activo

                                    </div>

                                    <small class="text-muted d-block mt-3">

                                        Módulo

                                    </small>

                                    <div class="font-weight-600">

                                        Configuración Financiera

                                    </div>

                                    <small class="text-muted d-block mt-3">

                                        Función

                                    </small>

                                    <div class="font-weight-600">

                                        Control automático de mora

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- ===================================== -->
                    <!-- PANEL DERECHO -->
                    <!-- ===================================== -->

                    <div class="col-lg-8">

                        <div class="card border-0 rounded-lg shadow-sm">

                            <div class="card-body">

                                <!-- ================================= -->
                                <!-- FILA 1 -->
                                <!-- ================================= -->

                                <div class="form-row">

                                    <!-- DÍAS GRACIA -->
                                    <div class="form-group col-md-6">

                                        <label for="grace_days" class="small font-weight-bold text-secondary">

                                            DÍAS DE GRACIA
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="number" id="grace_days" name="grace_days"
                                            class="form-control form-control-sm" placeholder="Ej: 3">

                                        <span class="invalid-feedback" id="grace_days-error"></span>

                                    </div>

                                    <!-- MORA DIARIA -->
                                    <div class="form-group col-md-6">

                                        <label for="daily_late_fee" class="small font-weight-bold text-secondary">

                                            MORA DIARIA
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="number" step="0.01" id="daily_late_fee" name="daily_late_fee"
                                            class="form-control form-control-sm" placeholder="Ej: 5.00">

                                        <span class="invalid-feedback" id="daily_late_fee-error"></span>

                                    </div>

                                </div>

                                <!-- ================================= -->
                                <!-- FILA 2 -->
                                <!-- ================================= -->

                                <div class="form-row">

                                    <!-- MORA MÁXIMA -->
                                    <div class="form-group col-md-6">

                                        <label for="max_late_fee" class="small font-weight-bold text-secondary">

                                            MORA MÁXIMA

                                        </label>

                                        <input type="number" step="0.01" id="max_late_fee" name="max_late_fee"
                                            class="form-control form-control-sm" placeholder="Ej: 500.00">

                                        <span class="invalid-feedback" id="max_late_fee-error"></span>

                                    </div>

                                    <!-- ESTADO -->
                                    <div class="form-group col-md-6">

                                        <label for="status" class="small font-weight-bold text-secondary">

                                            ESTADO
                                            <span class="text-danger">*</span>

                                        </label>

                                        <select id="status" name="status" class="form-control form-control-sm">

                                            <option value="activo">

                                                Activo

                                            </option>

                                            <option value="inactivo">

                                                Inactivo

                                            </option>

                                        </select>

                                        <span class="invalid-feedback" id="status-error"></span>

                                    </div>

                                </div>

                                <!-- ================================= -->
                                <!-- FILA 3 -->
                                <!-- ================================= -->

                                <div class="form-row">

                                    <!-- DOMINGOS -->
                                    <div class="form-group col-md-6">

                                        <label class="small font-weight-bold text-secondary d-block">

                                            ¿APLICAR DOMINGOS?

                                        </label>

                                        <div class="custom-control custom-switch mt-2">

                                            <input type="checkbox" class="custom-control-input" id="apply_sundays"
                                                name="apply_sundays" value="1">

                                            <label class="custom-control-label" for="apply_sundays">

                                                Sí, calcular domingos

                                            </label>

                                        </div>

                                    </div>

                                    <!-- FERIADOS -->
                                    <div class="form-group col-md-6">

                                        <label class="small font-weight-bold text-secondary d-block">

                                            ¿APLICAR FERIADOS?

                                        </label>

                                        <div class="custom-control custom-switch mt-2">

                                            <input type="checkbox" class="custom-control-input" id="apply_holidays"
                                                name="apply_holidays" value="1">

                                            <label class="custom-control-label" for="apply_holidays">

                                                Sí, calcular feriados

                                            </label>

                                        </div>

                                    </div>

                                </div>

                                <!-- ================================= -->
                                <!-- ALERTA -->
                                <!-- ================================= -->

                                <div class="alert alert-danger border-0 shadow-sm mt-4">

                                    <div class="d-flex">

                                        <div class="mr-3">

                                            <i class="fas fa-exclamation-circle fa-2x text-danger"></i>

                                        </div>

                                        <div>

                                            <strong>
                                                Importante:
                                            </strong>

                                            <br>

                                            Esta configuración afectará
                                            automáticamente el cálculo de mora
                                            en cronogramas, pagos y cuotas vencidas.

                                        </div>

                                    </div>

                                </div>

                                <!-- ================================= -->
                                <!-- ACCIONES -->
                                <!-- ================================= -->

                                <div class="form-row mt-4">

                                    <div class="col-12 d-flex justify-content-end">

                                        <button type="button" class="btn btn-light border mr-2"
                                            data-dismiss="modal">

                                            <i class="fas fa-times mr-1"></i>
                                            Cerrar

                                        </button>

                                        <button type="submit" class="btn btn-danger" id="btnSaveLateFeeSetting">

                                            <i class="fas fa-save mr-1"></i>
                                            Guardar Configuración

                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>
