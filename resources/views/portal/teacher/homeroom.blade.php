<x-layouts.portal title="Mi grupo">
    <div class="admin-head">
        <h1>Mi grupo</h1>
        <p class="lead-sm">Como director de grupo registras el comportamiento y las observaciones de cada periodo (en preescolar, solo las observaciones). Aparecen en el boletín.</p>
    </div>

    <form class="attendance-picker panel" method="GET" action="{{ route('portal.teacher.homeroom') }}">
        <div class="field">
            <label class="label" for="seccion">Grupo</label>
            <select id="seccion" name="seccion" data-autosubmit>
                @foreach ($sections as $option)
                    <option value="{{ $option->id }}" @selected($option->is($section))>{{ $option->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <span class="label" id="periodo-label">Periodo</span>
            <nav class="period-tabs" aria-labelledby="periodo-label">
                @foreach ($year->periods as $option)
                    <a href="{{ route('portal.teacher.homeroom', ['seccion' => $section->id, 'periodo' => $option->id]) }}"
                        @if ($option->is($period)) aria-current="page" @endif>{{ $option->number }}@if ($option->isClosed()) <x-icon name="lock" /><span class="sr-only"> (cerrado)</span>@endif</a>
                @endforeach
            </nav>
        </div>
        <noscript><button type="submit" class="btn btn-line btn-sm">Ver</button></noscript>
    </form>

    @error('period')
        <div class="error-summary" role="alert"><p>{{ $message }}</p></div>
    @enderror

    <section class="panel" aria-labelledby="grupo-title">
        <h2 id="grupo-title">{{ $section->label() }} · {{ $period->name() }}</h2>
        @php($locked = $period->isClosed())
        @if ($locked)
            <p class="notice">El {{ mb_strtolower($period->name()) }} está cerrado: solo lectura.</p>
            <p><a class="btn btn-azul btn-sm" href="{{ route('report-cards.section', [$section, $period]) }}">
                <x-icon name="download" /> Boletines del grupo en PDF (para imprimir)</a></p>
        @endif

        @if ($enrollments->isEmpty())
            <p class="empty">No hay estudiantes matriculados.</p>
        @else
            <form method="POST" action="{{ route('portal.teacher.homeroom.save') }}" novalidate>
                @csrf
                @method('PUT')
                <input type="hidden" name="seccion" value="{{ $section->id }}">
                <input type="hidden" name="periodo" value="{{ $period->id }}">
                <ol class="behavior-list">
                    @foreach ($enrollments as $enrollment)
                        @php($report = $reports->get($enrollment->id))
                        <li>
                            <p class="strong">
                                {{ $enrollment->student->sortName() }}
                                @if ($locked)
                                    <a class="small-link" href="{{ route('report-cards.student', [$enrollment->student, $period]) }}" target="_blank" rel="noopener">Boletín</a>
                                @endif
                            </p>
                            <div class="behavior-fields {{ $section->grade->isPreschool() ? 'single' : '' }}">
                                @unless ($section->grade->isPreschool())
                                <div class="field @error("behavior.{$enrollment->id}") has-error @enderror">
                                    <label class="label" for="b-{{ $enrollment->id }}">Comportamiento</label>
                                    <input id="b-{{ $enrollment->id }}" name="behavior[{{ $enrollment->id }}]" type="text" inputmode="decimal" maxlength="4" @disabled($locked)
                                        value="{{ old("behavior.{$enrollment->id}", $report?->behavior !== null ? $scale->format($report->behavior) : '') }}">
                                    @error("behavior.{$enrollment->id}")
                                        <p class="error">{{ $message }}</p>
                                    @enderror
                                </div>
                                @endunless
                                <div class="field @error("observations.{$enrollment->id}") has-error @enderror">
                                    <label class="label" for="o-{{ $enrollment->id }}">Observaciones <span class="opt">(opcional)</span></label>
                                    <textarea id="o-{{ $enrollment->id }}" name="observations[{{ $enrollment->id }}]" rows="2" maxlength="1000" @disabled($locked)>{{ old("observations.{$enrollment->id}", $report?->observations) }}</textarea>
                                    @error("observations.{$enrollment->id}")
                                        <p class="error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ol>
                @unless ($locked)
                    <div class="att-save">
                        <span>{{ $enrollments->count() }} estudiantes</span>
                        <button type="submit" class="btn btn-sol" data-submit data-loading-text="Guardando…"><span>Guardar</span></button>
                    </div>
                @endunless
            </form>
        @endif
    </section>

    @include('portal.partials.alerts', ['alerts' => $alerts, 'showSection' => false])
</x-layouts.portal>
