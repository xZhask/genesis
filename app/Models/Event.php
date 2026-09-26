<?php

namespace App\Models;

use App\Enums\ResourceVisibility;
use App\Enums\Role;
use App\Models\Concerns\HasAudience;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Event extends Model
{
    use HasAudience, HasFactory;

    protected $fillable = ['title', 'description', 'starts_at', 'ends_at', 'all_day', 'location', 'level', 'send_reminder', 'visibility', 'audience'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'all_day' => 'boolean',
            'send_reminder' => 'boolean',
            'visibility' => ResourceVisibility::class,
            'audience' => 'array',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Eventos que aún no terminan (incluye los de varios días en curso). */
    public function scopeUpcoming(Builder $query): void
    {
        $today = now()->startOfDay();

        $query->where(fn (Builder $q) => $q
            ->where('starts_at', '>=', $today)
            ->orWhere('ends_at', '>=', $today))
            ->orderBy('starts_at');
    }

    /** Eventos de un nivel más los de todo el colegio. */
    public function scopeForLevel(Builder $query, ?string $level): void
    {
        if ($level) {
            $query->where(fn (Builder $q) => $q->whereNull('level')->orWhere('level', $level));
        }
    }

    /** Eventos que tocan un rango de fechas (para la vista de mes). */
    public function scopeBetween(Builder $query, Carbon $from, Carbon $to): void
    {
        $query->where('starts_at', '<=', $to)
            ->where(fn (Builder $q) => $q
                ->where('starts_at', '>=', $from)
                ->orWhere('ends_at', '>=', $from))
            ->orderBy('starts_at');
    }

    /**
     * ¿Puede verlo esta persona? Los públicos, cualquiera; los de solo
     * familias, el personal del colegio y los acudientes de los grados a los
     * que va (dentro del nivel del evento, si tiene uno).
     */
    public function visibleTo(?User $user): bool
    {
        if (! $this->isForFamiliesOnly()) {
            return true;
        }
        if (! $user) {
            return false;
        }
        if ($user->isAdmin() || $user->hasRole(Role::Teacher)) {
            return true;
        }

        return $user->guardian !== null && $this->reachesGrades(Resource::gradesOf($user->guardian));
    }

    /** ¿Llega a alguno de estos grados? (nivel del evento y grados elegidos). */
    public function reachesGrades(array $grades): bool
    {
        if ($this->level) {
            $levelGrades = collect(config('school.levels'))->firstWhere('key', $this->level)['grades'] ?? [];
            $grades = array_values(array_intersect($grades, $levelGrades));
        }

        return $grades !== [] && $this->isForGrades($grades);
    }

    public function isMultiDay(): bool
    {
        return $this->ends_at !== null && ! $this->ends_at->isSameDay($this->starts_at);
    }

    /** Último día que ocupa el evento. */
    public function lastDay(): Carbon
    {
        return ($this->ends_at ?? $this->starts_at)->copy()->startOfDay();
    }

    public function occursOn(Carbon $day): bool
    {
        return $day->betweenIncluded($this->starts_at->copy()->startOfDay(), $this->lastDay());
    }

    /** "Del 5 al 9 de octubre", "Todo el día" o "7:00 a. m. – 9:00 a. m." */
    public function whenLabel(): string
    {
        $start = $this->starts_at;
        $end = $this->ends_at;

        if ($this->isMultiDay()) {
            return $start->isSameMonth($end)
                ? 'Del '.$start->day.' al '.$end->dayMonth()
                : 'Del '.$start->dayMonth().' al '.$end->dayMonth();
        }

        if ($this->all_day) {
            return 'Todo el día';
        }

        return $end ? $start->shortTime().' – '.$end->shortTime() : $start->shortTime();
    }

    public function metaLabel(): string
    {
        return collect([$this->whenLabel(), $this->location])->filter()->join(' · ');
    }

    public function levelName(): ?string
    {
        return $this->level
            ? collect(config('school.levels'))->firstWhere('key', $this->level)['short'] ?? null
            : null;
    }
}
