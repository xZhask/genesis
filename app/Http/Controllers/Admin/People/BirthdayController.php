<?php

namespace App\Http\Controllers\Admin\People;

use App\Http\Controllers\Controller;
use App\Models\BirthdayGreeting;
use App\Support\Birthdays;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Cumpleaños de estudiantes y personal: hoy, los próximos días y por mes. */
class BirthdayController extends Controller
{
    public function __invoke(Request $request): View
    {
        Gate::authorize('manage-people');

        $month = $request->integer('mes', today()->month);
        $month = $month >= 1 && $month <= 12 ? $month : today()->month;
        $kind = in_array($request->query('tipo'), ['student', 'staff'], true) ? $request->query('tipo') : null;

        $people = Birthdays::people()->when($kind, fn ($rows) => $rows->where('kind', $kind));
        $window = (int) config('school.alerts.birthday_days');
        $soon = fn ($row) => [$row['days'], $row['name']];

        return view('admin.people.birthdays', [
            'today' => $people->where('days', 0)->sortBy($soon)->values(),
            'upcoming' => $people->filter(fn ($row) => $row['days'] > 0 && $row['days'] < $window)->sortBy($soon)->values(),
            'byMonth' => $people->where('month', $month)->sortBy(fn ($row) => [$row['day'], $row['name']])->values(),
            'sent' => BirthdayGreeting::whereDate('celebrated_on', today())->whereNotNull('sent_at')->get()
                ->mapWithKeys(fn ($g) => [$g->celebrant_type.'|'.$g->celebrant_id => true]),
            'month' => $month,
            'kind' => $kind,
            'window' => $window,
            'missing' => Birthdays::missingDates(),
        ]);
    }
}
