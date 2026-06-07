<x-app-layout>
<x-slot name="header">Presupuesto y KPIs</x-slot>

<div style="padding: 1.5rem;">
    @if(session('success'))
    <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.85rem;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif

    <!-- KPIs -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:2rem;">
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); border-top:3px solid #06b6d4;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">MRR (Ingresos Recurrentes)</div>
            <div style="font-size:1.5rem; font-weight:700; color:#06b6d4; margin-top:0.25rem;">${{ number_format($mrr, 0, ',', '.') }}</div>
            <div style="font-size:0.7rem; color:#94a3b8;">Suscripciones activas</div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); border-top:3px solid #10b981;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Margen Operacional</div>
            <div style="font-size:1.5rem; font-weight:700; color:#10b981; margin-top:0.25rem;">{{ $margenOperacional }}%</div>
            <div style="font-size:0.7rem; color:#94a3b8;">(Ingresos - Gastos) / Ingresos</div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); border-top:3px solid #f59e0b;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Ticket Promedio</div>
            <div style="font-size:1.5rem; font-weight:700; color:#f59e0b; margin-top:0.25rem;">${{ number_format($ticketPromedio, 0, ',', '.') }}</div>
            <div style="font-size:0.7rem; color:#94a3b8;">Promedio por venta</div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); border-top:3px solid #8b5cf6;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Clientes Activos</div>
            <div style="font-size:1.5rem; font-weight:700; color:#8b5cf6; margin-top:0.25rem;">{{ $clientesActivos }}</div>
            <div style="font-size:0.7rem; color:#94a3b8;">Con suscripción vigente</div>
        </div>
    </div>

    <!-- Presupuesto vs Real -->
    <div style="background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); margin-bottom:2rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h3 style="margin:0; font-size:1rem; font-weight:700; color:#1e293b;"><i class="fas fa-calculator" style="color:#3b82f6;"></i> Presupuesto vs. Real — {{ now()->translatedFormat('F Y') }}</h3>
            <button onclick="document.getElementById('modalPresupuesto').style.display='flex'" style="padding:0.4rem 1rem; background:#3b82f6; color:#fff; border:none; border-radius:8px; font-size:0.8rem; font-weight:600; cursor:pointer;">
                <i class="fas fa-edit"></i> Editar Presupuesto
            </button>
        </div>
        <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:0.75rem; text-align:left; color:#64748b;">Categoría</th>
                    <th style="padding:0.75rem; text-align:right; color:#64748b;">Presupuesto</th>
                    <th style="padding:0.75rem; text-align:right; color:#64748b;">Real</th>
                    <th style="padding:0.75rem; text-align:right; color:#64748b;">Diferencia</th>
                    <th style="padding:0.75rem; text-align:center; color:#64748b;">Uso</th>
                </tr>
            </thead>
            <tbody>
                @forelse($presupuestoItems as $item)
                @php $diff = $item['presupuesto'] - $item['real']; $pct = $item['presupuesto'] > 0 ? round(($item['real'] / $item['presupuesto']) * 100) : 0; @endphp
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:0.6rem 0.75rem; font-weight:500;">{{ $item['categoria'] }}</td>
                    <td style="padding:0.6rem 0.75rem; text-align:right;">${{ number_format($item['presupuesto'], 0, ',', '.') }}</td>
                    <td style="padding:0.6rem 0.75rem; text-align:right; font-weight:600;">${{ number_format($item['real'], 0, ',', '.') }}</td>
                    <td style="padding:0.6rem 0.75rem; text-align:right; color:{{ $diff >= 0 ? '#10b981' : '#ef4444' }}; font-weight:600;">
                        {{ $diff >= 0 ? '+' : '' }}${{ number_format($diff, 0, ',', '.') }}
                    </td>
                    <td style="padding:0.6rem 0.75rem;">
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <div style="flex:1; background:#f1f5f9; border-radius:99px; height:8px; overflow:hidden;">
                                <div style="background:{{ $pct > 100 ? '#ef4444' : ($pct > 80 ? '#f59e0b' : '#10b981') }}; height:100%; border-radius:99px; width:{{ min($pct, 100) }}%;"></div>
                            </div>
                            <span style="font-size:0.7rem; font-weight:600; color:{{ $pct > 100 ? '#ef4444' : '#64748b' }};">{{ $pct }}%</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="padding:2rem; text-align:center; color:#94a3b8;">Define un presupuesto para comenzar a comparar</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Flujo de Caja Proyectado -->
    <div style="background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
        <h3 style="margin:0 0 1rem; font-size:1rem; font-weight:700; color:#1e293b;"><i class="fas fa-chart-line" style="color:#8b5cf6;"></i> Flujo de Caja Proyectado (Próximos 90 Días)</h3>
        <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:0.75rem; text-align:left; color:#64748b;">Período</th>
                    <th style="padding:0.75rem; text-align:right; color:#64748b;">Ingresos Esperados</th>
                    <th style="padding:0.75rem; text-align:right; color:#64748b;">Egresos Esperados</th>
                    <th style="padding:0.75rem; text-align:right; color:#64748b;">Saldo Proyectado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($flujoCaja as $fc)
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:0.6rem 0.75rem; font-weight:500;">{{ $fc['periodo'] }}</td>
                    <td style="padding:0.6rem 0.75rem; text-align:right; color:#10b981; font-weight:600;">${{ number_format($fc['ingresos'], 0, ',', '.') }}</td>
                    <td style="padding:0.6rem 0.75rem; text-align:right; color:#ef4444; font-weight:600;">${{ number_format($fc['egresos'], 0, ',', '.') }}</td>
                    <td style="padding:0.6rem 0.75rem; text-align:right; font-weight:700; color:{{ $fc['saldo'] >= 0 ? '#3b82f6' : '#ef4444' }};">${{ number_format($fc['saldo'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Presupuesto -->
<div id="modalPresupuesto" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:50; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:2rem; max-width:500px; width:90%; max-height:90vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:700;">Definir Presupuesto Mensual</h3>
            <button onclick="document.getElementById('modalPresupuesto').style.display='none'" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="{{ route('finanzas.presupuesto.store') }}">
            @csrf
            <div style="display:flex; flex-direction:column; gap:1rem;">
                @foreach($categorias as $cat)
                <div style="display:flex; align-items:center; gap:1rem;">
                    <label style="font-size:0.85rem; font-weight:500; color:#475569; min-width:150px;">{{ $cat->nombre }}</label>
                    <input type="number" name="presupuesto[{{ $cat->id }}]" value="{{ $cat->presupuesto_mensual ?? 0 }}" style="flex:1; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
                </div>
                @endforeach
            </div>
            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:1.5rem;">
                <button type="button" onclick="document.getElementById('modalPresupuesto').style.display='none'" style="padding:0.5rem 1.5rem; background:#f1f5f9; color:#475569; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" style="padding:0.5rem 1.5rem; background:#3b82f6; color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Guardar</button>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
