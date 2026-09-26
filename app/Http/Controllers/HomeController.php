<?php

namespace App\Http\Controllers;

use App\Enums\PublicationStatus;
use App\Models\DonationAccount;
use App\Models\Donor;
use App\Models\Event;
use App\Models\GalleryPhoto;
use App\Models\Post;
use App\Models\Testimonial;
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
            ->where('gallery_albums.status', PublicationStatus::Published)
            ->orderByDesc('gallery_albums.taken_on')
            ->orderByDesc('gallery_albums.id')
            ->orderBy('gallery_photos.position')
            ->with('album')
            ->limit(8)
            ->get()
            ->map(fn (GalleryPhoto $photo) => $photo->forLightbox(withAlbum: true));

        return view('home', [
            'demo' => $demo,
            'levels' => DemoContent::levels(),
            'heroPhotos' => $demo ? DemoContent::heroPhotos() : config('school.hero_photos'),
            'posts' => Post::published()->latest('published_at')->limit(3)->get(),
            'events' => Event::publicWeb()->upcoming()->limit(3)->get(),
            'photos' => $photos->isEmpty() && $demo ? DemoContent::photos() : $photos,
            'support' => $this->support($demo),
        ]);
    }

    /** Resumen de Apóyanos. En local, lo que falte se completa con contenido de ejemplo. */
    private function support(bool $demo): array
    {
        $testimonial = Testimonial::visible()->first();

        $support = [
            'accounts' => DonationAccount::visible()->get()
                ->map(fn (DonationAccount $a) => ['label' => $a->label, 'value' => $a->number])
                ->all(),
            'testimonial' => $testimonial ? ['quote' => $testimonial->quote, 'author' => $testimonial->signature()] : null,
            'volunteer_photos' => config('school.support.volunteer_photos'),
            'donors' => Donor::visible()->limit(8)->pluck('name')->all(),
        ];

        if ($demo) {
            foreach (DemoContent::support() as $key => $value) {
                $support[$key] = $support[$key] ?: $value;
            }
        }

        return $support;
    }
}
