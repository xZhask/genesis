<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DonorRequest;
use App\Models\Donor;
use App\Support\ImageResizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DonorController extends Controller
{
    private const NOT_FILLED = ['consent', 'photo', 'photo_consent', 'remove_photo'];

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
        $donor = new Donor($request->safe()->except(self::NOT_FILLED));
        $donor->consent_at = now();
        $this->applyPhoto($request, $donor);
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
        $donor->fill($request->safe()->except(self::NOT_FILLED));
        $donor->consent_at ??= now();
        $this->applyPhoto($request, $donor);
        $donor->save();

        return redirect()->route('admin.donors.index')->with('status_message', "Se actualizó «{$donor->name}».");
    }

    public function destroy(Donor $donor): RedirectResponse
    {
        $this->authorize('delete', $donor);

        if ($donor->photo_path) {
            Storage::disk('public')->delete($donor->photo_path);
        }

        $donor->delete();

        return redirect()->route('admin.donors.index')->with('status_message', "Se eliminó «{$donor->name}».");
    }

    /** Guarda, reemplaza o quita la foto; la fecha de autorización va con la foto vigente. */
    private function applyPhoto(DonorRequest $request, Donor $donor): void
    {
        if (! $request->hasFile('photo') && ! $request->boolean('remove_photo')) {
            return;
        }

        // Primero la nueva: si no se puede leer, la anterior sigue intacta
        $new = $request->hasFile('photo') ? ImageResizer::storeSquare($request->file('photo'), 'donors') : null;

        if ($donor->photo_path) {
            Storage::disk('public')->delete($donor->photo_path);
        }

        $donor->photo_path = $new;
        $donor->photo_consent_at = $new ? now() : null;
    }
}
