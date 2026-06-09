<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Contrato firmado</title></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:30px 0;">
        <tr><td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.08);">
                <tr><td style="background:linear-gradient(135deg,#FFC800 0%,#FF9C00 50%,#FF8100 100%);padding:30px;">
                    <div style="color:white;font-size:12px;letter-spacing:2px;text-transform:uppercase;opacity:0.9;margin-bottom:8px;">BigStudio · Agencia de Marketing</div>
                    <div style="color:white;font-size:22px;font-weight:900;line-height:1.2;">✓ Tu contrato quedó firmado</div>
                </td></tr>
                <tr><td style="padding:30px;color:#374151;font-size:15px;line-height:1.6;">
                    <p style="margin:0 0 15px 0;">Hola <strong>{{ $proyecto->cliente->nombre ?? '' }}</strong>,</p>
                    <p style="margin:0 0 15px 0;">Recibimos tu aceptación del contrato de servicio para <strong>{{ $proyecto->titulo }}</strong>. Adjuntamos una copia en PDF con el registro de tu firma electrónica.</p>
                    <table width="100%" cellpadding="8" cellspacing="0" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;margin:15px 0;">
                        <tr><td style="font-size:13px;color:#166534;">
                            <strong>Firmado por:</strong> {{ $proyecto->contrato_firmante }}<br>
                            <strong>Fecha:</strong> {{ $proyecto->contrato_firmado_at?->format('d/m/Y H:i') }} hrs<br>
                            <strong>Validez:</strong> Firma electrónica conforme a la Ley N° 19.799
                        </td></tr>
                    </table>
                    <p style="margin:0;font-size:14px;">Ya puedes continuar completando tu onboarding. ¿Dudas? <a href="mailto:hola@bigstudio.cl" style="color:#FF8100;">hola@bigstudio.cl</a></p>
                </td></tr>
                <tr><td style="background:#fafafa;padding:15px 30px;text-align:center;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;">Inversiones RV SpA · BigStudio · www.bigstudio.cl</td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
