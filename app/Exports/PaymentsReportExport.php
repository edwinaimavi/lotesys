<?php

namespace App\Exports;

use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentsReportExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles,
    WithEvents
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $payments = Payment::with([
            'sale.customer',
            'sale.lot.block.project.company',
            'bank'
        ]);

        /*
        |--------------------------------------------------------------------------
        | FILTROS
        |--------------------------------------------------------------------------
        */

        if (!empty($this->filters['company_id'])) {
            $payments->whereHas('sale.lot.block.project', function ($q) {
                $q->where('company_id', $this->filters['company_id']);
            });
        }

        if (!empty($this->filters['project_id'])) {
            $payments->whereHas('sale.lot.block', function ($q) {
                $q->where('project_id', $this->filters['project_id']);
            });
        }

        if (!empty($this->filters['block_id'])) {
            $payments->whereHas('sale.lot', function ($q) {
                $q->where('block_id', $this->filters['block_id']);
            });
        }

        if (!empty($this->filters['date_from'])) {
            $payments->whereDate(
                'payment_date',
                '>=',
                $this->filters['date_from']
            );
        }

        if (!empty($this->filters['date_to'])) {
            $payments->whereDate(
                'payment_date',
                '<=',
                $this->filters['date_to']
            );
        }

        return $payments->get();
    }

    public function headings(): array
    {
        return [
            'CÓDIGO VENTA',
            'CLIENTE',
            'EMPRESA',
            'PROYECTO',
            'LOTE',
            'FECHA PAGO',
            'TIPO PAGO',
            'MÉTODO PAGO',
            'BANCO',
            'N° OPERACIÓN',
            'MONTO',
            'ESTADO',
            'OBSERVACIÓN',
        ];
    }

    public function map($payment): array
    {
        return [
            optional($payment->sale)->sale_code,

            optional(
                optional($payment->sale)->customer
            )->full_name ?? '-',

            optional(
                optional(
                    optional(
                        optional(
                            optional($payment->sale)->lot
                        )->block
                    )->project
                )->company
            )->trade_name ?? '-',

            optional(
                optional(
                    optional(
                        optional($payment->sale)->lot
                    )->block
                )->project
            )->name ?? '-',

            optional(
                optional($payment->sale)->lot
            )->code ?? '-',

            $payment->payment_date
                ? \Carbon\Carbon::parse(
                    $payment->payment_date
                )->format('d/m/Y')
                : '',

            strtoupper($payment->payment_type),

            strtoupper($payment->payment_method),

            optional($payment->bank)->name ?? '-',

            $payment->operation_number,

            number_format($payment->amount, 2),

            strtoupper($payment->status),

            $payment->observation ?? '',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ESTILOS
    |--------------------------------------------------------------------------
    */

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F4E78'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet;

                // Agregar dos filas arriba
                $sheet->insertNewRowBefore(1, 2);

                // Título
                $sheet->mergeCells('A1:M1');
                $sheet->setCellValue(
                    'A1',
                    'REPORTE DE PAGOS'
                );

                // Fecha
                $sheet->mergeCells('A2:M2');
                $sheet->setCellValue(
                    'A2',
                    'Fecha de generación: ' .
                        now()->format('d/m/Y H:i')
                );

                // Estilo título
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 18,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' =>
                        \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' =>
                        \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => '198754',
                        ],
                    ],
                ]);

                // Estilo fecha
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'italic' => true,
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' =>
                        \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // Encabezados
                $sheet->getStyle('A3:M3')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' =>
                        \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => '0D6EFD',
                        ],
                    ],
                    'alignment' => [
                        'horizontal' =>
                        \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' =>
                        \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Bordes
                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle("A3:M{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(
                        \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                    );

                // Alineación vertical
                $sheet->getStyle("A3:M{$lastRow}")
                    ->getAlignment()
                    ->setVertical(
                        \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                    );
            },
        ];
    }
}
