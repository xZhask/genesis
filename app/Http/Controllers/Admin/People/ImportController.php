<?php

namespace App\Http\Controllers\Admin\People;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Support\StudentImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Importación de estudiantes y acudientes en dos pasos: subir el CSV y ver
 * la revisión (nada se guarda), y luego confirmar. El archivo espera en
 * storage/app/private/imports hasta confirmar o descartar.
 */
class ImportController extends Controller
{
    public function create(): View
    {
        Gate::authorize('manage-people');

        return view('admin.people.import.create', [
            'year' => SchoolYear::current(),
            'columns' => StudentImport::COLUMNS,
        ]);
    }

    public function template(): Response
    {
        Gate::authorize('manage-people');

        return response(StudentImport::template(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla-estudiantes.csv"',
        ]);
    }

    public function preview(Request $request): View|RedirectResponse
    {
        Gate::authorize('manage-people');
        $year = SchoolYear::current();
        abort_unless($year, 404);

        $request->validate([
            'file' => ['required', 'file', 'max:2048', 'mimes:csv,txt'],
        ], [
            'file.required' => 'Elige el archivo CSV.',
            'file.mimes' => 'El archivo debe ser CSV. En Excel: Archivo → Guardar como → CSV UTF-8 (delimitado por comas).',
            'file.max' => 'El archivo no puede pesar más de 2 MB.',
        ]);

        $this->discardPending($request);
        $path = $request->file('file')->storeAs('imports', Str::uuid().'.csv', 'local');

        $import = (new StudentImport($year))->parse(Storage::disk('local')->path($path));

        if ($import->isValid()) {
            $request->session()->put('import', ['path' => $path, 'year_id' => $year->id]);
        } else {
            Storage::disk('local')->delete($path);
        }

        return view('admin.people.import.preview', [
            'year' => $year,
            'import' => $import,
            'summary' => $import->isValid() ? $import->summary() : null,
            'fileName' => $request->file('file')->getClientOriginalName(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-people');

        $pending = $request->session()->pull('import');
        $year = SchoolYear::find($pending['year_id'] ?? null);
        if (! $pending || ! $year || ! Storage::disk('local')->exists($pending['path'])) {
            return redirect()->route('admin.people.import.create')
                ->withErrors(['file' => 'La revisión venció. Vuelve a subir el archivo.']);
        }

        // Se vuelve a validar: los datos pudieron cambiar desde la revisión
        $import = (new StudentImport($year))->parse(Storage::disk('local')->path($pending['path']));
        Storage::disk('local')->delete($pending['path']);

        if (! $import->isValid()) {
            return redirect()->route('admin.people.import.create')
                ->withErrors(['file' => 'El archivo ya no es válido: '.($import->errors[0] ?? 'revísalo y vuelve a subirlo.')]);
        }

        $counts = $import->apply();

        $message = 'Importación lista: '
            .trans_choice(':count estudiante nuevo|:count estudiantes nuevos', $counts['students']).', '
            .trans_choice(':count acudiente nuevo|:count acudientes nuevos', $counts['guardians']).' y '
            .trans_choice(':count vínculo nuevo|:count vínculos nuevos', $counts['links'])
            .'. Las filas de personas ya registradas actualizaron sus datos.';

        return redirect()->route('admin.people.students.index', ['lectivo' => $year->year])->with('status_message', $message);
    }

    public function discard(Request $request): RedirectResponse
    {
        Gate::authorize('manage-people');
        $this->discardPending($request);

        return redirect()->route('admin.people.import.create')->with('status_message', 'Se descartó la importación.');
    }

    private function discardPending(Request $request): void
    {
        if ($pending = $request->session()->pull('import')) {
            Storage::disk('local')->delete($pending['path']);
        }
    }
}
