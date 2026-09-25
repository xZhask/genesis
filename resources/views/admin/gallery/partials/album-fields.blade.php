@use('App\Enums\AlbumStatus')
@php
    $status = old('status', $album->status?->value ?? AlbumStatus::Draft->value);
@endphp

<x-field name="title" label="Nombre del álbum" hint="La actividad, tal como la reconocen las familias. Ejemplo: «Día de la familia».">
    <input id="title" name="title" type="text" maxlength="120" value="{{ old('title', $album->title) }}" required>
</x-field>

<x-field name="taken_on" label="Fecha de la actividad">
    <input id="taken_on" name="taken_on" type="date" max="{{ today()->toDateString() }}"
        value="{{ old('taken_on', $album->taken_on?->toDateString()) }}" required>
</x-field>

<x-field name="description" label="Descripción" optional hint="Una o dos frases sobre la actividad.">
    <textarea id="description" name="description" rows="3" maxlength="500">{{ old('description', $album->description) }}</textarea>
</x-field>

<fieldset class="field choices">
    <legend class="label">Estado</legend>
    <div class="pills">
        @foreach (AlbumStatus::cases() as $case)
            <label class="pill">
                <input type="radio" name="status" value="{{ $case->value }}" @checked($status === $case->value)>
                <span>{{ $case === AlbumStatus::Draft ? 'Borrador' : 'Publicar' }}</span>
            </label>
        @endforeach
    </div>
    <p class="hint">Un álbum publicado se ve en la web cuando tiene al menos una foto.</p>
</fieldset>
