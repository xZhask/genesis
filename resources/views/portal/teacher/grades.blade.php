<x-layouts.portal title="Notas">
    <div class="admin-head">
        <h1>{{ ($preschool ?? false) ? 'Evaluación' : 'Notas' }}</h1>
    </div>

    @if ($assignments->isEmpty())
        <p class="empty panel">Todavía no tienes materias asignadas{{ $year ? ' en '.$year->year : '' }}. La administración del colegio las asigna.</p>
    @else
        <form class="attendance-picker panel" method="GET" action="{{ route('portal.teacher.grades') }}">
            <div class="field">
                <label class="label" for="clase">Clase</label>
                <select id="clase" name="clase" data-autosubmit>
                    @foreach ($assignments as $option)
                        <option value="{{ $option->id }}" @selected($option->is($assignment))>{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <span class="label" id="periodo-label">Periodo</span>
                <nav class="period-tabs" aria-labelledby="periodo-label">
                    @foreach ($periods as $option)
                        <a href="{{ route('portal.teacher.grades', ['clase' => $assignment->id, 'periodo' => $option->id]) }}"
                            @if ($option->is($period)) aria-current="page" @endif
                            @if ($option->isClosed()) title="Cerrado" @endif>{{ $option->number }}@if ($option->isClosed()) <x-icon name="lock" /><span class="sr-only"> (cerrado)</span>@endif</a>
                    @endforeach
                </nav>
            </div>
            <noscript><button type="submit" class="btn btn-line btn-sm">Ver</button></noscript>
        </form>

        @error('period')
            <div class="error-summary" role="alert"><p>{{ $message }}</p></div>
        @enderror

        @if ($preschool)
            @php $locked = $period->isClosed(); @endphp
            <section class="panel" aria-labelledby="desc-title">
                <h2 id="desc-title">{{ $assignment->label() }} · {{ $period->name() }}</h2>
                <p class="hint">Preescolar se evalúa de forma descriptiva, sin notas: escribe cómo avanza cada niño en esta dimensión (lo que logra, lo que está
                    en proceso y cómo acompañarlo en casa). Aparece en su boletín.</p>
                @if ($locked)
                    <p class="notice">El {{ mb_strtolower($period->name()) }} está cerrado: sus descripciones son de solo lectura.</p>
                @endif

                @if ($enrollments->isEmpty())
                    <p class="empty">No hay niños matriculados en {{ $assignment->section->label() }}.</p>
                @else
                    <form method="POST" action="{{ route('portal.teacher.grades.descriptions') }}" novalidate>
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="clase" value="{{ $assignment->id }}">
                        <input type="hidden" name="periodo" value="{{ $period->id }}">
                        <ol class="behavior-list">
                            @foreach ($enrollments as $enrollment)
                                <li>
                                    <div class="field @error("descriptions.{$enrollment->id}") has-error @enderror">
                                        <label class="label" for="d-{{ $enrollment->id }}">{{ $enrollment->student->sortName() }}</label>
                                        <textarea id="d-{{ $enrollment->id }}" name="descriptions[{{ $enrollment->id }}]" rows="4" maxlength="1000" class="description-input"
                                            @disabled($locked)>{{ old("descriptions.{$enrollment->id}", $descriptions[$enrollment->id] ?? '') }}</textarea>
                                        @error("descriptions.{$enrollment->id}")
                                            <p class="error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                        @unless ($locked)
                            <div class="att-save">
                                <span>{{ $descriptions->count() }} de {{ $enrollments->count() }} con descripción</span>
                                <button type="submit" class="btn btn-sol" data-submit data-loading-text="Guardando…"><span>Guardar descripciones</span></button>
                            </div>
                        @endunless
                    </form>
                @endif
            </section>
        @else
            @php $locked = $period->isClosed(); @endphp
            @if ($locked)
                <p class="notice">El {{ mb_strtolower($period->name()) }} está cerrado: sus notas y logros son de solo lectura. Si hay que corregir algo, pide a la administración que lo reabra.</p>
            @endif

            {{-- Actividades por componente --}}
            <section class="panel" aria-labelledby="act-title">
                <h2 id="act-title">{{ $assignment->label() }} · {{ $period->name() }}</h2>
                <p class="hint">La nota del periodo es el promedio ponderado de saber ({{ $scale->format($scale->weight_knowing) }} %), hacer ({{ $scale->format($scale->weight_doing) }} %)
                    y ser ({{ $scale->format($scale->weight_being) }} %). Cada componente es el promedio de sus actividades.</p>

                <div class="component-cols">
                    @foreach ($grades->itemsByComponent() as $component => $items)
                        @php $case = App\Enums\EvaluationComponent::from($component); @endphp
                        <div class="component-col">
                            <h3>{{ $case->label() }} <small>{{ $case->hint() }}</small></h3>
                            <ul class="item-list">
                                @forelse ($items as $item)
                                    <li>
                                        <span>{{ $item->name }}</span>
                                        @unless ($locked)
                                            <form method="POST" action="{{ route('portal.teacher.grades.items.destroy', $item) }}"
                                                data-confirm="¿Eliminar «{{ $item->name }}» y sus notas?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" aria-label="Eliminar {{ $item->name }}">×</button>
                                            </form>
                                        @endunless
                                    </li>
                                @empty
                                    <li class="muted">Sin actividades</li>
                                @endforelse
                            </ul>
                        </div>
                    @endforeach
                </div>

                @unless ($locked)
                    <form class="add-item" method="POST" action="{{ route('portal.teacher.grades.items.store') }}" novalidate>
                        @csrf
                        <input type="hidden" name="clase" value="{{ $assignment->id }}">
                        <input type="hidden" name="periodo" value="{{ $period->id }}">
                        <label class="sr-only" for="item-component">Componente</label>
                        <select id="item-component" name="component">
                            @foreach ($components as $case)
                                <option value="{{ $case->value }}" @selected(old('component') === $case->value)>{{ $case->label() }}</option>
                            @endforeach
                        </select>
                        <label class="sr-only" for="item-name">Nombre de la actividad</label>
                        <input id="item-name" name="name" type="text" maxlength="80" placeholder="Nueva actividad (p. ej., Taller 1)" value="{{ old('name') }}"
                            @error('name') aria-invalid="true" aria-describedby="item-name-error" @enderror>
                        <button type="submit" class="btn btn-line btn-sm">Agregar</button>
                        @error('name')
                            <p class="error" id="item-name-error">{{ $message }}</p>
                        @enderror
                    </form>
                @endunless
            </section>

            {{-- Planilla --}}
            <section class="panel" aria-labelledby="planilla-title">
                <h2 id="planilla-title">Planilla</h2>
                @if ($enrollments->isEmpty())
                    <p class="empty">No hay estudiantes matriculados en {{ $assignment->section->label() }}.</p>
                @elseif ($grades->items->isEmpty())
                    <p class="hint">Agrega al menos una actividad para empezar a calificar.</p>
                @else
                    @error('scores')
                        <div class="error-summary" role="alert"><p>{{ $message }}</p></div>
                    @enderror
                    @php
                        // Datos para el cálculo en vivo (resources/js/grades.js)
                        $sheetWeights = ['knowing' => $scale->weight_knowing, 'doing' => $scale->weight_doing, 'being' => $scale->weight_being];
                        $sheetLevels = [
                            ['from' => $scale->superior_from, 'label' => App\Enums\Performance::Superior->label()],
                            ['from' => $scale->high_from, 'label' => App\Enums\Performance::High->label()],
                            ['from' => $scale->basic_from, 'label' => App\Enums\Performance::Basic->label()],
                            ['from' => -1, 'label' => App\Enums\Performance::Low->label()],
                        ];
                    @endphp
                    <form method="POST" action="{{ route('portal.teacher.grades.save') }}" novalidate
                        data-grade-sheet
                        data-weights="{{ json_encode($sheetWeights) }}"
                        data-decimals="{{ $scale->decimals }}"
                        data-min="{{ $scale->min_score }}" data-max="{{ $scale->max_score }}"
                        data-levels="{{ json_encode($sheetLevels) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="clase" value="{{ $assignment->id }}">
                        <input type="hidden" name="periodo" value="{{ $period->id }}">

                        <div class="sheet-wrap">
                            <table class="sheet">
                                <thead>
                                    <tr>
                                        <th scope="col" rowspan="2" class="sticky-col">Estudiante</th>
                                        @foreach ($grades->itemsByComponent() as $component => $items)
                                            @if ($items->isNotEmpty())
                                                <th scope="colgroup" colspan="{{ $items->count() }}" class="comp-{{ $component }}">{{ App\Enums\EvaluationComponent::from($component)->label() }}</th>
                                            @endif
                                        @endforeach
                                        <th scope="colgroup" colspan="5" class="calc">Resultado</th>
                                    </tr>
                                    <tr>
                                        @foreach ($grades->items as $item)
                                            <th scope="col" class="item-head comp-{{ $item->component->value }}">{{ $item->name }}</th>
                                        @endforeach
                                        <th scope="col" class="calc">Saber</th>
                                        <th scope="col" class="calc">Hacer</th>
                                        <th scope="col" class="calc">Ser</th>
                                        <th scope="col" class="calc">Nota</th>
                                        <th scope="col" class="calc">Desempeño</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($enrollments as $enrollment)
                                        @php $result = $grades->forEnrollment($enrollment->id); @endphp
                                        <tr data-row>
                                            <th scope="row" class="sticky-col">{{ $enrollment->student->sortName() }}</th>
                                            @foreach ($grades->items as $item)
                                                @php $key = "scores.{$item->id}.{$enrollment->id}"; @endphp
                                                @php $value = old($key, $scale->format($grades->score($item->id, $enrollment->id)) === '—' ? '' : $scale->format($grades->score($item->id, $enrollment->id))); @endphp
                                                <td class="@error($key) has-error @enderror">
                                                    <input type="text" inputmode="decimal" name="scores[{{ $item->id }}][{{ $enrollment->id }}]" value="{{ $value }}"
                                                        maxlength="4" data-component="{{ $item->component->value }}" @disabled($locked)
                                                        aria-label="{{ $item->name }} de {{ $enrollment->student->fullName() }}"
                                                        @error($key) aria-invalid="true" title="{{ $message }}" @enderror>
                                                </td>
                                            @endforeach
                                            <td class="calc" data-out="knowing">{{ $scale->format($result['knowing']) }}</td>
                                            <td class="calc" data-out="doing">{{ $scale->format($result['doing']) }}</td>
                                            <td class="calc" data-out="being">{{ $scale->format($result['being']) }}</td>
                                            <td class="calc strong" data-out="score">{{ $scale->format($result['score']) }}</td>
                                            <td class="calc perf-{{ $result['performance']?->value }}" data-out="performance">{{ $result['performance']?->label() ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="att-legend">Notas de {{ $scale->format($scale->min_score) }} a {{ $scale->format($scale->max_score) }}, con coma o punto. Deja vacía una casilla si aún no hay nota.</p>

                        @unless ($locked)
                            <div class="att-save">
                                <span>{{ $enrollments->count() }} estudiantes · {{ $grades->items->count() }} actividades</span>
                                <button type="submit" class="btn btn-sol" data-submit data-loading-text="Guardando…"><span>Guardar notas</span></button>
                            </div>
                        @endunless
                    </form>
                @endif
            </section>

            {{-- Logros --}}
            <section class="panel" aria-labelledby="logros-title">
                <h2 id="logros-title">Logros del periodo</h2>
                <p class="hint">Escribe cada logro <strong>en infinitivo</strong> («identificar las partes del cuento»). En el boletín se antepone la frase del desempeño de cada estudiante.</p>
                <form class="form compact" method="POST" action="{{ route('portal.teacher.grades.objectives') }}" novalidate data-form>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="clase" value="{{ $assignment->id }}">
                    <input type="hidden" name="periodo" value="{{ $period->id }}">
                    @foreach ($components as $case)
                        @php $name = "objectives.{$case->value}"; @endphp
                        <div class="field objective-field @error($name) has-error @enderror">
                            <label class="label" for="obj-{{ $case->value }}">{{ $case->label() }} <span class="opt">· {{ mb_strtolower($case->hint()) }}</span></label>
                            <textarea id="obj-{{ $case->value }}" name="objectives[{{ $case->value }}]" rows="2" maxlength="300" @disabled($locked)
                                data-objective>{{ old("objectives.{$case->value}", $objectives[$case->value] ?? '') }}</textarea>
                            @error($name)
                                <p class="error">{{ $message }}</p>
                            @enderror
                            <details class="objective-preview">
                                <summary>Ver cómo queda en el boletín</summary>
                                <ul>
                                    @foreach (array_reverse($performances) as $performance)
                                        <li><b>{{ $performance->label() }}:</b> {{ $scale->phrase($performance) }} <span data-objective-text>{{ lcfirst($objectives[$case->value] ?? '…') }}</span></li>
                                    @endforeach
                                </ul>
                            </details>
                        </div>
                    @endforeach
                    @unless ($locked)
                        <button type="submit" class="btn btn-line" data-submit><span>Guardar logros</span></button>
                    @endunless
                </form>
            </section>
        @endif
    @endif
</x-layouts.portal>
