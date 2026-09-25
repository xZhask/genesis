@extends('mail.layout')

@section('content')
    <h1 style="margin:0 0 8px;font-size:22px;color:#104976;">Restablece tu contraseña</h1>
    <p style="margin:0 0 20px;">
        Hola, {{ $name }}. Recibimos una solicitud para restablecer la contraseña de tu cuenta en el portal del colegio.
    </p>
    <p style="margin:0 0 20px;">
        <a href="{{ $url }}" style="display:inline-block;background:#F6B91C;color:#3A2A00;text-decoration:none;font-weight:700;padding:14px 24px;border-radius:999px;">Crear una contraseña nueva</a>
    </p>
    <p style="margin:0 0 12px;font-size:14px;color:#56708A;">
        El enlace vence en {{ $minutes }} minutos. Si no pediste este cambio, ignora este correo: tu contraseña sigue igual.
    </p>
    <p style="margin:0;font-size:12px;color:#56708A;word-break:break-all;">
        Si el botón no funciona, copia este enlace en tu navegador:<br>{{ $url }}
    </p>
@endsection
