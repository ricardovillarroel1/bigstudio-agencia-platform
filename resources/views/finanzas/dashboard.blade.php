<x-app-layout>
<x-slot name="header">Dashboard Financiero</x-slot>

@php
    // Badge de variación mes-vs-mes. $bueno = true si "subir" es bueno (ingresos/utilidad), false para egresos.
    function fzVarBadge($var, $bueno = true) {
        if ($var === null) {
            return '<span style="font-size:0.7rem;color:#94a3b8;">— sin dato mes anterior</span>';
        }
        $sube = $var > 0;
        $positivo = $sube ? $bueno : !$bueno; // verde/rojo
        $color = $var == 0 ? '#94a3b8' : ($positivo ? '#059669' : '#dc2626');
        $bg = $var == 0 ? '#f1f5f9' : ($positivo ? '#ecfdf5' : '#fef2f2');
        $flecha = $var == 0 ? '→' : ($sube ? '▲' : '▼');
        $txt = ($sube ? '+' : '') . number_format($var, 1, ',', '.') . '%';
        return '<span style="display:inline-flex;align-items:center;gap:3px;font-size:0.72rem;font-weight:700;color:'.$color.';background:'.$bg.';padding:2px 8px;border-radius:9999px;">'.$flecha.' '.$txt.'</span>';
    }
@endphp

