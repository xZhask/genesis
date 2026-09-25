<x-layouts.admin :title="'Plan de estudios '.$grade->name">
    <div class="admin-head">
        <h1>Académico</h1>
    </div>

    @include('admin.academic.partials.tabs')

    <nav class="grade-switch" aria-label="Grado">
        @foreach ($grades as $option)
            <a href="{{ route('admin.academic.curriculum.edit', $option) }}" @if ($option->is($grade)) aria-current="page" @endif>{{ $option->name }}</a>
        @endforeach
    </nav>

    <div class="admin-grid detail">
        <section class="panel" aria-labelledby="plan-title">
            <div class="panel-head">
                <h2 id="plan-title">Plan de estudios de {{ $grade->name }}</h2>
                <span class="muted" data-hours-total>{{ $plan->count() }} materias · {{ $plan->sum() }} h/semana</span>
            </div>
            @if ($grade->isPreschool())
                <p class="hint">Preescolar se evalúa de forma cualitativa por dimensiones del desarrollo; la intensidad horaria puede quedar en 0.</p>
            @endif

            <form class="form compact" method="POST" action="{{ route('admin.academic.curriculum.update', $grade) }}" novalidate data-form data-curriculum>
                @csrf
                @method('PUT')
                @foreach ($areas as $area)
                    @continue($area->subjects->isEmpty())
                    <fieldset class="plan-area">
                        <legend>{{ $area->name }}</legend>
                        @foreach ($area->subjects as $subject)
                            @php
                                $included = old("subjects.{$subject->id}.included", $plan->has($subject->id));
                                $hours = old("subjects.{$subject->id}.hours", $plan[$subject->id] ?? '');
                            @endphp
                            <div class="plan-row">
                                <label class="check">
                                    <input type="hidden" name="subjects[{{ $subject->id }}][included]" value="0">
                                    <input type="checkbox" name="subjects[{{ $subject->id }}][included]" value="1" @checked($included) data-plan-check>
                                    <span>{{ $subject->name }}</span>
                                </label>
                                <label class="plan-hours">
                                    <span class="sr-only">Horas semanales de {{ $subject->name }}</span>
                                    <input type="number" min="0" max="20" inputmode="numeric" name="subjects[{{ $subject->id }}][hours]" value="{{ $hours }}" class="w-num" data-plan-hours>
                                    <span aria-hidden="true">h</span>
                                </label>
                            </div>
                            @error("subjects.{$subject->id}.hours")
                                <p class="error">{{ $message }}</p>
                            @enderror
                        @endforeach
                    </fieldset>
                @endforeach
                <button type="submit" class="btn btn-azul" data-submit data-loading-text="Guardando…"><span>Guardar plan de {{ $grade->name }}</span></button>
            </form>
        </section>

        <section class="panel" aria-labelledby="copiar-title">
            <h2 id="copiar-title">Copiar de otro grado</h2>
            <p class="hint">Reemplaza el plan de {{ $grade->name }} por el del grado que elijas. Útil para grados con el mismo plan.</p>
            <form class="form compact" method="POST" action="{{ route('admin.academic.curriculum.copy', $grade) }}"
                data-confirm="¿Reemplazar el plan de {{ $grade->name }}? Se perderán sus materias actuales.">
                @csrf
                <x-field name="from" label="Copiar el plan de">
                    <select id="from" name="from">
                        @foreach ($grades as $option)
                            @continue($option->is($grade))
                            <option value="{{ $option->id }}">{{ $option->name }}</option>
                        @endforeach
                    </select>
                </x-field>
                <button type="submit" class="btn btn-line">Copiar plan</button>
            </form>
        </section>
    </div>
</x-layouts.admin>
