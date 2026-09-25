{{-- Selector de sección del año, agrupado por nivel. $sections, $selected, $name, $placeholder --}}
<select id="{{ $name }}" name="{{ $name }}">
    <option value="">{{ $placeholder }}</option>
    @foreach ($sections as $level => $levelSections)
        <optgroup label="{{ $level }}">
            @foreach ($levelSections as $option)
                <option value="{{ $option->id }}" @selected((string) $selected === (string) $option->id)>{{ $option->label() }}</option>
            @endforeach
        </optgroup>
    @endforeach
</select>