<div style="padding: 1.5rem;">
    <!-- Selector de período (aplica al instante, sin botón) -->
    <div style="margin-bottom:1.5rem;">
        @include('finanzas._periodo', ['ruta' => 'finanzas.dashboard', 'mes' => $mes, 'anio' => $anio])
    </div>

    <!-- KPIs con comparativa mes-vs-mes -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap:1rem; margin-bottom:1.75rem;">
        <!-- Ingresos -->
        <div class="bs-card" style="padding:1.25rem; border-left:4px solid #10b981;">
            <div style="font-size:0.72rem; color:#64748b; text-transform:uppercase; font-weight:700; letter-spacing:0.04em;">Ingresos del mes</div>
            <div class="bs-display" style="font-size:1.7rem; color:#059669; margin-top:0.3rem; line-height:1.1;">${{ number_format($totalIngresos, 0, ',', '.') }}</div>
            <div style="margin-top:0.5rem; display:flex; align-items:center; gap:0.5rem;">
                {!! fzVarBadge($comparativa['ingresos']['var'], true) !!}
                <span style="font-size:0.68rem; color:#94a3b8;">vs ${{ number_format($comparativa['ingresos']['previo'], 0, ',', '.') }}</span>
            </div>
        </div>
        <!-- Egresos -->
        <div class="bs-card" style="padding:1.25rem; border-left:4px solid #ef4444;">
            <div style="font-size:0.72rem; color:#64748b; text-transform:uppercase; font-weight:700; letter-spacing:0.04em;">Egresos del mes</div>
            <div class="bs-display" style="font-size:1.7rem; color:#dc2626; margin-top:0.3rem; line-height:1.1;">${{ number_format($totalEgresos, 0, ',', '.') }}</div>
            <div style="margin-top:0.5rem; display:flex; align-items:center; gap:0.5rem;">
                {!! fzVarBadge($comparativa['egresos']['var'], false) !!}
                <span style="font-size:0.68rem; color:#94a3b8;">vs ${{ number_format($comparativa['egresos']['previo'], 0, ',', '.') }}</span>
            </div>
        </div>
        <!-- Ganancia neta -->
        <div class="bs-card" style="padding:1.25rem; border-left:4px solid {{ $utilidad >= 0 ? '#FF8100' : '#ef4444' }};">
            <div style="font-size:0.72rem; color:#64748b; text-transform:uppercase; font-weight:700; letter-spacing:0.04em;">Ganancia neta</div>
            <div class="bs-display" style="font-size:1.7rem; color:{{ $utilidad >= 0 ? '#FF8100' : '#dc2626' }}; margin-top:0.3rem; line-height:1.1;">${{ number_format($utilidad, 0, ',', '.') }}</div>
            <div style="margin-top:0.5rem; display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                {!! fzVarBadge($comparativa['utilidad']['var'], true) !!}
                <span style="font-size:0.68rem; color:#94a3b8;">Margen {{ number_format($margen, 1, ',', '.') }}%</span>
            </div>
        </div>
        <!-- IVA -->
        <div class="bs-card" style="padding:1.25rem; border-left:4px solid #f59e0b;">
            <div style="font-size:0.72rem; color:#64748b; text-transform:uppercase; font-weight:700; letter-spacing:0.04em;">IVA a pagar</div>
            <div class="bs-display" style="font-size:1.7rem; color:#d97706; margin-top:0.3rem; line-height:1.1;">${{ number_format($ivaPagar, 0, ',', '.') }}</div>
            <div style="font-size:0.68rem; color:#94a3b8; margin-top:0.5rem;">Débito ${{ number_format($totalIvaDebito, 0, ',', '.') }} · Crédito ${{ number_format($ivaCredito, 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Gráfico de tendencia -->
    <div class="bs-card" style="padding:1.5rem; margin-bottom:1.75rem;">
        <h3 class="bs-display" style="font-size:1.05rem; color:#0f172a; margin:0 0 1rem;"><i class="fas fa-chart-column" style="color:#FF8100; margin-right:0.5rem;"></i>Ingresos vs Egresos — últimos 12 meses</h3>
        <div style="height:300px;"><canvas id="fzTendencia"></canvas></div>
    </div>

    <!-- Donuts: origen + categorías -->
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin-bottom:1.75rem;" class="fz-grid-2">
        <div class="bs-card" style="padding:1.5rem;">
            <h3 class="bs-display" style="font-size:1.05rem; color:#0f172a; margin:0 0 0.25rem;">Ingresos por origen</h3>
            <p style="font-size:0.75rem; color:#94a3b8; margin:0 0 1rem;">De dónde vino lo que ganaste este mes</p>
            @if(count($ingresosPorOrigen) > 0)
                <div style="display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap;">
                    <div style="width:180px; height:180px;"><canvas id="fzOrigen"></canvas></div>
                    <div style="flex:1; min-width:160px;">
                        @foreach($ingresosPorOrigen as $o)
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:0.4rem 0; border-bottom:1px solid #f1f5f9;">
                            <span style="font-size:0.82rem; color:#475569; display:flex; align-items:center; gap:0.5rem;"><span style="width:11px;height:11px;border-radius:3px;background:{{ $o['color'] }};display:inline-block;"></span>{{ $o['label'] }}</span>
                            <span style="font-weight:700; font-size:0.82rem; color:#0f172a;">${{ number_format($o['monto'], 0, ',', '.') }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            @else
                <p style="color:#94a3b8; font-size:0.85rem; text-align:center; padding:2.5rem 0;">Sin ingresos registrados este mes</p>
            @endif
        </div>

        <div class="bs-card" style="padding:1.5rem;">
            <h3 class="bs-display" style="font-size:1.05rem; color:#0f172a; margin:0 0 0.25rem;">Gastos por categoría</h3>
            <p style="font-size:0.75rem; color:#94a3b8; margin:0 0 1rem;">En qué se fue tu plata este mes</p>
            @if($gastosPorCategoria->count() > 0)
                <div style="display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap;">
                    <div style="width:180px; height:180px;"><canvas id="fzCategorias"></canvas></div>
                    <div style="flex:1; min-width:160px;">
                        @foreach($gastosPorCategoria as $g)
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:0.4rem 0; border-bottom:1px solid #f1f5f9;">
                            <span style="font-size:0.82rem; color:#475569; display:flex; align-items:center; gap:0.5rem;"><span style="width:11px;height:11px;border-radius:3px;background:{{ $g->color }};display:inline-block;"></span>{{ $g->nombre }}</span>
                            <span style="font-weight:700; font-size:0.82rem; color:#0f172a;">${{ number_format($g->total, 0, ',', '.') }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            @else
                <p style="color:#94a3b8; font-size:0.85rem; text-align:center; padding:2.5rem 0;">Sin gastos registrados este mes.<br><a href="{{ route('finanzas.egresos') }}" style="color:#FF8100; font-weight:600;">Registrar un gasto →</a></p>
            @endif
        </div>
    </div>

    <!-- Resumen IVA -->
    <div class="bs-card" style="padding:1.5rem;">
        <h3 class="bs-display" style="font-size:1.05rem; color:#0f172a; margin:0 0 1rem;"><i class="fas fa-percent" style="color:#f59e0b; margin-right:0.5rem;"></i>Resumen IVA del mes</h3>
        <div style="display:grid; grid-template-columns: 1fr auto 1fr auto 1fr; gap:1rem; text-align:center; align-items:center;">
            <div style="padding:1rem; background:#f0fdf4; border-radius:10px;">
                <div style="font-size:0.68rem; color:#64748b; text-transform:uppercase; font-weight:700;">IVA Débito</div>
                <div style="font-size:1.15rem; font-weight:700; color:#059669; margin-top:0.25rem;">${{ number_format($totalIvaDebito, 0, ',', '.') }}</div>
                <div style="font-size:0.62rem; color:#94a3b8;">de tus ventas</div>
            </div>
            <div style="font-size:1.4rem; color:#cbd5e1; font-weight:700;">−</div>
            <div style="padding:1rem; background:#fef2f2; border-radius:10px;">
                <div style="font-size:0.68rem; color:#64748b; text-transform:uppercase; font-weight:700;">IVA Crédito</div>
                <div style="font-size:1.15rem; font-weight:700; color:#dc2626; margin-top:0.25rem;">${{ number_format($ivaCredito, 0, ',', '.') }}</div>
                <div style="font-size:0.62rem; color:#94a3b8;">de tus compras</div>
            </div>
            <div style="font-size:1.4rem; color:#cbd5e1; font-weight:700;">=</div>
            <div style="padding:1rem; background:#fffbeb; border-radius:10px;">
                <div style="font-size:0.68rem; color:#64748b; text-transform:uppercase; font-weight:700;">A Pagar</div>
                <div style="font-size:1.15rem; font-weight:700; color:#d97706; margin-top:0.25rem;">${{ number_format($ivaPagar, 0, ',', '.') }}</div>
                <div style="font-size:0.62rem; color:#94a3b8;">al SII este mes</div>
            </div>
        </div>
        @if($remanente > 0)
        <div style="margin-top:0.75rem; padding:0.55rem 1rem; background:#eff6ff; border-radius:10px; font-size:0.78rem; color:#2563eb;"><i class="fas fa-info-circle"></i> Remanente del mes anterior: ${{ number_format($remanente, 0, ',', '.') }}</div>
        @endif
        @if($remanenteSiguiente > 0)
        <div style="margin-top:0.5rem; padding:0.55rem 1rem; background:#f0fdf4; border-radius:10px; font-size:0.78rem; color:#059669;"><i class="fas fa-arrow-right"></i> Remanente para el próximo mes: ${{ number_format($remanenteSiguiente, 0, ',', '.') }}</div>
        @endif
    </div>
</div>

<style>
    @media (max-width: 860px) { .fz-grid-2 { grid-template-columns: 1fr !important; } }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const CLP = (v) => '$' + new Intl.NumberFormat('es-CL').format(Math.round(v));

    // ---- Tendencia 12 meses ----
    const tend = @json($tendencia);
    const ctxT = document.getElementById('fzTendencia');
    if (ctxT && window.Chart) {
        const labels = tend.map(t => t.mes);
        const ingresos = tend.map(t => t.ingresos);
        const egresos = tend.map(t => t.egresos);
        const neto = tend.map(t => t.ingresos - t.egresos);
        new Chart(ctxT, {
            data: {
                labels,
                datasets: [
                    { type: 'bar', label: 'Ingresos', data: ingresos, backgroundColor: 'rgba(16,185,129,0.85)', borderRadius: 5, order: 2 },
                    { type: 'bar', label: 'Egresos', data: egresos, backgroundColor: 'rgba(239,68,68,0.85)', borderRadius: 5, order: 2 },
                    { type: 'line', label: 'Ganancia neta', data: neto, borderColor: '#FF8100', backgroundColor: '#FF8100', borderWidth: 2.5, tension: 0.3, pointRadius: 3, pointBackgroundColor: '#FF8100', order: 1 },
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8, font: { size: 12 } } },
                    tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + CLP(c.parsed.y) } }
                },
                scales: { y: { ticks: { callback: (v) => CLP(v), font: { size: 11 } }, grid: { color: '#f1f5f9' } }, x: { grid: { display: false }, ticks: { font: { size: 11 } } } }
            }
        });
    }

    // ---- Donut helper ----
    function donut(id, items, labelKey, valKey, colorKey) {
        const el = document.getElementById(id);
        if (!el || !window.Chart || !items.length) return;
        new Chart(el, {
            type: 'doughnut',
            data: {
                labels: items.map(i => i[labelKey]),
                datasets: [{ data: items.map(i => i[valKey]), backgroundColor: items.map(i => i[colorKey]), borderWidth: 2, borderColor: '#fff' }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '62%',
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => c.label + ': ' + CLP(c.parsed) } } }
            }
        });
    }
    donut('fzOrigen', @json($ingresosPorOrigen), 'label', 'monto', 'color');
    donut('fzCategorias', @json($gastosPorCategoria), 'nombre', 'total', 'color');
})();
</script>
</x-app-layout>
