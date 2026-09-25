<?php

namespace App\Http\Controllers;

use App\Models\GalleryAlbum;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function index(): View
    {
        return view('gallery.index', [
            'albums' => GalleryAlbum::published()
                ->withCount('photos')
                ->with(['coverPhoto', 'photos' => fn ($q) => $q->limit(1)])
                ->latest('taken_on')
                ->latest('id')
                ->paginate(12),
        ]);
    }

    public function show(Request $request, GalleryAlbum $album): View
    {
        // Borradores y álbumes sin fotos: 404 para el público; el admin puede previsualizarlos
        $preview = ! $album->isVisible();
        abort_if($preview && ! $request->user()?->isAdmin(), 404);

        $album->load('photos');

        return view('gallery.show', [
            'album' => $album,
            'preview' => $preview,
            'others' => GalleryAlbum::published()
                ->whereKeyNot($album->id)
                ->withCount('photos')
                ->with(['coverPhoto', 'photos' => fn ($q) => $q->limit(1)])
                ->latest('taken_on')
                ->limit(3)
                ->get(),
        ]);
    }
}
