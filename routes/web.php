<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function (\Illuminate\Http\Request $request) {
    // Shopify App Store envía al application_url con ?shop=...
    // En ese caso redirigimos al flujo de instalación.
    if ($request->filled('shop')) {
        return redirect()->route('shopify.install', $request->query());
    }
    return redirect('/login');
});

// ====================================================================
// SHOPIFY APP STORE — Instalación pública (sin auth previa)
// ====================================================================
Route::get('/install', [App\Http\Controllers\ShopifyOAuthController::class, 'installFromAppStore'])
    ->name('shopify.install');

// Onboarding post-OAuth: el merchant configura API key Lioren
// GET sin middleware auth: maneja el caso de magic token (cookie de sesión perdida en cross-site OAuth)
Route::get('/onboarding', [App\Http\Controllers\AppStoreOnboardingController::class, 'show'])
    ->name('appstore.onboarding');
Route::post('/onboarding', [App\Http\Controllers\AppStoreOnboardingController::class, 'store'])
    ->middleware('auth')
    ->name('appstore.onboarding.store');

// Dashboard simplificado para merchants instalados desde App Store
// (NO muestra planes ni billing externo — solo estado de integración)
Route::get('/app/dashboard', [App\Http\Controllers\AppStoreDashboardController::class, 'index'])
    ->middleware('auth')
    ->name('appstore.dashboard');

// GET en endpoints de webhook GDPR → 200 amigable (evita 405 que Shopify marca como error)
Route::get('/webhooks/customers/data_request', [App\Http\Controllers\ShopifyGdprController::class, 'gdprInfo']);
Route::get('/webhooks/customers/redact', [App\Http\Controllers\ShopifyGdprController::class, 'gdprInfo']);
Route::get('/webhooks/shop/redact', [App\Http\Controllers\ShopifyGdprController::class, 'gdprInfo']);

// Webhook app/uninstalled: limpia token e inactiva integración cuando el merchant desinstala
Route::post('/webhooks/app/uninstalled', [App\Http\Controllers\ShopifyGdprController::class, 'appUninstalled']);
Route::get('/webhooks/app/uninstalled', [App\Http\Controllers\ShopifyGdprController::class, 'gdprInfo']);

// Privacy Policy y Terms of Service — requeridos por Shopify App Store
Route::get('/privacy', function () { return view('legal.privacy'); })->name('legal.privacy');
Route::get('/terms', function () { return view('legal.terms'); })->name('legal.terms');

Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
    // === REVENUE FILTERS (improved) ===
    $revenueFilter = $request->get('revenue_filter', 'all');
    $filterDate = $request->get('filter_date'); // specific date: Y-m-d
    $filterMonth = $request->get('filter_month'); // specific month: 1-12
    $filterYear = $request->get('filter_year'); // specific year: 2021-2026

    // === UF → CLP CONVERSION ===
    // Get current UF value from mindicador.cl API (cached for 1 hour)
    $ufValue = \Illuminate\Support\Facades\Cache::remember('uf_value_today', 21600, function () {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)->get('https://mindicador.cl/api/uf');
            if ($response->successful()) {
                $data = $response->json();
                return $data['serie'][0]['valor'] ?? 39841.72;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Could not fetch UF value: ' . $e->getMessage());
        }
        return 39841.72; // Fallback value
    });

    // === PARCHE_08_DASH_ROUTE - Revenue desde facturas_servicio.created_at ===
    $sumFacturas = function($filter) use ($filterDate, $filterMonth, $filterYear) {
        $q = \App\Models\FacturaServicio::where('estado', 'pagada');
        $dx = \DB::raw('COALESCE(pagada_at, created_at)');
        if ($filter === 'day') {
            $q->whereDate($dx, $filterDate ?: today());
        } elseif ($filter === 'month') {
            $q->whereYear($dx, $filterYear ?: now()->year)->whereMonth($dx, $filterMonth ?: now()->month);
        } elseif ($filter === 'year') {
            $q->whereYear($dx, $filterYear ?: now()->year);
        }
        return ['revenue' => (int) $q->sum('monto'), 'count' => $q->count()];
    };
    $r = $sumFacturas($revenueFilter);
    $totalRevenue = $r['revenue'];
    $totalPayments = $r['count'];
    $totalPlanCount = $r['count'];
    $totalPlanRevenue = $r['revenue'];
    $totalPaymentRevenue = $r['revenue'];
    $revenueToday = $sumFacturas('day')['revenue'];
    $revenueMonth = $sumFacturas('month')['revenue'];

    // Recent subscribers
    $recentSubscribers = \App\Models\Suscripcion::with(['user', 'plan'])
        ->latest()
        ->take(4)
        ->get();

    // Filter metadata for the view
    $currentYear = (int) now()->year;
    $years = range($currentYear, $currentYear - 4);
    $months = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];

    return view('dashboard', compact(
        'totalRevenue', 'revenueToday', 'revenueMonth',
        'totalPayments', 'totalPlanCount', 'totalPlanRevenue', 'totalPaymentRevenue',
        'revenueFilter', 'recentSubscribers', 'ufValue',
        'years', 'months', 'filterDate', 'filterMonth', 'filterYear'
    ));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/profile/billing', [ProfileController::class, 'updateBilling'])->name('profile.billing.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::post('/notificaciones/marcar-vistas', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $keys = $request->input('keys', []);
        if (!is_array($keys)) { $keys = []; }
        $current = is_array($user->notif_dismissed ?? null) ? $user->notif_dismissed : [];
        $merged = array_values(array_unique(array_merge($current, $keys)));
        $user->notif_dismissed = array_slice($merged, -100);
        $user->save();
        return response()->json(['ok' => true, 'count' => count($user->notif_dismissed)]);
    })->name('notificaciones.marcar-vistas');
    Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])->name('profile.photo.delete');
});

// User Management Routes - Only accessible by admin role
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('usuarios', UserController::class);
});

// Clientes CRUD - Only accessible by admin role
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('clientes', App\Http\Controllers\AdminClienteController::class);
});

// Planes CRUD - Only accessible by admin role
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('planes', App\Http\Controllers\PlanController::class);
});

