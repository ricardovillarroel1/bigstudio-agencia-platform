<x-app-layout>
@php
    $estadoMeta = [
        '📋 Por hacer'   => ['#6B7280', '#F3F4F6'],
        '🔨 En progreso' => ['#2563EB', '#DBEAFE'],
        '👀 En revisión' => ['#7C3AED', '#EDE9FE'],
        '🚫 Bloqueado'   => ['#DC2626', '#FEE2E2'],
        '✅ Hecho'       => ['#059669', '#D1FAE5'],
    ];
    $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    $diasMes  = (int) $fin->day;
    $offset   = (int) $inicio->dayOfWeekIso - 1;
    $hoy      = \Carbon\Carbon::today();
    $esMesHoy = ($hoy->year === (int) $year && $hoy->month === (int) $month);
@endphp
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="bs-card overflow-hidden mb-6">
            <div class="px-6 py-5 flex items-center justify-between flex-wrap gap-3" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                <div>
                    <h2 class="bs-display text-2xl text-white m-0 leading-tight">Tareas — Calendario</h2>
                    <p class="text-sm text-white/90 mt-1 mb-0"><i class="fas fa-bolt"></i> Sincronizado con Notion</p>
                </div>
                <a href="{{ route('agencia.notion') }}" class="bs-btn-neutral shrink-0"><i class="fas fa-plus"></i> Nueva Tarea</a>
            </div>
        </div>

        @include('agencia.notion._nav')

        @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>@endif
        @if($error ?? null)<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ $error }}</div>@endif

        <div class="bs-card p-4 mb-6 flex items-center justify-between">
            <a href="{{ route('agencia.notion.calendario', ['y' => $prev->year, 'm' => $prev->month]) }}" class="bs-btn-neutral"><i class="fas fa-chevron-left"></i> {{ $meses[$prev->month] }}</a>
            <h3 class="bs-display text-xl text-gray-800 m-0">{{ $meses[$month] }} {{ $year }}</h3>
            <a href="{{ route('agencia.notion.calendario', ['y' => $next->year, 'm' => $next->month]) }}" class="bs-btn-neutral">{{ $meses[$next->month] }} <i class="fas fa-chevron-right"></i></a>
        </div>

        <div class="bs-card overflow-hidden">
            <div class="grid grid-cols-7 bg-gray-900 text-white text-xs font-semibold">
                @foreach(['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $d)<div class="px-2 py-2 text-center">{{ $d }}</div>@endforeach
            </div>
            <div class="grid grid-cols-7">
                @for($i = 0; $i < $offset; $i++)<div class="min-h-[110px] border-b border-r border-gray-100 bg-gray-50"></div>@endfor
                @for($dia = 1; $dia <= $diasMes; $dia++)
                    @php $esHoy = $esMesHoy && $dia === (int) $hoy->day; @endphp
                    <div class="min-h-[110px] border-b border-r border-gray-100 p-1.5 align-top">
                        <div class="text-xs {{ $esHoy ? 'bg-brand-600 text-white' : 'text-gray-400' }} font-semibold w-6 h-6 flex items-center justify-center rounded-full mb-1">{{ $dia }}</div>
                        @foreach(($porDia[$dia] ?? []) as $t)
                            @php $m = $estadoMeta[$t['estado']] ?? ['#6B7280','#F3F4F6']; @endphp
                            <div class="text-[11px] leading-tight rounded px-1.5 py-1 mb-1 truncate cursor-pointer hover:ring-1 hover:ring-gray-300 transition"
                                 style="background:{{ $m[1] }}; color:{{ $m[0] }};"
                                 title="{{ $t['titulo'] }}{{ $t['cliente'] ? ' · '.$t['cliente'] : '' }}"
                                 data-id="{{ $t['id'] }}" data-titulo="{{ $t['titulo'] }}" data-cliente="{{ $t['cliente'] }}" data-area="{{ $t['area'] }}"
                                 data-responsable="{{ $t['responsable'] }}" data-estado="{{ $t['estado'] }}" data-prioridad="{{ $t['prioridad'] }}"
                                 data-fecha="{{ $t['fecha_limite'] ? substr($t['fecha_limite'],0,10) : '' }}" data-notas="{{ $t['notas'] }}" data-url="{{ $t['url'] }}"
                                 onclick="abrirEditar(this)">
                                {{ \Illuminate\Support\Str::limit($t['titulo'], 22) }}
                            </div>
                        @endforeach
                    </div>
                @endfor
                @php $resto = (7 - (($offset + $diasMes) % 7)) % 7; @endphp
                @for($i = 0; $i < $resto; $i++)<div class="min-h-[110px] border-b border-r border-gray-100 bg-gray-50"></div>@endfor
            </div>
        </div>

        <div class="flex flex-wrap gap-3 mt-4">
            @foreach($estadoMeta as $k => $m)
                <span class="text-xs flex items-center gap-1"><span class="w-3 h-3 rounded-full inline-block" style="background:{{ $m[0] }};"></span>{{ $k }}</span>
            @endforeach
        </div>
    </div>
</div>

@include('agencia.notion._modal_editar')
</x-app-layout>
