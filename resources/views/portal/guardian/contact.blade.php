@use('App\Enums\ContactField')
@use('App\Enums\ContactRequestStatus')

<x-layouts.portal title="Mis datos">
    <div class="admin-head">
        <h1>Mis datos de contacto</h1>
        <p class="lead-sm">Con estos datos el colegio se comunica contigo. El correo también sirve para recuperar tu contraseña.</p>
    </div>

    <div class="admin-grid detail">
        <section class="panel" aria-labelledby="actuales-title">
            <h2 id="actuales-title">Datos registrados</h2>
            <dl class="contact-data">
                @foreach (ContactField::cases() as $field)
                    @php
                        $last = $latest[$field->value] ?? null;
                    @endphp
                    <div>
                        <dt>{{ $field->label() }}</dt>
                        <dd>
                            <strong>{{ $guardian->{$field->value} ?: 'Sin registrar' }}</strong>
                            @if ($last?->status === ContactRequestStatus::Pending)
                                <p class="contact-status">
                                    <span class="badge badge-{{ $last->status->badge() }}">{{ $last->status->label() }}</span>
                                    Pediste cambiarlo por <b>{{ $last->new_value }}</b> el {{ $last->created_at->longDate() }}.
                                </p>
                            @elseif ($last?->status === ContactRequestStatus::Rejected)
                                <p class="contact-status">
                                    <span class="badge badge-{{ $last->status->badge() }}">{{ $last->status->label() }}</span>
                                    El cambio a <b>{{ $last->new_value }}</b> no se aprobó.@if ($last->note) Motivo: {{ $last->note }}@endif
                                </p>
                            @elseif ($last?->status === ContactRequestStatus::Approved && $last->reviewed_at->gt(now()->subDays(30)))
                                <p class="contact-status">
                                    <span class="badge badge-{{ $last->status->badge() }}">Actualizado</span>
                                    El colegio aprobó el cambio el {{ $last->reviewed_at->longDate() }}.
                                </p>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
            <p class="muted">Para cambiar tu nombre o tu documento, comunícate con el colegio:
                <a href="tel:{{ config('school.contact.phone_link') }}">{{ config('school.contact.phone') }}</a>.</p>
        </section>

        <section class="panel" aria-labelledby="cambio-title">
            <h2 id="cambio-title">Solicitar un cambio</h2>
            <p class="muted">Escribe el dato nuevo. El colegio revisa la solicitud antes de actualizarlo.</p>
            <form class="form compact" method="POST" action="{{ route('portal.guardian.contact.store') }}" novalidate data-form>
                @csrf
                @php
                    // Si hay una solicitud en revisión, el formulario muestra lo pedido
                    $value = fn (ContactField $field) => old($field->value, ($latest[$field->value] ?? null)?->isPending()
                        ? $latest[$field->value]->new_value
                        : $guardian->{$field->value});
                @endphp
                <x-field name="phone" label="Teléfono" hint="Celular o fijo, por ejemplo 321 797 5579.">
                    <input id="phone" name="phone" type="tel" inputmode="tel" maxlength="30" autocomplete="tel"
                        value="{{ $value(ContactField::Phone) }}" aria-describedby="phone-hint @error('phone') phone-error @enderror"
                        @error('phone') aria-invalid="true" @enderror>
                </x-field>
                <x-field name="email" label="Correo">
                    <input id="email" name="email" type="email" inputmode="email" maxlength="255" autocomplete="email"
                        value="{{ $value(ContactField::Email) }}"
                        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                </x-field>
                <button type="submit" class="btn btn-azul" data-submit data-loading-text="Enviando…"><span>Enviar solicitud</span></button>
            </form>
        </section>
    </div>
</x-layouts.portal>
