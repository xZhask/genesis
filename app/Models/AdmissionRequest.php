<?php

namespace App\Models;

use App\Enums\AdmissionStatus;
use App\Enums\GuardianRelationship;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdmissionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_year',
        'grade',
        'student_first_names',
        'student_last_names',
        'student_birth_date',
        'current_school',
        'guardian_name',
        'guardian_relationship',
        'guardian_phone',
        'phone_has_whatsapp',
        'guardian_email',
        'comments',
        'privacy_accepted_at',
        'privacy_policy_version',
        'ip_address',
    ];

    protected $attributes = [
        'status' => 'received',
    ];

    protected function casts(): array
    {
        return [
            'status' => AdmissionStatus::class,
            'guardian_relationship' => GuardianRelationship::class,
            'student_birth_date' => 'date',
            'phone_has_whatsapp' => 'boolean',
            'privacy_accepted_at' => 'datetime',
            'interview_at' => 'datetime',
        ];
    }

    public function statusChanges(): HasMany
    {
        return $this->hasMany(AdmissionRequestStatusChange::class)->latest('id');
    }

    /**
     * Cambia el estado y deja constancia de quién lo hizo, cuándo y por qué.
     */
    public function changeStatus(AdmissionStatus $to, User $by, ?Carbon $interviewAt = null, ?string $note = null): void
    {
        DB::transaction(function () use ($to, $by, $interviewAt, $note) {
            $this->statusChanges()->create([
                'from_status' => $this->status,
                'to_status' => $to,
                'interview_at' => $interviewAt,
                'note' => $note,
                'changed_by' => $by->id,
            ]);

            $this->status = $to;
            if ($to === AdmissionStatus::InterviewScheduled) {
                $this->interview_at = $interviewAt;
            }
            $this->save();
        });
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);
        if ($term === '') {
            return;
        }

        $digits = preg_replace('/\D/', '', $term);

        $query->where(function (Builder $q) use ($term, $digits) {
            $q->where('code', 'like', "%{$term}%")
                ->orWhere('student_first_names', 'like', "%{$term}%")
                ->orWhere('student_last_names', 'like', "%{$term}%")
                ->orWhere('guardian_name', 'like', "%{$term}%");

            if (strlen($digits) >= 4) {
                $q->orWhere('guardian_phone', 'like', "%{$digits}%");
            }
        });
    }

    protected static function booted(): void
    {
        // Código legible para la familia y el colegio: PRE-2027-0042
        static::created(function (self $request) {
            $request->code = sprintf('PRE-%d-%04d', $request->school_year, $request->id);
            $request->saveQuietly();
        });
    }

    public function studentFullName(): string
    {
        return "{$this->student_first_names} {$this->student_last_names}";
    }

    /** Teléfono en formato de lectura: +57 321 797 5579 */
    public function formattedPhone(): string
    {
        $digits = substr($this->guardian_phone, 3);

        return strlen($digits) === 10
            ? '+57 '.substr($digits, 0, 3).' '.substr($digits, 3, 3).' '.substr($digits, 6)
            : $this->guardian_phone;
    }
}
