<?php
/**
 * Fix: Corregir la función obtenerIdsLocalizacion para:
 * 1. Normalizar acentos de la respuesta de Lioren (Quilpué → quilpue)
 * 2. Usar el ID correcto de Santiago (295) como fallback, no 13101
 * 3. Usar region_id como ciudadId ya que Lioren no devuelve ciudad_id
 * 4. Agregar búsqueda fuzzy como fallback
 */

$file = '/var/www/shopify-integrator/app/Http/Controllers/IntegracionController.php';
$content = file_get_contents($file);

// ============================================================
// FIX 1: Reemplazar la función obtenerIdsLocalizacion completa
// ============================================================

$oldFunction = <<<'OLD'
     * Obtener IDs de localización (comuna y ciudad) desde Lioren
     */
    private function obtenerIdsLocalizacion($nombreCiudad, $api_key)
    {
        try {
            // Normalizar nombre de ciudad
            $nombreNormalizado = $this->normalizarNombreCiudad($nombreCiudad);
            Log::channel('single')->info("Buscando localización para: {$nombreCiudad} (normalizado: {$nombreNormalizado})");
            // Obtener localidades desde Lioren (endpoint correcto es /comunas)
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$api_key}",
                'Accept' => 'application/json',
            ])->timeout(10)->get('https://www.lioren.cl/api/comunas');
            if (!$response->successful()) {
                Log::channel('single')->error('Error al obtener comunas de Lioren: ' . $response->status());
                return null;
            }
            $localidades = $response->json();
            
            // Si la respuesta tiene una clave 'comunas', usarla
            if (isset($localidades['comunas'])) {
                $localidades = $localidades['comunas'];
            }
            // Buscar coincidencia
            foreach ($localidades as $localidad) {
                $nombreLocalidad = strtolower($localidad['nombre'] ?? '');
                
                if (strpos($nombreLocalidad, $nombreNormalizado) !== false || 
                    strpos($nombreNormalizado, $nombreLocalidad) !== false) {
                    
                    Log::channel('single')->info("✅ Localización encontrada", [
                        'nombre' => $localidad['nombre'],
                        'comunaId' => $localidad['id'],
                        'ciudadId' => $localidad['ciudad_id'] ?? $localidad['ciudadid'] ?? null,
                    ]);
                    return [
                        'comunaId' => $localidad['id'],
                        'ciudadId' => $localidad['ciudad_id'] ?? $localidad['ciudadid'] ?? 131, // Santiago por defecto
                    ];
                }
            }
            Log::channel('single')->warning("No se encontró localización para: {$nombreCiudad}");
            return null;
        } catch (\Exception $e) {
            Log::channel('single')->error('Error obteniendo localización: ' . $e->getMessage());
            return null;
        }
    }
OLD;

