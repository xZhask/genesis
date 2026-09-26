<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/** Franja del horario (hora de clase o descanso) de un nivel en un año lectivo. */
class ScheduleBlock extends Model
{
    protected $fillable = ['school_year_id', 'level', 'starts_at', 'ends_at', 'is_break', 'label'];

    protected function casts(): array
    {
        return ['is_break' => 'boolean'];
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(ScheduleSlot::class);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('starts_at')->orderBy('ends_at');
    }

    /** «07:00» (para los campos de hora). */
    public function start(): string
    {
        return substr($this->starts_at, 0, 5);
    }

    public function end(): string
    {
        return substr($this->ends_at, 0, 5);
    }

    /** «7:00 – 7:55 a. m.» o «9:45 a. m. – 12:15 p. m.». */
    public function range(): string
    {
        return self::formatRange($this->start(), $this->end());
    }

    public static function formatRange(string $start, string $end): string
    {
        $from = Carbon::createFromFormat('H:i', $start);
        $to = Carbon::createFromFormat('H:i', $end);

        return $from->format('A') === $to->format('A')
            ? $from->format('g:i').' – '.$to->shortTime()
            : $from->shortTime().' – '.$to->shortTime();
    }

    /** ¿Se cruza con otra franja (mismo día)? */
    public function overlaps(self $other): bool
    {
        return $this->start() < $other->end() && $other->start() < $this->end();
    }
}
