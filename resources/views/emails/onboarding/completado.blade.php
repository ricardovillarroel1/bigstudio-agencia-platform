<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Onboarding completado</title>
</head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:30px 0;">
        <tr><td align="center">
            <table width="700" cellpadding="0" cellspacing="0" style="background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.08);">
                <tr><td style="background:linear-gradient(135deg,#FFC800 0%,#FF9C00 50%,#FF8100 100%);padding:25px 30px;">
                    <div style="color:white;font-size:12px;letter-spacing:2px;text-transform:uppercase;opacity:0.9;margin-bottom:6px;">BigStudio · Onboarding completado</div>
                    <div style="color:white;font-size:22px;font-weight:900;line-height:1.2;">🎉 {{ $proyecto->cliente->nombre ?? '' }} marcó su material como listo</div>
                </td></tr>

                <tr><td style="padding:25px 30px;color:#374151;font-size:14px;line-height:1.6;">
                    <table width="100%" cellpadding="8" cellspacing="0" style="background:#fafafa;border-radius:8px;margin-bottom:20px;">
                        <tr><td style="font-weight:bold;width:140px;">Proyecto:</td><td>{{ $proyecto->titulo }}</td></tr>
                        <tr><td style="font-weight:bold;">Cliente:</td><td>{{ $proyecto->cliente->nombre ?? '—' }}</td></tr>
                        <tr><td style="font-weight:bold;">Plantilla:</td><td>{{ $proyecto->plantilla->nombre ?? '—' }}</td></tr>
                        <tr><td style="font-weight:bold;">Completado:</td><td>{{ $proyecto->fecha_completado?->format('d/m/Y H:i') }}</td></tr>
                        <tr><td style="font-weight:bold;">Avance:</td><td><strong style="color:#10b981;">{{ $proyecto->porcentaje_avance }}%</strong></td></tr>
                    </table>

                    <h3 style="color:#FF8100;margin:25px 0 10px 0;font-size:16px;">Respuestas del cliente</h3>

                    @foreach($secciones as $seccion)
                        @php
                            $resps = $respuestas[$seccion['key']] ?? [];
                            $tieneAlgo = !empty($resps);
                        @endphp
                        @if($tieneAlgo)
                            <div style="margin-bottom:20px;padding:12px;background:#FFF7ED;border-left:3px solid #FF8100;border-radius:4px;">
                                <div style="font-weight:bold;color:#7c2d12;margin-bottom:8px;">{{ $seccion['titulo'] }}</div>
                                @foreach(($seccion['campos'] ?? []) as $campo)
                                    @if(isset($resps[$campo['key']]) && trim((string)$resps[$campo['key']]) !== '')
                                        <div style="font-size:13px;margin-bottom:6px;">
                                            <span style="color:#6b7280;">{{ $campo['label'] }}:</span>
                                            <span style="color:#1f2937;">{{ \Illuminate\Support\Str::limit((string)$resps[$campo['key']], 200) }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    @endforeach

                    <table cellpadding="0" cellspacing="0" style="margin:25px auto 10px;">
                        <tr><td style="background:linear-gradient(135deg,#FF9C00 0%,#FF8100 100%);border-radius:10px;">
                            <a href="{{ $adminUrl }}"
                               style="display:inline-block;padding:12px 28px;color:white;font-weight:bold;font-size:15px;text-decoration:none;">
                               Ver en el panel admin →
                            </a>
                        </td></tr>
                    </table>
                </td></tr>

                <tr><td style="background:#fafafa;padding:15px 30px;text-align:center;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;">
                    Notificación automática del portal de Onboarding · BigStudio
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