// Integración Shopify-Lioren Routes - SOLO ADMIN
Route::middleware(['auth', 'role:admin'])->prefix('integracion')->name('integracion.')->group(function () {
    Route::get('/', [App\Http\Controllers\IntegracionController::class, 'dashboard'])->name('index');
    Route::get('/dashboard', function () { return redirect()->route('integracion.index'); })->name('dashboard');
    Route::get('/configurar', [App\Http\Controllers\IntegracionController::class, 'index'])->name('configurar');
    Route::post('/procesar', [App\Http\Controllers\IntegracionController::class, 'procesar'])->name('procesar');
    Route::get('/productos', [App\Http\Controllers\IntegracionController::class, 'productos'])->name('productos');
    Route::get('/productos-lioren', [App\Http\Controllers\IntegracionController::class, 'productosLioren'])->name('productos-lioren');
    Route::get('/estado', [App\Http\Controllers\IntegracionController::class, 'estado'])->name('estado');
    Route::get('/resetear', [App\Http\Controllers\IntegracionController::class, 'confirmarReset'])->name('resetear');
    Route::delete('/resetear', [App\Http\Controllers\IntegracionController::class, 'resetearIntegracion'])->name('resetear.ejecutar');

    // Configuración de Bodegas
    Route::get('/bodegas', [App\Http\Controllers\WarehouseConfigController::class, 'index'])->name('bodegas');
    Route::get('/bodegas/config', [App\Http\Controllers\WarehouseConfigController::class, 'getConfig'])->name('bodegas.config');
    Route::get('/bodegas/lioren', [App\Http\Controllers\WarehouseConfigController::class, 'getLiorenBodegas'])->name('bodegas.lioren');
    Route::get('/bodegas/shopify-locations', [App\Http\Controllers\WarehouseConfigController::class, 'getShopifyLocations'])->name('bodegas.shopify');
    Route::post('/bodegas/configure-simple', [App\Http\Controllers\WarehouseConfigController::class, 'configureSimple'])->name('bodegas.configure-simple');
    Route::post('/bodegas/configure-advanced', [App\Http\Controllers\WarehouseConfigController::class, 'configureAdvanced'])->name('bodegas.configure-advanced');
    Route::post('/bodegas/modo', [App\Http\Controllers\WarehouseConfigController::class, 'setMode'])->name('bodegas.modo');
    Route::post('/bodegas/mapeo', [App\Http\Controllers\WarehouseConfigController::class, 'saveMapping'])->name('bodegas.mapeo');
    Route::delete('/bodegas/mapeo/{locationId}', [App\Http\Controllers\WarehouseConfigController::class, 'deleteMapping'])->name('bodegas.delete');

    // Inventario - Sincronización y Mapeo de Productos
    Route::get("/inventario", [App\Http\Controllers\InventoryController::class, "index"])->name("inventario");
    Route::post("/inventario/sync", [App\Http\Controllers\InventoryController::class, "fullSync"])->name("inventario.sync");
    Route::post("/inventario/map", [App\Http\Controllers\InventoryController::class, "mapProduct"])->name("inventario.map");
    Route::post("/inventario/unmap", [App\Http\Controllers\InventoryController::class, "unmapProduct"])->name("inventario.unmap");
    Route::get("/inventario/lioren-products", [App\Http\Controllers\InventoryController::class, "getLiorenProducts"])->name("inventario.lioren-products");
    Route::get("/inventario/sync-logs", [App\Http\Controllers\InventoryController::class, "getSyncLogs"])->name("inventario.sync-logs");
    // Correo masivo
    // Correos Integraciones - Pagina dedicada
    Route::get('/correos', [App\Http\Controllers\IntegracionController::class, 'correosIntegracion'])->name('correos');
    Route::post('/correo-individual', [App\Http\Controllers\IntegracionController::class, 'correoIndividual'])->name('correo-individual');
    Route::post('/correo-masivo', [App\Http\Controllers\IntegracionController::class, 'correoMasivo'])->name('correo-masivo');
});
// Shopify OAuth 2.0 Routes - ADMIN
Route::middleware(["auth", "role:admin"])->group(function () {
    Route::post("/admin/shopify/oauth/iniciar", [App\Http\Controllers\ShopifyOAuthController::class, "iniciarOAuthAdmin"])->name("admin.shopify.oauth.iniciar");
    Route::post("/admin/shopify/oauth/reconectar", [App\Http\Controllers\ShopifyOAuthController::class, "reconectarOAuth"])->name("admin.shopify.oauth.reconectar");
});

// Boletas Routes - SOLO ADMIN
Route::middleware(['auth', 'role:admin'])->prefix('boletas')->name('boletas.')->group(function () {
    Route::get('/', [App\Http\Controllers\AdminBoletaController::class, 'index'])->name('index');
    Route::get('/emitir', [App\Http\Controllers\IntegracionController::class, 'boletasForm'])->name('form');
    Route::post('/emitir', [App\Http\Controllers\IntegracionController::class, 'emitirBoleta'])->name('emitir');
});

// Notas de Crédito Routes - SOLO ADMIN
Route::middleware(['auth', 'role:admin'])->prefix('notas-credito')->name('notas-credito.')->group(function () {
    Route::get('/', [App\Http\Controllers\IntegracionController::class, 'notasCredito'])->name('index');
    Route::get('/{id}/pdf', [App\Http\Controllers\IntegracionController::class, 'notaCreditoPdf'])->name('pdf');
    Route::get('/{id}/xml', [App\Http\Controllers\IntegracionController::class, 'notaCreditoXml'])->name('xml');
});

// Configuración de Bodegas/Locations - SOLO ADMIN
Route::middleware(['auth', 'role:admin'])->prefix('warehouse')->name('warehouse.')->group(function () {
    Route::get('/config', [App\Http\Controllers\WarehouseConfigController::class, 'index'])->name('config');
    Route::post('/config/simple', [App\Http\Controllers\WarehouseConfigController::class, 'configureSimple'])->name('config.simple');
    Route::post('/config/advanced', [App\Http\Controllers\WarehouseConfigController::class, 'configureAdvanced'])->name('config.advanced');
    Route::post('/config/mapping', [App\Http\Controllers\WarehouseConfigController::class, 'createMapping'])->name('config.mapping');
    Route::delete('/config/mapping/{locationId}', [App\Http\Controllers\WarehouseConfigController::class, 'deleteMapping'])->name('config.mapping.delete');
    Route::get('/config/refresh-bodegas', [App\Http\Controllers\WarehouseConfigController::class, 'refreshBodegas'])->name('config.refresh');
});

// Boletas PDF/XML - SOLO ADMIN
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/boletas/{id}/pdf', [App\Http\Controllers\IntegracionController::class, 'boletaPdf'])->name('boletas.pdf');
    Route::get('/boletas/{id}/xml', [App\Http\Controllers\IntegracionController::class, 'boletaXml'])->name('boletas.xml');
});

// Facturas Emitidas PDF/XML - SOLO ADMIN
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/facturas-emitidas/{id}/pdf', [App\Http\Controllers\AdminBoletaController::class, 'facturaPdf'])->name('admin.facturas.pdf');
    Route::get('/facturas-emitidas/{id}/xml', [App\Http\Controllers\AdminBoletaController::class, 'facturaXml'])->name('admin.facturas.xml');
    Route::post("/documentos/{source}/{id}/reemitir", [App\Http\Controllers\AdminBoletaController::class, "reemitir"])->name("admin.documentos.reemitir");
});

