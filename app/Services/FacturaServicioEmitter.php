<?php

namespace App\Services;

use App\Models\FacturaServicio;
use App\Models\Cliente;
use App\Models\IntegracionConfig;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FacturaServicioEmitter
{
    /**
     * Crear factura de servicio y emitir DTE via Lioren API
     * Se usa en todos los flujos: Flow, transferencia aprobada, manual admin
     */
    public static function crearYEmitir(
        int $userId,
        int $planId,
        int $suscripcionId,
        ?int $paymentId = null,
        string $concepto = '',
        string $periodo = 'mensual',
        ?string $periodoInicio = null,
        ?string $periodoFin = null
    ): ?FacturaServicio {
        try {
            $plan = Plan::find($planId);
            if (!$plan) {
                Log::error('FacturaServicioEmitter: Plan no encontrado', ['plan_id' => $planId]);
                return null;
            }

            // Calcular monto en CLP
            $valorUF = self::obtenerValorUF();
            $montoOriginal = $periodo === 'anual' && $plan->precio_anual > 0
                ? $plan->precio_anual
                : $plan->precio;

            if ($plan->moneda === 'UF' && $valorUF) {
                $totalCLP = round($montoOriginal * $valorUF);
            } else {
                $totalCLP = round($montoOriginal);
            }

            $netoCLP = round($totalCLP / 1.19);
            $ivaCLP = $totalCLP - $netoCLP;

            // Crear registro de factura de servicio (FIX: monto siempre en CLP con IVA)
            $factura = FacturaServicio::create([
                'user_id' => $userId,
                'suscripcion_id' => $suscripcionId,
                'plan_id' => $planId,
                'payment_id' => $paymentId,
                'numero_factura' => 'FS-' . str_pad(FacturaServicio::max('id') + 1, 6, '0', STR_PAD_LEFT),
                'concepto' => $concepto ?: 'Suscripción ' . ($plan->nombre ?? 'Plan'),
                'monto' => $totalCLP,                // ← CLP con IVA (no UF)
                'moneda' => 'CLP',                   // ← siempre CLP en este campo
                'monto_neto' => $netoCLP,
                'monto_iva' => $ivaCLP,
                'monto_plan_clp' => $netoCLP,
                'periodo_inicio' => $periodoInicio ?? now()->toDateString(),
                'periodo_fin' => $periodoFin ?? now()->addDays($periodo === 'anual' ? 365 : 30)->toDateString(),
                'estado' => 'pagada',
                'pagada_at' => now(),
                'valor_uf_usado' => $plan->moneda === 'UF' ? $valorUF : null,
                'tipo' => 'manual',
            ]);

            // Emitir DTE real via Lioren
            self::emitirDTELioren($factura, $userId, $netoCLP);

            return $factura;
        } catch (\Exception $e) {
            Log::error('FacturaServicioEmitter: Error al crear factura', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'plan_id' => $planId,
            ]);
            return null;
        }
    }

    /**
     * Emitir DTE (factura electrónica tipo 33) via Lioren API
     * Usa el endpoint correcto /api/dtes con formato emisor/receptor/detalles
     */
    private static function emitirDTELioren(FacturaServicio $factura, int $userId, int $netoCLP): void
    {
        try {
            $cliente = Cliente::where('user_id', $userId)->first();
            if (!$cliente || !$cliente->rut) {
                Log::warning('FacturaServicioEmitter: Sin datos de facturación', ['user_id' => $userId]);
                return;
            }

            // Obtener API key de la cuenta madre (admin)
            // PARCHE_09_CUENTA_MADRE: buscar por email específico de BigStudio
            $cuentaMadre = IntegracionConfig::whereHas('user', function ($q) {
                $q->where('email', 'hola@bigstudio.cl');
            })->where('activo', true)->first();
            // Fallback: cualquier admin con lioren_api_key
            if (!$cuentaMadre || !$cuentaMadre->lioren_api_key) {
                $cuentaMadre = IntegracionConfig::whereHas('user', function ($q) {
                    $q->where('role', 'admin');
                })->whereNotNull('lioren_api_key')->first();
            }
            // Fallback final: el primer config activo con lioren_api_key
            if (!$cuentaMadre || !$cuentaMadre->lioren_api_key) {
                $cuentaMadre = IntegracionConfig::whereNotNull('lioren_api_key')->where('activo', true)->first();
            }

            if (!$cuentaMadre || !$cuentaMadre->lioren_api_key) {
                Log::warning('FacturaServicioEmitter: Sin cuenta madre configurada');
                return;
            }

            $apiKey = $cuentaMadre->lioren_api_key;

            // Limpiar RUT y asegurar formato con guión
            $rut = trim(str_replace(['.', ' '], '', $cliente->rut));
            if (!str_contains($rut, '-')) {
                $rut = substr($rut, 0, -1) . '-' . substr($rut, -1);
            }

            // Obtener email del usuario
            $user = \App\Models\User::find($userId);
            $email = $user->email ?? $cliente->email ?? null;

            // Receptor y detalles de la factura de servicio
            $receptorFS = [
                'rut' => $rut,
                'rs' => substr(trim($cliente->razon_social ?? $cliente->empresa ?? $cliente->nombre ?? ''), 0, 100),
                'giro' => substr(trim($cliente->giro ?? 'Servicios'), 0, 40),
                'comuna' => 228,
                'ciudad' => 15,
                'direccion' => substr(trim($cliente->direccion ?? 'Santiago'), 0, 50),
            ];
            if ($email) {
                $receptorFS['email'] = substr($email, 0, 80);
            }
            $detallesFS = [[
                'nombre' => 'Servicio Plan ' . ($factura->plan->nombre ?? 'N/A'),
                'cantidad' => 1,
                'precio' => $netoCLP,
                'exento' => false,
            ]];

            // Emitir vía LiorenService (punto ÚNICO de comunicación con Lioren).
            $result = app(\App\Services\LiorenService::class)->emitirFactura($apiKey, $detallesFS, $receptorFS, 'Factura Servicio - ' . $factura->numero_factura);

            if ($result['ok']) {
                $factura->update([
                    'lioren_factura_id' => $result['id'] ?? null,
                    'folio' => $result['folio'] ?? null,
                    'pdf_base64' => $result['pdf'] ?? null,
                ]);

                Log::info('FacturaServicioEmitter: DTE emitido exitosamente', [
                    'factura_id' => $factura->id,
                    'folio' => $result['folio'] ?? null,
                    'lioren_id' => $result['id'] ?? null,
                ]);
            } else {
                Log::error('FacturaServicioEmitter: Error al emitir DTE en Lioren', [
                    'factura_id' => $factura->id,
                    'response' => $result['error'] ?? '',
                    'status' => $result['status'] ?? null,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('FacturaServicioEmitter: Exception al emitir DTE', [
                'error' => $e->getMessage(),
                'factura_id' => $factura->id,
            ]);
        }
    }

    /**
     * Obtener valor actual de la UF
     */
    private static function obtenerValorUF(): float
    {
        return Cache::remember('valor_uf_emitter', 3600, function () {
            try {
                $dbValue = \DB::table('system_settings')->where('key', 'valor_uf')->value('value');
                if ($dbValue && (float)$dbValue > 0) {
                    return (float)$dbValue;
                }
            } catch (\Exception $e) {}

            try {
                $response = Http::withoutVerifying()->timeout(10)->get('https://mindicador.cl/api/uf');
                if ($response->successful()) {
                    $data = $response->json();
                    return $data['serie'][0]['valor'] ?? 39841.72;
                }
            } catch (\Exception $e) {}

            return 39841.72;
        });
    }

    /**
     * Re-emitir DTE en Lioren para una factura existente que no tiene folio.
     * Útil para corregir facturas que quedaron pagadas sin DTE por fallo de Lioren.
     */
    public static function reemitirDTE(FacturaServicio $factura): array
    {
        try {
            // Calcular el monto neto (preferir el campo guardado)
            $netoCLP = (int) ($factura->monto_neto ?: round($factura->monto / 1.19));

            if ($netoCLP <= 0) {
                return ['success' => false, 'error' => 'Monto neto inválido'];
            }

            self::emitirDTELioren($factura, $factura->user_id, $netoCLP);
            $factura->refresh();

            if ($factura->folio) {
                Log::info('FacturaServicioEmitter: DTE re-emitido', [
                    'factura_id' => $factura->id,
                    'folio' => $factura->folio,
                ]);
                return ['success' => true, 'folio' => $factura->folio];
            }

            return [
                'success' => false,
                'error' => 'Lioren no devolvió folio. Revisar logs en storage/logs/laravel.log',
            ];
        } catch (\Throwable $e) {
            Log::error('FacturaServicioEmitter: Error en reemitirDTE', [
                'factura_id' => $factura->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
