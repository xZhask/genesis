@php
    $admissions = $school['admissions'];
    $money = fn ($value) => number_format((int) $value, 0, ',', '.');
@endphp

<x-layouts.admin title="Configuración">
    <div class="admin-head">
        <h1>Configuración del sitio</h1>
        <p class="lead-sm">
            Datos que el colegio cambia de vez en cuando. Se ven en la web apenas guardas.
            @if ($lastChange)
                Último cambio: {{ $lastChange->updated_at->longDate() }}{{ $lastChange->editor ? ', por '.$lastChange->editor->name : '' }}.
            @endif
        </p>
    </div>

    <form class="form admin-form" method="POST" action="{{ route('admin.settings.update') }}" novalidate data-form>
        @csrf
        @method('PUT')

        <div class="admin-grid detail">
            <div class="stack">
                <section class="panel" aria-labelledby="admisiones-title">
                    <h2 id="admisiones-title">Admisiones</h2>

                    <div class="field-group">
                        <x-field name="admissions_badge" label="Aviso en la portada" optional hint="Etiqueta sobre el título del inicio. Vacío = no se muestra.">
                            <input id="admissions_badge" name="admissions_badge" type="text" maxlength="60" placeholder="Matrículas {{ $admissions['school_year'] }} abiertas"
                                value="{{ old('admissions_badge', $school['admissions_badge']) }}">
                        </x-field>
                        <x-field name="school_year" label="Año lectivo" hint="Para la pre-inscripción y los costos.">
                            <input id="school_year" name="school_year" type="number" min="2026" max="2100" inputmode="numeric"
                                value="{{ old('school_year', $admissions['school_year']) }}">
                        </x-field>
                    </div>

                    <fieldset class="cost-fields">
                        <legend class="label">Costos</legend>
                        <p class="hint">Escribe solo el número; se muestra como «$ 99.900».</p>
                        @foreach ($admissions['costs'] as $i => $cost)
                            <div class="cost-row">
                                <x-field name="costs.{{ $i }}.label" label="Grupo">
                                    <input id="costs.{{ $i }}.label" name="costs[{{ $i }}][label]" type="text" maxlength="60" value="{{ old("costs.{$i}.label", $cost['label']) }}">
                                </x-field>
                                <x-field name="costs.{{ $i }}.enrollment" label="Matrícula">
                                    <input id="costs.{{ $i }}.enrollment" name="costs[{{ $i }}][enrollment]" type="text" inputmode="numeric" maxlength="12"
                                        value="{{ old("costs.{$i}.enrollment", $money($cost['enrollment'])) }}">
                                </x-field>
                                <x-field name="costs.{{ $i }}.monthly" label="Mensualidad">
                                    <input id="costs.{{ $i }}.monthly" name="costs[{{ $i }}][monthly]" type="text" inputmode="numeric" maxlength="12"
                                        value="{{ old("costs.{$i}.monthly", $money($cost['monthly'])) }}">
                                </x-field>
                            </div>
                        @endforeach
                    </fieldset>

                    <x-field name="requirements" label="Documentos para la matrícula" hint="Uno por línea.">
                        <textarea id="requirements" name="requirements" rows="8">{{ old('requirements', implode("\n", $admissions['requirements'])) }}</textarea>
                    </x-field>

                    <x-field name="response_time" label="Tiempo de respuesta a una solicitud" hint="Se usa en «Te contactamos en los próximos 3 días hábiles».">
                        <input id="response_time" name="response_time" type="text" maxlength="40" value="{{ old('response_time', $admissions['response_time']) }}">
                    </x-field>
                </section>
            </div>

            <div class="stack">
                <section class="panel" aria-labelledby="contacto-title">
                    <h2 id="contacto-title">Atención y contacto</h2>

                    <x-field name="office_hours" label="Horarios de atención" optional hint="Uno por línea. Se muestran en el pie de página. Ejemplo: «Secretaría: lunes a viernes, 7:00 a. m. – 3:00 p. m.».">
                        <textarea id="office_hours" name="office_hours" rows="4">{{ old('office_hours', implode("\n", $school['office_hours'])) }}</textarea>
                    </x-field>

                    <label class="check">
                        <input type="checkbox" name="whatsapp_enabled" value="1" @checked(old('whatsapp_enabled', $school['whatsapp']['enabled']))>
                        <span>El {{ $school['contact']['phone'] }} tiene WhatsApp: mostrar el botón flotante y los enlaces a WhatsApp.</span>
                    </label>
                </section>

                <section class="panel" aria-labelledby="avisos-title">
                    <h2 id="avisos-title">Correos de aviso</h2>
                    <x-field name="admissions_notify_email" label="Recibe las pre-inscripciones">
                        <input id="admissions_notify_email" name="admissions_notify_email" type="email" maxlength="120"
                            value="{{ old('admissions_notify_email', $admissions['notify_email']) }}">
                    </x-field>
                    <x-field name="support_notify_email" label="Recibe los voluntarios">
                        <input id="support_notify_email" name="support_notify_email" type="email" maxlength="120"
                            value="{{ old('support_notify_email', $school['support']['notify_email']) }}">
                    </x-field>
                </section>

                <section class="panel" aria-labelledby="boletin-title">
                    <h2 id="boletin-title">Boletín</h2>
                    <p class="hint">Aparecen en el encabezado y las firmas del boletín en PDF. No se muestran en la web.</p>
                    <x-field name="report_approval" label="Resolución de aprobación" optional>
                        <input id="report_approval" name="report_approval" type="text" maxlength="160"
                            value="{{ old('report_approval', $school['report_card']['approval']) }}">
                    </x-field>
                    <div class="field-group even">
                        <x-field name="report_nit" label="NIT" optional>
                            <input id="report_nit" name="report_nit" type="text" maxlength="30" value="{{ old('report_nit', $school['report_card']['nit']) }}">
                        </x-field>
                        <x-field name="report_campus" label="Sede" optional>
                            <input id="report_campus" name="report_campus" type="text" maxlength="60" value="{{ old('report_campus', $school['report_card']['campus']) }}">
                        </x-field>
                    </div>
                    <x-field name="report_rector" label="Nombre de quien firma como rector o rectora" optional hint="Si se deja vacío, la firma dice solo «Rectoría».">
                        <input id="report_rector" name="report_rector" type="text" maxlength="120" value="{{ old('report_rector', $school['report_card']['rector']) }}" aria-describedby="report_rector-hint">
                    </x-field>
                </section>

                <p class="muted-panel panel">
                    El nombre, la dirección, el teléfono y las declaraciones del colegio (misión, visión, valores) no se editan aquí:
                    son textos oficiales y se cambian con el equipo de desarrollo.
                </p>

                <button type="submit" class="btn btn-azul" data-submit data-loading-text="Guardando…"><span>Guardar configuración</span></button>
            </div>
        </div>
    </form>
</x-layouts.admin>