// Rutas para CLIENTES
Route::middleware(['auth', 'role:cliente'])->prefix('cliente')->name('cliente.')->group(function () {
    // Inventario - accesible por clientes
    Route::get("/inventario", [App\Http\Controllers\InventoryController::class, "index"])->name("inventario");
    Route::post("/inventario/sync", [App\Http\Controllers\InventoryController::class, "fullSync"])->name("inventario.sync");
    Route::post("/inventario/map", [App\Http\Controllers\InventoryController::class, "mapProduct"])->name("inventario.map");
    Route::post("/inventario/unmap", [App\Http\Controllers\InventoryController::class, "unmapProduct"])->name("inventario.unmap");
    Route::get("/inventario/lioren-products", [App\Http\Controllers\InventoryController::class, "getLiorenProducts"])->name("inventario.lioren-products");
    Route::get("/inventario/sync-logs", [App\Http\Controllers\InventoryController::class, "getSyncLogs"])->name("inventario.sync-logs");
    // Correo masivo
    Route::post('/correo-masivo', [App\Http\Controllers\IntegracionController::class, 'correoMasivo'])->name('correo-masivo');
    Route::get('/dashboard', [App\Http\Controllers\ClienteController::class, 'dashboard'])->name('dashboard');
    Route::get('/planes', [App\Http\Controllers\ClienteController::class, 'planes'])->name('planes');
    Route::get('/estados-solicitud', [App\Http\Controllers\ClienteController::class, 'estadosSolicitud'])->name('estados-solicitud');
    Route::get('/planes-activos', [App\Http\Controllers\ClienteController::class, 'planesActivos'])->name('planes-activos');
    // LEGACY: /cliente/facturas redirige a /cliente/facturas-servicio (ambas mostraban lo mismo).
    // Se conserva el name() para que los routeIs() existentes no se rompan.
    Route::get('/facturas', function () { return redirect()->route('cliente.facturas-servicio'); })->name('facturas');
    Route::post('/pago-transferencia', [App\Http\Controllers\ClienteController::class, 'pagoTransferencia'])->name('pago-transferencia');
    Route::get('/facturas-servicio', [App\Http\Controllers\FacturaServicioController::class, 'index'])->name('facturas-servicio');
    // Documentos Emitidos (boletas + facturas de integración)
    Route::get('/documentos-emitidos', [App\Http\Controllers\ClienteController::class, 'documentosEmitidos'])->name('documentos-emitidos');

    // Trazabilidad de SKU: rastrear ventas por producto (solo del cliente actual).
    Route::get('/trazabilidad-sku', [App\Http\Controllers\TrazabilidadController::class, 'index'])->name('trazabilidad-sku');

    Route::get('/documentos/{tipo}/{id}/pdf', [App\Http\Controllers\ClienteController::class, 'documentoPdf'])->name('documento.pdf');
    Route::get('/documentos/{tipo}/{id}/xml', [App\Http\Controllers\ClienteController::class, 'documentoXml'])->name('documento.xml');

    // Suscripciones y Pagos
    Route::get('/suscripciones', [App\Http\Controllers\SuscripcionController::class, 'index'])->name('suscripciones');
    Route::get('/suscripciones/{suscripcion}/renovar', [App\Http\Controllers\SuscripcionController::class, 'renovar'])->name('suscripciones.renovar');
    Route::delete('/suscripciones/{suscripcion}/cancelar', [App\Http\Controllers\SuscripcionController::class, 'cancelar'])->name('suscripciones.cancelar');

    // Chats
    Route::get('/chats', [App\Http\Controllers\ChatController::class, 'index'])->name('chats');
    Route::post('/chats', [App\Http\Controllers\ChatController::class, 'store'])->name('chats.store');
    Route::get("/chats/unread-count", [App\Http\Controllers\ChatController::class, "getUnreadCountCliente"])->name("chats.unreadCount");

    // Solicitudes
    Route::post('/solicitudes', [App\Http\Controllers\SolicitudController::class, 'store'])->name('solicitudes.store');
    Route::get('/solicitudes/{solicitud}/config', [App\Http\Controllers\SolicitudController::class, 'getConfig'])->name('solicitudes.getConfig');
    Route::post('/solicitudes/{solicitud}/config', [App\Http\Controllers\SolicitudController::class, 'updateConfig'])->name('solicitudes.updateConfig');
    
    // Credenciales de Integración (Cliente)
    Route::get('/solicitudes/credenciales', [App\Http\Controllers\SolicitudController::class, 'credenciales'])->name('solicitudes.credenciales');
    Route::get('/solicitudes/{solicitud}/credenciales', [App\Http\Controllers\SolicitudController::class, 'credenciales'])->name('solicitudes.credenciales-id');
    Route::put('/solicitudes/{solicitud}/credenciales', [App\Http\Controllers\SolicitudController::class, 'guardarCredenciales'])->name('solicitudes.guardar-credenciales');
    
    // Shopify OAuth 2.0 Routes
    Route::post('/shopify/oauth/iniciar', [App\Http\Controllers\ShopifyOAuthController::class, 'iniciarOAuth'])->name('shopify.oauth.iniciar');
});

// Shopify OAuth Callback (sin middleware de rol porque Shopify redirige aquí)
// Shopify OAuth Callback — PÚBLICO (Shopify redirige aquí desde sus servidores,
// el usuario puede no estar logueado en Laravel cuando llega de cross-site OAuth).
// El callback valida HMAC + state internamente para garantizar seguridad.
Route::get('/shopify/oauth/callback', [App\Http\Controllers\ShopifyOAuthController::class, 'handleCallback'])->name('shopify.oauth.callback');

// Rutas de Chat (compartidas entre admin y cliente)
Route::middleware(['auth'])->group(function () {
    Route::get('/chats/{chat}', [App\Http\Controllers\ChatController::class, 'show'])->name('chats.show');
    Route::post('/chats/{chat}/messages', [App\Http\Controllers\ChatController::class, 'sendMessage'])->name('chats.sendMessage');
    Route::get('/chats/{chat}/new-messages', [App\Http\Controllers\ChatController::class, 'getNewMessages'])->name('chats.getNewMessages');
    Route::post('/chats/{chat}/close', [App\Http\Controllers\ChatController::class, 'close'])->name('chats.close');
    Route::post("/chats/{chat}/mark-read", [App\Http\Controllers\ChatController::class, "markAsRead"])->name("chats.markAsRead");
});

// Rutas de Chat para ADMIN
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/chats', [App\Http\Controllers\ChatController::class, 'adminIndex'])->name('chats');
    Route::get('/chats/unread-count', [App\Http\Controllers\ChatController::class, 'getUnreadCount'])->name('chats.unreadCount');
    Route::post("/chats/create-for-client", [App\Http\Controllers\ChatController::class, "createForClient"])->name("chats.createForClient");
    Route::get('/solicitudes', [App\Http\Controllers\SolicitudController::class, 'index'])->name('solicitudes');
    Route::get('/solicitudes/{solicitud}', [App\Http\Controllers\SolicitudController::class, 'show'])->name('solicitudes.show');
    Route::post('/solicitudes/{solicitud}/estado', [App\Http\Controllers\SolicitudController::class, 'updateEstado'])->name('solicitudes.updateEstado');
    
    // Solicitudes Pendientes de Conexión (Admin)
    Route::get('/solicitudes-pendientes-conexion', [App\Http\Controllers\SolicitudController::class, 'pendientesConexion'])->name('solicitudes.pendientes-conexion');
    Route::post('/solicitudes/{solicitud}/conectar', [App\Http\Controllers\SolicitudController::class, 'conectarIntegracion'])->name('solicitudes.conectar');
    Route::post('/solicitudes/{solicitud}/rechazar', [App\Http\Controllers\SolicitudController::class, 'rechazar'])->name('solicitudes.rechazar');
    
    // Suscripciones Admin
    Route::get('/suscripciones', [App\Http\Controllers\SuscripcionController::class, 'admin'])->name('suscripciones');

    // Suscripciones Admin - Gestión
    Route::post("/suscripciones/crear-manual", [App\Http\Controllers\AdminSuscripcionController::class, "crearManual"])->name("suscripciones.crear-manual");
    Route::delete("/suscripciones/{suscripcion}/cancelar", [App\Http\Controllers\AdminSuscripcionController::class, "cancelar"])->name("suscripciones.cancelar");
    Route::post("/suscripciones/{suscripcion}/reactivar", [App\Http\Controllers\AdminSuscripcionController::class, "reactivar"])->name("suscripciones.reactivar");
    Route::post("/suscripciones/{suscripcion}/renovar-manual", [App\Http\Controllers\AdminSuscripcionController::class, "renovarManual"])->name("suscripciones.renovar-manual");

    // Boletas Admin - Vista de documentos emitidos
    Route::get("/boletas-emitidas", [App\Http\Controllers\AdminBoletaController::class, "index"])->name("boletas.index");

    // Pagos por Transferencia - Admin (LEGACY)
    // GET /transferencias ahora redirige a /pagos-recibidos (vista unificada).
    // Las acciones POST se mantienen por si algun dia se reactiva el flujo de aprobacion.
    Route::get('/transferencias', function () { return redirect()->route('admin.pagos-recibidos.index'); })->name('transferencias.index');
    Route::post('/transferencias/{id}/aprobar', [App\Http\Controllers\PagoTransferenciaController::class, 'aprobar'])->name('transferencias.aprobar');
    Route::post('/transferencias/{id}/rechazar', [App\Http\Controllers\PagoTransferenciaController::class, 'rechazar'])->name('transferencias.rechazar');
});

