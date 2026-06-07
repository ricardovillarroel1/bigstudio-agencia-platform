<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Suscripcion;
use App\Models\FacturaServicio;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Services\FacturaServicioEmitter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class GenerarFacturaAnticipada extends Command
{
    protected $signature = 'facturacion:generar-anticipada';
    protected $description = 'Genera facturas de servicio 5 días antes del vencimiento del plan y notifica al cliente';

    public function handle()
    {
        $this->info('Buscando suscripciones que vencen en los próximos 5 días para generar facturas...');

        $hoy = now()->format('Y-m-d');
        $fechaLimite = now()->addDays(5)->format('Y-m-d');

        // Buscar suscripciones activas cuyo proximo_pago esté entre hoy y 5 días adelante
        $suscripciones = Suscripcion::where('estado', 'activa')
            ->where('pausada', false)
            ->whereDate('proximo_pago', '>=', $hoy)
            ->whereDate('proximo_pago', '<=', $fechaLimite)
            ->whereHas('plan', function($q) {
                $q->where('precio', '>', 0);
            })
            ->with(['plan', 'user'])
            ->get();

        $this->info("Encontradas {$suscripciones->count()} suscripciones que vencen entre {$hoy} y {$fechaLimite}.");

        if ($suscripciones->isEmpty()) {
            $this->info('No hay suscripciones que requieran factura anticipada.');
            return 0;
        }

        $valorUF = $this->obtenerValorUF();
        $this->info("Valor UF utilizado: $" . number_format($valorUF, 2, ',', '.'));

        $generadas = 0;

        foreach ($suscripciones as $suscripcion) {
            try {
                // Verificar que no exista ya una factura para este período
                $proximoPago = $suscripcion->proximo_pago->format('Y-m-d');
                $facturaExistente = FacturaServicio::where('suscripcion_id', $suscripcion->id)
                    ->where('periodo_inicio', '>=', $proximoPago)
                    ->where('estado', '!=', 'anulada')
                    ->first();

                if ($facturaExistente) {
                    $this->info("Factura ya existe para suscripción #{$suscripcion->id}, omitiendo.");
                    continue;
                }

                $plan = $suscripcion->plan;
                $user = $suscripcion->user;
                if (!$plan || !$user) continue;

                // Calcular montos (Plan es +IVA)
                $precioUF = $plan->precio;
                $montoNeto = round($precioUF * $valorUF);
                $montoIVA = round($montoNeto * 0.19);
                $montoTotal = $montoNeto + $montoIVA;

                // Período de la factura: desde proximo_pago hasta +30 días
                $periodoInicio = \Carbon\Carbon::parse($suscripcion->proximo_pago);
                $periodoFin = $periodoInicio->copy()->addDays(30);

                // Crear la factura de servicio
                $factura = FacturaServicio::create([
                    'user_id' => $user->id,
                    'suscripcion_id' => $suscripcion->id,
                    'plan_id' => $plan->id,
                    'concepto' => 'Suscripción ' . $plan->nombre . ' (Renovación Anticipada) - Período ' . $periodoInicio->format('d/m/Y') . ' al ' . $periodoFin->format('d/m/Y'),
                    'tipo' => 'mensual',
                    'estado' => 'pendiente',
                    'moneda' => 'CLP',
                    'monto' => $montoTotal,
                    'monto_neto' => $montoNeto,
                    'monto_iva' => $montoIVA,
                    'monto_plan_clp' => $montoNeto, // El monto base del plan es el neto
                    'monto_extra_clp' => 0,
                    'documentos_incluidos' => $plan->monthly_order_limit ?? 0,
                    'documentos_emitidos' => 0,
                    'documentos_extra' => 0,
                    'precio_extra_uf' => 0,
                    'valor_uf_usado' => $valorUF,
                    'periodo_inicio' => $periodoInicio,
                    'periodo_fin' => $periodoFin,
                ]);

                $generadas++;

                // Emitir DTE automáticamente en Lioren
                $this->info("  Emitiendo DTE en Lioren para factura #{$factura->id}...");
                try {
                    FacturaServicioEmitter::emitirDTELioren($factura, $user->id, $montoNeto);
                    $factura->refresh();
                } catch (\Exception $e) {
                    Log::error("Error emitiendo DTE para factura anticipada #{$factura->id}: " . $e->getMessage());
                }

                // Enviar notificación por Chat
                $this->enviarNotificacionChat($user, $plan, $factura, $montoTotal);

                // Enviar correo electrónico con branding Big Studio
                $this->enviarCorreoFactura($user, $plan, $factura, $montoTotal);

                $this->info("Factura #{$factura->id} generada y notificada para {$user->name} - {$plan->nombre} - $" . number_format($montoTotal, 0, ',', '.') . " CLP");

                Log::info("Factura anticipada generada y emitida", [
                    'factura_id' => $factura->id,
                    'user_id' => $user->id,
                    'plan' => $plan->nombre,
                    'monto_total' => $montoTotal,
                    'folio' => $factura->folio
                ]);
            } catch (\Exception $e) {
                Log::error("Error generando factura anticipada para suscripción #{$suscripcion->id}: " . $e->getMessage());
                $this->error("Error suscripción #{$suscripcion->id}: " . $e->getMessage());
            }
        }

        $this->info("Se generaron {$generadas} facturas anticipadas.");
        return 0;
    }

    private function enviarNotificacionChat($user, $plan, $factura, $montoCLP)
    {
        try {
            $chat = Chat::where('cliente_id', $user->id)
                ->where('contexto', 'notificacion_sistema')
                ->where('estado', 'activo')
                ->first();

            if (!$chat) {
                $chat = Chat::create([
                    'cliente_id' => $user->id,
                    'plan_id' => $plan->id,
                    'contexto' => 'notificacion_sistema',
                    'estado' => 'activo',
                    'mensaje_count' => 0,
                    'ultimo_mensaje_at' => now(),
                ]);
            }

            $mensaje = "📄 FACTURA DE SERVICIO GENERADA\n\n"
                . "Estimado/a {$user->name},\n\n"
                . "Se ha generado su factura de servicio para el próximo período:\n\n"
                . "Plan: {$plan->nombre}\n"
                . "Monto: $" . number_format($montoCLP, 0, ',', '.') . " CLP\n"
                . "Período: " . $factura->periodo_inicio->format('d/m/Y') . " al " . $factura->periodo_fin->format('d/m/Y') . "\n";
            
            if ($factura->folio) {
                $mensaje .= "Folio SII: {$factura->folio}\n";
            }

            $mensaje .= "\nPuede realizar el pago desde la sección \"Facturación\" en su panel.\n\n"
                . "Equipo Integraciones BigStudio";

            ChatMessage::create([
                'chat_id' => $chat->id,
                'user_id' => null,
                'mensaje' => $mensaje,
                'leido' => false,
            ]);

            $chat->update([
                'mensaje_count' => $chat->mensaje_count + 1,
                'ultimo_mensaje_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Error enviando notificación de chat de factura: " . $e->getMessage());
        }
    }

    private function enviarCorreoFactura($user, $plan, $factura, $montoCLP)
    {
        try {
            $email = $user->email;
            if (!$email) return;

            $asunto = "Factura lista para pago — Plan {$plan->nombre} | Integraciones BigStudio";
            $urlFacturacion = url('/admin/billing/' . $user->id);

            $contenidoHtml = "
            <div style='font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff;'>
                <!-- Header -->
                <div style='background: #1B2A4A; padding: 30px 20px; text-align: center;'>
                    <h1 style='color: #ffffff; margin: 0; font-size: 22px; font-weight: bold; letter-spacing: 1px;'>INTEGRACIONES BIG STUDIO</h1>
                    <div style='width: 50px; height: 3px; background: #26C6DA; margin: 12px auto 0;'></div>
                </div>

                <!-- Banner -->
                <div style='background: #26C6DA; padding: 15px 20px; text-align: center;'>
                    <p style='color: #ffffff; margin: 0; font-size: 18px; font-weight: bold;'>Factura de Servicio</p>
                </div>

                <!-- Contenido principal -->
                <div style='padding: 30px 30px 20px;'>
                    <p style='font-size: 15px; color: #333; margin: 0 0 15px;'>Hola <strong>{$user->name}</strong>,</p>
                    <p style='font-size: 14px; color: #555; line-height: 1.6; margin: 0 0 20px;'>Te informamos que tu factura de servicio para el próximo período ha sido emitida y está lista para el pago:</p>

                    <!-- Tabla de detalles -->
                    <div style='background: #F8F9FA; border-radius: 8px; padding: 20px; margin: 0 0 20px; border: 1px solid #E9ECEF;'>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 10px 0; color: #666; font-size: 14px; border-bottom: 1px solid #E9ECEF;'>Plan</td>
                                <td style='padding: 10px 0; font-weight: bold; text-align: right; font-size: 14px; color: #333; border-bottom: 1px solid #E9ECEF;'>{$plan->nombre}</td>
                            </tr>";
            
            if ($factura->folio) {
                $contenidoHtml .= "
                            <tr>
                                <td style='padding: 10px 0; color: #666; font-size: 14px; border-bottom: 1px solid #E9ECEF;'>Folio SII</td>
                                <td style='padding: 10px 0; font-weight: bold; text-align: right; font-size: 14px; color: #333; border-bottom: 1px solid #E9ECEF;'>{$factura->folio}</td>
                            </tr>";
            }

            $contenidoHtml .= "
                            <tr>
                                <td style='padding: 10px 0; color: #666; font-size: 14px; border-bottom: 1px solid #E9ECEF;'>Monto Total (IVA incl.)</td>
                                <td style='padding: 10px 0; font-weight: bold; text-align: right; font-size: 16px; color: #1B2A4A; border-bottom: 1px solid #E9ECEF;'>$" . number_format($montoCLP, 0, ',', '.') . " CLP</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px 0; color: #666; font-size: 14px; border-bottom: 1px solid #E9ECEF;'>Período</td>
                                <td style='padding: 10px 0; text-align: right; font-size: 14px; color: #333; border-bottom: 1px solid #E9ECEF;'>" . $factura->periodo_inicio->format('d/m/Y') . " al " . $factura->periodo_fin->format('d/m/Y') . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px 0; color: #666; font-size: 14px;'>Fecha límite de pago</td>
                                <td style='padding: 10px 0; font-weight: bold; text-align: right; font-size: 14px; color: #E53935;'>" . $factura->periodo_inicio->format('d/m/Y') . "</td>
                            </tr>
                        </table>
                    </div>

                    <p style='font-size: 14px; color: #555; line-height: 1.6; margin: 0 0 20px;'>Para evitar la interrupción de tu servicio, te recomendamos realizar el pago antes de la fecha límite.</p>

                    <!-- Botón CTA -->
                    <div style='text-align: center; margin: 25px 0;'>
                        <a href='{$urlFacturacion}' style='background: #26C6DA; color: #ffffff; padding: 14px 35px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 15px; display: inline-block; letter-spacing: 0.5px;'>
                            VER FACTURA Y PAGAR
                        </a>
                    </div>

                    <p style='font-size: 12px; color: #999; text-align: center; margin: 15px 0 0;'>También puedes ver y descargar tu factura desde la sección \"Facturación\" en tu panel.</p>
                </div>

                <!-- Separador -->
                <div style='height: 3px; background: #26C6DA; margin: 0 30px;'></div>

                <!-- Footer -->
                <div style='background: #F5F5F5; padding: 20px 30px; text-align: center;'>
                    <p style='color: #666; font-size: 13px; margin: 0 0 5px; font-weight: bold;'>Equipo Integraciones BigStudio</p>
                    <p style='color: #999; font-size: 12px; margin: 0 0 5px;'>hola@bigstudio.cl</p>
                    <p style='color: #bbb; font-size: 11px; margin: 10px 0 0;'>Este es un correo automático. Si tienes consultas, contáctanos por el chat interno o responde a este correo.</p>
                </div>
            </div>";

            Mail::html($contenidoHtml, function ($message) use ($email, $asunto) {
                $message->to($email)->subject($asunto);
            });

            Log::info("Correo de factura enviado a {$email}");
        } catch (\Exception $e) {
            Log::error("Error enviando correo de factura: " . $e->getMessage());
            $this->warn("  No se pudo enviar correo a {$user->email}: " . $e->getMessage());
        }
    }

    private function obtenerValorUF(): float
    {
        try {
            $setting = \DB::table('system_settings')->where('key', 'valor_uf')->first();
            if ($setting) {
                return (float) $setting->value;
            }
        } catch (\Exception $e) {}

        try {
            $response = Http::withoutVerifying()->timeout(10)->get('https://mindicador.cl/api/uf');
            if ($response->successful()) {
                $data = $response->json();
                return $data['serie'][0]['valor'] ?? 39841.72;
            }
        } catch (\Exception $e) {
            Log::error('Error obteniendo valor UF: ' . $e->getMessage());
        }

        return 39841.72;
    }
}
