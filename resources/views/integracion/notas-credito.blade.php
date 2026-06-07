<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Notas de Crédito Emitidas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    
                    <div class="mb-6 flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800 mb-2">🔄 Notas de Crédito</h1>
                            <p class="text-gray-600">Listado de todas las notas de crédito emitidas automáticamente</p>
                        </div>
                        <a href="{{ route('integracion.dashboard') }}" class="text-brand-600 hover:text-brand-900">
                            ← Volver al Dashboard
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                            <p>{{ session('success') }}</p>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                            <p>{{ session('error') }}</p>
                        </div>
                    @endif

                    @if($notasCredito->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Folio NC
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Doc. Original
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Pedido Shopify
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Receptor
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Monto Total
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Estado
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Fecha
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($notasCredito as $nc)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    #{{ $nc->folio ?? 'N/A' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    {{ $nc->tipo_documento_original == '33' ? 'Factura' : 'Boleta' }} #{{ $nc->folio_original }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    #{{ $nc->shopify_order_number }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900">{{ $nc->razon_social ?? 'N/A' }}</div>
                                                <div class="text-sm text-gray-500">{{ $nc->rut_receptor ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-semibold text-gray-900">
                                                    ${{ number_format($nc->monto_total, 0, ',', '.') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($nc->status === 'emitida')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        ✅ Emitida
                                                    </span>
                                                @elseif($nc->status === 'error')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        ❌ Error
                                                    </span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        ⏳ Pendiente
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $nc->emitida_at ? $nc->emitida_at->format('d/m/Y H:i') : $nc->created_at->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                @if($nc->status === 'emitida')
                                                    <div class="flex space-x-2">
                                                        @if($nc->pdf_path || $nc->pdf_base64)
                                                            <a href="{{ route('notas-credito.pdf', $nc->id) }}" 
                                                               target="_blank"
                                                               class="text-red-600 hover:text-red-900">
                                                                📄 PDF
                                                            </a>
                                                        @endif
                                                        @if($nc->xml_base64)
                                                            <a href="{{ route('notas-credito.xml', $nc->id) }}" 
                                                               class="text-blue-600 hover:text-blue-900">
                                                                📋 XML
                                                            </a>
                                                        @endif
                                                    </div>
                                                @elseif($nc->status === 'error')
                                                    <button 
                                                        onclick="alert('Error: {{ addslashes($nc->error_message) }}')"
                                                        class="text-red-600 hover:text-red-900">
                                                        Ver Error
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6">
                            {{ $notasCredito->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">📭</div>
                            <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay notas de crédito emitidas</h3>
                            <p class="text-gray-500">
                                Las notas de crédito se emitirán automáticamente cuando se cancelen o reembolsen pedidos en Shopify.
                            </p>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
