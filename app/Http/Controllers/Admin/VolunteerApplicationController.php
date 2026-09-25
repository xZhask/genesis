<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VolunteerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateVolunteerApplicationRequest;
use App\Models\VolunteerApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VolunteerApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', VolunteerApplication::class);

        $status = VolunteerStatus::tryFrom((string) $request->query('estado'));

        return view('admin.support.volunteers.index', [
            'applications' => VolunteerApplication::query()
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'status' => $status,
            'counts' => VolunteerApplication::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    public function show(VolunteerApplication $volunteer): View
    {
        $this->authorize('view', $volunteer);

        return view('admin.support.volunteers.show', [
            'application' => $volunteer,
            'statuses' => VolunteerStatus::cases(),
        ]);
    }

    public function update(UpdateVolunteerApplicationRequest $request, VolunteerApplication $volunteer): RedirectResponse
    {
        $volunteer->status = VolunteerStatus::from($request->validated('status'));
        $volunteer->admin_note = $request->validated('admin_note');
        $volunteer->save();

        return redirect()
            ->route('admin.volunteers.show', $volunteer)
            ->with('status_message', 'Se guardaron los cambios.');
    }
}
