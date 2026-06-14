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
    $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    $diasMes   = (int) $fin->day;
    $offset    = (int) $inicio->dayOfWeekIso - 1; // 0 = lunes
    $hoy       = \Carbon\Carbon::today();
    $esMesHoy  = ($hoy->year === (int) $year && $hoy->month === (int) $month);
@endphp
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="bs-card overflow-hidden mb-6">
            <div class="px-6 py-5 flex items-center justify-between flex-wrap gap-3" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                <div>
                    <h2 class="bs-display text-2xl text-white m-0 leading-tight">Tareas — Calendario</h2>
                    <p class="text-sm text-white/90 mt-1 mb-0">Tareas con fecha límite del mes</p>
                </div>
                <a href="{{ route('agencia.tareas') }}" class="bs-btn-neutral shrink-0"><i class="fas fa-plus"></i> Nueva Tarea</a>
            </div>
        </div>

        @include('agencia.tareas._nav')

        {{-- Navegación de mes --}}
        <div class="bs-card p-4 mb-6 flex items-center justify-between">
            <a href="{{ route('agencia.tareas.calendario', ['y' => $prev->year, 'm' => $prev->month]) }}" class="bs-btn-neutral"><i class="fas fa-chevron-left"></i> {{ $meses[$prev->month] }}</a>
            <h3 class="bs-display text-xl text-gray-800 m-0">{{ $meses[$month] }} {{ $year }}</h3>
            <a href="{{ route('agencia.tareas.calendario', ['y' => $next->year, 'm' => $next->month]) }}" class="bs-btn-neutral">{{ $meses[$next->month] }} <i class="fas fa-chevron-right"></i></a>
        </div>

        {{-- Grilla --}}
        <div class="bs-card overflow-hidden">
            <div class="grid grid-cols-7 bg-gray-900 text-white text-xs font-semibold">
                @foreach(['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $d)
                    <div class="px-2 py-2 text-center">{{ $d }}</div>
                @endforeach
            </div>
            <div class="grid grid-cols-7">
                @for($i = 0; $i < $offset; $i++)
                    <div class="min-h-[110px] border-b border-r border-gray-100 bg-gray-50"></div>
                @endfor
                @for($dia = 1; $dia <= $diasMes; $dia++)
                    @php $esHoy = $esMesHoy && $dia === (int) $hoy->day; @endphp
                    <div class="min-h-[110px] border-b border-r border-gray-100 p-1.5 align-top">
                        <div class="text-xs {{ $esHoy ? 'bg-brand-600 text-white' : 'text-gray-400' }} font-semibold w-6 h-6 flex items-center justify-center rounded-full mb-1">{{ $dia }}</div>
                        @foreach(($porDia[$dia] ?? []) as $t)
                            @php $m = $estadoMeta[$t->estado] ?? ['?','#6B7280','#F3F4F6']; @endphp
                            <div class="text-[11px] leading-tight rounded px-1.5 py-1 mb-1 truncate" style="background:{{ $m[2] }}; color:{{ $m[1] }};" title="{{ $t->titulo }}{{ $t->cliente ? ' · '.$t->cliente->nombre_proyecto : '' }} ({{ $m[0] }})">
                                {{ \Illuminate\Support\Str::limit($t->titulo, 22) }}
                            </div>
                        @endforeach
                    </div>
                @endfor
                @php $resto = (7 - (($offset + $diasMes) % 7)) % 7; @endphp
                @for($i = 0; $i < $resto; $i++)
                    <div class="min-h-[110px] border-b border-r border-gray-100 bg-gray-50"></div>
                @endfor
            </div>
        </div>

        {{-- Leyenda --}}
        <div class="flex flex-wrap gap-3 mt-4">
            @foreach($estadoMeta as $k => $m)
                <span class="text-xs flex items-center gap-1"><span class="w-3 h-3 rounded-full inline-block" style="background:{{ $m[1] }};"></span>{{ $m[0] }}</span>
            @endforeach
        </div>
    </div>
</div>
</x-app-layout>
