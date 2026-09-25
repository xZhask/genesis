<?php

namespace App\Models;

use App\Enums\PeriodStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodStatusChange extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['status', 'user_id'];

    protected function casts(): array
    {
        return ['status' => PeriodStatus::class];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
