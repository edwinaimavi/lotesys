<?php

namespace Tests\Feature;

use App\Models\CustomerPortalAccount;
use App\Models\PaymentReceipt;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array', 'cache.default' => 'array']);
        DB::purge('sqlite');
        // Explicit isolated schema: no migrate / migrate:fresh, no application database.
        $schemas = [
            'customers' => ['document_type', 'document_number', 'full_name', 'first_name', 'last_name', 'status'],
            'sales' => ['customer_id', 'lot_id', 'sale_code', 'sale_date', 'lot_price', 'status', 'late_fee_setting_id', 'is_legacy_sale', 'collection_rules_start_date'],
            'projects' => ['name'], 'blocks' => ['name'], 'lots' => ['project_id', 'block_id', 'number'],
            'sale_lots' => ['sale_id', 'lot_id', 'sale_price', 'is_primary'],
            'payment_schedules' => ['sale_id', 'installment_number', 'due_date', 'total_amount', 'late_fee', 'remaining_balance', 'status'],
            'payments' => ['sale_id', 'payment_schedule_id', 'payment_date', 'amount', 'payment_method', 'status', 'payment_type', 'late_fee_paid', 'observation'],
            'payment_details' => ['payment_id', 'payment_schedule_id', 'applied_amount'],
            'payment_receipts' => ['payment_id', 'disk', 'path', 'mime_type', 'original_name'],
            'invoices' => ['payment_id', 'sale_id', 'document_type', 'series', 'number', 'pdf_path', 'sunat_status', 'voided_at', 'issue_date', 'total_amount', 'currency'],
            'late_fee_settings' => ['daily_late_fee', 'grace_days', 'max_late_fee', 'apply_sundays', 'apply_holidays'],
            'holidays' => ['date', 'status'],
        ];
        foreach ($schemas as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) { $table->string($column)->nullable(); }
                $table->timestamps();
            });
        }
        Schema::create('customer_portal_accounts', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('customer_id')->unique(); $table->string('password');
            $table->boolean('must_change_password')->default(true); $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable(); $table->timestamps();
        });
        foreach ([1, 2] as $id) {
            DB::table('customers')->insert(['id' => $id, 'document_type' => 'DNI', 'document_number' => $id === 1 ? '12345678' : '87654321', 'full_name' => 'Cliente '.$id, 'status' => 1]);
            DB::table('projects')->insert(['id' => $id, 'name' => 'Proyecto '.$id]);
            DB::table('blocks')->insert(['id' => $id, 'name' => 'A']);
            DB::table('lots')->insert(['id' => $id, 'project_id' => $id, 'block_id' => $id, 'number' => '01']);
            DB::table('sales')->insert(['id' => $id, 'customer_id' => $id, 'lot_id' => $id, 'sale_code' => 'VTA00000'.$id, 'sale_date' => '2026-01-01', 'lot_price' => 1000, 'status' => 'activo']);
            DB::table('payment_schedules')->insert(['id' => $id, 'sale_id' => $id, 'installment_number' => 1, 'due_date' => '2030-01-01', 'total_amount' => 1000, 'late_fee' => 0, 'remaining_balance' => 99000, 'status' => 'parcial']);
            DB::table('payments')->insert(['id' => $id, 'sale_id' => $id, 'payment_schedule_id' => $id, 'payment_date' => '2026-01-01', 'amount' => 200, 'payment_method' => 'efectivo', 'status' => 'activo', 'payment_type' => 'cuota', 'late_fee_paid' => 0, 'observation' => 'SECRETO INTERNO']);
            DB::table('payment_details')->insert(['payment_id' => $id, 'payment_schedule_id' => $id, 'applied_amount' => 200]);
        }
        $private = Storage::fake('portal_receipts_test');
        config(['payments.receipts_storage.root' => $private->path('')]);
        Storage::fake('public');
        foreach ([1, 2] as $id) {
            PaymentReceipt::storage()->put($id.'.pdf', '%PDF-1.4 receipt');
            DB::table('payment_receipts')->insert(['id' => $id, 'payment_id' => $id, 'disk' => PaymentReceipt::DISK, 'path' => $id.'.pdf', 'mime_type' => 'application/pdf', 'original_name' => 'privado.pdf']);
            Storage::disk('public')->put('invoices/'.$id.'.pdf', '%PDF-1.4 invoice');
            DB::table('invoices')->insert(['id' => $id, 'payment_id' => $id, 'sale_id' => $id, 'document_type' => 'invoice', 'series' => 'F001', 'number' => $id, 'pdf_path' => 'invoices/'.$id.'.pdf', 'sunat_status' => 'accepted']);
        }
    }

    private function account(bool $temporary = false): CustomerPortalAccount
    {
        return CustomerPortalAccount::create(['customer_id' => 1, 'password' => Hash::make($temporary ? '12345678' : 'Personal123!'), 'must_change_password' => $temporary]);
    }

    private function login(bool $temporary = false)
    {
        $this->account($temporary);
        return $this->postJson('/portal-cliente/login', ['dni' => '12345678', 'password' => $temporary ? '12345678' : 'Personal123!']);
    }

    public function test_temporary_password_hash_and_mandatory_change(): void
    {
        $this->login(true)->assertOk()->assertJsonPath('must_change_password', true);
        $this->assertTrue(Hash::check('12345678', CustomerPortalAccount::first()->password));
        $this->assertNotSame('12345678', CustomerPortalAccount::first()->password);
        $this->getJson('/portal-cliente/me')->assertForbidden();
        $this->getJson('/portal-cliente/ventas/1')->assertForbidden();
        $this->get('/portal-cliente/comprobantes/1')->assertForbidden();
        $this->get('/portal-cliente/ventas/1/estado-cuenta')->assertForbidden();
        $this->postJson('/portal-cliente/change-password', ['password' => '12345678', 'password_confirmation' => '12345678'])->assertUnprocessable();
        $this->postJson('/portal-cliente/change-password', ['password' => 'Personal123!', 'password_confirmation' => 'Personal123!'])->assertOk()->assertJsonPath('must_change_password', false);
        $this->getJson('/portal-cliente/me')->assertOk();
        $this->postJson('/portal-cliente/logout')->assertOk();
        $this->postJson('/portal-cliente/login', ['dni' => '12345678', 'password' => '12345678'])->assertUnprocessable();
        $this->postJson('/portal-cliente/login', ['dni' => '12345678', 'password' => 'Personal123!'])->assertOk();
    }

    public function test_wrong_credentials_are_generic_and_throttled(): void
    {
        $this->account();
        foreach (range(1, 5) as $i) {
            $this->postJson('/portal-cliente/login', ['dni' => $i % 2 ? '12345678' : '99999999', 'password' => 'wrong'])->assertUnprocessable()->assertExactJson(['message' => 'Los datos ingresados no son correctos.']);
        }
        $this->postJson('/portal-cliente/login', ['dni' => '12345678', 'password' => 'Personal123!'])->assertTooManyRequests();
        $this->getJson('/portal-cliente/me')->assertUnauthorized();
    }

    public function test_admin_guard_remains_guest_and_admin_routes_are_protected(): void
    {
        $this->login()->assertOk();
        $this->assertGuest('web');
        $this->getJson('/dashboard')->assertUnauthorized();
        $this->getJson('/admin/sales')->assertUnauthorized();
        $this->get('/login')->assertOk();
    }

    public function test_logout_preserves_existing_admin_session(): void
    {
        $admin = new User;
        $admin->forceFill(['id' => 100, 'name' => 'Admin']);
        $this->actingAs($admin);
        $this->login()->assertOk();
        $this->postJson('/portal-cliente/logout')->assertOk();
        $this->assertAuthenticatedAs($admin);
        $this->getJson('/portal-cliente/session')->assertUnauthorized();
    }

    public function test_only_owned_operations_and_multiple_lots_share_one_schedule(): void
    {
        DB::table('lots')->insert(['id' => 3, 'project_id' => 1, 'block_id' => 1, 'number' => '02']);
        DB::table('sale_lots')->insert([['sale_id' => 1, 'lot_id' => 1], ['sale_id' => 1, 'lot_id' => 3]]);
        DB::table('sales')->insert(['id' => 3, 'customer_id' => 1, 'lot_id' => 3, 'sale_code' => 'VTA000003', 'status' => 'finalizado']);
        $this->login()->assertOk();
        $this->getJson('/portal-cliente/me')->assertOk()->assertJsonCount(2, 'operations')->assertDontSee('Proyecto 2');
        $this->getJson('/portal-cliente/ventas/1')->assertOk()->assertJsonCount(2, 'lots')->assertJsonCount(1, 'schedules')->assertJsonCount(1, 'payments')
            ->assertJsonPath('summary.paid', 200)->assertJsonPath('summary.balance', 800)->assertJsonPath('schedules.0.status', 'parcial')
            ->assertDontSee('SECRETO INTERNO')->assertDontSee('document_number')->assertDontSee('pdf_path')->assertDontSee('privado.pdf');
        foreach (['', '/cronograma', '/pagos', '/estado-cuenta'] as $suffix) {
            $this->getJson('/portal-cliente/ventas/2'.$suffix)->assertNotFound();
        }
    }

    public function test_schedule_and_payments_are_read_only(): void
    {
        $this->login();
        $before = DB::table('payment_schedules')->get()->toJson();
        $this->getJson('/portal-cliente/ventas/1/cronograma')->assertOk()->assertJsonCount(1);
        $this->getJson('/portal-cliente/ventas/1/pagos')->assertOk()->assertJsonPath('0.id', 1);
        foreach (['postJson', 'putJson', 'deleteJson'] as $method) {
            $this->{$method}('/portal-cliente/ventas/1/cronograma', [])->assertStatus(405);
        }
        $this->assertSame($before, DB::table('payment_schedules')->get()->toJson());
    }

    public function test_owned_documents_stream_and_download_but_foreign_ones_are_denied(): void
    {
        $this->login();
        foreach (['comprobantes', 'facturas'] as $kind) {
            $this->get('/portal-cliente/'.$kind.'/1')->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
            $response = $this->get('/portal-cliente/'.$kind.'/1?download=1')->assertOk();
            $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
            $this->get('/portal-cliente/'.$kind.'/2')->assertNotFound();
        }
        DB::table('invoices')->where('id', 1)->update(['payment_id' => 2]);
        $this->get('/portal-cliente/facturas/1')->assertNotFound();
    }

    public function test_statement_is_owned_and_contains_only_allowlisted_data(): void
    {
        $this->login();
        $response = $this->get('/portal-cliente/ventas/1/estado-cuenta')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $data = app(\App\Services\CustomerPortalStatement::class)->make(\App\Models\Sale::findOrFail(1));
        $html = view('portal.statement', ['data' => $data, 'name' => 'Cliente 1'])->render();
        $this->assertStringContainsString('VTA000001', $html);
        foreach (['Cliente 2', 'Proyecto 2', '12345678', 'SECRETO INTERNO'] as $private) { $this->assertStringNotContainsString($private, $html); }
        $this->get('/portal-cliente/ventas/2/estado-cuenta')->assertNotFound();
    }

    public function test_private_routes_require_session_even_for_admin(): void
    {
        foreach (['session', 'me', 'ventas/1', 'ventas/1/cronograma', 'ventas/1/pagos', 'comprobantes/1', 'facturas/1', 'ventas/1/estado-cuenta'] as $path) {
            $this->get('/portal-cliente/'.$path)->assertUnauthorized();
        }
    }

    public function test_initializer_is_idempotent_and_skips_ineligible_and_duplicate_dnis(): void
    {
        DB::table('customers')->insert(['id' => 3, 'document_type' => 'DNI', 'document_number' => '87654321', 'status' => 1]);
        $this->artisan('portal:initialize-customers')->assertSuccessful();
        $this->assertSame(1, CustomerPortalAccount::count());
        $account = CustomerPortalAccount::first();
        $this->assertTrue($account->must_change_password);
        $this->assertTrue(Hash::check('12345678', $account->password));
        $account->update(['password' => Hash::make('Personal123!'), 'must_change_password' => false]);
        $hash = $account->password;
        $this->artisan('portal:initialize-customers')->assertSuccessful();
        $this->assertSame($hash, $account->fresh()->password);
    }

    public function test_deactivated_account_and_changed_hash_revoke_sessions(): void
    {
        $this->login();
        CustomerPortalAccount::first()->update(['password' => Hash::make('OtherPassword123')]);
        $this->getJson('/portal-cliente/session')->assertUnauthorized();
    }

    public function test_late_fee_uses_existing_rules_and_ignores_voided_payments(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-09-21'));
        DB::table('late_fee_settings')->insert(['id' => 1, 'daily_late_fee' => 5, 'grace_days' => 1, 'max_late_fee' => 100, 'apply_sundays' => 1, 'apply_holidays' => 1]);
        DB::table('sales')->where('id', 1)->update(['late_fee_setting_id' => 1]);
        DB::table('payment_schedules')->where('id', 1)->update(['due_date' => '2026-09-18']);
        DB::table('payments')->insert(['id' => 3, 'sale_id' => 1, 'amount' => 500, 'status' => 'anulado']);
        DB::table('payment_details')->insert(['payment_id' => 3, 'payment_schedule_id' => 1, 'applied_amount' => 500]);
        $this->login();
        $this->getJson('/portal-cliente/ventas/1')->assertOk()->assertJsonPath('summary.paid', 200)->assertJsonPath('summary.balance', 810)->assertJsonPath('schedules.0.late_fee', 10);
    }

    public function test_landing_partial_never_links_to_admin_login(): void
    {
        $html = view('portal.landing')->render();
        $this->assertStringContainsString('/portal-cliente/login', $html);
        $this->assertStringNotContainsString('name="email"', $html);
        $this->assertStringNotContainsString('/admin', $html);
    }

    public function test_sale_note_without_stored_pdf_has_a_private_download(): void
    {
        DB::table('invoices')->where('id', 1)->update(['document_type' => 'sale_note', 'pdf_path' => null, 'total_amount' => 200]);
        $this->login();
        $this->getJson('/portal-cliente/ventas/1')->assertOk()->assertJsonCount(2, 'documents');
        $this->get('/portal-cliente/facturas/1?download=1')->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_csrf_is_required_and_rotated_token_allows_password_change_and_logout(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        $this->account(true);
        $this->postJson('/portal-cliente/login', ['dni' => '12345678', 'password' => '12345678'])->assertStatus(419);
        $this->withSession(['_token' => 'initial-csrf-token']);
        $login = $this->postJson('/portal-cliente/login', ['_token' => 'initial-csrf-token', 'dni' => '12345678', 'password' => '12345678'])->assertOk();
        $changed = $this->postJson('/portal-cliente/change-password', ['_token' => $login->json('csrf_token'), 'password' => 'Personal123!', 'password_confirmation' => 'Personal123!'])->assertOk();
        $this->postJson('/portal-cliente/logout', ['_token' => $changed->json('csrf_token')])->assertOk();
        $this->getJson('/portal-cliente/session')->assertUnauthorized();
    }
}
