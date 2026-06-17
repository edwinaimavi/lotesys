<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Roles creados por defecto
        $role1 = Role::Create(['name' => 'Administrador']);
        /*    $role2 = Role::Create(['name' => 'vendedor']); */
        $role2 = Role::Create(['name' => 'Vendedor']);


        Permission::create(['name' => 'admin.users.index', 'description' => 'Ver los Usuarios'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.users.store', 'description' => 'Crear Usuarios'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.users.update', 'description' => 'Actualizar Usuarios'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.users.destroy', 'description' => 'Eliminar Usuarios'])->syncRoles([$role1]);

        Permission::create(['name' => 'admin.roles.index', 'description' => 'Ver los Roles'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.roles.store', 'description' => 'Crear Roles'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.roles.update', 'description' => 'Actualizar Roles'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.roles.destroy', 'description' => 'Eliminar Roles'])->syncRoles([$role1]);

        Permission::create(['name' => 'admin.customers.index', 'description' => 'Ver los Clientes'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.customers.store', 'description' => 'Crear Clientes'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.customers.update', 'description' => 'Actualizar Clientes'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.customers.destroy', 'description' => 'Eliminar Clientes'])->syncRoles([$role1]);

        Permission::create(['name' => 'admin.companies.index', 'description' => 'Ver los Empresas'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.companies.store', 'description' => 'Crear Empresas'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.companies.update', 'description' => 'Actualizar Empresas'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.companies.destroy', 'description' => 'Eliminar Empresas'])->syncRoles([$role1]);

        Permission::create(['name' => 'admin.banks.index', 'description' => 'Ver los Bancos'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.banks.store', 'description' => 'Crear Bancos'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.banks.update', 'description' => 'Actualizar Bancos'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.banks.destroy', 'description' => 'Eliminar Bancos'])->syncRoles([$role1]);

        Permission::create(['name' => 'admin.late_fee_settings.index', 'description' => 'Ver Mora'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.late_fee_settings.store', 'description' => 'Crear Mora'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.late_fee_settings.update', 'description' => 'Actualizar Mora'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.late_fee_settings.destroy', 'description' => 'Eliminar Mora'])->syncRoles([$role1]);

        Permission::create(['name' => 'admin.holidays.index', 'description' => 'Ver Feriados'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.holidays.store', 'description' => 'Crear Feriados'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.holidays.update', 'description' => 'Actualizar Feriados'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.holidays.destroy', 'description' => 'Eliminar Feriados'])->syncRoles([$role1]);

        Permission::create(['name' => 'admin.projects.index', 'description' => 'Ver Proyectos'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.projects.store', 'description' => 'Crear Proyectos'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.projects.update', 'description' => 'Actualizar Proyectos'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.projects.destroy', 'description' => 'Eliminar Proyectos'])->syncRoles([$role1]);

        Permission::create(['name' => 'admin.blocks.index', 'description' => 'Ver Manzanas'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.blocks.store', 'description' => 'Crear Manzanas'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.blocks.update', 'description' => 'Actualizar Manzanas'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.blocks.destroy', 'description' => 'Eliminar Manzanas'])->syncRoles([$role1]);

        Permission::create(['name' => 'admin.lots.index', 'description' => 'Ver Lotes'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.lots.store', 'description' => 'Crear Lotes'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.lots.update', 'description' => 'Actualizar Lotes'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.lots.destroy', 'description' => 'Eliminar Lotes'])->syncRoles([$role1]);

        Permission::create(['name' => 'admin.sales.index', 'description' => 'Ver Ventas'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.sales.store', 'description' => 'Crear Ventas'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.sales.update', 'description' => 'Actualizar Ventas'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.sales.destroy', 'description' => 'Eliminar Ventas'])->syncRoles([$role1]);
        
        Permission::create(['name' => 'admin.payment_schedules.index', 'description' => 'Ver Cronograma'])->syncRoles([$role1]);

        Permission::create(['name' => 'admin.payments.index', 'description' => 'Ver Pago'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.payments.store', 'description' => 'Crear Pago'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.payments.update', 'description' => 'Actualizar Pago'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.payments.destroy', 'description' => 'Eliminar Pago'])->syncRoles([$role1]);

        Permission::create(['name' => 'admin.invoices.index', 'description' => 'Ver Comprobante'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.invoices.store', 'description' => 'Emitir Comprobante'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.invoices.update', 'description' => 'Actualizar Comprobante'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.invoices.destroy', 'description' => 'Eliminar Comprobante'])->syncRoles([$role1]);

        Permission::create(['name' => 'admin.amortizations.index', 'description' => 'Ver Amortizaciones'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.amortizations.store', 'description' => 'Crear Amortizaciones'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.amortizations.update', 'description' => 'Actualizar Amortizaciones'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.amortizations.destroy', 'description' => 'Eliminar Amortizaciones'])->syncRoles([$role1]);

        Permission::create(['name' => 'admin.rescissions.index', 'description' => 'Ver  Rescisión'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.rescissions.store', 'description' => 'Crear  Rescisión'])->syncRoles([$role1]);
        /*  Permission::create(['name' => 'admin.amortizations.update', 'description' => 'Actualizar Amortizaciones'])->syncRoles([$role1]); */
        /*  Permission::create(['name' => 'admin.amortizations.destroy', 'description' => 'Eliminar Amortizaciones'])->syncRoles([$role1]);
        */

        Permission::create(['name' => 'admin.reports.index', 'description' => 'Ver Reportes'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.reports.store', 'description' => 'Crear Reportes'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.reports.update', 'description' => 'Actualizar Reportes'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.reports.destroy', 'description' => 'Eliminar Reportes'])->syncRoles([$role1]);
    }
}
