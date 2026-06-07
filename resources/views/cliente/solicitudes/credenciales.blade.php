<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Configurar Credenciales de Integración') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-2xl font-bold text-gray-800 mb-4">🔐 Credenciales de Integración</h3>
                    <p class="text-gray-600 mb-4">
                        Para completar la activación de tu plan, necesitamos que ingreses las credenciales de tu tienda Shopify y tu cuenta Lioren.
                        Una vez que las ingreses, nuestro equipo procederá con la conexión.
                    </p>

                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <span class="text-2xl">ℹ️</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Importante:</strong> Asegúrate de tener acceso a tu panel de administración de Shopify y tu cuenta de Lioren para obtener estas credenciales.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @forelse($solicitudes as $solicitud)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h4 class="text-lg font-bold text-gray-800">
                                    {{ $solicitud->plan->nombre }}
                                </h4>
                                <p class="text-sm text-gray-600">
                                    {{ $solicitud->plan->empresa->nombre }}
                                </p>
                            </div>
                            <div>
                                @if($solicitud->tieneCredencialesCompletas())
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        ✅ Credenciales Completas
                                    </span>
                                @else
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        ⏳ Pendiente
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if($solicitud->integracion_conectada)
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <p class="text-green-800 font-semibold">
                                    🎉 ¡Integración conectada exitosamente!
                                </p>
                                <p class="text-sm text-green-700 mt-1">
                                    Conectada el {{ $solicitud->fecha_conexion->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        @else
                            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                                <p class="text-sm text-blue-700">
                                    <strong>✨ Nuevo:</strong> Conecta tu tienda con OAuth 2.0 en solo 2 clicks, o ingresa credenciales manualmente.
                                </p>
                            </div>

                            <!-- FORMULARIO OAUTH (PRINCIPAL) -->
                            <div class="bg-gradient-to-r from-brand-50 to-brand-50 border-2 border-brand-200 rounded-lg p-5 mb-6">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="text-2xl">🔗</span>
                                    <h5 class="text-lg font-bold text-brand-700">Conexión OAuth 2.0 (Recomendado)</h5>
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
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
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
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                                        >
                                        <p class="mt-1 text-xs text-gray-500">Token de autenticación de la API de Lioren</p>
                                    </div>

                                    <button 
                                        type="submit"
                                        class="w-full px-6 py-3 bg-brand-600 text-white font-bold rounded-lg hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition inline-flex items-center justify-center gap-2"
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

                                    <form action="{{ route('cliente.solicitudes.guardar-credenciales', $solicitud) }}" method="POST" class="space-y-4">
                                        @csrf
                                        @method('PUT')

                                        <!-- Shopify Section -->
                                        <div class="border-t pt-4">
                                            <h5 class="text-md font-bold text-brand-600 mb-3">📦 Credenciales de Shopify</h5>

                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Nombre de Tienda *
                                                </label>
                                                <input 
                                                    type="text" 
                                                    name="tienda_shopify"
                                                    value="{{ old('tienda_shopify', $solicitud->tienda_shopify) }}"
                                                    placeholder="tu-tienda.myshopify.com"
                                                    pattern="[a-zA-Z0-9\-]+\.myshopify\.com"
                                                    required
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                                                >
                                                <p class="mt-1 text-xs text-gray-500">Formato: tu-tienda.myshopify.com</p>
                                            </div>

                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Access Token *
                                                </label>
                                                <input 
                                                    type="password" 
                                                    name="access_token"
                                                    value="{{ old('access_token', $solicitud->access_token) }}"
                                                    placeholder="shpat_xxxxxxxxxxxxx"
                                                    minlength="20"
                                                    required
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                                                >
                                                <p class="mt-1 text-xs text-gray-500">Token de API de tu app personalizada de Shopify</p>
                                            </div>

                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    API Secret (para webhooks) *
                                                </label>
                                                <input 
                                                    type="password" 
                                                    name="api_secret"
                                                    value="{{ old('api_secret', $solicitud->api_secret) }}"
                                                    placeholder="shpss_xxxxxxxxxxxxx"
                                                    minlength="20"
                                                    required
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                                                >
                                                <p class="mt-1 text-xs text-gray-500">Secret key para validar webhooks de Shopify</p>
                                            </div>
                                        </div>

                                        <!-- Lioren Section -->
                                        <div class="border-t pt-4">
                                            <h5 class="text-md font-bold text-brand-600 mb-3">🏪 Credenciales de Lioren</h5>

                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    API Key (Bearer Token) *
                                                </label>
                                                <input 
                                                    type="password" 
                                                    name="api_key"
                                                    value="{{ old('api_key', $solicitud->api_key) }}"
                                                    placeholder="tu_api_key_de_lioren"
                                                    minlength="10"
                                                    required
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                                                >
                                                <p class="mt-1 text-xs text-gray-500">Token de autenticación de la API de Lioren</p>
                                            </div>

                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                                    Teléfono de Contacto (opcional)
                                                </label>
                                                <input 
                                                    type="text" 
                                                    name="telefono"
                                                    value="{{ old('telefono', $solicitud->telefono) }}"
                                                    placeholder="+56 9 1234 5678"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                                                >
                                            </div>
                                        </div>

                                        <div class="flex justify-end gap-4 pt-4 border-t border-gray-200">
                                            <button 
                                                type="submit"
                                                class="px-6 py-2 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition text-sm"
                                            >
                                                💾 Guardar Manualmente
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </details>
                        @endif
                    </div>
                </div>
            @empty
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <p class="text-gray-600">No tienes solicitudes pendientes de configuración.</p>
                        <a href="{{ route('cliente.planes') }}" class="mt-4 inline-block text-brand-600 hover:text-brand-800 font-semibold">
                            Ver Planes Disponibles →
                        </a>
                    </div>
                </div>
            @endforelse

            <!-- Ayuda -->
            <div class="bg-gray-50 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6">
                    <h4 class="text-lg font-bold text-gray-800 mb-3">❓ ¿Necesitas ayuda?</h4>
                    <div class="space-y-2 text-sm text-gray-600">
                        <p><strong>Método recomendado - OAuth 2.0:</strong></p>
                        <ol class="list-decimal list-inside ml-4 space-y-1">
                            <li>Ingresa la URL de tu tienda (ejemplo: mi-tienda.myshopify.com)</li>
                            <li>Ingresa tu API Key de Lioren</li>
                            <li>Haz clic en "Conectar con Shopify OAuth"</li>
                            <li>Autoriza la app en Shopify</li>
                            <li>¡Listo! La conexión se completará automáticamente</li>
                        </ol>
                        <p class="mt-3"><strong>Para obtener tu API Key de Lioren:</strong></p>
                        <ol class="list-decimal list-inside ml-4 space-y-1">
                            <li>Ingresa a tu cuenta de Lioren</li>
                            <li>Ve a Configuración → API</li>
                            <li>Copia tu Bearer Token</li>
                        </ol>
                        <p class="mt-3 text-xs text-gray-500">
                            <strong>Nota:</strong> El método manual solo está disponible para Custom Apps creadas antes de enero 2026.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
