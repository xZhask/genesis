<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Enums\ResourceType;
use App\Support\ImageResizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'title', 'summary', 'body', 'image_alt', 'grade', 'school_year', 'published_on', 'status', 'position'];

    protected function casts(): array
    {
        return [
            'type' => ResourceType::class,
            'status' => PublicationStatus::class,
            'published_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (self $resource) {
            $resource->deleteFile();
            ImageResizer::delete($resource->image_path);
        });
    }

    /** Todos los grados del colegio, en orden (config/school.php). */
    public static function grades(): array
    {
        return collect(config('school.levels'))->pluck('grades')->flatten()->all();
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', PublicationStatus::Published);
    }

    public function scopeOfType(Builder $query, ResourceType $type): void
    {
        $query->where('type', $type);
    }

    public function isPublished(): bool
    {
        return $this->status === PublicationStatus::Published;
    }

    /** Circular publicada en los últimos 7 días. */
    public function isNew(): bool
    {
        return $this->type === ResourceType::Circular && $this->published_on->gte(today()->subDays(7));
    }

    public function fileUrl(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }

    /** "240 KB", "1,2 MB" (sin depender de la extensión intl del hosting). */
    public function fileSizeLabel(): string
    {
        $bytes = (int) $this->file_size;

        return $bytes >= 1048576
            ? number_format($bytes / 1048576, 1, ',', '.').' MB'
            : max(1, (int) round($bytes / 1024)).' KB';
    }

    public function imageUrl(string $size = 'lg'): ?string
    {
        return ImageResizer::url($this->image_path, $size);
    }

    public function deleteFile(): void
    {
        if ($this->file_path) {
            Storage::disk('public')->delete($this->file_path);
        }
    }

    /** Ancla en /recursos: #circular-12, #utiles-3, #utiles-parvulos… */
    public function anchor(): string
    {
        return $this->type === ResourceType::Supplies
            ? self::gradeAnchor($this->grade)
            : $this->type->value.'-'.$this->id;
    }

    public static function gradeAnchor(string $grade): string
    {
        return 'utiles-'.Str::slug($grade);
    }

    /** Markdown seguro, igual que las noticias. */
    public function bodyHtml(): HtmlString
    {
        return new HtmlString(Str::markdown((string) $this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));
    }
}
