<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\AdmissionRequest;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $this->authorize('viewAny', AdmissionRequest::class);

        $counts = AdmissionRequest::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.dashboard', [
            'counts' => $counts,
            'pending' => ($counts[AdmissionStatus::Received->value] ?? 0),
            'upcomingInterviews' => AdmissionRequest::where('status', AdmissionStatus::InterviewScheduled)
                ->where('interview_at', '>=', now()->startOfDay())
                ->orderBy('interview_at')
                ->limit(5)
                ->get(),
            'latest' => AdmissionRequest::latest('id')->limit(5)->get(),
        ]);
    }
}
