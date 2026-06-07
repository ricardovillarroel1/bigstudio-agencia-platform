<x-app-layout>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                📊 Estado de la Integración
            </h2>
            <a href="{{ route('integracion.dashboard') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                ← Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if($config)
                <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg mb-6">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <strong class="text-lg">✅ Integración Activa</strong>
                            <p class="text-sm">Tu tienda Shopify está conectada con Lioren</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    
                    <!-- Shopify -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 bg-gradient-to-r from-green-50 to-white border-b border-gray-200">
                            <h3 class="text-lg font-bold mb-4 flex items-center">
                                <span class="text-2xl mr-2">🛍️</span>
                                Shopify
                            </h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tienda:</span>
                                    <span class="font-semibold">{{ $config->shopify_tienda }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Token:</span>
                                    <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{ Str::limit($config->shopify_token, 20, '...') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Estado:</span>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">Conectado</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lioren -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 bg-gradient-to-r from-blue-50 to-white border-b border-gray-200">
                            <h3 class="text-lg font-bold mb-4 flex items-center">
                                <span class="text-2xl mr-2">📄</span>
                                Lioren
                            </h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">API Key:</span>
                                    <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{ Str::limit($config->lioren_api_key, 20, '...') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Estado:</span>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">Conectado</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Última Sync:</span>
                                    <span class="text-sm">{{ $config->ultima_sincronizacion?->diffForHumans() ?? 'Nunca' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Estadísticas -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-bold mb-4">📊 Estadísticas</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-gradient-to-br from-brand-50 to-brand-100 p-4 rounded-lg">
                                <div class="text-3xl font-bold text-brand-600">{{ $stats['productos'] }}</div>
                                <div class="text-sm text-brand-800">Productos Sincronizados</div>
                            </div>
                            <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg">
                                <div class="text-3xl font-bold text-green-600">{{ $stats['boletas'] }}</div>
                                <div class="text-sm text-green-800">Boletas Emitidas</div>
                            </div>
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg">
                                <div class="text-3xl font-bold text-blue-600">${{ number_format($stats['total_facturado'], 0, ',', '.') }}</div>
                                <div class="text-sm text-blue-800">Total Facturado</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cómo funciona -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h3 class="text-lg font-bold text-blue-900 mb-4">🎯 ¿Cómo funciona la integración automática?</h3>
                    <ol class="space-y-3 text-blue-800">
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">1</span>
                            <span>Un cliente realiza una compra en tu tienda Shopify</span>
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">2</span>
                            <span>Shopify envía automáticamente una notificación (webhook) a este sistema</span>
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">3</span>
                            <span>El sistema extrae los datos del pedido (productos, cliente, total)</span>
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">4</span>
                            <span>Se emite automáticamente la boleta electrónica en Lioren</span>
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">5</span>
                            <span>La boleta se guarda con su PDF y XML en este sistema</span>
                        </li>
                    </ol>
                    <div class="mt-4 p-3 bg-blue-100 rounded">
                        <strong>✨ Todo esto sucede automáticamente sin que tengas que hacer nada.</strong>
                    </div>
                </div>

            @else
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-6 py-4 rounded-lg mb-6">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <strong class="text-lg">⚠️ No hay integración configurada</strong>
                            <p class="text-sm">Debes configurar la integración primero</p>
                        </div>
                    </div>
                </div>

                <div class="text-center py-12">
                    <a href="{{ route('integracion.index') }}" class="inline-flex items-center px-6 py-3 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700">
                        🚀 Configurar Integración
                    </a>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
