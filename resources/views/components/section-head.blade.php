@props(['id', 'title', 'lead' => null, 'link' => null, 'linkText' => null])

<div class="shead">
    <div>
        <h2 id="{{ $id }}">{{ $title }}</h2>
        @if ($lead)
            <p class="lead">{{ $lead }}</p>
        @endif
    </div>
    @if ($link)
        <a class="link" href="{{ $link }}">{{ $linkText }}</a>
    @endif
</div>
