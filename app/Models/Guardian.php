<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Models\Concerns\IsPerson;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Acudiente: madre, padre u otra persona responsable. Puede tener varios acudidos. */
class Guardian extends Model
{
    use HasFactory, IsPerson;

    protected $fillable = ['document_type', 'document_number', 'first_names', 'last_names', 'phone', 'email'];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
        ];
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)
            ->using(GuardianStudent::class)
            ->withPivot(['relationship', 'is_primary'])
            ->withTimestamps()
            ->orderBy('last_names')
            ->orderBy('first_names');
    }

    /** Pedidos de cambio de teléfono o correo hechos desde el portal. */
    public function contactRequests(): HasMany
    {
        return $this->hasMany(ContactUpdateRequest::class)->latest('id');
    }
}
