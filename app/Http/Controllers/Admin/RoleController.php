<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:admin.roles.index')->only('index', 'list');
        $this->middleware('can:admin.roles.store')->only('store');
        $this->middleware('can:admin.roles.update')->only('update');
        $this->middleware('can:admin.roles.destroy')->only('destroy');
    }

    public function index()
    {
        $permissions = Permission::query()->get();

        $moduleMeta = [
            'users' => ['label' => 'Usuarios', 'icon' => 'fas fa-users', 'order' => 10],
            'roles' => ['label' => 'Roles y permisos', 'icon' => 'fas fa-user-shield', 'order' => 20],
            'customers' => ['label' => 'Clientes', 'icon' => 'fas fa-address-book', 'order' => 30],
            'companies' => ['label' => 'Empresas', 'icon' => 'fas fa-building', 'order' => 40],
            'banks' => ['label' => 'Bancos', 'icon' => 'fas fa-university', 'order' => 50],
            'projects' => ['label' => 'Proyectos', 'icon' => 'fas fa-project-diagram', 'order' => 60],
            'blocks' => ['label' => 'Manzanas', 'icon' => 'fas fa-th-large', 'order' => 70],
            'lots' => ['label' => 'Lotes', 'icon' => 'fas fa-vector-square', 'order' => 80],
            'sales' => ['label' => 'Ventas', 'icon' => 'fas fa-handshake', 'order' => 90],
            'payments' => ['label' => 'Pagos', 'icon' => 'fas fa-money-check-alt', 'order' => 100],
            'invoices' => ['label' => 'Comprobantes', 'icon' => 'fas fa-file-invoice-dollar', 'order' => 110],
            'rescissions' => ['label' => 'Resoluciones', 'icon' => 'fas fa-file-contract', 'order' => 120],
            'reports' => ['label' => 'Reportes', 'icon' => 'fas fa-chart-bar', 'order' => 130],
            'payment-reports' => ['label' => 'Reportes de pagos', 'icon' => 'fas fa-chart-line', 'order' => 140],
            'payment_reports' => ['label' => 'Reportes de pagos', 'icon' => 'fas fa-chart-line', 'order' => 140],
            'holidays' => ['label' => 'Feriados', 'icon' => 'fas fa-calendar-alt', 'order' => 150],
            'amortizations' => ['label' => 'Amortizaciones', 'icon' => 'fas fa-calculator', 'order' => 160],
            'late-fee-settings' => ['label' => 'Configuración de mora', 'icon' => 'fas fa-clock', 'order' => 170],
            'late_fee_settings' => ['label' => 'Configuración de mora', 'icon' => 'fas fa-clock', 'order' => 170],
        ];

        $actionOrder = [
            'index' => 10,
            'list' => 15,
            'show' => 20,
            'store' => 30,
            'create' => 30,
            'update' => 40,
            'destroy' => 50,
            'delete' => 50,
            'export' => 60,
            'download' => 70,
            'print' => 80,
        ];

        $permissionGroups = $permissions
            ->groupBy(function (Permission $permission) {
                $parts = explode('.', $permission->name);

                return $parts[1] ?? 'otros';
            })
            ->map(function ($group, string $module) use ($moduleMeta, $actionOrder) {
                $meta = $moduleMeta[$module] ?? [
                    'label' => Str::headline(str_replace(['-', '_'], ' ', $module)),
                    'icon' => 'fas fa-folder-open',
                    'order' => 999,
                ];

                $sortedPermissions = $group->sortBy(function (Permission $permission) use ($actionOrder) {
                    $parts = explode('.', $permission->name);
                    $action = end($parts) ?: '';

                    return sprintf('%03d-%s', $actionOrder[$action] ?? 900, $permission->description ?: $permission->name);
                })->values();

                return [
                    'key' => $module,
                    'label' => $meta['label'],
                    'icon' => $meta['icon'],
                    'order' => $meta['order'],
                    'permissions' => $sortedPermissions,
                ];
            })
            ->sortBy(fn (array $group) => sprintf('%03d-%s', $group['order'], $group['label']))
            ->values();

        return view('admin.roles.index', compact('permissions', 'permissionGroups'));
    }

    public function getPermissions($id)
    {
        $role = Role::findOrFail($id);
        $permissions = $role->permissions->pluck('name');

        return response()->json($permissions);
    }

    public function list()
    {
        $roles = Role::orderBy('id', 'desc')->get();

        return DataTables::of($roles)
            ->addIndexColumn()
            ->addColumn('acciones', function ($role) {
                return view('admin.roles.partials.acciones', compact('role'))->render();
            })
            ->rawColumns(['acciones'])
            ->make(true);
    }

    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        if (!empty($data['permissions'])) {
            $permissions = Permission::whereIn('name', $data['permissions'])->pluck('id');
            $role->permissions()->sync($permissions);
        }

        return response()->json(['message' => 'Rol creado Exitosamente']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'array',
        ]);

        $role->update([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        if (!empty($data['permissions'])) {
            $permissions = Permission::whereIn('name', $data['permissions'])->pluck('id');
            $role->permissions()->sync($permissions);
        } else {
            $role->permissions()->detach();
        }

        return response()->json(['message' => 'Rol actualizado exitosamente.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
