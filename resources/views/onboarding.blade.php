<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Configurar Lioren Integration</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Inter, system-ui, sans-serif;
            background: linear-gradient(135deg, #FFF7EE 0%, #FFFFFF 100%);
            min-height: 100vh;
            color: #1a1a1a;
            padding: 2rem 1rem;
        }
        .wrapper {
            max-width: 640px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #d1fae5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        .icon svg { width: 32px; height: 32px; color: #059669; }
        h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .subtitle {
            color: #6b7280;
            font-size: 0.95rem;
        }
        .subtitle strong { color: #1a1a1a; font-weight: 600; }
        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            padding: 1.75rem 1.5rem;
            margin-bottom: 1.25rem;
        }
        .card-new {
            background: linear-gradient(135deg, #FFF7EE, #FFFEFB);
            border: 2px solid #FFD9A8;
        }
        .card-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            font-size: 1.05rem;
            font-weight: 700;
        }
        .card-title .step {
            background: #FF8100;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 700;
        }
        .card-new .card-title { color: #78350F; }
        .card-new .card-title .step { background: #FF8100; }
        .steps {
            margin-bottom: 1.25rem;
            color: #555;
            font-size: 0.92rem;
            line-height: 1.65;
        }
        .steps ol {
            padding-left: 1.5rem;
            margin-top: 0.5rem;
        }
        .steps ol li { margin-bottom: 0.35rem; }
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.85rem 1.75rem;
            background: #FF8100;
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 700;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            transition: background 0.15s;
            width: 100%;
        }
        .btn-primary:hover { background: #E67400; }
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            background: white;
            color: #FF8100;
            text-decoration: none;
            border: 1.5px solid #FF8100;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-secondary:hover { background: #FFF7EE; }
        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
            color: #9ca3af;
            font-size: 0.85rem;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }
        .alert {
            border-radius: 0.6rem;
            padding: 0.85rem 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert ul { margin-left: 1.25rem; }
        .field { margin-bottom: 1rem; }
        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.35rem;
        }
        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 0.7rem 0.85rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            background: white;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        input:focus {
            border-color: #FF8100;
            box-shadow: 0 0 0 3px rgba(255, 129, 0, 0.15);
        }
        .mono { font-family: ui-monospace, "SF Mono", Menlo, monospace; font-size: 0.85rem; }
        .help { font-size: 0.78rem; color: #6b7280; margin-top: 0.3rem; }
        .help a { color: #FF8100; text-decoration: underline; }
        .footer {
            text-align: center;
            font-size: 0.8rem;
            color: #9ca3af;
            margin-top: 1.5rem;
        }
        .footer a { color: #FF8100; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1>¡Conexión con Shopify exitosa!</h1>
            <p class="subtitle">
                Tu tienda <strong>{{ $config->shopify_tienda ?? '' }}</strong> ya está vinculada.
            </p>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Cliente NUEVO --}}
        <div class="card card-new">
            <div class="card-title">
                <span class="step">1</span>
                <span>¿Primera vez en BigStudio?</span>
            </div>
            <div class="steps">
                Para activar la facturación electrónica necesitas una cuenta en BigStudio,
                un servicio externo de facturación electrónica para comerciantes chilenos.
                <br><br>
                Si aún no tienes cuenta, escríbenos para activarla:
            </div>
            <a href="mailto:hola@bigstudio.cl?subject=Quiero activar mi cuenta BigStudio" class="btn-primary">
                Contactar a hola@bigstudio.cl
            </a>
        </div>

        <div class="divider">¿Ya tienes cuenta BigStudio? Conéctala abajo</div>

        {{-- Cliente CON cuenta --}}
        <div class="card">
            <div class="card-title">
                <span class="step">2</span>
                <span>Conecta tu cuenta BigStudio</span>
            </div>

            <form method="POST" action="{{ route('appstore.onboarding.store') }}">
                @csrf

                <div class="field">
                    <label for="bigstudio_email">Email de tu cuenta BigStudio</label>
                    <input type="email" name="bigstudio_email" id="bigstudio_email"
                           value="{{ old('bigstudio_email') }}" required
                           placeholder="tu@empresa.cl">
                </div>

                <div class="field">
                    <label for="connector_key">BigStudio Connector Key</label>
                    <input type="text" name="connector_key" id="connector_key"
                           class="mono"
                           value="{{ old('connector_key') }}" required
                           placeholder="bsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                    <p class="help">
                        Encuéntrala en <a href="https://integration-conector.bigstudio.cl/cliente/profile" target="_blank" rel="noopener">tu perfil de BigStudio</a> → sección "App de Shopify".
                    </p>
                </div>

                <button type="submit" class="btn-primary">Activar integración</button>
            </form>
        </div>

        <div class="footer">
            ¿Dudas? Escríbenos a <a href="mailto:hola@bigstudio.cl">hola@bigstudio.cl</a>
        </div>
    </div>
</body>
</html>
