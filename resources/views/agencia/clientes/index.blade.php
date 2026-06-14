<x-app-layout>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bs-card overflow-hidden mb-6">
                <div class="px-6 py-5 flex items-center justify-between flex-wrap gap-3" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                    <div>
                        <h2 class="bs-display text-2xl text-white m-0 leading-tight">Clientes de Agencia</h2>
                        <p class="text-sm text-white/90 mt-1 mb-0">Gestiona los clientes de servicios de Big Studio</p>
                    </div>
                    <a href="{{ route('agencia.clientes.create') }}" class="bs-btn-neutral shrink-0">
                        <i class="fas fa-plus"></i> Nuevo Cliente
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Filtros -->
            <div class="bs-card p-4 mb-6">
                <form method="GET" class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="text-xs text-gray-500 block mb-1">Buscar</label>
                        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre, email, RUT..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Estado</label>
                        <select name="estado" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                            <option value="">Todos</option>
                            <option value="activo" {{ request('estado') === 'activo' ? 'selected' : '' }}>Activos</option>
                            <option value="inactivo" {{ request('estado') === 'inactivo' ? 'selected' : '' }}>Inactivos</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-brand-700 transition">Filtrar</button>
                </form>
            </div>

            <!-- Tabla -->
            <div class="bs-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3 text-left">Cliente</th>
                                <th class="px-4 py-3 text-left">Contacto</th>
                                <th class="px-4 py-3 text-left">RUT</th>
                                <th class="px-4 py-3 text-center">Servicios</th>
                                <th class="px-4 py-3 text-center">Suscripciones</th>
                                <th class="px-4 py-3 text-center">Cobros Pend.</th>
                                <th class="px-4 py-3 text-center">Estado</th>
                                <th class="px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($clientes as $cliente)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-gray-800">{{ $cliente->nombre }}@if($cliente->proyecto) <span class="text-xs font-normal text-brand-600">({{ $cliente->proyecto }})</span>@endif</p>
                                        @if($cliente->razon_social)
                                            <p class="text-xs text-gray-400">{{ $cliente->razon_social }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-gray-600">{{ $cliente->email ?? '-' }}</p>
                                        <p class="text-xs text-gray-400">{{ $cliente->telefono ?? '' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $cliente->rut ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-block bg-brand-100 text-brand-700 px-2 py-0.5 rounded-full text-xs font-semibold">{{ $cliente->servicios_count }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-block bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs font-semibold">{{ $cliente->suscripciones_count }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($cliente->cobros_count > 0)
                                            <span class="inline-block bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full text-xs font-semibold">{{ $cliente->cobros_count }}</span>
                                        @else
                                            <span class="text-gray-300">0</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $cliente->estado === 'activo' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                            {{ ucfirst($cliente->estado) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button onclick="verCliente({{ $cliente->id }}, {!! htmlspecialchars(json_encode(['nombre'=>$cliente->nombre,'razon_social'=>$cliente->razon_social,'rut'=>$cliente->rut,'giro'=>$cliente->giro,'email'=>$cliente->email,'telefono'=>$cliente->telefono,'direccion_fiscal'=>$cliente->direccion_fiscal,'comuna'=>$cliente->comuna,'ciudad'=>$cliente->ciudad,'region'=>$cliente->region,'estado'=>$cliente->estado,'notas'=>$cliente->notas,'servicios_count'=>$cliente->servicios_count,'suscripciones_count'=>$cliente->suscripciones_count,'cobros_count'=>$cliente->cobros_count,'created_at'=>$cliente->created_at?->format('d/m/Y')]), ENT_QUOTES, 'UTF-8') !!})" class="text-blue-600 hover:text-blue-800 font-semibold text-xs">Ver</button>
                                        <a href="{{ route('agencia.clientes.detalle', $cliente) }}" class="text-amber-600 hover:text-amber-800 font-semibold text-xs ml-2">
                                            Tareas
                                            @if(($cliente->tareas_pendientes_count ?? 0) > 0)
                                                <span class="inline-block bg-amber-100 text-amber-700 rounded-full px-1.5 text-[10px]">{{ $cliente->tareas_pendientes_count }}</span>
                                            @endif
                                        </a>
                                        <a href="{{ route('agencia.clientes.edit', $cliente) }}" class="text-brand-600 hover:text-brand-800 font-semibold text-xs ml-2">Editar</a>
                                        <form method="POST" action="{{ route('agencia.clientes.delete', $cliente) }}" class="inline" onsubmit="return confirm('¿Eliminar este cliente?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 font-semibold text-xs ml-2">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-400">No hay clientes registrados. <a href="{{ route('agencia.clientes.create') }}" class="text-brand-600 hover:underline">Crear el primero</a></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($clientes->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">
                        {{ $clientes->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Vista Previa Cliente -->
    <div id="previewModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;padding:1rem;" onclick="if(event.target===this)cerrarPreview()">
        <div style="background:#fff;border-radius:1rem;width:100%;max-width:32rem;margin:0 auto;overflow:hidden;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);max-height:90vh;overflow-y:auto;">
            <!-- Header -->
            <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;background:#fff;">
                <h3 style="font-weight:700;color:#1f2937;font-size:1.125rem;margin:0;" id="preview_nombre"></h3>
                <button onclick="cerrarPreview()" style="color:#9ca3af;font-size:1.5rem;border:none;background:none;cursor:pointer;padding:0.25rem;line-height:1;">&times;</button>
            </div>
            <!-- Body -->
            <div style="padding:1.25rem 1.5rem;">
                <!-- Razon Social / RUT -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div>
                        <p style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.25rem 0;">Razon Social</p>
                        <p style="font-size:0.875rem;font-weight:500;color:#1f2937;margin:0;" id="preview_razon_social">-</p>
                    </div>
                    <div>
                        <p style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.25rem 0;">RUT</p>
                        <p style="font-size:0.875rem;font-weight:500;color:#1f2937;margin:0;" id="preview_rut">-</p>
                    </div>
                </div>
                <!-- Giro / Estado -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div>
                        <p style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.25rem 0;">Giro</p>
                        <p style="font-size:0.875rem;font-weight:500;color:#1f2937;margin:0;" id="preview_giro">-</p>
                    </div>
                    <div>
                        <p style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.25rem 0;">Estado</p>
                        <p style="font-size:0.875rem;font-weight:500;margin:0;" id="preview_estado">-</p>
                    </div>
                </div>
                <!-- Divider -->
                <hr style="border:none;border-top:1px solid #f3f4f6;margin:1rem 0;">
                <!-- Email / Telefono -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div>
                        <p style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.25rem 0;">Email</p>
                        <p style="font-size:0.875rem;font-weight:500;color:#1f2937;margin:0;word-break:break-all;" id="preview_email">-</p>
                    </div>
                    <div>
                        <p style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.25rem 0;">Telefono</p>
                        <p style="font-size:0.875rem;font-weight:500;color:#1f2937;margin:0;" id="preview_telefono">-</p>
                    </div>
                </div>
                <!-- Direccion / Comuna -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div>
                        <p style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.25rem 0;">Direccion</p>
                        <p style="font-size:0.875rem;font-weight:500;color:#1f2937;margin:0;" id="preview_direccion">-</p>
                    </div>
                    <div>
                        <p style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.25rem 0;">Comuna / Ciudad</p>
                        <p style="font-size:0.875rem;font-weight:500;color:#1f2937;margin:0;" id="preview_ubicacion">-</p>
                    </div>
                </div>
                <!-- Divider -->
                <hr style="border:none;border-top:1px solid #f3f4f6;margin:1rem 0;">
                <!-- Stats Cards -->
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem;text-align:center;margin-bottom:1rem;">
                    <div style="background:#FFF7EC;border-radius:0.5rem;padding:0.75rem;">
                        <p style="font-size:0.7rem;color:#FF8100;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.25rem 0;">Servicios</p>
                        <p style="font-size:1.25rem;font-weight:700;color:#FF8100;margin:0;" id="preview_servicios">0</p>
                    </div>
                    <div style="background:#f0fdf4;border-radius:0.5rem;padding:0.75rem;">
                        <p style="font-size:0.7rem;color:#16a34a;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.25rem 0;">Suscripciones</p>
                        <p style="font-size:1.25rem;font-weight:700;color:#15803d;margin:0;" id="preview_suscripciones">0</p>
                    </div>
                    <div style="background:#fffbeb;border-radius:0.5rem;padding:0.75rem;">
                        <p style="font-size:0.7rem;color:#d97706;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.25rem 0;">Cobros Pend.</p>
                        <p style="font-size:1.25rem;font-weight:700;color:#b45309;margin:0;" id="preview_cobros">0</p>
                    </div>
                </div>
                <!-- Notas -->
                <div style="margin-bottom:0.75rem;">
                    <p style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.25rem 0;">Notas</p>
                    <p style="font-size:0.875rem;color:#374151;margin:0;" id="preview_notas">-</p>
                </div>
                <!-- Cliente desde -->
                <div>
                    <p style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.25rem 0;">Cliente desde</p>
                    <p style="font-size:0.875rem;color:#374151;margin:0;" id="preview_created">-</p>
                </div>
            </div>
        </div>
    </div>
    <script>
        function verCliente(id, data) {
            document.getElementById('preview_nombre').textContent = data.nombre || '-';
            document.getElementById('preview_razon_social').textContent = data.razon_social || '-';
            document.getElementById('preview_rut').textContent = data.rut || '-';
            document.getElementById('preview_giro').textContent = data.giro || '-';
            document.getElementById('preview_email').textContent = data.email || '-';
            document.getElementById('preview_telefono').textContent = data.telefono || '-';
            document.getElementById('preview_direccion').textContent = data.direccion_fiscal || '-';
            document.getElementById('preview_ubicacion').textContent = (data.comuna || '-') + ' / ' + (data.ciudad || '-') + (data.region ? ' / ' + data.region : '');
            document.getElementById('preview_servicios').textContent = data.servicios_count || 0;
            document.getElementById('preview_suscripciones').textContent = data.suscripciones_count || 0;
            document.getElementById('preview_cobros').textContent = data.cobros_count || 0;
            document.getElementById('preview_notas').textContent = data.notas || 'Sin notas';
            document.getElementById('preview_created').textContent = data.created_at || '-';
            const estado = data.estado || 'inactivo';
            const el = document.getElementById('preview_estado');
            el.textContent = estado.charAt(0).toUpperCase() + estado.slice(1);
            el.className = 'text-sm font-medium ' + (estado === 'activo' ? 'text-green-600' : 'text-gray-500');
            var modal = document.getElementById('previewModal');
            document.body.appendChild(modal);
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        function cerrarPreview() {
            document.getElementById('previewModal').style.display = 'none';
            document.body.style.overflow = '';
        }
    </script>
</x-app-layout>
