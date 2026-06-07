<x-app-layout>

    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('agencia.clientes') }}" class="text-brand-600 hover:text-brand-800 text-sm">&larr; Volver a Clientes</a>
                <h2 class="text-xl font-bold text-gray-800 mt-2">Editar: {{ $cliente->nombre }}</h2>
            </div>

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <ul class="list-disc list-inside text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Datos del Cliente -->
                <div class="lg:col-span-2">
                    <form method="POST" action="{{ route('agencia.clientes.update', $cliente) }}" class="bs-card p-6">
                        @csrf @method('PUT')

                        <h3 class="font-semibold text-gray-700 mb-4 pb-2 border-b">Información General</h3>
                        <div class="grid md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                                <input type="text" name="nombre" value="{{ old('nombre', $cliente->nombre) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="email" value="{{ old('email', $cliente->email) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                                <input type="text" name="telefono" value="{{ old('telefono', $cliente->telefono) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                <select name="estado" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="activo" {{ $cliente->estado === 'activo' ? 'selected' : '' }}>Activo</option>
                                    <option value="inactivo" {{ $cliente->estado === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                            </div>
                        </div>

                        <h3 class="font-semibold text-gray-700 mb-4 pb-2 border-b">Datos de Facturación</h3>
                        <div class="grid md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">RUT</label>
                                <input type="text" name="rut" value="{{ old('rut', $cliente->rut) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Razón Social</label>
                                <input type="text" name="razon_social" value="{{ old('razon_social', $cliente->razon_social) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Giro</label>
                                <input type="text" name="giro" value="{{ old('giro', $cliente->giro) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dirección Fiscal</label>
                                <input type="text" name="direccion_fiscal" value="{{ old('direccion_fiscal', $cliente->direccion_fiscal) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                                <input type="text" name="ciudad" value="{{ old('ciudad', $cliente->ciudad) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Comuna</label>
                                <input type="text" name="comuna" value="{{ old('comuna', $cliente->comuna) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Región</label>
                                <input type="text" name="region" value="{{ old('region', $cliente->region) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                            <textarea name="notas" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('notas', $cliente->notas) }}</textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-lg font-semibold text-sm transition">Guardar Cambios</button>
                        </div>
                    </form>
                </div>

                <!-- Sidebar: Resumen -->
                <div class="space-y-6">
                    <div class="bs-card p-4">
                        <h4 class="font-semibold text-gray-700 mb-3">Resumen</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Servicios:</span><span class="font-semibold">{{ $cliente->servicios->count() }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Suscripciones:</span><span class="font-semibold">{{ $cliente->suscripciones->where('estado', 'activa')->count() }} activas</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Cobros pend.:</span><span class="font-semibold text-amber-600">{{ $cliente->cobros->where('estado', 'pendiente')->count() }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Creado:</span><span class="font-semibold">{{ $cliente->created_at->format('d/m/Y') }}</span></div>
                        </div>
                    </div>

                    <!-- Últimos Cobros -->
                    @if($cliente->cobros->count() > 0)
                    <div class="bs-card p-4">
                        <h4 class="font-semibold text-gray-700 mb-3">Últimos Cobros</h4>
                        @foreach($cliente->cobros->take(5) as $cobro)
                            <div class="flex justify-between items-center py-2 {{ !$loop->last ? 'border-b border-gray-50' : '' }}">
                                <div>
                                    <p class="text-xs text-gray-600">{{ Str::limit($cobro->concepto, 25) }}</p>
                                    <p class="text-xs text-gray-400">{{ $cobro->created_at->format('d/m/Y') }}</p>
                                </div>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $cobro->estado === 'pagado' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                    ${{ number_format($cobro->monto, 0, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
