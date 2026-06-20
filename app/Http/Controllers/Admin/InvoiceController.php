<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Company;

use App\Services\ApisPeruService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller


{

    public function getNextNumber(Request $request)
    {
        $documentType = $request->document_type;

        $companyId = $request->company_id;

        switch ($documentType) {
            case 'invoice':
                $series = 'F001';
                break;

            case 'sale_note':
                $series = 'NV001';
                break;

            default:
                $series = 'B001';
                break;
        }

        $query = Invoice::where('document_type', $documentType)
            ->where('series', $series);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $lastInvoice = $query
            ->orderByDesc('number')
            ->first();

        $number = $lastInvoice
            ? ((int) $lastInvoice->number + 1)
            : 1;

        return response()->json([
            'series' => $series,
            'number' => $number
        ]);
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
        $payment->load([
            'details.paymentSchedule',
            'sale.lot.block.project'
        ]);

        // ==========================================
        // CUOTAS
        // ==========================================

        $installments = $payment->details
            ->sortBy(function ($detail) {
                return $detail->paymentSchedule->installment_number;
            })
            ->map(function ($detail) {

                $number = $detail->paymentSchedule->installment_number;

                if ((int)$number === 0) {
                    return 'CUOTA INICIAL';
                }

                return 'CUOTA #' . $number;
            })
            ->implode(', ');

        // ==========================================
        // DATOS DEL LOTE
        // ==========================================

        $lot = $payment->sale->lot;
        $block = $lot?->block;
        $project = $block?->project;

        $description =
            $installments .
            ' ' .
            ($block->name ?? '') .
            ' - LT' .
            ($lot->number ?? '') .
            ' ' .
            strtoupper($project->name ?? '');

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
                'description' => trim($description),
                'legend' => $legend
            ]
        ]);
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

        try {
            $payment = Payment::with([
                'sale.customer',
                'sale.lot.block.project',
                'invoice'
            ])->findOrFail($data['payment_id']);

            $hasSunat = Invoice::where(
                'payment_id',
                $data['payment_id']
            )
                ->whereIn(
                    'document_type',
                    ['receipt', 'invoice']
                )
                ->exists();

            if ($hasSunat) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este pago ya tiene un comprobante SUNAT emitido.'
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

            if (! $send['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $send['message']
                ], 500);
            }

            $pdf = $apisPeru->getInvoicePdf($payload);
            if (! $pdf['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $pdf['message']
                ], 500);
            }

            $xml = $apisPeru->getInvoiceXml($payload);
            if (! $xml['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $xml['message']
                ], 500);
            }

            $prefix = $data['document_type'] === 'invoice'
                ? 'FACTURA'
                : 'BOLETA';

            $pdfFile = $prefix . '_' . $data['series'] . '-' . $data['number'] . '.pdf';
            $xmlFile = $prefix . '_' . $data['series'] . '-' . $data['number'] . '.xml';

            $pdfPath = 'invoices/' . $pdfFile;
            $xmlPath = 'invoices/' . $xmlFile;

            Storage::disk('public')->put($pdfPath, $pdf['data']);
            Storage::disk('public')->put($xmlPath, $xml['data']);

            DB::beginTransaction();

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

                'sunat_status'  => 'accepted',
                'hash_code'     => $send['data']['hash'] ?? null,
                'sunat_ticket'  => $send['data']['ticket'] ?? null,
                'sunat_code'    => $send['data']['code'] ?? null,
                'sunat_message' => $send['data']['message'] ?? null,

                'pdf_path'   => $pdfPath,
                'xml_path'   => $xmlPath,

                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            $documentName = $data['document_type'] === 'invoice'
                ? 'Factura'
                : 'Boleta';

            return response()->json([
                'success'    => true,
                'message'    => 'Comprobante emitido correctamente.',
                'pdf_url'    => asset('storage/' . $pdfPath),
                'xml_url'    => asset('storage/' . $xmlPath),
                'ticket_url' => route('admin.invoices.ticket', $invoice->id),
                'invoice_id' => $invoice->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

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
