<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Pagos</title>

    <style>
        @page {
            margin: 25px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #2d3748;
        }

        /*==========================
        =         HEADER          =
        ==========================*/

        .top-line {
            height: 8px;
            background: #0d6efd;
            margin-bottom: 15px;
        }

        .header {
            width: 100%;
            margin-bottom: 20px;
        }

        .header-left {
            float: left;
            width: 70%;
        }

        .header-right {
            float: right;
            width: 30%;
            text-align: right;
            font-size: 10px;
            color: #666;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 3px;
        }

        .subtitle {
            font-size: 11px;
            color: #777;
        }

        .clearfix {
            clear: both;
        }

        /*==========================
        =       INFO BOX          =
        ==========================*/

        .info-box {
            width: 100%;
            margin-top: 15px;
            margin-bottom: 20px;
            border: 1px solid #dbe2ea;
            background: #f8fafc;
            border-radius: 4px;
        }

        .info-box td {
            padding: 8px 10px;
            border: none;
            font-size: 10px;
        }

        .info-label {
            font-weight: bold;
            color: #444;
        }

        /*==========================
        =         TABLE           =
        ==========================*/

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table th {
            background: #198754;
            color: #fff;
            padding: 7px;
            font-size: 8.5px;
            text-align: center;
            border: 1px solid #e5e7eb;
        }

        .report-table td {
            border: 1px solid #e5e7eb;
            padding: 6px 4px;
            font-size: 8px;
            text-align: center;
        }

        .report-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .money {
            text-align: right;
        }

        /*==========================
        =      RESUMEN TOTAL      =
        ==========================*/

        .summary {
            margin-top: 18px;
            width: 40%;
            float: right;
        }

        .summary-title {
            background: #0d6efd;
            color: #fff;
            padding: 8px;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            border: 1px solid #0d6efd;
        }

        .summary table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary td {
            border: 1px solid #d1d5db;
            padding: 8px;
            font-size: 10px;
        }

        .summary td:first-child {
            background: #f8fafc;
            font-weight: bold;
        }

        .summary td:last-child {
            text-align: right;
        }

        /*==========================
        =         FOOTER          =
        ==========================*/

        .footer {
            position: fixed;
            bottom: -5px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }
    </style>
</head>

<body>

    <div class="top-line"></div>

    <div class="header">
        <div class="header-left">
            <div class="title">
                REPORTE DE PAGOS
            </div>

            <div class="subtitle">
                Sistema de Gestión Comercial - Reporte General de Pagos
            </div>
        </div>

        <div class="header-right">
            <strong>Fecha de generación</strong><br>
            {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <div class="clearfix"></div>

    <table class="info-box">
        <tr>
            <td width="20%" class="info-label">Registros encontrados:</td>
            <td width="15%">{{ $payments->count() }}</td>

            <td width="20%" class="info-label">Fecha del reporte:</td>
            <td>{{ now()->format('d/m/Y') }}</td>
        </tr>
    </table>

    <table class="report-table">
        <thead>
            <tr>
                <th>CÓDIGO</th>
                <th>CLIENTE</th>
                <th>EMPRESA</th>
                <th>PROYECTO</th>
                <th>LOTE</th>
                <th>FECHA PAGO</th>
                <th>TIPO</th>
                <th>MÉTODO</th>
                <th>MONTO</th>
                <th>MORA</th>
                <th>DESCUENTO</th>
                <th>ESTADO</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($payments as $payment)
                <tr>
                    <td>{{ optional($payment->sale)->sale_code }}</td>

                    <td>
                        {{ optional(optional($payment->sale)->customer)->full_name }}
                    </td>

                    <td>
                        {{ optional(optional(optional(optional(optional($payment->sale)->lot)->block)->project)->company)->trade_name }}
                    </td>

                    <td>
                        {{ optional(optional(optional(optional($payment->sale)->lot)->block)->project)->name }}
                    </td>

                    <td>
                        {{ optional(optional($payment->sale)->lot)->code }}
                    </td>

                    <td>
                        {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') : '-' }}
                    </td>

                    <td>
                        {{ strtoupper($payment->payment_type) }}
                    </td>

                    <td>
                        {{ ucfirst($payment->payment_method) }}
                    </td>

                    <td class="money">
                        S/ {{ number_format($payment->amount, 2) }}
                    </td>

                    <td class="money">
                        S/ {{ number_format($payment->late_fee_paid ?? 0, 2) }}
                    </td>

                    <td class="money">
                        S/ {{ number_format($payment->discount ?? 0, 2) }}
                    </td>

                    <td>
                        {{ strtoupper($payment->status) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">

        <div class="summary-title">
            RESUMEN DEL REPORTE
        </div>

        <table>
            <tr>
                <td>Total de registros</td>
                <td>{{ $payments->count() }}</td>
            </tr>
            <tr>
                <td>Total Pagado</td>
                <td>S/ {{ number_format($totalPagado, 2) }}</td>
            </tr>
            <tr>
                <td>Total Mora</td>
                <td>S/ {{ number_format($totalMora, 2) }}</td>
            </tr>
            <tr>
                <td>Total Descuentos</td>
                <td>S/ {{ number_format($totalDescuento, 2) }}</td>
            </tr>
        </table>

    </div>

    <div class="clearfix"></div>

    <div class="footer">
        Reporte generado automáticamente por el Sistema de Gestión Comercial • {{ now()->format('Y') }}
    </div>

</body>

</html>
