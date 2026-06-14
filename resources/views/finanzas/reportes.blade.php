<x-app-layout>
<x-slot name="header">Reportes Financieros</x-slot>

<div style="padding: 1.5rem;">
    <!-- Período (aplica al instante) -->
    <div style="margin-bottom:1.5rem;">
        @include('finanzas._periodo', ['ruta' => 'finanzas.reportes', 'mes' => $mes, 'anio' => $anio])
    </div>

    <!-- Reportes disponibles -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:1.5rem; margin-bottom:2rem;">
        <!-- Libro de Ventas -->
        <div style="background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
            <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1rem;">
                <div style="width:40px; height:40px; background:#10b98120; border-radius:10px; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-book" style="color:#10b981;"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size:1rem; font-weight:700; color:#1e293b;">Libro de Ventas</h3>
                    <p style="margin:0; font-size:0.75rem; color:#94a3b8;">Boletas y Facturas emitidas</p>
                </div>
            </div>
            <div style="font-size:0.85rem; color:#475569; margin-bottom:1rem;">
                <div style="display:flex; justify-content:space-between; padding:0.3rem 0;"><span>Boletas:</span><span style="font-weight:600;">{{ $countBoletas }}</span></div>
                <div style="display:flex; justify-content:space-between; padding:0.3rem 0;"><span>Facturas:</span><span style="font-weight:600;">{{ $countFacturas }}</span></div>
                <div style="display:flex; justify-content:space-between; padding:0.3rem 0;"><span>Notas de Crédito:</span><span style="font-weight:600;">{{ $countNC }}</span></div>
                <div style="display:flex; justify-content:space-between; padding:0.3rem 0; border-top:1px solid #f1f5f9; margin-top:0.25rem; padding-top:0.5rem;"><span style="font-weight:600;">Total Neto:</span><span style="font-weight:700; color:#10b981;">${{ number_format($ventasNeto, 0, ',', '.') }}</span></div>
            </div>
            <a href="{{ route('finanzas.reportes.libro-ventas', ['mes' => $mes, 'anio' => $anio]) }}" style="display:block; text-align:center; padding:0.5rem; background:#10b981; color:#fff; border-radius:8px; text-decoration:none; font-weight:600; font-size:0.85rem;">
                <i class="fas fa-download"></i> Descargar Excel
            </a>
        </div>

        <!-- Libro de Compras -->
        <div style="background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
            <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1rem;">
                <div style="width:40px; height:40px; background:#ef444420; border-radius:10px; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-book-open" style="color:#ef4444;"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size:1rem; font-weight:700; color:#1e293b;">Libro de Compras</h3>
                    <p style="margin:0; font-size:0.75rem; color:#94a3b8;">Facturas de compra registradas</p>
                </div>
            </div>
            <div style="font-size:0.85rem; color:#475569; margin-bottom:1rem;">
                <div style="display:flex; justify-content:space-between; padding:0.3rem 0;"><span>Facturas:</span><span style="font-weight:600;">{{ $countCompras }}</span></div>
                <div style="display:flex; justify-content:space-between; padding:0.3rem 0; border-top:1px solid #f1f5f9; margin-top:0.25rem; padding-top:0.5rem;"><span style="font-weight:600;">Total Neto:</span><span style="font-weight:700; color:#ef4444;">${{ number_format($comprasNeto, 0, ',', '.') }}</span></div>
            </div>
            <a href="{{ route('finanzas.reportes.libro-compras', ['mes' => $mes, 'anio' => $anio]) }}" style="display:block; text-align:center; padding:0.5rem; background:#ef4444; color:#fff; border-radius:8px; text-decoration:none; font-weight:600; font-size:0.85rem;">
                <i class="fas fa-download"></i> Descargar Excel
            </a>
        </div>

        <!-- Borrador F29 -->
        <div style="background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
            <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1rem;">
                <div style="width:40px; height:40px; background:#f59e0b20; border-radius:10px; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-file-alt" style="color:#f59e0b;"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size:1rem; font-weight:700; color:#1e293b;">Borrador F29</h3>
                    <p style="margin:0; font-size:0.75rem; color:#94a3b8;">Declaración mensual de IVA</p>
                </div>
            </div>
            <div style="font-size:0.85rem; color:#475569; margin-bottom:1rem;">
                <div style="display:flex; justify-content:space-between; padding:0.3rem 0;"><span>IVA Débito:</span><span style="font-weight:600;">${{ number_format($ivaDebito, 0, ',', '.') }}</span></div>
                <div style="display:flex; justify-content:space-between; padding:0.3rem 0;"><span>IVA Crédito:</span><span style="font-weight:600;">${{ number_format($ivaCredito, 0, ',', '.') }}</span></div>
                <div style="display:flex; justify-content:space-between; padding:0.3rem 0; border-top:1px solid #f1f5f9; margin-top:0.25rem; padding-top:0.5rem;"><span style="font-weight:600;">IVA a Pagar:</span><span style="font-weight:700; color:#f59e0b;">${{ number_format(max(0, $ivaDebito - $ivaCredito), 0, ',', '.') }}</span></div>
            </div>
            <a href="{{ route('finanzas.reportes.f29', ['mes' => $mes, 'anio' => $anio]) }}" style="display:block; text-align:center; padding:0.5rem; background:#f59e0b; color:#000; border-radius:8px; text-decoration:none; font-weight:600; font-size:0.85rem;">
                <i class="fas fa-download"></i> Descargar PDF
            </a>
        </div>

        <!-- Estado de Resultados -->
        <div style="background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
            <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1rem;">
                <div style="width:40px; height:40px; background:#3b82f620; border-radius:10px; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-chart-bar" style="color:#3b82f6;"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size:1rem; font-weight:700; color:#1e293b;">Estado de Resultados</h3>
                    <p style="margin:0; font-size:0.75rem; color:#94a3b8;">Ingresos vs Egresos del período</p>
                </div>
            </div>
            <div style="font-size:0.85rem; color:#475569; margin-bottom:1rem;">
                <div style="display:flex; justify-content:space-between; padding:0.3rem 0;"><span>Ingresos:</span><span style="font-weight:600; color:#10b981;">${{ number_format($totalIngresos, 0, ',', '.') }}</span></div>
                <div style="display:flex; justify-content:space-between; padding:0.3rem 0;"><span>Egresos:</span><span style="font-weight:600; color:#ef4444;">${{ number_format($totalEgresos, 0, ',', '.') }}</span></div>
                @php $utilidad = $totalIngresos - $totalEgresos; @endphp
                <div style="display:flex; justify-content:space-between; padding:0.3rem 0; border-top:1px solid #f1f5f9; margin-top:0.25rem; padding-top:0.5rem;"><span style="font-weight:600;">Utilidad:</span><span style="font-weight:700; color:{{ $utilidad >= 0 ? '#3b82f6' : '#ef4444' }};">${{ number_format($utilidad, 0, ',', '.') }}</span></div>
            </div>
            <a href="{{ route('finanzas.reportes.estado-resultados', ['mes' => $mes, 'anio' => $anio]) }}" style="display:block; text-align:center; padding:0.5rem; background:#3b82f6; color:#fff; border-radius:8px; text-decoration:none; font-weight:600; font-size:0.85rem;">
                <i class="fas fa-download"></i> Descargar Excel
            </a>
        </div>
    </div>
</div>
</x-app-layout>
