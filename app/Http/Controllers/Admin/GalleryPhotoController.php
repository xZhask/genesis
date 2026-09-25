<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGalleryPhotosRequest;
use App\Http\Requests\Admin\UpdateGalleryPhotosRequest;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Support\ImageResizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class GalleryPhotoController extends Controller
{
    public function store(StoreGalleryPhotosRequest $request, GalleryAlbum $album): JsonResponse|RedirectResponse
    {
        $position = $album->nextPosition();
        $saved = 0;

        foreach ($request->file('photos') as $file) {
            try {
                $image = ImageResizer::save($file, 'gallery');
            } catch (RuntimeException) {
                throw ValidationException::withMessages([
                    'photos' => "No se pudo leer «{$file->getClientOriginalName()}». Prueba con otra foto.",
                ]);
            }

            $photo = new GalleryPhoto;
            $photo->gallery_album_id = $album->id;
            $photo->path = $image['path'];
            $photo->width = $image['width'];
            $photo->height = $image['height'];
            $photo->position = $position++;
            $photo->save();
            $saved++;
        }

        if ($request->expectsJson()) {
            return response()->json(['saved' => $saved], 201);
        }

        return redirect()
            ->route('admin.albums.edit', ['album' => $album, 'subidas' => $saved])
            ->withFragment('fotos');
    }

    public function update(UpdateGalleryPhotosRequest $request, GalleryAlbum $album): RedirectResponse
    {
        $photos = $album->photos()->get();

        foreach ($request->validated('photos', []) as $id => $data) {
            $photos->firstWhere('id', $id)?->fill([
                'alt' => $data['alt'] ?? null,
                'caption' => $data['caption'] ?? null,
            ])->save();
        }

        $message = 'Se guardaron las descripciones.';
        $fragment = 'fotos';

        if ($request->action()) {
            [$action, $id] = $request->action();
            // La foto debe ser de este álbum
            $photo = $photos->firstWhere('id', $id) ?? abort(404);

            $message = match ($action) {
                'up', 'down' => $this->move($photos, $photo, $action === 'up' ? -1 : 1),
                'cover' => $this->makeCover($album, $photo),
                'delete' => $this->remove($photo),
            };

            $fragment = $action === 'delete' ? 'fotos' : "foto-{$photo->id}";
        }

        return redirect()
            ->route('admin.albums.edit', $album)
            ->withFragment($fragment)
            ->with('status_message', $message);
    }

    /** Intercambia la foto con su vecina y renumera el orden del álbum. */
    private function move(Collection $photos, GalleryPhoto $photo, int $step): string
    {
        $list = $photos->values()->all();
        $from = array_search($photo, $list, true);
        $to = $from + $step;

        if ($to >= 0 && $to < count($list)) {
            [$list[$from], $list[$to]] = [$list[$to], $list[$from]];
        }

        foreach ($list as $index => $item) {
            if ($item->position !== $index + 1) {
                $item->position = $index + 1;
                $item->save();
            }
        }

        return 'Se cambió el orden de las fotos.';
    }

    private function makeCover(GalleryAlbum $album, GalleryPhoto $photo): string
    {
        $album->cover_photo_id = $photo->id;
        $album->save();

        return 'Se cambió la portada del álbum.';
    }

    private function remove(GalleryPhoto $photo): string
    {
        // La llave foránea deja la portada en null si era esta foto
        $photo->delete();

        return 'Se eliminó la foto.';
    }
}
