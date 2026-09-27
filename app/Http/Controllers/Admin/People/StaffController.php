<?php

namespace App\Http\Controllers\Admin\People;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\People\StaffRequest;
use App\Models\SchoolYear;
use App\Models\User;
use App\Support\Accounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Docentes y administración. No se eliminan: se desactivan. */
class StaffController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-people');

        return view('admin.people.staff.index', [
            'staff' => User::whereIn('role', [Role::Teacher, Role::Admin])
                ->with('guardian')
                ->orderBy('role', 'desc')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get()
                ->groupBy(fn (User $u) => $u->role->value),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage-people');

        $user = new User;
        $user->role = Role::Teacher;

        return view('admin.people.staff.form', ['user' => $user]);
    }

    public function store(StaffRequest $request): RedirectResponse
    {
        $password = Accounts::temporaryPassword();
        $user = new User([...$request->safe()->except(['role', 'can_review_feedback']), 'password' => $password]);
        $user->role = Role::from($request->input('role'));
        $user->must_change_password = true;
        $this->applyFeedbackAccess($request, $user);
        $user->save();

        return redirect()->route('admin.people.staff.edit', $user)
            ->with('status_message', "Se creó la cuenta de {$user->name}.")
            ->with('credentials', [Accounts::slip($user, $password)]);
    }

    public function edit(User $user): View
    {
        Gate::authorize('manage-people');
        abort_unless($user->hasRole(Role::Teacher, Role::Admin), 404);

        $year = SchoolYear::current();
        $user->load(['guardian.students', 'assignments' => fn ($q) => $q->whereHas('section', fn ($s) => $s->where('school_year_id', $year?->id))->with('section.grade', 'subject')]);

        return view('admin.people.staff.form', ['user' => $user, 'year' => $year]);
    }

    public function update(StaffRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->hasRole(Role::Teacher, Role::Admin), 404);

        $role = Role::from($request->input('role'));
        if ($user->is($request->user()) && $role !== Role::Admin) {
            return back()->withErrors(['role' => 'No puedes quitarte el rol de administración a ti mismo.'])->withInput();
        }

        $user->fill($request->safe()->except(['role', 'can_review_feedback']));
        $user->role = $role;
        $this->applyFeedbackAccess($request, $user);
        if ($user->isDirty('can_review_feedback') && ! $user->can_review_feedback && $user->is($request->user())
            && User::feedbackReviewers()->whereKeyNot($user->id)->doesntExist()) {
            return back()->withErrors(['can_review_feedback' => 'Eres la única cuenta que lee el buzón. Autoriza primero a otra persona.'])->withInput();
        }
        $user->save();

        return back()->with('status_message', 'Se guardaron los datos de la cuenta.');
    }

    /**
     * Quién lee el buzón de sugerencias: solo lo decide quien ya lo lee
     * (así otro admin no puede darse el permiso a sí mismo). Un docente
     * nunca lo tiene.
     */
    private function applyFeedbackAccess(StaffRequest $request, User $user): void
    {
        if ($user->role !== Role::Admin) {
            $user->can_review_feedback = false;
        } elseif ($request->user()->canReviewFeedback()) {
            $user->can_review_feedback = $request->boolean('can_review_feedback');
        }
    }
}
