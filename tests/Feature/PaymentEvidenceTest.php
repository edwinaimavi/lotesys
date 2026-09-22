<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentEvidenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Esquema mínimo aislado: nunca ejecuta migraciones sobre la BD del proyecto.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) { $table->id(); });
        Schema::create('banks', function (Blueprint $table) { $table->id(); $table->string('bank_name'); });
        Schema::create('lots', function (Blueprint $table) { $table->id(); $table->string('status')->nullable(); });
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('late_fee_setting_id')->nullable();
            $table->string('status')->default('activo');
            $table->timestamps();
        });
        foreach ([
            '2026_05_12_033025_create_payment_schedules_table.php',
            '2026_05_12_140944_create_payments_table.php',
            '2026_05_12_153738_create_payment_details_table.php',
            '2026_05_23_045336_add_bank_id_to_payments_table.php',
            '2026_09_19_000001_add_origin_bank_to_payments_table.php',
            '2026_09_19_000002_create_payment_receipts_table.php',
        ] as $migration) {
            (require database_path('migrations/' . $migration))->up();
        }
        DB::table('users')->insert(['id' => 1]);
        DB::table('sales')->insert(['id' => 1]);
        DB::table('payment_schedules')->insert([
            'id' => 1, 'sale_id' => 1, 'installment_number' => 1,
            'due_date' => '2030-01-01', 'total_amount' => 2000, 'remaining_balance' => 2000,
        ]);
        $user = new User;
        $user->forceFill(['id' => 1, 'name' => 'Prueba pagos', 'email_verified_at' => now()]);
        $user->exists = true;
        $this->actingAs($user);
        $fake = Storage::fake('payment_evidence_test');
        config(['payments.receipts_storage.root' => $fake->path('')]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'sale_id' => 1, 'payment_type' => 'cuota', 'payment_date' => '2026-09-19',
            'amount' => 800, 'late_fee_paid' => 0, 'discount' => 0, 'status' => 'activo',
            'payment_method' => 'transferencia', 'origin_bank' => 'BBVA Perú',
            'operation_number' => 'REF-123',
            'payment_details' => [['payment_schedule_id' => 1, 'applied_amount' => 800]],
        ], $overrides);
    }

    private function store(array $overrides = [])
    {
        return $this->postJson(route('admin.payments.store'), $this->payload($overrides));
    }

    public function test_transfer_requires_origin_bank(): void
    {
        $this->store(['origin_bank' => null])->assertUnprocessable()->assertJsonValidationErrors('origin_bank');
    }

    public function test_transfer_requires_reference(): void
    {
        $this->store(['operation_number' => null])->assertUnprocessable()->assertJsonValidationErrors('operation_number');
    }

    public function test_transfer_stores_origin_without_using_company_bank(): void
    {
        $this->store(['bank_id' => 999])->assertCreated();
        $this->assertDatabaseHas('payments', ['origin_bank' => 'BBVA Perú', 'operation_number' => 'REF-123', 'bank_id' => null]);
    }

    public function test_other_entity_stores_its_final_name(): void
    {
        $this->store(['origin_bank' => 'Otra entidad', 'origin_bank_other' => 'Caja de prueba'])->assertCreated();
        $this->assertDatabaseHas('payments', ['origin_bank' => 'Caja de prueba']);
    }

    public function test_other_entity_requires_name(): void
    {
        $this->store(['origin_bank' => 'Otra entidad'])->assertUnprocessable()->assertJsonValidationErrors('origin_bank_other');
    }

    public function test_yape_requires_no_bank_and_discards_stale_bank(): void
    {
        $this->store(['payment_method' => 'yape'])->assertCreated();
        $this->assertDatabaseHas('payments', ['payment_method' => 'yape', 'origin_bank' => null, 'operation_number' => 'REF-123']);
    }

    public function test_yape_requires_reference(): void
    {
        $this->store(['payment_method' => 'yape', 'operation_number' => null])->assertUnprocessable()->assertJsonValidationErrors('operation_number');
    }

    public function test_plin_requires_reference(): void
    {
        $this->store(['payment_method' => 'plin', 'operation_number' => null])->assertUnprocessable()->assertJsonValidationErrors('operation_number');
    }

    public function test_deposit_requires_reference(): void
    {
        $this->store(['payment_method' => 'deposito', 'operation_number' => null])->assertUnprocessable()->assertJsonValidationErrors('operation_number');
    }

    public function test_cash_clears_bank_and_reference(): void
    {
        $this->store(['payment_method' => 'efectivo'])->assertCreated();
        $this->assertDatabaseHas('payments', ['origin_bank' => null, 'operation_number' => null]);
    }

    public function test_two_receipts_belong_to_one_payment_and_are_private(): void
    {
        $response = $this->store(['receipts' => [UploadedFile::fake()->image('uno.jpg'), UploadedFile::fake()->image('dos.png')]])->assertCreated();
        $payment = Payment::findOrFail($response->json('data.id'));
        $this->assertCount(2, $payment->receipts);
        $this->assertSame(1, Payment::count());
        foreach ($payment->receipts as $receipt) {
            $this->assertSame($payment->id, $receipt->payment_id);
            $this->assertTrue(PaymentReceipt::storage()->exists($receipt->path));
            $this->assertNotSame($receipt->original_name, basename($receipt->path));
            $this->assertArrayNotHasKey('path', $receipt->toArray());
        }
    }

    public function test_disallowed_or_disguised_files_are_rejected(): void
    {
        foreach (['payload.php', 'payload.svg', 'payload.html', 'payload.jpg'] as $name) {
            $source = UploadedFile::fake()->createWithContent($name, '<?php echo "unsafe";');
            // UploadedFile::fake infiere MIME desde el nombre; el upload real debe inspeccionar los bytes.
            $file = new UploadedFile($source->getPathname(), $name, null, null, true);
            $this->store(['receipts' => [$file]])->assertUnprocessable()->assertJsonValidationErrors('receipts.0');
        }
        $this->assertSame(0, Payment::count());
    }

    public function test_size_and_count_limits(): void
    {
        $this->store(['receipts' => [UploadedFile::fake()->image('large.jpg')->size(5121)]])->assertUnprocessable();
        $files = array_map(fn ($i) => UploadedFile::fake()->image("$i.jpg"), range(1, 11));
        $this->store(['receipts' => $files])->assertUnprocessable()->assertJsonValidationErrors('receipts');
    }

    public function test_receipt_of_another_payment_is_not_accessible(): void
    {
        $first = $this->store(['receipts' => [UploadedFile::fake()->image('voucher.jpg')]])->assertCreated()->json('data.id');
        $second = $this->store()->assertCreated()->json('data.id');
        $receipt = Payment::findOrFail($first)->receipts()->firstOrFail();
        $this->get(route('admin.payments.receipts.show', [$second, $receipt]))->assertNotFound();
        $this->get(route('admin.payments.receipts.show', [$first, $receipt]))->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_receipt_route_requires_authentication(): void
    {
        $id = $this->store(['receipts' => [UploadedFile::fake()->image('voucher.jpg')]])->assertCreated()->json('data.id');
        $receipt = Payment::findOrFail($id)->receipts()->firstOrFail();
        auth()->forgetGuards();
        $this->getJson(route('admin.payments.receipts.show', [$id, $receipt]))->assertUnauthorized();
    }

    public function test_update_validates_and_clears_inapplicable_fields(): void
    {
        $id = $this->store()->assertCreated()->json('data.id');
        $url = route('admin.payments.update', $id);
        $this->putJson($url, $this->payload(['operation_number' => null]))->assertUnprocessable();
        $this->putJson($url, $this->payload(['payment_method' => 'efectivo']))->assertOk();
        $this->assertDatabaseHas('payments', ['id' => $id, 'origin_bank' => null, 'operation_number' => null]);
    }

    public function test_reference_and_bank_maximum_length(): void
    {
        $this->store(['origin_bank' => str_repeat('x', 101)])->assertUnprocessable()->assertJsonValidationErrors('origin_bank');
        $this->store(['operation_number' => str_repeat('x', 101)])->assertUnprocessable()->assertJsonValidationErrors('operation_number');
    }

    public function test_update_enforces_total_receipt_limit_and_preserves_existing_files(): void
    {
        $files = array_map(fn ($i) => UploadedFile::fake()->image("$i.jpg"), range(1, 10));
        $id = $this->store(['receipts' => $files])->assertCreated()->json('data.id');
        $this->putJson(route('admin.payments.update', $id), $this->payload([
            'receipts' => [UploadedFile::fake()->image('extra.jpg')],
        ]))->assertUnprocessable()->assertJsonValidationErrors('receipts');
        $this->assertSame(10, PaymentReceipt::count());
        $this->assertCount(10, PaymentReceipt::storage()->allFiles());
    }

    public function test_annulled_status_keeps_receipts_as_evidence(): void
    {
        $id = $this->store(['receipts' => [UploadedFile::fake()->image('voucher.jpg')]])->assertCreated()->json('data.id');
        $payment = Payment::findOrFail($id);
        $payment->update(['status' => 'anulado']);
        $this->assertSame(1, $payment->receipts()->count());
        $this->assertCount(1, PaymentReceipt::storage()->allFiles());
    }

    public function test_historical_bank_is_only_a_display_fallback(): void
    {
        $id = $this->store(['payment_method' => 'efectivo'])->assertCreated()->json('data.id');
        DB::table('banks')->insert(['id' => 1, 'bank_name' => 'Cuenta empresa']);
        Payment::findOrFail($id)->update(['bank_id' => 1]);
        $this->getJson(route('admin.payments.evidence', $id))->assertOk()
            ->assertJsonPath('origin_bank', null)->assertJsonPath('historical_bank', 'Cuenta empresa');
        $this->putJson(route('admin.payments.update', $id), $this->payload(['bank_id' => 999]))->assertOk();
        $this->assertDatabaseHas('payments', ['id' => $id, 'bank_id' => 1, 'origin_bank' => 'BBVA Perú']);
        $this->getJson(route('admin.payments.evidence', $id))->assertOk()->assertJsonPath('historical_bank', null);
    }

    public function test_database_failure_removes_uploaded_files_and_rolls_back(): void
    {
        PaymentReceipt::creating(function () { throw new \RuntimeException('Simulated receipt failure'); });
        try {
            $this->store(['receipts' => [UploadedFile::fake()->image('voucher.jpg')]])->assertStatus(500);
            $this->assertSame(0, Payment::count());
            $this->assertSame([], PaymentReceipt::storage()->allFiles());
            $this->assertDatabaseHas('payment_schedules', ['id' => 1, 'remaining_balance' => 2000, 'status' => 'pendiente']);
        } finally {
            PaymentReceipt::flushEventListeners();
        }
    }
}
