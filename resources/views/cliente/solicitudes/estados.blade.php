<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mis Solicitudes de Integración') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif
            
            @if($solicitudes->isEmpty())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <div class="text-6xl mb-4">📋</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">No tienes solicitudes</h3>
                    <p class="text-gray-600 mb-4">Aún no has solicitado ninguna integración</p>
                    <a href="{{ route('cliente.planes') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        Ver Planes Disponibles
                    </a>
                </div>
            </div>
            @else
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($solicitudes as $solicitud)
                        <div class="border rounded-lg overflow-hidden hover:shadow-md transition" x-data="{ 
                            open: false,
                            loading: false,
                            formData: {
                                tienda_shopify: '{{ old('tienda_shopify', $solicitud->tienda_shopify) }}',
                                access_token: '{{ old('access_token', $solicitud->access_token) }}',
                                api_secret: '{{ old('api_secret', $solicitud->api_secret) }}',
                                api_key: '{{ old('api_key', $solicitud->api_key) }}',
                                telefono: '{{ old('telefono', $solicitud->telefono) }}'
                            },
                            async submitForm() {
                                this.loading = true;
                                try {
                                    const response = await fetch('{{ route('cliente.solicitudes.updateConfig', $solicitud->id) }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json'
                                        },
                                        body: JSON.stringify(this.formData)
                                    });
                                    const data = await response.json();
                                    if (data.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: '¡Credenciales Guardadas!',
                                            text: 'Tus credenciales se han guardado exitosamente',
                                            confirmButtonColor: '#4F46E5',
                                            confirmButtonText: 'Entendido'
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: data.message || 'Error al guardar las credenciales',
                                            confirmButtonColor: '#4F46E5'
                                        });
                                    }
                                } catch (error) {
                                    console.error(error);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'Error al guardar las credenciales',
                                        confirmButtonColor: '#4F46E5'
                                    });
                                } finally {
                                    this.loading = false;
                                }
                            }
                        }">
                            <div class="p-6">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-3">
                                        <h3 class="text-lg font-bold text-gray-800">
                                            {{ $solicitud->plan->nombre }}
                                        </h3>
                                        @if($solicitud->estado === 'pendiente')
                                            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">
                                                ⏳ Pendiente de Aprobación
                                            </span>
                                        @elseif($solicitud->estado === 'aprobada')
                                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">
                                                ✅ Aprobada - Pendiente de Pago
                                            </span>
                                        @elseif($solicitud->estado === 'en_proceso')
                                            <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-semibold">
                                                💳 Pagada - Pendiente de Credenciales
                                            </span>
                                        @elseif($solicitud->estado === 'activa' && $solicitud->integracion_conectada)
                                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
                                                🚀 Conectada y Activa
                                            </span>
                                        @elseif($solicitud->estado === 'activa')
                                            <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-semibold">
                                                💳 Pagada - Pendiente de Credenciales
                                            </span>
                                        @elseif($solicitud->estado === 'rechazada')
                                            <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">
                                                ❌ Rechazada
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <p class="text-sm text-gray-600 mb-2">
                                        <span class="font-semibold">Empresa:</span> {{ $solicitud->plan->empresa->nombre }}
                                    </p>
                                    
                                    <p class="text-sm text-gray-600 mb-2">
                                        <span class="font-semibold">Precio:</span> ${{ number_format($solicitud->plan->precio, 0, ',', '.') }} {{ $solicitud->plan->moneda }} / mes
                                    </p>
                                    
                                    <p class="text-sm text-gray-500">
                                        <span class="font-semibold">Solicitado:</span> {{ $solicitud->created_at->format('d/m/Y H:i') }}
                                    </p>

                                    @if($solicitud->estado === 'rechazada' && $solicitud->notas_admin)
                                    <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded">
                                        <p class="text-sm text-red-800">
                                            <span class="font-semibold">Motivo de rechazo:</span> {{ $solicitud->notas_admin }}
                                        </p>
                                    </div>
                                    @endif
                                    
                                    @if($solicitud->tienda_shopify)
                                    <p class="text-sm text-gray-600 mt-2">
                                        <span class="font-semibold">Tienda Shopify:</span> {{ $solicitud->tienda_shopify }}
                                    </p>
                                    @endif
                                </div>

                                    <div class="ml-4">
                                        @if($solicitud->estado === 'activa' && $solicitud->integracion_conectada)
                                        <div class="text-center">
                                            <div class="text-4xl mb-2">✅</div>
                                            <p class="text-xs text-green-600 font-semibold">Activa</p>
                                        </div>
                                        @elseif($solicitud->estado === 'aprobada' && !$solicitud->fecha_pago)
                                        <a href="{{ route('flow.payment-form') }}" 
                                           class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                            Pagar Plan
                                        </a>
                                        @endif
                                    </div>
                                </div>

                                <!-- Flujo de estados -->
                                <div class="mt-6 pt-4 border-t">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 text-center">
                                        <div class="w-8 h-8 mx-auto rounded-full flex items-center justify-center {{ $solicitud->estado !== 'rechazada' ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-600' }}">
                                            ✓
                                        </div>
                                        <p class="text-xs mt-1 font-semibold">Solicitado</p>
                                    </div>
                                    <div class="flex-1 h-1 {{ in_array($solicitud->estado, ['aprobada', 'en_proceso', 'activa']) ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                                    <div class="flex-1 text-center">
                                        <div class="w-8 h-8 mx-auto rounded-full flex items-center justify-center {{ in_array($solicitud->estado, ['aprobada', 'en_proceso', 'activa']) ? 'bg-green-500 text-white' : ($solicitud->estado === 'rechazada' ? 'bg-red-500 text-white' : 'bg-gray-300 text-gray-600') }}">
                                            {{ $solicitud->estado === 'rechazada' ? '✗' : '✓' }}
                                        </div>
                                        <p class="text-xs mt-1 font-semibold">{{ $solicitud->estado === 'rechazada' ? 'Rechazada' : 'Aprobada' }}</p>
                                    </div>
                                    @if($solicitud->estado !== 'rechazada')
                                    <div class="flex-1 h-1 {{ in_array($solicitud->estado, ['en_proceso', 'activa']) ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                                    <div class="flex-1 text-center">
                                        <div class="w-8 h-8 mx-auto rounded-full flex items-center justify-center {{ in_array($solicitud->estado, ['en_proceso', 'activa']) ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-600' }}">
                                            {{ in_array($solicitud->estado, ['en_proceso', 'activa']) ? '✓' : '?' }}
                                        </div>
                                        <p class="text-xs mt-1 font-semibold">Pagada</p>
                                    </div>
                                    <div class="flex-1 h-1 {{ ($solicitud->estado === 'activa' && $solicitud->integracion_conectada) ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                                    <div class="flex-1 text-center">
                                        <div class="w-8 h-8 mx-auto rounded-full flex items-center justify-center {{ ($solicitud->estado === 'activa' && $solicitud->integracion_conectada) ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-600' }}">
                                            {{ ($solicitud->estado === 'activa' && $solicitud->integracion_conectada) ? '✓' : '?' }}
                                        </div>
                                        <p class="text-xs mt-1 font-semibold">Conectada</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            </div>

                            <!-- Barra expandible para credenciales -->
                            @if(($solicitud->estado === 'en_proceso' || ($solicitud->estado === 'activa' && !$solicitud->integracion_conectada)) || $solicitud->tieneCredencialesCompletas())
                            <div class="border-t bg-gray-50">
                                <button 
                                    @click="open = !open"
                                    class="w-full px-6 py-3 flex items-center justify-between hover:bg-gray-100 transition"
                                >
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-gray-700">
                                            @if($solicitud->tieneCredencialesCompletas())
                                                🔐 Ver/Editar Credenciales
                                            @else
                                                🔐 Configurar Credenciales
                                            @endif
                                        </span>
                                        @if($solicitud->tieneCredencialesCompletas())
                                            <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full">✓ Completas</span>
                                        @else
                                            <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded-full">⏳ Pendiente</span>
                                        @endif
                                    </div>
                                    <svg 
                                        class="w-5 h-5 text-gray-500 transition-transform duration-200"
                                        :class="{ 'rotate-180': open }"
                                        fill="none" 
                                        stroke="currentColor" 
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>

                                <!-- Panel expandible con formulario -->
                                <div 
                                    x-show="open" 
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform -translate-y-2"
                                    x-transition:enter-end="opacity-100 transform translate-y-0"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 transform translate-y-0"
                                    x-transition:leave-end="opacity-0 transform -translate-y-2"
                                    class="px-6 pb-6 bg-white border-t"
                                    style="display: none;"
                                >
                                    @if($solicitud->integracion_conectada)
                                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mt-4">
                                            <p class="text-green-800 font-semibold">
                                                🎉 ¡Integración conectada exitosamente!
                                            </p>
                                            <p class="text-sm text-green-700 mt-1">
                                                Conectada el {{ $solicitud->fecha_conexion->format('d/m/Y H:i') }}
                                            </p>
                                        </div>
                                    @else
                                        <div class="mt-4">
                                            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                                                <p class="text-sm text-blue-700">
                                                    <strong>✨ Nuevo:</strong> Conecta tu tienda con OAuth 2.0 en solo 2 clicks, o ingresa credenciales manualmente.
                                                </p>
                                            </div>

                                            <!-- FORMULARIO OAUTH (PRINCIPAL) -->
                                            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 border-2 border-indigo-200 rounded-lg p-5 mb-6">
                                                <div class="flex items-center gap-2 mb-3">
                                                    <span class="text-2xl">🔗</span>
                                                    <h5 class="text-lg font-bold text-indigo-700">Conexión OAuth 2.0 (Recomendado)</h5>
                                                </div>
                                                <p class="text-sm text-gray-700 mb-4">
                                                    Autoriza tu tienda Shopify de forma segura sin copiar tokens manualmente.
                                                </p>

                                                <form action="{{ route('cliente.shopify.oauth.iniciar') }}" method="POST" class="space-y-4">
                                                    @csrf
                                                    <input type="hidden" name="solicitud_id" value="{{ $solicitud->id }}">

                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                                            URL de tu tienda Shopify *
                                                        </label>
                                                        <input 
                                                            type="text" 
                                                            name="shop_url"
                                                            value="{{ old('shop_url', $solicitud->tienda_shopify) }}"
                                                            placeholder="tu-tienda.myshopify.com"
                                                            pattern="[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com"
                                                            required
                                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                        >
                                                        <p class="mt-1 text-xs text-gray-500">Ejemplo: mi-tienda.myshopify.com</p>
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                                            Lioren API Key *
                                                        </label>
                                                        <input 
                                                            type="password" 
                                                            name="lioren_api_key"
                                                            value="{{ old('lioren_api_key', $solicitud->api_key) }}"
                                                            placeholder="Tu API Key de Lioren"
                                                            minlength="10"
                                                            required
                                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                        >
                                                        <p class="mt-1 text-xs text-gray-500">Token de autenticación de la API de Lioren</p>
                                                    </div>

                                                    <button 
                                                        type="submit"
                                                        class="w-full px-6 py-3 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition inline-flex items-center justify-center gap-2"
                                                    >
                                                        <span class="text-xl">🔗</span>
                                                        Conectar con Shopify OAuth
                                                    </button>
                                                </form>
                                            </div>

                                            <!-- SEPARADOR -->
                                            <div class="relative my-6">
                                                <div class="absolute inset-0 flex items-center">
                                                    <div class="w-full border-t border-gray-300"></div>
                                                </div>
                                                <div class="relative flex justify-center text-sm">
                                                    <span class="px-4 bg-white text-gray-500">o ingresa credenciales manualmente</span>
                                                </div>
                                            </div>

                                            <!-- FORMULARIO MANUAL (LEGACY) -->
                                            <details class="border border-gray-300 rounded-lg">
                                                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 text-sm font-medium text-gray-700 flex items-center justify-between">
                                                    <span>📝 ¿Tienes credenciales de una Custom App antigua?</span>
                                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </summary>

                                                <div class="px-4 pb-4 pt-2 border-t bg-gray-50">
                                                    <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-4">
                                                        <p class="text-xs text-yellow-800">
                                                            ⚠️ <strong>Nota:</strong> Este método solo funciona con Custom Apps creadas antes de enero 2026. Para nuevas integraciones, usa OAuth arriba.
                                                        </p>
                                                    </div>

                                                    <form @submit.prevent="submitForm" class="space-y-4">
                                                        <!-- Shopify Section -->
                                                        <div class="border-t pt-4">
                                                            <h5 class="text-md font-bold text-indigo-600 mb-3">📦 Credenciales de Shopify</h5>

                                                            <div class="mb-4">
                                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                                    Nombre de Tienda *
                                                                </label>
                                                                <input 
                                                                    type="text" 
                                                                    x-model="formData.tienda_shopify"
                                                                    placeholder="tu-tienda.myshopify.com"
                                                                    pattern="[a-zA-Z0-9\-]+\.myshopify\.com"
                                                                    required
                                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                                >
                                                                <p class="mt-1 text-xs text-gray-500">Formato: tu-tienda.myshopify.com</p>
                                                            </div>

                                                            <div class="mb-4">
                                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                                    Access Token *
                                                                </label>
                                                                <input 
                                                                    type="password" 
                                                                    x-model="formData.access_token"
                                                                    placeholder="shpat_xxxxxxxxxxxxx"
                                                                    minlength="20"
                                                                    required
                                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                                >
                                                                <p class="mt-1 text-xs text-gray-500">Token de API de tu app personalizada de Shopify</p>
                                                            </div>

                                                            <div class="mb-4">
                                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                                    API Secret (para webhooks) *
                                                                </label>
                                                                <input 
                                                                    type="password" 
                                                                    x-model="formData.api_secret"
                                                                    placeholder="shpss_xxxxxxxxxxxxx"
                                                                    minlength="20"
                                                                    required
                                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                                >
                                                                <p class="mt-1 text-xs text-gray-500">Secret key para validar webhooks de Shopify</p>
                                                            </div>
                                                        </div>

                                                        <!-- Lioren Section -->
                                                        <div class="border-t pt-4">
                                                            <h5 class="text-md font-bold text-indigo-600 mb-3">🏪 Credenciales de Lioren</h5>

                                                            <div class="mb-4">
                                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                                    API Key (Bearer Token) *
                                                                </label>
                                                                <input 
                                                                    type="password" 
                                                                    x-model="formData.api_key"
                                                                    placeholder="tu_api_key_de_lioren"
                                                                    minlength="10"
                                                                    required
                                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                                >
                                                                <p class="mt-1 text-xs text-gray-500">Token de autenticación de la API de Lioren</p>
                                                            </div>

                                                            <div class="mb-4">
                                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                                    Teléfono de Contacto (opcional)
                                                                </label>
                                                                <input 
                                                                    type="text" 
                                                                    x-model="formData.telefono"
                                                                    placeholder="+56 9 1234 5678"
                                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                                >
                                                            </div>
                                                        </div>

                                                        <div class="flex justify-end gap-4 pt-4 border-t border-gray-200">
                                                            <button 
                                                                type="submit"
                                                                :disabled="loading"
                                                                class="px-6 py-2 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition disabled:opacity-50 text-sm inline-flex items-center gap-2"
                                                            >
                                                                <span x-show="!loading" class="inline-flex items-center gap-2">
                                                                    <i class="fas fa-save"></i>
                                                                    Guardar Manualmente
                                                                </span>
                                                                <span x-show="loading" class="inline-flex items-center gap-2">
                                                                    <i class="fas fa-spinner fa-spin"></i>
                                                                    Guardando...
                                                                </span>
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </details>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
