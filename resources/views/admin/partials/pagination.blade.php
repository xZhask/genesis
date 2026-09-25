{{-- Paginación simple, sin Tailwind --}}
@if ($paginator->hasPages())
    <nav class="pager" aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span class="btn btn-line btn-sm" aria-disabled="true">« Anterior</span>
        @else
            <a class="btn btn-line btn-sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">« Anterior</a>
        @endif

        <span class="pager-info">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a class="btn btn-line btn-sm" href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente »</a>
        @else
            <span class="btn btn-line btn-sm" aria-disabled="true">Siguiente »</span>
        @endif
    </nav>
@endif
