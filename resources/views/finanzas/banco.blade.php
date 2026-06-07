<x-app-layout>
<x-slot name="header">Conciliación Bancaria</x-slot>

<div style="padding: 1.5rem;">
    @if(session('success'))
    <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.85rem;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.85rem;">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
    @endif

    <!-- Cuentas bancarias -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
        @forelse($cuentas as $cuenta)
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); border-left:4px solid {{ $cuenta->id == ($cuentaActiva->id ?? 0) ? '#FFC800' : '#e2e8f0' }}; cursor:pointer;" onclick="window.location='{{ route('finanzas.banco', ['cuenta_id' => $cuenta->id]) }}'">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">{{ $cuenta->banco }}</div>
                    <div style="font-size:0.85rem; font-weight:600; color:#1e293b; margin-top:0.15rem;">{{ $cuenta->titular }}</div>
                    <div style="font-size:0.7rem; color:#94a3b8;">{{ $cuenta->tipo_cuenta }} — {{ $cuenta->numero_cuenta }}</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:1.25rem; font-weight:700; color:{{ $cuenta->saldo_actual >= 0 ? '#10b981' : '#ef4444' }};">${{ number_format($cuenta->saldo_actual, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        @empty
        <div style="background:#fff; border-radius:12px; padding:2rem; text-align:center; color:#94a3b8; grid-column:span 3;">
            No hay cuentas bancarias registradas
        </div>
        @endforelse
        <div onclick="document.getElementById('modalCuenta').style.display='flex'" style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); border:2px dashed #e2e8f0; cursor:pointer; display:flex; align-items:center; justify-content:center; min-height:80px;">
            <span style="color:#94a3b8; font-weight:600;"><i class="fas fa-plus"></i> Nueva Cuenta</span>
        </div>
    </div>

    @if($cuentaActiva)
    <!-- Importar cartola -->
    <div style="background:#fff; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); margin-bottom:1.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
            <div>
                <h3 style="margin:0; font-size:1rem; font-weight:700; color:#1e293b;">
                    <i class="fas fa-university" style="color:#3b82f6;"></i> {{ $cuentaActiva->banco }} — {{ $cuentaActiva->titular }}
                </h3>
                <p style="margin:0.25rem 0 0; font-size:0.8rem; color:#94a3b8;">Importa la cartola en formato Excel (.xlsx, .xls) o CSV descargada desde tu banco</p>
            </div>
            <form method="POST" action="{{ route('finanzas.banco.importar') }}" enctype="multipart/form-data" style="display:flex; gap:0.75rem; align-items:center;">
                @csrf
                <input type="hidden" name="cuenta_id" value="{{ $cuentaActiva->id }}">
                <input type="file" name="archivo_cartola" accept=".xlsx,.xls,.csv" required style="padding:0.4rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.8rem;">
                <button type="submit" style="padding:0.5rem 1.5rem; background:#3b82f6; color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer; white-space:nowrap;">
                    <i class="fas fa-upload"></i> Importar Cartola
                </button>
            </form>
        </div>
    </div>

    <!-- Filtro de movimientos -->
    <form method="GET" style="display:flex; gap:1rem; align-items:center; margin-bottom:1rem; flex-wrap:wrap;">
        <input type="hidden" name="cuenta_id" value="{{ $cuentaActiva->id }}">
        <input type="date" name="desde" value="{{ $desde }}" style="padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
        <span style="color:#94a3b8;">hasta</span>
        <input type="date" name="hasta" value="{{ $hasta }}" style="padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
        <select name="estado_match" style="padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
            <option value="">Todos</option>
            <option value="conciliado" {{ request('estado_match') == 'conciliado' ? 'selected' : '' }}>Conciliados</option>
            <option value="pendiente" {{ request('estado_match') == 'pendiente' ? 'selected' : '' }}>Pendientes</option>
        </select>
        <button type="submit" style="padding:0.5rem 1.5rem; background:#FFC800; color:#000; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Filtrar</button>
    </form>

    <!-- Resumen -->
    <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:1rem; margin-bottom:1.5rem;">
        <div style="background:#fff; border-radius:12px; padding:1rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Total Movimientos</div>
            <div style="font-size:1.25rem; font-weight:700; color:#1e293b;">{{ $totalMovimientos }}</div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Conciliados</div>
            <div style="font-size:1.25rem; font-weight:700; color:#10b981;">{{ $conciliados }}</div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Pendientes</div>
            <div style="font-size:1.25rem; font-weight:700; color:#f59e0b;">{{ $pendientes }}</div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">% Conciliación</div>
            <div style="font-size:1.25rem; font-weight:700; color:#3b82f6;">{{ $totalMovimientos > 0 ? round(($conciliados / $totalMovimientos) * 100) : 0 }}%</div>
        </div>
    </div>

    <!-- Tabla de movimientos -->
    <div style="background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.08); overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Fecha</th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Descripción</th>
                        <th style="padding:0.75rem; text-align:right; color:#64748b;">Monto</th>
                        <th style="padding:0.75rem; text-align:center; color:#64748b;">Tipo</th>
                        <th style="padding:0.75rem; text-align:center; color:#64748b;">Estado</th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Match</th>
                        <th style="padding:0.75rem; text-align:center; color:#64748b;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimientos as $mov)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:0.6rem 0.75rem;">{{ \Carbon\Carbon::parse($mov->fecha)->format('d/m/Y') }}</td>
                        <td style="padding:0.6rem 0.75rem; max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $mov->descripcion }}</td>
                        <td style="padding:0.6rem 0.75rem; text-align:right; font-weight:600; color:{{ $mov->tipo == 'ingreso' ? '#10b981' : '#ef4444' }};">
                            {{ $mov->tipo == 'ingreso' ? '+' : '-' }}${{ number_format(abs($mov->monto), 0, ',', '.') }}
                        </td>
                        <td style="padding:0.6rem 0.75rem; text-align:center;">
                            <span style="background:{{ $mov->tipo == 'ingreso' ? '#10b981' : '#ef4444' }}20; color:{{ $mov->tipo == 'ingreso' ? '#10b981' : '#ef4444' }}; padding:0.15rem 0.5rem; border-radius:99px; font-size:0.7rem; font-weight:600;">{{ ucfirst($mov->tipo) }}</span>
                        </td>
                        <td style="padding:0.6rem 0.75rem; text-align:center;">
                            <span style="background:{{ $mov->estado_conciliacion == 'conciliado' ? '#10b981' : '#f59e0b' }}20; color:{{ $mov->estado_conciliacion == 'conciliado' ? '#10b981' : '#f59e0b' }}; padding:0.15rem 0.5rem; border-radius:99px; font-size:0.7rem; font-weight:600;">{{ ucfirst($mov->estado_conciliacion) }}</span>
                        </td>
                        <td style="padding:0.6rem 0.75rem; font-size:0.75rem; color:#64748b;">
                            @if($mov->match_descripcion)
                                <i class="fas fa-link" style="color:#10b981;"></i> {{ $mov->match_descripcion }}
                            @else
                                —
                            @endif
                        </td>
                        <td style="padding:0.6rem 0.75rem; text-align:center;">
                            @if($mov->estado_conciliacion == 'pendiente')
                            <button onclick="abrirMatchModal({{ $mov->id }}, '{{ addslashes($mov->descripcion) }}', {{ $mov->monto }}, '{{ $mov->tipo }}')" style="background:#3b82f6; color:#fff; border:none; padding:0.3rem 0.75rem; border-radius:6px; font-size:0.7rem; font-weight:600; cursor:pointer;">
                                <i class="fas fa-link"></i> Match
                            </button>
                            @else
                            <span style="color:#10b981; font-size:0.75rem;"><i class="fas fa-check"></i></span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="padding:2rem; text-align:center; color:#94a3b8;">No hay movimientos para el período seleccionado. Importa una cartola para comenzar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($movimientos instanceof \Illuminate\Pagination\LengthAwarePaginator && $movimientos->hasPages())
        <div style="padding:1rem; border-top:1px solid #f1f5f9;">{{ $movimientos->withQueryString()->links() }}</div>
        @endif
    </div>
    @endif
