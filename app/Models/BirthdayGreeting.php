<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Tarjeta de cumpleaños: una por persona (estudiante o personal) y cumpleaños. */
class BirthdayGreeting extends Model
{
    protected $fillable = ['celebrant_type', 'celebrant_id', 'celebrated_on', 'email', 'guardian_id', 'sent_at'];

    protected function casts(): array
    {
        return [
            'celebrated_on' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function celebrant(): MorphTo
    {
        return $this->morphTo();
    }

    /** Si la tarjeta de un estudiante va al correo de su acudiente. */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }
}
