@extends('mail.layout')

@section('content')
    @if ($guardian)
        <p style="margin:0 0 18px;">Hola, {{ strtok($guardian->first_names, ' ') }}. Hoy {{ $firstName }} está de cumpleaños y queremos celebrarlo con tu familia. Esta tarjeta es para compartirla:</p>
    @endif

    {{-- La tarjeta: tablas y colores sólidos, que se ven igual en todos los programas de correo --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-radius:16px;overflow:hidden;">
        <tr>
            <td width="34%" height="8" style="background:#3FA64A;font-size:0;line-height:0;">&nbsp;</td>
            <td width="33%" height="8" style="background:#F6B91C;font-size:0;line-height:0;">&nbsp;</td>
            <td width="33%" height="8" style="background:#1D5FA8;font-size:0;line-height:0;">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="3" align="center" style="background:#104976;padding:32px 24px 34px;color:#FFFFFF;">
                <div style="font-size:38px;line-height:1.2;">🎂🎈🎉</div>
                <p style="margin:12px 0 6px;font-size:30px;line-height:1.2;font-weight:800;color:#F6B91C;">¡Feliz cumpleaños!</p>
                <p style="margin:0;font-size:22px;line-height:1.3;font-weight:700;color:#FFFFFF;">{{ $fullName }}</p>
                @if ($role)
                    <p style="margin:4px 0 0;font-size:14px;color:#9FD7A5;">{{ $role }}</p>
                @endif
                <p style="margin:18px 0;font-size:16px;line-height:1.55;color:#FFFFFF;">{{ $cardMessage }}</p>
                <p style="margin:0;font-size:13px;color:#D5E6F7;">{{ $date->longDate() }}<br>{{ config('school.name') }}</p>
            </td>
        </tr>
    </table>
@endsection

@section('footer')
    @if ($guardian)
        @include('mail.family._footer')
    @else
        <br><br>
        Recibes este correo porque haces parte de la comunidad del colegio.
        <a href="{{ $unsubscribeUrl }}" style="color:#1D5FA8;">Dejar de recibir estas felicitaciones</a>.
    @endif
@endsection
