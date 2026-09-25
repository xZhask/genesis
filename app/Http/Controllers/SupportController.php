<?php

namespace App\Http\Controllers;

use App\Enums\VolunteerArea;
use App\Enums\VolunteerAvailability;
use App\Http\Requests\StoreVolunteerApplicationRequest;
use App\Mail\VolunteerApplicationReceived;
use App\Models\DonationAccount;
use App\Models\Donor;
use App\Models\Testimonial;
use App\Models\VolunteerApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function show(): View
    {
        return view('support.show', [
            'accounts' => DonationAccount::visible()->get(),
            'donors' => Donor::visible()->get(),
            'testimonials' => Testimonial::visible()->limit(3)->get(),
            'areas' => VolunteerArea::cases(),
            'availabilities' => VolunteerAvailability::cases(),
            'contact' => config('school.contact'),
            'whatsapp' => config('school.whatsapp'),
        ]);
    }

    public function storeVolunteer(StoreVolunteerApplicationRequest $request): RedirectResponse
    {
        $firstName = strtok($request->validated('name'), ' ');

        // Un robot llenó el campo trampa: respondemos igual, sin guardar nada.
        if (! $request->filled('website')) {
            $application = VolunteerApplication::create([
                ...$request->volunteerData(),
                'privacy_accepted_at' => now(),
                'privacy_policy_version' => config('school.privacy_policy_version'),
                'ip_address' => $request->ip(),
            ]);

            Mail::to(config('school.support.notify_email'))->queue(new VolunteerApplicationReceived($application));
        }

        return redirect()->to(route('support').'#voluntariado')->with('volunteer_name', $firstName);
    }
}
