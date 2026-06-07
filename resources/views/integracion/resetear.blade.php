<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            🔄 Resetear Integración
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    
                    <div class="mb-6">
                        <a href="{{ route('integracion.dashboard') }}" class="text-brand-600 hover:text-brand-900">
                            ← Volver al Dashboard
                        </a>
                    </div>

                    <h1 class="text-3xl font-bold text-gray-800 mb-2">🔄 Resetear Integración</h1>
                    <p class="text-gray-600 mb-6">Elimina la configuración actual para empezar desde cero</p>

                    @if(!$config)
                        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <span class="text-2xl">⚠️</span>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        <strong>No hay integración activa.</strong> No hay nada que resetear.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('integracion.index') }}" class="inline-flex items-center px-4 py-2 bg-brand-600 border border-transparent rounded-md font-semibold text-white hover:bg-brand-700">
                            Configurar Nueva Integración
                        </a>
                    @else
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <span class="text-2xl">⚠️</span>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">
                                        <strong>¡Atención!</strong> Esta acción eliminará toda la configuración de integración y no se puede deshacer.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-6 mb-6">
                            <h3 class="font-bold text-gray-800 mb-4">📊 Configuración Actual:</h3>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-gray-600">Tienda Shopify:</span>
                                    <span class="font-semibold text-gray-800">{{ $config->shopify_tienda }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-gray-600">Facturación:</span>
                                    <span class="font-semibold {{ $config->facturacion_enabled ? 'text-green-600' : 'text-gray-600' }}">
                                        {{ $config->facturacion_enabled ? '✅ Habilitada' : '❌ Deshabilitada' }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-gray-600">Productos sincronizados:</span>
                                    <span class="font-semibold text-gray-800">{{ $productosCount }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-gray-600">Facturas emitidas:</span>
                                    <span class="font-semibold text-gray-800">{{ $facturasCount }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-gray-600">Última sincronización:</span>
                                    <span class="font-semibold text-gray-800">
                                        {{ $config->ultima_sincronizacion ? $config->ultima_sincronizacion->format('d/m/Y H:i') : 'Nunca' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('integracion.resetear.ejecutar') }}" method="POST" onsubmit="return confirm('⚠️ ¿Estás seguro? Esta acción NO se puede deshacer.');">
                            @csrf
                            @method('DELETE')

                            <div class="bg-white border-2 border-gray-200 rounded-lg p-6 mb-6">
                                <h3 class="font-bold text-gray-800 mb-4">🗑️ ¿Qué deseas eliminar?</h3>
                                
                                <div class="space-y-3">
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input 
                                                id="eliminar_webhooks" 
                                                name="eliminar_webhooks" 
                                                type="checkbox" 
                                                checked
                                                disabled
                                                class="w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500"
                                            >
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="eliminar_webhooks" class="font-medium text-gray-900">
                                                Webhooks de Shopify (obligatorio)
                                            </label>
                                            <p class="text-gray-500">Se eliminarán todos los webhooks creados en Shopify</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input 
                                                id="eliminar_productos" 
                                                name="eliminar_productos" 
                                                type="checkbox" 
                                                value="1"
                                                class="w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500"
                                            >
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="eliminar_productos" class="font-medium text-gray-900">
                                                Productos sincronizados ({{ $productosCount }})
                                            </label>
                                            <p class="text-gray-500">Eliminar el historial de productos sincronizados</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input 
                                                id="eliminar_facturas" 
                                                name="eliminar_facturas" 
                                                type="checkbox" 
                                                value="1"
                                                class="w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500"
                                            >
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="eliminar_facturas" class="font-medium text-gray-900">
                                                Facturas emitidas ({{ $facturasCount }})
                                            </label>
                                            <p class="text-gray-500">Eliminar el historial de facturas/boletas emitidas</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-red-600 border border-transparent rounded-md font-semibold text-white hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    🗑️ Resetear Integración
                                </button>
                                <a href="{{ route('integracion.dashboard') }}" class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-gray-600 border border-transparent rounded-md font-semibold text-white hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Cancelar
                                </a>
                            </div>
                        </form>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
