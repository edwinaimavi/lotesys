<?php

namespace App\Exports;

use App\Models\Sale;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $sales = Sale::with([
            'customer',
            'lot.block.project.company'
        ]);

        if (!empty($this->filters['company_id'])) {
            $sales->whereHas('lot.block.project', function ($q) {
                $q->where('company_id', $this->filters['company_id']);
            });
        }

        if (!empty($this->filters['project_id'])) {
            $sales->whereHas('lot.block', function ($q) {
                $q->where('project_id', $this->filters['project_id']);
            });
        }

        if (!empty($this->filters['block_id'])) {
            $sales->whereHas('lot', function ($q) {
                $q->where('block_id', $this->filters['block_id']);
            });
        }

        if (!empty($this->filters['sale_type'])) {
            $sales->where('sale_type', $this->filters['sale_type']);
        }

        if (!empty($this->filters['date_from'])) {
            $sales->whereDate('sale_date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $sales->whereDate('sale_date', '<=', $this->filters['date_to']);
        }

        return $sales->get();
    }

    public function headings(): array
    {
        return [
            'CÓDIGO',
            'CLIENTE',
            'EMPRESA',
            'PROYECTO',
            'LOTE',
            'FECHA DE VENTA',
            'TIPO VENTA',
            'PRECIO LOTE',
            'MONTO RESTANTE',
            'ESTADO',
            'OBSERVACIÓN',
        ];
    }

    public function map($sale): array
    {
        return [
            $sale->sale_code,
            optional($sale->customer)->full_name,
            optional(
                optional(
                    optional(
                        optional($sale->lot)->block
                    )->project
                )->company
            )->trade_name ?? '-',
            optional(
                optional(
                    optional($sale->lot)->block
                )->project
            )->name ?? '-',
            optional($sale->lot)->code ?? '-',

            // FECHA DE VENTA
            $sale->sale_date
                ? \Carbon\Carbon::parse($sale->sale_date)->format('d/m/Y')
                : '',

            strtoupper($sale->sale_type),
            number_format($sale->lot_price, 2),
            number_format($sale->balance_finance, 2),
            strtoupper($sale->status),
            '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Encabezados
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

                // Insertar 2 filas al inicio
                $sheet->insertNewRowBefore(1, 2);

                // Título principal
                $sheet->mergeCells('A1:K1');
                $sheet->setCellValue('A1', 'REPORTE DE VENTAS');

                // Fecha de generación
                $sheet->mergeCells('A2:K2');
                $sheet->setCellValue(
                    'A2',
                    'Fecha de generación: ' . now()->format('d/m/Y H:i')
                );

                // Estilos del título
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 18,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '198754'], // Verde
                    ],
                ]);

                // Estilo de la fecha
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'italic' => true,
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // Encabezado de columnas (fila 3)
                $sheet->getStyle('A3:K3')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0D6EFD'], // Azul
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Bordes para toda la tabla
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("A3:K{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(
                        \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                    );

                // Centrar algunas columnas
                $sheet->getStyle("A3:K{$lastRow}")
                    ->getAlignment()
                    ->setVertical(
                        \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                    );
            },
        ];
    }
}
