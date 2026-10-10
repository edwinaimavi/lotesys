<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceApiLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InvoiceSunatRecoveryService
{
    private const MAX_AUTOMATIC_RETRIES = 6;

    private const AUTOMATIC_LOOKBACK_DAYS = 3;

    private const RETRYABLE_HTTP_STATUS = [408, 425, 429, 500, 502, 503, 504];

    public function __construct(private ApisPeruService $apisPeru)
    {
    }

    /**
     * Consulta primero el estado real en APISPERU/SUNAT. Solo si SUNAT
     * confirma que la constancia no existe (0127), y el intento previo fue
     * de conectividad/indeterminado, reenvía EXACTAMENTE el payload original.
     */
    public function recover(
        Invoice $invoice,
        bool $automatic = false,
        ?int $userId = null
    ): array {
        // Seguridad: nunca permitir recuperación real desde local/testing.
        // En esos entornos podemos usar credenciales reales por configuración,
        // por lo que una pulsación accidental no debe consultar ni reenviar
        // comprobantes a APISPERU/SUNAT.
        if (app()->environment(['local', 'testing'])) {
            return [
                'success' => false,
                'status' => 'environment_blocked',
                'message' => 'Recuperación SUNAT bloqueada en entorno local/testing. No se realizó ninguna comunicación con APISPERU/SUNAT.',
                'http_code' => 403,
            ];
        }

        $lock = Cache::lock('invoice-sunat-recovery:' . $invoice->id, 180);

        if (! $lock->get()) {
            return [
                'success' => false,
                'status' => 'busy',
                'message' => 'Este comprobante ya está siendo consultado o recuperado. Intente nuevamente en unos segundos.',
                'http_code' => 409,
            ];
        }

        try {
            return $this->recoverLocked($invoice, $automatic, $userId);
        } finally {
            $lock->release();
        }
    }

    public function isAutomaticCandidate(Invoice $invoice): bool
    {
        if ($invoice->sunat_status !== 'pending') {
            return false;
        }

        if (! in_array($invoice->document_type, ['receipt', 'invoice'], true)) {
            return false;
        }

        if (! $invoice->issue_date || $invoice->issue_date->lt(now()->subDays(self::AUTOMATIC_LOOKBACK_DAYS)->startOfDay())) {
            return false;
        }

        $transmissionLog = $this->latestTransmissionLog($invoice);

        return $transmissionLog && $this->transmissionLogIsRetryable($transmissionLog);
    }

    private function recoverLocked(Invoice $invoice, bool $automatic, ?int $userId): array
    {
        $invoice->refresh();
        $invoice->loadMissing('company');

        if (! in_array($invoice->document_type, ['receipt', 'invoice'], true)) {
            return [
                'success' => false,
                'status' => 'not_applicable',
                'message' => 'Solo se puede recuperar una boleta o factura electrónica.',
                'http_code' => 422,
            ];
        }

        if ($invoice->sunat_status === 'accepted') {
            return [
                'success' => true,
                'status' => 'accepted',
                'message' => $this->label($invoice) . ' ya figura como aceptado en LoteSys.',
                'http_code' => 200,
            ];
        }

        if ($invoice->sunat_status !== 'pending') {
            return [
                'success' => false,
                'status' => $invoice->sunat_status,
                'message' => $this->label($invoice) . ' ya no está pendiente y no debe reenviarse.',
                'http_code' => 422,
            ];
        }

        if ($automatic && ! $this->isAutomaticCandidate($invoice)) {
            return [
                'success' => false,
                'status' => 'skipped',
                'message' => $this->label($invoice) . ' no cumple las reglas de reintento automático.',
                'http_code' => 200,
            ];
        }

        if (! $invoice->company) {
            return [
                'success' => false,
                'status' => 'configuration_error',
                'message' => 'El comprobante no tiene empresa emisora asociada.',
                'http_code' => 422,
            ];
        }

        $transmissionLog = $this->latestTransmissionLog($invoice);
        $payload = $this->payloadFromTransmissionLog($transmissionLog);

        if (! $payload) {
            return [
                'success' => false,
                'status' => 'missing_payload',
                'message' => 'No se encontró el payload original del comprobante. No se reenviará para evitar alterar sus datos.',
                'http_code' => 422,
            ];
        }

        if (! $this->payloadMatchesInvoice($invoice, $payload)) {
            return [
                'success' => false,
                'status' => 'payload_mismatch',
                'message' => 'El payload auditado no coincide con la serie, número o tipo del comprobante. Se requiere revisión manual.',
                'http_code' => 422,
            ];
        }

        try {
            $this->apisPeru->useCompanyRuc($invoice->company->ruc);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 'configuration_error',
                'message' => $e->getMessage(),
                'http_code' => 422,
            ];
        }

        $tipo = $invoice->document_type === 'invoice' ? '01' : '03';
        $statusRequest = [
            'tipo' => $tipo,
            'serie' => $invoice->series,
            'numero' => (string) $invoice->number,
            'ruc' => (string) $invoice->company->ruc,
        ];

        $statusResult = $this->apisPeru->getInvoiceStatus(
            $tipo,
            (string) $invoice->series,
            (string) $invoice->number,
            (string) $invoice->company->ruc
        );

        $statusOutcome = $this->extractStatusOutcome($statusResult);
        $this->storeApiLog(
            $invoice,
            'status_check',
            $statusRequest,
            $this->statusResultForAudit($statusResult, $statusOutcome),
            $userId
        );

        if ($statusOutcome['kind'] === 'accepted') {
            return $this->finalizeAccepted(
                $invoice,
                $payload,
                $statusOutcome,
                $statusResult['data'] ?? [],
                $userId,
                'SUNAT confirmó que la constancia ya existía.'
            );
        }

        if ($statusOutcome['kind'] === 'rejected') {
            $this->markRejected($invoice, $statusOutcome, $userId);

            return [
                'success' => false,
                'status' => 'rejected',
                'message' => 'SUNAT registra el comprobante como rechazado: ' . $statusOutcome['message'],
                'http_code' => 422,
            ];
        }

        if ($statusOutcome['kind'] !== 'not_found') {
            $invoice->update([
                'sunat_message' => $statusOutcome['message'],
            ]);

            return [
                'success' => false,
                'status' => 'pending',
                'message' => 'No se pudo confirmar el estado en SUNAT. No se reenviará mientras la consulta sea incierta. ' . $statusOutcome['message'],
                'http_code' => 502,
            ];
        }

        if (! $transmissionLog || ! $this->transmissionLogIsRetryable($transmissionLog)) {
            return [
                'success' => false,
                'status' => 'pending',
                'message' => 'SUNAT no encontró la constancia, pero el último intento no está clasificado como falla transitoria. No se reenviará automáticamente.',
                'http_code' => 409,
            ];
        }

        $retryCount = $invoice->apiLogs()
            ->where('action', 'retry_send')
            ->count();

        if ($automatic && $retryCount >= self::MAX_AUTOMATIC_RETRIES) {
            return [
                'success' => false,
                'status' => 'pending',
                'message' => 'Se alcanzó el máximo de reintentos automáticos para ' . $this->label($invoice) . '. Requiere revisión manual.',
                'http_code' => 200,
            ];
        }

        // IMPORTANTE: se reutiliza el payload original. Nunca se recalcula
        // serie, correlativo, cliente, fecha, importes ni detalle.
        $send = $this->apisPeru->sendInvoice($payload);
        $sendOutcome = $this->extractSendOutcome($send);

        $auditSend = $send;
        $auditSend['success'] = $sendOutcome['confirmed'] && $sendOutcome['accepted'];

        if (! $auditSend['success'] && empty($auditSend['error_message'])) {
            $auditSend['error_message'] = $sendOutcome['message'];
        }

        $this->storeApiLog(
            $invoice,
            'retry_send',
            $payload,
            $auditSend,
            $userId
        );

        if (! $sendOutcome['confirmed']) {
            $invoice->update([
                'sunat_message' => $sendOutcome['message'],
            ]);

            return [
                'success' => false,
                'status' => 'pending',
                'message' => 'Se reintentó el mismo ' . $this->label($invoice) . ', pero SUNAT todavía no confirmó el resultado. No se generó un correlativo nuevo.',
                'http_code' => 502,
            ];
        }

        if (! $sendOutcome['accepted']) {
            $this->markRejected($invoice, $sendOutcome, $userId, $send['data']['hash'] ?? null);

            return [
                'success' => false,
                'status' => 'rejected',
                'message' => 'SUNAT rechazó el comprobante durante el reintento: ' . $sendOutcome['message'],
                'http_code' => 422,
            ];
        }

        return $this->finalizeAccepted(
            $invoice,
            $payload,
            $sendOutcome,
            $send['data'] ?? [],
            $userId,
            'El mismo comprobante fue reenviado y aceptado por SUNAT.'
        );
    }

    private function latestTransmissionLog(Invoice $invoice): ?InvoiceApiLog
    {
        return $invoice->apiLogs()
            ->whereIn('action', ['send', 'retry_send'])
            ->whereNotNull('request_payload')
            ->latest('id')
            ->first();
    }

    private function payloadFromTransmissionLog(?InvoiceApiLog $log): ?array
    {
        if (! $log || ! $log->request_payload) {
            return null;
        }

        $payload = json_decode($log->request_payload, true);

        return is_array($payload) ? $payload : null;
    }

    private function payloadMatchesInvoice(Invoice $invoice, array $payload): bool
    {
        $expectedTipo = $invoice->document_type === 'invoice' ? '01' : '03';

        if ((string) ($payload['tipoDoc'] ?? '') !== $expectedTipo) {
            return false;
        }

        if ((string) ($payload['serie'] ?? '') !== (string) $invoice->series) {
            return false;
        }

        if ((string) ($payload['correlativo'] ?? '') !== (string) $invoice->number) {
            return false;
        }

        $payloadRuc = data_get($payload, 'company.ruc');

        return ! $payloadRuc || (string) $payloadRuc === (string) $invoice->company?->ruc;
    }

    private function transmissionLogIsRetryable(InvoiceApiLog $log): bool
    {
        // Un intento que el propio log marcó como exitoso nunca debe entrar
        // a la cola automática. Si el invoice siguiera PENDING en ese caso,
        // la inconsistencia requiere revisión manual.
        if ($log->success === true) {
            return false;
        }

        $httpStatus = $log->http_status;

        if ($httpStatus !== null && in_array((int) $httpStatus, self::RETRYABLE_HTTP_STATUS, true)) {
            return true;
        }

        // IMPORTANTE: HTTP 200 por sí solo NO habilita un reenvío.
        // APISPERU puede responder 200 y, aun así, SUNAT rechazar o devolver
        // una respuesta no concluyente. Solo se automatizan fallas de red o
        // estados HTTP expresamente transitorios.

        $text = strtolower(trim(
            ($log->exception_message ?? '') . ' ' .
            ($log->error_message ?? '')
        ));

        if ($text === '') {
            return false;
        }

        $patterns = [
            'curl error 28',
            'timed out',
            'timeout',
            'connection timed out',
            'connection refused',
            'connection reset',
            'could not connect',
            'could not resolve host',
            'temporary failure',
            'network is unreachable',
            'server error',
            'service unavailable',
            'bad gateway',
            'gateway timeout',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($text, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function extractStatusOutcome(array $result): array
    {
        if (! ($result['success'] ?? false)) {
            return [
                'kind' => 'unknown',
                'code' => null,
                'message' => $result['message'] ?? 'No se pudo consultar el estado en APISPERU/SUNAT.',
            ];
        }

        $data = $result['data'] ?? null;

        if (! is_array($data)) {
            return [
                'kind' => 'unknown',
                'code' => null,
                'message' => 'APISPERU respondió sin un estado interpretable.',
            ];
        }

        $success = filter_var(
            $data['success'] ?? null,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        $cdr = is_array($data['cdrResponse'] ?? null)
            ? $data['cdrResponse']
            : [];

        $error = is_array($data['error'] ?? null)
            ? $data['error']
            : [];

        $providerCode = (string) ($data['code'] ?? $error['code'] ?? '');
        $cdrCode = array_key_exists('code', $cdr) ? (string) $cdr['code'] : null;
        $message = (string) (
            $cdr['description']
            ?? $data['message']
            ?? $error['message']
            ?? 'Sin detalle de SUNAT.'
        );

        if ($success === true && $cdrCode === '0') {
            return [
                'kind' => 'accepted',
                'code' => '0',
                'message' => $message ?: 'Aceptado por SUNAT.',
            ];
        }

        if ($success === true && $cdrCode !== null && $cdrCode !== '') {
            return [
                'kind' => 'rejected',
                'code' => $cdrCode,
                'message' => $message ?: 'SUNAT registra una constancia con rechazo.',
            ];
        }

        if ($success === false && $providerCode === '0127') {
            return [
                'kind' => 'not_found',
                'code' => '0127',
                'message' => $data['message'] ?? 'El ticket no existe',
            ];
        }

        return [
            'kind' => 'unknown',
            'code' => $providerCode ?: $cdrCode,
            'message' => $message,
        ];
    }

    private function extractSendOutcome(array $result): array
    {
        if (! ($result['success'] ?? false)) {
            return [
                'confirmed' => false,
                'accepted' => false,
                'code' => null,
                'message' => $result['message'] ?? 'No se pudo confirmar el comprobante con APISPERU/SUNAT.',
            ];
        }

        $data = $result['data'] ?? [];
        $sunatResponse = is_array($data) ? ($data['sunatResponse'] ?? null) : null;

        if (! is_array($sunatResponse) || ! array_key_exists('success', $sunatResponse)) {
            return [
                'confirmed' => false,
                'accepted' => false,
                'code' => null,
                'message' => 'APISPERU respondió, pero no confirmó el resultado de SUNAT.',
            ];
        }

        $accepted = filter_var(
            $sunatResponse['success'],
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) === true;

        $cdr = is_array($sunatResponse['cdrResponse'] ?? null)
            ? $sunatResponse['cdrResponse']
            : [];

        $error = is_array($sunatResponse['error'] ?? null)
            ? $sunatResponse['error']
            : [];

        $code = $cdr['code'] ?? $error['code'] ?? null;
        $message = $cdr['description']
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

    private function finalizeAccepted(
        Invoice $invoice,
        array $payload,
        array $outcome,
        array $providerData,
        ?int $userId,
        string $contextMessage
    ): array {
        $updates = [
            'sunat_status' => 'accepted',
            'sunat_code' => $outcome['code'] ?? '0',
            'sunat_message' => $outcome['message'] ?? 'Aceptado por SUNAT.',
        ];

        $hash = $providerData['hash'] ?? null;
        $ticket = $providerData['ticket'] ?? null;

        if ($hash) {
            $updates['hash_code'] = $hash;
        }

        if ($ticket) {
            $updates['sunat_ticket'] = $ticket;
        }

        if ($userId !== null) {
            $updates['updated_by'] = $userId;
        }

        $cdrZip = $providerData['cdrZip']
            ?? data_get($providerData, 'sunatResponse.cdrZip');

        if ($cdrZip) {
            $cdrPath = $this->storeCdrZip($invoice, $cdrZip);
            if ($cdrPath) {
                $updates['cdr_path'] = $cdrPath;
            }
        }

        // Persistir primero la aceptación: PDF/XML son artefactos auxiliares.
        $invoice->update($updates);
        $invoice->refresh();

        $artifactWarnings = $this->refreshArtifacts($invoice, $payload, $userId);

        $message = $contextMessage . ' ' . $this->label($invoice) . ' quedó aceptado en LoteSys.';

        if ($artifactWarnings) {
            $message .= ' ' . implode(' ', $artifactWarnings);
        }

        return [
            'success' => true,
            'status' => 'accepted',
            'message' => trim($message),
            'invoice_id' => $invoice->id,
            'http_code' => 200,
        ];
    }

    private function refreshArtifacts(Invoice $invoice, array $payload, ?int $userId): array
    {
        $warnings = [];
        $prefix = $invoice->document_type === 'invoice' ? 'FACTURA' : 'BOLETA';
        $pdfPath = $invoice->pdf_path;
        $xmlPath = $invoice->xml_path;

        try {
            $pdf = $this->apisPeru->getInvoicePdf($payload);

            if ($pdf['success'] ?? false) {
                $pdfPath = 'invoices/' . $prefix . '_' . $invoice->series . '-' . $invoice->number . '.pdf';
                Storage::disk('public')->put($pdfPath, $pdf['data']);
            } else {
                $warnings[] = 'No se pudo obtener el PDF definitivo.';
            }
        } catch (\Throwable $e) {
            $warnings[] = 'No se pudo obtener el PDF definitivo.';
            Log::warning('CPE recuperado/aceptado, pero no se pudo guardar el PDF.', [
                'invoice_id' => $invoice->id,
                'exception' => $e->getMessage(),
            ]);
        }

        try {
            $xml = $this->apisPeru->getInvoiceXml($payload);

            if ($xml['success'] ?? false) {
                $xmlPath = 'invoices/' . $prefix . '_' . $invoice->series . '-' . $invoice->number . '.xml';
                Storage::disk('public')->put($xmlPath, $xml['data']);
            } else {
                $warnings[] = 'No se pudo obtener el XML definitivo.';
            }
        } catch (\Throwable $e) {
            $warnings[] = 'No se pudo obtener el XML definitivo.';
            Log::warning('CPE recuperado/aceptado, pero no se pudo guardar el XML.', [
                'invoice_id' => $invoice->id,
                'exception' => $e->getMessage(),
            ]);
        }

        $updates = [
            'pdf_path' => $pdfPath,
            'xml_path' => $xmlPath,
        ];

        if ($userId !== null) {
            $updates['updated_by'] = $userId;
        }

        $invoice->update($updates);

        return $warnings;
    }

    private function storeCdrZip(Invoice $invoice, string $base64): ?string
    {
        $decoded = base64_decode($base64, true);

        if ($decoded === false || $decoded === '') {
            return null;
        }

        $path = 'invoices/CDR_' . $invoice->series . '-' . $invoice->number . '.zip';
        Storage::disk('public')->put($path, $decoded);

        return $path;
    }

    private function markRejected(
        Invoice $invoice,
        array $outcome,
        ?int $userId,
        ?string $hash = null
    ): void {
        $updates = [
            'sunat_status' => 'rejected',
            'sunat_code' => $outcome['code'] ?? null,
            'sunat_message' => $outcome['message'] ?? 'Rechazado por SUNAT.',
        ];

        if ($hash) {
            $updates['hash_code'] = $hash;
        }

        if ($userId !== null) {
            $updates['updated_by'] = $userId;
        }

        $invoice->update($updates);
    }

    private function statusResultForAudit(array $result, array $outcome): array
    {
        $data = $result['response_json'] ?? $result['data'] ?? null;

        if (is_array($data) && array_key_exists('cdrZip', $data)) {
            $data['cdrZip'] = '[CDR BASE64 OMITIDO DEL LOG]';
        }

        return [
            'success' => $outcome['kind'] === 'accepted',
            'endpoint' => $result['endpoint'] ?? null,
            'http_status' => $result['http_status'] ?? null,
            // No guardar response_body porque /invoice/status puede contener
            // todo el CDR en base64 y duplicaría innecesariamente el log.
            'response_body' => null,
            'response_json' => $data,
            'error_message' => $outcome['kind'] === 'accepted' ? null : ($outcome['message'] ?? null),
            'exception_message' => $result['exception_message'] ?? null,
        ];
    }

    private function storeApiLog(
        Invoice $invoice,
        string $action,
        array $payload,
        array $result,
        ?int $userId
    ): ?InvoiceApiLog {
        try {
            $responseJson = $result['response_json'] ?? null;

            return InvoiceApiLog::create([
                'invoice_id' => $invoice->id,
                'action' => $action,
                'provider' => 'APISPERU',
                'endpoint' => $result['endpoint'] ?? null,
                'http_status' => $result['http_status'] ?? null,
                'success' => (bool) ($result['success'] ?? false),
                'request_payload' => json_encode(
                    $this->sanitizeSensitiveValues($payload),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'response_body' => $this->protectSensitiveData($result['response_body'] ?? null),
                'response_json' => $responseJson === null
                    ? null
                    : json_encode(
                        $this->sanitizeSensitiveValues(is_array($responseJson) ? $responseJson : json_decode((string) $responseJson, true) ?? $responseJson),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                'error_message' => $this->protectSensitiveData($result['error_message'] ?? null),
                'exception_message' => $this->protectSensitiveData($result['exception_message'] ?? null),
                'created_by' => $userId,
            ]);
        } catch (\Throwable $e) {
            Log::error('No se pudo registrar auditoría de recuperación SUNAT.', [
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

    private function label(Invoice $invoice): string
    {
        $type = $invoice->document_type === 'invoice' ? 'Factura' : 'Boleta';

        return $type . ' ' . $invoice->series . '-' . $invoice->number;
    }
}
