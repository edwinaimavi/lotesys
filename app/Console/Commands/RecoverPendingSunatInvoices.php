<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\InvoiceSunatRecoveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RecoverPendingSunatInvoices extends Command
{
    protected $signature = 'invoices:recover-pending-sunat {--limit=100 : Máximo de comprobantes a revisar por ejecución} {--dry-run : Solo lista candidatos; no consulta ni reenvía a SUNAT}';

    protected $description = 'Consulta y recupera de forma segura comprobantes SUNAT pendientes por fallas transitorias';

    public function handle(InvoiceSunatRecoveryService $recovery): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Interruptor de seguridad para la automatización nocturna.
        // El dry-run siempre queda disponible para inspeccionar candidatos
        // sin consultar ni reenviar comprobantes a APISPERU/SUNAT.
        if (! $dryRun && ! config('sunat.recovery_automation_enabled', false)) {
            $this->warn(
                'Recuperación automática SUNAT desactivada. '
                . 'Use --dry-run para revisar candidatos o habilite '
                . 'SUNAT_RECOVERY_AUTOMATION_ENABLED=true en producción.'
            );

            return self::SUCCESS;
        }

        $limit = max(1, min((int) $this->option('limit'), 500));

        $invoices = Invoice::query()
            ->where('sunat_status', 'pending')
            ->whereIn('document_type', ['receipt', 'invoice'])
            ->whereDate('issue_date', '>=', now()->subDays(3)->toDateString())
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('No hay comprobantes SUNAT pendientes recientes.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $candidates = $invoices
                ->filter(fn (Invoice $invoice) => $recovery->isAutomaticCandidate($invoice))
                ->values();

            if ($candidates->isEmpty()) {
                $this->info('DRY-RUN: no hay comprobantes elegibles para recuperación automática.');
                return self::SUCCESS;
            }

            $this->warn('DRY-RUN: no se consultará ni reenviará ningún comprobante.');

            foreach ($candidates as $invoice) {
                $this->line(sprintf(
                    '%s | %s-%s | invoice_id=%d | payment_id=%s | fecha=%s',
                    strtoupper($invoice->document_type),
                    $invoice->series,
                    $invoice->number,
                    $invoice->id,
                    $invoice->payment_id ?? 'N/A',
                    optional($invoice->issue_date)->format('Y-m-d H:i:s') ?? 'N/A'
                ));
            }

            $this->info('Candidatos: ' . $candidates->count());
            return self::SUCCESS;
        }

        $reviewed = 0;
        $accepted = 0;
        $pending = 0;
        $rejected = 0;
        $skipped = 0;

        foreach ($invoices as $invoice) {
            if (! $recovery->isAutomaticCandidate($invoice)) {
                $skipped++;
                continue;
            }

            $reviewed++;

            try {
                $result = $recovery->recover($invoice, true, null);
                $status = $result['status'] ?? 'pending';

                if ($status === 'accepted') {
                    $accepted++;
                } elseif ($status === 'rejected') {
                    $rejected++;
                } elseif ($status === 'skipped') {
                    $skipped++;
                } else {
                    $pending++;
                }

                $this->line(sprintf(
                    '%s %s-%s: %s',
                    strtoupper($invoice->document_type),
                    $invoice->series,
                    $invoice->number,
                    $result['message'] ?? $status
                ));
            } catch (\Throwable $e) {
                $pending++;

                Log::error('Error en recuperación automática SUNAT.', [
                    'invoice_id' => $invoice->id,
                    'exception' => $e->getMessage(),
                ]);

                $this->error(
                    $invoice->series . '-' . $invoice->number . ': ' . $e->getMessage()
                );
            }
        }

        $this->info(sprintf(
            'Revisados: %d | Aceptados: %d | Pendientes: %d | Rechazados: %d | Omitidos: %d',
            $reviewed,
            $accepted,
            $pending,
            $rejected,
            $skipped
        ));

        return self::SUCCESS;
    }
}
