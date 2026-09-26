<x-layouts.admin title="Horarios">
    <div class="admin-head row">
        <h1>Académico</h1>
        @if ($years->count() > 1)
            <form method="GET" action="{{ route('admin.academic.schedule.index') }}" class="inline-select">
                <label for="lectivo">Año lectivo</label>
                <select id="lectivo" name="lectivo" data-autosubmit>
                    @foreach ($years as $option)
                        <option value="{{ $option->year }}" @selected($option->is($year))>{{ $option->year }}</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="btn btn-line btn-sm">Ver</button></noscript>
            </form>
        @endif
    </div>

    @include('admin.academic.partials.tabs')

    @if (! $year)
        <p class="empty panel">Primero <a href="{{ route('admin.academic.years.index') }}">crea un año lectivo</a>; el horario es de cada año.</p>
    @else
        <p class="lead-sm">Horario de clases de {{ $year->year }}, fijo para todo el año. Primero define las franjas de cada nivel; luego arma el horario de cada sección.</p>

        @error('blocks')
            <div class="error-summary" role="alert"><p>{{ $message }}</p></div>
        @enderror

        <h2 class="section-title">1. Franjas por nivel</h2>
        <div class="alert-grid schedule-levels">
            @foreach ($levels as $level)
                @php
                    $levelBlocks = $blocks[$level['key']] ?? collect();
                    $editing = old('level') === $level['key'];
                    // Filas: las guardadas y dos vacías para agregar
                    $rows = $editing ? old('blocks', []) : $levelBlocks->map(fn ($b) => [
                        'id' => $b->id, 'start' => $b->start(), 'end' => $b->end(), 'is_break' => $b->is_break, 'label' => $b->label,
                    ])->all();
                    $rows = [...array_values($rows), ['id' => null], ['id' => null]];
                @endphp
                <section class="panel lvl-{{ $level['key'] }}" id="franjas-{{ $level['key'] }}" aria-labelledby="franjas-{{ $level['key'] }}-title">
                    <h3 id="franjas-{{ $level['key'] }}-title">{{ $level['name'] }}</h3>
                    <form method="POST" action="{{ route('admin.academic.schedule.blocks', [$year, $level['key']]) }}" novalidate data-form>
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="level" value="{{ $level['key'] }}">
                        <div class="table-wrap">
                        <table class="table keep-grid blocks-table">
                            <thead>
                                <tr>
                                    <th scope="col">Inicio</th>
                                    <th scope="col">Fin</th>
                                    <th scope="col">Descanso</th>
                                    <th scope="col">Quitar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $i => $row)
                                    <tr>
                                        <td>
                                            <input type="hidden" name="blocks[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
                                            <label class="sr-only" for="b-{{ $level['key'] }}-{{ $i }}-start">Inicio</label>
                                            <input id="b-{{ $level['key'] }}-{{ $i }}-start" type="time" name="blocks[{{ $i }}][start]" value="{{ $row['start'] ?? '' }}" step="300">
                                        </td>
                                        <td>
                                            <label class="sr-only" for="b-{{ $level['key'] }}-{{ $i }}-end">Fin</label>
                                            <input id="b-{{ $level['key'] }}-{{ $i }}-end" type="time" name="blocks[{{ $i }}][end]" value="{{ $row['end'] ?? '' }}" step="300">
                                        </td>
                                        <td class="block-break">
                                            <label class="check">
                                                <input type="checkbox" name="blocks[{{ $i }}][is_break]" value="1" @checked(! empty($row['is_break']))>
                                                <span class="sr-only">Es descanso</span>
                                            </label>
                                            <label class="sr-only" for="b-{{ $level['key'] }}-{{ $i }}-label">Nombre del descanso</label>
                                            <input id="b-{{ $level['key'] }}-{{ $i }}-label" type="text" name="blocks[{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" maxlength="40" placeholder="Descanso">
                                        </td>
                                        <td>
                                            @if (! empty($row['id']))
                                                <label class="check" title="Quitar esta franja">
                                                    <input type="checkbox" name="blocks[{{ $i }}][delete]" value="1" @checked(! empty($row['delete']))>
                                                    <span class="sr-only">Quitar la franja {{ $row['start'] ?? '' }}</span>
                                                </label>
                                            @endif
                                        </td>
                                    </tr>
                                    @if ($editing)
                                        @foreach (['start', 'end', 'label'] as $field)
                                            @error("blocks.{$i}.{$field}")
                                                <tr><td colspan="4"><p class="error">{{ $message }}</p></td></tr>
                                            @enderror
                                        @endforeach
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                        <p class="hint">Deja en blanco las filas que no uses. Quitar una franja borra las clases que tenga en los horarios.</p>
                        <button type="submit" class="btn btn-line btn-sm" data-submit><span>Guardar franjas</span></button>
                    </form>
                </section>
            @endforeach
        </div>

        <h2 class="section-title">2. Horario de cada sección</h2>
        <section class="panel" aria-label="Secciones">
            <div class="table-wrap">
                <table class="table keep-grid">
                    <thead>
                        <tr>
                            <th scope="col">Sección</th>
                            <th scope="col">Clases cargadas</th>
                            <th scope="col"><span class="sr-only">Acción</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sections as $section)
                            @php
                                $total = $capacity[$section->grade->level] ?? 0;
                            @endphp
                            <tr>
                                <td><strong>{{ $section->label() }}</strong></td>
                                <td>
                                    @if (! $total)
                                        <span class="muted">Faltan las franjas del nivel</span>
                                    @else
                                        {{ $section->schedule_slots_count }} de {{ $total }}
                                        @if ($section->schedule_slots_count >= $total)
                                            <span class="badge badge-accepted">Completo</span>
                                        @elseif ($section->schedule_slots_count === 0)
                                            <span class="badge badge-withdrawn">Sin horario</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if ($total)
                                        <a class="btn btn-line btn-sm" href="{{ route('admin.academic.schedule.edit', $section) }}">Editar horario</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3">No hay secciones en {{ $year->year }}. <a href="{{ route('admin.academic.sections.index') }}">Créalas aquí</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</x-layouts.admin>
