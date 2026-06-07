<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Correos Corporativos</h2>
                    <p class="text-sm text-gray-500">Envia correos profesionales a tus clientes de agencia</p>
                </div>
                <span class="text-sm text-gray-400">{{ now()->format('d/m/Y') }}</span>
            </div>

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ session('error') }}</div>
            @endif

            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <!-- Enviar Correo Individual -->
                <div class="bs-card">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Enviar Correo Individual
                        </h3>
                    </div>
                    <div class="p-6">
                        <form action="{{ route('agencia.correos.enviar') }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <label class="text-xs text-gray-500 block mb-1">Cliente Destinatario</label>
                                <select name="agencia_cliente_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                    <option value="">Seleccionar cliente...</option>
                                    @foreach($clientes as $c)
                                        <option value="{{ $c->id }}">{{ $c->nombre }} ({{ $c->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="text-xs text-gray-500 block mb-1">Asunto</label>
                                <input type="text" name="asunto" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500" placeholder="Asunto del correo...">
                            </div>
                            <div class="mb-4">
                                <label class="text-xs text-gray-500 block mb-1">Contenido</label>
                                <textarea name="contenido" rows="5" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500" placeholder="Escribe el contenido del correo..."></textarea>
                            </div>
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 w-full">
                                Enviar Correo
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Enviar Correo Masivo -->
                <div class="bs-card">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                            <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            Correo Masivo a Todos los Clientes
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">
                            <p class="text-xs text-amber-700">
                                <strong>Atencion:</strong> Este correo se enviara a <strong>todos los clientes activos</strong> con email registrado ({{ $clientes->count() }} clientes).
                            </p>
                        </div>
                        <form action="{{ route('agencia.correos.masivo') }}" method="POST" onsubmit="return confirm('Se enviara este correo a {{ $clientes->count() }} clientes. ¿Continuar?')">
                            @csrf
                            <div class="mb-4">
                                <label class="text-xs text-gray-500 block mb-1">Asunto</label>
                                <input type="text" name="asunto" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500" placeholder="Asunto del comunicado...">
                            </div>
                            <div class="mb-4">
                                <label class="text-xs text-gray-500 block mb-1">Contenido del Comunicado</label>
                                <textarea name="contenido" rows="5" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500" placeholder="Escribe el comunicado para todos los clientes..."></textarea>
                            </div>
                            <button type="submit" class="bg-brand-600 text-white px-6 py-2 rounded-lg text-sm font-semibold hover:bg-brand-700 w-full">
                                Enviar a Todos los Clientes ({{ $clientes->count() }})
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="bs-card p-4 mb-4">
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
                            <option value="enviado" {{ request('estado') === 'enviado' ? 'selected' : '' }}>Enviado</option>
                            <option value="error" {{ request('estado') === 'error' ? 'selected' : '' }}>Error</option>
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
                    <a href="{{ route('agencia.correos') }}" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm">Limpiar</a>
                </form>
            </div>

            <!-- Historial de Correos -->
            <div class="bs-card">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-700">Historial de Correos</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wide">
                            <tr>
                                <th class="px-4 py-3 text-left">Fecha</th>
                                <th class="px-4 py-3 text-left">Destinatario</th>
                                <th class="px-4 py-3 text-left">Asunto</th>
                                <th class="px-4 py-3 text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($correos as $correo)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-500">{{ $correo->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-800">{{ $correo->destinatario_nombre }}</p>
                                        <p class="text-xs text-gray-400">{{ $correo->destinatario_email }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ Str::limit($correo->asunto, 60) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($correo->estado === 'enviado')
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Enviado</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Error</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-400">No hay correos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($correos->hasPages())
                    <div class="px-6 py-3 border-t border-gray-100">
                        {{ $correos->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
