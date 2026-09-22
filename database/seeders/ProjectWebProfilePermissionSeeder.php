<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ProjectWebProfilePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'admin.project-web-profiles.index' => 'Ver proyectos web',
            'admin.project-web-profiles.show' => 'Ver configuración de proyectos web',
            'admin.project-web-profiles.update' => 'Editar configuración de proyectos web',
        ];
        $role = Role::query()->where('name', 'Administrador')->first();

        foreach ($permissions as $name => $description) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );
            $role?->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
