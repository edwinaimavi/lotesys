<?php

use App\Models\Project;
use App\Models\ProjectWebProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function projectInventoryFixture(string $name = 'Bella Terra Inventario', bool $published = true): Project
{
    $companyId = DB::table('companies')->insertGetId([
        'business_name' => 'Empresa Inventario SAC',
        'trade_name' => 'Inventario',
        'ruc' => (string) random_int(10000000000, 99999999999),
        'status' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $project = Project::query()->create([
        'company_id' => $companyId,
        'name' => $name,
        'code' => 'INV-'.uniqid(),
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
        'short_description' => 'Proyecto público para inventario.',
        'sort_order' => 1,
    ]);

    return $project;
}

function projectInventoryBlock(Project $project, string $name = 'MZ A', int $status = 1): int
{
    return DB::table('blocks')->insertGetId([
        'project_id' => $project->id,
        'name' => $name,
        'status' => $status,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function projectInventoryLot(
    Project $project,
    int $blockId,
    string $number,
    string $status = 'disponible',
    float $price = 3200,
    float $area = 120
): void {
    DB::table('lots')->insert([
        'project_id' => $project->id,
        'block_id' => $blockId,
        'code' => 'INVLOT-'.uniqid(),
        'number' => $number,
        'area' => $area,
        'unit_measure' => 'm2',
        'cash_price' => $price,
        'financed_price' => $price,
        'status' => $status,
        'observation' => 'OBSERVACIÓN PRIVADA DEL INVENTARIO',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('la página de inventario es pública para proyectos publicados', function () {
    $project = projectInventoryFixture();
    $block = projectInventoryBlock($project);
    projectInventoryLot($project, $block, '01');

    $this->get(route('public.projects.lots', $project))
        ->assertOk()
        ->assertSee('Bella Terra Inventario')
        ->assertSee('Lote 01');
});

test('un proyecto oculto no expone su página de inventario', function () {
    $project = projectInventoryFixture('Proyecto privado', false);

    $this->get(route('public.projects.lots', $project))->assertNotFound();
});

test('el inventario muestra solamente disponibles en manzanas activas', function () {
    $project = projectInventoryFixture();
    $active = projectInventoryBlock($project, 'MZ A');
    $inactive = projectInventoryBlock($project, 'MZ X', 0);
    projectInventoryLot($project, $active, '01', 'disponible');
    projectInventoryLot($project, $active, '02', 'separado');
    projectInventoryLot($project, $active, '03', 'vendido');
    projectInventoryLot($project, $inactive, '99', 'disponible');

    $this->get(route('public.projects.lots', $project))
        ->assertOk()
        ->assertSee('Lote 01')
        ->assertDontSee('Lote 02')
        ->assertDontSee('Lote 03')
        ->assertDontSee('Lote 99');
});

test('el inventario pagina doce lotes por página', function () {
    $project = projectInventoryFixture();
    $block = projectInventoryBlock($project);

    foreach (range(1, 13) as $number) {
        projectInventoryLot($project, $block, str_pad((string) $number, 2, '0', STR_PAD_LEFT));
    }

    $this->get(route('public.projects.lots', $project))
        ->assertOk()
        ->assertSee('Lote 12')
        ->assertDontSee('Lote 13');

    $this->get(route('public.projects.lots', ['project' => $project, 'page' => 2]))
        ->assertOk()
        ->assertSee('Lote 13');
});

test('filtra el inventario por manzana del mismo proyecto', function () {
    $project = projectInventoryFixture();
    $blockA = projectInventoryBlock($project, 'MZ A');
    $blockB = projectInventoryBlock($project, 'MZ B');
    projectInventoryLot($project, $blockA, '01');
    projectInventoryLot($project, $blockB, '02');

    $this->get(route('public.projects.lots', ['project' => $project, 'block_id' => $blockB]))
        ->assertOk()
        ->assertDontSee('Lote 01')
        ->assertSee('Lote 02');
});

test('filtra el inventario por precio contado', function () {
    $project = projectInventoryFixture();
    $block = projectInventoryBlock($project);
    projectInventoryLot($project, $block, '01', 'disponible', 3000);
    projectInventoryLot($project, $block, '02', 'disponible', 5000);
    projectInventoryLot($project, $block, '03', 'disponible', 8000);

    $this->get(route('public.projects.lots', [
        'project' => $project,
        'min_price' => 4000,
        'max_price' => 6000,
    ]))->assertOk()
        ->assertDontSee('Lote 01')
        ->assertSee('Lote 02')
        ->assertDontSee('Lote 03');
});

test('filtra el inventario por área', function () {
    $project = projectInventoryFixture();
    $block = projectInventoryBlock($project);
    projectInventoryLot($project, $block, '01', 'disponible', 3000, 100);
    projectInventoryLot($project, $block, '02', 'disponible', 4000, 150);
    projectInventoryLot($project, $block, '03', 'disponible', 5000, 220);

    $this->get(route('public.projects.lots', [
        'project' => $project,
        'min_area' => 120,
        'max_area' => 180,
    ]))->assertOk()
        ->assertDontSee('Lote 01')
        ->assertSee('Lote 02')
        ->assertDontSee('Lote 03');
});

test('la página no expone observaciones privadas de los lotes', function () {
    $project = projectInventoryFixture();
    $block = projectInventoryBlock($project);
    projectInventoryLot($project, $block, '01');

    $this->get(route('public.projects.lots', $project))
        ->assertOk()
        ->assertDontSee('OBSERVACIÓN PRIVADA DEL INVENTARIO');
});

test('cada lote conserva el WhatsApp comercial específico', function () {
    $project = projectInventoryFixture('Bella Terra WhatsApp Página');
    $block = projectInventoryBlock($project, 'MZ C');
    projectInventoryLot($project, $block, '04', 'disponible', 3500, 125);

    $response = $this->get(route('public.projects.lots', $project))->assertOk();
    $response->assertSee('Me interesa este lote')
        ->assertSee('https://wa.me/'.preg_replace('/\D/', '', config('landing.whatsapp')), false);
});

test('la vista previa del home puede solicitar solamente tres lotes', function () {
    $project = projectInventoryFixture();
    $block = projectInventoryBlock($project);

    foreach (range(1, 8) as $number) {
        projectInventoryLot($project, $block, str_pad((string) $number, 2, '0', STR_PAD_LEFT));
    }

    $this->getJson(route('public.projects.available-lots', [
        'project' => $project,
        'per_page' => 3,
    ]))->assertOk()
        ->assertJsonCount(3, 'lots')
        ->assertJsonPath('pagination.total', 8)
        ->assertJsonPath('pagination.has_more', true);
});

test('la landing enlaza la vista previa con el inventario completo del proyecto', function () {
    $project = projectInventoryFixture('Proyecto con catálogo completo');
    $block = projectInventoryBlock($project);
    projectInventoryLot($project, $block, '01');

    $this->get('/')
        ->assertOk()
        ->assertSee('data-all-lots-url="'.route('public.projects.lots', $project).'"', false)
        ->assertSee('id="project-lot-explorer-all"', false);
});
