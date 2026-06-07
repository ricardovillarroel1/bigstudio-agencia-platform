<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Suscripcion;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class NotificarVencimientoPlanes extends Command
{
    protected $signature = 'suscripciones:notificar-vencimiento';
    protected $description = 'Envía correos y notificaciones de vencimiento de plan 7, 3 y 0 días antes';

    public function handle()
    {
        $this->info('Verificando suscripciones próximas a vencer...');

        $valorUF = $this->obtenerValorUF();
        $totalEnviados = 0;

        // Recordatorio 7 días antes
        $sieteDias = now()->addDays(7)->format('Y-m-d');
        $suscripciones7 = Suscripcion::where('estado', 'activa')
            ->where('pausada', false)
            ->whereDate('proximo_pago', $sieteDias)
            ->where('reminder_7d_sent', false)
            ->whereHas('plan', fn($q) => $q->where('precio', '>', 0))
            ->with(['plan', 'user'])
            ->get();

        foreach ($suscripciones7 as $sub) {
            $this->enviarRecordatorio($sub, 7, $valorUF);
            $sub->update(['reminder_7d_sent' => true]);
            $totalEnviados++;
        }

        // Recordatorio 3 días antes
        $tresDias = now()->addDays(3)->format('Y-m-d');
        $suscripciones3 = Suscripcion::where('estado', 'activa')
            ->where('pausada', false)
            ->whereDate('proximo_pago', $tresDias)
            ->where('reminder_3d_sent', false)
            ->whereHas('plan', fn($q) => $q->where('precio', '>', 0))
            ->with(['plan', 'user'])
            ->get();

        foreach ($suscripciones3 as $sub) {
            $this->enviarRecordatorio($sub, 3, $valorUF);
            $sub->update(['reminder_3d_sent' => true]);
            $totalEnviados++;
        }

        // Recordatorio el día del vencimiento
        $hoy = now()->format('Y-m-d');
        $suscripciones0 = Suscripcion::where('estado', 'activa')
            ->where('pausada', false)
            ->whereDate('proximo_pago', $hoy)
            ->where('reminder_0d_sent', false)
            ->whereHas('plan', fn($q) => $q->where('precio', '>', 0))
            ->with(['plan', 'user'])
            ->get();

        foreach ($suscripciones0 as $sub) {
            $this->enviarRecordatorio($sub, 0, $valorUF);
            $sub->update(['reminder_0d_sent' => true]);
            $totalEnviados++;
        }

        $this->info("Se enviaron {$totalEnviados} notificaciones de vencimiento.");
        Log::info("Notificaciones de vencimiento enviadas: {$totalEnviados}");

        return 0;
    }

    private function enviarRecordatorio(Suscripcion $suscripcion, int $diasRestantes, float $valorUF)
    {
        try {
            $user = $suscripcion->user;
            $plan = $suscripcion->plan;
            if (!$user || !$plan) return;

            $fechaVencimiento = $suscripcion->proximo_pago->format('d/m/Y');
            $precioUFNeto = $plan->precio; // Precio en UF sin IVA
            $precioUFConIva = round($precioUFNeto * 1.19, 2); // Precio con IVA
            $precioCLP = number_format(round($precioUFConIva * $valorUF), 0, ',', '.');

            $this->enviarCorreo($user, $plan, $diasRestantes, $fechaVencimiento, $precioUFNeto, $precioCLP);
            $this->enviarMensajeChat($user, $plan, $diasRestantes, $fechaVencimiento);

            Log::info("Recordatorio de vencimiento enviado", [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'plan' => $plan->nombre,
                'dias_restantes' => $diasRestantes,
                'fecha_vencimiento' => $fechaVencimiento,
            ]);

            $this->info("Recordatorio ({$diasRestantes} días) enviado a {$user->name} ({$user->email})");
        } catch (\Exception $e) {
            Log::error("Error al enviar recordatorio de vencimiento: " . $e->getMessage(), [
                'suscripcion_id' => $suscripcion->id,
            ]);
            $this->error("Error al notificar suscripción #{$suscripcion->id}: " . $e->getMessage());
        }
    }

    private function enviarCorreo($user, $plan, int $dias, string $fechaVencimiento, $precioUF, string $precioCLP)
    {
        $email = $user->email;
        if (!$email) return;

        switch ($dias) {
            case 7:
                $asunto = "Tu plan vence en 7 días — Renueva ahora | Integraciones BigStudio";
                $tituloBanner = "Tu plan vence en 7 días";
                $mensajePrincipal = "Te informamos que tu plan de integración con BigStudio está próximo a vencer. A continuación, los detalles de tu suscripción actual:";
                $diasTexto = "7 días";
                $seccionUrgencia = "";
                $mensajeCierre = "Para asegurar la continuidad de tu servicio de sincronización con Shopify, te recomendamos renovar tu plan antes de la fecha de vencimiento.";
                $botonTexto = "RENOVAR MI PLAN";
                $bannerBg = "#FFC107";
                $bannerTextColor = "#0A0A0A";
                $botonBg = "#FFC107";
                $botonTextColor = "#0A0A0A";
                break;

            case 3:
                $asunto = "Quedan 3 días para renovar tu plan | Integraciones BigStudio";
                $tituloBanner = "Quedan 3 días para renovar";
                $mensajePrincipal = "Este es un recordatorio importante: tu plan de integración vence en <strong style='color: #FFC107;'>3 días</strong>. Si no renuevas antes del <strong>{$fechaVencimiento}</strong>, tu servicio de sincronización será suspendido automáticamente.";
                $diasTexto = "3 días";
                $seccionUrgencia = "
                    <div style='background: #1A1A1A; border-left: 4px solid #FF9800; padding: 15px 20px; margin: 20px 0; border-radius: 0 6px 6px 0;'>
                        <p style='margin: 0 0 10px; font-weight: bold; color: #FF9800; font-size: 14px;'>¿Qué sucede si no renuevas?</p>
                        <ul style='margin: 0; padding-left: 20px; color: #AAAAAA; font-size: 13px;'>
                            <li style='margin-bottom: 5px;'>La sincronización de productos entre Lioren y Shopify se detendrá</li>
                            <li style='margin-bottom: 5px;'>Los pedidos de Shopify dejarán de facturarse automáticamente</li>
                            <li>El inventario dejará de actualizarse</li>
                        </ul>
                    </div>";
                $mensajeCierre = "";
                $botonTexto = "RENOVAR AHORA";
                $bannerBg = "#FF9800";
                $bannerTextColor = "#0A0A0A";
                $botonBg = "#FFC107";
                $botonTextColor = "#0A0A0A";
                break;

            case 0:
                $asunto = "Tu plan vence hoy — Renueva para no perder tu servicio | Integraciones BigStudio";
                $tituloBanner = "Tu plan vence HOY";
                $mensajePrincipal = "<strong style='color: #FF5252;'>Tu plan de integración vence hoy, {$fechaVencimiento}.</strong> Si no renuevas durante el día de hoy, tu servicio será suspendido automáticamente al finalizar la jornada.";
                $diasTexto = "HOY";
                $seccionUrgencia = "
                    <div style='background: #1A1A1A; border-left: 4px solid #FF5252; padding: 15px 20px; margin: 20px 0; border-radius: 0 6px 6px 0;'>
                        <p style='margin: 0 0 10px; font-weight: bold; color: #FF5252; font-size: 14px;'>Importante: Una vez suspendido el servicio</p>
                        <ul style='margin: 0; padding-left: 20px; color: #AAAAAA; font-size: 13px;'>
                            <li style='margin-bottom: 5px;'>Se detendrá toda sincronización con Shopify</li>
                            <li style='margin-bottom: 5px;'>No se emitirán facturas ni boletas automáticas</li>
                            <li>El inventario no se actualizará</li>
                        </ul>
                    </div>
                    <p style='color: #AAAAAA; font-size: 14px;'>Puedes reactivar tu plan en cualquier momento renovando desde tu cuenta.</p>";
                $mensajeCierre = "";
                $botonTexto = "RENOVAR AHORA — ÚLTIMO DÍA";
                $bannerBg = "#FF5252";
                $bannerTextColor = "#FFFFFF";
                $botonBg = "#FF5252";
                $botonTextColor = "#FFFFFF";
                break;

            default:
                return;
        }

        $urlRenovar = url('/planes-activos');

        $diasColor = '#FFC107';
        if ($dias === 0) $diasColor = '#FF5252';
        elseif ($dias <= 3) $diasColor = '#FF9800';

        $contenidoHtml = "
        <div style='font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #0A0A0A;'>
            <!-- Header oscuro con branding Big Studio -->
            <div style='background: #0A0A0A; padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid #1A1A1A;'>
                <h1 style='color: #FFFFFF; margin: 0 0 4px; font-size: 20px; font-weight: bold; letter-spacing: 2px;'>INTEGRACIONES</h1>
                <h2 style='color: #FFC107; margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 3px;'>BIG STUDIO</h2>
                <div style='width: 60px; height: 3px; background: #FFC107; margin: 14px auto 0;'></div>
            </div>

            <!-- Banner de alerta -->
            <div style='background: {$bannerBg}; padding: 14px 20px; text-align: center;'>
                <p style='color: {$bannerTextColor}; margin: 0; font-size: 16px; font-weight: bold; letter-spacing: 0.5px;'>{$tituloBanner}</p>
            </div>

            <!-- Contenido principal -->
            <div style='padding: 30px 30px 20px;'>
                <p style='font-size: 15px; color: #FFFFFF; margin: 0 0 15px;'>Hola <strong style='color: #FFC107;'>{$user->name}</strong>,</p>
                <p style='font-size: 14px; color: #BBBBBB; line-height: 1.7; margin: 0 0 20px;'>{$mensajePrincipal}</p>

                <!-- Tabla de detalles del plan -->
                <div style='background: #111111; border-radius: 8px; padding: 20px; margin: 0 0 20px; border: 1px solid #222222;'>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Plan</td>
                            <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;'>{$plan->nombre}</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Precio</td>
                            <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFC107; border-bottom: 1px solid #222222;'>{$precioUF} UF +IVA = \${$precioCLP} CLP</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Fecha de vencimiento</td>
                            <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;'>{$fechaVencimiento}</td>
                        </tr>
                        <tr>
                            <td style='padding: 12px 0; color: #888888; font-size: 13px;'>Días restantes</td>
                            <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 16px; color: {$diasColor};'>{$diasTexto}</td>
                        </tr>
                    </table>
                </div>

                {$seccionUrgencia}

                " . ($mensajeCierre ? "<p style='font-size: 14px; color: #BBBBBB; line-height: 1.7; margin: 0 0 20px;'>{$mensajeCierre}</p>" : "") . "

                <!-- Botón CTA -->
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$urlRenovar}' style='background: {$botonBg}; color: {$botonTextColor}; padding: 14px 40px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block; letter-spacing: 1px;'>
                        {$botonTexto}
                    </a>
                </div>

                <p style='font-size: 12px; color: #666666; text-align: center; margin: 15px 0 0;'>Si ya realizaste el pago, puedes ignorar este mensaje.</p>
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

        Log::info("Correo de recordatorio ({$dias} días) enviado a {$email}");
    }

    private function enviarMensajeChat($user, $plan, int $dias, string $fechaVencimiento)
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

            switch ($dias) {
                case 7:
                    $mensaje = "RECORDATORIO DE VENCIMIENTO - 7 DÍAS\n\n"
                        . "Estimado/a {$user->name},\n\n"
                        . "Le informamos que su plan \"{$plan->nombre}\" vence el {$fechaVencimiento} (en 7 días).\n\n"
                        . "Para asegurar la continuidad de su servicio, le recomendamos renovar su plan desde la sección \"Planes Activos\".\n\n"
                        . "Equipo Integraciones BigStudio";
                    break;
                case 3:
                    $mensaje = "AVISO DE VENCIMIENTO - 3 DÍAS\n\n"
                        . "Estimado/a {$user->name},\n\n"
                        . "Su plan \"{$plan->nombre}\" vence el {$fechaVencimiento} (en 3 días).\n\n"
                        . "Si no renueva antes de la fecha de vencimiento, su servicio será suspendido automáticamente.\n\n"
                        . "Renueve ahora desde \"Planes Activos\".\n\n"
                        . "Equipo Integraciones BigStudio";
                    break;
                case 0:
                    $mensaje = "AVISO URGENTE - VENCIMIENTO HOY\n\n"
                        . "Estimado/a {$user->name},\n\n"
                        . "Su plan \"{$plan->nombre}\" vence HOY {$fechaVencimiento}.\n\n"
                        . "Si no realiza el pago hoy, su servicio será suspendido automáticamente.\n\n"
                        . "Renueve ahora desde \"Planes Activos\" para mantener su servicio activo.\n\n"
                        . "Equipo Integraciones BigStudio";
                    break;
            }

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
            Log::error("Error al enviar mensaje de chat de vencimiento: " . $e->getMessage());
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
