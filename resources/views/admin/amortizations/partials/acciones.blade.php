<div class="btn-group shadow-sm" role="group" aria-label="Actions">

    {{-- VIEW --}}
    <button type="button" class="btn btn-outline-info btn-sm viewAmortization" data-toggle="tooltip"
        title="Ver Amortización" data-id="{{ $item->id }}" data-sale="{{ $item->sale->sale_code ?? '—' }}"
        data-payment="{{ $item->payment ? 'PAGO #' . $item->payment->id : '—' }}" data-sale_id="{{ $item->sale_id }}"
        data-payment_id="{{ $item->payment_id }}" data-date="{{ $item->date }}" data-amount="{{ $item->amount }}"
        data-recalculation_type="{{ $item->recalculation_type }}"
        data-reduced_installments="{{ $item->reduced_installments }}"
        data-new_installment="{{ $item->new_installment }}" data-observation="{{ $item->observation }}"
        data-created_at="{{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '—' }}"
        data-updated_at="{{ $item->updated_at ? $item->updated_at->format('d/m/Y H:i') : '—' }}"
        data-created_by="{{ $item->creator->name ?? 'No registrado' }}"
        data-updated_by="{{ $item->updater->name ?? 'No registrado' }}">

        <i class="fas fa-eye"></i>

    </button>

</div>
