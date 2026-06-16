<!-- VIEW AMORTIZATION MODAL -->
<div class="modal fade" id="viewAmortizationModal" tabindex="-1" role="dialog" aria-labelledby="viewAmortizationModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

        <div class="modal-content border-0 shadow-lg rounded-xl overflow-hidden">

            <!-- HEADER -->
            <div class="modal-header bg-white border-0 py-4 px-4">

                <h4 class="modal-title font-weight-bold text-dark" id="viewAmortizationModalLabel">

                    <i class="fas fa-eye text-primary mr-2"></i>

                    Información de la Amortización

                </h4>

                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Cerrar">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <!-- BODY -->
            <div class="modal-body pt-0">

                <div class="row">

                    <!-- PANEL IZQUIERDO -->
                    <div class="col-md-4 border-right text-center py-4">

                        <div class="mb-4">

                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center shadow"
                                style="
                                    width:180px;
                                    height:180px;
                                    background:
                                    linear-gradient(
                                        135deg,
                                        #007bff,
                                        #0056b3
                                    );
                                    color:white;
                                    font-size:70px;
                                ">

                                <i class="fas fa-chart-line"></i>

                            </div>

                        </div>

                        <h3 id="va_tipo_recalculo" class="font-weight-bold text-dark mb-2">

                            Amortización

                        </h3>

                        <p id="va_venta" class="text-muted h5 mb-3">

                            Venta

                        </p>

                        <div class="mb-4">

                            <span class="badge badge-primary px-4 py-2 shadow-sm">

                                Recalculo Financiero

                            </span>

                        </div>

                        <hr>

                        <div class="text-left px-3">

                            <small class="text-muted d-block">

                                Registrado por

                            </small>

                            <div id="va_created_by" class="font-weight-bold mb-3">

                                Usuario

                            </div>

                            <small class="text-muted d-block">

                                Última actualización

                            </small>

                            <div id="va_updated_at" class="font-weight-bold">

                                —

                            </div>

                        </div>

                    </div>

                    <!-- PANEL DERECHO -->
                    <div class="col-md-8 py-4">

                        <!-- INFORMACIÓN GENERAL -->
                        <div class="mb-4">

                            <h5 class="text-secondary mb-4">

                                Información General

                            </h5>

                            <div class="row">

                                <div class="col-md-4 mb-4">

                                    <small class="text-muted">

                                        Venta

                                    </small>

                                    <div id="va_sale" class="font-weight-bold text-dark h6">

                                        —

                                    </div>

                                </div>

                                <div class="col-md-4 mb-4">

                                    <small class="text-muted">

                                        Pago Relacionado

                                    </small>

                                    <div id="va_payment" class="font-weight-bold text-dark h6">

                                        —

                                    </div>

                                </div>

                                <div class="col-md-4 mb-4">

                                    <small class="text-muted">

                                        Fecha

                                    </small>

                                    <div id="va_date" class="font-weight-bold text-dark h6">

                                        —

                                    </div>

                                </div>

                            </div>

                        </div>

                        <hr>

                        <!-- INFORMACIÓN FINANCIERA -->
                        <div class="mb-4">

                            <h5 class="text-secondary mb-4">

                                Información Financiera

                            </h5>

                            <div class="row">

                                <div class="col-md-4 mb-3">

                                    <div class="p-4 rounded-lg border shadow-sm bg-light">

                                        <small class="text-muted d-block mb-2">

                                            Monto Amortizado

                                        </small>

                                        <h4 id="va_amount" class="font-weight-bold text-primary mb-0">

                                            S/ 0.00

                                        </h4>

                                    </div>

                                </div>

                                <div class="col-md-4 mb-3">

                                    <div class="p-4 rounded-lg border shadow-sm bg-light">

                                        <small class="text-muted d-block mb-2">

                                            Nueva Cuota

                                        </small>

                                        <h4 id="va_new_installment" class="font-weight-bold text-success mb-0">

                                            S/ 0.00

                                        </h4>

                                    </div>

                                </div>

                                <div class="col-md-4 mb-3">

                                    <div class="p-4 rounded-lg border shadow-sm bg-light">

                                        <small class="text-muted d-block mb-2">

                                            Cuotas Reducidas

                                        </small>

                                        <h4 id="va_reduced_installments" class="font-weight-bold text-danger mb-0">

                                            0

                                        </h4>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <hr>

                        <!-- DETALLE DEL RECÁLCULO -->
                        <div class="mb-4">

                            <h5 class="text-secondary mb-4">

                                Detalle del Recalculo

                            </h5>

                            <div class="table-responsive">

                                <table class="table table-bordered">

                                    <tbody>

                                        <tr>

                                            <th width="250">

                                                Tipo Recalculo

                                            </th>

                                            <td id="va_recalculation_type">

                                                —

                                            </td>

                                        </tr>

                                        <tr>

                                            <th>

                                                Monto Aplicado

                                            </th>

                                            <td id="va_amount_text">

                                                —

                                            </td>

                                        </tr>

                                        <tr>

                                            <th>

                                                Nueva Cuota Resultante

                                            </th>

                                            <td id="va_new_installment_text">

                                                —

                                            </td>

                                        </tr>

                                        <tr>

                                            <th>

                                                Cuotas Reducidas

                                            </th>

                                            <td id="va_reduced_installments_text">

                                                —

                                            </td>

                                        </tr>

                                        <tr>

                                            <th>

                                                Observación

                                            </th>

                                            <td id="va_observation">

                                                —

                                            </td>

                                        </tr>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                        <hr>

                        <!-- INFORMACIÓN DEL SISTEMA -->
                        <div>

                            <h5 class="text-secondary mb-3">

                                Información del Sistema

                            </h5>

                            <div class="table-responsive">

                                <table class="table table-borderless table-sm">

                                    <tbody>

                                        <tr>

                                            <td width="220">

                                                <strong>ID Amortización:</strong>

                                            </td>

                                            <td id="va_id">

                                                —

                                            </td>

                                        </tr>

                                        <tr>

                                            <td>

                                                <strong>Fecha de Registro:</strong>

                                            </td>

                                            <td id="va_created_at">

                                                —

                                            </td>

                                        </tr>

                                        <tr>

                                            <td>

                                                <strong>Usuario creador:</strong>

                                            </td>

                                            <td id="va_created_by_user">

                                                —

                                            </td>

                                        </tr>

                                        <tr>

                                            <td>

                                                <strong>Última edición:</strong>

                                            </td>

                                            <td id="va_updated_by_user">

                                                —

                                            </td>

                                        </tr>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>
                    <!-- /COL RIGHT -->

                </div>

            </div>

        </div>

    </div>

</div>
