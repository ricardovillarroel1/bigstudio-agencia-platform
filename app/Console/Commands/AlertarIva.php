<?php

namespace App\Console\Commands;

use App\Services\IvaCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Recordatorio de pago de IVA (F29). El IVA se paga el día 20 de cada mes y corresponde
 * al mes ANTERIOR. Envía correo a los administradores 5 días antes (día 15) y el día del
 * pago (día 20). No reenvía si ya se mandó ese aviso, ni si el período ya está pagado.
 */
class AlertarIva extends Command
{
    protected $signature = 'finanzas:alertar-iva {--force= : Forzar tipo de aviso: previa|dia} {--test : Envía un correo de muestra sin alterar el estado (no marca como enviado)}';
    protected $description = 'Avisa por correo el monto de IVA a pagar 5 días antes y el día 20 (día de pago).';

    /** Día en que se paga el IVA. */
    private const DIA_PAGO = 20;
    /** Días de anticipación del primer aviso. */
    private const DIAS_ANTES = 5;

    public function handle(IvaCalculator $iva): int
    {
        $hoy = now();
        $forzar = $this->option('force');
        $esPrueba = (bool) $this->option('test');

        // ¿Qué aviso corresponde hoy?
        $tipo = null;
        if ($forzar === 'previa' || $forzar === 'dia') {
            $tipo = $forzar;
        } elseif ($hoy->day === self::DIA_PAGO - self::DIAS_ANTES) {
            $tipo = 'previa';
        } elseif ($hoy->day === self::DIA_PAGO) {
            $tipo = 'dia';
        }
        if ($esPrueba && !$tipo) {
            $tipo = 'previa'; // muestra por defecto
        }

        if (!$tipo) {
            $this->info("Hoy ({$hoy->day}) no corresponde aviso de IVA. Avisos: día " . (self::DIA_PAGO - self::DIAS_ANTES) . " y día " . self::DIA_PAGO . '.');
            return 0;
        }

        // El IVA que se paga este mes es el del MES ANTERIOR.
        $periodo = $iva->periodoQueVenceEn($hoy->month, $hoy->year);
        $mes = $periodo['mes'];
        $anio = $periodo['anio'];

        $registro = DB::table('iva_mensual')->where('anio', $anio)->where('mes', $mes)->first();

        if (!$esPrueba) {
            if ($registro && $registro->pagado_at) {
                $this->info("IVA de {$mes}/{$anio} ya registrado como pagado. No se envía aviso.");
                return 0;
            }

            // Dedupe: no reenviar el mismo aviso del mismo período.
            if ($registro) {
                if ($tipo === 'previa' && $registro->recordatorio_previo_at) {
                    $this->info("Aviso 'previa' de {$mes}/{$anio} ya enviado.");
                    return 0;
                }
                if ($tipo === 'dia' && $registro->recordatorio_dia_at) {
                    $this->info("Aviso 'dia' de {$mes}/{$anio} ya enviado.");
                    return 0;
                }
            }
        }

        $calc = $iva->paraPeriodo($mes, $anio);
        if (!$esPrueba && $calc['a_pagar'] <= 0) {
            $this->info("IVA de {$mes}/{$anio} a pagar es \$0 (remanente o sin movimiento). No se envía aviso.");
            // Igual dejamos snapshot para no recalcular y para el historial.
            $this->snapshot($mes, $anio, $calc, $tipo);
            return 0;
        }

        $vence = Carbon::create($hoy->year, $hoy->month, self::DIA_PAGO)->endOfDay();
        $emails = \App\Models\User::role('admin')->pluck('email')->filter()->unique()->values()->all();

        if (empty($emails)) {
            $this->warn('No hay correos de administradores para notificar.');
            return 0;
        }

        $enviados = 0;
        foreach ($emails as $email) {
            try {
                $this->enviarCorreo($email, $mes, $anio, $calc, $tipo, $vence, $esPrueba);
                $enviados++;
            } catch (\Throwable $e) {
                Log::error('AlertarIva: error enviando a ' . $email . ': ' . $e->getMessage());
                $this->error("Error enviando a {$email}: " . $e->getMessage());
            }
        }

        if (!$esPrueba) {
            $this->snapshot($mes, $anio, $calc, $tipo);
        }

        $etq = $esPrueba ? ' [PRUEBA]' : '';
        $this->info("Aviso '{$tipo}'{$etq} de IVA {$mes}/{$anio} (\${$calc['a_pagar']}) enviado a {$enviados} destinatario(s).");
        Log::info("AlertarIva: aviso {$tipo}{$etq} IVA {$mes}/{$anio} monto {$calc['a_pagar']} -> {$enviados} correos.");
        return 0;
    }

    /** Guarda/actualiza el snapshot del período y marca el aviso como enviado. */
    private function snapshot(int $mes, int $anio, array $calc, string $tipo): void
    {
        $data = [
            'debito_fiscal' => $calc['debito'],
            'credito_fiscal' => $calc['credito'],
            'remanente_anterior' => $calc['remanente'],
            'iva_a_pagar' => $calc['a_pagar'],
            'remanente_siguiente' => $calc['remanente_siguiente'],
            'updated_at' => now(),
        ];
        if ($tipo === 'previa') {
            $data['recordatorio_previo_at'] = now();
        } elseif ($tipo === 'dia') {
            $data['recordatorio_dia_at'] = now();
        }
        DB::table('iva_mensual')->updateOrInsert(['anio' => $anio, 'mes' => $mes], $data);
    }

