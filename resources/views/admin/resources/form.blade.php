@use('App\Enums\ResourceType')
@use('App\Enums\PublicationStatus')
@use('App\Enums\ResourceVisibility')
@php
    $editing = $resource->exists;
    $type = old('type', $resource->type?->value ?? ResourceType::Circular->value);
    $status = old('status', $resource->status?->value ?? PublicationStatus::Draft->value);
    $levels = config('school.levels');
    $title = $editing ? 'Editar recurso' : 'Nuevo recurso';
    $visibility = old('visibility', $resource->visibility?->value ?? ResourceVisibility::Families->value);
    $audience = old('audience', $resource->audience ?? []);
@endphp

<x-layouts.admin :title="$title">
    <p class="back"><a href="{{ route('admin.resources.index', ['tipo' => ResourceType::from($type)->anchor()]) }}">← Recursos</a></p>
    <div class="admin-head">
        <h1>{{ $title }}</h1>
    </div>

    <p class="notice">Útiles, uniformes y horarios se publican en la web. Las circulares pueden ser públicas o solo para las familias del portal.
        En ningún caso incluyas nombres de estudiantes, notas ni datos personales.</p>

    <form class="form admin-form" method="POST" enctype="multipart/form-data" novalidate data-form data-resource-form
        action="{{ $editing ? route('admin.resources.update', $resource) : route('admin.resources.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="admin-grid detail">
            <div class="stack">
                <section class="panel">
                    <fieldset class="field choices">
                        <legend class="label">Tipo</legend>
                        <div class="pills">
                            @foreach (ResourceType::cases() as $case)
                                <label class="pill">
                                    <input type="radio" name="type" value="{{ $case->value }}" @checked($type === $case->value) data-type-radio>
                                    <span>{{ $case->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="field-group" data-for-types="supplies">
                        <x-field name="grade" label="Grado">
                            <select id="grade" name="grade">
                                <option value="">Elige el grado</option>
                                @foreach ($levels as $level)
                                    <optgroup label="{{ $level['name'] }}">
                                        @foreach ($level['grades'] as $grade)
                                            <option value="{{ $grade }}" @selected(old('grade', $resource->grade) === $grade)>{{ $grade }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </x-field>

                        <x-field name="school_year" label="Año lectivo">
                            <input id="school_year" name="school_year" type="number" min="2020" max="2100" inputmode="numeric"
                                value="{{ old('school_year', $resource->school_year) }}">
                        </x-field>
                    </div>

                    <x-field name="title" label="Título" hint="En las listas de útiles puedes dejarlo vacío: se titula «Lista de útiles 3.° 2027».">
                        <input id="title" name="title" type="text" maxlength="160" value="{{ old('title', $resource->title) }}">
                    </x-field>

                    <x-field name="summary" label="Resumen" optional hint="Una frase que explique de qué se trata.">
                        <input id="summary" name="summary" type="text" maxlength="280" value="{{ old('summary', $resource->summary) }}">
                    </x-field>

                    <x-field name="body" label="Texto" hint="Circulares y listas: escribe el texto, adjunta un PDF o ambos. Uniformes y horarios: el texto es obligatorio.">
                        <textarea id="body" name="body" rows="12" aria-describedby="body-help">{{ old('body', $resource->body) }}</textarea>
                    </x-field>
                    <details class="md-help" id="body-help">
                        <summary>Cómo dar formato al texto</summary>
                        <ul>
                            <li>Deja una línea en blanco para empezar un párrafo nuevo.</li>
                            <li><code>**texto**</code> se ve en <strong>negrita</strong>.</li>
                            <li>Empieza una línea con <code>- </code> para hacer una lista (ideal para útiles).</li>
                            <li>Una tabla para horarios:<br><code>| Nivel | Entrada | Salida |</code><br><code>|---|---|---|</code><br><code>| Preescolar | 7:00 a. m. | 11:30 a. m. |</code></li>
                        </ul>
                    </details>
                </section>
            </div>

            <div class="stack">
                <section class="panel" data-for-types="circular" aria-labelledby="visibilidad-title">
                    <h2 id="visibilidad-title">¿Quién la ve?</h2>
                    <fieldset @class(['field', 'choices', 'has-error' => $errors->has('visibility')])>
                        <legend class="sr-only">Visibilidad de la circular</legend>
                        @foreach (ResourceVisibility::cases() as $case)
                            <label class="check visibility-option">
                                <input type="radio" name="visibility" value="{{ $case->value }}" @checked($visibility === $case->value) data-visibility-radio>
                                <span><strong>{{ $case->label() }}</strong><small class="block">{{ $case->hint() }}</small></span>
                            </label>
                        @endforeach
                        @error('visibility')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset @class(['field', 'audience', 'has-error' => $errors->has('audience.*')]) data-audience>
                        <legend class="label">Grados</legend>
                        <p class="hint">Déjalos sin marcar para enviarla a todas las familias. Si la marcas para algunos grados, solo la ven los acudientes de estudiantes matriculados en ellos este año.</p>
                        @foreach ($levels as $level)
                            <div class="audience-level">
                                <span class="audience-level-name">{{ $level['short'] }}</span>
                                @foreach ($level['grades'] as $grade)
                                    <label class="check">
                                        <input type="checkbox" name="audience[]" value="{{ $grade }}" @checked(in_array($grade, $audience, true))>
                                        <span>{{ $grade }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                        @error('audience.*')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <label class="check">
                        <input type="checkbox" name="notify" value="1" @checked(old('notify'))>
                        <span>Avisar por correo a las familias al publicarla
                            <small class="block">Llega a quienes la pueden ver. Cada familia recibe el aviso una sola vez, aunque edites la circular.</small></span>
                    </label>
                </section>

                <section class="panel">
                    <h2>Publicación</h2>
                    <fieldset class="field choices">
                        <legend class="label">Estado</legend>
                        <div class="pills">
                            @foreach (PublicationStatus::cases() as $case)
                                <label class="pill">
                                    <input type="radio" name="status" value="{{ $case->value }}" @checked($status === $case->value)>
                                    <span>{{ $case === PublicationStatus::Draft ? 'Borrador' : 'Publicar' }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <x-field name="published_on" label="Fecha" hint="En las circulares, la fecha que lleva la circular.">
                        <input id="published_on" name="published_on" type="date" value="{{ old('published_on', $resource->published_on?->toDateString()) }}">
                    </x-field>

                    <div data-for-types="uniform schedule">
                        <x-field name="position" label="Orden en la página" optional hint="Número menor = aparece primero.">
                            <input id="position" name="position" type="number" min="0" max="999" inputmode="numeric" value="{{ old('position', $resource->position ?: '') }}">
                        </x-field>
                    </div>
                </section>

                <section class="panel">
                    <h2>Archivo PDF</h2>
                    @if ($resource->file_path)
                        <p><a href="{{ $resource->fileUrl() }}" target="_blank" rel="noopener">{{ $resource->file_name }}</a> <small class="muted">({{ $resource->fileSizeLabel() }})</small></p>
                        <label class="check">
                            <input type="checkbox" name="remove_file" value="1" @checked(old('remove_file'))>
                            <span>Quitar el PDF actual</span>
                        </label>
                    @endif
                    <x-field name="file" :label="$resource->file_path ? 'Reemplazar PDF' : 'Subir PDF'" optional hint="Solo PDF, hasta 5 MB. ¿La tienes en papel? Escanéala con el celular (Google Drive o Notas del iPhone la guardan en PDF).">
                        <input id="file" name="file" type="file" accept="application/pdf">
                    </x-field>
                </section>

                <section class="panel" data-for-types="uniform">
                    <h2>Foto del uniforme</h2>
                    @if ($resource->image_path)
                        <img class="cover-preview" src="{{ $resource->imageUrl('sm') }}" alt="{{ $resource->image_alt }}">
                        <label class="check">
                            <input type="checkbox" name="remove_image" value="1" @checked(old('remove_image'))>
                            <span>Quitar la foto actual</span>
                        </label>
                    @endif
                    <x-field name="image" :label="$resource->image_path ? 'Reemplazar foto' : 'Subir foto'" optional hint="JPG, PNG o WebP, hasta 8 MB. Mejor si se ve la prenda sola o en un maniquí.">
                        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                    </x-field>
                    <x-field name="image_alt" label="Descripción de la foto" hint="Ejemplo: «Jardinera azul con camisa blanca».">
                        <input id="image_alt" name="image_alt" type="text" maxlength="200" value="{{ old('image_alt', $resource->image_alt) }}">
                    </x-field>
                    <div @class(['field', 'consent', 'has-error' => $errors->has('minors_consent')])>
                        <label class="check">
                            <input id="minors_consent" type="checkbox" name="minors_consent" value="1" @checked(old('minors_consent'))>
                            <span>Si la foto muestra menores, cuento con la autorización escrita de sus acudientes para publicarla.</span>
                        </label>
                        @error('minors_consent')
                            <p class="error" id="minors_consent-error">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                <button type="submit" class="btn btn-azul" data-submit data-loading-text="Guardando…"><span>Guardar</span></button>
            </div>
        </div>
    </form>

    @if ($editing)
        <form class="danger-zone" method="POST" action="{{ route('admin.resources.destroy', $resource) }}" data-confirm="¿Eliminar «{{ $resource->title }}»? No se puede deshacer.">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
        </form>
    @endif
</x-layouts.admin>
