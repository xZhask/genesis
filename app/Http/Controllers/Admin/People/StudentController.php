<?php

namespace App\Http\Controllers\Admin\People;

use App\Enums\DocumentType;
use App\Enums\EnrollmentStatus;
use App\Enums\GuardianRelationship;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\People\StudentRequest;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Student::class);

        $years = SchoolYear::orderByDesc('year')->get();
        $year = $years->firstWhere('year', (int) $request->query('lectivo')) ?? $years->firstWhere('is_current', true) ?? $years->first();
        $section = $request->query('seccion');

        $students = Student::query()
            ->search($request->query('q'))
            ->when($year && $section === 'sin-matricula', fn (Builder $q) => $q->whereDoesntHave('enrollments', fn ($e) => $e->where('school_year_id', $year->id)))
            ->when($year && ctype_digit((string) $section), fn (Builder $q) => $q->whereHas('enrollments', fn ($e) => $e->where('section_id', $section)))
            ->with([
                'enrollments' => fn ($q) => $q->where('school_year_id', $year?->id)->with('section.grade'),
                'user',
            ])
            ->withCount('guardians')
            ->alphabetical()
            ->paginate(30)
            ->withQueryString();

        return view('admin.people.students.index', [
            'students' => $students,
            'years' => $years,
            'year' => $year,
            'sections' => $this->sectionsByLevel($year),
            'section' => $section,
            'total' => Student::count(),
            'awaitingAccount' => $year?->is_current ? Student::awaitingAccount($year)->count() : 0,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Student::class);
        $year = SchoolYear::current();

        return view('admin.people.students.create', [
            'student' => new Student(['document_type' => DocumentType::IdentityCard]),
            'year' => $year,
            'sections' => $this->sectionsByLevel($year),
        ]);
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $student = DB::transaction(function () use ($request) {
            $student = Student::create($request->safe()->except('section_id'));
            if ($request->filled('section_id')) {
                Enrollment::place($student, Section::findOrFail($request->input('section_id')));
            }

            return $student;
        });

        return redirect()->route('admin.people.students.edit', $student)
            ->with('status_message', "Se registró a {$student->fullName()}. Ahora vincula a sus acudientes.");
    }

    public function edit(Student $student): View
    {
        $this->authorize('update', $student);

        $year = SchoolYear::current();
        $student->load(['guardians.user', 'enrollments' => fn ($q) => $q->with('section.grade', 'schoolYear')->latest('school_year_id'), 'user']);

        return view('admin.people.students.edit', [
            'student' => $student,
            'year' => $year,
            'enrollment' => $student->enrollmentFor($year),
            'sections' => $this->sectionsByLevel($year),
            'relationships' => GuardianRelationship::cases(),
            'canHaveAccount' => $student->canHaveAccount($year),
            'accountsFrom' => config('school.academic.student_accounts_from'),
        ]);
    }

    public function update(StudentRequest $request, Student $student): RedirectResponse
    {
        $student->update($request->validated());

        return back()->with('status_message', 'Se guardaron los datos del estudiante.');
    }

    /** Matrícula del año actual: sección y estado (matriculado o retirado). */
    public function enroll(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('update', $student);
        $year = SchoolYear::current();
        abort_unless($year, 404);

        $data = $request->validate([
            'section_id' => ['required', Rule::exists('sections', 'id')->where('school_year_id', $year->id)],
            'status' => ['required', Rule::enum(EnrollmentStatus::class)],
        ], ['section_id.required' => 'Elige la sección.']);

        $enrollment = Enrollment::place($student, Section::findOrFail($data['section_id']));
        $status = EnrollmentStatus::from($data['status']);
        $enrollment->forceFill([
            'status' => $status,
            'withdrawn_on' => $status === EnrollmentStatus::Withdrawn ? ($enrollment->withdrawn_on ?? today()) : null,
        ])->save();

        return back()->with('status_message', "Se guardó la matrícula {$year->year}: {$enrollment->section->label()}, {$status->label()}.");
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->authorize('delete', $student);

        DB::transaction(function () use ($student) {
            // Su cuenta del portal ya no tiene uso
            if ($student->user?->role === Role::Student) {
                $student->user->delete();
            }
            $student->delete();
        });

        return redirect()->route('admin.people.students.index')->with('status_message', "Se eliminó a {$student->fullName()}.");
    }

    /**
     * Secciones del año agrupadas por nivel, para los selectores.
     *
     * @return Collection<string, Collection<int, Section>>
     */
    private function sectionsByLevel(?SchoolYear $year): Collection
    {
        if (! $year) {
            return collect();
        }

        $levels = collect(config('school.levels'))->pluck('name', 'key');

        return Section::where('school_year_id', $year->id)
            ->with('grade')
            ->get()
            ->sortBy(fn (Section $s) => [$s->grade->position, $s->name])
            ->groupBy(fn (Section $s) => $levels[$s->grade->level] ?? $s->grade->level);
    }
}
