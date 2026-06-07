<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Boleta;
use App\Models\FacturaEmitida;
use App\Models\IntegracionConfig;

class RetryFailedEmissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dte:retry-failed {--max-attempts=3 : Máximo de reintentos por documento} {--limit=50 : Máximo de documentos a reintentar por ejecución}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reintentar emisión de boletas y facturas que fallaron en Lioren API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $maxAttempts = (int) $this->option('max-attempts');
        $limit = (int) $this->option('limit');
        
        Log::channel('single')->info('=== INICIO RETRY EMISIONES FALLIDAS ===', [
            'max_attempts' => $maxAttempts,
            'limit' => $limit,
        ]);
        
        $this->info("Buscando emisiones fallidas (máx {$maxAttempts} intentos, límite {$limit})...");
        
        $totalRetried = 0;
        $totalSuccess = 0;
        $totalFailed = 0;
        
        // ============================================
        // REINTENTAR BOLETAS FALLIDAS
        // ============================================
        $boletasFallidas = Boleta::where('status', 'error')
            ->where(function ($q) use ($maxAttempts) {
                $q->whereNull('retry_count')
                  ->orWhere('retry_count', '<', $maxAttempts);
            })
            ->where('created_at', '>=', now()->subDays(7)) // Solo últimos 7 días
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
        
        $this->info("Boletas fallidas encontradas: {$boletasFallidas->count()}");
        
        foreach ($boletasFallidas as $boleta) {
            $totalRetried++;
            $retryCount = ($boleta->retry_count ?? 0) + 1;
            
            $this->info("  Reintentando boleta #{$boleta->id} (order: {$boleta->shopify_order_id}, intento {$retryCount}/{$maxAttempts})...");
            
            try {
                // Obtener configuración del usuario
                $config = IntegracionConfig::where('user_id', $boleta->user_id)
                    ->where('activo', true)
                    ->first();
                
                if (!$config) {
                    $this->warn("    Sin configuración activa para user_id: {$boleta->user_id}");
                    $boleta->update(['retry_count' => $retryCount, 'last_retry_at' => now()]);
                    $totalFailed++;
                    continue;
                }
                
                // Verificar que no se haya emitido ya (por otro proceso)
                $existingSuccess = Boleta::where('shopify_order_id', $boleta->shopify_order_id)
                    ->where('user_id', $boleta->user_id)
                    ->where('status', 'emitida')
                    ->exists();
                
                if ($existingSuccess) {
                    $this->info("    Ya existe boleta emitida para este pedido. Marcando como duplicada.");
                    $boleta->update([
                        'status' => 'duplicada',
                        'retry_count' => $retryCount,
                        'last_retry_at' => now(),
                    ]);
                    continue;
                }
                
                // Reconstruir detalles desde los datos guardados
                $detalles = is_string($boleta->detalles) ? json_decode($boleta->detalles, true) : $boleta->detalles;
                
                if (empty($detalles) || !is_array($detalles)) {
                    $this->warn("    Sin detalles válidos para reintentar.");
                    $boleta->update([
                        'retry_count' => $retryCount,
                        'last_retry_at' => now(),
                        'error_message' => 'Sin detalles válidos para reintento',
                    ]);
                    $totalFailed++;
                    continue;
                }
                
                // Emisión vía LiorenService (punto ÚNICO de comunicación con Lioren).
                $result = app(\App\Services\LiorenService::class)->emitirBoleta(
                    $config->lioren_api_key,
                    $detalles,
                    [
                        'rut' => $boleta->receptor_rut ?? '66666666-6',
                        'rs' => $boleta->receptor_nombre ?? 'Cliente',
                    ]
                );

                if ($result['ok']) {
                    $folio = $result['folio'] ?? $result['data']['folio'] ?? null;
                    
                    $boleta->update([
                        'status' => 'emitida',
                        'folio' => $folio,
                        'retry_count' => $retryCount,
                        'last_retry_at' => now(),
                        'error_message' => null,
                    ]);
                    
                    $this->info("    ✅ Boleta emitida exitosamente. Folio: {$folio}");
                    $totalSuccess++;
                    
                    Log::channel('single')->info("RETRY: Boleta emitida exitosamente", [
                        'boleta_id' => $boleta->id,
                        'folio' => $folio,
                        'intento' => $retryCount,
                    ]);
                } else {
                    $boleta->update([
                        'retry_count' => $retryCount,
                        'last_retry_at' => now(),
                        'error_message' => 'Retry #' . $retryCount . ': ' . ($result['error'] ?? 'Error desconocido'),
                    ]);

                    // Si alcanzó el máximo de intentos, marcar como fallo permanente
                    if ($retryCount >= $maxAttempts) {
                        $boleta->update(['status' => 'error_permanente']);
                        $this->error("    ❌ Máximo de intentos alcanzado. Marcada como error permanente.");
                    } else {
                        $this->warn("    ⚠️ Falló intento {$retryCount}. Error: " . substr($result['error'] ?? '', 0, 100));
                    }
                    $totalFailed++;
                }
                
            } catch (\Exception $e) {
                $boleta->update([
                    'retry_count' => ($boleta->retry_count ?? 0) + 1,
                    'last_retry_at' => now(),
                    'error_message' => 'Retry exception: ' . $e->getMessage(),
                ]);
                $this->error("    ❌ Excepción: " . $e->getMessage());
                $totalFailed++;
            }
            
            // Backoff: esperar entre reintentos para no saturar la API
            sleep(2);
        }
        
        // ============================================
        // REINTENTAR FACTURAS FALLIDAS
        // ============================================
        $facturasFallidas = FacturaEmitida::where('status', 'error')
            ->where(function ($q) use ($maxAttempts) {
                $q->whereNull('retry_count')
                  ->orWhere('retry_count', '<', $maxAttempts);
            })
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
        
        $this->info("Facturas fallidas encontradas: {$facturasFallidas->count()}");
        
        foreach ($facturasFallidas as $factura) {
            $totalRetried++;
            $retryCount = ($factura->retry_count ?? 0) + 1;
            
            $this->info("  Reintentando factura #{$factura->id} (order: {$factura->shopify_order_id}, intento {$retryCount}/{$maxAttempts})...");
            
            try {
                $config = IntegracionConfig::where('user_id', $factura->user_id)
                    ->where('activo', true)
                    ->first();
                
                if (!$config) {
                    $this->warn("    Sin configuración activa para user_id: {$factura->user_id}");
                    $factura->update(['retry_count' => $retryCount, 'last_retry_at' => now()]);
                    $totalFailed++;
                    continue;
                }
                
                // Verificar que no se haya emitido ya
                $existingSuccess = FacturaEmitida::where('shopify_order_id', $factura->shopify_order_id)
                    ->where('user_id', $factura->user_id)
                    ->where('status', 'emitida')
                    ->exists();
                
                if ($existingSuccess) {
                    $this->info("    Ya existe factura emitida para este pedido. Marcando como duplicada.");
                    $factura->update([
                        'status' => 'duplicada',
                        'retry_count' => $retryCount,
                        'last_retry_at' => now(),
                    ]);
                    continue;
                }
                
                // Reconstruir detalles
                $detalles = is_string($factura->detalles) ? json_decode($factura->detalles, true) : $factura->detalles;
                
                if (empty($detalles) || !is_array($detalles)) {
                    $this->warn("    Sin detalles válidos para reintentar.");
                    $factura->update([
                        'retry_count' => $retryCount,
                        'last_retry_at' => now(),
                        'error_message' => 'Sin detalles válidos para reintento',
                    ]);
                    $totalFailed++;
                    continue;
                }
                
                // Receptor para la factura.
                $receptorRetry = [
                    'rut' => $factura->rut_receptor,
                    'rs' => $factura->razon_social,
                    'giro' => $factura->giro ?? 'Comercio en general',
                    'direccion' => $factura->direccion ?? 'Sin direccion especificada',
                    'comuna' => $factura->comuna_id ?? 295,
                    'ciudad' => $factura->ciudad_id ?? 209,
                ];
                if ($factura->receptor_email) {
                    $receptorRetry['email'] = $factura->receptor_email;
                }

                // Emisión vía LiorenService (punto ÚNICO de comunicación con Lioren).
                $result = app(\App\Services\LiorenService::class)->emitirFactura(
                    $config->lioren_api_key,
                    $detalles,
                    $receptorRetry
                );

                if ($result['ok']) {
                    $folio = $result['folio'] ?? $result['data']['folio'] ?? null;
                    
                    $factura->update([
                        'status' => 'emitida',
                        'folio' => $folio,
                        'retry_count' => $retryCount,
                        'last_retry_at' => now(),
                        'error_message' => null,
                    ]);
                    
                    $this->info("    ✅ Factura emitida exitosamente. Folio: {$folio}");
                    $totalSuccess++;
                    
                    Log::channel('single')->info("RETRY: Factura emitida exitosamente", [
                        'factura_id' => $factura->id,
                        'folio' => $folio,
                        'intento' => $retryCount,
                    ]);
                } else {
                    $factura->update([
                        'retry_count' => $retryCount,
                        'last_retry_at' => now(),
                        'error_message' => 'Retry #' . $retryCount . ': ' . ($result['error'] ?? 'Error desconocido'),
                    ]);

                    if ($retryCount >= $maxAttempts) {
                        $factura->update(['status' => 'error_permanente']);
                        $this->error("    ❌ Máximo de intentos alcanzado. Marcada como error permanente.");
                    } else {
                        $this->warn("    ⚠️ Falló intento {$retryCount}. Error: " . substr($result['error'] ?? '', 0, 100));
                    }
                    $totalFailed++;
                }
                
            } catch (\Exception $e) {
                $factura->update([
                    'retry_count' => ($factura->retry_count ?? 0) + 1,
                    'last_retry_at' => now(),
                    'error_message' => 'Retry exception: ' . $e->getMessage(),
                ]);
                $this->error("    ❌ Excepción: " . $e->getMessage());
                $totalFailed++;
            }
            
            sleep(2);
        }
        
        // Resumen
        $this->info('');
        $this->info("=== RESUMEN RETRY ===");
        $this->info("Total reintentados: {$totalRetried}");
        $this->info("Exitosos: {$totalSuccess}");
        $this->info("Fallidos: {$totalFailed}");
        
        Log::channel('single')->info('=== FIN RETRY EMISIONES FALLIDAS ===', [
            'total_retried' => $totalRetried,
            'total_success' => $totalSuccess,
            'total_failed' => $totalFailed,
        ]);
        
        return 0;
    }
}
