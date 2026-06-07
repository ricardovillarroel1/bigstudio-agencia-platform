<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Suscripcion;
use App\Models\Plan;
use App\Models\User;
use App\Models\Payment;
use App\Models\FacturaServicio;
use App\Models\IntegracionConfig;
use Illuminate\Support\Facades\Log;
use App\Services\FacturaServicioEmitter;

class AdminSuscripcionController extends Controller
{
    /**
     * Mostrar todas las suscripciones con estadísticas
     */
    public function index()
    {
        $suscripciones = Suscripcion::with(['user', 'plan'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $estadisticas = [
            'activas' => Suscripcion::where('estado', 'activa')->count(),
            'vencidas' => Suscripcion::where('estado', 'vencida')->count(),
            'canceladas' => Suscripcion::where('estado', 'cancelada')->count(),
            'manuales' => Suscripcion::where('origen', 'manual')->count(),
            'gratis' => Suscripcion::where('estado', 'activa')
                ->whereHas('plan', function($q) { $q->where('precio', 0); })
                ->count(),
        ];

        $usuarios = User::role('cliente')
            ->orderBy('name')
            ->get();

        $planes = Plan::where('activo', true)->get();

        return view('admin.suscripciones.index', compact('suscripciones', 'estadisticas', 'usuarios', 'planes'));
    }

    /**
     * Crear suscripción manual desde el admin
     */
    public function crearManual(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:planes,id',
            'duracion_dias' => 'required|integer|min:1|max:36500',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $user = User::findOrFail($request->user_id);

        // Si es plan gratis, forzar duración indefinida (100 años)
        $duracionDias = $plan->precio == 0 ? 36500 : $request->duracion_dias;

        // Verificar si ya tiene suscripción activa
        $existente = Suscripcion::where('user_id', $request->user_id)
            ->where('estado', 'activa')
            ->first();

        if ($existente) {
            return redirect()->back()->with('error', "El usuario {$user->name} ya tiene una suscripción activa.");
        }

        $suscripcion = Suscripcion::create([
            'user_id' => $request->user_id,
            'plan_id' => $request->plan_id,
            'estado' => 'activa',
            'origen' => 'manual',
            'fecha_inicio' => now(),
            'proximo_pago' => now()->addDays($duracionDias),
            'fecha_fin' => now()->addDays($duracionDias),
        ]);

        // Crear factura de servicio y emitir DTE via Lioren (solo si plan no es gratis)
        if ($plan->precio > 0) {
            $conceptoFactura = 'Suscripción ' . $plan->nombre . ' (Ingreso Manual por Admin)';
            FacturaServicioEmitter::crearYEmitir(
                userId: $request->user_id,
                planId: $request->plan_id,
                suscripcionId: $suscripcion->id,
                concepto: $conceptoFactura,
                periodoInicio: now()->toDateString(),
                periodoFin: now()->addDays($duracionDias)->toDateString()
            );
        } else {
            // Plan gratis: solo crear registro sin emitir DTE
            FacturaServicio::create([
                'user_id' => $request->user_id,
                'suscripcion_id' => $suscripcion->id,
                'plan_id' => $request->plan_id,
                'numero_factura' => 'FS-' . str_pad(FacturaServicio::max('id') + 1, 6, '0', STR_PAD_LEFT),
                'concepto' => 'Suscripción ' . $plan->nombre . ' (Plan Gratis - Ingreso Manual)',
                'monto' => 0,
                'moneda' => 'CLP',
                'periodo_inicio' => now()->toDateString(),
                'periodo_fin' => now()->addDays($duracionDias)->toDateString(),
                'estado' => 'pagada',
            ]);
        }

        Log::info("Suscripción manual creada por admin", [
            'user_id' => $request->user_id,
            'plan_id' => $request->plan_id,
            'suscripcion_id' => $suscripcion->id,
            'plan_gratis' => $plan->precio == 0,
        ]);

        $mensaje = $plan->precio == 0
            ? "Suscripción GRATIS creada para {$user->name} con el plan {$plan->nombre}."
            : "Suscripción manual creada para {$user->name} con el plan {$plan->nombre}.";

        return redirect()->back()->with('success', $mensaje);
    }

    /**
     * Cancelar suscripción (admin o cliente)
     */
    public function cancelar(Suscripcion $suscripcion)
    {
        $suscripcion->update(['estado' => 'cancelada']);

        // Desactivar integración si existe
        IntegracionConfig::where('user_id', $suscripcion->user_id)
            ->update(['activo' => false]);

        Log::info("Suscripción cancelada por admin", [
            'suscripcion_id' => $suscripcion->id,
            'user_id' => $suscripcion->user_id,
        ]);

        return redirect()->back()->with('success', 'Suscripción cancelada exitosamente.');
    }

    /**
     * Reactivar suscripción
     */
    public function reactivar(Suscripcion $suscripcion)
    {
        $plan = $suscripcion->plan;
        $duracionDias = ($plan && $plan->precio == 0) ? 36500 : 30;

        $suscripcion->update([
            'estado' => 'activa',
            'fecha_inicio' => now(),
            'proximo_pago' => now()->addDays($duracionDias),
            'fecha_fin' => now()->addDays($duracionDias),
        ]);

        Log::info("Suscripción reactivada por admin", [
            'suscripcion_id' => $suscripcion->id,
            'user_id' => $suscripcion->user_id,
        ]);

        $suscripcion->resetReminders();

        return redirect()->back()->with('success', 'Suscripción reactivada exitosamente.');
    }

    /**
     * Renovar suscripción manualmente (pago por transferencia)
     * Extiende la suscripción activa o reactivar una vencida/cancelada por X días
     */
    public function renovarManual(Request $request, Suscripcion $suscripcion)
    {
        $request->validate([
            'duracion_dias' => 'required|integer|min:1|max:365',
            'motivo' => 'sometimes|string|max:500',
        ]);

        $user = $suscripcion->user;
        $plan = $suscripcion->plan;
        $duracionDias = $request->duracion_dias;
        $motivo = $request->motivo ?? 'Pago por transferencia';

        try {
            \DB::beginTransaction();

            if ($suscripcion->estado === 'activa') {
                // Suscripción activa: extender desde la fecha_fin actual
                $nuevaFechaFin = $suscripcion->fecha_fin > now()
                    ? $suscripcion->fecha_fin->copy()->addDays($duracionDias)
                    : now()->addDays($duracionDias);

                $suscripcion->update([
                    'fecha_inicio' => now(), 'fecha_fin' => $nuevaFechaFin,
                    'proximo_pago' => $nuevaFechaFin,
                ]);

                $fechaInicio = $suscripcion->fecha_fin->copy()->subDays($duracionDias);
                $fechaFin = $nuevaFechaFin;

            } else {
                // Suscripción vencida o cancelada: reactivar desde hoy
                $fechaInicio = now();
                $fechaFin = now()->addDays($duracionDias);

                $suscripcion->update([
                    'estado' => 'activa',
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'proximo_pago' => $fechaFin,
                ]);
            }

            $suscripcion->resetReminders();

            // Crear registro de pago
            $payment = Payment::create([
                'user_id' => $user->id,
                'suscripcion_id' => $suscripcion->id,
                'order_id' => 'MANUAL-TRANSFER-' . uniqid(),
                'amount' => $plan->precio ?? 0,
                'currency' => $plan->moneda ?? 'CLP',
                'email' => $user->email ?? '',
                'payment_method' => 9, // 9 = Transferencia/Manual
                'status' => 2, // Pagado
                'subject' => "Renovación manual ({$motivo}) - {$plan->nombre}",
                'paid_at' => now(),
                'periodo_inicio' => $fechaInicio,
                'periodo_fin' => $fechaFin,
            ]);

            // FIX: NO emitir factura en renovación manual - solo extender fecha
            // El cliente ya tiene factura pendiente o pagada
            // $conceptoFactura = 'Suscripción ' . $plan->nombre . ' (Renovación Manual - ' . $motivo . ')';
            // FacturaServicioEmitter::crearYEmitir(
            // userId: $user->id,
            // planId: $plan->id,
            // suscripcionId: $suscripcion->id,
            // paymentId: $payment->id,
            // concepto: $conceptoFactura,
            // periodoInicio: $fechaInicio->toDateString(),
            // periodoFin: $fechaFin->toDateString()
            // );
            // }

            \DB::commit();

            Log::info("Suscripción renovada manualmente por admin (transferencia)", [
                'suscripcion_id' => $suscripcion->id,
                'user_id' => $user->id,
                'plan' => $plan->nombre ?? 'N/A',
                'duracion_dias' => $duracionDias,
                'motivo' => $motivo,
                'nueva_fecha_fin' => $suscripcion->fecha_fin,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->back()->with('success', 
                "Suscripción de {$user->name} renovada exitosamente por {$duracionDias} días. " .
                "Nueva fecha de vencimiento: {$suscripcion->fresh()->fecha_fin->format('d/m/Y')}."
            );

        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error("Error al renovar suscripción manualmente", [
                'error' => $e->getMessage(),
                'suscripcion_id' => $suscripcion->id,
            ]);

            return redirect()->back()->with('error', 'Error al renovar la suscripción: ' . $e->getMessage());
        }
    }
}
