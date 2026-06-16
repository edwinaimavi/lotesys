<div class="btn-group shadow-sm" role="group" aria-label="Actions">

    {{-- VIEW --}}
    <button type="button" class="btn btn-outline-info btn-sm viewBlock" data-toggle="tooltip" title="Ver Manzana"
        data-id="{{ $block->id }}" data-project="{{ $block->project->name ?? '—' }}"
        data-project_id="{{ $block->project_id }}" data-name="{{ $block->name }}"
        data-description="{{ $block->description }}" data-status="{{ $block->status }}"
        data-created_at="{{ $block->created_at ? $block->created_at->format('d/m/Y H:i') : '—' }}"
        data-updated_at="{{ $block->updated_at ? $block->updated_at->format('d/m/Y H:i') : '—' }}"
        data-created_by="{{ $block->creator->name ?? 'No registrado' }}"
        data-updated_by="{{ $block->updater->name ?? 'No registrado' }}" data-lots_count="{{ $block->lots->count() }}">
        <i class="fas fa-eye"></i>
    </button>

    {{-- GENERAR LOTES --}}
    <button type="button" class="btn btn-outline-success btn-sm generateLots" data-toggle="tooltip"
        title="Generar Lotes" data-id="{{ $block->id }}" data-project="{{ $block->project->name ?? '—' }}"
        data-project_id="{{ $block->project_id }}" data-block="{{ $block->name }}"
        data-block_id="{{ $block->id }}">

        <i class="fas fa-layer-group"></i>

    </button>

    {{-- EDIT --}}
    <button type="button" class="btn btn-outline-primary btn-sm editBlock" data-toggle="tooltip" title="Editar Manzana"
        data-id="{{ $block->id }}" data-project_id="{{ $block->project_id }}" data-name="{{ $block->name }}"
        data-description="{{ $block->description }}" data-status="{{ $block->status }}">

        <i class="fas fa-pen"></i>

    </button>

    {{-- DELETE --}}
    <button type="button" class="btn btn-outline-danger btn-sm deleteBlock" data-id="{{ $block->id }}"
        data-toggle="tooltip" title="Eliminar Manzana">

        <i class="fas fa-trash"></i>

    </button>

</div>
