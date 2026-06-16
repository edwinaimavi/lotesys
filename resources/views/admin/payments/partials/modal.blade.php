<!-- MODAL PAGO -->
<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

        <div class="modal-content shadow-lg border-0 rounded-lg overflow-hidden">

            <!-- HEADER -->
            <div class="modal-header align-items-center"
                style="background: linear-gradient(90deg,#ffffff,#f3f6f8); border-bottom:1px solid #e6eaee;">

                <div class="d-flex align-items-center">

                    <div class="icon-circle bg-light mr-3 icon_modal">

                        <i class="fas fa-money-bill-wave text-primary"></i>

                    </div>

                    <div>

                        <h5 class="modal-title mb-0" id="paymentModalLabel">
                            Nuevo Pago
                        </h5>

                        <small class="text-muted">
                            Registro de pagos y cuotas
                        </small>

                    </div>

                </div>

                <button type="button" class="close ml-3" data-dismiss="modal" aria-label="Close" style="opacity:.9;">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <!-- BODY -->
            <div class="modal-body p-3" style="background: #f8fbfc;">

                <form id="paymentForm" autocomplete="off" class="row">

                    @csrf

                    <!-- PANEL IZQUIERDO -->
                    <div class="col-lg-4 mb-3">

                        <div class="card border-0 rounded-lg shadow-sm h-100">

                            <div class="card-body text-center py-4">

                                <div class="mb-3">

                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                                        style="
                                            width:120px;
                                            height:120px;
                                            background:linear-gradient(135deg,#28a745,#1e7e34);
                                            color:white;
                                            font-size:45px;
                                            box-shadow:0 6px 18px rgba(0,0,0,.1);
                                        ">

                                        <i class="fas fa-money-check-alt"></i>

                                    </div>

                                </div>

                                <h5 class="font-weight-bold text-dark mb-1">
                                    Pagos
                                </h5>

                                <small class="text-muted">
                                    Gestión de pagos inmobiliarios
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
                                        Estado inicial
                                    </small>

                                    <div class="badge badge-success py-2 px-3 mt-1">
                                        Activo
                                    </div>

                                    <small class="text-muted d-block mt-3">
                                        Módulo
                                    </small>

                                    <div class="font-weight-600">
                                        Pagos
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

                                    <div class="form-group col-md-6">

                                        <label for="sale_id" class="small font-weight-bold text-secondary">

                                            VENTA
                                            <span class="text-danger">*</span>

                                        </label>

                                        <select id="sale_id" name="sale_id" class="form-control form-control-sm">

                                            <option value="">
                                                Seleccione una venta
                                            </option>

                                            @foreach ($sales as $sale)
                                                <option value="{{ $sale->id }}">

                                                    {{ $sale->sale_code }}
                                                    -

                                                    @if ($sale->customer?->person_type == 'juridica')
                                                        {{ $sale->customer->business_name }}
                                                    @else
                                                        {{ trim(($sale->customer->first_name ?? '') . ' ' . ($sale->customer->last_name ?? '')) }}
                                                    @endif

                                                </option>
                                            @endforeach

                                        </select>

                                        <span class="invalid-feedback" id="sale_id-error"></span>

                                    </div>

                                    <div class="form-group col-md-6">

                                        <label for="payment_schedule_id" class="small font-weight-bold text-secondary">

                                            CUOTA PRINCIPAL
                                            <span class="text-danger">*</span>

                                        </label>

                                        <select id="payment_schedule_id" name="payment_schedule_id"
                                            class="form-control form-control-sm">

                                            <option value="">
                                                Seleccione una cuota
                                            </option>

                                        </select>

                                        <span class="invalid-feedback" id="payment_schedule_id-error"></span>

                                    </div>

                                </div>

                                <!-- DETALLE DE APLICACIÓN -->
                                <div class="card border shadow-sm mb-4">

                                    <div class="card-header bg-light">

                                        <div class="d-flex justify-content-between align-items-center">

                                            <div>

                                                <h6 class="mb-0 font-weight-bold">

                                                    <i class="fas fa-layer-group text-primary mr-1"></i>

                                                    Detalle de Aplicación del Pago

                                                </h6>

                                                <small class="text-muted">

                                                    Aquí se distribuirá el pago entre cuotas.

                                                </small>

                                            </div>

                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                id="btnAddInstallment">

                                                <i class="fas fa-plus"></i>
                                                Agregar Cuota

                                            </button>

                                        </div>

                                    </div>

                                    <div class="card-body p-0">

                                        <div class="table-responsive">

                                            <table class="table table-sm table-bordered mb-0" id="tablePaymentDetails">

                                                <thead class="bg-light">

                                                    <tr class="text-center">

                                                        <th width="40%">Cuota</th>

                                                        <th width="20%">Saldo</th>

                                                        <th width="20%">Monto Aplicado</th>

                                                        <th width="10%">Mora</th>

                                                        <th width="10%">Acción</th>

                                                    </tr>

                                                </thead>

                                                <tbody id="paymentDetailsBody">

                                                    <tr class="text-center text-muted empty-row">

                                                        <td colspan="5">
                                                            No hay cuotas agregadas
                                                        </td>

                                                    </tr>

                                                </tbody>

                                            </table>

                                        </div>

                                    </div>

                                </div>

                                <!-- FILA 2 -->
                                <div class="form-row">

                                    <div class="form-group col-md-4">

                                        <label for="payment_type" class="small font-weight-bold text-secondary">

                                            TIPO PAGO
                                            <span class="text-danger">*</span>

                                        </label>

                                        <select id="payment_type" name="payment_type"
                                            class="form-control form-control-sm">

                                            <option value="inicial">Inicial</option>

                                            <option value="cuota">Cuota</option>

                                            <option value="amortizacion">Amortización</option>

                                            <option value="cancelacion_total">
                                                Cancelación Total
                                            </option>

                                            <option value="mora">Mora</option>

                                        </select>

                                        <span class="invalid-feedback" id="payment_type-error"></span>

                                    </div>

                                    <div class="form-group col-md-4">

                                        <label for="payment_date" class="small font-weight-bold text-secondary">

                                            FECHA PAGO
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="date" id="payment_date" name="payment_date"
                                            class="form-control form-control-sm" value="{{ date('Y-m-d') }}">

                                        <span class="invalid-feedback" id="payment_date-error"></span>

                                    </div>

                                    <div class="form-group col-md-4">

                                        <label for="status" class="small font-weight-bold text-secondary">

                                            ESTADO
                                            <span class="text-danger">*</span>

                                        </label>

                                        <select id="status" name="status" class="form-control form-control-sm">

                                            <option value="activo">
                                                Activo
                                            </option>

                                            <option value="anulado">
                                                Anulado
                                            </option>

                                        </select>

                                        <span class="invalid-feedback" id="status-error"></span>

                                    </div>

                                </div>

                                <!-- FILA 3 -->
                                <div class="form-row">

                                    <div class="form-group col-md-4">

                                        <label for="amount" class="small font-weight-bold text-secondary">

                                            MONTO
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="number" step="0.01" min="0" id="amount"
                                            name="amount" class="form-control form-control-sm" placeholder="0.00">

                                        <span class="invalid-feedback" id="amount-error"></span>

                                    </div>

                                    <div class="form-group col-md-4">

                                        <label for="late_fee_paid" class="small font-weight-bold text-secondary">

                                            MORA PAGADA

                                        </label>

                                        <input type="number" step="0.01" min="0" id="late_fee_paid"
                                            name="late_fee_paid" class="form-control form-control-sm"
                                            placeholder="0.00" value="0">

                                        <span class="invalid-feedback" id="late_fee_paid-error"></span>

                                    </div>

                                    <div class="form-group col-md-4">

                                        <label for="discount" class="small font-weight-bold text-secondary">

                                            DESCUENTO

                                        </label>

                                        <input type="number" step="0.01" min="0" id="discount"
                                            name="discount" class="form-control form-control-sm" placeholder="0.00"
                                            value="0">

                                        <span class="invalid-feedback" id="discount-error"></span>

                                    </div>

                                </div>

                                <!-- FILA 4 -->
                                <!-- FILA 4 -->
                                <div class="form-row">

                                    <!-- MÉTODO -->
                                    <div class="form-group col-md-4">

                                        <label for="payment_method" class="small font-weight-bold text-secondary">

                                            MÉTODO PAGO
                                            <span class="text-danger">*</span>

                                        </label>

                                        <select id="payment_method" name="payment_method"
                                            class="form-control form-control-sm">

                                            <option value="efectivo">
                                                Efectivo
                                            </option>

                                            <option value="transferencia">
                                                Transferencia
                                            </option>

                                            <option value="deposito">
                                                Depósito
                                            </option>

                                            <option value="yape">
                                                Yape
                                            </option>

                                            <option value="plin">
                                                Plin
                                            </option>

                                        </select>

                                        <span class="invalid-feedback" id="payment_method-error"></span>

                                    </div>

                                    <!-- BANCO -->
                                    <div class="form-group col-md-4" id="bank_container" style="display:none;">

                                        <label for="bank_id" class="small font-weight-bold text-secondary">

                                            BANCO
                                            <span class="text-danger">*</span>

                                        </label>

                                        <select id="bank_id" name="bank_id" class="form-control form-control-sm">

                                            <option value="">
                                                Seleccione banco
                                            </option>

                                            @foreach ($banks as $bank)
                                                <option value="{{ $bank->id }}">

                                                    {{ $bank->bank_name }}
                                                    -
                                                    {{ $bank->currency }}

                                                </option>
                                            @endforeach

                                        </select>

                                        <span class="invalid-feedback" id="bank_id-error"></span>

                                    </div>

                                    <!-- OPERACIÓN -->
                                    <div class="form-group col-md-4" id="operation_container" style="display:none;">

                                        <label for="operation_number" class="small font-weight-bold text-secondary">

                                            N° OPERACIÓN

                                        </label>

                                        <input type="text" id="operation_number" name="operation_number"
                                            class="form-control form-control-sm"
                                            placeholder="Ingrese número operación">

                                        <span class="invalid-feedback" id="operation_number-error"></span>

                                    </div>

                                </div>
                                <!-- FILA 5 -->
                                <div class="form-row">

                                    <div class="form-group col-md-12">

                                        <label for="observation" class="small font-weight-bold text-secondary">

                                            OBSERVACIÓN

                                        </label>

                                        <textarea id="observation" name="observation" rows="3" class="form-control form-control-sm"
                                            placeholder="Ingrese observaciones del pago"></textarea>

                                        <span class="invalid-feedback" id="observation-error"></span>

                                    </div>

                                </div>

                                <!-- NOTA -->
                                <div class="form-row">

                                    <div class="col-12">

                                        <small class="text-muted">

                                            La información registrada aquí será utilizada
                                            para control financiero, caja y estado de cuotas.

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

                                        <button type="submit" class="btn btn-primary" id="btnSavePayment">

                                            <i class="fas fa-save mr-1"></i>
                                            Guardar Pago

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
