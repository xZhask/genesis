<?php

namespace App\Models;

use App\Models\Concerns\IsListed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasFactory, IsListed;

    protected $fillable = ['quote', 'author', 'role', 'is_visible', 'position'];

    protected function casts(): array
    {
        return ['consent_at' => 'datetime'];
    }

    /** "Ana Pérez, acudiente y voluntaria". */
    public function signature(): string
    {
        return $this->role ? "{$this->author}, {$this->role}" : $this->author;
    }
}
