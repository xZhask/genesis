<x-layouts.auth title="Avisos por correo">
    @if ($done)
        <h1>Listo</h1>
        <p class="auth-lead">No te enviaremos más avisos por correo (eventos, boletines y circulares).
            Los seguirás viendo en el portal, y puedes volver a activarlos en «Mis datos».</p>
        <p class="auth-help"><a href="{{ route('home') }}">Ir a la página del colegio</a></p>
    @else
        <h1>¿Dejar de recibir avisos?</h1>
        <p class="auth-lead">Hola, {{ $guardian->first_names }}. Si confirmas, el colegio no te enviará más avisos por correo:
            recordatorios de eventos, boletines disponibles y circulares. Te seguirán llegando los avisos de seguridad de tu cuenta y la respuesta a tus solicitudes de cambio de datos.</p>
        <form class="form" method="POST" action="{{ request()->fullUrl() }}">
            @csrf
            <button type="submit" class="btn btn-sol btn-block">Sí, dejar de recibirlos</button>
        </form>
        <p class="auth-help"><a href="{{ route('home') }}">No, seguir recibiéndolos</a></p>
    @endif
</x-layouts.auth>
