<!-- ===================================================== -->
<!-- MODAL FERIADOS -->
<!-- ===================================================== -->

<div class="modal fade" id="holidayModal" tabindex="-1" role="dialog" aria-labelledby="holidayModalLabel"
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

                        <i class="fas fa-calendar-alt text-danger"></i>

                    </div>

                    <div>

                        <h5 class="modal-title mb-0" id="holidayModalLabel">

                            Nuevo Feriado

                        </h5>

                        <small class="text-muted">

                            Gestión de fechas excluidas para cálculo automático de mora

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

                <form id="holidayForm" autocomplete="off" class="row">

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
                                                #991b1b
                                            );
                                            color:white;
                                            font-size:45px;
                                            box-shadow:
                                            0 6px 18px rgba(0,0,0,.1);
                                        ">

                                        <i class="fas fa-calendar-day"></i>

                                    </div>

                                </div>

                                <h5 class="font-weight-bold text-dark mb-1">

                                    Gestión de Feriados

                                </h5>

                                <small class="text-muted">

                                    Exclusión automática de mora

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

                                        Exclusión automática de fechas especiales

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

                                    <!-- FECHA -->
                                    <div class="form-group col-md-6">

                                        <label for="date" class="small font-weight-bold text-secondary">

                                            FECHA
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="date" id="date" name="date"
                                            class="form-control form-control-sm">

                                        <span class="invalid-feedback" id="date-error"></span>

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
                                <!-- FILA 2 -->
                                <!-- ================================= -->

                                <div class="form-row">

                                    <!-- DESCRIPCIÓN -->
                                    <div class="form-group col-12">

                                        <label for="description" class="small font-weight-bold text-secondary">

                                            DESCRIPCIÓN
                                            <span class="text-danger">*</span>

                                        </label>

                                        <textarea id="description" name="description" rows="4" class="form-control form-control-sm"
                                            placeholder="Ej: Fiestas Patrias, Navidad, Año Nuevo..."></textarea>

                                        <span class="invalid-feedback" id="description-error"></span>

                                    </div>

                                </div>

                                <!-- ================================= -->
                                <!-- ALERTA -->
                                <!-- ================================= -->

                                <div class="alert alert-warning border-0 shadow-sm mt-4">

                                    <div class="d-flex">

                                        <div class="mr-3">

                                            <i class="fas fa-exclamation-circle fa-2x text-warning"></i>

                                        </div>

                                        <div>

                                            <strong>
                                                Importante:
                                            </strong>

                                            <br>

                                            Los feriados activos podrán ser
                                            excluidos automáticamente del
                                            cálculo de mora dependiendo de
                                            la configuración financiera.

                                        </div>

                                    </div>

                                </div>

                                <!-- ================================= -->
                                <!-- ACCIONES -->
                                <!-- ================================= -->

                                <div class="form-row mt-4">

                                    <div class="col-12 d-flex justify-content-end">

                                        <button type="button" class="btn btn-light border mr-2" data-dismiss="modal">

                                            <i class="fas fa-times mr-1"></i>
                                            Cerrar

                                        </button>

                                        <button type="submit" class="btn btn-danger" id="btnSaveHoliday">

                                            <i class="fas fa-save mr-1"></i>
                                            Guardar Feriado

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
