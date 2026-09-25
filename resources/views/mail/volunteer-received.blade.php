@extends('mail.layout')

@php
    $rows = [
        'Nombre' => $application->name,
        'Celular' => $application->formattedPhone().($application->phone_has_whatsapp ? ' · tiene WhatsApp' : ''),
        'Correo' => $application->email ?: '—',
        'Quiere ayudar en' => $application->areasLabel(),
        'Disponibilidad' => $application->availability->label(),
        'Recibida' => $application->created_at->longDate().', '.$application->created_at->shortTime(),
    ];
@endphp

@section('content')
    <h1 style="margin:0 0 8px;font-size:22px;color:#104976;">Alguien quiere ser voluntario</h1>
    <p style="margin:0 0 20px;">Llegó una solicitud de voluntariado desde la página web. Comunícate para agradecerle y contarle cómo puede ayudar.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
        @foreach ($rows as $label => $value)
            <tr>
                <td style="padding:8px 12px 8px 0;border-bottom:1px solid #D8E4F0;color:#56708A;width:40%;vertical-align:top;">{{ $label }}</td>
                <td style="padding:8px 0;border-bottom:1px solid #D8E4F0;font-weight:600;">{{ $value }}</td>
            </tr>
        @endforeach
    </table>

    @if ($application->message)
        <p style="margin:20px 0 4px;color:#56708A;font-size:14px;">Mensaje</p>
        <p style="margin:0;padding:12px 14px;background:#EFF6FD;border-radius:10px;">{!! nl2br(e($application->message)) !!}</p>
    @endif

    <p style="margin:24px 0 0;">
        <a href="{{ route('admin.volunteers.show', $application) }}" style="display:inline-block;background:#1D5FA8;color:#FFFFFF;text-decoration:none;font-weight:700;padding:12px 20px;border-radius:999px;">Ver en el panel</a>
    </p>
@endsection
