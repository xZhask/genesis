<?php

namespace App\Http\Controllers;

use App\Enums\AlbumStatus;
use App\Models\Event;
use App\Models\GalleryPhoto;
use App\Models\Post;
use App\Support\DemoContent;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $demo = DemoContent::enabled();

        // Fotos de los álbumes publicados más recientes, en su orden
        $photos = GalleryPhoto::query()
            ->select('gallery_photos.*')
            ->join('gallery_albums', 'gallery_albums.id', '=', 'gallery_photos.gallery_album_id')
            ->where('gallery_albums.status', AlbumStatus::Published)
            ->orderByDesc('gallery_albums.taken_on')
            ->orderByDesc('gallery_albums.id')
            ->orderBy('gallery_photos.position')
            ->with('album')
            ->limit(8)
            ->get()
            ->map(fn (GalleryPhoto $photo) => $photo->forLightbox(withAlbum: true));

        return view('home', [
            'demo' => $demo,
            'levels' => config('school.levels'),
            'heroPhotos' => $demo ? DemoContent::heroPhotos() : config('school.hero_photos'),
            'posts' => Post::published()->latest('published_at')->limit(3)->get(),
            'events' => Event::upcoming()->limit(3)->get(),
            'photos' => $photos->isEmpty() && $demo ? DemoContent::photos() : $photos,
            'support' => $demo ? DemoContent::support() : config('school.support'),
        ]);
    }
}
