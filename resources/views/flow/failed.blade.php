<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Pago No Completado
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-md mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                <div class="mb-4">
                    <svg class="mx-auto h-16 w-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-red-600 mb-4">Pago No Completado</h2>
                <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
                    <p class="text-sm text-red-800">
                        El pago no pudo ser procesado correctamente.
                    </p>
                    @if(isset($payment['status']))
                    <p class="text-sm text-red-800 mt-2">
                        <strong>Estado:</strong>
                        @switch($payment['status'])
                        @case(1)
                        Pendiente
                        @break
                        @case(3)
                        Rechazado
                        @break
                        @case(4)
                        Anulado
                        @break
                        @default
                        Desconocido
                        @endswitch
                    </p>
                    @endif
                </div>
                <div class="space-y-3">
                    @if(auth()->check() && auth()->user()->role === 'admin')
                    <a href="{{ route('dashboard') }}"
                        class="block w-full bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300 transition-colors">
                        Volver al Dashboard
                    </a>
                    @else
                    <a href="{{ route('cliente.planes') }}"
                        class="block w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors font-semibold">
                        Intentar nuevamente
                    </a>
                    <a href="{{ route('cliente.dashboard') }}"
                        class="block w-full bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300 transition-colors">
                        Volver al Dashboard
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
