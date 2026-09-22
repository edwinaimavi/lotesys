<?php

use App\Models\Project;
use App\Models\ProjectWebProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function availabilityProject(string $status = 'available', bool $published = true): Project
{
    $companyId = DB::table('companies')->insertGetId([
        'business_name' => 'Empresa Pública SAC', 'trade_name' => 'Empresa Pública',
        'ruc' => (string) random_int(10000000000, 99999999999), 'status' => 1,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $project = Project::query()->create([
        'company_id' => $companyId, 'name' => 'Proyecto Disponibilidad', 'code' => 'AV-'.uniqid(),
        'district' => 'Tarapoto', 'province' => 'San Martín', 'department' => 'San Martín', 'status' => 1,
    ]);
    ProjectWebProfile::query()->create([
        'project_id' => $project->id, 'show_on_web' => $published, 'featured_on_home' => true,
        'commercial_status' => $status, 'cover_image_path' => 'landing/projects/cover.jpg', 'sort_order' => 1,
    ]);

    return $project;
}

function availabilityLot(Project $project, string $status, float $price = 25000, float $area = 120, int $blockStatus = 1): void
{
    $blockId = DB::table('blocks')->insertGetId([
        'project_id' => $project->id, 'name' => 'MZ '.uniqid(), 'status' => $blockStatus,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('lots')->insert([
        'project_id' => $project->id, 'block_id' => $blockId, 'code' => 'AVLOT-'.uniqid(),
        'number' => (string) random_int(1, 999), 'area' => $area, 'unit_measure' => 'm2',
        'cash_price' => $price, 'financed_price' => $price, 'status' => $status,
        'observation' => 'Dato privado que no debe exponerse', 'created_at' => now(), 'updated_at' => now(),
    ]);
}

function availabilityAdmin(): User
{
    $user = User::factory()->create();
    $permission = Permission::firstOrCreate(['name' => 'admin.project-web-profiles.update', 'guard_name' => 'web']);
    $user->givePermissionTo($permission);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

function availabilityUpdatePayload(array $overrides = []): array
{
    return array_merge([
        'show_on_web' => true, 'featured_on_home' => true,
        'commercial_status' => 'available', 'sort_order' => 1,
    ], $overrides);
}

test('el endpoint de disponibilidad por proyecto es público', function () {
    $project = availabilityProject();
    $this->getJson(route('public.projects.availability', $project))->assertOk()->assertJsonPath('success', true);
});

test('un proyecto oculto no puede consultarse públicamente', function () {
    $project = availabilityProject('available', false);
    $this->getJson(route('public.projects.availability', $project))->assertNotFound();
});

test('un proyecto publicado devuelve solamente su información comercial', function () {
    $project = availabilityProject();
    $this->getJson(route('public.projects.availability', $project))->assertOk()
        ->assertJsonPath('project.name', 'Proyecto Disponibilidad')
        ->assertJsonPath('project.company', 'Empresa Pública SAC')
        ->assertJsonPath('project.location', 'Tarapoto · San Martín');
});

test('cuenta lotes disponibles correctamente', function () {
    $project = availabilityProject();
    availabilityLot($project, 'disponible'); availabilityLot($project, 'disponible');
    $this->getJson(route('public.projects.availability', $project))->assertJsonPath('availability.available', 2);
});

test('la disponibilidad pública no expone separados ni vendidos', function () {
    $project = availabilityProject();
    availabilityLot($project, 'separado');
    availabilityLot($project, 'vendido');

    $response = $this->getJson(route('public.projects.availability', $project))->assertOk()
        ->assertJsonPath('availability.available', 0)
        ->assertJsonPath('availability.has_lots', false);

    expect($response->json('availability'))->not->toHaveKeys(['separated', 'sold']);
});

test('otros estados y manzanas inactivas no contaminan la disponibilidad pública', function () {
    $project = availabilityProject();
    availabilityLot($project, 'bloqueado'); availabilityLot($project, 'rescindido');
    availabilityLot($project, 'disponible', 1000, 40, 0);
    $this->getJson(route('public.projects.availability', $project))->assertJsonPath('availability.available', 0)
        ->assertJsonPath('availability.has_lots', false);
});

test('el precio mínimo usa exclusivamente lotes disponibles', function () {
    $project = availabilityProject();
    availabilityLot($project, 'disponible', 25000); availabilityLot($project, 'disponible', 32000); availabilityLot($project, 'vendido', 1000);
    $this->getJson(route('public.projects.availability', $project))->assertJsonPath('availability.minimum_price', 25000);
});

test('el área mínima usa exclusivamente lotes disponibles', function () {
    $project = availabilityProject();
    availabilityLot($project, 'disponible', 25000, 120); availabilityLot($project, 'disponible', 32000, 90); availabilityLot($project, 'separado', 1000, 30);
    $this->getJson(route('public.projects.availability', $project))->assertJsonPath('availability.minimum_area', 90);
});

test('un proyecto sin lotes devuelve métricas nulas y conteos seguros', function () {
    $project = availabilityProject();
    $this->getJson(route('public.projects.availability', $project))->assertJsonPath('availability.has_lots', false)
        ->assertJsonPath('availability.available', 0)->assertJsonPath('availability.minimum_price', null)->assertJsonPath('availability.minimum_area', null);
});

test('un proyecto en preventa sin lotes puede consultarse', function () {
    $project = availabilityProject('presale');
    $this->getJson(route('public.projects.availability', $project))->assertOk()
        ->assertJsonPath('project.commercial_status', 'presale')->assertJsonPath('availability.has_lots', false);
});

test('un proyecto próximo sin lotes puede consultarse', function () {
    $project = availabilityProject('coming_soon');
    $this->getJson(route('public.projects.availability', $project))->assertOk()
        ->assertJsonPath('project.commercial_status', 'coming_soon')->assertJsonPath('availability.has_lots', false);
});

test('la respuesta pública no expone datos personales ni comerciales internos', function () {
    $project = availabilityProject(); availabilityLot($project, 'vendido');
    $response = $this->getJson(route('public.projects.availability', $project))->assertOk();
    foreach (['buyer', 'customer', 'customer_id', 'sale', 'sale_id', 'payment', 'contract', 'dni', 'phone', 'observation'] as $key) {
        $response->assertJsonMissing([$key => '*']);
    }
    expect($response->json('availability'))->not->toHaveKeys(['separated', 'sold']);
    expect($response->getContent())->not->toContain('Dato privado que no debe exponerse');
});

test('las rutas del plano provienen exclusivamente del perfil web', function () {
    $project = availabilityProject();
    $project->webProfile()->update([
        'plan_image_path' => 'landing/projects/plans/general.webp',
        'plan_mobile_image_path' => 'landing/projects/plans/mobile.webp',
        'plan_image_alt' => 'Plano oficial', 'plan_caption' => 'Plano general del proyecto',
    ]);
    $this->getJson(route('public.projects.availability', $project))->assertOk()
        ->assertJsonPath('plan.url', 'http://lotesys.test/storage/landing/projects/plans/general.webp')
        ->assertJsonPath('plan.mobile_url', 'http://lotesys.test/storage/landing/projects/plans/mobile.webp')
        ->assertJsonPath('plan.alt', 'Plano oficial')->assertJsonPath('plan.caption', 'Plano general del proyecto');
});

test('editar el perfil sin nuevo plano conserva el plano actual', function () {
    $project = availabilityProject();
    $project->webProfile()->update(['plan_image_path' => 'landing/projects/plans/existing.jpg']);
    $this->actingAs(availabilityAdmin())->putJson(route('admin.project-web-profiles.update', $project), availabilityUpdatePayload())->assertOk();
    expect($project->webProfile()->first()->plan_image_path)->toBe('landing/projects/plans/existing.jpg');
});

test('un archivo de plano inválido es rechazado', function () {
    Storage::fake('public');
    $project = availabilityProject();
    $this->actingAs(availabilityAdmin())->post(route('admin.project-web-profiles.update', $project), availabilityUpdatePayload([
        '_method' => 'PUT', 'plan_image' => UploadedFile::fake()->create('plano.pdf', 200, 'application/pdf'),
    ]), ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('plan_image');
});

test('guarda plano principal y plano móvil en storage público', function () {
    Storage::fake('public');
    $project = availabilityProject();

    $response = $this->actingAs(availabilityAdmin())->post(route('admin.project-web-profiles.update', $project), availabilityUpdatePayload([
        '_method' => 'PUT',
        'plan_image' => UploadedFile::fake()->image('plano-general.jpg', 1600, 900),
        'plan_mobile_image' => UploadedFile::fake()->image('plano-movil.png', 700, 1000),
        'plan_image_alt' => 'Plano general del proyecto',
        'plan_caption' => 'Distribución referencial del proyecto',
    ]), ['Accept' => 'application/json'])->assertOk()->assertJsonPath('success', true);

    $profile = $project->fresh()->webProfile;
    Storage::disk('public')->assertExists($profile->plan_image_path);
    Storage::disk('public')->assertExists($profile->plan_mobile_image_path);
    expect($profile->plan_image_path)->toStartWith('landing/projects/plans/')
        ->and($profile->plan_mobile_image_path)->toStartWith('landing/projects/plans/');

    $response->assertJsonPath('data.plan_image_alt', 'Plano general del proyecto')
        ->assertJsonPath('data.plan_caption', 'Distribución referencial del proyecto');
});

test('si no existe plano móvil el endpoint público reutiliza el plano principal', function () {
    $project = availabilityProject();
    $project->webProfile()->update([
        'plan_image_path' => 'landing/projects/plans/general.jpg',
        'plan_mobile_image_path' => null,
    ]);

    $this->getJson(route('public.projects.availability', $project))->assertOk()
        ->assertJsonPath('plan.url', 'http://lotesys.test/storage/landing/projects/plans/general.jpg')
        ->assertJsonPath('plan.mobile_url', 'http://lotesys.test/storage/landing/projects/plans/general.jpg');
});

test('reemplazar un plano elimina el archivo anterior solo después de guardar el nuevo', function () {
    Storage::fake('public');
    $project = availabilityProject();
    Storage::disk('public')->put('landing/projects/plans/anterior.jpg', 'plano anterior');
    $project->webProfile()->update(['plan_image_path' => 'landing/projects/plans/anterior.jpg']);

    $this->actingAs(availabilityAdmin())->post(route('admin.project-web-profiles.update', $project), availabilityUpdatePayload([
        '_method' => 'PUT',
        'plan_image' => UploadedFile::fake()->image('nuevo-plano.jpg', 1600, 900),
    ]), ['Accept' => 'application/json'])->assertOk();

    $profile = $project->fresh()->webProfile;
    Storage::disk('public')->assertMissing('landing/projects/plans/anterior.jpg');
    Storage::disk('public')->assertExists($profile->plan_image_path);
    expect($profile->plan_image_path)->not->toBe('landing/projects/plans/anterior.jpg');
});
