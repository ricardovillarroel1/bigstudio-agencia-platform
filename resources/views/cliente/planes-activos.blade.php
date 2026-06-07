<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Planes Activos
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if($suscripcion)
                {{-- Contador de Documentos Emitidos --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 text-brand-700">Uso de Documentos del Ciclo</h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;" class="mb-4">
                            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 text-center border border-green-200">
                                <p class="text-sm text-green-700 font-medium">Boletas Emitidas</p>
                                <p class="text-3xl font-bold text-green-600 mt-1">{{ $documentosEmitidos['boletas'] ?? 0 }}</p>
                            </div>
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 text-center border border-blue-200">
                                <p class="text-sm text-blue-700 font-medium">Facturas Emitidas</p>
                                <p class="text-3xl font-bold text-blue-600 mt-1">{{ $documentosEmitidos['facturas'] ?? 0 }}</p>
                            </div>
                            <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4 text-center border border-red-200">
                                <p class="text-sm text-red-700 font-medium">Notas de Crédito</p>
                                <p class="text-3xl font-bold text-red-600 mt-1">{{ $documentosEmitidos['notas_credito'] ?? 0 }}</p>
                            </div>
                            <div class="bg-gradient-to-br from-brand-50 to-brand-100 rounded-lg p-4 text-center border border-brand-200">
                                <p class="text-sm text-brand-700 font-medium">Total del Mes</p>
                                <p class="text-3xl font-bold text-brand-600 mt-1">{{ $documentosEmitidos['total'] ?? 0 }}</p>
                            </div>
                        </div>

                        {{-- Barra de progreso de uso --}}
                        @php
                            $limiteDocumentos = $suscripcion->plan->monthly_order_limit ?? 0;
                            $totalEmitidos = $documentosEmitidos['total'] ?? 0;
                            $porcentajeUso = $limiteDocumentos > 0 ? min(100, round(($totalEmitidos / $limiteDocumentos) * 100)) : 0;
                            $documentosRestantes = $limiteDocumentos > 0 ? max(0, $limiteDocumentos - $totalEmitidos) : null;
                        @endphp

                        @if($limiteDocumentos > 0)
                            <div class="bg-gray-50 rounded-lg p-4 border">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium text-gray-700">
                                        Documentos utilizados: <strong>{{ $totalEmitidos }}</strong> de <strong>{{ $limiteDocumentos }}</strong>
                                    </span>
                                    <span class="text-sm font-semibold {{ $porcentajeUso >= 90 ? 'text-red-600' : ($porcentajeUso >= 70 ? 'text-yellow-600' : 'text-green-600') }}">
                                        {{ $porcentajeUso }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-4">
                                    <div class="h-4 rounded-full transition-all duration-500 {{ $porcentajeUso >= 90 ? 'bg-red-500' : ($porcentajeUso >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                                         style="width: {{ $porcentajeUso }}%"></div>
                                </div>
                                <div class="flex justify-between mt-2">
                                    <span class="text-xs text-gray-500">
                                        @if($documentosRestantes > 0)
                                            Te quedan <strong class="text-green-600">{{ $documentosRestantes }}</strong> documentos por emitir en este ciclo
                                        @else
                                            <span class="text-red-600 font-semibold">Has alcanzado el límite de documentos de tu plan</span>
                                        @endif
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        Ciclo: {{ $suscripcion->fecha_inicio->format('d/m/Y') }} - {{ $suscripcion->proximo_pago->format('d/m/Y') }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="bg-gradient-to-r from-brand-50 to-red-50 rounded-lg p-4 border border-brand-200">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-brand-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-sm font-semibold text-brand-700">Sin límite de documentos - Emisión ilimitada</span>
                                </div>
                                <p class="text-xs text-gray-600 mt-1 ml-7">Tu plan permite emitir documentos sin restricciones de cantidad por ciclo.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Suscripción Activa --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Suscripción Activa</h3>
                        
                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <p class="text-sm text-gray-600">Plan</p>
                                <p class="text-xl font-bold">{{ $suscripcion->plan->nombre ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Estado</p>
                                @if($suscripcion->estado === 'activa')
                                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">Activa</span>
                                @else
                                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">{{ ucfirst($suscripcion->estado) }}</span>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Fecha de Inicio</p>
                                <p class="text-lg font-semibold">{{ $suscripcion->fecha_inicio->format('d/m/Y') }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Próximo Pago</p>
                                @if($suscripcion->plan && $suscripcion->plan->precio == 0)
                                <p class="text-lg font-semibold text-teal-600">Indefinido</p>
                            @else
                                <p class="text-lg font-semibold">{{ $suscripcion->proximo_pago->format("d/m/Y") }}</p>
                            @endif
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Precio Mensual</p>
                                @if($suscripcion->plan && $suscripcion->plan->precio == 0)
                                    <p class="text-lg font-bold text-teal-600">GRATIS</p>
                                @else
                                    @php
                                        $precioUF = $suscripcion->plan->precio ?? 0;
                                        /* PARCHE_10_IVA_CLIENTE: total con IVA */ $precioCLP = round($precioUF * ($valorUF ?? 39841.72) * 1.19);
                                    @endphp
                                    <p class="text-2xl font-bold text-brand-700">${{ number_format($precioCLP, 0, ',', '.') }} <span class="text-sm font-normal text-gray-500">CLP / mes</span></p>
                                    <p class="text-xs text-gray-400 mt-1">{{ number_format($precioUF, 1, ',', '.') }} UF (UF = ${{ number_format($valorUF ?? 39841.72, 0, ',', '.') }})</p>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Días Restantes</p>
                                @if($suscripcion->plan && $suscripcion->plan->precio == 0)
                                    <p class="text-lg font-semibold text-teal-600">Sin vencimiento</p>
                                @else
                                    @php $dias = $suscripcion->diasRestantes(); @endphp
                                    <p class="text-lg font-semibold {{ $dias > 7 ? 'text-green-600' : 'text-orange-600' }}">{{ $dias }} días</p>
                                @endif
                            </div>
                        </div>

                        @if($suscripcion->estado === 'activa')
                            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                                {{-- Botón Renovar / Pagar Plan --}}
                                @if($suscripcion->plan && $suscripcion->plan->precio > 0)
                                <a href="{{ route('cliente.suscripciones.renovar', $suscripcion->id) }}" 
                                   style="display: inline-flex; align-items: center; gap: 0.5rem; background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); color: #000; font-weight: 700; padding: 0.75rem 1.5rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.9rem; text-transform: uppercase; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); transition: all 0.3s;"
                                   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px -4px rgba(248, 184, 0, 0.5)'"
                                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.1)'">
                                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                    Renovar Plan
                                </a>

                                {{-- Botón Plan Anual si está disponible --}}
                                @if($suscripcion->plan->plan_anual_activo && $suscripcion->plan->precio_anual > 0)
                                    @php
                                        $precioAnualUF = $suscripcion->plan->precio_anual;
                                        $precioAnualCLP = round($precioAnualUF * ($valorUF ?? 39841.72) * 1.19);
                                        $descuento = $suscripcion->plan->descuento_anual ?? 0;
                                    @endphp
                                    <a href="{{ route('cliente.suscripciones.renovar', $suscripcion->id) }}?tipo=anual" 
                                       style="display: inline-flex; align-items: center; gap: 0.5rem; background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: #fff; font-weight: 700; padding: 0.75rem 1.5rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.9rem; text-transform: uppercase; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); transition: all 0.3s;"
                                       onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px -4px rgba(16, 185, 129, 0.5)'"
                                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.1)'">
                                        <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        Plan Anual @if($descuento > 0)({{ $descuento }}% desc.)@endif
                                    </a>
                                    <span style="font-size: 0.8rem; color: #059669; font-weight: 600;">
                                        ${{ number_format($precioAnualCLP, 0, ',', '.') }} CLP/año
                                    </span>
                                @endif
                                @endif

                                {{-- Botón Cancelar Suscripción --}}
                                <form action="{{ route('cliente.suscripciones.cancelar', $suscripcion->id) }}" method="POST"
                                      onsubmit="return confirm('¿Estás seguro de cancelar tu suscripción? Perderás acceso a la integración al finalizar el período actual.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-6 rounded">
                                        Cancelar Suscripción
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Historial de Pagos --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Historial de Pagos</h3>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($pagos as $pago)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                {{ $pago->created_at->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                {{ $pago->plan->nombre ?? $pago->concepto ?? 'Suscripción' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @if($pago->periodo_inicio && $pago->periodo_fin)
                                                    {{ \Carbon\Carbon::parse($pago->periodo_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($pago->periodo_fin)->format('d/m/Y') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold">
                                                {{ number_format($pago->monto ?? $pago->amount ?? 0, 0, ',', '.') }} CLP
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if(in_array($pago->status ?? $pago->estado, ['pagado', 'pagada', 'completed', 2]))
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Pagado</span>
                                                @else
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ ucfirst($pago->status ?? $pago->estado ?? 'Pendiente') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                                No hay pagos registrados.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">No tienes una suscripción activa</h3>
                        <p class="text-gray-500 mb-6">Contrata un plan para comenzar a usar la integración Shopify - Lioren.</p>
                        <a href="{{ route('cliente.planes') }}" class="bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 px-6 rounded-lg">
                            Ver Planes Disponibles
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
