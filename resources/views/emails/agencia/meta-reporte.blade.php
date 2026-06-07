<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0; padding:0; background:#f4f4f7; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    @php $fmt = fn($n) => '$' . number_format((int)$n, 0, ',', '.'); @endphp
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7; padding:24px 0;">
        <tr><td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,.08);">
                <tr>
                    <td style="background:linear-gradient(135deg,#FFC800 0%,#FF9C00 50%,#FF8100 100%); padding:32px 40px; text-align:center;">
                        <img src="{{ url('images/bigstudio-logo-dark.png') }}" alt="Big Studio" width="150" style="display:block; margin:0 auto;">
                    </td>
                </tr>
                <tr><td style="padding:40px;">
                    <h1 style="margin:0 0 8px; font-size:21px; color:#1a1a1a;">Hola {{ $nombre }} 👋</h1>
                    <p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#555;">
                        Tu reporte de campañas publicitarias de <strong>{{ $mesLabel }}</strong> ya está listo. Aquí un resumen:
                    </p>

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FFF7EC; border-radius:12px; margin-bottom:24px;">
                        <tr><td style="padding:24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="font-size:14px; color:#888; padding:8px 0; border-bottom:1px solid #FCE4C4;">Inversión</td>
                                    <td style="font-size:15px; color:#1a1a1a; font-weight:700; text-align:right; padding:8px 0; border-bottom:1px solid #FCE4C4;">{{ $fmt($inversion) }}</td>
                                </tr>
                                <tr>
                                    <td style="font-size:14px; color:#888; padding:8px 0; border-bottom:1px solid #FCE4C4;">Ventas generadas</td>
                                    <td style="font-size:18px; color:#059669; font-weight:800; text-align:right; padding:8px 0; border-bottom:1px solid #FCE4C4;">{{ $fmt($ventas) }}</td>
                                </tr>
                                <tr>
                                    <td style="font-size:14px; color:#888; padding:8px 0; border-bottom:1px solid #FCE4C4;">Compras</td>
                                    <td style="font-size:15px; color:#1a1a1a; font-weight:700; text-align:right; padding:8px 0; border-bottom:1px solid #FCE4C4;">{{ number_format($compras,0,',','.') }}</td>
                                </tr>
                                <tr>
                                    <td style="font-size:14px; color:#888; padding:8px 0;">Retorno (ROAS)</td>
                                    <td style="font-size:20px; color:#FF8100; font-weight:800; text-align:right; padding:8px 0;">{{ $roas }}x</td>
                                </tr>
                            </table>
                        </td></tr>
                    </table>

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                        <tr><td align="center">
                            <a href="{{ $link }}" style="display:inline-block; background:linear-gradient(135deg,#FF9C00,#FF8100); color:#fff; text-decoration:none; padding:15px 44px; border-radius:10px; font-weight:700; font-size:16px;">
                                Ver reporte completo
                            </a>
                        </td></tr>
                    </table>
                    <p style="margin:0; font-size:12px; color:#999; text-align:center;">
                        Reporte interactivo con el detalle de cada campaña, embudo de conversión y más.
                    </p>
                </td></tr>
                <tr>
                    <td style="background:#1a1a1a; padding:24px 40px; text-align:center;">
                        <p style="margin:0 0 6px; font-size:13px; color:#fff; font-weight:600;">Big Studio · Agencia de Marketing Digital</p>
                        <p style="margin:0; font-size:11px; color:#777;">Este es un reporte automático de tus campañas en Meta Ads.</p>
                    </td>
                </tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
