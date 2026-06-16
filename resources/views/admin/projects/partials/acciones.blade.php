<div class="btn-group shadow-sm" role="group" aria-label="Actions">

    {{-- VIEW --}}
    <button type="button" class="btn btn-outline-info btn-sm viewProject" data-bs-toggle="tooltip"
        data-bs-title="Ver Proyecto" data-id="{{ $project->id }}"
        data-company="{{ $project->company->business_name ?? '—' }}" data-company_id="{{ $project->company_id }}"
        data-name="{{ $project->name }}" data-code="{{ $project->code }}" data-description="{{ $project->description }}"
        data-address="{{ $project->address }}" data-district="{{ $project->district }}"
        data-province="{{ $project->province }}" data-department="{{ $project->department }}"
        data-total_area="{{ $project->total_area }}" data-registry_number="{{ $project->registry_number }}"
        data-start_date="{{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('d/m/Y') : '—' }}"
        data-status="{{ $project->status }}"
        data-created_at="{{ $project->created_at ? $project->created_at->format('d/m/Y H:i') : '—' }}"
        data-updated_at="{{ $project->updated_at ? $project->updated_at->format('d/m/Y H:i') : '—' }}"
        data-created_by="{{ $project->creator->name ?? 'No registrado' }}"
        data-updated_by="{{ $project->updater->name ?? 'No registrado' }}"
        data-blocks_count="{{ $project->blocks()->count() }}" data-lots_count="{{ $project->lots()->count() }}"
        data-blocks="{{ $project->blocks->pluck('name')->implode(', ') }}">

        <i class="fas fa-eye"></i>

    </button>

    {{-- EDIT --}}
    <button type="button" class="btn btn-outline-primary btn-sm editProject" data-bs-toggle="tooltip"
        data-bs-title="Editar Proyecto" data-id="{{ $project->id }}" data-company_id="{{ $project->company_id }}"
        data-name="{{ $project->name }}" data-code="{{ $project->code }}"
        data-description="{{ $project->description }}" data-address="{{ $project->address }}"
        data-district="{{ $project->district }}" data-province="{{ $project->province }}"
        data-department="{{ $project->department }}" data-total_area="{{ $project->total_area }}"
        data-registry_number="{{ $project->registry_number }}" data-start_date="{{ $project->start_date }}"
        data-status="{{ $project->status }}">
        <i class="fas fa-pen"></i>
    </button>

    {{-- DELETE --}}
    <button type="button" class="btn btn-outline-danger btn-sm deleteProject" data-id="{{ $project->id }}"
        data-bs-toggle="tooltip" data-bs-title="Eliminar Proyecto">
        <i class="fas fa-trash"></i>
    </button>

</div>
