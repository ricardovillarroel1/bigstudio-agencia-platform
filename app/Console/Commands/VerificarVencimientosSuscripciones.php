<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Suscripcion;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class VerificarVencimientosSuscripciones extends Command
{
    protected $signature = 'suscripciones:verificar-vencimientos';
    protected $description = 'Verifica y marca como vencidas las suscripciones que no han sido renovadas, envía correo de suspensión';

    public function handle()
    {
        $hoy = now()->format('Y-m-d');

        $suscripcionesVencidas = Suscripcion::where('estado', 'activa')
            ->where('proximo_pago', '<', $hoy)
            ->get();

        if ($suscripcionesVencidas->isEmpty()) {
            $this->info('No hay suscripciones vencidas hoy.');
            Log::info('Verificación de vencimientos: No hay suscripciones vencidas.');
            return 0;
        }

        foreach ($suscripcionesVencidas as $suscripcion) {
            try {
                \DB::beginTransaction();

                $suscripcion->update([
                    'estado' => 'vencida',
                    'suspension_email_sent' => false,
                ]);

                Log::warning("Suscripción #{$suscripcion->id} marcada como vencida", [
                    'user_id' => $suscripcion->user_id,
                    'plan_id' => $suscripcion->plan_id,
                    'proximo_pago' => $suscripcion->proximo_pago,
                ]);

                $integracionConfig = \App\Models\IntegracionConfig::where('user_id', $suscripcion->user_id)
                    ->where('activo', true)
                    ->first();

                if ($integracionConfig) {
                    $integracionConfig->update(['activo' => false]);

                    Log::warning("Integración desactivada por vencimiento de suscripción", [
                        'user_id' => $suscripcion->user_id,
                        'suscripcion_id' => $suscripcion->id,
                        'integracion_config_id' => $integracionConfig->id,
                    ]);
                }

                \DB::commit();

                $suscripcion->load(['user', 'plan']);

                if ($suscripcion->user && !$suscripcion->suspension_email_sent) {
                    $this->enviarCorreoSuspension($suscripcion);
                    $this->enviarMensajeChatSuspension($suscripcion);
                    $suscripcion->update(['suspension_email_sent' => true]);
                }

                $this->info("Suscripción #{$suscripcion->id} del usuario {$suscripcion->user->name} marcada como vencida e integración desactivada.");
            } catch (\Exception $e) {
                \DB::rollBack();
                Log::error("Error al procesar vencimiento de suscripción #{$suscripcion->id}: " . $e->getMessage());
                $this->error("Error al procesar suscripción #{$suscripcion->id}");
            }
        }

        $this->info("Se procesaron {$suscripcionesVencidas->count()} suscripciones vencidas.");
        return 0;
    }

    private function enviarCorreoSuspension(Suscripcion $suscripcion)
    {
        try {
            $user = $suscripcion->user;
            $plan = $suscripcion->plan;
            $email = $user->email;

            if (!$email || !$plan) return;

            $fechaVencimiento = $suscripcion->proximo_pago->format('d/m/Y');
            $fechaSuspension = now()->format('d/m/Y');
            $urlRenovar = url('/planes-activos');

            $asunto = "Tu plan ha sido suspendido | Integraciones BigStudio";

            $contenidoHtml = "
            <div style='font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #0A0A0A;'>
                <!-- Header oscuro con branding Big Studio -->
                <div style='background: #0A0A0A; padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid #1A1A1A;'>
                    <h1 style='color: #FFFFFF; margin: 0 0 4px; font-size: 20px; font-weight: bold; letter-spacing: 2px;'>INTEGRACIONES</h1>
                    <h2 style='color: #FFC107; margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 3px;'>BIG STUDIO</h2>
                    <div style='width: 60px; height: 3px; background: #FFC107; margin: 14px auto 0;'></div>
                </div>

                <!-- Banner de suspensión -->
                <div style='background: #FF5252; padding: 14px 20px; text-align: center;'>
                    <p style='color: #FFFFFF; margin: 0; font-size: 16px; font-weight: bold; letter-spacing: 0.5px;'>Plan Suspendido</p>
                </div>

                <!-- Contenido principal -->
                <div style='padding: 30px 30px 20px;'>
                    <p style='font-size: 15px; color: #FFFFFF; margin: 0 0 15px;'>Hola <strong style='color: #FFC107;'>{$user->name}</strong>,</p>
                    <p style='font-size: 14px; color: #BBBBBB; line-height: 1.7; margin: 0 0 20px;'>Te informamos que tu plan de integración <strong style='color: #FFFFFF;'>{$plan->nombre}</strong> ha sido <strong style='color: #FF5252;'>suspendido</strong> debido a que no fue renovado antes de su fecha de vencimiento ({$fechaVencimiento}).</p>

                    <!-- Tabla de detalles -->
                    <div style='background: #111111; border-radius: 8px; padding: 20px; margin: 0 0 20px; border: 1px solid #222222;'>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Plan</td>
                                <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;'>{$plan->nombre}</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Estado</td>
                                <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FF5252; border-bottom: 1px solid #222222;'>Suspendido</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0; color: #888888; font-size: 13px;'>Fecha de suspensión</td>
                                <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF;'>{$fechaSuspension}</td>
                            </tr>
                        </table>
                    </div>

                    <!-- Sección de consecuencias -->
                    <div style='background: #1A1A1A; border-left: 4px solid #FF5252; padding: 15px 20px; margin: 0 0 20px; border-radius: 0 6px 6px 0;'>
                        <p style='margin: 0 0 10px; font-weight: bold; color: #FF5252; font-size: 14px;'>¿Qué significa esto?</p>
                        <ul style='margin: 0; padding-left: 20px; color: #AAAAAA; font-size: 13px;'>
                            <li style='margin-bottom: 5px;'>La sincronización de productos entre Lioren y Shopify se ha detenido</li>
                            <li style='margin-bottom: 5px;'>Los pedidos de Shopify ya no se facturan automáticamente</li>
                            <li>El inventario ya no se actualiza entre ambas plataformas</li>
                        </ul>
                    </div>

                    <!-- Sección de reactivación -->
                    <div style='background: #1A1A1A; border-left: 4px solid #4CAF50; padding: 15px 20px; margin: 0 0 20px; border-radius: 0 6px 6px 0;'>
                        <p style='margin: 0 0 5px; font-weight: bold; color: #4CAF50; font-size: 14px;'>¿Quieres reactivar tu servicio?</p>
                        <p style='margin: 0; color: #AAAAAA; font-size: 13px;'>Puedes renovar tu plan en cualquier momento desde tu cuenta. Una vez renovado, el servicio se reactivará automáticamente.</p>
                    </div>

                    <!-- Botón CTA -->
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$urlRenovar}' style='background: #FFC107; color: #0A0A0A; padding: 14px 40px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block; letter-spacing: 1px;'>
                            REACTIVAR MI PLAN
                        </a>
                    </div>

                    <p style='font-size: 12px; color: #666666; text-align: center; margin: 15px 0 0;'>Si tienes consultas sobre tu cuenta o necesitas asistencia, contáctanos a hola@bigstudio.cl.</p>
                </div>

                <!-- Separador dorado -->
                <div style='height: 2px; background: #FFC107; margin: 0 30px;'></div>

                <!-- Footer oscuro -->
                <div style='background: #0A0A0A; padding: 25px 30px; text-align: center; border-top: 1px solid #1A1A1A;'>
                    <p style='color: #FFFFFF; font-size: 13px; margin: 0 0 5px; font-weight: bold;'>Equipo Integraciones BigStudio</p>
                    <p style='color: #FFC107; font-size: 12px; margin: 0 0 5px;'>hola@bigstudio.cl</p>
                    <p style='color: #555555; font-size: 11px; margin: 12px 0 0;'>Este es un correo automático. Si tienes consultas, contáctanos por el chat interno o responde a este correo.</p>
                </div>
            </div>";

            Mail::html($contenidoHtml, function ($message) use ($email, $asunto) {
                $message->to($email)->subject($asunto);
            });

            Log::info("Correo de suspensión enviado a {$email}");
        } catch (\Exception $e) {
            Log::error("Error enviando correo de suspensión: " . $e->getMessage());
        }
    }

    private function enviarMensajeChatSuspension(Suscripcion $suscripcion)
    {
        try {
            $user = $suscripcion->user;
            $plan = $suscripcion->plan;
            if (!$user || !$plan) return;

            $fechaVencimiento = $suscripcion->proximo_pago->format('d/m/Y');

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

            $mensaje = "PLAN SUSPENDIDO\n\n"
                . "Estimado/a {$user->name},\n\n"
                . "Su plan \"{$plan->nombre}\" ha sido suspendido por no renovar antes del {$fechaVencimiento}.\n\n"
                . "Los servicios de sincronización, facturación automática e inventario han sido desactivados.\n\n"
                . "Puede reactivar su plan en cualquier momento desde la sección \"Planes Activos\".\n\n"
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
            Log::error("Error al enviar mensaje de chat de suspensión: " . $e->getMessage());
        }
    }
}
