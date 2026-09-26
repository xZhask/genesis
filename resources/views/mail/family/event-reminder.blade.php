@extends('mail.layout')

@section('content')
    <p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#3FA64A;text-transform:uppercase;letter-spacing:.04em;">Recordatorio</p>
    <h1 style="margin:0 0 12px;font-size:22px;color:#104976;">{{ $event->title }}</h1>

    <p style="margin:0 0 16px;padding:14px 16px;background:#EFF6FD;border-radius:10px;">
        @if ($event->isMultiDay())
            <strong>{{ $event->whenLabel() }}</strong>
        @else
            <strong>{{ ucfirst($event->starts_at->translatedFormat('l')) }}, {{ $event->starts_at->longDate() }}</strong><br>
            {{ $event->whenLabel() }}
        @endif
        @if ($event->location)
            <br>{{ $event->location }}
        @endif
        @if ($event->levelName())
            <br>Para las familias de {{ $event->levelName() }}
        @endif
    </p>

    @if ($event->description)
        <p style="margin:0 0 20px;">{{ Str::limit($event->description, 400) }}</p>
    @endif

    @include('mail.family._button', ['url' => $googleUrl, 'label' => 'Agregar a Google Calendar', 'margin' => '0 0 10px'])
    <p style="margin:0 0 20px;font-size:14px;">¿Usas otro calendario? <a href="{{ $icsUrl }}" style="color:#1D5FA8;">Descarga el evento (.ics)</a>.</p>

    <p style="margin:0;">Mira todo el calendario en <a href="{{ route('calendar') }}" style="color:#1D5FA8;">{{ preg_replace('#^https?://#', '', route('calendar')) }}</a>.</p>
@endsection

@section('footer')
    @include('mail.family._footer')
@endsection
