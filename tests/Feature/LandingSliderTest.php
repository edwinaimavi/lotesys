<?php

use App\Models\LandingSlider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function landingSliderUser(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $name) {
        $permission = Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        $user->givePermissionTo($permission);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

function landingSliderPayload(array $overrides = []): array
{
    return array_merge([
        'eyebrow' => 'NUEVA OPORTUNIDAD',
        'title' => 'Un nuevo lugar para crecer',
        'description' => 'Descripción del nuevo slide.',
        'secondary_text' => 'El futuro empieza aquí.',
        'button_text' => 'Conocer más',
        'button_url' => '#proyectos',
        'image_alt' => 'Vista panorámica del proyecto',
        'sort_order' => 4,
        'is_active' => true,
    ], $overrides);
}

test('un invitado no puede acceder al módulo administrativo', function () {
    $this->get(route('admin.landing-sliders.index'))->assertRedirect('/login');
});

test('un usuario sin permiso no puede administrar slides', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.landing-sliders.index'))->assertForbidden();
    $this->actingAs($user)->postJson(route('admin.landing-sliders.store'), landingSliderPayload())->assertForbidden();
});

test('un usuario autorizado puede crear un slide con imagen', function () {
    Storage::fake('public');
    $user = landingSliderUser(['admin.landing-sliders.store']);
    $payload = landingSliderPayload(['image' => UploadedFile::fake()->image('slide.jpg', 1600, 900)]);

    $response = $this->actingAs($user)->postJson(route('admin.landing-sliders.store'), $payload)
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', $payload['title']);

    $slider = LandingSlider::query()->findOrFail($response->json('data.id'));
    expect($slider->title)->toBe($payload['title']);
    Storage::disk('public')->assertExists($slider->image_path);
});

test('un usuario autorizado puede editar sin reemplazar la imagen', function () {
    $user = landingSliderUser(['admin.landing-sliders.update']);
    $slider = LandingSlider::query()->orderBy('id')->firstOrFail();
    $originalPath = $slider->image_path;

    $this->actingAs($user)->putJson(route('admin.landing-sliders.update', $slider), landingSliderPayload([
        'title' => 'Título actualizado',
        'sort_order' => 2,
    ]))->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', 'Título actualizado');

    expect($slider->fresh()->title)->toBe('Título actualizado')
        ->and($slider->fresh()->image_path)->toBe($originalPath);
});

test('un usuario autorizado puede activar y desactivar', function () {
    $user = landingSliderUser(['admin.landing-sliders.toggle']);
    $slider = LandingSlider::query()->firstOrFail();

    $this->actingAs($user)->patchJson(route('admin.landing-sliders.toggle', $slider))
        ->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.is_active', false);
    $this->actingAs($user)->patchJson(route('admin.landing-sliders.toggle', $slider))
        ->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.is_active', true);
});

test('la imagen es obligatoria al crear y rechaza formatos no permitidos', function () {
    Storage::fake('public');
    $user = landingSliderUser(['admin.landing-sliders.store']);

    $this->actingAs($user)->postJson(route('admin.landing-sliders.store'), landingSliderPayload())
        ->assertUnprocessable()->assertJsonValidationErrors('image');

    $this->actingAs($user)->postJson(route('admin.landing-sliders.store'), landingSliderPayload([
        'image' => UploadedFile::fake()->create('slide.svg', 20, 'image/svg+xml'),
    ]))->assertUnprocessable()->assertJsonValidationErrors('image');
});

test('rechaza protocolos inseguros en el enlace del botón', function () {
    Storage::fake('public');
    $user = landingSliderUser(['admin.landing-sliders.store']);

    $this->actingAs($user)->postJson(route('admin.landing-sliders.store'), landingSliderPayload([
        'button_url' => 'javascript:alert(1)',
        'image' => UploadedFile::fake()->image('slide.jpg'),
    ]))->assertUnprocessable()->assertJsonValidationErrors('button_url');
});

test('la landing muestra solo slides activos y respeta el orden', function () {
    LandingSlider::query()->update(['is_active' => false]);
    LandingSlider::query()->create(landingSliderPayload(['title' => 'SEGUNDO SLIDE PÚBLICO', 'image_path' => 'images/second.jpg', 'sort_order' => 2]));
    LandingSlider::query()->create(landingSliderPayload(['title' => 'PRIMER SLIDE PÚBLICO', 'image_path' => 'images/first.jpg', 'sort_order' => 1]));
    LandingSlider::query()->create(landingSliderPayload(['title' => 'SLIDE OCULTO', 'image_path' => 'images/hidden.jpg', 'sort_order' => 1, 'is_active' => false]));

    $response = $this->get('/')->assertOk()->assertSee('PRIMER SLIDE PÚBLICO')->assertSee('SEGUNDO SLIDE PÚBLICO')->assertDontSee('SLIDE OCULTO');
    expect(strpos($response->getContent(), 'PRIMER SLIDE PÚBLICO'))->toBeLessThan(strpos($response->getContent(), 'SEGUNDO SLIDE PÚBLICO'));
});

test('la landing usa el fallback de configuración cuando no hay slides activos', function () {
    LandingSlider::query()->update(['is_active' => false]);

    $this->get('/')
        ->assertOk()
        ->assertSee(config('landing.hero_slides.0.title_lead'))
        ->assertSee(config('landing.hero_slides.0.title_accent'))
        ->assertSee('id="lot-search"', false)
        ->assertSee('id="proyectos"', false);
});

test('más de cinco slides activos no bloquean el módulo', function () {
    $user = landingSliderUser(['admin.landing-sliders.index']);

    foreach (range(1, 6) as $order) {
        LandingSlider::query()->create(landingSliderPayload([
            'title' => "Slide adicional {$order}",
            'image_path' => "images/slide-{$order}.jpg",
            'sort_order' => $order + 10,
        ]));
    }

    $this->actingAs($user)->get(route('admin.landing-sliders.index'))
        ->assertOk()
        ->assertSee('Se recomienda mantener entre 3 y 5');
});
