<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Mis Facturas
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Resumen -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-600">Total Facturas</p>
                    <p class="text-3xl font-bold text-brand-600">{{ $facturas->total() }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-600">Pagadas</p>
                    <p class="text-3xl font-bold text-green-600">{{ $facturas->where('estado', 'pagada')->count() + $facturas->where('estado', 'pagado')->count() }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-600">Pendientes</p>
                    <p class="text-3xl font-bold text-yellow-600">{{ $facturas->where('estado', 'pendiente')->count() }}</p>
                </div>
            </div>

            <!-- Tabla de Facturas del Servicio -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-2">Facturas del Servicio</h3>
                    <p class="text-sm text-gray-500 mb-4">Facturas emitidas por BigStudio por el uso de la plataforma de integración.</p>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° Factura</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($facturas as $factura)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono font-semibold">
                                            {{ $factura->numero_factura ?? 'F-' . str_pad($factura->id, 6, '0', STR_PAD_LEFT) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            {{ $factura->created_at->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            {{ $factura->plan->nombre ?? $factura->concepto ?? 'Suscripción' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($factura->periodo_inicio && $factura->periodo_fin)
                                                {{ \Carbon\Carbon::parse($factura->periodo_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($factura->periodo_fin)->format('d/m/Y') }}
                                            @else
                                                {{ $factura->created_at->format('M Y') }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                                            ${{ number_format($factura->total_clp ?? 0, 0, ',', '.') }} CLP
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if(in_array($factura->estado, ['pagada', 'pagado']))
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                    Pagada
                                                </span>
                                            @elseif($factura->estado === 'pendiente')
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    Pendiente
                                                </span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    {{ ucfirst($factura->estado) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($factura->pdf_base64)
                                                <a href="{{ route('factura-servicio.pdf', $factura->id) }}" class="text-brand-600 hover:text-brand-900 font-medium">
                                                    <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                    Descargar PDF
                                                </a>
                                                @if($factura->folio)
                                                    <br><span class="text-xs text-gray-400">Folio: {{ $factura->folio }}</span>
                                                @endif
                                            @elseif($factura->monto == 0)
                                                <span class="text-gray-400 text-xs">Plan Gratis</span>
                                            @else
                                                <span class="text-yellow-500 text-xs"><i class="fas fa-clock mr-1"></i>Pendiente</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <p class="text-lg font-medium">No hay facturas aún</p>
                                            <p class="text-sm mt-1">Las facturas se generarán automáticamente cuando realices pagos del servicio.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if(method_exists($facturas, 'links'))
                        <div class="mt-4">
                            {{ $facturas->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
