<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lioren Integration — Panel</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Inter, system-ui, sans-serif;
            background: #f6f7f9;
            color: #1a1a1a;
            line-height: 1.5;
        }
        .topbar {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .topbar .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
            color: #FF8100;
        }
        .topbar .brand-mark {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, #FF8100, #FFC800);
            border-radius: 8px;
        }
        .topbar .user {
            font-size: 0.9rem;
            color: #666;
        }
        .topbar .user a {
            color: #FF8100;
            text-decoration: none;
            margin-left: 1rem;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2rem;
        }
        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
        }
        h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .subtitle {
            color: #666;
            margin-bottom: 2rem;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 700px) { .grid { grid-template-columns: 1fr; } }
        .card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.85rem;
            padding: 1.5rem;
        }
        .card h2 {
            font-size: 0.85rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        .card .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111;
        }
        .card .meta {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 0.4rem;
        }
        .status-pill {
            display: inline-block;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .section {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.85rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .section h3 {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.95rem;
        }
        .row:last-child { border-bottom: none; }
        .row .label { color: #6b7280; }
        .row .val { color: #111; font-weight: 500; }
        .btn {
            display: inline-block;
            padding: 0.65rem 1.25rem;
            background: #FF8100;
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
        }
        .btn:hover { background: #E67400; }
        .btn-secondary {
            background: white;
            color: #FF8100;
            border: 1px solid #FF8100;
        }
        .btn-secondary:hover { background: #FFF7EE; }
        .help {
            background: #FFF7EE;
            border-radius: 0.85rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .help h3 { color: #B45309; margin-bottom: 0.5rem; }
        .help p { font-size: 0.9rem; color: #78350F; }
        .help a { color: #FF8100; text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        th { text-align: left; padding: 0.5rem; color: #6b7280; font-weight: 600; background: #f9fafb; }
        td { padding: 0.65rem 0.5rem; border-top: 1px solid #f3f4f6; }
        .empty {
            text-align: center;
            padding: 2rem;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="brand">
            <div class="brand-mark"></div>
            <span>Lioren Integration</span>
        </div>
        <div class="user">
            {{ $emailContacto ?? Auth::user()->email }}
            <form action="{{ route('logout') }}" method="POST" style="display:inline">
                @csrf
                <button type="submit" style="background:none;border:none;color:#FF8100;cursor:pointer;font-size:0.9rem;margin-left:1rem">Cerrar sesión</button>
            </form>
        </div>
    </div>

    <div class="container">
        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        <h1>Panel de la integración</h1>
        <p class="subtitle">Sincronización Shopify → Lioren · facturación electrónica automática</p>

        <div class="grid">
            <div class="card">
                <h2>Tienda Shopify</h2>
                <div class="value" style="font-size:1.1rem">{{ $config->shopify_tienda ?? '—' }}</div>
                <div class="meta">
                    Estado:
                    @if($config && $config->activo)
                        <span class="status-pill status-active">Activa</span>
                    @else
                        <span class="status-pill status-inactive">Inactiva</span>
                    @endif
                </div>
            </div>
            <div class="card">
                <h2>Lioren</h2>
                <div class="value" style="font-size:1.1rem">
                    @if($config && $config->lioren_api_key) Conectado @else No conectado @endif
                </div>
                <div class="meta">
                    @if($config && $config->lioren_api_key)
                        API Key configurada
                    @else
                        Falta API Key — <a href="{{ route('appstore.onboarding') }}" style="color:#FF8100">configurar ahora</a>
                    @endif
                </div>
            </div>
            <div class="card">
                <h2>Documentos emitidos (últimos 30 días)</h2>
                <div class="value">{{ $documentosEmitidosUltimos30 ?? 0 }}</div>
                <div class="meta">Boletas y facturas generadas en Lioren desde pedidos de Shopify</div>
            </div>
            <div class="card">
                <h2>Pedidos sincronizados</h2>
                <div class="value">{{ $pedidosSincronizadosTotal ?? 0 }}</div>
                <div class="meta">Total histórico de pedidos procesados por la integración</div>
            </div>
        </div>

        <div class="section">
            <h3>Cómo funciona la integración</h3>
            <div class="row"><span class="label">1.</span><span class="val">El cliente paga su pedido en tu tienda Shopify</span></div>
            <div class="row"><span class="label">2.</span><span class="val">La app recibe el webhook orders/paid de Shopify</span></div>
            <div class="row"><span class="label">3.</span><span class="val">Si el cliente solicitó factura (campo en checkout), se emite Factura tipo 33; si no, Boleta tipo 39</span></div>
            <div class="row"><span class="label">4.</span><span class="val">La emisión se realiza vía la API de Lioren al SII</span></div>
            <div class="row"><span class="label">5.</span><span class="val">El DTE generado se envía al email del cliente</span></div>
        </div>

        <div class="section">
            <h3>Configuración</h3>
            <div class="row">
                <span class="label">API Key Lioren</span>
                <span class="val">{{ $config && $config->lioren_api_key ? substr($config->lioren_api_key, 0, 6) . '••••••••' : 'No configurada' }}</span>
            </div>
            <div class="row">
                <span class="label">Conectado el</span>
                <span class="val">{{ $config && $config->oauth_installed_at ? $config->oauth_installed_at->format('d/m/Y H:i') : '—' }}</span>
            </div>
            <div class="row">
                <span class="label">Nombre de contacto</span>
                <span class="val">{{ $nombreContacto ?? Auth::user()->name }}</span>
            </div>
            <div class="row">
                <span class="label">Email de contacto</span>
                <span class="val">{{ $emailContacto ?? Auth::user()->email }}</span>
            </div>
            <div style="margin-top:1rem">
                <a href="{{ route('appstore.onboarding') }}" class="btn btn-secondary">Editar configuración</a>
            </div>
        </div>

        <div class="help">
            <h3>¿Necesitas ayuda?</h3>
            <p>
                Escríbenos a <a href="mailto:hola@bigstudio.cl">hola@bigstudio.cl</a> o consulta
                la <a href="/privacy">política de privacidad</a> y los <a href="/terms">términos de servicio</a>.
            </p>
        </div>
    </div>
</body>
</html>
