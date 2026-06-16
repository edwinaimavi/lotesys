<!-- MODAL BANCO -->
<div class="modal fade" id="bankModal" tabindex="-1" role="dialog" aria-labelledby="bankModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

        <div class="modal-content shadow-lg border-0 rounded-lg overflow-hidden">

            <!-- HEADER -->
            <div class="modal-header align-items-center"
                style="
                    background:
                    linear-gradient(90deg,#ffffff,#f3f6f8);
                    border-bottom:1px solid #e6eaee;
                ">

                <div class="d-flex align-items-center">

                    <div class="icon-circle bg-light mr-3 icon_modal">

                        <i class="fas fa-university text-primary"></i>

                    </div>

                    <div>

                        <h5 class="modal-title mb-0" id="bankModalLabel">

                            Nuevo Banco

                        </h5>

                        <small class="text-muted">

                            Registro de cuentas bancarias

                        </small>

                    </div>

                </div>

                <button type="button" class="close ml-3" data-dismiss="modal" aria-label="Close" style="opacity:.9;">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <!-- BODY -->
            <div class="modal-body p-3" style="background: #f8fbfc;">

                <form id="bankForm" autocomplete="off" class="row">

                    @csrf

                    <!-- PANEL IZQUIERDO -->
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
                                                #007bff,
                                                #0056b3
                                            );
                                            color:white;
                                            font-size:45px;
                                            box-shadow:
                                            0 6px 18px rgba(0,0,0,.1);
                                        ">

                                        <i class="fas fa-university"></i>

                                    </div>

                                </div>

                                <h5 class="font-weight-bold text-dark mb-1">

                                    Bancos

                                </h5>

                                <small class="text-muted">

                                    Gestión financiera bancaria

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

                                        Bancos

                                    </div>

                                    <small class="text-muted d-block mt-3">

                                        Función

                                    </small>

                                    <div class="font-weight-600">

                                        Registro de cuentas bancarias

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- PANEL DERECHO -->
                    <div class="col-lg-8">

                        <div class="card border-0 rounded-lg shadow-sm">

                            <div class="card-body">

                                <!-- FILA 1 -->
                                <div class="form-row">

                                    <!-- BANCO -->
                                    <div class="form-group col-md-6">

                                        <label for="bank_name" class="small font-weight-bold text-secondary">

                                            BANCO
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="text" id="bank_name" name="bank_name"
                                            class="form-control form-control-sm" placeholder="Ej: BCP">

                                        <span class="invalid-feedback" id="bank_name-error"></span>

                                    </div>

                                    <!-- MONEDA -->
                                    <div class="form-group col-md-6">

                                        <label for="currency" class="small font-weight-bold text-secondary">

                                            MONEDA
                                            <span class="text-danger">*</span>

                                        </label>

                                        <select id="currency" name="currency" class="form-control form-control-sm">

                                            <option value="PEN">

                                                SOLES (PEN)

                                            </option>

                                            <option value="USD">

                                                DÓLARES (USD)

                                            </option>

                                        </select>

                                        <span class="invalid-feedback" id="currency-error"></span>

                                    </div>

                                </div>

                                <!-- FILA 2 -->
                                <div class="form-row">

                                    <!-- CUENTA -->
                                    <div class="form-group col-md-6">

                                        <label for="account_number" class="small font-weight-bold text-secondary">

                                            N° CUENTA
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="text" id="account_number" name="account_number"
                                            class="form-control form-control-sm" placeholder="Ingrese número de cuenta">

                                        <span class="invalid-feedback" id="account_number-error"></span>

                                    </div>

                                    <!-- CCI -->
                                    <div class="form-group col-md-6">

                                        <label for="cci" class="small font-weight-bold text-secondary">

                                            CCI
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="text" id="cci" name="cci"
                                            class="form-control form-control-sm" placeholder="Ingrese código CCI">

                                        <span class="invalid-feedback" id="cci-error"></span>

                                    </div>

                                </div>

                                <!-- FILA 3 -->
                                <div class="form-row">

                                    <!-- TITULAR -->
                                    <div class="form-group col-md-6">

                                        <label for="account_holder" class="small font-weight-bold text-secondary">

                                            TITULAR
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="text" id="account_holder" name="account_holder"
                                            class="form-control form-control-sm" placeholder="Nombre del titular">

                                        <span class="invalid-feedback" id="account_holder-error"></span>

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

                                <!-- OBSERVACIÓN -->
                                <div class="form-row">

                                    <div class="form-group col-md-12">

                                        <label for="description" class="small font-weight-bold text-secondary">

                                            DESCRIPCIÓN

                                        </label>

                                        <textarea id="description" name="description" rows="4" class="form-control form-control-sm"
                                            placeholder="Ingrese observaciones del banco"></textarea>

                                    </div>

                                </div>

                                <!-- ALERTA -->
                                <div class="alert alert-info border-0 shadow-sm">

                                    <div class="d-flex">

                                        <div class="mr-3">

                                            <i class="fas fa-info-circle fa-2x text-primary"></i>

                                        </div>

                                        <div>

                                            <strong>
                                                Importante:
                                            </strong>

                                            <br>

                                            Los datos bancarios serán utilizados
                                            en pagos, contratos y amortizaciones.

                                        </div>

                                    </div>

                                </div>

                                <!-- ACCIONES -->
                                <div class="form-row mt-4">

                                    <div class="col-12 d-flex justify-content-end">

                                        <button type="button" class="btn btn-light border mr-2"
                                            data-dismiss="modal">

                                            <i class="fas fa-times mr-1"></i>
                                            Cerrar

                                        </button>

                                        <button type="submit" class="btn btn-primary" id="btnSaveBank">

                                            <i class="fas fa-save mr-1"></i>
                                            Guardar Banco

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
