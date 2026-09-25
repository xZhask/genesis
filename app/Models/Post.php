<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Models\Concerns\HasUniqueSlug;
use App\Support\ImageResizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory, HasUniqueSlug;

    protected $fillable = ['title', 'excerpt', 'body', 'cover_alt', 'status', 'published_at'];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(fn (self $post) => ImageResizer::delete($post->cover_path));
    }

    protected static function slugFallback(): string
    {
        return 'noticia';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Publicadas y con fecha ya cumplida (las programadas no se ven). */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isVisible(): bool
    {
        return $this->status === PostStatus::Published && $this->published_at?->lte(now());
    }

    public function coverUrl(string $size = 'lg'): ?string
    {
        return ImageResizer::url($this->cover_path, $size);
    }

    public function url(): string
    {
        return route('news.show', $this);
    }

    /** Markdown seguro: se ignora el HTML escrito a mano y los enlaces peligrosos. */
    public function bodyHtml(): HtmlString
    {
        return new HtmlString(Str::markdown($this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));
    }

    /** Tono del marcador de foto cuando no hay portada (estilo del mockup). */
    public function tone(): string
    {
        return ['t-azul', 't-verde', 't-sol', 't-navy', 't-cielo'][$this->id % 5];
    }
}
