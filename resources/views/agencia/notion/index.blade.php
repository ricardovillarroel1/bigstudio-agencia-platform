<x-app-layout>
@php
    $estadoMeta = [
        '📋 Por hacer'   => ['#6B7280', '#F3F4F6'],
        '🔨 En progreso' => ['#2563EB', '#DBEAFE'],
        '👀 En revisión' => ['#7C3AED', '#EDE9FE'],
        '🚫 Bloqueado'   => ['#DC2626', '#FEE2E2'],
        '✅ Hecho'       => ['#059669', '#D1FAE5'],
    ];
    $pc = fn ($p) => str_contains((string) $p, 'Alta') ? '#EF4444' : (str_contains((string) $p, 'Baja') ? '#9CA3AF' : '#F59E0B');
    $rc = fn ($r) => str_contains((string) $r, 'Ricardo') ? '#FF8100' : (str_contains((string) $r, 'Ariel') ? '#7C3AED' : '#64748B');
    $ini = fn ($r) => $r ? mb_strtoupper(mb_substr(\Illuminate\Support\Str::before($r, ' ('), 0, 1)) : '';
@endphp
<div class="py-6">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

        <div class="bs-card overflow-hidden mb-6">
            <div class="px-6 py-5 flex items-center justify-between flex-wrap gap-3" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                <div>
                    <h2 class="bs-display text-2xl text-white m-0 leading-tight">Tareas</h2>
                    <p class="text-sm text-white/90 mt-1 mb-0"><i class="fas fa-bolt"></i> Sincronizado con Notion · {{ $total ?? 0 }} tareas</p>
                </div>
                <button onclick="document.getElementById('formNuevaTarea').classList.toggle('hidden')" class="bs-btn-neutral shrink-0">
                    <i class="fas fa-plus"></i> Nueva Tarea
                </button>
            </div>
        </div>

        @include('agencia.notion._nav')

        @if(session('error') || ($error ?? null))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ session('error') ?? $error }}</div>
        @endif

        {{-- Formulario nueva tarea --}}
        <div id="formNuevaTarea" class="bs-card p-6 mb-6 hidden">
            <h3 class="font-semibold text-gray-800 mb-4">Crear tarea (se guarda en Notion)</h3>
            <form id="formCrear" action="{{ route('agencia.notion.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="text-xs font-medium text-gray-500 block mb-1">Tarea *</label>
                        <input type="text" name="titulo" required maxlength="200" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 outline-none" placeholder="¿Qué hay que hacer?">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">Cliente</label>
                        <select name="cliente" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white"><option value="">—</option>@foreach($clientes as $c)<option value="{{ $c }}" {{ request('cliente')===$c?'selected':'' }}>{{ $c }}</option>@endforeach</select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">Responsable</label>
                        <select name="responsable" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">@foreach($responsables as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach</select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">Área</label>
                        <select name="area" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white"><option value="">—</option>@foreach($areas as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach</select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">Prioridad</label>
                        <select name="prioridad" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">@foreach($prioridades as $p)<option value="{{ $p }}" {{ $p==='🟡 Media'?'selected':'' }}>{{ $p }}</option>@endforeach</select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">Estado</label>
                        <select name="estado" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">@foreach($estados as $e)<option value="{{ $e }}">{{ $e }}</option>@endforeach</select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">Fecha límite</label>
                        <input type="date" name="fecha_limite" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-medium text-gray-500 block mb-1">Notas</label>
                        <textarea name="notas" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Detalle, links..."></textarea>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" id="btnCrear" class="bg-brand-600 text-white px-6 py-2 rounded-lg text-sm font-semibold hover:bg-brand-700 disabled:opacity-50">Crear en Notion</button>
                </div>
            </form>
        </div>

        {{-- Filtros --}}
        <div class="bs-card p-4 mb-6">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[220px]">
                    <label class="text-xs font-medium text-gray-500 block mb-1">Buscar</label>
                    <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Tarea o cliente..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="min-w-[200px]">
                    <label class="text-xs font-medium text-gray-500 block mb-1">Cliente</label>
                    <select name="cliente" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white"><option value="">Todos</option>@foreach($clientes as $c)<option value="{{ $c }}" {{ request('cliente')===$c?'selected':'' }}>{{ $c }}</option>@endforeach</select>
                </div>
                <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-brand-700">Filtrar</button>
                @if(request('cliente') || request('buscar'))<a href="{{ route('agencia.notion') }}" class="text-sm text-gray-500 px-3 py-2">Limpiar</a>@endif
            </form>
        </div>

        {{-- Tablero --}}
        <div class="flex gap-4 overflow-x-auto pb-4">
            @foreach($estados as $e)
                @php $m = $estadoMeta[$e] ?? ['#6B7280','#F3F4F6']; $items = $porEstado[$e] ?? []; @endphp
                <div class="w-80 shrink-0 flex flex-col">
                    <div class="rounded-t-xl px-3 py-2.5 flex items-center justify-between" style="background:{{ $m[1] }};">
                        <span class="font-semibold text-sm flex items-center gap-2" style="color:{{ $m[0] }};"><span class="w-2 h-2 rounded-full" style="background:{{ $m[0] }};"></span>{{ $e }}</span>
                        <span class="kanban-count text-xs font-bold px-2 py-0.5 rounded-full bg-white/70" data-estado="{{ $e }}" style="color:{{ $m[0] }};">{{ count($items) }}</span>
                    </div>
                    <div class="kanban-col bg-slate-100/70 rounded-b-xl p-2 flex-1 min-h-[240px] transition" data-estado="{{ $e }}" ondragover="dragOver(event)" ondragleave="dragLeave(event)" ondrop="dropCard(event)">
                        @foreach($items as $t)
                            @php
                                $f = !empty($t['fecha_limite']) ? \Carbon\Carbon::parse($t['fecha_limite']) : null;
                                $overdue = $f && $f->startOfDay()->lt(\Carbon\Carbon::today()) && $t['estado'] !== '✅ Hecho';
                            @endphp
                            <div class="group bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition p-3 mb-2 kanban-card cursor-grab active:cursor-grabbing"
                                 style="border-left:3px solid {{ $t['prioridad'] ? $pc($t['prioridad']) : '#E5E7EB' }};"
                                 draggable="true"
                                 data-id="{{ $t['id'] }}" data-titulo="{{ $t['titulo'] }}" data-cliente="{{ $t['cliente'] }}" data-area="{{ $t['area'] }}"
                                 data-responsable="{{ $t['responsable'] }}" data-estado="{{ $t['estado'] }}" data-prioridad="{{ $t['prioridad'] }}"
                                 data-fecha="{{ $t['fecha_limite'] ? substr($t['fecha_limite'],0,10) : '' }}" data-notas="{{ $t['notas'] }}" data-url="{{ $t['url'] }}"
                                 ondragstart="dragStart(event)" ondragend="dragEnd(event)">
                                <div class="flex items-start justify-between gap-2">
                                    <a href="{{ route('agencia.notion.tarea', $t['id']) }}" draggable="false" class="font-medium text-sm text-gray-800 leading-snug hover:text-brand-600 hover:underline" data-f="titulo">{{ $t['titulo'] ?: '(sin título)' }}</a>
                                    <button onclick="abrirEditar(this.closest('.kanban-card'))" class="opacity-0 group-hover:opacity-100 transition text-gray-300 hover:text-brand-600 shrink-0" title="Editar"><i class="fas fa-pen text-xs"></i></button>
                                </div>
                                <div data-f="cliente">@if($t['cliente'])<span class="inline-block text-[11px] mt-1.5 px-2 py-0.5 rounded-md bg-orange-50 text-orange-700 font-medium">{{ $t['cliente'] }}</span>@endif</div>
                                <div class="flex items-center justify-between mt-2.5">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span data-f="ini" class="w-5 h-5 rounded-full text-white text-[10px] font-bold flex items-center justify-center shrink-0" style="background:{{ $rc($t['responsable']) }};{{ $t['responsable'] ? '' : 'display:none;' }}">{{ $ini($t['responsable']) }}</span>
                                        <span data-f="resp" class="text-[11px] text-gray-500 truncate">{{ \Illuminate\Support\Str::before($t['responsable'], ' (') }}</span>
                                    </div>
                                    <span data-f="fecha" class="text-[11px] px-1.5 py-0.5 rounded {{ $overdue ? 'bg-red-50 text-red-600 font-medium' : 'text-gray-400' }}">{{ $f ? $f->format('d/m') : '' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@include('agencia.notion._modal_editar')

<script>
    const NOTION_CSRF = '{{ csrf_token() }}';
    const NOTION_BASE = '{{ url('agencia/notion') }}';
    const ESTADO_META = @json($estadoMeta);
    let draggedId = null;

    function dragStart(e) { draggedId = e.currentTarget.dataset.id; e.dataTransfer.effectAllowed = 'move'; e.currentTarget.classList.add('opacity-40', 'rotate-1'); }
    function dragEnd(e) { e.currentTarget.classList.remove('opacity-40', 'rotate-1'); }
    function dragOver(e) { e.preventDefault(); e.currentTarget.classList.add('ring-2', 'ring-amber-300'); }
    function dragLeave(e) { e.currentTarget.classList.remove('ring-2', 'ring-amber-300'); }
    function dropCard(e) {
        e.preventDefault();
        const col = e.currentTarget; col.classList.remove('ring-2', 'ring-amber-300');
        const estado = col.dataset.estado;
        const card = document.querySelector('.kanban-card[data-id="' + draggedId + '"]');
        if (!card || card.closest('.kanban-col') === col) return;
        col.appendChild(card); card.dataset.estado = estado; recount();
        fetch(NOTION_BASE + '/' + encodeURIComponent(draggedId) + '/estado', {
            method: 'PATCH', headers: { 'X-CSRF-TOKEN': NOTION_CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ estado: estado })
        }).then(r => { if (!r.ok) throw new Error(); notionToast('success', 'Estado actualizado'); })
          .catch(() => { notionToast('error', 'No se pudo mover. Recargo.'); setTimeout(() => location.reload(), 800); });
    }
    function recount() {
        document.querySelectorAll('.kanban-col').forEach(function (c) {
            const n = c.querySelectorAll('.kanban-card').length;
            const b = document.querySelector('.kanban-count[data-estado="' + c.dataset.estado + '"]'); if (b) b.textContent = n;
        });
    }

    // Edición en vivo de la tarjeta tras guardar (sin recargar)
    function actualizarCard(id, fd) {
        try {
            const card = document.querySelector('.kanban-card[data-id="' + id + '"]');
            const g = k => (fd.get(k) || '').toString();
            const pc = p => p.includes('Alta') ? '#EF4444' : (p.includes('Baja') ? '#9CA3AF' : '#F59E0B');
            const rc = r => r.includes('Ricardo') ? '#FF8100' : (r.includes('Ariel') ? '#7C3AED' : '#64748B');
            card.dataset.titulo = g('titulo'); card.dataset.cliente = g('cliente'); card.dataset.area = g('area');
            card.dataset.responsable = g('responsable'); card.dataset.estado = g('estado'); card.dataset.prioridad = g('prioridad');
            card.dataset.fecha = g('fecha_limite'); card.dataset.notas = g('notas');
            card.querySelector('[data-f="titulo"]').textContent = g('titulo') || '(sin título)';
            card.style.borderLeft = '3px solid ' + (g('prioridad') ? pc(g('prioridad')) : '#E5E7EB');
            const cl = card.querySelector('[data-f="cliente"]');
            cl.innerHTML = g('cliente') ? '<span class="inline-block text-[11px] mt-1.5 px-2 py-0.5 rounded-md bg-orange-50 text-orange-700 font-medium">' + g('cliente') + '</span>' : '';
            const resp = g('responsable'); const ini = card.querySelector('[data-f="ini"]');
            if (resp) { ini.style.display = ''; ini.style.background = rc(resp); ini.textContent = resp.charAt(0).toUpperCase(); } else { ini.style.display = 'none'; }
            card.querySelector('[data-f="resp"]').textContent = resp.split(' (')[0];
            const fechaEl = card.querySelector('[data-f="fecha"]'); const fv = g('fecha_limite');
            fechaEl.textContent = fv ? fv.slice(8, 10) + '/' + fv.slice(5, 7) : '';
            const targetCol = document.querySelector('.kanban-col[data-estado="' + g('estado') + '"]');
            if (targetCol && card.closest('.kanban-col') !== targetCol) { targetCol.appendChild(card); recount(); }
        } catch (err) { location.reload(); }
    }

    // Crear sin recargar
    document.getElementById('formCrear').addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('btnCrear'); btn.disabled = true; btn.textContent = 'Creando...';
        fetch(this.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': NOTION_CSRF, 'Accept': 'application/json' }, body: new FormData(this) })
            .then(r => r.json().then(j => ({ ok: r.ok, j })))
            .then(({ ok, j }) => { if (!ok || !j.ok) throw new Error(j.error || 'Error'); notionToast('success', 'Tarea creada'); setTimeout(() => location.reload(), 600); })
            .catch(err => { notionToast('error', err.message); btn.disabled = false; btn.textContent = 'Crear en Notion'; });
    });
</script>
</x-app-layout>
