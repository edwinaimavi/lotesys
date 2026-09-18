<div class="modal fade" id="roleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content role-modal-content">
            <div class="modal-header role-modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="exampleModalLabel">Nuevo Rol</h5>
                    <small>Define el nombre y los accesos que tendrá este rol dentro del sistema.</small>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="roleForm">
                @csrf

                <div class="modal-body role-modal-body">
                    <div class="role-name-panel">
                        <div class="form-group mb-0">
                            <label class="role-field-label" for="name">
                                <i class="fas fa-user-tag mr-2"></i>Nombre del rol
                            </label>
                            <input type="text" class="form-control role-name-input" id="name" name="name"
                                placeholder="Ej. Vendedor, Caja, Administrador" required autocomplete="off">
                            <small class="form-text text-muted">Usa un nombre corto que describa claramente la función del usuario.</small>
                            <div id="error-messages" class="alert alert-danger d-none mt-3 mb-0"></div>
                        </div>
                    </div>

                    <div class="role-permissions-toolbar">
                        <div class="role-permissions-heading">
                            <span class="role-permissions-icon"><i class="fas fa-shield-alt"></i></span>
                            <div>
                                <h6 class="mb-0">Permisos del rol</h6>
                                <small>Organizados por módulo para que puedas revisarlos rápidamente.</small>
                            </div>
                        </div>

                        <div class="role-permissions-summary">
                            <span class="permission-selected-counter">
                                <strong id="selectedPermissionCount">0</strong> de {{ $permissions->count() }} seleccionados
                            </span>
                            <div class="custom-control custom-switch ml-3">
                                <input type="checkbox" class="custom-control-input" id="toggleAllPermissions">
                                <label class="custom-control-label" for="toggleAllPermissions">Todos</label>
                            </div>
                        </div>
                    </div>

                    <div class="role-permission-filter">
                        <i class="fas fa-search"></i>
                        <input type="text" id="permissionSearch" class="form-control"
                            placeholder="Buscar módulo o permiso..." autocomplete="off">
                    </div>

                    <div class="row role-permissions-grid" id="permissionModules">
                        @foreach ($permissionGroups as $group)
                            <div class="col-12 col-lg-6 permission-module-column"
                                data-module-search="{{ mb_strtolower($group['label'] . ' ' . $group['key']) }}">
                                <section class="permission-module-card" data-module="{{ $group['key'] }}">
                                    <div class="permission-module-header">
                                        <div class="permission-module-title">
                                            <span class="permission-module-icon">
                                                <i class="{{ $group['icon'] }}"></i>
                                            </span>
                                            <div>
                                                <h6>{{ $group['label'] }}</h6>
                                                <small>{{ $group['permissions']->count() }} {{ $group['permissions']->count() === 1 ? 'permiso' : 'permisos' }}</small>
                                            </div>
                                        </div>

                                        <div class="custom-control custom-switch permission-module-toggle-wrap">
                                            <input type="checkbox"
                                                class="custom-control-input js-module-toggle"
                                                id="module_{{ $loop->index }}"
                                                data-module="{{ $group['key'] }}">
                                            <label class="custom-control-label" for="module_{{ $loop->index }}">Todos</label>
                                        </div>
                                    </div>

                                    <div class="permission-module-body">
                                        @foreach ($group['permissions'] as $permission)
                                            <div class="permission-row"
                                                data-permission-search="{{ mb_strtolower(($permission->description ?: $permission->name) . ' ' . $permission->name) }}">
                                                <div class="permission-copy">
                                                    <span class="permission-row-icon"><i class="fas fa-key"></i></span>
                                                    <label for="permission_{{ $permission->id }}" class="permission-label mb-0">
                                                        {{ $permission->description ?: $permission->name }}
                                                    </label>
                                                </div>

                                                <div class="custom-control custom-switch mb-0">
                                                    <input type="checkbox"
                                                        class="custom-control-input permission-switch"
                                                        value="{{ $permission->name }}"
                                                        id="permission_{{ $permission->id }}"
                                                        name="permissions[]"
                                                        data-module="{{ $group['key'] }}">
                                                    <label class="custom-control-label permission-switch-label"
                                                        for="permission_{{ $permission->id }}"
                                                        title="{{ $permission->name }}">
                                                        <span class="sr-only">Activar {{ $permission->description ?: $permission->name }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            </div>
                        @endforeach
                    </div>

                    <div id="permissionSearchEmpty" class="permission-search-empty d-none">
                        <i class="fas fa-search mb-2"></i>
                        <strong>No encontramos permisos con ese texto.</strong>
                        <span>Prueba con otro nombre de módulo o acción.</span>
                    </div>
                </div>

                <div class="modal-footer role-modal-footer">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save mr-1"></i> Guardar rol
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('js')
<script>
    (function ($) {
        'use strict';

        const $modal = $('#roleModal');
        const $permissions = $modal.find('.permission-switch');
        const $allToggle = $modal.find('#toggleAllPermissions');
        const $counter = $modal.find('#selectedPermissionCount');
        const $search = $modal.find('#permissionSearch');

        function updatePermissionUi() {
            const checked = $permissions.filter(':checked').length;
            $counter.text(checked);

            $allToggle.prop('checked', $permissions.length > 0 && checked === $permissions.length);
            $allToggle.prop('indeterminate', checked > 0 && checked < $permissions.length);

            $modal.find('.js-module-toggle').each(function () {
                const module = $(this).data('module');
                const $modulePermissions = $permissions.filter('[data-module="' + module + '"]');
                const moduleChecked = $modulePermissions.filter(':checked').length;

                $(this).prop('checked', $modulePermissions.length > 0 && moduleChecked === $modulePermissions.length);
                $(this).prop('indeterminate', moduleChecked > 0 && moduleChecked < $modulePermissions.length);
            });
        }

        $modal.on('change', '.permission-switch', updatePermissionUi);

        $modal.on('change', '.js-module-toggle', function () {
            const module = $(this).data('module');
            $permissions
                .filter('[data-module="' + module + '"]')
                .prop('checked', $(this).is(':checked'));

            updatePermissionUi();
        });

        $allToggle.on('change', function () {
            $permissions.prop('checked', $(this).is(':checked'));
            updatePermissionUi();
        });

        $search.on('input', function () {
            const query = $.trim($(this).val().toLowerCase());
            let visibleModules = 0;

            $modal.find('.permission-module-column').each(function () {
                const $column = $(this);
                const moduleText = String($column.data('module-search') || '');
                const moduleMatches = !query || moduleText.indexOf(query) !== -1;
                let visiblePermissions = 0;

                $column.find('.permission-row').each(function () {
                    const permissionText = String($(this).data('permission-search') || '');
                    const visible = moduleMatches || !query || permissionText.indexOf(query) !== -1;
                    $(this).toggle(visible);
                    if (visible) visiblePermissions++;
                });

                const showModule = !query || moduleMatches || visiblePermissions > 0;
                $column.toggle(showModule);
                if (showModule) visibleModules++;
            });

            $('#permissionSearchEmpty').toggleClass('d-none', visibleModules > 0);
        });

        $modal.on('shown.bs.modal', function () {
            setTimeout(updatePermissionUi, 0);
        });

        $modal.on('hidden.bs.modal', function () {
            $search.val('');
            $modal.find('.permission-module-column, .permission-row').show();
            $('#permissionSearchEmpty').addClass('d-none');
            setTimeout(updatePermissionUi, 0);
        });

        $(document).ajaxComplete(function () {
            if ($modal.hasClass('show')) {
                setTimeout(updatePermissionUi, 0);
            }
        });
    })(jQuery);
</script>
@endpush
