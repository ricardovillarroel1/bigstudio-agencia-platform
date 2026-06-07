<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PLANES EN LA BASE DE DATOS ===\n\n";

$planes = DB::table('planes')->get();

if ($planes->count() === 0) {
    echo "❌ No hay planes registrados\n";
} else {
    echo "Total: {$planes->count()}\n\n";
    foreach ($planes as $plan) {
        echo "ID: {$plan->id}\n";
        echo "Nombre: {$plan->nombre}\n";
        echo "Precio: \${$plan->precio} USD\n";
        echo "Empresa ID: {$plan->empresa_id}\n";
        echo "Activo: " . ($plan->activo ? 'SÍ' : 'NO') . "\n";
        echo "---\n";
    }
}

echo "\n=== ANÁLISIS DE CONVERSIÓN ===\n\n";
echo "Si un plan cuesta \$50 USD:\n";
echo "Conversión a CLP: \$50 * 800 = " . (50 * 800) . " CLP\n\n";

echo "Si ves 40,000 CLP en el pago:\n";
echo "Precio original en USD: 40,000 / 800 = \$" . (40000 / 800) . " USD\n";
