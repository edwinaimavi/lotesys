@php
    $sale = $payment->sale;
    $lot = $sale?->lot;
    $project = $lot?->project;
    $company = $project?->company;
    $customer = $sale?->customer;

    $customerName = $customer?->person_type === 'juridica'
        ? ($customer?->business_name ?? '—')
        : trim(($customer?->first_name ?? '') . ' ' . ($customer?->last_name ?? ''));
    $customerName = $customerName !== '' ? $customerName : '—';
@endphp

<div class="btn-group shadow-sm" role="group" aria-label="Actions">

    {{-- VER --}}
    <button type="button" class="btn btn-outline-info btn-sm viewPayment mr-2" data-toggle="tooltip" title="Ver Pago"
        data-id="{{ $payment->id }}" data-sale="{{ $sale?->sale_code ?? '—' }}"
        data-customer="{{ $customerName }}"
        data-company="{{ $company?->business_name ?? '—' }}"
        data-company_ruc="{{ $company?->ruc ?? '—' }}"
        data-project="{{ $project?->name ?? '—' }}"
        data-block="{{ $lot?->block?->name ?? '—' }}"
        data-lot_number="{{ $lot?->number ?? '—' }}"
        data-lot_code="{{ $lot?->code ?? '—' }}"
        data-installment="{{ $installmentLabel }}" data-payment_type="{{ $payment->payment_type }}"
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
        $sunatInvoices = $payment->invoices
            ->whereIn('document_type', ['receipt', 'invoice']);

        $acceptedInvoice = $sunatInvoices
            ->where('sunat_status', 'accepted')
            ->sortByDesc('id')
            ->first();

        $pendingInvoice = $sunatInvoices
            ->where('sunat_status', 'pending')
            ->sortByDesc('id')
            ->first();

        $retryableInvoice = $sunatInvoices
            ->whereIn('sunat_status', ['rejected', 'error'])
            ->sortByDesc('id')
            ->first();

        $saleNoteInvoice = $payment->invoices
            ->where('document_type', 'sale_note')
            ->sortByDesc('id')
            ->first();

        // Para PDF/ticket se prioriza un CPE aceptado; un intento rechazado
        // nunca debe presentarse como comprobante válido.
        $invoice = $acceptedInvoice ?: $saleNoteInvoice;

        $hasSunatDocument = (bool) $acceptedInvoice;
        $hasSaleNote = (bool) $saleNoteInvoice;
        $canOperate = $payment->status === 'activo';
    @endphp

    @if ($invoice)

        {{-- PDF (solo si existe) --}}
        @if ($invoice->pdf_path)
            <a href="{{ route('admin.invoices.downloadPdf', $invoice->id) }}" target="_blank"
                class="btn btn-outline-danger btn-sm mr-2" data-toggle="tooltip" title="Abrir PDF">
                <i class="fas fa-file-pdf"></i>
            </a>
        @endif

        {{-- IMPRIMIR TICKET --}}
        <a href="{{ route('admin.invoices.ticket', $invoice->id) }}" target="_blank"
            class="btn btn-outline-primary btn-sm mr-2" data-toggle="tooltip" title="Imprimir Ticket">
            <i class="fas fa-print"></i>
        </a>

    @endif

    {{-- ESTADO DE EMISIÓN SUNAT --}}
    @if ($hasSunatDocument)
        <button type="button" class="btn btn-outline-success btn-sm mr-2" disabled data-toggle="tooltip"
            title="Comprobante SUNAT emitido">
            <i class="fas fa-check-circle"></i>
        </button>
    @elseif ($pendingInvoice)
        <button type="button" class="btn btn-outline-warning btn-sm mr-2" disabled data-toggle="tooltip"
            title="Comprobante pendiente {{ $pendingInvoice->series }}-{{ $pendingInvoice->number }}. Revise Respuesta API antes de reintentar.">
            <i class="fas fa-clock"></i>
        </button>
    @elseif ($canOperate)
        {{-- Rechazado/error: se permite un nuevo intento con correlativo nuevo. --}}
        <button type="button" class="btn btn-outline-success btn-sm generateInvoice mr-2" data-toggle="tooltip"
            title="{{ $retryableInvoice
                ? 'Reintentar comprobante SUNAT (último intento ' . $retryableInvoice->series . '-' . $retryableInvoice->number . ')'
                : ($hasSaleNote ? 'Emitir Boleta / Factura SUNAT' : 'Emitir Comprobante') }}"
            data-payment_id="{{ $payment->id }}" data-sale_id="{{ $payment->sale_id }}"
            data-amount="{{ $payment->amount }}" data-payment_type="{{ $payment->payment_type }}"
            data-sale="{{ $payment->sale->sale_code ?? '' }}">
            <i class="fas fa-file-invoice-dollar"></i>
        </button>
    @endif

    {{-- ANULAR --}}
    @if ($canOperate)
        <button type="button" class="btn btn-outline-warning btn-sm cancelPayment mr-2"
            data-id="{{ $payment->id }}"
            data-has-accepted-cpe="{{ $acceptedInvoice ? 1 : 0 }}"
            data-has-pending-cpe="{{ $pendingInvoice ? 1 : 0 }}"
            data-cpe-label="{{ $acceptedInvoice ? (($acceptedInvoice->document_type === 'invoice' ? 'Factura' : 'Boleta') . ' ' . $acceptedInvoice->series . '-' . $acceptedInvoice->number) : '' }}"
            data-cpe-customer="{{ $acceptedInvoice->customer_name ?? '' }}"
            data-cpe-amount="{{ $acceptedInvoice->total_amount ?? '' }}"
            data-toggle="tooltip" title="Anular Pago">
            <i class="fas fa-ban"></i>
        </button>
    @else
        <button type="button" class="btn btn-outline-secondary btn-sm mr-2" disabled data-toggle="tooltip"
            title="Pago anulado">
            <i class="fas fa-ban"></i>
        </button>
    @endif

</div>
