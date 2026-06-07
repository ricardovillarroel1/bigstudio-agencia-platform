<x-app-layout>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                📦 Productos en Lioren (API)
            </h2>
            <a href="{{ route('integracion.dashboard') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                ← Volver al Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if($error)
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <strong>Error:</strong> {{ $error }}
                </div>
            @else
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <strong>✅ Conexión exitosa con Lioren</strong><br>
                    Total de productos encontrados: <strong>{{ $total ?? 0 }}</strong>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    
                    @if(count($productos) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código/SKU</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Venta</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Compra</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unidad</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($productos as $producto)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $producto['id'] ?? $producto['idproducto'] ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                <strong>{{ $producto['nombre'] ?? 'Sin nombre' }}</strong>
                                                @if(isset($producto['descripcion']) && $producto['descripcion'])
                                                    <br><span class="text-gray-500 text-xs">{{ Str::limit($producto['descripcion'], 50) }}</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <code class="bg-gray-100 px-2 py-1 rounded">{{ $producto['codigo'] ?? 'N/A' }}</code>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ${{ number_format($producto['precioventabruto'] ?? 0, 0, ',', '.') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                ${{ number_format($producto['preciocompraneto'] ?? 0, 0, ',', '.') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                @if(isset($producto['stock']))
                                                    <span class="px-2 py-1 rounded {{ $producto['stock'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                        {{ $producto['stock'] }}
                                                    </span>
                                                @else
                                                    <span class="text-gray-400">N/A</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $producto['unidad'] ?? 'Unidad' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 p-4 bg-blue-50 rounded">
                            <h3 class="font-bold text-blue-900 mb-2">🎉 ¡Estos son los productos reales en tu cuenta de Lioren!</h3>
                            <p class="text-blue-800 text-sm">
                                Esta información se obtiene directamente desde la API de Lioren en tiempo real.
                                Si ves productos aquí, significa que la sincronización funcionó correctamente.
                            </p>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay productos</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                No se encontraron productos en Lioren o aún no se ha ejecutado la sincronización.
                            </p>
                            <div class="mt-6">
                                <a href="{{ route('integracion.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700">
                                    Configurar Integración
                                </a>
                            </div>
                        </div>
                    @endif

                </div>
            </div>

            <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded p-4">
                <h3 class="font-bold text-yellow-900 mb-2">💡 Nota:</h3>
                <p class="text-yellow-800 text-sm">
                    Esta vista consulta directamente la API de Lioren. Si no ves productos aquí pero sí en la vista de "Productos Sincronizados",
                    significa que hubo un error al crear los productos en Lioren (aunque se guardaron en tu base de datos local).
                </p>
            </div>

        </div>
    </div>
</x-app-layout>
