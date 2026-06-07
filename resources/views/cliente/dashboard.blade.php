<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-bold text-gray-800 leading-tight">
            <span class="text-brand-600">Mi</span> Panel
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Hero card --}}
            <div class="rounded-2xl p-8 text-gray-900 relative overflow-hidden"
                 style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%); box-shadow: 0 10px 30px -5px rgba(255, 129, 0, 0.4);">
                <div class="relative z-10">
                    <h1 class="bs-display text-3xl sm:text-4xl m-0">Bienvenido, {{ auth()->user()->name }}</h1>
                    <p class="text-gray-900/80 mt-2 mb-0 text-sm font-medium">Panel de Cliente &middot; Big Studio</p>
                </div>
                {{-- Decoraci&oacute;n geom&eacute;trica --}}
                <div class="absolute right-0 top-0 opacity-10 pointer-events-none">
                    <svg width="220" height="220" viewBox="0 0 220 220" fill="none">
                        <circle cx="180" cy="40" r="80" stroke="white" stroke-width="3"/>
                        <circle cx="180" cy="40" r="40" fill="white"/>
                    </svg>
                </div>
            </div>

            {{-- Mi cuenta --}}
            <div>
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Mi cuenta</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="{{ route('cliente.planes') }}" class="bs-card hover:border-brand-300 hover:shadow-bs-glow transition-all p-5 flex items-center gap-4 no-underline group">
                        <div class="w-12 h-12 rounded-xl bg-brand-50 group-hover:bg-brand-100 transition-colors flex items-center justify-center text-brand-600 text-xl flex-shrink-0">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 m-0">Planes disponibles</h4>
                            <p class="text-xs text-gray-500 mt-0.5 mb-0">Suscr&iacute;bete a uno nuevo</p>
                        </div>
                    </a>
                    <a href="{{ route('cliente.planes-activos') }}" class="bs-card hover:border-brand-300 hover:shadow-bs-glow transition-all p-5 flex items-center gap-4 no-underline group">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 group-hover:bg-emerald-100 transition-colors flex items-center justify-center text-emerald-600 text-xl flex-shrink-0">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 m-0">Mi plan activo</h4>
                            <p class="text-xs text-gray-500 mt-0.5 mb-0">Lo que tienes contratado</p>
                        </div>
                    </a>
                    <a href="{{ route('profile.edit') }}" class="bs-card hover:border-brand-300 hover:shadow-bs-glow transition-all p-5 flex items-center gap-4 no-underline group">
                        <div class="w-12 h-12 rounded-xl bg-gray-100 group-hover:bg-brand-100 group-hover:text-brand-600 transition-colors flex items-center justify-center text-gray-600 text-xl flex-shrink-0">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 m-0">Mi perfil</h4>
                            <p class="text-xs text-gray-500 mt-0.5 mb-0">Datos y configuraci&oacute;n</p>
                        </div>
                    </a>
                </div>
            </div>

            {{-- Operaci&oacute;n --}}
            <div>
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Operaci&oacute;n</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="{{ route('cliente.estados-solicitud') }}" class="bs-card hover:border-brand-300 hover:shadow-bs-glow transition-all p-5 flex items-center gap-4 no-underline group">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 group-hover:bg-amber-100 transition-colors flex items-center justify-center text-amber-600 text-xl flex-shrink-0">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 m-0">Estados de solicitud</h4>
                            <p class="text-xs text-gray-500 mt-0.5 mb-0">Ver mis solicitudes</p>
                        </div>
                    </a>
                    <a href="{{ route('cliente.inventario') }}" class="bs-card hover:border-brand-300 hover:shadow-bs-glow transition-all p-5 flex items-center gap-4 no-underline group">
                        <div class="w-12 h-12 rounded-xl bg-gray-100 group-hover:bg-brand-100 group-hover:text-brand-600 transition-colors flex items-center justify-center text-gray-600 text-xl flex-shrink-0">
                            <i class="fas fa-boxes-stacked"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 m-0">Inventario</h4>
                            <p class="text-xs text-gray-500 mt-0.5 mb-0">Productos sincronizados</p>
                        </div>
                    </a>
                    <a href="{{ route('cliente.chats') }}" class="bs-card hover:border-brand-300 hover:shadow-bs-glow transition-all p-5 flex items-center gap-4 no-underline group">
                        <div class="w-12 h-12 rounded-xl bg-sky-50 group-hover:bg-sky-100 transition-colors flex items-center justify-center text-sky-600 text-xl flex-shrink-0">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 m-0">Chats</h4>
                            <p class="text-xs text-gray-500 mt-0.5 mb-0">Conversa con tu cuenta</p>
                        </div>
                    </a>
                </div>
            </div>

            {{-- Documentos --}}
            <div>
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Mis documentos emitidos</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="{{ route('cliente.documentos-emitidos') }}" class="bs-card hover:border-brand-300 hover:shadow-bs-glow transition-all p-5 flex items-center gap-4 no-underline group">
                        <div class="w-12 h-12 rounded-xl bg-brand-50 group-hover:bg-brand-100 transition-colors flex items-center justify-center text-brand-600 text-xl flex-shrink-0">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 m-0">Documentos emitidos</h4>
                            <p class="text-xs text-gray-500 mt-0.5 mb-0">Boletas, facturas y notas de cr&eacute;dito</p>
                        </div>
                    </a>
                    <a href="{{ route('cliente.facturas') }}" class="bs-card hover:border-brand-300 hover:shadow-bs-glow transition-all p-5 flex items-center gap-4 no-underline group">
                        <div class="w-12 h-12 rounded-xl bg-brand-50 group-hover:bg-brand-100 transition-colors flex items-center justify-center text-brand-600 text-xl flex-shrink-0">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 m-0">Mis facturas</h4>
                            <p class="text-xs text-gray-500 mt-0.5 mb-0">Documentos tributarios</p>
                        </div>
                    </a>
                </div>
            </div>

            {{-- Mi suscripcion --}}
            <div>
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Mi suscripci&oacute;n</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="{{ route('cliente.facturas-servicio') }}" class="bs-card hover:border-brand-300 hover:shadow-bs-glow transition-all p-5 flex items-center gap-4 no-underline group">
                        <div class="w-12 h-12 rounded-xl bg-gray-100 group-hover:bg-brand-100 group-hover:text-brand-600 transition-colors flex items-center justify-center text-gray-600 text-xl flex-shrink-0">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 m-0">Facturas de servicio</h4>
                            <p class="text-xs text-gray-500 mt-0.5 mb-0">Lo que pagas por la plataforma</p>
                        </div>
                    </a>
                    <a href="{{ route('cliente.cobros-pendientes') }}" class="bs-card hover:border-brand-300 hover:shadow-bs-glow transition-all p-5 flex items-center gap-4 no-underline group">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 group-hover:bg-amber-100 transition-colors flex items-center justify-center text-amber-600 text-xl flex-shrink-0">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 m-0">Cobros pendientes</h4>
                            <p class="text-xs text-gray-500 mt-0.5 mb-0">Pagos por confirmar</p>
                        </div>
                    </a>
                </div>
            </div>

            {{-- Info --}}
            <div class="rounded-xl p-5 mt-4" style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%); border: 1px solid #FFD89C;">
                <h3 class="bs-display text-lg m-0" style="color: #B85B00;">&#128229; Bienvenido a Big Studio</h3>
                <p class="text-sm mt-1.5 mb-0" style="color: #8A4400;">
                    Desde este panel puedes gestionar tus planes, consultar el estado de tus solicitudes y revisar tus facturas.
                    Si necesitas ayuda usa el m&oacute;dulo de Chats para conversar con tu cuenta.
                </p>
            </div>

        </div>
    </div>
</x-app-layout>
