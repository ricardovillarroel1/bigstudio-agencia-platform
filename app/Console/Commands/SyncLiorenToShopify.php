<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\IntegracionConfig;
use App\Models\ProductMapping;
use App\Models\SyncLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncLiorenToShopify extends Command
{
    protected $signature = 'sync:lioren-to-shopify {--user= : Sync only for specific user ID}';
    protected $description = 'Sincronizar stock desde Lioren hacia Shopify para productos mapeados';

    // Shopify REST API limit: 2 requests/second for standard apps
    // We'll do 1 request per 0.6 seconds to stay safe
    private const API_DELAY_MS = 600;
    private const MAX_RETRIES = 2;

    public function handle()
    {
        $userId = $this->option('user');

        $query = IntegracionConfig::where('activo', 1)
            ->where('sync_inventario_enabled', 1)
            ->whereNotNull('shopify_token')
            ->where('shopify_token', '!=', '');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $configs = $query->get();

        if ($configs->isEmpty()) {
            $this->info('No hay configuraciones activas para sincronizar.');
            return 0;
        }

        foreach ($configs as $config) {
            $this->syncForConfig($config);
        }

        return 0;
    }

    protected function syncForConfig(IntegracionConfig $config)
    {
        $this->info("Sincronizando Lioren → Shopify para usuario {$config->user_id} ({$config->shopify_tienda})...");

        try {
            // 1. Obtener todos los productos mapeados
            $mappings = ProductMapping::where('user_id', $config->user_id)
                ->whereNotNull('lioren_product_id')
                ->where('lioren_product_id', '!=', '')
                ->where('sync_status', 'mapped')
                ->get();

            if ($mappings->isEmpty()) {
                $this->info("  Sin productos mapeados para sincronizar.");
                return;
            }

            $this->info("  {$mappings->count()} productos mapeados encontrados.");

            // 2. Obtener todos los productos de Lioren con su stock
            $liorenProducts = $this->getAllLiorenProducts($config);
            if (empty($liorenProducts)) {
                $this->error("  No se pudieron obtener productos de Lioren.");
                return;
            }

            // Crear un mapa de lioren_id => stock total
            $liorenStockMap = [];
            foreach ($liorenProducts as $lp) {
                $totalStock = 0;
                if (isset($lp['stocks']) && is_array($lp['stocks'])) {
                    foreach ($lp['stocks'] as $stock) {
                        $totalStock += intval($stock['cantidad'] ?? 0);
                    }
                }
                $liorenStockMap[$lp['id']] = $totalStock;
            }

            $this->info("  " . count($liorenStockMap) . " productos Lioren con stock obtenidos.");

            // 3. Obtener location de Shopify
            $locationId = $this->getShopifyLocationId($config, $mappings);
            if (!$locationId) {
                $this->error("  No se pudo obtener location de Shopify.");
                return;
            }

            $this->info("  Location ID: {$locationId}");

            // 4. Pre-cargar inventory_item_ids en lotes para evitar llamadas individuales
            $inventoryItemCache = $this->preloadInventoryItemIds($config, $mappings);
            $this->info("  " . count($inventoryItemCache) . " inventory_item_ids cargados.");

            // 5. Comparar y actualizar stock en Shopify donde sea diferente
            $updated = 0;
            $errors = 0;
            $skipped = 0;
            $notInLioren = 0;

            foreach ($mappings as $mapping) {
                $liorenId = $mapping->lioren_product_id;
                $liorenStock = $liorenStockMap[$liorenId] ?? null;

                if ($liorenStock === null) {
                    $notInLioren++;
                    continue;
                }

                $currentShopifyStock = intval($mapping->stock);

                // Solo actualizar si el stock es diferente
                if (intval($liorenStock) !== $currentShopifyStock) {
                    $inventoryItemId = $inventoryItemCache[$mapping->shopify_variant_id] ?? null;

                    if (!$inventoryItemId) {
                        $skipped++;
                        continue;
                    }

                    $result = $this->setInventoryLevel(
                        $config,
                        $locationId,
                        $inventoryItemId,
                        intval($liorenStock),
                        $mapping->product_title
                    );

                    if ($result) {
                        $mapping->update([
                            'stock' => $liorenStock,
                            'last_synced_at' => now(),
                        ]);
                        $updated++;
                    } else {
                        $errors++;
                    }
                } else {
                    $skipped++;
                }
            }

            $message = "Lioren→Shopify: {$updated} actualizados, {$skipped} sin cambios, {$notInLioren} sin Lioren, {$errors} errores";
            $this->info("  {$message}");

            if ($updated > 0 || $errors > 0) {
                SyncLog::logSuccess(
                    $config->user_id,
                    'cron',
                    'lioren_to_shopify',
                    'inventory',
                    'batch',
                    $message,
                    ['updated' => $updated, 'skipped' => $skipped, 'not_in_lioren' => $notInLioren, 'errors' => $errors]
                );
            }

        } catch (\Exception $e) {
            $this->error("  Error: " . $e->getMessage());
            Log::error("SyncLiorenToShopify error for user {$config->user_id}: " . $e->getMessage());

            SyncLog::logError(
                $config->user_id,
                'cron',
                'lioren_to_shopify',
                'inventory',
                'batch',
                $e->getMessage()
            );
        }
    }

    /**
     * Pre-cargar inventory_item_ids usando la API de productos en lotes
     * En vez de hacer 1 llamada por variante, obtenemos todos los productos con sus variantes
     */
    protected function preloadInventoryItemIds(IntegracionConfig $config, $mappings): array
    {
        $cache = [];
        $page_info = null;
        $url = "https://{$config->shopify_tienda}/admin/api/2024-10/products.json?limit=250&fields=id,variants";

        do {
            usleep(self::API_DELAY_MS * 1000);

            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $config->shopify_token,
                'Accept' => 'application/json',
            ])->timeout(30)->get($url);

            if (!$response->successful()) break;

            $products = $response->json()['products'] ?? [];
            foreach ($products as $product) {
                foreach ($product['variants'] ?? [] as $variant) {
                    $cache[$variant['id']] = $variant['inventory_item_id'] ?? null;
                }
            }

            // Check for pagination via Link header
            $linkHeader = $response->header('Link') ?? '';
            if (preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches)) {
                $url = $matches[1];
            } else {
                break;
            }
        } while (true);

        return $cache;
    }

    protected function getAllLiorenProducts(IntegracionConfig $config): array
    {
        $allProducts = [];
        $page = 1;

        do {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$config->lioren_api_key}",
                'Accept' => 'application/json',
            ])->timeout(30)->get("https://www.lioren.cl/api/productos?rpp=100&page={$page}");

            if (!$response->successful()) {
                Log::error("Error obteniendo productos Lioren page {$page}: " . $response->status());
                break;
            }

            $data = $response->json();
            if (empty($data) || !is_array($data)) break;

            $allProducts = array_merge($allProducts, $data);
            $page++;
        } while (count($data) >= 100 && $page <= 100);

        return $allProducts;
    }

    protected function getShopifyLocationId(IntegracionConfig $config, $mappings = null): ?int
    {
        // Method 1: Try locations API
        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $config->shopify_token,
                'Accept' => 'application/json',
            ])->timeout(15)->get("https://{$config->shopify_tienda}/admin/api/2024-10/locations.json");

            if ($response->successful()) {
                $locations = $response->json()['locations'] ?? [];
                foreach ($locations as $loc) {
                    if ($loc['active'] ?? false) {
                        return $loc['id'];
                    }
                }
                return $locations[0]['id'] ?? null;
            }
        } catch (\Exception $e) {
            Log::info("Locations API not available, trying fallback: " . $e->getMessage());
        }

        // Method 2: Fallback - get location from inventory_levels of first mapped product
        if ($mappings && $mappings->count() > 0) {
            try {
                $firstMapping = $mappings->first();
                $variantId = $firstMapping->shopify_variant_id;

                usleep(self::API_DELAY_MS * 1000);
                $variantResponse = Http::withHeaders([
                    'X-Shopify-Access-Token' => $config->shopify_token,
                    'Accept' => 'application/json',
                ])->timeout(15)->get(
                    "https://{$config->shopify_tienda}/admin/api/2024-10/variants/{$variantId}.json?fields=inventory_item_id"
                );

                if ($variantResponse->successful()) {
                    $inventoryItemId = $variantResponse->json()['variant']['inventory_item_id'] ?? null;
                    if ($inventoryItemId) {
                        usleep(self::API_DELAY_MS * 1000);
                        $response = Http::withHeaders([
                            'X-Shopify-Access-Token' => $config->shopify_token,
                            'Accept' => 'application/json',
                        ])->timeout(15)->get(
                            "https://{$config->shopify_tienda}/admin/api/2024-10/inventory_levels.json?inventory_item_ids={$inventoryItemId}"
                        );
                        if ($response->successful()) {
                            $levels = $response->json()['inventory_levels'] ?? [];
                            if (!empty($levels)) {
                                return $levels[0]['location_id'];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Fallback location detection failed: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Set inventory level in Shopify with rate limiting and retry
     */
    protected function setInventoryLevel(
        IntegracionConfig $config,
        int $locationId,
        int $inventoryItemId,
        int $newStock,
        string $productTitle
    ): bool {
        for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
            // Rate limiting
            usleep(self::API_DELAY_MS * 1000);

            try {
                $response = Http::withHeaders([
                    'X-Shopify-Access-Token' => $config->shopify_token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(15)->post(
                    "https://{$config->shopify_tienda}/admin/api/2024-10/inventory_levels/set.json",
                    [
                        'location_id' => $locationId,
                        'inventory_item_id' => $inventoryItemId,
                        'available' => $newStock,
                    ]
                );

                if ($response->successful()) {
                    return true;
                }

                // Rate limited - wait and retry
                if ($response->status() === 429) {
                    $retryAfter = floatval($response->header('Retry-After', 2));
                    Log::info("Rate limited, waiting {$retryAfter}s before retry for '{$productTitle}'");
                    sleep(max(1, intval(ceil($retryAfter))));
                    continue;
                }

                // Inventory tracking not enabled - skip silently
                $body = $response->body();
                if (str_contains($body, 'does not have inventory tracking enabled')) {
                    return false;
                }

                Log::error("Error setting Shopify inventory for '{$productTitle}': {$body}");
                return false;

            } catch (\Exception $e) {
                Log::error("Exception updating Shopify inventory for '{$productTitle}': " . $e->getMessage());
                if ($attempt < self::MAX_RETRIES) {
                    sleep(1);
                    continue;
                }
                return false;
            }
        }

        return false;
    }
}
