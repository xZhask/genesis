<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Period;
use App\Models\Section;
use App\Models\Student;
use App\Support\ReportCard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Boletín en PDF (primaria y secundaria). Solo de periodos cerrados: sale de
 * las notas congeladas al cerrar. Se genera al momento y no se guarda.
 */
class ReportCardController extends Controller
{
    public function student(Request $request, Student $student, Period $period): Response
    {
        abort_unless($period->isClosed(), 404, 'El boletín está disponible cuando el colegio cierra el periodo.');

        $enrollment = Enrollment::where('student_id', $student->id)->where('school_year_id', $period->school_year_id)
            ->with('section.grade')->first();
        abort_unless($enrollment, 404);
        $this->authorize('downloadReportCard', [$student, $enrollment]);
        abort_if($enrollment->section->grade->isPreschool(), 404);

        $name = Str::slug("boletin-{$period->schoolYear->year}-p{$period->number}-{$student->last_names}-{$student->first_names}");

        return $this->pdf([new ReportCard($enrollment, $period)], $name, $request->boolean('descargar'));
    }

    /** Todos los boletines de una sección, para imprimir (director de grupo o admin). */
    public function section(Request $request, Section $section, Period $period): Response
    {
        $user = $request->user();
        abort_unless($user->is_active && ($user->isAdmin() || $section->homeroom_teacher_id === $user->id), 403);
        abort_unless($period->isClosed() && $period->school_year_id === $section->school_year_id, 404);
        abort_if($section->grade->isPreschool(), 404);

        $cards = $section->enrollments()->with('student')->get()
            ->sortBy(fn ($e) => $e->student->sortName(), SORT_NATURAL | SORT_FLAG_CASE)
            ->map(fn ($e) => new ReportCard($e, $period))
            ->values()
            ->all();
        abort_if($cards === [], 404);

        return $this->pdf($cards, Str::slug("boletines-{$period->schoolYear->year}-p{$period->number}-{$section->label()}"), true);
    }

    /** @param  list<ReportCard>  $cards */
    private function pdf(array $cards, string $name, bool $download): Response
    {
        // Un grupo completo puede tardar unos segundos en un hosting compartido
        set_time_limit(120);

        $pdf = Pdf::loadView('pdf.report-card', ['cards' => $cards, 'school' => config('school')])
            ->setPaper('letter')
            ->setOption(['defaultFont' => 'Figtree', 'isRemoteEnabled' => false, 'dpi' => 96]);

        return $download ? $pdf->download("{$name}.pdf") : $pdf->stream("{$name}.pdf");
    }
}
