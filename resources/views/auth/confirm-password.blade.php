<x-layouts.auth title="Confirma tu contraseña">
    <h1>Confirma tu contraseña</h1>
    <p class="auth-lead">Por seguridad, escribe tu contraseña para continuar.</p>

    <form class="form" method="POST" action="{{ route('password.confirm.store') }}" novalidate data-form>
        @csrf

        <x-field name="password" label="Contraseña">
            <input id="password" name="password" type="password" autocomplete="current-password" autofocus required
                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
        </x-field>

        <button type="submit" class="btn btn-sol btn-block" data-submit><span>Confirmar</span></button>
    </form>
</x-layouts.auth>
