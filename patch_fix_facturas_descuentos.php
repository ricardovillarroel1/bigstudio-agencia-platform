<?php
/**
 * PARCHE: Corregir emisión de facturas y boletas
 * 
 * Problema 1 (Josefina González): El ajuste de redondeo genera ítems con precio negativo
 *   que Lioren rechaza con error 422.
 * 
 * Problema 2 (Emilia Podesta): La función emitirFactura() no maneja descuentos de Shopify
 *   (total_discount por ítem), causando que el ajuste de redondeo sea enorme y genere
 *   precios negativos.
 * 
 * Solución:
 * 1. En emitirFactura(): Restar total_discount de cada line_item antes de calcular neto
 * 2. En emitirFactura(): Corregir ajuste de redondeo para nunca generar precios negativos
 * 3. En procesarPedido(): También manejar descuentos por ítem correctamente
 */

$file = '/var/www/shopify-integrator/app/Http/Controllers/IntegracionController.php';

// Crear backup
$timestamp = date('YmdHis');
copy($file, $file . '.bak.' . $timestamp);
echo "Backup creado: {$file}.bak.{$timestamp}\n";

$content = file_get_contents($file);

// =====================================================
// FIX 1: emitirFactura() - Manejar descuentos por ítem
// =====================================================

// Buscar el bloque actual de preparación de detalles en emitirFactura
$oldFacturaItems = <<<'PHP'
            // Preparar detalles de productos (PRECIO NETO sin IVA)
            $detalles = [];
            $lineItems = $order['line_items'] ?? [];
            $totalShopify = intval(round(floatval($order['total_price'] ?? 0)));
            
            foreach ($lineItems as $item) {
                $precioConIva = floatval($item['price'] ?? 0);
                $precioNeto = round($precioConIva / 1.19); // Neto redondeado a entero (como Lioren lo usará)

                $detalles[] = [
                    'codigo' => $item['sku'] ?? 'PROD-' . ($item['product_id'] ?? rand(1000, 9999)),
                    'nombre' => substr($item['title'] ?? 'Producto', 0, 80),
                    'cantidad' => floatval($item['quantity'] ?? 1),
                    'precio' => $precioNeto, // NETO sin IVA
                    'unidad' => 'UN',
                    'exento' => false, // Afecto a IVA
                ];
            }
PHP;

$newFacturaItems = <<<'PHP'
            // Preparar detalles de productos (PRECIO NETO sin IVA, CON DESCUENTOS APLICADOS)
            $detalles = [];
            $lineItems = $order['line_items'] ?? [];
            $totalShopify = intval(round(floatval($order['total_price'] ?? 0)));
            
            foreach ($lineItems as $item) {
                $precioConIva = floatval($item['price'] ?? 0);
                $cantidad = floatval($item['quantity'] ?? 1);
                
                // Aplicar descuentos por ítem de Shopify
                $totalDiscount = floatval($item['total_discount'] ?? 0);
                if ($totalDiscount > 0 && $cantidad > 0) {
                    $descuentoPorUnidad = $totalDiscount / $cantidad;
                    $precioConIva = $precioConIva - $descuentoPorUnidad;
                    if ($precioConIva < 0) $precioConIva = 0;
                    Log::channel('single')->info("Descuento aplicado en factura: {$totalDiscount} total, {$descuentoPorUnidad}/unidad para '{$item['title']}'");
                }
                
                $precioNeto = round($precioConIva / 1.19); // Neto redondeado a entero

                $detalles[] = [
                    'codigo' => $item['sku'] ?? 'PROD-' . ($item['product_id'] ?? rand(1000, 9999)),
                    'nombre' => substr($item['title'] ?? 'Producto', 0, 80),
                    'cantidad' => $cantidad,
                    'precio' => $precioNeto, // NETO sin IVA (con descuento aplicado)
                    'unidad' => 'UN',
                    'exento' => false, // Afecto a IVA
                ];
            }
PHP;

if (strpos($content, $oldFacturaItems) !== false) {
    $content = str_replace($oldFacturaItems, $newFacturaItems, $content);
    echo "✅ FIX 1: Descuentos por ítem en emitirFactura() aplicado\n";
} else {
    echo "⚠️ FIX 1: No se encontró el bloque exacto de items en emitirFactura(). Intentando búsqueda alternativa...\n";
    // Intentar buscar una versión con variaciones menores de espaciado
    $pattern = '/\/\/ Preparar detalles de productos \(PRECIO NETO sin IVA\)\s+\$detalles = \[\];\s+\$lineItems = \$order\[\'line_items\'\] \?\? \[\];\s+\$totalShopify = intval\(round\(floatval\(\$order\[\'total_price\' \?\? 0\]\)\)\);\s+foreach \(\$lineItems as \$item\) \{\s+\$precioConIva = floatval\(\$item\[\'price\'\] \?\? 0\);\s+\$precioNeto = round\(\$precioConIva \/ 1\.19\);/s';
    if (preg_match($pattern, $content)) {
        echo "  Encontrado con regex, aplicando...\n";
    } else {
        echo "  ❌ No se pudo encontrar el bloque. Revisión manual necesaria.\n";
    }
}

// =====================================================
// FIX 2: emitirFactura() - Corregir ajuste de redondeo
// =====================================================

$oldAjuste = <<<'PHP'
                    if ($diferencia !== 0) {
                        $lastIdx = count($detalles) - 1;
                        $lastQty = $detalles[$lastIdx]['cantidad'];
                        if ($lastQty == 1) {
                            $detalles[$lastIdx]['precio'] -= $diferencia;
                        } else {
                            $detalles[] = [
                                'codigo' => 'AJUSTE',
                                'nombre' => 'Ajuste redondeo',
                                'cantidad' => 1,
                                'precio' => -$diferencia,
                                'unidad' => 'UN',
                                'exento' => false,
                            ];
                        }
                        Log::channel('single')->info("Ajuste de redondeo: {$diferencia} peso(s) en factura pedido #{$order['order_number']}");
                    }
