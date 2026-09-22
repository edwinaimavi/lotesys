<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class LandingContactPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $role = Role::where('name', 'Administrador')->first();
        foreach (['index' => 'Ver contactos web', 'store' => 'Crear contactos web', 'show' => 'Ver detalle de contactos web', 'update' => 'Editar contactos web', 'toggle' => 'Activar o desactivar contactos web'] as $action => $description) {
            $permission = Permission::firstOrCreate(['name' => 'admin.landing-contacts.'.$action, 'guard_name' => 'web'], ['description' => $description]);
            $role?->givePermissionTo($permission);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
