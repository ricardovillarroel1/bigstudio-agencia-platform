<x-app-layout>
@php
    $estadoMeta = [
        'borrador'         => ['Borrador', '#6B7280', '#F3F4F6'],
        'pendiente'        => ['Pendiente', '#D97706', '#FEF3C7'],
        'en_curso'         => ['En curso', '#2563EB', '#DBEAFE'],
        'en_revision'      => ['En revisión', '#7C3AED', '#EDE9FE'],
        'requiere_cambios' => ['Requiere cambios', '#DC2626', '#FEE2E2'],
        'terminado'        => ['Terminado', '#059669', '#D1FAE5'],
    ];
@endphp
<div class="py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="bs-card overflow-hidden mb-6">
            <div class="px-6 py-5" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                <h2 class="bs-display text-2xl text-white m-0 leading-tight">{{ $panelCompleto ? 'Todas las tareas' : 'Mis tareas' }}</h2>
                <p class="text-sm text-white/90 mt-1 mb-0">{{ $panelCompleto ? 'Panel completo de tareas de todos los clientes' : 'Tareas que se te han compartido' }}</p>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
        @endif

        {{-- Filtro por estado --}}
        <div class="bs-card p-4 mb-6">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Estado</label>
                    <select name="estado" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Todos</option>
                        @foreach(['borrador','pendiente','en_curso','en_revision','requiere_cambios','terminado'] as $e)
                            <option value="{{ $e }}" {{ request('estado') === $e ? 'selected' : '' }}>{{ $estadoMeta[$e][0] }}</option>
                        @endforeach
                    </select>
                </div>
                @if(request('estado'))<a href="{{ route('agencia.mis-tareas') }}" class="text-sm text-gray-500 px-3 py-2">Limpiar</a>@endif
            </form>
        </div>

        <div class="bs-card overflow-hidden">
            @forelse($tareas as $tarea)
                @php $m = $estadoMeta[$tarea->estado] ?? ['?','#6B7280','#F3F4F6']; @endphp
                <div class="px-5 py-4 border-b border-gray-50">
                    <div class="flex items-start justify-between gap-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full" style="background:{{ $m[2] }}; color:{{ $m[1] }};">{{ $m[0] }}</span>
                            @if($panelCompleto)<span class="text-xs text-gray-400">{{ $tarea->cliente->nombre_proyecto ?? '' }}</span>@endif
                        </div>
                        <p class="font-semibold text-gray-800 mt-2 mb-0">{{ $tarea->titulo }}</p>
                        @if($tarea->descripcion)<p class="text-xs text-gray-500 mt-1 mb-0">{{ $tarea->descripcion }}</p>@endif
                        @if($tarea->fecha_limite)<p class="text-[11px] text-gray-500 mt-2 mb-0"><i class="far fa-calendar"></i> Límite: {{ $tarea->fecha_limite->format('d/m/Y') }}</p>@endif
                    </div>
                    <form action="{{ route('agencia.mis-tareas.estado', $tarea) }}" method="POST" class="shrink-0">
                        @csrf @method('PATCH')
                        <label class="text-[10px] text-gray-400 block mb-1">Cambiar estado</label>
                        <select name="estado" onchange="this.form.submit()" class="text-xs font-semibold rounded-lg px-3 py-1.5 border border-gray-200">
                            @foreach(['borrador','pendiente','en_curso','en_revision','requiere_cambios','terminado'] as $e)
                                <option value="{{ $e }}" {{ $tarea->estado===$e?'selected':'' }}>{{ $estadoMeta[$e][0] }}</option>
                            @endforeach
                        </select>
                    </form>
                    </div>{{-- /flex row --}}
                    <button type="button" onclick="document.getElementById('conv-{{ $tarea->id }}').classList.toggle('hidden')" class="mt-3 text-xs text-brand-600 font-medium hover:underline">
                        <i class="fas fa-comments"></i> Conversación
                        @if($tarea->comentarios->count())<span class="bg-orange-100 text-brand-700 rounded-full px-1.5">{{ $tarea->comentarios->count() }}</span>@endif
                        @if($tarea->archivos->count()) · <i class="fas fa-paperclip"></i> {{ $tarea->archivos->count() }}@endif
                    </button>
                    <div id="conv-{{ $tarea->id }}" class="hidden">
                        @include('agencia.tareas._conversacion', ['tarea' => $tarea, 'modo' => 'colaborador'])
                    </div>
                </div>{{-- /card --}}
            @empty
                <div class="px-5 py-10 text-center text-gray-400 text-sm">No tienes tareas asignadas por ahora.</div>
            @endforelse
            @if($tareas->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $tareas->links() }}</div>
            @endif
        </div>
    </div>
</div>
</x-app-layout>
