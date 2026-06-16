<div class="btn-group shadow-sm" role="group" aria-label="Actions">

    {{-- VIEW --}}
    <button type="button" class="btn btn-outline-info btn-sm viewSale" data-toggle="tooltip" title="Ver Venta"
        data-id="{{ $sale->id }}" data-sale_code="{{ $sale->sale_code }}"
        data-customer="
            @if ($sale->customer?->person_type == 'juridica') {{ $sale->customer->business_name }}
            @else
                {{ trim(($sale->customer->first_name ?? '') . ' ' . ($sale->customer->last_name ?? '')) }} @endif
        "
        data-lot="{{ $sale->lot->code ?? '—' }}" data-customer_id="{{ $sale->customer_id }}"
        data-lot_id="{{ $sale->lot_id }}" data-sale_date="{{ $sale->sale_date }}"
        data-lot_price="{{ $sale->lot_price }}" data-initial_payment="{{ $sale->initial_payment }}"
        data-balance_finance="{{ $sale->balance_finance }}" data-installments_count="{{ $sale->installments_count }}"
        data-monthly_payment="{{ $sale->monthly_payment }}" data-interest_rate="{{ $sale->interest_rate }}"
        data-first_payment_date="{{ $sale->first_payment_date }}" data-payment_day="{{ $sale->payment_day }}"
        data-status="{{ $sale->status }}"
        data-created_at="{{ $sale->created_at ? $sale->created_at->format('d/m/Y H:i') : '—' }}"
        data-updated_at="{{ $sale->updated_at ? $sale->updated_at->format('d/m/Y H:i') : '—' }}"
        data-created_by="{{ $sale->creator->name ?? 'No registrado' }}"
        data-updated_by="{{ $sale->updater->name ?? 'No registrado' }}">

        <i class="fas fa-eye"></i>

    </button>

    {{-- PAYMENT SCHEDULE --}}
    <button type="button" class="btn btn-outline-success btn-sm viewSchedule" data-toggle="tooltip"
        title="Ver Cronograma" data-id="{{ $sale->id }}" data-sale_code="{{ $sale->sale_code }}"
        data-customer="
            @if ($sale->customer?->person_type == 'juridica') {{ $sale->customer->business_name }}
            @else
                {{ trim(($sale->customer->first_name ?? '') . ' ' . ($sale->customer->last_name ?? '')) }} @endif
        "
        data-lot="{{ $sale->lot->code ?? '—' }}">

        <i class="fas fa-calendar-alt"></i>

    </button>

    {{-- EDIT --}}
    <button type="button" class="btn btn-outline-primary btn-sm editSale" data-toggle="tooltip" title="Editar Venta"
        data-id="{{ $sale->id }}" data-sale_code="{{ $sale->sale_code }}"
        data-customer_id="{{ $sale->customer_id }}" data-lot_id="{{ $sale->lot_id }}"
        data-sale_date="{{ $sale->sale_date }}" data-lot_price="{{ $sale->lot_price }}"
        data-initial_payment="{{ $sale->initial_payment }}" data-balance_finance="{{ $sale->balance_finance }}"
        data-installments_count="{{ $sale->installments_count }}" data-monthly_payment="{{ $sale->monthly_payment }}"
        data-interest_rate="{{ $sale->interest_rate }}" data-first_payment_date="{{ $sale->first_payment_date }}"
        data-payment_day="{{ $sale->payment_day }}" data-late_fee_setting_id="{{ $sale->late_fee_setting_id }}"
        data-status="{{ $sale->status }}">

        <i class="fas fa-pen"></i>

    </button>

    {{-- DELETE --}}
    <button type="button" class="btn btn-outline-danger btn-sm deleteSale" data-id="{{ $sale->id }}"
        data-toggle="tooltip" title="Eliminar Venta">

        <i class="fas fa-trash"></i>

    </button>

</div>
