<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        Gate::authorize('manage-settings');

        return view('admin.settings', [
            'school' => config('school'),
            'lastChange' => Settings::lastChange(),
        ]);
    }

    public function update(SettingsRequest $request): RedirectResponse
    {
        Settings::save($request->settings(), $request->user());

        return redirect()->route('admin.settings.edit')->with('status_message', 'Se guardó la configuración. Los cambios ya se ven en la web.');
    }
}
