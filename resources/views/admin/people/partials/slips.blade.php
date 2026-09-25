{{-- Hojas de acceso con la contraseña temporal. Se muestran una sola vez. --}}
<section class="slips" aria-labelledby="slips-title">
    <div class="slips-head no-print">
        <div>
            <h2 id="slips-title">{{ count($slips) === 1 ? 'Datos de ingreso' : 'Datos de ingreso ('.count($slips).')' }}</h2>
            <p class="hint">Imprímelos o díctalos ahora: la contraseña temporal no se vuelve a mostrar. Si se pierde, genera otra.</p>
        </div>
        <button type="button" class="btn btn-azul btn-sm" data-print>Imprimir</button>
    </div>

    <div class="slip-list">
        @foreach ($slips as $slip)
            <article class="slip">
                <p class="slip-school">Centro Educativo Cristiano Génesis · Portal</p>
                <h3>{{ $slip['name'] }} <small>{{ $slip['role'] }}</small></h3>
                <dl>
                    <div><dt>Ingresa en</dt><dd>{{ route('login') }}</dd></div>
                    <div><dt>{{ str_contains($slip['login'], '@') ? 'Correo' : 'Documento' }}</dt><dd class="mono">{{ $slip['login'] }}</dd></div>
                    <div><dt>Contraseña temporal</dt><dd class="mono slip-pass">{{ $slip['password'] }}</dd></div>
                </dl>
                <p class="slip-note">Al ingresar por primera vez te pediremos crear una contraseña propia.</p>
            </article>
        @endforeach
    </div>
</section>
