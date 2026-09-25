<?php

namespace App\Models;

use App\Enums\AdmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionRequestStatusChange extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['from_status', 'to_status', 'interview_at', 'note', 'changed_by'];

    protected function casts(): array
    {
        return [
            'from_status' => AdmissionStatus::class,
            'to_status' => AdmissionStatus::class,
            'interview_at' => 'datetime',
        ];
    }

    public function admissionRequest(): BelongsTo
    {
        return $this->belongsTo(AdmissionRequest::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
