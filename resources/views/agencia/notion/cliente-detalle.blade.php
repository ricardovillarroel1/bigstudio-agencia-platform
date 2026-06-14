<x-app-layout>
@php
    $estadoMeta = [
        '📋 Por hacer'=>['#6B7280','#F3F4F6'],'🔨 En progreso'=>['#2563EB','#DBEAFE'],'👀 En revisión'=>['#7C3AED','#EDE9FE'],'🚫 Bloqueado'=>['#DC2626','#FEE2E2'],'✅ Hecho'=>['#059669','#D1FAE5'],
    ];
@endphp
<div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-4"><a href="{{ route('agencia.notion.clientes') }}" class="text-sm text-gray-500 hover:text-gray-700"><i class="fas fa-arrow-left"></i> Volver a clientes</a></div>

        @if($error ?? null)
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ $error }}</div>
        @elseif(!$cliente)
            <div class="bs-card p-10 text-center text-gray-400">Cliente no encontrado.</div>
        @else
            <div class="bs-card overflow-hidden mb-6">
                <div class="px-6 py-5" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                    <h2 class="bs-display text-2xl text-white m-0">{{ $cliente['nombre'] }}</h2>
                    @if($cliente['rubro'])<p class="text-sm text-white/90 mt-1 mb-0">{{ $cliente['rubro'] }}</p>@endif
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    @if($cliente['estado'])<div><span class="text-gray-400">Estado:</span> {{ $cliente['estado'] }}</div>@endif
                    @if($cliente['sitio_web'])<div><span class="text-gray-400">Sitio:</span> <a href="{{ $cliente['sitio_web'] }}" target="_blank" rel="noopener" class="text-brand-600 hover:underline">{{ \Illuminate\Support\Str::after($cliente['sitio_web'], '://') }}</a></div>@endif
                    @if($cliente['email'])<div><span class="text-gray-400">Email:</span> {{ $cliente['email'] }}</div>@endif
                    @if($cliente['telefono'])<div><span class="text-gray-400">Teléfono:</span> {{ $cliente['telefono'] }}</div>@endif
                    @if(!empty($cliente['plataforma']))<div><span class="text-gray-400">Plataforma:</span> {{ implode(', ', $cliente['plataforma']) }}</div>@endif
                    @if(!empty($cliente['servicios']))<div class="md:col-span-2"><span class="text-gray-400">Servicios:</span> {{ implode(' · ', $cliente['servicios']) }}</div>@endif
                    @if($cliente['notas'])<div class="md:col-span-2"><span class="text-gray-400">Notas:</span> {{ $cliente['notas'] }}</div>@endif
                </div>
            </div>

            {{-- Contenido de la página (accesos / info adicional) --}}
            @if(!empty($bloques))
                <div class="bs-card p-6 mb-6">
                    @foreach($bloques as $b)
                        @if($b['kind']==='heading')
                            <h3 class="font-semibold text-gray-800 mt-4 mb-2 first:mt-0">{{ $b['text'] }}</h3>
                        @elseif($b['kind']==='p')
                            <p class="text-sm text-gray-600 mb-2">{{ $b['text'] }}</p>
                        @elseif($b['kind']==='li')
                            <div class="text-sm text-gray-600 mb-1 pl-4">• {{ $b['text'] }}</div>
                        @elseif($b['kind']==='quote')
                            <blockquote class="border-l-2 border-gray-300 pl-3 text-sm text-gray-500 my-2">{{ $b['text'] }}</blockquote>
                        @elseif($b['kind']==='divider')
                            <hr class="my-4 border-gray-100">
                        @elseif($b['kind']==='table' && !empty($b['rows']))
                            <div class="overflow-x-auto my-3">
                                <table class="w-full text-sm border border-gray-200 rounded-lg">
                                    <tbody>
                                        @foreach($b['rows'] as $ri => $row)
                                            <tr class="{{ $ri===0 ? 'bg-gray-900 text-white' : 'border-t border-gray-100' }}">
                                                @foreach($row as $cell)
                                                    <td class="px-3 py-2 {{ $ri===0 ? 'text-xs uppercase tracking-wide' : '' }}">{{ $cell }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Tareas de este cliente --}}
            <div class="bs-card overflow-hidden">
                <div class="px-5 py-3 bg-gray-900 text-white flex items-center justify-between">
                    <span class="font-semibold">Tareas</span>
                    <span class="text-xs bg-white/15 rounded-full px-2 py-0.5">{{ count($tareas) }}</span>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse($tareas as $t)
                        @php $m = $estadoMeta[$t['estado']] ?? ['#6B7280','#F3F4F6']; @endphp
                        <div class="px-5 py-3 flex items-center gap-3">
                            <span class="text-xs font-semibold rounded-full px-3 py-1 shrink-0" style="background:{{ $m[1] }}; color:{{ $m[0] }};">{{ $t['estado'] }}</span>
                            <span class="flex-1 text-sm text-gray-800">{{ $t['titulo'] }}</span>
                            <span class="text-xs text-gray-500 shrink-0">@if($t['fecha_limite']){{ \Carbon\Carbon::parse($t['fecha_limite'])->format('d/m') }}@endif</span>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-center text-gray-400 text-sm">Sin tareas para este cliente.</div>
                    @endforelse
                </div>
                <div class="px-5 py-3 border-t border-gray-100">
                    <a href="{{ route('agencia.notion', ['cliente' => $cliente['nombre']]) }}" class="text-sm text-brand-600 hover:underline">Ver en el tablero <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        @endif
    </div>
</div>
</x-app-layout>
