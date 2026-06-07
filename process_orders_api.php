<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\IntegracionConfig;
use Illuminate\Support\Facades\Http;

$config = IntegracionConfig::where('user_id', 8)->first();
if (!$config) {
    echo "Configuración no encontrada para el usuario 8.\n";
    exit;
}

$tienda = $config->shopify_tienda;
$token = decrypt($config->shopify_token);

$orders = ['2875', '2873', '2871', '2870', '2869', '2868', '2867'];

foreach ($orders as $orderNumber) {
    echo "Obteniendo pedido #$orderNumber de Shopify...\n";
    
    $response = Http::withHeaders([
        'X-Shopify-Access-Token' => $token,
        'Content-Type' => 'application/json',
    ])->get("https://{$tienda}/admin/api/2024-01/orders.json?name=$orderNumber&status=any");
    
    if ($response->successful() && isset($response->json()['orders'][0])) {
        $order = $response->json()['orders'][0];
        
        echo "Enviando webhook simulado para pedido #$orderNumber...\n";
        
        // Simular webhook
        $webhookResponse = Http::withHeaders([
            'X-Shopify-Topic' => 'orders/create',
            'X-Shopify-Shop-Domain' => $tienda,
            'Content-Type' => 'application/json',
        ])->post('https://integration-conector.bigstudio.cl/api/shopify/webhook', $order);
        
        echo "Respuesta webhook: " . $webhookResponse->status() . "\n";
    } else {
        echo "No se pudo obtener el pedido #$orderNumber de Shopify.\n";
    }
}
echo "Proceso finalizado.\n";
