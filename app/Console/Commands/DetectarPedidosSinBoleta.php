<?php

namespace App\Console\Commands;

use App\Models\IntegracionConfig;
use App\Models\Boleta;
use App\Models\FacturaEmitida;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class DetectarPedidosSinBoleta extends Command
{
    protected $signature = 'integraciones:detectar-sin-boleta {--dias=7} {--horas-tolerancia=3} {--dry-run}';
    protected $description = 'Detecta pedidos pagados en Shopify que NO tienen boleta/factura emitida y alerta por correo.';

    public function handle()
    {
        $dias = (int) $this->option('dias');
        $tolHoras = (int) $this->option('horas-tolerancia');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("Buscando pedidos pagados sin boleta (últimos {$dias} días, tolerancia {$tolHoras}h)...");

        // Lógica compartida con el panel admin (una sola fuente de verdad).
        $reporte = app(\App\Services\MonitoreoBoletasService::class)->detectarHuerfanos($dias, $tolHoras);

        if (empty($reporte)) {
            $this->info('✅ No hay pedidos pagados sin boleta.');
            return 0;
        }

        $total = array_sum(array_map(fn($r) => count($r['huerfanos']), $reporte));
        foreach ($reporte as $r) {
            $this->warn("  {$r['tienda']}: " . count($r['huerfanos']) . " pedido(s) SIN boleta");
        }
        $this->warn("⚠️ Total: {$total} pedido(s) pagado(s) sin boleta en " . count($reporte) . " tienda(s).");

        if ($dryRun) {
            $this->info('[dry-run] No se envía correo. Detalle:');
            foreach ($reporte as $r) {
                foreach ($r['huerfanos'] as $h) {
                    $this->line("  {$r['tienda']} | #{$h['number']} | {$h['cliente']} | \${$h['total']} | {$h['fecha']}");
                }
            }
            return 0;
        }

        $this->enviarAlerta($reporte, $total);
        $this->info("Correo de alerta enviado a hola@bigstudio.cl");
        return 0;
    }

    private function enviarAlerta(array $reporte, int $total)
    {
        try {
            $filas = '';
            foreach ($reporte as $r) {
                foreach ($r['huerfanos'] as $h) {
                    $montoFmt = '$' . number_format((float) $h['total'], 0, ',', '.');
                    $filas .= "<tr>
                        <td style='padding:8px 10px;border-bottom:1px solid #eee;font-size:13px;'>{$r['tienda']}</td>
                        <td style='padding:8px 10px;border-bottom:1px solid #eee;font-size:13px;font-weight:bold;'>#{$h['number']}</td>
                        <td style='padding:8px 10px;border-bottom:1px solid #eee;font-size:13px;'>{$h['cliente']}</td>
                        <td style='padding:8px 10px;border-bottom:1px solid #eee;font-size:13px;text-align:right;'>{$montoFmt}</td>
                        <td style='padding:8px 10px;border-bottom:1px solid #eee;font-size:12px;color:#888;'>{$h['fecha']}</td>
                    </tr>";
                }
            }

            $html = "
            <div style='font-family:Arial,sans-serif;max-width:680px;margin:0 auto;'>
                <div style='background:linear-gradient(135deg,#FFC800,#FF8100);padding:22px;text-align:center;border-radius:12px 12px 0 0;'>
                    <h2 style='color:#fff;margin:0;font-size:18px;'>⚠️ Pedidos pagados SIN boleta</h2>
                </div>
                <div style='padding:24px;background:#fff;border:1px solid #eee;border-top:none;border-radius:0 0 12px 12px;'>
                    <p style='font-size:14px;color:#333;'>Se detectaron <strong>{$total}</strong> pedido(s) pagado(s) en Shopify que aún no tienen boleta o factura emitida:</p>
                    <table style='width:100%;border-collapse:collapse;margin-top:12px;'>
                        <tr style='background:#FFF7EC;'>
                            <th style='padding:8px 10px;text-align:left;font-size:12px;color:#666;'>Tienda</th>
                            <th style='padding:8px 10px;text-align:left;font-size:12px;color:#666;'>Pedido</th>
                            <th style='padding:8px 10px;text-align:left;font-size:12px;color:#666;'>Cliente</th>
                            <th style='padding:8px 10px;text-align:right;font-size:12px;color:#666;'>Monto</th>
                            <th style='padding:8px 10px;text-align:left;font-size:12px;color:#666;'>Fecha</th>
                        </tr>
                        {$filas}
                    </table>
                    <p style='font-size:12px;color:#999;margin-top:18px;'>Revisa estos pedidos y emite sus boletas. Este es un aviso automático del sistema de Integraciones BigStudio (se ejecuta cada 6 horas).</p>
                </div>
            </div>";

            Mail::html($html, function ($message) use ($total) {
                $message->to('hola@bigstudio.cl')
                    ->subject("⚠️ {$total} pedido(s) pagado(s) SIN boleta — Integraciones BigStudio");
            });

            Log::info("Detector sin-boleta: alerta enviada", ['total' => $total]);
        } catch (\Throwable $e) {
            Log::error("Detector sin-boleta: error enviando correo: " . $e->getMessage());
            $this->error("No se pudo enviar el correo: " . $e->getMessage());
        }
    }
}
