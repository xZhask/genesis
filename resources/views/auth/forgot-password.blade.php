<x-layouts.auth title="Recuperar contraseña">
    <h1>Recuperar contraseña</h1>
    <p class="auth-lead">Escribe el correo de tu cuenta y te enviaremos un enlace para crear una contraseña nueva.</p>

    @if (session('status'))
        <div class="flash" role="status">{{ session('status') }}</div>
    @endif

    <form class="form" method="POST" action="{{ route('password.email') }}" novalidate data-form>
        @csrf

        <x-field name="email" label="Correo">
            <input id="email" name="email" type="email" inputmode="email" value="{{ old('email') }}" autocomplete="email"
                autofocus required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
        </x-field>

        <button type="submit" class="btn btn-sol btn-block" data-submit data-loading-text="Enviando…">
            <x-icon name="mail" /> <span>Enviar enlace</span>
        </button>
    </form>

    <p class="auth-help"><a href="{{ route('login') }}">← Volver a ingresar</a></p>
</x-layouts.auth>
