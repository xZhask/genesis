@use('App\Enums\PostStatus')
@php
    $editing = $post->exists;
    $status = old('status', $post->status?->value ?? PostStatus::Draft->value);
@endphp

<x-layouts.admin :title="$editing ? 'Editar noticia' : 'Nueva noticia'">
    <p class="back"><a href="{{ route('admin.posts.index') }}">← Noticias</a></p>
    <div class="admin-head">
        <h1>{{ $editing ? 'Editar noticia' : 'Nueva noticia' }}</h1>
    </div>

    <form class="form admin-form" method="POST" enctype="multipart/form-data" novalidate data-form
        action="{{ $editing ? route('admin.posts.update', $post) : route('admin.posts.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="admin-grid detail">
            <div class="stack">
                <section class="panel">
                    <x-field name="title" label="Título">
                        <input id="title" name="title" type="text" maxlength="160" value="{{ old('title', $post->title) }}" required>
                    </x-field>

                    <x-field name="excerpt" label="Resumen" hint="Una o dos frases. Se muestra en las tarjetas y al compartir por WhatsApp.">
                        <textarea id="excerpt" name="excerpt" rows="2" maxlength="280" required>{{ old('excerpt', $post->excerpt) }}</textarea>
                    </x-field>

                    <x-field name="body" label="Texto de la noticia">
                        <textarea id="body" name="body" rows="14" required aria-describedby="body-help">{{ old('body', $post->body) }}</textarea>
                    </x-field>
                    <details class="md-help" id="body-help">
                        <summary>Cómo dar formato al texto</summary>
                        <ul>
                            <li>Deja una línea en blanco para empezar un párrafo nuevo.</li>
                            <li><code>**texto**</code> se ve en <strong>negrita</strong>; <code>*texto*</code>, en <em>cursiva</em>.</li>
                            <li>Empieza una línea con <code>- </code> para hacer una lista.</li>
                            <li><code>## Subtítulo</code> crea un subtítulo.</li>
                            <li><code>[texto](https://…)</code> crea un enlace.</li>
                        </ul>
                    </details>
                </section>
            </div>

            <div class="stack">
                <section class="panel">
                    <h2>Publicación</h2>
                    <fieldset class="field choices">
                        <legend class="label">Estado</legend>
                        <div class="pills">
                            @foreach (PostStatus::cases() as $case)
                                <label class="pill">
                                    <input type="radio" name="status" value="{{ $case->value }}" @checked($status === $case->value)>
                                    <span>{{ $case === PostStatus::Draft ? 'Borrador' : 'Publicar' }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <x-field name="published_at" label="Fecha de publicación" optional
                        hint="Vacía = se publica al guardar. Una fecha futura la programa.">
                        <input id="published_at" name="published_at" type="datetime-local"
                            value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
                    </x-field>
                </section>

                <section class="panel">
                    <h2>Foto de portada</h2>
                    @if ($post->cover_path)
                        <img class="cover-preview" src="{{ $post->coverUrl('sm') }}" alt="{{ $post->cover_alt }}">
                        <label class="check">
                            <input type="checkbox" name="remove_cover" value="1" @checked(old('remove_cover'))>
                            <span>Quitar la foto actual</span>
                        </label>
                    @endif

                    <x-field name="cover" :label="$post->cover_path ? 'Reemplazar foto' : 'Subir foto'" optional hint="JPG, PNG o WebP, hasta 8 MB. Se ajusta el tamaño automáticamente.">
                        <input id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp">
                    </x-field>

                    <x-field name="cover_alt" label="Descripción de la foto" hint="Qué se ve en la foto, en pocas palabras. Ejemplo: «Estudiantes de 3.° presentando su proyecto de ciencias».">
                        <input id="cover_alt" name="cover_alt" type="text" maxlength="200" value="{{ old('cover_alt', $post->cover_alt) }}">
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
        <form class="danger-zone" method="POST" action="{{ route('admin.posts.destroy', $post) }}" data-confirm="¿Eliminar la noticia «{{ $post->title }}»? No se puede deshacer.">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger">Eliminar noticia</button>
        </form>
    @endif
</x-layouts.admin>
