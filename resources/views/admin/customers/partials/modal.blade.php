<!-- MODAL CLIENTE -->
<div class="modal fade" id="customerModal" tabindex="-1" role="dialog" aria-labelledby="customerModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

        <div class="modal-content shadow-lg border-0 rounded-lg overflow-hidden">

            <!-- HEADER -->
            <div class="modal-header align-items-center"
                style="background: linear-gradient(90deg,#ffffff,#f3f6f8); border-bottom:1px solid #e6eaee;">

                <div class="d-flex align-items-center">

                    <div class="icon-circle bg-light mr-3 icon_modal">

                        <i class="fas fa-users text-primary"></i>

                    </div>

                    <div>

                        <h5 class="modal-title mb-0" id="customerModalLabel">

                            Nuevo Cliente

                        </h5>

                        <small class="text-muted">
                            Gestión de clientes del sistema
                        </small>

                    </div>

                </div>

                <button type="button" class="close ml-3" data-dismiss="modal" aria-label="Close" style="opacity:.9;">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <!-- BODY -->
            <div class="modal-body p-3" style="background: #f8fbfc;">

                <form id="customerForm" autocomplete="off" class="row">

                    @csrf

                    <input type="hidden" name="department" id="department">
                    <input type="hidden" name="province" id="province">
                    <input type="hidden" name="district" id="district">
                    <input type="hidden" name="ubigeo" id="ubigeo">

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

                                        <i class="fas fa-user-friends"></i>

                                    </div>

                                </div>

                                <h5 class="font-weight-bold text-dark mb-1">
                                    Cliente
                                </h5>

                                <small class="text-muted">
                                    Gestión de clientes inmobiliarios
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
                                        Clientes
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

                                    <!-- TIPO PERSONA -->
                                    <div class="form-group col-md-4">

                                        <label class="small font-weight-bold text-secondary">

                                            TIPO PERSONA
                                            <span class="text-danger">*</span>

                                        </label>

                                        <select name="person_type" id="person_type"
                                            class="form-control form-control-sm">

                                            <option value="natural">
                                                Persona Natural
                                            </option>

                                            <option value="juridica">
                                                Persona Jurídica
                                            </option>

                                        </select>

                                    </div>

                                    <!-- TIPO DOCUMENTO -->
                                    <div class="form-group col-md-4">

                                        <label class="small font-weight-bold text-secondary">

                                            TIPO DOCUMENTO
                                            <span class="text-danger">*</span>

                                        </label>

                                        <select name="document_type" id="document_type"
                                            class="form-control form-control-sm">

                                            <option value="">
                                                Seleccione
                                            </option>

                                            <option value="DNI">
                                                DNI
                                            </option>

                                            <option value="CE">
                                                CE
                                            </option>

                                            <option value="RUC">
                                                RUC
                                            </option>

                                        </select>

                                    </div>

                                    <!-- NUMERO DOCUMENTO -->
                                    <div class="form-group col-md-4">

                                        <label class="small font-weight-bold text-secondary">

                                            N° DOCUMENTO
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="text" name="document_number" id="document_number"
                                            class="form-control form-control-sm">

                                    </div>

                                </div>

                                <!-- PERSONA NATURAL -->
                                <div id="naturalFields">

                                    <div class="form-row">

                                        <div class="form-group col-md-6">

                                            <label class="small font-weight-bold text-secondary">

                                                NOMBRES
                                                <span class="text-danger">*</span>

                                            </label>

                                            <input type="text" name="first_name" id="first_name"
                                                class="form-control form-control-sm">

                                        </div>

                                        <div class="form-group col-md-6">

                                            <label class="small font-weight-bold text-secondary">

                                                APELLIDOS
                                                <span class="text-danger">*</span>

                                            </label>

                                            <input type="text" name="last_name" id="last_name"
                                                class="form-control form-control-sm">

                                        </div>

                                    </div>

                                </div>

                                <!-- PERSONA JURIDICA -->
                                <div id="businessFields" class="d-none">

                                    <div class="form-row">

                                        <div class="form-group col-md-12">

                                            <label class="small font-weight-bold text-secondary">

                                                RAZÓN SOCIAL
                                                <span class="text-danger">*</span>

                                            </label>

                                            <input type="text" name="business_name" id="business_name"
                                                class="form-control form-control-sm">

                                        </div>

                                    </div>

                                </div>

                                <!-- FILA 3 -->
                                <div class="form-row">

                                    <!-- TELEFONO -->
                                    <div class="form-group col-md-4">

                                        <label class="small font-weight-bold text-secondary">

                                            TELÉFONO

                                        </label>

                                        <input type="text" name="phone" id="phone"
                                            class="form-control form-control-sm">

                                    </div>

                                    <!-- EMAIL -->
                                    <div class="form-group col-md-4">

                                        <label class="small font-weight-bold text-secondary">

                                            EMAIL

                                        </label>

                                        <input type="email" name="email" id="email"
                                            class="form-control form-control-sm">

                                    </div>

                                    <!-- DIRECCION -->
                                    <div class="form-group col-md-4">

                                        <label class="small font-weight-bold text-secondary">

                                            DIRECCIÓN

                                        </label>

                                        <input type="text" name="address" id="address"
                                            class="form-control form-control-sm">

                                    </div>

                                </div>

                                <!-- FILA 4 -->
                                <div class="form-row">

                                    <div class="form-group col-md-6">

                                        <label class="small font-weight-bold text-secondary">

                                            ESTADO

                                        </label>

                                        <select id="status" name="status" class="form-control form-control-sm">

                                            <option value="1" selected>
                                                Activo
                                            </option>

                                            <option value="0">
                                                Inactivo
                                            </option>

                                        </select>

                                    </div>

                                </div>

                                <!-- NOTA -->
                                <div class="form-row">

                                    <div class="col-12">

                                        <small class="text-muted">

                                            La información registrada aquí será utilizada
                                            para ventas, contratos y seguimiento de clientes.

                                        </small>

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

                                        <button type="submit" class="btn btn-primary" id="btnSaveCustomer">

                                            <i class="fas fa-save mr-1"></i>
                                            Guardar Cliente

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
