<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Boletas y Facturas Emitidas
        </h2>
    </x-slot>

    <div class="py-6">
        <div style="max-width: 100%; margin: 0 auto; padding: 0 1.5rem;">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Estadisticas -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-600">Total Documentos</p>
                    <p class="text-3xl font-bold text-brand-600">{{ $estadisticas['total'] }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-600">Boletas</p>
                    <p class="text-3xl font-bold text-green-600">{{ $estadisticas['boletas'] }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-600">Facturas</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $estadisticas['facturas'] }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-600">Notas de Credito</p>
                    <p class="text-3xl font-bold text-red-600">{{ $estadisticas['notas_credito'] }}</p>
                </div>
            </div>

            <!-- Resumen por Cliente -->
            @if(isset($clienteStats) && count($clienteStats) > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Documentos por Cliente (Ciclo Actual)</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan Activo</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Emitidos (Ciclo)</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Total Historico</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Límite Ciclo</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Disponibles</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Uso</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($clienteStats as $cs)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div style="width: 2rem; height: 2rem; background: linear-gradient(135deg, #FFC800, #FF9C00); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 0.75rem; flex-shrink: 0;">
                                                <span style="color: #000; font-weight: 700; font-size: 0.8rem;">{{ substr($cs['name'], 0, 1) }}</span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $cs['name'] }}</p>
                                                <p class="text-xs text-gray-500">{{ $cs['email'] }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                                        @if($cs['plan'] !== 'Sin plan')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-brand-100 text-brand-800">{{ $cs['plan'] }}</span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">Sin plan</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="text-lg font-bold text-brand-600">{{ $cs['docs_emitidos'] }}</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="text-sm font-semibold text-gray-700">{{ $cs['docs_total'] }}</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($cs['limite'])
                                            <span class="text-sm font-semibold text-gray-700">{{ number_format($cs['limite'], 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-sm text-green-600 font-semibold">Ilimitado</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($cs['limite'])
                                            @if($cs['disponibles'] > 0)
                                                <span class="text-sm font-bold text-green-600">{{ number_format($cs['disponibles'], 0, ',', '.') }}</span>
                                            @else
                                                <span class="text-sm font-bold text-red-600">0</span>
                                            @endif
                                        @else
                                            <span class="text-sm text-green-600">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($cs['limite'])
                                            @php
                                                $porcentaje = round(($cs['docs_emitidos'] / $cs['limite']) * 100);
                                                $barColor = $porcentaje >= 90 ? '#EF4444' : ($porcentaje >= 70 ? '#F59E0B' : '#10B981');
                                            @endphp
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <div style="flex: 1; background: #E5E7EB; border-radius: 9999px; height: 0.5rem; min-width: 60px;">
                                                    <div style="background: {{ $barColor }}; border-radius: 9999px; height: 100%; width: {{ min($porcentaje, 100) }}%;"></div>
                                                </div>
                                                <span class="text-xs font-semibold" style="color: {{ $barColor }};">{{ $porcentaje }}%</span>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Filtros -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-4">
                    <form method="GET" action="{{ url()->current() }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                            <select name="user_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500">
                                <option value="">Todos los clientes</option>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" {{ request('user_id') == $cliente->id ? 'selected' : '' }}>
                                        {{ $cliente->name }} ({{ $cliente->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                            <select name="tipo" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500">
                                <option value="">Todos</option>
                                <option value="boleta" {{ request('tipo') == 'boleta' ? 'selected' : '' }}>Boletas</option>
                                <option value="factura" {{ request('tipo') == 'factura' ? 'selected' : '' }}>Facturas</option>
                                <option value="nota_credito" {{ request('tipo') == 'nota_credito' ? 'selected' : '' }}>Notas de Credito</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                            <input type="month" name="mes" value="{{ request('mes', date('Y-m')) }}"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500">
                        </div>
                        <div>
                            <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-2 px-4 rounded">
                                Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de Documentos - FULL WIDTH -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4">
                    <h3 class="text-lg font-semibold mb-4">Documentos Emitidos</h3>
                    
                    <div style="overflow-x: auto; width: 100%;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem;">
                            <thead style="background: #f9fafb;">
                                <tr>
                                    <th style="padding: 0.6rem 0.5rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: #6b7280; text-transform: uppercase; white-space: nowrap;">Fecha</th>
                                    <th style="padding: 0.6rem 0.5rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: #6b7280; text-transform: uppercase; white-space: nowrap;">Cliente</th>
                                    <th style="padding: 0.6rem 0.5rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: #6b7280; text-transform: uppercase; white-space: nowrap;">Tienda</th>
                                    <th style="padding: 0.6rem 0.5rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: #6b7280; text-transform: uppercase; white-space: nowrap;">Tipo</th>
                                    <th style="padding: 0.6rem 0.5rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: #6b7280; text-transform: uppercase; white-space: nowrap;">N Doc.</th>
                                    <th style="padding: 0.6rem 0.5rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: #6b7280; text-transform: uppercase; white-space: nowrap;">Receptor</th>
                                    <th style="padding: 0.6rem 0.5rem; text-align: left; font-size: 0.7rem; font-weight: 600; color: #6b7280; text-transform: uppercase; white-space: nowrap;">Pedido</th>
                                    <th style="padding: 0.6rem 0.5rem; text-align: right; font-size: 0.7rem; font-weight: 600; color: #6b7280; text-transform: uppercase; white-space: nowrap;">Monto</th>
                                    <th style="padding: 0.6rem 0.5rem; text-align: center; font-size: 0.7rem; font-weight: 600; color: #6b7280; text-transform: uppercase; white-space: nowrap;">Estado</th>
                                    <th style="padding: 0.6rem 0.5rem; text-align: center; font-size: 0.7rem; font-weight: 600; color: #6b7280; text-transform: uppercase; white-space: nowrap;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($documentos as $doc)
                                    <tr style="border-bottom: 1px solid #f3f4f6;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                                        <td style="padding: 0.5rem; white-space: nowrap;">
                                            {{ \Carbon\Carbon::parse($doc->created_at)->format('d/m/Y H:i') }}
                                        </td>
                                        <td style="padding: 0.5rem; white-space: nowrap;">
                                            <span style="font-weight: 500;">{{ optional($doc->user)->name ?? 'N/A' }}</span>
                                        </td>
                                        <td style="padding: 0.5rem; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;">
                                            @php
                                                $tienda = optional(optional($doc->user)->integracionConfig)->shopify_tienda ?? '';
                                                $tiendaCorta = $tienda ? str_replace('.myshopify.com', '', $tienda) : 'N/A';
                                            @endphp
                                            {{ $tiendaCorta }}
                                        </td>
                                        <td style="padding: 0.5rem; white-space: nowrap;">
                                            @if($doc->source === 'nota_credito' || $doc->tipodoc == '61')
                                                <span style="padding: 0.15rem 0.5rem; font-size: 0.7rem; font-weight: 600; border-radius: 9999px; background: #fee2e2; color: #991b1b;">NC</span>
                                            @elseif($doc->source === 'factura_emitida')
                                                @if($doc->tipodoc == '33')
                                                    <span style="padding: 0.15rem 0.5rem; font-size: 0.7rem; font-weight: 600; border-radius: 9999px; background: #dbeafe; color: #1e40af;">Factura</span>
                                                @else
                                                    <span style="padding: 0.15rem 0.5rem; font-size: 0.7rem; font-weight: 600; border-radius: 9999px; background: #f3e8ff; color: #6b21a8;">DTE #{{ $doc->tipodoc }}</span>
                                                @endif
                                            @else
                                                @if($doc->tipodoc == '39')
                                                    <span style="padding: 0.15rem 0.5rem; font-size: 0.7rem; font-weight: 600; border-radius: 9999px; background: #dcfce7; color: #166534;">Boleta</span>
                                                @elseif(in_array($doc->tipodoc, ['33', '34']))
                                                    <span style="padding: 0.15rem 0.5rem; font-size: 0.7rem; font-weight: 600; border-radius: 9999px; background: #dbeafe; color: #1e40af;">Factura</span>
                                                @else
                                                    <span style="padding: 0.15rem 0.5rem; font-size: 0.7rem; font-weight: 600; border-radius: 9999px; background: #f3f4f6; color: #374151;">DTE #{{ $doc->tipodoc }}</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td style="padding: 0.5rem; white-space: nowrap; font-family: monospace; font-weight: 600;">
                                            {{ $doc->folio ?? '-' }}
                                        </td>
                                        <td style="padding: 0.5rem; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;">
                                            @if($doc->source === 'nota_credito')
                                                <span style="font-weight: 500;">{{ $doc->razon_social ?? $doc->receptor_nombre ?? '-' }}</span>
                                                @if($doc->rut_receptor ?? $doc->receptor_rut)
                                                    <br><span style="font-size: 0.7rem; color: #6b7280;">{{ $doc->rut_receptor ?? $doc->receptor_rut }}</span>
                                                @endif
                                            @elseif($doc->source === 'factura_emitida')
                                                <span style="font-weight: 500;">{{ $doc->razon_social ?? '-' }}</span>
                                                @if($doc->rut_receptor)
                                                    <br><span style="font-size: 0.7rem; color: #6b7280;">{{ $doc->rut_receptor }}</span>
                                                @endif
                                            @else
                                                <span style="font-weight: 500;">{{ $doc->receptor_nombre ?? '-' }}</span>
                                                @if($doc->receptor_rut)
                                                    <br><span style="font-size: 0.7rem; color: #6b7280;">{{ $doc->receptor_rut }}</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td style="padding: 0.5rem; white-space: nowrap;">
                                            @if($doc->shopify_order_number)
                                                #{{ $doc->shopify_order_number }}
                                            @elseif($doc->observaciones && preg_match('/#(\d+)/', $doc->observaciones, $m))
                                                #{{ $m[1] }}
                                            @else
                                                #{{ $doc->shopify_order_id ?? '-' }}
                                            @endif
                                        </td>
                                        <td style="padding: 0.5rem; white-space: nowrap; text-align: right; font-weight: 600;">
                                            ${{ number_format($doc->monto_total ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td style="padding: 0.5rem; white-space: nowrap; text-align: center;">
                                            @if(in_array($doc->status, ['emitida', 'emitido', 'exitoso']))
                                                <span style="padding: 0.15rem 0.5rem; font-size: 0.7rem; font-weight: 600; border-radius: 9999px; background: #dcfce7; color: #166534;">Emitida</span>
                                            @elseif($doc->status === 'pendiente')
                                                <span style="padding: 0.15rem 0.5rem; font-size: 0.7rem; font-weight: 600; border-radius: 9999px; background: #fef3c7; color: #92400e;">Pendiente</span>
                                            @elseif($doc->status === 'error')
                                                <span style="padding: 0.15rem 0.5rem; font-size: 0.7rem; font-weight: 600; border-radius: 9999px; background: #fee2e2; color: #991b1b;">Error</span>
                                            @else
                                                <span style="padding: 0.15rem 0.5rem; font-size: 0.7rem; font-weight: 600; border-radius: 9999px; background: #f3f4f6; color: #374151;">{{ ucfirst($doc->status ?? 'N/A') }}</span>
                                            @endif
                                        </td>
                                        <td style="padding: 0.5rem; white-space: nowrap; text-align: center;">
                                            <div style="display: flex; align-items: center; justify-content: center; gap: 0.25rem;">
                                                @if($doc->source === 'nota_credito')
                                                    <a href="{{ route('notas-credito.pdf', $doc->id) }}" target="_blank"
                                                       style="display: inline-flex; align-items: center; padding: 0.2rem 0.5rem; font-size: 0.7rem; font-weight: 500; border-radius: 0.25rem; background: #fef2f2; color: #b91c1c; text-decoration: none;"
                                                       title="Ver PDF">
                                                        <i class="fas fa-file-pdf" style="margin-right: 0.2rem; font-size: 0.7rem;"></i> PDF
                                                    </a>
                                                    <a href="{{ route('notas-credito.xml', $doc->id) }}" target="_blank"
                                                       style="display: inline-flex; align-items: center; padding: 0.2rem 0.5rem; font-size: 0.7rem; font-weight: 500; border-radius: 0.25rem; background: #f0fdf4; color: #15803d; text-decoration: none;"
                                                       title="Descargar XML">
                                                        <i class="fas fa-code" style="margin-right: 0.2rem; font-size: 0.7rem;"></i> XML
                                                    </a>
                                                @elseif($doc->source === 'factura_emitida')
                                                    <a href="{{ route('admin.facturas.pdf', $doc->id) }}" target="_blank"
                                                       style="display: inline-flex; align-items: center; padding: 0.2rem 0.5rem; font-size: 0.7rem; font-weight: 500; border-radius: 0.25rem; background: #fef2f2; color: #b91c1c; text-decoration: none;"
                                                       title="Ver PDF">
                                                        <i class="fas fa-file-pdf" style="margin-right: 0.2rem; font-size: 0.7rem;"></i> PDF
                                                    </a>
                                                    <a href="{{ route('admin.facturas.xml', $doc->id) }}" target="_blank"
                                                       style="display: inline-flex; align-items: center; padding: 0.2rem 0.5rem; font-size: 0.7rem; font-weight: 500; border-radius: 0.25rem; background: #f0fdf4; color: #15803d; text-decoration: none;"
                                                       title="Descargar XML">
                                                        <i class="fas fa-code" style="margin-right: 0.2rem; font-size: 0.7rem;"></i> XML
                                                    </a>
                                                @else
                                                    <a href="{{ route('boletas.pdf', $doc->id) }}" target="_blank"
                                                       style="display: inline-flex; align-items: center; padding: 0.2rem 0.5rem; font-size: 0.7rem; font-weight: 500; border-radius: 0.25rem; background: #fef2f2; color: #b91c1c; text-decoration: none;"
                                                       title="Ver PDF">
                                                        <i class="fas fa-file-pdf" style="margin-right: 0.2rem; font-size: 0.7rem;"></i> PDF
                                                    </a>
                                                    <a href="{{ route('boletas.xml', $doc->id) }}" target="_blank"
                                                       style="display: inline-flex; align-items: center; padding: 0.2rem 0.5rem; font-size: 0.7rem; font-weight: 500; border-radius: 0.25rem; background: #f0fdf4; color: #15803d; text-decoration: none;"
                                                       title="Descargar XML">
                                                        <i class="fas fa-code" style="margin-right: 0.2rem; font-size: 0.7rem;"></i> XML
                                                    </a>
                                                @endif

                                                {{-- Botón Re-emitir para documentos con Error --}}
                                                @if(in_array($doc->status, ['error', 'pendiente']) && $doc->source !== 'nota_credito')
                                                    <form method="POST" action="{{ route('admin.documentos.reemitir', ['source' => $doc->source, 'id' => $doc->id]) }}" style="display: inline;" onsubmit="return confirm('¿Estás seguro de re-emitir este documento?');">
                                                        @csrf
                                                        <button type="submit" style="display: inline-flex; align-items: center; padding: 0.2rem 0.6rem; font-size: 0.7rem; font-weight: 600; border-radius: 0.25rem; background-color: #2563eb; color: #ffffff; border: none; cursor: pointer; text-decoration: none; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#1d4ed8'" onmouseout="this.style.backgroundColor='#2563eb'" title="Re-emitir documento en Lioren">
                                                            <i class="fas fa-redo" style="margin-right: 0.3rem; font-size: 0.65rem;"></i> Re-emitir
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" style="padding: 3rem; text-align: center; color: #6b7280;">
                                            No hay documentos emitidos en este periodo.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if(method_exists($documentos, 'links'))
                        <div class="mt-4">
                            {{ $documentos->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
