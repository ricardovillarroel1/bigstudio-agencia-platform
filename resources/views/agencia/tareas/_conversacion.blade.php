{{-- Partial de conversación de una tarea. Params: $tarea, $modo ('admin'|'colaborador') --}}
@php
    $esAdmin = ($modo ?? 'admin') === 'admin';
    $rutaComentar = $esAdmin ? route('agencia.tareas.comentarios.store', $tarea) : route('agencia.mis-tareas.comentarios.store', $tarea);
    $rutaArchivo  = $esAdmin ? route('agencia.tareas.archivos.store', $tarea) : route('agencia.mis-tareas.archivos.store', $tarea);
    $rolColors = ['admin' => ['#FFF3E0', '#C2410C', 'Big Studio'], 'colaborador' => ['#DBEAFE', '#1D4ED8', 'Diseñador'], 'cliente' => ['#F3F4F6', '#374151', 'Cliente']];
    $comentarios = $tarea->comentarios->sortBy('created_at');
    $briefs      = $tarea->archivos->where('tipo', 'brief');
    $entregables = $tarea->archivos->where('tipo', 'entregable');
@endphp
<div class="mt-3 pt-3 border-t border-gray-100">

    {{-- ADJUNTOS --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Brief / referencias</p>
            @forelse($briefs as $arch)
                <div class="flex items-center justify-between text-xs bg-gray-50 rounded px-2 py-1 mb-1">
                    <a href="{{ route('agencia.tareas.archivos.descargar', $arch) }}" class="text-blue-600 hover:underline truncate" title="{{ $arch->nombre_original }}"><i class="fas fa-paperclip"></i> {{ \Illuminate\Support\Str::limit($arch->nombre_original, 24) }}</a>
                    <span class="text-gray-400 ml-2 shrink-0">{{ $arch->tamanoLegible() }}</span>
                </div>
            @empty
                <p class="text-xs text-gray-300">—</p>
            @endforelse
        </div>
        <div>
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Entregables</p>
            @forelse($entregables as $arch)
                <div class="flex items-center justify-between text-xs bg-emerald-50 rounded px-2 py-1 mb-1">
                    <a href="{{ route('agencia.tareas.archivos.descargar', $arch) }}" class="text-emerald-700 hover:underline truncate" title="{{ $arch->nombre_original }}"><i class="fas fa-file-arrow-down"></i> {{ \Illuminate\Support\Str::limit($arch->nombre_original, 24) }}</a>
                    <span class="text-gray-400 ml-2 shrink-0">{{ $arch->tamanoLegible() }}</span>
                </div>
            @empty
                <p class="text-xs text-gray-300">—</p>
            @endforelse
        </div>
    </div>

    {{-- HILO DE COMENTARIOS --}}
    @if($comentarios->count())
        <div class="space-y-2 mb-3 max-h-60 overflow-y-auto">
            @foreach($comentarios as $com)
                @php $rc = $rolColors[$com->rol] ?? ['#F3F4F6','#374151',ucfirst($com->rol)]; @endphp
                <div class="text-sm">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded" style="background:{{ $rc[0] }};color:{{ $rc[1] }};">{{ $rc[2] }}</span>
                        <span class="text-xs font-semibold text-gray-700">{{ $com->autor_nombre }}</span>
                        <span class="text-[11px] text-gray-400">{{ $com->created_at?->diffForHumans() }}</span>
                    </div>
                    <p class="text-gray-700 mt-0.5 mb-0 whitespace-pre-line pl-1">{{ $com->cuerpo }}</p>
                    @if($com->enlace_externo)
                        <a href="{{ $com->enlace_externo }}" target="_blank" rel="noopener" class="text-xs text-blue-600 hover:underline pl-1"><i class="fas fa-link"></i> {{ \Illuminate\Support\Str::limit($com->enlace_externo, 50) }}</a>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="text-xs text-gray-300 mb-3">Sin comentarios aún.</p>
    @endif

    {{-- FORMS: comentar + subir archivo --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <form action="{{ $rutaComentar }}" method="POST">
            @csrf
            <textarea name="cuerpo" rows="2" required maxlength="5000" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Escribe un comentario..."></textarea>
            <div class="flex gap-2 mt-1">
                <input type="url" name="enlace_externo" class="flex-1 border border-gray-300 rounded-lg px-2 py-1 text-xs" placeholder="Link Drive/Figma (opcional)">
                <button type="submit" class="bg-brand-600 text-white px-3 py-1 rounded-lg text-xs font-semibold hover:bg-brand-700 shrink-0">Comentar</button>
            </div>
        </form>
        <form action="{{ $rutaArchivo }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label class="text-[11px] text-gray-400 block mb-1">{{ $esAdmin ? 'Subir brief / referencia' : 'Subir entregable' }} (máx. 20MB)</label>
            <div class="flex gap-2">
                <input type="file" name="archivo" required class="flex-1 border border-gray-300 rounded-lg px-2 py-1 text-xs">
                <button type="submit" class="bg-gray-700 text-white px-3 py-1 rounded-lg text-xs font-semibold hover:bg-gray-800 shrink-0">Subir</button>
            </div>
        </form>
    </div>
</div>
