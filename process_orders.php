<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\IntegracionConfig;
use App\Http\Controllers\IntegracionController;
use Illuminate\Support\Facades\Http;

$config = IntegracionConfig::where('user_id', 8)->first();
if (!$config) {
    echo "Configuración no encontrada para el usuario 8.\n";
    exit;
}

$tienda = $config->shopify_tienda;
$token = decrypt($config->shopify_token);
$api_key = decrypt($config->lioren_api_key);

$controller = new IntegracionController();
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('procesarPedidoConFacturacion');
$method->setAccessible(true);

$orders = ['2875', '2873', '2871', '2870', '2869', '2868', '2867'];

foreach ($orders as $orderNumber) {
    echo "Procesando pedido #$orderNumber...\n";
    
    $response = Http::withHeaders([
        'X-Shopify-Access-Token' => $token,
        'Content-Type' => 'application/json',
    ])->get("https://{$tienda}/admin/api/2024-01/orders.json?name=$orderNumber&status=any");
    
    if ($response->successful() && isset($response->json()['orders'][0])) {
        $order = $response->json()['orders'][0];
        try {
            $method->invoke($controller, $order, $api_key, $config);
            echo "Pedido #$orderNumber procesado.\n";
        } catch (\Exception $e) {
            echo "Error al procesar pedido #$orderNumber: " . $e->getMessage() . "\n";
        }
    } else {
        echo "No se pudo obtener el pedido #$orderNumber de Shopify.\n";
    }
}
echo "Proceso finalizado.\n";
