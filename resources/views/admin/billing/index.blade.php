<x-app-layout>

    <x-slot name="header">
        <h2 class="font-display text-xl font-bold text-gray-800 leading-tight">
            <span class="text-brand-600">Facturaci&oacute;n</span> y Documentos
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Resumen general --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bs-card bs-card-body text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Total Clientes</p>
                    <p class="bs-display text-3xl text-gray-900 mt-1">{{ $clientes->count() }}</p>
                </div>
                <div class="bs-card bs-card-body text-center"
                     style="background: linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%); border-color: #FFD89C;">
                    <p class="text-xs uppercase tracking-wide font-semibold" style="color: #B85B00;">Con Documentos Extra</p>
                    <p class="bs-display text-3xl mt-1" style="color: #B85B00;">{{ $clientes->where('docs_extra', '>', 0)->count() }}</p>
                </div>
                <div class="bs-card bs-card-body text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Servicios Pausados</p>
                    <p class="bs-display text-3xl text-red-600 mt-1">{{ $clientes->where('pausada', true)->count() }}</p>
                </div>
                <div class="bs-card bs-card-body text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Facturas Pendientes</p>
                    <p class="bs-display text-3xl text-amber-700 mt-1">{{ $clientes->sum('facturas_pendientes') }}</p>
                </div>
            </div>

            {{-- Tabla de clientes --}}
            <div class="bs-card overflow-hidden">
                <div class="bs-card-header">
                    <h3 class="font-bold text-gray-800 m-0">Uso de Documentos por Cliente</h3>
                    <span class="text-xs text-gray-500">{{ $clientes->count() }} cliente{{ $clientes->count() === 1 ? '' : 's' }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="bs-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Plan</th>
                                <th class="text-center">Emitidos</th>
                                <th class="text-center">L&iacute;mite</th>
                                <th class="text-center">Extra</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Fact. Pend.</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clientes as $cliente)
                            <tr>
                                <td>
                                    <p class="font-semibold text-gray-900 m-0">{{ $cliente['name'] }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5 m-0">{{ $cliente['email'] }}</p>
                                </td>
                                <td class="text-gray-700">{{ $cliente['plan_nombre'] }}</td>
                                <td class="text-center font-semibold">{{ $cliente['docs_emitidos'] }}</td>
                                <td class="text-center">{{ $cliente['limite_incluido'] > 0 ? $cliente['limite_incluido'] : '∞' }}</td>
                                <td class="text-center font-bold {{ $cliente['docs_extra'] > 0 ? 'text-brand-600' : 'text-gray-400' }}">
                                    {{ $cliente['docs_extra'] > 0 ? $cliente['docs_extra'] : '—' }}
                                </td>
                                <td class="text-center">
                                    @if($cliente['pausada'])
                                        <span class="bs-badge-danger">Pausado</span>
                                    @elseif($cliente['suscripcion'])
                                        <span class="bs-badge-success">Activo</span>
                                    @else
                                        <span class="bs-badge-neutral">Sin plan</span>
                                    @endif
                                </td>
                                <td class="text-center font-bold {{ $cliente['facturas_pendientes'] > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                    {{ $cliente['facturas_pendientes'] > 0 ? $cliente['facturas_pendientes'] : '—' }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.billing.show', $cliente['id']) }}"
                                       class="bs-btn-primary bs-btn-sm">
                                        Ver Detalle
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
