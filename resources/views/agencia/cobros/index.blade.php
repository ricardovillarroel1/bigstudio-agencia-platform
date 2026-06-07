<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Cobros</h2>
                    <p class="text-sm text-gray-500 mt-1">Gestiona los cobros de pago unico de la agencia</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-400">Valor UF hoy</p>
                    <p class="text-sm font-bold text-brand-600">${{ number_format($valorUF, 2, ',', '.') }}</p>
                </div>
            </div>
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                    @foreach($errors->all() as $error) <p class="text-sm">{{ $error }}</p> @endforeach
                </div>
            @endif
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total Pendiente</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">${{ number_format($totalPendiente, 0, ',', '.') }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Pagado Este Mes</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">${{ number_format($totalPagadoMes, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <h3 class="font-semibold text-gray-700 mb-4">Crear Cobro (Pago Unico o en Cuotas)</h3>
                <form method="POST" action="{{ route('agencia.cobros.store') }}" id="cobroForm" class="space-y-3">
                    @csrf
                    <div class="grid md:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-gray-500 block mb-1">Cliente</label>
                            <select name="agencia_cliente_id" required class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                                <option value="">Seleccionar...</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 block mb-1">Fecha 1er Vencimiento</label>
                            <input type="date" name="vence_at" id="fechaVenceInput" required class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm" onchange="calcularTodo()">
                        </div>
                    </div>
                    {{-- Multi-service selector --}}
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Servicios</label>
                        <div class="flex gap-2">
                            <select id="servicioAddSelect" class="flex-1 border border-gray-300 rounded-lg px-2 py-2 text-sm">
                                <option value="">Seleccionar servicio para agregar...</option>
                                @foreach($servicios as $s)
                                    <option value="{{ $s->id }}" data-moneda="{{ $s->moneda }}" data-precio="{{ $s->precio }}" data-precio-uf="{{ $s->precio_uf }}" data-nombre="{{ $s->nombre }}">
                                        {{ $s->nombre }} - {{ $s->moneda === 'UF' ? number_format($s->precio_uf, 2, ',', '.') . ' UF' : '$' . number_format($s->precio, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" onclick="agregarServicio()" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition whitespace-nowrap">+ Agregar</button>
                        </div>
                        <div id="serviciosAgregados" class="mt-2 space-y-1"></div>
                        <div id="serviciosHiddenInputs"></div>
                    </div>
                    <div class="grid md:grid-cols-3 gap-3">
                        <div>
                            <label class="text-xs text-gray-500 block mb-1">Concepto (auto o manual)</label>
                            <input type="text" name="concepto" id="conceptoInput" placeholder="Se genera automaticamente al agregar servicios" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 block mb-1">Monto Neto Total (CLP)</label>
                            <input type="number" name="monto_neto" id="montoNetoInput" placeholder="0" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm" oninput="calcularTodo()">
                        </div>
                        <div>
                            <div class="bg-gray-50 rounded-lg px-3 py-2 border border-gray-200">
                                <p class="text-xs text-gray-500">Desglose Total</p>
                                <p class="text-xs mt-1">Neto: <span id="displayNeto" class="font-semibold">$0</span></p>
                                <p class="text-xs">IVA (19%): <span id="displayIVA" class="font-semibold">$0</span></p>
                                <p class="text-sm font-bold text-brand-600">Total: <span id="displayTotal">$0</span></p>
                            </div>
                        </div>
                    </div>
                    <div class="grid md:grid-cols-3 gap-3">
                        <div>
                            <label class="text-xs text-gray-500 block mb-1">Numero de Cuotas</label>
                            <select name="num_cuotas" id="numCuotasSelect" onchange="calcularTodo()" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                                <option value="1">1 (Pago Unico)</option>
                                <option value="2">2 Cuotas</option>
                                <option value="3">3 Cuotas</option>
                                <option value="4">4 Cuotas</option>
                                <option value="6">6 Cuotas</option>
                                <option value="8">8 Cuotas</option>
                                <option value="10">10 Cuotas</option>
                                <option value="12">12 Cuotas</option>
                            </select>
                        </div>
                        <div id="intervaloDiasContainer" style="display:none;">
                            <label class="text-xs text-gray-500 block mb-1">Intervalo entre Cuotas</label>
                            <select name="intervalo_dias" id="intervaloDiasSelect" onchange="calcularTodo()" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                                <option value="15">Cada 15 dias</option>
                                <option value="30" selected>Cada 30 dias (mensual)</option>
                                <option value="60">Cada 60 dias (bimestral)</option>
                            </select>
                        </div>
                        <div id="cuotaPreviewContainer" style="display:none;">
                            <div class="bg-brand-50 rounded-lg px-3 py-2 border border-brand-200">
                                <p class="text-xs text-brand-600 font-semibold">Detalle de Cuotas</p>
                                <p class="text-xs mt-1">Valor por cuota: <span id="displayCuotaValor" class="font-bold text-brand-700">$0</span></p>
                                <p class="text-xs">Cuotas: <span id="displayCuotaCantidad" class="font-semibold">1</span></p>
                            </div>
                        </div>
                    </div>
                    <div id="cuotasDetalleContainer" style="display:none;">
                        <div class="bg-white border border-brand-200 rounded-lg overflow-hidden">
                            <table class="w-full text-xs">
                                <thead class="bg-brand-50 text-brand-700">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Cuota</th>
                                        <th class="px-3 py-2 text-right">Monto</th>
                                        <th class="px-3 py-2 text-center">Vencimiento</th>
                                    </tr>
                                </thead>
                                <tbody id="cuotasDetalleBody" class="divide-y divide-brand-50"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-1 text-xs text-gray-600">
                            <input type="checkbox" name="enviar_correo" value="1" checked class="rounded border-gray-300 text-brand-600">
                            Enviar correo al cliente
                        </label>
                        <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg text-sm font-semibold transition" id="btnCrearCobro">Crear Cobro y Emitir Factura</button>
                    </div>
                </form>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
                <form method="GET" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Estado</label>
                        <select name="estado" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Todos</option>
                            <option value="pendiente" {{ request('estado') === 'pendiente' ? 'selected' : '' }}>Pendientes</option>
                            <option value="pagado" {{ request('estado') === 'pagado' ? 'selected' : '' }}>Pagados</option>
                            <option value="anulado" {{ request('estado') === 'anulado' ? 'selected' : '' }}>Anulados</option>
                        </select>
                    </div>
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
                        <label class="text-xs text-gray-500 block mb-1">Periodo</label>
                        <select name="periodo" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" onchange="toggleFechaFiltro(this.value)">
                            <option value="">Todo</option>
                            <option value="dia" {{ request('periodo') === 'dia' ? 'selected' : '' }}>Por Dia</option>
                            <option value="mes" {{ request('periodo') === 'mes' ? 'selected' : '' }}>Por Mes</option>
                            <option value="anio" {{ request('periodo') === 'anio' ? 'selected' : '' }}>Por Año</option>
                        </select>
                    </div>
                    <div id="filtroFechaDia" style="{{ request('periodo') === 'dia' ? '' : 'display:none' }}">
                        <label class="text-xs text-gray-500 block mb-1">Fecha</label>
                        <input type="date" name="fecha" value="{{ request('fecha', date('Y-m-d')) }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div id="filtroFechaMes" style="{{ request('periodo') === 'mes' ? '' : 'display:none' }}">
                        <label class="text-xs text-gray-500 block mb-1">Mes</label>
                        <input type="month" name="mes" value="{{ request('mes', date('Y-m')) }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div id="filtroFechaAnio" style="{{ request('periodo') === 'anio' ? '' : 'display:none' }}">
                        <label class="text-xs text-gray-500 block mb-1">Año</label>
                        <select name="anio" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            @for($y = date('Y'); $y >= 2024; $y--)
                                <option value="{{ $y }}" {{ request('anio', date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">Filtrar</button>
                    @if(request()->hasAny(['estado', 'cliente_id', 'periodo']))
                        <a href="{{ route('agencia.cobros') }}" class="text-gray-500 hover:text-gray-700 text-sm px-3 py-2">Limpiar</a>
                    @endif
                </form>
            </div>
            @if(request('periodo'))
            <div class="bg-gradient-to-r from-emerald-50 to-green-50 rounded-xl border border-emerald-200 p-4 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-emerald-600 uppercase font-semibold tracking-wide">Resumen del Periodo</p>
                        <p class="text-sm text-gray-600 mt-1">
                            @if(request('periodo') === 'dia') Dia: {{ \Carbon\Carbon::parse(request('fecha', now()))->format('d/m/Y') }}
                            @elseif(request('periodo') === 'mes') Mes: {{ \Carbon\Carbon::parse(request('mes', now()->format('Y-m')) . '-01')->translatedFormat('F Y') }}
                            @else Año: {{ request('anio', date('Y')) }}
                            @endif
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">Total Pagado</p>
                        <p class="text-xl font-bold text-emerald-600">${{ number_format($totalPagadoPeriodo ?? 0, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
            @endif
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left">Cliente</th>
                                <th class="px-4 py-3 text-left">Concepto</th>
                                <th class="px-4 py-3 text-right">Monto</th>
                                <th class="px-4 py-3 text-center">Cuota</th>
                                <th class="px-4 py-3 text-center">Estado</th>
                                <th class="px-4 py-3 text-center">Metodo</th>
                                <th class="px-4 py-3 text-center">Factura</th>
                                <th class="px-4 py-3 text-center">Vencimiento</th>
                                <th class="px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($cobros as $cobro)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ $cobro->cliente->nombre ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $cobro->concepto }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-brand-600">${{ number_format($cobro->monto, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($cobro->cuota_total && $cobro->cuota_total > 1)
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-brand-100 text-brand-700">{{ $cobro->cuota_numero }}/{{ $cobro->cuota_total }}</span>
                                        @else
                                            <span class="text-gray-400 text-xs">Unico</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                            {{ $cobro->estado === 'pagado' ? 'bg-green-100 text-green-700' : ($cobro->estado === 'pendiente' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                            {{ ucfirst($cobro->estado) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-xs text-gray-500">
                                        {{ $cobro->metodo_pago ? ucfirst($cobro->metodo_pago) : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($cobro->factura_estado === 'emitida')
                                            <div class="flex flex-col items-center gap-1">
                                                <span class="text-green-600 text-xs font-semibold">Folio #{{ $cobro->lioren_folio }}</span>
                                                <div class="flex gap-2">
                                                    <a href="{{ route('agencia.cobros.ver-factura', $cobro) }}" target="_blank" class="text-blue-500 hover:text-blue-700 text-xs" title="Ver Factura"><i class="fas fa-eye"></i></a>
                                                    <form method="POST" action="{{ route('agencia.cobros.reenviar-factura', $cobro) }}" class="inline" onsubmit="return confirm('Reenviar factura a ' + '{{ $cobro->cliente->email ?? "cliente" }}' + '?')">
                                                        @csrf
                                                        <button type="submit" class="text-amber-500 hover:text-amber-700 text-xs" title="Reenviar al cliente"><i class="fas fa-paper-plane"></i></button>
                                                    </form>
                                                </div>
                                            </div>
                                        @elseif($cobro->factura_estado === 'error')
                                            <span class="text-red-500 text-xs">Error</span>
                                        @elseif($cobro->nc_estado === 'emitida')
                                            <div class="flex flex-col items-center gap-1">
                                                <span class="text-orange-600 text-xs font-semibold">NC #{{ $cobro->nc_folio }}</span>
                                                <div class="flex gap-2">
                                                    <a href="{{ route('agencia.cobros.ver-factura', ['cobro' => $cobro, 'tipo' => 'nc']) }}" target="_blank" class="text-blue-500 hover:text-blue-700 text-xs" title="Ver NC"><i class="fas fa-eye"></i></a>
                                                    <form method="POST" action="{{ route('agencia.cobros.reenviar-factura', ['cobro' => $cobro, 'tipo' => 'nc']) }}" class="inline" onsubmit="return confirm('Reenviar NC a cliente?')">
                                                        @csrf
                                                        <button type="submit" class="text-amber-500 hover:text-amber-700 text-xs" title="Reenviar NC"><i class="fas fa-paper-plane"></i></button>
                                                    </form>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-gray-300 text-xs">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-xs text-gray-500">
                                        {{ $cobro->vence_at ? \Carbon\Carbon::parse($cobro->vence_at)->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($cobro->estado === 'pendiente')
                                            <div class="flex flex-col gap-1">
                                                <button onclick="marcarPagado({{ $cobro->id }})" class="text-green-600 hover:text-green-800 text-xs font-semibold">Marcar Pagado</button>
                                                <form method="POST" action="{{ route('agencia.cobros.enviar-correo', $cobro) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">Enviar Correo</button>
                                                </form>
                                                <form method="POST" action="{{ route('agencia.cobros.flow', $cobro) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-brand-600 hover:text-brand-800 text-xs font-semibold">Link Flow</button>
                                                </form>
                                                <form method="POST" action="{{ route('agencia.cobros.anular', $cobro) }}" class="inline" onsubmit="return confirm('Anular este cobro?')">
                                                    @csrf
                                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-semibold">Anular</button>
                                                </form>
                                            </div>
                                        @elseif($cobro->estado === 'pagado')
                                            <div class="flex flex-col gap-1">
                                                <span class="text-green-500 text-xs font-semibold">✓ Pagado</span>
                                                <button onclick="anularPago({{ $cobro->id }}, '{{ addslashes($cobro->concepto) }}', {{ $cobro->monto }}, '{{ $cobro->lioren_folio }}', '{{ $cobro->factura_estado }}')" class="text-red-500 hover:text-red-700 text-xs font-semibold">Anular Pago</button>
                                            </div>
                                        @else
                                            <span class="text-gray-400 text-xs">{{ ucfirst($cobro->estado) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-gray-400">No hay cobros registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($cobros->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">{{ $cobros->links() }}</div>
                @endif
            </div>
        </div>
    </div>
    <div id="pagarModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl p-6 w-full max-w-md">
            <h3 class="font-semibold text-gray-800 mb-4">Marcar como Pagado</h3>
            <form id="pagarForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-600">Metodo de Pago</label>
                        <select name="metodo_pago" required class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="transferencia">Transferencia</option>
                            <option value="flow">Flow</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Comprobante (imagen o PDF)</label>
                        <input type="file" name="comprobante" accept=".jpg,.jpeg,.png,.pdf" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Notas</label>
                        <input type="text" name="notas_admin" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Notas opcionales...">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" onclick="cerrarPagarModal()" class="px-4 py-2 border rounded-lg text-sm">Cancelar</button>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">Confirmar Pago</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        const valorUF = {{ $valorUF }};
        let serviciosAgregados = [];
        let servicioCounter = 0;

        function agregarServicio() {
            const sel = document.getElementById('servicioAddSelect');
            const opt = sel.options[sel.selectedIndex];
            if (!opt.value) return;

            const moneda = opt.dataset.moneda;
            const precio = parseFloat(opt.dataset.precio) || 0;
            const precioUf = parseFloat(opt.dataset.precioUf) || 0;
            const nombre = opt.dataset.nombre;

            let montoNeto = 0;
            let displayPrecio = '';
            if (moneda === 'UF') {
                montoNeto = Math.round(precioUf * valorUF);
                displayPrecio = precioUf.toFixed(2) + ' UF ($' + montoNeto.toLocaleString('es-CL') + ')';
            } else {
                montoNeto = precio;
                displayPrecio = '$' + precio.toLocaleString('es-CL');
            }

            servicioCounter++;
            const item = {
                id: servicioCounter,
                servicioId: opt.value,
                nombre: nombre,
                montoNeto: montoNeto,
                displayPrecio: displayPrecio
            };
            serviciosAgregados.push(item);

            sel.selectedIndex = 0;
            renderServicios();
            recalcularMontoDesdeServicios();
        }

        function quitarServicio(itemId) {
            serviciosAgregados = serviciosAgregados.filter(s => s.id !== itemId);
            renderServicios();
            recalcularMontoDesdeServicios();
        }

        function renderServicios() {
            const container = document.getElementById('serviciosAgregados');
            const hiddenContainer = document.getElementById('serviciosHiddenInputs');
            container.innerHTML = '';
            hiddenContainer.innerHTML = '';

            if (serviciosAgregados.length === 0) {
                container.innerHTML = '<p class="text-xs text-gray-400 italic">No hay servicios agregados. Puede agregar servicios o ingresar el monto manualmente.</p>';
                return;
            }

            serviciosAgregados.forEach((item, idx) => {
                const div = document.createElement('div');
                div.style.cssText = 'display:flex;align-items:center;justify-content:space-between;background:#f3f4f6;border-radius:8px;padding:6px 12px;';
                div.innerHTML = '<div style="flex:1;">' +
                    '<span style="font-size:13px;font-weight:600;color:#374151;">' + item.nombre + '</span>' +
                    '<span style="font-size:12px;color:#6366f1;margin-left:8px;">' + item.displayPrecio + '</span>' +
                    '</div>' +
                    '<button type="button" onclick="quitarServicio(' + item.id + ')" style="color:#ef4444;font-size:18px;font-weight:bold;border:none;background:none;cursor:pointer;padding:0 4px;" title="Quitar">&times;</button>';
                container.appendChild(div);

                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'servicios_ids[]';
                hidden.value = item.servicioId;
                hiddenContainer.appendChild(hidden);
            });
        }

        function recalcularMontoDesdeServicios() {
            if (serviciosAgregados.length > 0) {
                let totalNeto = 0;
                let conceptos = [];
                serviciosAgregados.forEach(s => {
                    totalNeto += s.montoNeto;
                    conceptos.push(s.nombre);
                });
                document.getElementById('montoNetoInput').value = totalNeto;
                document.getElementById('conceptoInput').value = conceptos.join(' + ');
            }
            calcularTodo();
        }

        function calcularTodo() {
            const neto = parseInt(document.getElementById('montoNetoInput').value) || 0;
            const iva = Math.round(neto * 0.19);
            const total = neto + iva;
            const numCuotas = parseInt(document.getElementById('numCuotasSelect').value) || 1;
            const intervaloDias = parseInt(document.getElementById('intervaloDiasSelect').value) || 30;
            const fechaInput = document.getElementById('fechaVenceInput').value;

            document.getElementById('displayNeto').textContent = '$' + neto.toLocaleString('es-CL');
            document.getElementById('displayIVA').textContent = '$' + iva.toLocaleString('es-CL');
            document.getElementById('displayTotal').textContent = '$' + total.toLocaleString('es-CL');

            const intervaloContainer = document.getElementById('intervaloDiasContainer');
            const previewContainer = document.getElementById('cuotaPreviewContainer');
            const detalleContainer = document.getElementById('cuotasDetalleContainer');
            const detalleBody = document.getElementById('cuotasDetalleBody');
            const btnCrear = document.getElementById('btnCrearCobro');

            if (numCuotas > 1) {
                intervaloContainer.style.display = '';
                previewContainer.style.display = '';
                detalleContainer.style.display = '';

                const montoPorCuota = Math.floor(total / numCuotas);
                const residuo = total - (montoPorCuota * numCuotas);

                document.getElementById('displayCuotaValor').textContent = '$' + montoPorCuota.toLocaleString('es-CL');
                document.getElementById('displayCuotaCantidad').textContent = numCuotas + ' cuotas';

                btnCrear.textContent = 'Crear ' + numCuotas + ' Cuotas y Emitir Facturas';

                detalleBody.innerHTML = '';
                for (let i = 1; i <= numCuotas; i++) {
                    let monto = montoPorCuota;
                    if (i === numCuotas) monto += residuo;

                    let fechaVence = '-';
                    if (fechaInput) {
                        const fecha = new Date(fechaInput + 'T12:00:00');
                        fecha.setDate(fecha.getDate() + ((i - 1) * intervaloDias));
                        fechaVence = fecha.toLocaleDateString('es-CL', { day: '2-digit', month: '2-digit', year: 'numeric' });
                    }

                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td class="px-3 py-2 text-left font-semibold">Cuota ' + i + '/' + numCuotas + '</td>' +
                        '<td class="px-3 py-2 text-right font-semibold text-brand-700">$' + monto.toLocaleString('es-CL') + '</td>' +
                        '<td class="px-3 py-2 text-center">' + fechaVence + '</td>';
                    detalleBody.appendChild(tr);
                }

                const trTotal = document.createElement('tr');
                trTotal.className = 'bg-brand-50 font-bold';
                trTotal.innerHTML = '<td class="px-3 py-2 text-left text-brand-700">TOTAL</td>' +
                    '<td class="px-3 py-2 text-right text-brand-700">$' + total.toLocaleString('es-CL') + '</td>' +
                    '<td class="px-3 py-2 text-center text-brand-600">' + numCuotas + ' cuotas</td>';
                detalleBody.appendChild(trTotal);
            } else {
                intervaloContainer.style.display = 'none';
                previewContainer.style.display = 'none';
                detalleContainer.style.display = 'none';
                btnCrear.textContent = 'Crear Cobro y Emitir Factura';
            }
        }

        function marcarPagado(cobroId) {
            document.getElementById('pagarForm').action = '/agencia/cobros/' + cobroId + '/pagar';
            document.getElementById('pagarModal').classList.remove('hidden');
        }

        function cerrarPagarModal() {
            document.getElementById('pagarModal').classList.add('hidden');
        }

        // Init
        renderServicios();

        // === Anular Pago Modal ===
        function anularPago(cobroId, concepto, monto, folioFactura, facturaEstado) {
            document.getElementById('anularPagoForm').action = '/agencia/cobros/' + cobroId + '/anular-pago';
            document.getElementById('anularPago_concepto').textContent = concepto;
            document.getElementById('anularPago_monto').textContent = '$' + monto.toLocaleString('es-CL');
            
            var ncSection = document.getElementById('anularPago_ncSection');
            if (facturaEstado === 'emitida' && folioFactura) {
                ncSection.style.display = '';
                document.getElementById('anularPago_folioInfo').textContent = 'Factura Folio #' + folioFactura + ' - Se emitirá Nota de Crédito automáticamente';
            } else {
                ncSection.style.display = 'none';
            }
            
            document.getElementById('anularPagoModal').classList.remove('hidden');
        }

        function cerrarAnularPagoModal() {
            document.getElementById('anularPagoModal').classList.add('hidden');
        }
    </script>

    <!-- Modal Anular Pago -->
    <div id="anularPagoModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl p-6 w-full max-w-md">
            <h3 class="font-semibold text-gray-800 mb-2">Anular Pago</h3>
            <p class="text-sm text-gray-500 mb-4">Esta acción revertirá el estado del cobro y opcionalmente emitirá una Nota de Crédito.</p>
            
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                <p class="text-xs text-red-600 font-semibold">Cobro a anular:</p>
                <p class="text-sm text-red-800 font-bold" id="anularPago_concepto"></p>
                <p class="text-lg text-red-700 font-bold" id="anularPago_monto"></p>
            </div>
            
            <form id="anularPagoForm" method="POST">
                @csrf
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-600">Motivo de Anulación</label>
                        <input type="text" name="motivo_anulacion" required class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Ej: Error en el monto, pago duplicado, etc.">
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Nuevo Estado del Cobro</label>
                        <select name="nuevo_estado" class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="anulado">Anulado (cerrar definitivamente)</option>
                            <option value="pendiente">Pendiente (volver a cobrar)</option>
                        </select>
                    </div>
                    <div id="anularPago_ncSection">
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input type="checkbox" name="emitir_nota_credito" value="1" checked class="rounded border-gray-300 text-red-600">
                            Emitir Nota de Crédito en Lioren
                        </label>
                        <p class="text-xs text-amber-600 mt-1" id="anularPago_folioInfo"></p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" onclick="cerrarAnularPagoModal()" class="px-4 py-2 border rounded-lg text-sm">Cancelar</button>
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold" onclick="return confirm('¿Está seguro de anular este pago? Esta acción no se puede deshacer.')">Confirmar Anulación</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function toggleFechaFiltro(val) {
        document.getElementById('filtroFechaDia').style.display = val === 'dia' ? '' : 'none';
        document.getElementById('filtroFechaMes').style.display = val === 'mes' ? '' : 'none';
        document.getElementById('filtroFechaAnio').style.display = val === 'anio' ? '' : 'none';
    }

    // Protección contra doble clic / doble submit en formulario de crear cobro
    (function() {
        const form = document.getElementById('cobroForm');
        const btn = document.getElementById('btnCrearCobro');
        if (form && btn) {
            let submitted = false;
            form.addEventListener('submit', function(e) {
                if (submitted) {
                    e.preventDefault();
                    return false;
                }
                submitted = true;
                btn.disabled = true;
                btn.style.opacity = '0.6';
                btn.style.cursor = 'not-allowed';
                btn.textContent = 'Procesando... No cierre esta página';
                setTimeout(function() {
                    submitted = false;
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.style.cursor = 'pointer';
                    btn.textContent = 'Crear Cobro y Emitir Factura';
                }, 15000);
            });
        }

        // También proteger el formulario de marcar como pagado
        const pagarForm = document.getElementById('pagarForm');
        if (pagarForm) {
            let pagarSubmitted = false;
            pagarForm.addEventListener('submit', function(e) {
                if (pagarSubmitted) {
                    e.preventDefault();
                    return false;
                }
                pagarSubmitted = true;
                const submitBtn = pagarForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.6';
                    submitBtn.textContent = 'Procesando...';
                }
                setTimeout(function() {
                    pagarSubmitted = false;
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                        submitBtn.textContent = 'Confirmar Pago';
                    }
                }, 15000);
            });
        }
    })();
    </script>
</x-app-layout>
