<div class="btn-group shadow-sm" role="group" aria-label="Actions">

    {{-- VIEW --}}
    <button type="button" class="btn btn-outline-info btn-sm viewLateFeeSetting" data-toggle="tooltip"
        title="Ver Configuración" data-id="{{ $lateFeeSetting->id }}" data-grace_days="{{ $lateFeeSetting->grace_days }}"
        data-daily_late_fee="{{ $lateFeeSetting->daily_late_fee }}"
        data-max_late_fee="{{ $lateFeeSetting->max_late_fee }}"
        data-apply_sundays="{{ $lateFeeSetting->apply_sundays }}"
        data-apply_holidays="{{ $lateFeeSetting->apply_holidays }}" data-status="{{ $lateFeeSetting->status }}"
        data-created_at="{{ $lateFeeSetting->created_at ? $lateFeeSetting->created_at->format('d/m/Y H:i') : '—' }}"
        data-updated_at="{{ $lateFeeSetting->updated_at ? $lateFeeSetting->updated_at->format('d/m/Y H:i') : '—' }}"
        data-created_by="{{ $lateFeeSetting->creator->name ?? 'No registrado' }}"
        data-updated_by="{{ $lateFeeSetting->updater->name ?? 'No registrado' }}">

        <i class="fas fa-eye"></i>

    </button>

    {{-- EDIT --}}
    <button type="button" class="btn btn-outline-primary btn-sm editLateFeeSetting" data-toggle="tooltip"
        title="Editar Configuración" data-id="{{ $lateFeeSetting->id }}"
        data-grace_days="{{ $lateFeeSetting->grace_days }}"
        data-daily_late_fee="{{ $lateFeeSetting->daily_late_fee }}"
        data-max_late_fee="{{ $lateFeeSetting->max_late_fee }}"
        data-apply_sundays="{{ $lateFeeSetting->apply_sundays }}"
        data-apply_holidays="{{ $lateFeeSetting->apply_holidays }}" data-status="{{ $lateFeeSetting->status }}">

        <i class="fas fa-pen"></i>

    </button>

    {{-- DELETE --}}
    <button type="button" class="btn btn-outline-danger btn-sm deleteLateFeeSetting"
        data-id="{{ $lateFeeSetting->id }}" data-toggle="tooltip" title="Eliminar Configuración">

        <i class="fas fa-trash"></i>

    </button>

</div>
