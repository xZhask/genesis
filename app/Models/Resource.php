<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Enums\ResourceType;
use App\Enums\ResourceVisibility;
use App\Support\ImageResizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'title', 'summary', 'body', 'image_alt', 'grade', 'school_year', 'published_on', 'status', 'visibility', 'audience', 'position'];

    protected function casts(): array
    {
        return [
            'type' => ResourceType::class,
            'status' => PublicationStatus::class,
            'visibility' => ResourceVisibility::class,
            'audience' => 'array',
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

    /** Lo que se ve en la web pública (las circulares «solo familias» no). */
    public function scopePublicWeb(Builder $query): void
    {
        $query->where('visibility', ResourceVisibility::Public);
    }

    public function isForFamiliesOnly(): bool
    {
        return $this->visibility === ResourceVisibility::Families;
    }

    /** ¿Va dirigida a alguno de estos grados? Sin grados elegidos, es para todas las familias. */
    public function isForGrades(array $grades): bool
    {
        return empty($this->audience) || array_intersect($this->audience, $grades) !== [];
    }

    /** «Todas las familias» o «Familias de 5.° y 6.°». */
    public function audienceLabel(): string
    {
        if (empty($this->audience)) {
            return 'Todas las familias';
        }
        // En el orden del colegio, no en el que se marcaron
        $grades = array_values(array_intersect(self::grades(), $this->audience));

        return 'Familias de '.collect($grades)->join(', ', ' y ');
    }

    /**
     * Circulares publicadas que ve un acudiente: las públicas y las de solo
     * familias dirigidas a todos o a los grados de sus acudidos este año.
     *
     * @return Collection<int, self>
     */
    public static function circularsFor(Guardian $guardian): Collection
    {
        $grades = self::gradesOf($guardian);

        return self::published()->ofType(ResourceType::Circular)
            ->latest('published_on')->latest('id')
            ->get()
            ->filter(fn (self $r) => ! $r->isForFamiliesOnly() || $r->isForGrades($grades))
            ->values();
    }

    /** Grados de los acudidos en el año actual. */
    public static function gradesOf(Guardian $guardian): array
    {
        $year = SchoolYear::current();

        return $year
            ? Enrollment::whereIn('student_id', $guardian->students()->pluck('students.id'))
                ->where('school_year_id', $year->id)
                ->with('section.grade')
                ->get()
                ->map(fn (Enrollment $e) => $e->section->grade->name)
                ->unique()->values()->all()
            : [];
    }

    /** ¿Puede esta persona abrir el recurso (y su PDF)? */
    public function visibleTo(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if (! $this->isPublished()) {
            return false;
        }
        if (! $this->isForFamiliesOnly()) {
            return true;
        }

        return $user->guardian !== null && $this->isForGrades(self::gradesOf($user->guardian));
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

    /** Las circulares «solo familias» guardan su PDF fuera del disco público. */
    public function fileDisk(): string
    {
        return $this->isForFamiliesOnly() ? 'local' : 'public';
    }

    public function fileUrl(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return $this->isForFamiliesOnly()
            ? route('circulars.file', $this)
            : Storage::disk('public')->url($this->file_path);
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
            Storage::disk($this->fileDisk())->delete($this->file_path);
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
