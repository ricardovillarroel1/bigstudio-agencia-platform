@php
    $brand600 = '#FF8100';
    $brand500 = '#FF9C00';
    $accent500 = '#FFC800';
    $dark = '#111827';
    $gray700 = '#374151';
    $gray500 = '#6B7280';
    $gray100 = '#F3F4F6';
    $brand50 = '#FFF7EC';
    $brand100 = '#FFEDD0';
    $brand200 = '#FFD89C';

    $descuento = (int) ($cotizacion->descuento_monto ?? 0);
    $netoFinal = (int) ($cotizacion->total_neto ?? 0);
    $subtotal  = $netoFinal + $descuento;
    $total     = (int) ($cotizacion->total ?? round($netoFinal * 1.19));
    $iva       = $total - $netoFinal;

    $logoUrl = url('/images/bigstudio-logo-gradient.png');
    $validaHasta = $cotizacion->valida_hasta ? $cotizacion->valida_hasta->format('d/m/Y') : null;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cotizaci&oacute;n #{{ $cotizacion->numero }}</title>
</head>
<body style="margin:0; padding:0; background:{{ $gray100 }}; font-family:'Helvetica Neue', Helvetica, Arial, sans-serif; color:{{ $dark }};">

<!-- Outer wrapper -->
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:{{ $gray100 }}; padding:32px 12px;">
<tr><td align="center">

    <!-- Main card -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px; background:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 4px 20px rgba(17,24,39,0.08);">

        <!-- Header with gradient + logo -->
        <tr><td style="background:linear-gradient(135deg, {{ $accent500 }} 0%, {{ $brand500 }} 50%, {{ $brand600 }} 100%); padding:32px 32px 28px; text-align:center;">
            <img src="{{ $logoUrl }}" alt="Big Studio" width="80" height="auto" style="display:block; margin:0 auto 12px; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.2));">
            <p style="margin:0; color:#ffffff; font-size:11px; font-weight:700; letter-spacing:3px; text-transform:uppercase; opacity:0.95;">Agencia de Marketing Digital</p>
        </td></tr>

        <!-- COTIZACION badge -->
        <tr><td style="padding:0; background:#ffffff;">
            <div style="background:{{ $dark }}; color:#ffffff; padding:14px 32px; display:block;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td style="font-size:11px; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:{{ $accent500 }};">Cotizaci&oacute;n</td>
                        <td align="right" style="font-size:18px; font-weight:900; color:#ffffff;">N&deg; {{ $cotizacion->numero }}</td>
                    </tr>
                </table>
            </div>
        </td></tr>

        <!-- Greeting -->
        <tr><td style="padding:28px 32px 8px; background:#ffffff;">
            <h2 style="margin:0 0 8px; font-size:18px; font-weight:700; color:{{ $dark }};">
                Hola {{ explode(' ', trim($cotizacion->cliente_nombre))[0] ?? 'cliente' }}, &iexcl;gracias por tu inter&eacute;s!
            </h2>
            <p style="margin:0 0 16px; font-size:14px; line-height:1.55; color:{{ $gray700 }};">
                Te enviamos la cotizaci&oacute;n con los servicios que conversamos. El detalle completo va adjunto en PDF y abajo ves el resumen.
                @if($validaHasta)
                Recuerda que esta propuesta es <strong>v&aacute;lida hasta el {{ $validaHasta }}</strong>.
                @endif
            </p>
        </td></tr>

        <!-- Resumen items -->
        <tr><td style="padding:0 32px 8px; background:#ffffff;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse; border:1px solid #E5E7EB; border-radius:10px; overflow:hidden;">
                <thead>
                    <tr style="background:{{ $dark }};">
                        <th align="left"  style="padding:10px 14px; font-size:10px; font-weight:700; color:#ffffff; letter-spacing:1px; text-transform:uppercase;">Descripci&oacute;n</th>
                        <th align="center" style="padding:10px 8px; font-size:10px; font-weight:700; color:#ffffff; letter-spacing:1px; text-transform:uppercase; width:50px;">Cant.</th>
                        <th align="right" style="padding:10px 14px; font-size:10px; font-weight:700; color:#ffffff; letter-spacing:1px; text-transform:uppercase; width:110px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cotizacion->items as $item)
                    <tr style="background: {{ $loop->even ? '#FAFAFA' : '#ffffff' }};">
                        <td style="padding:11px 14px; font-size:13px; color:{{ $dark }}; border-bottom:1px solid #F3F4F6;">
                            {{ $item->descripcion }}
                            @if($item->codigo)
                                <span style="color:{{ $gray500 }}; font-size:10px; font-family:monospace;"> &middot; {{ $item->codigo }}</span>
                            @endif
                        </td>
                        <td align="center" style="padding:11px 8px; font-size:13px; color:{{ $gray700 }}; border-bottom:1px solid #F3F4F6;">
                            {{ rtrim(rtrim(number_format((float) ($item->cantidad ?? 0), 2, ',', '.'), '0'), ',') }}
                        </td>
                        <td align="right" style="padding:11px 14px; font-size:13px; font-weight:600; color:{{ $dark }}; border-bottom:1px solid #F3F4F6;">
                            ${{ number_format((float) ($item->total_neto ?? 0), 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </td></tr>

        <!-- Totales (compactos) -->
        <tr><td style="padding:14px 32px 8px; background:#ffffff;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="font-size:13px;">
                <tr>
                    <td align="right" style="padding:4px 0; color:{{ $gray500 }};">Subtotal neto</td>
                    <td align="right" style="padding:4px 0; width:120px; color:{{ $dark }}; font-weight:600;">${{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
                @if($descuento > 0)
                <tr>
                    <td align="right" style="padding:4px 0; color:#DC2626;">Descuento</td>
                    <td align="right" style="padding:4px 0; color:#DC2626; font-weight:600;">-${{ number_format($descuento, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr>
                    <td align="right" style="padding:4px 0; color:{{ $gray500 }};">IVA (19%)</td>
                    <td align="right" style="padding:4px 0; color:{{ $dark }}; font-weight:600;">${{ number_format($iva, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="2" style="padding:14px 0 0;">
                        <div style="background:linear-gradient(135deg, {{ $accent500 }} 0%, {{ $brand500 }} 50%, {{ $brand600 }} 100%); padding:14px 18px; border-radius:10px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td style="font-size:12px; font-weight:700; letter-spacing:1.5px; color:#ffffff; text-transform:uppercase;">Total a pagar</td>
                                    <td align="right" style="font-size:22px; font-weight:900; color:#ffffff;">${{ number_format($total, 0, ',', '.') }}</td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </td></tr>

        @if(!empty($flowUrl))
        <!-- CTA Flow -->
        <tr><td align="center" style="padding:24px 32px 8px; background:#ffffff;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr><td align="center" style="background:linear-gradient(135deg, {{ $brand500 }} 0%, {{ $brand600 }} 100%); border-radius:10px; box-shadow:0 4px 12px rgba(255,129,0,0.35);">
                    <a href="{{ $flowUrl }}" style="display:inline-block; padding:16px 44px; color:#ffffff; text-decoration:none; font-size:15px; font-weight:800; letter-spacing:1px; text-transform:uppercase;">
                        &#128179; Pagar ahora con Flow
                    </a>
                </td></tr>
            </table>
            @php
                $pctFlow = (float) config('flow.recargo_pct', 0);
                $montoFlow = (int) round($cotizacion->total * (1 + $pctFlow / 100));
                $pctFlowTxt = rtrim(rtrim(number_format($pctFlow, 2, ',', ''), '0'), ',');
            @endphp
            <p style="margin:10px 0 0; font-size:11px; color:{{ $gray500 }};">Pago seguro con tarjeta de cr&eacute;dito, d&eacute;bito o transferencia</p>
            @if($pctFlow > 0)
            <p style="margin:6px 0 0; font-size:11px; color:{{ $gray500 }};">El pago online incluye un recargo del {{ $pctFlowTxt }}% por costo de pasarela: total <strong>${{ number_format($montoFlow, 0, ',', '.') }}</strong>. Pagando por transferencia bancaria: <strong>${{ number_format($cotizacion->total, 0, ',', '.') }}</strong> (sin recargo).</p>
            @endif
        </td></tr>
        @endif

        <!-- Alternativa: transferencia -->
        <tr><td style="padding:20px 32px 4px; background:#ffffff;">
            <div style="background:{{ $brand50 }}; border:1px solid {{ $brand200 }}; border-radius:10px; padding:16px 18px;">
                <p style="margin:0 0 10px; font-size:10px; font-weight:700; color:{{ $brand600 }}; letter-spacing:1.5px; text-transform:uppercase;">
                    &#127974; O paga por transferencia
                </p>
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="font-size:12px;">
                    <tr><td style="padding:3px 0; color:{{ $gray500 }}; width:130px;">Banco:</td><td style="padding:3px 0; color:{{ $dark }}; font-weight:600;">Banco Bci</td></tr>
                    <tr><td style="padding:3px 0; color:{{ $gray500 }};">Tipo cuenta:</td><td style="padding:3px 0; color:{{ $dark }}; font-weight:600;">Cuenta Corriente</td></tr>
                    <tr><td style="padding:3px 0; color:{{ $gray500 }};">N&uacute;mero:</td><td style="padding:3px 0; color:{{ $dark }}; font-weight:600; font-family:monospace;">97580848</td></tr>
                    <tr><td style="padding:3px 0; color:{{ $gray500 }};">RUT:</td><td style="padding:3px 0; color:{{ $dark }}; font-weight:600; font-family:monospace;">78.153.109-K</td></tr>
                    <tr><td style="padding:3px 0; color:{{ $gray500 }};">Titular:</td><td style="padding:3px 0; color:{{ $dark }}; font-weight:600;">Big Studio</td></tr>
                    <tr><td style="padding:3px 0; color:{{ $gray500 }};">Email:</td><td style="padding:3px 0; color:{{ $dark }}; font-weight:600;">hola@bigstudio.cl</td></tr>
                </table>
            </div>
        </td></tr>

        @if($cotizacion->notas ?? false)
        <!-- Notas -->
        <tr><td style="padding:16px 32px 8px; background:#ffffff;">
            <p style="margin:0 0 6px; font-size:10px; font-weight:700; color:{{ $gray500 }}; letter-spacing:1.5px; text-transform:uppercase;">Notas</p>
            <p style="margin:0; font-size:13px; color:{{ $gray700 }}; line-height:1.5;">{{ $cotizacion->notas }}</p>
        </td></tr>
        @endif

        <!-- Firma -->
        <tr><td style="padding:24px 32px 16px; background:#ffffff;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-top:1px solid #F3F4F6; padding-top:18px;">
                <tr>
                    <td style="width:60px; vertical-align:middle;">
                        <img src="{{ $logoUrl }}" alt="Big Studio" width="48" height="auto" style="display:block;">
                    </td>
                    <td style="vertical-align:middle; padding-left:14px;">
                        <p style="margin:0; font-size:14px; font-weight:700; color:{{ $dark }};">Equipo Big Studio</p>
                        <p style="margin:2px 0 0; font-size:12px; color:{{ $gray500 }};">Agencia de Marketing Digital</p>
                        <p style="margin:6px 0 0; font-size:12px;">
                            <a href="mailto:hola@bigstudio.cl" style="color:{{ $brand600 }}; text-decoration:none; font-weight:600;">hola@bigstudio.cl</a>
                            <span style="color:{{ $gray500 }};"> &middot; </span>
                            <a href="https://www.bigstudio.cl" style="color:{{ $brand600 }}; text-decoration:none; font-weight:600;">bigstudio.cl</a>
                        </p>
                    </td>
                </tr>
            </table>
        </td></tr>

        <!-- Stripe inferior -->
        <tr><td style="background:linear-gradient(90deg, {{ $accent500 }} 0%, {{ $brand500 }} 50%, {{ $brand600 }} 100%); height:6px; padding:0; font-size:0; line-height:0;">&nbsp;</td></tr>

    </table>

    <!-- Footer fuera del card -->
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px; padding:14px 12px;">
        <tr><td align="center" style="font-size:11px; color:{{ $gray500 }}; line-height:1.5;">
            Esta cotizaci&oacute;n fue generada autom&aacute;ticamente por <strong style="color:{{ $brand600 }};">Big Studio</strong>.<br>
            Si tienes preguntas, responde directamente a este correo.
        </td></tr>
    </table>

</td></tr>
</table>

</body>
</html>
