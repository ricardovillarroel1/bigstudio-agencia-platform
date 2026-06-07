<x-app-layout>
<x-slot name="header">Cuentas por Pagar</x-slot>

<div style="padding: 1.5rem;">
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
        <div style="padding:1rem 1.5rem; border-bottom:1px solid #f1f5f9;">
            <h3 style="margin:0; font-size:1rem; font-weight:700; color:#1e293b;">Todas las Facturas Pendientes</h3>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                <thead>
                    <tr style="background:#f8fafc;">
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
                    <tr><td colspan="6" style="padding:2rem; text-align:center; color:#94a3b8;">No hay facturas pendientes de pago</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-app-layout>
