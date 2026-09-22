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

function projectWebUser(array $permissions): User
{
    $user = User::factory()->create();
    foreach ($permissions as $name) {
        $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

function projectWebProject(string $name = 'Proyecto Real', array $overrides = []): Project
{
    $companyId = DB::table('companies')->insertGetId([
        'business_name' => $overrides['company'] ?? 'Inmobiliaria Real SAC',
        'trade_name' => 'Empresa real',
        'ruc' => (string) random_int(10000000000, 99999999999),
        'status' => 1,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    return Project::query()->create([
        'company_id' => $companyId,
        'name' => $name,
        'code' => 'WEB-'.uniqid(),
        'district' => $overrides['district'] ?? 'Tarapoto',
        'province' => $overrides['province'] ?? 'San Martín',
        'department' => $overrides['department'] ?? 'San Martín',
        'status' => $overrides['status'] ?? 1,
    ]);
}

function publishWebProject(Project $project, array $overrides = []): ProjectWebProfile
{
    return ProjectWebProfile::query()->create(array_merge([
        'project_id' => $project->id,
        'show_on_web' => true,
        'featured_on_home' => true,
        'commercial_status' => 'available',
        'cover_image_path' => 'landing/projects/cover-'.$project->id.'.jpg',
        'short_description' => 'Descripción comercial real.',
        'sort_order' => 1,
    ], $overrides));
}

function projectWebLot(Project $project, string $status = 'disponible', float $price = 25000, float $area = 120, int $blockStatus = 1): void
{
    $blockId = DB::table('blocks')->insertGetId([
        'project_id' => $project->id, 'name' => 'MZ '.uniqid(), 'status' => $blockStatus,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('lots')->insert([
        'project_id' => $project->id, 'block_id' => $blockId, 'code' => 'LOT-'.uniqid(),
        'number' => (string) random_int(1, 999), 'area' => $area, 'unit_measure' => 'm2',
        'cash_price' => $price, 'financed_price' => $price, 'status' => $status,
        'created_at' => now(), 'updated_at' => now(),
    ]);
}

test('el proyecto tiene una relación uno a uno con su perfil web', function () {
    $project = projectWebProject();
    $profile = publishWebProject($project);
    expect($project->fresh()->webProfile->is($profile))->toBeTrue()
        ->and($profile->project->is($project))->toBeTrue();
});

test('el perfil web aplica valores predeterminados seguros', function () {
    $project = projectWebProject();
    DB::table('project_web_profiles')->insert(['project_id' => $project->id, 'created_at' => now(), 'updated_at' => now()]);
    $profile = ProjectWebProfile::first();
    expect($profile->show_on_web)->toBeFalse()->and($profile->featured_on_home)->toBeFalse()
        ->and($profile->commercial_status)->toBe('coming_soon')->and($profile->sort_order)->toBe(1);
});

test('un invitado no accede al módulo de proyectos web', function () {
    $this->get(route('admin.project-web-profiles.index'))->assertRedirect('/login');
});

test('un usuario sin permisos no puede ver ni editar proyectos web', function () {
    $project = projectWebProject();
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.project-web-profiles.index'))->assertForbidden();
    $this->actingAs($user)->putJson(route('admin.project-web-profiles.update', $project), [])->assertForbidden();
});

test('el listado incluye proyectos que aún no tienen perfil web', function () {
    $project = projectWebProject('Proyecto sin configurar');
    $user = projectWebUser(['admin.project-web-profiles.index']);
    $this->actingAs($user)->get(route('admin.project-web-profiles.index'))->assertOk()->assertSee($project->name)->assertSee('NO PUBLICADO');
});

test('el permiso show permite consultar la configuración por ajax', function () {
    $project = projectWebProject(); publishWebProject($project);
    $user = projectWebUser(['admin.project-web-profiles.show']);
    $this->actingAs($user)->getJson(route('admin.project-web-profiles.show', $project))->assertOk()
        ->assertJsonPath('data.name', $project->name)->assertJsonPath('data.commercial_status_label', 'DISPONIBLE');
});

test('se configura un proyecto web con portada y todos sus datos', function () {
    Storage::fake('public');
    $project = projectWebProject();
    $user = projectWebUser(['admin.project-web-profiles.update']);
    $response = $this->actingAs($user)->post(route('admin.project-web-profiles.update', $project), [
        '_method' => 'PUT', 'show_on_web' => 1, 'featured_on_home' => 1,
        'commercial_status' => 'presale', 'cover_image' => UploadedFile::fake()->image('cover.jpg', 1200, 700),
        'mobile_image' => UploadedFile::fake()->image('mobile.png', 600, 900),
        'short_description' => 'Nueva descripción', 'image_alt' => 'Vista del proyecto',
        'badge_text' => 'Nueva etapa', 'sort_order' => 4,
    ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('success', true);
    $profile = ProjectWebProfile::query()->whereBelongsTo($project)->firstOrFail();
    expect($profile->commercial_status)->toBe('presale')->and($profile->sort_order)->toBe(4);
    Storage::disk('public')->assertExists($profile->cover_image_path);
    Storage::disk('public')->assertExists($profile->mobile_image_path);
});

test('la portada es obligatoria cuando se publica por primera vez', function () {
    $project = projectWebProject();
    $user = projectWebUser(['admin.project-web-profiles.update']);
    $this->actingAs($user)->putJson(route('admin.project-web-profiles.update', $project), [
        'show_on_web' => true, 'featured_on_home' => false, 'commercial_status' => 'coming_soon', 'sort_order' => 1,
    ])->assertUnprocessable()->assertJsonValidationErrors('cover_image');
});

test('rechaza imágenes con formatos no permitidos', function () {
    $project = projectWebProject();
    $user = projectWebUser(['admin.project-web-profiles.update']);
    $this->actingAs($user)->post(route('admin.project-web-profiles.update', $project), [
        '_method' => 'PUT', 'show_on_web' => 0, 'featured_on_home' => 0,
        'commercial_status' => 'coming_soon', 'sort_order' => 1,
        'cover_image' => UploadedFile::fake()->create('cover.svg', 20, 'image/svg+xml'),
    ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('cover_image');
});

test('editar sin subir portada conserva la imagen existente', function () {
    $project = projectWebProject(); $profile = publishWebProject($project);
    $user = projectWebUser(['admin.project-web-profiles.update']);
    $this->actingAs($user)->putJson(route('admin.project-web-profiles.update', $project), [
        'show_on_web' => true, 'featured_on_home' => false, 'commercial_status' => 'last_units', 'sort_order' => 2,
    ])->assertOk();
    expect($profile->fresh()->cover_image_path)->toBe('landing/projects/cover-'.$project->id.'.jpg');
});

test('un proyecto oculto no aparece en la landing', function () {
    $project = projectWebProject('Proyecto Oculto'); publishWebProject($project, ['show_on_web' => false]);
    $this->get('/')->assertOk()->assertDontSee('Proyecto Oculto');
});

test('un proyecto publicado y destacado aparece en la landing', function () {
    $project = projectWebProject('Proyecto Destacado'); publishWebProject($project);
    $this->get('/')->assertOk()->assertSee('Proyecto Destacado');
});

test('si no hay destacados se muestran los proyectos publicados', function () {
    $project = projectWebProject('Proyecto Publicado'); publishWebProject($project, ['featured_on_home' => false]);
    $this->get('/')->assertOk()->assertSee('Proyecto Publicado');
});

test('la portada pública limita el catálogo a seis proyectos', function () {
    foreach (range(1, 7) as $number) publishWebProject(projectWebProject('Proyecto límite '.$number), ['sort_order' => $number]);

    $html = $this->get('/')->assertOk()->getContent();
    $sectionStart = strpos($html, 'id="proyectos"');
    $sectionEnd = strpos($html, 'id="disponibilidad"', $sectionStart);

    expect($sectionStart)->not->toBeFalse();
    expect($sectionEnd)->not->toBeFalse();

    $projectsSection = substr($html, $sectionStart, $sectionEnd - $sectionStart);

    foreach (range(1, 6) as $number) {
        expect($projectsSection)->toContain('Proyecto límite '.$number);
    }

    expect($projectsSection)->not->toContain('Proyecto límite 7');
});

test('la landing respeta el orden comercial configurado', function () {
    publishWebProject(projectWebProject('Proyecto Alfa'), ['sort_order' => 2]);
    publishWebProject(projectWebProject('Proyecto Zeta'), ['sort_order' => 1]);
    $html = $this->get('/')->assertOk()->getContent();
    $sectionStart = strpos($html, 'id="proyectos"');
    expect(strpos($html, 'Proyecto Zeta', $sectionStart))->toBeLessThan(strpos($html, 'Proyecto Alfa', $sectionStart));
});

test('la tarjeta usa empresa y ubicación reales', function () {
    $project = projectWebProject('Proyecto Datos Reales', ['company' => 'Empresa Verificada SAC', 'district' => 'Morales']);
    publishWebProject($project);
    $this->get('/')->assertOk()->assertSee('Empresa Verificada SAC')->assertSee('Morales, San Martín, San Martín');
});

test('un proyecto próximo sin lotes muestra mensaje comercial sin métricas en cero', function () {
    $project = projectWebProject('Proyecto Próximo'); publishWebProject($project, ['commercial_status' => 'coming_soon']);
    $this->get('/')->assertOk()->assertSee('Un nuevo proyecto está por llegar.')->assertDontSee('0 lotes disponibles');
});

test('un proyecto en preventa sin lotes se publica sin inventar disponibilidad', function () {
    $project = projectWebProject('Proyecto Preventa'); publishWebProject($project, ['commercial_status' => 'presale']);
    $this->get('/')->assertOk()->assertSee('Sé de los primeros en conocer este proyecto.')->assertDontSee('0 lotes disponibles');
});

test('las métricas cuentan exclusivamente lotes disponibles en manzanas activas', function () {
    $project = projectWebProject('Proyecto Métricas'); publishWebProject($project);
    projectWebLot($project, 'disponible', 32000, 140);
    projectWebLot($project, 'disponible', 25000, 90);
    projectWebLot($project, 'vendido', 1000, 40);
    projectWebLot($project, 'separado', 2000, 50);
    projectWebLot($project, 'bloqueado', 3000, 60);
    projectWebLot($project, 'disponible', 4000, 70, 0);
    $this->get('/')->assertOk()->assertSee('<strong>2</strong> lotes disponibles', false)->assertSee('Desde S/ 25,000')->assertSee('Desde 90 m²')->assertDontSee('Desde S/ 1,000');
});

test('actualizar el perfil web no modifica los datos maestros del proyecto', function () {
    Storage::fake('public');
    $project = projectWebProject('Nombre Maestro', ['district' => 'Distrito Maestro']);
    $original = $project->only(['company_id', 'name', 'code', 'district', 'province', 'department', 'status']);
    $user = projectWebUser(['admin.project-web-profiles.update']);
    $this->actingAs($user)->post(route('admin.project-web-profiles.update', $project), [
        '_method' => 'PUT', 'show_on_web' => 1, 'featured_on_home' => 1, 'commercial_status' => 'available',
        'cover_image' => UploadedFile::fake()->image('cover.jpg'), 'sort_order' => 1,
    ], ['Accept' => 'application/json'])->assertOk();
    expect($project->fresh()->only(array_keys($original)))->toBe($original);
});
