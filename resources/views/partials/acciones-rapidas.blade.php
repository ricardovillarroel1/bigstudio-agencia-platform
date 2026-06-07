@auth
@if(Auth::user()->hasRole('admin'))
<!-- Acciones Rápidas -->
<div style="background: white; border-radius: 1rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); padding: 1.5rem; margin: 1.5rem 1.5rem 0 1.5rem;">
    <h3 style="font-size: 1.25rem; font-weight: 700; color: #111827; margin-bottom: 1.5rem;">Acciones Rápidas</h3>
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; width: 100%; box-sizing: border-box;">
        <a href="{{ route('usuarios.index') }}" style="display: flex; align-items: center; justify-content: center; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); border-radius: 0.5rem; font-weight: 700; font-size: 0.875rem; color: #000; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;">
            <svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            Usuarios
        </a>
        <a href="{{ route('integracion.index') }}" style="display: flex; align-items: center; justify-content: center; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); border-radius: 0.5rem; font-weight: 700; font-size: 0.875rem; color: #000; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;">
            <svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            Integración
        </a>
        <a href="{{ route('warehouse.config') }}" style="display: flex; align-items: center; justify-content: center; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); border-radius: 0.5rem; font-weight: 700; font-size: 0.875rem; color: #000; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;">
            <svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            Bodegas
        </a>
        <a href="{{ route('boletas.index') }}" style="display: flex; align-items: center; justify-content: center; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); border-radius: 0.5rem; font-weight: 700; font-size: 0.875rem; color: #000; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;">
            <svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Clientes
        </a>
        <a href="{{ route('flow.payment-form') }}" style="display: flex; align-items: center; justify-content: center; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #10B981 0%, #059669 100%); border-radius: 0.5rem; font-weight: 700; font-size: 0.875rem; color: #fff; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;">
            <svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
            Flow Pagos
        </a>
        <a href="{{ route('admin.cobros-asignados.index') }}" style="display: flex; align-items: center; justify-content: center; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%); border-radius: 0.5rem; font-weight: 700; font-size: 0.875rem; color: #fff; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;">
            <svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            Cobros Asignados
        </a>
        <a href="{{ route('agencia.dashboard') }}" style="display: flex; align-items: center; justify-content: center; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #EC4899 0%, #DB2777 100%); border-radius: 0.5rem; font-weight: 700; font-size: 0.875rem; color: #fff; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;">
            <svg style="width: 1.25rem; height: 1.25rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            Servicios Agencia
        </a>
    </div>
</div>
@endif
@endauth
