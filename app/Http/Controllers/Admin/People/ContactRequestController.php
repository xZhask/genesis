<?php

namespace App\Http\Controllers\Admin\People;

use App\Enums\ContactField;
use App\Http\Controllers\Controller;
use App\Models\ContactUpdateRequest;
use App\Support\FamilyMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Revisión de los cambios de teléfono y correo que piden los acudientes. */
class ContactRequestController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', ContactUpdateRequest::class);

        $with = ['guardian.students', 'guardian.user', 'reviewer'];

        return view('admin.people.contact-requests.index', [
            'pending' => ContactUpdateRequest::pending()->with($with)->oldest('id')->get(),
            'history' => ContactUpdateRequest::whereNot('status', 'pending')->with($with)->latest('reviewed_at')->paginate(20),
        ]);
    }

    public function approve(Request $request, ContactUpdateRequest $contactRequest): RedirectResponse
    {
        $this->authorize('update', $contactRequest);

        $previousEmail = FamilyMail::address($contactRequest->guardian);
        $contactRequest->approve($request->user());
        $contactRequest->refresh()->guardian->refresh();
        FamilyMail::queueContactReview($contactRequest, $contactRequest->field === ContactField::Email ? $previousEmail : null);

        return back()->with('status_message', "Se actualizó el {$this->fieldName($contactRequest)} de {$contactRequest->guardian->fullName()}.");
    }

    public function reject(Request $request, ContactUpdateRequest $contactRequest): RedirectResponse
    {
        $this->authorize('update', $contactRequest);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:300']], [], ['note' => 'motivo']);

        $contactRequest->reject($request->user(), $data['note'] ?? null);
        FamilyMail::queueContactReview($contactRequest);

        return back()->with('status_message', "Se rechazó el cambio de {$this->fieldName($contactRequest)} de {$contactRequest->guardian->fullName()}.");
    }

    private function fieldName(ContactUpdateRequest $contactRequest): string
    {
        return mb_strtolower($contactRequest->field->label());
    }
}
