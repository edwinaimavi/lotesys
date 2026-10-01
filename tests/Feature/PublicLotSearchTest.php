<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function createPublicSearchProject(int $id, string $name, ?string $district, string $province = 'SAN MARTIN', string $department = 'SAN MARTIN'): void
{
    DB::table('projects')->insert([
        'id' => $id,
        'company_id' => 999999,
        'name' => $name,
        'code' => 'PUBLIC-'.$id,
        'district' => $district,
        'province' => $province,
        'department' => $department,
        'status' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('blocks')->insert([
        'id' => $id + 100,
        'project_id' => $id,
        'name' => 'MZ A',
        'status' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function createPublicSearchLot(int $id, int $projectId, string $number, float $price, string $status = 'disponible'): void
{
    DB::table('lots')->insert([
        'id' => $id,
        'project_id' => $projectId,
        'block_id' => $projectId + 100,
        'code' => 'LOT-'.$id,
        'number' => $number,
        'area' => 120.50,
        'unit_measure' => 'm2',
        'cash_price' => $price,
        'financed_price' => $price,
        'status' => $status,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

beforeEach(function () {
    DB::table('companies')->insert([
        'id' => 999999,
        'business_name' => 'Empresa de prueba pública',
        'trade_name' => 'Prueba pública',
        'ruc' => '20999999999',
        'status' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    createPublicSearchProject(8100, 'Bella Terra Pública', 'TARAPOTO');
    createPublicSearchLot(8300, 8100, '08', 25000);
    createPublicSearchLot(8301, 8100, '09', 28000, 'vendido');
});

test('el endpoint de búsqueda de lotes es público', function () {
    $this->getJson(route('public.lots.search'))
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('devuelve exclusivamente lotes disponibles', function () {
    $response = $this->getJson(route('public.lots.search'))->assertOk();

    $response->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'LOT-8300')
        ->assertJsonPath('data.0.status', 'disponible')
        ->assertJsonMissing(['code' => 'LOT-8301']);
});

test('filtra por proyecto', function () {
    createPublicSearchProject(8400, 'Palmeras Públicas', 'MORALES');
    createPublicSearchLot(8600, 8400, '01', 45000);

    $this->getJson(route('public.lots.search', ['project_id' => 8400]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.project', 'Palmeras Públicas');
});

test('filtra por ubicación real del proyecto', function () {
    createPublicSearchProject(8400, 'Palmeras Públicas', 'MORALES');
    createPublicSearchLot(8600, 8400, '01', 45000);

    $this->getJson(route('public.lots.search', ['location' => 'MORALES']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.location', 'MORALES');
});

test('la landing lista proyectos activos aunque todavía no tengan lotes y usa provincia como ubicación alternativa', function () {
    createPublicSearchProject(8400, 'Madrid Público', null, 'LAMAS');

    $this->get('/')
        ->assertOk()
        ->assertSee('<option value="LAMAS">LAMAS</option>', false)
        ->assertSee('value="8400" data-location="LAMAS"', false)
        ->assertSee('Madrid Público');
});

test('filtra por provincia cuando el distrito del proyecto está vacío', function () {
    createPublicSearchProject(8400, 'Palmeras Públicas', null, 'LAMAS');
    createPublicSearchLot(8600, 8400, '01', 45000);

    $this->getJson(route('public.lots.search', ['location' => 'LAMAS']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.project', 'Palmeras Públicas')
        ->assertJsonPath('data.0.location', 'LAMAS');
});

test('filtra presupuesto mediante límites numéricos y conserva decimales', function () {
    createPublicSearchLot(8302, 8100, '10', 30000.50);
    createPublicSearchLot(8303, 8100, '11', 50000.50);
    createPublicSearchLot(8304, 8100, '12', 80000.50);

    $this->getJson(route('public.lots.search', ['min_price' => 30000.01, 'max_price' => 50000]))
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'LOT-8302');
    $this->getJson(route('public.lots.search', ['min_price' => 50000.01, 'max_price' => 80000]))
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'LOT-8303');
    $this->getJson(route('public.lots.search', ['min_price' => 80000.01]))
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'LOT-8304');
});

test('hasta 30000 incluye 25000 y 30000 sin incluir 30001 ni lotes no disponibles', function () {
    createPublicSearchLot(8302, 8100, '10', 30000);
    createPublicSearchLot(8303, 8100, '11', 30001);
    createPublicSearchLot(8304, 8100, '12', 26000, 'separado');

    $this->getJson(route('public.lots.price-ranges', [
        'location' => 'TARAPOTO',
        'project_id' => 8100,
    ]))->assertOk()
        ->assertJsonPath('data.0.label', 'Hasta S/ 30,000')
        ->assertJsonPath('data.0.min_price', null)
        ->assertJsonPath('data.0.max_price', 30000);

    $this->getJson(route('public.lots.search', [
        'location' => 'TARAPOTO',
        'project_id' => 8100,
        'max_price' => 30000,
    ]))->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.code', 'LOT-8300')
        ->assertJsonPath('data.1.code', 'LOT-8302')
        ->assertJsonMissing(['code' => 'LOT-8303'])
        ->assertJsonMissing(['code' => 'LOT-8304']);
});

test('cero coincidencias devuelve 200 con resultados vacíos', function () {
    $this->getJson(route('public.lots.search', [
        'location' => 'TARAPOTO',
        'project_id' => 8100,
        'max_price' => 1000,
    ]))->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0);
});

test('combina ubicación proyecto y presupuesto', function () {
    createPublicSearchProject(8400, 'Palmeras Públicas', 'MORALES');
    createPublicSearchLot(8600, 8400, '01', 45000);
    createPublicSearchLot(8601, 8400, '02', 90000);

    $this->getJson(route('public.lots.search', [
        'location' => 'MORALES',
        'project_id' => 8400,
        'min_price' => 30000.01,
        'max_price' => 50000,
    ]))->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'LOT-8600');
});

test('rechaza project_id inexistente', function () {
    $this->getJson(route('public.lots.search', ['project_id' => 99999999]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('project_id');
});

test('rechaza límites de presupuesto inválidos', function () {
    $this->getJson(route('public.lots.search', ['min_price' => 'cualquier_valor']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('min_price');

    $this->getJson(route('public.lots.search', ['min_price' => 50000, 'max_price' => 30000]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('max_price');
});

test('genera rangos comerciales desde los precios disponibles', function () {
    createPublicSearchLot(8302, 8100, '10', 45000);
    createPublicSearchLot(8303, 8100, '11', 65000);
    createPublicSearchLot(8304, 8100, '12', 90000);

    $this->getJson(route('public.lots.price-ranges'))
        ->assertOk()
        ->assertJsonPath('data.0.label', 'Hasta S/ 40,000')
        ->assertJsonPath('data.1.label', 'S/ 40,001 - S/ 60,000')
        ->assertJsonPath('data.2.label', 'S/ 60,001 - S/ 80,000')
        ->assertJsonPath('data.3.label', 'Más de S/ 80,000')
        ->assertJsonCount(4, 'data');
});

test('recalcula rangos por ubicación y proyecto sin incluir no disponibles', function () {
    createPublicSearchProject(8400, 'Palmeras Públicas', 'MORALES');
    createPublicSearchLot(8600, 8400, '01', 45000);
    createPublicSearchLot(8601, 8400, '02', 55000);
    createPublicSearchLot(8602, 8400, '03', 150000, 'bloqueado');

    $response = $this->getJson(route('public.lots.price-ranges', [
        'location' => 'MORALES',
        'project_id' => 8400,
    ]))->assertOk();

    expect($response->json('data'))->toBe([
        ['label' => 'Hasta S/ 50,000', 'min_price' => null, 'max_price' => 50000],
        ['label' => 'Más de S/ 50,000', 'min_price' => 50000.01, 'max_price' => null],
    ]);
});

test('la respuesta contiene solo información comercial', function () {
    $lot = $this->getJson(route('public.lots.search'))->assertOk()->json('data.0');

    expect(array_keys($lot))->toBe([
        'project',
        'location',
        'block',
        'lot_number',
        'code',
        'area',
        'unit_measure',
        'cash_price',
        'status',
    ]);
});

test('limita a 3 resultados y devuelve el total real', function () {
    foreach (range(1, 30) as $index) {
        createPublicSearchLot(8700 + $index, 8100, str_pad((string) (20 + $index), 2, '0', STR_PAD_LEFT), 35000);
    }

    $this->getJson(route('public.lots.search'))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('meta.total', 31)
        ->assertJsonPath('meta.shown', 3)
        ->assertJsonPath('meta.limit', 3);
});

test('la página pública carga ubicaciones proyectos y WhatsApp sin datos privados', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<option value="TARAPOTO">TARAPOTO</option>', false)
        ->assertSee('value="8100" data-location="TARAPOTO"', false)
        ->assertSee('data-whatsapp="'.config('landing.whatsapp').'"', false)
        ->assertDontSee('customer_id')
        ->assertDontSee('buyer');
});

test('la página general conserva filtros y pagina doce resultados comerciales', function () {
    foreach (range(1, 25) as $index) {
        createPublicSearchLot(8700 + $index, 8100, str_pad((string) (20 + $index), 2, '0', STR_PAD_LEFT), 30000);
    }
    $filters = ['location' => 'TARAPOTO', 'project_id' => 8100, 'min_price' => 29000, 'max_price' => 31000];
    $response = $this->get(route('public.lots.index', $filters))->assertOk()
        ->assertSee('Lotes disponibles')->assertSee('25 oportunidades encontradas')
        ->assertSee('Ubicación: TARAPOTO')->assertSee('Proyecto: Bella Terra Pública')
        ->assertSee('Desde S/ 29,000.00')->assertSee('Hasta S/ 31,000.00')
        ->assertSee('Me interesa este lote')->assertDontSee('LOT-8300')->assertDontSee('LOT-8301');
    $lots = $response->viewData('lots');
    expect($lots->count())->toBe(12)->and($lots->total())->toBe(25)->and($lots->perPage())->toBe(12);
    parse_str(parse_url($lots->nextPageUrl(), PHP_URL_QUERY), $query);
    expect($query)->toMatchArray($filters + ['page' => '2']);
    $second = $this->get($lots->nextPageUrl())->assertOk()->viewData('lots');
    expect($second->count())->toBe(12)->and($second->currentPage())->toBe(2)
        ->and(array_intersect($lots->pluck('code')->all(), $second->pluck('code')->all()))->toBe([]);
    $last = $this->get($second->nextPageUrl())->assertOk()->viewData('lots');
    expect($last->count())->toBe(1);
});

test('la página general aplica ubicación proyecto y límites de precio independientes', function () {
    createPublicSearchProject(8400, 'Palmeras Públicas', 'MORALES');
    createPublicSearchLot(8600, 8400, '01', 45000);
    createPublicSearchLot(8601, 8400, '02', 90000);
    foreach ([['location' => 'MORALES'], ['project_id' => 8400], ['min_price' => 40000], ['max_price' => 50000, 'min_price' => 30000]] as $filters) {
        $response = $this->get(route('public.lots.index', $filters))->assertOk()->assertDontSee('LOT-8300');
        expect($response->viewData('lots')->pluck('code')->all())->toContain('LOT-8600');
    }
    $this->get(route('public.lots.index', ['location' => 'MORALES', 'project_id' => 8100]))
        ->assertOk()->assertSee('0 oportunidades encontradas');
});

test('la página general excluye estados no disponibles y proyectos o manzanas inactivos', function () {
    foreach (['vendido', 'separado', 'bloqueado'] as $index => $status) {
        createPublicSearchLot(8700 + $index, 8100, (string)$index, 25000, $status);
    }
    createPublicSearchProject(8400, 'Proyecto inactivo', 'TARAPOTO');
    createPublicSearchLot(8600, 8400, '01', 25000);
    DB::table('projects')->where('id', 8400)->update(['status' => 0]);
    createPublicSearchProject(8500, 'Manzana inactiva', 'TARAPOTO');
    createPublicSearchLot(8601, 8500, '02', 25000);
    DB::table('blocks')->where('project_id', 8500)->update(['status' => 0]);
    $response = $this->get(route('public.lots.index'))->assertOk();
    expect($response->viewData('lots')->pluck('code')->all())->toBe(['LOT-8300']);
    $this->getJson(route('public.lots.search'))->assertOk()->assertJsonPath('meta.total', 1);
});

test('la página general usa datos permitidos sin exponer campos internos', function () {
    DB::table('lots')->where('id', 8300)->update(['observation' => 'SECRETO INTERNO DEL LOTE']);
    $response = $this->get(route('public.lots.index'))->assertOk();
    foreach (['customer_id', 'created_by', 'updated_by', 'observation', 'SECRETO INTERNO DEL LOTE'] as $private) {
        $response->assertDontSee($private);
    }
    expect(array_keys($response->viewData('lots')->first()))->toBe([
        'id', 'code', 'block', 'lot_number', 'area', 'unit_measure', 'cash_price', 'status', 'whatsapp_url', 'project', 'location',
    ]);
});

test('la landing ofrece el enlace oculto al catálogo público general', function () {
    $this->get('/')->assertOk()
        ->assertSee('data-all-lots-url="'.route('public.lots.index').'"', false)
        ->assertSee('id="lot-results-all" hidden', false);
});

test('la página general valida precios y número de página', function () {
    $this->getJson(route('public.lots.index', ['min_price' => 50000, 'max_price' => 30000]))
        ->assertUnprocessable()->assertJsonValidationErrors('max_price');
    $this->getJson(route('public.lots.index', ['page' => 0]))
        ->assertUnprocessable()->assertJsonValidationErrors('page');
});
