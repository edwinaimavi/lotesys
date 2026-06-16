<!-- VIEW SALE MODAL -->
<div class="modal fade" id="viewSaleModal" tabindex="-1" role="dialog" aria-labelledby="viewSaleModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

        <div class="modal-content border-0 shadow-lg rounded-xl overflow-hidden">

            <!-- HEADER -->
            <div class="modal-header bg-white border-0 py-4 px-4">

                <h4 class="modal-title font-weight-bold text-dark" id="viewSaleModalLabel">

                    <i class="fas fa-eye text-primary mr-2"></i>
                    Información de la Venta

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
                                    background:linear-gradient(135deg,#28a745,#1e7e34);
                                    color:white;
                                    font-size:70px;
                                ">

                                <i class="fas fa-file-signature"></i>

                            </div>

                        </div>

                        <h3 id="vs_codigo_venta" class="font-weight-bold text-dark mb-2">

                            VTA00001

                        </h3>

                        <p id="vs_cliente" class="text-muted h5 mb-3">

                            Cliente

                        </p>

                        <div class="mb-4">

                            <span id="vs_estado_badge" class="badge badge-success px-4 py-2 shadow-sm">

                                Activo

                            </span>

                        </div>

                        <hr>

                        <div class="text-left px-3">

                            <small class="text-muted d-block">
                                Registrado por
                            </small>

                            <div id="vs_created_by" class="font-weight-bold mb-3">

                                Usuario

                            </div>

                            <small class="text-muted d-block">
                                Última actualización
                            </small>

                            <div id="vs_updated_at" class="font-weight-bold">

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
                                        Cliente
                                    </small>

                                    <div id="vs_cliente_nombre" class="font-weight-bold text-dark h6">

                                        —

                                    </div>

                                </div>

                                <div class="col-md-4 mb-4">

                                    <small class="text-muted">
                                        Lote
                                    </small>

                                    <div id="vs_lote" class="font-weight-bold text-dark h6">

                                        —

                                    </div>

                                </div>

                                <div class="col-md-4 mb-4">

                                    <small class="text-muted">
                                        Fecha Venta
                                    </small>

                                    <div id="vs_fecha_venta" class="font-weight-bold text-dark h6">

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
                                            Precio Lote
                                        </small>

                                        <h4 id="vs_precio_lote" class="font-weight-bold text-primary mb-0">

                                            S/ 0.00

                                        </h4>

                                    </div>

                                </div>

                                <div class="col-md-4 mb-3">

                                    <div class="p-4 rounded-lg border shadow-sm bg-light">

                                        <small class="text-muted d-block mb-2">
                                            Inicial
                                        </small>

                                        <h4 id="vs_inicial" class="font-weight-bold text-success mb-0">

                                            S/ 0.00

                                        </h4>

                                    </div>

                                </div>

                                <div class="col-md-4 mb-3">

                                    <div class="p-4 rounded-lg border shadow-sm bg-light">

                                        <small class="text-muted d-block mb-2">
                                            Saldo Financiar
                                        </small>

                                        <h4 id="vs_saldo_financiar" class="font-weight-bold text-danger mb-0">

                                            S/ 0.00

                                        </h4>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <hr>

                        <!-- FINANCIAMIENTO -->
                        <div class="mb-4">

                            <h5 class="text-secondary mb-4">
                                Información de Financiamiento
                            </h5>

                            <div class="table-responsive">

                                <table class="table table-bordered">

                                    <tbody>

                                        <tr>

                                            <th width="250">
                                                Cantidad de Cuotas
                                            </th>

                                            <td id="vs_cantidad_cuotas">
                                                —
                                            </td>

                                        </tr>

                                        <tr>

                                            <th>
                                                Cuota Mensual
                                            </th>

                                            <td id="vs_cuota_mensual">
                                                —
                                            </td>

                                        </tr>

                                        <tr>

                                            <th>
                                                Tasa de Interés
                                            </th>

                                            <td id="vs_tasa_interes">
                                                —
                                            </td>

                                        </tr>

                                        <tr>

                                            <th>
                                                Fecha Primer Pago
                                            </th>

                                            <td id="vs_fecha_primer_pago">
                                                —
                                            </td>

                                        </tr>

                                        <tr>

                                            <th>
                                                Día de Pago
                                            </th>

                                            <td id="vs_dia_pago">
                                                —
                                            </td>

                                        </tr>

                                        <tr>

                                            <th>
                                                Estado
                                            </th>

                                            <td id="vs_estado_text">
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
                                                <strong>ID Venta:</strong>
                                            </td>

                                            <td id="vs_id">
                                                —
                                            </td>

                                        </tr>

                                        <tr>

                                            <td>
                                                <strong>Fecha de Registro:</strong>
                                            </td>

                                            <td id="vs_created_at">
                                                —
                                            </td>

                                        </tr>

                                        <tr>

                                            <td>
                                                <strong>Usuario creador:</strong>
                                            </td>

                                            <td id="vs_created_by_user">
                                                —
                                            </td>

                                        </tr>

                                        <tr>

                                            <td>
                                                <strong>Última edición:</strong>
                                            </td>

                                            <td id="vs_updated_by_user">
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
