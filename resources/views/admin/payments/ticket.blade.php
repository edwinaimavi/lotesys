<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Ticket</title>

    <style>
        @media print {
            @page {
                margin: 0;
                size: 80mm auto;
            }

            body {
                margin: 0;
            }
        }

        body {
            width: 80mm;
            margin: 0 auto;
            font-family: monospace;
            font-size: 12px;
            color: #000;
        }

        .center {
            text-align: center;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
    </style>
</head>

<body onload="window.print(); setTimeout(function(){ window.close(); }, 1000);">
    @php

        $logo = null;

        // Lo más seguro es comparar por RUC
        if ($invoice->company->ruc == '20607752312') {
            $logo = asset('storage/logoem/LOGOKREA.png');
        } elseif ($invoice->company->ruc == '20610686665') {
            $logo = asset('storage/logoem/LOGOFARJE.jpeg');
        }

    @endphp
    <div class="center">

        @if ($logo)
            <img src="{{ $logo }}" alt="Logo Empresa"
                style="max-width: 140px; max-height: 90px; margin-bottom:8px;">
            <br>
        @endif

        <strong style="font-size:14px;">
            {{ $invoice->company->business_name }}
        </strong>
        <br>

        RUC: {{ $invoice->company->ruc }}
        <br>

        {{ $invoice->company->address }}
        <br>

    </div>

    <div class="line"></div>

    <div class="center">
        <strong>
            @if ($invoice->document_type == 'sale_note')
                <h3>NOTA DE VENTA</h3>
            @elseif($invoice->document_type == 'receipt')
                <h3>BOLETA ELECTRÓNICA</h3>
            @else
                <h3>FACTURA ELECTRÓNICA</h3>
            @endif
        </strong><br>

        {{ $invoice->series }}-{{ $invoice->number }}
    </div>

    <div class="line"></div>

    Fecha:
    {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') }}
    <br><br>

    Cliente:<br>
    {{ $invoice->customer_name }}<br>

    {{ $invoice->customer_document_type == '6' ? 'RUC' : 'DNI' }}:
    {{ $invoice->customer_document }}

    <div class="line"></div>

    <strong>DESCRIPCIÓN</strong><br>
    {{ $invoice->concept }}

    <div class="line"></div>

    Cantidad: 1<br>
    P.Unit: S/ {{ number_format($invoice->subtotal, 2) }}

    <div class="line"></div>

    <strong>TOTAL: S/ {{ number_format($invoice->total_amount, 2) }}</strong>

    <div class="line"></div>

    SON:<br>
    {{ strtoupper($montoLetras) }}

    <div class="line"></div>

    Leyenda:<br>
    @if ($invoice->document_type != 'sale_note')
        <hr>

        <strong>Leyenda:</strong><br>
        {{ $invoice->legend }}

        <hr>

        Representación impresa del
        Comprobante Electrónico.
    @endif

    <div class="line"></div>

    <div class="center">
        Representación impresa del<br>
        Comprobante Electrónico.<br>
        Consulte en SUNAT Virtual.
    </div>

</body>

</html>
