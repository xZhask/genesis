@extends('mail.layout')

@section('content')
    <h1 style="margin:0 0 12px;font-size:22px;color:#104976;">Ya está disponible el boletín del {{ $periodName }}</h1>
    <p style="margin:0 0 16px;">
        Hola, {{ $guardian->first_names }}. El colegio cerró el {{ $periodName }} de {{ $year }}.
        Ya puedes consultar las notas y descargar el boletín de:
    </p>

    <ul style="margin:0 0 20px;padding-left:20px;">
        @foreach ($children as $enrollment)
            <li>{{ $enrollment->student->first_names }} ({{ $enrollment->section->label() }})</li>
        @endforeach
    </ul>

    @include('mail.family._button', ['url' => route('login'), 'label' => 'Ingresar al portal'])

    <p style="margin:0;font-size:14px;color:#56708A;">Por seguridad, las notas no van en este correo: se consultan en el portal con tu número de documento y tu contraseña.</p>
@endsection

@section('footer')
    @include('mail.family._footer')
@endsection
