<x-app-layout>
<x-slot name="header">Ingresos</x-slot>

<div style="padding: 1.5rem;">
    @if(session('success'))
    <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.85rem;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif

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
        <button type="button" onclick="document.getElementById('modalIngreso').style.display='flex'" style="padding:0.5rem 1.5rem; background:#10b981; color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer; margin-left:auto;">
            <i class="fas fa-plus"></i> Ingreso Manual
        </button>
    </form>

    <!-- Tarjetas resumen -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center; border-top:3px solid #10b981;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Total Ingresos</div>
            <div style="font-size:1.5rem; font-weight:700; color:#10b981;">${{ number_format($totalIngresos, 0, ',', '.') }}</div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Boletas</div>
            <div style="font-size:1.25rem; font-weight:700; color:#1e293b;">${{ number_format($ingresoBoletas, 0, ',', '.') }}</div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Facturas Venta</div>
            <div style="font-size:1.25rem; font-weight:700; color:#1e293b;">${{ number_format($ingresoFacturas, 0, ',', '.') }}</div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Cobros Agencia</div>
            <div style="font-size:1.25rem; font-weight:700; color:#1e293b;">${{ number_format($ingresoCobrosAgencia, 0, ',', '.') }}</div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Suscripciones</div>
            <div style="font-size:1.25rem; font-weight:700; color:#1e293b;">${{ number_format($ingresoPayments, 0, ',', '.') }}</div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Manuales</div>
            <div style="font-size:1.25rem; font-weight:700; color:#1e293b;">${{ number_format($ingresosManuales, 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Tabla de ingresos detallados -->
    <div style="background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.08); overflow:hidden;">
        <div style="padding:1rem 1.5rem; border-bottom:1px solid #f1f5f9;">
            <h3 style="margin:0; font-size:1rem; font-weight:700; color:#1e293b;">Detalle de Ingresos</h3>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Fecha</th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Fuente</th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Descripción</th>
                        <th style="padding:0.75rem; text-align:right; color:#64748b;">Neto</th>
                        <th style="padding:0.75rem; text-align:right; color:#64748b;">IVA</th>
                        <th style="padding:0.75rem; text-align:right; color:#64748b;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($detalleIngresos as $ing)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:0.6rem 0.75rem;">{{ $ing['fecha'] }}</td>
                        <td style="padding:0.6rem 0.75rem;">
                            @php $fuenteColors = ['Boleta'=>'#10b981','Factura'=>'#3b82f6','Cobro Agencia'=>'#8b5cf6','Suscripción'=>'#06b6d4','Manual'=>'#f59e0b']; @endphp
                            <span style="background:{{ $fuenteColors[$ing['fuente']] ?? '#94a3b8' }}20; color:{{ $fuenteColors[$ing['fuente']] ?? '#94a3b8' }}; padding:0.15rem 0.5rem; border-radius:99px; font-size:0.7rem; font-weight:600;">{{ $ing['fuente'] }}</span>
                        </td>
                        <td style="padding:0.6rem 0.75rem;">{{ $ing['descripcion'] }}</td>
                        <td style="padding:0.6rem 0.75rem; text-align:right;">${{ number_format($ing['neto'], 0, ',', '.') }}</td>
                        <td style="padding:0.6rem 0.75rem; text-align:right;">${{ number_format($ing['iva'], 0, ',', '.') }}</td>
                        <td style="padding:0.6rem 0.75rem; text-align:right; font-weight:600; color:#10b981;">${{ number_format($ing['total'], 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="padding:2rem; text-align:center; color:#94a3b8;">No hay ingresos registrados este mes</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Ingreso Manual -->
<div id="modalIngreso" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:50; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:2rem; max-width:450px; width:90%;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:700;">Registrar Ingreso Manual</h3>
            <button onclick="document.getElementById('modalIngreso').style.display='none'" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="{{ route('finanzas.ingresos.manual.store') }}">
            @csrf
            <div style="display:flex; flex-direction:column; gap:1rem;">
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Descripción *</label>
                    <input type="text" name="descripcion" required placeholder="Ej: Pago consultoría externa" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Monto Total *</label>
                    <input type="number" name="monto" required style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Fecha *</label>
                    <input type="date" name="fecha" required value="{{ now()->format('Y-m-d') }}" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Centro de Costo</label>
                    <select name="centro_costo_id" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                        <option value="">Seleccionar...</option>
                        @foreach($centrosCosto as $cc)
                        <option value="{{ $cc->id }}">{{ $cc->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Notas</label>
                    <textarea name="notas" rows="2" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; resize:vertical;"></textarea>
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:1.5rem;">
                <button type="button" onclick="document.getElementById('modalIngreso').style.display='none'" style="padding:0.5rem 1.5rem; background:#f1f5f9; color:#475569; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" style="padding:0.5rem 1.5rem; background:#10b981; color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Guardar</button>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
