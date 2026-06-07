<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Catalogo de Servicios</h2>
                    <p class="text-sm text-gray-500">Servicios que ofrece Big Studio a sus clientes</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-400">Valor UF hoy</p>
                    <p class="text-sm font-bold text-brand-600">${{ number_format($valorUF, 2, ',', '.') }}</p>
                </div>
            </div>
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                    @foreach($errors->all() as $error) <p class="text-sm">{{ $error }}</p> @endforeach
                </div>
            @endif
            <!-- Formulario para nuevo servicio -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <h3 class="font-semibold text-gray-700 mb-4">Agregar Nuevo Servicio</h3>
                <form method="POST" action="{{ route('agencia.servicios.store') }}" class="grid md:grid-cols-6 gap-3 items-end">
                    @csrf
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-500 block mb-1">Nombre del Servicio</label>
                        <input type="text" name="nombre" required placeholder="Ej: Manejo de anuncios Meta Ads" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Moneda</label>
                        <select name="moneda" id="createMoneda" onchange="togglePrecioCreate()" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="CLP">CLP</option>
                            <option value="UF">UF</option>
                        </select>
                    </div>
                    <div id="createPrecioCLP">
                        <label class="text-xs text-gray-500 block mb-1">Precio Base (CLP)</label>
                        <input type="number" name="precio" placeholder="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div id="createPrecioUF" style="display:none;">
                        <label class="text-xs text-gray-500 block mb-1">Precio Base (UF)</label>
                        <input type="number" name="precio_uf" step="0.0001" placeholder="0.00" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" oninput="calcularCLPCreate()">
                        <p id="createConversion" class="text-xs text-gray-400 mt-1"></p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Periodicidad</label>
                        <select name="periodicidad" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="mensual">Mensual</option>
                            <option value="trimestral">Trimestral</option>
                            <option value="semestral">Semestral</option>
                            <option value="anual">Anual</option>
                            <option value="unico">Pago Unico</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Crear Servicio</button>
                    </div>
                    <div class="md:col-span-6 mt-1">
                        <label class="text-xs text-gray-500 block mb-1">Descripcion (opcional)</label>
                        <input type="text" name="descripcion" placeholder="Ej: Inversion en Meta Ads de $2.000.000" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </form>
            </div>
            <!-- Lista de servicios -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left">Servicio</th>
                                <th class="px-4 py-3 text-right">Precio Base</th>
                                <th class="px-4 py-3 text-center">Periodicidad</th>
                                <th class="px-4 py-3 text-center">Clientes</th>
                                <th class="px-4 py-3 text-center">Estado</th>
                                <th class="px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($servicios as $servicio)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-gray-800">{{ $servicio->nombre }}</p>
                                        @if($servicio->descripcion)
                                            <p class="text-xs text-gray-400 mt-0.5">{{ $servicio->descripcion }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @if($servicio->moneda === 'UF')
                                            <p class="font-semibold text-brand-600">{{ number_format($servicio->precio_uf, 2, ',', '.') }} UF</p>
                                            <p class="text-xs text-gray-400">${{ number_format(round($servicio->precio_uf * $valorUF), 0, ',', '.') }} CLP</p>
                                        @else
                                            <p class="font-semibold text-brand-600">${{ number_format($servicio->precio, 0, ',', '.') }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-block px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full text-xs font-semibold">{{ ucfirst($servicio->periodicidad) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-block bg-brand-100 text-brand-700 px-2 py-0.5 rounded-full text-xs font-semibold">{{ $servicio->cliente_servicios_count }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <form method="POST" action="{{ route('agencia.servicios.toggle', $servicio) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $servicio->activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                                {{ $servicio->activo ? 'Activo' : 'Inactivo' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex flex-col gap-1 items-center">
                                            <button onclick="editarServicio({{ $servicio->id }}, {!! htmlspecialchars(json_encode(['nombre'=>$servicio->nombre,'descripcion'=>$servicio->descripcion,'moneda'=>$servicio->moneda ?? 'CLP','precio'=>$servicio->precio,'precio_uf'=>$servicio->precio_uf,'periodicidad'=>$servicio->periodicidad]), ENT_QUOTES, 'UTF-8') !!})" class="text-brand-600 hover:text-brand-800 text-xs font-semibold">Editar</button>
                                            <form method="POST" action="{{ route('agencia.servicios.delete', $servicio) }}" class="inline" onsubmit="return confirm('Eliminar este servicio?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-semibold">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">No hay servicios registrados. Crea el primero arriba.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Editar Servicio -->
    <!-- Modal Editar Servicio -->
    <div id="editServicioModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)cerrarEditModal()">
        <div style="background:#fff;border-radius:1rem;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);width:100%;max-width:32rem;margin:0 auto;overflow:hidden;">
            <form id="editServicioForm" method="POST">
                @csrf @method('PUT')
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-800">Editar Servicio</h3>
                    <button type="button" onclick="cerrarEditModal()" style="color:#9ca3af;padding:0.25rem;border-radius:0.5rem;border:none;background:none;cursor:pointer;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <!-- Body -->
                <div class="px-6 py-5 space-y-5">
                    <div>
                        <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.375rem;">Nombre del Servicio</label>
                        <input type="text" name="nombre" id="edit_nombre" required class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.375rem;">Descripcion</label>
                        <input type="text" name="descripcion" id="edit_descripcion" placeholder="Descripcion del servicio" class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div>
                            <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.375rem;">Moneda</label>
                            <select name="moneda" id="edit_moneda" onchange="togglePrecioEdit()" class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition bg-white">
                                <option value="CLP">CLP (Pesos Chilenos)</option>
                                <option value="UF">UF (Unidad de Fomento)</option>
                            </select>
                        </div>
                        <div id="editPrecioCLP">
                            <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.375rem;">Precio (CLP)</label>
                            <input type="number" name="precio" id="edit_precio" style="width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.625rem 0.875rem;font-size:0.875rem;outline:none;margin-bottom:1rem;">
                        </div>
                        <div id="editPrecioUF" style="display:none;">
                            <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.375rem;">Precio (UF)</label>
                            <input type="number" name="precio_uf" id="edit_precio_uf" step="0.0001" class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" oninput="calcularCLPEdit()">
                            <p id="editConversion" style="font-size:0.75rem;color:#6366f1;margin-top:0.25rem;font-weight:500;"></p>
                        </div>
                    </div>
                    <div>
                        <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.375rem;">Periodicidad</label>
                        <select name="periodicidad" id="edit_periodicidad" style="width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.625rem 0.875rem;font-size:0.875rem;outline:none;background:#fff;margin-bottom:1rem;">
                            <option value="mensual">Mensual</option>
                            <option value="trimestral">Trimestral</option>
                            <option value="semestral">Semestral</option>
                            <option value="anual">Anual</option>
                            <option value="unico">Pago Unico</option>
                        </select>
                    </div>
                </div>
                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50">
                    <button type="button" onclick="cerrarEditModal()" style="padding:0.625rem 1.25rem;border:1px solid #d1d5db;border-radius:0.5rem;font-size:0.875rem;font-weight:500;color:#374151;background:#fff;cursor:pointer;">Cancelar</button>
                    <button type="submit" style="padding:0.625rem 1.25rem;border:none;border-radius:0.5rem;font-size:0.875rem;font-weight:600;color:#fff;background:#FF8100;cursor:pointer;box-shadow:0 1px 2px rgba(0,0,0,0.05);">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        const valorUF = {{ $valorUF }};
        function togglePrecioCreate() {
            const moneda = document.getElementById('createMoneda').value;
            document.getElementById('createPrecioCLP').style.display = moneda === 'CLP' ? '' : 'none';
            document.getElementById('createPrecioUF').style.display = moneda === 'UF' ? '' : 'none';
        }
        function calcularCLPCreate() {
            const uf = parseFloat(document.querySelector('#createPrecioUF input').value) || 0;
            const clp = Math.round(uf * valorUF);
            document.getElementById('createConversion').textContent = uf > 0 ? '≈ $' + clp.toLocaleString('es-CL') + ' CLP' : '';
        }
        function togglePrecioEdit() {
            const moneda = document.getElementById('edit_moneda').value;
            document.getElementById('editPrecioCLP').style.display = moneda === 'CLP' ? '' : 'none';
            document.getElementById('editPrecioUF').style.display = moneda === 'UF' ? '' : 'none';
        }
        function calcularCLPEdit() {
            const uf = parseFloat(document.getElementById('edit_precio_uf').value) || 0;
            const clp = Math.round(uf * valorUF);
            document.getElementById('editConversion').textContent = uf > 0 ? '≈ $' + clp.toLocaleString('es-CL') + ' CLP' : '';
        }
        function editarServicio(id, data) {
            document.getElementById('editServicioForm').action = '/agencia/servicios/' + id;
            document.getElementById('edit_nombre').value = data.nombre;
            document.getElementById('edit_descripcion').value = data.descripcion || '';
            document.getElementById('edit_moneda').value = data.moneda || 'CLP';
            document.getElementById('edit_precio').value = data.precio;
            document.getElementById('edit_precio_uf').value = data.precio_uf || '';
            document.getElementById('edit_periodicidad').value = data.periodicidad;
            togglePrecioEdit();
            if (data.moneda === 'UF') calcularCLPEdit();
            var modal = document.getElementById('editServicioModal');
            document.body.appendChild(modal);
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        function cerrarEditModal() {
            document.getElementById('editServicioModal').style.display = 'none';
            document.body.style.overflow = '';
        }
    </script>
</x-app-layout>
