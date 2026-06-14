{{-- Modal de edición de tarea (AJAX → Notion). Requiere $estados,$clientes,$areas,$responsables,$prioridades --}}
<div id="modalEditar" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="cerrarEditar()"></div>
    <div class="relative h-full flex items-center justify-center p-4">
        <div id="modalPanel" class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto transition-all duration-150">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800 m-0">Editar tarea</h3>
                <button onclick="cerrarEditar()" class="text-gray-400 hover:text-gray-700 w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center"><i class="fas fa-times"></i></button>
            </div>
            <form id="formEditar" method="POST" class="p-6">
                @csrf @method('PATCH')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="md:col-span-2">
                        <label class="text-xs font-medium text-gray-500 block mb-1">Tarea</label>
                        <input name="titulo" id="ed_titulo" required maxlength="200" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">Cliente</label>
                        <select name="cliente" id="ed_cliente" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white"><option value="">—</option>@foreach($clientes as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach</select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">Responsable</label>
                        <select name="responsable" id="ed_responsable" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white"><option value="">—</option>@foreach($responsables as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach</select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">Área</label>
                        <select name="area" id="ed_area" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white"><option value="">—</option>@foreach($areas as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach</select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">Estado</label>
                        <select name="estado" id="ed_estado" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">@foreach($estados as $e)<option value="{{ $e }}">{{ $e }}</option>@endforeach</select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">Prioridad</label>
                        <select name="prioridad" id="ed_prioridad" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white"><option value="">—</option>@foreach($prioridades as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach</select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 block mb-1">Fecha límite</label>
                        <input type="date" name="fecha_limite" id="ed_fecha" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-medium text-gray-500 block mb-1">Notas</label>
                        <textarea name="notas" id="ed_notas" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 outline-none"></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-5">
                    <button type="button" onclick="archivarTarea()" class="text-red-600 text-sm hover:bg-red-50 px-3 py-2 rounded-lg"><i class="fas fa-trash"></i> Archivar</button>
                    <div class="flex items-center gap-3">
                        <a id="ed_notion" href="#" target="_blank" rel="noopener" class="text-sm text-gray-400 hover:text-gray-700" title="Abrir en Notion"><i class="fas fa-external-link-alt"></i></a>
                        <button type="submit" id="ed_guardar" class="bg-brand-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-brand-700 disabled:opacity-50">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function notionToast(icon, title) {
        if (window.Swal) { Swal.fire({ toast: true, position: 'top-end', icon: icon, title: title, showConfirmButton: false, timer: 2400, timerProgressBar: true }); }
    }
    const ED_BASE = '{{ url('agencia/notion') }}';
    const ED_CSRF = '{{ csrf_token() }}';
    let edId = null;

    function setSel(id, v) { const s = document.getElementById(id); if (s) s.value = v || ''; }
    function abrirEditar(el) {
        const d = el.dataset; edId = d.id;
        document.getElementById('formEditar').action = ED_BASE + '/' + encodeURIComponent(d.id);
        document.getElementById('ed_titulo').value = d.titulo || '';
        setSel('ed_cliente', d.cliente); setSel('ed_responsable', d.responsable); setSel('ed_area', d.area);
        setSel('ed_estado', d.estado); setSel('ed_prioridad', d.prioridad);
        document.getElementById('ed_fecha').value = d.fecha || '';
        document.getElementById('ed_notas').value = d.notas || '';
        document.getElementById('ed_notion').href = d.url || '#';
        const m = document.getElementById('modalEditar'); m.classList.remove('hidden');
        const p = document.getElementById('modalPanel'); p.style.opacity = 0; p.style.transform = 'translateY(8px)';
        requestAnimationFrame(() => { p.style.opacity = 1; p.style.transform = 'translateY(0)'; });
    }
    function cerrarEditar() { document.getElementById('modalEditar').classList.add('hidden'); }

    document.getElementById('formEditar').addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('ed_guardar'); btn.disabled = true; btn.textContent = 'Guardando...';
        const fd = new FormData(this);
        fetch(this.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': ED_CSRF, 'Accept': 'application/json' }, body: fd })
            .then(r => r.json().then(j => ({ ok: r.ok, j })))
            .then(({ ok, j }) => {
                if (!ok || !j.ok) throw new Error(j.error || 'No se pudo guardar');
                cerrarEditar();
                notionToast('success', 'Tarea actualizada');
                if (typeof actualizarCard === 'function' && document.querySelector('.kanban-card[data-id="' + edId + '"]')) {
                    actualizarCard(edId, fd);
                } else { setTimeout(() => location.reload(), 500); }
            })
            .catch(err => notionToast('error', err.message))
            .finally(() => { btn.disabled = false; btn.textContent = 'Guardar'; });
    });

    function archivarTarea() {
        const id = edId;
        const go = () => fetch(ED_BASE + '/' + encodeURIComponent(id), { method: 'POST', headers: { 'X-CSRF-TOKEN': ED_CSRF, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' }, body: '_method=DELETE' })
            .then(r => r.json().then(j => ({ ok: r.ok, j })))
            .then(({ ok, j }) => {
                if (!ok || !j.ok) throw new Error(j.error || 'No se pudo archivar');
                cerrarEditar();
                const card = document.querySelector('.kanban-card[data-id="' + id + '"]');
                if (card) { card.remove(); if (typeof recount === 'function') recount(); } else { setTimeout(() => location.reload(), 500); }
                notionToast('success', 'Tarea archivada');
            })
            .catch(err => notionToast('error', err.message));
        if (window.Swal) {
            Swal.fire({ title: '¿Archivar tarea?', text: 'Se archivará en Notion.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Archivar', cancelButtonText: 'Cancelar', confirmButtonColor: '#DC2626' }).then(r => { if (r.isConfirmed) go(); });
        } else if (confirm('¿Archivar esta tarea?')) { go(); }
    }
</script>
