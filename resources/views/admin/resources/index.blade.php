@use('App\Enums\ResourceType')
@use('App\Enums\PublicationStatus')

<x-layouts.admin title="Recursos">
    <div class="admin-head row">
        <h1>Recursos para acudientes</h1>
        <a class="btn btn-sol" href="{{ route('admin.resources.create', ['tipo' => $type->anchor()]) }}">+ {{ $type === ResourceType::Supplies ? 'Nueva lista de útiles' : ($type === ResourceType::Circular ? 'Nueva circular' : 'Nuevo '.mb_strtolower($type->label())) }}</a>
    </div>

    <nav class="tabs" aria-label="Tipo de recurso">
        @foreach (ResourceType::cases() as $case)
            <a href="{{ route('admin.resources.index', ['tipo' => $case->anchor()]) }}" @if ($type === $case) aria-current="page" @endif>{{ $case->plural() }}</a>
        @endforeach
    </nav>

    @if ($resources->isEmpty())
        <p class="empty panel">Todavía no hay {{ mb_strtolower($type->plural()) }}. <a href="{{ route('admin.resources.create', ['tipo' => $type->anchor()]) }}">Agrega el primero</a>.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Título</th>
                        <th scope="col">{{ $type === ResourceType::Supplies ? 'Grado' : ($type === ResourceType::Circular ? 'Fecha' : 'Orden') }}</th>
                        @if ($type === ResourceType::Circular)
                            <th scope="col">Quién la ve</th>
                        @endif
                        <th scope="col">Contenido</th>
                        <th scope="col">Estado</th>
                        <th scope="col"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($resources as $resource)
                        <tr>
                            <td data-label="Título"><a href="{{ route('admin.resources.edit', $resource) }}">{{ $resource->title }}</a></td>
                            <td data-label="{{ $type === ResourceType::Supplies ? 'Grado' : ($type === ResourceType::Circular ? 'Fecha' : 'Orden') }}" class="nowrap">
                                @if ($type === ResourceType::Supplies)
                                    {{ $resource->grade }} · {{ $resource->school_year }}
                                @elseif ($type === ResourceType::Circular)
                                    {{ $resource->published_on->longDate() }}
                                @else
                                    {{ $resource->position }}
                                @endif
                            </td>
                            @if ($type === ResourceType::Circular)
                                <td data-label="Quién la ve">{{ $resource->isForFamiliesOnly() ? $resource->audienceLabel() : 'Web pública' }}</td>
                            @endif
                            <td data-label="Contenido">{{ collect([$resource->file_path ? 'PDF' : null, $resource->body ? 'Texto' : null, $resource->image_path ? 'Foto' : null])->filter()->implode(' · ') }}</td>
                            <td data-label="Estado">
                                <span class="badge badge-{{ $resource->status === PublicationStatus::Published ? 'accepted' : 'withdrawn' }}">{{ $resource->status->label() }}</span>
                            </td>
                            <td class="row-actions">
                                @if ($resource->isPublished() && ! $resource->isForFamiliesOnly())
                                    <a href="{{ route('resources') }}#{{ $resource->anchor() }}" target="_blank" rel="noopener">Ver</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $resources->links('partials.pagination') }}
    @endif
</x-layouts.admin>
