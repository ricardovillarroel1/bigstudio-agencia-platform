<x-app-layout>
@php
    $estadoMeta = [
        '📋 Por hacer'   => ['#6B7280', '#F3F4F6'],
        '🔨 En progreso' => ['#2563EB', '#DBEAFE'],
        '👀 En revisión' => ['#7C3AED', '#EDE9FE'],
        '🚫 Bloqueado'   => ['#DC2626', '#FEE2E2'],
        '✅ Hecho'       => ['#059669', '#D1FAE5'],
    ];
    $colorPrioridad = function ($p) {
        if (str_contains((string) $p, 'Alta')) return '#DC2626';
        if (str_contains((string) $p, 'Baja')) return '#9CA3AF';
        return '#D97706';
    };
@endphp
<div class="py-6">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

        <div class="bs-card overflow-hidden mb-6">
            <div class="px-6 py-5 flex items-center justify-between flex-wrap gap-3" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                <div>
                    <h2 class="bs-display text-2xl text-white m-0 leading-tight">Tareas</h2>
                    <p class="text-sm text-white/90 mt-1 mb-0"><i class="fas fa-bolt"></i> Sincronizado con Notion · {{ $total ?? 0 }} tareas</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="https://www.notion.so/83f6f29fb44a432e960f155aaf27610c" target="_blank" rel="noopener" class="bs-btn-neutral"><i class="fas fa-external-link-alt"></i> Abrir en Notion</a>
                    <button onclick="document.getElementById('formNuevaTarea').classList.toggle('hidden')" class="bs-btn-neutral">
                        <i class="fas fa-plus"></i> Nueva Tarea
                    </button>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
        @endif
        @if(session('error') || ($error ?? null))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ session('error') ?? $error }}</div>
        @endif

        {{-- Formulario nueva tarea --}}
        <div id="formNuevaTarea" class="bs-card p-6 mb-6 hidden">
            <h3 class="font-semibold text-gray-800 mb-4">Crear tarea (se guarda en Notion)</h3>
            <form action="{{ route('agencia.notion.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-500 block mb-1">Tarea *</label>
                        <input type="text" name="titulo" required maxlength="200" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="¿Qué hay que hacer?">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Cliente</label>
                        <select name="cliente" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">—</option>
                            @foreach($clientes as $c)<option value="{{ $c }}" {{ request('cliente')===$c?'selected':'' }}>{{ $c }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Responsable</label>
                        <select name="responsable" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            @foreach($responsables as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Área</label>
                        <select name="area" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">—</option>
                            @foreach($areas as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Prioridad</label>
                        <select name="prioridad" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            @foreach($prioridades as $p)<option value="{{ $p }}" {{ $p==='🟡 Media'?'selected':'' }}>{{ $p }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Estado</label>
                        <select name="estado" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            @foreach($estados as $e)<option value="{{ $e }}">{{ $e }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Fecha límite</label>
                        <input type="date" name="fecha_limite" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs text-gray-500 block mb-1">Notas</label>
                        <textarea name="notas" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Detalle, links..."></textarea>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="bg-brand-600 text-white px-6 py-2 rounded-lg text-sm font-semibold hover:bg-brand-700">Crear en Notion</button>
                </div>
            </form>
        </div>

        {{-- Filtros --}}
        <div class="bs-card p-4 mb-6">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[220px]">
                    <label class="text-xs text-gray-500 block mb-1">Buscar</label>
                    <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Tarea o cliente..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="min-w-[200px]">
                    <label class="text-xs text-gray-500 block mb-1">Cliente</label>
                    <select name="cliente" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Todos</option>
                        @foreach($clientes as $c)<option value="{{ $c }}" {{ request('cliente')===$c?'selected':'' }}>{{ $c }}</option>@endforeach
                    </select>
                </div>
                <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-brand-700">Filtrar</button>
                @if(request('cliente') || request('buscar'))
                    <a href="{{ route('agencia.notion') }}" class="text-sm text-gray-500 px-3 py-2">Limpiar</a>
                @endif
            </form>
        </div>

        {{-- Tablero --}}
        <div class="flex gap-4 overflow-x-auto pb-4">
            @foreach($estados as $e)
                @php $m = $estadoMeta[$e] ?? ['#6B7280','#F3F4F6']; $items = $porEstado[$e] ?? []; @endphp
                <div class="w-80 shrink-0 flex flex-col">
                    <div class="rounded-t-xl px-3 py-2 flex items-center justify-between" style="background:{{ $m[1] }};">
                        <span class="font-semibold text-sm" style="color:{{ $m[0] }};">{{ $e }}</span>
                        <span class="kanban-count text-xs font-bold px-2 py-0.5 rounded-full bg-white/70" data-estado="{{ $e }}" style="color:{{ $m[0] }};">{{ count($items) }}</span>
                    </div>
                    <div class="kanban-col bg-gray-50 rounded-b-xl p-2 flex-1 min-h-[200px] transition"
                         data-estado="{{ $e }}"
                         ondragover="dragOver(event)" ondragleave="dragLeave(event)" ondrop="dropCard(event)">
                        @foreach($items as $t)
                            @php $f = !empty($t['fecha_limite']) ? \Carbon\Carbon::parse($t['fecha_limite'])->format('d/m') : null; @endphp
                            <div class="bs-card p-3 mb-2 kanban-card cursor-move" draggable="true" data-id="{{ $t['id'] }}" ondragstart="dragStart(event)" ondragend="dragEnd(event)">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="font-semibold text-sm text-gray-800 m-0 leading-snug">{{ $t['titulo'] ?: '(sin título)' }}</p>
                                    @if($t['prioridad'])<span class="text-xs shrink-0" style="color:{{ $colorPrioridad($t['prioridad']) }};" title="{{ $t['prioridad'] }}">●</span>@endif
                                </div>
                                @if($t['cliente'])<p class="text-xs text-brand-600 mt-1 mb-0">{{ $t['cliente'] }}</p>@endif
                                <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100">
                                    <span class="text-[11px] text-gray-500">
                                        @if($t['responsable'])<i class="fas fa-user"></i> {{ \Illuminate\Support\Str::before($t['responsable'], ' (') }}@endif
                                        @if($f) · <i class="far fa-calendar"></i> {{ $f }}@endif
                                    </span>
                                    <a href="{{ $t['url'] }}" target="_blank" rel="noopener" class="text-gray-400 hover:text-gray-600 text-xs" title="Abrir en Notion"><i class="fas fa-external-link-alt"></i></a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    const NOTION_CSRF = '{{ csrf_token() }}';
    const NOTION_BASE = '{{ url('agencia/notion') }}';
    let draggedId = null;

    function dragStart(e) { draggedId = e.currentTarget.dataset.id; e.dataTransfer.effectAllowed = 'move'; e.currentTarget.classList.add('opacity-40'); }
    function dragEnd(e) { e.currentTarget.classList.remove('opacity-40'); }
    function dragOver(e) { e.preventDefault(); e.currentTarget.classList.add('ring-2', 'ring-amber-400'); }
    function dragLeave(e) { e.currentTarget.classList.remove('ring-2', 'ring-amber-400'); }
    function dropCard(e) {
        e.preventDefault();
        const col = e.currentTarget;
        col.classList.remove('ring-2', 'ring-amber-400');
        const estado = col.dataset.estado;
        const card = document.querySelector('.kanban-card[data-id="' + draggedId + '"]');
        if (!card) return;
        if (card.closest('.kanban-col') === col) return;
        col.appendChild(card);
        recount();
        fetch(NOTION_BASE + '/' + encodeURIComponent(draggedId) + '/estado', {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': NOTION_CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ estado: estado })
        }).then(function (r) {
            if (!r.ok) throw new Error('fail');
        }).catch(function () {
            alert('No se pudo actualizar en Notion. Recargo.');
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
</script>
</x-app-layout>
