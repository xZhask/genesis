<?php

namespace App\Http\Controllers\Admin\People;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\People\GuardianRequest;
use App\Models\Guardian;
use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GuardianController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Guardian::class);

        $account = $request->query('cuenta');

        $guardians = Guardian::query()
            ->search($request->query('q'))
            ->when($account === 'sin-cuenta', fn (Builder $q) => $q->whereNull('user_id'))
            ->when($account === 'pendiente', fn (Builder $q) => $q->whereHas('user', fn ($u) => $u->where('must_change_password', true)))
            ->with(['students', 'user'])
            ->alphabetical()
            ->paginate(30)
            ->withQueryString();

        return view('admin.people.guardians.index', [
            'guardians' => $guardians,
            'account' => $account,
            'total' => Guardian::count(),
            'withoutAccount' => Guardian::whereNull('user_id')->whereHas('students')->count(),
        ]);
    }

    public function edit(Guardian $guardian): View
    {
        $this->authorize('update', $guardian);

        $year = SchoolYear::current();
        $guardian->load(['user', 'students.enrollments' => fn ($q) => $q->where('school_year_id', $year?->id)->with('section.grade')]);

        return view('admin.people.guardians.edit', ['guardian' => $guardian, 'year' => $year]);
    }

    public function update(GuardianRequest $request, Guardian $guardian): RedirectResponse
    {
        $guardian->update($request->validated());

        return back()->with('status_message', 'Se guardaron los datos del acudiente.');
    }

    public function destroy(Guardian $guardian): RedirectResponse
    {
        $this->authorize('delete', $guardian);

        DB::transaction(function () use ($guardian) {
            // Si la cuenta es también de un docente, se conserva
            if ($guardian->user?->role === Role::Guardian) {
                $guardian->user->delete();
            }
            $guardian->delete();
        });

        return redirect()->route('admin.people.guardians.index')->with('status_message', "Se eliminó a {$guardian->fullName()}.");
    }
}
