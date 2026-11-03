<?php

namespace App\Http\Controllers\Admin\People;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Support\PeopleExport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/** Descarga en Excel de las personas registradas. Lleva datos de menores: solo admin, y queda registro. */
class ExportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('manage-people');

        Log::info('Descarga del Excel de personas', ['user_id' => $request->user()->id, 'ip' => $request->ip()]);

        $name = 'personas-genesis-'.today()->toDateString().'.xlsx';

        return response((new PeopleExport(SchoolYear::current()))->build(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
