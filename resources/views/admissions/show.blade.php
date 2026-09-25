@php
    $contact = config('school.contact');
    $money = fn (int $value) => '$ '.number_format($value, 0, ',', '.');
    $steps = [
        ['Solicitud en línea', 'Llena el formulario de esta página. Te toma unos 3 minutos.'],
        ['Entrevista familiar', "Te contactamos en los próximos {$responseTime} para agendar una visita al colegio y conversar con la familia."],
        ['Entrega de documentos', 'Te indicamos qué documentos traer según el grado.'],
        ['Matrícula', 'Firmas la matrícula y ¡bienvenidos a la familia Génesis!'],
    ];
@endphp

<x-layouts.public title="Admisiones" description="Pre-inscribe a tu hijo o hija en el Centro Educativo Cristiano Génesis: proceso, costos, requisitos y formulario en línea.">
    <x-page-head eyebrow="Año lectivo {{ $schoolYear }}" title="Admisiones"
        lead="Pre-inscribe a tu hijo o hija en pocos minutos. Te contactaremos para agendar la entrevista y conocer el colegio.">
        <a class="btn btn-sol" href="#solicitud"><x-icon name="form" /> Solicitar pre-inscripción</a>
        <a class="btn btn-line" href="tel:{{ $contact['phone_link'] }}"><x-icon name="phone" /> Llamar al colegio</a>
    </x-page-head>

    {{-- Proceso --}}
    <section class="adm-steps" aria-labelledby="proceso-title">
        <div class="wrap">
            <x-section-head id="proceso-title" title="Cómo es el proceso" />
            <ol class="process">
                @foreach ($steps as [$title, $text])
                    <li>
                        <span class="num" aria-hidden="true">{{ $loop->iteration }}</span>
                        <div>
                            <h3>{{ $title }}</h3>
                            <p>{{ $text }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Costos y requisitos --}}
    <section class="adm-info" aria-labelledby="costos-title">
        <div class="wrap adm-info-grid">
            <div>
                <x-section-head id="costos-title" title="Costos {{ $schoolYear }}" />
                <div class="costs">
                    @foreach ($costs as $cost)
                        <article class="cost level-{{ $cost['levels'][0] }}">
                            <h3>{{ $cost['label'] }}</h3>
                            <dl>
                                <div>
                                    <dt>Matrícula <small>(pago anual)</small></dt>
                                    <dd>{{ $money($cost['enrollment']) }}</dd>
                                </div>
                                <div>
                                    <dt>Mensualidad</dt>
                                    <dd>{{ $money($cost['monthly']) }}</dd>
                                </div>
                            </dl>
                        </article>
                    @endforeach
                </div>
                <p class="note">Valores en pesos colombianos. Confírmalos con el colegio al momento de la matrícula.</p>
            </div>

            <div>
                <x-section-head id="requisitos-title" title="Documentos" />
                <div class="requirements">
                    <p>Los traes el día de la matrícula. No los necesitas para la pre-inscripción.</p>
                    <ul>
                        @foreach ($requirements as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Formulario --}}
    <section class="adm-form" id="solicitud" aria-labelledby="solicitud-title">
        <div class="wrap narrow">
            <x-section-head id="solicitud-title" title="Solicitud de pre-inscripción"
                lead="Solo te pedimos lo necesario para contactarte. Los campos sin la marca (opcional) son obligatorios." />
            @include('admissions.form')
        </div>
    </section>
</x-layouts.public>
