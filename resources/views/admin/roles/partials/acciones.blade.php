<div class="btn-group btn-group-sm role-action-group" role="group" aria-label="Acciones del rol">
    @can('admin.roles.update')
        <button type="button"
            class="btn btn-outline-primary editRole"
            data-id="{{ $role->id }}"
            data-name="{{ $role->name }}"
            title="Editar rol">
            <i class="fas fa-pen"></i>
        </button>
    @endcan

    @can('admin.roles.destroy')
        <button type="button"
            class="btn btn-outline-danger deleteRole"
            data-id="{{ $role->id }}"
            title="Eliminar rol">
            <i class="fas fa-trash"></i>
        </button>
    @endcan
</div>
