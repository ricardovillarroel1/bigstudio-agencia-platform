<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DEMO - Big Studio Integraciones</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; min-height: 100vh; display: flex; }
        
        /* Demo Banner */
        .demo-banner {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            background: linear-gradient(90deg, #f59e0b, #d97706);
            color: #fff; text-align: center; padding: 8px 16px;
            font-size: 13px; font-weight: 600;
            display: flex; align-items: center; justify-content: center; gap: 12px;
        }
        .demo-banner a { color: #fff; text-decoration: underline; font-weight: 700; }
        .demo-banner .demo-badge {
            background: rgba(255,255,255,0.25); padding: 2px 10px; border-radius: 12px;
            font-size: 11px; text-transform: uppercase; letter-spacing: 1px;
        }

        /* Sidebar */
        .sidebar {
            width: 260px; background: #0f172a; min-height: 100vh;
            padding-top: 44px; /* space for banner */
            position: fixed; left: 0; top: 0; bottom: 0;
            display: flex; flex-direction: column;
            transition: transform 0.3s ease;
            z-index: 900;
        }
        .sidebar-brand {
            padding: 20px 20px 16px; border-bottom: 1px solid #1e293b;
        }
        .sidebar-brand h2 { color: #fff; font-size: 16px; font-weight: 700; }
        .sidebar-brand p { color: #64748b; font-size: 11px; margin-top: 2px; }
        .sidebar-nav { padding: 12px 0; flex: 1; overflow-y: auto; }
        .sidebar-link {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 20px; color: #94a3b8; text-decoration: none;
            font-size: 13px; transition: all 0.15s;
        }
        .sidebar-link:hover { background: #1e293b; color: #e2e8f0; }
        .sidebar-link.active { background: #1e293b; color: #fff; border-left: 3px solid #6366f1; }
        .sidebar-link i { width: 18px; text-align: center; font-size: 14px; }
        .sidebar-section { padding: 16px 20px 6px; color: #475569; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
        .sidebar-user {
            padding: 16px 20px; border-top: 1px solid #1e293b;
        }
        .sidebar-user-name { color: #e2e8f0; font-size: 13px; font-weight: 600; }
        .sidebar-user-email { color: #64748b; font-size: 11px; }

        /* Main Content */
        .main-content {
            margin-left: 260px; flex: 1; padding-top: 44px; /* space for banner */
            min-height: 100vh;
        }
        .top-bar {
            background: #fff; padding: 12px 24px; border-bottom: 1px solid #e2e8f0;
            display: flex; align-items: center; justify-content: space-between;
        }
        .top-bar h2 { font-size: 16px; font-weight: 600; color: #1e293b; }
        .top-bar .date { font-size: 12px; color: #64748b; }
        main { padding: 24px; }

        /* Mobile toggle */
        .mobile-toggle { display: none; background: none; border: none; font-size: 20px; cursor: pointer; color: #1e293b; }
        .sidebar-overlay { display: none; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.active { display: block; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 800; }
            .main-content { margin-left: 0; }
            .mobile-toggle { display: block; }
        }

        /* Utility classes for content */
        .grid { display: grid; gap: 16px; }
        .grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-cols-4 { grid-template-columns: repeat(4, 1fr); }
        @media (max-width: 768px) { .grid-cols-2, .grid-cols-4 { grid-template-columns: 1fr; } }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 20px; }
        .stat-card { border-left: 4px solid; }
        .stat-card .label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card .value { font-size: 24px; font-weight: 700; margin-top: 4px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-red { background: #fce4ec; color: #c62828; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-indigo { background: #e0e7ff; color: #3730a3; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 10px 16px; font-size: 11px; color: #64748b; text-transform: uppercase; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        td { padding: 12px 16px; font-size: 13px; color: #334155; border-bottom: 1px solid #f1f5f9; }
        .btn { display: inline-block; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.15s; }
        .btn-primary { background: #6366f1; color: #fff; }
        .btn-primary:hover { background: #4f46e5; }
        .btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
        .btn-outline:hover { background: #f9fafb; }
        .progress-bar { width: 100%; background: #e5e7eb; border-radius: 8px; height: 12px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 8px; transition: width 0.5s; }
        .demo-action-blocked {
            cursor: not-allowed; opacity: 0.7;
        }
        .toast-demo {
            display: none; position: fixed; bottom: 24px; right: 24px; z-index: 2000;
            background: #1e293b; color: #fff; padding: 12px 20px; border-radius: 8px;
            font-size: 13px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease;
        }
        .toast-demo.show { display: flex; align-items: center; gap: 8px; }
        @keyframes slideIn { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body>
    <!-- Demo Banner -->
    <div class="demo-banner">
        <span class="demo-badge">DEMO</span>
        <span>Estás viendo una demostración del sistema — Los datos mostrados son ficticios</span>
        <a href="{{ route('demo.logout') }}">Salir del Demo</a>
    </div>

    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <h2><i class="fas fa-store" style="color: #6366f1;"></i> Big Studio</h2>
            <p>Integraciones Shopify</p>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section">Menú Principal</div>
            <a href="{{ route('demo.dashboard') }}" class="sidebar-link {{ request()->routeIs('demo.dashboard') ? 'active' : '' }}">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="{{ route('demo.planes') }}" class="sidebar-link {{ request()->routeIs('demo.planes') ? 'active' : '' }}">
                <i class="fas fa-tags"></i> Planes
            </a>
            <a href="{{ route('demo.chats') }}" class="sidebar-link {{ request()->routeIs('demo.chats') ? 'active' : '' }}">
                <i class="fas fa-comments"></i> Chats
            </a>
            <a href="{{ route('demo.estados-solicitud') }}" class="sidebar-link {{ request()->routeIs('demo.estados-solicitud') ? 'active' : '' }}">
                <i class="fas fa-tasks"></i> Estados de Solicitud
            </a>
            <a href="{{ route('demo.planes-activos') }}" class="sidebar-link {{ request()->routeIs('demo.planes-activos') ? 'active' : '' }}">
                <i class="fas fa-check-circle"></i> Planes Activos
            </a>
            <a href="{{ route('demo.inventario') }}" class="sidebar-link {{ request()->routeIs('demo.inventario') ? 'active' : '' }}">
                <i class="fas fa-boxes-stacked"></i> Inventario
            </a>
            <a href="{{ route('demo.facturas') }}" class="sidebar-link {{ request()->routeIs('demo.facturas') ? 'active' : '' }}">
                <i class="fas fa-file-invoice"></i> Facturas
            </a>
            <a href="{{ route('demo.facturas-servicio') }}" class="sidebar-link {{ request()->routeIs('demo.facturas-servicio') ? 'active' : '' }}">
                <i class="fas fa-file-invoice-dollar"></i> Facturas Servicio
            </a>
            <a href="{{ route('demo.cobros-pendientes') }}" class="sidebar-link {{ request()->routeIs('demo.cobros-pendientes') ? 'active' : '' }}">
                <i class="fas fa-clock"></i> Cobros Pendientes
            </a>
            <a href="{{ route('demo.documentos-emitidos') }}" class="sidebar-link {{ request()->routeIs('demo.documentos-emitidos') ? 'active' : '' }}">
                <i class="fas fa-file-alt"></i> Documentos Emitidos
            </a>
        </nav>
        <div class="sidebar-user">
            <div class="sidebar-user-name">Tienda Demo SpA</div>
            <div class="sidebar-user-email">demo@tiendademo.cl</div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <h2>@yield('page-title', 'Dashboard')</h2>
            <div class="date">{{ now()->format('d/m/Y') }}</div>
        </div>
        <main>
            @yield('content')
        </main>
    </div>

    <!-- Toast for blocked actions -->
    <div class="toast-demo" id="demoToast">
        <i class="fas fa-info-circle"></i>
        <span>Esta función no está disponible en el modo demo</span>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }
        function showDemoToast() {
            const toast = document.getElementById('demoToast');
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
        // Block all form submissions and action buttons in demo
        document.addEventListener('click', function(e) {
            const target = e.target.closest('.demo-action-blocked, [type="submit"]');
            if (target && !target.closest('.demo-login-form')) {
                e.preventDefault();
                showDemoToast();
            }
        });
    </script>
</body>
</html>
