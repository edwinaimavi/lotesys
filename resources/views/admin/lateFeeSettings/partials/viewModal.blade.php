<!-- VIEW LATE FEE SETTING MODAL -->
<div class="modal fade" id="viewLateFeeSettingModal" tabindex="-1" role="dialog"
    aria-labelledby="viewLateFeeSettingModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">

        <div class="modal-content border-0 shadow overflow-hidden" style="border-radius:18px;">

            <!-- HEADER -->
            <div class="modal-header border-0 py-3 px-4"
                style="
                    background:linear-gradient(135deg,#0f172a,#1e3a8a);
                ">

                <h5 class="modal-title text-white font-weight-bold mb-0" id="viewLateFeeSettingModalLabel">

                    <i class="fas fa-percent mr-2"></i>
                    Configuración de Mora

                </h5>

                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"
                    style="opacity:1;">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <!-- BODY -->
            <div class="modal-body p-4">

                <div class="row">

                    <!-- LEFT -->
                    <div class="col-md-4">

                        <div class="text-center">

                            <div class="mx-auto mb-3 d-flex align-items-center justify-content-center shadow"
                                style="
                                    width:95px;
                                    height:95px;
                                    border-radius:22px;
                                    background:linear-gradient(135deg,#dc2626,#991b1b);
                                    color:white;
                                    font-size:38px;
                                ">

                                <i class="fas fa-file-invoice-dollar"></i>

                            </div>

                            <h4 class="font-weight-bold text-dark mb-1">

                                Configuración Mora

                            </h4>

                            <div class="text-muted mb-3" style="font-size:14px;">

                                Penalidades automáticas

                            </div>

                            <span id="vls_status" class="badge badge-success px-3 py-2 shadow-sm"
                                style="
                                    border-radius:10px;
                                    font-size:12px;
                                ">

                                ACTIVO

                            </span>

                        </div>

                        <!-- INFO USER -->
                        <div class="mt-4">

                            <div class="card border-0 shadow-sm">

                                <div class="card-body py-3 px-3">

                                    <small class="text-muted d-block mb-1">
                                        Registrado por
                                    </small>

                                    <div id="vls_created_by" class="font-weight-bold text-dark mb-3"
                                        style="font-size:14px;">

                                        —
                                    </div>

                                    <small class="text-muted d-block mb-1">
                                        Última actualización
                                    </small>

                                    <div id="vls_updated_at" class="font-weight-bold text-dark" style="font-size:13px;">

                                        —
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- RIGHT -->
                    <div class="col-md-8">

                        <!-- CARDS -->
                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <div class="card border-0 shadow-sm h-100">

                                    <div class="card-body py-3">

                                        <small class="text-muted d-block mb-1" style="font-size:12px;">

                                            Días de Gracia

                                        </small>

                                        <div id="vls_grace_days" class="font-weight-bold text-dark"
                                            style="
                                                font-size:22px;
                                            ">

                                            —
                                        </div>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6 mb-3">

                                <div class="card border-0 shadow-sm h-100">

                                    <div class="card-body py-3">

                                        <small class="text-muted d-block mb-1" style="font-size:12px;">

                                            Mora Diaria

                                        </small>

                                        <div id="vls_daily_late_fee" class="font-weight-bold text-danger"
                                            style="
                                                font-size:20px;
                                            ">

                                            —
                                        </div>

                                    </div>

                                </div>

                            </div>

                            <div class="col-12 mb-3">

                                <div class="card border-0 shadow-sm">

                                    <div class="card-body py-3">

                                        <small class="text-muted d-block mb-1" style="font-size:12px;">

                                            Mora Máxima Permitida

                                        </small>

                                        <div id="vls_max_late_fee" class="font-weight-bold text-dark"
                                            style="font-size:18px;">

                                            —
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <!-- DETAIL -->
                        <div class="card border-0 shadow-sm mt-2">

                            <div class="card-header bg-white border-0 py-3">

                                <h6 class="mb-0 font-weight-bold text-dark">

                                    <i class="fas fa-cogs text-primary mr-1"></i>
                                    Configuración del Sistema

                                </h6>

                            </div>

                            <div class="table-responsive">

                                <table class="table table-sm mb-0">

                                    <tbody>

                                        <tr>

                                            <th width="220" class="border-top-0 text-muted py-3">

                                                Aplicar domingos

                                            </th>

                                            <td id="vls_apply_sundays"
                                                class="border-top-0 font-weight-bold text-dark py-3">

                                                —
                                            </td>

                                        </tr>

                                        <tr>

                                            <th class="text-muted py-3">

                                                Aplicar feriados

                                            </th>

                                            <td id="vls_apply_holidays" class="font-weight-bold text-dark py-3">

                                                —
                                            </td>

                                        </tr>

                                        <tr>

                                            <th class="text-muted py-3">

                                                Estado

                                            </th>

                                            <td id="vls_status_text" class="font-weight-bold text-dark py-3">

                                                —
                                            </td>

                                        </tr>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                        <!-- FOOT INFO -->
                        <div class="row mt-3">

                            <div class="col-md-4 mb-2">

                                <div class="card border-0 bg-light shadow-sm">

                                    <div class="card-body py-2 px-3">

                                        <small class="text-muted d-block" style="font-size:11px;">

                                            ID Configuración

                                        </small>

                                        <div id="vls_id" class="font-weight-bold text-dark" style="font-size:14px;">

                                            —
                                        </div>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-4 mb-2">

                                <div class="card border-0 bg-light shadow-sm">

                                    <div class="card-body py-2 px-3">

                                        <small class="text-muted d-block" style="font-size:11px;">

                                            Creado por

                                        </small>

                                        <div id="vls_created_by_user" class="font-weight-bold text-dark"
                                            style="font-size:14px;">

                                            —
                                        </div>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-4 mb-2">

                                <div class="card border-0 bg-light shadow-sm">

                                    <div class="card-body py-2 px-3">

                                        <small class="text-muted d-block" style="font-size:11px;">

                                            Última edición

                                        </small>

                                        <div id="vls_updated_by_user" class="font-weight-bold text-dark"
                                            style="font-size:14px;">

                                            —
                                        </div>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6 mb-2">

                                <div class="card border-0 bg-light shadow-sm">

                                    <div class="card-body py-2 px-3">

                                        <small class="text-muted d-block" style="font-size:11px;">

                                            Fecha Registro

                                        </small>

                                        <div id="vls_created_at" class="font-weight-bold text-dark"
                                            style="font-size:14px;">

                                            —
                                        </div>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6 mb-2">

                                <div class="card border-0 bg-light shadow-sm">

                                    <div class="card-body py-2 px-3">

                                        <small class="text-muted d-block" style="font-size:11px;">

                                            Última Actualización

                                        </small>

                                        <div id="vls_updated_at_footer" class="font-weight-bold text-dark"
                                            style="font-size:14px;">

                                            —
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>
                    <!-- END RIGHT -->

                </div>

            </div>

        </div>

    </div>

</div>
