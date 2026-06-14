<x-app-layout>
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="bs-card overflow-hidden mb-6">
            <div class="px-6 py-5 flex items-center justify-between flex-wrap gap-3" style="background: linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%);">
                <div>
                    <h2 class="bs-display text-2xl text-white m-0 leading-tight">Clientes</h2>
                    <p class="text-sm text-white/90 mt-1 mb-0"><i class="fas fa-bolt"></i> Fichas sincronizadas con Notion</p>
                </div>
            </div>
        </div>

        @include('agencia.notion._nav')

        @if($error ?? null)<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ $error }}</div>@endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($clientes as $c)
                <a href="{{ route('agencia.notion.clientes.ver', $c['id']) }}" class="bs-card p-5 block hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold text-gray-800 m-0">{{ $c['nombre'] ?: '(sin nombre)' }}</h3>
                        @if($c['estado'])<span class="text-[11px] rounded-full px-2 py-0.5 bg-gray-100 text-gray-600">{{ $c['estado'] }}</span>@endif
                    </div>
                    @if($c['rubro'])<p class="text-xs text-gray-500 mt-0 mb-2">{{ $c['rubro'] }}</p>@endif
                    @if(!empty($c['servicios']))
                        <div class="flex flex-wrap gap-1 mb-2">
                            @foreach($c['servicios'] as $s)<span class="text-[10px] rounded px-1.5 py-0.5 bg-orange-50 text-orange-700">{{ $s }}</span>@endforeach
                        </div>
                    @endif
                    <div class="text-xs text-gray-500 space-y-0.5">
                        @if($c['sitio_web'])<div class="truncate"><i class="fas fa-globe text-gray-400"></i> {{ \Illuminate\Support\Str::after($c['sitio_web'], '://') }}</div>@endif
                        @if($c['email'])<div class="truncate"><i class="fas fa-envelope text-gray-400"></i> {{ $c['email'] }}</div>@endif
                        @if($c['telefono'])<div><i class="fas fa-phone text-gray-400"></i> {{ $c['telefono'] }}</div>@endif
                    </div>
                    <div class="mt-3 text-xs text-brand-600 font-medium">Ver ficha <i class="fas fa-arrow-right"></i></div>
                </a>
            @empty
                <div class="bs-card p-10 text-center text-gray-400 md:col-span-3">No hay clientes.</div>
            @endforelse
        </div>
    </div>
</div>
</x-app-layout>
