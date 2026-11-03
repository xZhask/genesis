@php
    $editing = $donor->exists;
    $title = $editing ? 'Editar donante' : 'Nuevo donante';
@endphp

<x-layouts.admin :title="$title">
    <p class="back"><a href="{{ route('admin.donors.index') }}">← Donantes y aliados</a></p>
    <div class="admin-head">
        <h1>{{ $title }}</h1>
    </div>

    <form class="form admin-form narrow-form" method="POST" enctype="multipart/form-data" novalidate data-form
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

            @if ($donor->photo_path)
                <img class="donor-photo-preview" src="{{ $donor->photoUrl() }}" alt="Foto actual de {{ $donor->name }}" width="96" height="96">
                @if ($donor->photo_consent_at)
                    <p class="muted">Autorización de la foto registrada el {{ $donor->photo_consent_at->longDate() }}.</p>
                @endif
                <label class="check">
                    <input type="checkbox" name="remove_photo" value="1" @checked(old('remove_photo'))>
                    <span>Quitar la foto actual</span>
                </label>
            @endif

            <x-field name="photo" :label="$donor->photo_path ? 'Reemplazar foto' : 'Foto'" optional hint="Se muestra pequeña y cuadrada, recortada desde el centro. JPG, PNG o WebP, hasta 8 MB. Sin foto aparece la inicial del nombre.">
                <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp">
            </x-field>

            <div @class(['field', 'consent', 'has-error' => $errors->has('photo_consent')])>
                <label class="check">
                    <input id="photo_consent" type="checkbox" name="photo_consent" value="1" @checked(old('photo_consent'))>
                    <span>Si subo una foto, el donante autorizó publicarla en la página web y, si aparecen menores, cuento con la autorización escrita de sus acudientes.</span>
                </label>
                @error('photo_consent')
                    <p class="error" id="photo_consent-error">{{ $message }}</p>
                @enderror
            </div>

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
