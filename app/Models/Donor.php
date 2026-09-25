<?php

namespace App\Models;

use App\Models\Concerns\IsListed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donor extends Model
{
    use HasFactory, IsListed;

    protected $fillable = ['name', 'description', 'website', 'is_visible', 'position'];

    protected function casts(): array
    {
        return ['consent_at' => 'datetime'];
    }
}
