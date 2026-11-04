<!DOCTYPE html>
<html lang="es-CO">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('school.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#EFF6FD;font-family:'Segoe UI',Arial,sans-serif;color:#18324D;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#EFF6FD;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#FFFFFF;border-radius:16px;overflow:hidden;">
                    {{-- Fondo blanco: el escudo es azul oscuro y se perdería sobre el azul. Va incrustado para no depender del dominio. --}}
                    <tr>
                        <td align="center" style="background:#FFFFFF;padding:22px 28px 0;">
                            <img src="{{ $message->embed(resource_path('images/mail/logo.png')) }}" width="88" height="101" alt="Escudo del {{ config('school.name') }}" style="display:block;border:0;">
                            <p style="margin:8px 0 0;font-size:14px;font-weight:700;color:#104976;">{{ config('school.name') }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;font-size:15px;line-height:1.6;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px;background:#F6F9FC;font-size:12px;color:#56708A;line-height:1.5;">
                            {{ config('school.name') }} · {{ config('school.contact.address') }}, {{ config('school.contact.city') }}<br>
                            {{ config('school.contact.phone') }} · {{ config('school.contact.email') }}
                            @yield('footer')
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
