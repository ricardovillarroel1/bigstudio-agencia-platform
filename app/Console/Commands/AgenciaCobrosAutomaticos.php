<?php

namespace App\Console\Commands;

use App\Models\AgenciaSuscripcion;
use App\Models\AgenciaCobro;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AgenciaCobrosAutomaticos extends Command
{
    protected $signature = 'agencia:cobros-automaticos';
    protected $description = 'Facturacion recurrente de agencia: emite cobro+factura cuando vence el ciclo (proximo_cobro) y envia recordatorios.';

    public function handle()
    {
        $this->info('Procesando cobros automaticos de agencia...');
        $hoy = Carbon::now()->startOfDay();

        // ========== 1) EMISION: por cada suscripcion cuyo proximo_cobro ya llego ==========
        $this->emitirCobrosDelMes($hoy);

        // ========== 2) RECORDATORIOS: por cobro pendiente con factura emitida ==========
        $this->enviarRecordatorios($hoy);

        $this->info('Proceso completado.');
    }

    /**
     * Genera el cobro de cada suscripcion activa cuyo proximo_cobro ya vencio (o es hoy),
     * emite la factura y envia el correo. Avanza proximo_cobro al siguiente ciclo.
     * Idempotente por PERIODO de proximo_cobro (ignora cobros anulados).
     */
    private function emitirCobrosDelMes(Carbon $hoy)
    {
        $suscripciones = AgenciaSuscripcion::where('estado', 'activa')
            ->where('facturacion_automatica', true)
            ->whereDate('proximo_cobro', '<=', $hoy)
            ->with(['cliente'])
            ->get();

        foreach ($suscripciones as $sub) {
            try {
                $cliente = $sub->cliente;
                if (!$cliente) { continue; }

                // Periodo a cobrar = el de proximo_cobro de la suscripcion.
                $periodo = Carbon::parse($sub->proximo_cobro)->startOfDay();

                // Idempotencia robusta: no duplicar el cobro de ESTE periodo (mes/anio del
                // proximo_cobro), ignorando cobros anulados. NO se basa en el mes actual.
                $yaExiste = AgenciaCobro::where('agencia_suscripcion_id', $sub->id)
                    ->where('estado', '!=', 'anulado')
                    ->whereYear('vence_at', $periodo->year)
                    ->whereMonth('vence_at', $periodo->month)
                    ->exists();
                if ($yaExiste) {
                    $this->line("Suscripcion #{$sub->id}: cobro del periodo {$periodo->format('m/Y')} ya existe, omito.");
                    // Si el periodo quedo atrasado, avanzar para no reprocesar indefinidamente.
                    if ($periodo->lt($hoy)) {
                        $sub->update(['proximo_cobro' => $this->siguientePeriodo($periodo, $sub->periodicidad)]);
                    }
                    continue;
                }

                // Vencimiento de pago: 4 dias despues del inicio del ciclo.
                $vence = $periodo->copy()->addDays(4);

                $cobro = AgenciaCobro::create([
                    'agencia_cliente_id'     => $cliente->id,
                    'agencia_suscripcion_id' => $sub->id,
                    'concepto'               => $sub->concepto,
                    'monto'                  => $sub->monto,
                    'estado'                 => 'pendiente',
                    'vence_at'               => $vence,
                ]);

                // Emitir factura en Lioren (si el cliente tiene datos fiscales).
                if ($cliente->rut && $cliente->razon_social) {
                    try {
                        (new \App\Http\Controllers\AgenciaController())->emitirFacturaAgencia($cobro);
                        $cobro->refresh();
                    } catch (\Exception $e) {
                        $cobro->update(['factura_estado' => 'error', 'factura_error' => $e->getMessage()]);
                        Log::error("Factura Lioren agencia fallo (cobro #{$cobro->id}): " . $e->getMessage());
                    }
                }

                // Enviar correo con la factura adjunta (solo si esta realmente emitida).
                $this->enviarCorreo($cobro, 'factura');

                // Avanzar la suscripcion al siguiente ciclo segun periodicidad.
                $sub->update([
                    'proximo_cobro'         => $this->siguientePeriodo($periodo, $sub->periodicidad),
                    'factura_ciclo_emitida' => true,
                ]);

                $this->info("Cobro #{$cobro->id} emitido para {$cliente->nombre} (vence {$vence->format('d/m/Y')}).");
            } catch (\Exception $e) {
                Log::error("Error emitiendo cobro agencia (sub #{$sub->id}): " . $e->getMessage());
                $this->error("Error sub #{$sub->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Devuelve la fecha del siguiente ciclo segun la periodicidad de la suscripcion.
     */
    private function siguientePeriodo(Carbon $periodo, ?string $periodicidad): Carbon
    {
        return match ($periodicidad) {
            'trimestral' => $periodo->copy()->addMonths(3),
            'semestral'  => $periodo->copy()->addMonths(6),
            'anual'      => $periodo->copy()->addYear(),
            default      => $periodo->copy()->addMonth(),
        };
    }

    /**
     * Recordatorios por cobro:
     *   - 2 dias antes del vencimiento (dia 3)  -> reminder_2dias_at
     *   - el mismo dia del vencimiento (dia 5)   -> reminder_dia_at
     * Solo para cobros pendientes. Idempotente por columna.
     */
    private function enviarRecordatorios(Carbon $hoy)
    {
        $cobros = AgenciaCobro::where('estado', 'pendiente')
            ->whereNotNull('vence_at')
            ->whereNotNull('agencia_cliente_id')
            ->with('cliente')
            ->get();

        foreach ($cobros as $cobro) {
            $vence = Carbon::parse($cobro->vence_at)->startOfDay();
            $diasParaVencer = $hoy->diffInDays($vence, false); // + = futuro, 0 = hoy, - = pasado

            // Recordatorio 1: exactamente 2 dias antes.
            if ($diasParaVencer === 2 && is_null($cobro->reminder_2dias_at)) {
                if ($this->enviarCorreo($cobro, 'recordatorio')) {
                    $cobro->update(['reminder_2dias_at' => now()]);
                    $this->info("Recordatorio (2 dias antes) enviado para cobro #{$cobro->id}.");
                }
            }

            // Recordatorio 2: el mismo dia del vencimiento.
            if ($diasParaVencer === 0 && is_null($cobro->reminder_dia_at)) {
                if ($this->enviarCorreo($cobro, 'vencimiento')) {
                    $cobro->update(['reminder_dia_at' => now()]);
                    $this->info("Recordatorio (dia de vencimiento) enviado para cobro #{$cobro->id}.");
                }
            }
        }
    }

    /**
     * Envia el correo de agencia usando la plantilla unificada.
     * $tipo: 'factura' (dia 1) | 'recordatorio' (2 antes) | 'vencimiento' (dia 5)
     * Adjunta la factura SOLO si pertenece a ESTE cobro y esta realmente emitida (nunca anulada).
     */
    private function enviarCorreo(AgenciaCobro $cobro, string $tipo): bool
    {
        try {
            $cliente = $cobro->cliente;
            if (!$cliente || !$cliente->email) { return false; }

            // Adjuntar SOLO la factura de ESTE cobro y SOLO si esta emitida (no anulada/error).
            $pdfData = null;
            $folio = null;
            if ($cobro->factura_estado === 'emitida' && $cobro->lioren_folio && $cobro->lioren_pdf_url) {
                $raw = $cobro->lioren_pdf_url;
                $decoded = base64_decode($raw, true);
                // lioren_pdf_url puede ser base64 (contenido) o una URL http.
                if ($decoded !== false && strncmp($raw, 'http', 4) !== 0) {
                    $pdfData = $decoded;
                } else {
                    $fetched = @file_get_contents($raw);
                    if ($fetched !== false) { $pdfData = $fetched; }
                }
                $folio = $cobro->lioren_folio;
            }

            $subject = match ($tipo) {
                'factura'      => 'Tu factura del mes - Big Studio',
                'recordatorio' => 'Recordatorio: tu pago vence en 2 dias - Big Studio',
                'vencimiento'  => 'Tu pago vence hoy - Big Studio',
                default        => 'Big Studio',
            };

            $html = view('emails.agencia.cobro', [
                'cobro'   => $cobro,
                'cliente' => $cliente,
                'tipo'    => $tipo,
            ])->render();

            Mail::html($html, function ($message) use ($cliente, $pdfData, $folio, $subject) {
                $message->from(config('mail.from.address'), 'Big Studio')
                    ->to($cliente->email)
                    ->subject($subject);
                if ($pdfData && $folio) {
                    $message->attachData($pdfData, 'Factura_' . $folio . '.pdf', ['mime' => 'application/pdf']);
                }
            });

            if ($tipo === 'factura') {
                $cobro->update(['factura_enviada_at' => now()]);
            }

            Log::info("Correo agencia enviado", [
                'cobro_id' => $cobro->id,
                'tipo' => $tipo,
                'email' => $cliente->email,
                'factura_adjunta' => $pdfData ? ('SI folio ' . $folio) : 'NO',
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error("Error enviando correo agencia (cobro #{$cobro->id}, tipo {$tipo}): " . $e->getMessage());
            return false;
        }
    }
}
