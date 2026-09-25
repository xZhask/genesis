{{--
    Cuenta del portal de una persona.
    $user: cuenta (o null); $createRoute: ruta para crearla (o null si no aplica); $cannot: motivo si no puede tener cuenta.
--}}
<section class="panel" aria-labelledby="cuenta-title">
    <h2 id="cuenta-title">Cuenta del portal</h2>

    @if ($user)
        <dl class="data">
            <div><dt>Ingresa con</dt><dd class="mono">{{ $user->document_number ?? $user->email }}</dd></div>
            <div>
                <dt>Estado</dt>
                <dd>
                    @if (! $user->is_active)
                        <span class="badge badge-withdrawn">Desactivada</span>
                    @elseif ($user->must_change_password)
                        <span class="badge badge-in_review">Contraseña temporal</span>
                    @else
                        <span class="badge badge-accepted">Activa</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt>Último ingreso</dt>
                <dd>{{ $user->last_login_at ? $user->last_login_at->longDate().', '.$user->last_login_at->shortTime() : 'Nunca ha ingresado' }}</dd>
            </div>
            @if ($user->role !== ($role ?? $user->role))
                <div><dt>Cuenta de</dt><dd>{{ $user->role->label() }} (la misma persona)</dd></div>
            @endif
        </dl>

        @unless ($user->is(auth()->user()))
            <div class="actions">
                <form method="POST" action="{{ route('admin.people.accounts.reset', $user) }}"
                    data-confirm="¿Generar una contraseña temporal nueva para {{ $user->name }}? La actual dejará de funcionar.">
                    @csrf
                    <button type="submit" class="btn btn-line btn-sm">Generar contraseña temporal</button>
                </form>
                <form method="POST" action="{{ route('admin.people.accounts.toggle', $user) }}"
                    @if ($user->is_active) data-confirm="¿Desactivar la cuenta de {{ $user->name }}? No podrá ingresar hasta que la actives." @endif>
                    @csrf
                    <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-danger' : 'btn-line' }}">{{ $user->is_active ? 'Desactivar' : 'Activar' }}</button>
                </form>
            </div>
        @endunless
    @elseif ($createRoute)
        <p class="hint">Todavía no tiene cuenta. Se crea con su número de documento y una contraseña temporal para imprimir o dictar.</p>
        <form method="POST" action="{{ $createRoute }}">
            @csrf
            <button type="submit" class="btn btn-azul btn-sm">Crear cuenta</button>
        </form>
    @else
        <p class="hint">{{ $cannot }}</p>
    @endif
</section>
