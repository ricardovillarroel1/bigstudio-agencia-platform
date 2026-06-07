<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\IntegracionConfig;
use App\Models\Solicitud;
use App\Models\User;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\FacturaServicio;
use App\Services\FacturaServicioEmitter;

class ShopifyOAuthController extends Controller
{
    /**
     * Obtener credenciales de Shopify: primero busca por integración, luego fallback a .env
     */
    private function getShopifyCredentials(?int $userId = null): array
    {
        if ($userId) {
            $config = IntegracionConfig::where('user_id', $userId)->first();
            if ($config && $config->shopify_client_id && $config->shopify_client_secret) {
                return [
                    'client_id' => $config->shopify_client_id,
                    'client_secret' => $config->shopify_client_secret,
                ];
            }
        }

        // Fallback a credenciales globales del .env
        return [
            'client_id' => config('shopify.client_id'),
            'client_secret' => config('shopify.client_secret'),
        ];
    }

    /**
     * Punto de entrada público para instalación desde Shopify App Store.
     * Recibe ?shop=cliente.myshopify.com&hmac=...&timestamp=...
     * Valida HMAC, genera nonce y redirige al OAuth de Shopify de forma inmediata.
     */
    public function installFromAppStore(Request $request)
    {
        $shop = $request->query('shop');

        // Validar formato de shop domain
        // Sin shop o shop inválido → redirigir al login (evita 4xx que Shopify marca como error en su review)
        if (!$shop || !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/', $shop)) {
            Log::warning('Install: shop inválido o ausente, redirigiendo a /login', [
                'shop' => $shop,
                'ip' => $request->ip(),
            ]);
            return redirect('/login');
        }

        // Si Shopify mandó HMAC (instalación firmada), validarlo
        $queryParams = $request->query();
        if (!empty($queryParams['hmac'])) {
            if (!$this->verifyHmac($queryParams, config('shopify.client_secret'))) {
                Log::warning('Install: HMAC inválido, redirigiendo a /login', ['shop' => $shop]);
                return redirect('/login');
            }
        }

        // State con prefijo identificable "asn_" para detectar el origen App Store
        // incluso si la sesión Laravel se pierde por SameSite/cross-site cookies.
        $nonce = 'asn_' . Str::random(32);

        // Guardar también en sesión como fallback (puede perderse en cross-site)
        session([
            'shopify_nonce' => $nonce,
            'shopify_shop' => $shop,
            'oauth_origin' => 'appstore',
            'oauth_client_id' => config('shopify.client_id'),
            'oauth_client_secret' => config('shopify.client_secret'),
        ]);

        $authUrl = "https://{$shop}/admin/oauth/authorize?" . http_build_query([
            'client_id' => config('shopify.client_id'),
            'scope' => config('shopify.scopes'),
            'redirect_uri' => config('shopify.redirect_uri'),
            'state' => $nonce,
        ]);

        Log::info('Install App Store iniciado', ['shop' => $shop]);

        return redirect($authUrl);
    }

