<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CobroAsignado;
use App\Models\User;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\FacturaServicio;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\FacturaServicioEmitter;
use App\Models\Suscripcion;
use App\Models\Plan;
use App\Models\IntegracionConfig;

class CobroAsignadoController extends Controller
{
    /**
     * Vista admin: lista de cobros asignados
     */
    public function index()
    {
        $cobros = CobroAsignado::with(['admin', 'cliente'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $clientes = User::role('cliente')->orderBy('name')->get();

        return view('admin.cobros-asignados', compact('cobros', 'clientes'));
    }

    /**
     * Admin crea un cobro asignado a un cliente
     */
    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:users,id',
            'concepto' => 'required|string|max:500',
            'monto' => 'required|numeric|min:350',
        ]);

        $cobro = CobroAsignado::create([
            'admin_id' => auth()->id(),
            'cliente_id' => $request->cliente_id,
            'concepto' => $request->concepto,
            'monto' => $request->monto,
            'estado' => 'pendiente',
        ]);

        // Enviar notificación al cliente por Chat
        $this->notificarClienteChat($cobro);

        // Enviar correo al cliente
        $this->notificarClienteEmail($cobro);
        
        // Emitir factura via Lioren
        $this->emitirFacturaCobro($cobro);

        return redirect()->route('admin.cobros-asignados.index')
            ->with('success', 'Cobro asignado correctamente al cliente. Se le ha notificado por chat y correo.');
    }

    /**
     * Anular un cobro
     */
    public function anular(CobroAsignado $cobro)
    {
        $cobro->update(['estado' => 'anulado']);
        return redirect()->route('admin.cobros-asignados.index')
            ->with('success', 'Cobro anulado correctamente.');
    }

    /**
     * Vista del cliente: ver sus cobros pendientes
     */
    public function misCobros()
    {
        $cobros = CobroAsignado::where('cliente_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('cliente.cobros-pendientes', compact('cobros'));
    }

    private function notificarClienteChat(CobroAsignado $cobro)
    {
        try {
            $user = $cobro->cliente;
            if (!$user) return;

            $chat = Chat::where('cliente_id', $user->id)
                ->where('contexto', 'notificacion_sistema')
                ->where('estado', 'activo')
                ->first();

            if (!$chat) {
                $chat = Chat::create([
                    'cliente_id' => $user->id,
                    'contexto' => 'notificacion_sistema',
                    'estado' => 'activo',
                    'mensaje_count' => 0,
                    'ultimo_mensaje_at' => now(),
                ]);
            }

            $mensaje = "💳 NUEVO PAGO PENDIENTE\n\n"
                . "Estimado/a {$user->name},\n\n"
                . "Se le ha asignado un nuevo cobro:\n\n"
                . "📋 Concepto: {$cobro->concepto}\n"
                . "💰 Monto: $" . number_format(round($cobro->monto * 1.19), 0, ',', '.') . " CLP (IVA incluido)\n"
                . "📅 Fecha: " . now()->format('d/m/Y') . "\n\n"
                . "Puede realizar el pago desde la sección \"Cobros Pendientes\" en su perfil.\n\n"
                . "Equipo Big Studio";

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
            Log::error("Error notificando cobro por chat: " . $e->getMessage());
        }
    }

    private function notificarClienteEmail(CobroAsignado $cobro)
    {
        try {
            $user = $cobro->cliente;
            if (!$user || !$user->email) return;

            $asunto = "Nuevo pago pendiente - Integraciones Big Studio";
            $montoConIva = round($cobro->monto * 1.19);
            $montoFormateado = '$' . number_format($montoConIva, 0, ',', '.');

            $contenidoHtml = "
            <div style='font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #0A0A0A;'>
                <!-- Header oscuro con branding Big Studio -->
                <div style='background: #0A0A0A; padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid #1A1A1A;'>
                    <h1 style='color: #FFFFFF; margin: 0 0 4px; font-size: 20px; font-weight: bold; letter-spacing: 2px;'>INTEGRACIONES</h1>
                    <h2 style='color: #FFC107; margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 3px;'>BIG STUDIO</h2>
                    <div style='width: 60px; height: 3px; background: #FFC107; margin: 14px auto 0;'></div>
                </div>
                <!-- Banner de cobro -->
                <div style='background: #FFC107; padding: 14px 20px; text-align: center;'>
                    <p style='color: #0A0A0A; margin: 0; font-size: 16px; font-weight: bold; letter-spacing: 0.5px;'>Nuevo Pago Pendiente</p>
                </div>
                <!-- Contenido principal -->
                <div style='padding: 30px 30px 20px;'>
                    <p style='font-size: 15px; color: #FFFFFF; margin: 0 0 15px;'>Hola <strong style='color: #FFC107;'>{$user->name}</strong>,</p>
                    <p style='font-size: 14px; color: #BBBBBB; line-height: 1.7; margin: 0 0 20px;'>Se le ha asignado un nuevo cobro por el siguiente concepto:</p>
                    <!-- Tabla de detalles -->
                    <div style='background: #111111; border-radius: 8px; padding: 20px; margin: 0 0 20px; border: 1px solid #222222;'>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Concepto</td>
                                <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;'>{$cobro->concepto}</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Monto</td>
                                <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 18px; color: #FFC107; border-bottom: 1px solid #222222;'>{$montoFormateado} CLP</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0; color: #888888; font-size: 11px;'></td>
                                <td style='padding: 4px 0; text-align: right; font-size: 11px; color: #888888;'>(Neto: $" . number_format($cobro->monto, 0, ',', '.') . " + IVA 19%)</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px 0; color: #888888; font-size: 13px;'>Fecha</td>
                                <td style='padding: 12px 0; font-weight: bold; text-align: right; font-size: 14px; color: #FFFFFF;'>" . now()->format('d/m/Y') . "</td>
                            </tr>
                        </table>
                    </div>
                    <!-- Botón de acción -->
                    <div style='text-align: center; margin: 25px 0;'>
                        <a href='" . url('/cliente/cobros-pendientes') . "' style='background: #FFC107; color: #0A0A0A; padding: 14px 30px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 15px; display: inline-block; letter-spacing: 0.5px;'>
                            Ver Cobros Pendientes
                        </a>
                    </div>
                    <!-- Datos de transferencia -->
                    <div style='background: #1A1A1A; border-left: 4px solid #FFC107; padding: 15px 20px; margin: 0 0 20px; border-radius: 0 6px 6px 0;'>
                        <p style='margin: 0 0 10px; font-weight: bold; color: #FFC107; font-size: 14px;'>Datos para transferencia:</p>
                        <p style='margin: 0; color: #AAAAAA; font-size: 13px; line-height: 1.8;'>
                            Banco: Banco Bci<br>
                            Tipo: Cuenta Corriente<br>
                            Nombre: Big Studio<br>
                            RUT: 78.153.109-K<br>
                            N&deg; Cuenta: 97580848<br>
                            Email: hola@bigstudio.cl
                        </p>
                    </div>
                    <p style='font-size: 12px; color: #666666; text-align: center; margin: 15px 0 0;'>Si tienes consultas, cont&aacute;ctanos a hola@bigstudio.cl o por WhatsApp.</p>
                </div>
                <!-- Separador dorado -->
                <div style='height: 2px; background: #FFC107; margin: 0 30px;'></div>
                <!-- Footer oscuro -->
                <div style='background: #0A0A0A; padding: 25px 30px; text-align: center; border-top: 1px solid #1A1A1A;'>
                    <p style='color: #FFFFFF; font-size: 13px; margin: 0 0 5px; font-weight: bold;'>Equipo Integraciones BigStudio</p>
                    <p style='color: #FFC107; font-size: 12px; margin: 0 0 5px;'>hola@bigstudio.cl</p>
                    <p style='color: #555555; font-size: 11px; margin: 12px 0 0;'>Este es un correo autom&aacute;tico. Si tienes consultas, cont&aacute;ctanos por el chat interno o responde a este correo.</p>
                </div>
            </div>";

            Mail::html($contenidoHtml, function ($message) use ($user, $asunto) {
                $message->to($user->email)
                    ->subject($asunto);
            });

        } catch (\Exception $e) {
            Log::error("Error enviando correo de cobro: " . $e->getMessage());
        }
    }

    /**
     * Emitir factura para cobro asignado via Lioren
     */
    private function emitirFacturaCobro(CobroAsignado $cobro)
    {
        try {
            $user = User::find($cobro->cliente_id);
            if (!$user) {
                Log::warning("CobroAsignado: No se encontró el usuario", ["cobro_id" => $cobro->id]);
                return;
            }

            // Buscar suscripcion activa del usuario
            $suscripcion = Suscripcion::where("user_id", $user->id)
                ->where("estado", "activa")
                ->first();

            if (!$suscripcion) {
                Log::info("CobroAsignado: Usuario sin suscripcion activa, no se emite factura", ["user_id" => $user->id]);
                return;
            }

            $plan = $suscripcion->plan;
            $concepto = $cobro->concepto ?: "Cobro Asignado";

            FacturaServicioEmitter::crearYEmitir(
                userId: $user->id,
                planId: $plan ? $plan->id : null,
                suscripcionId: $suscripcion->id,
                concepto: $concepto,
                periodoInicio: now()->toDateString(),
                periodoFin: now()->addDays(30)->toDateString()
            );

            Log::info("CobroAsignado: Factura emitida", ["cobro_id" => $cobro->id, "user_id" => $user->id]);
        } catch (\Exception $e) {
            Log::error("CobroAsignado: Error al emitir factura: " . $e->getMessage(), ["cobro_id" => $cobro->id]);
        }
    }

}
