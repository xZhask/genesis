@use('App\Enums\AdmissionStatus')
@php($phone = $admission->guardian_phone)

<x-layouts.admin :title="$admission->code">
    <p class="back"><a href="{{ route('admin.admissions.index') }}">← Solicitudes</a></p>

    <div class="admin-head row">
        <div>
            <h1>{{ $admission->studentFullName() }}</h1>
            <p class="lead-sm">{{ $admission->code }} · {{ $admission->grade }} · Año lectivo {{ $admission->school_year }}</p>
        </div>
        <x-status-badge :status="$admission->status" class="badge-lg" />
    </div>

    @if ($admission->status === AdmissionStatus::InterviewScheduled && $admission->interview_at)
        <p class="notice">Entrevista: <strong>{{ $admission->interview_at->longDate() }}, {{ $admission->interview_at->shortTime() }}</strong></p>
    @endif

    <div class="admin-grid detail">
        <div class="stack">
            <section class="panel" aria-labelledby="acudiente-title">
                <h2 id="acudiente-title">Acudiente</h2>
                <dl class="data">
                    <div><dt>Nombre</dt><dd>{{ $admission->guardian_name }}</dd></div>
                    <div><dt>Parentesco</dt><dd>{{ $admission->guardian_relationship->label() }}</dd></div>
                    <div><dt>Celular</dt><dd>{{ $admission->formattedPhone() }} @if ($admission->phone_has_whatsapp) <small>· WhatsApp</small> @endif</dd></div>
                    <div><dt>Correo</dt><dd>{{ $admission->guardian_email ?: '—' }}</dd></div>
                </dl>
                <div class="actions">
                    <a class="btn btn-azul btn-sm" href="tel:{{ $phone }}"><x-icon name="phone" /> Llamar</a>
                    @if ($admission->phone_has_whatsapp)
                        <a class="btn btn-line btn-sm" href="https://wa.me/{{ ltrim($phone, '+') }}" target="_blank" rel="noopener"><x-icon name="whatsapp" /> WhatsApp</a>
                    @endif
                    @if ($admission->guardian_email)
                        <a class="btn btn-line btn-sm" href="mailto:{{ $admission->guardian_email }}?subject={{ rawurlencode('Pre-inscripción '.$admission->code) }}"><x-icon name="mail" /> Correo</a>
                    @endif
                </div>
            </section>

            <section class="panel" aria-labelledby="estudiante-title">
                <h2 id="estudiante-title">Estudiante</h2>
                <dl class="data">
                    <div><dt>Nombres</dt><dd>{{ $admission->student_first_names }}</dd></div>
                    <div><dt>Apellidos</dt><dd>{{ $admission->student_last_names }}</dd></div>
                    <div><dt>Nacimiento</dt><dd>{{ $admission->student_birth_date->longDate() }} <small>({{ $admission->student_birth_date->age }} años)</small></dd></div>
                    <div><dt>Grado al que aspira</dt><dd>{{ $admission->grade }} ({{ $admission->school_year }})</dd></div>
                    <div><dt>Colegio actual</dt><dd>{{ $admission->current_school ?: '—' }}</dd></div>
                </dl>
                @if ($admission->comments)
                    <h3>Comentarios de la familia</h3>
                    <p class="quote">{!! nl2br(e($admission->comments)) !!}</p>
                @endif
            </section>

            <section class="panel muted-panel" aria-labelledby="datos-title">
                <h2 id="datos-title">Autorización de datos</h2>
                <p>Aceptada el {{ $admission->privacy_accepted_at->longDate() }} a las {{ $admission->privacy_accepted_at->shortTime() }}
                    · versión {{ $admission->privacy_policy_version }} · IP {{ $admission->ip_address ?? '—' }}</p>
            </section>
        </div>

        <div class="stack">
            <section class="panel" aria-labelledby="estado-title">
                <h2 id="estado-title">Cambiar estado</h2>
                <form class="form compact" method="POST" action="{{ route('admin.admissions.status', $admission) }}" novalidate data-form data-status-form>
                    @csrf
                    @method('PUT')

                    <x-field name="status" label="Nuevo estado">
                        <select id="status" name="status" data-status-select @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
                            <option value="">Elige un estado</option>
                            @foreach ($statuses as $case)
                                @continue($case === $admission->status)
                                <option value="{{ $case->value }}" @selected(old('status') === $case->value)>{{ $case->label() }}</option>
                            @endforeach
                        </select>
                    </x-field>

                    <x-field name="interview_at" label="Fecha y hora de la entrevista" data-interview-field>
                        <input id="interview_at" name="interview_at" type="datetime-local" value="{{ old('interview_at') }}"
                            @error('interview_at') aria-invalid="true" aria-describedby="interview_at-error" @enderror>
                    </x-field>

                    <x-field name="note" label="Nota interna" optional>
                        <textarea id="note" name="note" rows="3" maxlength="1000">{{ old('note') }}</textarea>
                    </x-field>

                    <button type="submit" class="btn btn-azul" data-submit data-loading-text="Guardando…"><span>Guardar cambio</span></button>
                </form>
            </section>

            <section class="panel" aria-labelledby="historial-title">
                <h2 id="historial-title">Historial</h2>
                <ol class="timeline">
                    @foreach ($admission->statusChanges as $change)
                        <li>
                            <p>
                                <x-status-badge :status="$change->to_status" />
                                <small>desde {{ $change->from_status->label() }}</small>
                            </p>
                            @if ($change->interview_at)
                                <p>Entrevista: {{ $change->interview_at->longDate() }}, {{ $change->interview_at->shortTime() }}</p>
                            @endif
                            @if ($change->note)
                                <p class="quote">{{ $change->note }}</p>
                            @endif
                            <small>{{ $change->author?->name ?? 'Usuario eliminado' }} · {{ $change->created_at->longDate() }}, {{ $change->created_at->shortTime() }}</small>
                        </li>
                    @endforeach
                    <li>
                        <p><x-status-badge :status="AdmissionStatus::Received" /></p>
                        <small>Enviada desde la web · {{ $admission->created_at->longDate() }}, {{ $admission->created_at->shortTime() }}</small>
                    </li>
                </ol>
            </section>
        </div>
    </div>
</x-layouts.admin>
