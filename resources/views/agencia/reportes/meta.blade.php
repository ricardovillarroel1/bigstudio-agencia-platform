<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-bold text-gray-800 leading-tight">
            <span class="text-brand-600">Reporte</span> de Performance — Meta Ads
        </h2>
    </x-slot>

    @php
        $fmt = fn($n) => '$' . number_format((int) $n, 0, ',', '.');
        $inv = $resumen->inversion ?? 0;
        $ven = $resumen->ventas ?? 0;
        $comp = $resumen->compras ?? 0;
        $alc = $resumen->alcance ?? 0;
        $imp = $resumen->impresiones ?? 0;
        $clk = $resumen->clicks ?? 0;
        $roas = $inv > 0 ? round($ven / $inv, 2) : 0;
        $cpa = $comp > 0 ? round($inv / $comp) : 0;
        $ctr = $imp > 0 ? round($clk / $imp * 100, 2) : 0;
        $cpc = $clk > 0 ? round($inv / $clk) : 0;
        if (($usaRango ?? false) && !empty($desde) && !empty($hasta)) {
            $mesNombre = \Carbon\Carbon::parse($desde)->locale('es')->isoFormat('D MMM YYYY') . ' al ' . \Carbon\Carbon::parse($hasta)->locale('es')->isoFormat('D MMM YYYY');
        } else {
            try { $mesNombre = \Carbon\Carbon::createFromFormat('Y-m', $periodo)->locale('es')->isoFormat('MMMM YYYY'); }
            catch (\Throwable $e) { $mesNombre = $periodo; }
        }
        $generoLabels = ['male' => 'Hombres', 'female' => 'Mujeres', 'unknown' => 'Sin determinar'];
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="rounded-xl px-4 py-3 text-sm" style="background:#ECFDF5; border:1px solid #A7F3D0; color:#065F46;">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="rounded-xl px-4 py-3 text-sm" style="background:#FEF2F2; border:1px solid #FCA5A5; color:#991B1B;">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
            @endif

            {{-- Selector de cuenta + período --}}
            <div class="bs-card p-4">
                <div class="flex flex-wrap gap-4 items-end">
                    {{-- FORM 1: cuenta + mes (solo envía cuenta_id + periodo) --}}
                    <form method="GET" action="{{ route('agencia.reportes.meta-demo') }}" class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="bs-label">Cliente / Cuenta</label>
                        <select name="cuenta_id" class="bs-input" onchange="this.form.submit()">
                            @forelse($cuentas as $c)
                                <option value="{{ $c->id }}" {{ ($cuenta && $cuenta->id == $c->id) ? 'selected' : '' }}>
                                    {{ $c->nombre_cuenta }}{{ $c->cliente ? ' — '.$c->cliente->nombre : '' }}
                                </option>
                            @empty
                                <option value="">No hay cuentas vinculadas</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="min-w-[160px]">
                        <label class="bs-label">Mes</label>
                        <select name="periodo" class="bs-input" onchange="this.form.submit()">
                            @php
                                // Fix: usar startOfMonth() para evitar overflow del día 31
                                // (ej: 31 may - 1 mes = 1 may, saltando abril).
                                $mesesOpts = [];
                                $base = \Carbon\Carbon::now()->startOfMonth();
                                for ($i = 0; $i < 12; $i++) {
                                    $m = $base->copy()->subMonths($i);
                                    $mesesOpts[$m->format('Y-m')] = ucfirst($m->locale('es')->isoFormat('MMMM YYYY'));
                                }
                            @endphp
                            @foreach($mesesOpts as $val => $lbl)
                                <option value="{{ $val }}" {{ (!($usaRango ?? false) && $periodo == $val) ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    </form>

                    {{-- FORM 2: rango personalizado (independiente) --}}
                    <form method="GET" action="{{ route('agencia.reportes.meta-demo') }}" class="flex flex-wrap gap-3 items-end">
                        <input type="hidden" name="cuenta_id" value="{{ $cuenta->id ?? '' }}">
                        <div>
                            <label class="bs-label">Desde</label>
                            <input type="date" name="desde" value="{{ $desde ?? '' }}" class="bs-input" required>
                        </div>
                        <div>
                            <label class="bs-label">Hasta</label>
                            <input type="date" name="hasta" value="{{ $hasta ?? '' }}" class="bs-input" required>
                        </div>
                        <div>
                            <button type="submit" class="bs-btn-primary"><i class="fas fa-calendar-alt"></i> Ver rango</button>
                        </div>
                    </form>

                    {{-- FORM 3: actualizar datos desde Meta (independiente) --}}
                    @if($cuenta)
                    <form method="POST" action="{{ route('agencia.reportes.conexion.sincronizar', $cuenta) }}" class="flex items-end">
                        @csrf
                        <input type="hidden" name="periodo" value="{{ $periodo }}">
                        <button type="submit" class="bs-btn-secondary"><i class="fas fa-sync"></i> Actualizar datos</button>
                    </form>

                    {{-- BOTON: enviar al cliente --}}
                    @if($resumen)
                    <div class="flex items-end">
                        <button type="button" onclick="document.getElementById('modal-envio').showModal()" class="bs-btn-primary" style="background:#10B981;">
                            <i class="fas fa-paper-plane"></i> Enviar al cliente
                        </button>
                    </div>
                    @endif
                    @endif
                </div>
            </div>

            @if(!$cuenta)
                <div class="bs-card p-12 text-center">
                    <div class="inline-flex w-16 h-16 rounded-2xl mb-3 items-center justify-center" style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%);">
                        <i class="fab fa-facebook text-2xl text-brand-500"></i>
                    </div>
                    <p class="text-gray-600 m-0">No hay cuentas vinculadas todavía.</p>
                    <a href="{{ route('agencia.reportes.conexion') }}" class="bs-link text-sm mt-2 inline-block">Ir a Conectar Meta Ads</a>
                </div>
            @elseif(!$resumen)
                <div class="bs-card p-12 text-center">
                    <div class="inline-flex w-16 h-16 rounded-2xl mb-3 items-center justify-center" style="background:#FEF3C7;">
                        <i class="fas fa-clock text-2xl" style="color:#D97706;"></i>
                    </div>
                    <h4 class="bs-display text-lg text-gray-700 m-0">Sin datos para {{ $mesNombre }}</h4>
                    <p class="text-sm text-gray-500 mt-2 mb-3">Aún no se han sincronizado métricas de esta cuenta para este mes.</p>
                    <form method="POST" action="{{ route('agencia.reportes.conexion.sincronizar', $cuenta) }}">
                        @csrf
                        <input type="hidden" name="periodo" value="{{ $periodo }}">
                        <button type="submit" class="bs-btn-primary"><i class="fas fa-sync"></i> Sincronizar ahora</button>
                    </form>
                </div>
            @else
                {{-- Hero --}}
                <div class="bs-card overflow-hidden">
                    <div class="px-8 py-6 flex items-center justify-between flex-wrap gap-4" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                        <div>
                            <p class="text-sm text-white/90 m-0 font-semibold uppercase tracking-wide">Reporte mensual</p>
                            <h3 class="bs-display text-2xl text-white m-0 leading-tight">{{ $cuenta->cliente->nombre ?? $cuenta->nombre_cuenta }}</h3>
                            <p class="text-sm text-white/90 mt-1 mb-0"><i class="far fa-calendar"></i> {{ ucfirst($mesNombre) }} · Meta Ads</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-white/80 m-0 uppercase">Retorno (ROAS)</p>
                            <p class="bs-display text-4xl text-white m-0 leading-none">{{ $roas }}x</p>
                        </div>
                    </div>
                </div>

                {{-- KPIs --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bs-card p-5">
                        <p class="text-xs text-gray-500 uppercase tracking-wide m-0"><i class="fas fa-coins text-brand-500"></i> Inversión</p>
                        <p class="bs-display text-2xl text-gray-900 mt-1 mb-0">{{ $fmt($inv) }}</p>
                    </div>
                    <div class="bs-card p-5">
                        <p class="text-xs text-gray-500 uppercase tracking-wide m-0"><i class="fas fa-shopping-cart text-brand-500"></i> Ventas generadas</p>
                        <p class="bs-display text-2xl mt-1 mb-0" style="color:#059669;">{{ $fmt($ven) }}</p>
                    </div>
                    <div class="bs-card p-5">
                        <p class="text-xs text-gray-500 uppercase tracking-wide m-0"><i class="fas fa-bullseye text-brand-500"></i> Compras</p>
                        <p class="bs-display text-2xl text-gray-900 mt-1 mb-0">{{ number_format($comp,0,',','.') }}</p>
                        <p class="text-xs text-gray-500 mt-1 mb-0">Costo x compra: {{ $fmt($cpa) }}</p>
                    </div>
                    <div class="bs-card p-5">
                        <p class="text-xs text-gray-500 uppercase tracking-wide m-0"><i class="fas fa-eye text-brand-500"></i> Alcance</p>
                        <p class="bs-display text-2xl text-gray-900 mt-1 mb-0">{{ number_format($alc,0,',','.') }}</p>
                        <p class="text-xs text-gray-500 mt-1 mb-0">{{ number_format($imp,0,',','.') }} impresiones</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Embudo --}}
                    <div class="bs-card overflow-hidden">
                        <div class="bs-card-header"><h3 class="bs-display text-lg text-gray-800 m-0">Embudo de conversión</h3></div>
                        <div class="bs-card-body"><div class="bs-funnel">
                            @php
                                $etapas = [
                                    ['Alcance', $alc, '#FFC800'],
                                    ['Impresiones', $imp, '#FF9C00'],
                                    ['Clicks', $clk, '#FF8100'],
                                    ['Compras', $comp, '#10B981'],
                                ];
                                $maxEt = max(1, $alc, $imp, $clk, $comp);
                            @endphp
                            @foreach($etapas as $i => $et)
                                @php
                                    $ancho = max(6, round($et[1] / $maxEt * 100));
                                    $conv = ($i > 0 && $etapas[$i-1][1] > 0) ? round($et[1] / $etapas[$i-1][1] * 100, 1) : null;
                                @endphp
                                <div class="bs-funnel-row">
                                    <div class="bs-funnel-label">{{ $et[0] }}</div>
                                    <div class="bs-funnel-bar-wrap">
                                        <div class="bs-funnel-bar" style="width: {{ $ancho }}%; background: {{ $et[2] }};">
                                            <span class="bs-funnel-val">{{ number_format($et[1], 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                    <div class="bs-funnel-conv">@if($conv !== null)<i class="fas fa-arrow-down" style="font-size:0.6rem;"></i> {{ $conv }}%@endif</div>
                                </div>
                            @endforeach
                        </div>
                        <style>
                            .bs-funnel { display:flex; flex-direction:column; gap:14px; padding:8px 0; }
                            .bs-funnel-row { display:flex; align-items:center; gap:12px; }
                            .bs-funnel-label { width:90px; font-size:0.8rem; color:#6B7280; text-align:right; flex-shrink:0; }
                            .bs-funnel-bar-wrap { flex:1; background:#F3F4F6; border-radius:8px; overflow:hidden; }
                            .bs-funnel-bar { min-width:60px; padding:8px 12px; border-radius:8px; display:flex; align-items:center; justify-content:flex-end; transition:width .4s; }
                            .bs-funnel-val { color:#fff; font-weight:700; font-size:0.8rem; text-shadow:0 1px 1px rgba(0,0,0,0.15); }
                            .bs-funnel-conv { width:70px; font-size:0.75rem; font-weight:600; color:#059669; flex-shrink:0; }
                        </style></div>
                    </div>
                    {{-- Dona campañas --}}
                    <div class="bs-card overflow-hidden">
                        <div class="bs-card-header"><h3 class="bs-display text-lg text-gray-800 m-0">Inversión por campaña</h3></div>
                        <div class="bs-card-body">
                            @if($campanas->count() > 0)
                                <canvas id="chartDona" height="170"></canvas>
                            @else
                                <p class="text-sm text-gray-400 text-center py-8 m-0">Sin desglose de campañas este mes.</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ============ DEMOGRAFICOS ============ --}}
                @if($demoEdad->count() > 0 || $demoGenero->count() > 0 || $demoRegion->count() > 0)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Edad --}}
                    @if($demoEdad->count() > 0)
                    <div class="bs-card overflow-hidden">
                        <div class="bs-card-header"><h3 class="bs-display text-lg text-gray-800 m-0"><i class="fas fa-user-clock text-brand-500"></i> Performance por edad</h3></div>
                        <div class="bs-card-body">
                            @php $maxInvEdad = max(1, $demoEdad->max('inversion')); @endphp
                            <div class="space-y-3">
                                @foreach($demoEdad as $d)
                                @php $dRoas = $d->inversion > 0 ? round($d->ventas / $d->inversion, 2) : 0; $w = max(8, round($d->inversion / $maxInvEdad * 100)); @endphp
                                <div>
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="font-semibold text-gray-700">{{ $d->objeto_nombre }} años</span>
                                        <span class="text-gray-500">{{ $fmt($d->inversion) }} · <span style="color:#059669; font-weight:600;">{{ $d->compras }} compras</span> · <span style="color:#FF8100; font-weight:700;">{{ $dRoas }}x</span></span>
                                    </div>
                                    <div style="background:#F3F4F6; border-radius:6px; overflow:hidden; height:10px;">
                                        <div style="width:{{ $w }}%; height:100%; background:linear-gradient(90deg,#FF9C00,#FF8100);"></div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Género --}}
                    @if($demoGenero->count() > 0)
                    <div class="bs-card overflow-hidden">
                        <div class="bs-card-header"><h3 class="bs-display text-lg text-gray-800 m-0"><i class="fas fa-venus-mars text-brand-500"></i> Performance por género</h3></div>
                        <div class="bs-card-body">
                            <canvas id="chartGenero" height="170"></canvas>
                            <div class="grid grid-cols-{{ min(3,$demoGenero->count()) }} gap-2 mt-3 text-center">
                                @foreach($demoGenero as $g)
                                @php $gRoas = $g->inversion > 0 ? round($g->ventas / $g->inversion, 2) : 0; @endphp
                                <div class="p-2 rounded-lg" style="background:#FFF7EC;">
                                    <p class="text-xs text-gray-500 m-0 uppercase">{{ $generoLabels[$g->objeto_nombre] ?? ucfirst($g->objeto_nombre) }}</p>
                                    <p class="font-bold text-gray-800 m-0">{{ $fmt($g->inversion) }}</p>
                                    <p class="text-xs m-0" style="color:#FF8100; font-weight:700;">ROAS {{ $gRoas }}x</p>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Región (full width) - Meta no expone purchases por región, mostramos métricas de alcance/engagement --}}
                    @if($demoRegion->count() > 0)
                    @php
                        // Ordenamos por inversión ya que ventas no vienen por region en Meta API
                        $demoRegionOrdenadas = $demoRegion->sortByDesc('inversion')->values();
                        $totalInvRegion = max(1, $demoRegion->sum('inversion'));
                        // Si tenemos total de compras a nivel cuenta, podemos estimar compras prorrateadas por % de inversión
                        $compTotal = $resumen->compras ?? 0;
                        $venTotal = $resumen->ventas ?? 0;
                    @endphp
                    <div class="bs-card overflow-hidden lg:col-span-2">
                        <div class="bs-card-header flex items-center justify-between flex-wrap gap-2">
                            <h3 class="bs-display text-lg text-gray-800 m-0"><i class="fas fa-map-marker-alt text-brand-500"></i> Distribución geográfica por región</h3>
                            <span class="text-xs text-gray-400" title="Meta no atribuye compras al breakdown 'region'. Las compras estimadas se prorratean según % de inversión por región.">
                                <i class="fas fa-info-circle"></i> Compras estimadas (prorrateadas)
                            </span>
                        </div>
                        <div class="bs-card-body">
                            {{-- Gráfico horizontal: Top 10 regiones por inversión vs ventas estimadas --}}
                            <div style="margin-bottom:20px;">
                                <div style="position:relative; height:{{ max(260, min(10, $demoRegion->count()) * 36) }}px;">
                                    <canvas id="chartRegiones"></canvas>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm" style="min-width:720px;">
                                    <thead>
                                        <tr class="text-left text-xs text-gray-500 uppercase border-b border-gray-100">
                                            <th class="py-2 pr-3">Región</th>
                                            <th class="py-2 px-3 text-right">% Inv</th>
                                            <th class="py-2 px-3 text-right">Inversión</th>
                                            <th class="py-2 px-3 text-right">Alcance</th>
                                            <th class="py-2 px-3 text-right">Impresiones</th>
                                            <th class="py-2 px-3 text-right">Clicks</th>
                                            <th class="py-2 px-3 text-right">CTR</th>
                                            <th class="py-2 px-3 text-right">CPM</th>
                                            <th class="py-2 px-3 text-right">Compras est.</th>
                                            <th class="py-2 px-3 text-right">Ventas est.</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @foreach($demoRegionOrdenadas->take(15) as $r)
                                        @php
                                            $pctInv = round($r->inversion / $totalInvRegion * 100, 1);
                                            $rCtr = $r->impresiones > 0 ? round($r->clicks / $r->impresiones * 100, 2) : 0;
                                            $rCpm = $r->impresiones > 0 ? round($r->inversion / $r->impresiones * 1000) : 0;
                                            $rComprasEst = round($compTotal * $r->inversion / $totalInvRegion);
                                            $rVentasEst = round($venTotal * $r->inversion / $totalInvRegion);
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-3 pr-3 font-medium text-gray-800">{{ $r->objeto_nombre }}</td>
                                            <td class="py-3 px-3 text-right">
                                                <span style="display:inline-block; padding:2px 8px; border-radius:999px; font-weight:700; font-size:0.7rem; background:#FFF7EC; color:#FF8100;">{{ $pctInv }}%</span>
                                            </td>
                                            <td class="py-3 px-3 text-right text-gray-600">{{ $fmt($r->inversion) }}</td>
                                            <td class="py-3 px-3 text-right text-gray-600">{{ number_format($r->alcance,0,',','.') }}</td>
                                            <td class="py-3 px-3 text-right text-gray-600">{{ number_format($r->impresiones,0,',','.') }}</td>
                                            <td class="py-3 px-3 text-right text-gray-600">{{ number_format($r->clicks,0,',','.') }}</td>
                                            <td class="py-3 px-3 text-right text-gray-600">{{ $rCtr }}%</td>
                                            <td class="py-3 px-3 text-right text-gray-600">{{ $fmt($rCpm) }}</td>
                                            <td class="py-3 px-3 text-right text-gray-600">~{{ $rComprasEst }}</td>
                                            <td class="py-3 px-3 text-right font-semibold" style="color:#059669;">~{{ $fmt($rVentasEst) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-xs text-gray-400 mt-3 text-center" style="line-height:1.5;">
                                <i class="fas fa-info-circle"></i> Meta no expone compras directamente por región (limitación de la API).
                                Las columnas <strong>Compras est.</strong> y <strong>Ventas est.</strong> se prorratean según el % de inversión de cada región sobre el total ({{ number_format($compTotal,0,',','.') }} compras, {{ $fmt($venTotal) }}).
                                @if($demoRegion->count() > 15) · Mostrando top 15 de {{ $demoRegion->count() }} regiones @endif
                            </p>
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                {{-- Tabla campañas + gráficos --}}
                @if($campanas->count() > 0)
                <div class="bs-card overflow-hidden">
                    <div class="bs-card-header"><h3 class="bs-display text-lg text-gray-800 m-0">Rendimiento por campaña</h3></div>
                    <div class="bs-card-body">
                        {{-- Gráficos: Inversión vs Ventas (arriba) y ROAS (abajo) --}}
                        <div style="margin-bottom:24px;">
                            <p class="text-xs text-gray-500 uppercase tracking-wide" style="margin-bottom:8px; font-weight:600; letter-spacing:0.05em;">
                                <i class="fas fa-chart-bar text-brand-500"></i> Inversión vs Ventas por campaña
                            </p>
                            <div style="position:relative; height:{{ max(220, $campanas->take(8)->count() * 42) }}px;">
                                <canvas id="chartCampInvVen"></canvas>
                            </div>
                        </div>
                        <div style="margin-bottom:24px;">
                            <p class="text-xs text-gray-500 uppercase tracking-wide" style="margin-bottom:8px; font-weight:600; letter-spacing:0.05em;">
                                <i class="fas fa-bullseye text-brand-500"></i> ROAS por campaña <span style="text-transform:none; color:#9CA3AF; font-weight:500;">— verde ≥ 3x · naranja 1.5–3x · rojo &lt; 1.5x</span>
                            </p>
                            <div style="position:relative; height:{{ max(180, $campanas->take(8)->count() * 36) }}px;">
                                <canvas id="chartCampRoas"></canvas>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm" style="min-width:620px;">
                                <thead>
                                    <tr class="text-left text-xs text-gray-500 uppercase border-b border-gray-100">
                                        <th class="py-2 pr-3">Campaña</th>
                                        <th class="py-2 px-3 text-right">Inversión</th>
                                        <th class="py-2 px-3 text-right">Ventas</th>
                                        <th class="py-2 px-3 text-center">Compras</th>
                                        <th class="py-2 px-3 text-right">ROAS</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($campanas as $c)
                                    @php $cRoas = $c->inversion > 0 ? round($c->ventas / $c->inversion, 2) : 0; @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 pr-3 font-medium text-gray-800">{{ $c->objeto_nombre }}</td>
                                        <td class="py-3 px-3 text-right text-gray-600">{{ $fmt($c->inversion) }}</td>
                                        <td class="py-3 px-3 text-right font-semibold" style="color:#059669;">{{ $fmt($c->ventas) }}</td>
                                        <td class="py-3 px-3 text-center text-gray-600">{{ $c->compras }}</td>
                                        <td class="py-3 px-3 text-right font-bold" style="color:{{ $cRoas>=3 ? '#059669' : ($cRoas>=1.5 ? '#D97706' : '#DC2626') }};">{{ $cRoas }}x</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ===== Envío automático mensual ===== --}}
                @php
                    $diasConfigurados = [];
                    if (!empty($cuenta->reporte_dias)) {
                        foreach (preg_split('/[\s,;]+/', strtolower($cuenta->reporte_dias), -1, PREG_SPLIT_NO_EMPTY) as $t) {
                            if (in_array($t, ['last','ultimo','último','fin'], true)) { $diasConfigurados[] = 'last'; }
                            elseif (ctype_digit($t) && (int)$t >= 1 && (int)$t <= 31) { $diasConfigurados[] = (int)$t; }
                        }
                    }
                    $emailsActuales = is_array($cuenta->reporte_emails) ? $cuenta->reporte_emails : [];
                @endphp
                <div class="bs-card overflow-hidden">
                    <div class="bs-card-header flex items-center justify-between flex-wrap gap-2">
                        <h3 class="bs-display text-lg text-gray-800 m-0">
                            <i class="fas fa-clock text-brand-500"></i> Envío automático del reporte
                        </h3>
                        @if($cuenta->reporte_ultimo_envio)
                            <span class="text-xs text-gray-500">
                                <i class="fas fa-history"></i> Último envío: {{ $cuenta->reporte_ultimo_envio->format('d/m/Y H:i') }}
                            </span>
                        @endif
                    </div>
                    <div class="bs-card-body">
                        <form method="POST" action="{{ route('agencia.reportes.conexion.envio', $cuenta) }}" id="form-auto-envio">
                            @csrf

                            {{-- Toggle activo --}}
                            <label style="display:flex; align-items:center; gap:12px; padding:14px; background:#FFF7EC; border-radius:10px; cursor:pointer; margin-bottom:18px;">
                                <input type="checkbox" name="reporte_activo" value="1" {{ $cuenta->reporte_activo ? 'checked' : '' }} style="width:18px; height:18px; cursor:pointer;">
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-800 m-0">Activar envío automático mensual</p>
                                    <p class="text-xs text-gray-500 m-0">Cuando esté activado, el sistema enviará el reporte del mes anterior a los correos configurados, en los días que indiques abajo.</p>
                                </div>
                            </label>

                            {{-- Selector de días --}}
                            <label class="bs-label">Días del mes en que se envía</label>
                            <input type="hidden" name="reporte_dias" id="reporte_dias_input" value="{{ $cuenta->reporte_dias ?? '' }}">
                            <div id="dias-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(46px, 1fr)); gap:6px; margin-bottom:8px;">
                                @for($d = 1; $d <= 31; $d++)
                                    @php $activo = in_array($d, $diasConfigurados, true); @endphp
                                    <button type="button" data-dia="{{ $d }}"
                                        class="dia-btn"
                                        style="padding:8px 0; border:1.5px solid {{ $activo ? '#FF8100' : '#E5E7EB' }}; border-radius:8px; background:{{ $activo ? '#FF8100' : '#fff' }}; color:{{ $activo ? '#fff' : '#374151' }}; font-weight:{{ $activo ? '700' : '500' }}; cursor:pointer; transition:all .15s;">
                                        {{ $d }}
                                    </button>
                                @endfor
                                @php $activoLast = in_array('last', $diasConfigurados, true); @endphp
                                <button type="button" data-dia="last"
                                    class="dia-btn"
                                    style="grid-column:span 2; padding:8px 4px; border:1.5px solid {{ $activoLast ? '#FF8100' : '#E5E7EB' }}; border-radius:8px; background:{{ $activoLast ? '#FF8100' : '#fff' }}; color:{{ $activoLast ? '#fff' : '#374151' }}; font-weight:{{ $activoLast ? '700' : '500' }}; cursor:pointer; font-size:0.78rem;">
                                    Último día
                                </button>
                            </div>
                            <p class="text-xs text-gray-500" style="margin:0 0 18px;">
                                <i class="fas fa-info-circle"></i> Días seleccionados: <strong id="dias-resumen">{{ empty($diasConfigurados) ? 'ninguno' : implode(', ', array_map(fn($d) => $d === 'last' ? 'Último día' : $d, $diasConfigurados)) }}</strong>.
                                Si seleccionas el 29, 30 o 31 y el mes no tiene esos días, se enviará el último día disponible.
                            </p>

                            {{-- Correos destinatarios --}}
                            <label class="bs-label">Correos destinatarios fijos</label>
                            <textarea name="reporte_emails" rows="2" class="bs-input" placeholder="correo1@cliente.com, correo2@cliente.com">{{ implode(', ', $emailsActuales) }}</textarea>
                            <p class="text-xs text-gray-500" style="margin:6px 0 16px;">Separar varios correos con coma. Estos se usan para el envío automático y también como destinatarios por defecto en los envíos manuales.</p>

                            <div style="display:flex; justify-content:flex-end; gap:8px;">
                                <button type="submit" class="bs-btn-primary">
                                    <i class="fas fa-save"></i> Guardar configuración
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                (function() {
                    const grid = document.getElementById('dias-grid');
                    const input = document.getElementById('reporte_dias_input');
                    const resumen = document.getElementById('dias-resumen');
                    if (!grid || !input) return;

                    function leerSeleccionados() {
                        return Array.from(grid.querySelectorAll('.dia-btn'))
                            .filter(b => b.style.background === 'rgb(255, 129, 0)' || b.dataset.activo === '1')
                            .map(b => b.dataset.dia);
                    }
                    function sync() {
                        const sel = leerSeleccionados();
                        input.value = sel.join(',');
                        if (sel.length === 0) {
                            resumen.textContent = 'ninguno';
                        } else {
                            const labels = sel.map(d => d === 'last' ? 'Último día' : d);
                            // Ordenar numéricamente, "last" al final
                            labels.sort((a, b) => {
                                if (a === 'Último día') return 1;
                                if (b === 'Último día') return -1;
                                return parseInt(a) - parseInt(b);
                            });
                            resumen.textContent = labels.join(', ');
                        }
                    }
                    grid.querySelectorAll('.dia-btn').forEach(btn => {
                        // Marca estado inicial via data-activo para evitar parsing del color string
                        btn.dataset.activo = (btn.style.background === 'rgb(255, 129, 0)') ? '1' : '0';
                        btn.addEventListener('click', () => {
                            const activo = btn.dataset.activo === '1';
                            btn.dataset.activo = activo ? '0' : '1';
                            btn.style.background = activo ? '#fff' : '#FF8100';
                            btn.style.color = activo ? '#374151' : '#fff';
                            btn.style.borderColor = activo ? '#E5E7EB' : '#FF8100';
                            btn.style.fontWeight = activo ? '500' : '700';
                            sync();
                        });
                    });
                    sync();
                })();
                </script>

                <p class="text-xs text-gray-400 text-center">Datos sincronizados desde Meta Ads · última actualización {{ $cuenta->ultima_sync_at ? $cuenta->ultima_sync_at->format('d/m/Y H:i') : '—' }}</p>

                {{-- MODAL: Enviar reporte --}}
                @php
                    $savedEmails = is_array($cuenta->reporte_emails) ? $cuenta->reporte_emails : [];
                    if (empty($savedEmails) && $cuenta->cliente && filter_var($cuenta->cliente->email, FILTER_VALIDATE_EMAIL)) {
                        $savedEmails = [$cuenta->cliente->email];
                    }
                @endphp
                <dialog id="modal-envio" style="border:none; border-radius:16px; padding:0; max-width:560px; width:92%; box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                    <form method="POST" action="{{ route('agencia.reportes.conexion.enviar-ahora', $cuenta) }}" style="padding:28px;" id="form-envio-reporte">
                        @csrf
                        <input type="hidden" name="periodo" value="{{ $periodo }}">
                        {{-- hidden con los emails finales (lo llena el JS al enviar) --}}
                        <input type="hidden" name="emails" id="envio-emails-final">

                        <div class="flex items-center justify-between" style="margin-bottom:6px;">
                            <h3 class="bs-display text-xl text-gray-800 m-0">Enviar reporte por correo</h3>
                            <button type="button" onclick="document.getElementById('modal-envio').close()" style="background:transparent; border:none; font-size:20px; color:#9CA3AF; cursor:pointer; line-height:1;">×</button>
                        </div>
                        <p class="text-sm text-gray-500" style="margin:0 0 18px;">
                            Reporte de <strong>{{ ucfirst($mesNombre) }}</strong> de <strong>{{ $cuenta->cliente->nombre ?? $cuenta->nombre_cuenta }}</strong>.
                        </p>

                        <label class="bs-label">Destinatarios</label>
                        <div id="envio-chips" style="display:flex; flex-wrap:wrap; gap:6px; padding:10px; border:1.5px solid #E5E7EB; border-radius:10px; min-height:48px; background:#FAFAFA;"></div>
                        <p class="text-xs text-gray-400" style="margin:6px 0 0;">
                            <i class="fas fa-info-circle"></i> Haz clic en la <strong>×</strong> para quitar un correo.
                        </p>

                        <div style="margin-top:14px;">
                            <label class="bs-label">Agregar otro correo</label>
                            <div style="display:flex; gap:8px;">
                                <input type="email" id="envio-nuevo-email" class="bs-input" placeholder="cliente@ejemplo.com" style="flex:1;" autocomplete="off">
                                <button type="button" id="envio-btn-agregar" class="bs-btn-secondary" style="white-space:nowrap;">
                                    <i class="fas fa-plus"></i> Agregar
                                </button>
                            </div>
                            <p class="text-xs text-red-500" id="envio-error" style="margin:6px 0 0; display:none;"></p>
                        </div>

                        <label style="display:flex; align-items:center; gap:8px; margin-top:18px; padding:10px 12px; background:#FFF7EC; border-radius:10px; cursor:pointer;">
                            <input type="checkbox" name="guardar_emails" value="1" id="envio-guardar" checked style="width:16px; height:16px; cursor:pointer;">
                            <span class="text-sm text-gray-700">
                                <i class="fas fa-save text-brand-500"></i>
                                Guardar estos correos como destinatarios fijos de esta cuenta
                            </span>
                        </label>

                        <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:22px; border-top:1px solid #F3F4F6; padding-top:18px;">
                            <button type="button" onclick="document.getElementById('modal-envio').close()" class="bs-btn-secondary">Cancelar</button>
                            <button type="submit" class="bs-btn-primary" style="background:#10B981;" id="envio-btn-enviar">
                                <i class="fas fa-paper-plane"></i> Enviar ahora
                            </button>
                        </div>
                    </form>
                </dialog>

                <script>
                (function() {
                    const emailsIniciales = @json(array_values($savedEmails));
                    let emails = [...emailsIniciales];

                    const $chips = document.getElementById('envio-chips');
                    const $input = document.getElementById('envio-nuevo-email');
                    const $btnAdd = document.getElementById('envio-btn-agregar');
                    const $btnSend = document.getElementById('envio-btn-enviar');
                    const $hidden = document.getElementById('envio-emails-final');
                    const $err = document.getElementById('envio-error');
                    const $form = document.getElementById('form-envio-reporte');

                    const isValid = (e) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e);

                    function render() {
                        $chips.innerHTML = '';
                        if (emails.length === 0) {
                            $chips.innerHTML = '<span style="color:#9CA3AF; font-size:0.85rem; padding:4px;">Sin destinatarios. Agrega uno abajo.</span>';
                            $btnSend.disabled = true;
                            $btnSend.style.opacity = '0.5';
                        } else {
                            $btnSend.disabled = false;
                            $btnSend.style.opacity = '1';
                            emails.forEach((e, i) => {
                                const chip = document.createElement('span');
                                chip.style.cssText = 'display:inline-flex; align-items:center; gap:6px; background:#FFFFFF; border:1px solid #FED7AA; color:#9A3412; padding:5px 10px; border-radius:999px; font-size:0.82rem; font-weight:500;';
                                chip.innerHTML = '<i class="fas fa-envelope" style="font-size:0.7rem; color:#FF8100;"></i>' + e + '<button type="button" style="background:none; border:none; color:#FF8100; cursor:pointer; padding:0; line-height:1; font-size:14px;">×</button>';
                                chip.querySelector('button').onclick = () => { emails.splice(i,1); render(); };
                                $chips.appendChild(chip);
                            });
                        }
                    }

                    function agregar() {
                        $err.style.display = 'none';
                        const v = $input.value.trim().toLowerCase();
                        if (!v) return;
                        if (!isValid(v)) { $err.textContent = 'El correo no es válido.'; $err.style.display = 'block'; return; }
                        if (emails.includes(v)) { $err.textContent = 'Ese correo ya está en la lista.'; $err.style.display = 'block'; return; }
                        emails.push(v);
                        $input.value = '';
                        render();
                    }

                    $btnAdd.addEventListener('click', agregar);
                    $input.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' || e.key === ',' || e.key === ';') { e.preventDefault(); agregar(); }
                    });

                    $form.addEventListener('submit', (e) => {
                        // Si el usuario tipeó algo y no apretó Agregar, lo intentamos sumar antes de enviar
                        if ($input.value.trim()) agregar();
                        if (emails.length === 0) { e.preventDefault(); $err.textContent = 'Agrega al menos un correo.'; $err.style.display = 'block'; return; }
                        $hidden.value = emails.join(',');
                    });

                    render();
                })();
                </script>

                <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
                <script>
                    @if($campanas->count() > 0)
                    new Chart(document.getElementById('chartDona'), {
                        type: 'doughnut',
                        data: { labels: @json($campanas->pluck('objeto_nombre')),
                            datasets: [{ data: @json($campanas->pluck('inversion')),
                                backgroundColor: ['#FF8100','#FF9C00','#FFC800','#FCD34D','#FDE68A','#FEF3C7'] }] },
                        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } },
                            tooltip: { callbacks: { label: c => c.label + ': $' + c.raw.toLocaleString('es-CL') } } } }
                    });
                    @endif

                    @if($demoGenero->count() > 0)
                    new Chart(document.getElementById('chartGenero'), {
                        type: 'pie',
                        data: {
                            labels: @json($demoGenero->map(fn($g) => $generoLabels[$g->objeto_nombre] ?? ucfirst($g->objeto_nombre))->values()),
                            datasets: [{
                                data: @json($demoGenero->pluck('inversion')->values()),
                                backgroundColor: ['#FF8100','#FFC800','#9CA3AF']
                            }]
                        },
                        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } },
                            tooltip: { callbacks: { label: c => c.label + ': $' + c.raw.toLocaleString('es-CL') } } } }
                    });
                    @endif

                    {{-- ===== Gráfico regiones: top 10 inv vs ventas est ===== --}}
                    @if($demoRegion->count() > 0)
                    @php
                        $topReg = $demoRegionOrdenadas->take(10);
                        $totInv = max(1, $demoRegion->sum('inversion'));
                        $vTot = $resumen->ventas ?? 0;
                    @endphp
                    new Chart(document.getElementById('chartRegiones'), {
                        type: 'bar',
                        data: {
                            labels: @json($topReg->pluck('objeto_nombre')->values()),
                            datasets: [
                                {
                                    label: 'Inversión',
                                    data: @json($topReg->pluck('inversion')->values()),
                                    backgroundColor: '#FF8100',
                                    borderRadius: 6,
                                },
                                {
                                    label: 'Ventas estimadas',
                                    data: @json($topReg->map(fn($r) => round($vTot * $r->inversion / $totInv))->values()),
                                    backgroundColor: '#10B981',
                                    borderRadius: 6,
                                }
                            ]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom', labels: { font: { size: 11 } } },
                                tooltip: { callbacks: { label: c => c.dataset.label + ': $' + c.raw.toLocaleString('es-CL') } }
                            },
                            scales: {
                                x: { ticks: { callback: v => '$' + (v/1000).toFixed(0) + 'k', font: { size: 10 } }, grid: { color: '#F3F4F6' } },
                                y: { ticks: { font: { size: 11 }, autoSkip: false }, grid: { display: false } }
                            }
                        }
                    });
                    @endif

                    {{-- ===== Gráficos campañas: Inv vs Ven + ROAS ===== --}}
                    @if($campanas->count() > 0)
                    @php
                        $campChart = $campanas->take(8);
                        $roasData = $campChart->map(fn($c) => $c->inversion > 0 ? round($c->ventas / $c->inversion, 2) : 0)->values();
                        $labelsFull = $campChart->pluck('objeto_nombre')->values();
                    @endphp
                    const labelsFullCamp = @json($labelsFull);
                    const labelsCortosCamp = labelsFullCamp.map(l => l.length > 32 ? l.substring(0, 30) + '…' : l);

                    new Chart(document.getElementById('chartCampInvVen'), {
                        type: 'bar',
                        data: {
                            labels: labelsCortosCamp,
                            datasets: [
                                { label: 'Inversión', data: @json($campChart->pluck('inversion')->values()), backgroundColor: '#FF8100', borderRadius: 6, barPercentage: 0.8 },
                                { label: 'Ventas', data: @json($campChart->pluck('ventas')->values()), backgroundColor: '#10B981', borderRadius: 6, barPercentage: 0.8 }
                            ]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: { padding: { right: 12 } },
                            plugins: {
                                legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 14, padding: 14 } },
                                tooltip: {
                                    callbacks: {
                                        title: items => labelsFullCamp[items[0].dataIndex],
                                        label: c => c.dataset.label + ': $' + c.raw.toLocaleString('es-CL')
                                    }
                                }
                            },
                            scales: {
                                x: { ticks: { callback: v => v >= 1000000 ? '$' + (v/1000000).toFixed(1) + 'M' : '$' + (v/1000).toFixed(0) + 'k', font: { size: 10 } }, grid: { color: '#F3F4F6' } },
                                y: { ticks: { font: { size: 11 }, autoSkip: false }, grid: { display: false } }
                            }
                        }
                    });

                    new Chart(document.getElementById('chartCampRoas'), {
                        type: 'bar',
                        data: {
                            labels: labelsCortosCamp,
                            datasets: [{
                                label: 'ROAS',
                                data: @json($roasData),
                                backgroundColor: @json($roasData->map(fn($r) => $r >= 3 ? '#059669' : ($r >= 1.5 ? '#D97706' : '#DC2626'))->values()),
                                borderRadius: 6,
                                barPercentage: 0.7,
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: { padding: { right: 30 } },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        title: items => labelsFullCamp[items[0].dataIndex],
                                        label: c => 'ROAS: ' + c.raw + 'x'
                                    }
                                }
                            },
                            scales: {
                                x: { ticks: { callback: v => v + 'x', font: { size: 10 } }, grid: { color: '#F3F4F6' }, suggestedMin: 0 },
                                y: { ticks: { font: { size: 11 }, autoSkip: false }, grid: { display: false } }
                            }
                        }
                    });
                    @endif
                </script>
            @endif
        </div>
    </div>
</x-app-layout>
