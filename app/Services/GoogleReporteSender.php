<?php

namespace App\Services;

use App\Models\GoogleAdAccount;
use App\Models\GoogleAdInsight;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleReporteSender
{
    public function enviar(GoogleAdAccount $cuenta, ?string $periodo = null, ?array $destinos = null): bool
    {
        $emails = $destinos ?? (is_array($cuenta->reporte_emails) ? $cuenta->reporte_emails : []);
        if (empty($emails)) return false;

        if (empty($cuenta->reporte_token)) {
            $cuenta->reporte_token = Str::random(40);
            $cuenta->save();
        }
        $periodo = $periodo ?: now()->subMonthNoOverflow()->format('Y-m');

        $resumen = GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
            ->where('periodo', $periodo)->where('nivel', 'cuenta')->first();
        if (!$resumen) {
            try {
                (new GoogleAdsService())->syncAccount($cuenta, $periodo);
                $resumen = GoogleAdInsight::where('google_ad_account_id', $cuenta->id)
                    ->where('periodo', $periodo)->where('nivel', 'cuenta')->first();
            } catch (\Throwable $e) { Log::error('GoogleReporteSender sync: ' . $e->getMessage()); }
        }

        $link = url('/reporte-google/' . $cuenta->reporte_token . '?periodo=' . $periodo);
        $nombre = $cuenta->cliente->nombre ?? $cuenta->nombre_cuenta;
        try {
            $mesLabel = \Carbon\Carbon::createFromFormat('Y-m', $periodo)->locale('es')->isoFormat('MMMM YYYY');
        } catch (\Throwable $e) { $mesLabel = $periodo; }

        $inv = $resumen->inversion ?? 0;
        $ven = $resumen->ventas ?? 0;
        $roas = $inv > 0 ? round($ven / $inv, 2) : 0;

        $html = view('emails.agencia.google-reporte', [
            'nombre' => $nombre,
            'mesLabel' => ucfirst($mesLabel),
            'inversion' => $inv,
            'ventas' => $ven,
            'roas' => $roas,
            'compras' => $resumen->compras ?? 0,
            'link' => $link,
        ])->render();

        $asunto = 'Tu reporte Google Ads — ' . ucfirst($mesLabel) . ' | Big Studio';
        foreach ($emails as $email) {
            try {
                Mail::html($html, function ($m) use ($email, $asunto) {
                    $m->to($email)->subject($asunto)->from(config('mail.from.address'), 'Big Studio');
                });
            } catch (\Throwable $e) { Log::error('GoogleReporteSender envío ' . $email . ': ' . $e->getMessage()); }
        }
        $cuenta->reporte_ultimo_envio = now();
        $cuenta->save();
        Log::info('Reporte Google enviado', ['cuenta' => $cuenta->customer_id, 'emails' => $emails, 'periodo' => $periodo]);
        return true;
    }
}
