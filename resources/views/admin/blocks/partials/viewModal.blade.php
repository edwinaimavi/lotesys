<!-- VIEW BLOCK MODAL -->
<div class="modal fade" id="viewBlockModal" tabindex="-1" role="dialog" aria-labelledby="viewBlockModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

        <div class="modal-content border-0 shadow-lg rounded">

            <!-- HEADER -->
            <div class="modal-header"
                style="background: linear-gradient(135deg,#f7f7f7,#ececec); border-bottom:1px solid #e0e0e0;">

                <h5 class="modal-title" id="viewBlockModalLabel">

                    <i class="fas fa-eye text-primary mr-2"></i>
                    Información de la Manzana

                </h5>

                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">

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

                                    <i class="fas fa-th-large"></i>

                                </div>

                            </div>

                            <h5 id="vb_name" class="font-weight-bold text-dark mb-1">

                                Nombre de la Manzana

                            </h5>

                            <p id="vb_project" class="text-muted mb-1">

                                Proyecto

                            </p>

                            <div class="mt-3">

                                <span id="vb_status" class="badge badge-success py-2 px-3">

                                    Activo

                                </span>

                            </div>

                            <div class="mt-4 text-left w-100">

                                <small class="text-muted">
                                    Registrado por
                                </small>

                                <div id="vb_created_by" class="font-weight-600">

                                    Usuario

                                </div>

                                <small class="text-muted mt-3 d-block">
                                    Última actualización
                                </small>

                                <div id="vb_updated_at" class="font-weight-600">

                                    —

                                </div>

                            </div>

                        </div>

                        <!-- PANEL DERECHO -->
                        <div class="col-md-8">

                            <!-- INFORMACIÓN GENERAL -->
                            <div class="row mb-3">

                                <div class="col-12">

                                    <h6 class="text-secondary mb-2">
                                        Información General
                                    </h6>

                                    <div class="d-flex flex-wrap">

                                        <div class="mr-5 mb-2">

                                            <small class="text-muted">
                                                Proyecto
                                            </small>

                                            <div id="vb_project_name" class="font-weight-600">

                                                —

                                            </div>

                                        </div>

                                        <div class="mr-5 mb-2">

                                            <small class="text-muted">
                                                Estado
                                            </small>

                                            <div id="vb_status_text" class="font-weight-600">

                                                —

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <hr class="my-3">

                            <!-- DESCRIPCIÓN -->
                            <div class="row">

                                <div class="col-12">

                                    <h6 class="text-secondary mb-2">
                                        Descripción
                                    </h6>

                                    <div class="p-3 rounded bg-light border">

                                        <span id="vb_description" class="font-weight-600">

                                            —

                                        </span>

                                    </div>

                                </div>

                            </div>

                            <hr class="my-3">

                            <!-- INFORMACIÓN LOTES -->
                            <div class="row">

                                <div class="col-12">

                                    <h6 class="text-secondary mb-3">
                                        Información de Lotes
                                    </h6>

                                    <div class="row">

                                        <div class="col-md-6">

                                            <div class="p-3 rounded border bg-light">

                                                <small class="text-muted d-block mb-1">
                                                    Total de Lotes
                                                </small>

                                                <h4 id="vb_lots_count" class="font-weight-bold text-primary mb-0">

                                                    0

                                                </h4>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <hr class="my-3">

                            <!-- INFORMACIÓN SISTEMA -->
                            <div class="row">

                                <div class="col-12">

                                    <h6 class="text-secondary mb-2">
                                        Información del Sistema
                                    </h6>

                                    <div class="table-responsive">

                                        <table class="table table-sm table-borderless mb-0">

                                            <tr>

                                                <td width="180">
                                                    <strong>ID Manzana:</strong>
                                                </td>

                                                <td id="vb_id">
                                                    —
                                                </td>

                                            </tr>

                                            <tr>

                                                <td>
                                                    <strong>Fecha de Registro:</strong>
                                                </td>

                                                <td id="vb_created_at">
                                                    —
                                                </td>

                                            </tr>

                                            <tr>

                                                <td>
                                                    <strong>Usuario creador:</strong>
                                                </td>

                                                <td id="vb_created_by_user">
                                                    —
                                                </td>

                                            </tr>

                                            <tr>

                                                <td>
                                                    <strong>Última edición:</strong>
                                                </td>

                                                <td id="vb_updated_by_user">
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
