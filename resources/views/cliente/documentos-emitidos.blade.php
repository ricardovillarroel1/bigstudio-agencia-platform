<x-app-layout>
    @include('partials.acciones-rapidas')
    <x-slot name="header">
        <h2 class="font-display text-xl font-bold text-gray-800 leading-tight">
            <span class="text-brand-600">Boletas y Facturas</span> a Clientes
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- HEADER EXPLICATIVO BigStudio --}}
            <div class="rounded-2xl p-6 flex items-start gap-4"
                 style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%); border: 1px solid #FFD89C;">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background: linear-gradient(135deg, #FFC800 0%, #FF8100 100%); box-shadow: 0 4px 12px rgba(255, 129, 0, 0.3);">
                    <i class="fas fa-file-alt text-2xl text-white"></i>
                </div>
                <div class="flex-1">
                    <h3 class="bs-display text-xl m-0" style="color: #8A4400;">DTEs que has emitido a tus clientes</h3>
                    <p class="text-sm mt-1.5 mb-0" style="color: #5C2D00; line-height: 1.5;">
                        Aqu&iacute; ves <strong>todos los documentos tributarios (boletas, facturas y notas de cr&eacute;dito)</strong>
                        que t&uacute; emites a tus clientes finales a trav&eacute;s de la integraci&oacute;n con Shopify.
                        El consumo cuenta para el l&iacute;mite de tu plan Big Studio.
                    </p>
                </div>
            </div>

            <!-- Uso del Ciclo Actual - Look BigStudio (similar al admin) -->
            @if(isset($cicloInfo))
            @php
                $cInicio  = \Carbon\Carbon::parse($cicloInfo['inicio']);
                $cFin     = \Carbon\Carbon::parse($cicloInfo['fin']);
                $cDias    = $cInicio->diffInDays($cFin) + 1;
                $cDiaAct  = max(1, min($cDias, $cInicio->diffInDays(now()) + 1));
                $cPctTime = $cDias > 0 ? round(($cDiaAct / $cDias) * 100) : 0;
                $cPctDocs = (int) ($cicloInfo['porcentaje'] ?? 0);
                $cColor   = $cPctDocs >= 90 ? '#DC2626' : ($cPctDocs >= 75 ? '#FF8100' : '#059669');
            @endphp
            <div class="bs-card bs-card-body">
                {{-- Header con plan + rango fechas --}}
                <div class="flex flex-wrap justify-between items-start gap-2 mb-4">
                    <div>
                        <h3 class="bs-display text-lg text-gray-800 m-0">Uso del ciclo actual</h3>
                        @if($cicloInfo['plan'])
                            <span class="bs-badge-brand mt-1 inline-flex"><i class="fas fa-crown text-[0.6rem]"></i> {{ $cicloInfo['plan'] }}</span>
                        @endif
                    </div>
                    <span class="text-xs text-gray-500 font-medium">
                        {{ $cInicio->format('d/m/Y') }} &mdash; {{ $cFin->format('d/m/Y') }}
                    </span>
                </div>

                @if($cicloInfo['limite'])
                    {{-- Numero grande + porcentaje --}}
                    <div class="flex justify-between items-end gap-4 flex-wrap">
                        <div class="flex-1 min-w-[200px]">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide m-0">Documentos emitidos</p>
                            <p class="bs-display text-4xl text-gray-900 mt-1 mb-0 leading-none">
                                {{ number_format($cicloInfo['emitidos'], 0, ',', '.') }}<span class="text-gray-400 text-2xl font-medium"> / {{ number_format($cicloInfo['limite'], 0, ',', '.') }}</span>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide m-0">Uso del plan</p>
                            <p class="bs-display text-3xl mt-1 mb-0 leading-none" style="color: {{ $cColor }};">
                                {{ $cPctDocs }}<span class="text-base">%</span>
                            </p>
                        </div>
                    </div>

                    {{-- Barra de uso del plan (gradiente BigStudio) --}}
                    <div class="bs-progress mt-3"><div class="bs-progress-fill" style="width: {{ min(100, $cPctDocs) }}%;"></div></div>

                    {{-- Texto contextual --}}
                    <p class="text-xs text-gray-500 mt-2 mb-0">
                        @if(($cicloInfo['disponibles'] ?? 0) > 0)
                            Te quedan <strong class="text-emerald-600">{{ number_format($cicloInfo['disponibles'], 0, ',', '.') }}</strong> documentos disponibles en este ciclo
                        @else
                            <strong class="text-red-600">&#9888; Has alcanzado el l&iacute;mite de documentos de tu plan.</strong> Los documentos extra se cobrar&aacute;n en tu pr&oacute;xima factura.
                        @endif
                    </p>

                    {{-- Barra de tiempo del ciclo (sutil) --}}
                    <div class="mt-5 pt-4 border-t border-dashed border-gray-200">
                        <div class="flex justify-between text-xs text-gray-500 mb-1.5">
                            <span class="font-semibold uppercase tracking-wide">Tiempo del ciclo</span>
                            <span><strong class="text-gray-700">D&iacute;a {{ $cDiaAct }}</strong> de {{ $cDias }} &middot; {{ $cPctTime }}%</span>
                        </div>
                        <div class="bs-progress bs-progress-thin"><div class="bs-progress-fill" style="width: {{ $cPctTime }}%;"></div></div>
                    </div>
                @else
                    <div class="flex items-center gap-2 text-sm text-brand-700">
                        <i class="fas fa-infinity"></i>
                        <span class="font-semibold">Emisi&oacute;n ilimitada &mdash; {{ number_format($cicloInfo['emitidos'], 0, ',', '.') }} documentos emitidos en este ciclo</span>
                    </div>
                @endif
            </div>
            @endif

            <!-- Estadísticas del listado -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total Documentos</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Boletas</p>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['boletas'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-brand-500">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Facturas</p>
                    <p class="text-2xl font-bold text-brand-600">{{ $stats['facturas'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Notas de Crédito</p>
                    <p class="text-2xl font-bold text-red-600">{{ $stats['notas_credito'] }}</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="p-4 border-b border-gray-200">
                    <form method="GET" action="{{ route('cliente.documentos-emitidos') }}" class="flex flex-wrap gap-4 items-end">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Tipo de Documento</label>
                            <select name="tipo" class="rounded-md border-gray-300 text-sm focus:ring-brand-500 focus:border-brand-500">
                                <option value="">Todos</option>
                                <option value="boleta" {{ request('tipo') == 'boleta' ? 'selected' : '' }}>Boletas</option>
                                <option value="factura" {{ request('tipo') == 'factura' ? 'selected' : '' }}>Facturas</option>
                                <option value="nota_credito" {{ request('tipo') == 'nota_credito' ? 'selected' : '' }}>Notas de Crédito</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Mes</label>
                            <select name="mes" class="rounded-md border-gray-300 text-sm focus:ring-brand-500 focus:border-brand-500">
                                <option value="">Todos</option>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ request('mes') == $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Año</label>
                            <select name="anio" class="rounded-md border-gray-300 text-sm focus:ring-brand-500 focus:border-brand-500">
                                @for($y = now()->year; $y >= now()->year - 2; $y--)
                                    <option value="{{ $y }}" {{ (request('anio', now()->year) == $y) ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Buscar</label>
                            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Folio, RUT, receptor..." class="rounded-md border-gray-300 text-sm focus:ring-brand-500 focus:border-brand-500 w-48">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-md text-sm hover:bg-brand-700 transition">
                                Filtrar
                            </button>
                            <a href="{{ route('cliente.documentos-emitidos') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm hover:bg-gray-300 transition">
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de documentos -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Historial de Documentos Tributarios
                    </h3>
                    <span class="text-sm text-gray-500">{{ $documentos->count() }} documentos encontrados</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Folio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receptor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pedido Shopify</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto Total</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($documentos as $doc)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($doc->tipodoc == 39)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                Boleta
                                            </span>
                                        @elseif($doc->tipodoc == 33)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-100 text-brand-800">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                Factura
                                            </span>
                                        @elseif($doc->tipodoc == 61)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>
                                                Nota de Crédito
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                Tipo {{ $doc->tipodoc }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-semibold text-gray-900">#{{ $doc->folio ?? 'N/A' }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ \Carbon\Carbon::parse($doc->created_at)->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $doc->receptor_nombre ?? '-' }}</div>
                                        @if($doc->receptor_rut)
                                            <div class="text-xs text-gray-500">{{ $doc->receptor_rut }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        @if($doc->shopify_order_number)
                                            #{{ $doc->shopify_order_number }}
                                        @elseif($doc->shopify_order_id)
                                            <span class="text-xs text-gray-400">ID: {{ $doc->shopify_order_id }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span class="text-sm font-semibold text-gray-900">${{ number_format($doc->monto_total ?? 0, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if(in_array($doc->status, ['emitida', 'enviada', 'aceptada']))
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                {{ ucfirst($doc->status) }}
                                            </span>
                                        @elseif($doc->status == 'error')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                Error
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                {{ ucfirst($doc->status ?? 'N/A') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex justify-center gap-2">
                                            @if($doc->source == 'boleta')
                                                <a href="{{ route('cliente.documento.pdf', ['tipo' => 'boleta', 'id' => $doc->id]) }}" target="_blank" class="inline-flex items-center px-2 py-1 bg-red-50 text-red-700 rounded text-xs font-medium hover:bg-red-100 transition" title="Descargar PDF">
                                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                    PDF
                                                </a>
                                                <a href="{{ route('cliente.documento.xml', ['tipo' => 'boleta', 'id' => $doc->id]) }}" target="_blank" class="inline-flex items-center px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-medium hover:bg-blue-100 transition" title="Descargar XML">
                                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                                                    XML
                                                </a>
                                            @elseif($doc->source == 'factura')
                                                <a href="{{ route('cliente.documento.pdf', ['tipo' => 'factura', 'id' => $doc->id]) }}" target="_blank" class="inline-flex items-center px-2 py-1 bg-red-50 text-red-700 rounded text-xs font-medium hover:bg-red-100 transition" title="Descargar PDF">
                                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                    PDF
                                                </a>
                                                <a href="{{ route('cliente.documento.xml', ['tipo' => 'factura', 'id' => $doc->id]) }}" target="_blank" class="inline-flex items-center px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-medium hover:bg-blue-100 transition" title="Descargar XML">
                                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                                                    XML
                                                </a>
                                            @elseif($doc->source == 'nota_credito')
                                                <a href="{{ route('cliente.documento.pdf', ['tipo' => 'nota_credito', 'id' => $doc->id]) }}" target="_blank" class="inline-flex items-center px-2 py-1 bg-red-50 text-red-700 rounded text-xs font-medium hover:bg-red-100 transition" title="Descargar PDF">
                                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                    PDF
                                                </a>
                                                <a href="{{ route('cliente.documento.xml', ['tipo' => 'nota_credito', 'id' => $doc->id]) }}" target="_blank" class="inline-flex items-center px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-medium hover:bg-blue-100 transition" title="Descargar XML">
                                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                                                    XML
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p class="text-lg font-medium">No hay documentos emitidos</p>
                                        <p class="text-sm mt-1">Los documentos tributarios se generarán automáticamente cuando se procesen pedidos en tu tienda Shopify.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
