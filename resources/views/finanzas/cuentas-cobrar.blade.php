<x-app-layout>
<x-slot name="header">Cuentas por Cobrar</x-slot>

<div style="padding: 1.5rem;">
    <!-- Tarjetas resumen -->
    <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:1rem; margin-bottom:1.5rem;">
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center; border-top:3px solid #f59e0b;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Total Pendiente</div>
            <div style="font-size:1.5rem; font-weight:700; color:#f59e0b;">${{ number_format($totalPendiente, 0, ',', '.') }}</div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Vencidas</div>
            <div style="font-size:1.5rem; font-weight:700; color:#ef4444;">${{ number_format($totalVencido, 0, ',', '.') }}</div>
            <div style="font-size:0.7rem; color:#ef4444;">{{ $countVencidas }} docs</div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Por Vencer (30 días)</div>
            <div style="font-size:1.5rem; font-weight:700; color:#f59e0b;">${{ number_format($totalPorVencer, 0, ',', '.') }}</div>
            <div style="font-size:0.7rem; color:#f59e0b;">{{ $countPorVencer }} docs</div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Cobrado este Mes</div>
            <div style="font-size:1.5rem; font-weight:700; color:#10b981;">${{ number_format($cobradoMes, 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Aging Report -->
    <div style="background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); margin-bottom:1.5rem;">
        <h3 style="margin:0 0 1rem; font-size:1rem; font-weight:700; color:#1e293b;"><i class="fas fa-clock" style="color:#f59e0b;"></i> Antigüedad de Saldos (Aging Report)</h3>
        <div style="display:grid; grid-template-columns: repeat(5, 1fr); gap:0.5rem;">
            @foreach($aging as $rango => $datos)
            <div style="padding:1rem; background:{{ $datos['color'] }}10; border-radius:8px; text-align:center; border:1px solid {{ $datos['color'] }}30;">
                <div style="font-size:0.7rem; color:#64748b; font-weight:600;">{{ $rango }}</div>
                <div style="font-size:1.1rem; font-weight:700; color:{{ $datos['color'] }}; margin-top:0.25rem;">${{ number_format($datos['monto'], 0, ',', '.') }}</div>
                <div style="font-size:0.65rem; color:#94a3b8;">{{ $datos['count'] }} docs</div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Tabla de cuentas por cobrar -->
    <div style="background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.08); overflow:hidden;">
        <div style="padding:1rem 1.5rem; border-bottom:1px solid #f1f5f9;">
            <h3 style="margin:0; font-size:1rem; font-weight:700; color:#1e293b;">Documentos Pendientes de Cobro</h3>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Fecha</th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Tipo</th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Cliente</th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Descripción</th>
                        <th style="padding:0.75rem; text-align:right; color:#64748b;">Monto</th>
                        <th style="padding:0.75rem; text-align:center; color:#64748b;">Días</th>
                        <th style="padding:0.75rem; text-align:center; color:#64748b;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cuentasCobrar as $cc)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:0.6rem 0.75rem;">{{ $cc['fecha'] }}</td>
                        <td style="padding:0.6rem 0.75rem;">
                            <span style="background:#3b82f620; color:#3b82f6; padding:0.15rem 0.5rem; border-radius:99px; font-size:0.7rem; font-weight:600;">{{ $cc['tipo'] }}</span>
                        </td>
                        <td style="padding:0.6rem 0.75rem; font-weight:500;">{{ $cc['cliente'] }}</td>
                        <td style="padding:0.6rem 0.75rem; color:#64748b;">{{ $cc['descripcion'] }}</td>
                        <td style="padding:0.6rem 0.75rem; text-align:right; font-weight:600;">${{ number_format($cc['monto'], 0, ',', '.') }}</td>
                        <td style="padding:0.6rem 0.75rem; text-align:center;">
                            <span style="color:{{ $cc['dias'] > 30 ? '#ef4444' : ($cc['dias'] > 15 ? '#f59e0b' : '#10b981') }}; font-weight:600;">{{ $cc['dias'] }}d</span>
                        </td>
                        <td style="padding:0.6rem 0.75rem; text-align:center;">
                            <span style="background:{{ $cc['dias'] > 30 ? '#ef4444' : '#f59e0b' }}20; color:{{ $cc['dias'] > 30 ? '#ef4444' : '#f59e0b' }}; padding:0.15rem 0.5rem; border-radius:99px; font-size:0.7rem; font-weight:600;">
                                {{ $cc['dias'] > 30 ? 'Vencida' : 'Pendiente' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="padding:2rem; text-align:center; color:#94a3b8;">No hay cuentas por cobrar pendientes</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-app-layout>
