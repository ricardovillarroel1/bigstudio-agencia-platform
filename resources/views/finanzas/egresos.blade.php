<x-app-layout>
<x-slot name="header">Egresos / Facturas de Compra</x-slot>

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
        <button type="button" onclick="document.getElementById('modalFactura').style.display='flex'" style="padding:0.5rem 1.5rem; background:#3b82f6; color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer; margin-left:auto;">
            <i class="fas fa-plus"></i> Nueva Factura de Compra
        </button>
    </form>

    <!-- Tarjetas resumen -->
    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:1rem; margin-bottom:1.5rem;">
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center;">
            <div style="font-size:0.75rem; color:#64748b; text-transform:uppercase; font-weight:600;">Total Neto</div>
            <div style="font-size:1.5rem; font-weight:700; color:#1e293b;">${{ number_format($totalNeto, 0, ',', '.') }}</div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center;">
            <div style="font-size:0.75rem; color:#64748b; text-transform:uppercase; font-weight:600;">Total IVA (Crédito)</div>
            <div style="font-size:1.5rem; font-weight:700; color:#f59e0b;">${{ number_format($totalIva, 0, ',', '.') }}</div>
        </div>
        <div style="background:#fff; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.08); text-align:center;">
            <div style="font-size:0.75rem; color:#64748b; text-transform:uppercase; font-weight:600;">Total Bruto</div>
            <div style="font-size:1.5rem; font-weight:700; color:#ef4444;">${{ number_format($totalBruto, 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Tabla facturas de compra -->
    <div style="background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.08); overflow:hidden; margin-bottom:2rem;">
        <div style="padding:1rem 1.5rem; border-bottom:1px solid #f1f5f9;">
            <h3 style="margin:0; font-size:1rem; font-weight:700; color:#1e293b;">Facturas de Compra</h3>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Fecha</th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">N° Factura</th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Proveedor</th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">RUT</th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Categoría</th>
                        <th style="padding:0.75rem; text-align:right; color:#64748b;">Neto</th>
                        <th style="padding:0.75rem; text-align:right; color:#64748b;">IVA</th>
                        <th style="padding:0.75rem; text-align:right; color:#64748b;">Total</th>
                        <th style="padding:0.75rem; text-align:center; color:#64748b;">Estado</th>
                        <th style="padding:0.75rem; text-align:center; color:#64748b;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($facturasCompra as $fc)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:0.6rem 0.75rem;">{{ \Carbon\Carbon::parse($fc->fecha_emision)->format('d/m/Y') }}</td>
                        <td style="padding:0.6rem 0.75rem; font-weight:600;">{{ $fc->numero_factura }}</td>
                        <td style="padding:0.6rem 0.75rem;">{{ $fc->proveedor_nombre }}</td>
                        <td style="padding:0.6rem 0.75rem; font-size:0.75rem; color:#64748b;">{{ $fc->proveedor_rut ?? '-' }}</td>
                        <td style="padding:0.6rem 0.75rem;">
                            @if($fc->categoria_nombre)
                            <span style="background:{{ $fc->categoria_color ?? '#e2e8f0' }}20; color:{{ $fc->categoria_color ?? '#64748b' }}; padding:0.15rem 0.5rem; border-radius:99px; font-size:0.7rem; font-weight:600;">{{ $fc->categoria_nombre }}</span>
                            @else - @endif
                        </td>
                        <td style="padding:0.6rem 0.75rem; text-align:right;">${{ number_format($fc->monto_neto, 0, ',', '.') }}</td>
                        <td style="padding:0.6rem 0.75rem; text-align:right;">${{ number_format($fc->monto_iva, 0, ',', '.') }}</td>
                        <td style="padding:0.6rem 0.75rem; text-align:right; font-weight:600;">${{ number_format($fc->monto_total, 0, ',', '.') }}</td>
                        <td style="padding:0.6rem 0.75rem; text-align:center;">
                            @php $estadoColors = ['pendiente'=>'#f59e0b','pagada'=>'#10b981','vencida'=>'#ef4444','anulada'=>'#94a3b8']; @endphp
                            <span style="background:{{ $estadoColors[$fc->estado] ?? '#94a3b8' }}20; color:{{ $estadoColors[$fc->estado] ?? '#94a3b8' }}; padding:0.2rem 0.6rem; border-radius:99px; font-size:0.7rem; font-weight:600; text-transform:capitalize;">{{ $fc->estado }}</span>
                        </td>
                        <td style="padding:0.6rem 0.75rem; text-align:center;">
                            @if($fc->archivo_pdf)
                            <a href="{{ asset('storage/'.$fc->archivo_pdf) }}" target="_blank" style="color:#3b82f6; margin-right:0.5rem;" title="Ver PDF"><i class="fas fa-file-pdf"></i></a>
                            @endif
                            <form method="POST" action="{{ route('finanzas.egresos.factura-compra.delete', $fc->id) }}" style="display:inline;" onsubmit="return confirm('¿Eliminar esta factura?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:none; color:#ef4444; cursor:pointer;" title="Eliminar"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" style="padding:2rem; text-align:center; color:#94a3b8;">No hay facturas de compra registradas este mes</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Gastos Operativos Fijos -->
    <div style="background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.08); overflow:hidden;">
        <div style="padding:1rem 1.5rem; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:1rem; font-weight:700; color:#1e293b;">Gastos Operativos Fijos (Mensuales)</h3>
            <button type="button" onclick="document.getElementById('modalGasto').style.display='flex'" style="padding:0.4rem 1rem; background:#8b5cf6; color:#fff; border:none; border-radius:8px; font-size:0.8rem; font-weight:600; cursor:pointer;">
                <i class="fas fa-plus"></i> Agregar
            </button>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Concepto</th>
                        <th style="padding:0.75rem; text-align:left; color:#64748b;">Categoría</th>
                        <th style="padding:0.75rem; text-align:right; color:#64748b;">Monto Mensual</th>
                        <th style="padding:0.75rem; text-align:center; color:#64748b;">Estado</th>
                        <th style="padding:0.75rem; text-align:center; color:#64748b;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gastosOperativos as $go)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:0.6rem 0.75rem; font-weight:500;">{{ $go->concepto }}</td>
                        <td style="padding:0.6rem 0.75rem;">{{ $go->categoria_nombre ?? '-' }}</td>
                        <td style="padding:0.6rem 0.75rem; text-align:right; font-weight:600;">${{ number_format($go->monto, 0, ',', '.') }}</td>
                        <td style="padding:0.6rem 0.75rem; text-align:center;">
                            <span style="background:{{ $go->activo ? '#10b981' : '#94a3b8' }}20; color:{{ $go->activo ? '#10b981' : '#94a3b8' }}; padding:0.2rem 0.6rem; border-radius:99px; font-size:0.7rem; font-weight:600;">{{ $go->activo ? 'Activo' : 'Inactivo' }}</span>
                        </td>
                        <td style="padding:0.6rem 0.75rem; text-align:center;">
                            <form method="POST" action="{{ route('finanzas.egresos.gasto-operativo.toggle', $go->id) }}" style="display:inline;">
                                @csrf
                                <button type="submit" style="background:none; border:none; color:#f59e0b; cursor:pointer;" title="{{ $go->activo ? 'Desactivar' : 'Activar' }}">
                                    <i class="fas fa-{{ $go->activo ? 'pause' : 'play' }}"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="padding:2rem; text-align:center; color:#94a3b8;">No hay gastos operativos registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nueva Factura de Compra -->
<div id="modalFactura" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:50; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:2rem; max-width:600px; width:90%; max-height:90vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:700;">Nueva Factura de Compra</h3>
            <button onclick="document.getElementById('modalFactura').style.display='none'" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="{{ route('finanzas.egresos.factura-compra.store') }}" enctype="multipart/form-data">
            @csrf
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569; display:block; margin-bottom:0.25rem;">Proveedor *</label>
                    <input type="text" name="proveedor_nombre" required style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569; display:block; margin-bottom:0.25rem;">RUT Proveedor</label>
                    <input type="text" name="proveedor_rut" placeholder="12.345.678-9" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569; display:block; margin-bottom:0.25rem;">N° Factura *</label>
                    <input type="text" name="numero_factura" required style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569; display:block; margin-bottom:0.25rem;">Monto Neto *</label>
                    <input type="number" name="monto_neto" required style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569; display:block; margin-bottom:0.25rem;">Fecha Emisión *</label>
                    <input type="date" name="fecha_emision" required style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569; display:block; margin-bottom:0.25rem;">Fecha Vencimiento</label>
                    <input type="date" name="fecha_vencimiento" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569; display:block; margin-bottom:0.25rem;">Categoría</label>
                    <select name="categoria_id" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
                        <option value="">Seleccionar...</option>
                        @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569; display:block; margin-bottom:0.25rem;">Centro de Costo</label>
                    <select name="centro_costo_id" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
                        <option value="">Seleccionar...</option>
                        @foreach($centrosCosto as $cc)
                        <option value="{{ $cc->id }}">{{ $cc->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569; display:block; margin-bottom:0.25rem;">Estado</label>
                    <select name="estado" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
                        <option value="pendiente">Pendiente</option>
                        <option value="pagada">Pagada</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569; display:block; margin-bottom:0.25rem;">Método de Pago</label>
                    <select name="metodo_pago" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
                        <option value="">Seleccionar...</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div style="grid-column:span 2;">
                    <label style="font-size:0.8rem; font-weight:600; color:#475569; display:block; margin-bottom:0.25rem;">Adjuntar PDF</label>
                    <input type="file" name="archivo_pdf" accept=".pdf,.jpg,.png" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
                </div>
                <div style="grid-column:span 2;">
                    <label style="font-size:0.8rem; font-weight:600; color:#475569; display:block; margin-bottom:0.25rem;">Notas</label>
                    <textarea name="notas" rows="2" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem; resize:vertical;"></textarea>
                </div>
                <div style="grid-column:span 2;">
                    <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.85rem; color:#475569;">
                        <input type="checkbox" name="exento" value="1"> Factura exenta de IVA
                    </label>
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:1.5rem;">
                <button type="button" onclick="document.getElementById('modalFactura').style.display='none'" style="padding:0.5rem 1.5rem; background:#f1f5f9; color:#475569; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" style="padding:0.5rem 1.5rem; background:#3b82f6; color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Gasto Operativo -->
<div id="modalGasto" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:50; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:2rem; max-width:450px; width:90%;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:700;">Nuevo Gasto Operativo Fijo</h3>
            <button onclick="document.getElementById('modalGasto').style.display='none'" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="{{ route('finanzas.egresos.gasto-operativo.store') }}">
            @csrf
            <div style="display:flex; flex-direction:column; gap:1rem;">
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569; display:block; margin-bottom:0.25rem;">Concepto *</label>
                    <input type="text" name="concepto" required placeholder="Ej: Arriendo oficina" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569; display:block; margin-bottom:0.25rem;">Monto Mensual *</label>
                    <input type="number" name="monto" required style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.8rem; font-weight:600; color:#475569; display:block; margin-bottom:0.25rem;">Categoría</label>
                    <select name="categoria_id" style="width:100%; padding:0.5rem; border:1px solid #e2e8f0; border-radius:8px;">
                        <option value="">Seleccionar...</option>
                        @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:1.5rem;">
                <button type="button" onclick="document.getElementById('modalGasto').style.display='none'" style="padding:0.5rem 1.5rem; background:#f1f5f9; color:#475569; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" style="padding:0.5rem 1.5rem; background:#8b5cf6; color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Guardar</button>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
