<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    protected $fillable = ['area_id', 'name', 'position'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function grades(): BelongsToMany
    {
        return $this->belongsToMany(Grade::class)->withPivot('weekly_hours')->withTimestamps();
    }
}
