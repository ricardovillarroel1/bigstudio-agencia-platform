<x-app-layout>

    <div class="py-6">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Header -->
            <div class="flex items-center justify-between">
                <h2 class="bs-display text-2xl text-gray-800">Gesti&oacute;n de <span class="text-brand-600">Suscripciones</span></h2>
            </div>

            <!-- Estad&iacute;sticas -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div class="bs-card bs-card-body">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Activas</p>
                    <p class="bs-display text-3xl text-emerald-600 mt-1">{{ $estadisticas['activas'] }}</p>
                </div>
                <div class="bs-card bs-card-body">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Vencidas</p>
                    <p class="bs-display text-3xl text-red-600 mt-1">{{ $estadisticas['vencidas'] }}</p>
                </div>
                <div class="bs-card bs-card-body">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Canceladas</p>
                    <p class="bs-display text-3xl text-gray-600 mt-1">{{ $estadisticas['canceladas'] }}</p>
                </div>
                <div class="bs-card bs-card-body">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Manuales</p>
                    <p class="bs-display text-3xl text-gray-700 mt-1">{{ $estadisticas['manuales'] ?? 0 }}</p>
                </div>
                <div class="bs-card bs-card-body" style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%); border-color: #FFD89C;">
                    <p class="text-xs uppercase tracking-wide font-semibold" style="color: #B85B00;">Plan Gratis</p>
                    <p class="bs-display text-3xl mt-1" style="color: #B85B00;">{{ $estadisticas['gratis'] ?? 0 }}</p>
                </div>
            </div>

            <!-- Crear Suscripción Manual -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-4">
                    <h3 class="text-base font-semibold mb-2">Crear Suscripción Manual</h3>
                    <p class="text-sm text-gray-600 mb-3">Agrega una suscripción manualmente para un cliente. Si seleccionas el Plan Gratis, la suscripción será indefinida y sin costo.</p>
                    
                    <form action="{{ route('admin.suscripciones.crear-manual') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Cliente (Usuario)</label>
                            <select name="user_id" required class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500">
                                <option value="">Seleccionar cliente...</option>
                                @foreach($usuarios as $usuario)
                                    <option value="{{ $usuario->id }}">{{ $usuario->name }} ({{ $usuario->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Plan</label>
                            <select name="plan_id" id="plan_selector" required class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500" onchange="toggleDuracion()">
                                <option value="">Seleccionar plan...</option>
                                @foreach($planes as $plan)
                                    <option value="{{ $plan->id }}" data-precio="{{ $plan->precio }}">
                                        {{ $plan->nombre }} 
                                        @if($plan->precio == 0)
                                            (GRATIS)
                                        @else
                                            ({{ number_format($plan->precio, 0, ',', '.') }} {{ $plan->moneda }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div id="duracion_container">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Duración (días)</label>
                            <input type="number" name="duracion_dias" id="duracion_dias" value="30" min="1" max="36500" required
                                class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500">
                            <p id="duracion_hint" class="text-xs text-gray-500 mt-1" style="display:none;">Plan gratis: duración indefinida</p>
                        </div>
                        <div>
                            <button type="submit" class="bs-btn-primary w-full">
                                Crear Suscripción Manual
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de Suscripciones -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-4">
                    <h3 class="text-base font-semibold mb-3">Todas las Suscripciones</h3>

                    <div class="overflow-x-auto -mx-4 px-4" style="-webkit-overflow-scrolling: touch;">
                    <table class="w-full divide-y divide-gray-200 text-sm" style="min-width: 880px;">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Origen</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Inicio</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Próx. Pago</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Días Rest.</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($suscripciones as $suscripcion)
                                <tr>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm">
                                        #{{ $suscripcion->id }}
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm">
                                        {{ $suscripcion->user->name ?? 'N/A' }}
                                        <br><span class="text-xs text-gray-500">{{ $suscripcion->user->email ?? '' }}</span>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm">
                                        {{ $suscripcion->plan->nombre ?? 'N/A' }}
                                        @if($suscripcion->plan && $suscripcion->plan->precio == 0)
                                            <br><span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-accent-100 text-accent-800">GRATIS</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        @if($suscripcion->origen === 'manual')
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">MANUAL</span>
                                        @else
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-brand-100 text-brand-800">PAGO FLOW</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        @if($suscripcion->estado === 'activa')
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800">Activa</span>
                                        @elseif($suscripcion->estado === 'vencida')
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-800">Vencida</span>
                                        @else
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Cancelada</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm">
                                        {{ $suscripcion->fecha_inicio->format('d/m/Y') }}
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm">
                                        @if($suscripcion->plan && $suscripcion->plan->precio == 0)
                                            <span class="text-teal-600 font-semibold">Indefinido</span>
                                        @else
                                            {{ $suscripcion->proximo_pago->format('d/m/Y') }}
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm">
                                        @if($suscripcion->plan && $suscripcion->plan->precio == 0)
                                            <span class="text-teal-600 font-semibold">Sin vencimiento</span>
                                        @else
                                            @php
                                                $dias = $suscripcion->diasRestantes();
                                            @endphp
                                            @if($dias > 7)
                                                <span class="text-green-600">{{ $dias }} días</span>
                                            @elseif($dias >= 0)
                                                <span class="text-orange-600 font-semibold">{{ $dias }} días</span>
                                            @else
                                                <span class="text-red-600 font-semibold">Vencido ({{ abs($dias) }}d)</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm">
                                        <div class="flex flex-col gap-1">
                                            @if($suscripcion->plan && $suscripcion->plan->precio > 0)
                                                <button type="button"
                                                    onclick="abrirModalRenovar({{ $suscripcion->id }}, '{{ addslashes($suscripcion->user->name ?? 'N/A') }}', '{{ addslashes($suscripcion->plan->nombre ?? 'N/A') }}', '{{ $suscripcion->estado }}')"
                                                    class="bs-btn-primary bs-btn-sm w-full">
                                                    Renovar (Transferencia)
                                                </button>
                                            @endif
                                            @if($suscripcion->estado === 'activa')
                                                <form action="{{ route('admin.suscripciones.cancelar', $suscripcion) }}" method="POST"
                                                      onsubmit="return confirm('¿Estás seguro de cancelar la suscripción de {{ $suscripcion->user->name ?? 'este cliente' }}? Esto desactivará su integración.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="bg-red-500 hover:bg-red-700 text-white text-xs font-bold py-1 px-2 rounded w-full">
                                                        Cancelar
                                                    </button>
                                                </form>
                                            @elseif($suscripcion->estado === 'cancelada' || $suscripcion->estado === 'vencida')
                                                <form action="{{ route('admin.suscripciones.reactivar', $suscripcion) }}" method="POST"
                                                      onsubmit="return confirm('¿Reactivar la suscripción de {{ $suscripcion->user->name ?? 'este cliente' }}?')">
                                                    @csrf
                                                    <button type="submit" class="bg-green-500 hover:bg-green-700 text-white text-xs font-bold py-1 px-2 rounded w-full">
                                                        Reactivar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>

                    <div class="mt-4">
                        {{ $suscripciones->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Renovar Manual (Transferencia) -->
    <div id="modalRenovar" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
            <h3 class="text-lg font-bold mb-4">Renovar Suscripción por Transferencia</h3>
            
            <div class="bg-brand-50 border border-brand-200 rounded-lg p-3 mb-4">
                <p class="text-sm"><strong>Cliente:</strong> <span id="modal_cliente"></span></p>
                <p class="text-sm"><strong>Plan:</strong> <span id="modal_plan"></span></p>
                <p class="text-sm"><strong>Estado actual:</strong> <span id="modal_estado"></span></p>
            </div>

            <form id="formRenovar" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duración (días)</label>
                    <div class="flex gap-2 mb-2">
                        <button type="button" onclick="setDias(30)" class="px-3 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded">30 días</button>
                        <button type="button" onclick="setDias(60)" class="px-3 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded">60 días</button>
                        <button type="button" onclick="setDias(90)" class="px-3 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded">90 días</button>
                        <button type="button" onclick="setDias(365)" class="px-3 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded">1 año</button>
                    </div>
                    <input type="number" name="duracion_dias" id="modal_duracion" value="30" min="1" max="365" required
                        class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Motivo / Referencia de transferencia</label>
                    <input type="text" name="motivo" placeholder="Ej: Transferencia bancaria #12345" 
                        class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="cerrarModalRenovar()" class="px-4 py-2 text-sm bg-gray-300 hover:bg-gray-400 rounded-lg">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg">
                        Confirmar Renovación
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleDuracion() {
            const select = document.getElementById('plan_selector');
            const option = select.options[select.selectedIndex];
            const precio = parseFloat(option.getAttribute('data-precio') || '1');
            const duracionInput = document.getElementById('duracion_dias');
            const duracionHint = document.getElementById('duracion_hint');
            
            if (precio === 0) {
                duracionInput.value = 36500;
                duracionInput.readOnly = true;
                duracionInput.style.backgroundColor = '#f0fdfa';
                duracionHint.style.display = 'block';
            } else {
                duracionInput.value = 30;
                duracionInput.readOnly = false;
                duracionInput.style.backgroundColor = '';
                duracionHint.style.display = 'none';
            }
        }

        function abrirModalRenovar(suscripcionId, clienteNombre, planNombre, estado) {
            document.getElementById('modal_cliente').textContent = clienteNombre;
            document.getElementById('modal_plan').textContent = planNombre;
            document.getElementById('modal_estado').textContent = estado.charAt(0).toUpperCase() + estado.slice(1);
            document.getElementById('modal_duracion').value = 30;
            
            // Construir la URL de la acción del formulario
            const baseUrl = '{{ url("admin/suscripciones") }}';
            document.getElementById('formRenovar').action = baseUrl + '/' + suscripcionId + '/renovar-manual';
            
            document.getElementById('modalRenovar').classList.remove('hidden');
        }

        function cerrarModalRenovar() {
            document.getElementById('modalRenovar').classList.add('hidden');
        }

        function setDias(dias) {
            document.getElementById('modal_duracion').value = dias;
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('modalRenovar').addEventListener('click', function(e) {
            if (e.target === this) cerrarModalRenovar();
        });
    </script>
</x-app-layout>
