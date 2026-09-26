{{-- Botón de correo compatible con la mayoría de clientes --}}
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:{{ $margin ?? '0 0 20px' }};">
    <tr>
        <td style="background:{{ $color ?? '#F6B91C' }};border-radius:999px;">
            <a href="{{ $url }}" style="display:inline-block;padding:12px 22px;font-weight:700;color:{{ $text ?? '#18324D' }};text-decoration:none;">{{ $label }}</a>
        </td>
    </tr>
</table>
