<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SchoolYear extends Model
{
    use HasFactory;

    protected $fillable = ['year', 'starts_on', 'ends_on'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public static function current(): ?self
    {
        return static::where('is_current', true)->first();
    }

    public function periods(): HasMany
    {
        return $this->hasMany(Period::class)->orderBy('number');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /** Solo un año es el actual: el que usan el portal y las listas por defecto. */
    public function makeCurrent(): void
    {
        DB::transaction(function () {
            static::where('is_current', true)->update(['is_current' => false]);
            $this->forceFill(['is_current' => true])->save();
        });
    }

    /**
     * Crea los periodos repartiendo el año en partes iguales (fechas y pesos
     * provisionales; el admin los ajusta después).
     */
    public function createPeriods(int $count): void
    {
        $days = $this->starts_on->diffInDays($this->ends_on) + 1;
        $weight = round(100 / $count, 2);

        for ($n = 1; $n <= $count; $n++) {
            $start = $this->starts_on->copy()->addDays((int) floor($days * ($n - 1) / $count));
            $end = $n === $count
                ? $this->ends_on->copy()
                : $this->starts_on->copy()->addDays((int) floor($days * $n / $count) - 1);

            $this->periods()->create([
                'number' => $n,
                'starts_on' => $start,
                'ends_on' => $end,
                // El último absorbe el redondeo para que sumen 100
                'weight' => $n === $count ? 100 - $weight * ($count - 1) : $weight,
            ]);
        }
    }

    /** Periodo que contiene la fecha (o null si cae fuera del año). */
    public function periodFor(Carbon $date): ?Period
    {
        return $this->periods->first(fn (Period $p) => $date->betweenIncluded($p->starts_on, $p->ends_on));
    }
}
