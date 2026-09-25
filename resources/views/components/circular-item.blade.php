@props(['circular'])

<li class="circular" id="{{ $circular->anchor() }}">
    <div class="circular-meta">
        <time datetime="{{ $circular->published_on->toDateString() }}">{{ $circular->published_on->longDate() }}</time>
        @if ($circular->isNew())
            <span class="tag-new">Nueva</span>
        @endif
    </div>
    <h3>{{ $circular->title }}</h3>
    @if ($circular->summary)
        <p>{{ $circular->summary }}</p>
    @endif

    @if ($circular->file_path)
        <a class="btn btn-sm btn-azul" href="{{ $circular->fileUrl() }}" target="_blank" rel="noopener">
            <x-icon name="download" /> Ver PDF <span class="size">({{ $circular->fileSizeLabel() }})</span>
        </a>
    @endif

    @if ($circular->body)
        <details class="read-more">
            <summary>Leer circular</summary>
            <div class="prose">{{ $circular->bodyHtml() }}</div>
        </details>
    @endif
</li>
