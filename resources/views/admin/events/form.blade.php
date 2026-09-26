@php
    $editing = $event->exists;
    $allDay = (bool) old('all_day', $event->all_day ?? false);
    $multiDay = $editing && $event->isMultiDay();
@endphp

<x-layouts.admin :title="$editing ? 'Editar evento' : 'Nuevo evento'">
    <p class="back"><a href="{{ route('admin.events.index') }}">← Eventos</a></p>
    <div class="admin-head">
        <h1>{{ $editing ? 'Editar evento' : 'Nuevo evento' }}</h1>
    </div>

    <form class="form admin-form narrow-form" method="POST" novalidate data-form
        action="{{ $editing ? route('admin.events.update', $event) : route('admin.events.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <section class="panel stack">
            <x-field name="title" label="Título" hint="Corto y claro. Ejemplo: «Entrega de boletines del tercer periodo».">
                <input id="title" name="title" type="text" maxlength="160" value="{{ old('title', $event->title) }}" required>
            </x-field>

            <div class="row-2">
                <x-field name="date" label="Fecha">
                    <input id="date" name="date" type="date" value="{{ old('date', $event->starts_at?->toDateString()) }}" required>
                </x-field>
                <x-field name="end_date" label="Hasta" optional hint="Solo si dura varios días (recesos, semanas especiales).">
                    <input id="end_date" name="end_date" type="date" value="{{ old('end_date', $multiDay ? $event->ends_at->toDateString() : null) }}">
                </x-field>
            </div>

            <label class="check">
                <input type="checkbox" name="all_day" value="1" data-all-day @checked($allDay)>
                <span>Todo el día (sin horario)</span>
            </label>

            <div class="row-2" data-times @if ($allDay) hidden @endif>
                <x-field name="start_time" label="Hora de inicio">
                    <input id="start_time" name="start_time" type="time" value="{{ old('start_time', $event->exists && ! $event->all_day ? $event->starts_at->format('H:i') : null) }}">
                </x-field>
                <x-field name="end_time" label="Hora de fin" optional>
                    <input id="end_time" name="end_time" type="time" value="{{ old('end_time', $event->ends_at && ! $event->all_day ? $event->ends_at->format('H:i') : null) }}">
                </x-field>
            </div>

            <div class="row-2">
                <x-field name="location" label="Lugar" optional>
                    <input id="location" name="location" type="text" maxlength="160" value="{{ old('location', $event->location) }}">
                </x-field>
                <x-field name="level" label="¿Para quién es?">
                    <select id="level" name="level">
                        <option value="">Todo el colegio</option>
                        @foreach (config('school.levels') as $level)
                            <option value="{{ $level['key'] }}" @selected(old('level', $event->level) === $level['key'])>{{ $level['name'] }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>

            <x-field name="description" label="Descripción" optional hint="Qué deben saber o traer las familias.">
                <textarea id="description" name="description" rows="3" maxlength="2000">{{ old('description', $event->description) }}</textarea>
            </x-field>

            <label class="check">
                <input type="checkbox" name="send_reminder" value="1" @checked(old('send_reminder', $event->send_reminder))>
                <span>Enviar un recordatorio por correo a las familias {{ config('school.family_mail.reminder_days') }} días antes
                    <small class="block">A los acudientes del nivel elegido (o de todo el colegio) que tienen cuenta en el portal y correo.</small></span>
            </label>

            <button type="submit" class="btn btn-azul" data-submit data-loading-text="Guardando…"><span>Guardar evento</span></button>
        </section>
    </form>

    @if ($editing)
        <form class="danger-zone" method="POST" action="{{ route('admin.events.destroy', $event) }}" data-confirm="¿Eliminar «{{ $event->title }}» del calendario?">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger">Eliminar evento</button>
        </form>
    @endif
</x-layouts.admin>
