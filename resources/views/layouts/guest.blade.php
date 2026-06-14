<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Integraciones Big Studio') }}</title>

        <!-- Fonts: Inter (body) — Mostin (títulos) viene en el CSS compilado -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" referrerpolicy="no-referrer" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            * { box-sizing: border-box; }
            html, body { margin: 0; padding: 0; }
            body {
                font-family: 'Inter', 'Figtree', system-ui, -apple-system, sans-serif;
                background: #f8fafc;
                color: #1e293b;
                -webkit-font-smoothing: antialiased;
                min-height: 100vh;
            }

            .auth-shell { display: flex; min-height: 100vh; }

            /* ===== Panel de marca (izquierda) ===== */
            .auth-brand {
                display: none;
                position: relative;
                width: 44%;
                min-height: 100vh;
                background: linear-gradient(180deg, #0f0f0f 0%, #1a1a2e 100%);
                color: #fff;
                padding: 3.5rem 3.25rem;
                flex-direction: column;
                justify-content: space-between;
                overflow: hidden;
            }
            @media (min-width: 1024px) { .auth-brand { display: flex; } }
            .auth-brand::before {
                content: '';
                position: absolute;
                top: 0; left: 0; right: 0;
                height: 4px;
                background: linear-gradient(90deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);
            }
            .auth-brand::after {
                content: '';
                position: absolute;
                bottom: -180px; right: -180px;
                width: 460px; height: 460px;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(255,156,0,0.16) 0%, rgba(255,129,0,0.05) 45%, transparent 70%);
                pointer-events: none;
            }
            .auth-brand-logo { display: flex; align-items: center; gap: 0.85rem; position: relative; z-index: 1; }
            .auth-brand-logo img { height: 44px; width: auto; border-radius: 8px; }
            .auth-brand-logo span {
                font-family: 'Mostin', 'Inter', system-ui, sans-serif;
                font-weight: 900;
                font-size: 1.3rem;
                letter-spacing: 0.02em;
            }
            .auth-brand-center { position: relative; z-index: 1; }
            .auth-brand-center h1 {
                font-family: 'Mostin', 'Inter', system-ui, sans-serif;
                font-weight: 900;
                font-size: 2.35rem;
                line-height: 1.12;
                margin: 0 0 1rem;
                letter-spacing: -0.01em;
            }
            .auth-brand-center h1 em {
                font-style: normal;
                background: linear-gradient(90deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }
            .auth-brand-center p { color: #94a3b8; font-size: 0.95rem; line-height: 1.65; margin: 0 0 2rem; max-width: 40ch; }
            .auth-feature { display: flex; align-items: flex-start; gap: 0.8rem; margin-bottom: 1.1rem; }
            .auth-feature i {
                width: 34px; height: 34px; flex-shrink: 0;
                display: flex; align-items: center; justify-content: center;
                border-radius: 9px;
                background: rgba(255,156,0,0.12);
                color: #FF9C00;
                font-size: 0.85rem;
            }
            .auth-feature div strong { display: block; font-size: 0.875rem; font-weight: 600; color: #e2e8f0; }
            .auth-feature div span { font-size: 0.8rem; color: #64748b; }
            .auth-brand-footer { position: relative; z-index: 1; font-size: 0.78rem; color: #475569; }
            .auth-brand-footer a { color: #94a3b8; text-decoration: none; }

            /* ===== Panel del formulario (derecha) ===== */
            .auth-panel {
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 2.5rem 1.5rem;
            }
            .auth-mobile-logo { display: flex; align-items: center; gap: 0.7rem; margin-bottom: 1.75rem; }
            @media (min-width: 1024px) { .auth-mobile-logo { display: none; } }
            .auth-mobile-logo img { height: 40px; border-radius: 8px; }
            .auth-mobile-logo span { font-family: 'Mostin', 'Inter', sans-serif; font-weight: 900; font-size: 1.1rem; color: #0f172a; }

            .auth-card {
                width: 100%;
                max-width: 460px;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 18px;
                box-shadow: 0 10px 30px -12px rgba(15, 23, 42, 0.12), 0 4px 10px -6px rgba(15, 23, 42, 0.06);
                padding: 2.25rem 2.25rem 2rem;
            }
            .auth-card.wide { max-width: 600px; }

            /* ===== Sistema de formulario ===== */
            .auth-title {
                font-family: 'Mostin', 'Inter', system-ui, sans-serif;
                font-weight: 900;
                font-size: 1.55rem;
                color: #0f172a;
                margin: 0 0 0.3rem;
                letter-spacing: -0.01em;
            }
            .auth-subtitle { font-size: 0.875rem; color: #64748b; margin: 0 0 1.6rem; }
            .auth-label { display: block; font-size: 0.8rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem; }
            .auth-input {
                width: 100%;
                padding: 0.66rem 0.9rem;
                font-size: 0.9rem;
                font-family: inherit;
                color: #0f172a;
                background: #fff;
                border: 1.5px solid #e2e8f0;
                border-radius: 10px;
                outline: none;
                transition: border-color .15s ease, box-shadow .15s ease;
            }
            .auth-input::placeholder { color: #94a3b8; }
            .auth-input:hover { border-color: #cbd5e1; }
            .auth-input:focus { border-color: #FF9C00; box-shadow: 0 0 0 4px rgba(255, 156, 0, 0.14); }
            .auth-field { margin-bottom: 1.05rem; }
            .auth-hint { font-size: 0.72rem; color: #94a3b8; margin-top: 0.3rem; }

            .auth-section {
                font-size: 0.72rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.09em;
                color: #FF8100;
                margin: 1.5rem 0 0.9rem;
                padding-bottom: 0.45rem;
                border-bottom: 1px solid #f1f5f9;
            }
            .auth-section:first-of-type { margin-top: 0.25rem; }

            .auth-btn {
                width: 100%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                padding: 0.8rem 1rem;
                margin-top: 0.4rem;
                font-family: inherit;
                font-size: 0.85rem;
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                color: #fff;
                background: linear-gradient(135deg, #FF9C00 0%, #FF8100 100%);
                border: none;
                border-radius: 10px;
                cursor: pointer;
                box-shadow: 0 4px 14px -4px rgba(255, 129, 0, 0.45);
                transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
            }
            .auth-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 20px -6px rgba(255, 129, 0, 0.55); filter: brightness(1.03); }
            .auth-btn:active { transform: translateY(0); }
            .auth-btn:focus-visible { outline: 3px solid rgba(255,156,0,.35); outline-offset: 2px; }

            .auth-row { display: flex; align-items: center; justify-content: space-between; margin: 0.2rem 0 1.1rem; }
            .auth-check { display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.82rem; color: #475569; cursor: pointer; }
            .auth-check input { width: 15px; height: 15px; accent-color: #FF8100; cursor: pointer; }
            .auth-link { font-size: 0.82rem; font-weight: 600; color: #FF8100; text-decoration: none; transition: color .15s ease; }
            .auth-link:hover { color: #e07000; text-decoration: underline; }

            .auth-alt { margin-top: 1.4rem; padding-top: 1.2rem; border-top: 1px solid #f1f5f9; text-align: center; font-size: 0.84rem; color: #64748b; }

            .auth-grid-2 { display: grid; grid-template-columns: 1fr; gap: 0 1rem; }
            @media (min-width: 640px) { .auth-grid-2 { grid-template-columns: 1fr 1fr; } }

            .auth-error { font-size: 0.78rem; color: #dc2626; margin-top: 0.3rem; }
            .auth-status { font-size: 0.82rem; color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 0.6rem 0.9rem; margin-bottom: 1rem; }

            .auth-panel-footer { margin-top: 1.6rem; font-size: 0.74rem; color: #94a3b8; text-align: center; }
        </style>
    </head>
    <body>
        <x-meta-pixel />
        <div class="auth-shell">
            <!-- Panel de marca -->
            <aside class="auth-brand">
                <div class="auth-brand-logo">
                    <img src="{{ asset('images/logo.jpeg') }}" alt="Big Studio">
                    <span>BIG STUDIO</span>
                </div>
                <div class="auth-brand-center">
                    <h1>Tu operación,<br><em>en piloto automático</em></h1>
                    <p>Conecta Shopify con tu facturación electrónica y administra tu negocio desde un solo lugar.</p>
                    <div class="auth-feature">
                        <i class="fas fa-file-invoice"></i>
                        <div>
                            <strong>Boletas y facturas automáticas</strong>
                            <span>Cada venta queda documentada al instante</span>
                        </div>
                    </div>
                    <div class="auth-feature">
                        <i class="fas fa-cart-shopping"></i>
                        <div>
                            <strong>Integración Shopify + Lioren</strong>
                            <span>Pedidos, inventario y DTE sincronizados</span>
                        </div>
                    </div>
                    <div class="auth-feature">
                        <i class="fas fa-headset"></i>
                        <div>
                            <strong>Soporte cercano</strong>
                            <span>Un equipo real detrás de tu operación</span>
                        </div>
                    </div>
                </div>
                <div class="auth-brand-footer">
                    © {{ date('Y') }} Big Studio · Agencia de Marketing Digital · <a href="https://www.bigstudio.cl" target="_blank" rel="noopener">bigstudio.cl</a>
                </div>
            </aside>

            <!-- Panel del formulario -->
            <main class="auth-panel">
                <div class="auth-mobile-logo">
                    <img src="{{ asset('images/logo.jpeg') }}" alt="Big Studio">
                    <span>BIG STUDIO</span>
                </div>
                {{ $slot }}
                <div class="auth-panel-footer">Integraciones Big Studio · Plataforma segura</div>
            </main>
        </div>
    </body>
</html>
