<!-- VIEW LOT MODAL -->
<div class="modal fade" id="viewLotModal" tabindex="-1" role="dialog" aria-labelledby="viewLotModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

        <div class="modal-content border-0 shadow-lg rounded">

            <!-- HEADER -->
            <div class="modal-header"
                style="background: linear-gradient(135deg,#f7f7f7,#ececec); border-bottom:1px solid #e0e0e0;">

                <h5 class="modal-title" id="viewLotModalLabel">

                    <i class="fas fa-eye text-primary mr-2"></i>
                    Información del Lote

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

                                    <i class="fas fa-map"></i>

                                </div>

                            </div>

                            <h5 id="vl_code" class="font-weight-bold text-dark mb-1">

                                Código del Lote

                            </h5>

                            <p id="vl_project" class="text-muted mb-1">

                                Proyecto

                            </p>

                            <div class="mt-3">

                                <span id="vl_status" class="badge badge-success py-2 px-3">

                                    Disponible

                                </span>

                            </div>

                            <div class="mt-4 text-left w-100">

                                <small class="text-muted">
                                    Registrado por
                                </small>

                                <div id="vl_created_by" class="font-weight-600">

                                    Usuario

                                </div>

                                <small class="text-muted mt-3 d-block">
                                    Última actualización
                                </small>

                                <div id="vl_updated_at" class="font-weight-600">

                                    —

                                </div>

                            </div>

                        </div>

                        <!-- PANEL DERECHO -->
                        <div class="col-md-8">

                            <!-- INFORMACIÓN GENERAL -->
                            <div class="row mb-3">

                                <div class="col-12">

                                    <h6 class="text-secondary mb-3">
                                        Información General
                                    </h6>

                                    <div class="row">

                                        <div class="col-md-4 mb-3">

                                            <small class="text-muted">
                                                Proyecto
                                            </small>

                                            <div id="vl_project_name" class="font-weight-600">

                                                —

                                            </div>

                                        </div>

                                        <div class="col-md-4 mb-3">

                                            <small class="text-muted">
                                                Manzana
                                            </small>

                                            <div id="vl_block" class="font-weight-600">

                                                —

                                            </div>

                                        </div>

                                        <div class="col-md-4 mb-3">

                                            <small class="text-muted">
                                                Número de Lote
                                            </small>

                                            <div id="vl_number" class="font-weight-600">

                                                —

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <hr class="my-3">

                            <!-- ÁREA -->
                            <div class="row">

                                <div class="col-md-4">

                                    <div class="mb-3">

                                        <small class="text-muted">
                                            Área
                                        </small>

                                        <div id="vl_area" class="font-weight-bold text-dark">

                                            —

                                        </div>

                                    </div>

                                </div>

                                <div class="col-md-4">

                                    <div class="mb-3">

                                        <small class="text-muted">
                                            Unidad
                                        </small>

                                        <div id="vl_unit_measure" class="font-weight-bold text-dark">

                                            —

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <hr class="my-3">

                            <!-- PRECIOS -->
                            <div class="row">

                                <div class="col-12">

                                    <h6 class="text-secondary mb-3">
                                        Información Comercial
                                    </h6>

                                </div>

                                <div class="col-md-6">

                                    <div class="card border-0 shadow-sm mb-3">

                                        <div class="card-body">

                                            <small class="text-muted">
                                                Precio Contado
                                            </small>

                                            <h5 id="vl_cash_price" class="text-success font-weight-bold mb-0">

                                                S/ 0.00

                                            </h5>

                                        </div>

                                    </div>

                                </div>

                                <div class="col-md-6">

                                    <div class="card border-0 shadow-sm mb-3">

                                        <div class="card-body">

                                            <small class="text-muted">
                                                Precio Financiado
                                            </small>

                                            <h5 id="vl_financed_price" class="text-primary font-weight-bold mb-0">

                                                S/ 0.00

                                            </h5>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <hr class="my-3">

                            <!-- COLINDANTES -->
                            <div class="row">

                                <div class="col-12">

                                    <h6 class="text-secondary mb-3">
                                        Colindancias del Lote
                                    </h6>

                                </div>

                                <div class="col-md-6">

                                    <div class="border rounded p-3 mb-3 bg-light">

                                        <small class="text-muted">
                                            Norte
                                        </small>

                                        <div id="vl_north_boundary" class="font-weight-600">

                                            —

                                        </div>

                                    </div>

                                </div>

                                <div class="col-md-6">

                                    <div class="border rounded p-3 mb-3 bg-light">

                                        <small class="text-muted">
                                            Sur
                                        </small>

                                        <div id="vl_south_boundary" class="font-weight-600">

                                            —

                                        </div>

                                    </div>

                                </div>

                                <div class="col-md-6">

                                    <div class="border rounded p-3 mb-3 bg-light">

                                        <small class="text-muted">
                                            Este
                                        </small>

                                        <div id="vl_east_boundary" class="font-weight-600">

                                            —

                                        </div>

                                    </div>

                                </div>

                                <div class="col-md-6">

                                    <div class="border rounded p-3 mb-3 bg-light">

                                        <small class="text-muted">
                                            Oeste
                                        </small>

                                        <div id="vl_west_boundary" class="font-weight-600">

                                            —

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <hr class="my-3">

                            <!-- OBSERVACIÓN -->
                            <div class="row">

                                <div class="col-12">

                                    <h6 class="text-secondary mb-2">
                                        Observación
                                    </h6>

                                    <div class="p-3 rounded bg-light border">

                                        <span id="vl_observation" class="font-weight-600">

                                            —

                                        </span>

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
                                                    <strong>ID Lote:</strong>
                                                </td>

                                                <td id="vl_id">
                                                    —
                                                </td>

                                            </tr>

                                            <tr>

                                                <td>
                                                    <strong>Fecha de Registro:</strong>
                                                </td>

                                                <td id="vl_created_at">
                                                    —
                                                </td>

                                            </tr>

                                            <tr>

                                                <td>
                                                    <strong>Usuario creador:</strong>
                                                </td>

                                                <td id="vl_created_by_user">
                                                    —
                                                </td>

                                            </tr>

                                            <tr>

                                                <td>
                                                    <strong>Última edición:</strong>
                                                </td>

                                                <td id="vl_updated_by_user">
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
