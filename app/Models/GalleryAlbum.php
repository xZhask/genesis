<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GalleryAlbum extends Model
{
    use HasFactory, HasUniqueSlug;

    protected $fillable = ['title', 'description', 'taken_on', 'status'];

    protected function casts(): array
    {
        return [
            'status' => PublicationStatus::class,
            'taken_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // Una por una, para que cada foto borre sus archivos
        static::deleting(function (self $album) {
            $album->forceFill(['cover_photo_id' => null])->saveQuietly();
            $album->photos()->get()->each->delete();
        });
    }

    protected static function slugFallback(): string
    {
        return 'album';
    }

    public function photos(): HasMany
    {
        return $this->hasMany(GalleryPhoto::class)->orderBy('position')->orderBy('id');
    }

    public function coverPhoto(): BelongsTo
    {
        return $this->belongsTo(GalleryPhoto::class, 'cover_photo_id');
    }

    /** Publicados y con al menos una foto: un álbum vacío no se muestra. */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PublicationStatus::Published)->whereHas('photos');
    }

    public function isVisible(): bool
    {
        return $this->status === PublicationStatus::Published && $this->photos()->exists();
    }

    /** La portada elegida o, si no hay, la primera foto. */
    public function cover(): ?GalleryPhoto
    {
        return $this->coverPhoto ?? $this->photos->first();
    }

    public function url(): string
    {
        return route('gallery.show', $this);
    }

    public function nextPosition(): int
    {
        return (int) $this->photos()->max('position') + 1;
    }
}
