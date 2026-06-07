<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="bs-card overflow-hidden mb-6">
                <div class="px-6 py-5 flex items-center justify-between flex-wrap gap-3"
                     style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                    <div>
                        <h2 class="bs-display text-2xl text-white m-0 leading-tight">Plantillas de Onboarding</h2>
                        <p class="text-sm text-white/90 mt-1 mb-0">Define los onboardings por tipo de servicio</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('agencia.onboardings.index') }}"
                           class="bg-white/20 text-white font-semibold px-4 py-2.5 rounded-lg hover:bg-white/30 transition">
                            ← Onboardings
                        </a>
                        <a href="{{ route('agencia.onboardings.plantillas.create') }}"
                           class="bg-white text-orange-600 font-semibold px-5 py-2.5 rounded-lg hover:bg-orange-50 transition">
                            + Nueva plantilla
                        </a>
                    </div>
                </div>
            </div>

            <div class="bs-card overflow-hidden">
                @if(session('success'))
                    <div class="p-4 bg-green-50 text-green-800 border-b border-green-100">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="p-4 bg-red-50 text-red-800 border-b border-red-100">
                        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                    </div>
                @endif

                @if($plantillas->isEmpty())
                    <div class="p-10 text-center text-gray-500">
                        No hay plantillas. Crea la primera para poder generar onboardings.
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Plantilla</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tipo</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Secciones</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Días</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Uso</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Estado</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        @foreach($plantillas as $p)
                            <tr class="hover:bg-orange-50/30">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-gray-800">{{ $p->nombre }}</div>
                                    <div class="text-xs text-gray-500">{{ $p->slug }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ str_replace('_', ' ', $p->tipo_servicio) }}</td>
                                <td class="px-4 py-3 text-sm">{{ count($p->secciones ?? []) }}</td>
                                <td class="px-4 py-3 text-sm">{{ $p->dias_habiles_estimados }}</td>
                                <td class="px-4 py-3 text-sm">{{ $p->proyectos_count }} proyecto(s)</td>
                                <td class="px-4 py-3 text-center">
                                    <form method="POST" action="{{ route('agencia.onboardings.plantillas.toggle', $p) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="px-3 py-1 text-xs font-semibold rounded-full {{ $p->activo ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }}">
                                            {{ $p->activo ? 'Activa' : 'Inactiva' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('agencia.onboardings.plantillas.edit', $p) }}" class="text-orange-600 hover:text-orange-800 text-sm font-semibold mr-3">Editar</a>
                                    @if($p->proyectos_count === 0)
                                        <form method="POST" action="{{ route('agencia.onboardings.plantillas.destroy', $p) }}" class="inline"
                                              onsubmit="return confirm('¿Eliminar plantilla {{ $p->nombre }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-semibold">Eliminar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <div class="px-4 py-3">{{ $plantillas->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
