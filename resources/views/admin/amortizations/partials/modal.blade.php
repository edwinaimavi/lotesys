<!-- MODAL AMORTIZACIÓN -->
<div class="modal fade" id="amortizationModal" tabindex="-1" role="dialog" aria-labelledby="amortizationModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

        <div class="modal-content shadow-lg border-0 rounded-lg overflow-hidden">

            <!-- HEADER -->
            <div class="modal-header align-items-center"
                style="
                    background:
                    linear-gradient(90deg,#ffffff,#f3f6f8);
                    border-bottom:1px solid #e6eaee;
                ">

                <div class="d-flex align-items-center">

                    <div class="icon-circle bg-light mr-3 icon_modal">

                        <i class="fas fa-chart-line text-primary"></i>

                    </div>

                    <div>

                        <h5 class="modal-title mb-0" id="amortizationModalLabel">

                            Nueva Amortización

                        </h5>

                        <small class="text-muted">

                            Recalculo financiero y amortización de deuda

                        </small>

                    </div>

                </div>

                <button type="button" class="close ml-3" data-dismiss="modal" aria-label="Close" style="opacity:.9;">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <!-- BODY -->
            <div class="modal-body p-3" style="background: #f8fbfc;">

                <form id="amortizationForm" autocomplete="off" class="row">

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
                                            background:
                                            linear-gradient(
                                                135deg,
                                                #007bff,
                                                #0056b3
                                            );
                                            color:white;
                                            font-size:45px;
                                            box-shadow:
                                            0 6px 18px rgba(0,0,0,.1);
                                        ">

                                        <i class="fas fa-coins"></i>

                                    </div>

                                </div>

                                <h5 class="font-weight-bold text-dark mb-1">

                                    Amortizaciones

                                </h5>

                                <small class="text-muted">

                                    Motor financiero avanzado

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

                                        Amortizaciones

                                    </div>

                                    <small class="text-muted d-block mt-3">

                                        Función

                                    </small>

                                    <div class="font-weight-600">

                                        Recalcular cronograma financiero

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

                                    <div class="form-group col-md-12">

                                        <label for="sale_id" class="small font-weight-bold text-secondary">

                                            VENTA
                                            <span class="text-danger">*</span>

                                        </label>

                                        <select id="sale_id" name="sale_id" class="form-control form-control-sm">

                                            <option value="">
                                                Seleccione una venta
                                            </option>

                                            @foreach ($sales as $sale)
                                                <option value="{{ $sale->id }}"
                                                    data-installments="{{ $sale->installments_count }}"
                                                    data-paid="{{ $sale->paid_installments_count }}"
                                                    data-monthly="{{ $sale->monthly_payment }}">

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

                                </div>

                                <!-- FILA 2 -->
                                <div class="form-row">

                                    <!-- FECHA -->
                                    <div class="form-group col-md-3">

                                        <label for="date" class="small font-weight-bold text-secondary">

                                            FECHA
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="date" id="date" name="date"
                                            class="form-control form-control-sm" value="{{ date('Y-m-d') }}">

                                        <span class="invalid-feedback" id="date-error"></span>

                                    </div>

                                    <!-- MONTO -->
                                    <div class="form-group col-md-3">

                                        <label for="amount" class="small font-weight-bold text-secondary">

                                            MONTO AMORTIZAR
                                            <span class="text-danger">*</span>

                                        </label>

                                        <div class="input-group input-group-sm">

                                            <div class="input-group-prepend">

                                                <span class="input-group-text bg-light">

                                                    S/

                                                </span>

                                            </div>

                                            <input type="number" step="0.01" min="0" id="amount"
                                                name="amount" class="form-control" placeholder="0.00">

                                        </div>

                                        <span class="invalid-feedback" id="amount-error"></span>

                                    </div>

                                    <!-- DESCUENTO -->
                                    <div class="form-group col-md-3">

                                        <label for="discount_amount" class="small font-weight-bold text-secondary">

                                            DESCUENTO

                                        </label>

                                        <div class="input-group input-group-sm">

                                            <div class="input-group-prepend">

                                                <span class="input-group-text bg-light text-success">

                                                    S/

                                                </span>

                                            </div>

                                            <input type="number" step="0.01" min="0" id="discount_amount"
                                                name="discount_amount" class="form-control" placeholder="0.00">

                                        </div>

                                        <small class="text-muted">

                                            Opcional

                                        </small>

                                        <span class="invalid-feedback" id="discount_amount-error"></span>

                                    </div>

                                    <!-- TIPO -->
                                    <div class="form-group col-md-3">

                                        <label for="recalculation_type" class="small font-weight-bold text-secondary">

                                            TIPO RECÁLCULO
                                            <span class="text-danger">*</span>

                                        </label>

                                        <select id="recalculation_type" name="recalculation_type"
                                            class="form-control form-control-sm">

                                            <option value="">
                                                Seleccione
                                            </option>

                                            <option value="reducir_cuota">
                                                Reducir Cuota
                                            </option>

                                            <option value="reducir_tiempo">
                                                Reducir Tiempo
                                            </option>

                                            <option value="descuento">
                                                Aplicar Descuento
                                            </option>

                                        </select>

                                        <span class="invalid-feedback" id="recalculation_type-error"></span>

                                    </div>

                                </div>

                                <!-- FILA 3 -->
                                <!-- FILA 3 -->
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
                                    <div class="form-group col-md-4 d-none" id="bankBox">

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
                                    <div class="form-group col-md-4 d-none" id="operationNumberBox">

                                        <label for="operation_number" class="small font-weight-bold text-secondary">

                                            N° OPERACIÓN
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="text" name="operation_number" id="operation_number"
                                            class="form-control form-control-sm"
                                            placeholder="Ingrese número de operación">

                                    </div>

                                </div>

                                <!-- RESULTADOS -->
                                <div class="card border shadow-sm mb-4">

                                    <div class="card-header bg-light">

                                        <h6 class="mb-0 font-weight-bold">

                                            <i class="fas fa-calculator text-primary mr-1"></i>

                                            Resultado del Recalculo

                                        </h6>

                                    </div>

                                    <div class="alert alert-light border mb-0">

                                        <div class="row text-center">

                                            <div class="col-md-4">

                                                <small class="text-muted d-block">
                                                    Cuotas Actuales
                                                </small>

                                                <strong id="current_installments"
                                                    class="h5 d-block font-weight-bold text-dark">

                                                    0

                                                </strong>

                                            </div>

                                            <div class="col-md-4">

                                                <small class="text-muted d-block">
                                                    Nuevas Cuotas
                                                </small>

                                                <strong id="new_installments"
                                                    class="h5 d-block font-weight-bold text-primary">

                                                    0

                                                </strong>

                                            </div>

                                            <div class="col-md-4">

                                                <small class="text-muted d-block">
                                                    Cuotas Reducidas
                                                </small>

                                                <strong id="saved_installments"
                                                    class="h5 d-block font-weight-bold text-success">

                                                    0

                                                </strong>

                                            </div>

                                        </div>

                                    </div>

                                    <div class="card-body">

                                        <div class="form-row">

                                            <!-- NUEVA CUOTA -->
                                            <div class="form-group col-md-6 d-none" id="box_new_installment">

                                                <label for="new_installment"
                                                    class="small font-weight-bold text-secondary">

                                                    NUEVA CUOTA

                                                </label>

                                                <input type="number" step="0.01" min="0"
                                                    id="new_installment" name="new_installment"
                                                    class="form-control form-control-sm bg-light" placeholder="0.00"
                                                    readonly>

                                            </div>

                                            <!-- CUOTAS REDUCIDAS -->
                                            <div class="form-group col-md-6 d-none" id="box_reduced_installments">

                                                <label for="reduced_installments"
                                                    class="small font-weight-bold text-secondary">

                                                    CUOTAS REDUCIDAS

                                                </label>

                                                <input type="number" min="0" id="reduced_installments"
                                                    name="reduced_installments"
                                                    class="form-control form-control-sm bg-light" placeholder="0"
                                                    readonly>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                                <!-- OBSERVACIÓN -->
                                <div class="form-row">

                                    <div class="form-group col-md-12">

                                        <label for="observation" class="small font-weight-bold text-secondary">

                                            OBSERVACIÓN

                                        </label>

                                        <textarea id="observation" name="observation" rows="4" class="form-control form-control-sm"
                                            placeholder="Ingrese observaciones de la amortización"></textarea>

                                    </div>

                                </div>

                                <!-- ALERTA -->
                                <div class="alert alert-info border-0 shadow-sm">

                                    <div class="d-flex">

                                        <div class="mr-3">

                                            <i class="fas fa-info-circle fa-2x text-primary"></i>

                                        </div>

                                        <div>

                                            <strong>
                                                Importante:
                                            </strong>

                                            <br>

                                            Una amortización recalculará
                                            automáticamente el cronograma
                                            financiero de la venta.

                                        </div>

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

                                        <button type="submit" class="btn btn-primary" id="btnSaveAmortization">

                                            <i class="fas fa-save mr-1"></i>
                                            Guardar Amortización

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
