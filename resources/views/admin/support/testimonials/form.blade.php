@php
    $editing = $testimonial->exists;
    $title = $editing ? 'Editar testimonio' : 'Nuevo testimonio';
@endphp

<x-layouts.admin :title="$title">
    <p class="back"><a href="{{ route('admin.testimonials.index') }}">← Testimonios</a></p>
    <div class="admin-head">
        <h1>{{ $title }}</h1>
    </div>

    <form class="form admin-form narrow-form" method="POST" novalidate data-form
        action="{{ $editing ? route('admin.testimonials.update', $testimonial) : route('admin.testimonials.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <section class="panel">
            <x-field name="quote" label="Testimonio" hint="Sus palabras, sin comillas (la página las agrega). Máximo 400 caracteres.">
                <textarea id="quote" name="quote" rows="4" maxlength="400">{{ old('quote', $testimonial->quote) }}</textarea>
            </x-field>

            <div class="field-group">
                <x-field name="author" label="Nombre">
                    <input id="author" name="author" type="text" maxlength="80" value="{{ old('author', $testimonial->author) }}">
                </x-field>
                <x-field name="role" label="Relación con el colegio" optional hint="Ejemplo: «acudiente y voluntaria».">
                    <input id="role" name="role" type="text" maxlength="80" value="{{ old('role', $testimonial->role) }}">
                </x-field>
            </div>

            @include('admin.support.partials.listing', ['model' => $testimonial])

            @include('admin.support.partials.consent', [
                'record' => $testimonial,
                'text' => 'La persona autorizó publicar su testimonio y su nombre en la página web.',
            ])

            <button type="submit" class="btn btn-azul" data-submit data-loading-text="Guardando…"><span>Guardar</span></button>
        </section>
    </form>

    @if ($editing)
        <form class="danger-zone" method="POST" action="{{ route('admin.testimonials.destroy', $testimonial) }}" data-confirm="¿Eliminar el testimonio de {{ $testimonial->author }}?">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger">Eliminar testimonio</button>
        </form>
    @endif
</x-layouts.admin>
