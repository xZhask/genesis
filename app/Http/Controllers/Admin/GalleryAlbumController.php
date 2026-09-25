<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AlbumStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GalleryAlbumRequest;
use App\Models\GalleryAlbum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GalleryAlbumController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', GalleryAlbum::class);

        return view('admin.gallery.index', [
            'albums' => GalleryAlbum::query()
                ->withCount('photos')
                ->withCount(['photos as undescribed_count' => fn ($q) => $q->whereNull('alt')])
                ->with(['coverPhoto', 'photos' => fn ($q) => $q->limit(1)])
                ->latest('taken_on')
                ->latest('id')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', GalleryAlbum::class);

        return view('admin.gallery.create', [
            'album' => new GalleryAlbum(['status' => AlbumStatus::Draft, 'taken_on' => today()]),
        ]);
    }

    public function store(GalleryAlbumRequest $request): RedirectResponse
    {
        $album = GalleryAlbum::create($request->validated());

        return redirect()
            ->route('admin.albums.edit', $album)
            ->with('status_message', "Se creó el álbum «{$album->title}». Ahora sube las fotos.");
    }

    public function edit(Request $request, GalleryAlbum $album): View
    {
        $this->authorize('update', $album);

        $album->load('photos');

        return view('admin.gallery.edit', [
            'album' => $album,
            'uploaded' => (int) $request->query('subidas'),
            'failed' => (int) $request->query('fallidas'),
        ]);
    }

    public function update(GalleryAlbumRequest $request, GalleryAlbum $album): RedirectResponse
    {
        $album->update($request->validated());

        return redirect()
            ->route('admin.albums.edit', $album)
            ->with('status_message', "Se guardaron los datos del álbum «{$album->title}».");
    }

    public function destroy(GalleryAlbum $album): RedirectResponse
    {
        $this->authorize('delete', $album);

        $album->delete();

        return redirect()
            ->route('admin.albums.index')
            ->with('status_message', "Se eliminó el álbum «{$album->title}» y sus fotos.");
    }
}
