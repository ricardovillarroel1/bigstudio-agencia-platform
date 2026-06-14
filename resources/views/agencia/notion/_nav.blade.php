@php
    $pill = 'px-3 py-1.5 rounded-lg text-sm font-medium transition whitespace-nowrap';
    $on  = 'bg-brand-600 text-white';
    $off = 'bg-gray-100 text-gray-600 hover:bg-gray-200';
@endphp
<div class="flex flex-wrap items-center gap-2 mb-6">
    <a href="{{ route('agencia.notion') }}" class="{{ $pill }} {{ request()->routeIs('agencia.notion') ? $on : $off }}"><i class="fas fa-columns"></i> Tablero</a>
    <a href="{{ route('agencia.notion.por-cliente') }}" class="{{ $pill }} {{ request()->routeIs('agencia.notion.por-cliente') ? $on : $off }}"><i class="fas fa-users"></i> Por cliente</a>
    <a href="{{ route('agencia.notion.calendario') }}" class="{{ $pill }} {{ request()->routeIs('agencia.notion.calendario') ? $on : $off }}"><i class="far fa-calendar"></i> Calendario</a>
    <a href="{{ route('agencia.notion.clientes') }}" class="{{ $pill }} {{ request()->routeIs('agencia.notion.clientes*') ? $on : $off }}"><i class="fas fa-address-card"></i> Clientes</a>
</div>
