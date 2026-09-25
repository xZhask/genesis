<?php

namespace App\Http\Controllers\Admin\People;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use App\Support\Accounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Cuentas del portal: el colegio las crea y entrega una contraseña temporal
 * (se muestra una sola vez, para imprimirla o dictarla).
 */
class AccountController extends Controller
{
    public function storeForStudent(Student $student): RedirectResponse
    {
        Gate::authorize('manage-people');

        if (! $student->canHaveAccount(SchoolYear::current())) {
            return back()->withErrors(['account' => 'Los estudiantes tienen cuenta desde '.config('school.academic.student_accounts_from').' y deben estar matriculados en el año actual.']);
        }

        return $this->create($student);
    }

    public function storeForGuardian(Guardian $guardian): RedirectResponse
    {
        Gate::authorize('manage-people');

        return $this->create($guardian);
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-people');
        abort_if($user->is($request->user()), 403, 'Cambia tu propia contraseña desde tu cuenta.');

        $password = Accounts::resetPassword($user);

        return back()
            ->with('status_message', "Se generó una contraseña temporal para {$user->name}. La anterior ya no sirve.")
            ->with('credentials', [Accounts::slip($user, $password)]);
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-people');
        abort_if($user->is($request->user()), 403, 'No puedes desactivar tu propia cuenta.');

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        return back()->with('status_message', $user->is_active
            ? "Se activó la cuenta de {$user->name}."
            : "Se desactivó la cuenta de {$user->name}: ya no puede ingresar.");
    }

    /**
     * Crea de una vez las cuentas que faltan (acudientes, o estudiantes desde
     * 6.°) y muestra la hoja para imprimir. Las contraseñas no se guardan en
     * claro: si se pierde la hoja, se genera otra por persona.
     */
    public function bulk(Request $request): View|RedirectResponse
    {
        Gate::authorize('manage-people');

        $type = $request->validate(['type' => ['required', Rule::in(['guardians', 'students'])]])['type'];
        $year = SchoolYear::current();

        $people = $type === 'guardians'
            ? Guardian::whereNull('user_id')->whereHas('students')->alphabetical()->get()
            : Student::awaitingAccount($year)->alphabetical()->get();

        $slips = [];
        $skipped = [];
        foreach ($people as $person) {
            try {
                ['user' => $user, 'password' => $password] = Accounts::createFor($person);
                if ($password) {
                    $slips[] = Accounts::slip($user, $password);
                }
            } catch (InvalidArgumentException $e) {
                $skipped[] = "{$person->fullName()}: {$e->getMessage()}";
            }
        }

        if (! $slips && ! $skipped) {
            return back()->with('status_message', 'No hay cuentas pendientes por crear.');
        }

        return view('admin.people.accounts.slips', [
            'slips' => $slips,
            'skipped' => $skipped,
            'title' => $type === 'guardians' ? 'Cuentas de acudientes' : 'Cuentas de estudiantes',
        ]);
    }

    private function create(Student|Guardian $person): RedirectResponse
    {
        try {
            ['user' => $user, 'password' => $password] = Accounts::createFor($person);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['account' => $e->getMessage()]);
        }

        if (! $password) {
            $role = mb_strtolower($user->role->label());

            return back()->with('status_message', "{$person->fullName()} ya tenía cuenta como {$role}: se usará la misma para ver a sus acudidos.");
        }

        return back()
            ->with('status_message', "Se creó la cuenta de {$user->name}.")
            ->with('credentials', [Accounts::slip($user, $password)]);
    }
}