</div>

<!-- Modal Nueva Cuenta -->
<div id="modalCuenta" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:50; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:2rem; max-width:450px; width:90%;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:700;">Nueva Cuenta Bancaria</h3>
            <button onclick="document.getElementById('modalCuenta').style.display='none'" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="{{ route('finanzas.banco.cuenta.store') }}">
            @csrf
            <div style="display:flex; flex-direction:column; gap:1rem;">
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Nombre de la cuenta *</label>
                    <input type="text" name="titular" required placeholder="Ej: Nombre del titular" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Banco *</label>
                    <select name="banco" required style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                        <option value="">Seleccionar...</option>
                        <option value="BCI">BCI</option>
                        <option value="Banco de Chile">Banco de Chile</option>
                        <option value="Banco Estado">Banco Estado</option>
                        <option value="Banco Santander">Banco Santander</option>
                        <option value="Banco Itaú">Banco Itaú</option>
                        <option value="Banco Scotiabank">Banco Scotiabank</option>
                        <option value="Banco BICE">Banco BICE</option>
                        <option value="Banco Security">Banco Security</option>
                        <option value="Banco Falabella">Banco Falabella</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Tipo de Cuenta *</label>
                    <select name="tipo_cuenta" required style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                        <option value="corriente">Cuenta Corriente</option>
                        <option value="vista">Cuenta Vista</option>
                        <option value="ahorro">Cuenta de Ahorro</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Número de Cuenta</label>
                    <input type="text" name="numero_cuenta" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Saldo Inicial</label>
                    <input type="number" name="saldo_actual" value="0" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:1.5rem;">
                <button type="button" onclick="document.getElementById('modalCuenta').style.display='none'" style="padding:0.5rem 1.5rem; background:#f1f5f9; color:#475569; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" style="padding:0.5rem 1.5rem; background:#3b82f6; color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Crear</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Match Manual -->
