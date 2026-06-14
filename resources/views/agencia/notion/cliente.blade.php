<x-app-layout>
@php
    $estadoMeta = [
        '📋 Por hacer'   => ['#6B7280', '#F3F4F6'],
        '🔨 En progreso' => ['#2563EB', '#DBEAFE'],
        '👀 En revisión' => ['#7C3AED', '#EDE9FE'],
        '🚫 Bloqueado'   => ['#DC2626', '#FEE2E2'],
        '✅ Hecho'       => ['#059669', '#D1FAE5'],
    ];
    $colorPrioridad = fn ($p) => str_contains((string) $p, 'Alta') ? '#DC2626' : (str_contains((string) $p, 'Baja') ? '#9CA3AF' : '#D97706');
@endphp
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="bs-card overflow-hidden mb-6">
            <div class="px-6 py-5 flex items-center justify-between flex-wrap gap-3" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                <div>
                    <h2 class="bs-display text-2xl text-white m-0 leading-tight">Tareas — Por cliente</h2>
                    <p class="text-sm text-white/90 mt-1 mb-0"><i class="fas fa-bolt"></i> Sincronizado con Notion</p>
                </div>
                <a href="{{ route('agencia.notion') }}" class="bs-btn-neutral shrink-0"><i class="fas fa-plus"></i> Nueva Tarea</a>
            </div>
        </div>

        @include('agencia.notion._nav')

        @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>@endif
        @if(session('error') || ($error ?? null))<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ session('error') ?? $error }}</div>@endif

        <div class="bs-card p-4 mb-6">
            <form method="GET" action="{{ route('agencia.notion.por-cliente') }}" class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[220px]">
                    <label class="text-xs text-gray-500 block mb-1">Buscar</label>
                    <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Tarea o cliente..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-brand-700">Filtrar</button>
                @if(request('buscar'))<a href="{{ route('agencia.notion.por-cliente') }}" class="text-sm text-gray-500 px-3 py-2">Limpiar</a>@endif
            </form>
        </div>

        @forelse($porCliente as $nombre => $items)
            <div class="bs-card overflow-hidden mb-4">
                <div class="px-5 py-3 bg-gray-900 text-white flex items-center justify-between">
                    <span class="font-semibold">{{ $nombre }}</span>
                    <span class="text-xs bg-white/15 rounded-full px-2 py-0.5">{{ count($items) }} {{ \Illuminate\Support\Str::plural('tarea', count($items)) }}</span>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($items as $t)
                        @php $m = $estadoMeta[$t['estado']] ?? ['#6B7280','#F3F4F6']; @endphp
                        <div class="px-5 py-3 flex items-center gap-3 flex-wrap"
                             data-id="{{ $t['id'] }}" data-titulo="{{ $t['titulo'] }}" data-cliente="{{ $t['cliente'] }}" data-area="{{ $t['area'] }}"
                             data-responsable="{{ $t['responsable'] }}" data-estado="{{ $t['estado'] }}" data-prioridad="{{ $t['prioridad'] }}"
                             data-fecha="{{ $t['fecha_limite'] ? substr($t['fecha_limite'],0,10) : '' }}" data-notas="{{ $t['notas'] }}" data-url="{{ $t['url'] }}">
                            <span class="text-xs font-semibold rounded-full px-3 py-1 shrink-0" style="background:{{ $m[1] }}; color:{{ $m[0] }};">{{ $t['estado'] }}</span>
                            <div class="flex-1 min-w-[200px]">
                                <p class="font-semibold text-sm text-gray-800 m-0">{{ $t['titulo'] ?: '(sin título)' }}</p>
                                @if($t['notas'])<p class="text-xs text-gray-500 mt-0.5 mb-0">{{ \Illuminate\Support\Str::limit($t['notas'], 90) }}</p>@endif
                            </div>
                            @if($t['prioridad'])<span class="text-[11px] shrink-0" style="color:{{ $colorPrioridad($t['prioridad']) }};">● {{ \Illuminate\Support\Str::after($t['prioridad'], ' ') }}</span>@endif
                            <span class="text-xs text-gray-500 shrink-0 w-16 text-right">@if($t['fecha_limite']){{ \Carbon\Carbon::parse($t['fecha_limite'])->format('d/m') }}@else<span class="text-gray-300">—</span>@endif</span>
                            <button onclick="abrirEditar(this.closest('[data-id]'))" class="text-gray-400 hover:text-brand-600 text-sm shrink-0" title="Editar"><i class="fas fa-pen"></i></button>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="bs-card p-10 text-center text-gray-400">No hay tareas.</div>
        @endforelse
    </div>
</div>

@include('agencia.notion._modal_editar')
</x-app-layout>
