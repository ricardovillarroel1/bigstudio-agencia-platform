<x-app-layout>
    <div class="py-6">
        <div style="max-width: 100%; margin: 0 auto; padding: 0 1.5rem;">
            <div class="bs-card overflow-hidden mb-6">
                <div class="px-6 py-5" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                    <h2 class="bs-display text-2xl text-white m-0 leading-tight">Suscripciones de Servicios</h2>
                    <p class="text-sm text-white/90 mt-1 mb-0">Planes de cobro recurrente para clientes de agencia</p>
                </div>
            </div>

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ session('error') }}</div>
            @endif

            <!-- Crear suscripcion -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <h3 class="font-semibold text-gray-700 mb-4">Nueva Suscripcion</h3>
                <form id="suscForm" method="POST" action="{{ route('agencia.suscripciones.store') }}">
                    @csrf
                    <div class="grid md:grid-cols-3 gap-3 mb-4">
                        <div>
                            <label class="text-xs text-gray-500 block mb-1">Cliente</label>
                            <select name="agencia_cliente_id" id="suscCliente" required class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                                <option value="">Seleccionar...</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 block mb-1">Periodicidad</label>
                            <select name="periodicidad" id="suscPeriodicidad" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                                <option value="mensual">Mensual</option>
                                <option value="trimestral">Trimestral</option>
                                <option value="semestral">Semestral</option>
                                <option value="anual">Anual</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 block mb-1">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" required value="{{ date('Y-m-d') }}" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                        </div>
                    </div>

                    <!-- Servicios / Items -->
                    <div class="mb-4">
                        <label class="text-xs text-gray-500 block mb-2 font-semibold" style="font-size: 0.8rem;">Servicios de la Suscripcion</label>
                        <div id="suscItemsContainer">
                            <div class="suscItemRow grid gap-2 mb-2" style="grid-template-columns: 2fr 2fr 1fr auto; align-items: end;">
                                <div>
                                    <label class="text-xs text-gray-400 block mb-1">Servicio</label>
                                    <select name="items[0][servicio_id]" class="suscServicioSelect w-full border border-gray-300 rounded-lg px-2 py-2 text-sm" onchange="suscServicioChanged(this)">
                                        <option value="">Manual</option>
                                        @if(isset($servicios))
                                            @foreach($servicios as $srv)
                                                <option value="{{ $srv->id }}" data-precio="{{ $srv->precio ?? 0 }}" data-nombre="{{ $srv->nombre }}">{{ $srv->nombre }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-400 block mb-1">Descripcion</label>
                                    <input type="text" name="items[0][descripcion]" class="suscDescInput w-full border border-gray-300 rounded-lg px-2 py-2 text-sm" placeholder="Descripcion del servicio">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-400 block mb-1">Monto Neto</label>
                                    <input type="number" name="items[0][monto_neto]" class="suscMontoInput w-full border border-gray-300 rounded-lg px-2 py-2 text-sm" placeholder="0" min="0" oninput="calcularTotalSusc()">
                                </div>
                                <div style="padding-bottom: 2px;">
                                    <button type="button" onclick="removeSuscItem(this)" class="text-red-500 hover:text-red-700 text-lg font-bold" title="Eliminar">&times;</button>
                                </div>
                            </div>
                        </div>
                        <button type="button" onclick="addSuscItem()" class="text-brand-600 hover:text-brand-800 text-sm font-semibold mt-1">+ Agregar Servicio</button>
                    </div>

                    <!-- Totales -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-500">Total Neto:</span>
                            <span id="suscTotalNeto" class="font-semibold">$0</span>
                        </div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-500">IVA (19%):</span>
                            <span id="suscTotalIva" class="font-semibold">$0</span>
                        </div>
                        <div class="flex justify-between text-base border-t pt-2 mt-2">
                            <span class="font-bold text-gray-800">TOTAL:</span>
                            <span id="suscTotalFinal" class="font-bold text-brand-600">$0</span>
                        </div>
                    </div>

                    <input type="hidden" name="monto" id="suscMontoTotal" value="0">
                    <input type="hidden" name="concepto" id="suscConcepto" value="">

                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #4b5563;">
                            <input type="checkbox" name="facturacion_automatica" value="1" checked style="border-radius: 0.25rem; border: 1px solid #d1d5db; accent-color: #FF8100;">
                            Facturacion automatica
                        </label>
                        <button type="submit" style="background-color: #16a34a; color: white; padding: 0.625rem 2rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 700; border: none; cursor: pointer; margin-left: auto; transition: background-color 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.12);" onmouseover="this.style.backgroundColor='#15803d'" onmouseout="this.style.backgroundColor='#16a34a'">
                            &#10003; Crear Suscripcion
                        </button>
                    </div>
                </form>
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
                <form method="GET" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Estado</label>
                        <select name="estado" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Todos</option>
                            <option value="activa" {{ request('estado') === 'activa' ? 'selected' : '' }}>Activas</option>
                            <option value="pausada" {{ request('estado') === 'pausada' ? 'selected' : '' }}>Pausadas</option>
                            <option value="cancelada" {{ request('estado') === 'cancelada' ? 'selected' : '' }}>Canceladas</option>
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
                    <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">Filtrar</button>
                </form>
            </div>

            <!-- Tabla -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left">Cliente</th>
                                <th class="px-4 py-3 text-left">Servicios</th>
                                <th class="px-4 py-3 text-right">Monto</th>
                                <th class="px-4 py-3 text-center">Periodicidad</th>
                                <th class="px-4 py-3 text-center">Proximo Cobro</th>
                                <th class="px-4 py-3 text-center">Estado</th>
                                <th class="px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($suscripciones as $sub)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-800">{{ $sub->cliente->nombre ?? 'N/A' }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($sub->items && $sub->items->count() > 0)
                                            @foreach($sub->items as $item)
                                                <div class="text-xs text-gray-700 mb-1">
                                                    <span class="font-medium">{{ $item->descripcion }}</span>
                                                    <span class="text-gray-400 ml-1">${{ number_format($item->monto_neto, 0, ',', '.') }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-gray-500 text-xs">{{ $sub->concepto }}</span>
                                            @if($sub->descripcion)
                                                <span class="text-gray-400 text-xs block">{{ $sub->descripcion }}</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-brand-600">${{ number_format($sub->monto, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full text-xs font-semibold">{{ ucfirst($sub->periodicidad) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($sub->proximo_cobro)
                                            <span class="{{ $sub->proximo_cobro->isPast() ? 'text-red-600 font-bold' : ($sub->proximo_cobro->diffInDays(now()) <= 7 ? 'text-amber-600 font-semibold' : 'text-gray-600') }}">
                                                {{ $sub->proximo_cobro->format('d/m/Y') }}
                                            </span>
                                            @if($sub->proximo_cobro->isPast())
                                                <span class="block text-xs text-red-500">Vencido</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @php
                                            $estadoBadge = [
                                                'activa'    => 'bg-green-50 text-green-700',
                                                'pausada'   => 'bg-amber-50 text-amber-700',
                                                'cancelada' => 'bg-gray-100 text-gray-500',
                                                'vencida'   => 'bg-red-50 text-red-700',
                                            ];
                                        @endphp
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $estadoBadge[$sub->estado] ?? 'bg-gray-100 text-gray-500' }}">{{ ucfirst($sub->estado) }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-3">
                                            @if($sub->estado === 'activa')
                                                <form method="POST" action="{{ route('agencia.suscripciones.pausar', $sub) }}" onsubmit="return confirm('¿Pausar esta suscripción? No se generarán cobros automáticos hasta reanudarla.');">
                                                    @csrf
                                                    <button type="submit" class="text-amber-600 hover:text-amber-800 text-xs font-semibold">Pausar</button>
                                                </form>
                                            @elseif($sub->estado === 'pausada')
                                                <form method="POST" action="{{ route('agencia.suscripciones.pausar', $sub) }}" onsubmit="return confirm('¿Reanudar esta suscripción?');">
                                                    @csrf
                                                    <button type="submit" class="text-green-600 hover:text-green-800 text-xs font-semibold">Reanudar</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('agencia.suscripciones.eliminar', $sub) }}" onsubmit="return confirm('¿Eliminar esta suscripción? Si tiene cobros asociados se conservará como cancelada; si no, se borrará definitivamente. Esta acción no se puede deshacer.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-semibold">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">No hay suscripciones registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($suscripciones->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">{{ $suscripciones->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    <script>
    let suscItemIndex = 1;
    const serviciosData = @json($servicios ?? []);

    function addSuscItem() {
        const container = document.getElementById('suscItemsContainer');
        const html = `
            <div class="suscItemRow grid gap-2 mb-2" style="grid-template-columns: 2fr 2fr 1fr auto; align-items: end;">
                <div>
                    <select name="items[${suscItemIndex}][servicio_id]" class="suscServicioSelect w-full border border-gray-300 rounded-lg px-2 py-2 text-sm" onchange="suscServicioChanged(this)">
                        <option value="">Manual</option>
                        ${serviciosData.map(s => `<option value="${s.id}" data-precio="${s.precio || 0}" data-nombre="${s.nombre}">${s.nombre}</option>`).join('')}
                    </select>
                </div>
                <div>
                    <input type="text" name="items[${suscItemIndex}][descripcion]" class="suscDescInput w-full border border-gray-300 rounded-lg px-2 py-2 text-sm" placeholder="Descripcion del servicio">
                </div>
                <div>
                    <input type="number" name="items[${suscItemIndex}][monto_neto]" class="suscMontoInput w-full border border-gray-300 rounded-lg px-2 py-2 text-sm" placeholder="0" min="0" oninput="calcularTotalSusc()">
                </div>
                <div style="padding-bottom: 2px;">
                    <button type="button" onclick="removeSuscItem(this)" class="text-red-500 hover:text-red-700 text-lg font-bold" title="Eliminar">&times;</button>
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', html);
        suscItemIndex++;
    }

    function removeSuscItem(btn) {
        const rows = document.querySelectorAll('.suscItemRow');
        if (rows.length > 1) {
            btn.closest('.suscItemRow').remove();
            calcularTotalSusc();
        }
    }

    function suscServicioChanged(sel) {
        const row = sel.closest('.suscItemRow');
        const opt = sel.options[sel.selectedIndex];
        if (opt && opt.value) {
            const precio = opt.getAttribute('data-precio');
            const nombre = opt.getAttribute('data-nombre');
            if (precio && parseFloat(precio) > 0) {
                row.querySelector('.suscMontoInput').value = Math.round(parseFloat(precio));
            }
            if (nombre) {
                row.querySelector('.suscDescInput').value = nombre;
            }
        }
        calcularTotalSusc();
    }

    function calcularTotalSusc() {
        let totalNeto = 0;
        document.querySelectorAll('.suscMontoInput').forEach(inp => {
            totalNeto += parseInt(inp.value) || 0;
        });
        const iva = Math.round(totalNeto * 0.19);
        const total = totalNeto + iva;
        document.getElementById('suscTotalNeto').textContent = '$' + totalNeto.toLocaleString('es-CL');
        document.getElementById('suscTotalIva').textContent = '$' + iva.toLocaleString('es-CL');
        document.getElementById('suscTotalFinal').textContent = '$' + total.toLocaleString('es-CL');
        document.getElementById('suscMontoTotal').value = total;

        // Build concepto from items
        const descs = [];
        document.querySelectorAll('.suscDescInput').forEach(inp => {
            if (inp.value.trim()) descs.push(inp.value.trim());
        });
        document.getElementById('suscConcepto').value = descs.join(' + ') || 'Servicios de agencia';
    }

    document.getElementById('suscForm').addEventListener('submit', function(e) {
        calcularTotalSusc();
        const monto = parseInt(document.getElementById('suscMontoTotal').value) || 0;
        if (monto <= 0) {
            e.preventDefault();
            alert('Debe agregar al menos un servicio con monto.');
            return false;
        }
    });
    </script>
</x-app-layout>
