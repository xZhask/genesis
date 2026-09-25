{{-- Campos comunes: mostrar en la web y orden --}}

<label class="check">
    {{-- Tras un error, respeta lo que se marcó (una casilla sin marcar no se envía) --}}
    <input type="checkbox" name="is_visible" value="1" @checked($errors->any() ? old('is_visible') : $model->is_visible)>
    <span>Mostrar en la web</span>
</label>

<x-field name="position" label="Orden" optional hint="Número menor = aparece primero.">
    <input id="position" name="position" type="number" min="0" max="999" inputmode="numeric" value="{{ old('position', $model->position ?: '') }}">
</x-field>
