<x-app-layout>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Todas las Solicitudes
            </h2>
            <a href="{{ route('admin.solicitudes.pendientes-conexion') }}" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded">
                Ver Pendientes de Conexion
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if($solicitudes->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tienda Shopify</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($solicitudes as $solicitud)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{{ $solicitud->id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $solicitud->cliente->name ?? 'N/A' }}<br>
                                                <span class="text-gray-500 text-xs">{{ $solicitud->cliente->email ?? '' }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $solicitud->plan->nombre ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($solicitud->estado === 'activa')
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Activa</span>
                                                @elseif($solicitud->estado === 'pendiente')
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendiente</span>
                                                @elseif($solicitud->estado === 'en_proceso')
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">En Proceso</span>
                                                @elseif($solicitud->estado === 'rechazada')
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Rechazada</span>
                                                @else
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">{{ $solicitud->estado }}</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $solicitud->tienda_shopify ?? 'Sin configurar' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $solicitud->created_at->format('d/m/Y H:i') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <form method="POST" action="{{ route('admin.solicitudes.updateEstado', $solicitud) }}" class="inline">
                                                    @csrf
                                                    <select name="estado" onchange="this.form.submit()" class="text-sm border-gray-300 rounded-md">
                                                        <option value="pendiente" {{ $solicitud->estado === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                                        <option value="aprobada" {{ $solicitud->estado === 'aprobada' ? 'selected' : '' }}>Aprobada</option>
                                                        <option value="en_proceso" {{ $solicitud->estado === 'en_proceso' ? 'selected' : '' }}>En Proceso</option>
                                                        <option value="activa" {{ $solicitud->estado === 'activa' ? 'selected' : '' }}>Activa</option>
                                                        <option value="rechazada" {{ $solicitud->estado === 'rechazada' ? 'selected' : '' }}>Rechazada</option>
                                                    </select>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-6">
                            {{ $solicitudes->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <p class="text-gray-500">No hay solicitudes registradas</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
