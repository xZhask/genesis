{{-- Campo de formulario: etiqueta, ayuda, control (slot) y error --}}
@props(['name', 'label', 'optional' => false, 'hint' => null])

<div {{ $attributes->class(['field', 'has-error' => $errors->has($name)]) }}>
    <label class="label" for="{{ $name }}">
        {{ $label }}
        @if ($optional)
            <span class="opt">(opcional)</span>
        @endif
    </label>
    @if ($hint)
        <p class="hint" id="{{ $name }}-hint">{{ $hint }}</p>
    @endif
    {{ $slot }}
    @error($name)
        <p class="error" id="{{ $name }}-error">{{ $message }}</p>
    @enderror
</div>
