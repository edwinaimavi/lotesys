<div class="btn-group shadow-sm" role="group" aria-label="Actions">

    {{-- VER --}}
    <button type="button" class="btn btn-outline-info btn-sm viewPayment mr-2" data-toggle="tooltip" title="Ver Pago"
        data-id="{{ $payment->id }}" data-sale="{{ $payment->sale->sale_code ?? '—' }}"
        data-installment="{{ $payment->installment_label ?? '—' }}" data-payment_type="{{ $payment->payment_type }}"
        data-payment_date="{{ $payment->payment_date }}" data-amount="{{ $payment->amount }}"
        data-late_fee_paid="{{ $payment->late_fee_paid }}" data-discount="{{ $payment->discount }}"
        data-payment_method="{{ $payment->payment_method }}" data-operation_number="{{ $payment->operation_number }}"
        data-observation="{{ $payment->observation }}" data-status="{{ $payment->status }}"
        data-created_by="{{ optional($payment->creator)->name }}"
        data-updated_by="{{ optional($payment->updater)->name }}" data-created_at="{{ $payment->created_at }}"
        data-updated_at="{{ $payment->updated_at }}">
        <i class="fas fa-eye"></i>
    </button>

    @php
        $invoice = $payment->invoice;
        $hasSunatDocument =
            $invoice &&
            in_array($invoice->document_type, ['receipt', 'invoice']) &&
            $invoice->sunat_status == 'accepted';

        $hasSaleNote = $invoice && $invoice->document_type == 'sale_note';
    @endphp

    @if ($invoice)

        {{-- PDF (solo si existe) --}}
        @if ($invoice->pdf_path)
            <a href="{{ asset('storage/' . $invoice->pdf_path) }}" target="_blank"
                class="btn btn-outline-danger btn-sm mr-2" data-toggle="tooltip" title="Abrir PDF">
                <i class="fas fa-file-pdf"></i>
            </a>
        @endif

        {{-- IMPRIMIR TICKET --}}
        <a href="{{ route('admin.invoices.ticket', $invoice->id) }}" target="_blank"
            class="btn btn-outline-primary btn-sm mr-2" data-toggle="tooltip" title="Imprimir Ticket">
            <i class="fas fa-print"></i>
        </a>

        {{-- XML (solo si existe) --}}
       {{--  @if ($invoice->xml_path)
            <a href="{{ asset('storage/' . $invoice->xml_path) }}" target="_blank"
                class="btn btn-outline-secondary btn-sm mr-2" data-toggle="tooltip" title="Abrir XML">
                <i class="fas fa-file-code"></i>
            </a>
        @endif --}}

    @endif

    {{-- SI YA EXISTE BOLETA O FACTURA SUNAT --}}
    @if ($hasSunatDocument)
        <button type="button" class="btn btn-outline-success btn-sm mr-2" disabled data-toggle="tooltip"
            title="Comprobante SUNAT emitido">
            <i class="fas fa-check-circle"></i>
        </button>
    @else
        {{-- AÚN SE PUEDE EMITIR (aunque exista Nota de Venta) --}}
        <button type="button" class="btn btn-outline-success btn-sm generateInvoice mr-2" data-toggle="tooltip"
            title="{{ $hasSaleNote ? 'Emitir Boleta / Factura SUNAT' : 'Emitir Comprobante' }}"
            data-payment_id="{{ $payment->id }}" data-sale_id="{{ $payment->sale_id }}"
            data-amount="{{ $payment->amount }}" data-payment_type="{{ $payment->payment_type }}"
            data-sale="{{ $payment->sale->sale_code ?? '' }}">
            <i class="fas fa-file-invoice-dollar"></i>
        </button>
    @endif

    {{-- ANULAR --}}
    <button type="button" class="btn btn-outline-warning btn-sm cancelPayment mr-2" data-id="{{ $payment->id }}"
        data-toggle="tooltip" title="Anular Pago">
        <i class="fas fa-ban"></i>
    </button>

</div>