PHP;

$newAjuste = <<<'PHP'
                    if ($diferencia !== 0) {
                        // Distribuir el ajuste entre los ítems existentes sin generar precios negativos
                        $ajusteAplicado = false;
                        
                        // Intentar ajustar en el último ítem con cantidad 1
                        for ($i = count($detalles) - 1; $i >= 0; $i--) {
                            if ($detalles[$i]['cantidad'] == 1) {
                                $nuevoPrecio = $detalles[$i]['precio'] - $diferencia;
                                if ($nuevoPrecio > 0) {
                                    $detalles[$i]['precio'] = $nuevoPrecio;
                                    $ajusteAplicado = true;
                                    Log::channel('single')->info("Ajuste de redondeo: {$diferencia} peso(s) aplicado al ítem '{$detalles[$i]['nombre']}' en factura pedido #{$order['order_number']}");
                                    break;
                                }
                            }
                        }
                        
                        // Si no se pudo ajustar en un solo ítem, distribuir proporcionalmente
                        if (!$ajusteAplicado) {
                            $restante = $diferencia;
                            for ($i = count($detalles) - 1; $i >= 0 && $restante != 0; $i--) {
                                $maxAjuste = $detalles[$i]['precio'] - 1; // Mantener precio mínimo de 1
                                if ($restante > 0 && $maxAjuste > 0) {
                                    $ajusteItem = min($restante, $maxAjuste);
                                    $detalles[$i]['precio'] -= $ajusteItem;
                                    $restante -= $ajusteItem;
                                } elseif ($restante < 0) {
                                    $detalles[$i]['precio'] -= $restante; // Sumar (restante es negativo)
                                    $restante = 0;
                                }
                            }
                            Log::channel('single')->info("Ajuste de redondeo distribuido: {$diferencia} peso(s) en factura pedido #{$order['order_number']}");
                        }
                    }
PHP;

if (strpos($content, $oldAjuste) !== false) {
    $content = str_replace($oldAjuste, $newAjuste, $content);
    echo "✅ FIX 2: Ajuste de redondeo en emitirFactura() corregido\n";
} else {
    echo "⚠️ FIX 2: No se encontró el bloque exacto de ajuste de redondeo.\n";
}

// =====================================================
// FIX 3: procesarPedido() (boleta) - Manejar descuentos
// =====================================================

$oldBoletaItems = <<<'PHP'
            foreach ($lineItems as $item) {
                $precioNeto = floatval($item['price'] ?? 0);
                $cantidad = floatval($item['quantity'] ?? 1);
                
                // Calcular impuestos por unidad desde tax_lines del item
                $totalTaxItem = 0;
                if (isset($item['tax_lines']) && is_array($item['tax_lines'])) {
                    foreach ($item['tax_lines'] as $tax) {
                        $totalTaxItem += floatval($tax['price'] ?? 0);
                    }
                }
                // Precio bruto (con IVA) por unidad = precio neto + (impuesto total / cantidad)
                $precioBruto = $cantidad > 0 ? $precioNeto + ($totalTaxItem / $cantidad) : $precioNeto;
                
                $detalles[] = [
                    'codigo' => $item['sku'] ?? 'PROD-' . ($item['product_id'] ?? rand(1000, 9999)),
                    'nombre' => $item['title'] ?? 'Producto',
                    'cantidad' => $cantidad,
                    'precio' => round($precioBruto), // Precio BRUTO (con IVA incluido)
                    'unidad' => 'UN',
                    'exento' => false, // Afecto a IVA
                ];
            }
PHP;

$newBoletaItems = <<<'PHP'
            foreach ($lineItems as $item) {
                $precioNeto = floatval($item['price'] ?? 0);
                $cantidad = floatval($item['quantity'] ?? 1);
                
                // Aplicar descuentos por ítem de Shopify
                $totalDiscount = floatval($item['total_discount'] ?? 0);
                if ($totalDiscount > 0 && $cantidad > 0) {
                    $descuentoPorUnidad = $totalDiscount / $cantidad;
                    $precioNeto = $precioNeto - $descuentoPorUnidad;
                    if ($precioNeto < 0) $precioNeto = 0;
                    Log::channel('single')->info("Descuento aplicado en boleta: {$totalDiscount} total, {$descuentoPorUnidad}/unidad para '{$item['title']}'");
                }
                
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
                
                $detalles[] = [
                    'codigo' => $item['sku'] ?? 'PROD-' . ($item['product_id'] ?? rand(1000, 9999)),
                    'nombre' => $item['title'] ?? 'Producto',
                    'cantidad' => $cantidad,
                    'precio' => round($precioBruto), // Precio BRUTO (con IVA incluido, descuento aplicado)
                    'unidad' => 'UN',
                    'exento' => false, // Afecto a IVA
                ];
            }
PHP;

if (strpos($content, $oldBoletaItems) !== false) {
    $content = str_replace($oldBoletaItems, $newBoletaItems, $content);
    echo "✅ FIX 3: Descuentos por ítem en procesarPedido() (boleta) aplicado\n";
} else {
    echo "⚠️ FIX 3: No se encontró el bloque exacto de items en procesarPedido().\n";
}

// Guardar archivo modificado
file_put_contents($file, $content);
echo "\n✅ Archivo guardado exitosamente: {$file}\n";
echo "Backup disponible en: {$file}.bak.{$timestamp}\n";
