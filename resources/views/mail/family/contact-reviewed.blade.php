@extends('mail.layout')

@section('content')
    @php
        $field = mb_strtolower($request->field->label());
    @endphp
    @if ($approved)
        <h1 style="margin:0 0 12px;font-size:22px;color:#104976;">Actualizamos tu {{ $field }}</h1>
        <p style="margin:0 0 16px;">Hola, {{ $guardian->first_names }}. El colegio aprobó tu solicitud. Tu {{ $field }} en el portal ahora es:</p>
        <p style="margin:0 0 20px;padding:14px 16px;background:#EFF6FD;border-radius:10px;font-size:17px;"><strong>{{ $request->new_value }}</strong></p>
    @else
        <h1 style="margin:0 0 12px;font-size:22px;color:#104976;">Tu solicitud no se aprobó</h1>
        <p style="margin:0 0 16px;">Hola, {{ $guardian->first_names }}. El colegio revisó tu solicitud de cambiar el {{ $field }} a <strong>{{ $request->new_value }}</strong> y no la aprobó.</p>
        @if ($request->note)
            <p style="margin:0 0 20px;padding:14px 16px;background:#EFF6FD;border-radius:10px;">Motivo: {{ $request->note }}</p>
        @endif
        <p style="margin:0 0 20px;">Si tienes dudas, llama al {{ config('school.contact.phone') }}.</p>
    @endif

    @include('mail.family._button', ['url' => route('login'), 'label' => 'Ver mis datos en el portal'])
@endsection

@section('footer')
    @include('mail.family._footer')
@endsection
