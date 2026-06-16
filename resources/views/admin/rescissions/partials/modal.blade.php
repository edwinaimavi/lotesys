<div class="modal fade" id="rescissionModal" tabindex="-1" role="dialog" aria-labelledby="rescissionModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content border-0 shadow-lg rounded-xl">

            <div class="modal-header bg-danger text-white py-3">
                <div>
                    <h5 class="modal-title font-weight-bold mb-0" id="rescissionModalLabel">
                        <i class="fas fa-file-signature mr-1"></i>
                        Rescisión de Contrato
                    </h5>
                    <small class="d-block opacity-75">
                        Registrar la rescisión definitiva del contrato
                    </small>
                </div>

                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="formRescission" autocomplete="off">
                @csrf

                <div class="modal-body bg-light">

                    <input type="hidden" id="sale_id" name="sale_id">

                    <div class="row">

                        {{-- RESUMEN IZQUIERDO --}}
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 shadow-sm rounded-xl">
                                <div class="card-body">

                                    <div class="text-center mb-2">
                                        <div class="rounded-circle bg-danger d-inline-flex align-items-center justify-content-center"
                                            style="width: 90px; height: 90px;">
                                            <i class="fas fa-exclamation-triangle text-white fa-2x"></i>
                                        </div>
                                    </div>

                                    <h5 class="font-weight-bold text-dark text-center mb-2">
                                        Datos de la Venta
                                    </h5>

                                    <hr>

                                    <div class="mb-2">
                                        <small class="text-muted d-block">Código de venta</small>
                                        <strong id="txt_sale_code">-</strong>
                                    </div>

                                    <div class="mb-2">
                                        <small class="text-muted d-block">Cliente</small>
                                        <strong id="txt_customer_name">-</strong>
                                    </div>

                                    <div class="mb-2">
                                        <small class="text-muted d-block">Empresa</small>
                                        <strong id="txt_company_name">-</strong>
                                    </div>

                                    <div class="mb-2">
                                        <small class="text-muted d-block">Proyecto</small>
                                        <strong id="txt_project_name">-</strong>
                                    </div>

                                    <div class="mb-2">
                                        <small class="text-muted d-block">Lote</small>
                                        <strong id="txt_lot_name">-</strong>
                                    </div>

                                    <div class="mb-2">
                                        <small class="text-muted d-block">Fecha de venta</small>
                                        <strong id="txt_sale_date">-</strong>
                                    </div>

                                    <div class="mb-2">
                                        <small class="text-muted d-block">Cuotas vencidas</small>
                                        <strong id="txt_overdue_installments">-</strong>
                                    </div>

                                    <div class="mb-2">
                                        <small class="text-muted d-block">Monto pagado</small>
                                        <strong id="txt_amount_paid">S/ 0.00</strong>
                                    </div>

                                    <div class="mb-0">
                                        <small class="text-muted d-block">Saldo pendiente</small>
                                        <strong id="txt_balance_finance">S/ 0.00</strong>
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- FORMULARIO --}}
                        <div class="col-md-8">
                            <div class="card border-0 shadow-sm rounded-xl">
                                <div class="card-body">

                                    <div class="row">

                                        <div class="col-md-4 mb-3">
                                            <label class="font-weight-bold">
                                                Fecha de rescisión <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" id="rescission_date" name="rescission_date"
                                                class="form-control" value="{{ now()->format('Y-m-d') }}">
                                            <small class="text-danger error-text" id="error_rescission_date"></small>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="font-weight-bold">
                                                Cuotas vencidas <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" id="overdue_installments" name="overdue_installments"
                                                class="form-control" min="0" step="1" value="0">
                                            <small class="text-danger error-text"
                                                id="error_overdue_installments"></small>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="font-weight-bold">
                                                Penalidad <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" id="penalty_amount" name="penalty_amount"
                                                class="form-control" min="0" step="0.01" value="0.00">
                                            <small class="text-danger error-text" id="error_penalty_amount"></small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="font-weight-bold">
                                                Monto pagado
                                            </label>
                                            <input type="text" id="amount_paid" name="amount_paid"
                                                class="form-control" readonly value="0.00">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="font-weight-bold">
                                                Motivo <span class="text-danger">*</span>
                                            </label>
                                            <select id="reason" name="reason" class="form-control">
                                                <option value="">-- Seleccione --</option>
                                                <option value="incumplimiento_pago">Incumplimiento de pago</option>
                                                <option value="mora_excesiva">Mora excesiva</option>
                                                <option value="abandono_contrato">Abandono de contrato</option>
                                                <option value="acuerdo_mutuo">Acuerdo mutuo</option>
                                                <option value="otro">Otro</option>
                                            </select>
                                            <small class="text-danger error-text" id="error_reason"></small>
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label class="font-weight-bold">
                                                Observación
                                            </label>
                                            <textarea id="observation" name="observation" rows="4" class="form-control"
                                                placeholder="Ingrese una observación adicional..."></textarea>
                                            <small class="text-danger error-text" id="error_observation"></small>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="alert alert-warning border-0 mb-0">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                Al confirmar la rescisión, el sistema cambiará la venta a
                                                <strong>rescindido</strong>
                                                y liberará el lote como <strong>disponible</strong>.
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light border-top py-3">

                    <div class="mr-auto text-muted small">
                        <i class="fas fa-shield-alt text-warning"></i>
                        Esta operación registrará la rescisión y no eliminará el historial del contrato.
                    </div>

                    <button type="button" class="btn btn-outline-secondary px-4" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-danger px-4 shadow-sm" id="btnSaveRescission">
                        <i class="fas fa-file-signature mr-1"></i>
                        Confirmar Rescisión
                    </button>

                </div>

            </form>
        </div>
    </div>
</div>


<style>
    #rescissionModal .modal-dialog {
        max-width: 1050px;
    }

    #rescissionModal .modal-content {
        max-height: 92vh;
    }

    #rescissionModal .modal-body {
        overflow-y: auto;
        max-height: calc(92vh - 140px);
    }

    #rescissionModal .modal-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        background: #fff;
        position: sticky;
        bottom: 0;
        z-index: 10;
    }
</style>
