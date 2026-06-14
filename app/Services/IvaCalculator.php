<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cálculo del IVA mensual (F29) en un solo lugar, para que el controlador de Finanzas,
 * el comando de alertas y la campana de notificaciones usen EXACTAMENTE la misma fórmula
 * (la misma que FinanzasController::cerrarIva).
 *
 * Débito = Boletas + Facturas de venta + Facturas de agencia (cobradas) + Ingresos manuales − NC.
 * Crédito = Facturas de compra (pendiente|pagada) del período.
 * A pagar = max(0, Débito − Crédito − Remanente del mes anterior).
 */
class IvaCalculator
{
    private function adminIds(): array
    {
        return \App\Models\User::role('admin')->pluck('id')->toArray();
    }

    /**
     * @return array{debito:int,credito:int,remanente:int,a_pagar:int,remanente_siguiente:int}
     */
    public function paraPeriodo(int $mes, int $anio): array
    {
        $adminIds = $this->adminIds();
        $inicio = Carbon::create($anio, $mes, 1)->startOfDay();
        $fin = Carbon::create($anio, $mes, 1)->endOfMonth()->endOfDay();

        $debito = (float) DB::table('boletas')->where('status', 'emitida')->whereIn('user_id', $adminIds)
                ->whereBetween('created_at', [$inicio, $fin])->sum('monto_iva')
            + (float) DB::table('facturas_emitidas')->where('status', 'emitida')->whereIn('user_id', $adminIds)
                ->whereBetween('created_at', [$inicio, $fin])->sum('monto_iva')
            + (float) DB::table('agencia_cobros')->where('estado', 'pagado')->where('factura_estado', 'emitida')
                ->whereBetween('pagado_at', [$inicio, $fin])->sum(DB::raw('ROUND(monto * 0.19 / 1.19)'))
            + (float) DB::table('ingresos_manuales')
                ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])->sum('monto_iva');

        // Notas de crédito reducen el débito.
        $ivaNC = (float) DB::table('boletas')->where('status', 'emitida')->whereIn('user_id', $adminIds)
            ->whereIn('tipodoc', [61])->whereBetween('created_at', [$inicio, $fin])->sum('monto_iva');
        $debito -= $ivaNC;

        $credito = (float) DB::table('facturas_compra')->whereIn('estado', ['pendiente', 'pagada'])
            ->whereBetween('fecha_emision', [$inicio->toDateString(), $fin->toDateString()])->sum('monto_iva');

        $remanente = 0.0;
        $prevMes = $mes == 1 ? 12 : $mes - 1;
        $prevAnio = $mes == 1 ? $anio - 1 : $anio;
        $previo = DB::table('iva_mensual')->where('anio', $prevAnio)->where('mes', $prevMes)->first();
        if ($previo) {
            $remanente = (float) $previo->remanente_siguiente;
        }

        $aPagar = max(0, $debito - $credito - $remanente);
        $remanenteSig = max(0, ($credito + $remanente) - $debito);

        return [
            'debito' => (int) round($debito),
            'credito' => (int) round($credito),
            'remanente' => (int) round($remanente),
            'a_pagar' => (int) round($aPagar),
            'remanente_siguiente' => (int) round($remanenteSig),
        ];
    }

    /**
     * El IVA que se paga el día 20 del mes calendario indicado corresponde al MES ANTERIOR.
     * @return array{mes:int,anio:int}
     */
    public function periodoQueVenceEn(int $mesCalendario, int $anioCalendario): array
    {
        return [
            'mes' => $mesCalendario == 1 ? 12 : $mesCalendario - 1,
            'anio' => $mesCalendario == 1 ? $anioCalendario - 1 : $anioCalendario,
        ];
    }
}
