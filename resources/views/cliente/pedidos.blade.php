<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Mis Pedidos
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">Proximamente</h3>
                        <p class="text-gray-600">Aqui podras ver el historial de tus pedidos</p>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('cliente.dashboard') }}" class="text-blue-600 hover:text-blue-800">
                    &larr; Volver al Dashboard
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
