<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\Suscripcion;
use App\Models\Plan;
use App\Services\FacturaServicioEmitter;
use Illuminate\Support\Facades\Cache;

class FlowController extends Controller
{
    private $apiKey;
    private $secretKey;
    private $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('flow.api_key');
        $this->secretKey = config('flow.secret_key');
        $this->apiUrl = config('flow.api_url');
    }

    /**
     * Mostrar formulario de pago
     */
    public function showPaymentForm()
    {
        return view('flow.payment-form');
    }

    /**
     * Crear orden de pago en Flow
     */
    public function createPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:350',
            'subject' => 'required|string|max:255',
        ]);

        // Parametros requeridos por Flow
        $params = [
            'apiKey' => $this->apiKey,
            'commerceOrder' => uniqid('ORDER-'),
            'subject' => $request->subject,
            'currency' => 'CLP',
            'amount' => $request->amount,
            'email' => auth()->user()->email ?? 'cliente@example.com',
            'urlConfirmation' => route('flow.confirmation'),
            'urlReturn' => route('flow.return'),
            'optional' => json_encode([
                'user_id' => auth()->id(),
                'custom_data' => 'datos adicionales'
            ]),
        ];

        // Firmar parametros
        $params['s'] = $this->signParams($params);

        try {
            // Llamar a Flow API
            $response = Http::withoutVerifying()->asForm()->post("{$this->apiUrl}/payment/create", $params);

            if ($response->successful()) {
                $data = $response->json();
                $checkoutUrl = $data['url'] . '?token=' . $data['token'];
                return redirect($checkoutUrl);
            }

            return back()->withErrors(['error' => 'Error al crear el pago: ' . $response->body()]);
        } catch (\Exception $e) {
            Log::error('Error Flow createPayment: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al procesar el pago']);
        }
    }

    /**
     * Flow redirige aqui despues del pago (urlReturn)
     * El usuario vuelve a esta URL tras completar el pago en Flow
     */
    public function returnFromFlow(Request $request)
    {
        $token = $request->get('token');

        if (!$token) {
            return redirect('/')->withErrors(['error' => 'Token no valido']);
        }

        // Obtener estado del pago
        $paymentStatus = $this->getPaymentStatus($token);

        if ($paymentStatus && isset($paymentStatus['status']) && $paymentStatus['status'] == 2) {
            // Pago exitoso - verificar si la suscripcion ya fue creada por el webhook
            // Si no, crearla aqui como respaldo
            $this->ensurePaymentProcessed($paymentStatus, $token);

            return view('flow.success', ['payment' => $paymentStatus]);
        }

        // Pago fallido o pendiente
        return view('flow.failed', ['payment' => $paymentStatus ?? []]);
    }

    /**
     * Asegurar que el pago fue procesado correctamente.
     * Sirve como respaldo si el webhook de confirmacion no llego o fallo.
     */
    private function ensurePaymentProcessed($paymentStatus, $token)
    {
        try {
            // Verificar si ya existe un payment con este token
            $existingPayment = Payment::where('flow_token', $token)->first();

            if ($existingPayment && $existingPayment->suscripcion_id) {
                // Ya fue procesado correctamente por el webhook
                Log::info('Pago ya procesado por webhook', ['payment_id' => $existingPayment->id]);
                return;
            }

            // Extraer plan_id, user_id y periodo del commerceOrder o del optional
            $planId = null;
            $userId = null;
            $periodo = 'mensual';

            // Intentar extraer del optional
            $optional = $paymentStatus['optional'] ?? '';
            if (!empty($optional)) {
                parse_str(str_replace(['|', ':'], ['&', '='], $optional), $optionalData);
                $planId = $optionalData['plan_id'] ?? null;
                $userId = $optionalData['user_id'] ?? null;
                $periodo = $optionalData['periodo'] ?? 'mensual';
            }

            // Si optional esta vacio, intentar extraer del commerceOrder
            if (!$planId || !$userId) {
                $commerceOrder = $paymentStatus['commerceOrder'] ?? '';
                // Si el commerceOrder empieza con PLAN-, buscar el plan_id del usuario autenticado
                if (str_starts_with($commerceOrder, 'PLAN-') && auth()->check()) {
                    $userId = auth()->id();
                    // Buscar el plan mas reciente del subject
                    $subject = $paymentStatus['subject'] ?? '';
                    if (preg_match('/Plan (.+) - (.+)/', $subject, $matches)) {
                        $planName = $matches[1];
                        $plan = Plan::where('nombre', $planName)->first();
                        if ($plan) {
                            $planId = $plan->id;
                        }
                    }
                    // Si no se encontro por nombre, buscar la solicitud pendiente del usuario
                    if (!$planId) {
                        $solicitud = \App\Models\Solicitud::where('user_id', $userId)
                            ->whereIn('estado', ['pagada', 'pendiente_credenciales', 'aprobada'])
                            ->latest()
                            ->first();
                        if ($solicitud) {
                            $planId = $solicitud->plan_id;
                        }
                    }
                }
            }

            Log::info('ensurePaymentProcessed', [
                'token' => $token,
                'planId' => $planId,
                'userId' => $userId,
                'existingPayment' => $existingPayment ? $existingPayment->id : null,
            ]);

            if ($existingPayment) {
                // El payment existe pero sin suscripcion - actualizarlo
                if (!$existingPayment->user_id && $userId) {
                    $existingPayment->update(['user_id' => $userId]);
                }
                if ($planId && $userId && !$existingPayment->suscripcion_id) {
                    $this->crearORenovarSuscripcion($userId, $planId, $existingPayment, $periodo);
                    Log::info('Suscripcion creada desde returnFromFlow (respaldo)', [
                        'payment_id' => $existingPayment->id,
                        'periodo' => $periodo,
                    ]);
                }
            } else {
                // No existe el payment - crearlo
                $payment = Payment::create([
                    'order_id' => $paymentStatus['commerceOrder'],
                    'flow_token' => $token,
                    'subject' => $paymentStatus['subject'],
                    'amount' => $paymentStatus['amount'],
                    'currency' => $paymentStatus['currency'],
                    'email' => $paymentStatus['payer'],
                    'payment_method' => $paymentStatus['paymentMethod'] ?? 0,
                    'status' => $paymentStatus['status'],
                    'flow_response' => $paymentStatus,
                    'paid_at' => now(),
                    'user_id' => $userId,
                ]);

                if ($planId && $userId) {
                    $this->crearORenovarSuscripcion($userId, $planId, $payment, $periodo);
                    Log::info('Pago y suscripcion creados desde returnFromFlow', [
                        'payment_id' => $payment->id,
                        'periodo' => $periodo,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error en ensurePaymentProcessed: ' . $e->getMessage());
        }
    }

    /**
     * Webhook de confirmacion (Flow llama aqui en segundo plano)
     */
    public function confirmationWebhook(Request $request)
    {
        $token = $request->get('token');

        if (!$token) {
            return response('Token invalido', 400);
        }

        // Obtener estado del pago
        $paymentStatus = $this->getPaymentStatus($token);

        if ($paymentStatus && $paymentStatus['status'] == 2) {
            // Extraer datos del optional
            $optional = $paymentStatus['optional'] ?? '';
            $planId = null;
            $userId = null;
            $periodo = 'mensual';

            if (!empty($optional)) {
                parse_str(str_replace(['|', ':'], ['&', '='], $optional), $optionalData);
                $planId = $optionalData['plan_id'] ?? null;
                $userId = $optionalData['user_id'] ?? null;
                $periodo = $optionalData['periodo'] ?? 'mensual';
            }

            // Fallback: extraer del commerceOrder si optional esta vacio
            if ((!$planId || !$userId) && str_starts_with($paymentStatus['commerceOrder'] ?? '', 'PLAN-')) {
                $subject = $paymentStatus['subject'] ?? '';
                if (preg_match('/Plan (.+) - (.+)/', $subject, $matches)) {
                    $planName = $matches[1];
                    $plan = Plan::where('nombre', $planName)->first();
                    if ($plan) {
                        $planId = $planId ?? $plan->id;
                    }
                }
                // Buscar usuario por email del pago
                if (!$userId) {
                    $payer = $paymentStatus['payer'] ?? '';
                    $user = \App\Models\User::where('email', $payer)->first();
                    if ($user) {
                        $userId = $user->id;
                    }
                }
            }

            // Verificar si ya existe un payment con este token (evitar duplicados)
            $existingPayment = Payment::where('flow_token', $token)->first();

            if (!$existingPayment) {
                // Guardar pago en BD
                $payment = Payment::create([
                    'order_id' => $paymentStatus['commerceOrder'],
                    'flow_token' => $token,
                    'subject' => $paymentStatus['subject'],
                    'amount' => $paymentStatus['amount'],
                    'currency' => $paymentStatus['currency'],
                    'email' => $paymentStatus['payer'],
                    'payment_method' => $paymentStatus['paymentMethod'] ?? 0,
                    'status' => $paymentStatus['status'],
                    'flow_response' => $paymentStatus,
                    'paid_at' => now(),
                    'user_id' => $userId,
                ]);

                // Si es pago de plan, crear o renovar suscripcion
                if ($planId && $userId) {
                    $this->crearORenovarSuscripcion($userId, $planId, $payment, $periodo);
                }

                Log::info('Pago confirmado via webhook', [
                    'payment_id' => $payment->id,
                    'plan_id' => $planId,
                    'user_id' => $userId,
                    'periodo' => $periodo,
                ]);
            } else {
                // Ya existe, actualizar si falta informacion
                if (!$existingPayment->user_id && $userId) {
                    $existingPayment->update(['user_id' => $userId]);
                }
                if ($planId && $userId && !$existingPayment->suscripcion_id) {
                    $this->crearORenovarSuscripcion($userId, $planId, $existingPayment, $periodo);
                }
                Log::info('Pago ya existia, actualizado via webhook', [
                    'payment_id' => $existingPayment->id,
                    'periodo' => $periodo,
                ]);
            }

            Log::info('Pago confirmado', $paymentStatus);
        }

        // IMPORTANTE: Devolver HTTP 200
        return response('OK', 200);
    }

    /**
     * Crear o renovar suscripcion
     */
    private function crearORenovarSuscripcion($userId, $planId, Payment $payment, $periodo = 'mensual')
    {
        // Determinar duracion segun periodo (anual = 365 dias, mensual = 30 dias)
        $esAnual = ($periodo === 'anual');
        $diasDuracion = $esAnual ? 365 : 30;

        $fechaInicio = now();
        $fechaFin = now()->addDays($diasDuracion);
        $proximoPago = $fechaFin->copy();

        // Buscar suscripcion activa del usuario para este plan
        $suscripcion = Suscripcion::where('user_id', $userId)
            ->where('plan_id', $planId)
            ->where('estado', 'activa')
            ->first();

        if ($suscripcion) {
            // Renovar: extender dias desde la fecha_fin actual
            $fechaInicio = $suscripcion->fecha_fin;
            $fechaFin = $fechaInicio->copy()->addDays($diasDuracion);
            $proximoPago = $fechaFin->copy();

            $suscripcion->update([
                'fecha_inicio' => now(), 'fecha_fin' => $fechaFin,
                'proximo_pago' => $proximoPago,
                'estado' => 'activa',
            ]);

            Log::info("Suscripcion renovada", ['suscripcion_id' => $suscripcion->id]);
            $suscripcion->resetReminders();
        } else {
            // Buscar suscripcion cancelada del usuario para reactivarla
            $suscripcionCancelada = Suscripcion::where('user_id', $userId)
                ->where('plan_id', $planId)
                ->where('estado', 'cancelada')
                ->first();

            if ($suscripcionCancelada) {
                $suscripcionCancelada->update([
                    'estado' => 'activa',
                    'fecha_inicio' => $fechaInicio,
                    'fecha_inicio' => now(), 'fecha_fin' => $fechaFin,
                    'proximo_pago' => $proximoPago,
                ]);
                $suscripcion = $suscripcionCancelada;
                Log::info("Suscripcion reactivada", ['suscripcion_id' => $suscripcion->id]);
                $suscripcion->resetReminders();
            } else {
                // Crear nueva suscripcion
                $suscripcion = Suscripcion::create([
                    'user_id' => $userId,
                    'plan_id' => $planId,
                    'estado' => 'activa',
                    'fecha_inicio' => $fechaInicio,
                    'fecha_inicio' => now(), 'fecha_fin' => $fechaFin,
                    'proximo_pago' => $proximoPago,
                ]);
                Log::info("Nueva suscripcion creada", ['suscripcion_id' => $suscripcion->id]);
                $suscripcion->resetReminders();
            }
        }

        // Actualizar payment con datos de suscripcion
        $payment->update([
            'suscripcion_id' => $suscripcion->id,
            'periodo_inicio' => $fechaInicio,
            'periodo_fin' => $fechaFin,
        ]);

        // Actualizar la solicitud del usuario a estado 'pagada' si existe
        $solicitud = \App\Models\Solicitud::where('user_id', $userId)
            ->where('plan_id', $planId)
            ->whereIn('estado', ['aprobada', 'pendiente_pago'])
            ->latest()
            ->first();

        if ($solicitud) {
            $solicitud->update(['estado' => 'pagada']);
            Log::info("Solicitud actualizada a pagada", ['solicitud_id' => $solicitud->id]);
        }

        // Emitir factura de servicio via Lioren API
        $plan = Plan::find($planId);
        $conceptoFactura = $plan
            ? 'Suscripción ' . $plan->nombre . ' (Pago Flow - ' . ucfirst($periodo) . ')'
            : 'Suscripción (Pago Flow)';

        FacturaServicioEmitter::crearYEmitir(
            userId: $userId,
            planId: $planId,
            suscripcionId: $suscripcion->id,
            paymentId: $payment->id,
            concepto: $conceptoFactura,
            periodo: $periodo,
            periodoInicio: $fechaInicio->toDateString(),
            periodoFin: $fechaFin->toDateString()
        );
    }

    /**
     * Obtener estado del pago desde Flow
     */
    private function getPaymentStatus($token)
    {
        $params = [
            'apiKey' => $this->apiKey,
            'token' => $token,
        ];

        $params['s'] = $this->signParams($params);

        try {
            $response = Http::withoutVerifying()->get("{$this->apiUrl}/payment/getStatus", $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Error getStatus: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Error Flow getStatus: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Firmar parametros con HMAC SHA256
     */
    private function signParams(array $params)
    {
        // Ordenar alfabeticamente
        ksort($params);

        // Concatenar parametros
        $toSign = '';
        foreach ($params as $key => $value) {
            $toSign .= $key . $value;
        }

        // Generar firma HMAC SHA256
        return hash_hmac('sha256', $toSign, $this->secretKey);
    }

    /**
     * Crear un pago para un plan especifico
     */
    public function createPlanPayment(Request $request)
    {
        Log::info('createPlanPayment iniciado', ['request_data' => $request->all()]);

        $request->validate([
            'plan_id' => 'required|numeric',
            'periodo' => 'sometimes|string|in:mensual,anual',
        ]);

        // Obtener plan desde la base de datos
        $plan = \App\Models\Plan::with('empresa')->find($request->plan_id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan no encontrado'
            ], 404);
        }

        // Determinar si es pago anual o mensual
        $periodo = $request->input('periodo', 'mensual');
        $esAnual = ($periodo === 'anual' && $plan->plan_anual_activo && $plan->precio_anual > 0);

        // Usar precio anual o mensual segun corresponda
        // FIX: Convertir UF a CLP con IVA antes de enviar a Flow
        $valorUF = $this->obtenerValorUF();
        $precioUF = $esAnual ? $plan->precio_anual : $plan->precio;
        // Convertir a CLP con IVA incluido
        if ($plan->moneda === 'UF' && $valorUF) {
            $precio = round($precioUF * $valorUF * 1.19);
        } else {
            $precio = $precioUF;
        }
        $periodoLabel = $esAnual ? 'Anual' : 'Mensual';

        // Obtener email del usuario autenticado
        $userEmail = auth()->user()->email ?? 'cliente@example.com';

        // Parametros requeridos por Flow
        $params = [
            'apiKey' => $this->apiKey,
            'commerceOrder' => uniqid('PLAN-'),
            'subject' => 'Plan ' . $plan->nombre . ' (' . $periodoLabel . ') - ' . $plan->empresa->nombre,
            'currency' => 'CLP', // Siempre enviar en CLP a Flow
            'amount' => $precio,
            'email' => $userEmail,
            'urlConfirmation' => route('flow.confirmation'),
            'urlReturn' => route('flow.return'),
            'optional' => 'plan_id:' . $plan->id . '|user_id:' . auth()->id() . '|periodo:' . $periodo,
        ];

        // Firmar parametros
        $params['s'] = $this->signParams($params);

        Log::info('Datos para Flow', ['payment_data' => $params, 'periodo' => $periodo, 'precio_usado' => $precio]);

        try {
            // Llamar a Flow API
            $response = Http::withoutVerifying()->asForm()->post("{$this->apiUrl}/payment/create", $params);

            Log::info('Respuesta de Flow', ['response' => $response->json()]);

            if ($response->successful()) {
                $data = $response->json();

                // Construir URL de checkout
                $checkoutUrl = $data['url'] . '?token=' . $data['token'];

                return response()->json([
                    'success' => true,
                    'data' => $data,
                    'redirect_url' => $checkoutUrl,
                    'plan_data' => [
                        'id' => $plan->id,
                        'nombre' => $plan->nombre,
                        'empresa' => $plan->empresa->nombre,
                        'precio' => $precio,
                        'moneda' => $plan->moneda,
                        'periodo' => $periodo
                    ]
                ]);
            }

            Log::error('Error de Flow', ['error' => $response->body()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pago: ' . $response->body()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Flow Service Exception', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error de conexion con Flow: ' . $e->getMessage()
            ], 500);
        }
    }

    private function obtenerValorUF(): ?float
    {
        return Cache::remember('valor_uf_actual', 60 * 60 * 6, function () {
            try {
                $dbValue = \DB::table('system_settings')->where('key', 'valor_uf')->value('value');
                if ($dbValue && (float) $dbValue > 0) {
                    return (float) $dbValue;
                }
            } catch (\Exception $e) {
                Log::warning('No se pudo leer UF desde DB: ' . $e->getMessage());
            }
            try {
                $response = Http::timeout(15)->get('https://mindicador.cl/api/uf');
                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['serie'][0]['valor'])) {
                        return (float) $data['serie'][0]['valor'];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('No se pudo obtener UF desde API: ' . $e->getMessage());
            }
            return 39841.72; // Valor por defecto
        });
    }
}
