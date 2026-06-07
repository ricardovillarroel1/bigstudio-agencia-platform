<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Performance — {{ $cuenta->cliente->nombre ?? $cuenta->nombre_cuenta }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <style>
        body { background:#f3f4f6; font-family: system-ui, -apple-system, sans-serif; }
        .card { background:#fff; border-radius:1rem; box-shadow:0 1px 3px rgba(0,0,0,.08); }
    </style>
</head>
<body class="py-6 px-4">
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
        try { $mesNombre = ($usaRango ?? false) ? (\Carbon\Carbon::parse($desde)->locale('es')->isoFormat('D MMM') . ' al ' . \Carbon\Carbon::parse($hasta)->locale('es')->isoFormat('D MMM YYYY')) : \Carbon\Carbon::createFromFormat('Y-m', $periodo)->locale('es')->isoFormat('MMMM YYYY'); }
        catch (\Throwable $e) { $mesNombre = $periodo; }
    @endphp

    <div class="max-w-5xl mx-auto space-y-5">

        {{-- Header BigStudio --}}
        <div class="card overflow-hidden">
            <div class="px-8 py-6 flex items-center justify-between flex-wrap gap-4" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                <div class="flex items-center gap-4">
                    <img src="{{ asset('images/bigstudio-logo-dark.png') }}" alt="Big Studio" style="height:42px;" onerror="this.style.display='none'">
                    <div>
                        <p class="text-xs text-white/90 m-0 font-bold uppercase tracking-wider">Reporte de Performance</p>
                        <h1 class="text-2xl font-extrabold text-white m-0 leading-tight">{{ $cuenta->cliente->nombre ?? $cuenta->nombre_cuenta }}</h1>
                        <p class="text-sm text-white/90 mt-1 mb-0">{{ ucfirst($mesNombre) }} · Meta Ads</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xs text-white/80 m-0 uppercase">Retorno (ROAS)</p>
                    <p class="text-4xl font-extrabold text-white m-0 leading-none">{{ $roas }}x</p>
                </div>
            </div>
        </div>

        @if(!$resumen)
            <div class="card p-12 text-center text-gray-500">
                Aún no hay datos disponibles para este período.
            </div>
        @else
        {{-- KPIs --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="card p-5">
                <p class="text-xs text-gray-500 uppercase m-0">💰 Inversión</p>
                <p class="text-2xl font-extrabold text-gray-900 mt-1 mb-0">{{ $fmt($inv) }}</p>
            </div>
            <div class="card p-5">
                <p class="text-xs text-gray-500 uppercase m-0">🛒 Ventas generadas</p>
                <p class="text-2xl font-extrabold mt-1 mb-0" style="color:#059669;">{{ $fmt($ven) }}</p>
            </div>
            <div class="card p-5">
                <p class="text-xs text-gray-500 uppercase m-0">🎯 Compras</p>
                <p class="text-2xl font-extrabold text-gray-900 mt-1 mb-0">{{ number_format($comp,0,',','.') }}</p>
                <p class="text-xs text-gray-500 mt-1 mb-0">Costo x compra: {{ $fmt($cpa) }}</p>
            </div>
            <div class="card p-5">
                <p class="text-xs text-gray-500 uppercase m-0">👁️ Alcance</p>
                <p class="text-2xl font-extrabold text-gray-900 mt-1 mb-0">{{ number_format($alc,0,',','.') }}</p>
                <p class="text-xs text-gray-500 mt-1 mb-0">{{ number_format($imp,0,',','.') }} impresiones</p>
            </div>
        </div>

        {{-- Embudo --}}
        <div class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100"><h3 class="text-lg font-bold text-gray-800 m-0">Embudo de conversión</h3></div>
            <div class="p-6">
                @php
                    $etapas = [['Alcance',$alc,'#FFC800'],['Impresiones',$imp,'#FF9C00'],['Clicks',$clk,'#FF8100'],['Compras',$comp,'#10B981']];
                    $maxEt = max(1,$alc,$imp,$clk,$comp);
                @endphp
                <div class="flex flex-col gap-3">
                    @foreach($etapas as $i => $et)
                        @php $ancho = max(6, round($et[1]/$maxEt*100)); $conv = ($i>0 && $etapas[$i-1][1]>0) ? round($et[1]/$etapas[$i-1][1]*100,1) : null; @endphp
                        <div class="flex items-center gap-3">
                            <div class="w-24 text-sm text-gray-600 text-right shrink-0">{{ $et[0] }}</div>
                            <div class="flex-1 bg-gray-100 rounded-lg overflow-hidden">
                                <div class="py-2 px-3 rounded-lg flex items-center justify-end" style="width:{{ $ancho }}%; min-width:60px; background:{{ $et[2] }};">
                                    <span class="text-white font-bold text-sm">{{ number_format($et[1],0,',','.') }}</span>
                                </div>
                            </div>
                            <div class="w-16 text-sm font-semibold shrink-0" style="color:#059669;">@if($conv!==null)↓ {{ $conv }}%@endif</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ===== DEMOGRAFICOS ===== --}}
        @php
            $generoLabels = ['male' => 'Hombres', 'female' => 'Mujeres', 'unknown' => 'Sin determinar'];
            $hasDemo = ($demoEdad ?? collect())->count() > 0 || ($demoGenero ?? collect())->count() > 0 || ($demoRegion ?? collect())->count() > 0;
        @endphp
        @if($hasDemo)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            {{-- Edad --}}
            @if(($demoEdad ?? collect())->count() > 0)
            <div class="card overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 m-0">Performance por edad</h3>
                </div>
                <div class="p-6">
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
            @if(($demoGenero ?? collect())->count() > 0)
            <div class="card overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 m-0">Performance por género</h3>
                </div>
                <div class="p-6">
                    <canvas id="chartGeneroPub" height="170"></canvas>
                    <div class="grid grid-cols-{{ min(3, $demoGenero->count()) }} gap-2 mt-3 text-center">
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

            {{-- Región --}}
            @if(($demoRegion ?? collect())->count() > 0)
            @php
                $demoRegionOrd = $demoRegion->sortByDesc('inversion')->values();
                $totalInvRegion = max(1, $demoRegion->sum('inversion'));
                $compTotal = $resumen->compras ?? 0;
                $venTotal = $resumen->ventas ?? 0;
            @endphp
            <div class="card overflow-hidden lg:col-span-2">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
                    <h3 class="text-lg font-bold text-gray-800 m-0">Distribución geográfica por región</h3>
                    <span class="text-xs text-gray-400">Compras estimadas (prorrateadas por % inversión)</span>
                </div>
                <div class="p-6">
                    {{-- Gráfico top 10 regiones inv vs ventas est --}}
                    <div style="margin-bottom:20px; height:280px;">
                        <canvas id="chartRegionesPub"></canvas>
                    </div>
                    <div class="overflow-x-auto">
                    <table class="w-full text-sm" style="min-width:680px;">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase border-b border-gray-100">
                                <th class="py-2 pr-3">Región</th>
                                <th class="py-2 px-3 text-right">% Inv</th>
                                <th class="py-2 px-3 text-right">Inversión</th>
                                <th class="py-2 px-3 text-right">Alcance</th>
                                <th class="py-2 px-3 text-right">Clicks</th>
                                <th class="py-2 px-3 text-right">CTR</th>
                                <th class="py-2 px-3 text-right">Compras est.</th>
                                <th class="py-2 px-3 text-right">Ventas est.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($demoRegionOrd->take(15) as $r)
                            @php
                                $pctInv = round($r->inversion / $totalInvRegion * 100, 1);
                                $rCtr = $r->impresiones > 0 ? round($r->clicks / $r->impresiones * 100, 2) : 0;
                                $rComp = round($compTotal * $r->inversion / $totalInvRegion);
                                $rVen = round($venTotal * $r->inversion / $totalInvRegion);
                            @endphp
                            <tr>
                                <td class="py-3 pr-3 font-medium text-gray-800">{{ $r->objeto_nombre }}</td>
                                <td class="py-3 px-3 text-right"><span style="display:inline-block; padding:2px 8px; border-radius:999px; font-weight:700; font-size:0.7rem; background:#FFF7EC; color:#FF8100;">{{ $pctInv }}%</span></td>
                                <td class="py-3 px-3 text-right text-gray-600">{{ $fmt($r->inversion) }}</td>
                                <td class="py-3 px-3 text-right text-gray-600">{{ number_format($r->alcance,0,',','.') }}</td>
                                <td class="py-3 px-3 text-right text-gray-600">{{ number_format($r->clicks,0,',','.') }}</td>
                                <td class="py-3 px-3 text-right text-gray-600">{{ $rCtr }}%</td>
                                <td class="py-3 px-3 text-right text-gray-600">~{{ $rComp }}</td>
                                <td class="py-3 px-3 text-right font-semibold" style="color:#059669;">~{{ $fmt($rVen) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($demoRegion->count() > 15)
                    <p class="text-xs text-gray-400 mt-3 text-center">Mostrando top 15 de {{ $demoRegion->count() }} regiones</p>
                    @endif
                    </div>
                </div>
            </div>
            @endif
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Chart === 'undefined') return;

            @if(($demoGenero ?? collect())->count() > 0)
            var ctxG = document.getElementById('chartGeneroPub');
            if (ctxG) new Chart(ctxG, {
                type: 'pie',
                data: {
                    labels: @json($demoGenero->map(fn($g) => $generoLabels[$g->objeto_nombre] ?? ucfirst($g->objeto_nombre))->values()),
                    datasets: [{ data: @json($demoGenero->pluck('inversion')->values()),
                        backgroundColor: ['#FF8100','#FFC800','#9CA3AF'] }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } },
                    tooltip: { callbacks: { label: c => c.label + ': $' + c.raw.toLocaleString('es-CL') } } } }
            });
            @endif

            @if(($demoRegion ?? collect())->count() > 0)
            @php
                $topRegPub = $demoRegionOrd->take(10);
                $totInvPub = max(1, $demoRegion->sum('inversion'));
                $vTotPub = $resumen->ventas ?? 0;
            @endphp
            var ctxR = document.getElementById('chartRegionesPub');
            if (ctxR) new Chart(ctxR, {
                type: 'bar',
                data: {
                    labels: @json($topRegPub->pluck('objeto_nombre')->values()),
                    datasets: [
                        { label: 'Inversión', data: @json($topRegPub->pluck('inversion')->values()), backgroundColor: '#FF8100', borderRadius: 6 },
                        { label: 'Ventas estimadas', data: @json($topRegPub->map(fn($r) => round($vTotPub * $r->inversion / $totInvPub))->values()), backgroundColor: '#10B981', borderRadius: 6 }
                    ]
                },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } },
                        tooltip: { callbacks: { label: c => c.dataset.label + ': $' + c.raw.toLocaleString('es-CL') } } },
                    scales: {
                        x: { ticks: { callback: v => '$' + (v/1000).toFixed(0) + 'k', font: { size: 10 } }, grid: { color: '#F3F4F6' } },
                        y: { ticks: { font: { size: 10 }, autoSkip: false }, grid: { display: false } }
                    }
                }
            });
            @endif
        });
        </script>
        @endif

        {{-- Campañas --}}
        @if($campanas->count() > 0)
        <div class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100"><h3 class="text-lg font-bold text-gray-800 m-0">Rendimiento por campaña</h3></div>
            <div class="p-6">
                {{-- Gráficos Inv vs Ven (arriba) + ROAS (abajo) --}}
                <div style="margin-bottom:24px;">
                    <p class="text-xs text-gray-500 uppercase tracking-wide" style="margin-bottom:8px; font-weight:600; letter-spacing:0.05em;">Inversión vs Ventas por campaña</p>
                    <div style="position:relative; height:{{ max(220, $campanas->take(8)->count() * 42) }}px;">
                        <canvas id="chartCampInvVenPub"></canvas>
                    </div>
                </div>
                <div style="margin-bottom:24px;">
                    <p class="text-xs text-gray-500 uppercase tracking-wide" style="margin-bottom:8px; font-weight:600; letter-spacing:0.05em;">
                        ROAS por campaña <span style="text-transform:none; color:#9CA3AF; font-weight:500;">— verde ≥ 3x · naranja 1.5–3x · rojo &lt; 1.5x</span>
                    </p>
                    <div style="position:relative; height:{{ max(180, $campanas->take(8)->count() * 36) }}px;">
                        <canvas id="chartCampRoasPub"></canvas>
                    </div>
                </div>
                <div class="overflow-x-auto">
                <table class="w-full text-sm" style="min-width:560px;">
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
                        @php $cRoas = $c->inversion>0 ? round($c->ventas/$c->inversion,2) : 0; @endphp
                        <tr>
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

        {{-- Scripts charts campañas público --}}
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Chart === 'undefined') return;
            @php
                $campChartPub = $campanas->take(8);
                $roasDataPub = $campChartPub->map(fn($c) => $c->inversion > 0 ? round($c->ventas / $c->inversion, 2) : 0)->values();
            @endphp
            var labelsFullPub = @json($campChartPub->pluck('objeto_nombre')->values());
            var labelsCortPub = labelsFullPub.map(l => l.length > 32 ? l.substring(0, 30) + '…' : l);

            var ctxIV = document.getElementById('chartCampInvVenPub');
            if (ctxIV) new Chart(ctxIV, {
                type: 'bar',
                data: {
                    labels: labelsCortPub,
                    datasets: [
                        { label: 'Inversión', data: @json($campChartPub->pluck('inversion')->values()), backgroundColor: '#FF8100', borderRadius: 6, barPercentage: 0.8 },
                        { label: 'Ventas', data: @json($campChartPub->pluck('ventas')->values()), backgroundColor: '#10B981', borderRadius: 6, barPercentage: 0.8 }
                    ]
                },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    layout: { padding: { right: 12 } },
                    plugins: {
                        legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 14, padding: 14 } },
                        tooltip: { callbacks: {
                            title: items => labelsFullPub[items[0].dataIndex],
                            label: c => c.dataset.label + ': $' + c.raw.toLocaleString('es-CL')
                        } }
                    },
                    scales: {
                        x: { ticks: { callback: v => v >= 1000000 ? '$' + (v/1000000).toFixed(1) + 'M' : '$' + (v/1000).toFixed(0) + 'k', font: { size: 10 } }, grid: { color: '#F3F4F6' } },
                        y: { ticks: { font: { size: 11 }, autoSkip: false }, grid: { display: false } }
                    }
                }
            });

            var ctxRo = document.getElementById('chartCampRoasPub');
            if (ctxRo) new Chart(ctxRo, {
                type: 'bar',
                data: {
                    labels: labelsCortPub,
                    datasets: [{
                        label: 'ROAS',
                        data: @json($roasDataPub),
                        backgroundColor: @json($roasDataPub->map(fn($r) => $r >= 3 ? '#059669' : ($r >= 1.5 ? '#D97706' : '#DC2626'))->values()),
                        borderRadius: 6,
                        barPercentage: 0.7,
                    }]
                },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    layout: { padding: { right: 30 } },
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: {
                            title: items => labelsFullPub[items[0].dataIndex],
                            label: c => 'ROAS: ' + c.raw + 'x'
                        } }
                    },
                    scales: {
                        x: { ticks: { callback: v => v + 'x', font: { size: 10 } }, grid: { color: '#F3F4F6' }, suggestedMin: 0 },
                        y: { ticks: { font: { size: 11 }, autoSkip: false }, grid: { display: false } }
                    }
                }
            });
        });
        </script>
        @endif
        @endif {{-- cierra @else de @if(!$resumen) --}}

        <p class="text-center text-xs text-gray-400 py-4">
            Reporte generado por <strong>Big Studio</strong> · Agencia de Marketing Digital<br>
            Datos obtenidos directamente de Meta Ads
        </p>
    </div>
</body>
</html>