$newFunction = <<<'NEW'
     * Obtener IDs de localización (comuna y ciudad) desde Lioren
     * Normaliza acentos para búsqueda robusta y usa region_id como ciudadId
     */
    private function obtenerIdsLocalizacion($nombreCiudad, $api_key)
    {
        try {
            // Normalizar nombre de ciudad (quitar acentos y minúsculas)
            $nombreNormalizado = $this->normalizarNombreCiudad($nombreCiudad);
            $nombreSinAcentos = $this->quitarAcentos($nombreNormalizado);
            Log::channel('single')->info("Buscando localización para: {$nombreCiudad} (normalizado: {$nombreSinAcentos})");

            // Obtener comunas desde Lioren con cache de 24h
            $localidades = \Cache::remember('lioren_comunas_full_' . md5($api_key), 86400, function () use ($api_key) {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$api_key}",
                    'Accept' => 'application/json',
                ])->timeout(15)->get('https://www.lioren.cl/api/comunas');
                if (!$response->successful()) {
                    Log::channel('single')->error('Error al obtener comunas de Lioren: ' . $response->status());
                    return null;
                }
                $data = $response->json();
                // Si la respuesta tiene una clave 'comunas', usarla
                if (isset($data['comunas'])) {
                    $data = $data['comunas'];
                }
                return $data;
            });

            if (empty($localidades)) {
                Log::channel('single')->error('No se pudieron obtener comunas de Lioren');
                return null;
            }

            // PASO 1: Búsqueda exacta (sin acentos)
            foreach ($localidades as $localidad) {
                $nombreLocalidad = $this->quitarAcentos(strtolower($localidad['nombre'] ?? ''));
                
                if ($nombreLocalidad === $nombreSinAcentos) {
                    Log::channel('single')->info("Localización encontrada (exacta)", [
                        'nombre' => $localidad['nombre'],
                        'comunaId' => $localidad['id'],
                        'regionId' => $localidad['region_id'] ?? null,
                    ]);
                    return [
                        'comunaId' => $localidad['id'],
                        'ciudadId' => $localidad['ciudad_id'] ?? $localidad['ciudadid'] ?? $localidad['region_id'] ?? 15,
                    ];
                }
            }

            // PASO 2: Búsqueda parcial (contiene)
            foreach ($localidades as $localidad) {
                $nombreLocalidad = $this->quitarAcentos(strtolower($localidad['nombre'] ?? ''));
                
                if (strlen($nombreSinAcentos) >= 3 && (
                    strpos($nombreLocalidad, $nombreSinAcentos) !== false || 
                    strpos($nombreSinAcentos, $nombreLocalidad) !== false
                )) {
                    Log::channel('single')->info("Localización encontrada (parcial)", [
                        'nombre' => $localidad['nombre'],
                        'comunaId' => $localidad['id'],
                    ]);
                    return [
                        'comunaId' => $localidad['id'],
                        'ciudadId' => $localidad['ciudad_id'] ?? $localidad['ciudadid'] ?? $localidad['region_id'] ?? 15,
                    ];
                }
            }

            // PASO 3: Búsqueda fuzzy (similitud > 80%)
            $mejorMatch = null;
            $mejorSimilitud = 0;
            foreach ($localidades as $localidad) {
                $nombreLocalidad = $this->quitarAcentos(strtolower($localidad['nombre'] ?? ''));
                similar_text($nombreSinAcentos, $nombreLocalidad, $similitud);
                if ($similitud > 80 && $similitud > $mejorSimilitud) {
                    $mejorSimilitud = $similitud;
                    $mejorMatch = $localidad;
                }
            }
            if ($mejorMatch) {
                Log::channel('single')->info("Localización encontrada (fuzzy {$mejorSimilitud}%)", [
                    'nombre' => $mejorMatch['nombre'],
                    'comunaId' => $mejorMatch['id'],
                ]);
                return [
                    'comunaId' => $mejorMatch['id'],
                    'ciudadId' => $mejorMatch['ciudad_id'] ?? $mejorMatch['ciudadid'] ?? $mejorMatch['region_id'] ?? 15,
                ];
            }

            Log::channel('single')->warning("No se encontró localización para: {$nombreCiudad}");
            return null;
        } catch (\Exception $e) {
            Log::channel('single')->error('Error obteniendo localización: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Quitar acentos de un string para comparación robusta
     */
    private function quitarAcentos($string)
    {
        $acentos = ['á','é','í','ó','ú','ñ','ü','Á','É','Í','Ó','Ú','Ñ','Ü'];
        $sinAcentos = ['a','e','i','o','u','n','u','a','e','i','o','u','n','u'];
        return str_replace($acentos, $sinAcentos, $string);
    }
NEW;

if (strpos($content, $oldFunction) !== false) {
    $content = str_replace($oldFunction, $newFunction, $content);
    echo "1. OK - Función obtenerIdsLocalizacion reemplazada con normalización de acentos y búsqueda fuzzy\n";
} else {
    echo "1. ERROR - No se encontró la función exacta\n";
    // Intentar buscar por partes
    $pos = strpos($content, 'private function obtenerIdsLocalizacion');
    if ($pos !== false) {
        echo "   Función encontrada en posición: $pos\n";
    }
}

// ============================================================
// FIX 2: Corregir el fallback de Santiago (13101 → 295)
// ============================================================

// Fallback en emitirFactura
$old2 = "\$localizacion = ['comunaId' => 13101, 'ciudadId' => 131];";
$new2 = "\$localizacion = ['comunaId' => 295, 'ciudadId' => 15]; // Santiago (id=295 en Lioren, region_id=15)";
$count2 = substr_count($content, $old2);
$content = str_replace($old2, $new2, $content);
echo "2. OK - Fallback Santiago corregido: 13101→295, 131→15 ({$count2} ocurrencias)\n";

// Fallback en procesarPedido (boleta)
$old3 = "'comuna' => 13101,";
$new3 = "'comuna' => 295, // Santiago (id=295 en Lioren)";
$count3 = substr_count($content, $old3);
$content = str_replace($old3, $new3, $content);
echo "3. OK - Fallback comuna en boletas corregido ({$count3} ocurrencias)\n";

// Fallback ciudadId 131 → 15
$old4 = "'ciudadId' => 131";
$new4 = "'ciudadId' => 15";
$count4 = substr_count($content, $old4);
$content = str_replace($old4, $new4, $content);
echo "4. OK - Fallback ciudadId 131→15 ({$count4} ocurrencias)\n";

// Fallback ciudad => 131
$old5a = "'ciudad' => \$localizacion['ciudadId'] ?? 131,";
$new5a = "'ciudad' => \$localizacion['ciudadId'] ?? 15,";
$count5 = substr_count($content, $old5a);
$content = str_replace($old5a, $new5a, $content);
echo "5. OK - Fallback ciudad en receptor corregido ({$count5} ocurrencias)\n";

file_put_contents($file, $content);
echo "\n=== CORRECCIONES DE COMUNAS APLICADAS ===\n";
