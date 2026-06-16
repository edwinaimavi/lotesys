<!-- MODAL EMPRESA -->
<div class="modal fade"
     id="companyModal"
     tabindex="-1"
     role="dialog"
     aria-labelledby="companyModalLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">

        <div class="modal-content shadow-lg border-0 rounded-lg overflow-hidden">

            <!-- HEADER -->
            <div class="modal-header align-items-center"
                 style="background: linear-gradient(90deg,#ffffff,#f3f6f8); border-bottom:1px solid #e6eaee;">

                <div class="d-flex align-items-center">

                    <div class="icon-circle bg-light mr-3 icon_modal">

                        <i class="fas fa-building text-primary"></i>

                    </div>

                    <div>

                        <h5 class="modal-title mb-0" id="companyModalLabel">
                            Nueva Empresa
                        </h5>

                        <small class="text-muted">
                            Registro de información empresarial
                        </small>

                    </div>

                </div>

                <button type="button"
                        class="close ml-3"
                        data-dismiss="modal"
                        aria-label="Close"
                        style="opacity:.9;">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <!-- BODY -->
            <div class="modal-body p-3" style="background: #f8fbfc;">

                <form id="companyForm"
                      autocomplete="off"
                      class="row">

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
                                            background:linear-gradient(135deg,#007bff,#0056b3);
                                            color:white;
                                            font-size:45px;
                                            box-shadow:0 6px 18px rgba(0,0,0,.1);
                                         ">

                                        <i class="fas fa-building"></i>

                                    </div>

                                </div>

                                <h5 class="font-weight-bold text-dark mb-1">
                                    Empresa
                                </h5>

                                <small class="text-muted">
                                    Módulo de gestión empresarial
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
                                        Estado
                                    </small>

                                    <div class="badge badge-success py-2 px-3 mt-1">
                                        Activo
                                    </div>

                                    <small class="text-muted d-block mt-3">
                                        Módulo
                                    </small>

                                    <div class="font-weight-600">
                                        Empresas
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

                                    <div class="form-group col-md-8">

                                        <label for="business_name"
                                               class="small font-weight-bold text-secondary">

                                            RAZÓN SOCIAL
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="text"
                                               class="form-control form-control-sm"
                                               id="business_name"
                                               name="business_name"
                                               placeholder="Razón social de la empresa">

                                        <span class="invalid-feedback"
                                              id="business_name-error"></span>

                                    </div>

                                    <div class="form-group col-md-4">

                                        <label for="ruc"
                                               class="small font-weight-bold text-secondary">

                                            RUC
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="text"
                                               class="form-control form-control-sm"
                                               id="ruc"
                                               name="ruc"
                                               maxlength="11"
                                               placeholder="20123456789">

                                        <span class="invalid-feedback"
                                              id="ruc-error"></span>

                                    </div>

                                </div>

                                <!-- FILA 2 -->
                                <div class="form-row">

                                    <div class="form-group col-md-12">

                                        <label for="trade_name"
                                               class="small font-weight-bold text-secondary">

                                            NOMBRE COMERCIAL

                                        </label>

                                        <input type="text"
                                               class="form-control form-control-sm"
                                               id="trade_name"
                                               name="trade_name"
                                               placeholder="Nombre comercial">

                                        <span class="invalid-feedback"
                                              id="trade_name-error"></span>

                                    </div>

                                </div>

                                <!-- FILA 3 -->
                                <div class="form-row">

                                    <div class="form-group col-md-12">

                                        <label for="address"
                                               class="small font-weight-bold text-secondary">

                                            DIRECCIÓN

                                        </label>

                                        <input type="text"
                                               class="form-control form-control-sm"
                                               id="address"
                                               name="address"
                                               placeholder="Dirección de la empresa">

                                        <span class="invalid-feedback"
                                              id="address-error"></span>

                                    </div>

                                </div>

                                <!-- FILA 4 -->
                                <div class="form-row">

                                    <div class="form-group col-md-6">

                                        <label for="phone"
                                               class="small font-weight-bold text-secondary">

                                            TELÉFONO

                                        </label>

                                        <input type="text"
                                               class="form-control form-control-sm"
                                               id="phone"
                                               name="phone"
                                               placeholder="999999999">

                                        <span class="invalid-feedback"
                                              id="phone-error"></span>

                                    </div>

                                    <div class="form-group col-md-6">

                                        <label for="email"
                                               class="small font-weight-bold text-secondary">

                                            CORREO ELECTRÓNICO

                                        </label>

                                        <input type="email"
                                               class="form-control form-control-sm"
                                               id="email"
                                               name="email"
                                               placeholder="empresa@correo.com">

                                        <span class="invalid-feedback"
                                              id="email-error"></span>

                                    </div>

                                </div>

                                <!-- FILA 5 -->
                                <div class="form-row">

                                    <div class="form-group col-md-6">

                                        <label for="status"
                                               class="small font-weight-bold text-secondary">

                                            ESTADO

                                        </label>

                                        <select id="status"
                                                name="status"
                                                class="form-control form-control-sm">

                                            <option value="1" selected>
                                                Activo
                                            </option>

                                            <option value="0">
                                                Inactivo
                                            </option>

                                        </select>

                                        <span class="invalid-feedback"
                                              id="status-error"></span>

                                    </div>

                                </div>

                                <!-- NOTA -->
                                <div class="form-row">

                                    <div class="col-12">

                                        <small class="text-muted">

                                            La información registrada aquí será utilizada
                                            en todos los módulos del sistema.

                                        </small>

                                    </div>

                                </div>

                                <!-- ACCIONES -->
                                <div class="form-row mt-4">

                                    <div class="col-12 d-flex justify-content-end">

                                        <button type="button"
                                                class="btn btn-light border mr-2"
                                                data-dismiss="modal">

                                            <i class="fas fa-times mr-1"></i>
                                            Cerrar

                                        </button>

                                        <button type="submit"
                                                class="btn btn-primary"
                                                id="btnSaveCompany">

                                            <i class="fas fa-save mr-1"></i>
                                            Guardar Empresa

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