<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-bold text-gray-800 leading-tight">
            <span class="text-brand-600">Pagos</span> Recibidos
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Stats globales --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bs-card bs-card-body">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pagos totales</p>
                    <p class="bs-display text-3xl text-gray-900 mt-1">{{ number_format($stats['total'], 0, ',', '.') }}</p>
                </div>
                <div class="bs-card bs-card-body" style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%); border-color: #FFD89C;">
                    <p class="text-xs font-semibold uppercase tracking-wide" style="color: #B85B00;">Monto acumulado</p>
                    <p class="bs-display text-3xl mt-1" style="color: #B85B00;">${{ number_format($stats['monto_total'], 0, ',', '.') }}</p>
                </div>
                <div class="bs-card bs-card-body">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide flex items-center gap-1">
                        <span class="inline-block w-2 h-2 rounded-full" style="background:#0EA5E9;"></span> Flow
                    </p>
                    <p class="bs-display text-3xl text-gray-900 mt-1">{{ $stats['flow'] }}</p>
                </div>
                <div class="bs-card bs-card-body">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide flex items-center gap-1">
                        <span class="inline-block w-2 h-2 rounded-full" style="background:#8B5CF6;"></span> Transferencia
                    </p>
                    <p class="bs-display text-3xl text-gray-900 mt-1">{{ $stats['transferencia'] }}</p>
                </div>
                <div class="bs-card bs-card-body">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide flex items-center gap-1">
                        <span class="inline-block w-2 h-2 rounded-full" style="background:#10B981;"></span> Manual
                    </p>
                    <p class="bs-display text-3xl text-gray-900 mt-1">{{ $stats['manual'] }}</p>
                </div>
            </div>

            {{-- Tabla con filtros --}}
            <div class="bs-card overflow-hidden">
                <div class="bs-card-header">
                    <div>
                        <h3 class="font-bold text-gray-800 m-0">Historial completo</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            @if($hayFiltros)
                                Mostrando <strong class="text-brand-600">{{ $pagos->total() }}</strong> resultados filtrados
                                <a href="{{ route('admin.pagos-recibidos.index') }}" class="bs-link ml-2">Limpiar filtros</a>
                            @else
                                {{ $pagos->total() }} pagos en total
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Filtros --}}
                <form method="GET" action="{{ route('admin.pagos-recibidos.index') }}"
                      class="px-6 pt-4 pb-2 grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                    <div class="md:col-span-2">
                        <label class="bs-label">Origen</label>
                        <select name="origen" class="bs-input">
                            <option value="">Todos</option>
                            <option value="flow"          @if($filtros['origen']==='flow') selected @endif>Flow</option>
                            <option value="transferencia" @if($filtros['origen']==='transferencia') selected @endif>Transferencia</option>
                            <option value="manual"        @if($filtros['origen']==='manual') selected @endif>Manual</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="bs-label">Pagado desde</label>
                        <input type="date" name="desde" value="{{ $filtros['desde'] }}" class="bs-input">
                    </div>
                    <div class="md:col-span-2">
                        <label class="bs-label">Pagado hasta</label>
                        <input type="date" name="hasta" value="{{ $filtros['hasta'] }}" class="bs-input">
                    </div>
                    <div class="md:col-span-4">
                        <label class="bs-label">Buscar (cliente, N&deg;, folio)</label>
                        <input type="text" name="q" value="{{ $filtros['q'] }}" placeholder="Nombre, email, folio..." class="bs-input">
                    </div>
                    <div class="md:col-span-1">
                        <button type="submit" class="bs-btn-primary w-full">Filtrar</button>
                    </div>
                    <div class="md:col-span-1">
                        @if($hayFiltros)
                        <a href="{{ route('admin.pagos-recibidos.index') }}" class="bs-btn-neutral w-full">Limpiar</a>
                        @endif
                    </div>
                </form>

                @if($pagos->count() > 0)
                <div class="overflow-x-auto">
                    <table class="bs-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>N&deg; Factura</th>
                                <th>Origen</th>
                                <th>Periodo</th>
                                <th class="text-right">Monto</th>
                                <th>Pagado</th>
                                <th class="text-center">Doc.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pagos as $pago)
                            @php
                                if ($pago->flow_token) {
                                    $origen = 'flow'; $origenLabel = 'Flow'; $origenColor = '#0EA5E9'; $origenBg = '#E0F2FE';
                                } elseif (stripos($pago->concepto, 'transfer') !== false) {
                                    $origen = 'transferencia'; $origenLabel = 'Transferencia'; $origenColor = '#7C3AED'; $origenBg = '#EDE9FE';
                                } else {
                                    $origen = 'manual'; $origenLabel = 'Manual'; $origenColor = '#059669'; $origenBg = '#D1FAE5';
                                }
                                // Total CON IVA: campo `monto` ya incluye neto+IVA+extras.
                                // Fallback si monto esta vacio en registros viejos.
                                $totalCLP = (int) (($pago->monto ?? 0) > 0
                                    ? $pago->monto
                                    : ($pago->monto_neto ?? 0) + ($pago->monto_iva ?? 0) + ($pago->monto_extra_clp ?? 0));
                            @endphp
                            <tr>
                                <td>
                                    <div class="font-semibold text-gray-800">{{ $pago->user->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-500">{{ $pago->user->email ?? '' }}</div>
                                </td>
                                <td>
                                    @if($pago->numero_factura)
                                        <span class="font-semibold text-gray-800">{{ $pago->numero_factura }}</span>
                                    @elseif($pago->folio)
                                        <span class="font-semibold text-brand-600">FS-{{ str_pad($pago->folio, 6, '0', STR_PAD_LEFT) }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                    @if($pago->folio)
                                        <div class="text-xs text-gray-400">Folio SII: {{ $pago->folio }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="bs-badge" style="background: {{ $origenBg }}; color: {{ $origenColor }};">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full" style="background: {{ $origenColor }};"></span>
                                        {{ $origenLabel }}
                                    </span>
                                </td>
                                <td class="text-xs text-gray-600">
                                    @if($pago->periodo_inicio && $pago->periodo_fin)
                                        {{ $pago->periodo_inicio->format('d/m/y') }} &mdash; {{ $pago->periodo_fin->format('d/m/y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-right font-bold text-gray-900">
                                    ${{ number_format($totalCLP, 0, ',', '.') }}
                                </td>
                                <td class="text-xs">
                                    @if($pago->pagada_at)
                                        <div class="text-gray-700">{{ $pago->pagada_at->format('d/m/Y') }}</div>
                                        <div class="text-gray-400">{{ $pago->pagada_at->diffForHumans() }}</div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($pago->pdf_base64 || $pago->estado === 'pagada')
                                        <a href="{{ route('admin.billing.factura-pdf', $pago->id) }}" target="_blank"
                                           class="bs-btn-ghost bs-btn-sm" title="Ver PDF">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                            PDF
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $pagos->links() }}
                </div>
                @else
                <div class="p-12 text-center">
                    @if($hayFiltros)
                        <p class="text-gray-500 mb-3">No hay pagos que coincidan con los filtros.</p>
                        <a href="{{ route('admin.pagos-recibidos.index') }}" class="bs-link">&larr; Limpiar filtros</a>
                    @else
                        <p class="text-gray-500">A&uacute;n no hay pagos recibidos.</p>
                    @endif
                </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
