@php
    $vence = request('vence');
    $enTabla = request()->routeIs('agencia.tareas') && !$vence;
    $pill = 'px-3 py-1.5 rounded-lg text-sm font-medium transition whitespace-nowrap';
    $on  = 'bg-brand-600 text-white';
    $off = 'bg-gray-100 text-gray-600 hover:bg-gray-200';
@endphp
<div class="flex flex-wrap items-center gap-2 mb-6">
    <a href="{{ route('agencia.tareas') }}" class="{{ $pill }} {{ $enTabla ? $on : $off }}"><i class="fas fa-table"></i> Tabla</a>
    <a href="{{ route('agencia.tareas.tablero') }}" class="{{ $pill }} {{ request()->routeIs('agencia.tareas.tablero') ? $on : $off }}"><i class="fas fa-columns"></i> Tablero</a>
    <a href="{{ route('agencia.tareas.cliente') }}" class="{{ $pill }} {{ request()->routeIs('agencia.tareas.cliente') ? $on : $off }}"><i class="fas fa-users"></i> Por cliente</a>
    <a href="{{ route('agencia.tareas.calendario') }}" class="{{ $pill }} {{ request()->routeIs('agencia.tareas.calendario') ? $on : $off }}"><i class="far fa-calendar"></i> Calendario</a>
    <span class="mx-1 text-gray-300 hidden sm:inline">|</span>
    <a href="{{ route('agencia.tareas', ['vence' => 'semana']) }}" class="{{ $pill }} {{ ($enTabla === false && $vence === 'semana') ? $on : $off }}">⏰ Esta semana</a>
    <a href="{{ route('agencia.tareas', ['vence' => 'atrasadas']) }}" class="{{ $pill }} {{ $vence === 'atrasadas' ? $on : $off }}">🔴 Atrasadas</a>
</div>
