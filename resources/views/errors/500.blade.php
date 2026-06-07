<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error del servidor — Lioren Integration</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Inter, system-ui, sans-serif;
            background: linear-gradient(135deg, #FFF7EE 0%, #FFFFFF 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: #1a1a1a;
        }
        .card {
            background: #fff;
            border-radius: 1.25rem;
            box-shadow: 0 10px 40px rgba(255, 129, 0, 0.08);
            padding: 3rem 2.5rem;
            max-width: 480px;
            text-align: center;
        }
        .code {
            font-size: 5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #FF8100, #FFC800);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.75rem; }
        p { color: #666; line-height: 1.5; margin-bottom: 2rem; font-size: 0.95rem; }
        .btn {
            display: inline-block;
            background: #FF8100;
            color: white;
            text-decoration: none;
            padding: 0.85rem 2rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn:hover { background: #E67400; }
        .footer { margin-top: 2rem; font-size: 0.8rem; color: #999; }
        .footer a { color: #FF8100; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <div class="code">500</div>
        <h1>Algo salió mal</h1>
        <p>Tuvimos un problema procesando tu solicitud. Nuestro equipo ya fue notificado. Por favor intenta de nuevo en unos minutos.</p>
        <a class="btn" href="/">Volver al inicio</a>
        <div class="footer">
            ¿Sigue sin funcionar? <a href="mailto:hola@bigstudio.cl">hola@bigstudio.cl</a>
        </div>
    </div>
</body>
</html>
