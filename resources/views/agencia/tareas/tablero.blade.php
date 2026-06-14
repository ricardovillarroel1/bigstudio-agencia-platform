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
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

        <div class="bs-card overflow-hidden mb-6">
            <div class="px-6 py-5 flex items-center justify-between flex-wrap gap-3" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                <div>
                    <h2 class="bs-display text-2xl text-white m-0 leading-tight">Tareas — Tablero</h2>
                    <p class="text-sm text-white/90 mt-1 mb-0">Arrastra las tarjetas entre columnas para cambiar su estado</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('agencia.tareas') }}" class="bs-btn-neutral"><i class="fas fa-table"></i> Tabla</a>
                    <button onclick="document.getElementById('formNuevaTarea').classList.toggle('hidden')" class="bs-btn-neutral">
                        <i class="fas fa-plus"></i> Nueva Tarea
                    </button>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ $errors->first() }}</div>
        @endif

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
                            @foreach(['pendiente','borrador','en_curso','en_revision','requiere_cambios','terminado'] as $e)
                                <option value="{{ $e }}">{{ $estadoMeta[$e][0] }}</option>
                            @endforeach
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
            <form method="GET" action="{{ route('agencia.tareas.tablero') }}" class="flex flex-wrap gap-3 items-end">
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
                <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-brand-700">Filtrar</button>
                @if(request('cliente_id') || request('buscar'))
                    <a href="{{ route('agencia.tareas.tablero') }}" class="text-sm text-gray-500 px-3 py-2">Limpiar</a>
                @endif
            </form>
        </div>

        {{-- Tablero Kanban --}}
        <div class="flex gap-4 overflow-x-auto pb-4">
            @foreach(['borrador','pendiente','en_curso','en_revision','requiere_cambios','terminado'] as $e)
                @php $m = $estadoMeta[$e]; @endphp
                <div class="w-80 shrink-0 flex flex-col">
                    <div class="rounded-t-xl px-3 py-2 flex items-center justify-between" style="background:{{ $m[2] }};">
                        <span class="font-semibold text-sm" style="color:{{ $m[1] }};">{{ $m[0] }}</span>
                        <span class="kanban-count text-xs font-bold px-2 py-0.5 rounded-full bg-white/70" data-estado="{{ $e }}" style="color:{{ $m[1] }};">{{ $resumen[$e] ?? 0 }}</span>
                    </div>
                    <div class="kanban-col bg-gray-50 rounded-b-xl p-2 flex-1 min-h-[200px] transition"
                         data-estado="{{ $e }}"
                         ondragover="dragOver(event)" ondragleave="dragLeave(event)" ondrop="dropCard(event)">
                        @foreach($tareasPorEstado[$e] as $t)
                            @php $pc = $t->prioridad==='alta'?'#DC2626':($t->prioridad==='baja'?'#9CA3AF':'#D97706'); @endphp
                            <div class="bs-card p-3 mb-2 kanban-card cursor-move" draggable="true" data-id="{{ $t->id }}" ondragstart="dragStart(event)" ondragend="dragEnd(event)">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="font-semibold text-sm text-gray-800 m-0 leading-snug">{{ $t->titulo }}</p>
                                    <span class="text-xs shrink-0" style="color:{{ $pc }};" title="Prioridad {{ $t->prioridad }}">●</span>
                                </div>
                                @if($t->cliente)
                                    <p class="text-xs text-brand-600 mt-1 mb-0">{{ $t->cliente->nombre_proyecto }}</p>
                                @endif
                                @if($t->descripcion)
                                    <p class="text-xs text-gray-500 mt-1 mb-0">{{ \Illuminate\Support\Str::limit($t->descripcion, 70) }}</p>
                                @endif
                                <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100">
                                    <span class="text-[11px] text-gray-500">
                                        @if($t->fecha_limite)<i class="far fa-calendar"></i> {{ $t->fecha_limite->format('d/m') }}@else <span class="text-gray-300">—</span>@endif
                                    </span>
                                    <div class="flex items-center gap-2">
                                        @if($t->comparticiones->count())
                                            <span class="text-[11px] text-gray-500" title="{{ $t->comparticiones->pluck('email')->join(', ') }}"><i class="fas fa-user-check text-green-600"></i> {{ $t->comparticiones->count() }}</span>
                                        @endif
                                        <button onclick='abrirCompartir({{ $t->id }}, @json($t->titulo))' class="text-amber-600 hover:text-amber-700 text-xs" title="Compartir"><i class="fas fa-share"></i></button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
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
    const KANBAN_CSRF = '{{ csrf_token() }}';
    let draggedId = null;

    function dragStart(e) {
        draggedId = e.currentTarget.dataset.id;
        e.dataTransfer.effectAllowed = 'move';
        e.currentTarget.classList.add('opacity-40');
    }
    function dragEnd(e) {
        e.currentTarget.classList.remove('opacity-40');
    }
    function dragOver(e) {
        e.preventDefault();
        e.currentTarget.classList.add('ring-2', 'ring-amber-400');
    }
    function dragLeave(e) {
        e.currentTarget.classList.remove('ring-2', 'ring-amber-400');
    }
    function dropCard(e) {
        e.preventDefault();
        const col = e.currentTarget;
        col.classList.remove('ring-2', 'ring-amber-400');
        const estado = col.dataset.estado;
        const card = document.querySelector('.kanban-card[data-id="' + draggedId + '"]');
        if (!card) return;
        const origen = card.closest('.kanban-col');
        if (origen === col) return;
        col.appendChild(card);
        recount();
        fetch('{{ url('agencia/tareas') }}/' + draggedId + '/estado', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': KANBAN_CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: new URLSearchParams({ _method: 'PATCH', estado: estado })
        }).then(function (r) {
            if (!r.ok) throw new Error('fail');
        }).catch(function () {
            alert('No se pudo mover la tarea. Recargo la página.');
            location.reload();
        });
    }
    function recount() {
        document.querySelectorAll('.kanban-col').forEach(function (c) {
            const n = c.querySelectorAll('.kanban-card').length;
            const badge = document.querySelector('.kanban-count[data-estado="' + c.dataset.estado + '"]');
            if (badge) badge.textContent = n;
        });
    }

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
