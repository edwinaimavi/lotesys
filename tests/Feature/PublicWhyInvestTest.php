<?php

use App\Models\Project;
use App\Models\ProjectWebProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function whyInvestProject(string $name = 'Proyecto Inversión', bool $published = true): Project
{
    $companyId = DB::table('companies')->insertGetId([
        'business_name' => 'Empresa Inversión SAC',
        'trade_name' => 'Inversión',
        'ruc' => (string) random_int(10000000000, 99999999999),
        'status' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $project = Project::query()->create([
        'company_id' => $companyId,
        'name' => $name,
        'code' => 'WHY-'.uniqid(),
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

test('la sección por qué invertir ofrece cuatro acciones distintas', function () {
    whyInvestProject();

    $this->get('/')
        ->assertOk()
        ->assertSee('POR QUÉ INVERTIR CON GRUPO KREA')
        ->assertSee('Ubicación y entorno')
        ->assertSee('Disponibilidad real')
        ->assertSee('Opciones a tu medida')
        ->assertSee('Proceso transparente')
        ->assertSee('data-invest-open="location"', false)
        ->assertSee('data-invest-scroll', false)
        ->assertSee('data-invest-open="simulator"', false)
        ->assertSee('data-invest-open="process"', false);
});

test('los modales usan únicamente proyectos publicados', function () {
    $publicProject = whyInvestProject('Proyecto Público', true);
    whyInvestProject('Proyecto Oculto', false);

    $response = $this->get('/')->assertOk();

    $response->assertSee('Proyecto Público')
        ->assertSee('data-summary-url="'.route('public.projects.availability', $publicProject).'"', false)
        ->assertSee('data-lots-url="'.route('public.projects.lots', $publicProject).'"', false)
        ->assertDontSee('Proyecto Oculto');
});

test('disponibilidad navega a la sección pública sin convertir todos los botones en whatsapp', function () {
    whyInvestProject();

    $this->get('/')
        ->assertOk()
        ->assertSee('href="#disponibilidad" data-invest-scroll', false)
        ->assertSee('Ver disponibilidad')
        ->assertDontSee('Consultar planes y beneficios');
});

test('el organizador de presupuesto aclara que no es una cotización ni financiamiento', function () {
    whyInvestProject();

    $this->get('/')
        ->assertOk()
        ->assertSee('ORGANIZA TU PRESUPUESTO')
        ->assertSee('REFERENCIA MENSUAL SIMPLE')
        ->assertSee('No es una cotización ni una condición de financiamiento.')
        ->assertSee('No incluye intereses, gastos, promociones ni condiciones contractuales.');
});

test('el proceso transparente prioriza revisión antes de pagar', function () {
    whyInvestProject();

    $this->get('/')
        ->assertOk()
        ->assertSee('Cinco pasos para decidir con más información.')
        ->assertSee('Revisa la documentación.')
        ->assertSee('Confirma antes de pagar.')
        ->assertSee('data-dialog-scroll="preguntas"', false);
});
