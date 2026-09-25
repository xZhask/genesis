<x-layouts.admin title="Revisar importación">
    <p class="back"><a href="{{ route('admin.people.import.create') }}">← Importar</a></p>
    <div class="admin-head">
        <h1>Revisión de «{{ $fileName }}»</h1>
        <p class="lead-sm">Año lectivo {{ $year->year }}. Todavía no se ha guardado nada.</p>
    </div>

    @if (! $import->isValid())
        <section class="panel" aria-labelledby="errores-title">
            <h2 id="errores-title">{{ trans_choice('Hay :count problema en el archivo|Hay :count problemas en el archivo', count($import->errors)) }}</h2>
            <p class="hint">Corrígelos en Excel, guarda otra vez como CSV y vuelve a subirlo. No se importó ninguna fila.</p>
            <ul class="import-errors">
                @foreach (array_slice($import->errors, 0, 100) as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            @if (count($import->errors) > 100)
                <p class="hint">… y {{ count($import->errors) - 100 }} más.</p>
            @endif
            <a class="btn btn-azul" href="{{ route('admin.people.import.create') }}">Subir el archivo corregido</a>
        </section>
    @else
        <div class="admin-grid detail">
            <section class="panel" aria-labelledby="resumen-title">
                <h2 id="resumen-title">Todo está en orden</h2>
                <dl class="data">
                    <div><dt>Filas</dt><dd>{{ $summary['rows'] }}</dd></div>
                    <div><dt>Estudiantes</dt><dd>{{ trans_choice(':count nuevo|:count nuevos', $summary['students_new']) }} · {{ trans_choice(':count ya registrado|:count ya registrados', $summary['students_existing']) }} (se actualizan)</dd></div>
                    <div><dt>Acudientes</dt><dd>{{ trans_choice(':count nuevo|:count nuevos', $summary['guardians_new']) }} · {{ trans_choice(':count ya registrado|:count ya registrados', $summary['guardians_existing']) }} (se actualizan)</dd></div>
                </dl>

                <div class="actions">
                    <form method="POST" action="{{ route('admin.people.import.store') }}">
                        @csrf
                        <button type="submit" class="btn btn-azul" data-submit data-loading-text="Importando…"><span>Confirmar importación</span></button>
                    </form>
                    <form method="POST" action="{{ route('admin.people.import.discard') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-line">Descartar</button>
                    </form>
                </div>
            </section>

            <section class="panel" aria-labelledby="secciones-title">
                <h2 id="secciones-title">Estudiantes por sección</h2>
                <ul class="count-list">
                    @foreach ($summary['by_section'] as $label => $count)
                        <li><span>{{ $label }}</span> <b>{{ $count }}</b></li>
                    @endforeach
                </ul>
            </section>
        </div>
    @endif
</x-layouts.admin>
