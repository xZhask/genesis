<?php

namespace App\Http\Controllers\Admin\People;

use App\Enums\GuardianRelationship;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\People\LinkGuardianRequest;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Acudientes de un estudiante: vincular, cambiar parentesco o principal y desvincular. */
class StudentGuardianController extends Controller
{
    public function store(LinkGuardianRequest $request, Student $student): RedirectResponse
    {
        $guardian = $request->existingGuardian();
        $existed = (bool) $guardian;

        if ($guardian && $student->guardians()->whereKey($guardian->id)->exists()) {
            return back()->withErrors(['guardian_document_number' => 'Esa persona ya es acudiente de este estudiante.'])->withInput();
        }

        DB::transaction(function () use ($request, $student, &$guardian) {
            $guardian ??= Guardian::create($request->guardianData());

            // El primer acudiente es el principal
            $primary = $request->boolean('is_primary') || ! $student->guardians()->exists();
            if ($primary) {
                $student->guardians()->updateExistingPivot($student->guardians()->pluck('guardians.id'), ['is_primary' => false]);
            }

            $student->guardians()->attach($guardian->id, [
                'relationship' => $request->input('relationship'),
                'is_primary' => $primary,
            ]);
        });

        $message = $existed
            ? "{$guardian->fullName()} ya estaba registrado (acudiente de otro estudiante) y quedó vinculado."
            : "Se vinculó a {$guardian->fullName()} como acudiente.";

        return back()->with('status_message', $message);
    }

    public function update(Request $request, Student $student, Guardian $guardian): RedirectResponse
    {
        $this->authorize('update', $student);
        abort_unless($student->guardians()->whereKey($guardian->id)->exists(), 404);

        $data = $request->validate([
            'relationship' => ['required', Rule::enum(GuardianRelationship::class)],
        ]);

        DB::transaction(function () use ($request, $student, $guardian, $data) {
            if ($request->boolean('is_primary')) {
                $student->guardians()->updateExistingPivot($student->guardians()->pluck('guardians.id'), ['is_primary' => false]);
                $data['is_primary'] = true;
            }
            $student->guardians()->updateExistingPivot($guardian->id, $data);
        });

        return back()->with('status_message', "Se actualizó el vínculo con {$guardian->fullName()}.");
    }

    public function destroy(Student $student, Guardian $guardian): RedirectResponse
    {
        $this->authorize('update', $student);

        $wasPrimary = (bool) $student->guardians()->whereKey($guardian->id)->first()?->pivot->is_primary;
        $student->guardians()->detach($guardian->id);

        // Si era el principal, pasa a serlo el siguiente
        if ($wasPrimary && $next = $student->guardians()->first()) {
            $student->guardians()->updateExistingPivot($next->id, ['is_primary' => true]);
        }

        return back()->with('status_message', "{$guardian->fullName()} ya no es acudiente de {$student->fullName()}.");
    }
}
