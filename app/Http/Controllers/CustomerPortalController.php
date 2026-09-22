<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PaymentReceipt;
use App\Models\Sale;
use App\Services\CustomerPortalStatement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomerPortalController extends Controller
{
    private function sales(Request $request)
    {
        return Sale::where('customer_id', $request->attributes->get('portal_account')->customer_id);
    }

    public function me(Request $request, CustomerPortalStatement $statement)
    {
        $customer = $request->attributes->get('portal_account')->customer;
        return response()->json([
            'name' => $customer->full_name ?: trim($customer->first_name.' '.$customer->last_name),
            'operations' => $this->sales($request)->with(['lot.project', 'lot.block', 'saleLots.lot.project', 'saleLots.lot.block'])
                ->orderByDesc('id')->get()->map(fn ($sale) => $statement->operation($sale)),
        ]);
    }

    public function show(Request $request, string $sale, CustomerPortalStatement $statement)
    {
        $owned = $this->sales($request)->whereKey($sale)->firstOrFail();
        $data = $statement->make($owned);
        $data['documents'] = $this->documents($owned);
        $data['statement_url'] = route('portal.statement', $owned->id);
        return response()->json($data);
    }

    public function schedules(Request $request, string $sale, CustomerPortalStatement $statement)
    {
        return response()->json($statement->make($this->sales($request)->whereKey($sale)->firstOrFail())['schedules']);
    }

    public function payments(Request $request, string $sale, CustomerPortalStatement $statement)
    {
        return response()->json($statement->make($this->sales($request)->whereKey($sale)->firstOrFail())['payments']);
    }

    private function documents(Sale $sale): array
    {
        $receipts = PaymentReceipt::whereHas('payment', fn ($q) => $q->where('sale_id', $sale->id))->get()
            ->map(fn ($receipt) => ['label' => 'Adjunto de pago #'.$receipt->payment_id,
                'url' => route('portal.receipt', $receipt->id)]);
        $invoices = Invoice::where('sale_id', $sale->id)
            ->whereHas('payment', fn ($q) => $q->where('sale_id', $sale->id))
            ->whereIn('document_type', ['receipt', 'invoice', 'sale_note'])->get()
            ->filter(fn ($invoice) => $invoice->document_type === 'sale_note' || $this->invoicePath($invoice->pdf_path) !== null)
            ->map(fn ($invoice) => ['label' => (['receipt' => 'Boleta', 'invoice' => 'Factura', 'sale_note' => 'Nota de venta'][$invoice->document_type]).' '.$invoice->series.'-'.$invoice->number,
                'status' => $invoice->voided_at ? 'Anulado' : $invoice->sunat_status,
                'url' => route('portal.invoice', $invoice->id)]);
        return $receipts->concat($invoices)->values()->all();
    }

    public function receipt(Request $request, string $receipt)
    {
        $owned = PaymentReceipt::whereHas('payment.sale', fn ($q) => $q->where('customer_id', $request->attributes->get('portal_account')->customer_id))
            ->whereKey($receipt)->firstOrFail();
        abort_unless(in_array($owned->mime_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true), 404);
        $disk = PaymentReceipt::storage();
        abort_unless($owned->disk === PaymentReceipt::DISK && $disk->exists($owned->path), 404);
        $extension = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$owned->mime_type];
        return $disk->response($owned->path, 'comprobante-'.$owned->id.'.'.$extension,
            ['Content-Type' => $owned->mime_type, 'Content-Security-Policy' => "sandbox"], $request->boolean('download') ? 'attachment' : 'inline');
    }

    private function invoicePath(?string $path): ?string
    {
        $path = trim(str_replace('\\', '/', $path ?? ''));
        $path = preg_replace('#^https?://[^/]+/#', '', $path);
        $path = preg_replace('#^(storage/app/public/|app/public/|public/storage/|storage/|public/)#', '', $path);
        if (! preg_match('#^invoices/[a-zA-Z0-9_./-]+\.pdf$#D', $path) || str_contains($path, '..')) {
            return null;
        }
        return Storage::disk('public')->exists($path) ? $path : null;
    }

    public function invoice(Request $request, string $invoice)
    {
        $owned = Invoice::whereHas('sale', fn ($q) => $q->where('customer_id', $request->attributes->get('portal_account')->customer_id))
            ->whereHas('payment', fn ($q) => $q->whereColumn('payments.sale_id', 'invoices.sale_id'))
            ->whereIn('document_type', ['receipt', 'invoice', 'sale_note'])->whereKey($invoice)->firstOrFail();
        $path = $this->invoicePath($owned->pdf_path);
        if (! $path && $owned->document_type === 'sale_note') {
            $customer = $request->attributes->get('portal_account')->customer;
            $pdf = Pdf::loadView('portal.sale-note', [
                'document' => [
                    'code' => $owned->series.'-'.$owned->number,
                    'date' => $owned->issue_date?->format('d/m/Y'),
                    'amount' => $owned->total_amount, 'currency' => $owned->currency ?: 'PEN',
                    'status' => $owned->voided_at ? 'Anulada' : 'Emitida',
                ],
                'name' => $customer->full_name ?: trim($customer->first_name.' '.$customer->last_name),
            ])->setPaper('a4');
            return $request->boolean('download') ? $pdf->download('nota-venta-'.$owned->id.'.pdf') : $pdf->stream('nota-venta-'.$owned->id.'.pdf');
        }
        abort_unless($path, 404);
        return Storage::disk('public')->response($path, 'comprobante-'.$owned->id.'.pdf', ['Content-Type' => 'application/pdf'],
            $request->boolean('download') ? 'attachment' : 'inline');
    }

    public function statement(Request $request, string $sale, CustomerPortalStatement $statement)
    {
        $owned = $this->sales($request)->whereKey($sale)->firstOrFail();
        $customer = $request->attributes->get('portal_account')->customer;
        return Pdf::loadView('portal.statement', [
            'data' => $statement->make($owned),
            'name' => $customer->full_name ?: trim($customer->first_name.' '.$customer->last_name),
        ])->setPaper('a4')->download('estado-cuenta-'.$owned->id.'.pdf');
    }
}
