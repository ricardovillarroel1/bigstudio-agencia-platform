<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PagoTransferencia;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use App\Services\FacturaServicioEmitter;

class PagoTransferenciaController extends Controller
{
    /**
     * Listar pagos por transferencia (Admin)
     */
    public function index()
    {
        $transferencias = PagoTransferencia::with(['user', 'plan', 'revisor'])
            ->orderByRaw("FIELD(status, 'pendiente', 'aprobado', 'rechazado')")
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.transferencias.index', compact('transferencias'));
    }

    /**
     * Aprobar pago por transferencia
     */
    public function aprobar(Request $request, $id)
    {
        $transferencia = PagoTransferencia::findOrFail($id);

        if ($transferencia->status !== 'pendiente') {
            return back()->with('error', 'Este pago ya fue procesado.');
        }

        try {
            $transferencia->update([
                'status' => 'aprobado',
                'notas_admin' => $request->notas_admin,
                'revisado_por' => auth()->id(),
                'revisado_at' => now(),
            ]);

            // Crear o renovar suscripción (mismo flujo que Flow)
            $plan = $transferencia->plan;
            $user = $transferencia->user;
            $periodo = $transferencia->periodo;

            $diasDuracion = $periodo === 'anual' ? 365 : 30;
            $fechaInicio = now();
            $fechaFin = now()->addDays($diasDuracion);

            // Buscar suscripción activa existente
            $suscripcionExistente = Suscripcion::where('user_id', $user->id)
                ->where('estado', 'activa')
                ->first();

            if ($suscripcionExistente) {
                // Renovar: extender desde la fecha de fin actual
                $nuevaFechaFin = $suscripcionExistente->fecha_fin > now()
                    ? $suscripcionExistente->fecha_fin->addDays($diasDuracion)
                    : now()->addDays($diasDuracion);

                // FIX: reiniciar ciclo de documentos al renovar
                $suscripcionExistente->update([
                    'plan_id' => $plan->id,
                    'fecha_inicio' => now(), // ← reinicia el contador
                    'fecha_fin' => $nuevaFechaFin,
                    'proximo_pago' => $nuevaFechaFin,
                    'estado' => 'activa',
                    'pausada' => false,
                    'pausada_at' => null,
                    'motivo_pausa' => null,
                ]);

                $suscripcion = $suscripcionExistente;
                $suscripcion->resetReminders();
            } else {
                // Crear nueva suscripción
                $suscripcion = Suscripcion::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'estado' => 'activa',
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                ]);
            }

            // Crear registro de pago
            Payment::create([
                'user_id' => $user->id,
                'suscripcion_id' => $suscripcion->id,
                'flow_order' => 'TRANSFER_' . $transferencia->id,
                'amount' => $transferencia->monto,
                'currency' => 'CLP',
                'status' => 2, // Pagado
                'subject' => 'Pago por transferencia - ' . $plan->nombre . ' (' . $periodo . ')',
                'paid_at' => now(),
                'periodo_inicio' => $fechaInicio,
                'periodo_fin' => $fechaFin,
            ]);

            // Emitir factura de servicio via Lioren API
            $payment = Payment::where('flow_order', 'TRANSFER_' . $transferencia->id)->first();
            $conceptoFactura = 'Suscripción ' . $plan->nombre . ' (Transferencia - ' . ucfirst($periodo) . ')';

            FacturaServicioEmitter::crearYEmitir(
                userId: $user->id,
                planId: $plan->id,
                suscripcionId: $suscripcion->id,
                paymentId: $payment ? $payment->id : null,
                concepto: $conceptoFactura,
                periodo: $periodo,
                periodoInicio: $fechaInicio->toDateString(),
                periodoFin: $fechaFin->toDateString()
            );

            Log::channel('single')->info('Pago por transferencia APROBADO', [
                'pago_transferencia_id' => $transferencia->id,
                'user_id' => $user->id,
                'plan' => $plan->nombre,
                'monto' => $transferencia->monto,
                'aprobado_por' => auth()->id(),
            ]);

            return back()->with('success', 'Pago aprobado exitosamente. Se ha activado el plan y emitido la factura para el cliente.');
        } catch (\Exception $e) {
            Log::channel('single')->error('Error al aprobar pago por transferencia', [
                'error' => $e->getMessage(),
                'pago_transferencia_id' => $id,
            ]);

            return back()->with('error', 'Error al aprobar el pago: ' . $e->getMessage());
        }
    }

    /**
     * Rechazar pago por transferencia
     */
    public function rechazar(Request $request, $id)
    {
        $request->validate([
            'notas_admin' => 'required|string|max:500',
        ]);

        $transferencia = PagoTransferencia::findOrFail($id);

        if ($transferencia->status !== 'pendiente') {
            return back()->with('error', 'Este pago ya fue procesado.');
        }

        $transferencia->update([
            'status' => 'rechazado',
            'notas_admin' => $request->notas_admin,
            'revisado_por' => auth()->id(),
            'revisado_at' => now(),
        ]);

        Log::channel('single')->info('Pago por transferencia RECHAZADO', [
            'pago_transferencia_id' => $transferencia->id,
            'user_id' => $transferencia->user_id,
            'rechazado_por' => auth()->id(),
            'motivo' => $request->notas_admin,
        ]);

        return back()->with('success', 'Pago rechazado. Se ha notificado al cliente.');
    }
}
