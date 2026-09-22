<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class LandingSliderPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'admin.landing-sliders.index' => 'Ver slider principal',
            'admin.landing-sliders.store' => 'Crear slides del portal público',
            'admin.landing-sliders.show' => 'Ver detalle de slides',
            'admin.landing-sliders.update' => 'Editar slides del portal público',
            'admin.landing-sliders.toggle' => 'Activar o desactivar slides',
        ];

        $role = Role::query()->where('name', 'Administrador')->first();

        foreach ($permissions as $name => $description) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );

            if ($role) {
                $role->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
