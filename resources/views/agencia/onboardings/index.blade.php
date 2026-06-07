<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header naranja BigStudio --}}
            <div class="bs-card overflow-hidden mb-6">
                <div class="px-6 py-5 flex items-center justify-between flex-wrap gap-3"
                     style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                    <div>
                        <h2 class="bs-display text-2xl text-white m-0 leading-tight">Onboardings</h2>
                        <p class="text-sm text-white/90 mt-1 mb-0">Portales de inicio para cada cliente nuevo</p>
                    </div>
                    <a href="{{ route("agencia.onboardings.plantillas.index") }}" class="bg-white/20 text-white font-semibold px-4 py-2.5 rounded-lg hover:bg-white/30 transition mr-2">Plantillas</a>
                        <a href="{{ route("agencia.onboardings.create") }}"
                       class="bg-white text-orange-600 font-semibold px-5 py-2.5 rounded-lg hover:bg-orange-50 transition">
                        + Nuevo onboarding
                    </a>
                </div>
            </div>

            {{-- Contadores --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bs-card p-5">
                    <div class="text-sm text-gray-500 uppercase font-semibold">No iniciado</div>
                    <div class="text-3xl font-bold text-yellow-600 mt-1">{{ $contadores["no_iniciado"] }}</div>
                </div>
                <div class="bs-card p-5">
                    <div class="text-sm text-gray-500 uppercase font-semibold">En progreso</div>
                    <div class="text-3xl font-bold text-orange-600 mt-1">{{ $contadores["en_progreso"] }}</div>
                </div>
                <div class="bs-card p-5">
                    <div class="text-sm text-gray-500 uppercase font-semibold">Completado</div>
                    <div class="text-3xl font-bold text-green-600 mt-1">{{ $contadores["completado"] }}</div>
                </div>
            </div>

            {{-- Tabla --}}
            <div class="bs-card overflow-hidden">
                @if(session("success"))
                    <div class="p-4 bg-green-50 text-green-800 border-b border-green-100">{{ session("success") }}</div>
                @endif

                @if($proyectos->isEmpty())
                    <div class="p-10 text-center text-gray-500">
                        Aun no hay onboardings creados. Crea el primero para arrancar.
                    </div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Cliente</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Plantilla</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Estado</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Avance</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Creado</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        @foreach($proyectos as $p)
                            <tr class="hover:bg-orange-50/30">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-gray-800">{{ $p->cliente->nombre ?? "—" }}</div>
                                    <div class="text-xs text-gray-500">{{ $p->titulo }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $p->plantilla->nombre ?? "—" }}</td>
                                <td class="px-4 py-3">
                                    @php $colors = ["no_iniciado"=>"yellow","en_progreso"=>"orange","completado"=>"green","archivado"=>"gray"]; @endphp
                                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-{{ $colors[$p->estado] ?? "gray" }}-100 text-{{ $colors[$p->estado] ?? "gray" }}-700">
                                        {{ str_replace("_", " ", $p->estado) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-24 bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full bg-orange-500" style="width: {{ $p->porcentaje_avance }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-600">{{ $p->porcentaje_avance }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $p->created_at->format("d/m/Y") }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route("agencia.onboardings.show", $p) }}" class="text-orange-600 hover:text-orange-800 text-sm font-semibold">Ver</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <div class="px-4 py-3">{{ $proyectos->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
