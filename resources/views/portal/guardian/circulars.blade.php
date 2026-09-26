<x-layouts.portal title="Circulares">
    <div class="admin-head">
        <h1>Circulares</h1>
        <p class="lead-sm">Las circulares del colegio para tu familia, de la más reciente a la más antigua.</p>
    </div>

    @if ($circulars->isEmpty())
        <p class="empty panel">Todavía no hay circulares. Cuando el colegio publique una, aparecerá aquí.</p>
    @else
        <ul class="portal-circulars">
            @foreach ($circulars as $circular)
                <li class="panel portal-circular">
                    <p class="circular-meta">
                        <time datetime="{{ $circular->published_on->toDateString() }}">{{ $circular->published_on->longDate() }}</time>
                        @if ($circular->isNew())
                            <span class="badge badge-received">Nueva</span>
                        @endif
                        @if ($circular->isForFamiliesOnly() && $circular->audience)
                            <span class="badge badge-in_review">{{ $circular->audienceLabel() }}</span>
                        @endif
                    </p>
                    <h2>{{ $circular->title }}</h2>
                    @if ($circular->summary)
                        <p>{{ $circular->summary }}</p>
                    @endif
                    <div class="circular-actions">
                        @if ($circular->file_path)
                            <a class="btn btn-azul btn-sm" href="{{ $circular->fileUrl() }}" target="_blank" rel="noopener">
                                Ver PDF <small>({{ $circular->fileSizeLabel() }})</small>
                            </a>
                        @endif
                    </div>
                    @if ($circular->body)
                        <details class="circular-body">
                            <summary>Leer circular</summary>
                            <div class="md-body">{{ $circular->bodyHtml() }}</div>
                        </details>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.portal>
