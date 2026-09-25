<x-layouts.admin title="Donantes y aliados">
    <div class="admin-head row">
        <h1>Apóyanos</h1>
        <a class="btn btn-sol" href="{{ route('admin.donors.create') }}">+ Nuevo donante</a>
    </div>

    @include('admin.support.partials.tabs')

    <p class="lead-sm">Se muestran en «Gracias a quienes creen en nuestra misión». Publica solo a quienes lo autorizaron.</p>

    @if ($donors->isEmpty())
        <p class="empty panel">No hay donantes publicados. La sección no aparece en la web mientras la lista esté vacía.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Descripción</th>
                        <th scope="col">En la web</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($donors as $donor)
                        <tr>
                            <td data-label="Nombre"><a class="strong" href="{{ route('admin.donors.edit', $donor) }}">{{ $donor->name }}</a></td>
                            <td data-label="Descripción">{{ $donor->description ?? '—' }}</td>
                            <td data-label="En la web">@include('admin.support.partials.visible', ['visible' => $donor->is_visible])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.admin>