// Flow Payment Routes
Route::prefix('flow')->name('flow.')->group(function () {
    // Ruta de prueba simple
    Route::get('/debug-payment', function () {
        $controller = new App\Http\Controllers\FlowController();

        $params = [
            'apiKey' => config('flow.api_key'),
            'commerceOrder' => 'DEBUG_' . time(),
            'subject' => 'Test Debug',
            'currency' => 'CLP',
            'amount' => 1000,
            'email' => 'elianfa3000@gmail.com',
            'urlConfirmation' => route('flow.confirmation'),
            'urlReturn' => route('flow.return'),
        ];

        // Usar el método del controlador
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('signParams');
        $method->setAccessible(true);
        $signature = $method->invoke($controller, $params);
        $params['s'] = $signature;

        try {
            $response = Http::withoutVerifying()->asForm()->post(config('flow.api_url') . '/payment/create', $params);

            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'response' => $response->json(),
                'params_sent' => $params,
                'api_url' => config('flow.api_url'),
                'environment' => config('flow.environment'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'params_sent' => $params,
            ], 500);
        }
    })->name('debug-payment');

    // Rutas públicas (sin autenticación)
    Route::match(['GET', 'POST'], '/return', [App\Http\Controllers\FlowController::class, 'returnFromFlow'])->name('return');
    Route::post('/confirmation', [App\Http\Controllers\FlowController::class, 'confirmationWebhook'])->name('confirmation');

    // Ruta de prueba temporal sin autenticación
    Route::get('/test-payment', function () {
        $apiKey = config('flow.api_key');
        $secretKey = config('flow.secret_key');
        $apiUrl = config('flow.api_url');

        // Parámetros exactos según documentación
        $params = [
            'apiKey' => $apiKey,
            'commerceOrder' => uniqid('ORDER-'),
            'subject' => 'Pago de prueba',
            'currency' => 'CLP',
            'amount' => 1000,
            'email' => auth()->user()->email ?? 'test@example.com',
            'urlConfirmation' => route('flow.confirmation'),
            'urlReturn' => route('flow.return'),
            'optional' => json_encode([
                'test' => 'value'
            ]),
        ];

        // Firmar parámetros con HMAC SHA256
        ksort($params);
        $toSign = '';
        foreach ($params as $key => $value) {
            $toSign .= $key . $value;
        }
        $signature = hash_hmac('sha256', $toSign, $secretKey);
        $params['s'] = $signature;

        try {
            $response = Http::asForm()->post("{$apiUrl}/payment/create", $params);

            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
                'redirect_url' => $response->successful() ? ($response->json()['url'] ?? null) : null,
                'payment_data_sent' => $params,
                'api_url' => $apiUrl,
                'environment' => config('flow.environment'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'payment_data_sent' => $params,
                'api_url' => $apiUrl,
                'environment' => config('flow.environment'),
            ], 500);
        }
    })->name('test-payment');

    // Rutas protegidas
    Route::middleware('auth')->group(function () {
        Route::get('/payment-form', [App\Http\Controllers\FlowController::class, 'showPaymentForm'])->name('payment-form');
        Route::post('/create-payment', [App\Http\Controllers\FlowController::class, 'createPayment'])->name('create-payment');
        Route::post('/create-plan-payment', [App\Http\Controllers\FlowController::class, 'createPlanPayment'])->name('create-plan-payment');
    });
});

// Payment result pages
Route::middleware('auth')->group(function () {
    Route::get('/payment/success', function () {
        return view('flow.success');
    })->name('payment.success');

    Route::get('/payment/error', function () {
        return view('flow.error');
    })->name('payment.error');

    Route::get('/payment/pending', function () {
        return view('flow.pending');
    })->name('payment.pending');
});

// Webhook receiver - Public route (no auth required)
// Rutas de pago de facturas de servicio
Route::middleware(['auth'])->group(function () {
    Route::post('/factura-servicio/pagar', [App\Http\Controllers\FacturaServicioController::class, 'pagarFactura'])->name('factura-servicio.pagar');
    Route::get('/factura-servicio/pdf/{id}', [App\Http\Controllers\FacturaServicioController::class, 'descargarPDF'])->name('factura-servicio.pdf');
});
Route::get('/factura-servicio/return', [App\Http\Controllers\FacturaServicioController::class, 'returnFromFlow'])->name('factura-servicio.return');
Route::post('/factura-servicio/confirmation', [App\Http\Controllers\FacturaServicioController::class, 'confirmationWebhook'])->name('factura-servicio.confirmation');

Route::post('/integracion/webhook-receiver', [App\Http\Controllers\IntegracionController::class, 'webhookReceiver'])->name('integracion.webhook');

// Shopify GDPR Webhooks - Public routes (no auth required)
Route::post('/webhooks/customers/data_request', [App\Http\Controllers\ShopifyGdprController::class, 'customersDataRequest'])->name('webhooks.customers.data_request');
Route::post('/webhooks/customers/redact', [App\Http\Controllers\ShopifyGdprController::class, 'customersRedact'])->name('webhooks.customers.redact');
Route::post('/webhooks/shop/redact', [App\Http\Controllers\ShopifyGdprController::class, 'shopRedact'])->name('webhooks.shop.redact');

require __DIR__ . '/auth.php';

// Ruta de prueba temporal para Flow
Route::get('/test-flow', function () {
    $apiKey = config('flow.api_key');
    $secretKey = config('flow.secret_key');

    // Test básico de conexión con parámetros mínimos
    $url = 'https://www.flow.cl/api/payment/create';

    // Probar con diferentes combinaciones de parámetros
    $testCases = [
        'minimal' => [
            'apiKey' => $apiKey,
            'commerceOrder' => 'TEST_' . time(),
            'subject' => 'Test',
            'amount' => 1000,
            'email' => 'test@example.com',
            'urlConfirmation' => route('flow.confirmation'),
            'urlReturn' => route('flow.return'),
        ],
        'with_currency' => [
            'apiKey' => $apiKey,
            'commerceOrder' => 'TEST_' . time(),
            'subject' => 'Test',
            'currency' => 'CLP',
            'amount' => 1000,
            'email' => 'test@example.com',
            'urlConfirmation' => route('flow.confirmation'),
            'urlReturn' => route('flow.return'),
        ],
        'with_payment_method' => [
            'apiKey' => $apiKey,
            'commerceOrder' => 'TEST_' . time(),
            'subject' => 'Test',
            'currency' => 'CLP',
            'amount' => 1000,
            'email' => 'test@example.com',
            'paymentMethod' => 1,
            'urlConfirmation' => route('flow.confirmation'),
            'urlReturn' => route('flow.return'),
        ]
    ];

    $results = [];

    foreach ($testCases as $testName => $data) {
        // Ordenar los datos alfabéticamente
        ksort($data);

        // Crear firma
        $toSign = '';
        foreach ($data as $key => $value) {
            $toSign .= $key . $value;
        }
        $toSign .= $secretKey;
        $signature = hash('sha256', $toSign);
        $data['s'] = $signature;

        try {
            $response = Http::withoutVerifying()->post($url, $data);
            $results[$testName] = [
                'status' => $response->status(),
                'body' => $response->json(),
                'params_count' => count($data) - 1, // -1 para excluir la firma
            ];
        } catch (\Exception $e) {
            $results[$testName] = [
                'error' => $e->getMessage(),
                'params_count' => count($data) - 1,
            ];
        }
    }

    return response()->json([
        'test_results' => $results,
        'credentials' => [
            'api_key' => substr($apiKey, 0, 10) . '...',
            'has_secret' => !empty($secretKey),
        ]
    ]);
});

