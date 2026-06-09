<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    body { font-family: sans-serif; font-size: 11px; color: #2d2d2d; line-height: 1.6; }
    .header { background: #FF8100; color: #fff; padding: 18px 22px; border-radius: 6px; margin-bottom: 18px; }
    .header .marca { font-size: 9px; letter-spacing: 2px; text-transform: uppercase; opacity: .9; }
    .header h1 { font-size: 20px; margin: 4px 0 0; font-weight: bold; }
    .header .sub { font-size: 11px; opacity: .95; margin-top: 4px; }
    .intro { background: #FFF7ED; border-left: 3px solid #FF8100; padding: 10px 14px; margin-bottom: 16px; font-size: 10.5px; }
    .clausula { margin-bottom: 12px; }
    .clausula h2 { font-size: 12px; color: #FF8100; margin: 0 0 3px; }
    .clausula p { margin: 0; text-align: justify; }
    .cierre { margin-top: 16px; font-size: 10.5px; font-style: italic; color: #555; }
    .firma { margin-top: 26px; border-top: 1px solid #ddd; padding-top: 14px; }
    .firma-box { background: #f7f7f7; border: 1px solid #e0e0e0; border-radius: 6px; padding: 12px 16px; }
    .firma-box .ok { color: #16A34A; font-weight: bold; }
    .firma-grid td { padding: 3px 0; font-size: 10.5px; }
    .footer { margin-top: 22px; text-align: center; font-size: 8.5px; color: #999; }
    .partes td { font-size: 10.5px; padding: 4px 8px; vertical-align: top; }
    .partes .lbl { color: #888; width: 90px; }
</style>
</head>
<body>
    <div class="header">
        <div class="marca">BigStudio · Agencia de Marketing</div>
        <h1>{{ $contrato->nombre }}</h1>
        <div class="sub">Contrato de prestación de servicios · {{ $fecha }}</div>
    </div>

    @if($contrato->intro)
        <div class="intro">{!! $contrato->intro !!}</div>
    @endif

    <table class="partes" width="100%" style="margin-bottom:14px;">
        <tr>
            <td class="lbl">Prestador:</td>
            <td><b>Inversiones RV SpA</b> · RUT 78.153.109-K · Rep. Ricardo Andrés Villarroel González</td>
        </tr>
        <tr>
            <td class="lbl">Cliente:</td>
            <td><b>{{ $clienteNombre }}</b>{{ $clienteRut ? " · RUT ".$clienteRut : "" }}{{ $clienteEmail ? " · ".$clienteEmail : "" }}</td>
        </tr>
        <tr>
            <td class="lbl">Proyecto:</td>
            <td>{{ $proyecto->titulo }}</td>
        </tr>
    </table>

    @foreach($contrato->clausulas as $cl)
        <div class="clausula">
            <h2>{{ $cl['titulo'] ?? '' }}</h2>
            <p>{!! $cl['contenido'] ?? '' !!}</p>
        </div>
    @endforeach

    @if($contrato->cierre)
        <div class="cierre">{!! $contrato->cierre !!}</div>
    @endif

    <div class="firma">
        @if($proyecto->contrato_firmado_at)
            <div class="firma-box">
                <div class="ok">✓ CONTRATO ACEPTADO ELECTRÓNICAMENTE</div>
                <table class="firma-grid" width="100%">
                    <tr><td><b>Firmante:</b> {{ $proyecto->contrato_firmante }}</td></tr>
                    <tr><td><b>Fecha y hora:</b> {{ $proyecto->contrato_firmado_at->format('d/m/Y H:i') }} hrs</td></tr>
                    <tr><td><b>IP de registro:</b> {{ $proyecto->contrato_firma_ip }}</td></tr>
                </table>
                <div style="font-size:9px;color:#777;margin-top:6px;">Aceptación válida conforme a la Ley N° 19.799 sobre firma electrónica.</div>
            </div>
        @else
            <div class="firma-box" style="border-style:dashed;">
                <div style="color:#999;">Pendiente de aceptación por el Cliente.</div>
            </div>
        @endif
    </div>

    <div class="footer">
        BigStudio · Agencia de Marketing · Inversiones RV SpA · hola@bigstudio.cl · www.bigstudio.cl<br>
        Documento generado el {{ $fecha }}
    </div>
</body>
</html>
