<div class="btn-group shadow-sm" role="group" aria-label="Actions">

    {{-- VIEW --}}
    <button type="button" class="btn btn-outline-info btn-sm viewHoliday" data-toggle="tooltip" title="Ver Feriado"
        data-id="{{ $holiday->id }}" data-date="{{ $holiday->date }}" data-description="{{ $holiday->description }}"
        data-status="{{ $holiday->status }}"
        data-created_at="{{ $holiday->created_at ? $holiday->created_at->format('d/m/Y H:i') : '—' }}"
        data-updated_at="{{ $holiday->updated_at ? $holiday->updated_at->format('d/m/Y H:i') : '—' }}"
        data-created_by="{{ $holiday->creator->name ?? 'No registrado' }}"
        data-updated_by="{{ $holiday->updater->name ?? 'No registrado' }}">

        <i class="fas fa-eye"></i>

    </button>

    {{-- EDIT --}}
    <button type="button" class="btn btn-outline-primary btn-sm editHoliday" data-toggle="tooltip"
        title="Editar Feriado" data-id="{{ $holiday->id }}" data-date="{{ $holiday->date }}"
        data-description="{{ $holiday->description }}" data-status="{{ $holiday->status }}">

        <i class="fas fa-pen"></i>

    </button>

    {{-- DELETE --}}
    <button type="button" class="btn btn-outline-danger btn-sm deleteHoliday" data-id="{{ $holiday->id }}"
        data-toggle="tooltip" title="Eliminar Feriado">

        <i class="fas fa-trash"></i>

    </button>

</div>
