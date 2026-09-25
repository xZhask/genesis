{{-- Página temporal: se reemplaza cuando se construye cada sección --}}
@php($contact = config('school.contact'))

<x-layouts.public :title="$title">
    <section class="coming-soon">
        <div class="wrap">
            <h1>{{ $title }}</h1>
            <p class="lead">
                Estamos preparando esta sección. Mientras tanto, escríbenos a
                <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
                o llámanos al <a href="tel:{{ $contact['phone_link'] }}">{{ $contact['phone'] }}</a>.
            </p>
            <a class="btn btn-azul" href="{{ route('home') }}">Volver al inicio</a>
        </div>
    </section>
</x-layouts.public>
