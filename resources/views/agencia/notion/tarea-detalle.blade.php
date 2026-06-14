<x-app-layout>
@php
    $estadoMeta = [
        '📋 Por hacer'=>['#6B7280','#F3F4F6'],'🔨 En progreso'=>['#2563EB','#DBEAFE'],'👀 En revisión'=>['#7C3AED','#EDE9FE'],'🚫 Bloqueado'=>['#DC2626','#FEE2E2'],'✅ Hecho'=>['#059669','#D1FAE5'],
    ];
@endphp
<div class="py-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-4"><a href="{{ route('agencia.notion') }}" class="text-sm text-gray-500 hover:text-gray-700"><i class="fas fa-arrow-left"></i> Volver al tablero</a></div>

        @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>@endif
        @if(session('error') || ($error ?? null))<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ session('error') ?? $error }}</div>@endif

        @if(!($tarea ?? null))
            <div class="bs-card p-10 text-center text-gray-400">Tarea no encontrada.</div>
        @else
            @php $m = $estadoMeta[$tarea['estado']] ?? ['#6B7280','#F3F4F6']; @endphp
            <div id="tareaData" class="hidden"
                 data-id="{{ $tarea['id'] }}" data-titulo="{{ $tarea['titulo'] }}" data-cliente="{{ $tarea['cliente'] }}" data-area="{{ $tarea['area'] }}"
                 data-responsable="{{ $tarea['responsable'] }}" data-estado="{{ $tarea['estado'] }}" data-prioridad="{{ $tarea['prioridad'] }}"
                 data-fecha="{{ $tarea['fecha_limite'] ? substr($tarea['fecha_limite'],0,10) : '' }}" data-notas="{{ $tarea['notas'] }}" data-url="{{ $tarea['url'] }}"></div>

            <div class="bs-card overflow-hidden mb-6">
                <div class="px-6 py-5 flex items-start justify-between gap-3" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                    <h2 class="bs-display text-xl text-white m-0">{{ $tarea['titulo'] ?: '(sin título)' }}</h2>
                    <button onclick="abrirEditar(document.getElementById('tareaData'))" class="bs-btn-neutral shrink-0"><i class="fas fa-pen"></i> Editar</button>
                </div>
                <div class="p-6 flex flex-wrap gap-2 items-center">
                    <span class="text-xs font-semibold rounded-full px-3 py-1" style="background:{{ $m[1] }}; color:{{ $m[0] }};">{{ $tarea['estado'] }}</span>
                    @if($tarea['prioridad'])<span class="text-xs rounded-full px-3 py-1 bg-gray-100 text-gray-600">{{ $tarea['prioridad'] }}</span>@endif
                    @if($tarea['cliente'])<span class="text-xs rounded-full px-3 py-1 bg-orange-50 text-orange-700 font-medium">{{ $tarea['cliente'] }}</span>@endif
                    @if($tarea['area'])<span class="text-xs rounded-full px-3 py-1 bg-blue-50 text-blue-700">{{ $tarea['area'] }}</span>@endif
                    @if($tarea['responsable'])<span class="text-xs rounded-full px-3 py-1 bg-purple-50 text-purple-700"><i class="fas fa-user"></i> {{ \Illuminate\Support\Str::before($tarea['responsable'], ' (') }}</span>@endif
                    @if($tarea['fecha_limite'])<span class="text-xs rounded-full px-3 py-1 bg-gray-100 text-gray-600"><i class="far fa-calendar"></i> {{ \Carbon\Carbon::parse($tarea['fecha_limite'])->format('d/m/Y') }}</span>@endif
                </div>
                @if($tarea['notas'])
                    <div class="px-6 pb-5 -mt-2"><p class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3 m-0">{{ $tarea['notas'] }}</p></div>
                @endif
            </div>

            {{-- Contenido / brief de la página --}}
            <div class="bs-card p-6 mb-6">
                <h3 class="font-semibold text-gray-800 mt-0 mb-4 flex items-center gap-2"><i class="fas fa-file-lines text-gray-400"></i> Detalle</h3>
                @if(empty($bloques))
                    <p class="text-sm text-gray-400 m-0">Esta tarea no tiene contenido en el cuerpo. Agrega una nota abajo o el brief desde Notion.</p>
                @else
                    @foreach($bloques as $b)
                        @if($b['kind']==='heading')<h4 class="font-semibold text-gray-800 mt-4 mb-2 first:mt-0">{{ $b['text'] }}</h4>
                        @elseif($b['kind']==='p')<p class="text-sm text-gray-600 mb-2">{{ $b['text'] }}</p>
                        @elseif($b['kind']==='li')<div class="text-sm text-gray-600 mb-1 pl-4">• {{ $b['text'] }}</div>
                        @elseif($b['kind']==='quote')<blockquote class="border-l-2 border-amber-300 pl-3 text-sm text-gray-500 my-2">{{ $b['text'] }}</blockquote>
                        @elseif($b['kind']==='divider')<hr class="my-4 border-gray-100">
                        @elseif($b['kind']==='table' && !empty($b['rows']))
                            <div class="overflow-x-auto my-3"><table class="w-full text-sm border border-gray-200 rounded-lg"><tbody>
                                @foreach($b['rows'] as $ri => $row)
                                    <tr class="{{ $ri===0 ? 'bg-gray-900 text-white' : 'border-t border-gray-100' }}">@foreach($row as $cell)<td class="px-3 py-2 {{ $ri===0 ? 'text-xs uppercase tracking-wide' : '' }}">{{ $cell }}</td>@endforeach</tr>
                                @endforeach
                            </tbody></table></div>
                        @endif
                    @endforeach
                @endif
            </div>

            {{-- Agregar al detalle --}}
            <div class="bs-card p-6">
                <h3 class="font-semibold text-gray-800 mt-0 mb-1 flex items-center gap-2"><i class="fas fa-pen-to-square text-gray-400"></i> Agregar al detalle</h3>
                <p class="text-xs text-gray-400 mb-3">Soporta formato: <code class="bg-gray-100 px-1 rounded">#</code> título · <code class="bg-gray-100 px-1 rounded">-</code> viñeta · <code class="bg-gray-100 px-1 rounded">1.</code> numerada · <code class="bg-gray-100 px-1 rounded">&gt;</code> cita. Se agrega al cuerpo de la tarea en Notion.</p>
                <form method="POST" action="{{ route('agencia.notion.tarea.nota', $tarea['id']) }}">
                    @csrf
                    <textarea name="nota" rows="6" required maxlength="8000" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 outline-none font-mono" placeholder="# Brief&#10;- Punto 1&#10;- Punto 2&#10;&#10;Detalle del avance..."></textarea>
                    <div class="mt-3"><button type="submit" class="bg-brand-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-brand-700"><i class="fas fa-plus"></i> Agregar al detalle</button></div>
                </form>
            </div>
        @endif
    </div>
</div>

@include('agencia.notion._modal_editar')
</x-app-layout>
