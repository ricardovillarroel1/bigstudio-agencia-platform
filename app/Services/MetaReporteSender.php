<?php

namespace App\Services;

use App\Models\MetaAdAccount;
use App\Models\MetaAdInsight;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Envía el reporte mensual de Meta Ads al/los correos del cliente,
 * con un link público para verlo dinámico.
 */
class MetaReporteSender
{
    /**
     * Envía el reporte de una cuenta.
     * @param MetaAdAccount $cuenta
     * @param string|null $periodo  YYYY-MM. Si es null, usa el mes anterior.
     * @param array|null $destinos  Lista de correos. Si es null, usa los configurados en la cuenta.
     */
    public function enviar(MetaAdAccount $cuenta, ?string $periodo = null, ?array $destinos = null): bool
    {
        $emails = $destinos ?? (is_array($cuenta->reporte_emails) ? $cuenta->reporte_emails : []);
        if (empty($emails)) {
            return false;
        }

        // Asegurar token público
        if (empty($cuenta->reporte_token)) {
            $cuenta->reporte_token = Str::random(40);
            $cuenta->save();
        }

        // Período = mes anterior (el reporte del mes que cerró)
        $periodo = $periodo ?: now()->subMonthNoOverflow()->format('Y-m');

        // Asegurar que haya datos sincronizados de ese mes
        $resumen = MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
            ->where('periodo', $periodo)->where('nivel', 'cuenta')->first();
        if (!$resumen) {
            try {
                (new MetaAdsService())->syncAccount($cuenta, $periodo);
                $resumen = MetaAdInsight::where('meta_ad_account_id', $cuenta->id)
                    ->where('periodo', $periodo)->where('nivel', 'cuenta')->first();
            } catch (\Throwable $e) {
                Log::error('MetaReporteSender sync: ' . $e->getMessage());
            }
        }

        $link = url('/reporte-meta/' . $cuenta->reporte_token . '?periodo=' . $periodo);
        $nombre = $cuenta->cliente->nombre ?? $cuenta->nombre_cuenta;
        try {
            $mesLabel = \Carbon\Carbon::createFromFormat('Y-m', $periodo)->locale('es')->isoFormat('MMMM YYYY');
        } catch (\Throwable $e) {
            $mesLabel = $periodo;
        }

        $inv = $resumen->inversion ?? 0;
        $ven = $resumen->ventas ?? 0;
        $roas = $inv > 0 ? round($ven / $inv, 2) : 0;

        $html = view('emails.agencia.meta-reporte', [
            'nombre' => $nombre,
            'mesLabel' => ucfirst($mesLabel),
            'inversion' => $inv,
            'ventas' => $ven,
            'roas' => $roas,
            'compras' => $resumen->compras ?? 0,
            'link' => $link,
        ])->render();

        $asunto = 'Tu reporte de campañas — ' . ucfirst($mesLabel) . ' | Big Studio';

        foreach ($emails as $email) {
            try {
                Mail::html($html, function ($m) use ($email, $asunto) {
                    $m->to($email)->subject($asunto)->from(config('mail.from.address'), 'Big Studio');
                });
            } catch (\Throwable $e) {
                Log::error('MetaReporteSender envío a ' . $email . ': ' . $e->getMessage());
            }
        }

        $cuenta->reporte_ultimo_envio = now();
        $cuenta->save();

        Log::info('Reporte Meta enviado', ['cuenta' => $cuenta->act_id, 'emails' => $emails, 'periodo' => $periodo]);
        return true;
    }
}
