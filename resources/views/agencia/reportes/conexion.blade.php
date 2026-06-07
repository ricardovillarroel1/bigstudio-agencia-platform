<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-bold text-gray-800 leading-tight">
            <span class="text-brand-600">Conectar</span> Cuentas Meta Ads
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
            <div class="rounded-xl px-4 py-3 text-sm" style="background:#ECFDF5; border:1px solid #6EE7B7; color:#065F46;">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
            @endif
            @if($errors->any())
            <div class="rounded-xl px-4 py-3 text-sm" style="background:#FEF2F2; border:1px solid #FCA5A5; color:#991B1B;">
                <i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}
            </div>
            @endif

            {{-- Estado de conexión --}}
            <div class="bs-card overflow-hidden">
                <div class="px-6 py-5" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                    <h3 class="bs-display text-xl text-white m-0">Conexión con Meta Business</h3>
                    <p class="text-sm text-white/90 mt-1 mb-0">Pega el token de tu System User para traer datos reales de tus campañas.</p>
                </div>
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        @if($tokenSet)
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-semibold" style="background:#DCFCE7; color:#15803D;">
                                <span style="width:8px;height:8px;border-radius:50%;background:#22C55E;display:inline-block;"></span> Conectado
                            </span>
                            <span class="text-sm text-gray-500">Token configurado. Las sincronizaciones traerán datos reales de Meta.</span>
                        @else
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-semibold" style="background:#FEF3C7; color:#92400E;">
                                <span style="width:8px;height:8px;border-radius:50%;background:#F59E0B;display:inline-block;"></span> Modo DEMO
                            </span>
                            <span class="text-sm text-gray-500">Sin token aún. Los reportes muestran datos de ejemplo.</span>
                        @endif
                    </div>

                    <form action="{{ route('agencia.reportes.conexion.token') }}" method="POST" class="flex flex-col sm:flex-row gap-3 items-start sm:items-end">
                        @csrf
                        <div class="flex-1 w-full">
                            <label class="bs-label">System User Token {{ $tokenSet ? '(reemplazar)' : '' }}</label>
                            <input type="password" name="meta_system_token" class="bs-input font-mono" placeholder="EAAB... (token largo de Meta)" autocomplete="off">
                            <p class="text-xs text-gray-400 mt-1">Lo generas en tu Business Manager → System Users → Generar token con permiso <code>ads_read</code>.</p>
                        </div>
                        <button type="submit" class="bs-btn-primary shrink-0"><i class="fas fa-key"></i> Guardar token</button>
                    </form>
                </div>
            </div>

            {{-- Vincular nueva cuenta --}}
            <div class="bs-card overflow-hidden">
                <div class="bs-card-header"><h3 class="bs-display text-lg text-gray-800 m-0">Vincular cuenta publicitaria</h3></div>
                <div class="bs-card-body">
                    <form action="{{ route('agencia.reportes.conexion.cuenta') }}" method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                        @csrf
                        <div class="md:col-span-4">
                            <label class="bs-label">Cliente</label>
                            <select name="agencia_cliente_id" class="bs-input">
                                <option value="">— Sin asignar —</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-4">
                            <label class="bs-label">Nombre de la cuenta</label>
                            <input type="text" name="nombre_cuenta" class="bs-input" placeholder="Ej: Botas Militares — Meta" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="bs-label">Ad Account ID</label>
                            <input type="text" name="act_id" class="bs-input font-mono" placeholder="act_123..." required>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="bs-btn-primary w-full"><i class="fas fa-link"></i> Vincular</button>
                        </div>
                    </form>

                    @if(!empty($cuentasMeta))
                    <div class="mt-4 text-xs text-gray-500">
                        <strong>Cuentas detectadas en tu Meta:</strong>
                        @foreach($cuentasMeta as $cm)
                            <span class="inline-block bg-gray-100 rounded-full px-2 py-0.5 m-0.5 font-mono">act_{{ $cm['account_id'] ?? '' }} — {{ $cm['name'] ?? '' }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            {{-- Cuentas vinculadas --}}
            <div class="bs-card overflow-hidden">
                <div class="bs-card-header"><h3 class="bs-display text-lg text-gray-800 m-0">Cuentas vinculadas ({{ $cuentas->count() }})</h3></div>
                <div class="bs-card-body">
                    @if($cuentas->isEmpty())
                        <div class="text-center py-10">
                            <div class="inline-flex w-16 h-16 rounded-2xl mb-3 items-center justify-center" style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%);">
                                <i class="fab fa-facebook text-2xl text-brand-500"></i>
                            </div>
                            <p class="text-gray-500 m-0">Aún no has vinculado cuentas publicitarias.</p>
                        </div>
                    @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm" style="min-width:680px;">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 uppercase border-b border-gray-100">
                                    <th class="py-2 pr-3">Cuenta</th>
                                    <th class="py-2 px-3">Cliente</th>
                                    <th class="py-2 px-3">Ad Account ID</th>
                                    <th class="py-2 px-3">Última sync</th>
                                    <th class="py-2 pl-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($cuentas as $cuenta)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 pr-3 font-medium text-gray-800">{{ $cuenta->nombre_cuenta }}</td>
                                    <td class="py-3 px-3 text-gray-600">{{ $cuenta->cliente->nombre ?? '—' }}</td>
                                    <td class="py-3 px-3 font-mono text-xs text-gray-500">{{ $cuenta->act_id }}</td>
                                    <td class="py-3 px-3 text-gray-500">{{ $cuenta->ultima_sync_at ? $cuenta->ultima_sync_at->format('d/m/Y H:i') : 'Nunca' }}</td>
                                    <td class="py-3 pl-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <form action="{{ route('agencia.reportes.conexion.sincronizar', $cuenta) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-brand-600 hover:text-brand-800 text-xs font-semibold"><i class="fas fa-sync"></i> Sincronizar</button>
                                            </form>
                                            <a href="{{ route('agencia.reportes.meta-demo') }}" class="text-blue-600 hover:text-blue-800 text-xs font-semibold"><i class="fas fa-chart-line"></i> Ver reporte</a>
                                            <form action="{{ route('agencia.reportes.conexion.cuenta.eliminar', $cuenta) }}" method="POST" class="inline" onsubmit="return confirm('¿Desvincular esta cuenta? No se borra nada en Meta.')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-semibold"><i class="fas fa-unlink"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
