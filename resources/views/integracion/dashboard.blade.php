<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Integración Shopify - Lioren') }}
        </h2>
    </x-slot>
    <div class="py-12 bg-gradient-to-br from-brand-50 via-white to-brand-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="bg-gradient-to-r from-brand-600 to-brand-600 overflow-hidden shadow-xl sm:rounded-2xl mb-8">
                <div class="p-8 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-4xl font-bold mb-2">Dashboard de Integración</h1>
                            <p class="text-brand-100 text-lg">Shopify - Lioren | Sistema Multi-Cliente</p>
                        </div>
                        <div class="hidden md:block">
                            <div class="text-6xl opacity-20">
                                <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative shadow-sm">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative shadow-sm">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            <!-- Revenue Section -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl mb-8 border border-gray-100">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Ingresos Totales</h2>
                            <p class="text-sm text-gray-500">Resumen de pagos recibidos en CLP</p>
                        </div>
                        <div class="flex gap-2 mt-3 md:mt-0">
                            <a href="{{ route('integracion.dashboard', ['revenue_filter' => 'day']) }}"
                               class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $revenueFilter === 'day' ? 'bg-brand-600 text-white shadow-lg' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                Hoy
                            </a>
                            <a href="{{ route('integracion.dashboard', ['revenue_filter' => 'month']) }}"
                               class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $revenueFilter === 'month' ? 'bg-brand-600 text-white shadow-lg' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                Mes
                            </a>
                            <a href="{{ route('integracion.dashboard', ['revenue_filter' => 'year']) }}"
                               class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $revenueFilter === 'year' ? 'bg-brand-600 text-white shadow-lg' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                Año
                            </a>
                            <a href="{{ route('integracion.dashboard', ['revenue_filter' => 'all']) }}"
                               class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $revenueFilter === 'all' ? 'bg-brand-600 text-white shadow-lg' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                Total
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Main Revenue Display -->
                        <div class="md:col-span-2 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-6 text-white">
                            <p class="text-green-100 text-sm font-semibold uppercase tracking-wider mb-1">
                                @if($revenueFilter === 'day')
                                    Ingresos Hoy
                                @elseif($revenueFilter === 'month')
                                    Ingresos del Mes
                                @elseif($revenueFilter === 'year')
                                    Ingresos del Año
                                @else
                                    Ingresos Totales
                                @endif
                            </p>
                            <p class="text-4xl font-bold">${{ number_format($totalRevenue, 0, ',', '.') }} <span class="text-lg font-normal text-green-100">CLP</span></p>
                            <p class="text-green-100 text-sm mt-2">{{ $totalPayments }} {{ $totalPayments === 1 ? 'pago' : 'pagos' }} registrados</p>
                        </div>

                        <!-- Revenue Breakdown -->
                        <div class="bg-gray-50 rounded-2xl p-5 border border-gray-200">
                            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Hoy</p>
                            <p class="text-2xl font-bold text-gray-800 mt-1">${{ number_format($revenueToday, 0, ',', '.') }}</p>
                            <p class="text-xs text-gray-400 mt-1">CLP</p>
                        </div>
                        <div class="bg-gray-50 rounded-2xl p-5 border border-gray-200">
                            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Este Mes</p>
                            <p class="text-2xl font-bold text-gray-800 mt-1">${{ number_format($revenueMonth, 0, ',', '.') }}</p>
                            <p class="text-xs text-gray-400 mt-1">CLP</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="mb-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 border-blue-500">
                        <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Integraciones</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['total_integraciones'] }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 border-green-500">
                        <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Productos</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['total_productos'] }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 border-brand-500">
                        <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Webhooks</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['total_webhooks'] }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 border-yellow-500">
                        <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Boletas</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['total_boletas'] }}</p>
                    </div>
                </div>
            </div>

            <!-- Recent Plan Subscribers -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl mb-8 border border-gray-100">
                <div class="bg-gradient-to-r from-gray-50 to-white p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800">Últimas Contrataciones de Plan</h2>
                    <p class="text-sm text-gray-500 mt-1">Los últimos 4 clientes que contrataron un plan</p>
                </div>
                <div class="p-6">
                    @if($recentSubscribers->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($recentSubscribers as $sub)
                        <div class="border border-gray-200 rounded-xl p-5 hover:shadow-md transition-shadow bg-white">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-brand-100 rounded-full flex items-center justify-center flex-shrink-0">
                                        <span class="text-brand-600 font-bold text-lg">{{ $sub->user ? substr($sub->user->name, 0, 1) : '?' }}</span>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-800">{{ $sub->user ? $sub->user->name : 'Usuario eliminado' }}</h3>
                                        <p class="text-sm text-gray-500">{{ $sub->user ? $sub->user->email : '-' }}</p>
                                    </div>
                                </div>
                                @if($sub->estado === 'activa')
                                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">Activa</span>
                                @elseif($sub->estado === 'vencida')
                                    <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">Vencida</span>
                                @else
                                    <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-semibold">{{ ucfirst($sub->estado) }}</span>
                                @endif
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <div>
                                    <span class="inline-flex items-center px-3 py-1 bg-brand-50 text-brand-700 rounded-lg text-sm font-semibold">
                                        {{ $sub->plan ? $sub->plan->nombre : 'Plan eliminado' }}
                                    </span>
                                    @if($sub->plan && $sub->plan->precio > 0)
                                        <span class="ml-2 text-sm font-bold text-gray-700">${{ number_format(strtoupper($sub->plan->moneda ?? 'CLP') === 'UF' ? round((float)$sub->plan->precio * $ufValue) : (float)$sub->plan->precio, 0, ',', '.') }} CLP</span>
                                    @elseif($sub->plan)
                                        <span class="ml-2 text-sm font-semibold text-green-600">Gratis</span>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-400">Contratado</p>
                                    <p class="text-sm font-semibold text-gray-600">{{ $sub->created_at ? \Carbon\Carbon::parse($sub->created_at)->format('d/m/Y') : '-' }}</p>
                                </div>
                            </div>
                            @if($sub->fecha_inicio && $sub->fecha_fin)
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <div class="flex justify-between text-xs text-gray-400">
                                    <span>Inicio: {{ $sub->fecha_inicio->format('d/m/Y') }}</span>
                                    <span>Vence: {{ $sub->fecha_fin->format('d/m/Y') }}</span>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                            </svg>
                        </div>
                        <p class="text-gray-500 font-medium">No hay contrataciones de plan registradas</p>
                        <p class="text-gray-400 text-sm mt-1">Las contrataciones aparecerán aquí cuando los clientes contraten un plan</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Integraciones Manuales (Admin) -->
            @if($integraciones->count() > 0)
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl mb-8 border border-gray-100">
                <div class="bg-gradient-to-r from-gray-50 to-white p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800">Integraciones Manuales (Admin)</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($integraciones as $integracion)
                        <div class="border rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-10 h-10 bg-brand-100 rounded-full flex items-center justify-center">
                                            <span class="text-brand-600 font-bold">{{ substr($integracion->user->name, 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-gray-800">{{ $integracion->user->name }}</h3>
                                            <p class="text-sm text-gray-600">{{ $integracion->shopify_tienda }}</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        @if($integracion->facturacion_enabled)
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Facturación</span>
                                        @endif
                                        @if($integracion->shopify_visibility_enabled)
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">Visibilidad</span>
                                        @endif
                                        @if($integracion->notas_credito_enabled)
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">Notas Crédito</span>
                                        @endif
                                        @if($integracion->order_limit_enabled)
                                            <span class="px-2 py-1 bg-brand-100 text-brand-800 rounded text-xs">Límite: {{ $integracion->monthly_order_limit }}</span>
                                        @else
                                            <span class="px-2 py-1 bg-brand-100 text-brand-800 rounded text-xs">Sin límite</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right flex flex-col items-end gap-2">
                                    <div>
                                        <p class="text-xs text-gray-500">Última sincronización</p>
                                        <p class="text-sm font-semibold text-gray-800">
                                            {{ $integracion->ultima_sincronizacion ? $integracion->ultima_sincronizacion->diffForHumans() : 'N/A' }}
                                        </p>
                                    </div>
                                    <form action="{{ route('admin.shopify.oauth.reconectar') }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $integracion->user_id }}">
                                        <button type="submit" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 text-white text-xs font-semibold rounded-lg hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-1 transition shadow-sm"
                                            onclick="return confirm('¿Reconectar OAuth para {{ $integracion->user->name }}? Esto renovará el token de acceso y actualizará los webhooks sin crear una nueva suscripción.')">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                            Reconectar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Botón para Configurar Nueva Integración -->
            <div class="mb-8">
                <a href="{{ route('integracion.configurar') }}" class="group block">
                    <div class="relative bg-gradient-to-r from-brand-600 to-brand-600 overflow-hidden shadow-xl sm:rounded-2xl transition-all duration-300 transform group-hover:-translate-y-1 group-hover:shadow-2xl">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-bl-full"></div>
                        <div class="p-8 relative">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="text-5xl mr-6">
                                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-2xl font-bold text-white mb-1">Configurar Nueva Integración</h3>
                                        <p class="text-brand-100 text-base">
                                            Conecta manualmente una nueva cuenta de Shopify con Lioren
                                        </p>
                                    </div>
                                </div>
                                <svg class="w-8 h-8 text-white group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
</x-app-layout>
