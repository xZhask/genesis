@use('App\Enums\PublicationStatus')

<x-layouts.admin title="Galería">
    <div class="admin-head row">
        <h1>Galería</h1>
        <a class="btn btn-sol" href="{{ route('admin.albums.create') }}">+ Nuevo álbum</a>
    </div>

    @if ($albums->isEmpty())
        <p class="empty panel">Todavía no hay álbumes. <a href="{{ route('admin.albums.create') }}">Crea el primero</a> y sube las fotos de una actividad.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col"><span class="sr-only">Portada</span></th>
                        <th scope="col">Álbum</th>
                        <th scope="col">Actividad</th>
                        <th scope="col">Fotos</th>
                        <th scope="col">Estado</th>
                        <th scope="col"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($albums as $album)
                        @php $cover = $album->cover(); @endphp
                        <tr>
                            <td class="thumb-cell">
                                @if ($cover)
                                    <img class="thumb" src="{{ $cover->url('sm') }}" alt="" width="72" height="48" loading="lazy">
                                @endif
                            </td>
                            <td data-label="Álbum"><a href="{{ route('admin.albums.edit', $album) }}">{{ $album->title }}</a></td>
                            <td data-label="Actividad" class="nowrap">{{ $album->taken_on->longDate() }}</td>
                            <td data-label="Fotos">
                                {{ $album->photos_count }}
                                @if ($album->undescribed_count)
                                    <small class="warn-text">· {{ trans_choice(':count sin descripción|:count sin descripción', $album->undescribed_count) }}</small>
                                @endif
                            </td>
                            <td data-label="Estado">
                                @if ($album->status === PublicationStatus::Published && ! $album->photos_count)
                                    <span class="badge badge-in_review">Sin fotos</span>
                                @else
                                    <span class="badge badge-{{ $album->status === PublicationStatus::Published ? 'accepted' : 'withdrawn' }}">{{ $album->status->label() }}</span>
                                @endif
                            </td>
                            <td class="row-actions">
                                <a href="{{ route('gallery.show', $album) }}" target="_blank" rel="noopener">{{ $album->status === PublicationStatus::Published && $album->photos_count ? 'Ver' : 'Vista previa' }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $albums->links('partials.pagination') }}
    @endif
</x-layouts.admin>
