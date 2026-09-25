<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Post;
use App\Support\CalendarExport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $levels = collect(config('school.levels'));
        // En la URL el nivel va en español (?nivel=primaria); internamente se usa la clave
        $levelSlug = $levels->pluck('slug')->contains($request->query('nivel')) ? $request->query('nivel') : null;
        $level = $levelSlug ? $levels->firstWhere('slug', $levelSlug)['key'] : null;

        $view = $request->query('vista') === 'mes' ? 'month' : 'list';

        $data = [
            'view' => $view,
            'levels' => $levels,
            'levelSlug' => $levelSlug,
            'posts' => Post::published()->latest('published_at')->limit(3)->get(),
        ];

        if ($view === 'month') {
            $month = $this->parseMonth($request->query('mes'));
            $from = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
            $to = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

            $events = Event::between($from, $to)->forLevel($level)->get();

            $days = collect();
            for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
                $days->push([
                    'date' => $day->copy(),
                    'inMonth' => $day->isSameMonth($month),
                    'events' => $events->filter(fn (Event $e) => $e->occursOn($day))->values(),
                ]);
            }

            return view('calendar.index', $data + [
                'month' => $month,
                'weeks' => $days->chunk(7),
                'monthEvents' => $events->filter(fn (Event $e) => $e->lastDay()->gte($month->copy()->startOfMonth())
                    && $e->starts_at->lte($month->copy()->endOfMonth())),
            ]);
        }

        return view('calendar.index', $data + [
            'groups' => Event::upcoming()->forLevel($level)->limit(60)->get()
                ->groupBy(fn (Event $e) => Str::ucfirst($e->starts_at->max(now()->startOfDay())->translatedFormat('F \d\e Y'))),
        ]);
    }

    /** Descarga del evento para "Agregar a mi calendario". */
    public function ics(Event $event): Response
    {
        $filename = Str::slug($event->title).'.ics';

        return response(CalendarExport::ics($event), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function parseMonth(?string $value): Carbon
    {
        if ($value && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            return Carbon::createFromFormat('Y-m-d', "{$value}-01")->startOfDay();
        }

        return now()->startOfMonth();
    }
}
