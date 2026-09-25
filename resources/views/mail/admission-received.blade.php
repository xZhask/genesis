@extends('mail.layout')

@php
    $rows = [
        'Código' => $admission->code,
        'Año lectivo' => $admission->school_year,
        'Grado al que aspira' => $admission->grade,
        'Estudiante' => $admission->studentFullName(),
        'Fecha de nacimiento' => $admission->student_birth_date->longDate().' ('.$admission->student_birth_date->age.' años)',
        'Colegio actual' => $admission->current_school ?: '—',
        'Acudiente' => $admission->guardian_name.' ('.$admission->guardian_relationship->label().')',
        'Celular' => $admission->formattedPhone().($admission->phone_has_whatsapp ? ' · tiene WhatsApp' : ''),
        'Correo' => $admission->guardian_email ?: '—',
        'Recibida' => $admission->created_at->longDate().', '.$admission->created_at->shortTime(),
    ];
@endphp

@section('content')
    <h1 style="margin:0 0 8px;font-size:22px;color:#104976;">Nueva solicitud de pre-inscripción</h1>
    <p style="margin:0 0 20px;">Llegó una solicitud desde la página web. Comunícate con la familia para agendar la entrevista.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
        @foreach ($rows as $label => $value)
            <tr>
                <td style="padding:8px 12px 8px 0;border-bottom:1px solid #D8E4F0;color:#56708A;width:40%;vertical-align:top;">{{ $label }}</td>
                <td style="padding:8px 0;border-bottom:1px solid #D8E4F0;font-weight:600;">{{ $value }}</td>
            </tr>
        @endforeach
    </table>

    @if ($admission->comments)
        <p style="margin:20px 0 4px;color:#56708A;font-size:14px;">Comentarios de la familia</p>
        <p style="margin:0;padding:12px 14px;background:#EFF6FD;border-radius:10px;">{!! nl2br(e($admission->comments)) !!}</p>
    @endif

    <p style="margin:24px 0 0;">
        <a href="tel:{{ $admission->guardian_phone }}" style="display:inline-block;background:#1D5FA8;color:#FFFFFF;text-decoration:none;font-weight:700;padding:12px 20px;border-radius:999px;">Llamar a la familia</a>
        @if ($admission->phone_has_whatsapp)
            <a href="https://wa.me/{{ ltrim($admission->guardian_phone, '+') }}" style="display:inline-block;margin-left:8px;background:#25D366;color:#FFFFFF;text-decoration:none;font-weight:700;padding:12px 20px;border-radius:999px;">Escribir por WhatsApp</a>
        @endif
    </p>
@endsection
