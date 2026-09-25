<?php

namespace App\Models;

use App\Enums\AdmissionStatus;
use App\Enums\GuardianRelationship;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        ];
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
