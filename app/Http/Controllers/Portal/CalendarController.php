<?php

namespace App\Http\Controllers\Portal;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Calendario del portal: los eventos públicos y los de solo familias que le
 * corresponden a quien ingresa (acudientes: los grados de sus acudidos;
 * docentes: todos).
 */
class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->guardian || $user->hasRole(Role::Teacher), 403);

        $events = Event::upcoming()->limit(120)->get()
            ->filter(fn (Event $e) => $e->visibleTo($user))
            ->take(60);

        return view('portal.calendar', [
            'groups' => $events->groupBy(fn (Event $e) => Str::ucfirst($e->starts_at->max(now()->startOfDay())->translatedFormat('F \d\e Y'))),
        ]);
    }
}
