<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Support\StudentOverview;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Portal del estudiante (desde 6.°): solo su propia información; la URL no lleva identificador. */
class StudentPortalController extends Controller
{
    public function home(Request $request): View
    {
        $student = $request->user()->student;
        abort_unless($student, 403);

        return view('portal.student.show', [
            'overview' => new StudentOverview($student, SchoolYear::current()),
            'siblings' => collect(),
        ]);
    }
}
