<!-- VIEW PROJECT MODAL -->
<div class="modal fade" id="viewProjectModal" tabindex="-1" role="dialog" aria-labelledby="viewProjectModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

        <div class="modal-content border-0 shadow-lg rounded">

            <!-- HEADER -->
            <div class="modal-header"
                style="background: linear-gradient(135deg,#f7f7f7,#ececec); border-bottom:1px solid #e0e0e0;">

                <h5 class="modal-title" id="viewProjectModalLabel">

                    <i class="fas fa-eye text-primary mr-2"></i>
                    Información del Proyecto

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

                                    <i class="fas fa-map-marked-alt"></i>

                                </div>

                            </div>

                            <h5 id="vp_name" class="font-weight-bold text-dark mb-1">

                                Nombre del Proyecto

                            </h5>

                            <p id="vp_company" class="text-muted mb-1">

                                Empresa

                            </p>

                            <div class="mt-3">

                                <span id="vp_status" class="badge badge-success py-2 px-3">

                                    Activo

                                </span>

                            </div>

                            <div class="mt-4 text-left w-100">

                                <small class="text-muted">
                                    Registrado por
                                </small>

                                <div id="vp_created_by" class="font-weight-600">

                                    Usuario

                                </div>

                                <small class="text-muted mt-3 d-block">
                                    Última actualización
                                </small>

                                <div id="vp_updated_at" class="font-weight-600">

                                    —

                                </div>

                            </div>

                        </div>

                        <!-- PANEL DERECHO -->
                        <div class="col-md-8">

                            <!-- INFORMACIÓN GENERAL -->
                            <!-- INFORMACIÓN GENERAL -->
                            <div class="row mb-3">

                                <div class="col-12">

                                    <h6 class="text-secondary mb-3">
                                        Información General
                                    </h6>

                                    <div class="row">

                                        <!-- CÓDIGO -->
                                        <div class="col-md-3 mb-3">

                                            <small class="text-muted">
                                                Código
                                            </small>

                                            <div id="vp_code" class="font-weight-600">

                                                —

                                            </div>

                                        </div>

                                        <!-- FECHA INICIO -->
                                        <div class="col-md-3 mb-3">

                                            <small class="text-muted">
                                                Fecha de Inicio
                                            </small>

                                            <div id="vp_start_date" class="font-weight-600">

                                                —

                                            </div>

                                        </div>

                                        <!-- ÁREA TOTAL -->
                                        <div class="col-md-3 mb-3">

                                            <small class="text-muted">
                                                Área Total
                                            </small>

                                            <div id="vp_total_area" class="font-weight-600 text-primary">

                                                —

                                            </div>

                                        </div>

                                        <!-- PARTIDA -->
                                        <div class="col-md-3 mb-3">

                                            <small class="text-muted">
                                                N° Partida
                                            </small>

                                            <div id="vp_registry_number" class="font-weight-600 text-success">

                                                —

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <hr class="my-3">

                            <!-- INFORMACIÓN UBICACIÓN -->
                            <div class="row">

                                <div class="col-sm-4">

                                    <div class="mb-3">

                                        <small class="text-muted">
                                            Distrito
                                        </small>

                                        <div id="vp_district" class="font-weight-600">

                                            —

                                        </div>

                                    </div>

                                </div>

                                <div class="col-sm-4">

                                    <div class="mb-3">

                                        <small class="text-muted">
                                            Provincia
                                        </small>

                                        <div id="vp_province" class="font-weight-600">

                                            —

                                        </div>

                                    </div>

                                </div>

                                <div class="col-sm-4">

                                    <div class="mb-3">

                                        <small class="text-muted">
                                            Departamento
                                        </small>

                                        <div id="vp_department" class="font-weight-600">

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
                                        Dirección del Proyecto
                                    </h6>

                                    <div class="p-3 rounded bg-light border">

                                        <i class="fas fa-map-marker-alt text-danger mr-2"></i>

                                        <span id="vp_address" class="font-weight-600">

                                            —

                                        </span>

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

                                        <span id="vp_description" class="font-weight-600">

                                            —

                                        </span>

                                    </div>

                                </div>

                            </div>


                            <hr class="my-3">

                            <!-- INFORMACIÓN INMOBILIARIA -->
                            <div class="row">

                                <div class="col-12">

                                    <h6 class="text-secondary mb-3">
                                        Información Inmobiliaria
                                    </h6>

                                    <div class="row">

                                        <!-- MANZANAS -->
                                        <div class="col-md-6 mb-3">

                                            <div class="p-3 rounded border bg-light h-100">

                                                <small class="text-muted d-block mb-1">
                                                    Total de Manzanas
                                                </small>

                                                <h4 id="vp_blocks_count" class="font-weight-bold text-primary mb-2">

                                                    0

                                                </h4>

                                                <small class="text-muted d-block mb-1">
                                                    Manzanas Registradas
                                                </small>

                                                <div id="vp_blocks" class="font-weight-600">

                                                    —

                                                </div>

                                            </div>

                                        </div>

                                        <!-- LOTES -->
                                        <div class="col-md-6 mb-3">

                                            <div class="p-3 rounded border bg-light h-100">

                                                <small class="text-muted d-block mb-1">
                                                    Total de Lotes
                                                </small>

                                                <h4 id="vp_lots_count" class="font-weight-bold text-success">

                                                    0

                                                </h4>

                                                <small class="text-muted">
                                                    Lotes registrados en el proyecto
                                                </small>

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
                                                    <strong>ID Proyecto:</strong>
                                                </td>

                                                <td id="vp_id">
                                                    —
                                                </td>

                                            </tr>

                                            <tr>

                                                <td>
                                                    <strong>Fecha de Registro:</strong>
                                                </td>

                                                <td id="vp_created_at">
                                                    —
                                                </td>

                                            </tr>

                                            <tr>

                                                <td>
                                                    <strong>Usuario creador:</strong>
                                                </td>

                                                <td id="vp_created_by_user">
                                                    —
                                                </td>

                                            </tr>

                                            <tr>

                                                <td>
                                                    <strong>Última edición:</strong>
                                                </td>

                                                <td id="vp_updated_by_user">
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
