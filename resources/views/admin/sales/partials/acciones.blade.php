@php
    $saleLots = $sale->relationLoaded('saleLots')
        ? $sale->saleLots
            ->sortByDesc('is_primary')
            ->map(fn ($saleLot) => $saleLot->lot)
            ->filter()
            ->values()
        : collect();

    if ($saleLots->isEmpty() && $sale->lot) {
        $saleLots = collect([$sale->lot]);
    }

    $isMultiple = $saleLots->count() > 1;

    $saleLotsPayload = $saleLots->map(function ($lot) {
        return [
            'id' => $lot->id,
            'company' => $lot->project?->company?->trade_name
                ?: ($lot->project?->company?->business_name ?? '—'),
            'company_ruc' => $lot->project?->company?->ruc ?? '—',
            'project' => $lot->project?->name ?? '—',
            'block' => $lot->block?->name ?? '—',
            'lot_number' => $lot->number ?? '—',
            'lot_code' => $lot->code ?? '—',
            'area' => $lot->area,
            'unit_measure' => $lot->unit_measure,
        ];
    })->values();

    $encodedSaleLots = rawurlencode($saleLotsPayload->toJson());

    $saleLotsSummary = $saleLots
        ->map(fn ($lot) => ($lot->block?->name ?? '—') . ' · Lote ' . ($lot->number ?? '—') . ' · ' . ($lot->code ?? '—'))
        ->implode(' | ');
@endphp

<div class="btn-group shadow-sm" role="group" aria-label="Actions">

    {{-- VIEW --}}
    <button type="button" class="btn btn-outline-info btn-sm viewSale" data-toggle="tooltip" title="Ver Venta"
        data-id="{{ $sale->id }}" data-sale_code="{{ $sale->sale_code }}"
        data-customer="
            @if ($sale->customer?->person_type == 'juridica') {{ $sale->customer->business_name }}
            @else
                {{ trim(($sale->customer->first_name ?? '') . ' ' . ($sale->customer->last_name ?? '')) }} @endif
        "
        data-lot="{{ $sale->lot->code ?? '—' }}"
        data-company="{{ $sale->lot?->project?->company?->business_name ?? '—' }}"
        data-company_ruc="{{ $sale->lot?->project?->company?->ruc ?? '—' }}"
        data-project="{{ $sale->lot?->project?->name ?? '—' }}"
        data-block="{{ $sale->lot?->block?->name ?? '—' }}"
        data-lot_number="{{ $sale->lot?->number ?? '—' }}"
        data-lot_code="{{ $sale->lot?->code ?? '—' }}"
        data-sale_lots="{{ $encodedSaleLots }}"
        data-is_multiple="{{ $isMultiple ? 1 : 0 }}"
        data-customer_id="{{ $sale->customer_id }}"
        data-lot_id="{{ $sale->lot_id }}" data-sale_type="{{ $sale->sale_type }}"
        data-sale_date="{{ $sale->sale_date }}"
        data-lot_price="{{ $sale->lot_price }}" data-initial_payment="{{ $sale->initial_payment }}"
        data-balance_finance="{{ $sale->balance_finance }}" data-installments_count="{{ $sale->installments_count }}"
        data-monthly_payment="{{ $sale->monthly_payment }}" data-interest_rate="{{ $sale->interest_rate }}"
        data-first_payment_date="{{ $sale->first_payment_date }}" data-payment_day="{{ $sale->payment_day }}"
        data-status="{{ $sale->status }}" data-is_legacy_sale="{{ $sale->is_legacy_sale ? 1 : 0 }}"
        data-collection_rules_start_date="{{ optional($sale->collection_rules_start_date)->format('Y-m-d') }}"
        data-legacy_observation="{{ $sale->legacy_observation }}"
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
        data-company="{{ $sale->lot?->project?->company?->trade_name ?? $sale->lot?->project?->company?->business_name ?? '—' }}"
        data-project="{{ $sale->lot?->project?->name ?? '—' }}"
        data-block="{{ $sale->lot?->block?->name ?? '—' }}"
        data-lot_number="{{ $sale->lot?->number ?? '—' }}"
        data-lot_code="{{ $sale->lot?->code ?? '—' }}"
        data-lots_summary="{{ $saleLotsSummary }}">

        <i class="fas fa-calendar-alt"></i>

    </button>

    {{-- EDIT --}}
    @if ($isMultiple)
        <button type="button" class="btn btn-outline-secondary btn-sm" disabled
            title="La edición de ventas múltiples se habilitará en una fase posterior">
            <i class="fas fa-layer-group"></i>
        </button>
    @else
        <button type="button" class="btn btn-outline-primary btn-sm editSale" data-toggle="tooltip" title="Editar Venta"
            data-id="{{ $sale->id }}" data-sale_code="{{ $sale->sale_code }}"
            data-customer_id="{{ $sale->customer_id }}" data-lot_id="{{ $sale->lot_id }}"
            data-sale_type="{{ $sale->sale_type }}" data-sale_date="{{ $sale->sale_date }}" data-lot_price="{{ $sale->lot_price }}"
            data-initial_payment="{{ $sale->initial_payment }}" data-balance_finance="{{ $sale->balance_finance }}"
            data-installments_count="{{ $sale->installments_count }}" data-monthly_payment="{{ $sale->monthly_payment }}"
            data-interest_rate="{{ $sale->interest_rate }}" data-first_payment_date="{{ $sale->first_payment_date }}"
            data-payment_day="{{ $sale->payment_day }}" data-late_fee_setting_id="{{ $sale->late_fee_setting_id }}"
            data-is_legacy_sale="{{ $sale->is_legacy_sale ? 1 : 0 }}"
            data-collection_rules_start_date="{{ optional($sale->collection_rules_start_date)->format('Y-m-d') }}"
            data-legacy_observation="{{ $sale->legacy_observation }}" data-status="{{ $sale->status }}">

            <i class="fas fa-pen"></i>

        </button>
    @endif

    {{-- DELETE --}}
    <button type="button" class="btn btn-outline-danger btn-sm deleteSale" data-id="{{ $sale->id }}"
        data-toggle="tooltip" title="Eliminar Venta">

        <i class="fas fa-trash"></i>

    </button>

</div>
