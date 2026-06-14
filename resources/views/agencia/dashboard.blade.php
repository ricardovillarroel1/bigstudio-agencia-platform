<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-bold text-gray-800 leading-tight">
            <span class="text-brand-600">Dashboard</span> Agencia
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Hero -->
            <div class="bs-card overflow-hidden">
                <div class="px-8 py-7" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                    <h1 class="bs-display text-2xl text-white m-0 leading-tight">Servicios de Agencia</h1>
                    <p class="text-sm text-white/90 mt-1 mb-0">Gestión de clientes y servicios de Big Studio</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                <div class="bs-card p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide m-0">Clientes Activos</p>
                    <p class="bs-display text-2xl text-gray-900 mt-1 mb-0">{{ $totalClientes }}</p>
                </div>
                <div class="bs-card p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide m-0">Servicios</p>
                    <p class="bs-display text-2xl text-gray-900 mt-1 mb-0">{{ $totalServicios }}</p>
                </div>
                <div class="bs-card p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide m-0">Suscripciones Activas</p>
                    <p class="bs-display text-2xl mt-1 mb-0" style="color:#10B981;">{{ $suscripcionesActivas }}</p>
                </div>
                <div class="bs-card p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide m-0">Cobros Pendientes</p>
                    <p class="bs-display text-2xl mt-1 mb-0" style="color:#D97706;">{{ $cobrosPendientes }}</p>
                </div>
                <a href="{{ route('agencia.tareas') }}" class="bs-card p-5 block hover:shadow-md transition">
                    <p class="text-xs text-gray-500 uppercase tracking-wide m-0">Tareas Pendientes</p>
                    <p class="bs-display text-2xl mt-1 mb-0" style="color:#2563EB;">{{ $tareasPendientes }}</p>
                </a>
                <div class="bs-card p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide m-0">Ingresos Mes</p>
                    <p class="bs-display text-2xl text-brand-600 mt-1 mb-0">${{ number_format($ingresosMes, 0, ',', '.') }}</p>
                </div>
            </div>

            <!-- Date Filter -->
            <div class="bs-card p-4">
                <form method="GET" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="bs-label">Periodo</label>
                        <select name="periodo" class="bs-input" onchange="toggleDashFiltro(this.value)">
                            <option value="mes" {{ request('periodo', 'mes') === 'mes' ? 'selected' : '' }}>Por Mes</option>
                            <option value="dia" {{ request('periodo') === 'dia' ? 'selected' : '' }}>Por Día</option>
                            <option value="anio" {{ request('periodo') === 'anio' ? 'selected' : '' }}>Por Año</option>
                        </select>
                    </div>
                    <div id="dashFiltroDia" style="{{ request('periodo') === 'dia' ? '' : 'display:none' }}">
                        <label class="bs-label">Fecha</label>
                        <input type="date" name="fecha" value="{{ request('fecha', date('Y-m-d')) }}" class="bs-input">
                    </div>
                    <div id="dashFiltroMes" style="{{ request('periodo', 'mes') === 'mes' ? '' : 'display:none' }}">
                        <label class="bs-label">Mes</label>
                        <input type="month" name="mes" value="{{ request('mes', date('Y-m')) }}" class="bs-input">
                    </div>
                    <div id="dashFiltroAnio" style="{{ request('periodo') === 'anio' ? '' : 'display:none' }}">
                        <label class="bs-label">Año</label>
                        <select name="anio" class="bs-input">
                            @for($y = date('Y'); $y >= 2024; $y--)
                                <option value="{{ $y }}" {{ request('anio', date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <button type="submit" class="bs-btn-primary">Aplicar Filtro</button>
                </form>
            </div>

            <!-- Period Summary -->
            <div class="bs-card p-5" style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%);">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <p class="text-xs text-brand-600 uppercase font-semibold tracking-wide m-0">Resumen: {{ $labelPeriodo ?? 'Este Mes' }}</p>
                        <p class="text-sm text-gray-600 mt-1 mb-0">{{ $cobrosDelPeriodo ?? 0 }} cobro(s) pagado(s) en este periodo</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500 m-0">Total Ingresos</p>
                        <p class="bs-display text-3xl text-brand-600 m-0">${{ number_format($ingresosFiltro ?? 0, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <!-- Proximos Cobros -->
                <div class="bs-card overflow-hidden">
                    <div class="bs-card-header">
                        <h3 class="bs-display text-lg text-gray-800 m-0 flex items-center gap-2">
                            <i class="fas fa-clock" style="color:#D97706;"></i> Próximos Cobros (7 días)
                        </h3>
                    </div>
                    <div class="bs-card-body">
                        @forelse($proximosCobros as $sub)
                            <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-50' : '' }}">
                                <div>
                                    <p class="font-medium text-gray-800 m-0">{{ $sub->cliente->nombre }}</p>
                                    <p class="text-sm text-gray-500 m-0">{{ $sub->concepto }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-brand-600 m-0">${{ number_format($sub->monto, 0, ',', '.') }}</p>
                                    <p class="text-xs text-gray-400 m-0">{{ $sub->proximo_cobro->format('d/m/Y') }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-400 text-sm py-4 text-center m-0">No hay cobros próximos</p>
                        @endforelse
                    </div>
                </div>

                <!-- Ultimos Cobros (paginated 5) -->
                <div class="bs-card overflow-hidden">
                    <div class="bs-card-header flex items-center justify-between">
                        <h3 class="bs-display text-lg text-gray-800 m-0 flex items-center gap-2">
                            <i class="fas fa-receipt text-gray-500"></i> Últimos Cobros
                        </h3>
                        @if($ultimosCobros->hasPages())
                        <div class="flex items-center gap-2">
                            @if($ultimosCobros->onFirstPage())
                                <span class="text-gray-300 text-sm px-2 py-1 cursor-not-allowed">&larr;</span>
                            @else
                                <a href="{{ $ultimosCobros->previousPageUrl() }}" class="text-brand-600 hover:text-brand-800 text-sm px-2 py-1 bg-brand-50 rounded">&larr;</a>
                            @endif
                            <span class="text-xs text-gray-500">{{ $ultimosCobros->currentPage() }}/{{ $ultimosCobros->lastPage() }}</span>
                            @if($ultimosCobros->hasMorePages())
                                <a href="{{ $ultimosCobros->nextPageUrl() }}" class="text-brand-600 hover:text-brand-800 text-sm px-2 py-1 bg-brand-50 rounded">&rarr;</a>
                            @else
                                <span class="text-gray-300 text-sm px-2 py-1 cursor-not-allowed">&rarr;</span>
                            @endif
                        </div>
                        @endif
                    </div>
                    <div class="bs-card-body">
                        @forelse($ultimosCobros as $cobro)
                            <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-50' : '' }}">
                                <div>
                                    <p class="font-medium text-gray-800 m-0">{{ $cobro->cliente->nombre ?? 'N/A' }}</p>
                                    <p class="text-sm text-gray-500 truncate m-0" style="max-width: 250px;">{{ $cobro->concepto }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold m-0" style="color: {{ $cobro->estado === 'pagado' ? '#10B981' : ($cobro->estado === 'pendiente' ? '#D97706' : '#EF4444') }};">
                                        ${{ number_format($cobro->monto, 0, ',', '.') }}
                                    </p>
                                    @if($cobro->estado === 'pagado')
                                        <span class="bs-badge-success">Pagado</span>
                                    @elseif($cobro->estado === 'pendiente')
                                        <span class="bs-badge-warning">Pendiente</span>
                                    @else
                                        <span class="bs-badge-danger">{{ ucfirst($cobro->estado) }}</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-400 text-sm py-4 text-center m-0">No hay cobros registrados</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ route('agencia.clientes') }}" class="bs-card p-5 hover:shadow-bs-card-hover transition text-center group">
                    <div class="inline-flex w-12 h-12 rounded-xl mb-2 items-center justify-center group-hover:scale-105 transition-transform" style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%);">
                        <i class="fas fa-user-tie text-brand-600"></i>
                    </div>
                    <p class="font-semibold text-gray-700 m-0">Clientes</p>
                </a>
                <a href="{{ route('agencia.servicios') }}" class="bs-card p-5 hover:shadow-bs-card-hover transition text-center group">
                    <div class="inline-flex w-12 h-12 rounded-xl mb-2 items-center justify-center group-hover:scale-105 transition-transform" style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%);">
                        <i class="fas fa-concierge-bell text-brand-600"></i>
                    </div>
                    <p class="font-semibold text-gray-700 m-0">Servicios</p>
                </a>
                <a href="{{ route('agencia.suscripciones') }}" class="bs-card p-5 hover:shadow-bs-card-hover transition text-center group">
                    <div class="inline-flex w-12 h-12 rounded-xl mb-2 items-center justify-center group-hover:scale-105 transition-transform" style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%);">
                        <i class="fas fa-sync-alt text-brand-600"></i>
                    </div>
                    <p class="font-semibold text-gray-700 m-0">Suscripciones</p>
                </a>
                <a href="{{ route('agencia.cobros') }}" class="bs-card p-5 hover:shadow-bs-card-hover transition text-center group">
                    <div class="inline-flex w-12 h-12 rounded-xl mb-2 items-center justify-center group-hover:scale-105 transition-transform" style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%);">
                        <i class="fas fa-hand-holding-usd text-brand-600"></i>
                    </div>
                    <p class="font-semibold text-gray-700 m-0">Cobros</p>
                </a>
            </div>
        </div>
    </div>

    <script>
    function toggleDashFiltro(val) {
        document.getElementById('dashFiltroDia').style.display = val === 'dia' ? '' : 'none';
        document.getElementById('dashFiltroMes').style.display = val === 'mes' ? '' : 'none';
        document.getElementById('dashFiltroAnio').style.display = val === 'anio' ? '' : 'none';
    }
    </script>

            {{-- Widget Onboardings --}}
            <div class="bs-card overflow-hidden">
                <div class="px-6 py-4 flex items-center justify-between" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                    <div>
                        <h2 class="bs-display text-lg text-white m-0 leading-tight">🚀 Onboardings de clientes</h2>
                        <p class="text-xs text-white/90 mt-1 mb-0">Portales activos donde tus clientes te entregan el material</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('agencia.onboardings.index') }}" class="bg-white/20 text-white text-sm font-semibold px-3 py-1.5 rounded-lg hover:bg-white/30">Ver todos</a>
                        <a href="{{ route('agencia.onboardings.create') }}" class="bg-white text-orange-600 text-sm font-semibold px-3 py-1.5 rounded-lg hover:bg-orange-50">+ Nuevo</a>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-px bg-gray-100">
                    <a href="{{ route('agencia.onboardings.index', ['estado' => 'no_iniciado']) }}" class="block bg-white p-4 hover:bg-yellow-50 transition">
                        <div class="text-xs text-gray-500 uppercase font-semibold">No iniciado</div>
                        <div class="text-2xl font-bold text-yellow-600 mt-1">{{ $onboardingsNoIniciados ?? 0 }}</div>
                    </a>
                    <a href="{{ route('agencia.onboardings.index', ['estado' => 'en_progreso']) }}" class="block bg-white p-4 hover:bg-orange-50 transition">
                        <div class="text-xs text-gray-500 uppercase font-semibold">En progreso</div>
                        <div class="text-2xl font-bold text-orange-600 mt-1">{{ $onboardingsEnProgreso ?? 0 }}</div>
                    </a>
                    <a href="{{ route('agencia.onboardings.index', ['estado' => 'completado']) }}" class="block bg-white p-4 hover:bg-green-50 transition">
                        <div class="text-xs text-gray-500 uppercase font-semibold">Completados (30d)</div>
                        <div class="text-2xl font-bold text-green-600 mt-1">{{ $onboardingsCompletados30d ?? 0 }}</div>
                    </a>
                    <a href="{{ route('agencia.onboardings.plantillas.index') }}" class="block bg-white p-4 hover:bg-gray-50 transition">
                        <div class="text-xs text-gray-500 uppercase font-semibold">Plantillas</div>
                        <div class="text-2xl font-bold text-gray-700 mt-1">→ Gestionar</div>
                    </a>
                </div>

                @if(!empty($onboardingsRecientes) && $onboardingsRecientes->count())
                    <div class="p-5">
                        <div class="text-xs text-gray-500 uppercase font-semibold mb-3">Activos recientes</div>
                        <ul class="space-y-2">
                            @foreach($onboardingsRecientes as $o)
                                <li>
                                    <a href="{{ route('agencia.onboardings.show', $o) }}" class="flex items-center justify-between p-3 border border-gray-100 rounded-lg hover:bg-orange-50 transition">
                                        <div class="min-w-0 flex-1">
                                            <div class="font-semibold text-gray-800 truncate">{{ $o->titulo }}</div>
                                            <div class="text-xs text-gray-500">{{ $o->cliente->nombre ?? '—' }} · {{ $o->plantilla->nombre ?? '—' }}</div>
                                        </div>
                                        <div class="flex items-center gap-3 flex-shrink-0">
                                            <div class="w-24 bg-gray-200 rounded-full h-2 hidden sm:block">
                                                <div class="h-2 rounded-full bg-orange-500" style="width: {{ $o->porcentaje_avance }}%"></div>
                                            </div>
                                            <span class="text-sm font-bold text-orange-600">{{ $o->porcentaje_avance }}%</span>
                                            <span class="text-orange-600">→</span>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

</x-app-layout>
