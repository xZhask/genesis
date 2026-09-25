<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Support\StudentOverview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Portal del acudiente: solo sus acudidos. También lo usan los docentes que
 * son acudientes (su cuenta está vinculada a un registro de acudiente).
 */
class GuardianPortalController extends Controller
{
    public function home(Request $request): View|RedirectResponse
    {
        $guardian = $request->user()->guardian;
        abort_unless($guardian, 403);

        $year = SchoolYear::current();
        $students = $guardian->students()->get();

        // Con un solo acudido, se va directo a su información
        if ($students->count() === 1) {
            return redirect()->route('portal.guardian.student', $students->first());
        }

        return view('portal.guardian.home', [
            'overviews' => $students->map(fn (Student $s) => new StudentOverview($s, $year)),
            'year' => $year,
        ]);
    }

    public function student(Request $request, Student $student): View
    {
        abort_unless($request->user()->guardian, 403);
        $this->authorize('view', $student);

        return view('portal.student.show', [
            'overview' => new StudentOverview($student, SchoolYear::current()),
            'siblings' => $request->user()->guardian->students()->whereKeyNot($student->id)->get(),
        ]);
    }
}
