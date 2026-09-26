<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ResourceVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Event::class);

        $past = $request->query('vista') === 'pasados';
        $today = now()->startOfDay();

        $events = Event::query()
            ->when($past,
                fn ($q) => $q->where('starts_at', '<', $today)->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '<', $today))->orderByDesc('starts_at'),
                fn ($q) => $q->upcoming())
            ->paginate(25)
            ->withQueryString();

        return view('admin.events.index', ['events' => $events, 'past' => $past]);
    }

    public function create(): View
    {
        $this->authorize('create', Event::class);

        // Lo más seguro si nadie cambia la opción: solo las familias
        return view('admin.events.form', ['event' => new Event(['visibility' => ResourceVisibility::Families])]);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        $event = new Event($request->eventData());
        $event->created_by = $request->user()->id;
        $event->save();

        return redirect()->route('admin.events.index')->with('status_message', "Se agregó «{$event->title}» al calendario.");
    }

    public function edit(Event $event): View
    {
        $this->authorize('update', $event);

        return view('admin.events.form', ['event' => $event]);
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $event->update($request->eventData());

        return redirect()->route('admin.events.index')->with('status_message', "Se actualizó «{$event->title}».");
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()->route('admin.events.index')->with('status_message', "Se eliminó «{$event->title}» del calendario.");
    }
}
