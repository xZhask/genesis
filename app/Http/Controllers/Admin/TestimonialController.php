<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TestimonialRequest;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Testimonial::class);

        return view('admin.support.testimonials.index', ['testimonials' => Testimonial::ordered()->get()]);
    }

    public function create(): View
    {
        $this->authorize('create', Testimonial::class);

        return view('admin.support.testimonials.form', ['testimonial' => new Testimonial(['is_visible' => true])]);
    }

    public function store(TestimonialRequest $request): RedirectResponse
    {
        $testimonial = new Testimonial($request->safe()->except('consent'));
        $testimonial->consent_at = now();
        $testimonial->save();

        return redirect()->route('admin.testimonials.index')->with('status_message', "Se agregó el testimonio de {$testimonial->author}.");
    }

    public function edit(Testimonial $testimonial): View
    {
        $this->authorize('update', $testimonial);

        return view('admin.support.testimonials.form', ['testimonial' => $testimonial]);
    }

    public function update(TestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $testimonial->fill($request->safe()->except('consent'));
        $testimonial->consent_at ??= now();
        $testimonial->save();

        return redirect()->route('admin.testimonials.index')->with('status_message', "Se actualizó el testimonio de {$testimonial->author}.");
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('delete', $testimonial);

        $testimonial->delete();

        return redirect()->route('admin.testimonials.index')->with('status_message', "Se eliminó el testimonio de {$testimonial->author}.");
    }
}
