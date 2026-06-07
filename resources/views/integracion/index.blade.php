<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Configurar Integración Shopify - Lioren') }}
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

            <div class="mb-6">
                <a href="{{ route('integracion.dashboard') }}" class="text-brand-600 hover:text-brand-900">
                    &larr; Volver al Dashboard
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Integración Shopify - Lioren</h1>
                    <p class="text-gray-600 mb-6">Configuración de integración - Panel de Administración</p>

                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Importante:</strong> Este módulo creará webhooks automáticamente y sincronizará productos. Asegúrate de tener las credenciales correctas.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- ============================================ --}}
                    {{-- SECCIÓN 1: CONEXIÓN DE CREDENCIALES (OAuth)  --}}
                    {{-- ============================================ --}}

                    <div class="bg-gradient-to-r from-brand-50 to-brand-50 border-2 border-brand-200 rounded-lg p-5 mb-6">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                            <h3 class="text-lg font-bold text-brand-700">Conexión OAuth 2.0 (Recomendado)</h3>
                        </div>
                        <p class="text-sm text-gray-700 mb-4">
                            Autoriza la tienda Shopify de forma segura sin copiar tokens manualmente. Requiere una app registrada en Shopify Partners.
                        </p>

                        <form action="{{ route('admin.shopify.oauth.iniciar') }}" method="POST" class="space-y-4">
                            @csrf

                            <div class="bg-gradient-to-r from-brand-50 to-red-50 border-2 border-brand-200 rounded-lg p-4 mb-4">
                                <h4 class="text-md font-bold text-brand-700 mb-3">
                                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    Datos del Cliente
                                </h4>

                                <!-- Toggle: Nuevo vs Existente -->
                                <div class="flex items-center gap-4 mb-4 p-3 bg-white rounded-lg border border-brand-200">
                                    <span class="text-sm font-medium text-gray-700">Tipo de cliente:</span>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="client_type" value="new" checked
                                            class="w-4 h-4 text-brand-600 border-gray-300 focus:ring-brand-500"
                                            onchange="toggleClientType('new')">
                                        <span class="ml-2 text-sm font-medium text-gray-700">Crear nuevo</span>
                                    </label>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="client_type" value="existing"
                                            class="w-4 h-4 text-brand-600 border-gray-300 focus:ring-brand-500"
                                            onchange="toggleClientType('existing')">
                                        <span class="ml-2 text-sm font-medium text-gray-700">Cliente existente</span>
                                    </label>
                                </div>

                                <!-- Sección: Cliente Nuevo -->
                                <div id="new-client-section">
                                    <p class="text-xs text-gray-600 mb-3">Se creará automáticamente una cuenta para que el cliente pueda acceder a su panel.</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del cliente *</label>
                                            <input type="text" name="cliente_nombre" id="input_cliente_nombre" value="{{ old('cliente_nombre') }}" placeholder="Nombre completo"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Email del cliente *</label>
                                            <input type="email" name="cliente_email" id="input_cliente_email" value="{{ old('cliente_email') }}" placeholder="cliente@ejemplo.com"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña *</label>
                                            <input type="password" name="cliente_password" id="input_cliente_password" placeholder="Mínimo 8 caracteres" minlength="8"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Plan a asignar *</label>
                                            <select name="plan_id" id="input_plan_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                                @foreach(\App\Models\Plan::all() as $planItem)
                                                    <option value="{{ $planItem->id }}">{{ $planItem->nombre }} - {{ $planItem->precio == 0 ? "GRATIS" : "$" . number_format($planItem->precio, 0, ",", ".") . " CLP" }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sección: Cliente Existente -->
                                <div id="existing-client-section" style="display: none;">
                                    <p class="text-xs text-gray-600 mb-3">Selecciona un cliente que ya tiene cuenta en el sistema para configurar su integración.</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Seleccionar cliente *</label>
                                            <select name="existing_user_id" id="input_existing_user_id"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                                                onchange="updateExistingClientInfo()">
                                                <option value="">-- Seleccionar cliente --</option>
                                                @foreach(\App\Models\User::where('role', 'cliente')->orderBy('name')->get() as $existingClient)
                                                    <option value="{{ $existingClient->id }}"
                                                        data-name="{{ $existingClient->name }}"
                                                        data-email="{{ $existingClient->email }}"
                                                        data-has-config="{{ $existingClient->integracionConfig ? '1' : '0' }}"
                                                        data-has-sub="{{ $existingClient->suscripciones()->where('estado', 'activa')->exists() ? '1' : '0' }}"
                                                        data-shop="{{ $existingClient->integracionConfig->shop_domain ?? '' }}"
                                                        data-lioren="{{ $existingClient->integracionConfig->lioren_api_key ?? '' }}"
                                                        data-shopify-client-id="{{ $existingClient->integracionConfig->shopify_client_id ?? '' }}"
                                                        data-shopify-client-secret="{{ $existingClient->integracionConfig->shopify_client_secret ?? '' }}">
                                                        {{ $existingClient->name }} ({{ $existingClient->email }})
                                                        @if($existingClient->integracionConfig)
                                                            - Ya tiene integración
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div id="existing-client-info" style="display: none;" class="md:col-span-2">
                                            <div id="existing-client-warning" style="display: none;" class="p-3 bg-yellow-50 border border-yellow-300 rounded-lg mb-3">
                                                <p class="text-sm text-yellow-800 font-semibold">
                                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>
                                                    Este cliente ya tiene una integración configurada. Se actualizarán sus credenciales.
                                                </p>
                                            </div>
                                            <div class="p-3 bg-green-50 border border-green-300 rounded-lg">
                                                <p class="text-sm text-green-800">
                                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                    Cliente: <strong id="existing-client-name-display"></strong> (<span id="existing-client-email-display"></span>)
                                                </p>
                                            </div>
                                        </div>
                                        <div id="existing-plan-section">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Plan a asignar *</label>
                                            <select name="existing_plan_id" id="input_existing_plan_id"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                                @foreach(\App\Models\Plan::all() as $planItem)
                                                    <option value="{{ $planItem->id }}">{{ $planItem->nombre }} - {{ $planItem->precio == 0 ? "GRATIS" : "$" . number_format($planItem->precio, 0, ",", ".") . " CLP" }}</option>
                                                @endforeach
                                            </select>
                                            <p class="text-xs text-gray-500 mt-1" id="existing-sub-note" style="display: none;">Este cliente ya tiene una suscripción activa. Se mantendrá la existente.</p>
                                        </div>
                                    </div>

                                    {{-- Sección Reconectar: aparece cuando el cliente ya tiene integración --}}
                                    <div id="reconnect-section" style="display: none;" class="md:col-span-2 mt-3">
                                        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-300 rounded-lg p-5">
                                            <div class="flex items-start gap-3">
                                                <div class="flex-shrink-0">
                                                    <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                </div>
                                                <div class="flex-1">
                                                    <h4 class="text-md font-bold text-amber-800 mb-1">Reconectar Cliente Existente</h4>
                                                    <p class="text-sm text-amber-700 mb-2">
                                                        Este cliente ya tiene credenciales guardadas. Puedes reconectar directamente sin ingresar nuevamente la URL de Shopify ni la API Key de Lioren.
                                                    </p>
                                                    <div class="bg-white rounded-lg p-3 mb-3 border border-amber-200">
                                                        <p class="text-sm text-gray-700"><strong>Tienda:</strong> <span id="reconnect-shop"></span></p>
                                                        <p class="text-sm text-gray-700"><strong>API Key Lioren:</strong> <span id="reconnect-lioren"></span></p>
                                                    </div>
                                                    <p class="text-xs text-amber-600 mb-3">Esto renovará el token de acceso y actualizará los webhooks. No se creará una nueva suscripción.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                            function toggleClientType(type) {
                                const newSection = document.getElementById('new-client-section');
                                const existingSection = document.getElementById('existing-client-section');

                                if (type === 'new') {
                                    newSection.style.display = '';
                                    existingSection.style.display = 'none';
                                    // Enable new fields, disable existing
                                    document.getElementById('input_cliente_nombre').required = true;
                                    document.getElementById('input_cliente_email').required = true;
                                    document.getElementById('input_cliente_password').required = true;
                                    document.getElementById('input_existing_user_id').required = false;
                                } else {
                                    newSection.style.display = 'none';
                                    existingSection.style.display = '';
                                    // Disable new fields, enable existing
                                    document.getElementById('input_cliente_nombre').required = false;
                                    document.getElementById('input_cliente_email').required = false;
                                    document.getElementById('input_cliente_password').required = false;
                                    document.getElementById('input_existing_user_id').required = true;
                                }
                            }

                            function updateExistingClientInfo() {
                                const select = document.getElementById('input_existing_user_id');
                                const option = select.options[select.selectedIndex];
                                const infoDiv = document.getElementById('existing-client-info');
                                const warningDiv = document.getElementById('existing-client-warning');
                                const subNote = document.getElementById('existing-sub-note');
                                const reconnectSection = document.getElementById('reconnect-section');
                                const mainFormFields = document.getElementById('main-oauth-fields');
                                const mainSubmitBtn = document.getElementById('main-oauth-submit');

                                if (select.value) {
                                    infoDiv.style.display = '';
                                    document.getElementById('existing-client-name-display').textContent = option.dataset.name;
                                    document.getElementById('existing-client-email-display').textContent = option.dataset.email;

                                    if (option.dataset.hasConfig === '1') {
                                        warningDiv.style.display = '';
                                    } else {
                                        warningDiv.style.display = 'none';
                                    }

                                    if (option.dataset.hasSub === '1') {
                                        subNote.style.display = '';
                                    } else {
                                        subNote.style.display = 'none';
                                    }

                                    // Si tiene config con shop y lioren, mostrar opción de reconectar
                                    const reconnectForm = document.getElementById('reconnect-form');
                                    if (option.dataset.hasConfig === '1' && option.dataset.shop) {
                                        reconnectSection.style.display = '';
                                        document.getElementById('reconnect-shop').textContent = option.dataset.shop;
                                        const liorenKey = option.dataset.lioren || '';
                                        document.getElementById('reconnect-lioren').textContent = liorenKey ? liorenKey.substring(0, 8) + '...' : 'No configurada';
                                        document.getElementById('reconnect-user-id').value = select.value;
                                        // Pasar credenciales de Shopify al form de reconexión
                                        document.getElementById('reconnect-shopify-client-id').value = option.dataset.shopifyClientId || '';
                                        document.getElementById('reconnect-shopify-client-secret').value = option.dataset.shopifyClientSecret || '';
                                        // Ocultar los campos del formulario principal y mostrar el form de reconexión
                                        if (mainFormFields) mainFormFields.style.display = 'none';
                                        if (reconnectForm) reconnectForm.style.display = '';
                                    } else {
                                        reconnectSection.style.display = 'none';
                                        if (mainFormFields) mainFormFields.style.display = '';
                                        if (reconnectForm) reconnectForm.style.display = 'none';
                                    }
                                } else {
                                    infoDiv.style.display = 'none';
                                    reconnectSection.style.display = 'none';
                                    if (mainFormFields) mainFormFields.style.display = '';
                                    const reconnectForm = document.getElementById('reconnect-form');
                                    if (reconnectForm) reconnectForm.style.display = 'none';
                                }
                            }

                            // Initialize on page load
                            document.addEventListener('DOMContentLoaded', function() {
                                toggleClientType('new');
                            });
                            </script>
                            <div id="main-oauth-fields">

                            {{-- Credenciales de la App Shopify (por cliente) --}}
                            <div class="bg-gradient-to-r from-gray-50 to-slate-50 border-2 border-gray-300 rounded-lg p-4 mb-4">
                                <h4 class="text-md font-bold text-gray-700 mb-2">
                                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                    Credenciales de la App Shopify
                                </h4>
                                <p class="text-xs text-gray-500 mb-3">Cada cliente requiere su propia app en el Dev Dashboard de Shopify. Ingresa el Client ID y Secret de la app creada para este cliente.</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Shopify Client ID *</label>
                                        <input type="text" name="shopify_client_id" value="{{ old('shopify_client_id') }}" placeholder="Ej: 6d1c5fa2d80b3dc9ad28c0b80d5ec44d" required
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 font-mono text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Shopify Client Secret *</label>
                                        <input type="password" name="shopify_client_secret" value="{{ old('shopify_client_secret') }}" placeholder="shpss_xxxxxxxxxxxxx" required
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 font-mono text-sm">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    URL de la tienda Shopify *
                                </label>
                                <input 
                                    type="text" 
                                    name="shop_url"
                                    value="{{ old('shop_url') }}"
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
                                    value="{{ old('lioren_api_key') }}"
                                    placeholder="Tu API Key de Lioren"
                                    minlength="10"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                                >
                                <p class="mt-1 text-xs text-gray-500">Token de autenticación de la API de Lioren (Bearer Token)</p>
                            </div>

                            {{-- Opciones de Facturación dentro del OAuth --}}
                            <div class="mt-4 pt-4 border-t border-brand-200">
                                <h4 class="text-md font-bold text-brand-700 mb-3">Opciones de Facturación</h4>

                                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-lg p-4 mb-3">
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input id="oauth_facturacion_enabled" name="facturacion_enabled" type="checkbox" value="1"
                                                class="w-5 h-5 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500 focus:ring-2">
                                        </div>
                                        <div class="ml-4 text-sm">
                                            <label for="oauth_facturacion_enabled" class="font-bold text-gray-900 cursor-pointer">
                                                Habilitar emisión de facturas electrónicas
                                            </label>
                                            <p class="text-gray-700 mt-1">
                                                Al activar esta opción, el sistema podrá procesar tanto <strong>boletas</strong> como <strong>facturas</strong> según lo que elija cada cliente en el checkout de Shopify.
                                            </p>
                                            <div class="mt-2 p-2 bg-white rounded border border-green-300">
                                                <p class="text-xs text-gray-600 font-semibold mb-1">Cómo funciona:</p>
                                                <ul class="text-xs text-gray-600 space-y-1 ml-4">
                                                    <li>Si está desactivado: Solo se emitirán boletas para todos los pedidos</li>
                                                    <li>Si está activado: El sistema detectará automáticamente si el cliente eligió "Boleta" o "Factura" en Shopify</li>
                                                    <li>Los clientes que elijan factura deberán proporcionar: RUT, Razón Social, Giro y Dirección</li>
                                                    <li>Todo se procesa automáticamente vía webhooks</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gradient-to-r from-blue-50 to-cyan-50 border-2 border-blue-200 rounded-lg p-4 mb-3">
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input id="oauth_shopify_visibility_enabled" name="shopify_visibility_enabled" type="checkbox" value="1"
                                                class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                                        </div>
                                        <div class="ml-4 text-sm">
                                            <label for="oauth_shopify_visibility_enabled" class="font-bold text-gray-900 cursor-pointer">
                                                Visibilidad desde Shopify
                                            </label>
                                            <p class="text-gray-700 mt-1">
                                                Escribe automáticamente el número de boleta/factura en las notas del pedido de Shopify para que sea visible desde el panel de administración.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gradient-to-r from-red-50 to-orange-50 border-2 border-red-200 rounded-lg p-4 mb-3">
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input id="oauth_notas_credito_enabled" name="notas_credito_enabled" type="checkbox" value="1"
                                                class="w-5 h-5 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500 focus:ring-2">
                                        </div>
                                        <div class="ml-4 text-sm">
                                            <label for="oauth_notas_credito_enabled" class="font-bold text-gray-900 cursor-pointer">
                                                Notas de Crédito Automáticas
                                            </label>
                                            <p class="text-gray-700 mt-1">
                                                Emite automáticamente Notas de Crédito en Lioren cuando un pedido es cancelado o reembolsado en Shopify.
                                            </p>
                                        </div>
                                            
                                            <div style="margin-bottom: 0.75rem;">
                                                <label style="display: flex; align-items: center; cursor: pointer; padding: 0.5rem; background: white; border-radius: 0.375rem;">
                                                    <input 
                                                        type="checkbox" 
                                                        id="documentos_postventa_enabled" 
                                                        name="documentos_postventa_enabled" 
                                                        value="1"
                                                        {{ isset($integracion) && $integracion->documentos_postventa_enabled ? 'checked' : '' }}
                                                        style="width: 1.25rem; height: 1.25rem; margin-right: 0.75rem; cursor: pointer;">
                                                    <label for="documentos_postventa_enabled" class="font-bold text-gray-900 text-lg cursor-pointer">
                                                        📝 Documentos Postventa
                                                    </label>
                                                </label>
                                            </div>
                                    </div>
                                </div>

                                <div class="bg-gradient-to-r from-emerald-50 to-teal-50 border-2 border-emerald-200 rounded-lg p-4 mb-3">
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input id="oauth_sync_inventario_enabled" name="sync_inventario_enabled" type="checkbox" value="1"
                                                class="w-5 h-5 text-emerald-600 bg-gray-100 border-gray-300 rounded focus:ring-emerald-500 focus:ring-2">
                                        </div>
                                        <div class="ml-4 text-sm">
                                            <label for="oauth_sync_inventario_enabled" class="font-bold text-gray-900 cursor-pointer">
                                                Sincronización de Inventario
                                            </label>
                                            <p class="text-gray-700 mt-1">
                                                Sincroniza automáticamente el stock entre Lioren y Shopify en tiempo real. Cuando se vende en Shopify, se descuenta en Lioren y viceversa.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gradient-to-r from-brand-50 to-red-50 border-2 border-brand-200 rounded-lg p-4">
                                    <div class="mb-3">
                                        <div class="flex items-center">
                                            <input id="oauth_no_order_limit" name="no_order_limit" type="checkbox" value="1" checked
                                                onchange="toggleOAuthOrderLimit()"
                                                class="w-5 h-5 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 focus:ring-2">
                                            <label for="oauth_no_order_limit" class="ml-3 font-bold text-gray-900 cursor-pointer">
                                                Sin límite de pedidos
                                            </label>
                                        </div>
                                        <p class="text-gray-700 text-sm mt-1 ml-8">Procesar todos los pedidos sin restricciones</p>
                                    </div>
                                    <div id="oauth_order_limit_section" style="display: none;">
                                        <div class="bg-white border-2 border-brand-300 rounded-lg p-3">
                                            <label for="oauth_monthly_order_limit" class="block font-bold text-gray-900 mb-1 text-sm">
                                                Límite mensual de pedidos
                                            </label>
                                            <input type="number" id="oauth_monthly_order_limit" name="monthly_order_limit" min="1" placeholder="Ej: 200"
                                                class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button 
                                type="submit"
                                id="main-oauth-submit"
                                class="w-full px-6 py-3 bg-brand-600 text-white font-bold rounded-lg hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition inline-flex items-center justify-center gap-2"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                Conectar con Shopify OAuth
                            </button>
                            </div>{{-- cierre main-oauth-fields --}}
                        </form>

                        {{-- Formulario de Reconexión (separado del form principal) --}}
                        <form id="reconnect-form" action="{{ route('admin.shopify.oauth.reconectar') }}" method="POST" style="display: none; margin-top: 1rem;">
                            @csrf
                            <input type="hidden" name="user_id" id="reconnect-user-id" value="">
                            <input type="hidden" name="shopify_client_id" id="reconnect-shopify-client-id" value="">
                            <input type="hidden" name="shopify_client_secret" id="reconnect-shopify-client-secret" value="">
                            <button 
                                type="submit"
                                style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; width: 100%; padding: 1rem 1.5rem; background-color: #f59e0b; color: #ffffff; font-weight: 700; font-size: 1.125rem; border-radius: 0.5rem; border: 2px solid #d97706; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: background-color 0.2s;"
                                onmouseover="this.style.backgroundColor='#d97706'"
                                onmouseout="this.style.backgroundColor='#f59e0b'"
                                onclick="return confirm('¿Reconectar este cliente? Se renovará el token de acceso y se actualizarán los webhooks. No se creará una nueva suscripción.')"
                            >
                                <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Reconectar con credenciales guardadas
                            </button>
                        </form>
                    </div>

                    {{-- SEPARADOR --}}
                    <div class="relative my-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-white text-gray-500">o ingresa credenciales manualmente</span>
                        </div>
                    </div>

                    {{-- ============================================ --}}
                    {{-- SECCIÓN 2: FORMULARIO MANUAL                 --}}
                    {{-- ============================================ --}}

                    <form action="{{ route('integracion.procesar') }}" method="POST">
                        @csrf

                        {{-- Shopify Section --}}
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-brand-600 mb-4 pb-2 border-b-2 border-brand-100">
                                Credenciales de Shopify
                            </h2>

                            <div class="mb-4">
                                <x-input-label for="shopify_tienda" value="Nombre de Tienda *" />
                                <x-text-input 
                                    id="shopify_tienda" 
                                    class="block mt-1 w-full" 
                                    type="text" 
                                    name="shopify_tienda" 
                                    :value="old('shopify_tienda')" 
                                    required 
                                    placeholder="ejemplo.myshopify.com"
                                    pattern="[a-zA-Z0-9\-]+\.myshopify\.com"
                                />
                                <p class="mt-1 text-sm text-gray-500">Formato: tu-tienda.myshopify.com</p>
                                <x-input-error :messages="$errors->get('shopify_tienda')" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <x-input-label for="shopify_token" value="Access Token *" />
                                <x-text-input 
                                    id="shopify_token" 
                                    class="block mt-1 w-full" 
                                    type="password" 
                                    name="shopify_token" 
                                    required 
                                    placeholder="shpat_xxxxxxxxxxxxx"
                                    minlength="20"
                                />
                                <p class="mt-1 text-sm text-gray-500">Token de API de tu app personalizada de Shopify</p>
                                <x-input-error :messages="$errors->get('shopify_token')" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <x-input-label for="shopify_secret" value="API Secret (para webhooks) *" />
                                <x-text-input 
                                    id="shopify_secret" 
                                    class="block mt-1 w-full" 
                                    type="password" 
                                    name="shopify_secret" 
                                    required 
                                    placeholder="shpss_xxxxxxxxxxxxx"
                                    minlength="20"
                                />
                                <p class="mt-1 text-sm text-gray-500">Secret key para validar webhooks de Shopify</p>
                                <x-input-error :messages="$errors->get('shopify_secret')" class="mt-2" />
                            </div>
                        </div>

                        {{-- Lioren Section --}}
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-brand-600 mb-4 pb-2 border-b-2 border-brand-100">
                                Credenciales de Lioren
                            </h2>

                            <div class="mb-4">
                                <x-input-label for="lioren_api_key" value="API Key (Bearer Token) *" />
                                <x-text-input 
                                    id="lioren_api_key" 
                                    class="block mt-1 w-full" 
                                    type="password" 
                                    name="lioren_api_key" 
                                    required 
                                    placeholder="tu_api_key_de_lioren"
                                    minlength="10"
                                />
                                <p class="mt-1 text-sm text-gray-500">Token de autenticación de la API de Lioren</p>
                                <x-input-error :messages="$errors->get('lioren_api_key')" class="mt-2" />
                            </div>
                        </div>

                        {{-- Webhook Section --}}
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-brand-600 mb-4 pb-2 border-b-2 border-brand-100">
                                Configuración de Webhooks
                            </h2>

                            <div class="mb-4">
                                <x-input-label for="webhook_url" value="URL del Receptor de Webhooks" />
                                <x-text-input 
                                    id="webhook_url" 
                                    class="block mt-1 w-full" 
                                    type="text" 
                                    name="webhook_url" 
                                    :value="$webhook_url" 
                                    required 
                                    pattern="https?://.+"
                                />
                                <p class="mt-1 text-sm text-gray-500">URL pública donde Shopify enviará los eventos</p>
                                <x-input-error :messages="$errors->get('webhook_url')" class="mt-2" />
                            </div>
                        </div>

                        {{-- Facturación Section --}}
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-brand-600 mb-4 pb-2 border-b-2 border-brand-100">
                                Opciones de Facturación
                            </h2>

                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-lg p-6 mb-4">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input 
                                            id="facturacion_enabled" 
                                            name="facturacion_enabled" 
                                            type="checkbox" 
                                            value="1"
                                            class="w-5 h-5 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500 focus:ring-2"
                                        >
                                    </div>
                                    <div class="ml-4 text-sm">
                                        <label for="facturacion_enabled" class="font-bold text-gray-900 text-lg cursor-pointer">
                                            Habilitar emisión de facturas electrónicas
                                        </label>
                                        <p class="text-gray-700 mt-2">
                                            Al activar esta opción, el sistema podrá procesar tanto <strong>boletas</strong> como <strong>facturas</strong> según lo que elija cada cliente en el checkout de Shopify.
                                        </p>
                                        <div class="mt-3 p-3 bg-white rounded border border-green-300">
                                            <p class="text-xs text-gray-600 font-semibold mb-2">Cómo funciona:</p>
                                            <ul class="text-xs text-gray-600 space-y-1 ml-4">
                                                <li>Si está desactivado: Solo se emitirán boletas para todos los pedidos</li>
                                                <li>Si está activado: El sistema detectará automáticamente si el cliente eligió "Boleta" o "Factura" en Shopify</li>
                                                <li>Los clientes que elijan factura deberán proporcionar: RUT, Razón Social, Giro y Dirección</li>
                                                <li>Todo se procesa automáticamente vía webhooks</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gradient-to-r from-blue-50 to-cyan-50 border-2 border-blue-200 rounded-lg p-6 mb-4">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input 
                                            id="shopify_visibility_enabled" 
                                            name="shopify_visibility_enabled" 
                                            type="checkbox" 
                                            value="1"
                                            class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                                        >
                                    </div>
                                    <div class="ml-4 text-sm">
                                        <label for="shopify_visibility_enabled" class="font-bold text-gray-900 text-lg cursor-pointer">
                                            Visibilidad desde Shopify
                                        </label>
                                        <p class="text-gray-700 mt-2">
                                            Escribe automáticamente el número de boleta/factura en las notas del pedido de Shopify para que sea visible desde el panel de administración.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gradient-to-r from-red-50 to-orange-50 border-2 border-red-200 rounded-lg p-6">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input 
                                            id="notas_credito_enabled" 
                                            name="notas_credito_enabled" 
                                            type="checkbox" 
                                            value="1"
                                            class="w-5 h-5 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500 focus:ring-2"
                                        >
                                    </div>
                                    <div class="ml-4 text-sm">
                                        <label for="notas_credito_enabled" class="font-bold text-gray-900 text-lg cursor-pointer">
                                            Notas de Crédito Automáticas
                                        </label>
                                        <p class="text-gray-700 mt-2">
                                            Emite automáticamente Notas de Crédito en Lioren cuando un pedido es cancelado o reembolsado en Shopify.
                                        </p>
                                    </div>
                                            
                                            <div style="margin-bottom: 0.75rem;">
                                                <label style="display: flex; align-items: center; cursor: pointer; padding: 0.5rem; background: white; border-radius: 0.375rem;">
                                                    <input 
                                                        type="checkbox" 
                                                        id="documentos_postventa_enabled" 
                                                        name="documentos_postventa_enabled" 
                                                        value="1"
                                                        {{ isset($integracion) && $integracion->documentos_postventa_enabled ? 'checked' : '' }}
                                                        style="width: 1.25rem; height: 1.25rem; margin-right: 0.75rem; cursor: pointer;">
                                                    <label for="documentos_postventa_enabled" class="font-bold text-gray-900 text-lg cursor-pointer">
                                                        📝 Documentos Postventa
                                                    </label>
                                                </label>
                                            </div>
                                </div>
                            </div>
                        </div>

                        {{-- Sincronización de Inventario Section --}}
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-emerald-600 mb-4 pb-2 border-b-2 border-emerald-100">
                                Sincronización de Inventario
                            </h2>

                            <div class="bg-gradient-to-r from-emerald-50 to-teal-50 border-2 border-emerald-200 rounded-lg p-6">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input 
                                            id="sync_inventario_enabled" 
                                            name="sync_inventario_enabled" 
                                            type="checkbox" 
                                            value="1"
                                            class="w-5 h-5 text-emerald-600 bg-gray-100 border-gray-300 rounded focus:ring-emerald-500 focus:ring-2"
                                        >
                                    </div>
                                    <div class="ml-4 text-sm">
                                        <label for="sync_inventario_enabled" class="font-bold text-gray-900 text-lg cursor-pointer">
                                            Sincronización de Inventario
                                        </label>
                                        <p class="text-gray-700 mt-2">
                                            Sincroniza automáticamente el stock entre Lioren y Shopify en tiempo real. Cuando se vende un producto en Shopify, se descuenta automáticamente en Lioren y viceversa.
                                        </p>
                                        <div class="mt-3 bg-white border border-emerald-200 rounded-lg p-3">
                                            <p class="text-xs text-gray-600">
                                                <strong>Requisitos:</strong> El módulo de bodegas debe estar activo en Lioren. Los productos deben estar mapeados por SKU/código entre ambas plataformas.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Límite de Pedidos Section --}}
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-brand-600 mb-4 pb-2 border-b-2 border-brand-100">
                                Límite de Pedidos Mensuales
                            </h2>

                            <div class="bg-gradient-to-r from-brand-50 to-red-50 border-2 border-brand-200 rounded-lg p-6">
                                <div class="mb-4">
                                    <div class="flex items-center">
                                        <input 
                                            id="no_order_limit" 
                                            name="no_order_limit" 
                                            type="checkbox" 
                                            value="1"
                                            checked
                                            onchange="toggleOrderLimit()"
                                            class="w-5 h-5 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 focus:ring-2"
                                        >
                                        <label for="no_order_limit" class="ml-3 font-bold text-gray-900 text-lg cursor-pointer">
                                            Sin límite de pedidos
                                        </label>
                                    </div>
                                    <p class="text-gray-700 text-sm mt-2 ml-8">
                                        Procesar todos los pedidos sin restricciones
                                    </p>
                                </div>

                                <div id="order_limit_section" style="display: none;">
                                    <div class="bg-white border-2 border-brand-300 rounded-lg p-4">
                                        <label for="monthly_order_limit" class="block font-bold text-gray-900 mb-2">
                                            Límite mensual de pedidos
                                        </label>
                                        <input 
                                            type="number" 
                                            id="monthly_order_limit" 
                                            name="monthly_order_limit" 
                                            min="1"
                                            placeholder="Ej: 200"
                                            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                                        >
                                        <p class="text-xs text-gray-600 mt-2">
                                            Cuando se alcance este límite en el mes, no se procesarán más pedidos hasta el próximo mes
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-8">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        <strong>Nota:</strong> Después de conectar podrás configurar la sincronización de bodegas desde el Dashboard.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end">
                            <x-primary-button class="w-full justify-center text-lg py-3">
                                Conectar y Configurar Integración
                            </x-primary-button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleOrderLimit() {
            const checkbox = document.getElementById('no_order_limit');
            const section = document.getElementById('order_limit_section');
            const input = document.getElementById('monthly_order_limit');
            
            if (checkbox.checked) {
                section.style.display = 'none';
                input.value = '';
                input.removeAttribute('required');
            } else {
                section.style.display = 'block';
                input.setAttribute('required', 'required');
            }
        }

        function toggleOAuthOrderLimit() {
            const checkbox = document.getElementById('oauth_no_order_limit');
            const section = document.getElementById('oauth_order_limit_section');
            const input = document.getElementById('oauth_monthly_order_limit');
            
            if (checkbox.checked) {
                section.style.display = 'none';
                input.value = '';
                input.removeAttribute('required');
            } else {
                section.style.display = 'block';
                input.setAttribute('required', 'required');
            }
        }
    </script>
</x-app-layout>
