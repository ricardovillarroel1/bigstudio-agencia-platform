<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demo - Big Studio Integraciones</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
        }
        .login-container {
            background: #fff; border-radius: 16px; padding: 48px 40px;
            width: 100%; max-width: 420px; box-shadow: 0 25px 50px rgba(0,0,0,0.25);
        }
        .login-header { text-align: center; margin-bottom: 32px; }
        .login-header .icon {
            width: 64px; height: 64px; background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 16px; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px; font-size: 28px; color: #fff;
        }
        .login-header h1 { font-size: 22px; font-weight: 700; color: #1e293b; }
        .login-header p { font-size: 14px; color: #64748b; margin-top: 6px; }
        .demo-badge {
            display: inline-block; background: #fef3c7; color: #92400e;
            padding: 4px 12px; border-radius: 20px; font-size: 11px;
            font-weight: 600; text-transform: uppercase; letter-spacing: 1px;
            margin-top: 12px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px; }
        .form-group input {
            width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px;
            font-size: 14px; font-family: inherit; transition: border-color 0.15s;
        }
        .form-group input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .error { color: #dc2626; font-size: 12px; margin-top: 6px; }
        .btn-submit {
            width: 100%; padding: 12px; background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff; border: none; border-radius: 8px; font-size: 14px;
            font-weight: 600; cursor: pointer; transition: opacity 0.15s;
            font-family: inherit;
        }
        .btn-submit:hover { opacity: 0.9; }
        .footer-text { text-align: center; margin-top: 24px; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="icon">🏪</div>
            <h1>Big Studio Integraciones</h1>
            <p>Integración Shopify + Facturación Electrónica</p>
            <div class="demo-badge">Modo Demostración</div>
        </div>
        <form method="POST" action="{{ route('demo.authenticate') }}" class="demo-login-form">
            @csrf
            <div class="form-group">
                <label for="password">Clave de Acceso</label>
                <input type="password" id="password" name="password" placeholder="Ingresa la clave de demo" required autofocus>
                @if($errors->has('password'))
                    <div class="error">{{ $errors->first('password') }}</div>
                @endif
            </div>
            <button type="submit" class="btn-submit">Acceder al Demo</button>
        </form>
        <p class="footer-text">Solicita la clave de acceso a tu ejecutivo comercial</p>
    </div>
</body>
</html>
