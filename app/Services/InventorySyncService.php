<?php
namespace App\Services;

use App\Models\WarehouseMapping;
use App\Models\LocationBodegaMapping;
use App\Models\PendingLocationMapping;
use App\Models\ProductMapping;
use App\Models\SyncLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InventorySyncService
{
    protected $userId;
    protected $config;

    public function __construct($userId)
    {
        $this->userId = $userId;
        $this->config = \App\Models\IntegracionConfig::where('user_id', $userId)
            ->where('activo', true)
            ->first();
    }

    /**
     * Verificar si la sincronización de inventario está habilitada
     */
    public function isEnabled()
    {
        return $this->config && $this->config->sync_inventario_enabled;
    }

    /**
     * Sincronizar inventario después de un pedido pagado en Shopify
     * Descuenta stock en Lioren (si el módulo de bodegas está activo)
     * y registra el movimiento
     */
    public function syncAfterOrderPaid($orderData)
    {
        if (!$this->isEnabled()) {
            Log::info("📦 Sync inventario deshabilitado para usuario {$this->userId}");
            return ['success' => false, 'message' => 'Sincronización de inventario deshabilitada'];
        }

        Log::info("📦 Iniciando sincronización de inventario post-venta", [
            'order_id' => $orderData['id'] ?? null,
            'order_number' => $orderData['order_number'] ?? null,
            'line_items_count' => count($orderData['line_items'] ?? []),
        ]);

        $results = [];
        $lineItems = $orderData['line_items'] ?? [];

        foreach ($lineItems as $item) {
            try {
                $result = $this->processLineItem($item, $orderData);
                $results[] = $result;
            } catch (\Exception $e) {
                Log::error("Error procesando line item: " . $e->getMessage(), [
                    'variant_id' => $item['variant_id'] ?? null,
                    'product_id' => $item['product_id'] ?? null,
                ]);
                $results[] = [
                    'success' => false,
                    'product' => $item['title'] ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => true,
            'results' => $results,
            'total_items' => count($lineItems),
            'synced' => count(array_filter($results, fn($r) => $r['success'] ?? false)),
        ];
    }

    /**
     * Procesar un line item individual del pedido
     */
    protected function processLineItem($item, $orderData)
    {
        $variantId = $item['variant_id'] ?? null;
        $productId = $item['product_id'] ?? null;
        $quantity = $item['quantity'] ?? 1;
        $sku = $item['sku'] ?? null;
        $title = $item['title'] ?? 'Producto desconocido';

        Log::info("📦 Procesando item: {$title}", [
            'variant_id' => $variantId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'sku' => $sku,
        ]);

        // 1. Buscar mapeo de producto
        $mapping = $this->findProductMapping($productId, $variantId, $sku);

        // 2. Intentar descontar stock en Lioren (si hay mapeo y módulo de bodegas activo)
        $liorenResult = $this->tryLiorenStockAdjust($mapping, $quantity, $title);

        // 3. Registrar movimiento en sync_logs
        SyncLog::logSuccess(
            $this->userId,
            'order_paid',
            'shopify_to_lioren',
            'inventory',
            (string)($variantId ?? $productId),
            "Venta: -" . $quantity . " de '" . $title . "' (Pedido #" . ($orderData['order_number'] ?? $orderData['id']) . ")" .
            ($liorenResult['synced_to_lioren'] ? " | Lioren actualizado" : " | Lioren: " . ($liorenResult['message'] ?? 'No disponible')),
            [
                'order_id' => $orderData['id'],
                'order_number' => $orderData['order_number'] ?? null,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'sku' => $sku,
                'quantity_sold' => $quantity,
                'lioren_synced' => $liorenResult['synced_to_lioren'],
                'mapping_found' => $mapping !== null,
            ]
        );

        return [
            'success' => true,
            'product' => $title,
            'quantity' => $quantity,
            'sku' => $sku,
            'lioren_synced' => $liorenResult['synced_to_lioren'],
            'mapping_found' => $mapping !== null,
        ];
    }

    /**
     * Buscar mapeo de producto por diferentes criterios
     */
    protected function findProductMapping($productId, $variantId, $sku)
    {
        // Buscar por variant_id primero
        if ($variantId) {
            $mapping = ProductMapping::where('user_id', $this->userId)
                ->where('shopify_variant_id', $variantId)
                ->first();
            if ($mapping) return $mapping;
        }

        // Buscar por product_id
        if ($productId) {
            $mapping = ProductMapping::where('user_id', $this->userId)
                ->where('shopify_product_id', $productId)
                ->first();
            if ($mapping) return $mapping;
        }

        // Buscar por SKU
        if ($sku) {
            $mapping = ProductMapping::findBySku($sku, $this->userId);
            if ($mapping) return $mapping;
        }

        return null;
    }

    /**
     * Intentar ajustar stock en Lioren
     */
    protected function tryLiorenStockAdjust($mapping, $quantity, $title)
    {
        if (!$mapping || !$mapping->lioren_product_id) {
            return [
                'synced_to_lioren' => false,
                'message' => 'Sin mapeo a producto Lioren',
            ];
        }

        try {
            // Verificar si el módulo de bodegas está disponible
            $bodegaId = $this->getDefaultBodegaId();
            
            if (!$bodegaId) {
                // Intentar obtener bodegas de Lioren
                $bodegasResp = Http::withHeaders([
                    'Authorization' => "Bearer {$this->config->lioren_api_key}",
                    'Accept' => 'application/json',
                ])->get('https://www.lioren.cl/api/bodegas');

                $bodegasData = $bodegasResp->json();
                
                if (isset($bodegasData['errors']) && !empty($bodegasData['errors'])) {
                    return [
                        'synced_to_lioren' => false,
                        'message' => 'Módulo de bodegas no disponible en Lioren: ' . implode(', ', $bodegasData['errors']),
                    ];
                }

                $bodegas = $bodegasData['bodegas'] ?? $bodegasData;
                if (empty($bodegas)) {
                    return [
                        'synced_to_lioren' => false,
                        'message' => 'No hay bodegas configuradas en Lioren',
                    ];
                }

                $bodegaId = $bodegas[0]['id'] ?? null;
            }

            if (!$bodegaId) {
                return [
                    'synced_to_lioren' => false,
                    'message' => 'No se pudo determinar bodega',
                ];
            }

            // Quitar stock en Lioren
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->config->lioren_api_key}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->delete('https://www.lioren.cl/api/stocks', [
                'producto_id' => $mapping->lioren_product_id,
                'bodega_id' => $bodegaId,
                'cantidad' => $quantity,
            ]);

            if ($response->successful()) {
                // Actualizar stock en mapeo local
                // Preservar sync_status 'mapped' para productos vinculados a Lioren
                $updateData = [
                    'stock' => ($mapping->stock ?? 0) - $quantity,
                    'last_synced_at' => now(),
                ];
                if (!$mapping->lioren_product_id) {
                    $updateData['sync_status'] = 'synced';
                }
                $mapping->update($updateData);

                return [
                    'synced_to_lioren' => true,
                    'message' => "Stock actualizado en Lioren: -{$quantity}",
                ];
            } else {
                return [
                    'synced_to_lioren' => false,
                    'message' => 'Error API Lioren: ' . $response->body(),
                ];
            }
        } catch (\Exception $e) {
            return [
                'synced_to_lioren' => false,
                'message' => 'Excepción: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sincronizar inventario desde Shopify (webhook inventory_levels/update)
     */
    public function syncInventoryFromShopify($inventoryData)
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'Sincronización deshabilitada'];
        }

        Log::info("📦 Sincronizando inventario desde webhook", [
            'inventory_item_id' => $inventoryData['inventory_item_id'] ?? null,
            'location_id' => $inventoryData['location_id'] ?? null,
            'available' => $inventoryData['available'] ?? null,
        ]);

        try {
            $inventoryItemId = $inventoryData['inventory_item_id'] ?? null;
            $available = $inventoryData['available'] ?? null;

            if (!$inventoryItemId) {
                return ['success' => false, 'message' => 'No inventory_item_id'];
            }

            // Buscar variante de Shopify que corresponde a este inventory_item_id
            $variant = $this->findVariantByInventoryItemId($inventoryItemId);
            
            if (!$variant) {
                Log::info("No se encontró variante para inventory_item_id: {$inventoryItemId}");
                return ['success' => false, 'message' => 'Variante no encontrada'];
            }

            // Buscar mapeo
            $mapping = ProductMapping::where('user_id', $this->userId)
                ->where('shopify_variant_id', $variant['id'] ?? null)
                ->first();

            if (!$mapping) {
                return ['success' => false, 'message' => 'Sin mapeo de producto'];
            }

            // Actualizar stock local
            $mapping->update([
                'stock' => $available,
                'last_synced_at' => now(),
            ]);

            // Intentar sincronizar con Lioren
            $liorenResult = $this->tryLiorenStockSet($mapping, $available);

            SyncLog::logSuccess(
                $this->userId,
                'webhook',
                'shopify_to_lioren',
                'inventory',
                (string)$inventoryItemId,
                "Inventario actualizado: {$available} unidades" .
                ($liorenResult['synced_to_lioren'] ? " | Lioren sincronizado" : ""),
                [
                    'inventory_item_id' => $inventoryItemId,
                    'available' => $available,
                    'lioren_synced' => $liorenResult['synced_to_lioren'],
                ]
            );

            return ['success' => true, 'available' => $available];
        } catch (\Exception $e) {
            Log::error("Error sincronizando inventario: " . $e->getMessage());
            
            SyncLog::logError(
                $this->userId,
                'webhook',
                'shopify_to_lioren',
                'inventory',
                (string)($inventoryData['inventory_item_id'] ?? 'unknown'),
                $e->getMessage()
            );

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Intentar establecer stock absoluto en Lioren
     */
    protected function tryLiorenStockSet($mapping, $newQuantity)
    {
        if (!$mapping->lioren_product_id) {
            return ['synced_to_lioren' => false, 'message' => 'Sin producto Lioren'];
        }

        try {
            $bodegaId = $this->getDefaultBodegaId();
            if (!$bodegaId) {
                return ['synced_to_lioren' => false, 'message' => 'Sin bodega configurada'];
            }

            // Obtener stock actual de Lioren
            $currentStock = $mapping->stock ?? 0;
            $difference = $newQuantity - $currentStock;

            if ($difference == 0) {
                return ['synced_to_lioren' => true, 'message' => 'Stock ya sincronizado'];
            }

            if ($difference > 0) {
                // Agregar stock
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$this->config->lioren_api_key}",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->post('https://www.lioren.cl/api/stocks', [
                    'producto_id' => $mapping->lioren_product_id,
                    'bodega_id' => $bodegaId,
                    'cantidad' => $difference,
                ]);
            } else {
                // Quitar stock
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$this->config->lioren_api_key}",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->delete('https://www.lioren.cl/api/stocks', [
                    'producto_id' => $mapping->lioren_product_id,
                    'bodega_id' => $bodegaId,
                    'cantidad' => abs($difference),
                ]);
            }

            if ($response->successful()) {
                return ['synced_to_lioren' => true, 'message' => "Stock ajustado: {$difference}"];
            } else {
                return ['synced_to_lioren' => false, 'message' => 'Error API: ' . $response->body()];
            }
        } catch (\Exception $e) {
            return ['synced_to_lioren' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Buscar variante de Shopify por inventory_item_id
     */
    protected function findVariantByInventoryItemId($inventoryItemId)
    {
        if (!$this->config) return null;

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->config->shopify_token,
            ])->get("https://{$this->config->shopify_tienda}/admin/api/2024-10/inventory_items/{$inventoryItemId}.json");

            if ($response->successful()) {
                $item = $response->json()['inventory_item'] ?? null;
                if ($item) {
                    // Buscar la variante que tiene este inventory_item_id
                    // Necesitamos buscar en los productos
                    return ['id' => $item['id'], 'sku' => $item['sku'] ?? null];
                }
            }
        } catch (\Exception $e) {
            Log::error("Error buscando variante: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Obtener bodega por defecto
     */
    protected function getDefaultBodegaId()
    {
        if ($this->config->default_bodega_id) {
            return $this->config->default_bodega_id;
        }

        try {
            $warehouseConfig = WarehouseMapping::getConfig($this->userId);
            return $warehouseConfig->default_bodega_id ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Sincronizar inventario de Shopify a Lioren (sync completo)
     * Obtiene todos los productos de Shopify y actualiza mapeos
     */
    public function fullSync()
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'Sincronización deshabilitada'];
        }

        Log::info("📦 Iniciando sincronización completa de inventario para usuario {$this->userId}");

        try {
            $shopifyProducts = $this->getAllShopifyProducts();
            $liorenProducts = $this->getAllLiorenProducts();
            $synced = 0;
            $errors = 0;
            $autoMapped = 0;

            foreach ($shopifyProducts as $product) {
                foreach ($product['variants'] as $variant) {
                    try {
                        $sku = $variant['sku'] ?? null;
                        
                        // Buscar o crear mapeo
                        $mapping = ProductMapping::firstOrNew(
                            [
                                'user_id' => $this->userId,
                                'shopify_product_id' => $product['id'],
                                'shopify_variant_id' => $variant['id'],
                            ]
                        );
                        
                        // Actualizar datos de Shopify (SKU, precio, título)
                        $mapping->product_title = $product['title'];
                        $mapping->sku = $sku;
                        $mapping->price = $variant['price'] ?? 0;
                        // Solo actualizar stock desde Shopify si el producto NO está mapeado a Lioren
                        // Para productos mapeados, Lioren es la fuente de verdad del stock
                        if (!$mapping->lioren_product_id) {
                            $mapping->stock = $variant['inventory_quantity'] ?? 0;
                        }
                        $mapping->last_synced_at = now();
                        
                        // Preservar estado 'mapped' si ya tiene lioren_product_id
                        if (!$mapping->lioren_product_id) {
                            $mapping->sync_status = 'synced';
                        }
                        
                        $mapping->save();

                        // Intentar auto-mapear con Lioren por SKU/código o nombre
                        if (!$mapping->lioren_product_id) {
                            $liorenMatch = null;
                            $matchMethod = '';
                            
                            if ($sku) {
                                // 1. Coincidencia exacta de SKU/código
                                $liorenMatch = collect($liorenProducts)->first(function ($lp) use ($sku) {
                                    return strtolower(trim($lp['codigo'] ?? '')) === strtolower(trim($sku));
                                });
                                if ($liorenMatch) $matchMethod = 'SKU exacto';
                                

                            }
                            
                            // 3. Coincidencia por nombre del producto (si no hay match por SKU)
                            if (!$liorenMatch && $product['title']) {
                                $shopifyTitle = strtolower(trim($product['title']));
                                $liorenMatch = collect($liorenProducts)->first(function ($lp) use ($shopifyTitle) {
                                    $liorenName = strtolower(trim($lp['nombre'] ?? ''));
                                    return $liorenName !== '' && ($liorenName === $shopifyTitle || 
                                           str_contains($shopifyTitle, $liorenName) || 
                                           str_contains($liorenName, $shopifyTitle));
                                });
                                if ($liorenMatch) $matchMethod = 'nombre producto';
                            }
                            
                            if ($liorenMatch) {
                                $mapping->update([
                                    'lioren_product_id' => $liorenMatch['id'],
                                    'sync_status' => 'mapped',
                                ]);
                                $autoMapped++;
                                Log::info("Auto-mapeado por {$matchMethod}: '{$product['title']}' (SKU: {$sku}) -> Lioren #{$liorenMatch['id']} ({$liorenMatch['nombre']})");
                            }
                        }
                        $synced++;
                    } catch (\Exception $e) {
                        $errors++;
                        Log::error("Error sincronizando variante: " . $e->getMessage());
                    }
                }
            }

            SyncLog::logSuccess(
                $this->userId,
                'full_sync',
                'shopify_to_local',
                'inventory',
                'all',
                "Sincronización completa: {$synced} productos sincronizados, {$autoMapped} auto-mapeados, {$errors} errores"
            );

            return [
                'success' => true,
                'synced' => $synced,
                'errors' => $errors,
                'auto_mapped' => $autoMapped,
                'shopify_products' => count($shopifyProducts),
                'lioren_products' => count($liorenProducts),
            ];
        } catch (\Exception $e) {
            Log::error("Error en sincronización completa: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtener todos los productos de Shopify
     */
    public function getAllShopifyProducts()
    {
        $products = [];
        $url = "https://{$this->config->shopify_tienda}/admin/api/2024-10/products.json?limit=250&fields=id,title,variants";

        while ($url) {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->config->shopify_token,
            ])->get($url);

            if (!$response->successful()) break;

            $data = $response->json();
            $products = array_merge($products, $data['products'] ?? []);

            // Pagination via Link header
            $linkHeader = $response->header('Link');
            $url = null;
            if ($linkHeader && preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches)) {
                $url = $matches[1];
            }
        }

        return $products;
    }

    /**
     * Obtener todos los productos de Lioren
     */
    public function getAllLiorenProducts()
    {
        try {
            $allProducts = [];
            $page = 1;
            $maxPages = 20;
            
            do {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$this->config->lioren_api_key}",
                    'Accept' => 'application/json',
                ])->get("https://www.lioren.cl/api/productos?rpp=100&page={$page}");
                
                if (!$response->successful()) break;
                
                $data = $response->json();
                
                if (isset($data['errors'])) {
                    Log::warning("Lioren API error en pagina {$page}: " . json_encode($data['errors']));
                    break;
                }
                
                if (is_array($data) && !empty($data) && isset($data[0]['id'])) {
                    $allProducts = array_merge($allProducts, $data);
                }
                
                $page++;
                if (!is_array($data) || count($data) < 100) break;
                
            } while ($page <= $maxPages);
            
            Log::info("Lioren: Obtenidos " . count($allProducts) . " productos en " . ($page - 1) . " paginas");
            return $allProducts;
        } catch (\Exception $e) {
            Log::error("Error obteniendo productos Lioren: " . $e->getMessage());
        }
        return [];
    }

    /**
     * Obtener locations de Shopify
     */
    public function getShopifyLocations()
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->config->shopify_token,
        ])->get("https://{$this->config->shopify_tienda}/admin/api/2024-10/locations.json");

        if (!$response->successful()) {
            throw new \Exception("Error obteniendo locations de Shopify: " . $response->body());
        }

        return $response->json()['locations'] ?? [];
    }

    /**
     * Obtener bodegas de Lioren
     */
    public function getLiorenBodegas()
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->config->lioren_api_key}",
            'Accept' => 'application/json',
        ])->get('https://www.lioren.cl/api/bodegas');

        if (!$response->successful()) {
            throw new \Exception("Error obteniendo bodegas de Lioren: " . $response->body());
        }

        $data = $response->json();
        return $data['bodegas'] ?? $data;
    }

    /**
     * Obtener configuración actual
     */
    public function getCurrentConfig()
    {
        try {
            $config = WarehouseMapping::getConfig($this->userId);
            $mappings = LocationBodegaMapping::getMappedLocations($this->userId);
            $pending = PendingLocationMapping::getPending($this->userId);

            return [
                'mode' => $config->sync_mode ?? 'simple',
                'default_bodega' => [
                    'id' => $config->default_bodega_id ?? null,
                    'name' => $config->default_bodega_name ?? null,
                ],
                'mappings' => $mappings,
                'pending_locations' => $pending,
            ];
        } catch (\Exception $e) {
            return [
                'mode' => 'simple',
                'default_bodega' => ['id' => null, 'name' => null],
                'mappings' => [],
                'pending_locations' => [],
            ];
        }
    }

    /**
     * Obtener estadísticas de sincronización
     */
    public function getStats()
    {
        $totalMappings = ProductMapping::where('user_id', $this->userId)->count();
        $mappedToLioren = ProductMapping::where('user_id', $this->userId)
            ->whereNotNull('lioren_product_id')
            ->count();
        $recentLogs = SyncLog::where('user_id', $this->userId)
            ->where('entity_type', 'inventory')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        $errorsToday = SyncLog::where('user_id', $this->userId)
            ->where('entity_type', 'inventory')
            ->where('status', 'error')
            ->whereDate('created_at', today())
            ->count();

        return [
            'total_mappings' => $totalMappings,
            'mapped_to_lioren' => $mappedToLioren,
            'unmapped' => $totalMappings - $mappedToLioren,
            'recent_logs' => $recentLogs,
            'errors_today' => $errorsToday,
            'sync_enabled' => $this->isEnabled(),
        ];
    }
}
