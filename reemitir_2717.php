<?php
// Script para re-emitir la boleta del pedido #2717
// Obtener datos del pedido desde Shopify y emitir boleta en Lioren

require_once '/var/www/shopify-integrator/vendor/autoload.php';

$app = require_once '/var/www/shopify-integrator/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

$boletaId = 342;
$boleta = DB::table('boletas')->where('id', $boletaId)->first();

if (!$boleta) {
    echo "❌ Boleta no encontrada\n";
    exit;
}

echo "📋 Boleta encontrada: ID {$boleta->id}, Folio: {$boleta->folio}, Status: {$boleta->status}\n";

// Obtener config del usuario
$config = DB::table('integracion_configs')->where('user_id', $boleta->user_id)->first();
$shopifyToken = unserialize(Crypt::decryptString($config->shopify_token));
$liorenApiKey = unserialize(Crypt::decryptString($config->lioren_api_key));
$shopDomain = $config->shopify_tienda;

echo "🏪 Shop: {$shopDomain}\n";

// Obtener datos del pedido desde Shopify
$shopifyOrderId = $boleta->shopify_order_id;
$response = Http::withHeaders([
    'X-Shopify-Access-Token' => $shopifyToken,
    'Content-Type' => 'application/json'
])->get("https://{$shopDomain}/admin/api/2024-01/orders/{$shopifyOrderId}.json");

if (!$response->successful()) {
    echo "❌ Error al obtener pedido de Shopify: " . $response->status() . "\n";
    exit;
}

$order = $response->json()['order'];
echo "📦 Pedido #{$order['order_number']} - Total: \${$order['total_price']}\n";

// Construir detalles de la boleta con los productos actuales
$detalles = [];
foreach ($order['line_items'] as $item) {
    $precioConIva = round($item['price']);
    $detalles[] = [
        'nombre' => $item['name'],
        'cantidad' => $item['quantity'],
        'precio' => $precioConIva,
        'exento' => false
    ];
}

// Agregar envío si tiene costo
if (isset($order['shipping_lines']) && count($order['shipping_lines']) > 0) {
    foreach ($order['shipping_lines'] as $shipping) {
        $shippingPrice = round($shipping['price']);
        if ($shippingPrice > 0) {
            $detalles[] = [
                'nombre' => 'Envío: ' . ($shipping['title'] ?? 'Envío'),
                'cantidad' => 1,
                'precio' => $shippingPrice,
                'exento' => false
            ];
        }
    }
}

echo "📝 Detalles:\n";
foreach ($detalles as $d) {
    echo "   - {$d['nombre']} x{$d['cantidad']} = \${$d['precio']}\n";
}

// Preparar data para Lioren (boleta - tipodoc 39)
$data = [
    'emisor' => [
        'tipodoc' => '39'
    ],
    'receptor' => [
        'nombre' => $boleta->receptor_nombre,
        'email' => $boleta->receptor_email
    ],
    'detalles' => $detalles
];

// Si tiene RUT, agregarlo
if ($boleta->receptor_rut) {
    $data['receptor']['rut'] = $boleta->receptor_rut;
}

echo "\n🚀 Enviando a Lioren...\n";
echo "Data: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

// Enviar a Lioren
$liorenResponse = Http::withHeaders([
    'Authorization' => 'Bearer ' . $liorenApiKey,
    'Content-Type' => 'application/json'
])->post('https://www.lioren.cl/api/boletas', $data);

if (!$liorenResponse->successful()) {
    echo "❌ Error de Lioren: " . $liorenResponse->status() . " - " . $liorenResponse->body() . "\n";
    exit;
}

$liorenData = $liorenResponse->json();
echo "✅ Boleta emitida en Lioren!\n";
echo "   Folio: " . ($liorenData['folio'] ?? 'N/A') . "\n";
echo "   ID: " . ($liorenData['id'] ?? 'N/A') . "\n";

// Actualizar BD
$updateData = [
    'status' => 'emitida',
    'folio' => $liorenData['folio'] ?? $boleta->folio,
    'lioren_id' => $liorenData['id'] ?? null,
    'detalles' => json_encode($detalles),
    'error_message' => null,
    'updated_at' => now()
];

// Guardar PDF y XML si están disponibles
if (isset($liorenData['pdf_url'])) {
    $pdfContent = Http::get($liorenData['pdf_url'])->body();
    $updateData['pdf_base64'] = base64_encode($pdfContent);
}
if (isset($liorenData['xml_url'])) {
    $xmlContent = Http::get($liorenData['xml_url'])->body();
    $updateData['xml_base64'] = base64_encode($xmlContent);
}

DB::table('boletas')->where('id', $boletaId)->update($updateData);
echo "✅ BD actualizada\n";

// Actualizar nota en Shopify
$newFolio = $liorenData['folio'] ?? $boleta->folio;
$noteContent = "Boleta Lioren #{$newFolio}";

// Obtener notas actuales del pedido
$currentNote = $order['note'] ?? '';
if (strpos($currentNote, 'Boleta Lioren') !== false) {
    // Reemplazar la nota existente
    $currentNote = preg_replace('/Boleta Lioren #\d+/', "Boleta Lioren #{$newFolio}", $currentNote);
} else {
    $currentNote = $currentNote ? $currentNote . "\n" . $noteContent : $noteContent;
}

$updateOrder = Http::withHeaders([
    'X-Shopify-Access-Token' => $shopifyToken,
    'Content-Type' => 'application/json'
])->put("https://{$shopDomain}/admin/api/2024-01/orders/{$shopifyOrderId}.json", [
    'order' => [
        'id' => $shopifyOrderId,
        'note' => $currentNote,
        'tags' => $order['tags'] . ",Boleta-Lioren-#{$newFolio}"
    ]
]);

if ($updateOrder->successful()) {
    echo "✅ Shopify actualizado con nota y tag\n";
} else {
    echo "⚠️ Error actualizando Shopify: " . $updateOrder->status() . "\n";
}

echo "\n🎉 ¡Re-emisión completada exitosamente!\n";