<div id="modalMatch" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:50; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:2rem; max-width:500px; width:90%;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:700;">Conciliar Movimiento</h3>
            <button onclick="document.getElementById('modalMatch').style.display='none'" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <div id="matchInfo" style="background:#f8fafc; padding:1rem; border-radius:8px; margin-bottom:1rem; font-size:0.85rem;"></div>
        <form method="POST" action="{{ route('finanzas.banco.match') }}">
            @csrf
            <input type="hidden" name="movimiento_id" id="matchMovId">
            <div style="display:flex; flex-direction:column; gap:1rem;">
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Tipo de Match</label>
                    <select name="tipo_match" id="tipoMatch" onchange="toggleMatchFields()" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                        <option value="cobro_agencia">Cobro de Agencia</option>
                        <option value="pago_suscripcion">Pago de Suscripción</option>
                        <option value="factura_compra">Factura de Compra</option>
                        <option value="manual">Descripción Manual</option>
                    </select>
                </div>
                <div id="matchManualField" style="display:none;">
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Descripción del Match</label>
                    <input type="text" name="match_descripcion" placeholder="Ej: Pago arriendo oficina" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
                <div id="matchRefField">
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">ID de Referencia</label>
                    <input type="text" name="referencia_id" placeholder="ID del cobro, suscripción o factura" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:1.5rem;">
                <button type="button" onclick="document.getElementById('modalMatch').style.display='none'" style="padding:0.5rem 1.5rem; background:#f1f5f9; color:#475569; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" style="padding:0.5rem 1.5rem; background:#10b981; color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Conciliar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirMatchModal(id, desc, monto, tipo) {
    document.getElementById('matchMovId').value = id;
    document.getElementById('matchInfo').innerHTML = '<strong>' + desc + '</strong><br>Monto: ' + (tipo=='ingreso'?'+':'-') + '$' + Math.abs(monto).toLocaleString('es-CL');
    document.getElementById('modalMatch').style.display = 'flex';
}
function toggleMatchFields() {
    var tipo = document.getElementById('tipoMatch').value;
    document.getElementById('matchManualField').style.display = tipo === 'manual' ? 'block' : 'none';
    document.getElementById('matchRefField').style.display = tipo !== 'manual' ? 'block' : 'none';
}
</script>
</x-app-layout>
