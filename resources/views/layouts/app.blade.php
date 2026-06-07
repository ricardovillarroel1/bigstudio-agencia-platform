<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-meta-pixel />

    <style>
        body { background: #f1f5f9; margin: 0; }
        .sidebar {
            width: 260px;
            /* IMPORTANTE: height (no min-height) para que overflow-y: auto realmente
               dispare el scroll cuando el contenido excede el viewport.
               Con min-height + position: fixed, la caja crecia con el contenido
               y la parte de abajo quedaba fuera de pantalla sin scroll. */
            height: 100vh;
            background: linear-gradient(180deg, #0f0f0f 0%, #1a1a2e 100%);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 40;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .sidebar-logo {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255,200,0,0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .sidebar-logo img { height: 38px; width: auto; border-radius: 6px; }
        .sidebar-logo span {
            color: #fff;
            font-family: 'Mostin', system-ui, sans-serif;
            font-weight: 900;
            font-size: 1.15rem;
            letter-spacing: 0.01em;
        }
        .sidebar-section {
            padding: 0.75rem 0;
        }
        .sidebar-section-title {
            padding: 0.5rem 1.5rem;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #FFC800;
            margin-bottom: 0.25rem;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 1.5rem;
            color: #94a3b8;
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }
        .sidebar-link:hover {
            color: #fff;
            background: rgba(255,200,0,0.08);
            border-left-color: rgba(255,200,0,0.3);
        }
        .sidebar-link.active {
            color: #FF8100;
            background: linear-gradient(90deg, rgba(255,129,0,0.15) 0%, rgba(255,129,0,0.03) 100%);
            border-left-color: #FF8100;
            font-weight: 700;
        }
        .sidebar-link i { width: 20px; text-align: center; font-size: 0.9rem; }
        .sidebar-badge {
            background: #ef4444;
            color: #fff;
            border-radius: 9999px;
            padding: 0.1rem 0.45rem;
            font-size: 0.65rem;
            font-weight: 700;
            margin-left: auto;
            min-width: 18px;
            text-align: center;
            box-shadow: 0 2px 6px rgba(239,68,68,0.4);
            animation: pulse 2s infinite;
        }
        .sidebar-divider {
            height: 1px;
            background: rgba(255,255,255,0.06);
            margin: 0.25rem 1.25rem;
        }
        .sidebar-user {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.06);
            margin-top: auto;
        }
        .sidebar-user-name { color: #e2e8f0; font-weight: 600; font-size: 0.85rem; }
        .sidebar-user-email { color: #64748b; font-size: 0.7rem; }
        .sidebar-user-actions { display: flex; gap: 0.75rem; margin-top: 0.5rem; }
        .sidebar-user-actions a {
            color: #94a3b8;
            font-size: 0.75rem;
            text-decoration: none;
            transition: color 0.2s;
        }
        .sidebar-user-actions a:hover { color: #FFC800; }
        
        /* Accordion Sidebar
           NOTA: el scroll se maneja UNICAMENTE en .sidebar (parent).
           No poner overflow-y aca tambien o el usuario no puede expandir
           multiples accordions a la vez. */
        .sidebar-nav {
            flex: 1 0 auto;
            overflow: visible;
        }
        /* Estilizar el scrollbar del .sidebar (donde realmente vive el scroll) */
        .sidebar { scrollbar-width: thin; scrollbar-color: rgba(255,200,0,0.3) transparent; }
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-track { background: transparent; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,200,0,0.3); border-radius: 4px; }
        .sidebar::-webkit-scrollbar-thumb:hover { background: rgba(255,200,0,0.5); }
        .sidebar-accordion { border-bottom: 1px solid rgba(255,255,255,0.06); }
        .sidebar-accordion-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.7rem 1.5rem;
            cursor: pointer;
            user-select: none;
            transition: background 0.2s;
        }
        .sidebar-accordion-header:hover { background: rgba(255,200,0,0.05); }
        .sidebar-accordion-header .section-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #FFC800;
        }
        .sidebar-accordion-header .chevron {
            color: #FFC800;
            font-size: 0.6rem;
            transition: transform 0.3s ease;
        }
        .sidebar-accordion.open .chevron { transform: rotate(180deg); }
        .sidebar-accordion-body {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease;
        }
        .sidebar-accordion.open .sidebar-accordion-body {
            /* Generoso para que ningun accordion quede cortado aunque tenga muchos items */
            max-height: 1500px;
        }
        .sidebar-accordion-body .sidebar-link {
            padding-left: 1.75rem;
        }
        /* Subgrupos dentro de un accordion (Operacion / Catalogo / Cobros / etc.) */
        .sidebar-subsection {
            padding: 0.65rem 1.75rem 0.25rem;
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: #64748b;
            margin-top: 0.25rem;
            border-top: 1px solid rgba(255,255,255,0.04);
        }
        .sidebar-subsection:first-child {
            border-top: none;
            margin-top: 0;
            padding-top: 0.5rem;
        }
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
        }
        .top-bar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 1px 0 rgba(17,24,39,0.04);
        }
        /* Banda BigStudio: 3px gradiente naranja arriba del topbar */
        .top-bar::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);
        }
        .top-bar h2 {
            font-family: 'Mostin', system-ui, sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            letter-spacing: 0.005em;
        }
        .top-bar h2 .bs-accent { color: #FF8100; }

        /* ===== Topbar BigStudio (right side) ===== */
        .bs-topbar-left { flex: 1; display: flex; align-items: center; }
        .bs-topbar-right { display: flex; align-items: center; gap: 12px; position: relative; }
        .bs-plan-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 12px; border-radius: 999px;
            background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%);
            border: 1px solid #FFD89C;
            color: #8A4400; font-size: 0.75rem; font-weight: 700;
            white-space: nowrap;
            transition: all 0.15s;
        }
        .bs-plan-badge:hover {
            box-shadow: 0 2px 8px rgba(255, 129, 0, 0.2);
            transform: translateY(-1px);
        }
        .bs-plan-badge i { color: #FF8100; font-size: 0.7rem; }
        .bs-topbar-date { font-size: 0.8rem; color: #64748b; font-weight: 500; white-space: nowrap; }
        .bs-topbar-bell {
            position: relative;
            width: 38px; height: 38px;
            border-radius: 10px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: #64748b; transition: all 0.15s;
        }
        .bs-topbar-bell:hover {
            background: #FFF7EC; color: #FF8100;
        }
        .bs-bell-badge {
            position: absolute; top: 4px; right: 4px;
            min-width: 16px; height: 16px;
            background: #FF8100; color: white;
            border-radius: 999px; font-size: 0.6rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            padding: 0 4px; border: 2px solid #fff;
        }
        .bs-topbar-avatar {
            width: 38px; height: 38px;
            border-radius: 10px; cursor: pointer;
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #FFC800 0%, #FF8100 100%);
            color: white;
            font-family: 'Mostin', system-ui, sans-serif;
            font-weight: 900; font-size: 1rem;
            transition: all 0.15s;
            box-shadow: 0 2px 6px rgba(255, 129, 0, 0.25);
        }
        .bs-topbar-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .bs-topbar-avatar:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 129, 0, 0.4);
        }

        /* Dropdowns del topbar */
        .bs-topbar-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            min-width: 280px;
            background: white;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(17,24,39,0.12);
            overflow: hidden;
            z-index: 200;
            animation: bsDropIn 0.15s ease-out;
        }
        @keyframes bsDropIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .bs-dropdown-header {
            padding: 12px 16px;
            font-size: 0.7rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.06em;
            color: #6B7280; border-bottom: 1px solid #F3F4F6;
            display: flex; align-items: center; justify-content: space-between;
        }
        .bs-dropdown-count {
            background: linear-gradient(135deg,#FF9C00,#FF8100); color:#fff;
            font-size:0.65rem; font-weight:800; min-width:18px; height:18px;
            border-radius:9px; display:inline-flex; align-items:center;
            justify-content:center; padding:0 5px; margin-left:8px;
        }
        .bs-dropdown-user-header {
            padding: 16px;
            display: flex; align-items: center; gap: 12px;
            background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%);
            border-bottom: 1px solid #FFD89C;
        }
        .bs-dropdown-avatar {
            width: 44px; height: 44px; border-radius: 10px;
            background: linear-gradient(135deg, #FFC800 0%, #FF8100 100%);
            color: white;
            font-family: 'Mostin', system-ui, sans-serif;
            font-weight: 900; font-size: 1.1rem;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 6px rgba(255, 129, 0, 0.25);
            flex-shrink: 0;
        }
        .bs-dropdown-user-name {
            margin: 0; font-weight: 700; color: #111827; font-size: 0.9rem;
            font-family: 'Mostin', system-ui, sans-serif;
        }
        .bs-dropdown-user-email {
            margin: 2px 0 0 0; font-size: 0.75rem; color: #6B7280;
        }
        .bs-dropdown-item, .bs-dropdown-item-form button {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 16px;
            color: #374151;
            text-decoration: none;
            font-size: 0.85rem; font-weight: 500;
            transition: background 0.15s;
            border: none; background: white; cursor: pointer;
            width: 100%; text-align: left;
        }
        .bs-dropdown-item:hover, .bs-dropdown-item-form button:hover {
            background: #FFF7EC; color: #FF8100;
        }
        .bs-dropdown-item-form { margin: 0; }
        .bs-dropdown-icon {
            width: 32px; height: 32px; border-radius: 8px;
            background: #FFF7EC; color: #FF8100;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .bs-dropdown-title { margin: 0; font-weight: 700; color: #111827; font-size: 0.85rem; }
        .bs-dropdown-sub { margin: 2px 0 0 0; font-size: 0.7rem; color: #6B7280; }
        .bs-dropdown-empty {
            padding: 24px 16px; text-align: center;
            color: #9CA3AF; font-size: 0.85rem;
        }
        .bs-notif-x { flex-shrink:0; align-self:center; width:22px; height:22px; border:none; background:transparent; color:#C7CDD6; font-size:1.1rem; line-height:1; border-radius:6px; cursor:pointer; transition:all .15s; }
        .bs-notif-x:hover { background:#FEE2E2; color:#DC2626; }
        .bs-dropdown-empty i { font-size: 1.5rem; color: #10B981; display: block; margin-bottom: 6px; }

        @media (max-width: 768px) {
            .bs-plan-badge span { display: none; }
            .bs-topbar-date { display: none; }
        }
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            color: #1e293b;
            font-size: 1.5rem;
            cursor: pointer;
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            
        /* Accordion Sidebar */
                .main-content { margin-left: 0; }
            .mobile-toggle { display: block; }
            .sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 35;
            }
            .sidebar-overlay.active { display: block; }
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.05); }
        }
    </style>
</head>

<body class="font-sans antialiased">
    @auth
    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <a href="{{ Auth::user()->hasRole('admin') ? route('dashboard') : route('cliente.dashboard') }}">
                <img src="{{ asset('images/logo.jpeg') }}" alt="Logo">
            </a>
            <span>Big Studio</span>
        </div>

        @role('admin')
        @php
            // Modulo abierto decidido por el SERVIDOR (evita parpadeo del acordeon)
            $bsAcc = 'integraciones';
            if (request()->routeIs('agencia.*')) { $bsAcc = 'agencia'; }
            elseif (request()->routeIs('finanzas.*')) { $bsAcc = 'finanzas'; }
            elseif (request()->routeIs('config.*')) { $bsAcc = 'config'; }
        @endphp
        <div class="sidebar-nav">
        <!-- MÓDULO: INTEGRACIONES BIG STUDIO -->
        <div class="sidebar-accordion {{ $bsAcc === 'integraciones' ? 'open' : '' }}" data-accordion="integraciones">
            <div class="sidebar-accordion-header" onclick="toggleAccordion(this)">
                <span class="section-label"><i class="fas fa-plug" style="margin-right:6px;font-size:0.7rem;"></i> Integraciones Big Studio</span>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-accordion-body">

                {{-- Grupo: Operacion --}}
                <div class="sidebar-subsection">Operaci&oacute;n</div>
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i> Panel Principal
                </a>
                <a href="{{ route('integracion.index') }}" class="sidebar-link {{ request()->routeIs('integracion.index') || request()->routeIs('integracion.show') ? 'active' : '' }}">
                    <i class="fas fa-plug"></i> Integraci&oacute;n
                </a>
                <a href="{{ route('integracion.inventario') }}" class="sidebar-link {{ request()->routeIs('integracion.inventario*') ? 'active' : '' }}">
                    <i class="fas fa-boxes-stacked"></i> Inventario
                </a>
                <a href="{{ route('admin.solicitudes.pendientes-conexion') }}" class="sidebar-link {{ request()->routeIs('admin.solicitudes.*') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-list"></i> Solicitudes
                </a>
                <a href="{{ route('admin.chats') }}" class="sidebar-link {{ request()->routeIs('admin.chats') ? 'active' : '' }}">
                    <i class="fas fa-comments"></i> Chats
                    <span id="unreadBadge" class="sidebar-badge" style="display:none;"></span>
                </a>
                <a href="{{ route('admin.trazabilidad-sku') }}" class="sidebar-link {{ request()->routeIs('admin.trazabilidad-sku') ? 'active' : '' }}">
                    <i class="fas fa-route"></i> Trazabilidad SKU
                </a>

                {{-- Grupo: Catalogo --}}
                <div class="sidebar-subsection">Cat&aacute;logo</div>
                <a href="{{ route('planes.index') }}" class="sidebar-link {{ request()->routeIs('planes.*') ? 'active' : '' }}">
                    <i class="fas fa-layer-group"></i> Planes
                </a>
                <a href="{{ route('admin.suscripciones') }}" class="sidebar-link {{ request()->routeIs('admin.suscripciones') ? 'active' : '' }}">
                    <i class="fas fa-id-card"></i> Suscripciones
                </a>

                {{-- Grupo: Cobros --}}
                <div class="sidebar-subsection">Cobros</div>
                <a href="{{ route('admin.billing.index') }}" class="sidebar-link {{ request()->routeIs('admin.billing.*') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice-dollar"></i> Facturaci&oacute;n
                </a>
                <a href="{{ route('admin.pagos-recibidos.index') }}" class="sidebar-link {{ request()->routeIs('admin.pagos-recibidos.*') || request()->routeIs('admin.transferencias.*') ? 'active' : '' }}">
                    <i class="fas fa-coins"></i> Pagos Recibidos
                </a>
                <a href="{{ route('admin.cobros-asignados.index') }}" class="sidebar-link {{ request()->routeIs('admin.cobros-asignados.*') ? 'active' : '' }}">
                    <i class="fas fa-hand-holding-usd"></i> Cobros Asignados
                </a>

                {{-- Grupo: Documentos --}}
                <div class="sidebar-subsection">Documentos</div>
                <a href="{{ route('boletas.index') }}" class="sidebar-link {{ request()->routeIs('boletas.*') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice"></i> Documentos Emitidos
                </a>
                <a href="{{ route('admin.pedidos-sin-boleta.index') }}" class="sidebar-link {{ request()->routeIs('admin.pedidos-sin-boleta.*') ? 'active' : '' }}">
                    <i class="fas fa-receipt"></i> Pedidos sin Boleta
                </a>

                {{-- Grupo: Sistema --}}
                <div class="sidebar-subsection">Sistema</div>
                <a href="{{ route('usuarios.index') }}" class="sidebar-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i> Usuarios
                </a>
                <a href="{{ route('admin.settings') }}" class="sidebar-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                    <i class="fas fa-cog"></i> Configuraci&oacute;n
                </a>
                <a href="{{ route('integracion.correos') }}" class="sidebar-link {{ request()->routeIs('integracion.correos*') ? 'active' : '' }}">
                    <i class="fas fa-envelope"></i> Correos Integraciones
                </a>
            </div>
        </div>
        <!-- MÓDULO: SERVICIOS DE AGENCIA -->
        <div class="sidebar-accordion {{ $bsAcc === 'agencia' ? 'open' : '' }}" data-accordion="agencia">
            <div class="sidebar-accordion-header" onclick="toggleAccordion(this)">
                <span class="section-label"><i class="fas fa-briefcase" style="margin-right:6px;font-size:0.7rem;"></i> Servicios de Agencia</span>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-accordion-body">            <a href="{{ route('agencia.dashboard') }}" class="sidebar-link {{ request()->routeIs('agencia.dashboard') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i> Dashboard Agencia
            </a>
            <a href="{{ route('agencia.clientes') }}" class="sidebar-link {{ request()->routeIs('agencia.clientes*') ? 'active' : '' }}">
                <i class="fas fa-user-tie"></i> Clientes
            </a>
            <a href="{{ route('agencia.servicios') }}" class="sidebar-link {{ request()->routeIs('agencia.servicios*') ? 'active' : '' }}">
                <i class="fas fa-concierge-bell"></i> Servicios
            </a>
            <a href="{{ route('agencia.suscripciones') }}" class="sidebar-link {{ request()->routeIs('agencia.suscripciones*') ? 'active' : '' }}">
                <i class="fas fa-sync-alt"></i> Suscripciones
            </a>
            <a href="{{ route('agencia.cobros') }}" class="sidebar-link {{ request()->routeIs('agencia.cobros*') ? 'active' : '' }}">
                <i class="fas fa-hand-holding-usd"></i> Cobros
            </a>
            <a href="{{ route("agencia.cotizaciones") }}" class="sidebar-link {{ request()->routeIs("agencia.cotizaciones*") ? "active" : "" }}"><i class="fas fa-file-invoice"></i> Cotizaciones</a>
            <a href="{{ route('agencia.correos') }}" class="sidebar-link {{ request()->routeIs('agencia.correos*') ? 'active' : '' }}">
                <i class="fas fa-envelope"></i> Correos
            </a>
            <a href="{{ route('agencia.reportes.meta-demo') }}" class="sidebar-link {{ request()->routeIs('agencia.reportes.meta-demo') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i> Reportes Ads <span style="background:#FF8100;color:#fff;font-size:0.55rem;padding:0.05rem 0.35rem;border-radius:9999px;margin-left:4px;">DEMO</span>
            </a>
            <a href="{{ route('agencia.reportes.conexion') }}" class="sidebar-link {{ request()->routeIs('agencia.reportes.conexion') ? 'active' : '' }}">
                <i class="fas fa-plug"></i> Conectar Meta Ads
            </a>
            </div>
        </div>
        <!-- MÓDULO: FINANZAS -->
        <div class="sidebar-accordion {{ $bsAcc === 'finanzas' ? 'open' : '' }}" data-accordion="finanzas">
            <div class="sidebar-accordion-header" onclick="toggleAccordion(this)">
                <span class="section-label"><i class="fas fa-coins" style="margin-right:6px;font-size:0.7rem;"></i> Finanzas</span>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-accordion-body">            <a href="{{ route('finanzas.dashboard') }}" class="sidebar-link {{ request()->routeIs('finanzas.dashboard') ? 'active' : '' }}">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <a href="{{ route('finanzas.ingresos') }}" class="sidebar-link {{ request()->routeIs('finanzas.ingresos') ? 'active' : '' }}">
                <i class="fas fa-arrow-circle-down"></i> Ingresos
            </a>
            <a href="{{ route('finanzas.egresos') }}" class="sidebar-link {{ request()->routeIs('finanzas.egresos*') ? 'active' : '' }}">
                <i class="fas fa-arrow-circle-up"></i> Egresos
            </a>
            <a href="{{ route('finanzas.iva') }}" class="sidebar-link {{ request()->routeIs('finanzas.iva') ? 'active' : '' }}">
                <i class="fas fa-percentage"></i> IVA Mensual
            </a>
            <a href="{{ route('finanzas.banco') }}" class="sidebar-link {{ request()->routeIs('finanzas.banco*') ? 'active' : '' }}">
                <i class="fas fa-university"></i> Banco
            </a>
            <a href="{{ route('finanzas.cuentas-cobrar') }}" class="sidebar-link {{ request()->routeIs('finanzas.cuentas-cobrar') ? 'active' : '' }}">
                <i class="fas fa-hand-holding-usd"></i> Cuentas por Cobrar
            </a>
            <a href="{{ route('finanzas.cuentas-pagar') }}" class="sidebar-link {{ request()->routeIs('finanzas.cuentas-pagar') ? 'active' : '' }}">
                <i class="fas fa-credit-card"></i> Cuentas por Pagar
            </a>
            <a href="{{ route('finanzas.reportes') }}" class="sidebar-link {{ request()->routeIs('finanzas.reportes*') ? 'active' : '' }}">
                <i class="fas fa-file-alt"></i> Reportes
            </a>
            <a href="{{ route('finanzas.presupuesto') }}" class="sidebar-link {{ request()->routeIs('finanzas.presupuesto') ? 'active' : '' }}">
                <i class="fas fa-calculator"></i> Presupuesto
            </a>
            </div>
        </div>
        <!-- MÓDULO: CONFIGURACIÓN -->
        <div class="sidebar-accordion {{ $bsAcc === 'config' ? 'open' : '' }}" data-accordion="config">
            <div class="sidebar-accordion-header" onclick="toggleAccordion(this)">
                <span class="section-label"><i class="fas fa-cog" style="margin-right:6px;font-size:0.7rem;"></i> Configuración</span>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-accordion-body">            <a href="{{ route('config.colaboradores') }}" class="sidebar-link {{ request()->routeIs('config.colaboradores*') ? 'active' : '' }}">
                <i class="fas fa-user-shield"></i> Colaboradores
            </a>
            </div>
        </div>
        </div><!-- end sidebar-nav -->
        @endrole

        @role('colaborador')
        <!-- SIDEBAR DINÁMICO PARA COLABORADORES -->
        @php $userPerms = auth()->user()->getAllPermissions()->pluck('name')->toArray(); @endphp

        <div class="sidebar-nav">
        @if(count(array_filter($userPerms, fn($p) => str_starts_with($p, 'integraciones.'))) > 0)
        <div class="sidebar-accordion open" data-accordion="collab-integ">
            <div class="sidebar-accordion-header" onclick="toggleAccordion(this)">
                <span class="section-label"><i class="fas fa-plug" style="margin-right:6px;font-size:0.7rem;"></i> Integraciones</span>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-accordion-body">
            @if(in_array('integraciones.dashboard', $userPerms))<a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="fas fa-tachometer-alt"></i> Panel Principal</a>@endif
            @if(in_array('integraciones.boletas', $userPerms))<a href="{{ route('boletas.index') }}" class="sidebar-link {{ request()->routeIs('boletas.*') ? 'active' : '' }}"><i class="fas fa-file-invoice"></i> Documentos Emitidos</a>@endif
            @if(in_array('integraciones.facturas', $userPerms))<a href="{{ route('admin.billing.index') }}" class="sidebar-link {{ request()->routeIs('admin.billing.*') ? 'active' : '' }}"><i class="fas fa-file-invoice-dollar"></i> Facturación</a>@endif
            @if(in_array('integraciones.clientes', $userPerms))<a href="{{ route('usuarios.index') }}" class="sidebar-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}"><i class="fas fa-users"></i> Usuarios</a>@endif
            @if(in_array('integraciones.suscripciones', $userPerms))<a href="{{ route('admin.suscripciones') }}" class="sidebar-link {{ request()->routeIs('admin.suscripciones') ? 'active' : '' }}"><i class="fas fa-id-card"></i> Suscripciones</a>@endif
            @if(in_array('integraciones.chats', $userPerms))<a href="{{ route('admin.chats') }}" class="sidebar-link {{ request()->routeIs('admin.chats') ? 'active' : '' }}"><i class="fas fa-comments"></i> Chats</a>@endif
            @if(in_array('integraciones.configuracion', $userPerms))<a href="{{ route('admin.settings') }}" class="sidebar-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}"><i class="fas fa-cog"></i> Configuración</a>@endif
        </div>
        @endif

        @if(count(array_filter($userPerms, fn($p) => str_starts_with($p, 'agencia.'))) > 0)
        <div class="sidebar-accordion" data-accordion="collab-agencia">
            <div class="sidebar-accordion-header" onclick="toggleAccordion(this)">
                <span class="section-label"><i class="fas fa-briefcase" style="margin-right:6px;font-size:0.7rem;"></i> Agencia</span>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-accordion-body">
            @if(in_array('agencia.dashboard', $userPerms))<a href="{{ route('agencia.dashboard') }}" class="sidebar-link {{ request()->routeIs('agencia.dashboard') ? 'active' : '' }}"><i class="fas fa-chart-line"></i> Dashboard</a>@endif
            @if(in_array('agencia.clientes', $userPerms))<a href="{{ route('agencia.clientes') }}" class="sidebar-link {{ request()->routeIs('agencia.clientes*') ? 'active' : '' }}"><i class="fas fa-user-tie"></i> Clientes</a>@endif
            @if(in_array('agencia.servicios', $userPerms))<a href="{{ route('agencia.servicios') }}" class="sidebar-link {{ request()->routeIs('agencia.servicios*') ? 'active' : '' }}"><i class="fas fa-concierge-bell"></i> Servicios</a>@endif
            @if(in_array('agencia.cobros', $userPerms))<a href="{{ route('agencia.cobros') }}" class="sidebar-link {{ request()->routeIs('agencia.cobros*') ? 'active' : '' }}"><i class="fas fa-hand-holding-usd"></i> Cobros</a>@endif
            @if(in_array('agencia.cotizaciones', $userPerms))<a href="{{ route('agencia.cotizaciones') }}" class="sidebar-link {{ request()->routeIs('agencia.cotizaciones*') ? 'active' : '' }}"><i class="fas fa-file-invoice"></i> Cotizaciones</a>@endif
        </div>
        </div>
        @endif
        @if(count(array_filter($userPerms, fn($p) => str_starts_with($p, 'finanzas.'))) > 0)
        </div>
        </div>
        <div class="sidebar-accordion" data-accordion="collab-finanzas">
            <div class="sidebar-accordion-header" onclick="toggleAccordion(this)">
                <span class="section-label"><i class="fas fa-coins" style="margin-right:6px;font-size:0.7rem;"></i> Finanzas</span>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-accordion-body">
            @if(in_array('finanzas.dashboard', $userPerms))<a href="{{ route('finanzas.dashboard') }}" class="sidebar-link {{ request()->routeIs('finanzas.dashboard') ? 'active' : '' }}"><i class="fas fa-chart-pie"></i> Dashboard</a>@endif
            @if(in_array('finanzas.ingresos', $userPerms))<a href="{{ route('finanzas.ingresos') }}" class="sidebar-link {{ request()->routeIs('finanzas.ingresos') ? 'active' : '' }}"><i class="fas fa-arrow-circle-down"></i> Ingresos</a>@endif
            @if(in_array('finanzas.egresos', $userPerms))<a href="{{ route('finanzas.egresos') }}" class="sidebar-link {{ request()->routeIs('finanzas.egresos*') ? 'active' : '' }}"><i class="fas fa-arrow-circle-up"></i> Egresos</a>@endif
            @if(in_array('finanzas.iva', $userPerms))<a href="{{ route('finanzas.iva') }}" class="sidebar-link {{ request()->routeIs('finanzas.iva') ? 'active' : '' }}"><i class="fas fa-percentage"></i> IVA Mensual</a>@endif
            @if(in_array('finanzas.banco', $userPerms))<a href="{{ route('finanzas.banco') }}" class="sidebar-link {{ request()->routeIs('finanzas.banco*') ? 'active' : '' }}"><i class="fas fa-university"></i> Banco</a>@endif
            @if(in_array('finanzas.cuentas-cobrar', $userPerms))<a href="{{ route('finanzas.cuentas-cobrar') }}" class="sidebar-link {{ request()->routeIs('finanzas.cuentas-cobrar') ? 'active' : '' }}"><i class="fas fa-hand-holding-usd"></i> Cuentas por Cobrar</a>@endif
            @if(in_array('finanzas.cuentas-pagar', $userPerms))<a href="{{ route('finanzas.cuentas-pagar') }}" class="sidebar-link {{ request()->routeIs('finanzas.cuentas-pagar') ? 'active' : '' }}"><i class="fas fa-credit-card"></i> Cuentas por Pagar</a>@endif
            @if(in_array('finanzas.reportes', $userPerms))<a href="{{ route('finanzas.reportes') }}" class="sidebar-link {{ request()->routeIs('finanzas.reportes*') ? 'active' : '' }}"><i class="fas fa-file-alt"></i> Reportes</a>@endif
            @if(in_array('finanzas.presupuesto', $userPerms))<a href="{{ route('finanzas.presupuesto') }}" class="sidebar-link {{ request()->routeIs('finanzas.presupuesto') ? 'active' : '' }}"><i class="fas fa-calculator"></i> Presupuesto</a>@endif
            </div>
        </div>
        @endif
        </div><!-- end sidebar-nav -->
        @endrole

        @role('cliente')
        <div class="sidebar-section">

            {{-- Mi cuenta --}}
            <div class="sidebar-subsection" style="padding-left: 1.5rem;">Mi cuenta</div>
            <a href="{{ route('cliente.dashboard') }}" class="sidebar-link {{ request()->routeIs('cliente.dashboard') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i> Panel
            </a>
            <a href="{{ route('cliente.planes') }}" class="sidebar-link {{ request()->routeIs('cliente.planes') ? 'active' : '' }}">
                <i class="fas fa-layer-group"></i> Planes disponibles
            </a>
            <a href="{{ route('cliente.planes-activos') }}" class="sidebar-link {{ request()->routeIs('cliente.planes-activos') ? 'active' : '' }}">
                <i class="fas fa-check-circle"></i> Mi plan activo
            </a>

            {{-- Operacion --}}
            <div class="sidebar-subsection" style="padding-left: 1.5rem;">Operaci&oacute;n</div>
            <a href="{{ route('cliente.chats') }}" class="sidebar-link {{ request()->routeIs('cliente.chats') ? 'active' : '' }}">
                <i class="fas fa-comments"></i> Chats
                <span id="clienteUnreadBadge" class="sidebar-badge" style="display:none;"></span>
            </a>
            <a href="{{ route('cliente.estados-solicitud') }}" class="sidebar-link {{ request()->routeIs('cliente.estados-solicitud') ? 'active' : '' }}">
                <i class="fas fa-tasks"></i> Estados de solicitud
            </a>
            <a href="{{ route('cliente.inventario') }}" class="sidebar-link {{ request()->routeIs('cliente.inventario*') ? 'active' : '' }}">
                <i class="fas fa-boxes-stacked"></i> Inventario
            </a>
            <a href="{{ route('cliente.trazabilidad-sku') }}" class="sidebar-link {{ request()->routeIs('cliente.trazabilidad-sku') ? 'active' : '' }}">
                <i class="fas fa-route"></i> Trazabilidad SKU
            </a>

            {{-- DTEs que el cliente emite a SUS clientes finales --}}
            <div class="sidebar-subsection" style="padding-left: 1.5rem;">DTEs que emites</div>
            <a href="{{ route('cliente.documentos-emitidos') }}" class="sidebar-link {{ request()->routeIs('cliente.documentos-emitidos') ? 'active' : '' }}">
                <i class="fas fa-file-alt"></i> Boletas y facturas a clientes
            </a>

            {{-- Tu plan Big Studio (lo que BigStudio le cobra al cliente) --}}
            <div class="sidebar-subsection" style="padding-left: 1.5rem;">Tu plan Big Studio</div>
            <a href="{{ route('cliente.facturas-servicio') }}" class="sidebar-link {{ request()->routeIs('cliente.facturas-servicio') || request()->routeIs('cliente.facturas') ? 'active' : '' }}">
                <i class="fas fa-file-invoice-dollar"></i> Facturas de servicio
            </a>
            <a href="{{ route('cliente.cobros-pendientes') }}" class="sidebar-link {{ request()->routeIs('cliente.cobros-pendientes') ? 'active' : '' }}">
                <i class="fas fa-clock"></i> Cobros pendientes
            </a>
        </div>
        @endrole

        <!-- User info at bottom -->
        <div class="sidebar-user">
            <div class="sidebar-user-name">{{ Auth::user()->name }}</div>
            <div class="sidebar-user-email">{{ Auth::user()->email }}</div>
            <div class="sidebar-user-actions">
                <a href="{{ route('profile.edit') }}"><i class="fas fa-user-edit"></i> Perfil</a>
                <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                    @csrf
                    <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar BigStudio -->
        @php
            // Datos contextuales para el topbar (solo se ejecutan una vez por pagina)
            $bsUser = auth()->user();
            $bsInitial = strtoupper(substr($bsUser->name ?? '?', 0, 1));
            $bsIsCliente = $bsUser->hasRole('cliente');
            $bsIsAdmin = $bsUser->hasRole('admin');
            $bsPlanBadge = null;
            $bsFactPendientes = 0;
            $bsNotifs = [];
            try {
                if ($bsIsCliente) {
                    $bsSusc = \App\Models\Suscripcion::where('user_id', $bsUser->id)
                        ->where('estado', 'activa')->with('plan')->first();
                    if ($bsSusc && $bsSusc->plan) { $bsPlanBadge = $bsSusc->plan->nombre ?? null; }

                    $bsFactPendientes = \App\Models\FacturaServicio::where('user_id', $bsUser->id)
                        ->where('estado', 'pendiente')->where('monto', '>', 0)->count();
                    if ($bsFactPendientes > 0) {
                        $bsNotifs[] = ['icon' => 'fa-file-invoice-dollar', 'bg' => '#FEF3C7', 'color' => '#92400E',
                            'title' => $bsFactPendientes . ' factura' . ($bsFactPendientes === 1 ? '' : 's') . ' pendiente' . ($bsFactPendientes === 1 ? '' : 's'),
                            'sub' => 'Click para revisar y pagar', 'url' => route('cliente.facturas-servicio')];
                    }
                    if ($bsSusc && $bsSusc->proximo_pago) {
                        $bsDias = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($bsSusc->proximo_pago)->startOfDay(), false);
                        if ($bsDias >= 0 && $bsDias <= 7) {
                            $bsNotifs[] = ['icon' => 'fa-clock', 'bg' => '#FFEDD0', 'color' => '#C2410C',
                                'title' => $bsDias === 0 ? 'Tu plan se renueva hoy' : 'Tu plan se renueva en ' . $bsDias . ' dia' . ($bsDias === 1 ? '' : 's'),
                                'sub' => $bsSusc->plan->nombre ?? 'Suscripcion activa',
                                'url' => \Illuminate\Support\Facades\Route::has('cliente.suscripcion') ? route('cliente.suscripcion') : route('cliente.facturas-servicio')];
                        }
                    }
                    if (\Illuminate\Support\Facades\Schema::hasTable('chats')) {
                        $bsChatCli = \App\Models\Chat::where('cliente_id', $bsUser->id)->where('estado', 'abierto')
                            ->where('ultimo_mensaje_at', '>=', now()->subDays(3))->count();
                        if ($bsChatCli > 0 && \Illuminate\Support\Facades\Route::has('cliente.chats')) {
                            $bsNotifs[] = ['icon' => 'fa-comments', 'bg' => '#DBEAFE', 'color' => '#1D4ED8',
                                'title' => 'Tienes ' . $bsChatCli . ' chat' . ($bsChatCli === 1 ? '' : 's') . ' activo' . ($bsChatCli === 1 ? '' : 's'),
                                'sub' => 'Revisa las respuestas del equipo', 'url' => route('cliente.chats')];
                        }
                    }
                }
                if ($bsIsAdmin) {
                    $bsDteError = \App\Models\FacturaEmitida::whereIn('status', ['error', 'pending'])->count();
                    if ($bsDteError > 0) {
                        $bsNotifs[] = ['icon' => 'fa-triangle-exclamation', 'bg' => '#FEE2E2', 'color' => '#B91C1C',
                            'title' => $bsDteError . ' documento' . ($bsDteError === 1 ? '' : 's') . ' sin emitir',
                            'sub' => 'Pedidos con error en Lioren', 'url' => route('boletas.index')];
                    }
                    $bsCobrosPend = \App\Models\AgenciaCobro::where('estado', 'pendiente')->count();
                    if ($bsCobrosPend > 0) {
                        $bsNotifs[] = ['icon' => 'fa-hand-holding-dollar', 'bg' => '#FEF3C7', 'color' => '#92400E',
                            'title' => $bsCobrosPend . ' cobro' . ($bsCobrosPend === 1 ? '' : 's') . ' pendiente' . ($bsCobrosPend === 1 ? '' : 's'),
                            'sub' => 'Cobros de agencia por pagar', 'url' => route('agencia.cobros')];
                    }
                    $bsCotizPorFacturar = \App\Models\AgenciaCotizacion::whereNotNull('pagado_at')
                        ->where(function ($q) { $q->whereNull('factura_estado')->orWhereNotIn('factura_estado', ['emitida']); })->count();
                    if ($bsCotizPorFacturar > 0) {
                        $bsNotifs[] = ['icon' => 'fa-file-circle-check', 'bg' => '#DCFCE7', 'color' => '#15803D',
                            'title' => $bsCotizPorFacturar . ' cotizacion' . ($bsCotizPorFacturar === 1 ? '' : 'es') . ' pagada' . ($bsCotizPorFacturar === 1 ? '' : 's'),
                            'sub' => 'Pagadas, falta emitir factura', 'url' => route('agencia.cotizaciones')];
                    }
                    if (\Illuminate\Support\Facades\Schema::hasTable('chats')) {
                        $bsChatsAdmin = \App\Models\Chat::where('estado', 'abierto')->where('ultimo_mensaje_at', '>=', now()->subDays(3))->count();
                        if ($bsChatsAdmin > 0) {
                            $bsNotifs[] = ['icon' => 'fa-comments', 'bg' => '#DBEAFE', 'color' => '#1D4ED8',
                                'title' => $bsChatsAdmin . ' chat' . ($bsChatsAdmin === 1 ? '' : 's') . ' activo' . ($bsChatsAdmin === 1 ? '' : 's'),
                                'sub' => 'Clientes esperando respuesta', 'url' => route('admin.chats')];
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Notif topbar: ' . $e->getMessage());
            }
            // Filtrar notificaciones ya descartadas por el usuario (persistido en BD).
            $bsDismissed = is_array($bsUser->notif_dismissed ?? null) ? $bsUser->notif_dismissed : [];
            if (!empty($bsDismissed)) {
                $bsNotifs = array_values(array_filter($bsNotifs, function ($n) use ($bsDismissed) {
                    return !in_array(\Illuminate\Support\Str::slug($n['title']), $bsDismissed);
                }));
            }
            $bsNotifCount = count($bsNotifs);
        @endphp
        <div class="top-bar">
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>

            <div class="bs-topbar-left">
                @if (isset($header))
                    {{ $header }}
                @endif
            </div>

            <div class="bs-topbar-right">
                {{-- Plan badge (solo cliente) --}}
                @if($bsPlanBadge)
                    <div class="bs-plan-badge" title="Tu plan Big Studio actual">
                        <i class="fas fa-crown"></i>
                        <span>{{ $bsPlanBadge }}</span>
                    </div>
                @endif

                {{-- Fecha --}}
                <div class="bs-topbar-date">{{ now()->format('d M Y') }}</div>

                {{-- Notificaciones --}}
                <div class="bs-topbar-bell" onclick="bsToggleNotif()" id="bsBell">
                    <i class="fas fa-bell"></i>
                    @if($bsNotifCount > 0)
                        <span class="bs-bell-badge" id="bsBellBadge">{{ $bsNotifCount > 9 ? '9+' : $bsNotifCount }}</span>
                    @endif
                </div>

                {{-- Avatar usuario --}}
                <div class="bs-topbar-avatar" onclick="bsToggleUserMenu()" id="bsAvatar" title="{{ $bsUser->name }}">
                    @if($bsUser->profile_photo_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($bsUser->profile_photo_path) }}" alt="{{ $bsUser->name }}" style="width:100%;height:100%;object-fit:cover;object-position:center;display:block;">
                    @else
                        {{ $bsInitial }}
                    @endif
                </div>

                {{-- Dropdown notificaciones --}}
                <div id="bsNotifMenu" class="bs-topbar-dropdown" style="display:none; right: 70px;">
                    <div class="bs-dropdown-header">
                        Notificaciones
                        @if($bsNotifCount > 0)<span class="bs-dropdown-count" id="bsDropCount">{{ $bsNotifCount }}</span>@endif
                    </div>
                    @forelse($bsNotifs as $n)
                        <a href="{{ $n['url'] }}" class="bs-dropdown-item" data-bs-notif="{{ \Illuminate\Support\Str::slug($n['title']) }}">
                            <span class="bs-dropdown-icon" style="background:{{ $n['bg'] }}; color:{{ $n['color'] }};"><i class="fas {{ $n['icon'] }}"></i></span>
                            <div style="flex:1; min-width:0;">
                                <p class="bs-dropdown-title">{{ $n['title'] }}</p>
                                <p class="bs-dropdown-sub">{{ $n['sub'] }}</p>
                            </div>
                            <button type="button" class="bs-notif-x" title="Marcar como le&iacute;da" onclick="event.preventDefault(); event.stopPropagation(); bsDismissNotif(this.closest('[data-bs-notif]').getAttribute('data-bs-notif'));">&times;</button>
                        </a>
                    @empty
                    @endforelse
                    <div class="bs-dropdown-empty" id="bsNotifEmpty" style="{{ $bsNotifCount > 0 ? 'display:none;' : '' }}">
                        <i class="fas fa-check-circle"></i>
                        <p class="m-0">No hay notificaciones nuevas</p>
                    </div>
                </div>

                {{-- Dropdown usuario --}}
                <div id="bsUserMenu" class="bs-topbar-dropdown" style="display:none;">
                    <div class="bs-dropdown-user-header">
                        <div class="bs-dropdown-avatar">{{ $bsInitial }}</div>
                        <div>
                            <p class="bs-dropdown-user-name">{{ $bsUser->name }}</p>
                            <p class="bs-dropdown-user-email">{{ $bsUser->email }}</p>
                        </div>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="bs-dropdown-item">
                        <span class="bs-dropdown-icon"><i class="fas fa-user-circle"></i></span>
                        <span>Mi perfil</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="bs-dropdown-item-form">
                        @csrf
                        <button type="submit" class="bs-dropdown-item w-full">
                            <span class="bs-dropdown-icon" style="background:#FEF2F2; color:#DC2626;"><i class="fas fa-sign-out-alt"></i></span>
                            <span>Cerrar sesi&oacute;n</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }

        // Dropdowns del topbar (notificaciones + usuario)
        function bsToggleNotif() {
            const n = document.getElementById('bsNotifMenu');
            const u = document.getElementById('bsUserMenu');
            if (u) u.style.display = 'none';
            const opening = n.style.display === 'none';
            n.style.display = opening ? 'block' : 'none';
            // Al ABRIR: marcar como vistas todas las notificaciones actuales (persisten),
            // pero siguen visibles en esta sesion. En la proxima carga no reaparecen.
            if (opening && typeof bsMarkAllNotifSeen === 'function') {
                bsMarkAllNotifSeen();
            }
        }
        function bsToggleUserMenu() {
            const u = document.getElementById('bsUserMenu');
            const n = document.getElementById('bsNotifMenu');
            if (n) n.style.display = 'none';
            u.style.display = u.style.display === 'none' ? 'block' : 'none';
        }
        document.addEventListener('click', function(e) {
            const bell = document.getElementById('bsBell');
            const av   = document.getElementById('bsAvatar');
            const n    = document.getElementById('bsNotifMenu');
            const u    = document.getElementById('bsUserMenu');
            if (n && bell && !bell.contains(e.target) && !n.contains(e.target)) n.style.display = 'none';
            if (u && av   && !av.contains(e.target)   && !u.contains(e.target)) u.style.display = 'none';
        });
    </script>
    @else
    <!-- Guest: no sidebar -->
    <div class="min-h-screen">
        <main>
            {{ $slot }}
        </main>
    </div>
    @endauth

    @auth
    @if(auth()->user()->hasRole('admin'))
    <script>
        function updateUnreadCount() {
            fetch('{{ route("admin.chats.unreadCount") }}')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('unreadBadge');
                    if (badge) {
                        if (data.count > 0) {
                            badge.textContent = data.count;
                            badge.style.display = 'inline-block';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        updateUnreadCount();
        setInterval(updateUnreadCount, 120000);
    </script>
    @endif
    @if(auth()->user()->hasRole('cliente'))
    <script>
        function updateClienteUnreadCount() {
            fetch('{{ route("cliente.chats.unreadCount") }}')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('clienteUnreadBadge');
                    if (badge) {
                        if (data.count > 0) {
                            badge.textContent = data.count;
                            badge.style.display = 'inline-block';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        updateClienteUnreadCount();
        setInterval(updateClienteUnreadCount, 60000);
    </script>
    @endif
    @endauth

    <script>
        // Sidebar accordion de apertura UNICA: solo un modulo abierto a la vez.
        function toggleAccordion(header) {
            var item = header.closest('.sidebar-accordion');
            if (!item) return;
            var willOpen = !item.classList.contains('open');
            document.querySelectorAll('.sidebar-accordion').forEach(function (o) { o.classList.remove('open'); });
            if (willOpen) {
                item.classList.add('open');
                try { localStorage.setItem('bs-accordion-open', (header.dataset && header.dataset.accordion) || ''); } catch (e) {}
            } else {
                try { localStorage.removeItem('bs-accordion-open'); } catch (e) {}
            }
        }
        // Al cargar: el SERVIDOR ya abrio el modulo correcto (clase .open en el HTML),
        // por eso NO tocamos nada si ya hay uno abierto -> cero animacion -> cero parpadeo.
        document.addEventListener('DOMContentLoaded', function () {
            var accs = Array.prototype.slice.call(document.querySelectorAll('.sidebar-accordion'));
            if (!accs.length) return;

            // Si el servidor ya dejo un modulo abierto, respetarlo (no re-aplicar).
            var yaAbierto = accs.some(function (item) { return item.classList.contains('open'); });
            if (yaAbierto) return;

            // Fallback (solo si el server no abrio ninguno): link activo o ultimo abierto.
            var target = accs.find(function (item) { return !!item.querySelector('.sidebar-link.active'); });
            if (!target) {
                var saved = null; try { saved = localStorage.getItem('bs-accordion-open'); } catch (e) {}
                if (saved) target = accs.find(function (item) { return (item.dataset && item.dataset.accordion) === saved; });
            }
            if (target) target.classList.add('open');
        });
    </script>

    <script>
    /* BS-NOTIF-READ-JS v2: persistencia SERVER-SIDE (BD users.notif_dismissed). El @php ya filtra al cargar. */
    function bsNotifMarcarServidor(keys){
        if(!keys || !keys.length) return;
        try {
            fetch('{{ route('notificaciones.marcar-vistas') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ keys: keys })
            });
        } catch(e){}
    }
    function bsNotifRefreshBadge(){
        var menu = document.getElementById('bsNotifMenu'); if(!menu) return;
        var items = menu.querySelectorAll('.bs-dropdown-item[data-bs-notif]');
        var visible = 0;
        items.forEach(function(it){ if(it.style.display !== 'none') visible++; });
        var bell = document.getElementById('bsBellBadge');
        if (bell){ if(visible>0){ bell.textContent = visible>9?'9+':visible; bell.style.display=''; } else { bell.style.display='none'; } }
        var cnt = document.getElementById('bsDropCount');
        if (cnt){ if(visible>0){ cnt.textContent = visible; cnt.style.display=''; } else { cnt.style.display='none'; } }
        var empty = document.getElementById('bsNotifEmpty');
        if (empty){ empty.style.display = visible>0 ? 'none' : ''; }
    }
    // Descartar una: ocultar en DOM + persistir en servidor (no reaparece nunca).
    function bsDismissNotif(key){
        var menu = document.getElementById('bsNotifMenu'); if(!menu) return;
        var el = menu.querySelector('.bs-dropdown-item[data-bs-notif="'+key+'"]');
        if (el) el.style.display = 'none';
        bsNotifRefreshBadge();
        bsNotifMarcarServidor([key]);
    }
    // Al abrir la campana: marcar TODAS como vistas en el servidor (siguen visibles esta sesion, no reaparecen al recargar).
    function bsMarkAllNotifSeen(){
        var menu = document.getElementById('bsNotifMenu'); if(!menu) return;
        var keys = [];
        menu.querySelectorAll('.bs-dropdown-item[data-bs-notif]').forEach(function(it){
            var k = it.getAttribute('data-bs-notif'); if(k) keys.push(k);
        });
        bsNotifMarcarServidor(keys);
    }
    document.addEventListener('DOMContentLoaded', function(){
        var menu = document.getElementById('bsNotifMenu'); if(!menu) return;
        menu.querySelectorAll('.bs-dropdown-item[data-bs-notif]').forEach(function(it){
            it.addEventListener('click', function(){ bsNotifMarcarServidor([it.getAttribute('data-bs-notif')]); }, true);
        });
        bsNotifRefreshBadge();
    });
    </script>
</body>
</html>
