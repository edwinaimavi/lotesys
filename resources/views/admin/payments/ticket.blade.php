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

        .logo {
            max-width: 140px;
            max-height: 90px;
            margin-bottom: 8px;
        }

        h3 {
            margin: 4px 0;
            font-size: 15px;
        }

        .qr-box {
            text-align: center;
            margin-top: 8px;
            margin-bottom: 8px;
        }

        .qr-box svg {
            width: 95px;
            height: 95px;
        }

        .small-text {
            font-size: 10px;
        }

        .bold {
            font-weight: bold;
        }
    </style>
</head>

<body onload="window.print(); setTimeout(function(){ window.close(); }, 1000);">

    @php
        $logo = null;

        if ($invoice->company && $invoice->company->ruc == '20607752312') {
            $logo = asset('storage/logoem/LOGOKREA.png');
        } elseif ($invoice->company && $invoice->company->ruc == '20610686665') {
            $logo = asset('storage/logoem/LOGOFARJE.jpeg');
        }

        $tipoComprobanteSunat = match ($invoice->document_type) {
            'invoice' => '01',
            'receipt' => '03',
            'sale_note' => 'NV',
            default => '00',
        };

        $documentoClienteTipo = $invoice->customer_document_type == '6' ? '6' : '1';

        $fechaQr = $invoice->issue_date
            ? \Carbon\Carbon::parse($invoice->issue_date)->format('Y-m-d')
            : now()->format('Y-m-d');

        /*
        |--------------------------------------------------------------------------
        | QR DEL COMPROBANTE
        |--------------------------------------------------------------------------
        | Formato base:
        | RUC EMPRESA | TIPO DOC | SERIE | NÚMERO | IGV | TOTAL | FECHA | TIPO DOC CLIENTE | DOC CLIENTE
        |
        | Para boleta/factura sirve como resumen de comprobante.
        | Para nota de venta sirve como control interno.
        |--------------------------------------------------------------------------
        */

        $qrText = implode('|', [
            $invoice->company->ruc ?? '',
            $tipoComprobanteSunat,
            $invoice->series ?? '',
            $invoice->number ?? '',
            number_format((float) ($invoice->tax_amount ?? 0), 2, '.', ''),
            number_format((float) ($invoice->total_amount ?? 0), 2, '.', ''),
            $fechaQr,
            $documentoClienteTipo,
            $invoice->customer_document ?? '',
        ]);
    @endphp

    <div class="center">

        @if ($logo)
            <img src="{{ $logo }}" alt="Logo Empresa" class="logo">
            <br>
        @endif

        <strong style="font-size:14px;">
            {{ $invoice->company->business_name ?? 'EMPRESA' }}
        </strong>
        <br>

        RUC: {{ $invoice->company->ruc ?? '—' }}
        <br>

        {{ $invoice->company->address ?? '—' }}
        <br>

    </div>

    <div class="line"></div>

    <div class="center">
        @if ($invoice->document_type == 'sale_note')
            <h3>NOTA DE VENTA</h3>
        @elseif($invoice->document_type == 'receipt')
            <h3>BOLETA ELECTRÓNICA</h3>
        @else
            <h3>FACTURA ELECTRÓNICA</h3>
        @endif

        <strong>
            {{ $invoice->series }}-{{ $invoice->number }}
        </strong>
    </div>

    <div class="line"></div>

    Fecha:
    {{ $invoice->issue_date ? \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') : now()->format('d/m/Y') }}
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

    <strong>
        TOTAL: S/ {{ number_format($invoice->total_amount, 2) }}
    </strong>

    <div class="line"></div>

    SON:<br>
    {{ strtoupper($montoLetras) }}

    @if ($invoice->document_type != 'sale_note')
        <div class="line"></div>

        <strong>Leyenda:</strong><br>
        {{ $invoice->legend }}

        <div class="line"></div>

        <div class="center small-text">
            Representación impresa del<br>
            Comprobante Electrónico.
        </div>
    @endif

    <div class="line"></div>

    <div class="qr-box">
        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(95)->margin(1)->generate($qrText) !!}
    </div>

    <div class="center small-text">
        Escanee el QR para verificar<br>
        los datos principales del comprobante.
    </div>

    <div class="line"></div>

    <div class="center">
        @if ($invoice->document_type == 'sale_note')
            Documento interno de control.<br>
            Gracias por su preferencia.
        @else
            Representación impresa del<br>
            Comprobante Electrónico.<br>
            Consulte en SUNAT Virtual.
        @endif
    </div>

</body>

</html>
