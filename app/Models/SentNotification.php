<?php

namespace App\Models;

use App\Enums\FamilyNotice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Bandeja de salida de los correos a las familias: una fila por aviso, sin repetir. */
class SentNotification extends Model
{
    protected $fillable = ['type', 'guardian_id', 'subject_type', 'subject_id', 'email', 'sent_at'];

    protected function casts(): array
    {
        return [
            'type' => FamilyNotice::class,
            'sent_at' => 'datetime',
        ];
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
