{{-- Android abre mejor Google Calendar; iPhone y Outlook, el archivo .ics --}}
@props(['event'])

<details {{ $attributes->class('add-cal') }}>
    <summary>Agregar a mi calendario<span class="sr-only">: {{ $event->title }}</span></summary>
    <div class="add-cal-menu">
        <a href="{{ \App\Support\CalendarExport::googleUrl($event) }}" target="_blank" rel="noopener">Google Calendar <small>(Android)</small></a>
        <a href="{{ route('calendar.ics', $event) }}" download>iPhone u Outlook <small>(.ics)</small></a>
    </div>
</details>
