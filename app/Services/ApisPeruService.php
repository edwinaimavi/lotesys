<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class ApisPeruService
{
    protected string $baseUrl;

    protected ?string $token = null;

    public function __construct()
    {
        $this->baseUrl = 'https://facturacion.apisperu.com/api/v1';
    }

    /*
    |--------------------------------------------------------------------------
    | SELECCIONAR TOKEN SEGÚN EMPRESA / RUC
    |--------------------------------------------------------------------------
    */
    public function useCompanyRuc(?string $ruc): self
    {
        $ruc = trim((string) $ruc);

        $token = match ($ruc) {

            env('APISPERU_RUC_KREA') => env('APISPERU_TOKEN_KREA'),

            env('APISPERU_RUC_FARJE') => env('APISPERU_TOKEN_FARJE'),

            default => null,
        };

        if (!$token) {
            throw new Exception(
                'No existe token APISPERU configurado para el RUC: ' . $ruc
            );
        }

        $this->token = $token;

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | CLIENTE HTTP
    |--------------------------------------------------------------------------
    */
    protected function client()
    {
        if (!$this->token) {
            throw new Exception(
                'No se ha seleccionado un token APISPERU para la empresa emisora.'
            );
        }

        return Http::withToken($this->token)
            ->acceptJson();
    }

    /*
    |--------------------------------------------------------------------------
    | EMPRESAS APISPERU
    |--------------------------------------------------------------------------
    */
    public function getCompanies(?string $ruc = null): array
    {
        try {

            if ($ruc) {
                $this->useCompanyRuc($ruc);
            } else {
                $this->useCompanyRuc(env('APISPERU_RUC_KREA'));
            }

            $response = $this->client()
                ->get("{$this->baseUrl}/companies");

            if (!$response->successful()) {
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

    /*
    |--------------------------------------------------------------------------
    | ENVIAR COMPROBANTE
    |--------------------------------------------------------------------------
    */
    public function sendInvoice(array $payload): array
    {
        try {

            $response = $this->client()
                ->post("{$this->baseUrl}/invoice/send", $payload);

            if (!$response->successful()) {
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

    /*
    |--------------------------------------------------------------------------
    | PDF
    |--------------------------------------------------------------------------
    */
    public function getInvoicePdf(array $payload): array
    {
        try {

            $response = $this->client()
                ->post("{$this->baseUrl}/invoice/pdf", $payload);

            if (!$response->successful()) {
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

    /*
    |--------------------------------------------------------------------------
    | XML
    |--------------------------------------------------------------------------
    */
    public function getInvoiceXml(array $payload): array
    {
        try {

            $response = $this->client()
                ->post("{$this->baseUrl}/invoice/xml", $payload);

            if (!$response->successful()) {
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
