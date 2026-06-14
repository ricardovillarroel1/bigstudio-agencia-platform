<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Cotizaciones</h2>
                    <p class="text-sm text-gray-500">Crea y gestiona cotizaciones profesionales para clientes de agencia</p>
                </div>
                <span class="text-sm text-gray-400">{{ now()->format('d/m/Y') }}</span>
            </div>

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ session('error') }}</div>
            @endif

            <!-- Nueva Cotizacion -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-700">Nueva Cotizacion</h3>
                    <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold">Proximo: #{{ $proximoNumero }}</span>
                </div>
                <div class="p-6">
                    <form action="{{ route('agencia.cotizaciones.store') }}" method="POST" id="formCotizacion" enctype="multipart/form-data">
                        @csrf

                        <!-- Datos del Cliente -->
                        <div class="mb-5">
                            <h4 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-3">Datos del Cliente</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                                <div>
                                    <label class="text-xs text-gray-500 block mb-1">Cliente</label>
                                    <select name="agencia_cliente_id" id="selectClienteCot" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500" onchange="llenarDatosCliente()">
                                        <option value="">Seleccionar o ingresar manualmente...</option>
                                        @foreach($clientes as $c)
                                            <option value="{{ $c->id }}"
                                                data-nombre="{{ $c->nombre }}"
                                                data-rut="{{ $c->rut }}"
                                                data-email="{{ $c->email }}"
                                                data-telefono="{{ $c->telefono ?? '' }}"
                                                data-direccion="{{ $c->direccion ?? '' }}"
                                                data-giro="{{ $c->giro ?? '' }}">
                                                {{ $c->nombre }} ({{ $c->rut }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 block mb-1">Nombre / Razon Social <span class="text-red-500">*</span></label>
                                    <input type="text" name="cliente_nombre" id="cotNombre" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 block mb-1">RUT</label>
                                    <input type="text" name="cliente_rut" id="cotRut" placeholder="12.345.678-9" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="text-xs text-gray-500 block mb-1">Email <span class="text-red-500">*</span></label>
                                    <input type="email" name="cliente_email" id="cotEmail" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 block mb-1">Telefono</label>
                                    <input type="text" name="cliente_telefono" id="cotTelefono" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 block mb-1">Direccion</label>
                                    <input type="text" name="cliente_direccion" id="cotDireccion" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 block mb-1">Giro</label>
                                    <input type="text" name="cliente_giro" id="cotGiro" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-200 my-5">

                        <!-- Items -->
                        <div class="mb-5">
                            <h4 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-3">Items / Servicios</h4>
                            <div id="itemsContainer">
                                <div class="item-row grid gap-2 mb-2 items-end" data-index="0" style="grid-template-columns: 2fr 1fr 3fr 1fr 2fr 2fr auto;">
                                    <div>
                                        <label class="text-xs text-gray-400 block mb-1">Servicio</label>
                                        <select name="items[0][servicio_id]" class="servicio-select w-full border border-gray-300 rounded-lg px-2 py-2 text-sm" onchange="llenarServicio(this, 0)">
                                            <option value="">Manual</option>
                                            @foreach($servicios as $s)
                                                <option value="{{ $s->id }}" data-codigo="{{ $s->codigo ?? '' }}" data-nombre="{{ $s->nombre }}" data-precio="{{ $s->moneda === 'UF' ? round(($s->precio_uf ?? 0) * ($valorUF ?? 39000)) : $s->precio }}" data-moneda="{{ $s->moneda ?? 'CLP' }}" data-precio-uf="{{ $s->precio_uf ?? 0 }}">{{ $s->nombre }}{{ $s->moneda === 'UF' ? ' ('.number_format($s->precio_uf, 2).' UF)' : '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-400 block mb-1">Codigo</label>
                                        <input type="text" name="items[0][codigo]" class="item-codigo w-full border border-gray-300 rounded-lg px-2 py-2 text-sm" placeholder="COD">
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-400 block mb-1">Descripcion</label>
                                        <input type="text" name="items[0][descripcion]" class="item-desc w-full border border-gray-300 rounded-lg px-2 py-2 text-sm" placeholder="Descripcion del servicio" required>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-400 block mb-1">Cant.</label>
                                        <input type="number" name="items[0][cantidad]" value="1" min="1" class="item-cant w-full border border-gray-300 rounded-lg px-2 py-2 text-sm" oninput="calcularTotales()">
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-400 block mb-1">P. Neto</label>
                                        <input type="number" name="items[0][precio_neto]" min="0" class="item-precio w-full border border-gray-300 rounded-lg px-2 py-2 text-sm" placeholder="0" oninput="calcularTotales()">
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-400 block mb-1">Total Neto</label>
                                        <input type="text" class="item-total w-full border border-gray-200 rounded-lg px-2 py-2 text-sm bg-gray-50 text-gray-600" readonly>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-400 block mb-1">&nbsp;</label>
                                        <button type="button" onclick="this.closest('.item-row').remove(); calcularTotales();" class="text-red-400 hover:text-red-600 px-2 py-2 text-sm">✕</button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" onclick="agregarItem()" class="mt-2 text-brand-600 hover:text-brand-800 text-sm font-semibold">+ Agregar Item</button>
                        </div>

                        <hr class="border-gray-200 my-5">

                        <!-- Opciones -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Descuento (%)</label>
                                <input type="number" name="descuento_porcentaje" id="descuentoPct" value="0" min="0" max="100" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" oninput="calcularTotales()">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Valida Hasta</label>
                                <input type="date" name="valida_hasta" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-500 block mb-1">Notas</label>
                                <input type="text" name="notas" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Notas adicionales...">
                            </div>
                        </div>

                        <!-- PDF complemento (opcional) -->
                        <div class="mb-5">
                            <label class="text-xs text-gray-500 block mb-1">PDF complemento (opcional)</label>
                            <input type="file" name="pdf_complemento" accept="application/pdf" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <p class="text-xs text-gray-400 mt-1">Se adjuntará al correo de la cotización para que el cliente lo descargue (solo PDF, máx. 5MB).</p>
                        </div>

                        <!-- Totales -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-5">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-500">Subtotal Neto:</span>
                                <span class="font-semibold" id="subtotalNeto">$0</span>
                            </div>
                            <div class="flex justify-between text-sm mb-1 text-red-500">
                                <span>Descuento:</span>
                                <span id="descuentoMonto">-$0</span>
                            </div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-500">Total Neto:</span>
                                <span class="font-semibold" id="totalNeto">$0</span>
                            </div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-500">IVA (19%):</span>
                                <span class="font-semibold" id="totalIva">$0</span>
                            </div>
                            <div class="flex justify-between text-base font-bold border-t border-gray-200 pt-2 mt-2">
                                <span class="text-brand-700">TOTAL:</span>
                                <span class="text-brand-700" id="totalFinal">$0</span>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button type="submit" class="bg-brand-600 text-white px-6 py-2 rounded-lg text-sm font-semibold hover:bg-brand-700">Crear Cotización</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Filtros de Cotizaciones -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
                <form method="GET" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Cliente</label>
                        <select name="cliente_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Todos</option>
                            @foreach($clientes as $c)
                                <option value="{{ $c->id }}" {{ request('cliente_id') == $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Estado</label>
                        <select name="estado" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Todos</option>
                            <option value="borrador" {{ request('estado') === 'borrador' ? 'selected' : '' }}>Borrador</option>
                            <option value="enviada" {{ request('estado') === 'enviada' ? 'selected' : '' }}>Enviada</option>
                            <option value="aceptada" {{ request('estado') === 'aceptada' ? 'selected' : '' }}>Aceptada</option>
                            <option value="pagada" {{ request('estado') === 'pagada' ? 'selected' : '' }}>Pagada</option>
                            <option value="facturada" {{ request('estado') === 'facturada' ? 'selected' : '' }}>Facturada</option>
                            <option value="cancelada" {{ request('estado') === 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Desde</label>
                        <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Hasta</label>
                        <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">Filtrar</button>
                    <a href="{{ route('agencia.cotizaciones') }}" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm">Limpiar</a>
                </form>
            </div>

            <!-- Cotizaciones Registradas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-700">Cotizaciones Registradas</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wide">
                            <tr>
                                <th class="px-4 py-3 text-left">N&deg;</th>
                                <th class="px-4 py-3 text-left">Fecha</th>
                                <th class="px-4 py-3 text-left">Cliente</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3 text-center">Valida Hasta</th>
                                <th class="px-4 py-3 text-center">Estado</th>
                                <th class="px-4 py-3 text-center">Factura</th>
                                <th class="px-4 py-3 text-center" style="min-width: 220px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($cotizaciones as $cot)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-bold text-amber-600">#{{ $cot->numero }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $cot->created_at->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-800">{{ $cot->cliente_nombre }}</p>
                                        <p class="text-xs text-gray-400">{{ $cot->cliente_rut }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-brand-600">${{ number_format($cot->total, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($cot->valida_hasta)
                                            <span class="{{ $cot->valida_hasta->isPast() ? 'text-red-500 font-semibold' : 'text-green-600' }} text-xs">
                                                {{ $cot->valida_hasta->format('d/m/Y') }}
                                            </span>
                                        @else
                                            <span class="text-gray-300">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @switch($cot->estado)
                                            @case('borrador')
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">Borrador</span>
                                                @break
                                            @case('enviada')
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Enviada</span>
                                                @break
                                            @case('aceptada')
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-brand-100 text-brand-700">Aceptada</span>
                                                @break
                                            @case('pagada')
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Pagada</span>
                                                @break
                                            @case('facturada')
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Facturada</span>
                                                @break
                                            @case('vencida')
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Vencida</span>
                                                @break
                                            @case('cancelada')
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-200 text-gray-500">Cancelada</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($cot->factura_estado === 'emitida')
                                            <span class="text-green-600 text-xs font-semibold">Folio #{{ $cot->lioren_folio }}</span>
                                        @else
                                            <span class="text-gray-300">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <!-- Visualizar -->
                                            <button onclick="verCotizacion({{ $cot->id }})" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium transition" title="Visualizar">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                Ver
                                            </button>
                                            <!-- PDF -->
                                            <a href="{{ route('agencia.cotizaciones.descargar-pdf', $cot->id) }}" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-medium transition" title="Descargar PDF">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                PDF
                                            </a>
                                            @if($cot->pdf_complemento_path)
                                            <!-- PDF complemento adjunto -->
                                            <a href="{{ Storage::disk('public')->url($cot->pdf_complemento_path) }}" target="_blank" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs font-medium transition" title="Ver PDF complemento adjunto">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                Adjunto
                                            </a>
                                            @endif
                                            @if($cot->estado === 'borrador')
                                                <!-- Enviar -->
                                                <form action="{{ route('agencia.cotizaciones.enviar', $cot) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button class="inline-flex items-center gap-1 px-2 py-1 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-medium transition" title="Enviar">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                        Enviar
                                                    </button>
                                                </form>
                                            @endif
                                            @if(in_array($cot->estado, ['enviada', 'aceptada', 'pagada']) && $cot->factura_estado !== 'emitida')
                                                <!-- Facturar -->
                                                <form action="{{ route('agencia.cotizaciones.facturar', $cot) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button class="inline-flex items-center gap-1 px-2 py-1 rounded bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs font-medium transition" title="Facturar">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                                                        Facturar
                                                    </button>
                                                </form>
                                            @endif
                                            @if($cot->estado !== 'cancelada' && $cot->estado !== 'facturada')
                                                <!-- Cancelar -->
                                                <form action="{{ route('agencia.cotizaciones.cancelar', $cot) }}" method="POST" class="inline" onsubmit="return confirm('Cancelar esta cotizacion?')">
                                                    @csrf
                                                    <button class="inline-flex items-center gap-1 px-2 py-1 rounded bg-red-50 hover:bg-red-100 text-red-600 text-xs font-medium transition" title="Cancelar">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-400">No hay cotizaciones registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($cotizaciones->hasPages())
                    <div class="px-6 py-3 border-t border-gray-100">
                        {{ $cotizaciones->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        let itemIndex = 1;
        const serviciosDataCot = @json($servicios ?? []);
        const valorUFCot = {{ $valorUF ?? 39000 }};

        function llenarDatosCliente() {
            const sel = document.getElementById('selectClienteCot');
            const opt = sel.options[sel.selectedIndex];
            if (opt && opt.value) {
                document.getElementById('cotNombre').value = opt.dataset.nombre || '';
                document.getElementById('cotRut').value = opt.dataset.rut || '';
                document.getElementById('cotEmail').value = opt.dataset.email || '';
                document.getElementById('cotTelefono').value = opt.dataset.telefono || '';
                document.getElementById('cotDireccion').value = opt.dataset.direccion || '';
                document.getElementById('cotGiro').value = opt.dataset.giro || '';
            }
        }

        function llenarServicio(select, idx) {
            const opt = select.options[select.selectedIndex];
            if (opt && opt.value) {
                const row = select.closest('.item-row');
                row.querySelector('.item-codigo').value = opt.dataset.codigo || '';
                row.querySelector('.item-desc').value = opt.dataset.nombre || '';
                const precioNeto = parseInt(opt.dataset.precio) || 0;
                row.querySelector('.item-precio').value = precioNeto;
                calcularTotales();
            }
        }

        function agregarItem() {
            const container = document.getElementById('itemsContainer');
            const html = `
                <div class="item-row grid gap-2 mb-2 items-end" data-index="${itemIndex}" style="grid-template-columns: 2fr 1fr 3fr 1fr 2fr 2fr auto;">
                    <div>
                        <select name="items[${itemIndex}][servicio_id]" class="servicio-select w-full border border-gray-300 rounded-lg px-2 py-2 text-sm" onchange="llenarServicio(this, ${itemIndex})">
                            <option value="">Manual</option>
                            ${serviciosDataCot.map(s => '<option value="'+s.id+'" data-codigo="'+(s.codigo||'')+'" data-nombre="'+s.nombre+'" data-precio="'+(s.moneda==='UF' ? Math.round((s.precio_uf||0)*valorUFCot) : s.precio)+'" data-moneda="'+(s.moneda||'CLP')+'" data-precio-uf="'+(s.precio_uf||0)+'">'+s.nombre+(s.moneda==='UF' ? ' ('+parseFloat(s.precio_uf||0).toFixed(2)+' UF)' : '')+'</option>').join('')}
                        </select>
                    </div>
                    <div>
                        <input type="text" name="items[${itemIndex}][codigo]" class="item-codigo w-full border border-gray-300 rounded-lg px-2 py-2 text-sm" placeholder="COD">
                    </div>
                    <div>
                        <input type="text" name="items[${itemIndex}][descripcion]" class="item-desc w-full border border-gray-300 rounded-lg px-2 py-2 text-sm" placeholder="Descripcion del servicio" required>
                    </div>
                    <div>
                        <input type="number" name="items[${itemIndex}][cantidad]" value="1" min="1" class="item-cant w-full border border-gray-300 rounded-lg px-2 py-2 text-sm" oninput="calcularTotales()">
                    </div>
                    <div>
                        <input type="number" name="items[${itemIndex}][precio_neto]" min="0" class="item-precio w-full border border-gray-300 rounded-lg px-2 py-2 text-sm" placeholder="0" oninput="calcularTotales()">
                    </div>
                    <div>
                        <input type="text" class="item-total w-full border border-gray-200 rounded-lg px-2 py-2 text-sm bg-gray-50 text-gray-600" readonly>
                    </div>
                    <div>
                        <button type="button" onclick="this.closest('.item-row').remove(); calcularTotales();" class="text-red-400 hover:text-red-600 px-2 py-2 text-sm">✕</button>
                    </div>
                </div>`;
            container.insertAdjacentHTML('beforeend', html);
            itemIndex++;
        }

        function formatCLP(n) {
            return '$' + Math.round(n).toLocaleString('es-CL');
        }

        function calcularTotales() {
            let subtotal = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const cant = parseInt(row.querySelector('.item-cant')?.value) || 1;
                const precio = parseInt(row.querySelector('.item-precio')?.value) || 0;
                const total = cant * precio;
                const totalEl = row.querySelector('.item-total');
                if (totalEl) totalEl.value = formatCLP(total);
                subtotal += total;
            });

            const descPct = parseFloat(document.getElementById('descuentoPct').value) || 0;
            const descMonto = Math.round(subtotal * descPct / 100);
            const totalNeto = subtotal - descMonto;
            const iva = Math.round(totalNeto * 0.19);
            const total = totalNeto + iva;

            document.getElementById('subtotalNeto').textContent = formatCLP(subtotal);
            document.getElementById('descuentoMonto').textContent = '-' + formatCLP(descMonto);
            document.getElementById('totalNeto').textContent = formatCLP(totalNeto);
            document.getElementById('totalIva').textContent = formatCLP(iva);
            document.getElementById('totalFinal').textContent = formatCLP(total);
        }
    </script>

<!-- Modal Visualizar Cotización - Estilo Email Big Studio -->
<div id="verCotModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background: rgba(17,24,39,0.55); backdrop-filter: blur(2px);" onclick="if(event.target===this)cerrarVerCot()">
    <div style="max-width: 700px; width: 100%; max-height: 92vh; overflow-y: auto; border-radius: 14px; overflow: hidden; background: #ffffff; box-shadow: 0 25px 60px rgba(0,0,0,0.3);">

        {{-- HEADER: gradiente BigStudio con logo --}}
        <div style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%); padding: 26px 30px 22px; text-align: center; position: relative;">
            <button onclick="cerrarVerCot()" style="position: absolute; top: 12px; right: 14px; color: #ffffff; font-size: 26px; background: rgba(0,0,0,0.15); border: none; cursor: pointer; line-height: 1; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='rgba(0,0,0,0.3)';" onmouseout="this.style.background='rgba(0,0,0,0.15)';">&times;</button>
            <img src="{{ asset('images/bigstudio-logo-gradient.png') }}" alt="Big Studio" style="width: 56px; height: auto; display: block; margin: 0 auto 8px; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.2));">
            <p style="color: #ffffff; font-size: 10px; margin: 0; letter-spacing: 2.5px; font-weight: 700; text-transform: uppercase; opacity: 0.95;">Agencia de Marketing Digital</p>
        </div>
        {{-- BARRA TITULO con N° y estado --}}
        <div style="background: #111827; padding: 16px 30px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="vertical-align: middle;">
                        <p style="color: #FFC800; font-size: 10px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; margin: 0;">Cotización</p>
                        <h2 style="color: #ffffff; font-size: 22px; font-weight: 900; margin: 2px 0 0; letter-spacing: -0.5px;"><span id="vc_numero"></span></h2>
                    </td>
                    <td style="vertical-align: middle; text-align: right;">
                        <p style="color: #9CA3AF; font-size: 11px; margin: 0;" id="vc_fecha"></p>
                        <span id="vc_estado" style="display: inline-block; margin-top: 6px;"></span>
                    </td>
                </tr>
            </table>
        </div>

        {{-- DATOS DEL CLIENTE --}}
        <div style="background: #ffffff; padding: 20px 30px 8px;">
            <p style="color: #FF8100; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; margin: 0 0 10px;">Cliente</p>
            <div style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%); border: 1px solid #FFD89C; border-radius: 10px; padding: 14px 18px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr>
                        <td style="padding: 4px 0; color: #6B7280; width: 110px; vertical-align: top;">Cliente:</td>
                        <td style="padding: 4px 0; color: #111827; font-weight: 700;" id="vc_cliente_nombre"></td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #6B7280;">RUT:</td>
                        <td style="padding: 4px 0; color: #374151; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;" id="vc_cliente_rut"></td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #6B7280;">Email:</td>
                        <td style="padding: 4px 0; color: #374151;" id="vc_cliente_email"></td>
                    </tr>
                    <tr id="vc_telefono_row">
                        <td style="padding: 4px 0; color: #6B7280;">Teléfono:</td>
                        <td style="padding: 4px 0; color: #374151;" id="vc_cliente_telefono"></td>
                    </tr>
                    <tr id="vc_direccion_row">
                        <td style="padding: 4px 0; color: #6B7280;">Dirección:</td>
                        <td style="padding: 4px 0; color: #374151;" id="vc_cliente_direccion"></td>
                    </tr>
                    <tr id="vc_giro_row">
                        <td style="padding: 4px 0; color: #6B7280;">Giro:</td>
                        <td style="padding: 4px 0; color: #374151;" id="vc_cliente_giro"></td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- ITEMS --}}
        <div style="background: #ffffff; padding: 16px 30px 8px;">
            <p style="color: #FF8100; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; margin: 0 0 10px;">Items / Servicios</p>
            <div style="border: 1px solid #E5E7EB; border-radius: 10px; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #111827;">
                            <th style="padding: 10px 14px; color: #ffffff; font-size: 10px; text-align: left; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; width: 90px;">Código</th>
                            <th style="padding: 10px 14px; color: #ffffff; font-size: 10px; text-align: left; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Descripción</th>
                            <th style="padding: 10px 8px; color: #ffffff; font-size: 10px; text-align: center; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; width: 55px;">Cant.</th>
                            <th style="padding: 10px 14px; color: #ffffff; font-size: 10px; text-align: right; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; width: 100px;">P. Neto</th>
                            <th style="padding: 10px 14px; color: #ffffff; font-size: 10px; text-align: right; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; width: 110px;">Total</th>
                        </tr>
                    </thead>
                    <tbody id="vc_items"></tbody>
                </table>
            </div>
        </div>

        {{-- TOTALES --}}
        <div style="background: #ffffff; padding: 16px 30px 8px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <tr>
                    <td style="padding: 6px 0; color: #6B7280; text-align: right;">Subtotal neto</td>
                    <td style="padding: 6px 0; color: #111827; font-weight: 600; text-align: right; width: 140px;" id="vc_subtotal_neto"></td>
                </tr>
                <tr id="vc_descuento_row" style="display:none;">
                    <td style="padding: 6px 0; color: #DC2626; text-align: right;">Descuento (<span id="vc_desc_pct"></span>%)</td>
                    <td style="padding: 6px 0; color: #DC2626; font-weight: 600; text-align: right;" id="vc_descuento_monto"></td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #6B7280; text-align: right;">Neto con descuento</td>
                    <td style="padding: 6px 0; color: #111827; font-weight: 600; text-align: right;" id="vc_total_neto"></td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #6B7280; text-align: right;">IVA (19%)</td>
                    <td style="padding: 6px 0; color: #111827; font-weight: 600; text-align: right;" id="vc_iva"></td>
                </tr>
                <tr><td colspan="2" style="padding: 10px 0 0;">
                    <div style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%); padding: 12px 18px; border-radius: 10px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="color: #ffffff; font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;">Total</td>
                                <td style="color: #ffffff; font-size: 20px; font-weight: 900; text-align: right;" id="vc_total"></td>
                            </tr>
                        </table>
                    </div>
                </td></tr>
            </table>
        </div>

        {{-- VALIDEZ + FACTURA --}}
        <div id="vc_validez_section" style="background: #ffffff; padding: 0 30px 8px; display: none;">
            <p style="color: #6B7280; font-size: 12px; margin: 0;">Válida hasta: <strong style="color: #FF8100;" id="vc_validez"></strong></p>
        </div>
        <div id="vc_factura_section" style="background: #ffffff; padding: 0 30px 8px; display: none;">
            <p style="color: #059669; font-size: 12px; font-weight: 700; margin: 0;" id="vc_factura"></p>
        </div>

        {{-- NOTAS --}}
        <div id="vc_notas_section" style="background: #ffffff; padding: 12px 30px; display: none;">
            <p style="color: #FF8100; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; margin: 0 0 6px;">Notas</p>
            <p style="color: #374151; font-size: 13px; line-height: 1.5; margin: 0;" id="vc_notas"></p>
        </div>

        {{-- DATOS BANCARIOS --}}
        <div style="background: #ffffff; padding: 16px 30px 20px;">
            <div style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%); border: 1px solid #FFD89C; border-radius: 10px; padding: 14px 18px;">
                <p style="margin: 0 0 10px; color: #FF8100; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px;">🏦 Datos para transferencia</p>
                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <tr><td style="padding: 2px 0; color: #6B7280; width: 130px;">Banco:</td><td style="padding: 2px 0; color: #111827; font-weight: 600;">Banco Bci</td></tr>
                    <tr><td style="padding: 2px 0; color: #6B7280;">Tipo cuenta:</td><td style="padding: 2px 0; color: #111827; font-weight: 600;">Cuenta Corriente</td></tr>
                    <tr><td style="padding: 2px 0; color: #6B7280;">N° Cuenta:</td><td style="padding: 2px 0; color: #111827; font-weight: 600; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">97580848</td></tr>
                    <tr><td style="padding: 2px 0; color: #6B7280;">RUT:</td><td style="padding: 2px 0; color: #111827; font-weight: 600; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">78.153.109-K</td></tr>
                    <tr><td style="padding: 2px 0; color: #6B7280;">Titular:</td><td style="padding: 2px 0; color: #111827; font-weight: 600;">Big Studio</td></tr>
                    <tr><td style="padding: 2px 0; color: #6B7280;">Email:</td><td style="padding: 2px 0; color: #111827; font-weight: 600;">hola@bigstudio.cl</td></tr>
                </table>
            </div>
        </div>

        {{-- FOOTER + STRIPE BRAND --}}
        <div style="background: #F9FAFB; padding: 16px 30px; text-align: center; border-top: 1px solid #F3F4F6;">
            <p style="color: #111827; font-size: 12px; margin: 0 0 4px; font-weight: 700;">Big Studio · Agencia de Marketing Digital</p>
            <p style="color: #6B7280; font-size: 11px; margin: 0;">
                <a href="mailto:hola@bigstudio.cl" style="color: #FF8100; text-decoration: none; font-weight: 600;">hola@bigstudio.cl</a>
                ·
                <a href="https://www.bigstudio.cl" style="color: #FF8100; text-decoration: none; font-weight: 600;">bigstudio.cl</a>
            </p>
        </div>
        <div style="height: 5px; background: linear-gradient(90deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);"></div>
    </div>
</div>

<script>
    function verCotizacion(cotId) {
        fetch('/agencia/cotizaciones/' + cotId + '/ver')
            .then(r => r.json())
            .then(data => {
                document.getElementById('vc_numero').textContent = '#' + data.numero;
                document.getElementById('vc_fecha').textContent = 'Fecha: ' + data.fecha;
                document.getElementById('vc_cliente_nombre').textContent = data.cliente_nombre || '-';
                document.getElementById('vc_cliente_rut').textContent = data.cliente_rut || '';
                document.getElementById('vc_cliente_email').textContent = data.cliente_email || '';
                
                var telRow = document.getElementById('vc_telefono_row');
                if (data.cliente_telefono) { telRow.style.display = ''; document.getElementById('vc_cliente_telefono').textContent = data.cliente_telefono; }
                else { telRow.style.display = 'none'; }
                
                var dirRow = document.getElementById('vc_direccion_row');
                if (data.cliente_direccion) { dirRow.style.display = ''; document.getElementById('vc_cliente_direccion').textContent = data.cliente_direccion; }
                else { dirRow.style.display = 'none'; }
                
                var giroRow = document.getElementById('vc_giro_row');
                if (data.cliente_giro) { giroRow.style.display = ''; document.getElementById('vc_cliente_giro').textContent = data.cliente_giro; }
                else { giroRow.style.display = 'none'; }
                
                // Estado badge (paleta BigStudio + semantica)
                var estadoEl = document.getElementById('vc_estado');
                var estadoStyles = {
                    'borrador':  'background:rgba(255,255,255,0.15); color:#E5E7EB;',
                    'enviada':   'background:#FFEDD0; color:#8A4400;',
                    'aceptada':  'background:#D1FAE5; color:#065F46;',
                    'pagada':    'background:#10B981; color:#ffffff;',
                    'facturada': 'background:#FFC800; color:#111827;',
                    'vencida':   'background:#FEE2E2; color:#991B1B;',
                    'cancelada': 'background:rgba(255,255,255,0.1); color:#9CA3AF;',
                };
                estadoEl.style.cssText = (estadoStyles[data.estado] || estadoStyles['borrador']) + ' padding:5px 14px; border-radius:999px; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:1px;';
                estadoEl.textContent = data.estado.charAt(0).toUpperCase() + data.estado.slice(1);
                
                // Validez
                if (data.valida_hasta) {
                    document.getElementById('vc_validez_section').style.display = '';
                    document.getElementById('vc_validez').textContent = data.valida_hasta;
                } else {
                    document.getElementById('vc_validez_section').style.display = 'none';
                }
                
                // Factura
                if (data.factura_estado === 'emitida') {
                    document.getElementById('vc_factura_section').style.display = '';
                    document.getElementById('vc_factura').textContent = 'Factura Emitida - Folio #' + data.lioren_folio;
                } else {
                    document.getElementById('vc_factura_section').style.display = 'none';
                }
                
                // Items (filas claras tipo PDF/email BigStudio)
                var tbody = document.getElementById('vc_items');
                tbody.innerHTML = '';
                data.items.forEach(function(item, idx) {
                    var bgColor = idx % 2 === 0 ? '#ffffff' : '#FAFAFA';
                    var borderBottom = idx === data.items.length - 1 ? 'none' : '1px solid #F3F4F6';
                    var tr = document.createElement('tr');
                    tr.style.background = bgColor;
                    tr.innerHTML =
                        '<td style="padding:10px 14px;color:#9CA3AF;font-size:11px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;border-bottom:' + borderBottom + ';">' + (item.codigo || '—') + '</td>' +
                        '<td style="padding:10px 14px;color:#111827;font-size:13px;border-bottom:' + borderBottom + ';">' + item.descripcion + '</td>' +
                        '<td style="padding:10px 8px;color:#374151;font-size:13px;text-align:center;border-bottom:' + borderBottom + ';">' + item.cantidad + '</td>' +
                        '<td style="padding:10px 14px;color:#6B7280;font-size:13px;text-align:right;font-variant-numeric:tabular-nums;border-bottom:' + borderBottom + ';">$' + item.precio_unitario_neto.toLocaleString('es-CL') + '</td>' +
                        '<td style="padding:10px 14px;color:#111827;font-size:13px;text-align:right;font-weight:700;font-variant-numeric:tabular-nums;border-bottom:' + borderBottom + ';">$' + item.total_neto.toLocaleString('es-CL') + '</td>';
                    tbody.appendChild(tr);
                });
                
                // Totals
                document.getElementById('vc_subtotal_neto').textContent = '$' + data.subtotal_neto.toLocaleString('es-CL');
                document.getElementById('vc_total_neto').textContent = '$' + data.total_neto.toLocaleString('es-CL');
                document.getElementById('vc_iva').textContent = '$' + data.iva.toLocaleString('es-CL');
                document.getElementById('vc_total').textContent = '$' + data.total.toLocaleString('es-CL');
                
                if (data.descuento_porcentaje > 0) {
                    document.getElementById('vc_descuento_row').style.display = '';
                    document.getElementById('vc_desc_pct').textContent = data.descuento_porcentaje;
                    document.getElementById('vc_descuento_monto').textContent = '-$' + data.descuento_monto.toLocaleString('es-CL');
                } else {
                    document.getElementById('vc_descuento_row').style.display = 'none';
                }
                
                if (data.notas) {
                    document.getElementById('vc_notas_section').style.display = '';
                    document.getElementById('vc_notas').textContent = data.notas;
                } else {
                    document.getElementById('vc_notas_section').style.display = 'none';
                }
                
                document.getElementById('verCotModal').classList.remove('hidden');
            })
            .catch(err => {
                alert('Error al cargar la cotización: ' + err.message);
            });
    }
    
    function cerrarVerCot() {
        document.getElementById('verCotModal').classList.add('hidden');
    }
</script>
</x-app-layout>
