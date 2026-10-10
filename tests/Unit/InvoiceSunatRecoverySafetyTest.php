<?php

namespace Tests\Unit;

use App\Models\InvoiceApiLog;
use App\Services\ApisPeruService;
use App\Services\InvoiceSunatRecoveryService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class InvoiceSunatRecoverySafetyTest extends TestCase
{
    private function service(): InvoiceSunatRecoveryService
    {
        return new InvoiceSunatRecoveryService(
            $this->createMock(ApisPeruService::class)
        );
    }

    private function invokePrivate(object $object, string $method, mixed ...$arguments): mixed
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($object, ...$arguments);
    }

    public function test_timeout_log_is_retryable(): void
    {
        $log = new InvoiceApiLog([
            'success' => false,
            'http_status' => null,
            'exception_message' => 'cURL error 28: Operation timed out after 30001 milliseconds with 0 bytes received',
        ]);

        $this->assertTrue(
            $this->invokePrivate($this->service(), 'transmissionLogIsRetryable', $log)
        );
    }

    public function test_http_503_is_retryable(): void
    {
        $log = new InvoiceApiLog([
            'success' => false,
            'http_status' => 503,
            'error_message' => 'Service unavailable',
        ]);

        $this->assertTrue(
            $this->invokePrivate($this->service(), 'transmissionLogIsRetryable', $log)
        );
    }

    public function test_plain_http_200_failure_is_not_automatically_retryable(): void
    {
        $log = new InvoiceApiLog([
            'success' => false,
            'http_status' => 200,
            'error_message' => null,
            'exception_message' => null,
        ]);

        $this->assertFalse(
            $this->invokePrivate($this->service(), 'transmissionLogIsRetryable', $log)
        );
    }

    public function test_successful_log_is_never_automatically_retryable(): void
    {
        $log = new InvoiceApiLog([
            'success' => true,
            'http_status' => 200,
            'error_message' => 'timeout text should not matter',
        ]);

        $this->assertFalse(
            $this->invokePrivate($this->service(), 'transmissionLogIsRetryable', $log)
        );
    }

    public function test_status_accepted_is_recognized(): void
    {
        $result = [
            'success' => true,
            'data' => [
                'success' => true,
                'cdrResponse' => [
                    'id' => 'B002-44',
                    'code' => '0',
                    'description' => 'La Boleta numero B002-44, ha sido aceptada',
                    'notes' => [],
                ],
                'code' => '0004',
                'message' => 'La constancia existe',
            ],
        ];

        $outcome = $this->invokePrivate(
            $this->service(),
            'extractStatusOutcome',
            $result
        );

        $this->assertSame('accepted', $outcome['kind']);
        $this->assertSame('0', $outcome['code']);
        $this->assertStringContainsString('aceptada', strtolower($outcome['message']));
    }

    public function test_status_0127_is_recognized_as_not_found(): void
    {
        $result = [
            'success' => true,
            'data' => [
                'success' => false,
                'error' => [
                    'code' => '0127',
                    'message' => '',
                ],
                'code' => '0127',
                'message' => 'El ticket no existe',
            ],
        ];

        $outcome = $this->invokePrivate(
            $this->service(),
            'extractStatusOutcome',
            $result
        );

        $this->assertSame('not_found', $outcome['kind']);
        $this->assertSame('0127', $outcome['code']);
        $this->assertSame('El ticket no existe', $outcome['message']);
    }

    public function test_unrecognized_status_is_unknown_and_does_not_authorize_resend(): void
    {
        $result = [
            'success' => true,
            'data' => [
                'success' => false,
                'code' => '9999',
                'message' => 'Respuesta no concluyente',
            ],
        ];

        $outcome = $this->invokePrivate(
            $this->service(),
            'extractStatusOutcome',
            $result
        );

        $this->assertSame('unknown', $outcome['kind']);
        $this->assertSame('9999', $outcome['code']);
    }

    public function test_send_accepted_requires_nested_sunat_confirmation(): void
    {
        $result = [
            'success' => true,
            'data' => [
                'sunatResponse' => [
                    'success' => true,
                    'cdrResponse' => [
                        'code' => '0',
                        'description' => 'Aceptado por SUNAT',
                    ],
                ],
            ],
        ];

        $outcome = $this->invokePrivate(
            $this->service(),
            'extractSendOutcome',
            $result
        );

        $this->assertTrue($outcome['confirmed']);
        $this->assertTrue($outcome['accepted']);
        $this->assertSame('0', $outcome['code']);
    }

    public function test_http_success_without_nested_sunat_confirmation_stays_uncertain(): void
    {
        $result = [
            'success' => true,
            'data' => [
                'message' => 'APISPERU respondió HTTP 200',
            ],
        ];

        $outcome = $this->invokePrivate(
            $this->service(),
            'extractSendOutcome',
            $result
        );

        $this->assertFalse($outcome['confirmed']);
        $this->assertFalse($outcome['accepted']);
    }

    public function test_send_rejection_is_confirmed_but_not_accepted(): void
    {
        $result = [
            'success' => true,
            'data' => [
                'sunatResponse' => [
                    'success' => false,
                    'error' => [
                        'code' => '1033',
                        'message' => 'El comprobante fue registrado previamente con otros datos',
                    ],
                ],
            ],
        ];

        $outcome = $this->invokePrivate(
            $this->service(),
            'extractSendOutcome',
            $result
        );

        $this->assertTrue($outcome['confirmed']);
        $this->assertFalse($outcome['accepted']);
        $this->assertSame('1033', $outcome['code']);
    }

    public function test_transport_failure_is_not_confirmed(): void
    {
        $result = [
            'success' => false,
            'message' => 'cURL error 28: Operation timed out',
        ];

        $outcome = $this->invokePrivate(
            $this->service(),
            'extractSendOutcome',
            $result
        );

        $this->assertFalse($outcome['confirmed']);
        $this->assertFalse($outcome['accepted']);
    }
}
