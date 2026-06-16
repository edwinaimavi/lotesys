<!-- VIEW COMPANY MODAL -->
<div class="modal fade"
     id="viewCompanyModal"
     tabindex="-1"
     role="dialog"
     aria-labelledby="viewCompanyModalLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered"
         role="document">

        <div class="modal-content border-0 shadow-lg rounded">

            <!-- HEADER -->
            <div class="modal-header"
                 style="background: linear-gradient(135deg,#f7f7f7,#ececec); border-bottom:1px solid #e0e0e0;">

                <h5 class="modal-title"
                    id="viewCompanyModalLabel">

                    <i class="fas fa-eye text-primary mr-2"></i>
                    Información de la Empresa

                </h5>

                <button type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Cerrar">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <!-- BODY -->
            <div class="modal-body bg-white">

                <div class="container-fluid">

                    <div class="row">

                        <!-- PANEL IZQUIERDO -->
                        <div class="col-md-4 text-center border-right">

                            <div class="mb-3">

                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                                     style="
                                        width:180px;
                                        height:180px;
                                        background:linear-gradient(135deg,#007bff,#0056b3);
                                        color:white;
                                        font-size:70px;
                                        box-shadow:0 8px 20px rgba(0,0,0,.1);
                                     ">

                                    <i class="fas fa-building"></i>

                                </div>

                            </div>

                            <h5 id="vc_business_name"
                                class="font-weight-bold text-dark mb-1">

                                Razón Social

                            </h5>

                            <p id="vc_trade_name"
                               class="text-muted mb-1">

                                Nombre Comercial

                            </p>

                            <div class="mt-3">

                                <span id="vc_status"
                                      class="badge badge-success py-2 px-3">

                                    Activo

                                </span>

                            </div>

                            <div class="mt-4 text-left w-100">

                                <small class="text-muted">
                                    Registrado por
                                </small>

                                <div id="vc_created_by"
                                     class="font-weight-600">

                                    Usuario

                                </div>

                                <small class="text-muted mt-3 d-block">
                                    Última actualización
                                </small>

                                <div id="vc_updated_at"
                                     class="font-weight-600">

                                    —

                                </div>

                            </div>

                        </div>

                        <!-- PANEL DERECHO -->
                        <div class="col-md-8">

                            <!-- CONTACTO -->
                            <div class="row mb-3">

                                <div class="col-12">

                                    <h6 class="text-secondary mb-2">
                                        Información de Contacto
                                    </h6>

                                    <div class="d-flex flex-wrap">

                                        <div class="mr-5 mb-2">

                                            <small class="text-muted">
                                                Teléfono
                                            </small>

                                            <div id="vc_phone"
                                                 class="font-weight-600">

                                                —

                                            </div>

                                        </div>

                                        <div class="mr-5 mb-2">

                                            <small class="text-muted">
                                                Correo Electrónico
                                            </small>

                                            <div id="vc_email"
                                                 class="font-weight-600">

                                                —

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <hr class="my-3">

                            <!-- INFORMACIÓN EMPRESA -->
                            <div class="row">

                                <div class="col-sm-6">

                                    <div class="mb-3">

                                        <small class="text-muted">
                                            RUC
                                        </small>

                                        <div id="vc_ruc"
                                             class="font-weight-600">

                                            —

                                        </div>

                                    </div>

                                    <div class="mb-3">

                                        <small class="text-muted">
                                            Estado
                                        </small>

                                        <div id="vc_status_text"
                                             class="font-weight-600">

                                            —

                                        </div>

                                    </div>

                                </div>

                                <div class="col-sm-6">

                                    <div class="mb-3">

                                        <small class="text-muted">
                                            Fecha de Registro
                                        </small>

                                        <div id="vc_created_at"
                                             class="font-weight-600">

                                            —

                                        </div>

                                    </div>

                                    <div class="mb-3">

                                        <small class="text-muted">
                                            ID Empresa
                                        </small>

                                        <div id="vc_id"
                                             class="font-weight-600">

                                            —

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <hr class="my-3">

                            <!-- DIRECCIÓN -->
                            <div class="row">

                                <div class="col-12">

                                    <h6 class="text-secondary mb-2">
                                        Dirección
                                    </h6>

                                    <div class="p-3 rounded bg-light border">

                                        <i class="fas fa-map-marker-alt text-danger mr-2"></i>

                                        <span id="vc_address"
                                              class="font-weight-600">

                                            —

                                        </span>

                                    </div>

                                </div>

                            </div>

                            <hr class="my-3">

                            <!-- INFORMACIÓN EXTRA -->
                            <div class="row">

                                <div class="col-12">

                                    <h6 class="text-secondary mb-2">
                                        Información del Sistema
                                    </h6>

                                    <div class="table-responsive">

                                        <table class="table table-sm table-borderless mb-0">

                                            <tr>
                                                <td width="180">
                                                    <strong>Usuario creador:</strong>
                                                </td>

                                                <td id="vc_creator_user">
                                                    —
                                                </td>
                                            </tr>

                                            <tr>
                                                <td>
                                                    <strong>Última edición:</strong>
                                                </td>

                                                <td id="vc_updater_user">
                                                    —
                                                </td>
                                            </tr>

                                         

                                        </table>

                                    </div>

                                </div>

                            </div>

                        </div>
                        <!-- /col-md-8 -->

                    </div>
                    <!-- /row -->

                </div>
                <!-- /container-fluid -->

            </div>
            <!-- /modal-body -->

        </div>

    </div>

</div>