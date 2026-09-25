<x-layouts.auth title="Crear contraseña nueva">
    <h1>Crear contraseña nueva</h1>
    <p class="auth-lead">Usa al menos 8 caracteres, con letras y números.</p>

    <form class="form" method="POST" action="{{ route('password.update') }}" novalidate data-form>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-field name="email" label="Correo">
            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" autocomplete="username" required
                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
        </x-field>

        <x-field name="password" label="Contraseña nueva">
            <input id="password" name="password" type="password" autocomplete="new-password" autofocus required
                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
        </x-field>

        <x-field name="password_confirmation" label="Repite la contraseña">
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        </x-field>

        <button type="submit" class="btn btn-sol btn-block" data-submit data-loading-text="Guardando…">
            <span>Guardar contraseña</span>
        </button>
    </form>
</x-layouts.auth>
