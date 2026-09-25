<x-layouts.admin title="Importar estudiantes">
    <div class="admin-head">
        <h1>Personas</h1>
    </div>

    @include('admin.people.partials.tabs')

    @if (! $year)
        <p class="empty panel">Primero <a href="{{ route('admin.academic.years.index') }}">crea el año lectivo actual</a> y sus secciones: la importación matricula a cada estudiante en su sección.</p>
    @else
        <div class="admin-grid detail">
            <section class="panel" aria-labelledby="subir-title">
                <h2 id="subir-title">Importar estudiantes y acudientes · {{ $year->year }}</h2>
                <ol class="steps-list">
                    <li><a href="{{ route('admin.people.import.template') }}">Descarga la plantilla</a> y ábrela en Excel.</li>
                    <li>Escribe una fila por estudiante. Si tiene dos acudientes, repite al estudiante en otra fila con el segundo acudiente.</li>
                    <li>Guárdala como <strong>CSV</strong> (Archivo → Guardar como → CSV UTF-8) y súbela aquí.</li>
                    <li>Revisa el resumen: nada se guarda hasta que confirmes.</li>
                </ol>

                <form class="form compact" method="POST" action="{{ route('admin.people.import.preview') }}" enctype="multipart/form-data" novalidate data-form>
                    @csrf
                    <x-field name="file" label="Archivo CSV">
                        <input id="file" name="file" type="file" accept=".csv,text/csv">
                    </x-field>
                    <button type="submit" class="btn btn-azul" data-submit data-loading-text="Revisando…"><span>Revisar archivo</span></button>
                </form>
            </section>

            <section class="panel" aria-labelledby="columnas-title">
                <h2 id="columnas-title">Columnas</h2>
                <dl class="data columns-help">
                    <div><dt>documento, nombres, apellidos, grado</dt><dd>Obligatorias. Grado como aparece en el colegio: «Párvulos», «Transición», «3», «3.°» o «tercero».</dd></div>
                    <div><dt>seccion</dt><dd>Si se deja vacía, va a la sección 1. La sección debe existir en {{ $year->year }}.</dd></div>
                    <div><dt>tipo_documento</dt><dd>RC, TI, CC, CE, PPT o PA. Vacío: RC en preescolar y TI desde 1.°.</dd></div>
                    <div><dt>fecha_nacimiento</dt><dd>Opcional, como 14/03/2015.</dd></div>
                    <div><dt>acudiente_documento, acudiente_nombres, acudiente_apellidos</dt><dd>Si el acudiente ya está registrado, basta con el documento. Vacío el tipo: CC.</dd></div>
                    <div><dt>parentesco</dt><dd>Madre, padre, abuela, tío, hermana u otro.</dd></div>
                    <div><dt>acudiente_telefono, acudiente_correo</dt><dd>Opcionales.</dd></div>
                    <div><dt>acudiente_principal</dt><dd>«sí» para el acudiente a quien el colegio llama primero. Si nadie lo tiene, es el primero de la lista.</dd></div>
                </dl>
                <p class="hint">Si un estudiante o acudiente ya existe (mismo documento), se actualizan sus datos; no se duplica. Las cuentas del portal se crean después, desde Acudientes.</p>
            </section>
        </div>
    @endif
</x-layouts.admin>
