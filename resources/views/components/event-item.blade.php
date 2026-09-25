@props(['event', 'showLevel' => false])

<li class="ev">
    <time class="date" datetime="{{ $event->starts_at->toDateString() }}">
        <b>{{ $event->starts_at->format('d') }}</b>
        <small>{{ mb_strtoupper(rtrim($event->starts_at->translatedFormat('M'), '.')) }}</small>
    </time>
    <div>
        <strong>{{ $event->title }}</strong>
        <div class="meta">
            {{ $event->metaLabel() }}
            @if ($showLevel && $event->levelName())
                · <span class="lvl lvl-{{ $event->level }}">{{ $event->levelName() }}</span>
            @endif
        </div>
        @if ($event->description && $showLevel)
            <p class="desc">{{ $event->description }}</p>
        @endif
        <x-add-to-calendar :event="$event" />
    </div>
</li>
