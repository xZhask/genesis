<?php

namespace App\Models;

use App\Enums\VolunteerArea;
use App\Enums\VolunteerAvailability;
use App\Enums\VolunteerStatus;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VolunteerApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'phone', 'phone_has_whatsapp', 'email', 'areas', 'availability', 'message',
        'privacy_accepted_at', 'privacy_policy_version', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'areas' => AsEnumCollection::of(VolunteerArea::class),
            'availability' => VolunteerAvailability::class,
            'status' => VolunteerStatus::class,
            'phone_has_whatsapp' => 'boolean',
            'privacy_accepted_at' => 'datetime',
        ];
    }

    public function areasLabel(): string
    {
        return $this->areas->map->label()->implode(', ');
    }

    /** +573217975579 → "+57 321 797 5579". */
    public function formattedPhone(): string
    {
        $digits = substr($this->phone, 3);

        return strlen($digits) === 10
            ? '+57 '.substr($digits, 0, 3).' '.substr($digits, 3, 3).' '.substr($digits, 6)
            : $this->phone;
    }
}
