<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Error en el Pago
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

                <h2 class="text-2xl font-bold text-red-600 mb-4">Error en el Pago</h2>

                <p class="text-gray-600 mb-6">
                    Hubo un problema procesando tu pago.
                    Por favor, intenta nuevamente.
                </p>

                @if(session('error'))
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                    <p class="text-red-800">{{ session('error') }}</p>
                </div>
                @endif

                <div class="space-y-3">
                    <a href="{{ route('flow.payment-form') }}"
                        class="block w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                        Intentar nuevamente
                    </a>

                    <a href="{{ route('dashboard') }}"
                        class="block w-full bg-gray-200 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-300 transition-colors">
                        Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
