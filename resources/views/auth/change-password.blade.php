<x-layouts.auth title="Crea tu contraseña">
    @if ($forced)
        <h1>Crea tu contraseña</h1>
        <p class="auth-lead">Hola, {{ auth()->user()->name }}. Ingresaste con una contraseña temporal del colegio: elige una propia para seguir.
            Usa al menos 8 caracteres, con letras y números.</p>
    @else
        <h1>Cambiar contraseña</h1>
        <p class="auth-lead">Usa al menos 8 caracteres, con letras y números.</p>
    @endif

    <form class="form" method="POST" action="{{ route('password.change.update') }}" novalidate data-form>
        @csrf
        @method('PUT')
        {{-- Para que el administrador de contraseñas del teléfono la guarde con el documento --}}
        <input type="text" name="username" value="{{ auth()->user()->document_number ?? auth()->user()->email }}" autocomplete="username" hidden>

        @unless ($forced)
            <x-field name="current_password" label="Contraseña actual">
                <input id="current_password" name="current_password" type="password" autocomplete="current-password" autofocus required
                    @error('current_password') aria-invalid="true" aria-describedby="current_password-error" @enderror>
            </x-field>
        @endunless

        <x-field name="password" label="Contraseña nueva">
            <input id="password" name="password" type="password" autocomplete="new-password" required @if ($forced) autofocus @endif
                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
        </x-field>

        <x-field name="password_confirmation" label="Repite la contraseña nueva">
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        </x-field>

        <button type="submit" class="btn btn-sol btn-block" data-submit data-loading-text="Guardando…">
            <x-icon name="lock" /> <span>Guardar contraseña</span>
        </button>
    </form>

    @if ($forced)
        <form method="POST" action="{{ route('logout') }}" class="auth-help">
            @csrf
            <button type="submit" class="link-button">Salir sin cambiarla</button>
        </form>
    @else
        <p class="auth-help"><a href="{{ auth()->user()->homeUrl() }}">← Volver</a></p>
    @endif
</x-layouts.auth>
