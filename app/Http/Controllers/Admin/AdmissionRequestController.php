<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdmissionStatusRequest;
use App\Models\AdmissionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AdmissionRequestController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AdmissionRequest::class);

        $status = AdmissionStatus::tryFrom((string) $request->query('estado'));
        $year = $request->integer('anio') ?: null;

        $base = AdmissionRequest::query()
            ->when($year, fn ($q) => $q->where('school_year', $year))
            ->search($request->query('q'));

        return view('admin.admissions.index', [
            'admissions' => (clone $base)
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'counts' => (clone $base)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'years' => AdmissionRequest::distinct()->orderByDesc('school_year')->pluck('school_year'),
            'status' => $status,
            'year' => $year,
            'search' => $request->query('q'),
        ]);
    }

    public function show(AdmissionRequest $admission): View
    {
        $this->authorize('view', $admission);

        return view('admin.admissions.show', [
            'admission' => $admission->load('statusChanges.author'),
            'statuses' => AdmissionStatus::cases(),
        ]);
    }

    public function updateStatus(UpdateAdmissionStatusRequest $request, AdmissionRequest $admission): RedirectResponse
    {
        $this->authorize('update', $admission);

        $admission->changeStatus(
            to: $request->enum('status', AdmissionStatus::class),
            by: $request->user(),
            interviewAt: $request->filled('interview_at') ? Carbon::parse($request->input('interview_at')) : null,
            note: $request->input('note'),
        );

        return redirect()
            ->route('admin.admissions.show', $admission)
            ->with('status_message', "Estado actualizado a «{$admission->status->label()}».");
    }
}
