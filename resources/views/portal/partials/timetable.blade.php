{{-- Horario semanal: cuadrícula en computador e impresión; un día a la vez en el celular --}}
@php
    $now = now();
    $showEmptyRows = $showEmptyRows ?? true;
@endphp

<div class="tt" data-timetable data-today="{{ $today }}">
    <div class="table-wrap tt-week">
        <table class="table keep-grid tt-table">
            <caption class="sr-only">{{ $title }}</caption>
            <thead>
                <tr>
                    <th scope="col">Hora</th>
                    @foreach ($days as $day => $name)
                        <th scope="col" @class(['is-today' => $day === $now->dayOfWeekIso])>{{ $name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($timetable->rows as $row)
                    @if ($row['break'])
                        <tr class="tt-break">
                            <th scope="row">{{ $row['range'] }}</th>
                            <td colspan="{{ count($days) }}">{{ $row['break'] }}</td>
                        </tr>
                    @else
                        <tr>
                            <th scope="row">{{ $row['range'] }}</th>
                            @foreach ($days as $day => $name)
                                <td @class(['is-now' => $timetable->isNow($row, $day), 'is-empty' => empty($row['cells'][$day])])>
                                    @forelse ($row['cells'][$day] ?? [] as $entry)
                                        <span class="tt-entry">
                                            <strong>{{ $entry['subject'] }}</strong>
                                            @if ($entry['detail'])<small>{{ $entry['detail'] }}</small>@endif
                                        </span>
                                    @empty
                                        <span class="tt-free"><span class="sr-only">Sin clase</span></span>
                                    @endforelse
                                    @if ($timetable->isNow($row, $day))<span class="tt-now-tag">Ahora</span>@endif
                                </td>
                            @endforeach
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="tt-mobile">
        {{-- Las pestañas aparecen con JavaScript; sin él se ven todos los días seguidos --}}
        <div class="tt-tabs" role="tablist" aria-label="Día de la semana" hidden>
            @foreach ($days as $day => $name)
                <button type="button" role="tab" id="tt-tab-{{ $day }}" aria-controls="tt-day-{{ $day }}" data-day="{{ $day }}"
                    aria-selected="{{ $day === $today ? 'true' : 'false' }}">
                    {{ mb_substr($name, 0, 3) }}@if ($day === $now->dayOfWeekIso)<span class="sr-only"> (hoy)</span>@endif
                </button>
            @endforeach
        </div>

        @foreach ($days as $day => $name)
            <section class="tt-day" id="tt-day-{{ $day }}" role="tabpanel" aria-labelledby="tt-tab-{{ $day }}" data-day="{{ $day }}">
                <h3>{{ $name }}@if ($day === $now->dayOfWeekIso) <small>· hoy</small>@endif</h3>
                <ol class="tt-list">
                    @php
                        $any = false;
                    @endphp
                    @foreach ($timetable->rows as $row)
                        @if ($row['break'])
                            <li class="tt-break"><span class="tt-time">{{ $row['range'] }}</span><span>{{ $row['break'] }}</span></li>
                        @elseif (! empty($row['cells'][$day]))
                            @php
                                $any = true;
                            @endphp
                            <li @class(['is-now' => $timetable->isNow($row, $day)])>
                                <span class="tt-time">{{ $row['range'] }}</span>
                                <span>
                                    @foreach ($row['cells'][$day] as $entry)
                                        <span class="tt-entry">
                                            <strong>{{ $entry['subject'] }}</strong>
                                            @if ($entry['detail'])<small>{{ $entry['detail'] }}</small>@endif
                                        </span>
                                    @endforeach
                                    @if ($timetable->isNow($row, $day))<span class="tt-now-tag">Ahora</span>@endif
                                </span>
                            </li>
                        @elseif ($showEmptyRows)
                            <li class="is-empty"><span class="tt-time">{{ $row['range'] }}</span><span>Sin clase</span></li>
                        @endif
                    @endforeach
                </ol>
                @unless ($any)
                    <p class="muted">No hay clases este día.</p>
                @endunless
            </section>
        @endforeach
    </div>
</div>
