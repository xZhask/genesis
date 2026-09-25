{{-- Aviso para docentes, estudiantes y acudientes hasta la fase 2 --}}
<x-layouts.public title="Portal académico">
    <section class="coming-soon">
        <div class="wrap">
            <h1>Hola, {{ auth()->user()->name }}</h1>
            <p class="lead">
                Tu portal ({{ mb_strtolower(auth()->user()->role->label()) }}) estará disponible muy pronto. Allí podrás consultar
                notas, asistencia y boletines. Te avisaremos cuando esté listo.
            </p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-azul">Cerrar sesión</button>
            </form>
        </div>
    </section>
</x-layouts.public>