// === Admin Settings (Meta Pixel, etc.) ===
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Admin Billing Management
    Route::get('/billing', [App\Http\Controllers\AdminBillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/{userId}', [App\Http\Controllers\AdminBillingController::class, 'show'])->name('billing.show');
    Route::post('/billing/toggle-pausa/{suscripcionId}', [App\Http\Controllers\AdminBillingController::class, 'togglePausa'])->name('billing.toggle-pausa');
    Route::post('/billing/reiniciar-ciclo/{suscripcionId}', [App\Http\Controllers\AdminBillingController::class, 'reiniciarCiclo'])->name('billing.reiniciar-ciclo');
    Route::post('/billing/reemitir-dte/{facturaId}', [App\Http\Controllers\AdminBillingController::class, 'reemitirDTE'])->name('billing.reemitir-dte');
    Route::post('/billing/marcar-pagada/{facturaId}', [App\Http\Controllers\AdminBillingController::class, 'marcarPagada'])->name('billing.marcar-pagada');
    Route::get('/billing/factura-pdf/{facturaId}', [App\Http\Controllers\AdminBillingController::class, 'descargarPDF'])->name('billing.factura-pdf');

    // Pagos Recibidos: vista unificada que reemplaza /admin/transferencias.
    // Fuente unica de verdad sobre todos los pagos (Flow + transferencias + manuales).
    Route::get('/pagos-recibidos', [App\Http\Controllers\PagosRecibidosController::class, 'index'])->name('pagos-recibidos.index');

    // Monitoreo: panel de pedidos pagados sin boleta (detecta y permite emitir con 1 clic).
    Route::get('/pedidos-sin-boleta', [App\Http\Controllers\AdminMonitoreoController::class, 'pedidosSinBoleta'])->name('pedidos-sin-boleta.index');
    Route::post('/pedidos-sin-boleta/emitir', [App\Http\Controllers\AdminMonitoreoController::class, 'emitirPedidoSinBoleta'])->name('pedidos-sin-boleta.emitir');

    // Trazabilidad de SKU para admin: ve TODAS las ventas, puede filtrar por cliente.
    Route::get('/trazabilidad-sku', [App\Http\Controllers\TrazabilidadController::class, 'index'])->name('trazabilidad-sku');

    Route::get('/settings', [App\Http\Controllers\SettingController::class, 'index'])->name('settings');
    Route::put('/settings', [App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');
});

// === Cobros Asignados ===
// Admin: gestionar cobros asignados a clientes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/cobros-asignados', [App\Http\Controllers\CobroAsignadoController::class, 'index'])->name('cobros-asignados.index');
    Route::post('/cobros-asignados', [App\Http\Controllers\CobroAsignadoController::class, 'store'])->name('cobros-asignados.store');
    Route::patch('/cobros-asignados/{cobro}/anular', [App\Http\Controllers\CobroAsignadoController::class, 'anular'])->name('cobros-asignados.anular');
});

// Cliente: ver sus cobros pendientes
Route::middleware(['auth'])->prefix('cliente')->name('cliente.')->group(function () {
    Route::get('/cobros-pendientes', [App\Http\Controllers\CobroAsignadoController::class, 'misCobros'])->name('cobros-pendientes');
});


