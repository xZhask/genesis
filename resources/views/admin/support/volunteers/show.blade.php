@php($phone = $application->phone)

<x-layouts.admin :title="$application->name">
    <p class="back"><a href="{{ route('admin.volunteers.index') }}">← Voluntarios</a></p>

    <div class="admin-head row">
        <div>
            <h1>{{ $application->name }}</h1>
            <p class="lead-sm">Solicitud de voluntariado · {{ $application->created_at->longDate() }}</p>
        </div>
        <span class="badge badge-lg badge-{{ $application->status->badge() }}">{{ $application->status->label() }}</span>
    </div>

    <div class="admin-grid detail">
        <div class="stack">
            <section class="panel" aria-labelledby="contacto-title">
                <h2 id="contacto-title">Contacto</h2>
                <dl class="data">
                    <div><dt>Celular</dt><dd>{{ $application->formattedPhone() }} @if ($application->phone_has_whatsapp) <small>· WhatsApp</small> @endif</dd></div>
                    <div><dt>Correo</dt><dd>{{ $application->email ?: '—' }}</dd></div>
                </dl>
                <div class="actions">
                    <a class="btn btn-azul btn-sm" href="tel:{{ $phone }}"><x-icon name="phone" /> Llamar</a>
                    @if ($application->phone_has_whatsapp)
                        <a class="btn btn-line btn-sm" href="https://wa.me/{{ ltrim($phone, '+') }}" target="_blank" rel="noopener"><x-icon name="whatsapp" /> WhatsApp</a>
                    @endif
                    @if ($application->email)
                        <a class="btn btn-line btn-sm" href="mailto:{{ $application->email }}?subject={{ rawurlencode('Voluntariado en Génesis') }}"><x-icon name="mail" /> Correo</a>
                    @endif
                </div>
            </section>

            <section class="panel" aria-labelledby="ayuda-title">
                <h2 id="ayuda-title">Cómo quiere ayudar</h2>
                <dl class="data">
                    <div><dt>Áreas</dt><dd>{{ $application->areasLabel() }}</dd></div>
                    <div><dt>Disponibilidad</dt><dd>{{ $application->availability->label() }}</dd></div>
                </dl>
                @if ($application->message)
                    <h3>Mensaje</h3>
                    <p class="quote">{!! nl2br(e($application->message)) !!}</p>
                @endif
            </section>

            <section class="panel muted-panel" aria-labelledby="datos-title">
                <h2 id="datos-title">Autorización de datos</h2>
                <p>Aceptada el {{ $application->privacy_accepted_at->longDate() }} a las {{ $application->privacy_accepted_at->shortTime() }}
                    · versión {{ $application->privacy_policy_version }} · IP {{ $application->ip_address ?? '—' }}</p>
            </section>
        </div>

        <div class="stack">
            <section class="panel" aria-labelledby="seguimiento-title">
                <h2 id="seguimiento-title">Seguimiento</h2>
                <form class="form compact" method="POST" action="{{ route('admin.volunteers.update', $application) }}" novalidate data-form>
                    @csrf
                    @method('PUT')

                    <fieldset class="field choices">
                        <legend class="label">Estado</legend>
                        <div class="pills">
                            @foreach ($statuses as $case)
                                <label class="pill">
                                    <input type="radio" name="status" value="{{ $case->value }}" @checked(old('status', $application->status->value) === $case->value)>
                                    <span>{{ $case->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <x-field name="admin_note" label="Nota interna" optional hint="Solo la ve el equipo del colegio. Ejemplo: «Llamé el martes; ayudará en la jornada de pintura».">
                        <textarea id="admin_note" name="admin_note" rows="4" maxlength="2000">{{ old('admin_note', $application->admin_note) }}</textarea>
                    </x-field>

                    <button type="submit" class="btn btn-azul" data-submit data-loading-text="Guardando…"><span>Guardar</span></button>
                </form>
            </section>
        </div>
    </div>
</x-layouts.admin>
