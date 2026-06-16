<!-- VIEW CUSTOMER MODAL -->
<div class="modal fade" id="viewCustomerModal" tabindex="-1" role="dialog" aria-labelledby="viewCustomerModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

        <div class="modal-content border-0 shadow-lg rounded">

            <!-- HEADER -->
            <div class="modal-header"
                style="background: linear-gradient(135deg,#f7f7f7,#ececec); border-bottom:1px solid #e0e0e0;">

                <h5 class="modal-title" id="viewCustomerModalLabel">

                    <i class="fas fa-eye text-primary mr-2"></i>
                    Información del Cliente

                </h5>

                <button type="button" class="close" data-dismiss="modal">

                    <span>&times;</span>

                </button>

            </div>

            <!-- BODY -->
            <div class="modal-body bg-white">

                <div class="container-fluid">

                    <div class="row">

                        <!-- LEFT -->
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

                                    <i class="fas fa-user"></i>

                                </div>

                            </div>

                            <h4 id="vc_full_name" class="font-weight-bold text-dark mb-1">

                                Cliente

                            </h4>

                            <p id="vc_document" class="text-muted mb-1">

                                Documento

                            </p>

                            <div class="mt-3">

                                <span id="vc_status_badge" class="badge badge-success py-2 px-3">

                                    Activo

                                </span>

                            </div>

                            <div class="mt-4 text-left w-100">

                                <small class="text-muted">
                                    Registrado por
                                </small>

                                <div id="vc_created_by_user" class="font-weight-600">

                                    Usuario

                                </div>

                                <small class="text-muted mt-3 d-block">
                                    Última actualización
                                </small>

                                <div id="vc_updated_at">

                                    —

                                </div>

                            </div>

                        </div>

                        <!-- RIGHT -->
                        <div class="col-md-8">

                            <!-- GENERAL -->
                            <div class="row mb-3">

                                <div class="col-12">

                                    <h6 class="text-secondary mb-3">
                                        Información General
                                    </h6>

                                    <div class="row">

                                        <div class="col-md-6 mb-3">

                                            <small class="text-muted">
                                                Tipo Persona
                                            </small>

                                            <div id="vc_person_type" class="font-weight-600">
                                                —
                                            </div>

                                        </div>

                                        <div class="col-md-6 mb-3">

                                            <small class="text-muted">
                                                Tipo Documento
                                            </small>

                                            <div id="vc_document_type" class="font-weight-600">
                                                —
                                            </div>

                                        </div>

                                        <div class="col-md-6 mb-3">

                                            <small class="text-muted">
                                                Número Documento
                                            </small>

                                            <div id="vc_document_number" class="font-weight-600">
                                                —
                                            </div>

                                        </div>

                                        <div class="col-md-6 mb-3">

                                            <small class="text-muted">
                                                Teléfono
                                            </small>

                                            <div id="vc_phone" class="font-weight-600">
                                                —
                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <hr>

                            <!-- CONTACTO -->
                            <div class="row">

                                <div class="col-12">

                                    <h6 class="text-secondary mb-3">
                                        Información de Contacto
                                    </h6>

                                    <div class="mb-3">

                                        <small class="text-muted">
                                            Correo Electrónico
                                        </small>

                                        <div id="vc_email" class="font-weight-600">
                                            —
                                        </div>

                                    </div>

                                    <div>

                                        <small class="text-muted">
                                            Dirección
                                        </small>

                                        <div class="p-3 rounded bg-light border">

                                            <span id="vc_address">

                                                —

                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <hr>

                            <!-- SISTEMA -->
                            <div class="row">

                                <div class="col-12">

                                    <h6 class="text-secondary mb-2">
                                        Información del Sistema
                                    </h6>

                                    <table class="table table-sm table-borderless">

                                        <tr>

                                            <td width="180">
                                                <strong>ID Cliente:</strong>
                                            </td>

                                            <td id="vc_id">—</td>

                                        </tr>

                                        <tr>

                                            <td>
                                                <strong>Fecha Registro:</strong>
                                            </td>

                                            <td id="vc_created_at">—</td>

                                        </tr>

                                        <tr>

                                            <td>
                                                <strong>Usuario creador:</strong>
                                            </td>

                                            <td id="vc_created_by">—</td>

                                        </tr>

                                        <tr>

                                            <td>
                                                <strong>Última edición:</strong>
                                            </td>

                                            <td id="vc_updated_by">—</td>

                                        </tr>

                                    </table>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
