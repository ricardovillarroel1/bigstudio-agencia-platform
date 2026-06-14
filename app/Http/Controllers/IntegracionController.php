<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\InventorySyncService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\ProductSyncService;
use App\Services\WebhookSyncService;
use App\Models\FacturaEmitida;
use App\Models\Boleta;

class IntegracionController extends Controller
{
    /**
     * Mostrar el dashboard de integración
     */
        public function dashboard(\Illuminate\Http\Request $request)
    {
        // Obtener solo las integraciones MANUALES (sin solicitud asociada)
        $integraciones = \App\Models\IntegracionConfig::with(['user'])
            ->where('activo', true)
            ->whereNull('solicitud_id')
            ->latest()
            ->get();

        // Estadísticas generales
        $stats = [
            'total_integraciones' => $integraciones->count(),
            'total_productos' => \App\Models\ProductMapping::where('sync_status', 'synced')->count(),
            'total_webhooks' => \App\Models\ClienteWebhook::count(),
            'total_boletas' => \App\Models\Boleta::where('status', 'emitida')->count(),
        ];

        // UF → CLP conversion
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
            return 39841.72;
        });

        // Helper to calculate CLP from suscripciones
        $calcRevenue = function ($subs) use ($ufValue) {
            $total = 0;
            foreach ($subs as $sub) {
                if ($sub->plan && (float) $sub->plan->precio > 0) {
                    $precio = (float) $sub->plan->precio;
                    $total += strtoupper($sub->plan->moneda ?? 'CLP') === 'UF' ? round($precio * $ufValue) : round($precio);
                }
            }
            return $total;
        };

        // PARCHE_06_FILTROS - filtro real por fecha de pago
        $revenueFilter = $request->get('revenue_filter', 'all');
        $mes = (int) $request->get('mes', now()->month);
        $anio = (int) $request->get('anio', now()->year);
        $sumByDate = function($filter) use ($mes, $anio) {
            $q = \App\Models\FacturaServicio::where('estado', 'pagada');
            $dx = \DB::raw('COALESCE(pagada_at, created_at)');
            if ($filter === 'day') $q->whereDate($dx, today());
            elseif ($filter === 'month') $q->whereYear($dx, $anio)->whereMonth($dx, $mes);
            elseif ($filter === 'year') $q->whereYear($dx, $anio);
            return ['revenue' => (int) $q->sum('monto'), 'count' => $q->count()];
        };
        $r = $sumByDate($revenueFilter);
        $totalRevenue = $r['revenue'];
        $totalPayments = $r['count'];

        // PARCHE_07_REVENUE_VARS - calcular todas las metricas con sumByDate
        $totalRevenueAllTime = $sumByDate('all')['revenue'];
        $revenueToday = $sumByDate('day')['revenue'];
        $revenueMonth = $sumByDate('month')['revenue'];
        $revenueYear = $sumByDate('year')['revenue'];

        // Last 4 clients who contracted a plan
        $recentSubscribers = \App\Models\Suscripcion::with(['user', 'plan'])
            ->latest()
            ->take(4)
            ->get();

        return view('integracion.dashboard', compact(
            'integraciones', 'stats', 'totalRevenue', 'totalRevenueAllTime',
            'revenueToday', 'revenueMonth', 'revenueYear', 'totalPayments',
            'revenueFilter', 'recentSubscribers', 'ufValue'
        ));
    }

    /**
     * Mostrar el formulario de configuración
     */
    public function index()
    {
        $webhook_url = url('/integracion/webhook-receiver');
        return view('integracion.index', compact('webhook_url'));
    }

    /**
     * Procesar la integración
     */
    public function procesar(Request $request)
    {
        $request->validate([
            'shopify_tienda' => 'required|string',
            'shopify_token' => 'required|string|min:20',
            'shopify_secret' => 'required|string|min:20',
            'lioren_api_key' => 'required|string|min:10',
            'webhook_url' => 'required|url',
            'facturacion_enabled' => 'nullable|boolean',
            'shopify_visibility_enabled' => 'nullable|boolean',
            'notas_credito_enabled' => 'nullable|boolean',
            'documentos_postventa_enabled' => 'nullable|boolean',
            'sync_inventario_enabled' => 'nullable|boolean',
            'no_order_limit' => 'nullable|boolean',
            'monthly_order_limit' => 'nullable|integer|min:1',
        ]);

        $facturacionEnabled = $request->has('facturacion_enabled') && $request->facturacion_enabled == '1';
        $shopifyVisibilityEnabled = $request->has('shopify_visibility_enabled') && $request->shopify_visibility_enabled == '1';
        $notasCreditoEnabled = $request->has('notas_credito_enabled') && $request->notas_credito_enabled == '1';
        $documentosPostventaEnabled = $request->has('documentos_postventa_enabled') && $request->documentos_postventa_enabled == '1';
        $syncInventarioEnabled = $request->has('sync_inventario_enabled') && $request->sync_inventario_enabled == '1';
        $noOrderLimit = $request->has('no_order_limit') && $request->no_order_limit == '1';
        $orderLimitEnabled = !$noOrderLimit;
        $monthlyOrderLimit = $orderLimitEnabled ? $request->monthly_order_limit : null;

        $data = [
            'shopify_tienda' => $request->shopify_tienda,
            'shopify_token' => $request->shopify_token,
            'shopify_secret' => $request->shopify_secret,
            'lioren_api_key' => $request->lioren_api_key,
            'webhook_url' => $request->webhook_url,
            'facturacion_enabled' => $facturacionEnabled,
            'shopify_visibility_enabled' => $shopifyVisibilityEnabled,
            'notas_credito_enabled' => $notasCreditoEnabled,
                'documentos_postventa_enabled' => $documentosPostventaEnabled,
        ];

        // Guardar en sesión (temporal para la vista)
        session($data);

        // Guardar en base de datos (permanente para webhooks)
        \App\Models\IntegracionConfig::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'shopify_tienda' => $request->shopify_tienda,
                'shopify_token' => $request->shopify_token,
                'shopify_secret' => $request->shopify_secret,
                'lioren_api_key' => $request->lioren_api_key,
                'facturacion_enabled' => $facturacionEnabled,
                'shopify_visibility_enabled' => $shopifyVisibilityEnabled,
                'notas_credito_enabled' => $notasCreditoEnabled,
                'documentos_postventa_enabled' => $documentosPostventaEnabled,
                'order_limit_enabled' => $orderLimitEnabled,
                'monthly_order_limit' => $monthlyOrderLimit,
                'activo' => true,
                'ultima_sincronizacion' => now(),
            ]
        );

        Log::info("Configuración guardada - Facturación: " . ($facturacionEnabled ? 'HABILITADA' : 'DESHABILITADA') . " - Visibilidad Shopify: " . ($shopifyVisibilityEnabled ? 'HABILITADA' : 'DESHABILITADA') . " - Notas de Crédito: " . ($notasCreditoEnabled ? 'HABILITADA' : 'DESHABILITADA') . " - Límite pedidos: " . ($orderLimitEnabled ? $monthlyOrderLimit . ' mensuales' : 'SIN LÍMITE'));

        // Validar Shopify
        $shopify_valid = $this->validarShopify($data['shopify_tienda'], $data['shopify_token']);
        
        // Validar Lioren
        $lioren_valid = $this->validarLioren($data['lioren_api_key']);

        // Crear webhooks
        $webhooks_creados = [];
        if ($shopify_valid['success']) {
            $webhooks_creados = $this->crearWebhooks(
                $data['shopify_tienda'],
                $data['shopify_token'],
                $data['webhook_url']
            );
        }

        // Obtener y sincronizar productos
        $productos_sincronizados = 0;
        Log::info("Validaciones - Shopify: " . ($shopify_valid['success'] ? 'OK' : 'FAIL') . ", Lioren: " . ($lioren_valid['success'] ? 'OK' : 'FAIL'));
        
        $syncResults = ['success' => false, 'results' => []];
        
        if ($shopify_valid['success'] && $lioren_valid['success']) {
            Log::info("Llamando a sincronización bidireccional...");
            
            // Usar el nuevo servicio de sincronización bidireccional
            $syncService = new ProductSyncService(
                auth()->id(),
                $data['shopify_tienda'],
                $data['shopify_token'],
                $data['lioren_api_key']
            );

            $syncResults = $syncService->initialBidirectionalSync();
            
            $productos_sincronizados = $syncResults['results']['total_synced'] ?? 0;
            Log::info("Productos sincronizados: {$productos_sincronizados}");
        } else {
            Log::warning("No se sincronizarán productos porque las validaciones fallaron");
            $productos_sincronizados = 0;
        }

        $data['shopify_valid'] = $shopify_valid;
        $data['lioren_valid'] = $lioren_valid;
        $data['webhooks_creados'] = $webhooks_creados;
        $data['productos_sincronizados'] = $productos_sincronizados;
        $data['sync_results'] = $syncResults;

        return view('integracion.procesar', $data);
    }

    /**
     * Sincronizar productos de Shopify a Lioren
     */
    private function sincronizarProductos($tienda, $token, $api_key)
    {
        Log::info("=== INICIANDO SINCRONIZACIÓN DE PRODUCTOS ===");
        Log::info("Tienda: {$tienda}");
        
        try {
            // Obtener productos de Shopify
            Log::info("Obteniendo productos de Shopify...");
            
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
            ])->get("https://{$tienda}/admin/api/2024-01/products.json?limit=10");
            
            Log::info("Respuesta Shopify: Status {$response->status()}");

            if (!$response->successful()) {
                return 0;
            }

            $productos = $response->json()['products'] ?? [];
            $sincronizados = 0;
            
            Log::info("Productos encontrados en Shopify: " . count($productos));

            foreach ($productos as $producto) {
                $variant = $producto['variants'][0] ?? [];
                $precio = floatval($variant['price'] ?? 0);
                
                // Calcular precio neto (sin IVA) y bruto (con IVA)
                $precioventabruto = $precio;
                $preciocompraneto = round($precio / 1.19, 2); // Precio sin IVA (19%)
                
                // Preparar datos para Lioren según documentación oficial
                $datos_lioren = [
                    'nombre' => $producto['title'] ?? 'Producto sin nombre',
                    'codigo' => $variant['sku'] ?? 'SKU-' . $producto['id'],
                    'fraccionable' => 0, // No fraccionable por defecto
                    'exento' => 0, // Afecto a IVA por defecto
                    'preciocompraneto' => $preciocompraneto,
                    'precioventabruto' => $precioventabruto,
                    'unidad' => 'Unidad',
                    'descripcion' => strip_tags($producto['body_html'] ?? ''),
                ];

                // Enviar a Lioren
                Log::info("Intentando crear producto en Lioren: {$producto['title']}", $datos_lioren);
                
                $lioren_response = Http::withHeaders([
                    'Authorization' => "Bearer {$api_key}",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->post('https://www.lioren.cl/api/productos', $datos_lioren);

                Log::info("Respuesta Lioren: Status {$lioren_response->status()}", [
                    'body' => $lioren_response->body()
                ]);

                if ($lioren_response->successful()) {
                    $lioren_data = $lioren_response->json();
                    
                    // Guardar mapeo en BD
                    \App\Models\ProductMapping::updateOrCreate(
                        ['shopify_product_id' => $producto['id']],
                        [
                            'lioren_product_id' => $lioren_data['id'] ?? null,
                            'product_title' => $producto['title'],
                            'sku' => $variant['sku'] ?? '',
                            'price' => $variant['price'] ?? 0,
                            'stock' => $variant['inventory_quantity'] ?? 0,
                            'sync_status' => 'synced',
                            'last_synced_at' => now(),
                        ]
                    );

                    $sincronizados++;
                    Log::info("Producto sincronizado: {$producto['title']}");
                } else {
                    // Guardar error
                    \App\Models\ProductMapping::updateOrCreate(
                        ['shopify_product_id' => $producto['id']],
                        [
                            'product_title' => $producto['title'],
                            'sync_status' => 'error',
                            'last_error' => $lioren_response->body(),
                        ]
                    );
                }
            }

            return $sincronizados;

        } catch (\Exception $e) {
            Log::error("Error sincronizando productos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Crear webhooks en Shopify
     */
    private function crearWebhooks($tienda, $token, $webhook_url)
    {
        $config = \App\Models\IntegracionConfig::where('user_id', auth()->id())->first();
        
        $webhooks = [
            ['topic' => 'orders/create', 'nombre' => 'Nuevos Pedidos'],
            ['topic' => 'orders/paid', 'nombre' => 'Pedidos Pagados (transferencia / pago confirmado)'],
            ['topic' => 'products/create', 'nombre' => 'Productos Creados'],
            ['topic' => 'products/update', 'nombre' => 'Productos Actualizados'],
            ['topic' => 'inventory_levels/update', 'nombre' => 'Inventario Actualizado']
        ];

        // Agregar webhooks de Notas de Crédito si el PLAN lo permite
        $planFeatures = $this->getPlanFeatures($config->user_id);
        if ($planFeatures['notas_credito_enabled']) {
            $webhooks[] = ['topic' => 'orders/cancelled', 'nombre' => 'Pedidos Cancelados'];
            $webhooks[] = ['topic' => 'refunds/create', 'nombre' => 'Reembolsos Creados'];
        }
        // Agregar webhook de Documentos Postventa si el PLAN lo permite
        if ($planFeatures['documentos_postventa_enabled']) {
            $webhooks[] = ['topic' => 'orders/edited', 'nombre' => 'Pedidos Editados (Postventa)'];
        }

        $creados = [];

        foreach ($webhooks as $webhook) {
            try {
                $url_completa = $webhook_url . '?evento=' . str_replace('/', '_', $webhook['topic']);
                
                $response = Http::withHeaders([
                    'X-Shopify-Access-Token' => $token,
                ])->post("https://{$tienda}/admin/api/2024-01/webhooks.json", [
                    'webhook' => [
                        'topic' => $webhook['topic'],
                        'address' => $url_completa,
                        'format' => 'json'
                    ]
                ]);

                if ($response->successful()) {
                    $result = $response->json();
                    $creados[] = [
                        'topic' => $webhook['topic'],
                        'nombre' => $webhook['nombre'],
                        'id' => $result['webhook']['id'] ?? null,
                        'success' => true
                    ];
                    Log::info("Webhook creado: {$webhook['topic']}");
                } else {
                    $creados[] = [
                        'topic' => $webhook['topic'],
                        'nombre' => $webhook['nombre'],
                        'success' => false,
                        'error' => $response->body()
                    ];
                    Log::error("Error creando webhook {$webhook['topic']}: " . $response->body());
                }
            } catch (\Exception $e) {
                $creados[] = [
                    'topic' => $webhook['topic'],
                    'nombre' => $webhook['nombre'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                Log::error("Excepción creando webhook: " . $e->getMessage());
            }
        }

        return $creados;
    }

    /**
     * Ver productos sincronizados
     */
    public function productos()
    {
        $productos = \App\Models\ProductMapping::orderBy('created_at', 'desc')->get();
        return view('integracion.productos', compact('productos'));
    }

    /**
     * Ver productos directamente desde Lioren
     */
    public function productosLioren()
    {
        $config = \App\Models\IntegracionConfig::getActiva();
        $api_key = $config ? $config->lioren_api_key : (session('lioren_api_key') ?? env('LIOREN_API_KEY'));
        
        if (!$api_key) {
            return view('integracion.productos-lioren', [
                'error' => 'No hay API Key de Lioren configurada. Por favor, ejecuta la integración primero.',
                'productos' => []
            ]);
        }

        try {
            Log::info("Obteniendo productos de Lioren...");
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$api_key}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->get('https://www.lioren.cl/api/productos');

            Log::info("Respuesta Lioren: Status {$response->status()}");

            if ($response->successful()) {
                $productos = $response->json();
                
                // Si la respuesta es un objeto con 'data', extraerlo
                if (isset($productos['data'])) {
                    $productos = $productos['data'];
                }
                
                // Si no es array, convertirlo
                if (!is_array($productos)) {
                    $productos = [];
                }

                return view('integracion.productos-lioren', [
                    'productos' => $productos,
                    'error' => null,
                    'total' => count($productos)
                ]);
            } else {
                return view('integracion.productos-lioren', [
                    'error' => "Error al obtener productos de Lioren (HTTP {$response->status()}): " . $response->body(),
                    'productos' => []
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Error obteniendo productos de Lioren: " . $e->getMessage());
            
            return view('integracion.productos-lioren', [
                'error' => 'Error: ' . $e->getMessage(),
                'productos' => []
            ]);
        }
    }

    /**
     * Receptor de webhooks de Shopify
     */
    public function webhookReceiver(Request $request)
    {
        $hmac_header = $request->header('X-Shopify-Hmac-Sha256');
        $shop_domain = $request->header('X-Shopify-Shop-Domain');
        $topic = $request->header('X-Shopify-Topic');
        $evento = $request->query('evento');
        $userId = $request->query('user_id'); // NUEVO: Identificar al cliente

        $data = $request->getContent();

        // Registrar en log
        Log::channel('single')->info('=== WEBHOOK RECIBIDO ===', [
            'evento' => $evento,
            'topic' => $topic,
            'shop' => $shop_domain,
            'user_id' => $userId,
        ]);

        // Obtener configuración del cliente específico
        if ($userId) {
            $config = \App\Models\IntegracionConfig::where('user_id', $userId)
                ->where('activo', true)
                ->first();
        } else {
            // Fallback: buscar por tienda (para compatibilidad con webhooks antiguos)
            $config = \App\Models\IntegracionConfig::where('shopify_tienda', $shop_domain)
                ->where('activo', true)
                ->first();
        }
        
        if (!$config) {
            Log::channel('single')->error('No hay configuración activa para este cliente', [
                'user_id' => $userId,
                'shop' => $shop_domain,
            ]);
            return response()->json(['error' => 'No configuration found'], 500);
        }

        Log::channel('single')->info('✅ Configuración encontrada', [
            'config_id' => $config->id,
            'user_id' => $config->user_id,
        ]);

        // Validar HMAC de Shopify (HABILITADO)
        if ($hmac_header) { // HMAC verification ENABLED
            $calculated_hmac = base64_encode(hash_hmac('sha256', $data, $config->shopify_secret, true));
            
            if (!hash_equals($calculated_hmac, $hmac_header)) {
                Log::channel('single')->error('HMAC inválido - Webhook rechazado', [
                    'calculated' => mb_substr($calculated_hmac, 0, 20),
                    'received' => mb_substr($hmac_header, 0, 20),
                ]);
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            Log::channel('single')->info('✅ HMAC válido');
        } else {
            Log::channel('single')->info('ℹ️ Webhook sin header HMAC - aceptado sin verificación');
        }

        $webhook_data = json_decode($data, true);

        if (!$webhook_data) {
            Log::channel('single')->error('Error al decodificar JSON');
            return response()->json(['error' => 'Bad Request'], 400);
        }

        // Si no hay evento en query param, mapear desde el topic header de Shopify
        if (empty($evento) && $topic) {
            $topicMap = [
                // 'orders/paid' => 'orders_create', // REMOVED: orders/paid was causing duplicate emissions
                'orders/create' => 'orders_create',
                'orders/cancelled' => 'orders_cancelled',
                'products/create' => 'products_create',
                'products/update' => 'products_update',
                'products/delete' => 'products_delete',
                'inventory_levels/update' => 'inventory_levels_update',
                'refunds/create' => 'refunds_create',
                'orders/edited' => 'orders_edited',
            ];
            $evento = $topicMap[$topic] ?? str_replace('/', '_', $topic);
            Log::channel('single')->info("Topic '{$topic}' mapeado a evento: {$evento}");
        }

        // Procesar según el tipo de evento
        try {
            $lioren_api_key = $config->lioren_api_key;

            // Inicializar servicio de sincronización con el user_id correcto
            $webhookSync = new WebhookSyncService($config->user_id);

            switch ($evento) {
                case 'orders_create':
                case 'order_create':
                    // Verificar si la suscripción está pausada por factura pendiente
                    $suscripcionActiva = \App\Models\Suscripcion::where('user_id', $config->user_id)
                        ->where('estado', 'activa')
                        ->first();
                    
                    if ($suscripcionActiva && $suscripcionActiva->pausada) {
                        Log::channel('single')->warning("⏸️ Suscripción pausada por factura pendiente - DTE no emitido", [
                            'user_id' => $config->user_id,
                            'suscripcion_id' => $suscripcionActiva->id,
                        ]);
                        return response()->json(['status' => 'subscription_paused', 'message' => 'Suscripción pausada por factura pendiente de pago'], 200);
                    }

                    // Verificar límite de pedidos mensuales (permitir extras con cobro adicional)
                    if ($config->order_limit_enabled && $config->monthly_order_limit) {
                        $ordersThisMonth = $this->getMonthlyOrderCount($config->user_id);
                        
                        if ($ordersThisMonth >= $config->monthly_order_limit) {
                            Log::channel('single')->info("📊 Límite de ciclo superado: {$ordersThisMonth}/{$config->monthly_order_limit} - Documento extra con cobro adicional de 0.0002 UF+IVA");
                        } else {
                            Log::channel('single')->info("📊 Pedidos este ciclo: {$ordersThisMonth}/{$config->monthly_order_limit}");
                        }
                    }                    // PROTECCIÓN ANTI-DUPLICADOS: Lock atómico con MySQL GET_LOCK
                    $orderId = $webhook_data['id'] ?? null;
                    $lockKey = 'webhook_order_' . $config->user_id . '_' . $orderId;
                    
                    if ($orderId) {
                        // 1) Cache check rápido (primera barrera, no atómico pero filtra la mayoría).
                        //    Si el cache no está disponible (permisos, disco, etc.) NO se aborta:
                        //    las barreras de MySQL lock + verificación en BD garantizan la no-duplicación.
                        try {
                            if (Cache::has($lockKey)) {
                                Log::channel('single')->warning("Webhook duplicado detectado (cache) para pedido #{$orderId}. Ignorando.");
                                return response()->json(['status' => 'duplicate_ignored'], 200);
                            }
                        } catch (\Throwable $cacheEx) {
                            Log::channel('single')->warning("Cache no disponible en check anti-duplicado, se continúa con lock MySQL + BD: " . $cacheEx->getMessage());
                        }
                        
                        // 2) MySQL advisory lock atómico (segunda barrera - a prueba de race conditions)
                        //    GET_LOCK con timeout=0 retorna 1 si obtiene el lock, 0 si ya está tomado
                        $lockName = 'dte_emit_' . $config->user_id . '_' . $orderId;
                        $gotLock = \DB::select("SELECT GET_LOCK(?, 0) as locked", [$lockName])[0]->locked;
                        
                        if (!$gotLock) {
                            Log::channel('single')->warning("Webhook duplicado detectado (MySQL lock) para pedido #{$orderId}. Otro proceso ya lo está emitiendo.");
                            return response()->json(['status' => 'duplicate_ignored_lock'], 200);
                        }
                        
                        try {
                            // 3) Verificar en DB si ya existe documento para este pedido (tercera barrera)
                            $existingBoleta = \App\Models\Boleta::where('shopify_order_id', (string) $orderId)
                                ->where('user_id', $config->user_id)->first();
                            $existingFactura = \App\Models\FacturaEmitida::where('shopify_order_id', (string) $orderId)
                                ->where('user_id', $config->user_id)->first();
                            
                            if ($existingBoleta || $existingFactura) {
                                $docType = $existingBoleta ? 'boleta #' . $existingBoleta->folio : 'factura #' . $existingFactura->folio;
                                Log::channel('single')->warning("Pedido #{$orderId} ya tiene {$docType} emitida. Ignorando webhook duplicado.");
                                return response()->json(['status' => 'already_emitted'], 200);
                            }
                            
                            // Marcar en cache para prevenir futuros duplicados (best-effort).
                            // Si el cache falla NO se aborta la emisión: el documento es lo crítico
                            // y la no-duplicación ya está cubierta por el lock MySQL + verificación en BD.
                            try {
                                Cache::put($lockKey, true, 300);
                            } catch (\Throwable $cacheEx) {
                                Log::channel('single')->warning("No se pudo escribir cache anti-duplicado (la emisión continúa): " . $cacheEx->getMessage());
                            }
                            
                            // Verificar si la facturación está habilitada
                            if ($config->facturacion_enabled) {
                                Log::channel('single')->info('Facturación habilitada - Procesando con módulo de facturación');
                                $this->procesarPedidoConFacturacion($webhook_data, $lioren_api_key, $config);
                            } else {
                                Log::channel('single')->info('Facturación deshabilitada - Procesando solo boleta');
                                $this->procesarPedido($webhook_data, $lioren_api_key, $config);
                            }
                        } finally {
                            // Siempre liberar el lock MySQL al terminar
                            \DB::select("SELECT RELEASE_LOCK(?)", [$lockName]);
                        }
                    } else {
                        // Sin order_id, procesar normalmente (caso raro)
                        if ($config->facturacion_enabled) {
                            $this->procesarPedidoConFacturacion($webhook_data, $lioren_api_key, $config);
                        } else {
                            $this->procesarPedido($webhook_data, $lioren_api_key, $config);
                        }
                    }
                    break;
                    
                case 'products_create':
                case 'product_create':
                    Log::channel('single')->info('🆕 Webhook: Producto creado');
                    $webhookSync->handleProductCreate($webhook_data);
                    break;
                    
                case 'products_update':
                case 'product_update':
                    Log::channel('single')->info('✏️ Webhook: Producto actualizado');
                    $webhookSync->handleProductUpdate($webhook_data);
                    break;

                case 'products_delete':
                case 'product_delete':
                    Log::channel('single')->info('🗑️ Webhook: Producto eliminado');
                    $webhookSync->handleProductDelete($webhook_data);
                    break;
                    
                case 'inventory_levels_update':
                case 'inventory_update':
                    Log::channel('single')->info('📦 Webhook: Inventario actualizado');
                    $webhookSync->handleInventoryUpdate($webhook_data);
                    break;

                case 'orders_paid':
                case 'order_paid':
                    // Un pedido puede nacer 'pending' (transferencia/depósito) y orders/create NO
                    // emite hasta confirmar el pago. Al confirmarse llega orders/paid: aquí emitimos
                    // el documento SI aún no existe (las barreras anti-duplicado evitan doble emisión).
                    Log::channel('single')->info('📋 Webhook orders/paid recibido - Verificando emisión de documento');
                    $this->procesarPedidoPagado($webhook_data, $lioren_api_key, $config);
                    break;

                case 'orders_cancelled':
                case 'order_cancelled':
                    $planFeaturesWH = $this->getPlanFeatures($config->user_id);
                    if ($planFeaturesWH['notas_credito_enabled']) {
                        Log::channel('single')->info('🔄 Pedido cancelado - Emitiendo Nota de Crédito');
                        $this->procesarCancelacion($webhook_data, $lioren_api_key, $config);
                    } else {
                        Log::channel('single')->info('⚠️ Notas de Crédito deshabilitadas - Cancelación no procesada');
                    }
                    break;

                case 'refunds_create':
                case 'refund_create':
                    if ($planFeaturesWH['notas_credito_enabled'] ?? $this->getPlanFeatures($config->user_id)['notas_credito_enabled']) {
                        Log::channel('single')->info('🔄 Reembolso creado - Emitiendo Nota de Crédito');
                        $this->procesarReembolso($webhook_data, $lioren_api_key, $config);
                    } else {
                        Log::channel('single')->info('⚠️ Notas de Crédito deshabilitadas - Reembolso no procesado');
                    }
                    break;

                case 'orders_edited':
                case 'order_edited':
                    $planFeaturesEdit = $this->getPlanFeatures($config->user_id);
                    if ($planFeaturesEdit['documentos_postventa_enabled']) {
                        Log::channel('single')->info('📝 Pedido editado - Procesando documento postventa');
                        $this->procesarPedidoEditado($webhook_data, $lioren_api_key, $config);
                    } else {
                        Log::channel('single')->info('⚠️ Documentos Postventa deshabilitados - Edición no procesada');
                    }
                    break;
            }

            Log::channel('single')->info('Webhook procesado exitosamente');
            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::channel('single')->error('Error al procesar webhook: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Validar credenciales de Shopify
     */
    private function validarShopify($tienda, $token)
    {
        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
            ])->get("https://{$tienda}/admin/api/2024-01/shop.json");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Conexión exitosa con Shopify',
                    'data' => $response->json()['shop'] ?? null
                ];
            }

            return [
                'success' => false,
                'message' => "Credenciales inválidas (HTTP {$response->status()})",
                'data' => null
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Validar credenciales de Lioren
     */
    private function validarLioren($api_key)
    {
        try {
            Log::info("Validando Lioren con API Key: " . mb_substr($api_key, 0, 10) . "...");
            
            // Intentar crear un producto de prueba (sin guardarlo realmente)
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$api_key}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->get('https://www.lioren.cl/api/productos');

            Log::info("Respuesta Lioren validación: Status {$response->status()}");
            Log::info("Body: " . mb_substr($response->body(), 0, 200));

            // Lioren puede responder 200 o 401
            if ($response->status() === 200 || $response->status() === 201) {
                return [
                    'success' => true,
                    'message' => 'Conexión exitosa con Lioren'
                ];
            }

            if ($response->status() === 401) {
                return [
                    'success' => false,
                    'message' => "API Key inválida o sin permisos"
                ];
            }

            return [
                'success' => false,
                'message' => "Error (HTTP {$response->status()}): " . $response->body()
            ];
        } catch (\Exception $e) {
            Log::error("Excepción validando Lioren: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Procesar pedido CON facturación habilitada (detecta boleta o factura)
     */
    private function procesarPedidoConFacturacion($order, $api_key, $config)
    {
        $orderId = $order['id'] ?? null;
        Log::channel('single')->info('=== PROCESANDO PEDIDO CON FACTURACIÓN ===', [
            'order_id' => $orderId,
            'order_number' => $order['order_number'] ?? null,
            'total' => $order['total_price'] ?? null,
        ]);

        // VALIDACIÓN CRÍTICA: No emitir si el pago está pendiente
        $financialStatus = $order['financial_status'] ?? null;
        if ($financialStatus === 'pending') {
            Log::channel('single')->warning("⚠️ PAGO PENDIENTE - Pedido #{$order['order_number']} tiene financial_status='pending'. No se emite factura/boleta hasta que el pago esté confirmado.", [
                'order_id' => $orderId,
                'financial_status' => $financialStatus,
            ]);
            return;
        }

        // Protección contra duplicados: verificación de seguridad en DB
        // (El lock atómico principal está en el webhook handler con MySQL GET_LOCK)
        if ($orderId) {
            // Las filas error_permanente (reintentos agotados) NO bloquean: permiten re-emitir.
            $existingBoleta = \App\Models\Boleta::where('shopify_order_id', (string) $orderId)
                ->where('user_id', $config->user_id)
                ->where('status', '!=', 'error_permanente')
                ->first();
            $existingFactura = \App\Models\FacturaEmitida::where('shopify_order_id', (string) $orderId)
                ->where('user_id', $config->user_id)
                ->whereNotIn('status', ['error_permanente', 'duplicada'])
                ->first();
            if ($existingBoleta) {
                Log::channel('single')->warning("[SAFETY] Pedido #{$orderId} ya tiene boleta emitida (folio #{$existingBoleta->folio}). Omitiendo duplicado.");
                return;
            }
            if ($existingFactura) {
                Log::channel('single')->warning("[SAFETY] Pedido #{$orderId} ya tiene factura emitida (folio #{$existingFactura->folio}). Omitiendo duplicado.");
                return;
            }
        }

                try {
            // ====================================================================
            // DETECCIÓN DE TIPO DE DOCUMENTO: note_attributes + tags + notas
            // Prioridad: 1) note_attributes (checkout normal), 2) tags + notas (pedidos manuales)
            // ====================================================================

            $tipoComprobante = null;
            $rut = null;
            $razonSocial = null;
            $giro = null;
            $direccionFiscal = null;
            $fuenteDatos = 'ninguna';

            // === FUENTE 1: note_attributes (flujo normal del checkout) ===
            $noteAttributes = $order['note_attributes'] ?? [];
            foreach ($noteAttributes as $attr) {
                $name = strtolower($attr['name'] ?? '');
                $value = $attr['value'] ?? null;
                if ($name === 'tipo_comprobante') {
                    $tipoComprobante = strtolower($value);
                } elseif ($name === 'rut') {
                    $rut = trim($value);
                } elseif ($name === 'razon_social') {
                    $razonSocial = trim($value);
                } elseif ($name === 'giro') {
                    $giro = trim($value);
                } elseif ($name === 'direccion_fiscal') {
                    $direccionFiscal = trim($value);
                }
            }

            if ($tipoComprobante) {
                $fuenteDatos = 'note_attributes';
            }

            // === FUENTE 2: Tags del pedido (pedidos manuales/preliminares) ===
            // Solo si note_attributes no definió el tipo de comprobante
            if (!$tipoComprobante) {
                $tags = $order['tags'] ?? '';
                $tagsArray = array_map('trim', array_map('strtolower', explode(',', $tags)));

                if (in_array('factura', $tagsArray)) {
                    $tipoComprobante = 'factura';
                    $fuenteDatos = 'tags+notas';
                } elseif (in_array('boleta', $tagsArray)) {
                    $tipoComprobante = 'boleta';
                    $fuenteDatos = 'tags';
                }

                // Si es factura por tag, leer datos fiscales de las NOTAS del pedido
                if ($tipoComprobante === 'factura') {
                    $notas = $order['note'] ?? '';
                    if (!empty($notas)) {
                        $lineas = explode("\n", $notas);
                        foreach ($lineas as $linea) {
                            $linea = trim($linea);
                            if (empty($linea)) continue;

                            // Parsear formato "CAMPO: valor" o "CAMPO:valor"
                            if (preg_match('/^(RUT|RAZON|RAZON_SOCIAL|RAZÓN|RAZÓN SOCIAL|GIRO|DIR|DIRECCION|DIRECCIÓN|DIRECCION_FISCAL)\s*:\s*(.+)$/iu', $linea, $matches)) {
                                $campo = strtolower(trim($matches[1]));
                                $valor = trim($matches[2]);

                                if ($campo === 'rut') {
                                    $rut = $valor;
                                } elseif (in_array($campo, ['razon', 'razon_social', 'razón', 'razón social'])) {
                                    $razonSocial = $valor;
                                } elseif ($campo === 'giro') {
                                    $giro = $valor;
                                } elseif (in_array($campo, ['dir', 'direccion', 'dirección', 'direccion_fiscal'])) {
                                    $direccionFiscal = $valor;
                                }
                            }
                        }
                    }
                }
            }

            Log::channel('single')->info('Datos de documento extraídos', [
                'fuente' => $fuenteDatos,
                'tipo_comprobante' => $tipoComprobante,
                'rut' => $rut ? 'presente' : 'ausente',
                'razon_social' => $razonSocial ? 'presente' : 'ausente',
                'giro' => $giro ? 'presente' : 'ausente',
                'direccion_fiscal' => $direccionFiscal ? 'presente' : 'ausente',
            ]);

            // Decidir si es factura o boleta
            if ($tipoComprobante === 'factura' && $rut && $razonSocial && $giro) {
                Log::channel('single')->info("📄 Emitiendo FACTURA (fuente: {$fuenteDatos})");
                $this->emitirFactura($order, $api_key, $rut, $razonSocial, $giro, $config, $direccionFiscal);
            } else {
                if ($tipoComprobante === 'factura') {
                    Log::channel('single')->warning('⚠️ Tag "factura" detectado pero datos incompletos. Emitiendo BOLETA.', [
                        'rut' => $rut ? 'OK' : 'FALTA',
                        'razon_social' => $razonSocial ? 'OK' : 'FALTA',
                        'giro' => $giro ? 'OK' : 'FALTA',
                    ]);
                }
                Log::channel('single')->info('📝 Emitiendo BOLETA');
                $this->procesarPedido($order, $api_key, $config);
            }

        } catch (\Exception $e) {
            Log::channel('single')->error('Error en procesarPedidoConFacturacion: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Emitir FACTURA en Lioren
     */
    private function emitirFactura($order, $api_key, $rut, $razonSocial, $giro, $config = null, $direccionFiscal = null)
    {
        try {
            // PROTECCIÓN ANTI-DUPLICADOS: Verificar si ya existe factura para este pedido
            $orderId = $order['id'] ?? null;
            if ($orderId) {
                $existingFactura = \App\Models\FacturaEmitida::where('shopify_order_id', (string) $orderId)->where('user_id', $config ? $config->user_id : null)->first();
                if ($existingFactura) {
                    Log::channel('single')->warning("Factura ya existe para pedido #{$orderId} (folio #{$existingFactura->folio}). Omitiendo duplicado en emitirFactura().");
                    return;
                }
            }

            // Extraer datos del cliente para dirección
            $customer = $order['customer'] ?? [];
            $shippingAddress = $order['shipping_address'] ?? [];
            $billingAddress = $order['billing_address'] ?? [];

            // Priorizar: 1) dirección fiscal de notas/note_attributes, 2) billing address, 3) shipping address
            $address = $billingAddress ?: $shippingAddress;
            if ($direccionFiscal) {
                $direccion = $direccionFiscal;
                $ciudad = $address['city'] ?? $address['province'] ?? 'Santiago';
                Log::channel('single')->info('Usando dirección fiscal proporcionada: ' . $direccionFiscal);
            } else {
                $direccion = trim(($address['address1'] ?? '') . ' ' . ($address['address2'] ?? ''));
                $ciudad = $address['city'] ?? $address['province'] ?? 'Santiago';
            }
            $customerEmail = $customer['email'] ?? $order['email'] ?? null;

            // Si no hay dirección, usar datos por defecto
            if (empty($direccion)) {
                $direccion = 'Sin dirección especificada';
            }

            // Obtener IDs de localización (comuna y ciudad)
            $localizacion = $this->obtenerIdsLocalizacion($ciudad, $api_key);

            if (!$localizacion) {
                Log::channel('single')->warning('No se pudo obtener IDs de localización para: ' . $ciudad . ', usando Santiago por defecto');
                // Usar valores por defecto (Santiago Centro)
                $localizacion = ['comunaId' => 295, 'ciudadId' => 15]; // Santiago (id=295 en Lioren, region_id=15)
            }

            // Preparar detalles de productos (PRECIO NETO sin IVA, CON DESCUENTOS APLICADOS)
            $detalles = [];
            $lineItems = $order['line_items'] ?? [];
            // current_total_price refleja el total REAL tras ediciones/devoluciones; total_price puede
            // quedar desfasado si el pedido se editó antes de confirmar el pago (p.ej. transferencias).
            $totalShopify = intval(round(floatval($order['current_total_price'] ?? $order['total_price'] ?? 0)));
            
            // NO EMITIR FACTURA si el total es $0 (descuento 100%)
            if ($totalShopify <= 0) {
                Log::channel('single')->info("Pedido #{$order['order_number']} con total \$0 (descuento 100%) - No se emite factura");
                return;
            }
            
            foreach ($lineItems as $item) {
                $precioConIva = floatval($item['price'] ?? 0);
                $cantidad = floatval($item['quantity'] ?? 1);
                
                // Aplicar descuentos por ítem de Shopify
                // Prioridad: discount_allocations (más confiable), fallback: total_discount
                $totalDiscount = 0;
                if (isset($item['discount_allocations']) && is_array($item['discount_allocations']) && count($item['discount_allocations']) > 0) {
                    foreach ($item['discount_allocations'] as $discount) {
                        $totalDiscount += floatval($discount['amount'] ?? 0);
                    }
                }
                if ($totalDiscount == 0) {
                    $totalDiscount = floatval($item['total_discount'] ?? 0);
                }
                if ($totalDiscount > 0 && $cantidad > 0) {
                    $descuentoPorUnidad = $totalDiscount / $cantidad;
                    $precioConIva = $precioConIva - $descuentoPorUnidad;
                    if ($precioConIva < 0) $precioConIva = 0;
                    Log::channel('single')->info("Descuento aplicado en factura: {$totalDiscount} total, {$descuentoPorUnidad}/unidad para '{$item['title']}'");
                }
                
                // Detectar si el ítem es exento de IVA desde Shopify
                // CORREGIDO: Por defecto, todos los items están AFECTOS a IVA (exento = false)
                // Solo marcar como exento si está explícitamente configurado en el cliente
                $esExento = false; // Siempre afecto a IVA por defecto
                
                $precioNeto = round($precioConIva / 1.19); // Neto redondeado a entero
                
                // Validar código (SKU) MinLength:3 para Lioren
                $codigoItem = $item['sku'] ?? '';
                if (strlen(trim($codigoItem)) < 3) {
                    $codigoItem = 'PROD-' . ($item['product_id'] ?? rand(1000, 9999));
                }
                
                $detalles[] = [
                    'codigo' => mb_substr($codigoItem, 0, 128),
                    'nombre' => mb_substr($item['title'] ?? 'Producto', 0, 80),
                    'cantidad' => $cantidad,
                    'precio' => $precioNeto, // NETO sin IVA (con descuento aplicado)
                    'unidad' => 'UN',
                    'exento' => $esExento, // Detectado desde Shopify taxable field
                ];
            }

            // Agregar costos de envío como ítem adicional si existen
            $shippingLines = $order['shipping_lines'] ?? [];
            foreach ($shippingLines as $shipping) {
                $shippingPriceConIva = floatval($shipping['price'] ?? 0);
                
                // Aplicar descuentos de envío (ej: envío gratis parcial)
                $shippingDiscount = 0;
                if (isset($shipping['discount_allocations']) && is_array($shipping['discount_allocations'])) {
                    foreach ($shipping['discount_allocations'] as $discount) {
                        $shippingDiscount += floatval($discount['amount'] ?? 0);
                    }
                }
                if ($shippingDiscount > 0) {
                    $shippingPriceConIva -= $shippingDiscount;
                    Log::channel('single')->info("Descuento de envío aplicado en factura: \${$shippingDiscount}");
                }
                
                if ($shippingPriceConIva > 0) {
                    $shippingNeto = round($shippingPriceConIva / 1.19); // Neto redondeado a entero
                    $detalles[] = [
                        'codigo' => 'ENVIO',
                        'nombre' => mb_substr($shipping['title'] ?? 'Envío', 0, 80),
                        'cantidad' => 1,
                        'precio' => $shippingNeto, // NETO sin IVA
                        'unidad' => 'UN',
                        'exento' => false,
                    ];
                }
            }


            // AJUSTE DE REDONDEO: Asegurar que el total de la factura coincida EXACTAMENTE con Shopify
            // Calcular neto objetivo tal que: neto + round(neto * 0.19) = totalShopify
            if ($totalShopify > 0 && !empty($detalles)) {
                $netoObjetivo = intval(round($totalShopify / 1.19));
                $ivaCalculado = intval(round($netoObjetivo * 0.19));
                $totalCalculado = $netoObjetivo + $ivaCalculado;
                
                // Ajustar neto si no coincide exactamente
                if ($totalCalculado !== $totalShopify) {
                    $netoObjetivo += ($totalShopify - $totalCalculado);
                }
                
                // Calcular suma actual de netos
                $sumaNetosActual = 0;
                foreach ($detalles as $d) {
                    $sumaNetosActual += intval($d['precio'] * $d['cantidad']);
                }
                $diferencia = $sumaNetosActual - $netoObjetivo;
                
                if ($diferencia !== 0 && count($detalles) > 0) {
                    // Aplicar ajuste al último ítem para que sea exacto
                    $lastIndex = count($detalles) - 1;
                    $detalles[$lastIndex]['precio'] -= $diferencia;
                    
                    if ($detalles[$lastIndex]['precio'] < 0) {
                        $detalles[$lastIndex]['precio'] = 0;
                        Log::channel('single')->warning("Ajuste de redondeo resultó en precio negativo para '{$detalles[$lastIndex]['nombre']}' en pedido #{$order['order_number']}");
                    } else {
                        Log::channel('single')->info("Ajuste de redondeo exacto: {$diferencia} peso(s) en '{$detalles[$lastIndex]['nombre']}' - Neto objetivo: {$netoObjetivo}, Total Shopify: {$totalShopify}");
                    }
                }
            }
            if (empty($detalles)) {
                Log::channel('single')->warning('Pedido sin productos, no se emite factura');
                return;
            }

            // Limpiar RUT (quitar solo puntos, mantener guión del dígito verificador)
            $rutLimpio = trim(str_replace('.', '', $rut));
            // Asegurar que tenga el formato correcto (12345678-9)
            if (!str_contains($rutLimpio, '-')) {
                // Si no tiene guión, agregarlo antes del último dígito
                $rutLimpio = mb_substr($rutLimpio, 0, -1) . '-' . mb_substr($rutLimpio, -1);
            }

            // BLINDAJE: si el dígito verificador no cuadra, Lioren rechazará la factura
            // (validation.rutchile). Fallback directo a boleta sin gastar el intento.
            if (!$this->validarRutChileno($rutLimpio)) {
                Log::channel('single')->warning("⚠️ RUT '{$rutLimpio}' con dígito verificador inválido. FALLBACK directo a BOLETA para pedido #" . ($order['order_number'] ?? $order['id']));
                $this->procesarPedido($order, $api_key, $config);
                return;
            }

            // Receptor de la factura
            $receptorFactura = [
                'rut' => $rutLimpio,
                'rs' => mb_substr($razonSocial, 0, 100),
                'giro' => mb_substr($giro, 0, 40),
                'comuna' => $localizacion['comunaId'],
                'ciudad' => $localizacion['ciudadId'] ?? 15, // Santiago por defecto si null
                'direccion' => mb_substr($direccion, 0, 50),
            ];
            if ($customerEmail) {
                $receptorFactura['email'] = mb_substr($customerEmail, 0, 80);
            }
            // BLINDAJE: sanitizar datos fiscales del receptor antes de emitir.
            $receptorFactura = $this->sanitizarDatosFiscales(['receptor' => $receptorFactura], $order, $config)['receptor'];

            Log::channel('single')->info('Emitiendo factura en Lioren', [
                'rut' => $rutLimpio,
                'razon_social' => $razonSocial,
                'total_items' => count($detalles),
            ]);

            // Emitir vía LiorenService (punto ÚNICO de comunicación con Lioren).
            $lioren = app(\App\Services\LiorenService::class);
            $result = $lioren->emitirFactura($api_key, $detalles, $receptorFactura, 'Pedido Shopify #' . ($order['order_number'] ?? $order['id']));

            if ($result['ok']) {

                // Guardar factura en base de datos
                $factura = \App\Models\FacturaEmitida::create([
                    'user_id' => $config ? $config->user_id : null,
                    'shopify_order_id' => (string)$order['id'],
                    'shopify_order_number' => (string)($order['order_number'] ?? $order['id']),
                    'tipo_documento' => '33',
                    'lioren_factura_id' => $result['id'] ?? null,
                    'folio' => $result['folio'] ?? null,
                    'rut_receptor' => $rutLimpio,
                    'razon_social' => $razonSocial,
                    'monto_neto' => $result['montoneto'] ?? 0,
                    'monto_iva' => $result['montoiva'] ?? 0,
                    'monto_total' => $result['montototal'] ?? 0,
                    'status' => 'emitida',
                    'emitida_at' => now(),
                ]);

                // Guardar PDF y XML como archivos
                if (isset($result['pdf'])) {
                    $factura->pdf_path = $factura->savePdfFromBase64($result['pdf']);
                }
                if (isset($result['xml'])) {
                    $factura->xml_path = $factura->saveXmlFromBase64($result['xml']);
                }
                $factura->save();

                Log::channel('single')->info("✅ Factura #{$result['folio']} emitida exitosamente para pedido Shopify #{$order['order_number']}");

                // Actualizar nota en Shopify si está habilitado
                if ($config && $config->shopify_visibility_enabled && isset($result['folio'])) {
                    $this->updateShopifyOrderNote($order['id'], "Factura Lioren #{$result['folio']}", $config);
                }
                
                // Sincronizar inventario si está habilitado
                if ($config) {
                    $this->syncInventoryAfterOrder($order, $config);
                }

            } else {
                $errorBody = $result['error'] ?? '';
                if (str_contains($errorBody, "receptor.rut") || str_contains($errorBody, "validation.rutchile")) {
                    Log::channel("single")->warning("⚠️ RUT inválido detectado en factura. Aplicando FALLBACK a BOLETA para pedido #" . ($order["order_number"] ?? $order["id"]));
                    $this->procesarPedido($order, $api_key, $config);
                    return;
                }
                Log::channel("single")->error("Error al emitir factura en Lioren", [
                    "status" => $result['status'] ?? null,
                    "body" => $errorBody,
                ]);
                FacturaEmitida::create([
                    'user_id' => $config ? $config->user_id : null,
                    'shopify_order_id' => (string)$order['id'],
                    'shopify_order_number' => (string)($order['order_number'] ?? $order['id']),
                    'tipo_documento' => '33',
                    'rut_receptor' => $rutLimpio,
                    'razon_social' => $razonSocial,
                    // Acotado a los tamaños reales de columna (giro 40, direccion 50, email 80):
                    // un giro largo reventaba el INSERT y el error ni siquiera quedaba registrado.
                    'receptor_email' => $customerEmail ? mb_substr($customerEmail, 0, 80) : null,
                    'giro' => mb_substr($giro ?? 'Comercio en general', 0, 40),
                    'direccion' => mb_substr($direccion ?? 'Sin direccion especificada', 0, 50),
                    'comuna_id' => $comunaId ?? 295,
                    'ciudad_id' => $ciudadId ?? 209,
                    'monto_neto' => 0,
                    'monto_iva' => 0,
                    'monto_total' => $totalShopify ?? 0,
                    'detalles' => $detalles ?? [],
                    'status' => 'error',
                    'error_message' => $errorBody,
                    'retry_count' => 0,
                ]);
            }

        } catch (\Exception $e) {
            Log::channel('single')->error('Excepción al emitir factura: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * BLINDAJE DEFINITIVO: Diccionario completo de 346 comunas de Chile con IDs de Lioren.
     * Incluye variantes sin tilde y aliases comunes.
     * NUNCA falla - siempre retorna un ID válido.
     */
    private function getDiccionarioComunasLioren()
    {
        return [
    'arica' => ['comunaId' => 1, 'ciudadId' => 1],
    'camarones' => ['comunaId' => 2, 'ciudadId' => 1],
    'putre' => ['comunaId' => 3, 'ciudadId' => 1],
    'general lagos' => ['comunaId' => 4, 'ciudadId' => 1],
    'iquique' => ['comunaId' => 5, 'ciudadId' => 2],
    'alto hospicio' => ['comunaId' => 6, 'ciudadId' => 2],
    'pozo almonte' => ['comunaId' => 7, 'ciudadId' => 2],
    'camiña' => ['comunaId' => 8, 'ciudadId' => 2],
    'camina' => ['comunaId' => 8, 'ciudadId' => 2],
    'colchane' => ['comunaId' => 9, 'ciudadId' => 2],
    'huara' => ['comunaId' => 10, 'ciudadId' => 2],
    'pica' => ['comunaId' => 11, 'ciudadId' => 2],
    'antofagasta' => ['comunaId' => 12, 'ciudadId' => 3],
    'mejillones' => ['comunaId' => 13, 'ciudadId' => 3],
    'sierra gorda' => ['comunaId' => 14, 'ciudadId' => 3],
    'taltal' => ['comunaId' => 15, 'ciudadId' => 3],
    'calama' => ['comunaId' => 16, 'ciudadId' => 3],
    'ollagüe' => ['comunaId' => 17, 'ciudadId' => 3],
    'ollague' => ['comunaId' => 17, 'ciudadId' => 3],
    'san pedro de atacama' => ['comunaId' => 18, 'ciudadId' => 3],
    'tocopilla' => ['comunaId' => 19, 'ciudadId' => 3],
    'maría elena' => ['comunaId' => 20, 'ciudadId' => 3],
    'maria elena' => ['comunaId' => 20, 'ciudadId' => 3],
    'copiapó' => ['comunaId' => 21, 'ciudadId' => 4],
    'copiapo' => ['comunaId' => 21, 'ciudadId' => 4],
    'caldera' => ['comunaId' => 22, 'ciudadId' => 4],
    'tierra amarilla' => ['comunaId' => 23, 'ciudadId' => 4],
    'chañaral' => ['comunaId' => 24, 'ciudadId' => 4],
    'chanaral' => ['comunaId' => 24, 'ciudadId' => 4],
    'diego de almagro' => ['comunaId' => 25, 'ciudadId' => 4],
    'vallenar' => ['comunaId' => 26, 'ciudadId' => 4],
    'alto del carmen' => ['comunaId' => 27, 'ciudadId' => 4],
    'freirina' => ['comunaId' => 28, 'ciudadId' => 4],
    'huasco' => ['comunaId' => 29, 'ciudadId' => 4],
    'la serena' => ['comunaId' => 30, 'ciudadId' => 5],
    'coquimbo' => ['comunaId' => 31, 'ciudadId' => 5],
    'andacollo' => ['comunaId' => 32, 'ciudadId' => 5],
    'la higuera' => ['comunaId' => 33, 'ciudadId' => 5],
    'paihuano' => ['comunaId' => 34, 'ciudadId' => 5],
    'vicuña' => ['comunaId' => 35, 'ciudadId' => 5],
    'vicuna' => ['comunaId' => 35, 'ciudadId' => 5],
    'illapel' => ['comunaId' => 36, 'ciudadId' => 5],
    'canela' => ['comunaId' => 37, 'ciudadId' => 5],
    'los vilos' => ['comunaId' => 38, 'ciudadId' => 5],
    'salamanca' => ['comunaId' => 39, 'ciudadId' => 5],
    'ovalle' => ['comunaId' => 40, 'ciudadId' => 5],
    'combarbalá' => ['comunaId' => 41, 'ciudadId' => 5],
    'combarbala' => ['comunaId' => 41, 'ciudadId' => 5],
    'monte patria' => ['comunaId' => 42, 'ciudadId' => 5],
    'punitaqui' => ['comunaId' => 43, 'ciudadId' => 5],
    'río hurtado' => ['comunaId' => 44, 'ciudadId' => 5],
    'rio hurtado' => ['comunaId' => 44, 'ciudadId' => 5],
    'valparaíso' => ['comunaId' => 45, 'ciudadId' => 6],
    'valparaiso' => ['comunaId' => 45, 'ciudadId' => 6],
    'casablanca' => ['comunaId' => 46, 'ciudadId' => 6],
    'concón' => ['comunaId' => 47, 'ciudadId' => 6],
    'concon' => ['comunaId' => 47, 'ciudadId' => 6],
    'juan fernández' => ['comunaId' => 48, 'ciudadId' => 6],
    'juan fernandez' => ['comunaId' => 48, 'ciudadId' => 6],
    'puchuncaví' => ['comunaId' => 49, 'ciudadId' => 6],
    'puchuncavi' => ['comunaId' => 49, 'ciudadId' => 6],
    'quintero' => ['comunaId' => 50, 'ciudadId' => 6],
    'viña del mar' => ['comunaId' => 51, 'ciudadId' => 6],
    'vina del mar' => ['comunaId' => 51, 'ciudadId' => 6],
    'isla de pascua' => ['comunaId' => 52, 'ciudadId' => 6],
    'los andes' => ['comunaId' => 53, 'ciudadId' => 6],
    'calle larga' => ['comunaId' => 54, 'ciudadId' => 6],
    'rinconada' => ['comunaId' => 55, 'ciudadId' => 6],
    'san esteban' => ['comunaId' => 56, 'ciudadId' => 6],
    'la ligua' => ['comunaId' => 57, 'ciudadId' => 6],
    'cabildo' => ['comunaId' => 58, 'ciudadId' => 6],
    'papudo' => ['comunaId' => 59, 'ciudadId' => 6],
    'petorca' => ['comunaId' => 60, 'ciudadId' => 6],
    'zapallar' => ['comunaId' => 61, 'ciudadId' => 6],
    'quillota' => ['comunaId' => 62, 'ciudadId' => 6],
    'la calera' => ['comunaId' => 63, 'ciudadId' => 6],
    'hijuelas' => ['comunaId' => 64, 'ciudadId' => 6],
    'la cruz' => ['comunaId' => 65, 'ciudadId' => 6],
    'nogales' => ['comunaId' => 66, 'ciudadId' => 6],
    'san antonio' => ['comunaId' => 67, 'ciudadId' => 6],
    'algarrobo' => ['comunaId' => 68, 'ciudadId' => 6],
    'cartagena' => ['comunaId' => 69, 'ciudadId' => 6],
    'el quisco' => ['comunaId' => 70, 'ciudadId' => 6],
    'el tabo' => ['comunaId' => 71, 'ciudadId' => 6],
    'santo domingo' => ['comunaId' => 72, 'ciudadId' => 6],
    'san felipe' => ['comunaId' => 73, 'ciudadId' => 6],
    'catemu' => ['comunaId' => 74, 'ciudadId' => 6],
    'llay-llay' => ['comunaId' => 75, 'ciudadId' => 6],
    'panquehue' => ['comunaId' => 76, 'ciudadId' => 6],
    'putaendo' => ['comunaId' => 77, 'ciudadId' => 6],
    'santa maría' => ['comunaId' => 78, 'ciudadId' => 6],
    'santa maria' => ['comunaId' => 78, 'ciudadId' => 6],
    'quilpué' => ['comunaId' => 79, 'ciudadId' => 6],
    'quilpue' => ['comunaId' => 79, 'ciudadId' => 6],
    'limache' => ['comunaId' => 80, 'ciudadId' => 6],
    'olmué' => ['comunaId' => 81, 'ciudadId' => 6],
    'olmue' => ['comunaId' => 81, 'ciudadId' => 6],
    'villa alemana' => ['comunaId' => 82, 'ciudadId' => 6],
    'rancagua' => ['comunaId' => 83, 'ciudadId' => 7],
    'codegua' => ['comunaId' => 84, 'ciudadId' => 7],
    'coinco' => ['comunaId' => 85, 'ciudadId' => 7],
    'coltauco' => ['comunaId' => 86, 'ciudadId' => 7],
    'doñihue' => ['comunaId' => 87, 'ciudadId' => 7],
    'donihue' => ['comunaId' => 87, 'ciudadId' => 7],
    'graneros' => ['comunaId' => 88, 'ciudadId' => 7],
    'las cabras' => ['comunaId' => 89, 'ciudadId' => 7],
    'machalí' => ['comunaId' => 90, 'ciudadId' => 7],
    'machali' => ['comunaId' => 90, 'ciudadId' => 7],
    'malloa' => ['comunaId' => 91, 'ciudadId' => 7],
    'mostazal' => ['comunaId' => 92, 'ciudadId' => 7],
    'olivar' => ['comunaId' => 93, 'ciudadId' => 7],
    'peumo' => ['comunaId' => 94, 'ciudadId' => 7],
    'pichidegua' => ['comunaId' => 95, 'ciudadId' => 7],
    'quinta de tilcoco' => ['comunaId' => 96, 'ciudadId' => 7],
    'rengo' => ['comunaId' => 97, 'ciudadId' => 7],
    'requínoa' => ['comunaId' => 98, 'ciudadId' => 7],
    'requinoa' => ['comunaId' => 98, 'ciudadId' => 7],
    'san vicente' => ['comunaId' => 99, 'ciudadId' => 7],
    'pichilemu' => ['comunaId' => 100, 'ciudadId' => 7],
    'la estrella' => ['comunaId' => 101, 'ciudadId' => 7],
    'litueche' => ['comunaId' => 102, 'ciudadId' => 7],
    'marchihue' => ['comunaId' => 103, 'ciudadId' => 7],
    'navidad' => ['comunaId' => 104, 'ciudadId' => 7],
    'paredones' => ['comunaId' => 105, 'ciudadId' => 7],
    'san fernando' => ['comunaId' => 106, 'ciudadId' => 7],
    'chépica' => ['comunaId' => 107, 'ciudadId' => 7],
    'chepica' => ['comunaId' => 107, 'ciudadId' => 7],
    'chimbarongo' => ['comunaId' => 108, 'ciudadId' => 7],
    'lolol' => ['comunaId' => 109, 'ciudadId' => 7],
    'nancagua' => ['comunaId' => 110, 'ciudadId' => 7],
    'palmilla' => ['comunaId' => 111, 'ciudadId' => 7],
    'peralillo' => ['comunaId' => 112, 'ciudadId' => 7],
    'placilla' => ['comunaId' => 113, 'ciudadId' => 7],
    'pumanque' => ['comunaId' => 114, 'ciudadId' => 7],
    'santa cruz' => ['comunaId' => 115, 'ciudadId' => 7],
    'talca' => ['comunaId' => 116, 'ciudadId' => 8],
    'constitución' => ['comunaId' => 117, 'ciudadId' => 8],
    'constitucion' => ['comunaId' => 117, 'ciudadId' => 8],
    'curepto' => ['comunaId' => 118, 'ciudadId' => 8],
    'empedrado' => ['comunaId' => 119, 'ciudadId' => 8],
    'maule' => ['comunaId' => 120, 'ciudadId' => 8],
    'pelarco' => ['comunaId' => 121, 'ciudadId' => 8],
    'pencahue' => ['comunaId' => 122, 'ciudadId' => 8],
    'río claro' => ['comunaId' => 123, 'ciudadId' => 8],
    'rio claro' => ['comunaId' => 123, 'ciudadId' => 8],
    'san clemente' => ['comunaId' => 124, 'ciudadId' => 8],
    'san rafael' => ['comunaId' => 125, 'ciudadId' => 8],
    'cauquenes' => ['comunaId' => 126, 'ciudadId' => 8],
    'chanco' => ['comunaId' => 127, 'ciudadId' => 8],
    'pelluhue' => ['comunaId' => 128, 'ciudadId' => 8],
    'curicó' => ['comunaId' => 129, 'ciudadId' => 8],
    'curico' => ['comunaId' => 129, 'ciudadId' => 8],
    'hualañé' => ['comunaId' => 130, 'ciudadId' => 8],
    'hualane' => ['comunaId' => 130, 'ciudadId' => 8],
    'licantén' => ['comunaId' => 131, 'ciudadId' => 8],
    'licanten' => ['comunaId' => 131, 'ciudadId' => 8],
    'molina' => ['comunaId' => 132, 'ciudadId' => 8],
    'rauco' => ['comunaId' => 133, 'ciudadId' => 8],
    'romeral' => ['comunaId' => 134, 'ciudadId' => 8],
    'sagrada familia' => ['comunaId' => 135, 'ciudadId' => 8],
    'teno' => ['comunaId' => 136, 'ciudadId' => 8],
    'vichuquén' => ['comunaId' => 137, 'ciudadId' => 8],
    'vichuquen' => ['comunaId' => 137, 'ciudadId' => 8],
    'linares' => ['comunaId' => 138, 'ciudadId' => 8],
    'colbún' => ['comunaId' => 139, 'ciudadId' => 8],
    'colbun' => ['comunaId' => 139, 'ciudadId' => 8],
    'longaví' => ['comunaId' => 140, 'ciudadId' => 8],
    'longavi' => ['comunaId' => 140, 'ciudadId' => 8],
    'parral' => ['comunaId' => 141, 'ciudadId' => 8],
    'retiro' => ['comunaId' => 142, 'ciudadId' => 8],
    'san javier' => ['comunaId' => 143, 'ciudadId' => 8],
    'villa alegre' => ['comunaId' => 144, 'ciudadId' => 8],
    'yerbas buenas' => ['comunaId' => 145, 'ciudadId' => 8],
    'concepción' => ['comunaId' => 146, 'ciudadId' => 9],
    'concepcion' => ['comunaId' => 146, 'ciudadId' => 9],
    'coronel' => ['comunaId' => 147, 'ciudadId' => 9],
    'chiguayante' => ['comunaId' => 148, 'ciudadId' => 9],
    'florida' => ['comunaId' => 149, 'ciudadId' => 9],
    'hualqui' => ['comunaId' => 150, 'ciudadId' => 9],
    'lota' => ['comunaId' => 151, 'ciudadId' => 9],
    'penco' => ['comunaId' => 152, 'ciudadId' => 9],
    'san pedro de la paz' => ['comunaId' => 153, 'ciudadId' => 9],
    'santa juana' => ['comunaId' => 154, 'ciudadId' => 9],
    'talcahuano' => ['comunaId' => 155, 'ciudadId' => 9],
    'tomé' => ['comunaId' => 156, 'ciudadId' => 9],
    'tome' => ['comunaId' => 156, 'ciudadId' => 9],
    'hualpén' => ['comunaId' => 157, 'ciudadId' => 9],
    'hualpen' => ['comunaId' => 157, 'ciudadId' => 9],
    'lebu' => ['comunaId' => 158, 'ciudadId' => 9],
    'arauco' => ['comunaId' => 159, 'ciudadId' => 9],
    'cañete' => ['comunaId' => 160, 'ciudadId' => 9],
    'canete' => ['comunaId' => 160, 'ciudadId' => 9],
    'contulmo' => ['comunaId' => 161, 'ciudadId' => 9],
    'curanilahue' => ['comunaId' => 162, 'ciudadId' => 9],
    'los álamos' => ['comunaId' => 163, 'ciudadId' => 9],
    'los alamos' => ['comunaId' => 163, 'ciudadId' => 9],
    'tirúa' => ['comunaId' => 164, 'ciudadId' => 9],
    'tirua' => ['comunaId' => 164, 'ciudadId' => 9],
    'los ángeles' => ['comunaId' => 165, 'ciudadId' => 9],
    'los angeles' => ['comunaId' => 165, 'ciudadId' => 9],
    'antuco' => ['comunaId' => 166, 'ciudadId' => 9],
    'cabrero' => ['comunaId' => 167, 'ciudadId' => 9],
    'laja' => ['comunaId' => 168, 'ciudadId' => 9],
    'mulchén' => ['comunaId' => 169, 'ciudadId' => 9],
    'mulchen' => ['comunaId' => 169, 'ciudadId' => 9],
    'nacimiento' => ['comunaId' => 170, 'ciudadId' => 9],
    'negrete' => ['comunaId' => 171, 'ciudadId' => 9],
    'quilaco' => ['comunaId' => 172, 'ciudadId' => 9],
    'quilleco' => ['comunaId' => 173, 'ciudadId' => 9],
    'san rosendo' => ['comunaId' => 174, 'ciudadId' => 9],
    'santa bárbara' => ['comunaId' => 175, 'ciudadId' => 9],
    'santa barbara' => ['comunaId' => 175, 'ciudadId' => 9],
    'tucapel' => ['comunaId' => 176, 'ciudadId' => 9],
    'yumbel' => ['comunaId' => 177, 'ciudadId' => 9],
    'alto biobío' => ['comunaId' => 178, 'ciudadId' => 9],
    'alto biobio' => ['comunaId' => 178, 'ciudadId' => 9],
    'temuco' => ['comunaId' => 200, 'ciudadId' => 10],
    'carahue' => ['comunaId' => 201, 'ciudadId' => 10],
    'cunco' => ['comunaId' => 202, 'ciudadId' => 10],
    'curarrehue' => ['comunaId' => 203, 'ciudadId' => 10],
    'freire' => ['comunaId' => 204, 'ciudadId' => 10],
    'galvarino' => ['comunaId' => 205, 'ciudadId' => 10],
    'gorbea' => ['comunaId' => 206, 'ciudadId' => 10],
    'lautaro' => ['comunaId' => 207, 'ciudadId' => 10],
    'loncoche' => ['comunaId' => 208, 'ciudadId' => 10],
    'melipeuco' => ['comunaId' => 209, 'ciudadId' => 10],
    'nueva imperial' => ['comunaId' => 210, 'ciudadId' => 10],
    'padre las casas' => ['comunaId' => 211, 'ciudadId' => 10],
    'perquenco' => ['comunaId' => 212, 'ciudadId' => 10],
    'pitrufquén' => ['comunaId' => 213, 'ciudadId' => 10],
    'pitrufquen' => ['comunaId' => 213, 'ciudadId' => 10],
    'pucón' => ['comunaId' => 214, 'ciudadId' => 10],
    'pucon' => ['comunaId' => 214, 'ciudadId' => 10],
    'saavedra' => ['comunaId' => 215, 'ciudadId' => 10],
    'teodoro schmidt' => ['comunaId' => 216, 'ciudadId' => 10],
    'toltén' => ['comunaId' => 217, 'ciudadId' => 10],
    'tolten' => ['comunaId' => 217, 'ciudadId' => 10],
    'vilcún' => ['comunaId' => 218, 'ciudadId' => 10],
    'vilcun' => ['comunaId' => 218, 'ciudadId' => 10],
    'villarrica' => ['comunaId' => 219, 'ciudadId' => 10],
    'cholchol' => ['comunaId' => 220, 'ciudadId' => 10],
    'angol' => ['comunaId' => 221, 'ciudadId' => 10],
    'collipulli' => ['comunaId' => 222, 'ciudadId' => 10],
    'curacautín' => ['comunaId' => 223, 'ciudadId' => 10],
    'curacautin' => ['comunaId' => 223, 'ciudadId' => 10],
    'ercilla' => ['comunaId' => 224, 'ciudadId' => 10],
    'lonquimay' => ['comunaId' => 225, 'ciudadId' => 10],
    'los sauces' => ['comunaId' => 226, 'ciudadId' => 10],
    'lumaco' => ['comunaId' => 227, 'ciudadId' => 10],
    'purén' => ['comunaId' => 228, 'ciudadId' => 10],
    'puren' => ['comunaId' => 228, 'ciudadId' => 10],
    'renaico' => ['comunaId' => 229, 'ciudadId' => 10],
    'traiguén' => ['comunaId' => 230, 'ciudadId' => 10],
    'traiguen' => ['comunaId' => 230, 'ciudadId' => 10],
    'victoria' => ['comunaId' => 231, 'ciudadId' => 10],
    'valdivia' => ['comunaId' => 232, 'ciudadId' => 11],
    'corral' => ['comunaId' => 233, 'ciudadId' => 11],
    'lanco' => ['comunaId' => 234, 'ciudadId' => 11],
    'los lagos' => ['comunaId' => 235, 'ciudadId' => 11],
    'máfil' => ['comunaId' => 236, 'ciudadId' => 11],
    'mafil' => ['comunaId' => 236, 'ciudadId' => 11],
    'mariquina' => ['comunaId' => 237, 'ciudadId' => 11],
    'paillaco' => ['comunaId' => 238, 'ciudadId' => 11],
    'panguipulli' => ['comunaId' => 239, 'ciudadId' => 11],
    'la unión' => ['comunaId' => 240, 'ciudadId' => 11],
    'la union' => ['comunaId' => 240, 'ciudadId' => 11],
    'futrono' => ['comunaId' => 241, 'ciudadId' => 11],
    'lago ranco' => ['comunaId' => 242, 'ciudadId' => 11],
    'río bueno' => ['comunaId' => 243, 'ciudadId' => 11],
    'rio bueno' => ['comunaId' => 243, 'ciudadId' => 11],
    'puerto montt' => ['comunaId' => 244, 'ciudadId' => 12],
    'calbuco' => ['comunaId' => 245, 'ciudadId' => 12],
    'cochamó' => ['comunaId' => 246, 'ciudadId' => 12],
    'cochamo' => ['comunaId' => 246, 'ciudadId' => 12],
    'fresia' => ['comunaId' => 247, 'ciudadId' => 12],
    'frutillar' => ['comunaId' => 248, 'ciudadId' => 12],
    'los muermos' => ['comunaId' => 249, 'ciudadId' => 12],
    'llanquihue' => ['comunaId' => 250, 'ciudadId' => 12],
    'maullín' => ['comunaId' => 251, 'ciudadId' => 12],
    'maullin' => ['comunaId' => 251, 'ciudadId' => 12],
    'puerto varas' => ['comunaId' => 252, 'ciudadId' => 12],
    'castro' => ['comunaId' => 253, 'ciudadId' => 12],
    'ancud' => ['comunaId' => 254, 'ciudadId' => 12],
    'chonchi' => ['comunaId' => 255, 'ciudadId' => 12],
    'curaco de vélez' => ['comunaId' => 256, 'ciudadId' => 12],
    'curaco de velez' => ['comunaId' => 256, 'ciudadId' => 12],
    'dalcahue' => ['comunaId' => 257, 'ciudadId' => 12],
    'puqueldón' => ['comunaId' => 258, 'ciudadId' => 12],
    'puqueldon' => ['comunaId' => 258, 'ciudadId' => 12],
    'queilén' => ['comunaId' => 259, 'ciudadId' => 12],
    'queilen' => ['comunaId' => 259, 'ciudadId' => 12],
    'quellón' => ['comunaId' => 260, 'ciudadId' => 12],
    'quellon' => ['comunaId' => 260, 'ciudadId' => 12],
    'quemchi' => ['comunaId' => 261, 'ciudadId' => 12],
    'quinchao' => ['comunaId' => 262, 'ciudadId' => 12],
    'osorno' => ['comunaId' => 263, 'ciudadId' => 12],
    'puerto octay' => ['comunaId' => 264, 'ciudadId' => 12],
    'purranque' => ['comunaId' => 265, 'ciudadId' => 12],
    'puyehue' => ['comunaId' => 266, 'ciudadId' => 12],
    'río negro' => ['comunaId' => 267, 'ciudadId' => 12],
    'rio negro' => ['comunaId' => 267, 'ciudadId' => 12],
    'san juan de la costa' => ['comunaId' => 268, 'ciudadId' => 12],
    'san pablo' => ['comunaId' => 269, 'ciudadId' => 12],
    'chaitén' => ['comunaId' => 270, 'ciudadId' => 12],
    'chaiten' => ['comunaId' => 270, 'ciudadId' => 12],
    'futaleufú' => ['comunaId' => 271, 'ciudadId' => 12],
    'futaleufu' => ['comunaId' => 271, 'ciudadId' => 12],
    'hualaihué' => ['comunaId' => 272, 'ciudadId' => 12],
    'hualaihue' => ['comunaId' => 272, 'ciudadId' => 12],
    'palena' => ['comunaId' => 273, 'ciudadId' => 12],
    'coyhaique' => ['comunaId' => 274, 'ciudadId' => 13],
    'lago verde' => ['comunaId' => 275, 'ciudadId' => 13],
    'aysén' => ['comunaId' => 276, 'ciudadId' => 13],
    'aysen' => ['comunaId' => 276, 'ciudadId' => 13],
    'cisnes' => ['comunaId' => 277, 'ciudadId' => 13],
    'guaitecas' => ['comunaId' => 278, 'ciudadId' => 13],
    'cochrane' => ['comunaId' => 279, 'ciudadId' => 13],
    'ohiggins' => ['comunaId' => 280, 'ciudadId' => 13],
    'tortel' => ['comunaId' => 281, 'ciudadId' => 13],
    'chile chico' => ['comunaId' => 282, 'ciudadId' => 13],
    'río ibáñez' => ['comunaId' => 283, 'ciudadId' => 13],
    'rio ibanez' => ['comunaId' => 283, 'ciudadId' => 13],
    'punta arenas' => ['comunaId' => 284, 'ciudadId' => 14],
    'laguna blanca' => ['comunaId' => 285, 'ciudadId' => 14],
    'río verde' => ['comunaId' => 286, 'ciudadId' => 14],
    'rio verde' => ['comunaId' => 286, 'ciudadId' => 14],
    'san gregorio' => ['comunaId' => 287, 'ciudadId' => 14],
    'cabo de hornos' => ['comunaId' => 288, 'ciudadId' => 14],
    'antártica' => ['comunaId' => 289, 'ciudadId' => 14],
    'antartica' => ['comunaId' => 289, 'ciudadId' => 14],
    'porvenir' => ['comunaId' => 290, 'ciudadId' => 14],
    'primavera' => ['comunaId' => 291, 'ciudadId' => 14],
    'timaukel' => ['comunaId' => 292, 'ciudadId' => 14],
    'natales' => ['comunaId' => 293, 'ciudadId' => 14],
    'torres del paine' => ['comunaId' => 294, 'ciudadId' => 14],
    'santiago' => ['comunaId' => 295, 'ciudadId' => 15],
    'cerrillos' => ['comunaId' => 296, 'ciudadId' => 15],
    'cerro navia' => ['comunaId' => 297, 'ciudadId' => 15],
    'conchalí' => ['comunaId' => 298, 'ciudadId' => 15],
    'conchali' => ['comunaId' => 298, 'ciudadId' => 15],
    'el bosque' => ['comunaId' => 299, 'ciudadId' => 15],
    'estación central' => ['comunaId' => 300, 'ciudadId' => 15],
    'estacion central' => ['comunaId' => 300, 'ciudadId' => 15],
    'huechuraba' => ['comunaId' => 301, 'ciudadId' => 15],
    'independencia' => ['comunaId' => 302, 'ciudadId' => 15],
    'la cisterna' => ['comunaId' => 303, 'ciudadId' => 15],
    'la florida' => ['comunaId' => 304, 'ciudadId' => 15],
    'la granja' => ['comunaId' => 305, 'ciudadId' => 15],
    'la pintana' => ['comunaId' => 306, 'ciudadId' => 15],
    'la reina' => ['comunaId' => 307, 'ciudadId' => 15],
    'las condes' => ['comunaId' => 308, 'ciudadId' => 15],
    'lo barnechea' => ['comunaId' => 309, 'ciudadId' => 15],
    'lo espejo' => ['comunaId' => 310, 'ciudadId' => 15],
    'lo prado' => ['comunaId' => 311, 'ciudadId' => 15],
    'macul' => ['comunaId' => 312, 'ciudadId' => 15],
    'maipú' => ['comunaId' => 313, 'ciudadId' => 15],
    'maipu' => ['comunaId' => 313, 'ciudadId' => 15],
    'ñuñoa' => ['comunaId' => 314, 'ciudadId' => 15],
    'nunoa' => ['comunaId' => 314, 'ciudadId' => 15],
    'pedro aguirre cerda' => ['comunaId' => 315, 'ciudadId' => 15],
    'peñalolén' => ['comunaId' => 316, 'ciudadId' => 15],
    'penalolen' => ['comunaId' => 316, 'ciudadId' => 15],
    'providencia' => ['comunaId' => 317, 'ciudadId' => 15],
    'pudahuel' => ['comunaId' => 318, 'ciudadId' => 15],
    'quilicura' => ['comunaId' => 319, 'ciudadId' => 15],
    'quinta normal' => ['comunaId' => 320, 'ciudadId' => 15],
    'recoleta' => ['comunaId' => 321, 'ciudadId' => 15],
    'renca' => ['comunaId' => 322, 'ciudadId' => 15],
    'san joaquín' => ['comunaId' => 323, 'ciudadId' => 15],
    'san joaquin' => ['comunaId' => 323, 'ciudadId' => 15],
    'san miguel' => ['comunaId' => 324, 'ciudadId' => 15],
    'san ramón' => ['comunaId' => 325, 'ciudadId' => 15],
    'san ramon' => ['comunaId' => 325, 'ciudadId' => 15],
    'vitacura' => ['comunaId' => 326, 'ciudadId' => 15],
    'puente alto' => ['comunaId' => 327, 'ciudadId' => 15],
    'pirque' => ['comunaId' => 328, 'ciudadId' => 15],
    'san josé de maipo' => ['comunaId' => 329, 'ciudadId' => 15],
    'san jose de maipo' => ['comunaId' => 329, 'ciudadId' => 15],
    'colina' => ['comunaId' => 330, 'ciudadId' => 15],
    'lampa' => ['comunaId' => 331, 'ciudadId' => 15],
    'til til' => ['comunaId' => 332, 'ciudadId' => 15],
    'san bernardo' => ['comunaId' => 333, 'ciudadId' => 15],
    'buin' => ['comunaId' => 334, 'ciudadId' => 15],
    'calera de tango' => ['comunaId' => 335, 'ciudadId' => 15],
    'paine' => ['comunaId' => 336, 'ciudadId' => 15],
    'melipilla' => ['comunaId' => 337, 'ciudadId' => 15],
    'alhué' => ['comunaId' => 338, 'ciudadId' => 15],
    'alhue' => ['comunaId' => 338, 'ciudadId' => 15],
    'curacaví' => ['comunaId' => 339, 'ciudadId' => 15],
    'curacavi' => ['comunaId' => 339, 'ciudadId' => 15],
    'maría pinto' => ['comunaId' => 340, 'ciudadId' => 15],
    'maria pinto' => ['comunaId' => 340, 'ciudadId' => 15],
    'san pedro' => ['comunaId' => 341, 'ciudadId' => 15],
    'talagante' => ['comunaId' => 342, 'ciudadId' => 15],
    'el monte' => ['comunaId' => 343, 'ciudadId' => 15],
    'isla de maipo' => ['comunaId' => 344, 'ciudadId' => 15],
    'padre hurtado' => ['comunaId' => 345, 'ciudadId' => 15],
    'peñaflor' => ['comunaId' => 346, 'ciudadId' => 15],
    'penaflor' => ['comunaId' => 346, 'ciudadId' => 15],
    'chillán' => ['comunaId' => 179, 'ciudadId' => 27],
    'chillan' => ['comunaId' => 179, 'ciudadId' => 27],
    'bulnes' => ['comunaId' => 180, 'ciudadId' => 27],
    'chillán viejo' => ['comunaId' => 184, 'ciudadId' => 27],
    'chillan viejo' => ['comunaId' => 184, 'ciudadId' => 27],
    'el carmen' => ['comunaId' => 185, 'ciudadId' => 27],
    'pemuco' => ['comunaId' => 188, 'ciudadId' => 27],
    'pinto' => ['comunaId' => 189, 'ciudadId' => 27],
    'quillón' => ['comunaId' => 191, 'ciudadId' => 27],
    'quillon' => ['comunaId' => 191, 'ciudadId' => 27],
    'san ignacio' => ['comunaId' => 196, 'ciudadId' => 27],
    'yungay' => ['comunaId' => 199, 'ciudadId' => 27],
    'cobquecura' => ['comunaId' => 181, 'ciudadId' => 27],
    'coelemu' => ['comunaId' => 182, 'ciudadId' => 27],
    'ninhue' => ['comunaId' => 186, 'ciudadId' => 27],
    'portezuelo' => ['comunaId' => 190, 'ciudadId' => 27],
    'quirihue' => ['comunaId' => 192, 'ciudadId' => 27],
    'ránquil' => ['comunaId' => 193, 'ciudadId' => 27],
    'ranquil' => ['comunaId' => 193, 'ciudadId' => 27],
    'treguaco' => ['comunaId' => 198, 'ciudadId' => 27],
    'coihueco' => ['comunaId' => 183, 'ciudadId' => 27],
    'ñiquén' => ['comunaId' => 187, 'ciudadId' => 27],
    'niquen' => ['comunaId' => 187, 'ciudadId' => 27],
    'san carlos' => ['comunaId' => 194, 'ciudadId' => 27],
    'san fabián' => ['comunaId' => 195, 'ciudadId' => 27],
    'san fabian' => ['comunaId' => 195, 'ciudadId' => 27],
    'san nicolás' => ['comunaId' => 197, 'ciudadId' => 27],
    'san nicolas' => ['comunaId' => 197, 'ciudadId' => 27],
    'stgo' => ['comunaId' => 295, 'ciudadId' => 15],
    'stgo.' => ['comunaId' => 295, 'ciudadId' => 15],
    'stgo centro' => ['comunaId' => 295, 'ciudadId' => 15],
    'santiago centro' => ['comunaId' => 295, 'ciudadId' => 15],
    'vina' => ['comunaId' => 51, 'ciudadId' => 6],
    'valpo' => ['comunaId' => 45, 'ciudadId' => 6],
    'region metropolitana' => ['comunaId' => 295, 'ciudadId' => 15],
    'rm' => ['comunaId' => 295, 'ciudadId' => 15],
        ];
    }

    /**
     * BLINDAJE: Quitar acentos de un string para comparación robusta
     */
    private function quitarAcentos($string)
    {
        $search  = ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ñ','Ñ','ü','Ü'];
        $replace = ['a','e','i','o','u','A','E','I','O','U','n','N','u','U'];
        return str_replace($search, $replace, $string);
    }

    /**
     * BLINDAJE DEFINITIVO: Obtener IDs de localización (comuna y ciudad) desde diccionario local.
     * Búsqueda en 5 niveles: exacta, sin tilde, parcial, API Lioren, fallback Santiago.
     * NUNCA retorna null - SIEMPRE retorna un ID válido.
     */
    private function obtenerIdsLocalizacion($nombreCiudad, $api_key)
    {
        $fallback = ['comunaId' => 295, 'ciudadId' => 15]; // Santiago

        if (empty($nombreCiudad)) {
            \Log::channel('single')->info("BLINDAJE: Ciudad vacía, usando Santiago como fallback");
            return $fallback;
        }

        try {
            $diccionario = $this->getDiccionarioComunasLioren();
            $nombre = strtolower(trim($nombreCiudad));
            $nombreSinAcento = strtolower($this->quitarAcentos(trim($nombreCiudad)));

            \Log::channel('single')->info("BLINDAJE: Buscando comuna para: {$nombreCiudad} (normalizado: {$nombreSinAcento})");

            // NIVEL 1: Búsqueda exacta
            if (isset($diccionario[$nombre])) {
                \Log::channel('single')->info("BLINDAJE: Comuna encontrada por nombre exacto: {$nombre}");
                return $diccionario[$nombre];
            }

            // NIVEL 2: Búsqueda sin tilde
            if (isset($diccionario[$nombreSinAcento])) {
                \Log::channel('single')->info("BLINDAJE: Comuna encontrada sin tilde: {$nombreSinAcento}");
                return $diccionario[$nombreSinAcento];
            }

            // NIVEL 3: Búsqueda parcial (la ciudad contiene el nombre de una comuna o viceversa)
            foreach ($diccionario as $key => $value) {
                $keySinAcento = strtolower($this->quitarAcentos($key));
                if (strpos($nombreSinAcento, $keySinAcento) !== false || strpos($keySinAcento, $nombreSinAcento) !== false) {
                    \Log::channel('single')->info("BLINDAJE: Comuna encontrada por búsqueda parcial: {$key}");
                    return $value;
                }
            }

            // NIVEL 4: Consulta a API de Lioren como último recurso antes del fallback
            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Authorization' => 'Bearer ' . $api_key,
                ])->get('https://www.lioren.cl/comunas');

                if ($response->successful()) {
                    $comunas = $response->json();
                    if (is_array($comunas)) {
                        foreach ($comunas as $comuna) {
                            $comunaNombre = strtolower($this->quitarAcentos($comuna['nombre'] ?? ''));
                            if ($comunaNombre === $nombreSinAcento || strpos($comunaNombre, $nombreSinAcento) !== false || strpos($nombreSinAcento, $comunaNombre) !== false) {
                                $result = [
                                    'comunaId' => $comuna['id'],
                                    'ciudadId' => $comuna['region_id'] ?? 15
                                ];
                                \Log::channel('single')->info("BLINDAJE: Comuna encontrada vía API Lioren: {$comuna['nombre']}");
                                return $result;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::channel('single')->warning("BLINDAJE: Error consultando API Lioren: " . $e->getMessage());
            }

            // NIVEL 5: Fallback a Santiago
            \Log::channel('single')->warning("BLINDAJE: Comuna '{$nombreCiudad}' no encontrada en ningún nivel. Usando Santiago como fallback.");
            return $fallback;

        } catch (\Exception $e) {
            \Log::channel('single')->error("BLINDAJE: Error en obtenerIdsLocalizacion: " . $e->getMessage());
            return $fallback;
        }
    }

    /**
     * BLINDAJE: Sanitizar y validar todos los datos fiscales antes de enviar a Lioren.
     * Garantiza que NUNCA se envíe un campo inválido que cause rechazo.
     */
    private function sanitizarDatosFiscales($datos, $order, $config, $contexto = null)
    {
        // Sanitizar RUT: remover puntos, espacios, y validar formato
        if (isset($datos['receptor']['rut'])) {
            $rut = $datos['receptor']['rut'];
            $rut = str_replace(['.', ' '], '', trim($rut));
            $rut = strtoupper($rut);
            if (!preg_match('/^\d{1,10}-[\dkK]$/', $rut)) {
                $rut = preg_replace('/[^0-9kK]/', '', $rut);
                if (strlen($rut) >= 2) {
                    $dv = mb_substr($rut, -1);
                    $cuerpo = mb_substr($rut, 0, -1);
                    $rut = $cuerpo . '-' . $dv;
                }
            }
            // BLINDAJE: en BOLETAS el RUT es opcional. Si el dígito verificador no cuadra
            // (Lioren lo rechaza con validation.rutchile), se emite a consumidor final.
            if ($contexto === 'boleta' && !$this->validarRutChileno($rut)) {
                Log::channel('single')->warning("BLINDAJE: RUT '{$rut}' con dígito verificador inválido en boleta. Emitiendo a consumidor final (66666666-6).");
                $rut = '66666666-6';
            }
            $datos['receptor']['rut'] = $rut;
        }
        // Sanitizar razón social: campo 'rs' o 'razonSocial', mínimo 5 caracteres
        $rsKey = isset($datos['receptor']['rs']) ? 'rs' : (isset($datos['receptor']['razonSocial']) ? 'razonSocial' : null);
        if ($rsKey) {
            $razon = trim($datos['receptor'][$rsKey]);
            if (strlen($razon) < 5) {
                $orderData = is_array($order) ? $order : [];
                $nombreCliente = ($orderData['billing_address']['company'] ?? '') ?: (($orderData['billing_address']['first_name'] ?? '') . ' ' . ($orderData['billing_address']['last_name'] ?? ''));
                $razon = strlen(trim($nombreCliente)) >= 5 ? trim($nombreCliente) : $razon . ' CHILE';
            }
            $datos['receptor'][$rsKey] = mb_substr($razon, 0, 100);
        }
        // Sanitizar giro: mínimo 5 caracteres (requisito Lioren API)
        if (isset($datos['receptor']['giro'])) {
            $giro = trim($datos['receptor']['giro']);
            if (strlen($giro) < 5) {
                $giro = 'Comercio en general';
            }
            $datos['receptor']['giro'] = mb_substr($giro, 0, 40);
        }
        // BLINDAJE COMUNA: Validar que comunaId sea un entero válido > 0
        if (isset($datos['receptor']['comuna'])) {
            $comunaId = intval($datos['receptor']['comuna']);
            if ($comunaId <= 0 || $comunaId > 400) {
                \Log::channel('single')->warning("BLINDAJE: comunaId invalido ({$comunaId}), recalculando...");
                $orderData = is_array($order) ? $order : [];
                $ciudad = $orderData['billing_address']['city'] ?? $orderData['shipping_address']['city'] ?? '';
                if (strlen(trim($ciudad)) > 0) {
                    $api_key = is_object($config) ? ($config->lioren_api_key ?? '') : '';
                    $loc = $this->obtenerIdsLocalizacion($ciudad, $api_key);
                    $datos['receptor']['comuna'] = $loc['comunaId'];
                    if (isset($datos['receptor']['ciudad'])) {
                        $datos['receptor']['ciudad'] = $loc['ciudadId'];
                    }
                } else {
                    $datos['receptor']['comuna'] = 295; // Santiago
                }
            }
        }
        // Sanitizar dirección: mínimo 5 caracteres (requisito Lioren API)
        if (isset($datos['receptor']['direccion'])) {
            $dir = trim($datos['receptor']['direccion']);
            if (strlen($dir) < 5) {
                $orderData = is_array($order) ? $order : [];
                $dir = ($orderData['billing_address']['address1'] ?? '') ?: 'Sin direccion especificada';
                if (strlen(trim($dir)) < 5) {
                    $dir = 'Sin direccion especificada';
                }
            }
            $datos['receptor']['direccion'] = mb_substr($dir, 0, 50);
        }
        // Sanitizar email
        if (isset($datos['receptor']['email'])) {
            $email = trim($datos['receptor']['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $orderData = is_array($order) ? $order : [];
                $configEmail = is_object($config) ? ($config->notification_email ?? 'sin-email@placeholder.cl') : 'sin-email@placeholder.cl';
                $email = $orderData['email'] ?? $configEmail;
            }
            $datos['receptor']['email'] = mb_substr($email, 0, 80);
        }
        // Sanitizar items: precios, cantidades, códigos, nombres
        $itemsKey = isset($datos['items']) ? 'items' : (isset($datos['detalles']) ? 'detalles' : null);
        if ($itemsKey && is_array($datos[$itemsKey])) {
            foreach ($datos[$itemsKey] as $key => $item) {
                if (isset($item['precioUnitario']) && $item['precioUnitario'] <= 0) {
                    $datos[$itemsKey][$key]['precioUnitario'] = 1;
                }
                if (isset($item['precio']) && $item['precio'] <= 0) {
                    $datos[$itemsKey][$key]['precio'] = 1;
                }
                if (isset($item['cantidad']) && $item['cantidad'] <= 0) {
                    $datos[$itemsKey][$key]['cantidad'] = 1;
                }
                // Validar código MinLength:3 (requisito Lioren API)
                if (isset($item['codigo'])) {
                    $codigo = trim($item['codigo']);
                    if (strlen($codigo) < 3) {
                        $datos[$itemsKey][$key]['codigo'] = 'PROD-' . str_pad($key + 1, 4, '0', STR_PAD_LEFT);
                    }
                    $datos[$itemsKey][$key]['codigo'] = mb_substr($datos[$itemsKey][$key]['codigo'], 0, 128);
                }
                // Validar nombre MinLength:3 (requisito Lioren API)
                if (isset($item['nombre'])) {
                    $nombre = trim($item['nombre']);
                    if (strlen($nombre) < 3) {
                        $datos[$itemsKey][$key]['nombre'] = 'Producto sin nombre';
                    }
                    $datos[$itemsKey][$key]['nombre'] = mb_substr($datos[$itemsKey][$key]['nombre'], 0, 80);
                }
            }
        }
        \Log::channel('single')->info('BLINDAJE: Datos fiscales sanitizados correctamente', [
            'tipo' => $datos['encabezado']['tipodoc'] ?? $datos['tipodoc'] ?? 'desconocido',
            'receptor_rut' => $datos['receptor']['rut'] ?? 'N/A',
            'receptor_comuna' => $datos['receptor']['comuna'] ?? 'N/A',
        ]);
        return $datos;
    }



    /**
     * BLINDAJE: Validar RUT chileno con módulo 11
     */
    private function validarRutChileno($rut)
    {
        $rut = str_replace(['.', ' '], '', trim($rut));
        $rut = strtoupper($rut);
        if (!preg_match('/^(\d{1,10})-([\dK])$/', $rut, $matches)) {
            return false;
        }
        $cuerpo = $matches[1];
        $dvIngresado = $matches[2];
        $dvCalculado = $this->calcularDvRut($cuerpo);
        return $dvIngresado === $dvCalculado;
    }

    /**
     * BLINDAJE: Calcular dígito verificador de RUT
     */
    private function calcularDvRut($cuerpo)
    {
        $suma = 0;
        $multiplo = 2;
        for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
            $suma += intval($cuerpo[$i]) * $multiplo;
            $multiplo = $multiplo < 7 ? $multiplo + 1 : 2;
        }
        $resto = $suma % 11;
        $dv = 11 - $resto;
        if ($dv == 11) return '0';
        if ($dv == 10) return 'K';
        return strval($dv);
    }


    /**
     * Procesar pedido y emitir boleta automáticamente (SIN facturación)
     */
    private function procesarPedido($order, $api_key, $config)
    {
        $orderId = $order['id'] ?? null;
        Log::channel('single')->info('=== PROCESANDO PEDIDO (SOLO BOLETA) ===', [
            'order_id' => $orderId,
            'order_id_type' => gettype($orderId),
            'order_number' => $order['order_number'] ?? null,
            'total' => $order['total_price'] ?? null,
        ]);

        // VALIDACIÓN CRÍTICA: No emitir si el pago está pendiente
        $financialStatus = $order['financial_status'] ?? null;
        if ($financialStatus === 'pending') {
            Log::channel('single')->warning("⚠️ PAGO PENDIENTE - Pedido #{$order['order_number']} tiene financial_status='pending'. No se emite boleta hasta que el pago esté confirmado.", [
                'order_id' => $orderId,
                'financial_status' => $financialStatus,
            ]);
            return;
        }

        // Protección contra duplicados: verificación de seguridad en DB
        // (El lock atómico principal está en el webhook handler con MySQL GET_LOCK)
        if ($orderId) {
            // Las filas error_permanente (reintentos agotados) NO bloquean: el pedido quedaría
            // atascado para siempre y la re-emisión (detector de huérfanos / manual) sería imposible.
            $existingBoleta = \App\Models\Boleta::where('shopify_order_id', (string) $orderId)
                ->where('user_id', $config->user_id)
                ->where('status', '!=', 'error_permanente')
                ->first();
            if ($existingBoleta) {
                Log::channel('single')->warning("[SAFETY] Pedido #{$orderId} ya tiene boleta emitida (folio #{$existingBoleta->folio}). Omitiendo duplicado.");
                return;
            }
            $existingFactura = \App\Models\FacturaEmitida::where('shopify_order_id', (string) $orderId)
                ->where('user_id', $config->user_id)
                ->whereNotIn('status', ['error_permanente', 'duplicada'])
                ->first();
            if ($existingFactura) {
                Log::channel('single')->warning("[SAFETY] Pedido #{$orderId} ya tiene factura emitida (folio #{$existingFactura->folio}). Omitiendo boleta duplicada.");
                return;
            }
        }

        try {
            // Extraer datos del cliente
            $customer = $order['customer'] ?? [];
            $customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
            $customerEmail = $customer['email'] ?? $order['email'] ?? null;
            
            // Extraer RUT de note_attributes si existe
            $rut = null;
            if (isset($order['note_attributes']) && is_array($order['note_attributes'])) {
                foreach ($order['note_attributes'] as $attr) {
                    if (strtolower($attr['name'] ?? '') === 'rut') {
                        $rut = $attr['value'];
                        break;
                    }
                }
            }

            // Preparar detalles de productos
            $detalles = [];
            $lineItems = $order['line_items'] ?? [];
            // current_total_price refleja el total REAL tras ediciones/devoluciones; total_price puede
            // quedar desfasado si el pedido se editó antes de confirmar el pago (p.ej. transferencias).
            $totalShopify = intval(round(floatval($order['current_total_price'] ?? $order['total_price'] ?? 0)));
            
            // NO EMITIR BOLETA si el total es $0 (descuento 100%)
            if ($totalShopify <= 0) {
                Log::channel('single')->info("Pedido #{$order['order_number']} con total \$0 (descuento 100%) - No se emite boleta");
                return;
            }
            
            foreach ($lineItems as $item) {
                $precioNeto = floatval($item['price'] ?? 0);
                $cantidad = floatval($item['quantity'] ?? 1);
                
                // Aplicar descuentos por ítem de Shopify
                // Prioridad: discount_allocations (más confiable), fallback: total_discount
                $totalDiscount = 0;
                if (isset($item['discount_allocations']) && is_array($item['discount_allocations']) && count($item['discount_allocations']) > 0) {
                    foreach ($item['discount_allocations'] as $discount) {
                        $totalDiscount += floatval($discount['amount'] ?? 0);
                    }
                }
                if ($totalDiscount == 0) {
                    $totalDiscount = floatval($item['total_discount'] ?? 0);
                }
                if ($totalDiscount > 0 && $cantidad > 0) {
                    $descuentoPorUnidad = $totalDiscount / $cantidad;
                    $precioNeto = $precioNeto - $descuentoPorUnidad;
                    if ($precioNeto < 0) $precioNeto = 0;
                    Log::channel('single')->info("Descuento aplicado en boleta: {$totalDiscount} total, {$descuentoPorUnidad}/unidad para '{$item['title']}'");
                }
                
                // Detectar si el ítem es exento de IVA desde Shopify
                // CORREGIDO: Por defecto, todos los items están AFECTOS a IVA (exento = false)
                // Solo marcar como exento si está explícitamente configurado en el cliente
                $esExento = false; // Siempre afecto a IVA por defecto
                
                // Calcular impuestos por unidad desde tax_lines del item
                $totalTaxItem = 0;
                if (isset($item['tax_lines']) && is_array($item['tax_lines'])) {
                    foreach ($item['tax_lines'] as $tax) {
                        $totalTaxItem += floatval($tax['price'] ?? 0);
                    }
                }
                // Precio bruto (con IVA) por unidad = precio neto + (impuesto total / cantidad)
                // Nota: si hay descuento, los tax_lines ya están ajustados por Shopify
                $precioBruto = $cantidad > 0 ? $precioNeto + ($totalTaxItem / $cantidad) : $precioNeto;
                
                // Validar código (SKU) MinLength:3 para Lioren
                $codigoItem = $item['sku'] ?? '';
                if (strlen(trim($codigoItem)) < 3) {
                    $codigoItem = 'PROD-' . ($item['product_id'] ?? rand(1000, 9999));
                }
                
                $detalles[] = [
                    'codigo' => mb_substr($codigoItem, 0, 128),
                    'nombre' => mb_substr($item['title'] ?? 'Producto', 0, 80),
                    'cantidad' => $cantidad,
                    'precio' => round($precioBruto), // Precio BRUTO (con IVA incluido, descuento aplicado)
                    'unidad' => 'UN',
                    'exento' => $esExento, // Detectado desde Shopify taxable field
                ];
            }

            // Agregar costos de envío como ítem adicional si existen
            $shippingLines = $order['shipping_lines'] ?? [];
            foreach ($shippingLines as $shipping) {
                $shippingPrice = floatval($shipping['price'] ?? 0);
                
                // Aplicar descuentos de envío (ej: envío gratis parcial)
                $shippingDiscount = 0;
                if (isset($shipping['discount_allocations']) && is_array($shipping['discount_allocations'])) {
                    foreach ($shipping['discount_allocations'] as $discount) {
                        $shippingDiscount += floatval($discount['amount'] ?? 0);
                    }
                }
                if ($shippingDiscount > 0) {
                    $shippingPrice -= $shippingDiscount;
                    Log::channel('single')->info("Descuento de envío aplicado en boleta: \${$shippingDiscount}");
                }
                
                if ($shippingPrice > 0) {
                    // Calcular impuestos del envío
                    $shippingTax = 0;
                    if (isset($shipping['tax_lines']) && is_array($shipping['tax_lines'])) {
                        foreach ($shipping['tax_lines'] as $tax) {
                            $shippingTax += floatval($tax['price'] ?? 0);
                        }
                    }
                    $shippingBruto = $shippingPrice + $shippingTax;
                    $detalles[] = [
                        'codigo' => 'ENVIO',
                        'nombre' => mb_substr($shipping['title'] ?? 'Envío', 0, 80),
                        'cantidad' => 1,
                        'precio' => round($shippingBruto), // Precio BRUTO (con IVA incluido)
                        'unidad' => 'UN',
                        'exento' => false,
                    ];
                }
            }

            if (empty($detalles)) {
                Log::channel('single')->warning('Pedido sin productos, no se emite boleta');
                return;
            }

            // AJUSTE DE REDONDEO BOLETA: Asegurar que el total coincida con Shopify
            if ($totalShopify > 0 && !empty($detalles)) {
                $sumaActual = 0;
                foreach ($detalles as $d) {
                    $sumaActual += intval($d['precio'] * $d['cantidad']);
                }
                $difBoleta = $sumaActual - $totalShopify;
                if ($difBoleta !== 0) {
                    $ajustado = false;
                    for ($i = count($detalles) - 1; $i >= 0; $i--) {
                        if ($detalles[$i]['cantidad'] == 1) {
                            $np = $detalles[$i]['precio'] - $difBoleta;
                            if ($np > 0) {
                                $detalles[$i]['precio'] = $np;
                                $ajustado = true;
                                Log::channel('single')->info('Ajuste redondeo boleta: ' . $difBoleta . ' peso(s) en item #' . $i);
                                break;
                            }
                        }
                    }
                    if (!$ajustado) {
                        $restante = $difBoleta;
                        for ($i = count($detalles) - 1; $i >= 0 && $restante != 0; $i--) {
                            $maxAdj = $detalles[$i]['precio'] - 1;
                            if ($restante > 0 && $maxAdj > 0) {
                                $adj = min($restante, $maxAdj);
                                $detalles[$i]['precio'] -= $adj;
                                $restante -= $adj;
                            } elseif ($restante < 0) {
                                $detalles[$i]['precio'] -= $restante;
                                $restante = 0;
                            }
                        }
                        Log::channel('single')->info('Ajuste redondeo boleta distribuido: ' . $difBoleta . ' peso(s)');
                    }
                }
            }

            // Receptor (solo si hay datos del cliente)
            $receptorBoleta = [];
            if ($rut || $customerName) {
                $receptorBoleta = array_filter([
                    'rut' => $rut,
                    'rs' => $customerName ?: 'Cliente',
                    'email' => $customerEmail,
                ]);
            }
            // BLINDAJE: sanitizar datos fiscales del receptor antes de emitir.
            $receptorBoleta = $this->sanitizarDatosFiscales(['receptor' => $receptorBoleta], $order, $config, 'boleta')['receptor'] ?? [];

            // Emitir vía LiorenService (punto ÚNICO de comunicación con Lioren).
            $lioren = app(\App\Services\LiorenService::class);
            $result = $lioren->emitirBoleta($api_key, $detalles, $receptorBoleta, 'Pedido Shopify #' . ($order['order_number'] ?? $order['id']));

            if ($result['ok']) {

                // Convertir order_id a string explícitamente
                $shopifyOrderId = (string)($order['id'] ?? '');
                Log::channel('single')->info("💾 Guardando boleta con shopify_order_id: {$shopifyOrderId}");

                // Guardar boleta en base de datos
                $boleta = \App\Models\Boleta::create([
                    'user_id' => $config->user_id, // Usuario del cliente
                    'shopify_order_id' => $shopifyOrderId, // AGREGADO: ID de Shopify para buscar en reembolsos
                    'lioren_id' => $result['id'] ?? null,
                    'tipodoc' => $result['tipodoc'] ?? '39',
                    'folio' => $result['folio'] ?? null,
                    'fecha' => $result['fecha'] ?? now()->format('Y-m-d'),
                    'receptor_rut' => $rut,
                    'receptor_nombre' => $customerName ?: $result['rs'] ?? null,
                    'receptor_email' => $customerEmail,
                    'monto_neto' => $result['montoneto'] ?? 0,
                    'monto_exento' => $result['montoexento'] ?? 0,
                    'monto_iva' => $result['montoiva'] ?? 0,
                    'monto_total' => $result['montototal'] ?? 0,
                    'detalles' => $result['detalles'] ?? $detalles,
                    'observaciones' => 'Pedido Shopify #' . ($order['order_number'] ?? $order['id']),
                    'status' => 'emitida',
                ]);

                // Guardar PDF y XML como archivos
                if (isset($result['pdf'])) {
                    $boleta->pdf_path = $boleta->savePdfFromBase64($result['pdf']);
                }
                if (isset($result['xml'])) {
                    $boleta->xml_path = $boleta->saveXmlFromBase64($result['xml']);
                }
                $boleta->save();

                Log::channel('single')->info("✅ Boleta #{$boleta->folio} emitida exitosamente para pedido Shopify #{$order['order_number']}");

                // Actualizar nota en Shopify si está habilitado
                if ($config->shopify_visibility_enabled && $boleta->folio) {
                    $this->updateShopifyOrderNote($order['id'], "Boleta Lioren #{$boleta->folio}", $config);
                }

                // Sincronizar inventario si está habilitado
                $this->syncInventoryAfterOrder($order, $config);


            } else {
                $errMsg = $result['error'] ?? 'Error desconocido';
                Log::channel('single')->error('Error al emitir boleta en Lioren', [
                    'status' => $result['status'] ?? null,
                    'body' => $errMsg,
                ]);

                // Guardar error en BD
                \App\Models\Boleta::create([
                    'user_id' => $config->user_id,
                    'shopify_order_id' => (string)($order['id'] ?? ''),
                    'fecha' => now()->format('Y-m-d'),
                    'receptor_rut' => $rut,
                    'receptor_nombre' => $customerName,
                    'monto_total' => $totalShopify ?? 0,
                    'detalles' => $detalles,
                    'observaciones' => 'Pedido Shopify #' . ($order['order_number'] ?? $order['id']),
                    'status' => 'error',
                    'error_message' => $errMsg,
                ]);
            }

        } catch (\Exception $e) {
            Log::channel('single')->error('Excepción al procesar pedido: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Procesar producto creado
     */
    private function procesarProductoCreado($product, $api_key)
    {
        Log::channel('single')->info('Procesando producto creado', [
            'product_id' => $product['id'] ?? null,
            'title' => $product['title'] ?? null,
        ]);

        // Aquí iría la lógica para crear el producto en Lioren
    }

    /**
     * Procesar producto actualizado
     */
    private function procesarProductoActualizado($product, $api_key)
    {
        Log::channel('single')->info('Procesando producto actualizado', [
            'product_id' => $product['id'] ?? null,
            'title' => $product['title'] ?? null,
        ]);

        // Aquí iría la lógica para actualizar el producto en Lioren
    }

    /**
     * Procesar inventario
     */
    private function procesarInventario($inventory, $api_key)
    {
        Log::channel('single')->info('Procesando inventario', [
            'inventory_item_id' => $inventory['inventory_item_id'] ?? null,
            'available' => $inventory['available'] ?? null,
        ]);

        // Aquí iría la lógica para actualizar el inventario en Lioren
    }

    /**
     * Mostrar formulario de emisión de boletas
     */
    public function boletasForm()
    {
        $config = \App\Models\IntegracionConfig::getActiva();
        $api_key = $config ? $config->lioren_api_key : (session('lioren_api_key') ?? env('LIOREN_API_KEY'));
        
        // Obtener productos sincronizados para el formulario
        $productos = \App\Models\ProductMapping::where('sync_status', 'synced')
            ->orderBy('product_title')
            ->get();

        return view('integracion.boletas-form', compact('productos', 'api_key'));
    }

    /**
     * Emitir boleta en Lioren
     */
    public function emitirBoleta(Request $request)
    {
        $request->validate([
            'detalles' => 'required|array|min:1',
            'detalles.*.codigo' => 'required|string',
            'detalles.*.nombre' => 'required|string',
            'detalles.*.cantidad' => 'required|numeric|min:0.000001',
            'detalles.*.precio' => 'required|numeric|min:0',
            'receptor_rut' => 'nullable|string',
            'receptor_nombre' => 'nullable|string',
            'receptor_email' => 'nullable|email',
            'observaciones' => 'nullable|string|max:250',
        ]);

        $config = \App\Models\IntegracionConfig::getActiva();
        $api_key = $config ? $config->lioren_api_key : (session('lioren_api_key') ?? env('LIOREN_API_KEY'));

        if (!$api_key) {
            return back()->with('error', 'No hay API Key de Lioren configurada');
        }

        try {
            // Preparar detalles
            $detalles = [];
            foreach ($request->detalles as $detalle) {
                $detalles[] = [
                    'codigo' => $detalle['codigo'],
                    'nombre' => mb_substr($detalle['nombre'], 0, 80),
                    'cantidad' => floatval($detalle['cantidad']),
                    'precio' => floatval($detalle['precio']), // Precio BRUTO (con IVA)
                    'unidad' => $detalle['unidad'] ?? 'UN',
                    'exento' => false, // Afecto a IVA
                ];
            }

            // Receptor (si se proporcionó)
            $receptorManual = [];
            if ($request->receptor_rut || $request->receptor_nombre) {
                $receptorManual = array_filter([
                    'rut' => $request->receptor_rut,
                    'rs' => $request->receptor_nombre,
                    'email' => $request->receptor_email,
                ]);
            }
            // BLINDAJE: sanitizar datos fiscales del receptor antes de emitir.
            $receptorManual = $this->sanitizarDatosFiscales(['receptor' => $receptorManual], [], $config, 'boleta')['receptor'] ?? [];

            // Emitir vía LiorenService (punto ÚNICO de comunicación con Lioren).
            $result = app(\App\Services\LiorenService::class)->emitirBoleta($api_key, $detalles, $receptorManual, (string) $request->observaciones);

            if ($result['ok']) {

                // Guardar en base de datos
                $boleta = \App\Models\Boleta::create([
                    'user_id' => auth()->id(),
                    'lioren_id' => $result['id'] ?? null,
                    'tipodoc' => $result['tipodoc'] ?? '39',
                    'folio' => $result['folio'] ?? null,
                    'fecha' => $result['fecha'] ?? now()->format('Y-m-d'),
                    'receptor_rut' => $request->receptor_rut,
                    'receptor_nombre' => $request->receptor_nombre ?? $result['rs'] ?? null,
                    'receptor_email' => $request->receptor_email,
                    'monto_neto' => $result['montoneto'] ?? 0,
                    'monto_exento' => $result['montoexento'] ?? 0,
                    'monto_iva' => $result['montoiva'] ?? 0,
                    'monto_total' => $result['montototal'] ?? 0,
                    'detalles' => $result['detalles'] ?? $detalles,
                    'pagos' => $result['pagos'] ?? null,
                    'observaciones' => $request->observaciones,
                    'status' => 'emitida',
                ]);

                // Guardar PDF y XML como archivos
                if (isset($result['pdf'])) {
                    $boleta->pdf_path = $boleta->savePdfFromBase64($result['pdf']);
                }
                if (isset($result['xml'])) {
                    $boleta->xml_path = $boleta->saveXmlFromBase64($result['xml']);
                }
                $boleta->save();

                return redirect()->route('integracion.boletas')
                    ->with('success', "¡Boleta #{$boleta->folio} emitida exitosamente!");

            } else {
                // Guardar error
                \App\Models\Boleta::create([
                    'user_id' => auth()->id(),
                    'fecha' => now()->format('Y-m-d'),
                    'receptor_rut' => $request->receptor_rut,
                    'receptor_nombre' => $request->receptor_nombre,
                    'monto_total' => 0,
                    'detalles' => $detalles,
                    'observaciones' => $request->observaciones,
                    'status' => 'error',
                    'error_message' => $result['error'] ?? 'Error desconocido',
                ]);

                return back()->with('error', "Error al emitir boleta: " . ($result['error'] ?? 'Error desconocido'));
            }

        } catch (\Exception $e) {
            Log::error("Error emitiendo boleta: " . $e->getMessage());
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Listar boletas emitidas
     */
    public function boletas()
    {
        $query = \App\Models\Boleta::with("user");
        
        // Filtrar por cliente (form uses name="user_id")
        if (request("user_id")) {
            $query->where("user_id", request("user_id"));
        }
        
        // Filtrar por tipo
        if (request("tipo")) {
            if (request("tipo") == "boleta") {
                $query->where("tipodoc", "39");
            } elseif (request("tipo") == "factura") {
                $query->whereIn("tipodoc", ["33", "34"]);
            } elseif (request("tipo") == "nota_credito") {
                $query->where("tipodoc", "61");
            }
        }
        
        // Filtrar por mes (form sends "2026-03" format)
        if (request("mes")) {
            $mesValue = request("mes");
            if (strpos($mesValue, "-") !== false) {
                $parts = explode("-", $mesValue);
                $query->whereYear("created_at", $parts[0]);
                $query->whereMonth("created_at", $parts[1]);
            } else {
                $query->whereMonth("created_at", $mesValue);
            }
        }
        
        $documentos = $query->orderBy("created_at", "desc")->paginate(20);
        
        // Estadisticas - fix the orWhere bug
        $estadisticas = [
            "total" => \App\Models\Boleta::count(),
            "boletas" => \App\Models\Boleta::where("tipodoc", "39")->count(),
            "facturas" => \App\Models\Boleta::whereIn("tipodoc", ["33", "34"])->count(),
            "notas_credito" => \App\Models\NotaCredito::where("status", "emitida")->count(),
        ];
        
        // Get clients using Spatie roles
        $clientes = \App\Models\User::role("cliente")->get();
        
        // Per-client document stats with plan limits
        $clienteStats = [];
        foreach ($clientes as $cliente) {
            $suscripcion = \App\Models\Suscripcion::with("plan")
                ->where("user_id", $cliente->id)
                ->where("estado", "activa")
                ->first();
            
            $limiteDocumentos = null;
            $planNombre = "Sin plan";
            
            // Contar documentos dentro del ciclo de suscripción (no mes calendario)
            if ($suscripcion && $suscripcion->plan) {
                $planNombre = $suscripcion->plan->nombre;
                $limiteDocumentos = $suscripcion->plan->monthly_order_limit;
                $inicioCiclo = $suscripcion->fecha_inicio;
                $finCiclo = $suscripcion->fecha_fin ?? $suscripcion->proximo_pago ?? now();
            } else {
                $inicioCiclo = now()->startOfMonth();
                $finCiclo = now()->endOfMonth();
            }
            
            $docsEmitidosBoletas = \App\Models\Boleta::where("user_id", $cliente->id)
                ->whereBetween("created_at", [$inicioCiclo, $finCiclo])
                ->where("status", "emitida")
                ->count();
            $docsEmitidosFacturas = \App\Models\FacturaEmitida::where("user_id", $cliente->id)
                ->whereBetween("created_at", [$inicioCiclo, $finCiclo])
                ->where("status", "emitida")
                ->count();
            $docsEmitidos = $docsEmitidosBoletas + $docsEmitidosFacturas;
            
            $clienteStats[] = [
                "id" => $cliente->id,
                "name" => $cliente->name,
                "email" => $cliente->email,
                "plan" => $planNombre,
                "docs_emitidos" => $docsEmitidos,
                "docs_total" => \App\Models\Boleta::where("user_id", $cliente->id)->count() + \App\Models\FacturaEmitida::where("user_id", $cliente->id)->count(),
                "limite" => $limiteDocumentos,
                "disponibles" => $limiteDocumentos ? max(0, $limiteDocumentos - $docsEmitidos) : null,
            ];
        }
        
        return view("integracion.boletas", compact("documentos", "estadisticas", "clientes", "clienteStats"));
    }

    /**
     * Descargar PDF de boleta
     */
    public function boletaPdf($id)
    {
        $boleta = \App\Models\Boleta::findOrFail($id);

        // Intentar primero con el archivo
        if ($boleta->pdf_path && \Storage::exists($boleta->pdf_path)) {
            return response(\Storage::get($boleta->pdf_path))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "inline; filename=boleta_{$boleta->folio}.pdf");
        }

        // Fallback a base64 si existe (para compatibilidad con datos antiguos)
        if ($boleta->pdf_base64) {
            $pdf = base64_decode($boleta->pdf_base64);
            return response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "inline; filename=boleta_{$boleta->folio}.pdf");
        }

        abort(404, 'PDF no disponible');
    }

    /**
     * Descargar XML de boleta
     */
    public function boletaXml($id)
    {
        $boleta = \App\Models\Boleta::findOrFail($id);

        if (!$boleta->xml_base64) {
            abort(404, 'XML no disponible');
        }

        $xml = base64_decode($boleta->xml_base64);
        
        return response($xml)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', "attachment; filename=boleta_{$boleta->folio}.xml");
    }

    /**
     * Ver estado de la integración
     */
    public function estado()
    {
        $config = \App\Models\IntegracionConfig::getActiva();
        
        $stats = [
            'productos' => \App\Models\ProductMapping::where('sync_status', 'synced')->count(),
            'boletas' => \App\Models\Boleta::where('status', 'emitida')->count(),
            'total_facturado' => \App\Models\Boleta::where('status', 'emitida')->sum('monto_total'),    
        ];

        return view('integracion.estado', compact('config', 'stats'));
    }

    /**
     * Mapeo de ciudades/comunas normalizadas
     */
    private $mapeoNormalizado = [
        'stgo' => 'santiago',
        'conce' => 'concepción',
        'valpo' => 'valparaíso',
        'la serena' => 'la serena',
        'antofa' => 'antofagasta',
        'las condes' => 'las condes',
        'providencia' => 'providencia',
        'ñuñoa' => 'ñuñoa',
        'maipu' => 'maipú',
        'la florida' => 'la florida',
        'puente alto' => 'puente alto',
    ];

    /**
     * Normalizar nombre de ciudad
     */
    private function normalizarCiudad($ciudad)
    {
        $ciudadLower = strtolower(trim($ciudad));
        return $this->mapeoNormalizado[$ciudadLower] ?? $ciudadLower;
    }

    /**
     * Resetear/Eliminar integración completa
     */
    public function resetearIntegracion(Request $request)
    {
        try {
            Log::info("=== INICIANDO RESETEO DE INTEGRACIÓN ===");

            $config = \App\Models\IntegracionConfig::where('user_id', auth()->id())->first();
            
            $resultados = [
                'webhooks_eliminados' => 0,
                'config_eliminada' => false,
                'productos_eliminados' => 0,
                'facturas_eliminadas' => 0,
                'errores' => [],
            ];

            // 1. Eliminar webhooks de Shopify (si existe configuración)
            if ($config) {
                try {
                    Log::info("Obteniendo webhooks de Shopify...");
                    
                    $response = Http::withHeaders([
                        'X-Shopify-Access-Token' => $config->shopify_token,
                    ])->get("https://{$config->shopify_tienda}/admin/api/2024-01/webhooks.json");

                    if ($response->successful()) {
                        $webhooks = $response->json()['webhooks'] ?? [];
                        
                        foreach ($webhooks as $webhook) {
                            $deleteResponse = Http::withHeaders([
                                'X-Shopify-Access-Token' => $config->shopify_token,
                            ])->delete("https://{$config->shopify_tienda}/admin/api/2024-01/webhooks/{$webhook['id']}.json");

                            if ($deleteResponse->successful()) {
                                $resultados['webhooks_eliminados']++;
                                Log::info("Webhook eliminado: {$webhook['id']}");
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $resultados['errores'][] = "Error eliminando webhooks: " . $e->getMessage();
                    Log::error("Error eliminando webhooks: " . $e->getMessage());
                }
            }

            // 2. Eliminar productos sincronizados (opcional)
            if ($request->has('eliminar_productos')) {
                $productosEliminados = \App\Models\ProductMapping::count();
                \App\Models\ProductMapping::truncate();
                $resultados['productos_eliminados'] = $productosEliminados;
                Log::info("Productos eliminados: {$productosEliminados}");
            }

            // 3. Eliminar facturas emitidas (opcional)
            if ($request->has('eliminar_facturas')) {
                $facturasEliminadas = \App\Models\FacturaEmitida::count();
                \App\Models\FacturaEmitida::truncate();
                $resultados['facturas_eliminadas'] = $facturasEliminadas;
                Log::info("Facturas eliminadas: {$facturasEliminadas}");
            }

            // 4. Eliminar configuración de BD
            if ($config) {
                $config->delete();
                $resultados['config_eliminada'] = true;
                Log::info("Configuración eliminada de BD");
            }

            // 5. Limpiar sesión
            session()->forget(['shopify_tienda', 'shopify_token', 'shopify_secret', 'lioren_api_key', 'facturacion_enabled']);
            Log::info("Sesión limpiada");

            Log::info("=== RESETEO COMPLETADO ===", $resultados);

            return redirect()->route('integracion.dashboard')->with('success', '✅ Integración reseteada exitosamente. Puedes configurar desde cero.');

        } catch (\Exception $e) {
            Log::error("Error reseteando integración: " . $e->getMessage());
            return redirect()->route('integracion.dashboard')->with('error', '❌ Error al resetear: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar vista de confirmación para resetear
     */
    public function confirmarReset()
    {
        $config = \App\Models\IntegracionConfig::where('user_id', auth()->id())->first();
        $productosCount = \App\Models\ProductMapping::count();
        $facturasCount = \App\Models\FacturaEmitida::count();

        return view('integracion.resetear', compact('config', 'productosCount', 'facturasCount'));
    }

    /**
     * Actualizar nota del pedido en Shopify con el numero de folio.
     * Obtiene la nota existente y la agrega al final, ademas agrega tags.
     */
    /**
     * Sincronizar inventario después de procesar un pedido pagado
     */
    private function syncInventoryAfterOrder($order, $config)
    {
        try {
            if (!$config->sync_inventario_enabled) {
                Log::channel('single')->info('📦 Sincronización de inventario deshabilitada para este cliente');
                return;
            }

            Log::channel('single')->info('📦 Iniciando sincronización de inventario post-venta', [
                'order_id' => $order['id'] ?? null,
                'order_number' => $order['order_number'] ?? null,
            ]);

            $inventorySync = new InventorySyncService($config->user_id);
            $result = $inventorySync->syncAfterOrderPaid($order);

            if ($result['success']) {
                Log::channel('single')->info('📦 Inventario sincronizado', [
                    'total_items' => $result['total_items'] ?? 0,
                    'synced' => $result['synced'] ?? 0,
                ]);
            } else {
                Log::channel('single')->warning('📦 Inventario no sincronizado: ' . ($result['message'] ?? 'Error desconocido'));
            }
        } catch (\Exception $e) {
            // No fallar el procesamiento del pedido por error de inventario
            Log::channel('single')->error('📦 Error sincronizando inventario: ' . $e->getMessage());
        }
    }

    private function updateShopifyOrderNote($orderId, $note, $config)
    {
        try {
            Log::channel('single')->info("Actualizando nota en Shopify para pedido #{$orderId}: {$note}");
            
            // Primero obtener la nota actual del pedido para no sobreescribirla
            $getResponse = Http::withHeaders([
                'X-Shopify-Access-Token' => $config->shopify_token,
                'Content-Type' => 'application/json',
            ])->get("https://{$config->shopify_tienda}/admin/api/2024-10/orders/{$orderId}.json?fields=id,note,tags");
            
            $existingNote = '';
            $existingTags = '';
            if ($getResponse->successful()) {
                $orderData = $getResponse->json()['order'] ?? [];
                $existingNote = $orderData['note'] ?? '';
                $existingTags = $orderData['tags'] ?? '';
            }
            
            // Construir nota final: agregar nueva nota al final de la existente
            if (strlen($existingNote) > 0 && strpos($existingNote, $note) === false) {
                $finalNote = $existingNote . "\n" . $note;
            } elseif (strlen($existingNote) > 0) {
                $finalNote = $existingNote;
            } else {
                $finalNote = $note;
            }
            
            // Agregar tag con el tipo de documento
            $newTag = str_replace(' ', '-', $note);
            if (strlen($existingTags) > 0 && strpos($existingTags, $newTag) === false) {
                $finalTags = $existingTags . ', ' . $newTag;
            } elseif (strlen($existingTags) === 0) {
                $finalTags = $newTag;
            } else {
                $finalTags = $existingTags;
            }
            
            // Actualizar pedido con nota y tags
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $config->shopify_token,
                'Content-Type' => 'application/json',
            ])->put("https://{$config->shopify_tienda}/admin/api/2024-10/orders/{$orderId}.json", [
                'order' => [
                    'id' => $orderId,
                    'note' => $finalNote,
                    'tags' => $finalTags,
                ]
            ]);

            if ($response->successful()) {
                Log::channel('single')->info("Nota y tags actualizados exitosamente en Shopify para pedido #{$orderId}");
                return true;
            } else {
                Log::channel('single')->error("Error actualizando nota en Shopify", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::channel('single')->error("Excepcion actualizando nota en Shopify: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Contar documentos emitidos dentro del ciclo de suscripción actual del cliente.
     * Si no tiene suscripción activa, usa mes calendario como fallback.
     */
    private function getMonthlyOrderCount($userId)
    {
        // Obtener suscripción activa para determinar el ciclo
        $suscripcion = \App\Models\Suscripcion::where('user_id', $userId)
            ->where('estado', 'activa')
            ->first();
        
        if ($suscripcion && $suscripcion->fecha_inicio) {
            $inicioCiclo = $suscripcion->fecha_inicio;
            $finCiclo = $suscripcion->fecha_fin ?? $suscripcion->proximo_pago ?? now();
        } else {
            // Fallback: mes calendario si no hay suscripción
            $inicioCiclo = now()->startOfMonth();
            $finCiclo = now()->endOfMonth();
        }
        
        $boletasCount = \App\Models\Boleta::where('user_id', $userId)
            ->whereBetween('created_at', [$inicioCiclo, $finCiclo])
            ->where('status', 'emitida')
            ->count();
        $facturasCount = \App\Models\FacturaEmitida::where('user_id', $userId)
            ->whereBetween('created_at', [$inicioCiclo, $finCiclo])
            ->where('status', 'emitida')
            ->count();
        return $boletasCount + $facturasCount;
    }

    /**
     * Procesar cancelación de pedido y emitir Nota de Crédito
     */
    /**
     * Procesar pedido PAGADO (webhook orders/paid).
     * Cubre los pedidos que nacieron con pago 'pending' (transferencia/depósito): orders/create
     * NO emite documento hasta que el pago esté confirmado. Cuando el merchant confirma el pago,
     * Shopify dispara orders/paid y aquí se emite el documento, SI aún no existe.
     * Para pagos inmediatos (donde orders/create ya emitió) este método no hace nada (el documento
     * ya existe), evitando doble emisión. Usa el mismo lock que orders/create para coordinar.
     */
    private function procesarPedidoPagado($order, $api_key, $config)
    {
        $orderId = $order['id'] ?? null;
        if (!$orderId) {
            Log::channel('single')->warning('orders/paid sin order_id, se omite');
            return;
        }

        $lockName = 'dte_emit_' . $config->user_id . '_' . $orderId;
        $gotLock = \DB::select("SELECT GET_LOCK(?, 0) as locked", [$lockName])[0]->locked;
        if (!$gotLock) {
            Log::channel('single')->info("orders/paid: otro proceso ya está emitiendo el pedido #{$orderId}. Omitiendo.");
            return;
        }

        try {
            $existeBoleta = \App\Models\Boleta::where('shopify_order_id', (string) $orderId)
                ->where('user_id', $config->user_id)->where('status', 'emitida')->exists();
            $existeFactura = \App\Models\FacturaEmitida::where('shopify_order_id', (string) $orderId)
                ->where('user_id', $config->user_id)->where('status', 'emitida')->exists();

            if ($existeBoleta || $existeFactura) {
                Log::channel('single')->info("orders/paid: el pedido #{$orderId} ya tiene documento emitido. Nada que hacer.");
                return;
            }

            // No existe documento => el pedido nació pendiente y ahora el pago está confirmado. Emitir.
            Log::channel('single')->info("orders/paid: pago confirmado y sin documento previo. Emitiendo para pedido #{$orderId}.");
            if ($config->facturacion_enabled) {
                $this->procesarPedidoConFacturacion($order, $api_key, $config);
            } else {
                $this->procesarPedido($order, $api_key, $config);
            }
        } catch (\Throwable $e) {
            Log::channel('single')->error('Error en procesarPedidoPagado (orders/paid): ' . $e->getMessage());
        } finally {
            \DB::select("SELECT RELEASE_LOCK(?)", [$lockName]);
        }
    }

    /**
     * Procesar pedido editado (Documentos Postventa)
     * Cuando un pedido se modifica en Shopify:
     * - Si se agregan productos (monto mayor): emite nueva boleta/factura por la diferencia
     * - Si se eliminan productos (monto menor): emite nota de crédito parcial por la diferencia
     */
    private function procesarPedidoEditado($webhookData, $api_key, $config)
    {
        try {
            // El webhook orders/edited de Shopify envía los datos dentro de 'order_edit'
            // La estructura es: { "order_edit": { "id": ..., "order_id": 123456, ... } }
            // El order_id real está en $webhookData['order_edit']['order_id']
            $orderId = $webhookData['order_id'] 
                ?? $webhookData['id'] 
                ?? ($webhookData['order_edit']['order_id'] ?? null)
                ?? null;
            
            // Si aún no tenemos order_id, intentar extraerlo del admin_graphql_api_order_id
            if (!$orderId && isset($webhookData['order_edit']['admin_graphql_api_order_id'])) {
                // Formato: gid://shopify/Order/123456789
                $gid = $webhookData['order_edit']['admin_graphql_api_order_id'];
                if (preg_match('/Order\/(\d+)/', $gid, $matches)) {
                    $orderId = $matches[1];
                }
            }
            
            Log::channel('single')->info('=== PROCESANDO PEDIDO EDITADO (POSTVENTA) ===', [
                'order_id' => $orderId,
                'webhook_keys' => array_keys($webhookData),
                'has_order_edit' => isset($webhookData['order_edit']),
                'order_edit_keys' => isset($webhookData['order_edit']) ? array_keys($webhookData['order_edit']) : [],
            ]);

            if (!$orderId) {
                Log::channel('single')->error('No se encontró order_id en el webhook de edición', [
                    'webhook_data_sample' => json_encode(array_slice($webhookData, 0, 5)),
                ]);
                return;
            }

            // Obtener el pedido actualizado desde Shopify API
            $shopifyDomain = $config->shopify_tienda;
            $accessToken = $config->shopify_token;
            
            $response = \Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
            ])->get("https://{$shopifyDomain}/admin/api/2024-01/orders/{$orderId}.json");

            if (!$response->successful()) {
                Log::channel('single')->error('Error al obtener pedido de Shopify', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return;
            }

            $order = $response->json()['order'] ?? null;
            if (!$order) {
                Log::channel('single')->error('Pedido no encontrado en respuesta de Shopify');
                return;
            }

            // current_total_price refleja el total REAL del pedido tras ediciones y devoluciones.
            // total_price NO descuenta items eliminados (refunds), por lo que sobre-facturaba
            // cuando una edicion intercambiaba productos (quitar uno y agregar otro).
            $newTotal = floatval($order['current_total_price'] ?? $order['total_price'] ?? 0);

            // Buscar la boleta o factura original emitida para este pedido
            // SOLUCIÓN DEFINITIVA: Buscar primero en facturas, luego en boletas
            $facturaOriginal = \App\Models\FacturaEmitida::where('shopify_order_id', (string) $orderId)
                ->where('user_id', $config->user_id)
                ->where('status', 'emitida')
                ->orderBy('created_at', 'desc')
                ->first();

            $boletaOriginal = null;
            if ($facturaOriginal) {
                // Crear objeto compatible con el flujo existente usando datos de la factura
                $boletaOriginal = (object) [
                    'id' => $facturaOriginal->id,
                    'folio' => $facturaOriginal->folio,
                    'tipodoc' => '33', // Factura
                    'monto_total' => $facturaOriginal->monto_total,
                    'monto_neto' => $facturaOriginal->monto_neto,
                    'receptor_rut' => $facturaOriginal->rut_receptor,
                    'receptor_nombre' => $facturaOriginal->razon_social,
                    'receptor_email' => $facturaOriginal->receptor_email ?? null,
                    'fecha' => $facturaOriginal->emitida_at ?? $facturaOriginal->created_at,
                ];
                Log::channel('single')->info("✅ Factura original encontrada para pedido editado: Folio {$facturaOriginal->folio}, tipodoc=33");
            } else {
                $boletaOriginal = \App\Models\Boleta::where('shopify_order_id', (string) $orderId)
                    ->where('user_id', $config->user_id)
                    ->where('tipodoc', '!=', '61') // Excluir notas de crédito
                    ->orderBy('created_at', 'desc')
                    ->first();
                if ($boletaOriginal) {
                    Log::channel('single')->info("✅ Boleta original encontrada para pedido editado: Folio {$boletaOriginal->folio}, tipodoc={$boletaOriginal->tipodoc}");
                }
            }

            if (!$boletaOriginal) {
                Log::channel('single')->warning('No se encontró documento original para el pedido editado', [
                    'order_id' => $orderId,
                ]);
                // Si no hay documento original, emitir uno nuevo completo
                if ($config->facturacion_enabled) {
                    $this->procesarPedidoConFacturacion($order, $api_key, $config);
                } else {
                    $this->procesarPedido($order, $api_key, $config);
                }
                return;
            }

            // Comparar contra TODO lo ya facturado para este pedido (boletas + facturas, menos
            // notas de credito), no solo el documento original. Asi soporta multiples ediciones
            // sin doble facturar: la diferencia siempre es (total actual) - (total ya facturado).
            $totalFacturado = $this->totalFacturadoPedido($orderId, $config->user_id);
            $diferencia = $newTotal - $totalFacturado;

            Log::channel('single')->info('📊 Comparación de montos postventa', [
                'total_facturado' => $totalFacturado,
                'new_total' => $newTotal,
                'diferencia' => $diferencia,
            ]);

            if (abs($diferencia) < 1) {
                // No hay diferencia significativa
                Log::channel('single')->info('Sin diferencia significativa en el monto - No se emite documento postventa');
                return;
            }

            if ($diferencia > 0) {
                // Monto mayor: se agregaron productos - emitir nueva boleta/factura por la diferencia
                Log::channel('single')->info("📈 Monto aumentó en \${$diferencia} - Emitiendo documento adicional");
                
                // Construir un pseudo-order con solo los items nuevos/diferencia
                $this->emitirDocumentoPostventaAdicional($order, $api_key, $config, $boletaOriginal, $diferencia);
                
            } else {
                // Monto menor: se eliminaron productos - emitir nota de crédito parcial
                $diferenciaNegativa = abs($diferencia);
                Log::channel('single')->info("📉 Monto disminuyó en \${$diferenciaNegativa} - Emitiendo Nota de Crédito parcial");
                
                $planFeaturesNC = $this->getPlanFeatures($config->user_id);
                if ($planFeaturesNC['notas_credito_enabled']) {
                    $this->emitirNotaCreditoPostventa($order, $api_key, $config, $boletaOriginal, $diferenciaNegativa);
                } else {
                    Log::channel('single')->warning('Notas de Crédito deshabilitadas por PLAN - No se puede emitir NC por edición de pedido');
                }
            }

        } catch (\Exception $e) {
            Log::channel('single')->error('Error en procesarPedidoEditado: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Suma el total ya facturado para un pedido: boletas + facturas emitidas, menos notas de credito.
     * Se usa para calcular cuanto falta facturar tras una edicion (postventa), soportando
     * multiples ediciones e intercambios de productos sin doble facturar.
     */
    private function totalFacturadoPedido($orderId, $userId): float
    {
        $total = 0.0;

        $boletas = \App\Models\Boleta::where('shopify_order_id', (string) $orderId)
            ->where('user_id', $userId)
            ->where('status', 'emitida')
            ->get(['tipodoc', 'monto_total']);
        foreach ($boletas as $b) {
            // Nota de credito (tipodoc 61) resta; boletas/facturas (39/33) suman.
            if ((string) $b->tipodoc === '61') {
                $total -= floatval($b->monto_total);
            } else {
                $total += floatval($b->monto_total);
            }
        }

        $facturas = \App\Models\FacturaEmitida::where('shopify_order_id', (string) $orderId)
            ->where('user_id', $userId)
            ->where('status', 'emitida')
            ->get(['monto_total']);
        foreach ($facturas as $f) {
            $total += floatval($f->monto_total);
        }

        // Notas de credito viven en su propia tabla (notas_credito) y restan del total facturado.
        $totalNC = \App\Models\NotaCredito::where('shopify_order_id', (string) $orderId)
            ->where('user_id', $userId)
            ->where('status', 'emitida')
            ->sum('monto_total');
        $total -= floatval($totalNC);

        return $total;
    }

    /**
     * Emitir documento adicional por productos agregados postventa
     */
    private function emitirDocumentoPostventaAdicional($order, $api_key, $config, $boletaOriginal, $diferencia)
    {
        try {
            $orderId = $order['id'] ?? null;
            $customer = $order['customer'] ?? [];
            $customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
            $customerEmail = $customer['email'] ?? $order['email'] ?? null;
            
            // Para facturas: usar datos del receptor del documento original (RUT, razón social, etc.)
            $tipoDoc = $boletaOriginal->tipodoc ?? '39';
            $rut = null;
            $razonSocial = null;
            $giro = null;
            $direccion = null;
            
            if ($tipoDoc == '33' && $boletaOriginal) {
                // Usar datos del receptor de la factura original
                $rut = $boletaOriginal->receptor_rut ?? null;
                $razonSocial = $boletaOriginal->receptor_nombre ?? $customerName;
                Log::channel('single')->info('Usando datos del receptor de factura original', [
                    'rut' => $rut,
                    'razon_social' => $razonSocial,
                ]);
            }
            
            // Fallback: Extraer RUT de note_attributes si no lo tenemos del doc original
            if (!$rut && isset($order['note_attributes']) && is_array($order['note_attributes'])) {
                foreach ($order['note_attributes'] as $attr) {
                    if (strtolower($attr['name'] ?? '') === 'rut') {
                        $rut = $attr['value'];
                        break;
                    }
                }
            }
            
            // Fallback: Extraer datos fiscales de la nota del pedido
            if (!$rut && isset($order['note'])) {
                $noteLines = explode("\n", $order['note']);
                foreach ($noteLines as $linea) {
                    $linea = trim($linea);
                    if (preg_match('/^RUT\s*:\s*(.+)$/iu', $linea, $matches)) {
                        $rut = trim($matches[1]);
                    } elseif (preg_match('/^(RAZON|RAZON_SOCIAL|RAZÓN)\s*:\s*(.+)$/iu', $linea, $matches)) {
                        $razonSocial = $razonSocial ?: trim($matches[2]);
                    } elseif (preg_match('/^GIRO\s*:\s*(.+)$/iu', $linea, $matches)) {
                        $giro = trim($matches[1]);
                    } elseif (preg_match('/^(DIR|DIRECCION|DIRECCIÓN)\s*:\s*(.+)$/iu', $linea, $matches)) {
                        $direccion = trim($matches[2]);
                    }
                }
            }
            
            // Crear detalle con la diferencia como un item
            $detalles = [[
                'codigo' => 'POSTVENTA-' . $orderId,
                'nombre' => mb_substr('Ajuste Postventa - Productos adicionales Pedido #' . ($order['order_number'] ?? $orderId), 0, 80),
                'cantidad' => 1,
                'precio' => round($diferencia),
                'unidad' => 'UN',
                'exento' => false,
            ]];
            
            // Emitir vía LiorenService (punto ÚNICO de comunicación con Lioren).
            $lioren = app(\App\Services\LiorenService::class);
            $obsPostventa = 'Postventa - Productos adicionales Pedido #' . ($order['order_number'] ?? $orderId);

            if ($tipoDoc == '39') {
                // Boleta: precio bruto con IVA
                $receptorB = ($rut || $customerName)
                    ? ['rut' => $rut, 'rs' => $customerName ?: 'Cliente', 'email' => $customerEmail]
                    : [];
                $result = $lioren->emitirBoleta($api_key, $detalles, $receptorB, $obsPostventa);
            } else {
                // Factura: precio neto sin IVA
                $detalles[0]['precio'] = round($detalles[0]['precio'] / 1.19);
                $receptorF = [
                    'rut' => str_replace('.', '', $rut ?? '66666666-6'),
                    'rs' => $razonSocial ?: ($customerName ?: 'Cliente'),
                    'giro' => $giro ?: 'Comercio',
                    'comuna' => 295,
                    'ciudad' => 131,
                    'direccion' => mb_substr($direccion ?: 'Sin dirección', 0, 50),
                ];
                if ($customerEmail) {
                    $receptorF['email'] = mb_substr($customerEmail, 0, 80);
                }
                $result = $lioren->emitirFactura($api_key, $detalles, $receptorF, $obsPostventa);
            }

            if ($result['ok']) {
                if ($tipoDoc == '33') {
                    // Guardar como factura emitida
                    $montoNeto = round($diferencia / 1.19);
                    $montoIva = round($diferencia) - $montoNeto;
                    \App\Models\FacturaEmitida::create([
                        'user_id' => $config->user_id,
                        'shopify_order_id' => (string) $orderId,
                        'shopify_order_number' => (string) ($order['order_number'] ?? ''),
                        'tipo_documento' => '33',
                        'lioren_factura_id' => $result['id'] ?? null,
                        'folio' => $result['folio'],
                        'rut_receptor' => $rut ?? '66666666-6',
                        'razon_social' => $razonSocial ?: ($customerName ?: 'Cliente'),
                        'monto_neto' => $montoNeto,
                        'monto_iva' => $montoIva,
                        'monto_total' => round($diferencia),
                        'status' => 'emitida',
                        'emitida_at' => now(),
                    ]);
                    Log::channel('single')->info("✅ Factura postventa adicional emitida exitosamente", [
                        'folio' => $result['folio'],
                        'total' => round($diferencia),
                        'neto' => $montoNeto,
                        'iva' => $montoIva,
                        'order_id' => $orderId,
                    ]);
                } else {
                    // Guardar como boleta (tipodoc 39: el monto de la diferencia es BRUTO con IVA)
                    $boletaTotal = (int) round($diferencia);
                    $boletaNeto  = (int) round($boletaTotal / 1.19);
                    $boletaIva   = $boletaTotal - $boletaNeto;
                    $boleta = \App\Models\Boleta::create([
                        'user_id' => $config->user_id,
                        'shopify_order_id' => (string) $orderId,
                        'lioren_id' => $result['id'] ?? null,
                        'tipodoc' => '39',
                        'folio' => $result['folio'],
                        'fecha' => now()->format('Y-m-d'),
                        'receptor_rut' => $rut ?? '66666666-6',
                        'receptor_nombre' => $customerName ?: 'Cliente',
                        'monto_neto' => $boletaNeto,
                        'monto_iva' => $boletaIva,
                        'monto_total' => $boletaTotal,
                        'status' => 'emitida',
                        'observaciones' => 'Postventa - Productos adicionales Pedido #' . ($order['order_number'] ?? $orderId),
                    ]);
                    if (isset($result['pdf']) && $boleta->canSavePdf()) {
                        $boleta->pdf_path = $boleta->savePdfFromBase64($result['pdf']);
                    }
                    if (isset($result['xml']) && $boleta->canSaveXml()) {
                        $boleta->xml_path = $boleta->saveXmlFromBase64($result['xml']);
                    }
                    $boleta->save();
                    Log::channel('single')->info("✅ Boleta postventa adicional emitida exitosamente", [
                        'folio' => $result['folio'],
                        'total' => round($diferencia),
                        'order_id' => $orderId,
                    ]);
                }
                
                // Actualizar nota y tags en Shopify
                $this->actualizarShopifyPostventaAdicional($order, $config, $result['folio'], $tipoDoc, $boletaOriginal->folio);
                
            } else {
                Log::channel('single')->error('Error al emitir documento postventa adicional en Lioren', [
                    'status' => $result['status'] ?? null,
                    'response' => $result['error'] ?? $result,
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('single')->error('Error en emitirDocumentoPostventaAdicional: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
    
    /**
     * Actualizar nota y tags en Shopify después de emitir documento postventa adicional
     */
    private function actualizarShopifyPostventaAdicional($order, $config, $folioNuevo, $tipoDoc, $folioOriginal)
    {
        try {
            $orderId = $order['id'] ?? null;
            if (!$orderId) return;
            
            $tipoNombre = ($tipoDoc == '33') ? 'Factura' : 'Boleta';
            $tagNuevo = "{$tipoNombre}-Lioren-#{$folioNuevo}";
            
            // Agregar a nota existente
            $notaActual = $order['note'] ?? '';
            $notaAdicional = "\n{$tipoNombre} Adicional Lioren #{$folioNuevo}";
            $nuevaNota = trim($notaActual . $notaAdicional);
            
            // Agregar tag
            $tagsActuales = $order['tags'] ?? '';
            $nuevasTags = $tagsActuales ? "{$tagsActuales}, {$tagNuevo}" : $tagNuevo;
            
            $shopifyDomain = $config->shopify_tienda;
            $accessToken = $config->shopify_token;
            
            \Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type' => 'application/json',
            ])->put("https://{$shopifyDomain}/admin/api/2024-01/orders/{$orderId}.json", [
                'order' => [
                    'id' => $orderId,
                    'note' => $nuevaNota,
                    'tags' => $nuevasTags,
                ]
            ]);
            
            Log::channel('single')->info("Nota y tags actualizados en Shopify para documento postventa adicional", [
                'order_id' => $orderId,
                'folio_nuevo' => $folioNuevo,
                'tag' => $tagNuevo,
            ]);
        } catch (\Exception $e) {
            Log::channel('single')->error('Error actualizando Shopify postventa adicional: ' . $e->getMessage());
        }
    }


    /**
     * Emitir Nota de Crédito parcial por productos eliminados postventa
     */
    private function emitirNotaCreditoPostventa($order, $api_key, $config, $boletaOriginal, $diferencia)
    {
        try {
            // NO EMITIR NC si la diferencia es $0
            if (floatval($diferencia) <= 0) {
                Log::channel('single')->info("Pedido con diferencia \$0 - No se emite nota de crédito postventa");
                return;
            }

            $orderId = $order['id'] ?? null;
            $customer = $order['customer'] ?? [];
            $customerName = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
            $customerEmail = $customer['email'] ?? $order['email'] ?? null;

            // Idempotencia: la garantiza el calculo por total ya facturado (totalFacturadoPedido,
            // que resta las NC ya emitidas). Si el pedido no cambio, la diferencia es 0 y no se
            // emite nada; y soporta multiples reducciones legitimas sobre el mismo documento.
            // El webhookReceiver ya bloquea el reproceso del mismo webhook (advisory lock + cache).

            // SOLUCIÓN DEFINITIVA: Diferenciar cálculo según tipo de documento original
            // - BOLETA (39): Lioren espera precio BRUTO (con IVA) → enviar tal cual
            // - FACTURA (33): Lioren espera precio NETO (sin IVA) → dividir por 1.19
            $tipoDocOrig = $boletaOriginal->tipodoc ?? '39';
            if ($tipoDocOrig === '39') {
                // Boleta: enviar monto bruto (con IVA incluido)
                $montoNetoNC = round($diferencia);
                Log::channel('single')->info("NC Postventa para BOLETA: precio bruto (con IVA) = \${$montoNetoNC}");
            } else {
                // Factura: enviar monto neto (sin IVA)
                $montoNetoNC = round($diferencia / 1.19);
                Log::channel('single')->info("NC Postventa para FACTURA: precio neto (sin IVA) = \${$montoNetoNC} (de \${$diferencia} con IVA)");
            }
            
            $fechaRef = $boletaOriginal->fecha instanceof \Carbon\Carbon 
                ? $boletaOriginal->fecha->format('Y-m-d') 
                : $boletaOriginal->fecha;

            // Preparar datos de Nota de Crédito con formato correcto Lioren
            $receptorNC = array_filter([
                'rut' => str_replace('.', '', $boletaOriginal->receptor_rut ?? '66666666-6'),
                'rs' => mb_substr($boletaOriginal->receptor_nombre ?? $customerName ?: 'Cliente', 0, 100),
                'giro' => 'Comercio',
                'comuna' => 295, // Santiago (id=295 en Lioren)
                'ciudad' => 131,
                'direccion' => 'Sin dirección',
                'email' => $customerEmail ? mb_substr($customerEmail, 0, 80) : null,
            ]);
            // BLINDAJE: sanitizar datos fiscales del receptor antes de emitir.
            $receptorNC = $this->sanitizarDatosFiscales(['receptor' => $receptorNC], $order ?? [], $config)['receptor'];

            $detallesNC = [[
                'nombre' => mb_substr('Ajuste Postventa - Productos eliminados Pedido #' . ($order['order_number'] ?? $orderId), 0, 80),
                'cantidad' => 1,
                'precio' => $montoNetoNC,
                'exento' => false,
            ]];
            $referenciaNC = [
                'fecha' => $fechaRef,
                'tipodoc' => $boletaOriginal->tipodoc ?? '39',
                'folio' => (string) $boletaOriginal->folio,
                'razon' => 1,
                'glosa' => 'Ajuste postventa - Productos eliminados del pedido',
            ];

            // Emitir vía LiorenService (punto ÚNICO de comunicación con Lioren).
            $lioren = app(\App\Services\LiorenService::class);
            $result = $lioren->emitirNotaCredito($api_key, $detallesNC, $receptorNC, $referenciaNC, 'NC Postventa Pedido #' . ($order['order_number'] ?? $orderId));

            if ($result['ok']) {
                // Guardar la Nota de Crédito
                $ncTotal = (int) round($diferencia);
                $ncNeto  = (int) round($ncTotal / 1.19);
                $notaCredito = \App\Models\NotaCredito::create([
                    'user_id' => $config->user_id,
                    'shopify_order_id' => (string) $orderId,
                    'shopify_order_number' => (string) ($order['order_number'] ?? ''),
                    'tipo_documento_original' => (string) ($boletaOriginal->tipodoc ?? '39'),
                    'folio_original' => (int) $boletaOriginal->folio,
                    'lioren_nota_id' => $result['id'] ?? null,
                    'folio' => $result['folio'],
                    'rut_receptor' => $boletaOriginal->receptor_rut ?? '66666666-6',
                    'razon_social' => $boletaOriginal->receptor_nombre ?? ($customerName ?: 'Cliente'),
                    'monto_neto' => $result['montoneto'] ?? $ncNeto,
                    'monto_iva' => $result['montoiva'] ?? ($ncTotal - $ncNeto),
                    'monto_total' => $result['montototal'] ?? $ncTotal,
                    'status' => 'emitida',
                    'glosa' => 'Ajuste postventa - Productos eliminados del pedido',
                    'emitida_at' => now(),
                ]);

                // Guardar PDF y XML si están disponibles
                if (isset($result['pdf'])) {
                    $notaCredito->pdf_base64 = $result['pdf'];
                    if (method_exists($notaCredito, 'savePdfFromBase64')) {
                        $notaCredito->pdf_path = $notaCredito->savePdfFromBase64($result['pdf']);
                    }
                }
                if (isset($result['xml'])) {
                    $notaCredito->xml_base64 = $result['xml'];
                    if (method_exists($notaCredito, 'saveXmlFromBase64')) {
                        $notaCredito->xml_path = $notaCredito->saveXmlFromBase64($result['xml']);
                    }
                }
                $notaCredito->save();

                Log::channel('single')->info("✅ Nota de Crédito postventa emitida exitosamente", [
                    'folio' => $result['folio'],
                    'total' => round($diferencia),
                    'order_id' => $orderId,
                    'boleta_original_folio' => $boletaOriginal->folio,
                ]);

                // Actualizar nota en Shopify si está habilitado
                if ($config && $config->shopify_visibility_enabled && isset($result['folio'])) {
                    $this->updateShopifyOrderNote($orderId, "Nota de Crédito Lioren #{$result['folio']} (Postventa)", $config);
                }
            } else {
                Log::channel('single')->error('Error al emitir NC postventa en Lioren', [
                    'status' => $result['status'] ?? null,
                    'response' => $result['error'] ?? $result,
                ]);
            }

        } catch (\Exception $e) {
            Log::channel('single')->error('Error en emitirNotaCreditoPostventa: ' . $e->getMessage());
        }
    }

    private function procesarCancelacion($order, $api_key, $config)
    {
        Log::channel('single')->info('=== PROCESANDO CANCELACIÓN DE PEDIDO ===', [
            'order_id' => $order['id'] ?? null,
            'order_number' => $order['order_number'] ?? null,
        ]);

        try {
            $orderId = (string)$order['id'];

            // Extraer email del cliente para envío de NC
            $customer = $order['customer'] ?? [];
            $customerEmail = $customer['email'] ?? $order['email'] ?? null;

            // Buscar boleta o factura original
            $boleta = \App\Models\Boleta::where('observaciones', 'LIKE', "%Shopify #{$order['order_number']}%")
                ->where('user_id', $config->user_id)
                ->where('status', 'emitida')
                ->first();

            // Si no se encontró por observaciones, buscar por shopify_order_id
            if (!$boleta) {
                $boleta = \App\Models\Boleta::where('shopify_order_id', $orderId)
                    ->where('user_id', $config->user_id)
                    ->where('status', 'emitida')
                    ->first();
            }

            $factura = \App\Models\FacturaEmitida::where('shopify_order_id', $orderId)
                ->where('user_id', $config->user_id)
                ->where('status', 'emitida')
                ->first();

            if (!$boleta && !$factura) {
                Log::channel('single')->warning('No se encontró boleta/factura original para este pedido');
                return;
            }

            // Determinar tipo de documento original y folio
            if ($factura) {
                $tipoDocOriginal = '33'; // Factura
                $folioOriginal = $factura->folio;
                $rutReceptor = $factura->rut_receptor;
                $razonSocial = $factura->razon_social;
                $montoTotal = $factura->monto_total;
            } else {
                $tipoDocOriginal = '39'; // Boleta
                $folioOriginal = $boleta->folio;
                $rutReceptor = $boleta->receptor_rut ?? '66666666-6';
                $razonSocial = $boleta->receptor_nombre ?? 'Cliente';
                $montoTotal = $boleta->monto_total;
                // Usar email de la boleta original si no hay email en la orden
                if (!$customerEmail && $boleta->receptor_email) {
                    $customerEmail = $boleta->receptor_email;
                }
            }

            Log::channel('single')->info("Documento original encontrado: Tipo {$tipoDocOriginal}, Folio {$folioOriginal}");

            // Emitir Nota de Crédito
            $this->emitirNotaCredito(
                $api_key,
                $config,
                $tipoDocOriginal,
                $folioOriginal,
                $rutReceptor,
                $razonSocial,
                $montoTotal,
                $orderId,
                $order['order_number'] ?? $orderId,
                'Anula documento por cancelación de pedido',
                $customerEmail
            );

        } catch (\Exception $e) {
            Log::channel('single')->error('Error procesando cancelación: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Procesar reembolso y emitir Nota de Crédito
     */
    private function procesarReembolso($refund, $api_key, $config)
    {
        Log::channel('single')->info('=== PROCESANDO REEMBOLSO ===', [
            'refund_id' => $refund['id'] ?? null,
            'order_id' => $refund['order_id'] ?? null,
            'order_id_type' => gettype($refund['order_id'] ?? null),
        ]);

        try {
            $orderId = (string)($refund['order_id'] ?? null);

            if (!$orderId) {
                Log::channel('single')->warning('Reembolso sin order_id');
                return;
            }

            Log::channel('single')->info("🔍 Buscando documento original para order_id: {$orderId}");

            // Buscar boleta o factura original
            $factura = \App\Models\FacturaEmitida::where('shopify_order_id', $orderId)
                ->where('user_id', $config->user_id)
                ->where('status', 'emitida')
                ->first();

            $boleta = null;
            if (!$factura) {
                // Buscar boleta por shopify_order_id
                $boleta = \App\Models\Boleta::where('shopify_order_id', $orderId)
                    ->where('user_id', $config->user_id)
                    ->where('status', 'emitida')
                    ->first();
                
                if ($boleta) {
                    Log::channel('single')->info("✅ Boleta encontrada: Folio {$boleta->folio}");
                } else {
                    Log::channel('single')->warning("❌ No se encontró boleta con shopify_order_id: {$orderId}");
                }
            }

            if (!$boleta && !$factura) {
                Log::channel('single')->warning('No se encontró boleta/factura original para este reembolso');
                return;
            }

            // Extraer email del cliente del reembolso
            $customerEmail = $refund['user_id'] ?? null; // Shopify refund no siempre tiene email directo
            // Intentar obtener email de la orden original en el refund
            if (isset($refund['order']) && isset($refund['order']['customer'])) {
                $customerEmail = $refund['order']['customer']['email'] ?? null;
            } elseif (isset($refund['order']) && isset($refund['order']['email'])) {
                $customerEmail = $refund['order']['email'];
            } else {
                $customerEmail = null;
            }

            // Determinar tipo de documento original y folio
            if ($factura) {
                $tipoDocOriginal = '33'; // Factura
                $folioOriginal = $factura->folio;
                $rutReceptor = $factura->rut_receptor;
                $razonSocial = $factura->razon_social;
                $montoDocOriginal = $factura->monto_total;
                $orderNumber = $factura->shopify_order_number;
            } else {
                $tipoDocOriginal = '39'; // Boleta
                $folioOriginal = $boleta->folio;
                $rutReceptor = $boleta->receptor_rut ?? '66666666-6';
                $razonSocial = $boleta->receptor_nombre ?? 'Cliente';
                $montoDocOriginal = $boleta->monto_total;
                $orderNumber = $orderId;
                // Usar email de la boleta original si no hay email en el refund
                if (!$customerEmail && $boleta->receptor_email) {
                    $customerEmail = $boleta->receptor_email;
                }
            }
            
            // MEJORA: Calcular monto real del reembolso (parcial o total)
            // Extraer monto de transactions del refund de Shopify
            $montoReembolso = 0;
            if (isset($refund['transactions']) && is_array($refund['transactions'])) {
                foreach ($refund['transactions'] as $transaction) {
                    if (isset($transaction['amount']) && floatval($transaction['amount']) > 0) {
                        $montoReembolso += floatval($transaction['amount']);
                    }
                }
            }
            
            // Si no hay transactions, intentar calcular desde refund_line_items
            if ($montoReembolso == 0 && isset($refund['refund_line_items']) && is_array($refund['refund_line_items'])) {
                foreach ($refund['refund_line_items'] as $refundItem) {
                    $subtotal = floatval($refundItem['subtotal'] ?? 0);
                    $totalTax = floatval($refundItem['total_tax'] ?? 0);
                    $montoReembolso += $subtotal + $totalTax;
                }
            }
            
            // Si aún no hay monto, usar el monto total del documento original (reembolso completo)
            if ($montoReembolso <= 0) {
                $montoReembolso = $montoDocOriginal;
                Log::channel('single')->info("Reembolso sin monto explícito - usando monto total del documento: \${$montoDocOriginal}");
            }
            
            // Redondear a entero (pesos chilenos)
            $montoReembolso = intval(round($montoReembolso));
            
            // Determinar si es reembolso parcial o total
            $esParcial = $montoReembolso < $montoDocOriginal;
            $glosaNC = $esParcial 
                ? "Reembolso parcial (\${$montoReembolso} de \${$montoDocOriginal})"
                : 'Anula documento por reembolso total';

            // ===== Idempotencia por total realmente facturado =====
            // El reembolso y el webhook orders/edited pueden dispararse juntos (p.ej. al cambiar
            // un producto por otro). Para no duplicar documentos, el reembolso solo emite NC por la
            // SOBRE-facturacion real del pedido: diferencia = total actual - total ya facturado
            // (boletas + facturas - notas de credito). Misma logica que orders/edited => idempotente.
            try {
                $shopifyDomain = $config->shopify_tienda;
                $accessToken = $config->shopify_token;
                $orderResponse = Http::withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                ])->timeout(10)->get("https://{$shopifyDomain}/admin/api/2024-01/orders/{$orderId}.json");

                if ($orderResponse->successful()) {
                    $currentOrder = $orderResponse->json()['order'] ?? null;
                    if ($currentOrder) {
                        $currentTotalPrice = floatval($currentOrder['current_total_price'] ?? 0);
                        $totalFacturado = $this->totalFacturadoPedido($orderId, $config->user_id);
                        $diferenciaNeta = $currentTotalPrice - $totalFacturado;

                        Log::channel('single')->info('Reembolso: evaluacion por total facturado', [
                            'current_total_price' => $currentTotalPrice,
                            'total_facturado' => $totalFacturado,
                            'diferencia_neta' => $diferenciaNeta,
                        ]);

                        // Solo se emite NC si el pedido quedo SOBRE-facturado (diferencia negativa).
                        // Si es >= 0, es un cambio de producto/aumento (lo documenta orders/edited)
                        // o el pedido ya esta cuadrado por una emision previa.
                        if ($diferenciaNeta >= -1) {
                            Log::channel('single')->info('Reembolso: el pedido no quedo sobre-facturado. No se emite NC (lo maneja orders/edited o ya esta cuadrado).');
                            return;
                        }

                        // Emitir NC exactamente por la sobre-facturacion real.
                        $montoReembolso = (int) round(abs($diferenciaNeta));
                    }
                }
            } catch (\Exception $checkEx) {
                Log::channel('single')->warning('No se pudo verificar total facturado del reembolso: ' . $checkEx->getMessage());
                // Si no se puede verificar, continuar con el monto calculado de las transacciones.
            }
            // ===== FIN =====
            
            Log::channel('single')->info("Documento original encontrado: Tipo {$tipoDocOriginal}, Folio {$folioOriginal}", [
                'monto_documento' => $montoDocOriginal,
                'monto_reembolso' => $montoReembolso,
                'es_parcial' => $esParcial,
            ]);
            
            // Emitir Nota de Crédito con el monto real del reembolso
            $this->emitirNotaCredito(
                $api_key,
                $config,
                $tipoDocOriginal,
                $folioOriginal,
                $rutReceptor,
                $razonSocial,
                $montoReembolso, // Monto real del reembolso (parcial o total)
                $orderId,
                $orderNumber,
                $glosaNC,
                $customerEmail
            );

        } catch (\Exception $e) {
            Log::channel('single')->error('Error procesando reembolso: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Obtener comunas desde Lioren con cache (24 horas)
     */
    private function obtenerComunas($api_key)
    {
        return \Cache::remember('lioren_comunas', 86400, function () use ($api_key) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$api_key}",
                    'Accept' => 'application/json',
                ])->timeout(10)->get('https://www.lioren.cl/api/comunas');

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                Log::channel('single')->error('Error obteniendo comunas: ' . $e->getMessage());
            }
            return [];
        });
    }

    /**
     * Normalizar nombre de comuna/ciudad (quita acentos, mayúsculas, espacios)
     */
    private function normalizarNombre($nombre)
    {
        $nombre = mb_strtolower($nombre, 'UTF-8');
        $nombre = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
            $nombre
        );
        $nombre = preg_replace('/\s+/', ' ', $nombre);
        return trim($nombre);
    }

    /**
     * Buscar ID de comuna por nombre (fuzzy search)
     */
    private function buscarComunaId($nombreComuna, $api_key)
    {
        $comunas = $this->obtenerComunas($api_key);
        
        if (empty($comunas)) {
            return 13101; // Santiago por defecto si falla
        }

        $nombreBuscado = $this->normalizarNombre($nombreComuna);
        
        // Búsqueda exacta
        foreach ($comunas as $comuna) {
            if ($this->normalizarNombre($comuna['nombre'] ?? '') === $nombreBuscado) {
                return $comuna['id'];
            }
        }
        
        // Búsqueda parcial (fuzzy)
        foreach ($comunas as $comuna) {
            $nombreComuna = $this->normalizarNombre($comuna['nombre'] ?? '');
            if (strpos($nombreComuna, $nombreBuscado) !== false || strpos($nombreBuscado, $nombreComuna) !== false) {
                return $comuna['id'];
            }
        }
        
        // Fallback: Santiago
        foreach ($comunas as $comuna) {
            if ($this->normalizarNombre($comuna['nombre'] ?? '') === 'santiago') {
                return $comuna['id'];
            }
        }
        
        return 13101; // ID hardcoded de Santiago como último recurso
    }

    /**
     * Emitir Nota de Crédito en Lioren
     */
    private function emitirNotaCredito($api_key, $config, $tipoDocOriginal, $folioOriginal, $rutReceptor, $razonSocial, $montoTotal, $orderId, $orderNumber, $glosa, $customerEmail = null)
    {
        try {
            // NO EMITIR NC si el monto es $0
            if (floatval($montoTotal) <= 0) {
                Log::channel('single')->info("Pedido #{$orderNumber} con monto \$0 - No se emite nota de crédito");
                return;
            }

            Log::channel('single')->info('Emitiendo Nota de Crédito en Lioren', [
                'tipo_doc_original' => $tipoDocOriginal,
                'folio_original' => $folioOriginal,
                'monto' => $montoTotal,
            ]);

            // PROTECCIÓN ANTI-DUPLICADOS: Verificar si ya existe NC para este pedido
            $existingNC = \App\Models\NotaCredito::where('shopify_order_id', $orderId)
                ->where('status', 'emitida')
                ->first();
            if ($existingNC) {
                Log::channel('single')->warning("NC ya existe para pedido #{$orderId} (folio #{$existingNC->folio}). Omitiendo duplicado.");
                return;
            }

            // SOLUCIÓN DEFINITIVA: Diferenciar cálculo según tipo de documento original
            // - BOLETA (39): Lioren espera precio BRUTO (con IVA) → enviar tal cual
            // - FACTURA (33): Lioren espera precio NETO (sin IVA) → dividir por 1.19
            if ($tipoDocOriginal === '39') {
                // Boleta: enviar monto bruto (con IVA incluido), Lioren NO suma IVA adicional
                $precioNC = round($montoTotal);
                Log::channel('single')->info("NC para BOLETA: precio bruto (con IVA) = \${$precioNC}");
            } else {
                // Factura: enviar monto neto (sin IVA), Lioren sumará 19% de IVA
                $precioNC = round($montoTotal / 1.19);
                Log::channel('single')->info("NC para FACTURA: precio neto (sin IVA) = \${$precioNC} (de \${$montoTotal} con IVA)");
            }

            // Obtener ID de comuna válido desde API de Lioren
            $comunaId = $this->buscarComunaId('Santiago', $api_key);

            // Receptor de la nota de crédito
            $receptorNC = array_filter([
                'rut' => str_replace('.', '', $rutReceptor),
                'rs' => mb_substr($razonSocial, 0, 100),
                'giro' => 'Comercio',
                'comuna' => $comunaId,
                'ciudad' => 131, // Santiago
                'direccion' => 'Sin dirección',
                'email' => $customerEmail ? mb_substr($customerEmail, 0, 80) : null,
            ]);
            // BLINDAJE: sanitizar datos fiscales del receptor antes de emitir.
            $receptorNC = $this->sanitizarDatosFiscales(['receptor' => $receptorNC], [], $config)['receptor'];

            $detallesNC = [[
                'nombre' => mb_substr('Devolución por cancelación/reembolso', 0, 80),
                'cantidad' => 1,
                'precio' => $precioNC,
                'exento' => false,
            ]];
            $referenciaNC = [
                'fecha' => now()->format('Y-m-d'),
                'tipodoc' => $tipoDocOriginal, // 39=Boleta, 33=Factura
                'folio' => (string) $folioOriginal,
                'razon' => 1, // Anula documento de referencia
                'glosa' => $glosa,
            ];

            // Emitir vía LiorenService (punto ÚNICO de comunicación con Lioren).
            $lioren = app(\App\Services\LiorenService::class);
            $result = $lioren->emitirNotaCredito($api_key, $detallesNC, $receptorNC, $referenciaNC, $glosa);

            if ($result['ok']) {
                // Guardar Nota de Crédito en base de datos
                $notaCredito = \App\Models\NotaCredito::create([
                    'user_id' => $config->user_id,
                    'shopify_order_id' => $orderId,
                    'shopify_order_number' => $orderNumber,
                    'tipo_documento_original' => $tipoDocOriginal,
                    'folio_original' => $folioOriginal,
                    'lioren_nota_id' => $result['id'] ?? null,
                    'folio' => $result['folio'] ?? null,
                    'rut_receptor' => $rutReceptor,
                    'razon_social' => $razonSocial,
                    'monto_neto' => $result['montoneto'] ?? 0,
                    'monto_iva' => $result['montoiva'] ?? 0,
                    'monto_total' => $result['montototal'] ?? 0,
                    'status' => 'emitida',
                    'glosa' => $glosa,
                    'emitida_at' => now(),
                ]);

                // Guardar PDF y XML como archivos
                if (isset($result['pdf'])) {
                    $notaCredito->pdf_path = $notaCredito->savePdfFromBase64($result['pdf']);
                }
                if (isset($result['xml'])) {
                    $notaCredito->xml_path = $notaCredito->saveXmlFromBase64($result['xml']);
                }
                $notaCredito->save();

                Log::channel('single')->info("✅ Nota de Crédito #{$result['folio']} emitida exitosamente");

                // Actualizar nota en Shopify si está habilitado
                if ($config->shopify_visibility_enabled && isset($result['folio'])) {
                    $this->updateShopifyOrderNote($orderId, "Nota de Crédito Lioren #{$result['folio']}", $config);
                }

            } else {
                Log::channel('single')->error('Error al emitir Nota de Crédito en Lioren', [
                    'status' => $result['status'] ?? null,
                    'body' => $result['error'] ?? '',
                ]);

                // Guardar error en BD
                \App\Models\NotaCredito::create([
                    'user_id' => $config->user_id,
                    'shopify_order_id' => $orderId,
                    'shopify_order_number' => $orderNumber,
                    'tipo_documento_original' => $tipoDocOriginal,
                    'folio_original' => $folioOriginal,
                    'rut_receptor' => $rutReceptor,
                    'razon_social' => $razonSocial,
                    'monto_neto' => 0,
                    'monto_iva' => 0,
                    'monto_total' => 0,
                    'status' => 'error',
                    'glosa' => $glosa,
                    'error_message' => $result['error'] ?? '',
                ]);
            }

        } catch (\Exception $e) {
            Log::channel('single')->error('Excepción al emitir Nota de Crédito: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Listar notas de crédito emitidas
     */
    public function notasCredito()
    {
        $notasCredito = \App\Models\NotaCredito::orderBy('created_at', 'desc')->paginate(20);
        return view('integracion.notas-credito', compact('notasCredito'));
    }

    /**
     * Descargar PDF de nota de crédito
     */
    public function notaCreditoPdf($id)
    {
        $notaCredito = \App\Models\NotaCredito::findOrFail($id);

        // Intentar primero con el archivo
        if ($notaCredito->pdf_path && \Storage::exists($notaCredito->pdf_path)) {
            return response(\Storage::get($notaCredito->pdf_path))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "inline; filename=nota_credito_{$notaCredito->folio}.pdf");
        }

        // Fallback a base64 si existe (para compatibilidad con datos antiguos)
        if ($notaCredito->pdf_base64) {
            $pdf = base64_decode($notaCredito->pdf_base64);
            return response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "inline; filename=nota_credito_{$notaCredito->folio}.pdf");
        }

        abort(404, 'PDF no disponible');
    }

    /**
     * Descargar XML de nota de crédito
     */
    public function notaCreditoXml($id)
    {
        $notaCredito = \App\Models\NotaCredito::findOrFail($id);

        if (!$notaCredito->xml_base64) {
            abort(404, 'XML no disponible');
        }

        $xml = base64_decode($notaCredito->xml_base64);
        
        return response($xml)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', "attachment; filename=nota_credito_{$notaCredito->folio}.xml");
    }

    public function correoMasivo(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'asunto' => 'required|string|max:255',
            'contenido' => 'required|string',
        ]);
        
        // Get all active integration clients (users with integracion_configs)
        $configs = \App\Models\IntegracionConfig::with('user')->get();
        $enviados = 0;
        $errores = 0;
        
        foreach ($configs as $config) {
            $user = $config->user;
            if (!$user || !$user->email) continue;
            
            $asunto = $request->asunto;
            $contenido = $request->contenido;
            $nombreCliente = $user->name ?? $user->email;
            
            $contenidoHtml = $this->buildIntegracionEmailHtml($nombreCliente, $request->asunto, $request->contenido);
            
            try {
                \Mail::html($contenidoHtml, function ($message) use ($user, $asunto) {
                    $message->to($user->email, $user->name)->subject($asunto);
                });
                $enviados++;
            } catch (\Exception $e) {
                $errores++;
                \Log::error('Error correo masivo integracion a ' . $user->email . ': ' . $e->getMessage());
            }
        }
        
        return back()->with('success', "Correo masivo enviado: {$enviados} exitosos, {$errores} errores.");
    }


    /**
     * Correos Integraciones - Pagina dedicada
     */
    public function correosIntegracion(\Illuminate\Http\Request $request)
    {
        // Get all integration clients
        $configs = \App\Models\IntegracionConfig::with("user")->get();
        $clientes = $configs->map(function($c) { return $c->user; })->filter(function($u) { return $u && $u->email; })->values();
        
        // Get historial from correos_integracion table or build from logs
        $historial = \App\Models\CorreoIntegracion::query();
        
        if ($request->filled("cliente")) {
            $historial->where("user_id", $request->cliente);
        }
        if ($request->filled("estado")) {
            $historial->where("estado", $request->estado);
        }
        if ($request->filled("desde")) {
            $historial->whereDate("created_at", ">=", $request->desde);
        }
        if ($request->filled("hasta")) {
            $historial->whereDate("created_at", "<=", $request->hasta);
        }
        
        $historial = $historial->orderBy("created_at", "desc")->get();
        
        return view("integracion.correos", compact("clientes", "historial"));
    }

    /**
     * Enviar correo individual a un cliente de integracion
     */
    public function correoIndividual(\Illuminate\Http\Request $request)
    {
        $request->validate([
            "cliente_id" => "required|exists:users,id",
            "asunto" => "required|string|max:255",
            "contenido" => "required|string",
        ]);
        
        $user = \App\Models\User::find($request->cliente_id);
        if (!$user || !$user->email) {
            return back()->with("error", "Cliente no encontrado o sin email.");
        }
        
        $nombreCliente = $user->name ?? $user->email;
        $contenidoHtml = $this->buildIntegracionEmailHtml($nombreCliente, $request->asunto, $request->contenido);
        
        try {
            \Mail::html($contenidoHtml, function ($message) use ($user, $request) {
                $message->to($user->email, $user->name)->subject($request->asunto);
            });
            
            // Log the email
            \App\Models\CorreoIntegracion::create([
                "user_id" => $user->id,
                "destinatario_nombre" => $nombreCliente,
                "destinatario_email" => $user->email,
                "asunto" => $request->asunto,
                "contenido" => $request->contenido,
                "estado" => "enviado",
                "tipo" => "individual",
            ]);
            
            return back()->with("success", "Correo enviado exitosamente a {$user->email}");
        } catch (\Exception $e) {
            \App\Models\CorreoIntegracion::create([
                "user_id" => $user->id,
                "destinatario_nombre" => $nombreCliente,
                "destinatario_email" => $user->email,
                "asunto" => $request->asunto,
                "contenido" => $request->contenido,
                "estado" => "error",
                "tipo" => "individual",
            ]);
            return back()->with("error", "Error al enviar correo: " . $e->getMessage());
        }
    }

    /**
     * Build the HTML email template with integraciones branding
     */
    private function buildIntegracionEmailHtml($nombreCliente, $asunto, $contenido)
    {
        return '
        <div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #0A0A0A;">
            <!-- Header oscuro con branding Big Studio -->
            <div style="background: #0A0A0A; padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid #1A1A1A;">
                <h1 style="color: #FFFFFF; margin: 0 0 4px; font-size: 20px; font-weight: bold; letter-spacing: 2px;">INTEGRACIONES</h1>
                <h2 style="color: #FFC107; margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 3px;">BIG STUDIO</h2>
                <div style="width: 60px; height: 3px; background: #FFC107; margin: 14px auto 0;"></div>
            </div>
            <!-- Banner dorado -->
            <div style="background: #FFC107; padding: 14px 20px; text-align: center;">
                <p style="color: #0A0A0A; margin: 0; font-size: 16px; font-weight: bold; letter-spacing: 0.5px;">' . htmlspecialchars($asunto) . '</p>
            </div>
            <!-- Contenido principal -->
            <div style="padding: 30px 30px 20px;">
                <p style="font-size: 15px; color: #FFFFFF; margin: 0 0 15px;">Hola <strong style="color: #FFC107;">' . htmlspecialchars($nombreCliente) . '</strong>,</p>
                <div style="font-size: 14px; color: #BBBBBB; line-height: 1.8; margin: 0 0 20px; padding: 20px; background: #111111; border-left: 4px solid #FFC107; border-radius: 0 6px 6px 0;">
                    ' . nl2br(htmlspecialchars($contenido)) . '
                </div>
                <p style="font-size: 12px; color: #666666; text-align: center; margin: 15px 0 0;">Si tienes consultas, contactanos a hola@bigstudio.cl o por WhatsApp.</p>
            </div>
            <!-- Separador dorado -->
            <div style="height: 2px; background: #FFC107; margin: 0 30px;"></div>
            <!-- Footer oscuro -->
            <div style="background: #0A0A0A; padding: 25px 30px; text-align: center; border-top: 1px solid #1A1A1A;">
                <p style="color: #FFFFFF; font-size: 13px; margin: 0 0 5px; font-weight: bold;">Equipo Integraciones BigStudio</p>
                <p style="color: #FFC107; font-size: 12px; margin: 0 0 5px;">hola@bigstudio.cl</p>
                <p style="color: #555555; font-size: 11px; margin: 12px 0 0;">Este es un correo automatico. Si tienes consultas, contactanos por el chat interno o responde a este correo.</p>
            </div>
        </div>';
    }

    /**
     * Obtener las features habilitadas según el PLAN del usuario (no la config individual)
     * Las features se determinan por el plan de la suscripción activa del usuario.
     */
    private function getPlanFeatures($userId)
    {
        $suscripcion = \App\Models\Suscripcion::where('user_id', $userId)
            ->where('estado', 'activa')
            ->first();
        
        if ($suscripcion && $suscripcion->plan_id) {
            $plan = \App\Models\Plan::find($suscripcion->plan_id);
            if ($plan) {
                return [
                    'facturacion_enabled' => (bool) $plan->facturacion_enabled,
                    'notas_credito_enabled' => (bool) $plan->notas_credito_enabled,
                    'documentos_postventa_enabled' => (bool) $plan->documentos_postventa_enabled,
                    'sync_inventario_enabled' => (bool) $plan->sync_inventario_enabled,
                    'shopify_visibility_enabled' => (bool) $plan->shopify_visibility_enabled,
                    'boletas_enabled' => (bool) $plan->boletas_enabled,
                ];
            }
        }
        
        // Fallback: si no hay suscripción activa, usar la config individual (compatibilidad)
        $config = \App\Models\IntegracionConfig::where('user_id', $userId)->first();
        if ($config) {
            return [
                'facturacion_enabled' => (bool) $config->facturacion_enabled,
                'notas_credito_enabled' => (bool) $config->notas_credito_enabled,
                'documentos_postventa_enabled' => (bool) $config->documentos_postventa_enabled,
                'sync_inventario_enabled' => true,
                'shopify_visibility_enabled' => true,
                'boletas_enabled' => true,
            ];
        }
        
        // Default: todo deshabilitado
        return [
            'facturacion_enabled' => false,
            'notas_credito_enabled' => false,
            'documentos_postventa_enabled' => false,
            'sync_inventario_enabled' => false,
            'shopify_visibility_enabled' => false,
            'boletas_enabled' => false,
        ];
    }

}
