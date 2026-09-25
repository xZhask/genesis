<?php

namespace App\Models;

use App\Support\ImageResizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryPhoto extends Model
{
    use HasFactory;

    protected $fillable = ['alt', 'caption'];

    protected static function booted(): void
    {
        static::deleted(fn (self $photo) => ImageResizer::delete($photo->path));
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(GalleryAlbum::class, 'gallery_album_id');
    }

    public function url(string $size = 'lg'): string
    {
        return ImageResizer::url($this->path, $size);
    }

    /** Texto alternativo; si aún no se describió, el título del álbum. */
    public function altText(): string
    {
        return $this->alt ?: 'Foto de '.$this->album->title;
    }

    /**
     * Datos para la cuadrícula y el visor (componente x-gallery-photo). Con
     * $withAlbum, el pie incluye el álbum (en el inicio se mezclan álbumes).
     */
    public function forLightbox(bool $withAlbum = false): array
    {
        $width = min(ImageResizer::SIZES['sm'], $this->width);
        $caption = $withAlbum
            ? implode(' · ', array_filter([$this->album->title, $this->caption]))
            : (string) $this->caption;

        return [
            'thumb' => $this->url('sm'),
            'full' => $this->url('lg'),
            'alt' => $this->altText(),
            'caption' => $caption,
            'width' => $width,
            'height' => (int) round($this->height * $width / max($this->width, 1)),
            'tone' => null,
        ];
    }
}
