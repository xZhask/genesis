@php($contact = config('school.contact'))

<x-layouts.public title="Solicitud enviada">
    <section class="thanks" aria-labelledby="page-title">
        <div class="wrap narrow">
            <div class="thanks-card">
                <span class="check-badge" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5" /></svg>
                </span>
                <h1 id="page-title">¡Recibimos tu solicitud!</h1>
                <p class="lead">Gracias por pensar en Génesis para tu familia.</p>

                <div class="code">
                    <span>Tu código de solicitud</span>
                    <strong>{{ $code }}</strong>
                    <small>Anótalo o tómale una captura: te servirá si te comunicas con el colegio.</small>
                </div>

                <h2>¿Qué sigue?</h2>
                <ol class="next">
                    <li>Revisamos tu solicitud y te contactamos en los próximos <strong>{{ $responseTime }}</strong>.</li>
                    <li>Agendamos una entrevista familiar y una visita al colegio.</li>
                    <li>Te indicamos los documentos que debes traer según el grado.</li>
                </ol>

                <div class="actions">
                    <a class="btn btn-azul" href="{{ route('home') }}">Volver al inicio</a>
                    <a class="btn btn-line" href="tel:{{ $contact['phone_link'] }}"><x-icon name="phone" /> {{ $contact['phone'] }}</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