    private function enviarCorreo(string $email, int $mes, int $anio, array $calc, string $tipo, Carbon $vence, bool $esPrueba = false): void
    {
        $mesNombre = Carbon::create($anio, $mes, 1)->translatedFormat('F Y');
        $monto = number_format($calc['a_pagar'], 0, ',', '.');
        $venceTxt = $vence->translatedFormat('d \d\e F');
        $diasRestantes = now()->startOfDay()->diffInDays($vence->copy()->startOfDay(), false);

        if ($tipo === 'previa') {
            $asunto = "Recordatorio: tu IVA de {$mesNombre} vence el {$venceTxt} — \${$monto}";
            $tituloBanner = 'Tu IVA vence en ' . max(0, $diasRestantes) . ' día' . (max(0, $diasRestantes) === 1 ? '' : 's');
            $bannerBg = '#FF9800';
        } else {
            $asunto = "HOY se paga tu IVA de {$mesNombre} — \${$monto}";
            $tituloBanner = 'HOY es el último día para pagar tu IVA';
            $bannerBg = '#FF5252';
        }
        if ($esPrueba) {
            $asunto = '[PRUEBA] ' . $asunto;
        }

        $urlIva = rtrim(config('app.url'), '/') . '/finanzas/iva?mes=' . $mes . '&anio=' . $anio;

        $html = "
        <div style='font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #0A0A0A;'>
            <div style='background: #0A0A0A; padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid #1A1A1A;'>
                <h1 style='color: #FFFFFF; margin: 0 0 4px; font-size: 20px; font-weight: bold; letter-spacing: 2px;'>FINANZAS</h1>
                <h2 style='color: #FFC107; margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 3px;'>BIG STUDIO</h2>
                <div style='width: 60px; height: 3px; background: #FFC107; margin: 14px auto 0;'></div>
            </div>
            <div style='background: {$bannerBg}; padding: 14px 20px; text-align: center;'>
                <p style='color: #FFFFFF; margin: 0; font-size: 16px; font-weight: bold; letter-spacing: 0.5px;'>{$tituloBanner}</p>
            </div>
            <div style='padding: 30px 30px 20px;'>
                <p style='font-size: 15px; color: #FFFFFF; margin: 0 0 15px;'>Hola <strong style='color: #FFC107;'>Ricardo</strong>,</p>
                <p style='font-size: 14px; color: #BBBBBB; line-height: 1.7; margin: 0 0 20px;'>
                    Te recordamos el pago del <strong style='color:#FFFFFF;'>IVA (F29)</strong> correspondiente a <strong style='color:#FFC107;'>{$mesNombre}</strong>.
                    El plazo es hasta el <strong style='color:#FFFFFF;'>día " . self::DIA_PAGO . "</strong> de este mes.
                </p>
                <div style='background: #111111; border-radius: 8px; padding: 20px; margin: 0 0 20px; border: 1px solid #222222;'>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 10px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>IVA Débito (ventas)</td>
                            <td style='padding: 10px 0; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;'>\$" . number_format($calc['debito'], 0, ',', '.') . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>IVA Crédito (compras)</td>
                            <td style='padding: 10px 0; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;'>− \$" . number_format($calc['credito'], 0, ',', '.') . "</td>
                        </tr>" .
                        ($calc['remanente'] > 0 ? "
                        <tr>
                            <td style='padding: 10px 0; color: #888888; font-size: 13px; border-bottom: 1px solid #222222;'>Remanente mes anterior</td>
                            <td style='padding: 10px 0; text-align: right; font-size: 14px; color: #FFFFFF; border-bottom: 1px solid #222222;'>− \$" . number_format($calc['remanente'], 0, ',', '.') . "</td>
                        </tr>" : '') . "
                        <tr>
                            <td style='padding: 14px 0 4px; color: #FFC107; font-size: 14px; font-weight:bold;'>IVA A PAGAR</td>
                            <td style='padding: 14px 0 4px; text-align: right; font-size: 22px; color: #FFC107; font-weight:bold;'>\${$monto}</td>
                        </tr>
                    </table>
                </div>
                <div style='text-align: center; margin: 28px 0;'>
                    <a href='{$urlIva}' style='background: #FFC107; color: #0A0A0A; padding: 14px 40px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block; letter-spacing: 1px;'>
                        VER DETALLE Y REGISTRAR PAGO
                    </a>
                </div>
                <p style='font-size: 12px; color: #666666; text-align: center; margin: 15px 0 0;'>Cuando registres el pago en el sistema, dejarás de recibir este aviso.</p>
            </div>
            <div style='height: 2px; background: #FFC107; margin: 0 30px;'></div>
            <div style='background: #0A0A0A; padding: 25px 30px; text-align: center; border-top: 1px solid #1A1A1A;'>
                <p style='color: #FFFFFF; font-size: 13px; margin: 0 0 5px; font-weight: bold;'>Finanzas · Big Studio</p>
                <p style='color: #FFC107; font-size: 12px; margin: 0 0 5px;'>hola@bigstudio.cl</p>
                <p style='color: #555555; font-size: 11px; margin: 12px 0 0;'>Aviso automático del módulo de Finanzas. Este monto es referencial según los documentos registrados; valida siempre con tu F29 en el SII.</p>
            </div>
        </div>";

        Mail::html($html, function ($message) use ($email, $asunto) {
            $message->to($email)->subject($asunto);
        });
    }
}
