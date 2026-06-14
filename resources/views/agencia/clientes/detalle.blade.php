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
    $pendientes = $cliente->tareas->whereIn('estado', \App\Models\AgenciaTarea::ESTADOS_PENDIENTES)->count();
@endphp
<div class="py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        <a href="{{ route('agencia.clientes') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-3 inline-block"><i class="fas fa-arrow-left"></i> Volver a clientes</a>

        <div class="bs-card overflow-hidden mb-6">
            <div class="px-6 py-5 flex items-center justify-between flex-wrap gap-3" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                <div>
                    <h2 class="bs-display text-2xl text-white m-0 leading-tight">{{ $cliente->nombre }}@if($cliente->proyecto) <span class="text-lg font-normal text-white/80">({{ $cliente->proyecto }})</span>@endif</h2>
                    <p class="text-sm text-white/90 mt-1 mb-0">{{ $pendientes }} tarea(s) por hacer</p>
                </div>
                <a href="{{ route('agencia.clientes.edit', $cliente) }}" class="bs-btn-neutral shrink-0"><i class="fas fa-edit"></i> Editar cliente</a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ $errors->first() }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Info del cliente --}}
            <div class="bs-card p-5 h-fit">
                <p class="text-xs text-brand-600 font-bold uppercase tracking-wide mb-3">Datos del cliente</p>
                <dl class="text-sm space-y-2">
                    <div><dt class="text-gray-500 text-xs">Email</dt><dd class="text-gray-800 m-0">{{ $cliente->email ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500 text-xs">Teléfono</dt><dd class="text-gray-800 m-0">{{ $cliente->telefono ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500 text-xs">RUT</dt><dd class="text-gray-800 m-0">{{ $cliente->rut ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500 text-xs">Razón social</dt><dd class="text-gray-800 m-0">{{ $cliente->razon_social ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500 text-xs">Estado</dt><dd class="m-0"><span class="text-xs px-2 py-0.5 rounded-full {{ $cliente->estado==='activo'?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500' }}">{{ ucfirst($cliente->estado) }}</span></dd></div>
                </dl>
            </div>

            {{-- Tareas --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Nueva tarea --}}
                <div class="bs-card p-5">
                    <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-plus text-brand-600"></i> Nueva tarea para {{ $cliente->nombre }}</h3>
                    <form action="{{ route('agencia.tareas.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="agencia_cliente_id" value="{{ $cliente->id }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-500 block mb-1">Título *</label>
                                <input type="text" name="titulo" required maxlength="180" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ej: Diseñar feed de septiembre">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-500 block mb-1">Descripción</label>
                                <textarea name="descripcion" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
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
                        <button type="submit" class="mt-3 bg-brand-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-brand-700">Agregar tarea</button>
                    </form>
                </div>

                {{-- Lista de tareas --}}
                <div class="bs-card overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100"><h3 class="font-semibold text-gray-800 m-0">Tareas del cliente</h3></div>
                    @forelse($cliente->tareas as $tarea)
                        @php $m = $estadoMeta[$tarea->estado] ?? ['?','#6B7280','#F3F4F6']; @endphp
                        <div class="px-5 py-4 border-b border-gray-50">
                            <div class="flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800 m-0">{{ $tarea->titulo }}</p>
                                @if($tarea->descripcion)<p class="text-xs text-gray-500 mt-1 mb-0">{{ $tarea->descripcion }}</p>@endif
                                <div class="flex items-center gap-3 mt-2">
                                    @if($tarea->fecha_limite)<span class="text-[11px] text-gray-500"><i class="far fa-calendar"></i> {{ $tarea->fecha_limite->format('d/m/Y') }}</span>@endif
                                    @if($tarea->comparticiones->count())<span class="text-[11px] text-green-600" title="{{ $tarea->comparticiones->pluck('email')->join(', ') }}"><i class="fas fa-user-check"></i> {{ $tarea->comparticiones->count() }} compartida(s)</span>@endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <form action="{{ route('agencia.tareas.estado', $tarea) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <select name="estado" onchange="this.form.submit()" class="text-xs font-semibold rounded-full px-3 py-1 border-0" style="background:{{ $m[2] }}; color:{{ $m[1] }};">
                                        @foreach(['borrador','pendiente','en_curso','en_revision','requiere_cambios','terminado'] as $e)
                                            <option value="{{ $e }}" {{ $tarea->estado===$e?'selected':'' }}>{{ $estadoMeta[$e][0] }}</option>
                                        @endforeach
                                    </select>
                                </form>
                                <button onclick='abrirCompartir({{ $tarea->id }}, @json($tarea->titulo))' class="px-2 py-1 rounded bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs" title="Compartir"><i class="fas fa-share"></i></button>
                                <form action="{{ route('agencia.tareas.delete', $tarea) }}" method="POST" onsubmit="return confirm('¿Eliminar esta tarea?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-2 py-1 rounded bg-red-50 hover:bg-red-100 text-red-600 text-xs" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                            </div>{{-- /flex row --}}

                            {{-- Acuse de lectura por colaborador --}}
                            @if($tarea->comparticiones->count())
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                @foreach($tarea->comparticiones as $c)
                                    @if($c->primer_acceso_en)
                                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-green-50 text-green-700" title="{{ $c->email }}"><i class="fas fa-eye"></i> {{ $c->user->name ?? $c->email }} · visto {{ $c->primer_acceso_en->diffForHumans() }}</span>
                                    @else
                                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-500" title="{{ $c->email }}"><i class="fas fa-eye-slash"></i> {{ $c->user->name ?? $c->email }} · no visto</span>
                                    @endif
                                @endforeach
                            </div>
                            @endif

                            {{-- Conversación (colapsable) --}}
                            <button type="button" onclick="document.getElementById('conv-{{ $tarea->id }}').classList.toggle('hidden')" class="mt-2 text-xs text-brand-600 font-medium hover:underline">
                                <i class="fas fa-comments"></i> Conversación
                                @if($tarea->comentarios->count())<span class="bg-orange-100 text-brand-700 rounded-full px-1.5">{{ $tarea->comentarios->count() }}</span>@endif
                                @if($tarea->archivos->count()) · <i class="fas fa-paperclip"></i> {{ $tarea->archivos->count() }}@endif
                            </button>
                            <div id="conv-{{ $tarea->id }}" class="hidden">
                                @include('agencia.tareas._conversacion', ['tarea' => $tarea, 'modo' => 'admin'])
                            </div>
                        </div>{{-- /card --}}
                    @empty
                        <div class="px-5 py-8 text-center text-gray-400 text-sm">Este cliente no tiene tareas todavía.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal compartir --}}
<div id="modalCompartir" class="fixed inset-0 bg-black/50 z-50" style="display:none; align-items:center; justify-content:center; padding:1rem;">
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
        document.getElementById('modalCompartir').style.display = 'flex';
    }
    function cerrarCompartir() { document.getElementById('modalCompartir').style.display = 'none'; }
</script>
</x-app-layout>
