<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-bold text-gray-800 leading-tight">
            <span class="text-brand-600">Reporte</span> de Performance — Meta Ads
        </h2>
    </x-slot>

    @php
        // ====== DATOS DE EJEMPLO (prototipo). En produccion vienen de la Meta Marketing API ======
        $cliente = 'Botas Militares Chile';
        $periodo = 'Mayo 2026';
        $moneda = 'CLP';
        // KPIs del mes
        $inversion = 1850000;
        $ventas = 7215000;
        $roas = round($ventas / $inversion, 2);
        $compras = 412;
        $cpa = round($inversion / $compras);
        $alcance = 184300;
        $impresiones = 612400;
        $clicks = 14820;
        $ctr = round($clicks / $impresiones * 100, 2);
        $cpc = round($inversion / $clicks);
        // Comparativa mes anterior (para flechas)
        $roasPrev = 3.4; $invPrev = 1620000; $ventasPrev = 5508000; $cpaPrev = 5100;
        // Serie diaria (gasto vs ventas)
        $dias = [];
        for ($d = 1; $d <= 31; $d++) { $dias[] = $d; }
        $serieGasto = [42,51,48,60,55,70,68,59,62,75,80,72,66,71,90,95,88,76,69,82,99,110,105,92,88,101,120,115,98,90,103];
        $serieGasto = array_map(fn($v) => $v * 1000, $serieGasto);
        $serieVentas = [150,190,170,240,210,300,280,230,250,320,360,310,270,300,410,450,400,330,300,360,470,540,510,420,400,490,600,560,470,430,500];
        $serieVentas = array_map(fn($v) => $v * 1000, $serieVentas);
        // Campañas activas
        $campanas = [
            ['nombre' => 'Conversiones — Botas Tácticas', 'inv' => 720000, 'ventas' => 3240000, 'compras' => 168, 'estado' => 'activa'],
            ['nombre' => 'Retargeting — Carrito abandonado', 'inv' => 310000, 'ventas' => 1890000, 'compras' => 122, 'estado' => 'activa'],
            ['nombre' => 'Prospecting — Público frío', 'inv' => 540000, 'ventas' => 1350000, 'compras' => 78, 'estado' => 'activa'],
            ['nombre' => 'Catálogo — DPA', 'inv' => 180000, 'ventas' => 540000, 'compras' => 32, 'estado' => 'activa'],
            ['nombre' => 'Reconocimiento — Video', 'inv' => 100000, 'ventas' => 195000, 'compras' => 12, 'estado' => 'pausada'],
        ];
        foreach ($campanas as &$c) { $c['roas'] = round($c['ventas'] / max($c['inv'],1), 2); }
        unset($c);
        usort($campanas, fn($a,$b) => $b['roas'] <=> $a['roas']);
        // Top anuncios
        $anuncios = [
            ['nombre' => 'Carrusel — Bototos negros oferta', 'roas' => 6.8, 'ventas' => 1480000, 'ctr' => 3.1, 'thumb' => '🥾'],
            ['nombre' => 'Video — Resistencia al agua', 'roas' => 5.2, 'ventas' => 980000, 'ctr' => 2.7, 'thumb' => '🎥'],
            ['nombre' => 'Imagen — Testimonios reales', 'roas' => 4.9, 'ventas' => 760000, 'ctr' => 2.4, 'thumb' => '⭐'],
            ['nombre' => 'Colección — Nueva temporada', 'roas' => 4.1, 'ventas' => 620000, 'ctr' => 2.0, 'thumb' => '🛍️'],
        ];
        $fmt = fn($n) => '$' . number_format($n, 0, ',', '.');
        $pct = fn($now, $prev) => $prev > 0 ? round(($now - $prev) / $prev * 100) : 0;
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Aviso prototipo --}}
            <div class="rounded-xl px-4 py-3 text-sm" style="background:#FEF3C7; border:1px solid #FCD34D; color:#92400E;">
                <i class="fas fa-flask"></i> <strong>Vista previa / prototipo</strong> — datos de ejemplo. Así se vería el reporte dinámico cuando conectemos la cuenta real de Meta Ads.
            </div>

            {{-- Hero --}}
            <div class="bs-card overflow-hidden">
                <div class="px-8 py-6 flex items-center justify-between flex-wrap gap-4" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                    <div>
                        <p class="text-sm text-white/90 m-0 font-semibold uppercase tracking-wide">Reporte mensual</p>
                        <h3 class="bs-display text-2xl text-white m-0 leading-tight">{{ $cliente }}</h3>
                        <p class="text-sm text-white/90 mt-1 mb-0"><i class="far fa-calendar"></i> {{ $periodo }} · Meta Ads (Facebook + Instagram)</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-white/80 m-0 uppercase">Retorno (ROAS)</p>
                        <p class="bs-display text-4xl text-white m-0 leading-none">{{ $roas }}x</p>
                        <p class="text-xs text-white/90 mt-1 mb-0">
                            @if($pct($roas,$roasPrev) >= 0)<i class="fas fa-arrow-up"></i> +{{ $pct($roas,$roasPrev) }}%@else<i class="fas fa-arrow-down"></i> {{ $pct($roas,$roasPrev) }}%@endif vs mes anterior
                        </p>
                    </div>
                </div>
            </div>

            {{-- KPIs principales --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bs-card p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide m-0"><i class="fas fa-coins text-brand-500"></i> Inversión</p>
                    <p class="bs-display text-2xl text-gray-900 mt-1 mb-0">{{ $fmt($inversion) }}</p>
                    <p class="text-xs mt-1 mb-0" style="color:{{ $pct($inversion,$invPrev)>=0 ? '#059669':'#DC2626' }};">
                        {{ $pct($inversion,$invPrev)>=0?'▲':'▼' }} {{ abs($pct($inversion,$invPrev)) }}% vs mes anterior
                    </p>
                </div>
                <div class="bs-card p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide m-0"><i class="fas fa-shopping-cart text-brand-500"></i> Ventas generadas</p>
                    <p class="bs-display text-2xl mt-1 mb-0" style="color:#059669;">{{ $fmt($ventas) }}</p>
                    <p class="text-xs mt-1 mb-0" style="color:#059669;">▲ {{ $pct($ventas,$ventasPrev) }}% vs mes anterior</p>
                </div>
                <div class="bs-card p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide m-0"><i class="fas fa-bullseye text-brand-500"></i> Compras</p>
                    <p class="bs-display text-2xl text-gray-900 mt-1 mb-0">{{ $compras }}</p>
                    <p class="text-xs text-gray-500 mt-1 mb-0">Costo x compra: {{ $fmt($cpa) }}</p>
                </div>
                <div class="bs-card p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide m-0"><i class="fas fa-eye text-brand-500"></i> Alcance</p>
                    <p class="bs-display text-2xl text-gray-900 mt-1 mb-0">{{ number_format($alcance,0,',','.') }}</p>
                    <p class="text-xs text-gray-500 mt-1 mb-0">{{ number_format($impresiones,0,',','.') }} impresiones</p>
                </div>
            </div>

            {{-- Gráfico: inversión vs ventas por día --}}
            <div class="bs-card overflow-hidden">
                <div class="bs-card-header flex items-center justify-between">
                    <h3 class="bs-display text-lg text-gray-800 m-0">Inversión vs. Ventas por día</h3>
                    <span class="text-xs text-gray-400">{{ $periodo }}</span>
                </div>
                <div class="bs-card-body">
                    <canvas id="chartLinea" height="90"></canvas>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Embudo --}}
                <div class="bs-card overflow-hidden">
                    <div class="bs-card-header"><h3 class="bs-display text-lg text-gray-800 m-0">Embudo de conversión</h3></div>
                    <div class="bs-card-body"><canvas id="chartEmbudo" height="170"></canvas></div>
                </div>
                {{-- Distribución de inversión por campaña --}}
                <div class="bs-card overflow-hidden">
                    <div class="bs-card-header"><h3 class="bs-display text-lg text-gray-800 m-0">¿Dónde se invirtió?</h3></div>
                    <div class="bs-card-body"><canvas id="chartDona" height="170"></canvas></div>
                </div>
            </div>

            {{-- Tabla de campañas con ROAS --}}
            <div class="bs-card overflow-hidden">
                <div class="bs-card-header"><h3 class="bs-display text-lg text-gray-800 m-0">Rendimiento por campaña</h3></div>
                <div class="bs-card-body">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm" style="min-width:680px;">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 uppercase border-b border-gray-100">
                                    <th class="py-2 pr-3">Campaña</th>
                                    <th class="py-2 px-3 text-right">Inversión</th>
                                    <th class="py-2 px-3 text-right">Ventas</th>
                                    <th class="py-2 px-3 text-center">Compras</th>
                                    <th class="py-2 px-3 text-right">ROAS</th>
                                    <th class="py-2 pl-3" style="width:120px;">Eficiencia</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($campanas as $c)
                                @php $maxRoas = 7; $w = min(100, round($c['roas']/$maxRoas*100)); @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 pr-3">
                                        <span class="font-medium text-gray-800">{{ $c['nombre'] }}</span>
                                        @if($c['estado']==='pausada')<span class="bs-badge-neutral ml-1">Pausada</span>@else<span class="bs-badge-success ml-1">Activa</span>@endif
                                    </td>
                                    <td class="py-3 px-3 text-right text-gray-600">{{ $fmt($c['inv']) }}</td>
                                    <td class="py-3 px-3 text-right font-semibold" style="color:#059669;">{{ $fmt($c['ventas']) }}</td>
                                    <td class="py-3 px-3 text-center text-gray-600">{{ $c['compras'] }}</td>
                                    <td class="py-3 px-3 text-right font-bold" style="color:{{ $c['roas']>=3 ? '#059669' : ($c['roas']>=1.5 ? '#D97706' : '#DC2626') }};">{{ $c['roas'] }}x</td>
                                    <td class="py-3 pl-3">
                                        <div style="background:#F3F4F6; border-radius:9999px; height:8px; overflow:hidden;">
                                            <div style="height:100%; width:{{ $w }}%; border-radius:9999px; background:linear-gradient(90deg,#FF9C00,#FF8100);"></div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Top anuncios más rentables --}}
            <div class="bs-card overflow-hidden">
                <div class="bs-card-header"><h3 class="bs-display text-lg text-gray-800 m-0">🏆 Anuncios más rentables</h3></div>
                <div class="bs-card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($anuncios as $i => $a)
                        <div class="flex items-center gap-4 p-4 rounded-xl" style="background:linear-gradient(135deg,#FFF7EC,#FFEDD0);">
                            <div class="flex items-center justify-center rounded-xl bg-white shadow-sm" style="width:56px;height:56px;font-size:1.8rem;">{{ $a['thumb'] }}</div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-800 m-0 truncate">{{ $a['nombre'] }}</p>
                                <p class="text-xs text-gray-500 m-0">CTR {{ $a['ctr'] }}% · {{ $fmt($a['ventas']) }} en ventas</p>
                            </div>
                            <div class="text-right">
                                <p class="bs-display text-xl m-0" style="color:#FF8100;">{{ $a['roas'] }}x</p>
                                <p class="text-[0.6rem] text-gray-400 m-0 uppercase">ROAS</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- CTA envío --}}
            <div class="bs-card p-6 text-center">
                <p class="text-gray-600 mb-3">Este reporte se enviaría automáticamente al cliente el día 1 de cada mes.</p>
                <button class="bs-btn-primary" onclick="alert('En producción: envía el reporte por correo al cliente con su enlace privado.')">
                    <i class="fas fa-paper-plane"></i> Enviar reporte al cliente
                </button>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const brand = '#FF8100', brand2 = '#FF9C00', green = '#10B981';
        const dias = @json($dias);
        const gasto = @json($serieGasto);
        const ventas = @json($serieVentas);

        new Chart(document.getElementById('chartLinea'), {
            type: 'line',
            data: {
                labels: dias.map(d => d + ' may'),
                datasets: [
                    { label: 'Ventas', data: ventas, borderColor: green, backgroundColor: 'rgba(16,185,129,0.08)', fill: true, tension: 0.35, borderWidth: 2, pointRadius: 0 },
                    { label: 'Inversión', data: gasto, borderColor: brand, backgroundColor: 'rgba(255,129,0,0.08)', fill: true, tension: 0.35, borderWidth: 2, pointRadius: 0 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: true,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: c => c.dataset.label + ': $' + c.raw.toLocaleString('es-CL') } } },
                scales: { y: { ticks: { callback: v => '$' + (v/1000) + 'k' } } }
            }
        });

        new Chart(document.getElementById('chartEmbudo'), {
            type: 'bar',
            data: {
                labels: ['Alcance', 'Impresiones', 'Clicks', 'Compras'],
                datasets: [{ data: [{{ $alcance }}, {{ $impresiones }}, {{ $clicks }}, {{ $compras }}],
                    backgroundColor: ['#FFC800','#FF9C00','#FF8100','#10B981'], borderRadius: 8 }]
            },
            options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false },
                tooltip: { callbacks: { label: c => c.raw.toLocaleString('es-CL') } } },
                scales: { x: { ticks: { callback: v => v >= 1000 ? (v/1000)+'k' : v } } } }
        });

        new Chart(document.getElementById('chartDona'), {
            type: 'doughnut',
            data: {
                labels: @json(array_column($campanas, 'nombre')),
                datasets: [{ data: @json(array_column($campanas, 'inv')),
                    backgroundColor: ['#FF8100','#FF9C00','#FFC800','#FCD34D','#FDE68A'] }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } },
                tooltip: { callbacks: { label: c => c.label + ': $' + c.raw.toLocaleString('es-CL') } } } }
        });
    </script>
</x-app-layout>
