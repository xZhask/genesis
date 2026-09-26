<x-layouts.portal :title="$title">
    @if ($back)
        <p class="back no-print"><a href="{{ $back }}">← Volver</a></p>
    @endif

    <div class="admin-head row">
        <div>
            <p class="print-only">{{ config('school.name') }}</p>
            <h1>{{ $title }}</h1>
            @if ($subtitle)
                <p class="lead-sm">{{ $subtitle }}</p>
            @endif
        </div>
        @unless ($timetable->isEmpty())
            <button type="button" class="btn btn-line btn-sm no-print" data-print>Imprimir</button>
        @endunless
    </div>

    @if ($homerooms->isNotEmpty())
        <nav class="grade-switch no-print" aria-label="Horarios">
            <a href="{{ route('portal.teacher.schedule') }}" @if (! $section) aria-current="page" @endif>Mi horario</a>
            @foreach ($homerooms as $homeroom)
                <a href="{{ route('portal.teacher.schedule', ['seccion' => $homeroom->id]) }}" @if ($section?->is($homeroom)) aria-current="page" @endif>Grupo {{ $homeroom->label() }}</a>
            @endforeach
        </nav>
    @endif

    @if ($timetable->isEmpty())
        <p class="empty panel">El colegio todavía no ha cargado el horario de clases. Cuando lo haga, aparecerá aquí.</p>
    @else
        @include('portal.partials.timetable', ['showEmptyRows' => ! (request()->routeIs('portal.teacher.schedule') && ! $section)])
    @endif
</x-layouts.portal>
