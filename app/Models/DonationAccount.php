<?php

namespace App\Models;

use App\Models\Concerns\IsListed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DonationAccount extends Model
{
    use HasFactory, IsListed;

    protected $fillable = ['label', 'number', 'holder', 'holder_document', 'is_visible', 'position'];

    /** Línea del titular: "Centro Educativo Cristiano Génesis · NIT 900…". */
    public function holderLine(): ?string
    {
        $parts = array_filter([$this->holder, $this->holder_document]);

        return $parts ? implode(' · ', $parts) : null;
    }
}
