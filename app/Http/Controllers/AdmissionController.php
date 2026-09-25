<?php

namespace App\Http\Controllers;

use App\Enums\GuardianRelationship;
use App\Http\Requests\StoreAdmissionRequest;
use App\Mail\AdmissionRequestConfirmation;
use App\Mail\AdmissionRequestReceived;
use App\Models\AdmissionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdmissionController extends Controller
{
    public function show(): View
    {
        $admissions = config('school.admissions');

        return view('admissions.show', [
            'schoolYear' => $admissions['school_year'],
            'costs' => $admissions['costs'],
            'requirements' => $admissions['requirements'],
            'responseTime' => $admissions['response_time'],
            'levels' => config('school.levels'),
            'relationships' => GuardianRelationship::cases(),
        ]);
    }

    public function store(StoreAdmissionRequest $request): RedirectResponse
    {
        // Un robot llenó el campo trampa: respondemos igual, sin guardar nada.
        if ($request->filled('website')) {
            return redirect()->route('admissions.thanks')->with('admission_code', 'PRE-'.config('school.admissions.school_year').'-0000');
        }

        $admission = AdmissionRequest::create([
            ...$request->admissionData(),
            'school_year' => config('school.admissions.school_year'),
            'privacy_accepted_at' => now(),
            'privacy_policy_version' => config('school.privacy_policy_version'),
            'ip_address' => $request->ip(),
        ]);

        Mail::to(config('school.admissions.notify_email'))->queue(new AdmissionRequestReceived($admission));

        if ($admission->guardian_email) {
            Mail::to($admission->guardian_email)->queue(new AdmissionRequestConfirmation($admission));
        }

        return redirect()->route('admissions.thanks')->with('admission_code', $admission->code);
    }

    public function thanks(): View|RedirectResponse
    {
        if (! session()->has('admission_code')) {
            return redirect()->route('admissions');
        }

        return view('admissions.thanks', [
            'code' => session('admission_code'),
            'responseTime' => config('school.admissions.response_time'),
        ]);
    }
}
