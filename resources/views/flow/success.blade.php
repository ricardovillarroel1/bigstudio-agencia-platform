<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Pago Exitoso
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-md mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                <div class="mb-4">
                    <svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-green-600 mb-4">Pago Exitoso</h2>
                <p class="text-gray-600 mb-4">
                    Tu pago ha sido procesado correctamente.
                </p>
                @if(isset($payment))
                <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-6 text-left">
                    @if(isset($payment['flowOrder']))
                    <p class="text-sm text-green-800">
                        <strong>Orden Flow:</strong> {{ $payment['flowOrder'] }}
                    </p>
                    @endif
                    @if(isset($payment['amount']))
                    <p class="text-sm text-green-800">
                        <strong>Monto:</strong> ${{ number_format($payment['amount'] ?? 0, 0, ',', '.') }} CLP
                    </p>
                    @endif
                    <p class="text-sm text-green-800">
                        <strong>Estado:</strong> Pagado
                    </p>
                </div>
                @endif
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
                    <p class="text-sm text-blue-800">
                        Tu plan ha sido activado. Ya puedes configurar tus credenciales de integracion.
                    </p>
                </div>
                <div class="space-y-3">
                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('dashboard') }}"
                        class="block w-full bg-yellow-500 text-black py-2 px-4 rounded-md hover:bg-yellow-600 transition-colors font-semibold">
                        Ir al Panel de Administracion
                    </a>
                    @else
                    <a href="{{ route('cliente.estados-solicitud') }}"
                        class="block w-full bg-yellow-500 text-black py-2 px-4 rounded-md hover:bg-yellow-600 transition-colors font-semibold">
                        Configurar Credenciales
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

    {{-- Meta Pixel Purchase Event --}}
    @php
        $metaPixelId = \App\Models\Setting::get('meta_pixel_id');
    @endphp
    @if($metaPixelId && isset($payment) && isset($payment['status']) && $payment['status'] == 2)
    <script>
        // Ensure fbq is loaded (it should be from the layout component)
        if (typeof fbq !== 'undefined') {
            fbq('track', 'Purchase', {
                value: {{ $payment['amount'] ?? 0 }},
                currency: '{{ $payment['currency'] ?? 'CLP' }}',
                content_name: '{{ $payment['subject'] ?? 'Plan' }}',
                content_type: 'product',
                content_ids: ['{{ $payment['commerceOrder'] ?? '' }}'],
                order_id: '{{ $payment['commerceOrder'] ?? '' }}'
            });
            console.log('Meta Pixel: Purchase event fired', {
                value: {{ $payment['amount'] ?? 0 }},
                currency: '{{ $payment['currency'] ?? 'CLP' }}',
                content_name: '{{ $payment['subject'] ?? 'Plan' }}'
            });
        }
    </script>
    @endif
</x-app-layout>
