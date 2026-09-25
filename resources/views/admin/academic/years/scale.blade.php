<x-layouts.admin :title="'Escala de valoración '.$year->year">
    <p class="back"><a href="{{ route('admin.academic.years.edit', $year) }}">← Año lectivo {{ $year->year }}</a></p>
    <div class="admin-head">
        <h1>Escala de valoración {{ $year->year }}</h1>
        <p class="lead-sm">Según el SIEE del colegio. Los valores iniciales son provisionales (ver pendientes).</p>
    </div>

    @include('admin.academic.partials.tabs')

    @php($num = fn ($field) => old($field, str_replace('.', ',', rtrim(rtrim(number_format($scale->{$field}, 2, '.', ''), '0'), '.'))))

    <form class="form admin-form" method="POST" action="{{ route('admin.academic.scale.update', $year) }}" novalidate data-form>
        @csrf
        @method('PUT')

        @error('weights')
            <div class="error-summary" role="alert"><p>{{ $message }}</p></div>
        @enderror

        <div class="admin-grid">
            <section class="panel">
                <h2>Notas</h2>
                <div class="field-group even">
                    <x-field name="min_score" label="Nota mínima">
                        <input id="min_score" name="min_score" type="text" inputmode="decimal" value="{{ $num('min_score') }}">
                    </x-field>
                    <x-field name="max_score" label="Nota máxima">
                        <input id="max_score" name="max_score" type="text" inputmode="decimal" value="{{ $num('max_score') }}">
                    </x-field>
                </div>
                <div class="field-group even">
                    <x-field name="passing_score" label="Nota para aprobar">
                        <input id="passing_score" name="passing_score" type="text" inputmode="decimal" value="{{ $num('passing_score') }}">
                    </x-field>
                    <x-field name="decimals" label="Decimales">
                        <select id="decimals" name="decimals">
                            @foreach ([1, 2] as $d)
                                <option value="{{ $d }}" @selected((int) old('decimals', $scale->decimals) === $d)>{{ $d }} ({{ $d === 1 ? '3,5' : '3,50' }})</option>
                            @endforeach
                        </select>
                    </x-field>
                </div>

                <h3>Pesos de la nota del periodo (%)</h3>
                <div class="field-group three">
                    <x-field name="weight_knowing" label="Saber">
                        <input id="weight_knowing" name="weight_knowing" type="text" inputmode="decimal" value="{{ $num('weight_knowing') }}">
                    </x-field>
                    <x-field name="weight_doing" label="Hacer">
                        <input id="weight_doing" name="weight_doing" type="text" inputmode="decimal" value="{{ $num('weight_doing') }}">
                    </x-field>
                    <x-field name="weight_being" label="Ser">
                        <input id="weight_being" name="weight_being" type="text" inputmode="decimal" value="{{ $num('weight_being') }}">
                    </x-field>
                </div>
                <p class="hint">Deben sumar 100 %.</p>
            </section>

            <section class="panel">
                <h2>Desempeños y frases de los logros</h2>
                <p class="hint">Cada desempeño empieza en la nota indicada (Bajo empieza en la mínima). La frase se antepone al logro en el boletín.</p>
                @foreach (array_reverse($performances) as $performance)
                    <fieldset class="plain-fieldset scale-level">
                        <legend>{{ $performance->label() }}</legend>
                        <div class="field-group">
                            <x-field name="phrases.{{ $performance->value }}" label="Frase">
                                <input id="phrases.{{ $performance->value }}" name="phrases[{{ $performance->value }}]" type="text" maxlength="80"
                                    value="{{ old("phrases.{$performance->value}", $scale->phrase($performance)) }}">
                            </x-field>
                            @if ($performance !== App\Enums\Performance::Low)
                                <x-field name="{{ $performance->value }}_from" label="Desde">
                                    <input id="{{ $performance->value }}_from" name="{{ $performance->value }}_from" type="text" inputmode="decimal" value="{{ $num($performance->value.'_from') }}">
                                </x-field>
                            @else
                                <div class="field"><span class="label">Desde</span><p class="muted">la nota mínima</p></div>
                            @endif
                        </div>
                    </fieldset>
                @endforeach
            </section>
        </div>

        <button type="submit" class="btn btn-azul" data-submit data-loading-text="Guardando…"><span>Guardar escala</span></button>
    </form>
</x-layouts.admin>