// ==========================================
// MÓDULO SERVICIOS AGENCIA (independiente)
// ==========================================
Route::middleware(['auth', 'role:admin'])->prefix('agencia')->name('agencia.')->group(function () {
    // Dashboard
    Route::get('/', [App\Http\Controllers\AgenciaController::class, 'dashboard'])->name('dashboard');

    // Reportes de Performance Meta Ads (datos reales o demo)
    Route::get('/reportes/meta-demo', [App\Http\Controllers\MetaAdsController::class, 'reporte'])->name('reportes.meta-demo');
    Route::get('/reportes/meta', [App\Http\Controllers\MetaAdsController::class, 'reporte'])->name('reportes.meta');

    // Conexión de cuentas Meta Ads
    Route::get('/reportes/conexion', [App\Http\Controllers\MetaAdsController::class, 'index'])->name('reportes.conexion');
    Route::post('/reportes/conexion/token', [App\Http\Controllers\MetaAdsController::class, 'guardarToken'])->name('reportes.conexion.token');
    Route::post('/reportes/conexion/cuenta', [App\Http\Controllers\MetaAdsController::class, 'vincularCuenta'])->name('reportes.conexion.cuenta');
    Route::delete('/reportes/conexion/cuenta/{cuenta}', [App\Http\Controllers\MetaAdsController::class, 'eliminarCuenta'])->name('reportes.conexion.cuenta.eliminar');
    Route::post('/reportes/conexion/cuenta/{cuenta}/sincronizar', [App\Http\Controllers\MetaAdsController::class, 'sincronizar'])->name('reportes.conexion.sincronizar');
    Route::post('/reportes/conexion/cuenta/{cuenta}/envio', [App\Http\Controllers\MetaAdsController::class, 'guardarEnvio'])->name('reportes.conexion.envio');
    Route::post('/reportes/conexion/cuenta/{cuenta}/enviar-ahora', [App\Http\Controllers\MetaAdsController::class, 'enviarAhora'])->name('reportes.conexion.enviar-ahora');


    // Clientes
    Route::get('/clientes', [App\Http\Controllers\AgenciaController::class, 'clientes'])->name('clientes');
    Route::get('/clientes/crear', [App\Http\Controllers\AgenciaController::class, 'clienteCreate'])->name('clientes.create');
    Route::post('/clientes', [App\Http\Controllers\AgenciaController::class, 'clienteStore'])->name('clientes.store');
    Route::get('/clientes/{cliente}/editar', [App\Http\Controllers\AgenciaController::class, 'clienteEdit'])->name('clientes.edit');
    Route::put('/clientes/{cliente}', [App\Http\Controllers\AgenciaController::class, 'clienteUpdate'])->name('clientes.update');
    Route::delete('/clientes/{cliente}', [App\Http\Controllers\AgenciaController::class, 'clienteDelete'])->name('clientes.delete');
    Route::get('/clientes/{cliente}/ver', [App\Http\Controllers\AgenciaController::class, 'clienteVer'])->name('clientes.ver');

    // Servicios (catálogo)
    Route::get('/servicios', [App\Http\Controllers\AgenciaController::class, 'servicios'])->name('servicios');
    Route::post('/servicios', [App\Http\Controllers\AgenciaController::class, 'servicioStore'])->name('servicios.store');
    Route::put('/servicios/{servicio}', [App\Http\Controllers\AgenciaController::class, 'servicioUpdate'])->name('servicios.update');
    Route::post('/servicios/{servicio}/toggle', [App\Http\Controllers\AgenciaController::class, 'servicioToggle'])->name('servicios.toggle');
    Route::delete('/servicios/{servicio}', [App\Http\Controllers\AgenciaController::class, 'servicioDelete'])->name('servicios.delete');

    // Asignaciones (cliente-servicio)
    Route::post('/asignaciones', [App\Http\Controllers\AgenciaController::class, 'asignacionStore'])->name('asignaciones.store');
    Route::put('/asignaciones/{asignacion}', [App\Http\Controllers\AgenciaController::class, 'asignacionUpdate'])->name('asignaciones.update');
    Route::delete('/asignaciones/{asignacion}', [App\Http\Controllers\AgenciaController::class, 'asignacionDelete'])->name('asignaciones.delete');

    // Suscripciones
    Route::get('/suscripciones', [App\Http\Controllers\AgenciaController::class, 'suscripciones'])->name('suscripciones');
    Route::post('/suscripciones', [App\Http\Controllers\AgenciaController::class, 'suscripcionStore'])->name('suscripciones.store');
    Route::post('/suscripciones/{suscripcion}/cancelar', [App\Http\Controllers\AgenciaController::class, 'suscripcionCancelar'])->name('suscripciones.cancelar');
    Route::post('/suscripciones/{suscripcion}/reactivar', [App\Http\Controllers\AgenciaController::class, 'suscripcionReactivar'])->name('suscripciones.reactivar');
    Route::post('/suscripciones/{suscripcion}/pausar', [App\Http\Controllers\AgenciaController::class, 'suscripcionPausar'])->name('suscripciones.pausar');
    Route::delete('/suscripciones/{suscripcion}/eliminar', [App\Http\Controllers\AgenciaController::class, 'suscripcionEliminar'])->name('suscripciones.eliminar');
    Route::post('/suscripciones/{suscripcion}/confirmar-pago', [App\Http\Controllers\AgenciaController::class, 'suscripcionConfirmarPago'])->name('suscripciones.confirmar-pago');

    // Cobros
    Route::get('/cobros', [App\Http\Controllers\AgenciaController::class, 'cobros'])->name('cobros');
    Route::post('/cobros', [App\Http\Controllers\AgenciaController::class, 'cobroStore'])->name('cobros.store');
    Route::post('/cobros/{cobro}/pagar', [App\Http\Controllers\AgenciaController::class, 'cobroMarcarPagado'])->name('cobros.pagar');
    Route::post('/cobros/{cobro}/anular', [App\Http\Controllers\AgenciaController::class, 'cobroAnular'])->name('cobros.anular');
    Route::post('/cobros/{cobro}/anular-pago', [App\Http\Controllers\AgenciaController::class, 'cobroAnularPago'])->name('cobros.anular-pago');
    Route::get("/cobros/{cobro}/ver-factura", [App\Http\Controllers\AgenciaController::class, "verFactura"])->name("cobros.ver-factura");
    Route::post("/cobros/{cobro}/reenviar-factura", [App\Http\Controllers\AgenciaController::class, "reenviarFactura"])->name("cobros.reenviar-factura");
    Route::post('/cobros/{cobro}/enviar-correo', [App\Http\Controllers\AgenciaController::class, 'cobroEnviarCorreo'])->name('cobros.enviar-correo');
    Route::get('/cobros/{cobro}/comprobante', [App\Http\Controllers\AgenciaController::class, 'cobroVerComprobante'])->name('cobros.comprobante');
    Route::post("/cobros/{cobro}/flow", [App\Http\Controllers\AgenciaController::class, "crearPagoFlow"])->name("cobros.flow");
    // Cotizaciones
    Route::get("/cotizaciones", [App\Http\Controllers\AgenciaController::class, "cotizaciones"])->name("cotizaciones");
    Route::post("/cotizaciones", [App\Http\Controllers\AgenciaController::class, "cotizacionStore"])->name("cotizaciones.store");
    Route::post("/cotizaciones/{cotizacion}/enviar", [App\Http\Controllers\AgenciaController::class, "cotizacionEnviar"])->name("cotizaciones.enviar");
    Route::post("/cotizaciones/{cotizacion}/facturar", [App\Http\Controllers\AgenciaController::class, "cotizacionFacturar"])->name("cotizaciones.facturar");
    Route::post("/cotizaciones/{cotizacion}/cancelar", [App\Http\Controllers\AgenciaController::class, "cotizacionCancelar"])->name("cotizaciones.cancelar");
    Route::get("/cotizaciones/{cotizacion}/ver", [App\Http\Controllers\AgenciaController::class, "cotizacionVer"])->name("cotizaciones.ver");
    Route::get("/cotizaciones/{cotizacion}/descargar-pdf", [App\Http\Controllers\AgenciaController::class, "cotizacionDescargarPdf"])->name("cotizaciones.descargar-pdf");
    // Correos Corporativos
    Route::get("/correos", [App\Http\Controllers\AgenciaController::class, "correos"])->name("correos");
    Route::post("/correos/enviar", [App\Http\Controllers\AgenciaController::class, "correoEnviar"])->name("correos.enviar");
    Route::post("/correos/masivo", [App\Http\Controllers\AgenciaController::class, "correoMasivo"])->name("correos.masivo");
    Route::post('/cobros/{cobro}/flow', [App\Http\Controllers\AgenciaController::class, 'crearPagoFlow'])->name('cobros.flow');

    // ----- ONBOARDINGS -----
    // ----- ONBOARDING PLANTILLAS -----
    Route::get("/onboardings/plantillas", [App\Http\Controllers\AgenciaOnboardingPlantillaController::class, "index"])->name("onboardings.plantillas.index");
    Route::get("/onboardings/plantillas/crear", [App\Http\Controllers\AgenciaOnboardingPlantillaController::class, "create"])->name("onboardings.plantillas.create");
    Route::post("/onboardings/plantillas", [App\Http\Controllers\AgenciaOnboardingPlantillaController::class, "store"])->name("onboardings.plantillas.store");
    Route::get("/onboardings/plantillas/{plantilla}/editar", [App\Http\Controllers\AgenciaOnboardingPlantillaController::class, "edit"])->name("onboardings.plantillas.edit");
    Route::put("/onboardings/plantillas/{plantilla}", [App\Http\Controllers\AgenciaOnboardingPlantillaController::class, "update"])->name("onboardings.plantillas.update");
    Route::post("/onboardings/plantillas/{plantilla}/toggle", [App\Http\Controllers\AgenciaOnboardingPlantillaController::class, "toggle"])->name("onboardings.plantillas.toggle");
    Route::delete("/onboardings/plantillas/{plantilla}", [App\Http\Controllers\AgenciaOnboardingPlantillaController::class, "destroy"])->name("onboardings.plantillas.destroy");
    Route::post("/onboardings/plantillas/{plantilla}/duplicar", [App\Http\Controllers\AgenciaOnboardingPlantillaController::class, "duplicate"])->name("onboardings.plantillas.duplicate");

    Route::get("/onboardings", [App\Http\Controllers\AgenciaOnboardingController::class, "index"])->name("onboardings.index");
    Route::get("/onboardings/crear", [App\Http\Controllers\AgenciaOnboardingController::class, "create"])->name("onboardings.create");
    Route::post("/onboardings", [App\Http\Controllers\AgenciaOnboardingController::class, "store"])->name("onboardings.store");
    Route::get("/onboardings/{onboarding}", [App\Http\Controllers\AgenciaOnboardingController::class, "show"])->name("onboardings.show");
    Route::get("/onboardings/{onboarding}/editar", [App\Http\Controllers\AgenciaOnboardingController::class, "edit"])->name("onboardings.edit");
    Route::put("/onboardings/{onboarding}", [App\Http\Controllers\AgenciaOnboardingController::class, "update"])->name("onboardings.update");
    Route::delete("/onboardings/{onboarding}", [App\Http\Controllers\AgenciaOnboardingController::class, "destroy"])->name("onboardings.destroy");
    Route::get("/onboardings/{onboarding}/imprimir", [App\Http\Controllers\AgenciaOnboardingController::class, "imprimir"])->name("onboardings.imprimir");
    Route::get("/onboardings/{onboarding}/zip", [App\Http\Controllers\AgenciaOnboardingController::class, "descargarZip"])->name("onboardings.zip");
    Route::get("/onboardings/{onboarding}/csv-shopify", [App\Http\Controllers\AgenciaOnboardingController::class, "descargarCsvShopify"])->name("onboardings.csv-shopify");
    Route::post("/onboardings/{onboarding}/enviar-invitacion", [App\Http\Controllers\AgenciaOnboardingController::class, "enviarInvitacion"])->name("onboardings.enviar-invitacion");
});

