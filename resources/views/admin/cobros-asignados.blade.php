<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Cobros Asignados a Clientes
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Formulario para crear nuevo cobro --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem; color: #1f2937;">
                        <i class="fas fa-plus-circle" style="color: #10B981; margin-right: 0.5rem;"></i>
                        Asignar Nuevo Cobro
                    </h3>

                    <form action="{{ route('admin.cobros-asignados.store') }}" method="POST">
                        @csrf
                        <div style="display: grid; grid-template-columns: 1fr 2fr 1fr auto; gap: 1rem; align-items: end;">
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Cliente</label>
                                <select name="cliente_id" required style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;">
                                    <option value="">Seleccionar cliente...</option>
                                    @foreach($clientes as $cliente)
                                        <option value="{{ $cliente->id }}">{{ $cliente->name }} ({{ $cliente->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Concepto / Descripción</label>
                                <input type="text" name="concepto" required placeholder="Ej: Desarrollo personalizado, Servicio adicional..." style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Monto (CLP)</label>
                                <input type="number" name="monto" required min="350" placeholder="350" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem;">
                                <small style="color: #6b7280; font-size: 0.75rem;">Mínimo: $350 CLP</small>
                            </div>
                            <div>
                                <button type="submit" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 100%); color: #000; font-weight: 700; padding: 0.5rem 1.5rem; border-radius: 0.375rem; border: none; cursor: pointer; font-size: 0.875rem; white-space: nowrap;">
                                    <i class="fas fa-paper-plane"></i> Asignar Cobro
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Lista de cobros --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem; color: #1f2937;">
                        <i class="fas fa-list" style="color: #FF8100; margin-right: 0.5rem;"></i>
                        Historial de Cobros Asignados
                    </h3>

                    <div class="overflow-x-auto">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                                    <th style="padding: 0.75rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase;">Fecha</th>
                                    <th style="padding: 0.75rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase;">Cliente</th>
                                    <th style="padding: 0.75rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase;">Concepto</th>
                                    <th style="padding: 0.75rem; text-align: right; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase;">Monto</th>
                                    <th style="padding: 0.75rem; text-align: center; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase;">Estado</th>
                                    <th style="padding: 0.75rem; text-align: center; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cobros as $cobro)
                                    <tr style="border-bottom: 1px solid #e5e7eb;">
                                        <td style="padding: 0.75rem; font-size: 0.875rem;">{{ $cobro->created_at->format('d/m/Y H:i') }}</td>
                                        <td style="padding: 0.75rem; font-size: 0.875rem;">
                                            <strong>{{ $cobro->cliente->name ?? 'N/A' }}</strong>
                                            <br><span style="color: #6b7280; font-size: 0.75rem;">{{ $cobro->cliente->email ?? '' }}</span>
                                        </td>
                                        <td style="padding: 0.75rem; font-size: 0.875rem;">{{ $cobro->concepto }}</td>
                                        <td style="padding: 0.75rem; font-size: 0.875rem; text-align: right; font-weight: 700;">
                                            ${{ number_format($cobro->monto, 0, ',', '.') }} CLP
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center;">
                                            @if($cobro->estado === 'pagado')
                                                <span style="background: #dcfce7; color: #166534; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Pagado</span>
                                            @elseif($cobro->estado === 'anulado')
                                                <span style="background: #fee2e2; color: #991b1b; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Anulado</span>
                                            @else
                                                <span style="background: #fef3c7; color: #92400e; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Pendiente</span>
                                            @endif
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center;">
                                            @if($cobro->estado === 'pendiente')
                                                <form action="{{ route('admin.cobros-asignados.anular', $cobro) }}" method="POST" style="display: inline;" onsubmit="return confirm('¿Anular este cobro?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" style="color: #ef4444; background: none; border: none; cursor: pointer; font-size: 0.8rem; font-weight: 600;">
                                                        <i class="fas fa-times"></i> Anular
                                                    </button>
                                                </form>
                                            @elseif($cobro->estado === 'pagado' && $cobro->pagado_at)
                                                <span style="color: #6b7280; font-size: 0.75rem;">Pagado el {{ $cobro->pagado_at->format('d/m/Y') }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="padding: 2rem; text-align: center; color: #6b7280;">
                                            No hay cobros asignados aún.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($cobros->hasPages())
                        <div style="margin-top: 1rem;">
                            {{ $cobros->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
