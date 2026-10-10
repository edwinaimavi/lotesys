<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceApiLog;
use App\Services\ApisPeruService;
use App\Services\InvoiceSunatRecoveryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class InvoiceSunatRecoveryFlowTest extends TestCase
{
    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Protección crítica del propio test: este archivo crea y elimina
        // tablas mínimas para una simulación aislada. Nunca debe ejecutarse
        // contra MySQL/PostgreSQL ni fuera del entorno testing.
        if (! app()->environment('testing')
            || DB::connection()->getDriverName() !== 'sqlite') {
            $this->fail(
                'InvoiceSunatRecoveryFlowTest solo puede ejecutarse en APP_ENV=testing con SQLite. '
                . 'Se bloqueó antes de modificar cualquier tabla.'
            );
        }

        $this->rebuildIsolatedSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('invoice_api_logs');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('companies');

        parent::tearDown();
    }

    public function test_local_environment_blocks_public_recovery_before_any_api_call(): void
    {
        $fixture = $this->makePendingFixture();

        $api = $this->createMock(ApisPeruService::class);
        $api->expects($this->never())->method('useCompanyRuc');
        $api->expects($this->never())->method('getInvoiceStatus');
        $api->expects($this->never())->method('sendInvoice');

        $service = new InvoiceSunatRecoveryService($api);

        $result = $service->recover($fixture['invoice']);

        $this->assertSame('environment_blocked', $result['status']);
        $this->assertFalse($result['success']);

        $fixture['invoice']->refresh();

        $this->assertSame('pending', $fixture['invoice']->sunat_status);
        $this->assertSame(1, $fixture['invoice']->apiLogs()->count());
    }

    public function test_existing_constancia_is_synchronized_without_resend(): void
    {
        $fixture = $this->makePendingFixture();

        $api = $this->apiMockBase($fixture['invoice']);

        $api->expects($this->once())
            ->method('getInvoiceStatus')
            ->willReturn($this->statusAccepted());

        $api->expects($this->never())
            ->method('sendInvoice');

        $this->expectArtifacts($api);

        $result = $this->invokeRecoveryCore(
            new InvoiceSunatRecoveryService($api),
            $fixture['invoice']
        );

        $fixture['invoice']->refresh();

        $this->assertTrue($result['success']);
        $this->assertSame('accepted', $result['status']);
        $this->assertSame('accepted', $fixture['invoice']->sunat_status);
        $this->assertSame('0', $fixture['invoice']->sunat_code);
        $this->assertSame($fixture['series'], $fixture['invoice']->series);
        $this->assertSame($fixture['number'], $fixture['invoice']->number);

        $this->assertSame(
            ['send', 'status_check'],
            $fixture['invoice']->apiLogs()->orderBy('id')->pluck('action')->all()
        );
    }

    public function test_0127_resends_exact_same_payload_and_accepts_same_correlative(): void
    {
        $fixture = $this->makePendingFixture();

        $api = $this->apiMockBase($fixture['invoice']);

        $api->expects($this->once())
            ->method('getInvoiceStatus')
            ->willReturn($this->statusNotFound());

        $api->expects($this->once())
            ->method('sendInvoice')
            ->with($this->equalTo($fixture['payload']))
            ->willReturn($this->sendAccepted());

        $this->expectArtifacts($api);

        $result = $this->invokeRecoveryCore(
            new InvoiceSunatRecoveryService($api),
            $fixture['invoice']
        );

        $fixture['invoice']->refresh();

        $this->assertTrue($result['success']);
        $this->assertSame('accepted', $result['status']);
        $this->assertSame('accepted', $fixture['invoice']->sunat_status);

        $this->assertSame($fixture['series'], $fixture['invoice']->series);
        $this->assertSame($fixture['number'], $fixture['invoice']->number);

        $logs = $fixture['invoice']->apiLogs()->orderBy('id')->get();

        $this->assertSame(
            ['send', 'status_check', 'retry_send'],
            $logs->pluck('action')->all()
        );

        $retryPayload = json_decode(
            (string) $logs->firstWhere('action', 'retry_send')->request_payload,
            true
        );

        $this->assertSame($fixture['payload'], $retryPayload);
        $this->assertTrue((bool) $logs->firstWhere('action', 'retry_send')->success);
    }

    public function test_0127_then_real_sunat_rejection_marks_invoice_rejected(): void
    {
        $fixture = $this->makePendingFixture();

        $api = $this->apiMockBase($fixture['invoice']);

        $api->expects($this->once())
            ->method('getInvoiceStatus')
            ->willReturn($this->statusNotFound());

        $api->expects($this->once())
            ->method('sendInvoice')
            ->with($this->equalTo($fixture['payload']))
            ->willReturn($this->sendRejected());

        $api->expects($this->never())->method('getInvoicePdf');
        $api->expects($this->never())->method('getInvoiceXml');

        $result = $this->invokeRecoveryCore(
            new InvoiceSunatRecoveryService($api),
            $fixture['invoice']
        );

        $fixture['invoice']->refresh();

        $this->assertFalse($result['success']);
        $this->assertSame('rejected', $result['status']);
        $this->assertSame('rejected', $fixture['invoice']->sunat_status);
        $this->assertSame('1033', $fixture['invoice']->sunat_code);
        $this->assertSame($fixture['series'], $fixture['invoice']->series);
        $this->assertSame($fixture['number'], $fixture['invoice']->number);
    }

    public function test_0127_then_transport_timeout_keeps_pending_and_same_correlative(): void
    {
        $fixture = $this->makePendingFixture();

        $api = $this->apiMockBase($fixture['invoice']);

        $api->expects($this->once())
            ->method('getInvoiceStatus')
            ->willReturn($this->statusNotFound());

        $api->expects($this->once())
            ->method('sendInvoice')
            ->with($this->equalTo($fixture['payload']))
            ->willReturn($this->sendTimeout());

        $api->expects($this->never())->method('getInvoicePdf');
        $api->expects($this->never())->method('getInvoiceXml');

        $result = $this->invokeRecoveryCore(
            new InvoiceSunatRecoveryService($api),
            $fixture['invoice']
        );

        $fixture['invoice']->refresh();

        $this->assertFalse($result['success']);
        $this->assertSame('pending', $result['status']);
        $this->assertSame('pending', $fixture['invoice']->sunat_status);
        $this->assertSame($fixture['series'], $fixture['invoice']->series);
        $this->assertSame($fixture['number'], $fixture['invoice']->number);

        $retry = $fixture['invoice']->apiLogs()
            ->where('action', 'retry_send')
            ->latest('id')
            ->first();

        $this->assertNotNull($retry);
        $this->assertFalse((bool) $retry->success);
    }

    public function test_unknown_status_never_resends(): void
    {
        $fixture = $this->makePendingFixture();

        $api = $this->apiMockBase($fixture['invoice']);

        $api->expects($this->once())
            ->method('getInvoiceStatus')
            ->willReturn($this->statusUnknown());

        $api->expects($this->never())->method('sendInvoice');
        $api->expects($this->never())->method('getInvoicePdf');
        $api->expects($this->never())->method('getInvoiceXml');

        $result = $this->invokeRecoveryCore(
            new InvoiceSunatRecoveryService($api),
            $fixture['invoice']
        );

        $fixture['invoice']->refresh();

        $this->assertFalse($result['success']);
        $this->assertSame('pending', $result['status']);
        $this->assertSame('pending', $fixture['invoice']->sunat_status);
        $this->assertSame(
            0,
            $fixture['invoice']->apiLogs()->where('action', 'retry_send')->count()
        );
    }

    private function rebuildIsolatedSchema(): void
    {
        Schema::dropIfExists('invoice_api_logs');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('companies');

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('business_name')->nullable();
            $table->string('trade_name')->nullable();
            $table->string('ruc', 20);
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('related_invoice_id')->nullable();

            $table->string('document_type', 30);
            $table->string('series', 10);
            $table->string('number', 20);
            $table->date('issue_date');

            $table->string('customer_document_type', 2)->nullable();
            $table->string('customer_document', 20)->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_address')->nullable();
            $table->string('customer_department')->nullable();
            $table->string('customer_province')->nullable();
            $table->string('customer_district')->nullable();
            $table->string('customer_ubigeo', 10)->nullable();

            $table->text('concept')->nullable();
            $table->text('legend')->nullable();
            $table->string('currency', 10)->default('PEN');

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);

            $table->string('sunat_status', 20)->default('pending');
            $table->string('hash_code')->nullable();

            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();
            $table->string('pdf_path')->nullable();

            $table->string('sunat_ticket')->nullable();
            $table->string('sunat_code')->nullable();
            $table->text('sunat_message')->nullable();

            $table->string('credit_note_reason_code', 2)->nullable();
            $table->text('credit_note_reason')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
        });

        Schema::create('invoice_api_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('action', 50)->nullable();
            $table->string('provider', 50)->default('APISPERU');
            $table->string('endpoint')->nullable();
            $table->integer('http_status')->nullable();
            $table->boolean('success')->default(false);
            $table->longText('request_payload')->nullable();
            $table->longText('response_body')->nullable();
            $table->longText('response_json')->nullable();
            $table->text('error_message')->nullable();
            $table->text('exception_message')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    private function makePendingFixture(): array
    {
        $this->sequence++;

        $company = Company::create([
            'business_name' => 'EMPRESA TEST LOCAL',
            'trade_name' => 'TEST',
            'ruc' => '20607752312',
            'status' => 'active',
        ]);

        $series = 'BTST';
        $number = 'FLOW' . $this->sequence;

        $invoice = Invoice::create([
            'payment_id' => null,
            'sale_id' => null,
            'company_id' => $company->id,
            'document_type' => 'receipt',
            'series' => $series,
            'number' => $number,
            'issue_date' => now()->toDateString(),
            'customer_document_type' => '1',
            'customer_document' => '12345678',
            'customer_name' => 'CLIENTE TEST LOCAL',
            'concept' => 'SIMULACION LOCAL SIN RED',
            'legend' => 'SIMULACION LOCAL',
            'currency' => 'PEN',
            'subtotal' => 100,
            'tax_amount' => 0,
            'total_amount' => 100,
            'sunat_status' => 'pending',
            'sunat_code' => null,
            'sunat_message' => 'SIMULACION LOCAL SIN RED',
        ]);

        $payload = [
            'ublVersion' => '2.1',
            'tipoOperacion' => '0101',
            'tipoDoc' => '03',
            'serie' => $series,
            'correlativo' => $number,
            'fechaEmision' => now()->format('Y-m-d\TH:i:sP'),
            'tipoMoneda' => 'PEN',
            'company' => [
                'ruc' => (string) $company->ruc,
            ],
            'client' => [
                'tipoDoc' => '1',
                'numDoc' => '12345678',
                'rznSocial' => 'CLIENTE TEST LOCAL',
            ],
            'details' => [
                [
                    'codProducto' => 'TEST',
                    'unidad' => 'NIU',
                    'descripcion' => 'SIMULACION LOCAL SIN RED',
                    'cantidad' => 1,
                    'mtoValorUnitario' => 100,
                    'mtoValorVenta' => 100,
                ],
            ],
            'mtoImpVenta' => 100,
        ];

        InvoiceApiLog::create([
            'invoice_id' => $invoice->id,
            'action' => 'send',
            'provider' => 'APISPERU',
            'endpoint' => 'https://facturacion.apisperu.com/api/v1/invoice/send',
            'http_status' => null,
            'success' => false,
            'request_payload' => json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            'response_body' => null,
            'response_json' => null,
            'error_message' => null,
            'exception_message' => 'cURL error 28: Operation timed out after 30001 milliseconds with 0 bytes received',
            'created_by' => null,
        ]);

        $invoice->load('company');

        return [
            'invoice' => $invoice,
            'payload' => $payload,
            'series' => $series,
            'number' => $number,
        ];
    }

    private function apiMockBase(Invoice $invoice): ApisPeruService
    {
        $api = $this->createMock(ApisPeruService::class);

        $api->expects($this->once())
            ->method('useCompanyRuc')
            ->with((string) $invoice->company->ruc)
            ->willReturnSelf();

        return $api;
    }

    private function expectArtifacts(ApisPeruService $api): void
    {
        $api->expects($this->once())
            ->method('getInvoicePdf')
            ->willReturn([
                'success' => true,
                'data' => '%PDF-FAKE-LOCAL',
            ]);

        $api->expects($this->once())
            ->method('getInvoiceXml')
            ->willReturn([
                'success' => true,
                'data' => '<xml>FAKE-LOCAL</xml>',
            ]);
    }

    private function invokeRecoveryCore(
        InvoiceSunatRecoveryService $service,
        Invoice $invoice
    ): array {
        // Solo el test invoca el núcleo privado con un API 100% mockeado.
        // El bloqueo público local/testing permanece intacto.
        $method = new ReflectionMethod(
            InvoiceSunatRecoveryService::class,
            'recoverLocked'
        );

        $method->setAccessible(true);

        return $method->invoke($service, $invoice, false, null);
    }

    private function statusAccepted(): array
    {
        $data = [
            'success' => true,
            'cdrResponse' => [
                'id' => 'BTST-FLOW',
                'code' => '0',
                'description' => 'La Boleta ha sido aceptada',
                'notes' => [],
            ],
            'code' => '0004',
            'message' => 'La constancia existe',
        ];

        return [
            'success' => true,
            'data' => $data,
            'endpoint' => 'https://facturacion.apisperu.com/api/v1/invoice/status',
            'http_status' => 200,
            'response_body' => json_encode($data),
            'response_json' => $data,
            'error_message' => null,
            'exception_message' => null,
        ];
    }

    private function statusNotFound(): array
    {
        $data = [
            'success' => false,
            'error' => [
                'code' => '0127',
                'message' => '',
            ],
            'code' => '0127',
            'message' => 'El ticket no existe',
        ];

        return [
            'success' => true,
            'data' => $data,
            'endpoint' => 'https://facturacion.apisperu.com/api/v1/invoice/status',
            'http_status' => 200,
            'response_body' => json_encode($data),
            'response_json' => $data,
            'error_message' => null,
            'exception_message' => null,
        ];
    }

    private function statusUnknown(): array
    {
        $data = [
            'success' => false,
            'code' => '9999',
            'message' => 'Respuesta no concluyente',
        ];

        return [
            'success' => true,
            'data' => $data,
            'endpoint' => 'https://facturacion.apisperu.com/api/v1/invoice/status',
            'http_status' => 200,
            'response_body' => json_encode($data),
            'response_json' => $data,
            'error_message' => null,
            'exception_message' => null,
        ];
    }

    private function sendAccepted(): array
    {
        $data = [
            'sunatResponse' => [
                'success' => true,
                'cdrResponse' => [
                    'code' => '0',
                    'description' => 'Aceptado por SUNAT',
                ],
            ],
            'hash' => 'HASH-FAKE-LOCAL',
        ];

        return [
            'success' => true,
            'data' => $data,
            'endpoint' => 'https://facturacion.apisperu.com/api/v1/invoice/send',
            'http_status' => 200,
            'response_body' => json_encode($data),
            'response_json' => $data,
            'error_message' => null,
            'exception_message' => null,
        ];
    }

    private function sendRejected(): array
    {
        $data = [
            'sunatResponse' => [
                'success' => false,
                'error' => [
                    'code' => '1033',
                    'message' => 'El comprobante fue registrado previamente con otros datos',
                ],
            ],
        ];

        return [
            'success' => true,
            'data' => $data,
            'endpoint' => 'https://facturacion.apisperu.com/api/v1/invoice/send',
            'http_status' => 200,
            'response_body' => json_encode($data),
            'response_json' => $data,
            'error_message' => null,
            'exception_message' => null,
        ];
    }

    private function sendTimeout(): array
    {
        return [
            'success' => false,
            'message' => 'cURL error 28: Operation timed out after 30001 milliseconds with 0 bytes received',
            'endpoint' => 'https://facturacion.apisperu.com/api/v1/invoice/send',
            'http_status' => null,
            'response_body' => null,
            'response_json' => null,
            'error_message' => null,
            'exception_message' => 'cURL error 28: Operation timed out after 30001 milliseconds with 0 bytes received',
        ];
    }
}
