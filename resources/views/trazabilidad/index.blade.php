<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-bold text-gray-800 leading-tight">
            <span class="text-brand-600">Trazabilidad</span> de SKU
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- HEADER explicativo --}}
            <div class="rounded-2xl p-6 flex items-start gap-4"
                 style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%); border: 1px solid #FFD89C;">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background: linear-gradient(135deg, #FFC800 0%, #FF8100 100%); box-shadow: 0 4px 12px rgba(255, 129, 0, 0.3);">
                    <i class="fas fa-route text-2xl text-white"></i>
                </div>
                <div class="flex-1">
                    <h3 class="bs-display text-xl m-0" style="color: #8A4400;">Trazabilidad de productos por SKU</h3>
                    <p class="text-sm mt-1.5 mb-0" style="color: #5C2D00; line-height: 1.5;">
                        Busca un SKU para ver <strong>a qui&eacute;n se vendi&oacute;, cu&aacute;nto y cu&aacute;ndo</strong> en un periodo determinado.
                        Datos extra&iacute;dos de boletas y facturas emitidas a trav&eacute;s de la integraci&oacute;n.
                    </p>
                </div>
            </div>

            {{-- Formulario de busqueda --}}
            <div class="bs-card bs-card-body">
                <form method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                        {{-- SKU con autocomplete --}}
                        <div class="@if($isAdmin) md:col-span-4 @else md:col-span-6 @endif">
                            <label class="bs-label">SKU del producto</label>
                            <input type="text" name="sku" value="{{ $filtros['sku'] }}"
                                   autocomplete="off" required
                                   placeholder="Ej: 1985940404"
                                   class="bs-input font-mono">
                            <p class="text-xs text-gray-400 mt-1">
                                Escribe el c&oacute;digo exacto del producto
                            </p>
                        </div>

                        @if($isAdmin)
                        <div class="md:col-span-3">
                            <label class="bs-label">Cliente</label>
                            <select name="user_id" class="bs-input">
                                <option value="">Todos los clientes</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->id }}" @if((string)$filtros['user_id'] === (string)$c->id) selected @endif>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div class="@if($isAdmin) md:col-span-3 @else md:col-span-4 @endif">
                            <label class="bs-label">Periodo</label>
                            <select name="periodo" class="bs-input" onchange="bsToggleRango(this.value)">
                                <option value="dia"    @if($filtros['periodo']==='dia') selected @endif>Hoy</option>
                                <option value="semana" @if($filtros['periodo']==='semana') selected @endif>Esta semana</option>
                                <option value="mes"    @if($filtros['periodo']==='mes') selected @endif>Este mes</option>
                                <option value="anio"   @if($filtros['periodo']==='anio') selected @endif>Este a&ntilde;o</option>
                                <option value="rango"  @if($filtros['periodo']==='rango') selected @endif>Personalizado</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <button type="submit" class="bs-btn-primary w-full">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>

                    {{-- Rango personalizado (visible solo si periodo=rango) --}}
                    <div id="bs-rango-personalizado" class="grid grid-cols-2 gap-3 {{ $filtros['periodo']==='rango' ? '' : 'hidden' }}">
                        <div>
                            <label class="bs-label">Desde</label>
                            <input type="date" name="desde" value="{{ $filtros['desde'] }}" class="bs-input">
                        </div>
                        <div>
                            <label class="bs-label">Hasta</label>
                            <input type="date" name="hasta" value="{{ $filtros['hasta'] }}" class="bs-input">
                        </div>
                    </div>
                </form>
            </div>

            @if($filtros['sku'] === '')
                {{-- Empty state inicial --}}
                <div class="bs-card">
                    <div class="p-12 text-center">
                        <div class="inline-flex w-20 h-20 rounded-2xl mb-4 items-center justify-center"
                             style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%);">
                            <i class="fas fa-search text-3xl text-brand-600"></i>
                        </div>
                        <h4 class="bs-display text-xl text-gray-700 m-0">Empieza buscando un SKU</h4>
                        <p class="text-sm text-gray-500 mt-2 mb-0 max-w-md mx-auto">
                            Ingresa el c&oacute;digo del producto que quieres rastrear y elige un periodo.
                            Te mostraremos todas las ventas, clientes y montos.
                        </p>
                    </div>
                </div>
            @elseif($resultados->isEmpty())
                {{-- Empty state inteligente segun diagnostico --}}
                @php $d = $diagnostico ?? null; @endphp
                <div class="bs-card">
                    <div class="p-12 text-center max-w-2xl mx-auto">

                        @if($d && $d['caso'] === 'sku_pertenece_a_otro_cliente')
                            {{-- CASO: el SKU existe pero en otro cliente --}}
                            <div class="inline-flex w-20 h-20 rounded-2xl mb-4 items-center justify-center"
                                 style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%);">
                                <i class="fas fa-exchange-alt text-3xl text-brand-600"></i>
                            </div>
                            <h4 class="bs-display text-xl text-gray-800 m-0">Este SKU pertenece a otro cliente</h4>
                            <p class="text-sm text-gray-500 mt-2 mb-3">
                                El SKU <strong class="text-brand-600 font-mono">{{ $filtros['sku'] }}</strong>
                                @if($d['nombre_producto'])
                                    (<em>{{ $d['nombre_producto'] }}</em>)
                                @endif
                                no tiene ventas en
                                <strong>{{ ($clientes->firstWhere('id', $d['user_id_filtrado']))->name ?? 'el cliente seleccionado' }}</strong>.
                            </p>
                            <p class="text-sm text-gray-700 mt-4 mb-3">
                                S&iacute; aparece en
                                @foreach($d['clientes_con_ventas'] as $cv)
                                    <a href="?sku={{ $filtros['sku'] }}&user_id={{ $cv->id }}&periodo={{ $filtros['periodo'] }}"
                                       class="bs-badge-brand inline-flex hover:opacity-80 transition-opacity ml-1">
                                        <i class="fas fa-user text-[0.6rem]"></i> {{ $cv->name }}
                                    </a>
                                @endforeach
                            </p>
                            <p class="text-xs text-gray-400 mt-4">Click en un cliente para ver sus ventas de este SKU.</p>

                        @elseif($d && $d['caso'] === 'sku_inexistente')
                            {{-- CASO: el SKU no existe en ningun lado --}}
                            <div class="inline-flex w-20 h-20 rounded-2xl mb-4 items-center justify-center bg-red-50">
                                <i class="fas fa-times-circle text-3xl text-red-500"></i>
                            </div>
                            <h4 class="bs-display text-xl text-gray-800 m-0">SKU no encontrado</h4>
                            <p class="text-sm text-gray-500 mt-2 mb-0">
                                El SKU <strong class="text-red-600 font-mono">{{ $filtros['sku'] }}</strong>
                                no aparece en el cat&aacute;logo ni en ninguna venta registrada.
                            </p>
                            <p class="text-xs text-gray-400 mt-3">Verifica que el c&oacute;digo est&eacute; bien escrito.</p>

                        @elseif($d && $d['caso'] === 'sku_en_catalogo_sin_ventas')
                            {{-- CASO: el SKU existe en catalogo pero nunca se vendio --}}
                            <div class="inline-flex w-20 h-20 rounded-2xl mb-4 items-center justify-center bg-gray-100">
                                <i class="fas fa-box-open text-3xl text-gray-400"></i>
                            </div>
                            <h4 class="bs-display text-xl text-gray-800 m-0">Producto sin ventas</h4>
                            <p class="text-sm text-gray-500 mt-2 mb-0">
                                El SKU <strong class="text-brand-600 font-mono">{{ $filtros['sku'] }}</strong>
                                @if($d['nombre_producto'])
                                    (<em>{{ $d['nombre_producto'] }}</em>)
                                @endif
                                est&aacute; en el cat&aacute;logo pero a&uacute;n no se ha vendido.
                            </p>

                        @else
                            {{-- CASO default: SKU existe pero fuera del rango temporal --}}
                            <div class="inline-flex w-20 h-20 rounded-2xl mb-4 items-center justify-center bg-gray-100">
                                <i class="fas fa-inbox text-3xl text-gray-400"></i>
                            </div>
                            <h4 class="bs-display text-xl text-gray-700 m-0">Sin ventas en este periodo</h4>
                            <p class="text-sm text-gray-500 mt-2 mb-0">
                                El SKU <strong class="text-brand-600 font-mono">{{ $filtros['sku'] }}</strong>
                                @if($d && $d['nombre_producto'])
                                    (<em>{{ $d['nombre_producto'] }}</em>)
                                @endif
                                no tiene ventas registradas entre
                                <strong>{{ $desde->format('d/m/Y') }}</strong> y
                                <strong>{{ $hasta->format('d/m/Y') }}</strong>.
                            </p>
                            @if($d && $d['ventas_totales'] > 0)
                                <p class="text-sm text-gray-700 mt-3 mb-0">
                                    <i class="fas fa-info-circle text-brand-500"></i>
                                    Este SKU tiene <strong class="text-brand-600">{{ $d['ventas_totales'] }} ventas hist&oacute;ricas</strong>.
                                    Prueba ampliar el rango (ej: este a&ntilde;o).
                                </p>
                            @else
                                <p class="text-xs text-gray-400 mt-3">Prueba ampliar el rango (ej: este a&ntilde;o).</p>
                            @endif
                        @endif
                    </div>
                </div>
            @else
                {{-- 4 KPI cards --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bs-card bs-card-body" style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%); border-color: #FFD89C;">
                        <p class="text-xs font-semibold uppercase tracking-wide" style="color: #B85B00;">Unidades vendidas</p>
                        <p class="bs-display text-3xl mt-1" style="color: #8A4400;">{{ number_format($stats['unidades'], 0, ',', '.') }}</p>
                    </div>
                    <div class="bs-card bs-card-body">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Monto total</p>
                        <p class="bs-display text-3xl text-gray-900 mt-1">${{ number_format($stats['monto_total'], 0, ',', '.') }}</p>
                    </div>
                    <div class="bs-card bs-card-body">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Clientes &uacute;nicos</p>
                        <p class="bs-display text-3xl text-gray-900 mt-1">{{ $stats['clientes_unicos'] }}</p>
                    </div>
                    <div class="bs-card bs-card-body">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">&Uacute;ltima venta</p>
                        <p class="bs-display text-lg text-gray-900 mt-1">{{ $stats['ultima_venta']->format('d/m/Y') }}</p>
                        <p class="text-xs text-gray-400">{{ $stats['ultima_venta']->diffForHumans() }}</p>
                    </div>
                </div>

                {{-- Info del producto --}}
                @if($stats['producto_nombre'])
                <div class="bs-card bs-card-body flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-box"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide m-0">Producto</p>
                        <p class="font-bold text-gray-900 m-0">{{ $stats['producto_nombre'] }} <span class="font-mono text-xs text-gray-400 ml-2">SKU: {{ $filtros['sku'] }}</span></p>
                    </div>
                </div>
                @endif

                {{-- Top compradores + serie diaria --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div class="bs-card lg:col-span-1">
                        <div class="bs-card-header">
                            <h3 class="font-bold text-gray-800 m-0">Top compradores</h3>
                            <span class="text-xs text-gray-400">Por unidades</span>
                        </div>
                        <div class="p-4">
                            @foreach($stats['top_compradores'] as $i => $tc)
                            <div class="flex items-center gap-3 py-2 {{ !$loop->last ? 'border-b border-gray-50' : '' }}">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0
                                            {{ $i === 0 ? 'bg-brand-100 text-brand-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $i + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 m-0 truncate">{{ $tc['nombre'] }}</p>
                                    @if($tc['rut'])
                                        <p class="text-xs text-gray-400 m-0 font-mono">{{ $tc['rut'] }}</p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-brand-600 m-0">{{ number_format($tc['unidades'], 0, ',', '.') }} u.</p>
                                    <p class="text-xs text-gray-400 m-0">${{ number_format($tc['monto'], 0, ',', '.') }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Sparkline (agrupada por semana si rango es largo) --}}
                    @php
                        $series = $stats['series_diarias'] ?: [];
                        $diasTotales = count($series);
                        $agrupacion = 'dia';
                        $serieAgrupada = $series;

                        // Si hay mas de 31 puntos, agrupar por semana para que las barras sean visibles
                        if ($diasTotales > 31) {
                            $agrupacion = 'semana';
                            $serieAgrupada = [];
                            foreach ($series as $fecha => $cant) {
                                $c = \Carbon\Carbon::parse($fecha);
                                $semanaKey = $c->copy()->startOfWeek()->format('Y-m-d');
                                $serieAgrupada[$semanaKey] = ($serieAgrupada[$semanaKey] ?? 0) + $cant;
                            }
                            ksort($serieAgrupada);
                        }
                        // Si hay mas de 26 semanas (>6 meses), agrupar por mes
                        if (count($serieAgrupada) > 26) {
                            $agrupacion = 'mes';
                            $tmp = [];
                            foreach ($serieAgrupada as $fecha => $cant) {
                                $mesKey = \Carbon\Carbon::parse($fecha)->startOfMonth()->format('Y-m-d');
                                $tmp[$mesKey] = ($tmp[$mesKey] ?? 0) + $cant;
                            }
                            $serieAgrupada = $tmp;
                        }

                        $maxVal = $serieAgrupada ? max($serieAgrupada) : 1;
                        $maxVal = max(1, $maxVal);
                        $totalSerie = array_sum($serieAgrupada);
                        $promedio = count($serieAgrupada) > 0 ? round($totalSerie / count($serieAgrupada), 1) : 0;
                    @endphp
                    <div class="bs-card lg:col-span-2">
                        <div class="bs-card-header">
                            <h3 class="font-bold text-gray-800 m-0">
                                Unidades por {{ $agrupacion === 'mes' ? 'mes' : ($agrupacion === 'semana' ? 'semana' : 'd&iacute;a') }}
                            </h3>
                            <div class="flex items-center gap-3 text-xs">
                                <span class="text-gray-500">{{ $desde->format('d/m/y') }} &mdash; {{ $hasta->format('d/m/y') }}</span>
                                <span class="bs-badge-brand">{{ count($serieAgrupada) }} {{ $agrupacion === 'mes' ? 'meses' : ($agrupacion === 'semana' ? 'semanas' : 'días') }}</span>
                            </div>
                        </div>
                        <div class="p-6 pt-4">
                            {{-- Stats arriba del grafico --}}
                            <div class="flex justify-between items-baseline mb-3">
                                <p class="text-xs text-gray-500 m-0">
                                    Pico: <strong class="text-brand-600">{{ number_format($maxVal, 0, ',', '.') }} u.</strong>
                                    <span class="mx-2">·</span>
                                    Promedio: <strong class="text-gray-700">{{ number_format($promedio, 1, ',', '.') }} u.</strong>
                                </p>
                            </div>

                            @if(empty($serieAgrupada))
                                <p class="text-center text-gray-400 py-8 text-sm">Sin datos para graficar.</p>
                            @else
                                {{-- Grilla horizontal de referencia --}}
                                <div class="relative" style="height: 180px;">
                                    {{-- Lineas guias --}}
                                    <div class="absolute inset-0 flex flex-col justify-between pointer-events-none">
                                        <div class="border-t border-dashed border-gray-200"></div>
                                        <div class="border-t border-dashed border-gray-200"></div>
                                        <div class="border-t border-dashed border-gray-200"></div>
                                        <div class="border-t border-gray-300"></div>
                                    </div>
                                    {{-- Labels Y eje izquierdo --}}
                                    <div class="absolute inset-y-0 left-0 -ml-1 flex flex-col justify-between text-[10px] text-gray-400 font-semibold" style="width: 28px;">
                                        <span>{{ number_format($maxVal, 0, ',', '.') }}</span>
                                        <span>{{ number_format($maxVal * 0.66, 0, ',', '.') }}</span>
                                        <span>{{ number_format($maxVal * 0.33, 0, ',', '.') }}</span>
                                        <span>0</span>
                                    </div>
                                    {{-- Barras --}}
                                    <div class="absolute inset-0 pl-9 flex items-end gap-1">
                                        @foreach($serieAgrupada as $fecha => $cant)
                                            @php
                                                $altura = max(8, ($cant / $maxVal) * 100); // min 8% para visibilidad
                                                $color  = $cant >= $maxVal * 0.66 ? '#FF8100' : ($cant >= $maxVal * 0.33 ? '#FF9C00' : '#FFC800');
                                            @endphp
                                            <div class="flex-1 flex flex-col items-center justify-end h-full group relative">
                                                {{-- Tooltip --}}
                                                <div class="absolute bottom-full mb-2 hidden group-hover:block whitespace-nowrap bg-gray-900 text-white text-xs rounded-md px-2 py-1.5 z-10 shadow-lg pointer-events-none">
                                                    <div class="font-bold">{{ number_format($cant, 0, ',', '.') }} unidades</div>
                                                    <div class="text-gray-300 text-[10px]">
                                                        @if($agrupacion === 'mes')
                                                            {{ \Carbon\Carbon::parse($fecha)->translatedFormat('F Y') }}
                                                        @elseif($agrupacion === 'semana')
                                                            Semana del {{ \Carbon\Carbon::parse($fecha)->format('d/m') }}
                                                        @else
                                                            {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}
                                                        @endif
                                                    </div>
                                                </div>
                                                {{-- Barra --}}
                                                <div class="w-full rounded-t transition-all hover:opacity-80 cursor-default" style="height: {{ $altura }}%; background-color: {{ $color }};"></div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Labels X eje (mostrar solo algunos para no saturar) --}}
                                <div class="pl-9 flex gap-1 mt-2">
                                    @php
                                        $totalBarras = count($serieAgrupada);
                                        $cadaN = max(1, (int) ceil($totalBarras / 8));
                                        $idx = 0;
                                    @endphp
                                    @foreach($serieAgrupada as $fecha => $cant)
                                        <div class="flex-1 text-center text-[10px] text-gray-500 font-medium">
                                            @if($idx % $cadaN === 0)
                                                @if($agrupacion === 'mes')
                                                    {{ \Carbon\Carbon::parse($fecha)->translatedFormat('M') }}
                                                @elseif($agrupacion === 'semana')
                                                    {{ \Carbon\Carbon::parse($fecha)->format('d/m') }}
                                                @else
                                                    {{ \Carbon\Carbon::parse($fecha)->format('d/m') }}
                                                @endif
                                            @endif
                                        </div>
                                        @php $idx++; @endphp
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Tabla de ventas --}}
                <div class="bs-card overflow-hidden">
                    <div class="bs-card-header">
                        <h3 class="font-bold text-gray-800 m-0">Detalle de ventas</h3>
                        <span class="text-xs text-gray-500">{{ $resultados->count() }} {{ $resultados->count() === 1 ? 'venta' : 'ventas' }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="bs-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Documento</th>
                                    <th>Comprador</th>
                                    @if($isAdmin)
                                    <th>Tienda</th>
                                    @endif
                                    <th class="text-right">Cant.</th>
                                    <th class="text-right">P. unit.</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resultados as $v)
                                <tr>
                                    <td class="text-xs">
                                        <div class="font-semibold text-gray-800">{{ $v['fecha']->format('d/m/Y') }}</div>
                                        <div class="text-gray-400">{{ $v['fecha']->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        @if($v['tipo'] === 'boleta')
                                            <span class="bs-badge-neutral">Boleta</span>
                                        @else
                                            <span class="bs-badge-brand">Factura</span>
                                        @endif
                                        @if($v['folio'])
                                            <div class="text-xs text-gray-500 mt-1">N&deg; {{ $v['folio'] }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="font-semibold text-gray-800 text-sm">{{ $v['receptor_nombre'] }}</div>
                                        @if($v['receptor_rut'])
                                            <div class="text-xs text-gray-400 font-mono">{{ $v['receptor_rut'] }}</div>
                                        @endif
                                    </td>
                                    @if($isAdmin)
                                    <td class="text-xs text-gray-600">
                                        @php $u = $clientes->firstWhere('id', $v['cliente_user_id']); @endphp
                                        {{ $u->name ?? '—' }}
                                    </td>
                                    @endif
                                    <td class="text-right font-bold text-brand-600">
                                        {{ number_format($v['cantidad'], 0, ',', '.') }} {{ $v['unidad'] }}
                                    </td>
                                    <td class="text-right text-gray-600">
                                        ${{ number_format($v['precio'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-right font-bold text-gray-900">
                                        ${{ number_format($v['total_linea'], 0, ',', '.') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function bsToggleRango(val) {
            const el = document.getElementById('bs-rango-personalizado');
            if (val === 'rango') el.classList.remove('hidden');
            else el.classList.add('hidden');
        }
    </script>
</x-app-layout>
