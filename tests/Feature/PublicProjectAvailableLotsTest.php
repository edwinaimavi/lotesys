<?php

use App\Models\Project;
use App\Models\ProjectWebProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function availableLotsProjectFixture(string $name = 'Bella Terra Pública', bool $published = true): Project
{
    $companyId = DB::table('companies')->insertGetId([
        'business_name' => 'Empresa Lotes Públicos SAC',
        'trade_name' => 'Lotes Públicos',
        'ruc' => (string) random_int(10000000000, 99999999999),
        'status' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $project = Project::query()->create([
        'company_id' => $companyId,
        'name' => $name,
        'code' => 'PL-'.uniqid(),
        'district' => 'Tarapoto',
        'province' => 'San Martín',
        'department' => 'San Martín',
        'status' => 1,
    ]);

    ProjectWebProfile::query()->create([
        'project_id' => $project->id,
        'show_on_web' => $published,
        'featured_on_home' => true,
        'commercial_status' => 'available',
        'cover_image_path' => 'landing/projects/cover.jpg',
        'sort_order' => 1,
    ]);

    return $project;
}

function availableLotsBlockFixture(Project $project, string $name = 'MZ A', int $status = 1): int
{
    return DB::table('blocks')->insertGetId([
        'project_id' => $project->id,
        'name' => $name,
        'status' => $status,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function availableLotsLotFixture(
    Project $project,
    int $blockId,
    string $number,
    string $status = 'disponible',
    float $price = 3200,
    float $area = 120
): int {
    return DB::table('lots')->insertGetId([
        'project_id' => $project->id,
        'block_id' => $blockId,
        'code' => 'PUBLOT-'.uniqid(),
        'number' => $number,
        'area' => $area,
        'unit_measure' => 'm2',
        'cash_price' => $price,
        'financed_price' => $price,
        'status' => $status,
        'observation' => 'Observación interna privada',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('el endpoint de lotes disponibles es público para un proyecto publicado', function () {
    $project = availableLotsProjectFixture();
    $block = availableLotsBlockFixture($project);
    availableLotsLotFixture($project, $block, '01');

    $this->getJson(route('public.projects.available-lots', $project))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('project.name', 'Bella Terra Pública');
});

test('un proyecto oculto no expone su inventario público', function () {
    $project = availableLotsProjectFixture('Proyecto Oculto', false);

    $this->getJson(route('public.projects.available-lots', $project))
        ->assertNotFound();
});

test('devuelve solamente lotes disponibles', function () {
    $project = availableLotsProjectFixture();
    $block = availableLotsBlockFixture($project);
    availableLotsLotFixture($project, $block, '01', 'disponible');
    availableLotsLotFixture($project, $block, '02', 'separado');
    availableLotsLotFixture($project, $block, '03', 'vendido');

    $this->getJson(route('public.projects.available-lots', $project))
        ->assertOk()
        ->assertJsonCount(1, 'lots')
        ->assertJsonPath('lots.0.lot_number', '01')
        ->assertJsonPath('lots.0.status', 'disponible');
});

test('excluye lotes separados', function () {
    $project = availableLotsProjectFixture();
    $block = availableLotsBlockFixture($project);
    availableLotsLotFixture($project, $block, '01', 'separado');

    $this->getJson(route('public.projects.available-lots', $project))
        ->assertOk()
        ->assertJsonCount(0, 'lots');
});

test('excluye lotes vendidos', function () {
    $project = availableLotsProjectFixture();
    $block = availableLotsBlockFixture($project);
    availableLotsLotFixture($project, $block, '01', 'vendido');

    $this->getJson(route('public.projects.available-lots', $project))
        ->assertOk()
        ->assertJsonCount(0, 'lots');
});

test('excluye otros estados del inventario público', function () {
    $project = availableLotsProjectFixture();
    $block = availableLotsBlockFixture($project);
    availableLotsLotFixture($project, $block, '01', 'bloqueado');
    availableLotsLotFixture($project, $block, '02', 'rescindido');

    $this->getJson(route('public.projects.available-lots', $project))
        ->assertOk()
        ->assertJsonCount(0, 'lots');
});

test('excluye lotes de manzanas inactivas', function () {
    $project = availableLotsProjectFixture();
    $block = availableLotsBlockFixture($project, 'MZ X', 0);
    availableLotsLotFixture($project, $block, '01');

    $this->getJson(route('public.projects.available-lots', $project))
        ->assertOk()
        ->assertJsonCount(0, 'lots');
});

test('no devuelve lotes pertenecientes a otro proyecto', function () {
    $project = availableLotsProjectFixture('Proyecto Uno');
    $otherProject = availableLotsProjectFixture('Proyecto Dos');
    $block = availableLotsBlockFixture($project);
    $otherBlock = availableLotsBlockFixture($otherProject);
    availableLotsLotFixture($project, $block, '01');
    availableLotsLotFixture($otherProject, $otherBlock, '99');

    $this->getJson(route('public.projects.available-lots', $project))
        ->assertOk()
        ->assertJsonCount(1, 'lots')
        ->assertJsonPath('lots.0.lot_number', '01');
});

test('usa el precio contado y el área reales del lote', function () {
    $project = availableLotsProjectFixture();
    $block = availableLotsBlockFixture($project);
    availableLotsLotFixture($project, $block, '07', 'disponible', 4321.50, 143.75);

    $this->getJson(route('public.projects.available-lots', $project))
        ->assertOk()
        ->assertJsonPath('lots.0.cash_price', 4321.5)
        ->assertJsonPath('lots.0.area', 143.75);
});

test('devuelve manzana número y código reales del lote', function () {
    $project = availableLotsProjectFixture();
    $block = availableLotsBlockFixture($project, 'MZ B');
    $lotId = availableLotsLotFixture($project, $block, '12');
    $code = DB::table('lots')->where('id', $lotId)->value('code');

    $this->getJson(route('public.projects.available-lots', $project))
        ->assertOk()
        ->assertJsonPath('lots.0.block', 'MZ B')
        ->assertJsonPath('lots.0.lot_number', '12')
        ->assertJsonPath('lots.0.code', $code);
});

test('la respuesta del lote no expone campos privados', function () {
    $project = availableLotsProjectFixture();
    $block = availableLotsBlockFixture($project);
    availableLotsLotFixture($project, $block, '01');

    $lot = $this->getJson(route('public.projects.available-lots', $project))
        ->assertOk()
        ->json('lots.0');

    expect(array_keys($lot))->toBe([
        'id',
        'code',
        'block',
        'lot_number',
        'area',
        'unit_measure',
        'cash_price',
        'status',
        'whatsapp_url',
    ]);

    expect(json_encode($lot))->not->toContain('Observación interna privada');
});

test('la primera página entrega como máximo seis lotes', function () {
    $project = availableLotsProjectFixture();
    $block = availableLotsBlockFixture($project);

    foreach (range(1, 8) as $number) {
        availableLotsLotFixture($project, $block, str_pad((string) $number, 2, '0', STR_PAD_LEFT));
    }

    $this->getJson(route('public.projects.available-lots', $project))
        ->assertOk()
        ->assertJsonCount(6, 'lots')
        ->assertJsonPath('pagination.per_page', 6)
        ->assertJsonPath('pagination.total', 8);
});

test('informa has more cuando quedan lotes por mostrar', function () {
    $project = availableLotsProjectFixture();
    $block = availableLotsBlockFixture($project);

    foreach (range(1, 7) as $number) {
        availableLotsLotFixture($project, $block, (string) $number);
    }

    $this->getJson(route('public.projects.available-lots', $project))
        ->assertOk()
        ->assertJsonPath('pagination.has_more', true);
});

test('la segunda página devuelve los siguientes lotes', function () {
    $project = availableLotsProjectFixture();
    $block = availableLotsBlockFixture($project);

    foreach (range(1, 8) as $number) {
        availableLotsLotFixture($project, $block, str_pad((string) $number, 2, '0', STR_PAD_LEFT));
    }

    $this->getJson(route('public.projects.available-lots', [
        'project' => $project,
        'page' => 2,
    ]))->assertOk()
        ->assertJsonCount(2, 'lots')
        ->assertJsonPath('lots.0.lot_number', '07')
        ->assertJsonPath('lots.1.lot_number', '08')
        ->assertJsonPath('pagination.has_more', false);
});

test('el WhatsApp identifica proyecto lote área y precio', function () {
    $project = availableLotsProjectFixture('Bella Terra WhatsApp');
    $block = availableLotsBlockFixture($project, 'MZ C');
    availableLotsLotFixture($project, $block, '04', 'disponible', 3500, 125);

    $url = $this->getJson(route('public.projects.available-lots', $project))
        ->assertOk()
        ->json('lots.0.whatsapp_url');

    expect($url)->toStartWith('https://wa.me/'.preg_replace('/\D/', '', config('landing.whatsapp')));
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
    expect($query['text'] ?? '')
        ->toContain('lote 04')
        ->toContain('manzana C')
        ->toContain('Bella Terra WhatsApp')
        ->toContain('125 m²')
        ->toContain('S/ 3,500.00');
});

test('un proyecto sin disponibles devuelve colección vacía y paginación segura', function () {
    $project = availableLotsProjectFixture();

    $this->getJson(route('public.projects.available-lots', $project))
        ->assertOk()
        ->assertJsonCount(0, 'lots')
        ->assertJsonPath('pagination.total', 0)
        ->assertJsonPath('pagination.has_more', false);
});


test('la landing enlaza proyectos publicados con el explorador local de lotes', function () {
    $project = availableLotsProjectFixture('Proyecto Explorador');
    $block = availableLotsBlockFixture($project);
    availableLotsLotFixture($project, $block, '01');

    $this->get('/')
        ->assertOk()
        ->assertSee('data-lots-url="'.route('public.projects.available-lots', $project).'"', false)
        ->assertSee('id="project-lot-explorer"', false);
});

test('rechaza per page mayor a seis', function () {
    $project = availableLotsProjectFixture();

    $this->getJson(route('public.projects.available-lots', [
        'project' => $project,
        'per_page' => 7,
    ]))->assertUnprocessable()
        ->assertJsonValidationErrors('per_page');
});
