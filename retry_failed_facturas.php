<?php
/**
 * Script para re-emitir facturas que fallaron por error de precio negativo.
 * Solo re-intenta las que fallaron por "detalles.X.precio" (precio fuera de rango).
 * 
 * Primero elimina el registro de error para que la protección anti-duplicados no bloquee,
 * luego obtiene el pedido de Shopify y lo reprocesa con la lógica corregida.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\FacturaEmitida;
use App\Models\IntegracionConfig;

echo "=== Re-emitiendo facturas con error de precio ===\n\n";

// Obtener facturas con error de precio
$facturasError = FacturaEmitida::where('status', 'error')
    ->where('error_message', 'like', '%detalles%precio%')
    ->get();

echo "Facturas con error de precio encontradas: " . $facturasError->count() . "\n\n";

foreach ($facturasError as $factura) {
    echo "--- Procesando Factura ID {$factura->id} ---\n";
    echo "  Pedido Shopify: #{$factura->shopify_order_number} (ID: {$factura->shopify_order_id})\n";
    echo "  RUT: {$factura->rut_receptor}\n";
    echo "  Razón Social: {$factura->razon_social}\n";
    
    // Determinar a qué usuario pertenece esta factura
    // Buscar por shopify_order_id en las configuraciones
    $config = null;
    $configs = IntegracionConfig::where('activo', true)->get();
    
    foreach ($configs as $c) {
        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $c->shopify_token,
            ])->timeout(10)->get("https://{$c->shopify_tienda}/admin/api/2024-01/orders/{$factura->shopify_order_id}.json");
            
            if ($response->successful()) {
                $config = $c;
                $order = $response->json()['order'];
                echo "  Tienda: {$c->shopify_tienda} (user_id: {$c->user_id})\n";
                break;
            }
        } catch (\Exception $e) {
            continue;
        }
    }
    
    if (!$config || !isset($order)) {
        echo "  ❌ No se pudo obtener el pedido de Shopify. Saltando.\n\n";
        continue;
    }
    
    // Eliminar el registro de error para que la protección anti-duplicados no bloquee
    echo "  Eliminando registro de error ID {$factura->id}...\n";
    $factura->delete();
    
    // Re-procesar el pedido usando el controlador
    try {
        $controller = app(\App\Http\Controllers\IntegracionController::class);
        
        // Llamar al método procesarPedidoConFacturacion via reflection
        $method = new ReflectionMethod($controller, 'procesarPedidoConFacturacion');
        $method->setAccessible(true);
        $method->invoke($controller, $order, $config->lioren_api_key, $config);
        
        echo "  ✅ Pedido reprocesado exitosamente\n\n";
    } catch (\Exception $e) {
        echo "  ❌ Error al reprocesar: " . $e->getMessage() . "\n\n";
    }
}

echo "=== Proceso completado ===\n";
