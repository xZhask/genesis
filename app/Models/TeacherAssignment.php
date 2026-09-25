<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un docente dicta una materia en una sección (del año de esa sección). */
class TeacherAssignment extends Model
{
    protected $fillable = ['teacher_id', 'section_id', 'subject_id'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** "Matemáticas · 7.° 1". */
    public function label(): string
    {
        return "{$this->subject->name} · {$this->section->label()}";
    }
}
