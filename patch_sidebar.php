<?php
// Patch: Add Finanzas + Config sidebar sections to app.blade.php

$filePath = '/var/www/shopify-integrator/resources/views/layouts/app.blade.php';
$content = file_get_contents($filePath);

// Find the end of Agencia section (before @endrole for admin)
// The structure is: </div> (agencia section) then @endrole
// We need to add Finanzas + Config sections between the Agencia </div> and @endrole

$agenciaEndMarker = '<i class="fas fa-envelope"></i> Correos
            </a>
        </div>
        @endrole';

$replacement = '<i class="fas fa-envelope"></i> Correos
            </a>
        </div>

        <div class="sidebar-divider"></div>
        <!-- MÓDULO: FINANZAS -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Finanzas</div>
            <a href="{{ route(\'finanzas.dashboard\') }}" class="sidebar-link {{ request()->routeIs(\'finanzas.dashboard\') ? \'active\' : \'\' }}">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <a href="{{ route(\'finanzas.ingresos\') }}" class="sidebar-link {{ request()->routeIs(\'finanzas.ingresos\') ? \'active\' : \'\' }}">
                <i class="fas fa-arrow-circle-down"></i> Ingresos
            </a>
            <a href="{{ route(\'finanzas.egresos\') }}" class="sidebar-link {{ request()->routeIs(\'finanzas.egresos*\') ? \'active\' : \'\' }}">
                <i class="fas fa-arrow-circle-up"></i> Egresos
            </a>
            <a href="{{ route(\'finanzas.iva\') }}" class="sidebar-link {{ request()->routeIs(\'finanzas.iva\') ? \'active\' : \'\' }}">
                <i class="fas fa-percentage"></i> IVA Mensual
            </a>
            <a href="{{ route(\'finanzas.banco\') }}" class="sidebar-link {{ request()->routeIs(\'finanzas.banco*\') ? \'active\' : \'\' }}">
                <i class="fas fa-university"></i> Banco
            </a>
            <a href="{{ route(\'finanzas.cuentas-cobrar\') }}" class="sidebar-link {{ request()->routeIs(\'finanzas.cuentas-cobrar\') ? \'active\' : \'\' }}">
                <i class="fas fa-hand-holding-usd"></i> Cuentas por Cobrar
            </a>
            <a href="{{ route(\'finanzas.cuentas-pagar\') }}" class="sidebar-link {{ request()->routeIs(\'finanzas.cuentas-pagar\') ? \'active\' : \'\' }}">
                <i class="fas fa-credit-card"></i> Cuentas por Pagar
            </a>
            <a href="{{ route(\'finanzas.reportes\') }}" class="sidebar-link {{ request()->routeIs(\'finanzas.reportes*\') ? \'active\' : \'\' }}">
                <i class="fas fa-file-alt"></i> Reportes
            </a>
            <a href="{{ route(\'finanzas.presupuesto\') }}" class="sidebar-link {{ request()->routeIs(\'finanzas.presupuesto\') ? \'active\' : \'\' }}">
                <i class="fas fa-calculator"></i> Presupuesto
            </a>
        </div>

        <div class="sidebar-divider"></div>
        <!-- MÓDULO: CONFIGURACIÓN -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Configuración</div>
            <a href="{{ route(\'config.colaboradores\') }}" class="sidebar-link {{ request()->routeIs(\'config.colaboradores*\') ? \'active\' : \'\' }}">
                <i class="fas fa-user-shield"></i> Colaboradores
            </a>
        </div>
        @endrole

        @role(\'colaborador\')
        <!-- SIDEBAR DINÁMICO PARA COLABORADORES -->
        @php $userPerms = auth()->user()->getAllPermissions()->pluck(\'name\')->toArray(); @endphp

        @if(count(array_filter($userPerms, fn($p) => str_starts_with($p, \'integraciones.\'))) > 0)
        <div class="sidebar-section">
            <div class="sidebar-section-title">Integraciones</div>
            @if(in_array(\'integraciones.dashboard\', $userPerms))<a href="{{ route(\'dashboard\') }}" class="sidebar-link {{ request()->routeIs(\'dashboard\') ? \'active\' : \'\' }}"><i class="fas fa-tachometer-alt"></i> Panel Principal</a>@endif
            @if(in_array(\'integraciones.boletas\', $userPerms))<a href="{{ route(\'boletas.index\') }}" class="sidebar-link {{ request()->routeIs(\'boletas.*\') ? \'active\' : \'\' }}"><i class="fas fa-file-invoice"></i> Documentos Emitidos</a>@endif
            @if(in_array(\'integraciones.facturas\', $userPerms))<a href="{{ route(\'admin.billing.index\') }}" class="sidebar-link {{ request()->routeIs(\'admin.billing.*\') ? \'active\' : \'\' }}"><i class="fas fa-file-invoice-dollar"></i> Facturación</a>@endif
            @if(in_array(\'integraciones.clientes\', $userPerms))<a href="{{ route(\'usuarios.index\') }}" class="sidebar-link {{ request()->routeIs(\'usuarios.*\') ? \'active\' : \'\' }}"><i class="fas fa-users"></i> Usuarios</a>@endif
            @if(in_array(\'integraciones.suscripciones\', $userPerms))<a href="{{ route(\'admin.suscripciones\') }}" class="sidebar-link {{ request()->routeIs(\'admin.suscripciones\') ? \'active\' : \'\' }}"><i class="fas fa-id-card"></i> Suscripciones</a>@endif
            @if(in_array(\'integraciones.chats\', $userPerms))<a href="{{ route(\'admin.chats\') }}" class="sidebar-link {{ request()->routeIs(\'admin.chats\') ? \'active\' : \'\' }}"><i class="fas fa-comments"></i> Chats</a>@endif
            @if(in_array(\'integraciones.configuracion\', $userPerms))<a href="{{ route(\'admin.settings\') }}" class="sidebar-link {{ request()->routeIs(\'admin.settings\') ? \'active\' : \'\' }}"><i class="fas fa-cog"></i> Configuración</a>@endif
        </div>
        @endif

        @if(count(array_filter($userPerms, fn($p) => str_starts_with($p, \'agencia.\'))) > 0)
        <div class="sidebar-section">
            <div class="sidebar-section-title">Agencia</div>
            @if(in_array(\'agencia.dashboard\', $userPerms))<a href="{{ route(\'agencia.dashboard\') }}" class="sidebar-link {{ request()->routeIs(\'agencia.dashboard\') ? \'active\' : \'\' }}"><i class="fas fa-chart-line"></i> Dashboard</a>@endif
            @if(in_array(\'agencia.clientes\', $userPerms))<a href="{{ route(\'agencia.clientes\') }}" class="sidebar-link {{ request()->routeIs(\'agencia.clientes*\') ? \'active\' : \'\' }}"><i class="fas fa-user-tie"></i> Clientes</a>@endif
            @if(in_array(\'agencia.servicios\', $userPerms))<a href="{{ route(\'agencia.servicios\') }}" class="sidebar-link {{ request()->routeIs(\'agencia.servicios*\') ? \'active\' : \'\' }}"><i class="fas fa-concierge-bell"></i> Servicios</a>@endif
            @if(in_array(\'agencia.cobros\', $userPerms))<a href="{{ route(\'agencia.cobros\') }}" class="sidebar-link {{ request()->routeIs(\'agencia.cobros*\') ? \'active\' : \'\' }}"><i class="fas fa-hand-holding-usd"></i> Cobros</a>@endif
            @if(in_array(\'agencia.cotizaciones\', $userPerms))<a href="{{ route(\'agencia.cotizaciones\') }}" class="sidebar-link {{ request()->routeIs(\'agencia.cotizaciones*\') ? \'active\' : \'\' }}"><i class="fas fa-file-invoice"></i> Cotizaciones</a>@endif
        </div>
        @endif

        @if(count(array_filter($userPerms, fn($p) => str_starts_with($p, \'finanzas.\'))) > 0)
        <div class="sidebar-section">
            <div class="sidebar-section-title">Finanzas</div>
            @if(in_array(\'finanzas.dashboard\', $userPerms))<a href="{{ route(\'finanzas.dashboard\') }}" class="sidebar-link {{ request()->routeIs(\'finanzas.dashboard\') ? \'active\' : \'\' }}"><i class="fas fa-chart-pie"></i> Dashboard</a>@endif
            @if(in_array(\'finanzas.ingresos\', $userPerms))<a href="{{ route(\'finanzas.ingresos\') }}" class="sidebar-link {{ request()->routeIs(\'finanzas.ingresos\') ? \'active\' : \'\' }}"><i class="fas fa-arrow-circle-down"></i> Ingresos</a>@endif
            @if(in_array(\'finanzas.egresos\', $userPerms))<a href="{{ route(\'finanzas.egresos\') }}" class="sidebar-link {{ request()->routeIs(\'finanzas.egresos*\') ? \'active\' : \'\' }}"><i class="fas fa-arrow-circle-up"></i> Egresos</a>@endif
            @if(in_array(\'finanzas.iva\', $userPerms))<a href="{{ route(\'finanzas.iva\') }}" class="sidebar-link {{ request()->routeIs(\'finanzas.iva\') ? \'active\' : \'\' }}"><i class="fas fa-percentage"></i> IVA Mensual</a>@endif
            @if(in_array(\'finanzas.banco\', $userPerms))<a href="{{ route(\'finanzas.banco\') }}" class="sidebar-link {{ request()->routeIs(\'finanzas.banco*\') ? \'active\' : \'\' }}"><i class="fas fa-university"></i> Banco</a>@endif
            @if(in_array(\'finanzas.cuentas-cobrar\', $userPerms))<a href="{{ route(\'finanzas.cuentas-cobrar\') }}" class="sidebar-link {{ request()->routeIs(\'finanzas.cuentas-cobrar\') ? \'active\' : \'\' }}"><i class="fas fa-hand-holding-usd"></i> Cuentas por Cobrar</a>@endif
            @if(in_array(\'finanzas.cuentas-pagar\', $userPerms))<a href="{{ route(\'finanzas.cuentas-pagar\') }}" class="sidebar-link {{ request()->routeIs(\'finanzas.cuentas-pagar\') ? \'active\' : \'\' }}"><i class="fas fa-credit-card"></i> Cuentas por Pagar</a>@endif
            @if(in_array(\'finanzas.reportes\', $userPerms))<a href="{{ route(\'finanzas.reportes\') }}" class="sidebar-link {{ request()->routeIs(\'finanzas.reportes*\') ? \'active\' : \'\' }}"><i class="fas fa-file-alt"></i> Reportes</a>@endif
            @if(in_array(\'finanzas.presupuesto\', $userPerms))<a href="{{ route(\'finanzas.presupuesto\') }}" class="sidebar-link {{ request()->routeIs(\'finanzas.presupuesto\') ? \'active\' : \'\' }}"><i class="fas fa-calculator"></i> Presupuesto</a>@endif
        </div>
        @endif
        @endrole';

if (strpos($content, 'finanzas.dashboard') === false) {
    $content = str_replace($agenciaEndMarker, $replacement, $content);
    file_put_contents($filePath, $content);
    echo "Sidebar updated with Finanzas + Config + Colaborador sections\n";
} else {
    echo "Sidebar already has Finanzas section\n";
}
