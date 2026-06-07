<x-app-layout>
<x-slot name="header">Cálculo de IVA Mensual</x-slot>

<div style="padding: 1.5rem;">
    <!-- Filtro -->
    <form method="GET" style="display:flex; gap:1rem; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap;">
        <select name="mes" style="padding:0.5rem 1rem; border:1px solid #e2e8f0; border-radius:8px;">
            @for($m=1; $m<=12; $m++)
                <option value="{{ $m }}" {{ $mes == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}</option>
            @endfor
        </select>
        <select name="anio" style="padding:0.5rem 1rem; border:1px solid #e2e8f0; border-radius:8px;">
            @for($a=now()->year; $a>=now()->year-3; $a--)
                <option value="{{ $a }}" {{ $anio == $a ? 'selected' : '' }}>{{ $a }}</option>
            @endfor
        </select>
        <button type="submit" style="padding:0.5rem 1.5rem; background:#FFC800; color:#000; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Filtrar</button>
    </form>

    <!-- Cálculo principal -->
    <div style="background:#fff; border-radius:12px; padding:2rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); margin-bottom:2rem;">
        <h3 style="font-size:1.1rem; font-weight:700; color:#1e293b; margin:0 0 1.5rem;">
            <i class="fas fa-calculator" style="color:#f59e0b; margin-right:0.5rem;"></i>
            Cálculo IVA — {{ \Carbon\Carbon::create($anio, $mes)->translatedFormat('F Y') }}
        </h3>

        <!-- IVA Débito -->
        <div style="margin-bottom:1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.75rem 1rem; background:#f0fdf4; border-radius:8px; margin-bottom:0.5rem;">
                <span style="font-weight:700; color:#166534;">IVA DÉBITO FISCAL (Ventas)</span>
                <span style="font-size:1.25rem; font-weight:700; color:#10b981;">${{ number_format($totalIvaDebito, 0, ',', '.') }}</span>
            </div>
            <div style="padding:0 1rem;">
                <div style="display:flex; justify-content:space-between; padding:0.4rem 0; font-size:0.85rem; color:#475569; border-bottom:1px solid #f1f5f9;">
                    <span>IVA Boletas ({{ $countBoletas }} docs)</span>
                    <span style="font-weight:600;">${{ number_format($ivaBoletas, 0, ',', '.') }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:0.4rem 0; font-size:0.85rem; color:#475569; border-bottom:1px solid #f1f5f9;">
                    <span>IVA Facturas de Venta ({{ $countFacturas }} docs)</span>
                    <span style="font-weight:600;">${{ number_format($ivaFacturas, 0, ',', '.') }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:0.4rem 0; font-size:0.85rem; color:#475569;">
                    <span>IVA Notas de Crédito ({{ $countNC }} docs) <span style="color:#ef4444; font-size:0.75rem;">resta</span></span>
                    <span style="font-weight:600; color:#ef4444;">-${{ number_format($ivaNC, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- IVA Crédito -->
        <div style="margin-bottom:1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.75rem 1rem; background:#fef2f2; border-radius:8px; margin-bottom:0.5rem;">
                <span style="font-weight:700; color:#991b1b;">IVA CRÉDITO FISCAL (Compras)</span>
                <span style="font-size:1.25rem; font-weight:700; color:#ef4444;">${{ number_format($totalIvaCredito, 0, ',', '.') }}</span>
            </div>
            <div style="padding:0 1rem;">
                <div style="display:flex; justify-content:space-between; padding:0.4rem 0; font-size:0.85rem; color:#475569;">
                    <span>IVA Facturas de Compra ({{ $countCompras }} docs)</span>
                    <span style="font-weight:600;">${{ number_format($totalIvaCredito, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Remanente -->
        @if($remanente > 0)
        <div style="margin-bottom:1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.75rem 1rem; background:#eff6ff; border-radius:8px;">
                <span style="font-weight:700; color:#1e40af;">REMANENTE MES ANTERIOR</span>
                <span style="font-size:1.25rem; font-weight:700; color:#3b82f6;">${{ number_format($remanente, 0, ',', '.') }}</span>
            </div>
        </div>
        @endif

        <!-- Resultado -->
        <div style="border-top:3px solid #1e293b; padding-top:1rem; margin-top:1rem;">
            @php $resultado = $totalIvaDebito - $totalIvaCredito - $remanente; @endphp
            @if($resultado > 0)
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1rem 1.5rem; background:#fffbeb; border-radius:12px; border:2px solid #f59e0b;">
                <div>
                    <span style="font-size:1.1rem; font-weight:700; color:#92400e;">IVA A PAGAR</span>
                    <div style="font-size:0.75rem; color:#a16207; margin-top:0.25rem;">Plazo: hasta el día 12 del mes siguiente</div>
                </div>
                <span style="font-size:2rem; font-weight:800; color:#f59e0b;">${{ number_format($resultado, 0, ',', '.') }}</span>
            </div>
            @else
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1rem 1.5rem; background:#f0fdf4; border-radius:12px; border:2px solid #10b981;">
                <div>
                    <span style="font-size:1.1rem; font-weight:700; color:#166534;">REMANENTE A FAVOR</span>
                    <div style="font-size:0.75rem; color:#15803d; margin-top:0.25rem;">Se arrastra al mes siguiente como crédito</div>
                </div>
                <span style="font-size:2rem; font-weight:800; color:#10b981;">${{ number_format(abs($resultado), 0, ',', '.') }}</span>
            </div>
            @endif
        </div>
    </div>

    <!-- Fórmula explicativa -->
    <div style="background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); margin-bottom:2rem;">
        <h3 style="font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 1rem;"><i class="fas fa-info-circle" style="color:#3b82f6;"></i> Fórmula de Cálculo</h3>
        <div style="background:#f8fafc; padding:1rem; border-radius:8px; font-family:monospace; font-size:0.85rem; color:#475569; line-height:1.8;">
            IVA Débito (Boletas + Facturas Venta - NC) = ${{ number_format($totalIvaDebito, 0, ',', '.') }}<br>
            − IVA Crédito (Facturas Compra) = ${{ number_format($totalIvaCredito, 0, ',', '.') }}<br>
            @if($remanente > 0)
            − Remanente Anterior = ${{ number_format($remanente, 0, ',', '.') }}<br>
            @endif
            ────────────────────────<br>
            <strong>= {{ $resultado > 0 ? 'IVA a Pagar' : 'Remanente' }}: ${{ number_format(abs($resultado), 0, ',', '.') }}</strong>
        </div>
    </div>

    <!-- Historial IVA últimos 6 meses -->
    <div style="background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
        <h3 style="font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 1rem;">Historial IVA (Últimos 6 Meses)</h3>
        <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:0.75rem; text-align:left; color:#64748b;">Mes</th>
                    <th style="padding:0.75rem; text-align:right; color:#64748b;">Débito</th>
                    <th style="padding:0.75rem; text-align:right; color:#64748b;">Crédito</th>
                    <th style="padding:0.75rem; text-align:right; color:#64748b;">Remanente</th>
                    <th style="padding:0.75rem; text-align:right; color:#64748b;">Resultado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($historialIva as $h)
                <tr style="border-bottom:1px solid #f1f5f9; {{ $h['mes'] == \Carbon\Carbon::create($anio, $mes)->translatedFormat('F Y') ? 'background:#fffbeb;' : '' }}">
                    <td style="padding:0.6rem 0.75rem; font-weight:500;">{{ $h['mes'] }}</td>
                    <td style="padding:0.6rem 0.75rem; text-align:right; color:#10b981;">${{ number_format($h['debito'], 0, ',', '.') }}</td>
                    <td style="padding:0.6rem 0.75rem; text-align:right; color:#ef4444;">${{ number_format($h['credito'], 0, ',', '.') }}</td>
                    <td style="padding:0.6rem 0.75rem; text-align:right; color:#3b82f6;">${{ number_format($h['remanente'], 0, ',', '.') }}</td>
                    @php $r = $h['debito'] - $h['credito'] - $h['remanente']; @endphp
                    <td style="padding:0.6rem 0.75rem; text-align:right; font-weight:700; color:{{ $r > 0 ? '#f59e0b' : '#10b981' }};">
                        {{ $r > 0 ? 'Pagar' : 'Remanente' }} ${{ number_format(abs($r), 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
</x-app-layout>
