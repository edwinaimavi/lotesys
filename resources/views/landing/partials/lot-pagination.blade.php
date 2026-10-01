@if($lots->lastPage() > 1)
    @php
        $startPage = max(1, $lots->currentPage() - 2);
        $endPage = min($lots->lastPage(), $lots->currentPage() + 2);
    @endphp
    <nav class="inventory-pagination" aria-label="Paginación de lotes">
        @if($lots->onFirstPage())<span class="is-disabled">← Anterior</span>@else<a href="{{ $lots->previousPageUrl() }}">← Anterior</a>@endif
        <div class="inventory-pages">
            @for($page = $startPage; $page <= $endPage; $page++)
                @if($page === $lots->currentPage())<span class="is-current" aria-current="page">{{ $page }}</span>@else<a href="{{ $lots->url($page) }}">{{ $page }}</a>@endif
            @endfor
        </div>
        @if($lots->hasMorePages())<a href="{{ $lots->nextPageUrl() }}">Siguiente →</a>@else<span class="is-disabled">Siguiente →</span>@endif
    </nav>
@endif