    /**
     * Iniciar flujo OAuth con Shopify (CLIENTE)
     */
    public function iniciarOAuth(Request $request)
    {
        $request->validate([
            'shop_url' => ['required', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/'],
            'lioren_api_key' => 'required|string',
            'solicitud_id' => 'required|exists:solicitudes,id'
        ]);

        $shop = $request->shop_url;
        $nonce = Str::random(32);

        // Guardar datos en sesión para recuperarlos en el callback
        session([
            'shopify_nonce' => $nonce,
            'shopify_shop' => $shop,
            'lioren_api_key' => $request->lioren_api_key,
            'solicitud_id' => $request->solicitud_id,
            'oauth_origin' => 'cliente', // Identificar origen
        ]);

        // Obtener credenciales (para cliente, usar las del usuario autenticado o fallback)
        $credentials = $this->getShopifyCredentials(auth()->id());

        // Guardar client_id y client_secret en sesión para el callback
        session([
            'oauth_client_id' => $credentials['client_id'],
            'oauth_client_secret' => $credentials['client_secret'],
        ]);

        // Construir URL de autorización OAuth
        $authUrl = "https://{$shop}/admin/oauth/authorize?" . http_build_query([
            'client_id' => $credentials['client_id'],
            'scope' => config('shopify.scopes'),
            'redirect_uri' => config('shopify.redirect_uri'),
            'state' => $nonce
        ]);

        Log::info("Iniciando OAuth (cliente) para shop: {$shop}", ['user_id' => auth()->id()]);

        return redirect($authUrl);
    }

    /**
     * Iniciar flujo OAuth con Shopify (ADMIN)
     */
    public function iniciarOAuthAdmin(Request $request)
    {
        $clientType = $request->input('client_type', 'new');

        if ($clientType === 'existing') {
            // Validación para cliente existente
            $request->validate([
                'shop_url' => ['required', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/'],
                'lioren_api_key' => 'required|string',
                'existing_user_id' => 'required|exists:users,id',
                'existing_plan_id' => 'required|exists:planes,id',
            ]);

            $existingUser = \App\Models\User::findOrFail($request->existing_user_id);
            $shop = $request->shop_url;
            $nonce = Str::random(32);

            // Obtener credenciales específicas del cliente o las nuevas del formulario
            $clientId = $request->input('shopify_client_id');
            $clientSecret = $request->input('shopify_client_secret');

            if (!$clientId || !$clientSecret) {
                // Buscar credenciales existentes del cliente
                $existingConfig = IntegracionConfig::where('user_id', $existingUser->id)->first();
                if ($existingConfig && $existingConfig->shopify_client_id) {
                    $clientId = $existingConfig->shopify_client_id;
                    $clientSecret = $existingConfig->shopify_client_secret;
                } else {
                    // Fallback a .env
                    $clientId = config('shopify.client_id');
                    $clientSecret = config('shopify.client_secret');
                }
            }

            session([
                'shopify_nonce' => $nonce,
                'shopify_shop' => $shop,
                'lioren_api_key' => $request->lioren_api_key,
                'solicitud_id' => null,
                'oauth_origin' => 'admin',
                'client_type' => 'existing',
                'existing_user_id' => $existingUser->id,
                'cliente_nombre' => $existingUser->name,
                'cliente_email' => $existingUser->email,
                'cliente_password' => null,
                'plan_id' => $request->existing_plan_id,
                'facturacion_enabled' => $request->has('facturacion_enabled'),
                'shopify_visibility' => $request->has('shopify_visibility_enabled'),
                'notas_credito_auto' => $request->has('notas_credito_enabled'),
                'sync_inventario' => $request->has('sync_inventario_enabled'),
                'sin_limite_pedidos' => $request->has('no_order_limit'),
                'monthly_order_limit' => $request->monthly_order_limit,
                'oauth_client_id' => $clientId,
                'oauth_client_secret' => $clientSecret,
            ]);
        } else {
            // Validación para cliente nuevo
            $request->validate([
                'shop_url' => ['required', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/'],
                'lioren_api_key' => 'required|string',
                'cliente_nombre' => 'required|string|max:255',
                'cliente_email' => 'required|email|max:255',
                'cliente_password' => 'required|string|min:8',
                'plan_id' => 'required|exists:planes,id',
                'shopify_client_id' => 'required|string',
                'shopify_client_secret' => 'required|string',
            ]);

            $shop = $request->shop_url;
            $nonce = Str::random(32);

            $clientId = $request->shopify_client_id;
            $clientSecret = $request->shopify_client_secret;

            session([
                'shopify_nonce' => $nonce,
                'shopify_shop' => $shop,
                'lioren_api_key' => $request->lioren_api_key,
                'solicitud_id' => null,
                'oauth_origin' => 'admin',
                'client_type' => 'new',
                'cliente_nombre' => $request->cliente_nombre,
                'cliente_email' => $request->cliente_email,
                'cliente_password' => $request->cliente_password,
                'plan_id' => $request->plan_id,
                'facturacion_enabled' => $request->has('facturacion_enabled'),
                'shopify_visibility' => $request->has('shopify_visibility_enabled'),
                'notas_credito_auto' => $request->has('notas_credito_enabled'),
                'sync_inventario' => $request->has('sync_inventario_enabled'),
                'sin_limite_pedidos' => $request->has('no_order_limit'),
                'monthly_order_limit' => $request->monthly_order_limit,
                'oauth_client_id' => $clientId,
                'oauth_client_secret' => $clientSecret,
            ]);
        }
        // Construir URL de autorización OAuth usando credenciales específicas
        $authUrl = "https://{$shop}/admin/oauth/authorize?" . http_build_query([
            'client_id' => $clientId,
            'scope' => config('shopify.scopes'),
            'redirect_uri' => config('shopify.redirect_uri'),
            'state' => $nonce
        ]);

        Log::info("Iniciando OAuth (admin) para shop: {$shop}", [
            'user_id' => auth()->id(),
            'client_id' => $clientId,
        ]);

        return redirect($authUrl);
    }

    /**
     * Reconectar OAuth para un cliente existente (usa credenciales guardadas)
     * No crea nueva suscripción ni nuevo usuario
     */
    public function reconectarOAuth(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $clienteUser = User::findOrFail($request->user_id);
        $config = IntegracionConfig::where('user_id', $clienteUser->id)->first();

        if (!$config || !$config->shop_domain) {
            return redirect()->route('integracion.dashboard')
                ->with('error', 'Este cliente no tiene una integración previa con tienda Shopify configurada.');
        }

        $shop = $config->shop_domain;
        $nonce = Str::random(32);

        // Usar credenciales específicas de esta integración
        $clientId = $config->shopify_client_id ?: config('shopify.client_id');
        $clientSecret = $config->shopify_client_secret ?: config('shopify.client_secret');

        // Guardar en sesión con origen 'reconnect' para que el callback sepa que es reconexión
        session([
            'shopify_nonce' => $nonce,
            'shopify_shop' => $shop,
            'lioren_api_key' => $config->lioren_api_key,
            'solicitud_id' => null,
            'oauth_origin' => 'reconnect',
            'client_type' => 'existing',
            'existing_user_id' => $clienteUser->id,
            'cliente_nombre' => $clienteUser->name,
            'cliente_email' => $clienteUser->email,
            'cliente_password' => null,
            // Mantener la configuración actual del cliente
            'facturacion_enabled' => $config->facturacion_enabled,
            'shopify_visibility' => $config->shopify_visibility_enabled,
            'notas_credito_auto' => $config->notas_credito_enabled,
            'sync_inventario' => $config->sync_inventario_enabled,
            'sin_limite_pedidos' => !$config->order_limit_enabled,
            'monthly_order_limit' => $config->monthly_order_limit,
            'oauth_client_id' => $clientId,
            'oauth_client_secret' => $clientSecret,
        ]);

        // Construir URL de autorización OAuth con credenciales específicas
        $authUrl = "https://{$shop}/admin/oauth/authorize?" . http_build_query([
            'client_id' => $clientId,
            'scope' => config('shopify.scopes'),
            'redirect_uri' => config('shopify.redirect_uri'),
            'state' => $nonce
        ]);

        Log::info("Reconectando OAuth para shop: {$shop}", [
            'admin_user_id' => auth()->id(),
            'cliente_user_id' => $clienteUser->id,
            'client_id' => $clientId,
        ]);

        return redirect($authUrl);
    }

    /**
     * Manejar callback de Shopify después de autorización
     */
    public function handleCallback(Request $request)
    {
        $queryParams = $request->all();
        $code = $queryParams['code'] ?? '';
        $shop = $queryParams['shop'] ?? '';
        $state = $queryParams['state'] ?? '';

        // === Detectar origen sin depender de sesión Laravel ===
        // Si el state empieza con "asn_" → instalación pública App Store
        // (resiliente a session loss por SameSite cookies en cross-site OAuth)
        $isAppStoreByState = is_string($state) && str_starts_with($state, 'asn_');

        if ($isAppStoreByState) {
            $origin = 'appstore';
        } else {
            $origin = session('oauth_origin', 'cliente');
        }

        // Obtener credenciales: si es App Store, usar siempre las globales del .env
        if ($origin === 'appstore') {
            $clientId = config('shopify.client_id');
            $clientSecret = config('shopify.client_secret');
        } else {
            $clientId = session('oauth_client_id', config('shopify.client_id'));
            $clientSecret = session('oauth_client_secret', config('shopify.client_secret'));
        }

        // Determinar rutas de redirección según origen
        $isAdmin = in_array($origin, ['admin', 'reconnect']);
        if ($origin === 'appstore') {
            $errorRoute = 'shopify.install'; // si falla, retry el install
            $successRoute = 'appstore.onboarding';
        } else {
            $errorRoute = $isAdmin ? 'integracion.index' : 'cliente.estados-solicitud';
            $successRoute = $isAdmin ? 'integracion.index' : 'cliente.estados-solicitud';
        }

        // Validar state (protección CSRF)
        // Para App Store: validar el prefijo "asn_" (no requiere sesión)
        // Para flujos admin/cliente: validar contra el nonce de sesión
        if ($origin === 'appstore') {
            // El state ya fue verificado como prefijo "asn_". Adicionalmente exigir HMAC válido.
            if (!$state || strlen($state) < 30) {
                Log::error('App Store: state inválido', ['state_recibido' => $state, 'shop' => $shop]);
                return redirect('/login');
            }
        } else {
            if ($state !== session('shopify_nonce')) {
                Log::error('Estado OAuth inválido', ['state_recibido' => $state]);
                return redirect()->route($errorRoute)
                    ->with('error', 'Estado OAuth inválido. Por favor intenta nuevamente.');
            }
        }

        // Validar HMAC con el client_secret específico de esta integración
        // (siempre obligatorio — es la garantía de que Shopify firmó la respuesta)
        if (!$this->verifyHmac($queryParams, $clientSecret)) {
            Log::error('Firma HMAC inválida', ['shop' => $shop, 'origin' => $origin]);
            if ($origin === 'appstore') {
                return redirect('/login');
            }
            return redirect()->route($errorRoute)
                ->with('error', 'Firma HMAC inválida. Conexión rechazada por seguridad.');
        }

        try {
            // Intercambiar código por access token usando credenciales específicas
            $accessToken = $this->exchangeCodeForToken($shop, $code, $clientId, $clientSecret);

            // Recuperar datos de sesión
            $solicitudId = session('solicitud_id');
            $liorenApiKey = session('lioren_api_key');

            // ============================================================
            // Flujo APP STORE: instalación pública desde Shopify
            // ============================================================
            if ($origin === 'appstore') {
                $shopName = str_replace('.myshopify.com', '', $shop);
                $autoEmail = "owner+{$shopName}@shopify-lioren.bigstudio.cl";

                // 1. Crear o encontrar usuario por shop (puede editar email en onboarding)
                $clienteUser = User::firstOrCreate(
                    ['email' => $autoEmail],
                    [
                        'name' => "Tienda {$shopName}",
                        'password' => Hash::make(Str::random(40)),
                        'role' => 'cliente',
                    ]
                );
                if (!$clienteUser->hasRole('cliente')) {
                    $clienteUser->syncRoles(['cliente']);
                    $clienteUser->update(['role' => 'cliente']);
                }

                // 2. Crear/actualizar Solicitud sin plan (se elige en onboarding)
                $solicitud = Solicitud::updateOrCreate(
                    ['cliente_id' => $clienteUser->id, 'tienda_shopify' => $shop],
                    [
                        'descripcion' => 'Instalación desde Shopify App Store',
                        'email' => $autoEmail,
                        'access_token' => $accessToken,
                        'api_secret' => $clientSecret,
                        'estado' => 'pendiente_onboarding',
                        'integracion_conectada' => false,
                        'fecha_conexion' => now(),
                    ]
                );

                // 3. Crear/actualizar IntegracionConfig (sin lioren_api_key todavía)
                IntegracionConfig::updateOrCreate(
                    ['user_id' => $clienteUser->id],
                    [
                        'solicitud_id' => $solicitud->id,
                        'shopify_tienda' => $shop,
                        'shopify_token' => $accessToken,
                        'shopify_secret' => $clientSecret,
                        'shopify_client_id' => $clientId,
                        'shopify_client_secret' => $clientSecret,
                        'shop_domain' => $shop,
                        'auth_method' => 'oauth',
                        'oauth_installed_at' => now(),
                        'activo' => false,
                    ]
                );

                // 4. Auto-login del merchant recién instalado
                // Hacemos Auth::login Y ADEMÁS generamos un token de un solo uso
                // (porque la cookie de sesión puede perderse en cross-site OAuth)
                \Illuminate\Support\Facades\Auth::login($clienteUser);

                $loginToken = Str::random(64);
                $clienteUser->update([
                    'appstore_login_token' => $loginToken,
                    'appstore_login_expires_at' => now()->addMinutes(15),
                ]);

                // 5. Registrar webhook orders/paid en Shopify
                $this->registerOrdersPaidWebhook($shop, $accessToken);

                Log::info('OAuth App Store completado', [
                    'shop' => $shop,
                    'user_id' => $clienteUser->id,
                    'solicitud_id' => $solicitud->id,
                ]);

                session()->forget([
                    'shopify_nonce', 'shopify_shop', 'oauth_origin',
                    'oauth_client_id', 'oauth_client_secret',
                ]);

                // Si ya tiene API key Lioren (re-instalación), ir directo al dashboard
                $config = IntegracionConfig::where('user_id', $clienteUser->id)->first();
                if ($config && $config->lioren_api_key) {
                    return redirect()->route('appstore.dashboard')
                        ->with('success', "¡Conexión con {$shop} restablecida! La integración está activa.");
                }

                return redirect()->route('appstore.onboarding', ['t' => $loginToken])
                    ->with('success', "¡Conexión exitosa con {$shop}! Configura tu integración con Lioren para activar la facturación automática.");
            }

            if ($origin === 'admin' || $origin === 'reconnect') {
                // Flujo ADMIN: crear usuario cliente + integración + suscripción
                $clienteNombre = session('cliente_nombre');
                $clienteEmail = session('cliente_email');
                $clientePassword = session('cliente_password');
                $planId = session('plan_id');
                $facturacionEnabled = session('facturacion_enabled', false);
                $shopifyVisibility = session('shopify_visibility', false);
                $notasCreditoAuto = session('notas_credito_auto', false);
                $syncInventario = session('sync_inventario', false);
                $sinLimitePedidos = session('sin_limite_pedidos', false);
                $monthlyOrderLimit = session('monthly_order_limit');

                // 1. Crear o encontrar usuario cliente
                $clientType = session('client_type', 'new');
                $existingUserId = session('existing_user_id');

                if ($clientType === 'existing' && $existingUserId) {
                    // Usar usuario existente directamente
                    $clienteUser = User::findOrFail($existingUserId);
                    if (!$clienteUser->hasRole('cliente')) {
                        $clienteUser->syncRoles(['cliente']);
                        $clienteUser->update(['role' => 'cliente']);
                    }
                    Log::info("Usando usuario cliente existente: {$clienteUser->email}", ['user_id' => $clienteUser->id]);
                } else {
                    // Crear o encontrar usuario por email
                    $clienteUser = User::where('email', $clienteEmail)->first();
                    if (!$clienteUser) {
                        $clienteUser = User::create([
                            'name' => $clienteNombre,
                            'email' => $clienteEmail,
                            'password' => Hash::make($clientePassword),
                            'role' => 'cliente',
                        ]);
                        $clienteUser->assignRole('cliente');
                        Log::info("Usuario cliente creado: {$clienteEmail}", ['user_id' => $clienteUser->id]);
                    } else {
                        // Asegurar que tenga rol cliente
                        if (!$clienteUser->hasRole('cliente')) {
                            $clienteUser->syncRoles(['cliente']);
                            $clienteUser->update(['role' => 'cliente']);
                        }
                        Log::info("Usuario cliente existente encontrado: {$clienteEmail}", ['user_id' => $clienteUser->id]);
                    }
                }

                // 2. Crear IntegracionConfig para el CLIENTE (con credenciales específicas)
                $orderLimitEnabled = !$sinLimitePedidos;
                IntegracionConfig::updateOrCreate(
                    ['user_id' => $clienteUser->id],
                    [
                        'shopify_tienda' => $shop,
                        'shopify_token' => $accessToken,
                        'shopify_secret' => $clientSecret,
                        'shopify_client_id' => $clientId,
                        'shopify_client_secret' => $clientSecret,
                        'lioren_api_key' => $liorenApiKey,
                        'shop_domain' => $shop,
                        'auth_method' => 'oauth',
                        'oauth_installed_at' => now(),
                        'facturacion_enabled' => $facturacionEnabled,
                        'shopify_visibility_enabled' => $shopifyVisibility,
                        'notas_credito_enabled' => $notasCreditoAuto,
                        'sync_inventario_enabled' => $syncInventario,
                        'order_limit_enabled' => $orderLimitEnabled,
                        'monthly_order_limit' => $orderLimitEnabled ? $monthlyOrderLimit : null,
                        'activo' => true,
                    ]
                );

                // 3. Crear suscripción para el cliente (solo si NO es reconexión)
                if ($origin !== 'reconnect') {
                    $plan = Plan::findOrFail($planId);
                    $existingSub = Suscripcion::where('user_id', $clienteUser->id)
                        ->where('estado', 'activa')
                        ->first();

                    if (!$existingSub) {
                        $duracionDias = $plan->precio == 0 ? 36500 : 30; // Plan gratis = ~100 años, pago = 30 días
                        $suscripcion = Suscripcion::create([
                            'user_id' => $clienteUser->id,
                            'plan_id' => $planId,
                            'estado' => 'activa',
                            'origen' => 'manual',
                            'fecha_inicio' => now(),
                            'proximo_pago' => now()->addDays($duracionDias),
                            'fecha_fin' => now()->addDays($duracionDias),
                        ]);

                        // Crear factura de servicio y emitir DTE via Lioren
                        if ($plan->precio > 0) {
                            FacturaServicioEmitter::crearYEmitir(
                                userId: $clienteUser->id,
                                planId: $planId,
                                suscripcionId: $suscripcion->id,
                                concepto: 'Suscripción ' . $plan->nombre . ' (Ingreso Manual por Admin)',
                                periodoInicio: now()->toDateString(),
                                periodoFin: now()->addDays($duracionDias)->toDateString()
                            );
                        } else {
                            FacturaServicio::create([
                                'user_id' => $clienteUser->id,
                                'suscripcion_id' => $suscripcion->id,
                                'plan_id' => $planId,
                                'numero_factura' => 'FS-' . str_pad(FacturaServicio::max('id') + 1, 6, '0', STR_PAD_LEFT),
                                'concepto' => 'Suscripción ' . $plan->nombre . ' (Plan Gratis)',
                                'monto' => 0,
                                'moneda' => 'CLP',
                                'periodo_inicio' => now()->toDateString(),
                                'periodo_fin' => now()->addDays($duracionDias)->toDateString(),
                                'estado' => 'pagada',
                            ]);
                        }

                        Log::info("Suscripción creada para cliente {$clienteEmail}", [
                            'suscripcion_id' => $suscripcion->id,
                            'plan' => $plan->nombre,
                        ]);
                    }
                } else {
                    Log::info("Reconexión OAuth - se omite creación de suscripción para {$clienteUser->email}");
                }

                // 4. Registrar webhook orders/paid en Shopify
                $this->registerOrdersPaidWebhook($shop, $accessToken);

                Log::info("OAuth exitoso (admin) para shop: {$shop}", [
                    'admin_user_id' => auth()->id(),
                    'cliente_user_id' => $clienteUser->id,
                    'client_id' => $clientId,
                ]);

                // Limpiar sesión
                session()->forget([
                    'shopify_nonce', 'shopify_shop', 'lioren_api_key', 'solicitud_id', 
                    'oauth_origin', 'cliente_nombre', 'cliente_email', 'cliente_password',
                    'plan_id', 'facturacion_enabled', 'shopify_visibility', 'notas_credito_auto',
                    'sin_limite_pedidos', 'monthly_order_limit', 'client_type', 'existing_user_id',
                    'sync_inventario', 'oauth_client_id', 'oauth_client_secret'
                ]);

                $successMsg = $origin === 'reconnect'
                    ? "Cliente {$clienteNombre} reconectado exitosamente a {$shop}. Token y webhooks actualizados."
                    : "Cliente {$clienteNombre} creado y conectado exitosamente a {$shop}";

                return redirect()->route($successRoute)
                    ->with('success', $successMsg);

            } else {
                // Flujo CLIENTE: actualizar solicitud
                $solicitud = Solicitud::findOrFail($solicitudId);
                $solicitud->update([
                    'tienda_shopify' => $shop,
                    'access_token' => $accessToken,
                    'api_secret' => $clientSecret,
                    'api_key' => $liorenApiKey,
                ]);

                // Crear/actualizar IntegracionConfig
                IntegracionConfig::updateOrCreate(
                    ['user_id' => auth()->id()],
                    [
                        'solicitud_id' => $solicitudId,
                        'shopify_tienda' => $shop,
                        'shopify_token' => $accessToken,
                        'shopify_secret' => $clientSecret,
                        'shopify_client_id' => $clientId,
                        'shopify_client_secret' => $clientSecret,
                        'lioren_api_key' => $liorenApiKey,
                        'shop_domain' => $shop,
                        'auth_method' => 'oauth',
                        'oauth_installed_at' => now(),
                        'activo' => false,
                    ]
                );

                // Registrar webhook orders/paid en Shopify
                $this->registerOrdersPaidWebhook($shop, $accessToken);

                Log::info("OAuth exitoso (cliente) para shop: {$shop}", [
                    'user_id' => auth()->id(),
                    'solicitud_id' => $solicitudId
                ]);

                // Limpiar sesión
                session()->forget([
                    'shopify_nonce', 'shopify_shop', 'lioren_api_key', 'solicitud_id', 
                    'oauth_origin', 'oauth_client_id', 'oauth_client_secret'
                ]);

                return redirect()->route($successRoute)
                    ->with('success', 'Credenciales guardadas. Ahora puedes conectar la integración.');
            }

        } catch (\Exception $e) {
            Log::error('Error en callback OAuth: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Limpiar sesión
            session()->forget([
                'shopify_nonce', 'shopify_shop', 'lioren_api_key', 'solicitud_id', 
                'oauth_origin', 'cliente_nombre', 'cliente_email', 'cliente_password',
                'plan_id', 'facturacion_enabled', 'shopify_visibility', 'notas_credito_auto',
                'sin_limite_pedidos', 'monthly_order_limit', 'client_type', 'existing_user_id',
                'oauth_client_id', 'oauth_client_secret'
            ]);

            return redirect()->route($errorRoute)
                ->with('error', 'Error al conectar: ' . $e->getMessage());
        }
    }

    /**
     * Verificar firma HMAC de Shopify
     */
    private function verifyHmac(array $params, string $secret): bool
    {
        $hmac = $params['hmac'] ?? '';
        
        // Remover hmac y signature de los parámetros
        unset($params['hmac'], $params['signature']);
        
        // Ordenar alfabéticamente
        ksort($params);
        
        // Construir query string
        $queryString = http_build_query($params);
        
        // Calcular HMAC
        $calculatedHmac = hash_hmac('sha256', $queryString, $secret);
        
        // Comparación segura
        return hash_equals($calculatedHmac, $hmac);
    }

    /**
     * Registrar webhook orders/paid en Shopify
     */
    private function registerOrdersPaidWebhook(string $shop, string $accessToken): void
    {
        // Webhooks que van al receptor genérico de pedidos
        $topicsToReceiver = ['orders/paid', 'refunds/create', 'orders/cancelled', 'orders/edited'];
        $receiverUrl = config('app.url') . '/integracion/webhook-receiver';

        // Webhook de ciclo de vida (uninstall) → endpoint dedicado
        $lifecycleTopics = [
            'app/uninstalled' => config('app.url') . '/webhooks/app/uninstalled',
        ];

        try {
            // Obtener webhooks existentes (de cualquier URL)
            $existingResponse = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type' => 'application/json',
            ])->get("https://{$shop}/admin/api/2024-10/webhooks.json");

            $existingByAddress = [];
            if ($existingResponse->successful()) {
                $existingWebhooks = $existingResponse->json()['webhooks'] ?? [];
                foreach ($existingWebhooks as $wh) {
                    $existingByAddress[$wh['address']][] = $wh['topic'];
                }
            }

            // Construir el plan: topic → url
            $plan = [];
            foreach ($topicsToReceiver as $topic) {
                $plan[$topic] = $receiverUrl;
            }
            foreach ($lifecycleTopics as $topic => $url) {
                $plan[$topic] = $url;
            }

            // Registrar los que faltan
            foreach ($plan as $topic => $url) {
                $alreadyAtThisUrl = in_array($topic, $existingByAddress[$url] ?? []);
                if ($alreadyAtThisUrl) {
                    Log::info("Webhook {$topic} ya existe para {$shop} en {$url}");
                    continue;
                }

                $response = Http::withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                    'Content-Type' => 'application/json',
                ])->post("https://{$shop}/admin/api/2024-10/webhooks.json", [
                    'webhook' => [
                        'topic' => $topic,
                        'address' => $url,
                        'format' => 'json',
                    ]
                ]);

                if ($response->successful()) {
                    $webhookId = $response->json()['webhook']['id'] ?? null;
                    Log::info("Webhook {$topic} registrado exitosamente para {$shop}", ['webhook_id' => $webhookId]);
                } else {
                    Log::error("Error registrando webhook {$topic} para {$shop}", [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Excepcion registrando webhooks para {$shop}: " . $e->getMessage());
        }
    }

    /**
     * Intercambiar código OAuth por access token (con credenciales específicas)
     */
    private function exchangeCodeForToken(string $shop, string $code, string $clientId, string $clientSecret): string
    {
        $response = Http::post("https://{$shop}/admin/oauth/access_token", [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code
        ]);

        if ($response->failed()) {
            Log::error('Error intercambiando código por token', [
                'shop' => $shop,
                'status' => $response->status(),
                'body' => $response->body(),
                'client_id' => $clientId,
            ]);
            throw new \Exception('No se pudo obtener el access token de Shopify');
        }

        $data = $response->json();
        
        if (!isset($data['access_token'])) {
            throw new \Exception('Respuesta de Shopify no contiene access_token');
        }

        return $data['access_token'];
    }
}
