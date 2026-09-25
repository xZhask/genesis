@use('App\Enums\AlbumStatus')
@use('App\Http\Requests\Admin\StoreGalleryPhotosRequest')
@php
    $photos = $album->photos;
    $cover = $album->cover();
    $undescribed = $photos->whereNull('alt')->count();
    $visible = $album->status === AlbumStatus::Published && $photos->isNotEmpty();
@endphp

<x-layouts.admin :title="$album->title">
    <p class="back"><a href="{{ route('admin.albums.index') }}">← Galería</a></p>
    <div class="admin-head row">
        <h1>{{ $album->title }}</h1>
        <a class="btn btn-line btn-sm" href="{{ route('gallery.show', $album) }}" target="_blank" rel="noopener">{{ $visible ? 'Ver en la web ↗' : 'Vista previa ↗' }}</a>
    </div>

    @if ($uploaded)
        <div class="flash" role="status">
            {{ trans_choice('Se subió :count foto.|Se subieron :count fotos.', $uploaded) }}
            Describe cada una abajo para quienes usan lectores de pantalla.
        </div>
    @endif

    @if ($album->status === AlbumStatus::Published && $photos->isEmpty())
        <p class="notice">El álbum está marcado como publicado, pero no se verá en la web hasta que tenga fotos.</p>
    @endif

    <div class="admin-grid detail">
        <div class="stack">
            <section class="panel" aria-labelledby="subir-title">
                <h2 id="subir-title">Subir fotos</h2>

                <form class="form admin-form uploader" method="POST" enctype="multipart/form-data" novalidate
                    action="{{ route('admin.albums.photos.store', $album) }}"
                    data-uploader data-max-kb="{{ StoreGalleryPhotosRequest::MAX_KB }}"
                    data-return="{{ route('admin.albums.edit', $album) }}">
                    @csrf

                    <x-field name="photos" label="Fotos" hint="JPG, PNG o WebP, hasta 10 MB cada una. Puedes elegir varias a la vez; se ajustan de tamaño automáticamente.">
                        <input id="photos" name="photos[]" type="file" multiple accept="image/jpeg,image/png,image/webp">
                        @if ($errors->has('photos.*'))
                            <p class="error">{{ $errors->first('photos.*') }}</p>
                        @endif
                    </x-field>

                    <div @class(['field', 'consent', 'has-error' => $errors->has('minors_consent')])>
                        <label class="check">
                            <input id="minors_consent" type="checkbox" name="minors_consent" value="1">
                            <span>Si las fotos muestran menores, cuento con la autorización escrita de sus acudientes para publicarlas.</span>
                        </label>
                        @error('minors_consent')
                            <p class="error" id="minors_consent-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <p class="error" data-upload-error hidden></p>

                    <button type="submit" class="btn btn-azul" data-upload-submit><span>Subir fotos</span></button>

                    <div class="upload-status" data-upload-status hidden>
                        <progress max="100" value="0"></progress>
                        <p data-upload-text aria-live="polite"></p>
                        <ul class="upload-errors" data-upload-errors></ul>
                    </div>
                </form>
            </section>

            <section class="panel" id="fotos" aria-labelledby="fotos-title">
                <h2 id="fotos-title">Fotos ({{ $photos->count() }})</h2>

                @if ($photos->isEmpty())
                    <p class="empty">Todavía no hay fotos en este álbum.</p>
                @else
                    @if ($undescribed)
                        <p class="notice">
                            {{ trans_choice(':count foto no tiene descripción|:count fotos no tienen descripción', $undescribed) }}.
                            Mientras tanto se usa «Foto de {{ $album->title }}».
                        </p>
                    @endif

                    <form class="form photo-form" method="POST" action="{{ route('admin.albums.photos.update', $album) }}" novalidate>
                        @csrf
                        @method('PUT')

                        {{-- Primer botón del formulario: el que usa la tecla Enter --}}
                        <div class="photo-bar">
                            <span class="muted">Escribe las descripciones y guarda al final.</span>
                            <button type="submit" class="btn btn-azul btn-sm">Guardar descripciones</button>
                        </div>

                        <ol class="photo-list">
                            @foreach ($photos as $photo)
                                @php
                                    $size = $photo->forLightbox();
                                    $isCover = $cover?->is($photo);
                                @endphp
                                <li class="photo-item" id="foto-{{ $photo->id }}">
                                    <div class="photo-thumb">
                                        <img src="{{ $photo->url('sm') }}" alt="" width="{{ $size['width'] }}" height="{{ $size['height'] }}" loading="lazy">
                                        @if ($isCover)
                                            <span class="badge badge-accepted">Portada</span>
                                        @endif
                                    </div>

                                    <div class="photo-fields">
                                        <div @class(['field', 'has-error' => $errors->has("photos.{$photo->id}.alt")])>
                                            <label class="label" for="alt-{{ $photo->id }}">
                                                Descripción
                                                @unless ($photo->alt)
                                                    <span class="warn-text">(pendiente)</span>
                                                @endunless
                                            </label>
                                            <input id="alt-{{ $photo->id }}" name="photos[{{ $photo->id }}][alt]" type="text" maxlength="200"
                                                value="{{ old("photos.{$photo->id}.alt", $photo->alt) }}" placeholder="Qué se ve en la foto">
                                            @error("photos.{$photo->id}.alt")
                                                <p class="error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="field">
                                            <label class="label" for="caption-{{ $photo->id }}">Pie de foto <span class="opt">(opcional)</span></label>
                                            <input id="caption-{{ $photo->id }}" name="photos[{{ $photo->id }}][caption]" type="text" maxlength="200"
                                                value="{{ old("photos.{$photo->id}.caption", $photo->caption) }}" placeholder="Se muestra debajo de la foto ampliada">
                                        </div>
                                    </div>

                                    <div class="photo-actions">
                                        <button type="submit" name="do" value="up:{{ $photo->id }}" class="btn btn-line btn-sm" @disabled($loop->first)
                                            aria-label="Mover la foto {{ $loop->iteration }} antes">↑</button>
                                        <button type="submit" name="do" value="down:{{ $photo->id }}" class="btn btn-line btn-sm" @disabled($loop->last)
                                            aria-label="Mover la foto {{ $loop->iteration }} después">↓</button>
                                        <button type="submit" name="do" value="cover:{{ $photo->id }}" class="btn btn-line btn-sm" @disabled($isCover)>Usar de portada</button>
                                        <button type="submit" name="do" value="delete:{{ $photo->id }}" class="btn btn-sm btn-danger"
                                            data-confirm="¿Eliminar esta foto? No se puede deshacer.">Eliminar</button>
                                    </div>
                                </li>
                            @endforeach
                        </ol>

                        <button type="submit" class="btn btn-azul">Guardar descripciones</button>
                    </form>
                @endif
            </section>
        </div>

        <div class="stack">
            <form class="form admin-form" method="POST" action="{{ route('admin.albums.update', $album) }}" novalidate data-form>
                @csrf
                @method('PUT')
                <section class="panel">
                    <h2>Datos del álbum</h2>
                    @include('admin.gallery.partials.album-fields')
                    <button type="submit" class="btn btn-azul" data-submit data-loading-text="Guardando…"><span>Guardar</span></button>
                </section>
            </form>

            <form class="danger-zone" method="POST" action="{{ route('admin.albums.destroy', $album) }}"
                data-confirm="¿Eliminar el álbum «{{ $album->title }}» y sus {{ $photos->count() }} fotos? No se puede deshacer.">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger">Eliminar álbum</button>
            </form>
        </div>
    </div>
</x-layouts.admin>
