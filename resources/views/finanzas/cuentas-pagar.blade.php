<x-app-layout>
<x-slot name="header">Cuentas por Pagar</x-slot>

<div style="padding: 1.5rem;">
    @if(session('success'))
        <div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#047857; padding:0.7rem 1rem; border-radius:10px; margin-bottom:1rem; font-size:0.85rem;"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; padding:0.7rem 1rem; border-radius:10px; margin-bottom:1rem; font-size:0.85rem;"><i class="fas fa-triangle-exclamation"></i> {{ session('error') }}</div>
    @endif

    <!-- Qué muestra este módulo -->
    <div style="background:linear-gradient(135deg,#FFF7EC,#fff); border:1px solid #FFE0B3; border-radius:12px; padding:1rem 1.25rem; margin-bottom:1.5rem; display:flex; gap:0.75rem; align-items:flex-start;">
        <i class="fas fa-circle-info" style="color:#FF8100; margin-top:0.15rem;"></i>
        <div style="font-size:0.82rem; color:#475569; line-height:1.55;">
            <strong style="color:#0f172a;">A quién le debes y cuándo.</strong> Aquí aparecen las <strong>facturas de compra en estado «Pendiente»</strong> (con proveedor y vencimiento) y tus <strong>gastos fijos mensuales</strong>. Las compras que importa Lioren entran como <em>pagadas</em> (cargo automático), por eso no las verás aquí; si registras o marcas una compra como <strong>Pendiente</strong> en Egresos, aparecerá en esta lista.
        </div>
    </div>

    <!-- Tarjetas resumen -->
    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:1rem; margin-bottom:1.5rem;">
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center; border-top:3px solid #ef4444;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Total por Pagar</div>
            <div style="font-size:1.5rem; font-weight:700; color:#ef4444;">${{ number_format($totalPorPagar, 0, ',', '.') }}</div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Vencidas</div>
            <div style="font-size:1.5rem; font-weight:700; color:#ef4444;">${{ number_format($totalVencidas, 0, ',', '.') }}</div>
            <div style="font-size:0.7rem; color:#ef4444;">{{ $countVencidas }} facturas</div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Próximos 7 Días</div>
            <div style="font-size:1.5rem; font-weight:700; color:#f59e0b;">${{ number_format($totalProximas, 0, ',', '.') }}</div>
            <div style="font-size:0.7rem; color:#f59e0b;">{{ $countProximas }} facturas</div>
        </div>
    </div>

    <!-- Calendario de vencimientos -->
    <div style="background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); margin-bottom:1.5rem;">
        <h3 style="margin:0 0 1rem; font-size:1rem; font-weight:700; color:#1e293b;"><i class="fas fa-calendar-alt" style="color:#ef4444;"></i> Calendario de Vencimientos</h3>
        @if($vencimientosProximos->count() > 0)
        <div style="display:flex; flex-direction:column; gap:0.5rem;">
            @foreach($vencimientosProximos as $fc)
            @php
                $diasRestantes = \Carbon\Carbon::parse($fc->fecha_vencimiento)->diffInDays(now(), false);
                $vencida = $diasRestantes > 0;
            @endphp
            <div style="display:flex; align-items:center; gap:1rem; padding:0.75rem 1rem; background:{{ $vencida ? '#fef2f2' : '#fffbeb' }}; border-radius:8px; border-left:4px solid {{ $vencida ? '#ef4444' : '#f59e0b' }};">
                <div style="min-width:80px; text-align:center;">
                    <div style="font-size:1.25rem; font-weight:700; color:{{ $vencida ? '#ef4444' : '#f59e0b' }};">{{ \Carbon\Carbon::parse($fc->fecha_vencimiento)->format('d') }}</div>
                    <div style="font-size:0.65rem; color:#64748b;">{{ \Carbon\Carbon::parse($fc->fecha_vencimiento)->translatedFormat('M Y') }}</div>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:600; font-size:0.85rem; color:#1e293b;">{{ $fc->proveedor_nombre }}</div>
                    <div style="font-size:0.75rem; color:#64748b;">Factura #{{ $fc->numero_factura }}</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-weight:700; font-size:1rem; color:#1e293b;">${{ number_format($fc->monto_total, 0, ',', '.') }}</div>
                    <div style="font-size:0.7rem; color:{{ $vencida ? '#ef4444' : '#f59e0b' }}; font-weight:600;">
                        {{ $vencida ? 'Vencida hace '.abs($diasRestantes).' días' : 'Vence en '.abs($diasRestantes).' días' }}
                    </div>
                </div>
                <form method="POST" action="{{ route('finanzas.egresos.factura-compra.marcar-pagada', $fc->id) }}">
                    @csrf
                    <button type="submit" style="background:#10b981; color:#fff; border:none; padding:0.4rem 0.75rem; border-radius:6px; font-size:0.75rem; font-weight:600; cursor:pointer; white-space:nowrap;">
                        <i class="fas fa-check"></i> Pagada
                    </button>
                </form>
            </div>
            @endforeach
        </div>
        @else
        <p style="color:#94a3b8; text-align:center; padding:2rem 0; font-size:0.85rem;">No hay facturas pendientes de pago</p>
        @endif
    </div>

    <!-- Tabla completa -->
    <div style="background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.08); overflow:hidden;">
        <form method="POST" action="{{ route('finanzas.cuentas-pagar.marcar-pagadas') }}" id="cxpForm" onsubmit="return cxpConfirm()">
        @csrf
        <div style="padding:1rem 1.5rem; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
            <h3 style="margin:0; font-size:1rem; font-weight:700; color:#1e293b;">Todas las Facturas Pendientes</h3>
            <button type="submit" style="padding:0.5rem 1rem; background:#10b981; color:#fff; border:none; border-radius:9px; font-weight:700; font-size:0.78rem; cursor:pointer; box-shadow:0 3px 10px -4px rgba(16,185,129,0.5);"><i class="fas fa-check-double"></i> Marcar seleccionadas como pagadas</button>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:0.75rem; text-align:center; color:#64748b; width:36px;"><input type="checkbox" onclick="cxpToggleAll(this)" title="Seleccionar todas" style="cursor:pointer;"></th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Proveedor</th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">N° Factura</th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Emisión</th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Vencimiento</th>
                        <th style="padding:0.75rem; text-align:right; color:#64748b;">Total</th>
                        <th style="padding:0.75rem; text-align:center; color:#64748b;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($facturasPendientes as $fp)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:0.6rem 0.75rem; text-align:center;"><input type="checkbox" name="ids[]" value="{{ $fp->id }}" class="cxp-check" style="cursor:pointer;"></td>
                        <td style="padding:0.6rem 0.75rem; font-weight:500;">{{ $fp->proveedor_nombre }}</td>
                        <td style="padding:0.6rem 0.75rem;">{{ $fp->numero_factura }}</td>
                        <td style="padding:0.6rem 0.75rem;">{{ \Carbon\Carbon::parse($fp->fecha_emision)->format('d/m/Y') }}</td>
                        <td style="padding:0.6rem 0.75rem;">{{ $fp->fecha_vencimiento ? \Carbon\Carbon::parse($fp->fecha_vencimiento)->format('d/m/Y') : '-' }}</td>
                        <td style="padding:0.6rem 0.75rem; text-align:right; font-weight:600;">${{ number_format($fp->monto_total, 0, ',', '.') }}</td>
                        <td style="padding:0.6rem 0.75rem; text-align:center;">
                            @php $venc = $fp->fecha_vencimiento && \Carbon\Carbon::parse($fp->fecha_vencimiento)->isPast(); @endphp
                            <span style="background:{{ $venc ? '#ef4444' : '#f59e0b' }}20; color:{{ $venc ? '#ef4444' : '#f59e0b' }}; padding:0.15rem 0.5rem; border-radius:99px; font-size:0.7rem; font-weight:600;">
                                {{ $venc ? 'Vencida' : 'Pendiente' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="padding:2rem; text-align:center; color:#94a3b8;">No hay facturas pendientes de pago 🎉</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </form>
    </div>

    <!-- Gastos fijos mensuales -->
    <div style="background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.08); overflow:hidden; margin-top:1.5rem;">
        <div style="padding:1rem 1.5rem; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
            <h3 style="margin:0; font-size:1rem; font-weight:700; color:#1e293b;"><i class="fas fa-repeat" style="color:#8b5cf6;"></i> Gastos fijos mensuales</h3>
            <a href="{{ route('finanzas.egresos') }}" style="font-size:0.78rem; color:#8b5cf6; font-weight:600; text-decoration:none;">Administrar en Egresos →</a>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Concepto</th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Categoría</th>
                        <th style="padding:0.75rem; text-align:center; color:#64748b;">Día de pago</th>
                        <th style="padding:0.75rem; text-align:right; color:#64748b;">Monto mensual</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gastosOperativos as $go)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:0.6rem 0.75rem; font-weight:500;">{{ $go->concepto }}</td>
                        <td style="padding:0.6rem 0.75rem; color:#64748b;">{{ $go->categoria_nombre ?? '-' }}</td>
                        <td style="padding:0.6rem 0.75rem; text-align:center; color:#64748b;">{{ $go->dia_pago ? 'Día '.$go->dia_pago : '-' }}</td>
                        <td style="padding:0.6rem 0.75rem; text-align:right; font-weight:600;">${{ number_format($go->monto, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="padding:1.5rem; text-align:center; color:#94a3b8;">No hay gastos fijos registrados. Agrégalos en <a href="{{ route('finanzas.egresos') }}" style="color:#8b5cf6; font-weight:600;">Egresos</a> (arriendo, software, sueldos, etc.).</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    function cxpToggleAll(cb) { document.querySelectorAll('.cxp-check').forEach(function (c) { c.checked = cb.checked; }); }
    function cxpConfirm() {
        var n = document.querySelectorAll('.cxp-check:checked').length;
        if (n === 0) { alert('Selecciona al menos una factura para marcar como pagada.'); return false; }
        return confirm('¿Marcar ' + n + ' factura(s) como pagada(s)? Saldrán de Cuentas por Pagar.');
    }
</script>
</x-app-layout>
