<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tu onboarding BigStudio te espera</title>
</head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:30px 0;">
        <tr><td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.08);">
                <tr><td style="background:linear-gradient(135deg,#FFC800 0%,#FF9C00 50%,#FF8100 100%);padding:30px;">
                    <div style="color:white;font-size:12px;letter-spacing:2px;text-transform:uppercase;opacity:0.9;margin-bottom:8px;">BigStudio · Recordatorio amable</div>
                    <div style="color:white;font-size:22px;font-weight:900;line-height:1.2;">Tu onboarding te está esperando, {{ $proyecto->cliente->nombre ?? '' }} 👋</div>
                </td></tr>

                <tr><td style="padding:30px;color:#374151;font-size:15px;line-height:1.6;">
                    <p style="margin:0 0 15px 0;">
                        Hace <strong>{{ $diasSinAvance }} día{{ $diasSinAvance > 1 ? 's' : '' }}</strong>
                        que no avanzas con el onboarding de <strong>{{ $proyecto->titulo }}</strong>.
                    </p>

                    <p style="margin:0 0 20px 0;">
                        Sabemos que la agenda es brava — pero mientras antes completes el material,
                        antes arrancamos tu proyecto. Llevas
                        <strong style="color:#FF8100;">{{ $proyecto->porcentaje_avance }}%</strong> del camino.
                    </p>

                    <table cellpadding="0" cellspacing="0" style="margin:0 auto 25px;">
                        <tr><td style="background:linear-gradient(135deg,#FF9C00 0%,#FF8100 100%);border-radius:10px;">
                            <a href="{{ $urlPublica }}"
                               style="display:inline-block;padding:14px 32px;color:white;font-weight:bold;font-size:16px;text-decoration:none;">
                               Retomar mi onboarding →
                            </a>
                        </td></tr>
                    </table>

                    <p style="margin:0 0 15px 0;font-size:13px;color:#6b7280;">
                        Tu progreso está guardado — vas a continuar exactamente donde te quedaste.
                    </p>

                    <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;padding:15px;font-size:13px;color:#7c2d12;margin-top:20px;">
                        <strong>¿Te quedaste en algo?</strong> Si tienes dudas sobre qué pedirte o cómo,
                        escríbenos a <a href="mailto:hola@bigstudio.cl" style="color:#FF8100;text-decoration:none;font-weight:bold;">hola@bigstudio.cl</a>
                        y te ayudamos.
                    </div>
                </td></tr>

                <tr><td style="background:#fafafa;padding:20px 30px;text-align:center;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;">
                    BigStudio · <a href="https://www.bigstudio.cl" style="color:#FF8100;text-decoration:none;">www.bigstudio.cl</a>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
