<x-app-layout>
<x-slot name="header">Centros de Costo</x-slot>

<div style="padding: 1.5rem;">
    @if(session('success'))
    <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.85rem;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
        <div>
            <h2 style="margin:0; font-size:1.25rem; font-weight:700; color:#1e293b;">Centros de Costo</h2>
            <p style="margin:0.25rem 0 0; font-size:0.85rem; color:#94a3b8;">Clasifica ingresos y gastos por línea de negocio</p>
        </div>
        <button onclick="document.getElementById('modalCC').style.display='flex'" style="padding:0.6rem 1.5rem; background:#3b82f6; color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer; font-size:0.85rem;">
            <i class="fas fa-plus"></i> Nuevo Centro
        </button>
    </div>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:1rem;">
        @forelse($centrosCosto as $cc)
        <div style="background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); border-left:4px solid {{ $cc->color ?? '#94a3b8' }};">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <h3 style="margin:0; font-size:1rem; font-weight:700; color:#1e293b;">{{ $cc->nombre }}</h3>
                <span style="background:{{ $cc->activo ? '#10b981' : '#94a3b8' }}20; color:{{ $cc->activo ? '#10b981' : '#94a3b8' }}; padding:0.15rem 0.5rem; border-radius:99px; font-size:0.7rem; font-weight:600;">{{ $cc->activo ? 'Activo' : 'Inactivo' }}</span>
            </div>
            @if($cc->descripcion)
            <p style="margin:0 0 1rem; font-size:0.8rem; color:#64748b;">{{ $cc->descripcion }}</p>
            @endif
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem; font-size:0.8rem;">
                <div style="padding:0.5rem; background:#f8fafc; border-radius:6px; text-align:center;">
                    <div style="font-size:0.65rem; color:#64748b;">Ingresos</div>
                    <div style="font-weight:700; color:#10b981;">${{ number_format($cc->total_ingresos ?? 0, 0, ',', '.') }}</div>
                </div>
                <div style="padding:0.5rem; background:#f8fafc; border-radius:6px; text-align:center;">
                    <div style="font-size:0.65rem; color:#64748b;">Egresos</div>
                    <div style="font-weight:700; color:#ef4444;">${{ number_format($cc->total_egresos ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>
            @if($cc->presupuesto_mensual)
            <div style="margin-top:0.75rem;">
                @php $usoPct = $cc->presupuesto_mensual > 0 ? round((($cc->total_egresos ?? 0) / $cc->presupuesto_mensual) * 100) : 0; @endphp
                <div style="display:flex; justify-content:space-between; font-size:0.7rem; color:#64748b; margin-bottom:0.25rem;">
                    <span>Presupuesto: ${{ number_format($cc->presupuesto_mensual, 0, ',', '.') }}</span>
                    <span>{{ $usoPct }}%</span>
                </div>
                <div style="background:#f1f5f9; border-radius:99px; height:6px; overflow:hidden;">
                    <div style="background:{{ $usoPct > 100 ? '#ef4444' : ($usoPct > 80 ? '#f59e0b' : '#10b981') }}; height:100%; border-radius:99px; width:{{ min($usoPct, 100) }}%;"></div>
                </div>
            </div>
            @endif
        </div>
        @empty
        <div style="background:#fff; border-radius:12px; padding:3rem; text-align:center; grid-column:span 3; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
            <p style="color:#94a3b8;">No hay centros de costo. Crea uno para clasificar tus ingresos y gastos.</p>
        </div>
        @endforelse
    </div>
</div>

<!-- Modal Crear Centro de Costo -->
<div id="modalCC" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:50; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:2rem; max-width:450px; width:90%;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:700;">Nuevo Centro de Costo</h3>
            <button onclick="document.getElementById('modalCC').style.display='none'" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="{{ route('finanzas.centros-costo.store') }}">
            @csrf
            <div style="display:flex; flex-direction:column; gap:1rem;">
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Nombre *</label>
                    <input type="text" name="nombre" required placeholder="Ej: Integraciones Shopify" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Descripción</label>
                    <textarea name="descripcion" rows="2" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; resize:vertical;"></textarea>
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Color</label>
                    <input type="color" name="color" value="#3b82f6" style="width:60px; height:36px; border:1px solid #e2e8f0; border-radius:8px; cursor:pointer;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Presupuesto Mensual</label>
                    <input type="number" name="presupuesto_mensual" value="0" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:1.5rem;">
                <button type="button" onclick="document.getElementById('modalCC').style.display='none'" style="padding:0.5rem 1.5rem; background:#f1f5f9; color:#475569; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" style="padding:0.5rem 1.5rem; background:#3b82f6; color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Crear</button>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
