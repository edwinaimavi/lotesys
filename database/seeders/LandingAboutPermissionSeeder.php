<?php

namespace Database\Seeders;

use App\Models\LandingAboutSection;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class LandingAboutPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'admin.landing-about.index' => 'Ver Quiénes somos de la web pública',
            'admin.landing-about.update' => 'Editar Quiénes somos de la web pública',
        ];

        $role = Role::query()->where('name', 'Administrador')->first();

        foreach ($permissions as $name => $description) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );

            $role?->givePermissionTo($permission);
        }

        LandingAboutSection::query()->firstOrCreate(
            ['key' => 'about'],
            [
                'eyebrow' => 'SOMOS GRUPO KREA',
                'title' => 'Quiénes somos',
                'description' => 'GRUPO KREA es un grupo empresarial peruano que integra a MS KREA, FARJE INGENIEROS y BARRIO FINO. Su propósito es impulsar el desarrollo de la región San Martín, integrando experiencia en construcción, ingeniería y desarrollo inmobiliario, con una visión integral y compromiso con el progreso local.',
                'mission_title' => 'Misión',
                'mission_text' => 'Desarrollar proyectos inmobiliarios y de construcción con calidad e innovación, generando valor para las personas y contribuyendo al crecimiento sostenible de San Martín, respaldados por capital y talento peruano.',
                'vision_title' => 'Visión',
                'vision_text' => 'Ser reconocidos como un grupo líder en el desarrollo inmobiliario de la región, transformando territorios en espacios de calidad para vivir, invertir y crecer, con un compromiso constante con la sostenibilidad y el desarrollo local.',
                'image_alt' => 'Grupo Krea',
                'is_active' => true,
            ],
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
