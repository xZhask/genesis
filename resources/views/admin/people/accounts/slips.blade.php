<x-layouts.admin :title="$title">
    <div class="admin-head no-print">
        <h1>{{ $title }}</h1>
        <p class="lead-sm">{{ trans_choice('Se creó :count cuenta.|Se crearon :count cuentas.', count($slips)) }}
            Imprime esta hoja ahora, recorta cada ficha y entrégala a su dueño: las contraseñas temporales no se vuelven a mostrar.</p>
    </div>

    @if ($skipped)
        <div class="error-summary no-print" role="alert">
            <p>No se pudo crear la cuenta de:</p>
            <ul>
                @foreach ($skipped as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($slips)
        @include('admin.people.partials.slips', ['slips' => $slips])
    @endif

    <p class="no-print"><a class="btn-link" href="{{ route('admin.people.guardians.index') }}">← Volver a Personas</a></p>
</x-layouts.admin>
