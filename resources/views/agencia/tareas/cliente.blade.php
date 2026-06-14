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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="bs-card overflow-hidden mb-6">
            <div class="px-6 py-5 flex items-center justify-between flex-wrap gap-3" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                <div>
                    <h2 class="bs-display text-2xl text-white m-0 leading-tight">Tareas — Por cliente</h2>
                    <p class="text-sm text-white/90 mt-1 mb-0">Todas las tareas agrupadas por cliente</p>
                </div>
                <a href="{{ route('agencia.tareas') }}" class="bs-btn-neutral shrink-0"><i class="fas fa-plus"></i> Nueva Tarea</a>
            </div>
        </div>

        @include('agencia.tareas._nav')

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
        @endif

        {{-- Filtro búsqueda --}}
        <div class="bs-card p-4 mb-6">
            <form method="GET" action="{{ route('agencia.tareas.cliente') }}" class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[220px]">
                    <label class="text-xs text-gray-500 block mb-1">Buscar</label>
                    <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Cliente, proyecto o tarea..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-brand-700">Filtrar</button>
                @if(request('buscar'))
                    <a href="{{ route('agencia.tareas.cliente') }}" class="text-sm text-gray-500 px-3 py-2">Limpiar</a>
                @endif
            </form>
        </div>

        @forelse($porCliente as $nombreCliente => $items)
            <div class="bs-card overflow-hidden mb-4">
                <div class="px-5 py-3 bg-gray-900 text-white flex items-center justify-between">
                    <span class="font-semibold">{{ $nombreCliente }}</span>
                    <span class="text-xs bg-white/15 rounded-full px-2 py-0.5">{{ $items->count() }} {{ \Illuminate\Support\Str::plural('tarea', $items->count()) }}</span>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($items as $t)
                        @php $m = $estadoMeta[$t->estado] ?? ['?','#6B7280','#F3F4F6']; @endphp
                        <div class="px-5 py-3 flex items-center gap-3 flex-wrap">
                            <span class="text-xs font-semibold rounded-full px-3 py-1 shrink-0" style="background:{{ $m[2] }}; color:{{ $m[1] }};">{{ $m[0] }}</span>
                            <div class="flex-1 min-w-[200px]">
                                <p class="font-semibold text-sm text-gray-800 m-0">{{ $t->titulo }}</p>
                                @if($t->descripcion)<p class="text-xs text-gray-500 mt-0.5 mb-0">{{ \Illuminate\Support\Str::limit($t->descripcion, 90) }}</p>@endif
                            </div>
                            <span class="text-[11px] uppercase tracking-wide shrink-0" style="color:{{ $t->prioridad==='alta'?'#DC2626':($t->prioridad==='baja'?'#9CA3AF':'#D97706') }};">● {{ $t->prioridad }}</span>
                            <span class="text-xs text-gray-500 shrink-0 w-20 text-right">
                                @if($t->fecha_limite)<i class="far fa-calendar"></i> {{ $t->fecha_limite->format('d/m/Y') }}@else <span class="text-gray-300">—</span>@endif
                            </span>
                            <div class="flex items-center gap-2 shrink-0">
                                @if($t->comparticiones->count())
                                    <span class="text-[11px] text-gray-500" title="{{ $t->comparticiones->pluck('email')->join(', ') }}"><i class="fas fa-user-check text-green-600"></i> {{ $t->comparticiones->count() }}</span>
                                @endif
                                <button onclick='abrirCompartir({{ $t->id }}, @json($t->titulo))' class="text-amber-600 hover:text-amber-700 text-xs" title="Compartir"><i class="fas fa-share"></i></button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="bs-card p-10 text-center text-gray-400">No hay tareas todavía.</div>
        @endforelse
    </div>
</div>

{{-- Modal compartir --}}
<div id="modalCompartir" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none;">
    <div class="bg-white rounded-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800 m-0">Compartir tarea</h3>
            <button onclick="cerrarCompartir()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-sm text-gray-500 mb-4" id="compartirTitulo"></p>
        <form id="formCompartir" method="POST">
            @csrf
            @if($colaboradores->count())
                <label class="text-xs text-gray-500 block mb-2">Colaboradores</label>
                <div class="space-y-2 mb-4 max-h-40 overflow-y-auto">
                    @foreach($colaboradores as $col)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="emails[]" value="{{ $col->email }}" class="rounded">
                            <span>{{ $col->name }} <span class="text-gray-400">({{ $col->email }})</span></span>
                        </label>
                    @endforeach
                </div>
            @endif
            <label class="text-xs text-gray-500 block mb-1">Otro correo (opcional)</label>
            <input type="email" name="emails[]" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-4" placeholder="diseñador@correo.com">
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="cerrarCompartir()" class="px-4 py-2 text-sm text-gray-600">Cancelar</button>
                <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-brand-700">Compartir y enviar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirCompartir(id, titulo) {
        const f = document.getElementById('formCompartir');
        f.action = '{{ url('agencia/tareas') }}/' + id + '/compartir';
        document.getElementById('compartirTitulo').textContent = titulo;
        document.getElementById('modalCompartir').style.display = 'flex';
    }
    function cerrarCompartir() {
        document.getElementById('modalCompartir').style.display = 'none';
    }
</script>
</x-app-layout>
