<?php

namespace Tests\Feature;

use App\Models\{Block, Company, Customer, Lot, PaymentSchedule, Project, Sale, SaleLot, User};
use App\Services\Contracts\{AmountToWordsService, SaleContractService};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use ZipArchive;

class SaleContractTest extends TestCase
{
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        // These tests cannot touch the application's configured database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2025_06_27_220837_create_permission_tables.php'))->up();
        foreach ([Company::class, Project::class, Block::class, Lot::class, Customer::class, Sale::class, SaleLot::class, PaymentSchedule::class] as $class) {
            $model = new $class;
            Schema::create($model->getTable(), function (Blueprint $table) use ($model) {
                $table->id();
                foreach ($model->getFillable() as $field) {
                    if ($field !== 'gender') {
                        $table->string($field)->nullable();
                    }
                }
                $table->timestamps();
            });
        }
        (require database_path('migrations/2026_09_22_000001_add_gender_to_customers_table.php'))->up();
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) unlink($path);
        }
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    private function authorizeUser(bool $authorized = true): User
    {
        $user = User::factory()->create();
        if ($authorized) {
            $user->givePermissionTo(Permission::findOrCreate('admin.sales.index', 'web'));
        }
        $this->actingAs($user);

        return $user;
    }

    private function sale(string $ruc = '20607752312'): Sale
    {
        $company = Company::create(['business_name' => 'Grupo Krea de prueba', 'ruc' => $ruc]);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Proyecto Prueba', 'code' => 'PR01']);
        $block = Block::create(['project_id' => $project->id, 'name' => 'B']);
        $lot = Lot::create(['project_id' => $project->id, 'block_id' => $block->id, 'number' => '08', 'code' => 'B08', 'area' => '178.79', 'unit_measure' => 'm²']);
        $customer = Customer::create(['first_name' => 'Ana', 'last_name' => 'Prueba', 'full_name' => 'Ana Prueba', 'gender' => 'femenino', 'document_type' => 'DNI', 'document_number' => '01099395']);

        return Sale::create(['customer_id' => $customer->id, 'lot_id' => $lot->id, 'sale_code' => 'VTA00001', 'sale_date' => '2026-07-24', 'sale_type' => 'financiado', 'lot_price' => '51113.28', 'initial_payment' => '1113.28', 'balance_finance' => '50000.00', 'monthly_payment' => '999.00', 'installments_count' => '2']);
    }

    private function quota(Sale $sale, int $number, string $amount = '713.28', string $type = 'cuota'): void
    {
        $sale->paymentSchedules()->create(['schedule_type' => $type, 'installment_number' => $number, 'installment_amount' => $amount, 'due_date' => '2026-09-05']);
    }

    private function data(Sale $sale): array
    {
        return app(SaleContractService::class)->data($sale);
    }

    public function test_guest_cannot_read_or_generate_contract(): void
    {
        $this->getJson('/admin/sales/1/contract-data')->assertUnauthorized();
        $this->postJson('/admin/sales/1/contract-generate')->assertUnauthorized();
    }

    public function test_nonexistent_sale_returns_404(): void
    {
        $this->authorizeUser();
        $this->getJson('/admin/sales/9999/contract-data')->assertNotFound();
        $this->postJson('/admin/sales/9999/contract-generate')->assertNotFound();
    }

    public function test_sales_permission_is_required_for_both_endpoints(): void
    {
        $sale = $this->sale();
        $this->authorizeUser(false);
        $this->getJson('/admin/sales/'.$sale->id.'/contract-data')->assertForbidden();
        $this->postJson('/admin/sales/'.$sale->id.'/contract-generate')->assertForbidden();
    }

    public function test_krea_template_and_customer_defaults_and_nulls(): void
    {
        $sale = $this->sale();
        $this->authorizeUser();
        $response = $this->getJson('/admin/sales/'.$sale->id.'/contract-data')->assertOk();
        $response->assertJsonPath('template.ruc', '20607752312')->assertJsonPath('values.full_name', 'Ana Prueba')
            ->assertJsonPath('values.gender', 'femenino')->assertJsonPath('values.buyer_role', 'La Compradora')
            ->assertJsonPath('values.buyer_nationality', 'peruana')->assertJsonPath('values.representative_document', '74723182');
        foreach (['address', 'district', 'province', 'department', 'matrix_property_name', 'initial_payment_bank', 'buyer_marital_status'] as $key) {
            $response->assertJsonPath('values.'.$key, '');
        }
        $this->assertStringContainsString('_parametrizado.docx', $this->data($sale)['template']['path']);
    }

    public function test_other_company_does_not_get_krea_template_even_with_posted_ruc(): void
    {
        $sale = $this->sale('20111111111');
        $this->authorizeUser();
        $this->getJson('/admin/sales/'.$sale->id.'/contract-data')->assertUnprocessable()->assertJsonValidationErrors('contract');
        $this->postJson('/admin/sales/'.$sale->id.'/contract-generate', ['values' => ['company_ruc' => '20607752312']])
            ->assertUnprocessable()->assertJsonPath('errors.contract.0', 'No existe una plantilla de contrato configurada para esta empresa.');
    }

    public function test_barrio_fino_resolves_by_ruc_with_its_own_template_and_empty_manual_defaults(): void
    {
        $sale = $this->sale('20615005917');
        $company = $sale->lot->project->company;
        $company->update([
            'business_name' => 'CONSTRUCTORA E INMOBILIARIA BARRIO FINO S.A.C.',
            'trade_name' => 'Grupo Krea', // The trade name must not select the template.
            'address' => 'Dirección Barrio Fino', 'phone' => '999123456', 'email' => 'barrio@example.test',
        ]);
        $this->authorizeUser();
        $response = $this->getJson('/admin/sales/'.$sale->id.'/contract-data')->assertOk();
        $response->assertJsonPath('template.name', 'Barrio Fino')
            ->assertJsonPath('template.ruc', '20615005917')
            ->assertJsonPath('template.max_installments', 35)
            ->assertJsonPath('template.max_bills', 35);
        $template = $this->data($sale)['template'];
        $this->assertSame('app/private/contracts/templates/barrio-fino/contrato_compra_venta_lote_barrio_fino_parametrizado.docx', $template['path']);
        $this->assertNotSame(config('contracts.templates.grupo_krea.path'), $template['path']);
        foreach (['business_name', 'trade_name', 'ruc', 'address', 'phone', 'email'] as $field) {
            $response->assertJsonPath('values.company_'.$field, $company->$field);
        }
        foreach (['representative_name', 'representative_document', 'representative_registry_entry', 'registration_payment_due_date'] as $field) {
            $response->assertJsonPath('values.'.$field, '')
                ->assertJsonPath('fields.'.$field.'.source', null)
                ->assertJsonPath('fields.'.$field.'.in_template', true);
        }
    }

    public function test_barrio_fino_uses_real_ordered_quotas_and_clears_remaining_bills_and_schedule_slots(): void
    {
        $sale = $this->sale('20615005917');
        $this->quota($sale, 2, '502.25');
        $this->quota($sale, 0, '1113.28', 'inicial');
        $this->quota($sale, 1, '701.99');
        $this->quota($sale, 2, '603.50');
        $data = $this->data($sale);
        $this->assertSame(3, $data['schedule_count']);
        foreach (['701.99', '502.25', '603.50'] as $index => $amount) {
            $n = sprintf('%02d', $index + 1);
            $this->assertSame($amount, $data['values']['schedule_'.$n.'_installment_amount']);
            $this->assertSame($amount, $data['values']['bill_'.$n.'_amount']);
        }
        $this->assertSame('', $data['values']['registration_payment_due_date']);
        $input = $data['values'];
        $input['schedule_35_installment_amount'] = '999.00';
        $input['bill_35_number'] = '35';
        $values = app(SaleContractService::class)->validateValues(['values' => $input], $data);
        for ($i = 4; $i <= 35; $i++) {
            $n = sprintf('%02d', $i);
            foreach (['installment_number', 'installment_amount', 'installment_amount_words', 'due_date'] as $suffix) {
                $this->assertSame('', $values['schedule_'.$n.'_'.$suffix]);
            }
            foreach (['amount', 'amount_words', 'number'] as $suffix) {
                $this->assertSame('', $values['bill_'.$n.'_'.$suffix]);
            }
        }
    }

    public function test_barrio_fino_36_quotas_return_a_controlled_error_on_both_endpoints(): void
    {
        $sale = $this->sale('20615005917');
        for ($i = 1; $i <= 36; $i++) $this->quota($sale, $i);
        $this->authorizeUser();
        foreach (['getJson' => 'contract-data', 'postJson' => 'contract-generate'] as $method => $endpoint) {
            $this->$method('/admin/sales/'.$sale->id.'/'.$endpoint)->assertUnprocessable()
                ->assertJsonPath('errors.contract.0', 'La plantilla de Barrio Fino admite hasta 35 cuotas.');
        }
    }

    public function test_barrio_fino_generates_35_quotas_with_manual_date_intact_media_and_no_database_writes(): void
    {
        // The lightweight fixture defaults to strings; multi-digit quota numbers need numeric ordering.
        Schema::table('payment_schedules', function (Blueprint $table) {
            $table->unsignedInteger('installment_number')->nullable()->change();
        });
        $sale = $this->sale('20615005917');
        $sale->lot->project->company->update(['business_name' => 'CONSTRUCTORA E INMOBILIARIA BARRIO FINO S.A.C.']);
        for ($i = 35; $i >= 1; $i--) $this->quota($sale, $i, $i === 35 ? '8123.45' : '712.34');
        $this->quota($sale, 0, '1113.28', 'inicial');
        $data = $this->data($sale);
        $this->assertSame(35, $data['schedule_count']);
        for ($i = 1; $i <= 35; $i++) {
            $n = sprintf('%02d', $i);
            $amount = $i === 35 ? '8123.45' : '712.34';
            $this->assertSame((string) $i, $data['values']['schedule_'.$n.'_installment_number']);
            $this->assertSame($amount, $data['values']['bill_'.$n.'_amount']);
            $this->assertSame($n, $data['values']['bill_'.$n.'_number']);
        }
        $this->assertSame('', $data['values']['registration_payment_due_date']);
        $values = array_replace($data['values'], ['registration_payment_due_date' => '17/11/2030']);
        $tables = ['customers', 'sales', 'projects', 'lots', 'companies', 'payment_schedules', 'sale_lots'];
        $before = [];
        foreach ($tables as $table) $before[$table] = DB::table($table)->get()->toJson();
        $this->authorizeUser();
        $response = $this->postJson('/admin/sales/'.$sale->id.'/contract-generate', ['values' => $values])->assertOk();
        $response->assertDownload('Contrato_VTA00001_01099395_Barrio_Fino.docx');
        $path = $response->baseResponse->getFile()->getPathname();
        $this->temporaryFiles[] = $path;
        $zip = new ZipArchive;
        $template = new ZipArchive;
        $this->assertTrue($zip->open($path, ZipArchive::CHECKCONS));
        $this->assertTrue($template->open(storage_path($data['template']['path']), ZipArchive::CHECKCONS));
        try {
            $this->assertNotFalse($zip->locateName('[Content_Types].xml'));
            $this->assertNotFalse($zip->locateName('word/document.xml'));
            $images = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if (str_starts_with($entry, 'word/media/')) {
                    $images[] = $entry;
                    $this->assertSame($template->getFromName($entry), $zip->getFromName($entry), $entry);
                }
                if (str_ends_with($entry, '.xml') || str_ends_with($entry, '.rels')) {
                    $part = new \DOMDocument;
                    $this->assertTrue($part->loadXML($zip->getFromName($entry)), $entry);
                    $this->assertStringNotContainsString('${', $part->textContent, $entry);
                }
                if (str_ends_with($entry, '.rels')) {
                    $this->assertSame($template->getFromName($entry), $zip->getFromName($entry), $entry);
                }
            }
            sort($images);
            $this->assertSame([
                'word/media/image1.png', 'word/media/image2.png', 'word/media/image3.png', 'word/media/image4.png',
                'word/media/image5.png', 'word/media/image6.jpg', 'word/media/image7.png', 'word/media/image8.jpg',
            ], $images);
            $doc = new \DOMDocument;
            $this->assertTrue($doc->loadXML($zip->getFromName('word/document.xml')));
            foreach (['CONSTRUCTORA E INMOBILIARIA BARRIO FINO S.A.C.', '20615005917', '17/11/2030', '8,123.45', $data['values']['lot_price_words']] as $text) {
                $this->assertStringContainsString($text, $doc->textContent);
            }
            foreach (['KENNY JOSUE FARJE PULACHE', '74723182', '11175694', 'MS KREA S.A.C.'] as $text) {
                $this->assertStringNotContainsString($text, $doc->textContent);
            }
        } finally {
            $template->close();
            $zip->close();
        }
        foreach ($tables as $table) $this->assertSame($before[$table], DB::table($table)->get()->toJson(), $table);
    }

    public function test_single_lot_legacy_fallback(): void
    {
        $data = $this->data($this->sale());
        $this->assertSame('08', $data['values']['lot_number']);
        $this->assertSame('B08', $data['values']['lot_01_lot_code']);
        $this->assertSame(1, $data['lots_count']);
    }

    public function test_farje_resolution_aliases_gender_and_unused_slots(): void
    {
        $sale = $this->sale('20610686665');
        $sale->lot->project->company->update(['business_name' => 'FARJE INGENIEROS E.I.R.L.', 'trade_name' => 'Grupo Krea']);
        $this->quota($sale, 2, '502.25');
        $this->quota($sale, 1, '701.99');
        $this->quota($sale, 0, '100.00', 'inicial');
        $this->authorizeUser();
        $response = $this->getJson('/admin/sales/'.$sale->id.'/contract-data')->assertOk();
        $response->assertJsonPath('template.name', 'FARJE')->assertJsonPath('template.ruc', '20610686665')
            ->assertJsonPath('template.max_installments', 36)
            ->assertJsonPath('values.company_business_name', 'FARJE INGENIEROS E.I.R.L.')
            ->assertJsonPath('values.representative_name', 'KENNY JOSUE FARJE PULACHE')
            ->assertJsonPath('values.representative_document', '74723182')
            ->assertJsonPath('values.representative_registry_entry', '11175694')
            ->assertJsonPath('values.buyer_informed', 'enterada')->assertJsonPath('values.buyer_obligated', 'obligada')
            ->assertJsonPath('gender_defaults.masculino.buyer_obligated', 'obligado');
        $data = $this->data($sale);
        $this->assertSame('app/private/contracts/templates/farje/contrato_compra_venta_lote_farje_parametrizado.docx', $data['template']['path']);
        foreach (['grupo_krea', 'barrio_fino'] as $other) {
            $this->assertNotSame(config('contracts.templates.'.$other.'.path'), $data['template']['path']);
        }
        foreach (['matrix_property_name', 'initial_payment_dates', 'bank_name', 'bank_account_number'] as $key) {
            $this->assertSame('', $data['values'][$key]);
            $this->assertNull($data['fields'][$key]['source']);
        }
        $this->assertSame('701.99', $data['values']['schedule_01_installment_amount']);
        $this->assertSame('502.25', $data['values']['schedule_02_installment_amount']);
        $this->assertArrayNotHasKey('bill_01_amount', $data['values']);
        for ($i = 3; $i <= 36; $i++) {
            foreach (['installment_amount', 'installment_amount_words', 'due_date'] as $suffix) {
                $this->assertSame('', $data['values']['schedule_'.sprintf('%02d', $i).'_'.$suffix]);
            }
        }
        $service = app(SaleContractService::class);
        $values = $service->validateValues(['values' => array_replace($data['values'], [
            'gender' => 'masculino', 'initial_payment_bank' => 'Banco manual',
            'initial_payment_account' => '123456', 'initial_payment_dates_text' => '24/07/2026',
        ])], $data);
        $this->assertSame('enterado', $values['buyer_informed']);
        $this->assertSame('obligado', $values['buyer_obligated']);
        $this->assertSame('Banco manual', $values['bank_name']);
        $this->assertSame('123456', $values['bank_account_number']);
        $this->assertSame('24/07/2026', $values['initial_payment_dates']);
        $sale->customer->update(['gender' => 'no_especificado']);
        $data = $this->data($sale);
        $this->assertSame('', $data['values']['buyer_informed']);
        $this->assertSame('', $data['values']['buyer_obligated']);
        $manual = ['buyer_informed' => 'con conocimiento', 'buyer_obligated' => 'con obligación', 'bank_name' => 'Banco elegido'];
        $values = $service->validateValues(['values' => array_replace($data['values'], $manual)], $data);
        foreach ($manual as $key => $value) $this->assertSame($value, $values[$key]);
    }

    public function test_farje_rejects_37_quotas_on_both_endpoints(): void
    {
        $sale = $this->sale('20610686665');
        for ($i = 1; $i <= 37; $i++) $this->quota($sale, $i);
        $this->authorizeUser();
        foreach (['getJson' => 'contract-data', 'postJson' => 'contract-generate'] as $method => $endpoint) {
            $this->$method('/admin/sales/'.$sale->id.'/'.$endpoint)->assertUnprocessable()
                ->assertJsonPath('errors.contract.0', 'La plantilla de FARJE admite hasta 36 cuotas.');
        }
    }

    public function test_farje_generates_36_quotas_valid_docx_with_media_without_database_writes(): void
    {
        Schema::table('payment_schedules', function (Blueprint $table) {
            $table->unsignedInteger('installment_number')->nullable()->change();
        });
        $sale = $this->sale('20610686665');
        $sale->lot->project->company->update(['business_name' => 'FARJE INGENIEROS E.I.R.L.']);
        for ($i = 36; $i >= 1; $i--) $this->quota($sale, $i, $i === 36 ? '8123.45' : '712.34');
        $this->quota($sale, 0, '100.00', 'inicial');
        $data = $this->data($sale);
        $this->assertSame(36, $data['schedule_count']);
        $this->assertSame('8123.45', $data['values']['schedule_36_installment_amount']);
        $tables = ['customers', 'sales', 'projects', 'lots', 'companies', 'payment_schedules', 'sale_lots'];
        $before = [];
        foreach ($tables as $table) $before[$table] = DB::table($table)->get()->toJson();
        $this->authorizeUser();
        $response = $this->postJson('/admin/sales/'.$sale->id.'/contract-generate', ['values' => $data['values']])->assertOk();
        $response->assertDownload('Contrato_VTA00001_01099395_FARJE.docx');
        $path = $response->baseResponse->getFile()->getPathname();
        $this->temporaryFiles[] = $path;
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path, ZipArchive::CHECKCONS));
        try {
            $this->assertNotFalse($zip->locateName('[Content_Types].xml'));
            $images = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if (str_starts_with($entry, 'word/media/')) {
                    $images[] = $entry;
                    $this->assertNotEmpty($zip->getFromName($entry));
                }
                if (str_ends_with($entry, '.xml') || str_ends_with($entry, '.rels')) {
                    $part = new \DOMDocument;
                    $this->assertTrue($part->loadXML($zip->getFromName($entry)), $entry);
                    $this->assertStringNotContainsString('${', $part->textContent, $entry);
                }
            }
            sort($images);
            $this->assertSame([
                'word/media/image1.png', 'word/media/image2.png', 'word/media/image3.png', 'word/media/image4.png',
                'word/media/image5.png', 'word/media/image6.jpg', 'word/media/image7.png', 'word/media/image8.png',
            ], $images);
            $doc = new \DOMDocument;
            $this->assertTrue($doc->loadXML($zip->getFromName('word/document.xml')));
            foreach (['FARJE INGENIEROS E.I.R.L.', '20610686665', '8,123.45', 'enterada', 'obligada'] as $text) {
                $this->assertStringContainsString($text, $doc->textContent);
            }
        } finally {
            $zip->close();
        }
        foreach ($tables as $table) $this->assertSame($before[$table], DB::table($table)->get()->toJson(), $table);
    }

    public function test_lots_from_a_different_company_are_rejected(): void
    {
        $sale = $this->sale();
        $other = $this->sale('20999999999');
        $sale->saleLots()->create(['lot_id' => $sale->lot_id, 'is_primary' => true]);
        $sale->saleLots()->create(['lot_id' => $other->lot_id, 'is_primary' => false]);
        $this->authorizeUser();
        foreach (['getJson' => 'contract-data', 'postJson' => 'contract-generate'] as $method => $endpoint) {
            $this->$method('/admin/sales/'.$sale->id.'/'.$endpoint)->assertUnprocessable()
                ->assertJsonPath('errors.contract.0', 'Los lotes pertenecen a empresas diferentes. Revise la venta antes de generar el contrato.');
        }
    }

    public function test_multiple_sale_uses_sale_lots_and_primary_flag(): void
    {
        $sale = $this->sale();
        $second = $sale->lot->replicate();
        $second->number = '09';
        $second->code = 'B09';
        $second->area = '140.00';
        $second->save();
        $sale->saleLots()->create(['lot_id' => $sale->lot_id, 'is_primary' => false]);
        $sale->saleLots()->create(['lot_id' => $second->id, 'is_primary' => true]);
        $data = $this->data($sale);
        $this->assertSame(2, $data['lots_count']);
        $this->assertSame('B09', $data['values']['lot_code']);
        $this->assertStringContainsString('Lote 09 - 140.00 m²', $data['values']['lots_summary']);
        $this->assertStringContainsString('Lote 08 - 178.79 m²', $data['values']['lots_summary']);
    }

    public function test_real_schedule_order_excludes_initial_and_clears_unused_slots(): void
    {
        $sale = $this->sale();
        $this->quota($sale, 2, '502.25');
        $this->quota($sale, 0, '1113.28', 'inicial');
        $this->quota($sale, 1, '701.99');
        $data = $this->data($sale);
        $this->assertSame(2, $data['schedule_count']);
        $this->assertSame('701.99', $data['values']['schedule_01_installment_amount']);
        $this->assertSame('502.25', $data['values']['schedule_02_installment_amount']);
        $this->assertSame('2026-09-05', $data['values']['schedule_01_due_date']);
        $this->assertSame('', $data['values']['schedule_03_installment_amount']);
        $this->assertSame('', $data['values']['bill_36_number']);
        $this->assertSame('01', $data['values']['bill_01_number']);
    }

    public function test_more_than_36_quotas_is_rejected_on_both_endpoints(): void
    {
        $sale = $this->sale();
        for ($i = 1; $i <= 37; $i++) $this->quota($sale, $i);
        $this->authorizeUser();
        foreach (['getJson' => 'contract-data', 'postJson' => 'contract-generate'] as $method => $endpoint) {
            $this->$method('/admin/sales/'.$sale->id.'/'.$endpoint)->assertUnprocessable()->assertJsonPath('errors.contract.0', 'Esta plantilla de Grupo Krea admite hasta 36 cuotas. Revise el contrato o la plantilla.');
        }
    }

    public function test_post_returns_valid_docx_with_edited_values_and_original_images_without_writes(): void
    {
        $sale = $this->sale();
        $this->quota($sale, 1);
        $data = $this->data($sale);
        $tables = ['customers', 'sales', 'projects', 'lots', 'companies', 'payment_schedules', 'sale_lots'];
        $before = [];
        foreach ($tables as $table) $before[$table] = DB::table($table)->get()->toJson();
        $values = array_replace($data['values'], ['full_name' => 'Luis & María <Prueba>', 'gender' => 'masculino', 'address' => 'Dirección editada', 'lot_price' => '21000.01', 'sale_date' => '2026-12-25', 'schedule_01_installment_amount' => '345.67']);
        $this->authorizeUser();
        $response = $this->postJson('/admin/sales/'.$sale->id.'/contract-generate', ['values' => $values])->assertOk();
        $response->assertDownload('Contrato_VTA00001_01099395_Grupo_Krea.docx');
        $path = $response->baseResponse->getFile()->getPathname();
        $this->temporaryFiles[] = $path;
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path, ZipArchive::CHECKCONS));
        $this->assertNotFalse($zip->locateName('[Content_Types].xml'));
        $this->assertNotFalse($zip->locateName('word/document.xml'));
        $generatedImages = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (str_starts_with($entry, 'word/media/')) $generatedImages[] = $entry;
            if (str_ends_with($entry, '.xml') || str_ends_with($entry, '.rels')) {
                $part = new \DOMDocument;
                $this->assertTrue($part->loadXML($zip->getFromName($entry)), $entry);
                $this->assertStringNotContainsString('${', $part->textContent, $entry);
                if (str_ends_with($entry, '.rels')) {
                    foreach ($part->getElementsByTagName('Relationship') as $relationship) {
                        if ($relationship->getAttribute('TargetMode') === 'External') continue;
                        $target = $relationship->getAttribute('Target');
                        $base = $entry === '_rels/.rels' ? '' : dirname(dirname($entry)).'/';
                        $segments = [];
                        foreach (explode('/', str_starts_with($target, '/') ? substr($target, 1) : $base.$target) as $segment) {
                            if ($segment === '..') array_pop($segments);
                            elseif ($segment !== '' && $segment !== '.') $segments[] = $segment;
                        }
                        $this->assertNotFalse($zip->locateName(implode('/', $segments)), $entry.' -> '.$target);
                    }
                }
            }
        }
        $this->assertCount(8, $generatedImages);
        $xml = $zip->getFromName('word/document.xml');
        $dom = new \DOMDocument;
        $this->assertTrue($dom->loadXML($xml));
        $this->assertStringNotContainsString('${', $xml);
        $this->assertStringContainsString('Luis &amp; María &lt;Prueba&gt;', $xml);
        $this->assertStringContainsString('El Comprador', $xml);
        $this->assertStringContainsString('Dirección editada', $xml);
        $this->assertStringContainsString('21,000.01', $xml);
        $this->assertStringContainsString('345.67', $xml);
        $this->assertStringContainsString('diciembre', $xml);
        $original = new ZipArchive;
        $original->open(storage_path('app/private/contracts/templates/grupo-krea/contrato_compra_venta_lote_grupo_krea.docx'));
        $images = 0;
        for ($i = 0; $i < $original->numFiles; $i++) {
            $name = $original->getNameIndex($i);
            if (str_ends_with($name, '.rels')) {
                $this->assertSame($original->getFromName($name), $zip->getFromName($name), $name);
            }
            if (str_starts_with($name, 'word/media/')) {
                $images++;
                $this->assertSame(hash('sha256', $original->getFromName($name)), hash('sha256', $zip->getFromName($name)));
            }
        }
        $this->assertSame(8, $images);
        $original->close();
        $zip->close();
        foreach ($tables as $table) $this->assertSame($before[$table], DB::table($table)->get()->toJson(), $table);
        ob_start();
        $response->baseResponse->sendContent();
        ob_end_clean();
        $this->assertFileDoesNotExist($path);
    }

    public function test_csrf_is_required_outside_the_test_bypass(): void
    {
        $sale = $this->sale();
        $this->authorizeUser();
        // Laravel normally skips CSRF in tests; explicitly exercise the real middleware.
        $this->app->instance('env', 'local');
        $this->postJson('/admin/sales/'.$sale->id.'/contract-generate', ['values' => []])->assertStatus(419);
    }

    public function test_new_template_variables_are_exposed_as_manual_fields_and_replaced(): void
    {
        $sale = $this->sale();
        $path = storage_path('app/private/contracts/tmp/test_manual_'.bin2hex(random_bytes(6)).'.docx');
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0700, true);
        copy(storage_path(config('contracts.templates.grupo_krea.path')), $path);
        $this->temporaryFiles[] = $path;
        $zip = new ZipArchive;
        $zip->open($path);
        $xml = str_replace('${matrix_property_name}', '${extra_manual_field}', $zip->getFromName('word/document.xml'));
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();
        config(['contracts.templates.grupo_krea.path' => 'app/private/contracts/tmp/'.basename($path)]);
        $data = $this->data($sale);
        $this->assertSame('', $data['values']['extra_manual_field']);
        $this->assertNull($data['fields']['extra_manual_field']['source']);
        $values = array_replace($data['values'], ['extra_manual_field' => 'Predio manual de prueba']);
        $file = app(SaleContractService::class)->generate($sale, ['values' => $values]);
        $this->temporaryFiles[] = $file['path'];
        $zip->open($file['path']);
        $this->assertStringContainsString('Predio manual de prueba', $zip->getFromName('word/document.xml'));
        $this->assertStringNotContainsString('${', $zip->getFromName('word/document.xml'));
        $zip->close();
    }

    public function test_editing_principal_lot_updates_summary_and_never_changes_the_route_sale(): void
    {
        $sale = $this->sale();
        $data = $this->data($sale);
        $input = array_replace($data['values'], ['sale_id' => '999999', 'lot_number' => '99']);
        $values = app(SaleContractService::class)->validateValues(['values' => $input], $data);
        $this->assertSame('99', $values['lot_01_lot_number']);
        $this->assertStringContainsString('Lote 99', $values['lots_summary']);
        $file = app(SaleContractService::class)->generate($sale, ['values' => $input]);
        $this->temporaryFiles[] = $file['path'];
        $this->assertSame('08', $sale->lot->fresh()->number);
    }

    public function test_manual_treatment_is_preserved_for_unspecified_gender(): void
    {
        $data = $this->data($this->sale());
        $input = array_replace($data['values'], ['gender' => 'no_especificado', 'buyer_role' => 'La parte compradora', 'buyer_nationality' => 'nacionalidad indicada', 'buyer_marital_status' => 'estado indicado']);
        $values = app(SaleContractService::class)->validateValues(['values' => $input], $data);
        $this->assertSame('La parte compradora', $values['buyer_role']);
        $this->assertSame('nacionalidad indicada', $values['buyer_nationality']);
        $this->assertSame('estado indicado', $values['buyer_marital_status']);
    }

    public function test_invalid_amount_date_gender_and_macros_are_rejected(): void
    {
        $sale = $this->sale();
        $data = $this->data($sale);
        $this->authorizeUser();
        $values = array_replace($data['values'], ['lot_price' => '1,000.00', 'sale_date' => '2026-02-31', 'gender' => 'invalido', 'full_name' => '${unresolved}']);
        $this->postJson('/admin/sales/'.$sale->id.'/contract-generate', ['values' => $values])->assertUnprocessable()
            ->assertJsonValidationErrors(['values.lot_price', 'values.sale_date', 'values.gender', 'values.full_name']);
    }

    public function test_gender_migration_preserves_existing_customer(): void
    {
        $migration = require database_path('migrations/2026_09_22_000001_add_gender_to_customers_table.php');
        $migration->down();
        $id = DB::table('customers')->insertGetId(['full_name' => 'Cliente anterior']);
        $migration->up();
        $this->assertSame('Cliente anterior', DB::table('customers')->find($id)->full_name);
        $this->assertNull(DB::table('customers')->find($id)->gender);
    }

    public function test_customer_can_save_gender_without_requiring_it(): void
    {
        $this->authorizeUser();
        $payload = ['person_type' => 'natural', 'first_name' => 'Prueba', 'last_name' => 'Cliente', 'document_type' => 'DNI', 'document_number' => '01099395', 'status' => '1'];
        $response = $this->postJson('/admin/customers', $payload)->assertCreated();
        $id = $response->json('data.id');
        $this->assertNull(Customer::find($id)->gender);
        $this->putJson('/admin/customers/'.$id, $payload + ['gender' => 'masculino'])->assertOk();
        $this->assertSame('masculino', Customer::find($id)->gender);
        $this->putJson('/admin/customers/'.$id, $payload + ['gender' => 'otro'])->assertUnprocessable()->assertJsonValidationErrors('gender');
    }

    public function test_amount_words_boundaries_accents_and_cents(): void
    {
        $service = app(AmountToWordsService::class);
        foreach ([
            '0' => 'Cero con 00/100 Soles', '21.01' => 'Veintiún con 01/100 Soles',
            '100' => 'Cien con 00/100 Soles', '51113.28' => 'Cincuenta Y Un Mil Ciento Trece con 28/100 Soles',
            '1000000' => 'Un Millón con 00/100 Soles', '21000000' => 'Veintiún Millones con 00/100 Soles',
            '999999999.99' => 'Novecientos Noventa Y Nueve Millones Novecientos Noventa Y Nueve Mil Novecientos Noventa Y Nueve con 99/100 Soles',
        ] as $amount => $expected) {
            $this->assertSame($expected, $service->convert($amount));
        }
    }
}
