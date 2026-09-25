<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ContactField;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ContactUpdateRequestRequest;
use App\Models\ContactUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * «Mis datos» del acudiente: ve su teléfono y su correo y pide cambiarlos.
 * El cambio no se aplica hasta que el admin lo aprueba.
 */
class ContactController extends Controller
{
    public function show(Request $request): View
    {
        $guardian = $request->user()->guardian;
        abort_unless($guardian, 403);

        // Por cada dato, solo interesa la solicitud más reciente
        $latest = $guardian->contactRequests()->get()->unique(fn ($r) => $r->field->value)->keyBy(fn ($r) => $r->field->value);

        return view('portal.guardian.contact', ['guardian' => $guardian, 'latest' => $latest]);
    }

    public function store(ContactUpdateRequestRequest $request): RedirectResponse
    {
        $guardian = $request->user()->guardian;

        $created = collect(ContactField::cases())
            ->filter(fn (ContactField $field) => filled($request->validated($field->value)))
            ->map(fn (ContactField $field) => ContactUpdateRequest::submit($guardian, $field, $request->validated($field->value)))
            ->filter();

        return redirect()->route('portal.guardian.contact')->with('status_message', $created->isEmpty()
            ? 'No hay cambios nuevos por revisar.'
            : 'Recibimos tu solicitud. El colegio la revisará y verás aquí cuando se apruebe.');
    }
}
