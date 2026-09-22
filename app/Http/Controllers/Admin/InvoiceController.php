<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceApiLog;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\Company;

use App\Services\ApisPeruService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class InvoiceController extends Controller


{
    private const RECEIPT_SERIES = 'B002';

    private const INVOICE_SERIES = 'F002';

    private const SALE_NOTE_SERIES = 'NV001';

    private function seriesForDocumentType(string $documentType): string
    {
        return match ($documentType) {
            'invoice' => self::INVOICE_SERIES,
            'sale_note' => self::SALE_NOTE_SERIES,
            default => self::RECEIPT_SERIES,
        };
    }

    public function getNextNumber(Request $request)
    {
        $data = $request->validate([
            'document_type' => 'required|in:receipt,invoice,sale_note',
            'company_id' => 'required|exists:companies,id',
        ]);

        $documentType = $data['document_type'];
        $companyId = $data['company_id'];

        $series = $this->seriesForDocumentType($documentType);

        $number = $this->calculateNextInvoiceNumber(
            $companyId,
            $documentType,
            $series
        );

        return response()->json([
            'series' => $series,
            'number' => $number
        ]);
    }

    private function calculateNextInvoiceNumber(
        int $companyId,
        string $documentType,
        string $series
    ): int {
        $maxNumber = Invoice::where('company_id', $companyId)
            ->where('document_type', $documentType)
            ->where('series', $series)
            ->max(DB::raw('CAST(number AS UNSIGNED)'));

        return ((int) $maxNumber) + 1;
    }

    private function lockInvoiceSequence(
        int $companyId,
        string $documentType,
        string $series
    ): void {
        Invoice::where('company_id', $companyId)
            ->where('document_type', $documentType)
            ->where('series', $series)
            ->lockForUpdate()
            ->get(['id']);
    }

    private function allocateNextInvoiceNumber(
        int $companyId,
        string $documentType,
        string $series
    ): int {
        $this->lockInvoiceSequence($companyId, $documentType, $series);

        $number = $this->calculateNextInvoiceNumber(
            $companyId,
            $documentType,
            $series
        );

        if (! $this->invoiceNumberExists($companyId, $documentType, $series, $number)) {
            return $number;
        }

        $collision = $series . '-' . $number;
        $number = $this->calculateNextInvoiceNumber(
            $companyId,
            $documentType,
            $series
        );

        if ($this->invoiceNumberExists($companyId, $documentType, $series, $number)) {
            $collision = $series . '-' . $number;

            throw new \RuntimeException(
                "Ya existe un comprobante con la serie y número {$collision}. " .
                'Se recalculó el correlativo, intente nuevamente.'
            );
        }

        return $number;
    }

    private function invoiceNumberExists(
        int $companyId,
        string $documentType,
        string $series,
        int $number
    ): bool {
        return Invoice::where('company_id', $companyId)
            ->where('document_type', $documentType)
            ->where('series', $series)
            ->where('number', (string) $number)
            ->exists();
    }

    /**
     * Un pago solo queda bloqueado para una nueva emisión si ya existe
     * un CPE aceptado o si existe un intento pendiente cuyo resultado
     * todavía no está confirmado. Los intentos rechazados/error sí permiten
     * un nuevo intento con un correlativo nuevo.
     */
    private function blockingSunatInvoiceForPayment(int $paymentId): ?Invoice
    {
        return Invoice::query()
            ->where('payment_id', $paymentId)
            ->whereIn('document_type', ['receipt', 'invoice'])
            ->whereIn('sunat_status', ['accepted', 'pending'])
            ->orderByRaw("CASE WHEN sunat_status = 'accepted' THEN 1 ELSE 2 END")
            ->latest('id')
            ->first();
    }

    private function blockingSunatInvoiceMessage(Invoice $invoice): string
    {
        $label = ($invoice->document_type === 'invoice' ? 'Factura' : 'Boleta') .
            ' ' . $invoice->series . '-' . $invoice->number;

        if ($invoice->sunat_status === 'pending') {
            return "Existe un comprobante pendiente ({$label}). " .
                'Revise la Respuesta API antes de volver a intentar para evitar duplicados.';
        }

        return "Este pago ya tiene un comprobante SUNAT aceptado ({$label}).";
    }

    /**
     * Distingue la respuesta HTTP de APISPERU del resultado tributario real.
     * HTTP 200 no implica aceptación: SUNAT debe confirmar success=true.
     */
    private function extractSunatOutcome(array $result): array
    {
        if (! ($result['success'] ?? false)) {
            return [
                'confirmed' => false,
                'accepted' => false,
                'code' => null,
                'message' => $result['message'] ??
                    'No se pudo confirmar el comprobante con APISPERU/SUNAT.',
            ];
        }

        $data = $result['data'] ?? [];
        $sunatResponse = is_array($data) ? ($data['sunatResponse'] ?? null) : null;

        if (! is_array($sunatResponse) || ! array_key_exists('success', $sunatResponse)) {
            return [
                'confirmed' => false,
                'accepted' => false,
                'code' => null,
                'message' => 'APISPERU respondió, pero no confirmó el resultado de SUNAT. ' .
                    'El comprobante queda pendiente para evitar una emisión duplicada.',
            ];
        }

        $accepted = filter_var(
            $sunatResponse['success'],
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) === true;

        $cdrResponse = is_array($sunatResponse['cdrResponse'] ?? null)
            ? $sunatResponse['cdrResponse']
            : [];

        $error = is_array($sunatResponse['error'] ?? null)
            ? $sunatResponse['error']
            : [];

        $code = $cdrResponse['code'] ?? $error['code'] ?? null;
        $message = $cdrResponse['description']
            ?? $error['message']
            ?? $sunatResponse['message']
            ?? ($accepted ? 'Aceptado por SUNAT.' : 'Rechazado por SUNAT.');

        return [
            'confirmed' => true,
            'accepted' => $accepted,
            'code' => $code === null ? null : (string) $code,
            'message' => (string) $message,
        ];
    }


    public function getCustomerData(Sale $sale)
    {
        $sale->load([
            'customer',
            'lot.block.project.company'
        ]);

        if (!$sale->customer) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado.'
            ], 404);
        }

        $company = optional(
            optional(
                optional(
                    optional($sale->lot)->block
                )->project
            )->company
        );
        $customer = $sale->customer;

        return response()->json([
            'success' => true,
            'data' => [
                'customer_name'          => $sale->customer->full_name,
                'customer_document'      => $sale->customer->document_number,
                'customer_address'       => $sale->customer->address,
                'customer_department' => $customer->department,
                'customer_province'   => $customer->province,
                'customer_district'   => $customer->district,
                'customer_ubigeo'     => $customer->ubigeo,
                'customer_document_type' => $sale->customer->document_type,

                'company_id'   => $company?->id,
                'company_name' => $company
                    ? $company->business_name . ' - ' . $company->ruc
                    : '',
            ]
        ]);
    }


    public function getPaymentDescription(Payment $payment)
    {
        $description = $this->buildPaymentDescription($payment);
        $saleLotSummary = $this->buildSaleLotSummary($payment);

        // ==========================================
        // LEYENDA SUNAT
        // ==========================================

        $operationNumber = trim($payment->operation_number ?? '');

        if ($operationNumber == '') {
            $operationNumber = '--';
        }

        $legend =
            'NRO. DE OPERACION ' .
            $operationNumber .
            "//" .
            'BIENES TRANSFERIDOS EN LA AMAZONÍA REGIÓN SELVA PARA SER CONSUMIDOS EN LA MISMA';

        return response()->json([
            'success' => true,
            'data' => [
                'description' => $description,
                'legend' => $legend,
                'sale_lot_summary' => $saleLotSummary,
            ]
        ]);
    }

    private function buildPaymentDescription(Payment $payment): string
    {
        $payment->loadMissing([
            'details.paymentSchedule',
            'sale.lot.block.project',
            'sale.saleLots.lot.block.project',
        ]);

        $sale = $payment->sale;
        $lot = $sale->lot;
        $block = $lot?->block;
        $project = $block?->project;
        $blockName = trim($block->name ?? '');
        $blockLabel = str_starts_with(strtoupper($blockName), 'MZ')
            ? $blockName
            : 'MZ ' . $blockName;
        $location = trim(
            $blockLabel .
                ' - LT' . ($lot->number ?? '') .
                ' ' . strtoupper($project->name ?? '')
        );

        $saleLots = $sale->saleLots
            ->filter(fn ($saleLot) => $saleLot->lot)
            ->sortByDesc('is_primary')
            ->values();
        $isMultipleLotSale = $saleLots->count() > 1;
        $multipleSaleLocation = null;

        if ($isMultipleLotSale) {
            $projectName = strtoupper(
                $saleLots->first()?->lot?->block?->project?->name
                    ?? $project?->name
                    ?? ''
            );
            $saleCode = trim((string) ($sale->sale_code ?? ''));

            if ($saleLots->count() <= 4) {
                $lotLabels = $saleLots
                    ->map(fn ($saleLot) => $this->formatLotShortLabel($saleLot->lot))
                    ->filter()
                    ->implode(', ');

                $multipleSaleLocation = trim(
                    $saleCode . ' - LOTES ' . $lotLabels . ' - ' . $projectName,
                    ' -'
                );
            } else {
                $multipleSaleLocation = trim(
                    'VENTA MÚLTIPLE ' . $saleCode .
                        ' - ' . $saleLots->count() . ' LOTES - ' . $projectName,
                    ' -'
                );
            }
        }

        $details = $payment->details
            ->sortBy(function ($detail) {
                return $detail->paymentSchedule->installment_number;
            })
            ->values();
        $hasMultipleDetails = $details->count() > 1;

        $installments = $details
            ->map(function ($detail, $index) use (
                $payment,
                $location,
                $hasMultipleDetails,
                $isMultipleLotSale,
                $multipleSaleLocation
            ) {

                $schedule = $detail->paymentSchedule;
                $number = $schedule->installment_number;
                $appliedAmount = round((float) $detail->applied_amount, 2);

                $previouslyApplied = (float) PaymentDetail::where(
                    'payment_schedule_id',
                    $schedule->id
                )
                    ->where('payment_id', '<', $payment->id)
                    ->whereHas('payment', function ($query) {
                        $query->where('status', 'activo');
                    })
                    ->sum('applied_amount');

                $pendingBeforePayment = max(
                    round((float) $schedule->total_amount - $previouslyApplied, 2),
                    0
                );
                $lateFeeApplied = (int) $payment->payment_schedule_id ===
                    (int) $schedule->id
                    ? (float) $payment->late_fee_paid
                    : 0;
                $coversPendingAmount =
                    $appliedAmount + 0.01 >= $pendingBeforePayment;
                $coversLateFee =
                    $lateFeeApplied + 0.01 >= (float) $schedule->late_fee;

                if ($coversPendingAmount && $coversLateFee) {
                    $paymentLabel = 'PAGO';
                } elseif ($hasMultipleDetails && $index > 0) {
                    $paymentLabel = 'ADELANTO';
                } else {
                    $paymentLabel = 'PAGO PARCIAL';
                }

                if ($isMultipleLotSale) {
                    return $paymentLabel . ' ' .
                        $this->formatInstallmentVisualLabel($number) . ' - ' .
                        $multipleSaleLocation . ' - S/ ' .
                        number_format($appliedAmount, 2, '.', ',');
                }

                return $paymentLabel . ' ' .
                    $this->formatInstallmentVisualLabel($number) . ' ' .
                    $location . ' S/ ' .
                    number_format($appliedAmount, 2, '.', ',');
            })
            ->implode(' / ');

        return trim($installments);
    }

    private function buildSaleLotSummary(Payment $payment): array
    {
        $payment->loadMissing([
            'sale.saleLots.lot.block.project',
        ]);

        $sale = $payment->sale;
        $saleLots = $sale->saleLots
            ->filter(fn ($saleLot) => $saleLot->lot)
            ->sortByDesc('is_primary')
            ->values();

        if ($saleLots->count() <= 1) {
            return [
                'is_multiple' => false,
                'lot_count' => $saleLots->count() ?: 1,
                'sale_code' => $sale->sale_code,
                'project_name' => null,
                'lots' => [],
            ];
        }

        $firstLot = $saleLots->first()?->lot;

        return [
            'is_multiple' => true,
            'lot_count' => $saleLots->count(),
            'sale_code' => $sale->sale_code,
            'project_name' => $firstLot?->block?->project?->name,
            'lots' => $saleLots
                ->map(function ($saleLot) {
                    $lot = $saleLot->lot;

                    return [
                        'label' => $this->formatLotLongLabel($lot),
                        'code' => $lot->code,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function formatLotShortLabel($lot): string
    {
        if (! $lot) {
            return '';
        }

        $blockName = trim((string) ($lot->block?->name ?? ''));
        $blockLabel = str_starts_with(strtoupper($blockName), 'MZ')
            ? strtoupper($blockName)
            : 'MZ ' . strtoupper($blockName);

        return trim($blockLabel . '/L' . ($lot->number ?? ''));
    }

    private function formatLotLongLabel($lot): string
    {
        if (! $lot) {
            return '';
        }

        $blockName = trim((string) ($lot->block?->name ?? ''));
        $blockLabel = str_starts_with(strtoupper($blockName), 'MZ')
            ? $blockName
            : 'MZ ' . $blockName;

        return trim($blockLabel . ' / Lote ' . ($lot->number ?? ''));
    }

    private function formatInstallmentVisualLabel($installmentNumber): string
    {
        $visualNumber = ((int) $installmentNumber) + 1;

        return (int) $installmentNumber === 0
            ? 'CUOTA ' . $visualNumber . ' - INICIAL'
            : 'CUOTA ' . $visualNumber;
    }

    public function getCompanies()
    {
        $companies = Company::where('status', 1)
            ->select(
                'id',
                'business_name',
                'ruc',
                'address'
            )
            ->orderBy('business_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $companies
        ]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.invoices.index');
    }

    public function list()
    {
        $invoices = Invoice::with([
            'payment',
            'sale',
            'company',
        ])->withExists('apiLogs')->orderBy('id', 'desc');

        return DataTables::eloquent($invoices)

            ->addIndexColumn()

            ->editColumn('document_type', function ($invoice) {

                $types = [
                    'invoice' => [
                        'label' => 'Factura',
                        'class' => 'badge-primary',
                    ],
                    'receipt' => [
                        'label' => 'Boleta',
                        'class' => 'badge-info',
                    ],
                    'sale_note' => [
                        'label' => 'Nota de Venta',
                        'class' => 'badge-secondary',
                    ],
                    'credit_note' => [
                        'label' => 'Nota de Crédito',
                        'class' => 'badge-warning',
                    ],
                    'debit_note' => [
                        'label' => 'Nota de Débito',
                        'class' => 'badge-warning',
                    ],
                ];

                $type = $types[$invoice->document_type] ?? [
                    'label' => $invoice->document_type ?: '—',
                    'class' => 'badge-secondary',
                ];

                return '<span class="badge ' . $type['class'] . ' px-3 py-2">'
                    . e($type['label'])
                    . '</span>';
            })

            ->addColumn('customer', function ($invoice) {

                return e($invoice->customer_name ?: '—');
            })

            ->editColumn('issue_date', function ($invoice) {

                $date = $invoice->issue_date ?: $invoice->created_at;

                return $date
                    ? date('d/m/Y', strtotime($date))
                    : '—';
            })

            ->editColumn('total_amount', function ($invoice) {

                $currency = $invoice->currency ?: 'PEN';

                return e($currency) . ' ' .
                    number_format((float) $invoice->total_amount, 2);
            })

            ->editColumn('sunat_status', function ($invoice) {

                $statuses = [
                    'accepted' => [
                        'label' => 'Emitido / Aceptado',
                        'class' => 'badge-success',
                    ],
                    'pending' => [
                        'label' => 'Pendiente',
                        'class' => 'badge-warning',
                    ],
                    'rejected' => [
                        'label' => 'Rechazado',
                        'class' => 'badge-danger',
                    ],
                    'voided' => [
                        'label' => 'Anulado',
                        'class' => 'badge-secondary',
                    ],
                    'error' => [
                        'label' => 'Error',
                        'class' => 'badge-danger',
                    ],
                ];

                $status = $statuses[$invoice->sunat_status] ?? [
                    'label' => 'Sin enviar / Interno',
                    'class' => 'badge-secondary',
                ];

                return '<span class="badge ' . $status['class'] . ' px-3 py-2">'
                    . e($status['label'])
                    . '</span>';
            })

            ->addColumn('acciones', function ($invoice) {

                $pdfPath = $this->normalizePublicInvoicePath(
                    $invoice->pdf_path
                );

                $xmlPath = $this->normalizePublicInvoicePath(
                    $invoice->xml_path
                );

                $pdfExists = $pdfPath &&
                    Storage::disk('public')->exists($pdfPath);

                $xmlExists = $xmlPath &&
                    Storage::disk('public')->exists($xmlPath);

                $pdfButton = $pdfExists
                    ? '<a href="' . route('admin.invoices.downloadPdf', $invoice->id) . '" target="_blank" class="btn btn-outline-danger btn-sm" title="PDF" data-toggle="tooltip"><i class="fas fa-file-pdf"></i></a>'
                    : '<button type="button" class="btn btn-outline-secondary btn-sm" title="PDF no disponible" disabled><i class="fas fa-file-pdf"></i></button>';

                $xmlButton = $xmlExists
                    ? '<a href="' . route('admin.invoices.downloadXml', $invoice->id) . '" class="btn btn-outline-primary btn-sm" title="XML" data-toggle="tooltip"><i class="fas fa-file-code"></i></a>'
                    : '<button type="button" class="btn btn-outline-secondary btn-sm" title="XML no disponible" disabled><i class="fas fa-file-code"></i></button>';

                $apiButton = $invoice->api_logs_exists
                    ? '<button type="button" class="btn btn-outline-info btn-sm btn-api-response" title="Ver respuesta API" data-toggle="tooltip" data-url="' . route('admin.invoices.apiLogs', $invoice->id) . '"><i class="fas fa-code"></i></button>'
                    : '<button type="button" class="btn btn-outline-secondary btn-sm" title="Sin respuesta API registrada" data-toggle="tooltip" disabled><i class="fas fa-code"></i></button>';

                return '<div class="btn-group shadow-sm" role="group">'
                    . $pdfButton
                    . $xmlButton
                    . $apiButton
                    . '</div>';
            })

            ->rawColumns([
                'document_type',
                'sunat_status',
                'acciones',
            ])

            ->make(true);
    }

    public function apiLogs(Invoice $invoice)
    {
        $logs = $invoice->apiLogs()
            ->latest('id')
            ->get()
            ->map(function (InvoiceApiLog $log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'provider' => $log->provider,
                    'endpoint' => $log->endpoint,
                    'http_status' => $log->http_status,
                    'success' => $log->success,
                    'request_payload' => $this->protectSensitiveData($log->request_payload),
                    'response_body' => $this->protectSensitiveData($log->response_body),
                    'response_json' => $this->protectSensitiveData($log->response_json),
                    'error_message' => $this->protectSensitiveData($log->error_message),
                    'exception_message' => $this->protectSensitiveData($log->exception_message),
                    'created_at' => optional($log->created_at)->format('d/m/Y H:i:s'),
                ];
            });

        return response()->json([
            'invoice' => [
                'id' => $invoice->id,
                'document_type' => $invoice->document_type,
                'series' => $invoice->series,
                'number' => $invoice->number,
            ],
            'logs' => $logs,
        ]);
    }

    public function downloadPdf($id)
    {
        $invoice = Invoice::findOrFail($id);

        $path = $this->resolveInvoiceFilePath($invoice->pdf_path);

        if (!$path) {
            return redirect()
                ->back()
                ->with('error', 'El archivo PDF no está disponible.');
        }

        return response()->file(
            Storage::disk('public')->path($path)
        );
    }

    public function downloadXml($id)
    {
        $invoice = Invoice::findOrFail($id);

        $path = $this->resolveInvoiceFilePath($invoice->xml_path);

        if (!$path) {
            return redirect()
                ->back()
                ->with('error', 'El archivo XML no está disponible.');
        }

        $absolutePath = Storage::disk('public')->path($path);

        return response()->download($absolutePath);
    }

    private function resolveInvoiceFilePath(?string $path): ?string
    {
        $normalizedPath = $this->normalizePublicInvoicePath($path);

        if (
            !$normalizedPath ||
            !Storage::disk('public')->exists($normalizedPath)
        ) {
            return null;
        }

        return $normalizedPath;
    }

    private function normalizePublicInvoicePath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $path = trim(str_replace('\\', '/', $path));

        $path = preg_replace('#^https?://[^/]+/#', '', $path);

        $prefixes = [
            'storage/app/public/',
            'app/public/',
            'public/storage/',
            'storage/',
            'public/',
        ];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
                break;
            }
        }

        return ltrim($path, '/');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
 /*    public function store(Request $request)
    {
        $request->validate([

            'payment_id'      => 'required|exists:payments,id',
            'sale_id'         => 'required|exists:sales,id',
            'document_type'   => 'required',
            'series'          => 'required',
            'number'          => 'required',
            'company_id'      => 'nullable',
            'customer_document' => 'nullable',
            'customer_name'   => 'nullable',
            'customer_address' => 'nullable',
            'description'     => 'nullable',
            'legend'          => 'nullable',
            'subtotal'        => 'required|numeric',
            'tax_amount'      => 'required|numeric',
            'total_amount'    => 'required|numeric',

        ]);

        // Evitar duplicados
        $exists = Invoice::where('document_type', $request->document_type)
            ->where('series', $request->series)
            ->where('number', $request->number)
            ->first();

        if ($exists) {

            return response()->json([
                'success' => false,
                'message' => 'El comprobante ya existe.'
            ], 422);
        }

        $invoice = Invoice::create([

            'payment_id' => $request->payment_id,
            'sale_id'    => $request->sale_id,
            'company_id' => $request->company_id,

            'document_type' => $request->document_type,
            'series'        => $request->series,
            'number'        => $request->number,
            'issue_date'    => now()->format('Y-m-d'),

            'customer_document_type' => strlen($request->customer_document) == 11 ? '6' : '1',
            'customer_document'      => $request->customer_document,
            'customer_name'          => $request->customer_name,
            'customer_address'       => $request->customer_address,

            'concept' => $request->description,
            'legend'  => $request->legend,

            'currency'     => 'PEN',
            'subtotal'     => $request->subtotal,
            'tax_amount'   => $request->tax_amount,
            'total_amount' => $request->total_amount,

            'sunat_status' => 'pending',

            'created_by' => 1,

        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comprobante guardado correctamente.',
            'invoice_id' => $invoice->id
        ]);
    } */

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    /*  public function sendToSunat(Invoice $invoice, ApisPeruService $apisPeru)
    {


        try {

            $invoice->load([
                'company',
                'sale.customer',
                'sale.lot.block.project'
            ]);

            if (! $invoice->company) {
                return response()->json([
                    'success' => false,
                    'message' => 'La empresa emisora no fue encontrada.'
                ], 404);
            }

            $tipoDoc = $invoice->document_type === 'receipt' ? '03' : '01';
            $customerTipoDoc = $invoice->customer_document_type === '6' ? '6' : '1';

            $company = $invoice->company;

            $payload = [
                'ublVersion'    => '2.1',
                'tipoOperacion' => '0101',
                'tipoDoc'       => $tipoDoc,
                'serie'         => $invoice->series,
                'correlativo'   => $invoice->number,
                'fechaEmision'  => now()->format('Y-m-d\TH:i:sP'),
                'formaPago'     => [
                    'moneda' => 'PEN',
                    'tipo'   => 'Contado',
                ],
                'tipoMoneda' => 'PEN',
                'client' => [
                    'tipoDoc'   => $customerTipoDoc,
                    'numDoc'    => $invoice->customer_document,
                    'rznSocial' => $invoice->customer_name,
                    'address'   => [
                        'direccion'    => $invoice->customer_address ?? '',
                        'provincia'    => $invoice->customer_province ?? '',
                        'departamento' => $invoice->customer_department ?? '',
                        'distrito'     => $invoice->customer_district ?? '',
                        'ubigueo'      => $invoice->customer_ubigeo ?? '',
                    ],
                ],
                'company' => [
                    'ruc'             => $company->ruc,
                    'razonSocial'     => $company->business_name,
                    'nombreComercial' => $company->trade_name ?? '',
                    'address'         => [
                        'direccion'    => $company->address ?? '',
                        'provincia'    => 'SAN MARTIN',
                        'departamento' => 'SAN MARTIN',
                        'distrito'     => 'TARAPOTO',
                        'ubigueo'      => '220901',
                    ],
                ],
                'mtoOperExoneradas' => (float) $invoice->total_amount,
                'mtoIGV'            => 0,
                'valorVenta'        => (float) $invoice->subtotal,
                'totalImpuestos'    => 0,
                'subTotal'          => (float) $invoice->subtotal,
                'mtoImpVenta'       => (float) $invoice->total_amount,
                'details' => [
                    [
                        'codProducto'       => '00',
                        'unidad'            => 'NIU',
                        'descripcion'       => $invoice->concept,
                        'cantidad'          => 1,
                        'mtoValorUnitario'  => (float) $invoice->subtotal,
                        'mtoValorVenta'     => (float) $invoice->subtotal,
                        'mtoBaseIgv'        => (float) $invoice->subtotal,
                        'porcentajeIgv'     => 0,
                        'igv'               => 0,
                        'tipAfeIgv'         => 20,
                        'totalImpuestos'    => 0,
                        'mtoPrecioUnitario' => (float) $invoice->subtotal,
                    ]
                ],
                'legends' => [
                    [
                        'code'  => '1000',
                        'value' => (string) $invoice->total_amount,
                    ],
                    [
                        'code'  => '2001',
                        'value' => $invoice->legend,
                    ],
                ],
            ];

            $send = $apisPeru->sendInvoice($payload);

            if (! $send['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $send['message']
                ], 500);
            }

            $pdf = $apisPeru->getInvoicePdf($payload);
            $xml = $apisPeru->getInvoiceXml($payload);

            $pdfPath = null;
            $xmlPath = null;

            if ($pdf['success']) {
                $pdfFile = 'invoice_' . $invoice->series . '_' . $invoice->number . '.pdf';
                $pdfPath = 'invoices/' . $pdfFile;

                $pdfContent = $pdf['data'];
                Storage::disk('public')->put($pdfPath, $pdfContent);
            }

            if ($xml['success']) {
                $xmlFile = 'invoice_' . $invoice->series . '_' . $invoice->number . '.xml';
                $xmlPath = 'invoices/' . $xmlFile;

                $xmlContent = $xml['data'];
                Storage::disk('public')->put($xmlPath, $xmlContent);
            }

            $apiData = $send['data'];

            $invoice->update([
                'sunat_status' => 'accepted',
                'hash_code'    => $apiData['hash'] ?? null,
                'sunat_ticket'  => $apiData['ticket'] ?? null,
                'sunat_code'    => $apiData['code'] ?? null,
                'sunat_message' => $apiData['message'] ?? null,
                'pdf_path'      => $pdfPath,
                'xml_path'      => $xmlPath,
                'updated_by'    => Auth::id() ?? 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Boleta emitida correctamente.',
                'data'    => $apiData,
                'pdf_url' => $pdfPath
                    ? asset('storage/' . $pdfPath)
                    : null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    } */


    public function generate(Request $request, ApisPeruService $apisPeru)
    {
        $payload = null;
        $apiLog = null;
        $invoice = null;
        $pdfPath = null;
        $xmlPath = null;
        $data = $request->validate([
            'payment_id'           => 'required|exists:payments,id',
            'sale_id'              => 'required|exists:sales,id',
            'company_id'           => 'required|exists:companies,id',
            'document_type' => 'required|in:receipt,invoice,sale_note',
            'series'               => 'required|string|max:10',
            'number'               => 'required',
            'customer_document'    => 'required|string|max:20',
            'customer_name'        => 'required|string|max:255',
            'customer_address'     => 'nullable|string|max:255',
            'customer_department'  => 'nullable|string|max:100',
            'customer_province'    => 'nullable|string|max:100',
            'customer_district'    => 'nullable|string|max:100',
            'customer_ubigeo'      => 'nullable|string|max:10',
            'description'          => 'nullable|string',
            'legend'               => 'nullable|string',
            'subtotal'             => 'required|numeric',
            'tax_amount'           => 'required|numeric',
            'total_amount'         => 'required|numeric',
        ]);

        // La serie tributaria la decide el backend. El valor enviado por el
        // frontend es únicamente informativo y nunca puede forzar B001/F001.
        $data['series'] = $this->seriesForDocumentType($data['document_type']);

        try {
            $payment = Payment::with([
                'sale.customer',
                'sale.lot.block.project',
                'details.paymentSchedule',
                'invoice'
            ])->findOrFail($data['payment_id']);

            $data['description'] = $this->buildPaymentDescription($payment);

            $blockingInvoice = $this->blockingSunatInvoiceForPayment(
                (int) $data['payment_id']
            );

            if ($blockingInvoice) {
                return response()->json([
                    'success' => false,
                    'message' => $this->blockingSunatInvoiceMessage($blockingInvoice),
                ], 422);
            }

            $company = Company::where('id', $data['company_id'])
                ->where('status', 1)
                ->first();

            if (! $company) {
                return response()->json([
                    'success' => false,
                    'message' => 'La empresa seleccionada no existe o está inactiva.'
                ], 404);
            }
            try {
                $apisPeru->useCompanyRuc($company->ruc);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            // =========================================
            // ¿ES NOTA DE VENTA?
            // =========================================

            if ($data['document_type'] == 'sale_note') {
                DB::beginTransaction();

                try {
                    Company::whereKey($data['company_id'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    $data['number'] = $this->allocateNextInvoiceNumber(
                        (int) $data['company_id'],
                        $data['document_type'],
                        $data['series']
                    );

                    $invoice = Invoice::updateOrCreate(
                        [
                            'payment_id' => $data['payment_id']
                        ],
                        [
                            'sale_id'    => $data['sale_id'],
                            'company_id' => $data['company_id'],

                            'document_type' => 'sale_note',
                            'series'        => $data['series'],
                            'number'        => $data['number'],
                            'issue_date'    => now()->format('Y-m-d'),

                            'customer_document_type' =>
                            strlen($data['customer_document']) == 11 ? '6' : '1',

                            'customer_document'   => $data['customer_document'],
                            'customer_name'       => $data['customer_name'],
                            'customer_address'    => $data['customer_address'],

                            'customer_department' => $data['customer_department'],
                            'customer_province'   => $data['customer_province'],
                            'customer_district'   => $data['customer_district'],
                            'customer_ubigeo'     => $data['customer_ubigeo'],

                            'concept'      => $data['description'],
                            'legend'       => null,
                            'currency'     => 'PEN',
                            'subtotal'     => $data['subtotal'],
                            'tax_amount'   => 0,
                            'total_amount' => $data['total_amount'],

                            'sunat_status' => 'accepted',

                            'hash_code'     => null,
                            'sunat_ticket'  => null,
                            'sunat_code'    => null,
                            'sunat_message' => null,
                            'pdf_path'      => null,
                            'xml_path'      => null,

                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]
                    );

                    DB::commit();
                } catch (\Throwable $e) {
                    DB::rollBack();
                    throw $e;
                }

                return response()->json([
                    'success'    => true,
                    'message'    => 'Nota de venta emitida correctamente.',
                    'ticket_url' => route('admin.invoices.ticket', $invoice->id),
                    'invoice_id' => $invoice->id,
                ]);
            }

            $tipoDoc = $data['document_type'] === 'receipt' ? '03' : '01';
            if ($data['document_type'] === 'invoice') {

                if (strlen($data['customer_document']) != 11) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Para emitir una factura el cliente debe tener RUC.'
                    ], 422);
                }

                $customerTipoDoc = '6';
            } else {

                $customerTipoDoc = strlen($data['customer_document']) == 11 ? '6' : '1';
            }

            DB::beginTransaction();

            try {
                Company::whereKey($data['company_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $blockingInvoice = $this->blockingSunatInvoiceForPayment(
                    (int) $data['payment_id']
                );

                if ($blockingInvoice) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => $this->blockingSunatInvoiceMessage($blockingInvoice),
                    ], 422);
                }

                $data['number'] = $this->allocateNextInvoiceNumber(
                    (int) $data['company_id'],
                    $data['document_type'],
                    $data['series']
                );

                $invoice = Invoice::create([
                    'payment_id' => $data['payment_id'],
                    'sale_id'    => $data['sale_id'],
                    'company_id' => $data['company_id'],

                    'document_type' => $data['document_type'],
                    'series'        => $data['series'],
                    'number'        => $data['number'],
                    'issue_date'    => now()->format('Y-m-d'),

                    'customer_document_type' => $customerTipoDoc,
                    'customer_document'      => $data['customer_document'],
                    'customer_name'          => $data['customer_name'],
                    'customer_address'       => $data['customer_address'],

                    'customer_department' => $data['customer_department'],
                    'customer_province'   => $data['customer_province'],
                    'customer_district'   => $data['customer_district'],
                    'customer_ubigeo'     => $data['customer_ubigeo'],

                    'concept'      => $data['description'],
                    'legend'       => $data['legend'],
                    'currency'     => 'PEN',
                    'subtotal'     => $data['subtotal'],
                    'tax_amount'   => $data['tax_amount'],
                    'total_amount' => $data['total_amount'],

                    'sunat_status' => 'pending',

                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            $payload = [
                'ublVersion'    => '2.1',
                'tipoOperacion' => '0101',
                'tipoDoc'       => $tipoDoc,
                'serie'         => $data['series'],
                'correlativo'   => $data['number'],
                'fechaEmision'  => now()->format('Y-m-d\TH:i:sP'),
                'formaPago'     => [
                    'moneda' => 'PEN',
                    'tipo'   => 'Contado',
                ],
                'tipoMoneda' => 'PEN',
                'client' => [
                    'tipoDoc'   => $customerTipoDoc,
                    'numDoc'    => $data['customer_document'],
                    'rznSocial' => $data['customer_name'],
                    'address'   => [
                        'direccion'    => $data['customer_address'] ?? '',
                        'provincia'    => $data['customer_province'] ?? '',
                        'departamento' => $data['customer_department'] ?? '',
                        'distrito'     => $data['customer_district'] ?? '',
                        'ubigueo'      => $data['customer_ubigeo'] ?? '',
                    ],
                ],
                'company' => [
                    'ruc'             => $company->ruc,
                    'razonSocial'     => $company->business_name,
                    'nombreComercial' => $company->trade_name ?? '',
                    'address'         => [
                        'direccion'    => $company->address ?? '',
                        'provincia'    => 'SAN MARTIN',
                        'departamento' => 'SAN MARTIN',
                        'distrito'     => 'TARAPOTO',
                        'ubigueo'      => '220901',
                    ],
                ],
                'mtoOperExoneradas' => (float) $data['total_amount'],
                'mtoIGV'            => 0,
                'valorVenta'        => (float) $data['subtotal'],
                'totalImpuestos'    => 0,
                'subTotal'          => (float) $data['subtotal'],
                'mtoImpVenta'       => (float) $data['total_amount'],
                'details' => [
                    [
                        'codProducto'       => '00',
                        'unidad'            => 'NIU',
                        'descripcion'       => $data['description'] ?: 'PAGO',
                        'cantidad'          => 1,
                        'mtoValorUnitario'  => (float) $data['subtotal'],
                        'mtoValorVenta'     => (float) $data['subtotal'],
                        'mtoBaseIgv'        => (float) $data['subtotal'],
                        'porcentajeIgv'     => 0,
                        'igv'               => 0,
                        'tipAfeIgv'         => 20,
                        'totalImpuestos'    => 0,
                        'mtoPrecioUnitario' => (float) $data['subtotal'],
                    ]
                ],
                'legends' => [
                    [
                        'code'  => '1000',
                        'value' => $this->numeroALetras($data['total_amount']),
                    ],
                    [
                        'code'  => '2001',
                        'value' => $data['legend'] ?? '',
                    ],
                ],
            ];

            $send = $apisPeru->sendInvoice($payload);
            $sunat = $this->extractSunatOutcome($send);

            // En auditoría, success significa aceptación tributaria real,
            // no solamente una respuesta HTTP exitosa del proveedor.
            $auditSend = $send;
            $auditSend['success'] = $sunat['confirmed'] && $sunat['accepted'];

            if (! $auditSend['success'] && empty($auditSend['error_message'])) {
                $auditSend['error_message'] = $sunat['message'];
            }

            $apiLog = $this->storeApiLog($invoice, $payload, $auditSend);

            if (! $sunat['confirmed']) {
                // Resultado ambiguo: se mantiene PENDING y se bloquea el reintento
                // hasta revisar la Respuesta API. Esto evita duplicar un CPE que
                // pudo haber llegado a SUNAT aunque se haya perdido la respuesta.
                $invoice->update([
                    'sunat_message' => $sunat['message'],
                    'updated_by' => Auth::id(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $sunat['message'],
                ], 502);
            }

            if (! $sunat['accepted']) {
                // Rechazo confirmado: conservar el intento para auditoría,
                // consumir su correlativo y permitir un nuevo intento con el
                // siguiente número.
                $invoice->update([
                    'sunat_status' => 'rejected',
                    'hash_code' => $send['data']['hash'] ?? null,
                    'sunat_code' => $sunat['code'],
                    'sunat_message' => $sunat['message'],
                    'updated_by' => Auth::id(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'SUNAT rechazó el comprobante: ' . $sunat['message'],
                ], 422);
            }

            // SUNAT ya confirmó la aceptación. Persistimos primero ese hecho.
            // PDF/XML son archivos auxiliares y un fallo al descargarlos no debe
            // convertir un CPE aceptado en pendiente ni habilitar una reemisión.
            DB::transaction(function () use ($invoice, $send, $sunat) {
                $lockedInvoice = Invoice::query()
                    ->whereKey($invoice->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedInvoice->update([
                    'sunat_status' => 'accepted',
                    'hash_code' => $send['data']['hash'] ?? null,
                    'sunat_ticket' => $send['data']['ticket'] ?? null,
                    'sunat_code' => $sunat['code'],
                    'sunat_message' => $sunat['message'],
                    'updated_by' => Auth::id(),
                ]);
            });

            $prefix = $data['document_type'] === 'invoice'
                ? 'FACTURA'
                : 'BOLETA';

            $artifactWarnings = [];

            try {
                $pdf = $apisPeru->getInvoicePdf($payload);

                if ($pdf['success'] ?? false) {
                    $pdfFile = $prefix . '_' . $data['series'] . '-' . $data['number'] . '.pdf';
                    $pdfPath = 'invoices/' . $pdfFile;
                    Storage::disk('public')->put($pdfPath, $pdf['data']);
                } else {
                    $artifactWarnings[] = 'No se pudo obtener el PDF.';
                }
            } catch (\Throwable $e) {
                $artifactWarnings[] = 'No se pudo obtener el PDF.';

                Log::warning('Comprobante aceptado, pero no se pudo guardar su PDF.', [
                    'invoice_id' => $invoice->id,
                    'exception' => $e->getMessage(),
                ]);
            }

            try {
                $xml = $apisPeru->getInvoiceXml($payload);

                if ($xml['success'] ?? false) {
                    $xmlFile = $prefix . '_' . $data['series'] . '-' . $data['number'] . '.xml';
                    $xmlPath = 'invoices/' . $xmlFile;
                    Storage::disk('public')->put($xmlPath, $xml['data']);
                } else {
                    $artifactWarnings[] = 'No se pudo obtener el XML.';
                }
            } catch (\Throwable $e) {
                $artifactWarnings[] = 'No se pudo obtener el XML.';

                Log::warning('Comprobante aceptado, pero no se pudo guardar su XML.', [
                    'invoice_id' => $invoice->id,
                    'exception' => $e->getMessage(),
                ]);
            }

            $invoice->update([
                'pdf_path' => $pdfPath,
                'xml_path' => $xmlPath,
                'updated_by' => Auth::id(),
            ]);

            $message = empty($artifactWarnings)
                ? 'Comprobante aceptado por SUNAT correctamente.'
                : 'Comprobante aceptado por SUNAT. ' . implode(' ', $artifactWarnings);

            return response()->json([
                'success'    => true,
                'message'    => $message,
                'pdf_url'    => $pdfPath
                    ? route('admin.invoices.downloadPdf', $invoice->id)
                    : null,
                'xml_url'    => $xmlPath
                    ? route('admin.invoices.downloadXml', $invoice->id)
                    : null,
                'ticket_url' => route('admin.invoices.ticket', $invoice->id),
                'invoice_id' => $invoice->id,
            ]);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if ($payload && ! $apiLog) {
                $this->storeApiLog($invoice, $payload, [
                    'success' => false,
                    'endpoint' => null,
                    'http_status' => null,
                    'response_body' => null,
                    'response_json' => null,
                    'error_message' => null,
                    'exception_message' => $e->getMessage(),
                ]);
            }

            if (!empty($pdfPath) && Storage::disk('public')->exists($pdfPath)) {
                Storage::disk('public')->delete($pdfPath);
            }

            if (!empty($xmlPath) && Storage::disk('public')->exists($xmlPath)) {
                Storage::disk('public')->delete($xmlPath);
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function storeApiLog(?Invoice $invoice, array $payload, array $result): ?InvoiceApiLog
    {
        try {
            $safePayload = $this->sanitizeSensitiveValues($payload);
            $responseBody = $this->protectSensitiveData($result['response_body'] ?? null);
            $responseJson = $result['response_json'] ?? null;

            return InvoiceApiLog::create([
                'invoice_id' => $invoice?->id,
                'action' => 'send',
                'provider' => 'APISPERU',
                'endpoint' => $result['endpoint'] ?? null,
                'http_status' => $result['http_status'] ?? null,
                'success' => (bool) ($result['success'] ?? false),
                'request_payload' => json_encode($safePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'response_body' => $responseBody,
                'response_json' => $responseJson === null
                    ? null
                    : json_encode($this->sanitizeSensitiveValues($responseJson), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'error_message' => $this->protectSensitiveData($result['error_message'] ?? null),
                'exception_message' => $this->protectSensitiveData($result['exception_message'] ?? null),
                'created_by' => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            Log::error('No se pudo registrar la auditoría de APISPERU.', [
                'invoice_id' => $invoice?->id,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function sanitizeSensitiveValues($value)
    {
        if (! is_array($value)) {
            return is_string($value) ? $this->protectSensitiveData($value) : $value;
        }

        foreach ($value as $key => $item) {
            if (preg_match('/authorization|token|bearer|api[_-]?key/i', (string) $key)) {
                $value[$key] = '[PROTECTED]';
                continue;
            }

            $value[$key] = $this->sanitizeSensitiveValues($item);
        }

        return $value;
    }

    private function protectSensitiveData(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $patterns = [
            '/("?(?:authorization|token|api[_-]?key)"?\s*[:=]\s*")([^"]*)(")/i',
            '/(bearer\s+)[A-Za-z0-9._~+\/-]+=*/i',
        ];

        return preg_replace($patterns, ['$1[PROTECTED]$3', '$1[PROTECTED]'], $value);
    }


    private function numeroALetras($numero, $moneda = 'SOLES', $centimos = '/100')
    {
        $unidad = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        $especiales = ['ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
        $miles = ['', 'MIL', 'MILLON', 'MIL MILLONES', 'BILLON'];

        $num = explode('.', number_format($numero, 2, '.', ''));
        $entero = str_pad($num[0], 18, '0', STR_PAD_LEFT);
        $decimal = $num[1];

        $letras = '';

        for ($i = 0; $i < 6; $i++) {

            $seccion = substr($entero, $i * 3, 3);
            $cientos = (int)$seccion[0];
            $diez = (int)$seccion[1];
            $unidadNum = (int)$seccion[2];

            if ($cientos > 0) {
                $letras .= ' ' . $centenas[$cientos];
            }

            if ($diez > 1) {
                $letras .= ' ' . $decenas[$diez];
                if ($unidadNum > 0) {
                    $letras .= ' Y ' . $unidad[$unidadNum];
                }
            } elseif ($diez == 1) {
                if ($unidadNum > 0) {
                    $letras .= ' ' . $especiales[$unidadNum - 1];
                } else {
                    $letras .= ' DIEZ';
                }
            } elseif ($unidadNum > 0) {
                $letras .= ' ' . $unidad[$unidadNum];
            }

            if ((int)$seccion > 0) {
                $letras .= ' ' . $miles[5 - $i];
            }
        }

        $letras = trim(preg_replace('/\s+/', ' ', $letras));

        return 'SON ' . $letras . ' CON ' . ($decimal ?: '00') . $centimos . ' ' . $moneda;
    }


    public function ticket(Invoice $invoice)
    {
        $invoice->load([
            'company',
            'sale.customer'
        ]);

        $montoLetras = $this->numeroALetras(
            $invoice->total_amount
        );

        return view(
            'admin.payments.ticket',
            compact(
                'invoice',
                'montoLetras'
            )
        );
    }
}
