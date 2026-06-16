<!-- MODAL VENTA -->
<div class="modal fade" id="saleModal" tabindex="-1" role="dialog" aria-labelledby="saleModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

        <div class="modal-content shadow-lg border-0 rounded-lg overflow-hidden">

            <!-- HEADER -->
            <div class="modal-header align-items-center"
                style="
                    background: linear-gradient(90deg,#ffffff,#f3f6f8);
                    border-bottom:1px solid #e6eaee;
                ">

                <div class="d-flex align-items-center">

                    <div class="icon-circle bg-light mr-3 icon_modal">

                        <i class="fas fa-file-invoice-dollar text-primary"></i>

                    </div>

                    <div>

                        <h5 class="modal-title mb-0" id="saleModalLabel">

                            Nueva Venta

                        </h5>

                        <small class="text-muted">

                            Registro de ventas inmobiliarias

                        </small>

                    </div>

                </div>

                <button type="button" class="close ml-3" data-dismiss="modal" aria-label="Close" style="opacity:.9;">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <!-- BODY -->
            <div class="modal-body p-3" style="background: #f8fbfc;">

                <form id="saleForm" autocomplete="off" class="row">

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
                                            background:linear-gradient(135deg,#007bff,#0056b3);
                                            color:white;
                                            font-size:45px;
                                            box-shadow:0 6px 18px rgba(0,0,0,.1);
                                        ">

                                        <i class="fas fa-file-invoice-dollar"></i>

                                    </div>

                                </div>

                                <h5 class="font-weight-bold text-dark mb-1">

                                    Venta

                                </h5>

                                <small class="text-muted">

                                    Gestión de ventas inmobiliarias

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

                                        Ventas

                                    </div>

                                    <!-- INFO MORA -->
                                    <small class="text-muted d-block mt-3">

                                        Configuración Mora

                                    </small>

                                    <div class="font-weight-600 text-primary">

                                        Automática por venta

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

                                    <div class="form-group col-md-4">

                                        <label for="sale_code" class="small font-weight-bold text-secondary">

                                            CÓDIGO
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="text" id="sale_code" name="sale_code"
                                            class="form-control form-control-sm" readonly>

                                        <span class="invalid-feedback" id="sale_code-error"></span>

                                    </div>

                                    <div class="form-group col-md-4">

                                        <label for="sale_date" class="small font-weight-bold text-secondary">

                                            FECHA VENTA
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="date" id="sale_date" name="sale_date"
                                            class="form-control form-control-sm" value="{{ date('Y-m-d') }}">

                                        <span class="invalid-feedback" id="sale_date-error"></span>

                                    </div>

                                    <div class="form-group col-md-4">

                                        <label for="status" class="small font-weight-bold text-secondary">

                                            ESTADO

                                        </label>

                                        <select id="status" name="status" class="form-control form-control-sm">

                                            <option value="activo">
                                                Activo
                                            </option>

                                            <option value="cancelado">
                                                Cancelado
                                            </option>

                                            <option value="rescindido">
                                                Rescindido
                                            </option>

                                            <option value="finalizado">
                                                Finalizado
                                            </option>

                                        </select>

                                        <span class="invalid-feedback" id="status-error"></span>

                                    </div>

                                </div>

                                <!-- FILA 2 -->
                                <div class="form-row">

                                    <div class="form-group col-md-6">

                                        <label for="customer_id" class="small font-weight-bold text-secondary">

                                            CLIENTE
                                            <span class="text-danger">*</span>

                                        </label>

                                        <select id="customer_id" name="customer_id"
                                            class="form-control form-control-sm">

                                            <option value="">
                                                Seleccione un cliente
                                            </option>

                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}">

                                                    @if ($customer->person_type == 'natural')
                                                        {{ $customer->first_name }}
                                                        {{ $customer->last_name }}
                                                    @else
                                                        {{ $customer->business_name }}
                                                    @endif

                                                </option>
                                            @endforeach

                                        </select>

                                        <span class="invalid-feedback" id="customer_id-error"></span>

                                    </div>

                                    <div class="form-group col-md-6">

                                        <label for="lot_id" class="small font-weight-bold text-secondary">

                                            LOTE
                                            <span class="text-danger">*</span>

                                        </label>

                                        <select id="lot_id" name="lot_id" class="form-control form-control-sm">

                                            <option value="">
                                                Seleccione un lote
                                            </option>

                                            @foreach ($lots as $lot)
                                                @if ($lot->status == 'disponible')
                                                    <option value="{{ $lot->id }}"
                                                        data-cash_price="{{ $lot->cash_price }}"
                                                        data-financed_price="{{ $lot->financed_price }}">

                                                        {{ $lot->code }}
                                                        -
                                                        {{ $lot->project->name ?? '' }}

                                                    </option>
                                                @endif
                                            @endforeach

                                        </select>

                                        <span class="invalid-feedback" id="lot_id-error"></span>

                                    </div>

                                </div>

                                <!-- FILA 3 -->
                                <div class="form-row">

                                    <div class="form-group col-md-4">

                                        <label for="sale_type" class="small font-weight-bold text-secondary">

                                            TIPO DE VENTA
                                            <span class="text-danger">*</span>

                                        </label>

                                        <select id="sale_type" name="sale_type" class="form-control form-control-sm">

                                            <option value="">
                                                Seleccione
                                            </option>

                                            <option value="contado">
                                                Contado
                                            </option>

                                            <option value="financiado">
                                                Financiado
                                            </option>

                                        </select>

                                        <span class="invalid-feedback" id="sale_type-error"></span>

                                    </div>

                                    <div class="form-group col-md-4">

                                        <label for="lot_price" class="small font-weight-bold text-secondary">

                                            PRECIO LOTE
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="number" step="0.01" min="0" id="lot_price"
                                            name="lot_price" class="form-control form-control-sm" placeholder="0.00">

                                        <span class="invalid-feedback" id="lot_price-error"></span>

                                    </div>

                                    <div class="form-group col-md-4">

                                        <label for="initial_payment" class="small font-weight-bold text-secondary">

                                            INICIAL
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="number" step="0.01" min="0" id="initial_payment"
                                            name="initial_payment" class="form-control form-control-sm"
                                            placeholder="0.00">

                                        <span class="invalid-feedback" id="initial_payment-error"></span>

                                    </div>

                                </div>

                                <!-- FILA 4 -->
                                <div class="form-row">

                                    <div class="form-group col-md-4">

                                        <label for="balance_finance" class="small font-weight-bold text-secondary">

                                            SALDO
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="number" step="0.01" min="0" id="balance_finance"
                                            name="balance_finance" class="form-control form-control-sm"
                                            placeholder="0.00" readonly>

                                        <span class="invalid-feedback" id="balance_finance-error"></span>

                                    </div>

                                    <div class="form-group col-md-2">

                                        <label for="installments_count" class="small font-weight-bold text-secondary">

                                            CUOTAS
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="number" min="1" id="installments_count"
                                            name="installments_count" class="form-control form-control-sm">

                                        <span class="invalid-feedback" id="installments_count-error"></span>

                                    </div>

                                    <div class="form-group col-md-2">

                                        <label for="monthly_payment" class="small font-weight-bold text-secondary">

                                            CUOTA
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="number" step="0.01" min="0" id="monthly_payment"
                                            name="monthly_payment" class="form-control form-control-sm" readonly>

                                        <span class="invalid-feedback" id="monthly_payment-error"></span>

                                    </div>

                                    <div class="form-group col-md-2">

                                        <label for="interest_rate" class="small font-weight-bold text-secondary">

                                            INTERÉS %
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="number" step="0.01" min="0" id="interest_rate"
                                            name="interest_rate" class="form-control form-control-sm">

                                        <span class="invalid-feedback" id="interest_rate-error"></span>

                                    </div>

                                    <div class="form-group col-md-2">

                                        <label for="payment_day" class="small font-weight-bold text-secondary">

                                            DÍA PAGO
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="number" min="1" max="31" id="payment_day"
                                            name="payment_day" class="form-control form-control-sm">

                                        <span class="invalid-feedback" id="payment_day-error"></span>

                                    </div>

                                </div>

                                <!-- FILA 5 -->
                                <div class="form-row">

                                    <div class="form-group col-md-6">

                                        <label for="first_payment_date" class="small font-weight-bold text-secondary">

                                            PRIMER PAGO
                                            <span class="text-danger">*</span>

                                        </label>

                                        <input type="date" id="first_payment_date" name="first_payment_date"
                                            class="form-control form-control-sm">

                                        <span class="invalid-feedback" id="first_payment_date-error"></span>

                                    </div>

                                    <!-- CONFIGURACIÓN MORA -->
                                    <div class="form-group col-md-6">

                                        <label for="late_fee_setting_id"
                                            class="small font-weight-bold text-secondary">

                                            CONFIGURACIÓN MORA

                                        </label>

                                        <select id="late_fee_setting_id" name="late_fee_setting_id"
                                            class="form-control form-control-sm">

                                            <option value="">
                                                Sin mora
                                            </option>

                                            @foreach ($lateFeeSettings as $setting)
                                                @if ($setting->status == 'activo')
                                                    <option value="{{ $setting->id }}">

                                                        Gracia:
                                                        {{ $setting->grace_days }} días
                                                        |
                                                        Mora:
                                                        S/ {{ number_format($setting->daily_late_fee, 2) }}
                                                        |
                                                        Dom:
                                                        {{ $setting->apply_sundays ? 'SI' : 'NO' }}
                                                        |
                                                        Fer:
                                                        {{ $setting->apply_holidays ? 'SI' : 'NO' }}

                                                    </option>
                                                @endif
                                            @endforeach

                                        </select>

                                        <small class="text-muted">

                                            Define cómo se calculará la mora para esta venta.

                                        </small>

                                        <span class="invalid-feedback" id="late_fee_setting_id-error"></span>

                                    </div>

                                </div>

                                <!-- NOTA -->
                                <div class="form-row">

                                    <div class="col-12">

                                        <div class="alert alert-info border-0 shadow-sm mb-0">

                                            <i class="fas fa-info-circle mr-1"></i>

                                            La información registrada aquí será utilizada
                                            para contratos, cronogramas, pagos y cálculo automático
                                            de mora del cliente.

                                        </div>

                                    </div>

                                </div>

                                <!-- ACCIONES -->
                                <div class="form-row mt-4">

                                    <div class="col-12 d-flex justify-content-end flex-wrap">

                                        <button type="button" class="btn btn-light border mr-2 mb-2"
                                            data-dismiss="modal">

                                            <i class="fas fa-times mr-1"></i>
                                            Cerrar

                                        </button>

                                        <button type="submit" class="btn btn-primary mb-2" id="btnSaveSale">

                                            <i class="fas fa-save mr-1"></i>
                                            Guardar Venta

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
