<x-layouts.admin :title="'Horario '.$section->label()">
    <p class="back"><a href="{{ route('admin.academic.schedule.index', ['lectivo' => $section->schoolYear->year]) }}">← Horarios</a></p>
    <div class="admin-head">
        <h1>Horario de {{ $section->label() }}</h1>
        <p class="lead-sm">Año lectivo {{ $section->schoolYear->year }}. Elige la materia de cada casilla; el docente sale de las asignaciones docentes.</p>
    </div>

    @include('admin.academic.partials.tabs')

    @if ($source)
        <p class="notice">Se copió el horario de {{ $source->label() }}. Revísalo y guarda para aplicarlo a {{ $section->label() }}.</p>
    @endif

    @if ($errors->has('conflicts') || $conflicts)
        <div class="error-summary" role="alert">
            <p><strong>{{ $errors->has('conflicts') ? 'No se guardó: hay docentes con dos clases a la misma hora.' : 'El horario guardado tiene choques (quizá cambió una asignación docente).' }}</strong></p>
            <ul>
                @foreach ($errors->has('conflicts') ? $errors->get('conflicts') : $conflicts as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @error('slots')
        <div class="error-summary" role="alert"><p>{{ $message }}</p></div>
    @enderror

    @if ($blocks->reject->is_break->isEmpty())
        <p class="empty panel">Primero define las franjas de {{ $section->grade->levelConfig()['name'] }} en
            <a href="{{ route('admin.academic.schedule.index', ['lectivo' => $section->schoolYear->year]) }}#franjas-{{ $section->grade->level }}">Horarios</a>.</p>
    @else
        <div class="schedule-editor">
            <form class="panel" method="POST" action="{{ route('admin.academic.schedule.update', $section) }}" novalidate data-form data-schedule-grid>
                @csrf
                @method('PUT')
                <div class="table-wrap">
                    <table class="table keep-grid tt-table tt-edit">
                        <thead>
                            <tr>
                                <th scope="col">Hora</th>
                                @foreach ($days as $name)
                                    <th scope="col">{{ $name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($blocks as $block)
                                @if ($block->is_break)
                                    <tr class="tt-break">
                                        <th scope="row">{{ $block->range() }}</th>
                                        <td colspan="{{ count($days) }}">{{ $block->label ?: 'Descanso' }}</td>
                                    </tr>
                                @else
                                    <tr>
                                        <th scope="row">{{ $block->range() }}</th>
                                        @foreach ($days as $day => $name)
                                            @php
                                                $value = old("slots.{$block->id}.{$day}", $grid[$block->id][$day] ?? '');
                                            @endphp
                                            <td>
                                                <label class="sr-only" for="s-{{ $block->id }}-{{ $day }}">{{ $name }}, {{ $block->range() }}</label>
                                                <select id="s-{{ $block->id }}-{{ $day }}" name="slots[{{ $block->id }}][{{ $day }}]" data-slot>
                                                    <option value="">—</option>
                                                    @foreach ($section->grade->subjects as $subject)
                                                        <option value="{{ $subject->id }}" @selected((string) $value === (string) $subject->id)>{{ $subject->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-azul" data-submit data-loading-text="Guardando…"><span>Guardar horario</span></button>
                </div>
            </form>

            <div class="schedule-side">
                <section class="panel" aria-labelledby="horas-title">
                    <h2 id="horas-title">Horas por materia</h2>
                    <p class="hint">Clases en el horario frente a la intensidad del plan de estudios.</p>
                    <table class="table keep-grid hours-table">
                        <thead>
                            <tr>
                                <th scope="col">Materia</th>
                                <th scope="col" class="num">Horario</th>
                                <th scope="col" class="num">Plan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($section->grade->subjects as $subject)
                                @php
                                    $count = collect(old('slots', $grid))->flatten()->filter(fn ($id) => (int) $id === $subject->id)->count();
                                    $plan = (int) $subject->pivot->weekly_hours;
                                @endphp
                                <tr data-hours-row="{{ $subject->id }}" data-plan="{{ $plan }}" @class(['is-off' => $plan && $count !== $plan])>
                                    <td>
                                        {{ $subject->name }}
                                        <small class="block">{{ $teachers[$subject->id] ?? 'Sin docente asignado' }}</small>
                                    </td>
                                    <td class="num" data-hours-count>{{ $count }}</td>
                                    <td class="num">{{ $plan ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>

                @if ($siblings->isNotEmpty())
                    <section class="panel" aria-labelledby="copiar-title">
                        <h2 id="copiar-title">Copiar de otra sección</h2>
                        <p class="hint">Llena la cuadrícula con el horario de otra sección de {{ $section->grade->name }}. No se guarda hasta que lo revises.</p>
                        <form class="form compact" method="GET" action="{{ route('admin.academic.schedule.edit', $section) }}">
                            <x-field name="copiar" label="Sección">
                                <select id="copiar" name="copiar">
                                    @foreach ($siblings as $sibling)
                                        <option value="{{ $sibling->id }}">{{ $sibling->label() }}</option>
                                    @endforeach
                                </select>
                            </x-field>
                            <button type="submit" class="btn btn-line btn-sm">Copiar</button>
                        </form>
                    </section>
                @endif
            </div>
        </div>
    @endif
</x-layouts.admin>
