@extends('mail.layout')

@section('content')
    <h1 style="margin:0 0 12px;font-size:22px;color:#104976;">El correo de tu cuenta cambió</h1>
    <p style="margin:0 0 16px;">
        Hola, {{ $guardian->first_names }}. El {{ $date->longDate() }} el colegio cambió el correo de tu cuenta del portal a
        <strong>{{ $masked }}</strong>, a partir de una solicitud hecha desde el portal.
    </p>
    <p style="margin:0 0 20px;padding:14px 16px;background:#FDECEA;border-radius:10px;">
        <strong>¿No fuiste tú?</strong> Llama cuanto antes al colegio: {{ config('school.contact.phone') }}.
    </p>
    <p style="margin:0;font-size:14px;color:#56708A;">Te enviamos este aviso de seguridad a tu correo anterior. Si tú hiciste el cambio, no tienes que hacer nada.</p>
@endsection
