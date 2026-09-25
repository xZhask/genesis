<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DonorRequest;
use App\Models\Donor;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DonorController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Donor::class);

        return view('admin.support.donors.index', ['donors' => Donor::ordered()->get()]);
    }

    public function create(): View
    {
        $this->authorize('create', Donor::class);

        return view('admin.support.donors.form', ['donor' => new Donor(['is_visible' => true])]);
    }

    public function store(DonorRequest $request): RedirectResponse
    {
        $donor = new Donor($request->safe()->except('consent'));
        $donor->consent_at = now();
        $donor->save();

        return redirect()->route('admin.donors.index')->with('status_message', "Se agregó «{$donor->name}».");
    }

    public function edit(Donor $donor): View
    {
        $this->authorize('update', $donor);

        return view('admin.support.donors.form', ['donor' => $donor]);
    }

    public function update(DonorRequest $request, Donor $donor): RedirectResponse
    {
        $donor->fill($request->safe()->except('consent'));
        $donor->consent_at ??= now();
        $donor->save();

        return redirect()->route('admin.donors.index')->with('status_message', "Se actualizó «{$donor->name}».");
    }

    public function destroy(Donor $donor): RedirectResponse
    {
        $this->authorize('delete', $donor);

        $donor->delete();

        return redirect()->route('admin.donors.index')->with('status_message', "Se eliminó «{$donor->name}».");
    }
}
