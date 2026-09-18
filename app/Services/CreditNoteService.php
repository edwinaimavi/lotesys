<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceApiLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CreditNoteService
{
    private const RECEIPT_CREDIT_NOTE_SERIES = 'BC01';

    private const INVOICE_CREDIT_NOTE_SERIES = 'FC01';

    public function __construct(private ApisPeruService $apisPeru)
    {
    }

    /**
     * Emite una Nota de Crédito por anulación de la operación (motivo SUNAT 01)
     * para un comprobante aceptado. La anulación financiera del pago se realiza
     * fuera de este servicio y solamente después de obtener confirmación SUNAT.
     */
    public function annulAcceptedInvoice(Invoice $originalInvoice, string $reason): array
    {
        $reason = trim($reason);

        if ($reason === '') {
            return [
                'success' => false,
                'message' => 'Debe indicar el motivo o sustento de la anulación.',
            ];
        }

        $originalInvoice->loadMissing(['company', 'payment', 'sale']);

        if (! in_array($originalInvoice->document_type, ['receipt', 'invoice'], true)) {
            return [
                'success' => false,
                'message' => 'El comprobante asociado no admite Nota de Crédito SUNAT.',
            ];
        }

        $acceptedCreditNote = Invoice::query()
            ->where('related_invoice_id', $originalInvoice->id)
            ->where('document_type', 'credit_note')
            ->where('sunat_status', 'accepted')
            ->latest('id')
            ->first();

        // Idempotencia: si SUNAT ya aceptó la Nota de Crédito, no se emite otra.
        if ($acceptedCreditNote) {
            $this->markOriginalAsVoided($originalInvoice);

            return [
                'success' => true,
                'credit_note' => $acceptedCreditNote,
                'message' => 'La Nota de Crédito ya había sido aceptada por SUNAT.',
                'already_existed' => true,
            ];
        }

        $pendingCreditNote = Invoice::query()
            ->where('related_invoice_id', $originalInvoice->id)
            ->where('document_type', 'credit_note')
            ->where('sunat_status', 'pending')
            ->latest('id')
            ->first();

        if ($pendingCreditNote) {
            return [
                'success' => false,
                'message' => 'Existe una Nota de Crédito pendiente (' .
                    $pendingCreditNote->series . '-' . $pendingCreditNote->number .
                    '). Revise la respuesta de APISPERU antes de volver a intentar.',
            ];
        }

        if ($originalInvoice->sunat_status !== 'accepted') {
            return [
                'success' => false,
                'message' => 'Solo se puede emitir Nota de Crédito para un comprobante SUNAT aceptado.',
            ];
        }

        if (! $originalInvoice->company) {
            return [
                'success' => false,
                'message' => 'No se encontró la empresa emisora del comprobante.',
            ];
        }

        $creditNote = $this->reserveCreditNote($originalInvoice, $reason);
        $payload = $this->buildCreditNotePayload($originalInvoice, $creditNote, $reason);

        $this->apisPeru->useCompanyRuc($originalInvoice->company->ruc);

        $send = $this->apisPeru->sendNote($payload);
        $sunat = $this->extractSunatOutcome($send);

        // Para la auditoría de la Nota de Crédito, success representa la
        // aceptación tributaria, no solamente un HTTP 200 del proveedor.
        $auditSend = $send;
        $auditSend['success'] = $sunat['confirmed'] && $sunat['accepted'];
        if (! $auditSend['success'] && empty($auditSend['error_message'])) {
            $auditSend['error_message'] = $sunat['message'];
        }
        $this->storeApiLog($creditNote, 'credit_note_send', $payload, $auditSend);

        if (! $sunat['confirmed']) {
            $creditNote->update([
                'sunat_message' => $sunat['message'],
                'updated_by' => Auth::id(),
            ]);

            return [
                'success' => false,
                'message' => $sunat['message'],
                'credit_note' => $creditNote->fresh(),
            ];
        }

        if (! $sunat['accepted']) {
            $creditNote->update([
                'sunat_status' => 'rejected',
                'sunat_code' => $sunat['code'],
                'sunat_message' => $sunat['message'],
                'updated_by' => Auth::id(),
            ]);

            return [
                'success' => false,
                'message' => 'SUNAT rechazó la Nota de Crédito: ' . $sunat['message'],
                'credit_note' => $creditNote->fresh(),
            ];
        }

        // SUNAT ya confirmó la Nota de Crédito. Persistimos primero ese hecho
        // tributario; PDF/XML son representaciones auxiliares y no deben impedir
        // la reversión financiera si su descarga falla.
        DB::transaction(function () use (
            $originalInvoice,
            $creditNote,
            $send,
            $sunat
        ) {
            $lockedOriginal = Invoice::query()
                ->whereKey($originalInvoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedCreditNote = Invoice::query()
                ->whereKey($creditNote->id)
                ->lockForUpdate()
                ->firstOrFail();

            $responseData = $send['data'] ?? [];

            $lockedCreditNote->update([
                'sunat_status' => 'accepted',
                'hash_code' => $responseData['hash'] ?? null,
                'sunat_ticket' => $responseData['ticket'] ?? null,
                'sunat_code' => $sunat['code'],
                'sunat_message' => $sunat['message'],
                'updated_by' => Auth::id(),
            ]);

            $lockedOriginal->update([
                'sunat_status' => 'voided',
                'voided_at' => now(),
                'voided_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        });

        $pdfPath = null;
        $xmlPath = null;

        try {
            $pdf = $this->apisPeru->getNotePdf($payload);
            $this->storeApiLog($creditNote, 'credit_note_pdf', $payload, $pdf, false);

            if ($pdf['success'] ?? false) {
                $pdfPath = 'invoices/NOTA_CREDITO_' .
                    $creditNote->series . '-' . $creditNote->number . '.pdf';
                Storage::disk('public')->put($pdfPath, $pdf['data']);
            }
        } catch (\Throwable $e) {
            Log::warning('Nota de Crédito aceptada, pero no se pudo guardar su PDF.', [
                'invoice_id' => $creditNote->id,
                'exception' => $e->getMessage(),
            ]);
        }

        try {
            $xml = $this->apisPeru->getNoteXml($payload);
            $this->storeApiLog($creditNote, 'credit_note_xml', $payload, $xml, false);

            if ($xml['success'] ?? false) {
                $xmlPath = 'invoices/NOTA_CREDITO_' .
                    $creditNote->series . '-' . $creditNote->number . '.xml';
                Storage::disk('public')->put($xmlPath, $xml['data']);
            }
        } catch (\Throwable $e) {
            Log::warning('Nota de Crédito aceptada, pero no se pudo guardar su XML.', [
                'invoice_id' => $creditNote->id,
                'exception' => $e->getMessage(),
            ]);
        }

        if ($pdfPath || $xmlPath) {
            $creditNote->update([
                'pdf_path' => $pdfPath,
                'xml_path' => $xmlPath,
                'updated_by' => Auth::id(),
            ]);
        }

        return [
            'success' => true,
            'credit_note' => $creditNote->fresh(),
            'message' => 'Nota de Crédito aceptada por SUNAT.',
            'already_existed' => false,
        ];
    }

    private function reserveCreditNote(Invoice $originalInvoice, string $reason): Invoice
    {
        $series = $originalInvoice->document_type === 'invoice'
            ? self::INVOICE_CREDIT_NOTE_SERIES
            : self::RECEIPT_CREDIT_NOTE_SERIES;

        return DB::transaction(function () use ($originalInvoice, $reason, $series) {
            Company::query()
                ->whereKey($originalInvoice->company_id)
                ->lockForUpdate()
                ->firstOrFail();

            Invoice::query()
                ->where('company_id', $originalInvoice->company_id)
                ->where('document_type', 'credit_note')
                ->where('series', $series)
                ->lockForUpdate()
                ->get(['id']);

            $maxNumber = Invoice::query()
                ->where('company_id', $originalInvoice->company_id)
                ->where('document_type', 'credit_note')
                ->where('series', $series)
                ->max(DB::raw('CAST(number AS UNSIGNED)'));

            $number = ((int) $maxNumber) + 1;

            $exists = Invoice::query()
                ->where('company_id', $originalInvoice->company_id)
                ->where('document_type', 'credit_note')
                ->where('series', $series)
                ->where('number', (string) $number)
                ->exists();

            if ($exists) {
                throw new RuntimeException(
                    "Ya existe la Nota de Crédito {$series}-{$number}. Intente nuevamente."
                );
            }

            return Invoice::create([
                'payment_id' => $originalInvoice->payment_id,
                'sale_id' => $originalInvoice->sale_id,
                'company_id' => $originalInvoice->company_id,
                'related_invoice_id' => $originalInvoice->id,
                'document_type' => 'credit_note',
                'series' => $series,
                'number' => $number,
                'issue_date' => now()->toDateString(),
                'customer_document_type' => $originalInvoice->customer_document_type,
                'customer_document' => $originalInvoice->customer_document,
                'customer_name' => $originalInvoice->customer_name,
                'customer_address' => $originalInvoice->customer_address,
                'customer_department' => $originalInvoice->customer_department,
                'customer_province' => $originalInvoice->customer_province,
                'customer_district' => $originalInvoice->customer_district,
                'customer_ubigeo' => $originalInvoice->customer_ubigeo,
                'concept' => 'ANULACIÓN DE LA OPERACIÓN ' .
                    $originalInvoice->series . '-' . $originalInvoice->number,
                'legend' => $originalInvoice->legend,
                'currency' => $originalInvoice->currency ?: 'PEN',
                'subtotal' => $originalInvoice->subtotal,
                'tax_amount' => $originalInvoice->tax_amount,
                'total_amount' => $originalInvoice->total_amount,
                'sunat_status' => 'pending',
                'credit_note_reason_code' => '01',
                'credit_note_reason' => $reason,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        });
    }

    private function buildCreditNotePayload(
        Invoice $originalInvoice,
        Invoice $creditNote,
        string $reason
    ): array {
        $source = $this->loadOriginalInvoicePayload($originalInvoice);
        $originalInvoice->loadMissing('company');
        $company = $originalInvoice->company;

        $client = $source['client'] ?? [
            'tipoDoc' => $originalInvoice->customer_document_type ?: '1',
            'numDoc' => $originalInvoice->customer_document,
            'rznSocial' => $originalInvoice->customer_name,
            'address' => [
                'direccion' => $originalInvoice->customer_address ?? '',
                'provincia' => $originalInvoice->customer_province ?? '',
                'departamento' => $originalInvoice->customer_department ?? '',
                'distrito' => $originalInvoice->customer_district ?? '',
                'ubigueo' => $originalInvoice->customer_ubigeo ?? '',
            ],
        ];

        $companyPayload = $source['company'] ?? [
            'ruc' => $company->ruc,
            'razonSocial' => $company->business_name,
            'nombreComercial' => $company->trade_name ?? '',
            'address' => [
                'direccion' => $company->address ?? '',
                'provincia' => 'SAN MARTIN',
                'departamento' => 'SAN MARTIN',
                'distrito' => 'TARAPOTO',
                'ubigueo' => '220901',
            ],
        ];

        $details = $source['details'] ?? [[
            'codProducto' => '00',
            'unidad' => 'NIU',
            'descripcion' => $originalInvoice->concept ?: 'PAGO',
            'cantidad' => 1,
            'mtoValorUnitario' => (float) $originalInvoice->subtotal,
            'mtoValorVenta' => (float) $originalInvoice->subtotal,
            'mtoBaseIgv' => (float) $originalInvoice->subtotal,
            'porcentajeIgv' => 0,
            'igv' => 0,
            'tipAfeIgv' => 20,
            'totalImpuestos' => 0,
            'mtoPrecioUnitario' => (float) $originalInvoice->subtotal,
        ]];

        $payload = [
            'ublVersion' => $source['ublVersion'] ?? '2.1',
            'tipoDoc' => '07',
            'serie' => $creditNote->series,
            'correlativo' => (string) $creditNote->number,
            'fechaEmision' => now()->format('Y-m-d\TH:i:sP'),
            'tipoMoneda' => $source['tipoMoneda'] ?? ($originalInvoice->currency ?: 'PEN'),
            'client' => $client,
            'company' => $companyPayload,
            'codMotivo' => '01',
            'desMotivo' => $reason,
            'tipDocAfectado' => $originalInvoice->document_type === 'invoice' ? '01' : '03',
            'numDocfectado' => $originalInvoice->series . '-' . $originalInvoice->number,
            'details' => $details,
        ];

        // SUNAT no admite PaymentTerms/FormaPago en la Nota de Crédito.
        // No copiar formaPago ni cuotas del comprobante original: hacerlo genera
        // cac:PaymentTerms/cbc:PaymentMeansID (por ejemplo, "Contado") y SUNAT
        // rechaza la NC con código 3246.
        $copyKeys = [
            'sumOtrosCargos',
            'mtoOperGravadas',
            'mtoOperInafectas',
            'mtoOperExoneradas',
            'mtoOperExportacion',
            'mtoIGV',
            'mtoIGVGratuitas',
            'mtoISC',
            'mtoOtrosTributos',
            'icbper',
            'valorVenta',
            'subTotal',
            'mtoImpVenta',
            'legends',
            'guias',
            'relDocs',
            'compra',
            'mtoBaseIsc',
            'mtoBaseOth',
            'totalImpuestos',
            'mtoOperGratuitas',
            'perception',
            'mtoBaseIvap',
            'mtoIvap',
            'redondeo',
            'name',
        ];

        foreach ($copyKeys as $key) {
            if (array_key_exists($key, $source)) {
                $payload[$key] = $source[$key];
            }
        }

        // Fallback exacto para los comprobantes inmobiliarios actuales (exonerados).
        if (! array_key_exists('mtoImpVenta', $payload)) {
            $payload['mtoOperExoneradas'] = (float) $originalInvoice->total_amount;
            $payload['mtoIGV'] = (float) $originalInvoice->tax_amount;
            $payload['valorVenta'] = (float) $originalInvoice->subtotal;
            $payload['totalImpuestos'] = (float) $originalInvoice->tax_amount;
            $payload['subTotal'] = (float) $originalInvoice->subtotal;
            $payload['mtoImpVenta'] = (float) $originalInvoice->total_amount;
        }

        return $payload;
    }

    private function loadOriginalInvoicePayload(Invoice $invoice): array
    {
        $requestPayload = InvoiceApiLog::query()
            ->where('invoice_id', $invoice->id)
            ->where('action', 'send')
            ->whereNotNull('request_payload')
            ->latest('id')
            ->value('request_payload');

        if (! $requestPayload) {
            return [];
        }

        $decoded = json_decode($requestPayload, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function extractSunatOutcome(array $result): array
    {
        if (! ($result['success'] ?? false)) {
            return [
                'confirmed' => false,
                'accepted' => false,
                'code' => null,
                'message' => $result['message'] ??
                    'No se pudo confirmar la Nota de Crédito con APISPERU/SUNAT.',
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
                    'El pago no fue anulado para evitar inconsistencias.',
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

    private function markOriginalAsVoided(Invoice $invoice): void
    {
        if ($invoice->sunat_status === 'voided') {
            return;
        }

        $invoice->update([
            'sunat_status' => 'voided',
            'voided_at' => $invoice->voided_at ?: now(),
            'voided_by' => $invoice->voided_by ?: Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    private function storeApiLog(
        Invoice $invoice,
        string $action,
        array $payload,
        array $result,
        bool $storeResponseBody = true
    ): ?InvoiceApiLog {
        try {
            $safePayload = $this->sanitizeSensitiveValues($payload);
            $responseJson = $result['response_json'] ?? null;

            return InvoiceApiLog::create([
                'invoice_id' => $invoice->id,
                'action' => $action,
                'provider' => 'APISPERU',
                'endpoint' => $result['endpoint'] ?? null,
                'http_status' => $result['http_status'] ?? null,
                'success' => (bool) ($result['success'] ?? false),
                'request_payload' => json_encode(
                    $safePayload,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'response_body' => $storeResponseBody
                    ? $this->protectSensitiveData($result['response_body'] ?? null)
                    : null,
                'response_json' => $responseJson === null
                    ? null
                    : json_encode(
                        $this->sanitizeSensitiveValues($responseJson),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                'error_message' => $this->protectSensitiveData($result['error_message'] ?? null),
                'exception_message' => $this->protectSensitiveData($result['exception_message'] ?? null),
                'created_by' => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            Log::error('No se pudo registrar la auditoría de Nota de Crédito APISPERU.', [
                'invoice_id' => $invoice->id,
                'action' => $action,
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
}
