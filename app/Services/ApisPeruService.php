<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class ApisPeruService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = 'https://facturacion.apisperu.com/api/v1';
        $this->token = env('APISPERU_TOKEN');
    }

    protected function client()
    {
        return Http::withToken($this->token)->acceptJson();
    }

    public function getCompanies(): array
    {
        try {
            $response = $this->client()
                ->get("{$this->baseUrl}/companies");

            if (! $response->successful()) {
                throw new Exception('Error APISPERU: ' . $response->body());
            }

            return [
                'success' => true,
                'data' => $response->json(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function sendInvoice(array $payload): array
    {
        try {
            $response = $this->client()
                ->post("{$this->baseUrl}/invoice/send", $payload);

            if (! $response->successful()) {
                throw new Exception('Error APISPERU: ' . $response->body());
            }

            return [
                'success' => true,
                'data' => $response->json(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getInvoicePdf(array $payload): array
    {
        try {
            $response = $this->client()
                ->post("{$this->baseUrl}/invoice/pdf", $payload);

            if (! $response->successful()) {
                throw new Exception('Error APISPERU PDF: ' . $response->body());
            }

            return [
                'success' => true,
                'data' => $response->body(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getInvoiceXml(array $payload): array
    {
        try {
            $response = $this->client()
                ->post("{$this->baseUrl}/invoice/xml", $payload);

            if (! $response->successful()) {
                throw new Exception('Error APISPERU XML: ' . $response->body());
            }

            return [
                'success' => true,
                'data' => $response->body(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
