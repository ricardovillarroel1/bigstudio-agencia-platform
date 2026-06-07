<x-app-layout>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Mensajes de éxito/error --}}
            @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4">
                <div class="flex items-center">
                    <span class="text-green-600 text-xl mr-3">&#10004;</span>
                    <p class="text-green-700 font-medium">{{ session('success') }}</p>
                </div>
            </div>
            @endif
            @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
                <div class="flex items-center">
                    <span class="text-red-600 text-xl mr-3">&#10060;</span>
                    <div>
                        @foreach($errors->all() as $error)
                        <p class="text-red-700 font-medium">{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- ============================================================ --}}
            {{-- VISTA ADMIN --}}
            {{-- ============================================================ --}}
            @if($isAdmin)

            {{-- Header --}}
            <div class="overflow-hidden shadow-xl sm:rounded-2xl mb-8" style="background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);">
                <div class="p-8 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold mb-2" style="color: #ffffff;">Inventario</h1>
                            <p class="text-lg" style="color: rgba(255,255,255,0.85);">Gestión de inventario y mapeo de productos por cliente</p>
                        </div>
                        @if($selectedConfig)
                        <a href="{{ route($routePrefix) }}" class="bg-white/20 text-white px-5 py-2.5 rounded-xl font-semibold hover:bg-white/30 transition border border-white/30">
                            &#8592; Volver a Clientes
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Si NO hay cliente seleccionado: mostrar lista de clientes --}}
            @if(!$selectedConfigId)

            {{-- Resumen global --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                @php
                    $totalClients = count($clients);
                    $activeClients = collect($clients)->where('activo', true)->count();
                    $totalProducts = collect($clients)->sum('total_products');
                    $totalErrors = collect($clients)->sum('errors_today');
                @endphp
                <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 border-blue-500">
                    <p class="text-gray-500 text-xs font-semibold uppercase">Total Clientes</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalClients }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 border-green-500">
                    <p class="text-gray-500 text-xs font-semibold uppercase">Clientes Activos</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $activeClients }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 border-brand-500">
                    <p class="text-gray-500 text-xs font-semibold uppercase">Total Productos</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalProducts }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 border-red-500">
                    <p class="text-gray-500 text-xs font-semibold uppercase">Errores Hoy</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalErrors }}</p>
                </div>
            </div>

            {{-- Lista de clientes --}}
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100">
                <div class="bg-gradient-to-r from-gray-50 to-white p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800">Clientes con Integración</h2>
                    <p class="text-gray-500 text-sm mt-1">Selecciona un cliente para ver y gestionar su inventario</p>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse($clients as $client)
                    <a href="{{ route($routePrefix, ['config_id' => $client['config']->id]) }}"
                       class="block hover:bg-blue-50/50 transition-colors duration-150">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                {{-- Info del cliente --}}
                                <div class="flex items-center gap-4 flex-1">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-md
                                        {{ $client['activo'] ? 'bg-gradient-to-br from-blue-500 to-brand-600' : 'bg-gray-400' }}">
                                        {{ strtoupper(substr($client['user']->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-3 flex-wrap">
                                            <h3 class="text-lg font-bold text-gray-800 truncate">{{ $client['user']->name ?? 'Sin nombre' }}</h3>
                                            @if($client['activo'])
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Activo</span>
                                            @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactivo</span>
                                            @endif
                                            @if($client['sync_enabled'])
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Sync ON</span>
                                            @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Sync OFF</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                                            <p class="text-sm text-gray-500">{{ $client['user']->email ?? '' }}</p>
                                            <span class="text-gray-300 hidden sm:inline">|</span>
                                            <p class="text-sm text-gray-500">{{ $client['config']->shopify_tienda }}</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Estadísticas rápidas --}}
                                <div class="hidden sm:flex items-center gap-6 ml-4">
                                    <div class="text-center">
                                        <p class="text-2xl font-bold text-gray-800">{{ $client['total_products'] }}</p>
                                        <p class="text-xs text-gray-500 font-medium">Productos</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-2xl font-bold text-green-600">{{ $client['mapped'] }}</p>
                                        <p class="text-xs text-gray-500 font-medium">Mapeados</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-2xl font-bold text-yellow-600">{{ $client['unmapped'] }}</p>
                                        <p class="text-xs text-gray-500 font-medium">Sin Mapear</p>
                                    </div>
                                    @if($client['errors_today'] > 0)
                                    <div class="text-center">
                                        <p class="text-2xl font-bold text-red-600">{{ $client['errors_today'] }}</p>
                                        <p class="text-xs text-gray-500 font-medium">Errores</p>
                                    </div>
                                    @endif
                                    <div class="text-gray-400 ml-2">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            @if($client['last_sync'])
                            <div class="mt-2 ml-16">
                                <p class="text-xs text-gray-400">Última sincronización: {{ $client['last_sync']->format('d/m/Y H:i') }}</p>
                            </div>
                            @endif
                        </div>
                    </a>
                    @empty
                    <div class="p-12 text-center">
                        <p class="text-gray-500 text-lg">No hay clientes con integración configurada</p>
                    </div>
                    @endforelse
                </div>
            </div>

            @else
            {{-- ============================================================ --}}
            {{-- ADMIN: Detalle de un cliente seleccionado --}}
            {{-- ============================================================ --}}

            @if($selectedConfig)
            @php
                $clientUser = \App\Models\User::find($selectedConfig->user_id);
            @endphp

            {{-- Info del cliente seleccionado --}}
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6 border border-gray-100">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full flex items-center justify-center text-white font-bold text-xl shadow-md bg-gradient-to-br from-blue-500 to-brand-600">
                            {{ strtoupper(substr($clientUser->name ?? '?', 0, 1)) }}
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">{{ $clientUser->name ?? 'Cliente' }}</h2>
                            <p class="text-gray-500">{{ $clientUser->email ?? '' }} &middot; {{ $selectedConfig->shopify_tienda }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <form action="{{ route($routePrefix . '.sync') }}" method="POST">
                            @csrf
                            <input type="hidden" name="config_id" value="{{ $selectedConfig->id }}">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg">
                                Sincronizar Productos
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Estadísticas del cliente --}}
            @if(!empty($stats))
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 border-blue-500">
                    <p class="text-gray-500 text-xs font-semibold uppercase">Total Mapeos</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total_mappings'] ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 border-green-500">
                    <p class="text-gray-500 text-xs font-semibold uppercase">Mapeados a Lioren</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['mapped_to_lioren'] ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 border-yellow-500">
                    <p class="text-gray-500 text-xs font-semibold uppercase">Sin Mapear</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['unmapped'] ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 border-red-500">
                    <p class="text-gray-500 text-xs font-semibold uppercase">Errores Hoy</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['errors_today'] ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 {{ ($stats['sync_enabled'] ?? false) ? 'border-green-500' : 'border-gray-400' }}">
                    <p class="text-gray-500 text-xs font-semibold uppercase">Sync Estado</p>
                    <p class="text-lg font-bold {{ ($stats['sync_enabled'] ?? false) ? 'text-green-600' : 'text-gray-500' }}">
                        {{ ($stats['sync_enabled'] ?? false) ? 'Activo' : 'Inactivo' }}
                    </p>
                </div>
            </div>
            @endif

            {{-- Tabla de Mapeo de Productos --}}
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100 mb-8">
                <div class="bg-gradient-to-r from-gray-50 to-white p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center">
                        Mapeo de Productos Shopify &#8594; Lioren
                    </h2>
                    <p class="text-gray-500 text-sm mt-1">Vincula tus productos de Shopify con los productos de Lioren para sincronizar inventario</p>
                </div>
                <div class="p-6">
                    @if(count($mappings) > 0)
                    {{-- Filtro de búsqueda --}}
                    <div class="mb-4">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text" id="searchProductsAdmin" placeholder="Buscar por nombre de producto o SKU..." 
                                   class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-brand-500 focus:border-brand-500"
                                   onkeyup="filterTableAdmin()">
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto Shopify</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Precio</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto Lioren</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($mappings as $mapping)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-900">{{ $mapping->product_title }}</div>
                                        <div class="text-xs text-gray-500">ID: {{ $mapping->shopify_product_id }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $mapping->sku ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">${{ number_format($mapping->price ?? 0, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm font-medium {{ ($mapping->stock ?? 0) < 0 ? 'text-red-600' : 'text-gray-800' }}">
                                        {{ $mapping->stock ?? 0 }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($mapping->lioren_product_id)
                                        <span class="text-green-700 font-medium">Lioren #{{ $mapping->lioren_product_id }}</span>
                                        @else
                                        <span class="text-gray-400 italic">Sin mapear</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $mapping->sync_status === 'mapped' ? 'bg-green-100 text-green-800' : ($mapping->sync_status === 'error' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800') }}">
                                            {{ ucfirst($mapping->sync_status ?? 'synced') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($mapping->lioren_product_id)
                                        <form action="{{ route($routePrefix . '.unmap') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="mapping_id" value="{{ $mapping->id }}">
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Desmapear</button>
                                        </form>
                                        @else
                                        <button onclick="openMapModal({{ $mapping->id }}, '{{ addslashes($mapping->product_title) }}')"
                                                class="text-blue-600 hover:text-blue-800 text-sm font-medium">Mapear</button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-8">
                        <p class="text-gray-500 mb-4">No hay productos mapeados aún</p>
                        <form action="{{ route($routePrefix . '.sync') }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="config_id" value="{{ $selectedConfig->id }}">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-blue-700 transition">
                                Sincronizar Productos Ahora
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Historial de Sincronización --}}
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100">
                <div class="bg-gradient-to-r from-gray-50 to-white p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center">
                        Historial de Sincronización
                    </h2>
                </div>
                <div class="p-6">
                    @if(count($syncLogs) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dirección</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mensaje</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($syncLogs as $log)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">
                                        {{ $log->created_at->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $log->sync_type }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        @if($log->direction === 'shopify_to_lioren')
                                        <span class="text-blue-600">Shopify &#8594; Lioren</span>
                                        @elseif($log->direction === 'lioren_to_shopify')
                                        <span class="text-brand-600">Lioren &#8594; Shopify</span>
                                        @elseif($log->direction === 'shopify_to_local')
                                        <span class="text-green-600">Shopify &#8594; Local</span>
                                        @else
                                        {{ $log->direction }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($log->status === 'success')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">OK</span>
                                        @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Error</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 max-w-md truncate" title="{{ $log->message }}">
                                        {{ Str::limit($log->message, 80) }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($syncLogs, 'hasPages') && $syncLogs->hasPages())
                        <div class="mt-4">{{ $syncLogs->links() }}</div>
                    @endif
                    @else
                    <div class="text-center py-8">
                        <p class="text-gray-500">No hay registros de sincronización aún</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Modal para mapear producto --}}
            <div id="mapModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; align-items: center; justify-content: center;">
                <div style="background: white; border-radius: 1rem; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Mapear Producto a Lioren</h3>
                    <p class="text-gray-600 mb-4">Producto: <strong id="mapProductName"></strong></p>
                    <form action="{{ route($routePrefix . '.map') }}" method="POST">
                        @csrf
                        <input type="hidden" name="mapping_id" id="mapMappingId">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">ID Producto Lioren</label>
                            <input type="text" name="lioren_product_id" id="mapLiorenId" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Ej: 6211153">
                            <p class="text-xs text-gray-500 mt-1">Ingresa el ID del producto en Lioren</p>
                        </div>
                        <div id="liorenProductsList" class="mb-4 max-h-48 overflow-y-auto border rounded-lg" style="display: none;"></div>
                        <div class="flex gap-3 justify-end">
                            <button type="button" onclick="closeMapModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                                Guardar Mapeo
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function openMapModal(mappingId, productName) {
                    document.getElementById('mapMappingId').value = mappingId;
                    document.getElementById('mapProductName').textContent = productName;
                    document.getElementById('mapModal').style.display = 'flex';
                    loadLiorenProducts();
                }
                function closeMapModal() {
                    document.getElementById('mapModal').style.display = 'none';
                }
                function loadLiorenProducts() {
                    const configId = '{{ $selectedConfig->id ?? "" }}';
                    if (!configId) return;
                    fetch(`{{ route($routePrefix . '.lioren-products') }}?config_id=${configId}`)
                        .then(r => r.json())
                        .then(data => {
                            if (data.success && data.products.length > 0) {
                                const list = document.getElementById('liorenProductsList');
                                list.innerHTML = '<p class="px-3 py-2 text-xs text-gray-500 font-medium bg-gray-50">Productos disponibles en Lioren (clic para seleccionar):</p>';
                                data.products.forEach(p => {
                                    const item = document.createElement('div');
                                    item.className = 'px-3 py-2 hover:bg-blue-50 cursor-pointer border-b text-sm';
                                    item.innerHTML = `<strong>${p.nombre}</strong> <span class="text-gray-500">(Código: ${p.codigo}, ID: ${p.id})</span>`;
                                    item.onclick = () => { document.getElementById('mapLiorenId').value = p.id; };
                                    list.appendChild(item);
                                });
                                list.style.display = 'block';
                            }
                        })
                        .catch(err => console.error('Error loading Lioren products:', err));
                }
                document.getElementById('mapModal').addEventListener('click', function(e) {
                    if (e.target === this) closeMapModal();
                });
            </script>

            @endif
            @endif

            @endif

            {{-- ============================================================ --}}
            {{-- VISTA CLIENTE: Muestra directamente su inventario --}}
            {{-- ============================================================ --}}
            @if(!$isAdmin)

            {{-- Header cliente --}}
            <div class="overflow-hidden shadow-xl sm:rounded-2xl mb-8" style="background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);">
                <div class="p-8">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <h1 class="text-3xl font-bold mb-2" style="color: #ffffff;">Inventario Shopify - Lioren</h1>
                            <p class="text-lg" style="color: rgba(255,255,255,0.85);">Mapeo de productos y sincronización de stock</p>
                        </div>
                        <div class="flex items-center gap-4">
                            @if($selectedConfig)
                            <form action="{{ route($routePrefix . '.sync') }}" method="POST">
                                @csrf
                                <input type="hidden" name="config_id" value="{{ $selectedConfig->id }}">
                                <button type="submit" class="px-6 py-3 rounded-xl font-bold transition shadow-lg" style="background-color: #ffffff; color: #2563eb; border: none; cursor: pointer;" onmouseover="this.style.backgroundColor='#eff6ff'" onmouseout="this.style.backgroundColor='#ffffff'">
                                    Sincronizar Productos
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Estadísticas cliente (clickeables como filtros) --}}
            @if(!empty($stats))
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 border-blue-500 cursor-pointer transition hover:shadow-xl hover:scale-105" onclick="window.location='?filtro_estado=todos'" id="statCardTodos">
                    <p class="text-gray-500 text-xs font-semibold uppercase">Total Mapeos</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total_mappings'] ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 border-green-500 cursor-pointer transition hover:shadow-xl hover:scale-105" onclick="window.location='?filtro_estado=mapeados'" id="statCardMapeados">
                    <p class="text-gray-500 text-xs font-semibold uppercase">Mapeados a Lioren</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['mapped_to_lioren'] ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 border-yellow-500 cursor-pointer transition hover:shadow-xl hover:scale-105" onclick="window.location='?filtro_estado=sin_mapear'" id="statCardSinMapear">
                    <p class="text-gray-500 text-xs font-semibold uppercase">Sin Mapear</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['unmapped'] ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 border-red-500 cursor-pointer transition hover:shadow-xl hover:scale-105" onclick="window.location='?filtro_estado=error'" id="statCardError">
                    <p class="text-gray-500 text-xs font-semibold uppercase">Errores Hoy</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['errors_today'] ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-5 border-l-4 {{ ($stats['sync_enabled'] ?? false) ? 'border-green-500' : 'border-gray-400' }}">
                    <p class="text-gray-500 text-xs font-semibold uppercase">Sync Estado</p>
                    <p class="text-lg font-bold {{ ($stats['sync_enabled'] ?? false) ? 'text-green-600' : 'text-gray-500' }}">
                        {{ ($stats['sync_enabled'] ?? false) ? 'Activo' : 'Inactivo' }}
                    </p>
                </div>
            </div>
            @endif

            {{-- Tabla de Mapeo de Productos (cliente) --}}
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100 mb-8">
                <div class="bg-gradient-to-r from-gray-50 to-white p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center">
                        Mapeo de Productos Shopify &#8594; Lioren
                    </h2>
                    <p class="text-gray-500 text-sm mt-1">Vincula tus productos de Shopify con los productos de Lioren para sincronizar inventario</p>
                </div>
                <div class="p-6">
                    @if(count($mappings) > 0)
                    {{-- Barra de filtros y búsqueda (server-side) --}}
                    @php $fe = request('filtro_estado', 'todos'); $bp = request('buscar_prod', ''); @endphp
                    <div class="mb-4 flex flex-col md:flex-row gap-3 items-start md:items-center justify-between">
                        {{-- Filtros por estado como links --}}
                        <div class="flex flex-wrap gap-2">
                            <a href="?filtro_estado=todos{{ $bp ? '&buscar_prod='.urlencode($bp) : '' }}" class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $fe==='todos' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                Todos ({{ $countAll ?? 0 }})
                            </a>
                            <a href="?filtro_estado=mapeados{{ $bp ? '&buscar_prod='.urlencode($bp) : '' }}" class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $fe==='mapeados' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                Mapeados ({{ $countMapped ?? 0 }})
                            </a>
                            <a href="?filtro_estado=sin_mapear{{ $bp ? '&buscar_prod='.urlencode($bp) : '' }}" class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $fe==='sin_mapear' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                Sin Mapear ({{ $countUnmapped ?? 0 }})
                            </a>
                            <a href="?filtro_estado=error{{ $bp ? '&buscar_prod='.urlencode($bp) : '' }}" class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $fe==='error' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                Error ({{ $countError ?? 0 }})
                            </a>
                        </div>
                        {{-- Búsqueda server-side --}}
                        <form method="GET" action="" class="relative w-full md:w-80" style="margin:0;">
                            <input type="hidden" name="filtro_estado" value="{{ $fe }}">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text" name="buscar_prod" value="{{ $bp }}" placeholder="Buscar por nombre o SKU + Enter..."
                                   class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-brand-500 focus:border-brand-500">
                        </form>
                    </div>
                    @if($bp)
                    <div class="mb-3 flex items-center gap-2 text-sm text-gray-600 bg-brand-50 px-4 py-2 rounded-lg">
                        <span>Resultados para "<strong>{{ $bp }}</strong>"</span>
                        <a href="?filtro_estado={{ $fe }}" class="text-brand-600 hover:underline ml-auto">Limpiar búsqueda</a>
                    </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="clientProductTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto Shopify</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Precio</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto Lioren</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="clientProductTableBody">
                                @foreach($mappings as $mapping)
                                <tr class="hover:bg-gray-50 client-product-row" 
                                    data-mapped="{{ $mapping->lioren_product_id ? '1' : '0' }}" 
                                    data-status="{{ $mapping->sync_status ?? 'synced' }}"
                                    data-search="{{ strtolower($mapping->product_title . ' ' . ($mapping->sku ?? '')) }}">
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-900">{{ $mapping->product_title }}</div>
                                        <div class="text-xs text-gray-500">ID: {{ $mapping->shopify_product_id }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $mapping->sku ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">${{ number_format($mapping->price ?? 0, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm font-medium {{ ($mapping->stock ?? 0) < 0 ? 'text-red-600' : 'text-gray-800' }}">
                                        {{ $mapping->stock ?? 0 }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($mapping->lioren_product_id)
                                        <span class="text-green-700 font-medium">Lioren #{{ $mapping->lioren_product_id }}</span>
                                        @else
                                        <span class="text-gray-400 italic">Sin mapear</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $mapping->sync_status === 'mapped' ? 'bg-green-100 text-green-800' : ($mapping->sync_status === 'error' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800') }}">
                                            {{ ucfirst($mapping->sync_status ?? 'synced') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($mapping->lioren_product_id)
                                        <form action="{{ route($routePrefix . '.unmap') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="mapping_id" value="{{ $mapping->id }}">
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Desmapear</button>
                                        </form>
                                        @else
                                        <button onclick="openMapModal({{ $mapping->id }}, '{{ addslashes($mapping->product_title) }}')" 
                                                class="text-blue-600 hover:text-blue-800 text-sm font-medium">Mapear</button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginación server-side (10 por página) --}}
                    @if(method_exists($mappings, 'hasPages') && $mappings->hasPages())
                        <div class="mt-6 border-t border-gray-200 pt-4">{{ $mappings->links() }}</div>
                    @endif

                    @else
                    <div class="text-center py-8">
                        <p class="text-gray-500 mb-4">{{ request('buscar_prod') ? 'No se encontraron productos para tu búsqueda.' : 'No hay productos mapeados aún' }}</p>
                        @if($selectedConfig)
                        <form action="{{ route($routePrefix . '.sync') }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="config_id" value="{{ $selectedConfig->id }}">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-blue-700 transition">
                                Sincronizar Productos Ahora
                            </button>
                        </form>
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            {{-- Historial de Sincronización (cliente) --}}
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100">
                <div class="p-6 border-b border-gray-200" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        Historial de Sincronización
                    </h2>
                </div>
                <div class="p-6">
                    @if(count($syncLogs) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dirección</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mensaje</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($syncLogs as $log)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">
                                        {{ $log->created_at->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $log->sync_type }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        @if($log->direction === 'shopify_to_lioren')
                                        <span class="text-blue-600">Shopify &#8594; Lioren</span>
                                        @elseif($log->direction === 'lioren_to_shopify')
                                        <span class="text-brand-600">Lioren &#8594; Shopify</span>
                                        @elseif($log->direction === 'shopify_to_local')
                                        <span class="text-green-600">Shopify &#8594; Local</span>
                                        @else
                                        {{ $log->direction }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($log->status === 'success')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">OK</span>
                                        @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Error</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 max-w-md truncate" title="{{ $log->message }}">
                                        {{ Str::limit($log->message, 80) }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($syncLogs, 'hasPages') && $syncLogs->hasPages())
                        <div class="mt-4">{{ $syncLogs->links() }}</div>
                    @endif
                    @else
                    <div class="text-center py-8">
                        <p class="text-gray-500">No hay registros de sincronización aún</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Modal para mapear producto (cliente) --}}
            <div id="mapModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; align-items: center; justify-content: center;">
                <div style="background: white; border-radius: 1rem; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Mapear Producto a Lioren</h3>
                    <p class="text-gray-600 mb-4">Producto: <strong id="mapProductName"></strong></p>
                    <form action="{{ route($routePrefix . '.map') }}" method="POST">
                        @csrf
                        <input type="hidden" name="mapping_id" id="mapMappingId">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">ID Producto Lioren</label>
                            <input type="text" name="lioren_product_id" id="mapLiorenId" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Ej: 6211153">
                            <p class="text-xs text-gray-500 mt-1">Ingresa el ID del producto en Lioren</p>
                        </div>
                        <div id="liorenProductsList" class="mb-4 max-h-48 overflow-y-auto border rounded-lg" style="display: none;"></div>
                        <div class="flex gap-3 justify-end">
                            <button type="button" onclick="closeMapModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                                Guardar Mapeo
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function openMapModal(mappingId, productName) {
                    document.getElementById('mapMappingId').value = mappingId;
                    document.getElementById('mapProductName').textContent = productName;
                    document.getElementById('mapModal').style.display = 'flex';
                    loadLiorenProducts();
                }
                function closeMapModal() {
                    document.getElementById('mapModal').style.display = 'none';
                }
                function loadLiorenProducts() {
                    const configId = '{{ $selectedConfig->id ?? "" }}';
                    if (!configId) return;
                    fetch(`{{ route($routePrefix . '.lioren-products') }}?config_id=${configId}`)
                        .then(r => r.json())
                        .then(data => {
                            if (data.success && data.products.length > 0) {
                                const list = document.getElementById('liorenProductsList');
                                list.innerHTML = '<p class="px-3 py-2 text-xs text-gray-500 font-medium bg-gray-50">Productos disponibles en Lioren (clic para seleccionar):</p>';
                                data.products.forEach(p => {
                                    const item = document.createElement('div');
                                    item.className = 'px-3 py-2 hover:bg-blue-50 cursor-pointer border-b text-sm';
                                    item.innerHTML = `<strong>${p.nombre}</strong> <span class="text-gray-500">(Código: ${p.codigo}, ID: ${p.id})</span>`;
                                    item.onclick = () => { document.getElementById('mapLiorenId').value = p.id; };
                                    list.appendChild(item);
                                });
                                list.style.display = 'block';
                            }
                        })
                        .catch(err => console.error('Error loading Lioren products:', err));
                }
                document.getElementById('mapModal').addEventListener('click', function(e) {
                    if (e.target === this) closeMapModal();
                });
            </script>

            @endif

        </div>
    </div>

<script>
// ============================================================
// FILTRO ADMIN (sin paginación)
// ============================================================
function filterTableAdmin() {
    var input = document.getElementById('searchProductsAdmin');
    var filter = input.value.toLowerCase();
    var table = input.closest('.p-6').querySelector('table');
    if (!table) return;
    var rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    for (var i = 0; i < rows.length; i++) {
        var productName = rows[i].cells[0] ? rows[i].cells[0].textContent.toLowerCase() : '';
        var sku = rows[i].cells[1] ? rows[i].cells[1].textContent.toLowerCase() : '';
        if (productName.indexOf(filter) > -1 || sku.indexOf(filter) > -1) {
            rows[i].style.display = '';
        } else {
            rows[i].style.display = 'none';
        }
    }
}

// ============================================================
// CLIENTE: paginacion y busqueda ahora son server-side (Laravel paginate + form GET).
// El JS de filtrado/paginacion client-side fue removido porque ocultaba filas.
// Las tarjetas-stat siguen funcionando como links de filtro via href.
// ============================================================
</script>

<style>
.client-filter-btn {
    transition: all 0.2s ease;
}
.client-filter-btn.active,
.client-filter-btn[class*="bg-brand-600"] {
    /* Active state handled by JS */
}
</style>

</x-app-layout>
