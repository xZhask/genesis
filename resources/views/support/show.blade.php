@php
    $describedBy = fn (string $name, bool $hint = false) => collect([
        $hint ? "{$name}-hint" : null,
        $errors->has($name) ? "{$name}-error" : null,
    ])->filter()->join(' ');
    $invalid = fn (string $name) => $errors->has($name) ? 'aria-invalid=true' : '';
    $oldAreas = old('areas', []);
    $mailDonation = 'mailto:'.$contact['email'].'?subject='.rawurlencode('Quiero hacer una donación');
@endphp

<x-layouts.public title="Apóyanos" description="Dona o súmate como voluntario al Centro Educativo Cristiano Génesis en Zambrano, Bolívar.">
    <x-page-head eyebrow="Apóyanos" title="Juntos hacemos más por los niños"
        lead="Tu donación o tu tiempo se convierten en materiales, mejores espacios y nuevas oportunidades para nuestros estudiantes.">
        <a class="btn btn-sol" href="#donar">Hacer una donación</a>
        <a class="btn btn-line" href="#voluntariado">Ser voluntario</a>
    </x-page-head>

    {{-- Donaciones --}}
    <section class="support-page donate" id="donar" aria-labelledby="donar-title">
        <div class="wrap donate-grid">
            <div class="donate-intro">
                <h2 id="donar-title">Haz una donación</h2>
                @if ($accounts->isNotEmpty())
                    <p>Transfiere desde la app de tu banco a cualquiera de estas cuentas. Toca <strong>Copiar</strong> y pega el número.</p>
                @else
                    <p>Escríbenos y te contamos cómo puedes aportar al colegio.</p>
                @endif
            </div>

            <div class="give">
                @if ($accounts->isNotEmpty())
                    <ul class="acct">
                        @foreach ($accounts as $account)
                            <li class="row">
                                <div>
                                    <small>{{ $account->label }}</small>
                                    <b>{{ $account->number }}</b>
                                    @if ($account->holderLine())
                                        <small class="holder">{{ $account->holderLine() }}</small>
                                    @endif
                                </div>
                                <button type="button" class="copy" data-copy="{{ $account->number }}">
                                    Copiar<span class="sr-only"> número de {{ $account->label }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <h3>Escríbenos</h3>
                    <p class="contact-links">
                        <a href="{{ $mailDonation }}"><x-icon name="mail" /> {{ $contact['email'] }}</a>
                        <a href="tel:{{ $contact['phone_link'] }}"><x-icon name="phone" /> {{ $contact['phone'] }}</a>
                    </p>
                @endif
            </div>

            {{-- Después de las cuentas en el celular; bajo la introducción en pantallas anchas --}}
            @if ($accounts->isNotEmpty())
                <div class="donate-after">
                    <h3>¿Ya donaste?</h3>
                    <p>Cuéntanos para darte las gracias y confirmar que recibimos tu aporte.</p>
                    <p class="contact-links">
                        @if ($whatsapp['enabled'])
                            <a href="https://wa.me/{{ $whatsapp['number'] }}?text={{ rawurlencode('Hola, hice una donación al colegio.') }}" target="_blank" rel="noopener"><x-icon name="whatsapp" /> WhatsApp</a>
                        @endif
                        <a href="{{ $mailDonation }}"><x-icon name="mail" /> {{ $contact['email'] }}</a>
                    </p>
                </div>
            @endif
        </div>
    </section>

    {{-- Voluntariado --}}
    <section class="support-page volunteer-section" id="voluntariado" aria-labelledby="voluntariado-title">
        <div class="wrap volunteer-grid">
            <div class="volunteer-intro">
                <h2 id="voluntariado-title">Súmate como voluntario</h2>
                <p class="lead">No necesitas experiencia, solo ganas de ayudar. Cuéntanos cómo te gustaría participar y te llamamos.</p>

                <ul class="ways">
                    @foreach ($areas as $area)
                        @continue(! $area->description())
                        <li>
                            <span class="ico"><x-icon :name="$area->icon()" /></span>
                            <div><strong>{{ $area->label() }}</strong><span>{{ $area->description() }}</span></div>
                        </li>
                    @endforeach
                </ul>

                @foreach ($testimonials as $testimonial)
                    <blockquote class="testimonial">
                        <p>“{{ $testimonial->quote }}”</p>
                        <cite>{{ $testimonial->signature() }}</cite>
                    </blockquote>
                @endforeach
            </div>

            <div class="volunteer-form">
                @if (session('volunteer_name'))
                    <div class="sent" role="status" tabindex="-1" data-sent>
                        <h3>¡Gracias, {{ session('volunteer_name') }}!</h3>
                        <p>Recibimos tus datos. Pronto te llamaremos para contarte cómo puedes ayudar.</p>
                    </div>
                @else
                    <form class="form" method="POST" action="{{ route('support.volunteer') }}" novalidate data-form>
                        @csrf

                        @if ($errors->any())
                            <div class="error-summary" role="alert" tabindex="-1" data-error-summary>
                                <h3>Revisa {{ $errors->count() === 1 ? 'este dato' : 'estos '.$errors->count().' datos' }} para enviar</h3>
                                <ul>
                                    @foreach ($errors->keys() as $field)
                                        <li><a href="#{{ explode('.', $field)[0] }}">{{ $errors->first($field) }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <x-field name="name" label="Tu nombre">
                            <input id="name" name="name" type="text" value="{{ old('name') }}" maxlength="120" autocomplete="name"
                                aria-describedby="{{ $describedBy('name') }}" {{ $invalid('name') }}>
                        </x-field>

                        <x-field name="phone" label="Celular" hint="Ejemplo: 321 797 5579">
                            <input id="phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" maxlength="20" autocomplete="tel-national"
                                aria-describedby="{{ $describedBy('phone', true) }}" {{ $invalid('phone') }}>
                            <label class="check">
                                <input type="checkbox" name="phone_has_whatsapp" value="1" @checked(old('phone_has_whatsapp', true))>
                                <span>Este número tiene WhatsApp</span>
                            </label>
                        </x-field>

                        <x-field name="email" label="Correo" optional>
                            <input id="email" name="email" type="email" inputmode="email" value="{{ old('email') }}" maxlength="120" autocomplete="email"
                                aria-describedby="{{ $describedBy('email') }}" {{ $invalid('email') }}>
                        </x-field>

                        <fieldset @class(['field', 'choices', 'has-error' => $errors->has('areas') || $errors->has('areas.*')])>
                            <legend class="label">¿Cómo te gustaría ayudar? <span class="opt">(elige una o varias)</span></legend>
                            <div class="pills" id="areas" tabindex="-1">
                                @foreach ($areas as $area)
                                    <label class="pill">
                                        <input type="checkbox" name="areas[]" value="{{ $area->value }}" @checked(in_array($area->value, $oldAreas, true))>
                                        <span>{{ $area->label() }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @if ($errors->has('areas') || $errors->has('areas.*'))
                                <p class="error" id="areas-error">{{ $errors->first('areas') ?: $errors->first('areas.*') }}</p>
                            @endif
                        </fieldset>

                        <fieldset @class(['field', 'choices', 'has-error' => $errors->has('availability')])>
                            <legend class="label">¿Cuándo puedes?</legend>
                            <div class="pills" id="availability" tabindex="-1">
                                @foreach ($availabilities as $availability)
                                    <label class="pill">
                                        <input type="radio" name="availability" value="{{ $availability->value }}" @checked(old('availability') === $availability->value)>
                                        <span>{{ $availability->label() }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('availability')
                                <p class="error" id="availability-error">{{ $message }}</p>
                            @enderror
                        </fieldset>

                        <x-field name="message" label="¿Algo más que quieras contarnos?" optional hint="Por ejemplo, tu oficio o una idea que tengas.">
                            <textarea id="message" name="message" rows="3" maxlength="1000"
                                aria-describedby="{{ $describedBy('message', true) }}" {{ $invalid('message') }}>{{ old('message') }}</textarea>
                        </x-field>

                        {{-- Campo trampa: oculto para las personas, los robots lo llenan --}}
                        <div class="hp" aria-hidden="true">
                            <label for="website">No llenes este campo</label>
                            <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                        </div>

                        <div @class(['field', 'consent', 'has-error' => $errors->has('privacy')])>
                            <label class="check">
                                <input id="privacy" type="checkbox" name="privacy" value="1" @checked(old('privacy'))
                                    aria-describedby="{{ $describedBy('privacy') }}" {{ $invalid('privacy') }}>
                                <span>
                                    Autorizo al {{ config('school.name') }} a tratar mis datos para contactarme sobre el voluntariado,
                                    según la <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Política de tratamiento de datos</a>.
                                </span>
                            </label>
                            @error('privacy')
                                <p class="error" id="privacy-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-sol btn-block" data-submit data-loading-text="Enviando…">
                            <span>Quiero ser voluntario</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </section>

    {{-- Donantes y aliados --}}
    @if ($donors->isNotEmpty())
        <section class="support-page allies" id="aliados" aria-labelledby="aliados-title">
            <div class="wrap">
                <x-section-head id="aliados-title" title="Gracias a quienes creen en nuestra misión"
                    lead="Personas, familias y organizaciones que ya aportan al colegio." />
                <ul class="ally-list">
                    @foreach ($donors as $donor)
                        <li>
                            @if ($donor->website)
                                <a href="{{ $donor->website }}" target="_blank" rel="noopener nofollow"><strong>{{ $donor->name }}</strong></a>
                            @else
                                <strong>{{ $donor->name }}</strong>
                            @endif
                            @if ($donor->description)
                                <span>{{ $donor->description }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
</x-layouts.public>
