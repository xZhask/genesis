<?php

namespace App\Models;

use App\Enums\PeriodStatus;
use App\Exceptions\PeriodClosedException;
use App\Support\PeriodResults;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Period extends Model
{
    protected $fillable = ['number', 'starts_on', 'ends_on', 'weight'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'weight' => 'decimal:2',
            'status' => PeriodStatus::class,
            'closed_at' => 'datetime',
        ];
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function statusChanges(): HasMany
    {
        return $this->hasMany(PeriodStatusChange::class)->latest('id');
    }

    public function isClosed(): bool
    {
        return $this->status === PeriodStatus::Closed;
    }

    public function name(): string
    {
        return "Periodo {$this->number}";
    }

    /** Periodo cerrado = solo lectura (regla de seguridad 3). Queda registrado quién y cuándo. */
    public function close(User $user): void
    {
        $this->changeStatus(PeriodStatus::Closed, $user);
    }

    public function reopen(User $user): void
    {
        $this->changeStatus(PeriodStatus::Open, $user);
    }

    /**
     * Para escribir notas, logros o comportamiento: vuelve a leer el periodo
     * con bloqueo (dentro de una transacción) y falla si está cerrado.
     *
     * @throws PeriodClosedException
     */
    public function ensureOpen(): void
    {
        if (static::lockForUpdate()->find($this->id)?->isClosed() ?? true) {
            throw new PeriodClosedException($this);
        }
    }

    private function changeStatus(PeriodStatus $status, User $user): void
    {
        DB::transaction(function () use ($status, $user) {
            $this->forceFill([
                'status' => $status,
                'closed_by' => $status === PeriodStatus::Closed ? $user->id : null,
                'closed_at' => $status === PeriodStatus::Closed ? now() : null,
            ])->save();

            $this->statusChanges()->create(['status' => $status, 'user_id' => $user->id]);

            // Al cerrar se congelan las notas; al reabrir vuelven a calcularse en vivo
            $status === PeriodStatus::Closed ? PeriodResults::freeze($this) : PeriodResults::clear($this);
        });
    }
}
