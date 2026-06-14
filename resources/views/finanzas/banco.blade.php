<x-app-layout>
<x-slot name="header">Banco y Conciliación</x-slot>

<div style="padding: 1.5rem;">
    @if(session('success'))
    <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.85rem;"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.85rem;"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
    @endif

    <!-- Período (aplica al instante) -->
    <div style="margin-bottom:1.5rem;">
        @include('finanzas._periodo', ['ruta' => 'finanzas.banco', 'mes' => $mes, 'anio' => $anio])
    </div>

    <!-- Saldo y resumen del período -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:1.25rem;">
        <div style="background:linear-gradient(135deg,#0f172a,#1e293b); border-radius:14px; padding:1.4rem; color:#fff; box-shadow:0 6px 18px -8px rgba(15,23,42,0.5);">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:0.5rem;">
                <div style="font-size:0.7rem; color:#cbd5e1; text-transform:uppercase; font-weight:600; letter-spacing:0.04em;">Saldo de la cuenta{{ $cuentaActiva ? ' · '.$cuentaActiva->banco : '' }}</div>
                @if($cuentaActiva)
                <button onclick="document.getElementById('modalSaldo').style.display='flex'" style="background:rgba(255,255,255,0.12); color:#fff; border:none; border-radius:7px; font-size:0.68rem; font-weight:700; padding:0.25rem 0.55rem; cursor:pointer;"><i class="fas fa-pen"></i> Actualizar</button>
                @endif
            </div>
            <div style="font-size:1.9rem; font-weight:800; margin-top:0.3rem; color:{{ ($saldoProyectado ?? 0) >= 0 ? '#4ade80' : '#f87171' }};">{{ $saldoProyectado === null ? '—' : '$'.number_format($saldoProyectado, 0, ',', '.') }}</div>
            <div style="font-size:0.68rem; color:#94a3b8; margin-top:0.2rem;">
                @if($saldoCuentaFecha)
                    Proyectado · ancla ${{ number_format($saldoCuenta, 0, ',', '.') }} del {{ \Carbon\Carbon::parse($saldoCuentaFecha)->format('d/m/Y') }}@if($movPostAncla > 0) <span style="color:#cbd5e1;">{{ $ajusteDesdeAncla >= 0 ? '+' : '−' }}${{ number_format(abs($ajusteDesdeAncla), 0, ',', '.') }}</span> ({{ $movPostAncla }} mov. nuevos)@endif
                @else
                    Ingresa tu saldo real (botón Actualizar) y se proyectará solo con los movimientos nuevos
                @endif
            </div>
            <div style="font-size:0.66rem; color:#94a3b8; margin-top:0.55rem; border-top:1px solid rgba(255,255,255,0.08); padding-top:0.45rem;">Flujo neto según Finanzas: <strong style="color:#cbd5e1;">${{ number_format($flujoNetoFinanzas, 0, ',', '.') }}</strong> <span style="opacity:0.65;">(referencial)</span></div>
        </div>
        <div style="background:#fff; border-radius:14px; padding:1.4rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); border-top:3px solid #10b981;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Ingresos del mes</div>
            <div style="font-size:1.5rem; font-weight:700; color:#10b981; margin-top:0.25rem;">+${{ number_format($ingresosMes, 0, ',', '.') }}</div>
        </div>
        <div style="background:#fff; border-radius:14px; padding:1.4rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); border-top:3px solid #ef4444;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Egresos del mes</div>
            <div style="font-size:1.5rem; font-weight:700; color:#ef4444; margin-top:0.25rem;">−${{ number_format($egresosMes, 0, ',', '.') }}</div>
        </div>
        @php $resultadoMes = $ingresosMes - $egresosMes; @endphp
        <div style="background:#fff; border-radius:14px; padding:1.4rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); border-top:3px solid #3b82f6;">
            <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">Resultado del mes</div>
            <div style="font-size:1.5rem; font-weight:700; color:{{ $resultadoMes >= 0 ? '#3b82f6' : '#ef4444' }}; margin-top:0.25rem;">{{ $resultadoMes >= 0 ? '+' : '−' }}${{ number_format(abs($resultadoMes), 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Cuentas bancarias -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
        @forelse($cuentas as $cuenta)
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); border-left:4px solid {{ $cuenta->id == ($cuentaActiva->id ?? 0) ? '#FFC800' : '#e2e8f0' }};">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-size:0.7rem; color:#64748b; text-transform:uppercase; font-weight:600;">{{ $cuenta->banco }}</div>
                    <div style="font-size:0.85rem; font-weight:600; color:#1e293b; margin-top:0.15rem;">{{ $cuenta->titular }}</div>
                    <div style="font-size:0.7rem; color:#94a3b8;">{{ ucfirst($cuenta->tipo_cuenta) }} — {{ $cuenta->numero_cuenta }}</div>
                </div>
                <div style="text-align:right;">
                    <i class="fas fa-university" style="font-size:1.6rem; color:#e2e8f0;"></i>
                </div>
            </div>
        </div>
        @empty
        <div style="background:#fff; border-radius:12px; padding:2rem; text-align:center; color:#94a3b8; grid-column:1/-1;">
            No hay cuentas bancarias registradas
        </div>
        @endforelse
        <div onclick="document.getElementById('modalCuenta').style.display='flex'" style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); border:2px dashed #e2e8f0; cursor:pointer; display:flex; align-items:center; justify-content:center; min-height:80px;">
            <span style="color:#94a3b8; font-weight:600;"><i class="fas fa-plus"></i> Nueva Cuenta</span>
        </div>
    </div>

    <!-- Estado de cuenta automático (según Finanzas) -->
    <div style="background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.08); overflow:hidden; margin-bottom:1.5rem;">
        <div style="padding:1rem 1.5rem; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
            <div>
                <h3 style="margin:0; font-size:1rem; font-weight:700; color:#1e293b;"><i class="fas fa-list-ul" style="color:#3b82f6;"></i> Estado de cuenta (según Finanzas)</h3>
                <p style="margin:0.2rem 0 0; font-size:0.76rem; color:#94a3b8;">Movimientos detectados automáticamente: ingresos cobrados y egresos pagados del período.</p>
            </div>
            <div style="text-align:right;">
                <div style="font-size:0.7rem; color:#94a3b8;">Acumulado inicial (Finanzas)</div>
                <div style="font-size:0.95rem; font-weight:700; color:#475569;">${{ number_format($saldoInicial, 0, ',', '.') }}</div>
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Fecha</th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Detalle</th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Origen</th>
                        <th style="padding:0.75rem; text-align:right; color:#64748b;">Cargo / Abono</th>
                        <th style="padding:0.75rem; text-align:right; color:#64748b;">Acumul. (Finanzas)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimientosMes as $m)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:0.6rem 0.75rem; white-space:nowrap;">{{ \Carbon\Carbon::parse($m['fecha'])->format('d/m/Y') }}</td>
                        <td style="padding:0.6rem 0.75rem;">{{ $m['descripcion'] }}</td>
                        <td style="padding:0.6rem 0.75rem;"><span style="background:#eef2ff; color:#4338ca; padding:0.12rem 0.5rem; border-radius:99px; font-size:0.68rem; font-weight:600;">{{ $m['origen'] }}</span></td>
                        <td style="padding:0.6rem 0.75rem; text-align:right; font-weight:600; color:{{ $m['tipo'] === 'ingreso' ? '#10b981' : '#ef4444' }};">
                            {{ $m['tipo'] === 'ingreso' ? '+' : '−' }}${{ number_format($m['monto'], 0, ',', '.') }}
                        </td>
                        <td style="padding:0.6rem 0.75rem; text-align:right; font-weight:700; color:{{ $m['saldo'] >= 0 ? '#1e293b' : '#ef4444' }};">${{ number_format($m['saldo'], 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="padding:2.5rem 1rem; text-align:center; color:#94a3b8;">No hay movimientos en este período. Se generan solos a medida que se cobran ingresos o se pagan egresos.</td></tr>
                    @endforelse
                </tbody>
                @if(count($movimientosMes) > 0)
                <tfoot>
                    <tr style="background:#f8fafc; border-top:2px solid #e2e8f0;">
                        <td colspan="4" style="padding:0.75rem; text-align:right; font-weight:700; color:#475569;">Flujo acumulado del período</td>
                        <td style="padding:0.75rem; text-align:right; font-weight:800; color:{{ $saldoActual >= 0 ? '#10b981' : '#ef4444' }};">${{ number_format($saldoActual, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- Conciliación con cartola real del banco -->
    @if($cuentaActiva)
    <div style="background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.08); overflow:hidden;">
        <div style="padding:1.25rem 1.5rem; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
            <div>
                <h3 style="margin:0; font-size:1rem; font-weight:700; color:#1e293b;"><i class="fas fa-scale-balanced" style="color:#8b5cf6;"></i> Conciliación con la cartola del banco</h3>
                <p style="margin:0.2rem 0 0; font-size:0.76rem; color:#94a3b8;">Importa la cartola ({{ $cuentaActiva->banco }}) y concíliala contra los movimientos de Finanzas.
                    @if($statsCartola['total'] > 0) · {{ $statsCartola['conciliados'] }}/{{ $statsCartola['total'] }} conciliados · {{ $statsCartola['pendientes'] }} pendientes @endif
                </p>
            </div>
            <form method="POST" action="{{ route('finanzas.banco.importar') }}" enctype="multipart/form-data" style="display:flex; gap:0.6rem; align-items:center;">
                @csrf
                <input type="hidden" name="cuenta_id" value="{{ $cuentaActiva->id }}">
                <input type="file" name="archivo" accept=".xlsx,.xls,.csv,.txt" required style="padding:0.4rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.78rem; max-width:220px;">
                <button type="submit" style="padding:0.5rem 1.2rem; background:#8b5cf6; color:#fff; border:none; border-radius:8px; font-weight:700; font-size:0.8rem; cursor:pointer; white-space:nowrap;"><i class="fas fa-upload"></i> Importar</button>
            </form>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Fecha</th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Descripción</th>
                        <th style="padding:0.75rem; text-align:right; color:#64748b;">Monto</th>
                        <th style="padding:0.75rem; text-align:center; color:#64748b;">Estado</th>
                        <th style="padding:0.75rem; text-align:center; color:#64748b;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cartola as $mov)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:0.6rem 0.75rem; white-space:nowrap;">{{ \Carbon\Carbon::parse($mov->fecha)->format('d/m/Y') }}</td>
                        <td style="padding:0.6rem 0.75rem; max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $mov->descripcion }}</td>
                        <td style="padding:0.6rem 0.75rem; text-align:right; font-weight:600; color:{{ $mov->tipo === 'ingreso' ? '#10b981' : '#ef4444' }};">{{ $mov->tipo === 'ingreso' ? '+' : '−' }}${{ number_format(abs($mov->monto), 0, ',', '.') }}</td>
                        <td style="padding:0.6rem 0.75rem; text-align:center;">
                            <span style="background:{{ $mov->estado_conciliacion === 'conciliado' ? '#10b981' : '#f59e0b' }}20; color:{{ $mov->estado_conciliacion === 'conciliado' ? '#10b981' : '#f59e0b' }}; padding:0.15rem 0.5rem; border-radius:99px; font-size:0.7rem; font-weight:600;">{{ ucfirst($mov->estado_conciliacion) }}</span>
                        </td>
                        <td style="padding:0.6rem 0.75rem; text-align:center;">
                            @if($mov->estado_conciliacion === 'pendiente')
                            <button onclick="abrirMatchModal({{ $mov->id }}, '{{ addslashes($mov->descripcion) }}', {{ $mov->monto }}, '{{ $mov->tipo }}')" style="background:#3b82f6; color:#fff; border:none; padding:0.3rem 0.75rem; border-radius:6px; font-size:0.7rem; font-weight:600; cursor:pointer;"><i class="fas fa-link"></i> Conciliar</button>
                            @else
                            <span style="color:#10b981;"><i class="fas fa-check"></i></span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="padding:2rem 1rem; text-align:center; color:#94a3b8;">Aún no importas la cartola del banco. Súbela para conciliar los movimientos reales contra los de Finanzas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

@if($cuentaActiva)
<!-- Modal Actualizar Saldo Real -->
<div id="modalSaldo" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:50; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:2rem; max-width:420px; width:90%;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:700;">Saldo real de la cuenta</h3>
            <button onclick="document.getElementById('modalSaldo').style.display='none'" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <p style="font-size:0.8rem; color:#64748b; margin:0 0 1rem; line-height:1.5;">Escribe el saldo que muestra tu banco ({{ $cuentaActiva->banco }} {{ $cuentaActiva->numero_cuenta }}) hoy. Quedará como <strong>ancla</strong>: desde esta fecha el saldo se proyecta solo, sumando y restando los movimientos nuevos de Finanzas. Vuelve a fijarlo cuando quieras recalibrarlo.</p>
        <form method="POST" action="{{ route('finanzas.banco.cuenta.saldo', $cuentaActiva->id) }}">
            @csrf
            <label style="font-size:0.8rem; font-weight:600; color:#475569;">Saldo actual (CLP)</label>
            <input type="number" name="saldo_actual" value="{{ $saldoCuenta ?? 0 }}" required style="width:100%; padding:0.55rem; border:1px solid #e2e8f0; border-radius:8px; margin-top:0.25rem; font-size:1rem;">
            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:1.5rem;">
                <button type="button" onclick="document.getElementById('modalSaldo').style.display='none'" style="padding:0.5rem 1.5rem; background:#f1f5f9; color:#475569; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" style="padding:0.5rem 1.5rem; background:#0f172a; color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Guardar saldo</button>
            </div>
        </form>
    </div>
</div>
@endif

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
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Titular / nombre de la cuenta *</label>
                    <input type="text" name="titular" required placeholder="Ej: Inversiones RV SpA" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
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
                    <label style="font-size:0.8rem; font-weight:600; color:#475569;">Número de Cuenta *</label>
                    <input type="text" name="numero_cuenta" required style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:1.5rem;">
                <button type="button" onclick="document.getElementById('modalCuenta').style.display='none'" style="padding:0.5rem 1.5rem; background:#f1f5f9; color:#475569; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" style="padding:0.5rem 1.5rem; background:#3b82f6; color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Crear</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Conciliar Movimiento -->
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
