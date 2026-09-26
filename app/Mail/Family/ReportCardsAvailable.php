<?php

namespace App\Mail\Family;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Period;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** «El boletín ya está disponible»: sin notas, solo el enlace al portal. */
class ReportCardsAvailable extends FamilyMailable
{
    public function __construct(Guardian $guardian, public Period $period)
    {
        parent::__construct($guardian);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Boletín del {$this->periodName()} disponible en el portal");
    }

    public function content(): Content
    {
        $children = Enrollment::whereIn('student_id', $this->guardian->students()->pluck('students.id'))
            ->where('school_year_id', $this->period->school_year_id)
            ->where('status', EnrollmentStatus::Active)
            ->with(['student', 'section.grade'])
            ->get();

        return new Content(view: 'mail.family.report-cards', with: $this->common() + [
            'periodName' => $this->periodName(),
            'year' => $this->period->schoolYear->year,
            'children' => $children,
        ]);
    }

    private function periodName(): string
    {
        return mb_strtolower($this->period->name());
    }
}
