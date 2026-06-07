<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Pago Pendiente
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-md mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                <div class="mb-4">
                    <svg class="mx-auto h-16 w-16 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>

                <h2 class="text-2xl font-bold text-yellow-600 mb-4">Pago Pendiente</h2>

                <p class="text-gray-600 mb-6">
                    Tu pago esta siendo procesado.
                    Te notificaremos cuando se confirme.
                </p>

                @if(session('message'))
                <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                    <p class="text-yellow-800">{{ session('message') }}</p>
                </div>
                @endif

                <div class="space-y-3">
                    <a href="{{ route('dashboard') }}"
                        class="block w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                        Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
