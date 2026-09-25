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
                    <tr>
                        <td style="background:#104976;padding:20px 28px;color:#FFFFFF;">
                            <strong style="font-size:20px;letter-spacing:.06em;">GENESIS</strong><br>
                            <span style="font-size:13px;color:#9FD7A5;">Centro Educativo Cristiano</span>
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
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
