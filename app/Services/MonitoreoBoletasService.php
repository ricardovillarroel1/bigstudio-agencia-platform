<?php

namespace App\Services;

use App\Models\IntegracionConfig;
use App\Models\Boleta;
use App\Models\FacturaEmitida;
use App\Http\Controllers\IntegracionController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Servicio de monitoreo de boletas: detecta pedidos pagados en Shopify que no tienen
 * documento emitido, y permite re-emitirlos. Usado por el comando programado (alerta por
 * correo) y por el panel admin "Pedidos sin boleta".
 */
class MonitoreoBoletasService
{
    /**
     * Devuelve los pedidos pagados sin boleta/factura, agrupados por tienda.
     * Estructura: [ ['tienda'=>..., 'user_id'=>..., 'huerfanos'=>[ ['order_id','number','total','fecha','cliente'], ... ] ], ... ]
     */
    public function detectarHuerfanos(int $dias = 7, int $tolHoras = 3): array
    {
        $desde = now()->subDays($dias);
        $configs = IntegracionConfig::where('activo', 1)
            ->whereNotNull('shopify_token')
            ->whereNotNull('shopify_tienda')
            ->get();

        $reporte = [];
        foreach ($configs as $cfg) {
            if (!$cfg->facturacion_enabled) continue;

            try {
                $pedidos = $this->obtenerPedidosPagados($cfg->shopify_tienda, $cfg->shopify_token, $desde);
            } catch (\Throwable $e) {
                Log::warning("Monitoreo boletas: error consultando {$cfg->shopify_tienda}: " . $e->getMessage());
                continue;
            }

            $huerfanos = [];
            foreach ($pedidos as $o) {
                $oid = (string) ($o['id'] ?? '');
                if (!$oid) continue;

                $monto = (float) ($o['current_total_price'] ?? $o['total_price'] ?? 0);
                if ($monto <= 0) continue; // pedidos de $0 no requieren boleta

                $ref = $o['updated_at'] ?? $o['created_at'] ?? null;
                if ($ref && Carbon::parse($ref)->gt(now()->subHours($tolHoras))) continue; // muy reciente

                $tieneB = Boleta::where('shopify_order_id', $oid)->where('user_id', $cfg->user_id)->where('status', 'emitida')->exists();
                $tieneF = FacturaEmitida::where('shopify_order_id', $oid)->where('user_id', $cfg->user_id)->where('status', 'emitida')->exists();

                if (!$tieneB && !$tieneF) {
                    $huerfanos[] = [
                        'order_id' => $oid,
                        'number' => $o['order_number'] ?? $oid,
                        'total' => $monto,
                        'fecha' => isset($o['created_at']) ? Carbon::parse($o['created_at'])->format('d/m/Y H:i') : '',
                        'cliente' => trim(($o['customer']['first_name'] ?? '') . ' ' . ($o['customer']['last_name'] ?? '')) ?: 's/cliente',
                    ];
                }
            }

            if (count($huerfanos)) {
                $reporte[] = ['tienda' => $cfg->shopify_tienda, 'user_id' => $cfg->user_id, 'huerfanos' => $huerfanos];
            }
        }
        return $reporte;
    }

    /**
     * Re-emite el documento de un pedido específico (usado por el botón "Emitir" del panel).
     * Reutiliza el flujo real del sistema (procesarPedidoPagado): verifica que no exista
     * documento y emite por el monto correcto.
     */
    public function emitirPedido(string $orderId, IntegracionConfig $config): array
    {
        try {
            $resp = Http::withHeaders(['X-Shopify-Access-Token' => $config->shopify_token])->timeout(30)
                ->get("https://{$config->shopify_tienda}/admin/api/2024-10/orders/{$orderId}.json");
            $order = $resp->json()['order'] ?? null;
            if (!$order) return ['ok' => false, 'msg' => 'No se encontró el pedido en Shopify.'];

            $ctrl = app(IntegracionController::class);
            $ref = new \ReflectionMethod($ctrl, 'procesarPedidoPagado');
            $ref->setAccessible(true);
            $ref->invoke($ctrl, $order, $config->lioren_api_key, $config);

            $b = Boleta::where('shopify_order_id', $orderId)->where('user_id', $config->user_id)->where('status', 'emitida')->first();
            $f = FacturaEmitida::where('shopify_order_id', $orderId)->where('user_id', $config->user_id)->where('status', 'emitida')->first();
            if ($b) return ['ok' => true, 'msg' => "Boleta #{$b->folio} emitida ($" . number_format($b->monto_total, 0, ',', '.') . ")."];
            if ($f) return ['ok' => true, 'msg' => "Factura #{$f->folio} emitida ($" . number_format($f->monto_total, 0, ',', '.') . ")."];
            return ['ok' => false, 'msg' => 'No se pudo emitir (revisar datos fiscales del pedido o logs).'];
        } catch (\Throwable $e) {
            Log::error("Monitoreo boletas: error emitiendo pedido {$orderId}: " . $e->getMessage());
            return ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
        }
    }

    private function obtenerPedidosPagados($shop, $token, Carbon $desde): array
    {
        $pedidos = [];
        $url = "https://{$shop}/admin/api/2024-10/orders.json";
        $params = [
            'status' => 'any',
            'financial_status' => 'paid',
            'created_at_min' => $desde->toIso8601String(),
            'limit' => 250,
            'fields' => 'id,order_number,total_price,current_total_price,financial_status,created_at,updated_at,customer,cancelled_at',
        ];
        for ($i = 0; $i < 5; $i++) {
            $resp = Http::withHeaders(['X-Shopify-Access-Token' => $token])->timeout(30)->get($url, $params);
            if (!$resp->successful()) break;
            foreach ($resp->json()['orders'] ?? [] as $o) {
                if (!empty($o['cancelled_at'])) continue;
                $pedidos[] = $o;
            }
            $link = $resp->header('Link');
            if (!$link || strpos($link, 'rel="next"') === false) break;
            if (preg_match('/<([^>]+)>;\s*rel="next"/', $link, $m)) { $url = $m[1]; $params = []; }
            else break;
        }
        return $pedidos;
    }
}
