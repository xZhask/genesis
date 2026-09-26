<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublicationStatus;
use App\Enums\ResourceType;
use App\Enums\ResourceVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResourceRequest;
use App\Models\Resource;
use App\Support\FamilyMail;
use App\Support\ImageResizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResourceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Resource::class);

        $type = ResourceType::fromAnchor($request->query('tipo')) ?? ResourceType::Circular;

        $query = Resource::ofType($type);
        $query = match ($type) {
            ResourceType::Circular => $query->latest('published_on')->latest('id'),
            ResourceType::Supplies => $query->orderByDesc('school_year')->orderByRaw($this->gradeOrder()),
            default => $query->orderBy('position')->orderBy('id'),
        };

        return view('admin.resources.index', [
            'type' => $type,
            'resources' => $query->paginate(30)->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Resource::class);

        $type = ResourceType::fromAnchor($request->query('tipo')) ?? ResourceType::Circular;

        return view('admin.resources.form', ['resource' => new Resource([
            'type' => $type,
            'status' => PublicationStatus::Draft,
            // Lo más seguro si nadie cambia la opción: solo las familias
            'visibility' => ResourceVisibility::Families,
            'published_on' => today(),
            'school_year' => config('school.admissions.school_year'),
        ])]);
    }

    public function store(ResourceRequest $request): RedirectResponse
    {
        $resource = new Resource($request->resourceData());
        $this->syncFiles($request, $resource);
        $resource->save();
        $this->notifyFamilies($request, $resource);

        return $this->saved($resource, 'Se guardó');
    }

    public function edit(Resource $resource): View
    {
        $this->authorize('update', $resource);

        return view('admin.resources.form', ['resource' => $resource]);
    }

    public function update(ResourceRequest $request, Resource $resource): RedirectResponse
    {
        $resource->fill($request->resourceData());
        $this->syncFiles($request, $resource);
        $resource->save();
        $this->notifyFamilies($request, $resource);

        return $this->saved($resource, 'Se actualizó');
    }

    public function destroy(Resource $resource): RedirectResponse
    {
        $this->authorize('delete', $resource);

        $resource->delete();

        return redirect()
            ->route('admin.resources.index', ['tipo' => $resource->type->anchor()])
            ->with('status_message', "Se eliminó «{$resource->title}».");
    }

    /** PDF adjunto y foto (solo uniformes): reemplazar, quitar o conservar. */
    private function syncFiles(ResourceRequest $request, Resource $resource): void
    {
        // Si cambió la visibilidad, el PDF actual pasa al disco que corresponde
        $previousDisk = $resource->exists && $resource->isDirty('visibility')
            ? (ResourceVisibility::from($resource->getRawOriginal('visibility')) === ResourceVisibility::Families ? 'local' : 'public')
            : $resource->fileDisk();

        if ($request->hasFile('file') || $request->boolean('remove_file')) {
            if ($resource->file_path) {
                Storage::disk($previousDisk)->delete($resource->file_path);
            }
            $resource->file_path = $resource->file_name = $resource->file_size = null;

            if ($file = $request->file('file')) {
                $resource->file_path = $file->store('resources', $resource->fileDisk());
                $resource->file_name = Str::limit($file->getClientOriginalName(), 200, '');
                $resource->file_size = $file->getSize();
            }
        } elseif ($resource->file_path && $previousDisk !== $resource->fileDisk()) {
            Storage::disk($resource->fileDisk())->put($resource->file_path, Storage::disk($previousDisk)->get($resource->file_path));
            Storage::disk($previousDisk)->delete($resource->file_path);
        }

        $isUniform = $resource->type === ResourceType::Uniform;

        if (! $isUniform || $request->hasFile('image') || $request->boolean('remove_image')) {
            ImageResizer::delete($resource->image_path);
            $resource->image_path = $isUniform && $request->hasFile('image')
                ? ImageResizer::store($request->file('image'), 'resources')
                : null;

            if (! $resource->image_path) {
                $resource->image_alt = null;
            }
        }
    }

    /** Aviso por correo de una circular publicada (una sola vez por familia). */
    private function notifyFamilies(ResourceRequest $request, Resource $resource): void
    {
        if ($resource->type === ResourceType::Circular && $request->boolean('notify')) {
            FamilyMail::queueCircular($resource);
        }
    }

    private function saved(Resource $resource, string $verb): RedirectResponse
    {
        $state = match (true) {
            ! $resource->isPublished() => 'Quedó como borrador.',
            $resource->isForFamiliesOnly() => "Ya la ven en el portal: {$resource->audienceLabel()}.",
            default => 'Ya se ve en la web.',
        };

        return redirect()
            ->route('admin.resources.index', ['tipo' => $resource->type->anchor()])
            ->with('status_message', "{$verb} «{$resource->title}». {$state}");
    }

    /** Ordena los grados como en el colegio (Párvulos … 9.°), no alfabéticamente. */
    private function gradeOrder(): string
    {
        // Los grados vienen de config/school.php, no del usuario
        $cases = collect(Resource::grades())
            ->map(fn ($grade, $i) => "when '".str_replace("'", "''", $grade)."' then {$i}")
            ->implode(' ');

        return "case grade {$cases} else 99 end";
    }
}