// Reporte Meta Ads publico (sin login, via token) para el cliente
Route::get("/reporte-meta/{token}", [App\Http\Controllers\MetaAdsController::class, "reportePublico"])->name("reporte.meta.publico");

// Flow webhooks para agencia (sin auth)
// Flow return: el usuario vuelve del checkout (normalmente GET, pero algunos integradores envian POST).
Route::match(['get', 'post'], '/agencia-flow/return', [App\Http\Controllers\AgenciaController::class, 'flowReturn'])->name('agencia.flow.return');
// Flow confirmation: callback server-to-server (siempre POST). Excluido de CSRF en VerifyCsrfToken.
Route::post('/agencia-flow/confirmation', [App\Http\Controllers\AgenciaController::class, 'flowConfirmation'])->name('agencia.flow.confirmation');
// Cotizaciones Flow callbacks (public)
Route::match(["get", "post"], "/agencia-cotizaciones-flow/return", [App\Http\Controllers\AgenciaController::class, "cotizacionFlowReturn"])->name("agencia.cotizaciones.flow.return");
Route::post("/agencia-cotizaciones-flow/confirmation", [App\Http\Controllers\AgenciaController::class, "cotizacionFlowConfirmation"])->name("agencia.cotizaciones.flow.confirmation");


// ============================================
// DEMO MODE ROUTES
// ============================================
Route::get('/demo', [App\Http\Controllers\DemoController::class, 'login'])->name('demo.login');
Route::post('/demo/authenticate', [App\Http\Controllers\DemoController::class, 'authenticate'])->name('demo.authenticate');
Route::get('/demo/logout', [App\Http\Controllers\DemoController::class, 'logout'])->name('demo.logout');

Route::middleware(['demo.auth'])->prefix('demo')->name('demo.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DemoController::class, 'dashboard'])->name('dashboard');
    Route::get('/planes', [App\Http\Controllers\DemoController::class, 'planes'])->name('planes');
    Route::get('/planes-activos', [App\Http\Controllers\DemoController::class, 'planesActivos'])->name('planes-activos');
    Route::get('/documentos-emitidos', [App\Http\Controllers\DemoController::class, 'documentosEmitidos'])->name('documentos-emitidos');
    Route::get('/pedidos', [App\Http\Controllers\DemoController::class, 'pedidos'])->name('pedidos');
    Route::get('/facturas', [App\Http\Controllers\DemoController::class, 'facturas'])->name('facturas');
    Route::get('/facturas-servicio', [App\Http\Controllers\DemoController::class, 'facturasServicio'])->name('facturas-servicio');
    Route::get('/cobros-pendientes', [App\Http\Controllers\DemoController::class, 'cobrosPendientes'])->name('cobros-pendientes');
    Route::get('/estados-solicitud', [App\Http\Controllers\DemoController::class, 'estadosSolicitud'])->name('estados-solicitud');
    Route::get('/inventario', [App\Http\Controllers\DemoController::class, 'inventario'])->name('inventario');
    Route::get('/chats', [App\Http\Controllers\DemoController::class, 'chats'])->name('chats');
});


// ============================================
// FINANZAS MODULE ROUTES
// ============================================
Route::middleware(['auth'])->prefix('finanzas')->name('finanzas.')->group(function () {
    Route::get('/', [\App\Http\Controllers\FinanzasController::class, 'dashboard'])->name('dashboard')->middleware('module.permission:finanzas.dashboard');
    Route::get('/ingresos', [\App\Http\Controllers\FinanzasController::class, 'ingresos'])->name('ingresos')->middleware('module.permission:finanzas.ingresos');
    Route::post('/ingresos', [\App\Http\Controllers\FinanzasController::class, 'storeIngresoManual'])->name('ingresos.store')->middleware('module.permission:finanzas.ingresos');
    Route::get('/egresos', [\App\Http\Controllers\FinanzasController::class, 'egresos'])->name('egresos')->middleware('module.permission:finanzas.egresos');
    Route::post('/egresos/factura-compra', [\App\Http\Controllers\FinanzasController::class, 'storeFacturaCompra'])->name('egresos.factura-compra.store')->middleware('module.permission:finanzas.egresos');
    Route::put('/egresos/factura-compra/{id}', [\App\Http\Controllers\FinanzasController::class, 'updateFacturaCompra'])->name('egresos.factura-compra.update')->middleware('module.permission:finanzas.egresos');
    Route::delete('/egresos/factura-compra/{id}', [\App\Http\Controllers\FinanzasController::class, 'deleteFacturaCompra'])->name('egresos.factura-compra.delete')->middleware('module.permission:finanzas.egresos');
    Route::post('/egresos/gasto-operativo', [\App\Http\Controllers\FinanzasController::class, 'storeGastoOperativo'])->name('egresos.gasto-operativo.store')->middleware('module.permission:finanzas.egresos');
    Route::post('/egresos/gasto-operativo/{id}/toggle', [\App\Http\Controllers\FinanzasController::class, 'toggleGastoOperativo'])->name('egresos.gasto-operativo.toggle')->middleware('module.permission:finanzas.egresos');
    Route::post('/egresos/categoria', [\App\Http\Controllers\FinanzasController::class, 'storeCategoria'])->name('egresos.categoria.store')->middleware('module.permission:finanzas.egresos');
    Route::post('/egresos/centro-costo', [\App\Http\Controllers\FinanzasController::class, 'storeCentroCosto'])->name('egresos.centro-costo.store')->middleware('module.permission:finanzas.egresos');
    Route::get('/iva', [\App\Http\Controllers\FinanzasController::class, 'iva'])->name('iva')->middleware('module.permission:finanzas.iva');
    Route::post('/iva/cerrar', [\App\Http\Controllers\FinanzasController::class, 'cerrarIva'])->name('iva.cerrar')->middleware('module.permission:finanzas.iva');
    Route::get('/banco', [\App\Http\Controllers\FinanzasController::class, 'banco'])->name('banco')->middleware('module.permission:finanzas.banco');
    Route::post('/banco/cuenta', [\App\Http\Controllers\FinanzasController::class, 'storeCuentaBanco'])->name('banco.cuenta.store')->middleware('module.permission:finanzas.banco');
    Route::post('/banco/importar', [\App\Http\Controllers\FinanzasController::class, 'importarCartola'])->name('banco.importar')->middleware('module.permission:finanzas.banco');
    Route::post('/banco/movimiento/{id}/conciliar', [\App\Http\Controllers\FinanzasController::class, 'conciliarMovimiento'])->name('banco.conciliar')->middleware('module.permission:finanzas.banco');
    Route::get('/cuentas-cobrar', [\App\Http\Controllers\FinanzasController::class, 'cuentasCobrar'])->name('cuentas-cobrar')->middleware('module.permission:finanzas.cuentas-cobrar');
    Route::get('/cuentas-pagar', [\App\Http\Controllers\FinanzasController::class, 'cuentasPagar'])->name('cuentas-pagar')->middleware('module.permission:finanzas.cuentas-pagar');
    Route::get('/reportes', [\App\Http\Controllers\FinanzasController::class, 'reportes'])->name('reportes')->middleware('module.permission:finanzas.reportes');
    Route::get('/reportes/libro-ventas', [\App\Http\Controllers\FinanzasController::class, 'exportarLibroVentas'])->name('reportes.libro-ventas')->middleware('module.permission:finanzas.reportes');
    Route::get('/reportes/libro-compras', [\App\Http\Controllers\FinanzasController::class, 'exportarLibroCompras'])->name('reportes.libro-compras')->middleware('module.permission:finanzas.reportes');
    Route::get('/presupuesto', [\App\Http\Controllers\FinanzasController::class, 'presupuesto'])->name('presupuesto')->middleware('module.permission:finanzas.presupuesto');
    Route::post('/presupuesto', [\App\Http\Controllers\FinanzasController::class, 'storePresupuesto'])->name('presupuesto.store')->middleware('module.permission:finanzas.presupuesto');
    Route::get('/centros-costo', [\App\Http\Controllers\FinanzasController::class, 'centrosCosto'])->name('centros-costo')->middleware('module.permission:finanzas.presupuesto');
    Route::post('/centros-costo', [\App\Http\Controllers\FinanzasController::class, 'storeCentroCostoPage'])->name('centros-costo.store')->middleware('module.permission:finanzas.presupuesto');
    Route::post('/banco/match', [\App\Http\Controllers\FinanzasController::class, 'matchMovimiento'])->name('banco.match')->middleware('module.permission:finanzas.banco');
    Route::get('/reportes/f29', [\App\Http\Controllers\FinanzasController::class, 'exportarF29'])->name('reportes.f29')->middleware('module.permission:finanzas.reportes');
    Route::get('/reportes/estado-resultados', [\App\Http\Controllers\FinanzasController::class, 'exportarEstadoResultados'])->name('reportes.estado-resultados')->middleware('module.permission:finanzas.reportes');
    Route::post('/egresos/factura-compra/{id}/marcar-pagada', [\App\Http\Controllers\FinanzasController::class, 'marcarPagada'])->name('egresos.factura-compra.marcar-pagada')->middleware('module.permission:finanzas.egresos');
    Route::post('/ingresos/manual', [\App\Http\Controllers\FinanzasController::class, 'storeIngresoManual'])->name('ingresos.manual.store')->middleware('module.permission:finanzas.ingresos');

});

