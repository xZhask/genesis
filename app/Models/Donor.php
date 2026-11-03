<?php

namespace App\Models;

use App\Models\Concerns\IsListed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Donor extends Model
{
    use HasFactory, IsListed;

    protected $fillable = ['name', 'description', 'website', 'is_visible', 'position'];

    protected function casts(): array
    {
        return ['consent_at' => 'datetime', 'photo_consent_at' => 'datetime'];
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    /** Inicial que reemplaza a la foto en el muro de agradecimiento. */
    public function initial(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 1));
    }
}
