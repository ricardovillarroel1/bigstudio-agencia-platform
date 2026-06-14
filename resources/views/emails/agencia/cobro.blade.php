@php
    $tipo = $tipo ?? 'factura';
    $nombreCliente = $cliente->nombre ?? $cliente->razon_social ?? 'cliente';
    $monto = (int) ($cobro->monto ?? 0);
    $vence = $cobro->vence_at ? \Carbon\Carbon::parse($cobro->vence_at) : null;

    $facturaEmitida = ($cobro->factura_estado === 'emitida' && $cobro->lioren_folio);

    $cfg = match ($tipo) {
        'recordatorio' => [
            'banner'   => 'Recordatorio de Pago',
            'bannerBg' => '#FF9C00',
            'titulo'   => 'Tu pago vence en 2 días',
            'intro'    => 'Te recordamos que tu cobro mensual está próximo a vencer. Aquí están los detalles:',
        ],
        'vencimiento'  => [
            'banner'   => 'Tu pago vence hoy',
            'bannerBg' => '#EF4444',
            'titulo'   => 'Hoy es el último día de pago',
            'intro'    => 'Hoy vence tu cobro mensual. Para evitar la suspensión del servicio, regulariza tu pago:',
        ],
        default        => [
            'banner'   => 'Factura del Mes',
            'bannerBg' => '#10B981',
            'titulo'   => '¡Tu factura del mes está lista!',
            'intro'    => $facturaEmitida
                ? 'Adjuntamos tu factura del mes. Estos son los detalles de tu cobro:'
                : 'Estos son los detalles de tu cobro mensual:',
        ],
    };
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $cfg['banner'] }} - Big Studio</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f7; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.08);">
                    <!-- Header con gradiente Big Studio -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%); padding:32px 40px; text-align:center;">
                            <img src="https://integration-conector.bigstudio.cl/images/bigstudio-logo-dark.png" alt="Big Studio" width="150" style="display:block; margin:0 auto;">
                        </td>
                    </tr>
                    <!-- Banner del tipo de correo -->
                    <tr>
                        <td style="background:{{ $cfg['bannerBg'] }}; padding:14px 40px; text-align:center;">
                            <p style="margin:0; color:#ffffff; font-size:16px; font-weight:700; letter-spacing:0.3px;">{{ $cfg['banner'] }}</p>
                        </td>
                    </tr>
                    <!-- Cuerpo -->
                    <tr>
                        <td style="padding:40px;">
                            <h1 style="margin:0 0 8px; font-size:21px; color:#1a1a1a; font-weight:700;">{{ $cfg['titulo'] }}</h1>
                            <p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#555;">
                                Hola <strong>{{ $nombreCliente }}</strong>, {{ $cfg['intro'] }}
                            </p>

                            <!-- Tarjeta de detalles -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FFF7EC; border-radius:12px; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:24px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="font-size:14px; color:#888; padding:8px 0; border-bottom:1px solid #FCE4C4;">Concepto</td>
                                                <td style="font-size:14px; color:#1a1a1a; font-weight:600; text-align:right; padding:8px 0; border-bottom:1px solid #FCE4C4;">{{ $cobro->concepto }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:14px; color:#888; padding:8px 0; border-bottom:1px solid #FCE4C4;">Monto</td>
                                                <td style="font-size:20px; color:#FF8100; font-weight:700; text-align:right; padding:8px 0; border-bottom:1px solid #FCE4C4;">${{ number_format($monto, 0, ',', '.') }} CLP</td>
                                            </tr>
                                            @if($vence)
                                            <tr>
                                                <td style="font-size:14px; color:#888; padding:8px 0;">Vencimiento</td>
                                                <td style="font-size:15px; color:{{ $tipo === 'vencimiento' ? '#EF4444' : '#FF9C00' }}; font-weight:700; text-align:right; padding:8px 0;">{{ $vence->format('d/m/Y') }}</td>
                                            </tr>
                                            @endif
                                            @if($facturaEmitida)
                                            <tr>
                                                <td style="font-size:14px; color:#888; padding:8px 0; border-top:1px solid #FCE4C4;">Factura N°</td>
                                                <td style="font-size:14px; color:#1a1a1a; font-weight:600; text-align:right; padding:8px 0; border-top:1px solid #FCE4C4;">{{ $cobro->lioren_folio }}</td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            @if($facturaEmitida && $tipo === 'factura')
                            <p style="margin:0 0 20px; font-size:13px; color:#10B981; text-align:center;">📎 Adjuntamos tu factura en PDF a este correo.</p>
                            @endif

                            <!-- Botón de pago Flow -->
                            @if($cobro->flow_token)
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                                <tr>
                                    <td align="center">
                                        <a href="https://www.flow.cl/app/web/pay.php?token={{ $cobro->flow_token }}" style="display:inline-block; background: linear-gradient(135deg, #FF9C00, #FF8100); color:#ffffff; text-decoration:none; padding:14px 40px; border-radius:8px; font-weight:600; font-size:15px;">Pagar con tarjeta</a>
                                    </td>
                                </tr>
                            </table>
                            @php
                                $pctFlow = (float) config('flow.recargo_pct', 0);
                                $montoFlowCobro = (int) round($cobro->monto * (1 + $pctFlow / 100));
                                $pctFlowTxt = rtrim(rtrim(number_format($pctFlow, 2, ',', ''), '0'), ',');
                            @endphp
                            @if($pctFlow > 0)
                            <p style="margin:0 0 16px; font-size:11px; color:#999; text-align:center;">El pago online incluye un recargo del {{ $pctFlowTxt }}% por costo de pasarela: total ${{ number_format($montoFlowCobro, 0, ',', '.') }}. Por transferencia: ${{ number_format($cobro->monto, 0, ',', '.') }} (sin recargo).</p>
                            @endif
                            @endif

                            <p style="margin:8px 0 16px; font-size:13px; line-height:1.6; color:#888; text-align:center;">
                                También puedes pagar por transferencia bancaria y enviarnos el comprobante por WhatsApp.
                            </p>

                            <!-- Datos de transferencia -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAFAFA; border-left:4px solid #FF8100; border-radius:0 8px 8px 0; margin-bottom:20px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <p style="margin:0 0 8px; font-size:14px; color:#FF8100; font-weight:700;">Datos para transferencia</p>
                                        <p style="margin:0; font-size:13px; line-height:1.9; color:#555;">
                                            Banco: Banco Bci<br>
                                            Tipo: Cuenta Corriente<br>
                                            Nombre: Big Studio<br>
                                            RUT: 78.153.109-K<br>
                                            N° Cuenta: 97580848<br>
                                            Email: <a href="mailto:hola@bigstudio.cl" style="color:#FF8100; text-decoration:none;">hola@bigstudio.cl</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0; font-size:13px; line-height:1.6; color:#999;">
                                Si tienes consultas, escríbenos a <a href="mailto:hola@bigstudio.cl" style="color:#FF8100;">hola@bigstudio.cl</a> o por WhatsApp.
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#1a1a1a; padding:24px 40px; text-align:center;">
                            <img src="https://integration-conector.bigstudio.cl/images/bigstudio-logo-gradient.png" alt="Big Studio" width="100" style="display:block; margin:0 auto 12px; opacity:0.9;">
                            <p style="margin:0 0 6px; font-size:12px; color:#999;">Agencia de Marketing Digital</p>
                            <p style="margin:0; font-size:12px; color:#777;">© {{ date('Y') }} Big Studio · Todos los derechos reservados</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