// ============================================
// CONFIG MODULE ROUTES (Colaboradores)
// ============================================
Route::middleware(['auth'])->prefix('config')->name('config.')->group(function () {
    Route::get('/colaboradores', [\App\Http\Controllers\ColaboradorController::class, 'index'])->name('colaboradores')->middleware('module.permission:config.usuarios');
    Route::post('/colaboradores', [\App\Http\Controllers\ColaboradorController::class, 'store'])->name('colaboradores.store')->middleware('module.permission:config.usuarios');
    Route::put('/colaboradores/{id}', [\App\Http\Controllers\ColaboradorController::class, 'update'])->name('colaboradores.update')->middleware('module.permission:config.usuarios');
    Route::post('/colaboradores/{id}/toggle', [\App\Http\Controllers\ColaboradorController::class, 'toggleStatus'])->name('colaboradores.toggle')->middleware('module.permission:config.usuarios');
    Route::delete('/colaboradores/{id}', [\App\Http\Controllers\ColaboradorController::class, 'destroy'])->name('colaboradores.destroy')->middleware('module.permission:config.usuarios');
    Route::put('/colaboradores/{id}/permisos', [\App\Http\Controllers\ColaboradorController::class, 'updatePermisos'])->name('colaboradores.permisos')->middleware('module.permission:config.usuarios');
});


// ==========================================
// PORTAL PUBLICO DE ONBOARDING (sin login, acceso por token)
// ==========================================
// Portal del cliente: bienvenida + wizard
Route::get("/o/{token}", [App\Http\Controllers\OnboardingPublicoController::class, "mostrar"])->name("onboarding.publico");
Route::get("/o/{token}/w", [App\Http\Controllers\OnboardingPublicoController::class, "wizard"])->name("onboarding.wizard.inicio");
Route::get("/o/{token}/w/{indice}", [App\Http\Controllers\OnboardingPublicoController::class, "wizard"])->name("onboarding.wizard");
Route::post("/o/{token}/w/{indice}", [App\Http\Controllers\OnboardingPublicoController::class, "guardar"])->name("onboarding.wizard.guardar");
Route::post("/o/{token}/w/{indice}/autoguardar", [App\Http\Controllers\OnboardingPublicoController::class, "autoguardar"])->name("onboarding.wizard.autoguardar");
Route::get("/o/{token}/completado", [App\Http\Controllers\OnboardingPublicoController::class, "completado"])->name("onboarding.completado");
Route::post("/o/{token}/u/{indice}/{campoKey}", [App\Http\Controllers\OnboardingPublicoController::class, "subirArchivo"])->name("onboarding.archivo.subir");
Route::delete("/o/{token}/a/{archivo}", [App\Http\Controllers\OnboardingPublicoController::class, "eliminarArchivo"])->name("onboarding.archivo.eliminar");
// Productos (constructor visual del cliente)
// Productos - rutas con segmento literal van PRIMERO (evita colision con {indice}/{campoKey})
Route::post("/o/{token}/productos/{producto}/imagen", [App\Http\Controllers\OnboardingPublicoController::class, "subirImagenProducto"])->name("onboarding.productos.imagen");
Route::delete("/o/{token}/productos/{producto}/imagen/{archivo}", [App\Http\Controllers\OnboardingPublicoController::class, "eliminarImagenProducto"])->name("onboarding.productos.imagen.eliminar");
Route::post("/o/{token}/productos/{producto}/duplicar", [App\Http\Controllers\OnboardingPublicoController::class, "duplicarProducto"])->name("onboarding.productos.duplicar");
Route::put("/o/{token}/productos/{producto}", [App\Http\Controllers\OnboardingPublicoController::class, "actualizarProducto"])->name("onboarding.productos.actualizar");
Route::delete("/o/{token}/productos/{producto}", [App\Http\Controllers\OnboardingPublicoController::class, "eliminarProducto"])->name("onboarding.productos.eliminar");
Route::post("/o/{token}/productos-origen/{indice}/{campoKey}", [App\Http\Controllers\OnboardingPublicoController::class, "guardarOrigenProductos"])->name("onboarding.productos.origen");
Route::get("/o/{token}/productos/{indice}/{campoKey}", [App\Http\Controllers\OnboardingPublicoController::class, "listarProductos"])->name("onboarding.productos.listar");
Route::post("/o/{token}/productos/{indice}/{campoKey}", [App\Http\Controllers\OnboardingPublicoController::class, "crearProducto"])->name("onboarding.productos.crear");
Route::get("/o/{token}/a/{archivo}", [App\Http\Controllers\OnboardingPublicoController::class, "descargarArchivo"])->name("onboarding.archivo.descargar");

