{{-- Modal de edición de tarea (escribe en Notion). Requiere $estados,$clientes,$areas,$responsables,$prioridades --}}
<div id="modalEditar" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none;">
    <div class="bg-white rounded-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800 m-0">Editar tarea</h3>
            <button onclick="cerrarEditar()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="formEditar" method="POST">
            @csrf @method('PATCH')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="md:col-span-2">
                    <label class="text-xs text-gray-500 block mb-1">Tarea *</label>
                    <input name="titulo" id="ed_titulo" required maxlength="200" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Cliente</label>
                    <select name="cliente" id="ed_cliente" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">—</option>@foreach($clientes as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Responsable</label>
                    <select name="responsable" id="ed_responsable" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">—</option>@foreach($responsables as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Área</label>
                    <select name="area" id="ed_area" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">—</option>@foreach($areas as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Estado</label>
                    <select name="estado" id="ed_estado" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        @foreach($estados as $e)<option value="{{ $e }}">{{ $e }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Prioridad</label>
                    <select name="prioridad" id="ed_prioridad" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">—</option>@foreach($prioridades as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Fecha límite</label>
                    <input type="date" name="fecha_limite" id="ed_fecha" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs text-gray-500 block mb-1">Notas</label>
                    <textarea name="notas" id="ed_notas" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                </div>
            </div>
            <div class="flex items-center justify-between mt-4">
                <button type="button" onclick="archivarTarea()" class="text-red-600 text-sm hover:text-red-700"><i class="fas fa-trash"></i> Archivar</button>
                <div class="flex items-center gap-3">
                    <a id="ed_notion" href="#" target="_blank" rel="noopener" class="text-sm text-gray-400 hover:text-gray-600"><i class="fas fa-external-link-alt"></i></a>
                    <button type="submit" class="bg-brand-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-brand-700">Guardar</button>
                </div>
            </div>
        </form>
        <form id="formArchivar" method="POST" class="hidden">@csrf @method('DELETE')</form>
    </div>
</div>
<script>
    const ED_BASE = '{{ url('agencia/notion') }}';
    function abrirEditar(el) {
        const d = el.dataset;
        document.getElementById('formEditar').action = ED_BASE + '/' + encodeURIComponent(d.id);
        document.getElementById('formArchivar').action = ED_BASE + '/' + encodeURIComponent(d.id);
        document.getElementById('ed_titulo').value = d.titulo || '';
        setSel('ed_cliente', d.cliente); setSel('ed_responsable', d.responsable); setSel('ed_area', d.area);
        setSel('ed_estado', d.estado); setSel('ed_prioridad', d.prioridad);
        document.getElementById('ed_fecha').value = d.fecha || '';
        document.getElementById('ed_notas').value = d.notas || '';
        document.getElementById('ed_notion').href = d.url || '#';
        document.getElementById('modalEditar').style.display = 'flex';
    }
    function setSel(id, val) { const s = document.getElementById(id); if (s) s.value = val || ''; }
    function cerrarEditar() { document.getElementById('modalEditar').style.display = 'none'; }
    function archivarTarea() { if (confirm('¿Archivar esta tarea en Notion?')) document.getElementById('formArchivar').submit(); }
</script>
