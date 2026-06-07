<x-app-layout>
<x-slot name="header">Dashboard Financiero</x-slot>

<div style="padding: 1.5rem;">
    <!-- Filtro de mes -->
    <form method="GET" style="display:flex; gap:1rem; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap;">
        <select name="mes" style="padding:0.5rem 1rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.9rem;">
            @for($m=1; $m<=12; $m++)
                <option value="{{ $m }}" {{ $mes == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}</option>
            @endfor
        </select>
        <select name="anio" style="padding:0.5rem 1rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.9rem;">
            @for($a=now()->year; $a>=now()->year-3; $a--)
                <option value="{{ $a }}" {{ $anio == $a ? 'selected' : '' }}>{{ $a }}</option>
            @endfor
        </select>
        <button type="submit" style="padding:0.5rem 1.5rem; background:#FFC800; color:#000; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Filtrar</button>
    </form>

    <!-- Tarjetas principales -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:2rem;">
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); border-left:4px solid #10b981;">
            <div style="font-size:0.75rem; color:#64748b; text-transform:uppercase; font-weight:600;">Ingresos</div>
            <div style="font-size:1.75rem; font-weight:700; color:#10b981; margin-top:0.25rem;">${{ number_format($totalIngresos, 0, ',', '.') }}</div>
            <div style="font-size:0.7rem; color:#94a3b8; margin-top:0.25rem;">
                Boletas: ${{ number_format($ingresoBoletas, 0, ',', '.') }} |
                Facturas: ${{ number_format($ingresoFacturas, 0, ',', '.') }}
            </div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); border-left:4px solid #ef4444;">
            <div style="font-size:0.75rem; color:#64748b; text-transform:uppercase; font-weight:600;">Egresos</div>
            <div style="font-size:1.75rem; font-weight:700; color:#ef4444; margin-top:0.25rem;">${{ number_format($totalEgresos, 0, ',', '.') }}</div>
            <div style="font-size:0.7rem; color:#94a3b8; margin-top:0.25rem;">
                F. Compra: ${{ number_format($egresoFacturasCompra, 0, ',', '.') }} |
                Gastos Op: ${{ number_format($egresoGastosOp, 0, ',', '.') }}
            </div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); border-left:4px solid #3b82f6;">
            <div style="font-size:0.75rem; color:#64748b; text-transform:uppercase; font-weight:600;">Utilidad Bruta</div>
            @php $utilidad = $totalIngresos - $totalEgresos; @endphp
            <div style="font-size:1.75rem; font-weight:700; color:{{ $utilidad >= 0 ? '#3b82f6' : '#ef4444' }}; margin-top:0.25rem;">
                ${{ number_format($utilidad, 0, ',', '.') }}
            </div>
            @if($totalIngresos > 0)
            <div style="font-size:0.7rem; color:#94a3b8; margin-top:0.25rem;">Margen: {{ round(($utilidad / $totalIngresos) * 100, 1) }}%</div>
            @endif
        </div>
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); border-left:4px solid #f59e0b;">
            <div style="font-size:0.75rem; color:#64748b; text-transform:uppercase; font-weight:600;">IVA a Pagar</div>
            <div style="font-size:1.75rem; font-weight:700; color:#f59e0b; margin-top:0.25rem;">${{ number_format($ivaPagar, 0, ',', '.') }}</div>
            <div style="font-size:0.7rem; color:#94a3b8; margin-top:0.25rem;">
                Débito: ${{ number_format($totalIvaDebito, 0, ',', '.') }} |
                Crédito: ${{ number_format($ivaCredito, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <!-- Desglose de ingresos -->
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin-bottom:2rem;">
        <div style="background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
            <h3 style="font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 1rem;">Desglose de Ingresos</h3>
            <div style="display:flex; flex-direction:column; gap:0.75rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; padding:0.5rem 0; border-bottom:1px solid #f1f5f9;">
                    <span style="font-size:0.85rem; color:#475569;"><i class="fas fa-receipt" style="color:#10b981; margin-right:0.5rem;"></i>Boletas</span>
                    <span style="font-weight:600; color:#1e293b;">${{ number_format($ingresoBoletas, 0, ',', '.') }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:0.5rem 0; border-bottom:1px solid #f1f5f9;">
                    <span style="font-size:0.85rem; color:#475569;"><i class="fas fa-file-invoice" style="color:#3b82f6; margin-right:0.5rem;"></i>Facturas Venta</span>
                    <span style="font-weight:600; color:#1e293b;">${{ number_format($ingresoFacturas, 0, ',', '.') }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:0.5rem 0; border-bottom:1px solid #f1f5f9;">
                    <span style="font-size:0.85rem; color:#475569;"><i class="fas fa-briefcase" style="color:#8b5cf6; margin-right:0.5rem;"></i>Cobros Agencia</span>
                    <span style="font-weight:600; color:#1e293b;">${{ number_format($ingresoCobrosAgencia, 0, ',', '.') }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:0.5rem 0; border-bottom:1px solid #f1f5f9;">
                    <span style="font-size:0.85rem; color:#475569;"><i class="fas fa-credit-card" style="color:#06b6d4; margin-right:0.5rem;"></i>Pagos Suscripciones</span>
                    <span style="font-weight:600; color:#1e293b;">${{ number_format($ingresoPayments, 0, ',', '.') }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:0.5rem 0;">
                    <span style="font-size:0.85rem; color:#475569;"><i class="fas fa-plus-circle" style="color:#f59e0b; margin-right:0.5rem;"></i>Ingresos Manuales</span>
                    <span style="font-weight:600; color:#1e293b;">${{ number_format($ingresosManuales, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Gastos por categoría -->
        <div style="background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
            <h3 style="font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 1rem;">Gastos por Categoría</h3>
            @if($gastosPorCategoria->count() > 0)
                @foreach($gastosPorCategoria as $gasto)
                <div style="margin-bottom:0.75rem;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:0.25rem;">
                        <span style="font-size:0.85rem; color:#475569;">{{ $gasto->nombre }}</span>
                        <span style="font-weight:600; font-size:0.85rem; color:#1e293b;">${{ number_format($gasto->total, 0, ',', '.') }}</span>
                    </div>
                    <div style="background:#f1f5f9; border-radius:99px; height:8px; overflow:hidden;">
                        <div style="background:{{ $gasto->color }}; height:100%; border-radius:99px; width:{{ $totalEgresos > 0 ? round(($gasto->total / $totalEgresos) * 100) : 0 }}%;"></div>
                    </div>
                </div>
                @endforeach
            @else
                <p style="color:#94a3b8; font-size:0.85rem; text-align:center; padding:2rem 0;">No hay gastos registrados este mes</p>
            @endif
        </div>
    </div>

    <!-- Resumen IVA -->
    <div style="background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); margin-bottom:2rem;">
        <h3 style="font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 1rem;"><i class="fas fa-percentage" style="color:#f59e0b; margin-right:0.5rem;"></i>Resumen IVA</h3>
        <div style="display:grid; grid-template-columns: repeat(5, 1fr); gap:1rem; text-align:center;">
            <div style="padding:1rem; background:#f0fdf4; border-radius:8px;">
                <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">IVA Débito</div>
                <div style="font-size:1.1rem; font-weight:700; color:#10b981; margin-top:0.25rem;">${{ number_format($totalIvaDebito, 0, ',', '.') }}</div>
            </div>
            <div style="font-size:1.5rem; color:#94a3b8; display:flex; align-items:center; justify-content:center;">−</div>
            <div style="padding:1rem; background:#fef2f2; border-radius:8px;">
                <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">IVA Crédito</div>
                <div style="font-size:1.1rem; font-weight:700; color:#ef4444; margin-top:0.25rem;">${{ number_format($ivaCredito, 0, ',', '.') }}</div>
            </div>
            <div style="font-size:1.5rem; color:#94a3b8; display:flex; align-items:center; justify-content:center;">=</div>
            <div style="padding:1rem; background:#fffbeb; border-radius:8px;">
                <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">A Pagar</div>
                <div style="font-size:1.1rem; font-weight:700; color:#f59e0b; margin-top:0.25rem;">${{ number_format($ivaPagar, 0, ',', '.') }}</div>
            </div>
        </div>
        @if($remanente > 0)
        <div style="margin-top:0.75rem; padding:0.5rem 1rem; background:#eff6ff; border-radius:8px; font-size:0.8rem; color:#3b82f6;">
            <i class="fas fa-info-circle"></i> Remanente del mes anterior: ${{ number_format($remanente, 0, ',', '.') }}
        </div>
        @endif
        @if($remanenteSiguiente > 0)
        <div style="margin-top:0.5rem; padding:0.5rem 1rem; background:#f0fdf4; border-radius:8px; font-size:0.8rem; color:#10b981;">
            <i class="fas fa-arrow-right"></i> Remanente para el próximo mes: ${{ number_format($remanenteSiguiente, 0, ',', '.') }}
        </div>
        @endif
    </div>

    <!-- Tendencia 12 meses -->
    <div style="background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
        <h3 style="font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 1rem;"><i class="fas fa-chart-bar" style="color:#3b82f6; margin-right:0.5rem;"></i>Tendencia Últimos 12 Meses</h3>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:0.75rem; text-align:left; color:#64748b; font-weight:600;">Mes</th>
                        <th style="padding:0.75rem; text-align:right; color:#64748b; font-weight:600;">Ingresos</th>
                        <th style="padding:0.75rem; text-align:right; color:#64748b; font-weight:600;">Egresos</th>
                        <th style="padding:0.75rem; text-align:right; color:#64748b; font-weight:600;">Resultado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tendencia as $t)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:0.6rem 0.75rem; font-weight:500;">{{ $t['mes'] }}</td>
                        <td style="padding:0.6rem 0.75rem; text-align:right; color:#10b981; font-weight:600;">${{ number_format($t['ingresos'], 0, ',', '.') }}</td>
                        <td style="padding:0.6rem 0.75rem; text-align:right; color:#ef4444; font-weight:600;">${{ number_format($t['egresos'], 0, ',', '.') }}</td>
                        @php $res = $t['ingresos'] - $t['egresos']; @endphp
                        <td style="padding:0.6rem 0.75rem; text-align:right; color:{{ $res >= 0 ? '#3b82f6' : '#ef4444' }}; font-weight:700;">${{ number_format($res, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-app-layout>
