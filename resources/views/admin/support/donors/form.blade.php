@php
    $editing = $donor->exists;
    $title = $editing ? 'Editar donante' : 'Nuevo donante';
@endphp

<x-layouts.admin :title="$title">
    <p class="back"><a href="{{ route('admin.donors.index') }}">← Donantes y aliados</a></p>
    <div class="admin-head">
        <h1>{{ $title }}</h1>
    </div>

    <form class="form admin-form narrow-form" method="POST" novalidate data-form
        action="{{ $editing ? route('admin.donors.update', $donor) : route('admin.donors.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <section class="panel">
            <x-field name="name" label="Nombre" hint="Persona, familia, empresa, iglesia o fundación, tal como quiere aparecer.">
                <input id="name" name="name" type="text" maxlength="120" value="{{ old('name', $donor->name) }}">
            </x-field>

            <x-field name="description" label="Descripción" optional hint="Ejemplo: «Empresa aliada» o «Donó los pupitres de 3.°».">
                <input id="description" name="description" type="text" maxlength="160" value="{{ old('description', $donor->description) }}">
            </x-field>

            <x-field name="website" label="Página web" optional>
                <input id="website" name="website" type="url" inputmode="url" maxlength="255" placeholder="https://" value="{{ old('website', $donor->website) }}">
            </x-field>

            @include('admin.support.partials.listing', ['model' => $donor])

            @include('admin.support.partials.consent', [
                'record' => $donor,
                'text' => 'El donante autorizó publicar su nombre en la página web.',
            ])

            <button type="submit" class="btn btn-azul" data-submit data-loading-text="Guardando…"><span>Guardar</span></button>
        </section>
    </form>

    @if ($editing)
        <form class="danger-zone" method="POST" action="{{ route('admin.donors.destroy', $donor) }}" data-confirm="¿Eliminar a «{{ $donor->name }}» de la lista?">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
        </form>
    @endif
</x-layouts.admin>
