<x-layouts.auth title="Ingresar al portal">
    <h1>Ingresar al portal</h1>
    <p class="auth-lead">Para acudientes, estudiantes, docentes y administración del colegio.</p>

    @if (session('status'))
        <div class="flash" role="status">{{ session('status') }}</div>
    @endif

    <form class="form" method="POST" action="{{ route('login.store') }}" novalidate data-form>
        @csrf

        <x-field name="login" label="Número de documento" hint="Tu cédula o tarjeta de identidad. El personal del colegio también puede usar su correo.">
            <input id="login" name="login" type="text" value="{{ old('login') }}" autocomplete="username" autocapitalize="off" spellcheck="false"
                autofocus required aria-describedby="login-hint @error('login') login-error @enderror" @error('login') aria-invalid="true" @enderror>
        </x-field>

        <x-field name="password" label="Contraseña">
            <input id="password" name="password" type="password" autocomplete="current-password" required
                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
        </x-field>

        <div class="auth-row">
            <label class="check">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                <span>Recordarme en este dispositivo</span>
            </label>
            <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
        </div>

        <button type="submit" class="btn btn-sol btn-block" data-submit data-loading-text="Ingresando…">
            <x-icon name="lock" /> <span>Ingresar</span>
        </button>
    </form>

    <p class="auth-help">¿No tienes cuenta? El colegio la crea por ti. Escríbenos a
        <a href="mailto:{{ config('school.contact.email') }}">{{ config('school.contact.email') }}</a>.</p>
</x-layouts.auth>
