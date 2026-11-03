<x-layouts.auth title="Felicitaciones por correo">
    @if ($done)
        <h1>Listo</h1>
        <p class="auth-lead">No te enviaremos más felicitaciones de cumpleaños por correo. Si cambias de opinión, avísale al colegio.</p>
        <p class="auth-help"><a href="{{ route('home') }}">Ir a la página del colegio</a></p>
    @else
        <h1>¿Dejar de recibir felicitaciones?</h1>
        <p class="auth-lead">Si confirmas, el colegio no te enviará más la tarjeta de cumpleaños por correo.</p>
        <form class="form" method="POST" action="{{ request()->fullUrl() }}">
            @csrf
            <button type="submit" class="btn btn-sol btn-block">Sí, dejar de recibirlas</button>
        </form>
        <p class="auth-help"><a href="{{ route('home') }}">No, seguir recibiéndolas</a></p>
    @endif
</x-layouts.auth>
