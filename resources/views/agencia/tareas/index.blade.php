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
                    <h2 class="bs-display text-2xl text-white m-0 leading-tight">Tareas</h2>
                    <p class="text-sm text-white/90 mt-1 mb-0">Gestiona las tareas por cliente y compártelas con tu equipo</p>
                </div>
                <button onclick="document.getElementById('formNuevaTarea').classList.toggle('hidden')" class="bs-btn-neutral shrink-0">
                    <i class="fas fa-plus"></i> Nueva Tarea
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ $errors->first() }}</div>
        @endif

        {{-- Resumen por estado --}}
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
            @foreach(['borrador','pendiente','en_curso','en_revision','requiere_cambios','terminado'] as $e)
                <div class="bs-card p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide m-0">{{ $estadoMeta[$e][0] }}</p>
                    <p class="bs-display text-2xl mt-1 mb-0" style="color:{{ $estadoMeta[$e][1] }};">{{ $resumen[$e] ?? 0 }}</p>
                </div>
            @endforeach
        </div>

        {{-- Formulario nueva tarea --}}
        <div id="formNuevaTarea" class="bs-card p-6 mb-6 hidden">
            <h3 class="font-semibold text-gray-800 mb-4">Crear nueva tarea</h3>
            <form action="{{ route('agencia.tareas.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Cliente *</label>
                        <select name="agencia_cliente_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Selecciona cliente...</option>
                            @foreach($clientes as $c)
                                <option value="{{ $c->id }}" {{ request('cliente_id') == $c->id ? 'selected' : '' }}>{{ $c->nombre_proyecto }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Título *</label>
                        <input type="text" name="titulo" required maxlength="180" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ej: Diseñar banner home">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-500 block mb-1">Descripción</label>
                        <textarea name="descripcion" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Detalle de la tarea..."></textarea>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Estado</label>
                        <select name="estado" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="pendiente">Pendiente</option>
                            <option value="borrador">Borrador</option>
                            <option value="en_curso">En curso</option>
                            <option value="en_revision">En revisión</option>
                            <option value="requiere_cambios">Requiere cambios</option>
                            <option value="terminado">Terminado</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Prioridad</label>
                        <select name="prioridad" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="media">Media</option>
                            <option value="baja">Baja</option>
                            <option value="alta">Alta</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Fecha límite</label>
                        <input type="date" name="fecha_limite" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="bg-brand-600 text-white px-6 py-2 rounded-lg text-sm font-semibold hover:bg-brand-700">Crear Tarea</button>
                </div>
            </form>
        </div>

        {{-- Filtros --}}
        <div class="bs-card p-4 mb-6">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[220px]">
                    <label class="text-xs text-gray-500 block mb-1">Buscar</label>
                    <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Cliente, proyecto o tarea..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="min-w-[200px]">
                    <label class="text-xs text-gray-500 block mb-1">Cliente</label>
                    <select name="cliente_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Todos</option>
                        @foreach($clientes as $c)
                            <option value="{{ $c->id }}" {{ request('cliente_id') == $c->id ? 'selected' : '' }}>{{ $c->nombre_proyecto }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Estado</label>
                    <select name="estado" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Todos</option>
                        @foreach(['borrador','pendiente','en_curso','en_revision','requiere_cambios','terminado'] as $e)
                            <option value="{{ $e }}" {{ request('estado') === $e ? 'selected' : '' }}>{{ $estadoMeta[$e][0] }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-brand-700">Filtrar</button>
                @if(request('cliente_id') || request('estado') || request('buscar'))
                    <a href="{{ route('agencia.tareas') }}" class="text-sm text-gray-500 px-3 py-2">Limpiar</a>
                @endif
            </form>
        </div>

        {{-- Tabla de tareas --}}
        <div class="bs-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-900 text-white">
                            <th class="px-4 py-3 text-left text-xs uppercase tracking-wide">Cliente</th>
                            <th class="px-4 py-3 text-left text-xs uppercase tracking-wide">Tarea</th>
                            <th class="px-4 py-3 text-left text-xs uppercase tracking-wide">Estado</th>
                            <th class="px-4 py-3 text-left text-xs uppercase tracking-wide">Compartida</th>
                            <th class="px-4 py-3 text-left text-xs uppercase tracking-wide">Límite</th>
                            <th class="px-4 py-3 text-center text-xs uppercase tracking-wide">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tareas as $tarea)
                            @php $m = $estadoMeta[$tarea->estado] ?? ['?','#6B7280','#F3F4F6']; @endphp
                            <tr class="border-b border-gray-100">
                                <td class="px-4 py-3">
                                    @if($tarea->cliente)
                                        <a href="{{ route('agencia.clientes.detalle', $tarea->cliente) }}" class="text-brand-600 hover:underline">{{ $tarea->cliente->nombre_proyecto }}</a>
                                    @else — @endif
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-gray-800 m-0">{{ $tarea->titulo }}</p>
                                    @if($tarea->descripcion)<p class="text-xs text-gray-500 mt-0.5 mb-0">{{ \Illuminate\Support\Str::limit($tarea->descripcion, 80) }}</p>@endif
                                    <span class="text-[10px] uppercase tracking-wide" style="color:{{ $tarea->prioridad==='alta'?'#DC2626':($tarea->prioridad==='baja'?'#9CA3AF':'#D97706') }};">● {{ $tarea->prioridad }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <form action="{{ route('agencia.tareas.estado', $tarea) }}" method="POST" class="inline">
                                        @csrf @method('PATCH')
                                        <select name="estado" onchange="this.form.submit()" class="text-xs font-semibold rounded-full px-3 py-1 border-0" style="background:{{ $m[2] }}; color:{{ $m[1] }};">
                                            @foreach(['borrador','pendiente','en_curso','en_revision','requiere_cambios','terminado'] as $e)
                                                <option value="{{ $e }}" {{ $tarea->estado===$e?'selected':'' }}>{{ $estadoMeta[$e][0] }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="px-4 py-3">
                                    @if($tarea->comparticiones->count())
                                        <span class="text-xs text-gray-600" title="{{ $tarea->comparticiones->pluck('email')->join(', ') }}"><i class="fas fa-user-check text-green-600"></i> {{ $tarea->comparticiones->count() }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600">{{ $tarea->fecha_limite ? $tarea->fecha_limite->format('d/m/Y') : '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button onclick='abrirCompartir({{ $tarea->id }}, @json($tarea->titulo))' class="px-2 py-1 rounded bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs font-medium" title="Compartir"><i class="fas fa-share"></i></button>
                                        <form action="{{ route('agencia.tareas.delete', $tarea) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta tarea?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="px-2 py-1 rounded bg-red-50 hover:bg-red-100 text-red-600 text-xs font-medium" title="Eliminar"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">No hay tareas. Crea la primera con "Nueva Tarea".</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($tareas->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $tareas->links() }}</div>
            @endif
        </div>
    </div>
</div>

{{-- Modal compartir --}}
<div id="modalCompartir" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4" style="display:none;">
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
        var f = document.getElementById('formCompartir');
        f.action = '{{ url('agencia/tareas') }}/' + id + '/compartir';
        document.getElementById('compartirTitulo').textContent = titulo;
        var m = document.getElementById('modalCompartir');
        m.style.display = 'flex';
    }
    function cerrarCompartir() {
        document.getElementById('modalCompartir').style.display = 'none';
    }
</script>
</x-app-layout>
