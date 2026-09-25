@extends('mail.layout')

@section('content')
    <h1 style="margin:0 0 8px;font-size:22px;color:#104976;">¡Recibimos tu solicitud!</h1>
    <p style="margin:0 0 16px;">
        Hola, {{ $admission->guardian_name }}. Gracias por pensar en Génesis para {{ $admission->student_first_names }}.
        Recibimos la solicitud de pre-inscripción a <strong>{{ $admission->grade }}</strong> para el año lectivo {{ $admission->school_year }}.
    </p>

    <p style="margin:0 0 20px;padding:14px 16px;background:#EFF6FD;border-radius:10px;">
        Tu código de solicitud es <strong style="font-size:18px;color:#104976;">{{ $admission->code }}</strong>.<br>
        Tenlo a mano si te comunicas con el colegio.
    </p>

    <p style="margin:0 0 8px;font-weight:700;">¿Qué sigue?</p>
    <ol style="margin:0 0 20px;padding-left:20px;">
        <li>Revisamos tu solicitud y te contactamos en los próximos {{ $responseTime }} al {{ $admission->formattedPhone() }}.</li>
        <li>Agendamos una entrevista familiar y una visita al colegio.</li>
        <li>Te indicamos los documentos que debes traer según el grado.</li>
    </ol>

    <p style="margin:0;">Si tienes preguntas, llámanos al {{ config('school.contact.phone') }} o responde a este correo.</p>
@endsection
