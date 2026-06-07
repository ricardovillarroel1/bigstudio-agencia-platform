<?php

namespace App\Console\Commands;

use App\Models\MetaAdAccount;
use App\Services\MetaReporteSender;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Envía los reportes de Meta Ads a los clientes según la configuración de cada cuenta.
 *
 * Cada cuenta tiene:
 *   - reporte_activo (bool): si está activado el envío auto
 *   - reporte_dias (string): días del mes en CSV, ej "15,30". Acepta "last" o "ultimo" para el último día del mes.
 *   - reporte_emails (json): destinatarios
 *
 * Este comando se programa para correr 1 vez al día (Kernel::schedule).
 * Se puede forzar el envío con --force-day=N para testing, y --account=ID para una cuenta puntual.
 */
class EnviarReportesMeta extends Command
{
    protected $signature = 'meta:enviar-reportes
                            {--force-day= : Forzar el día del mes (ej: 15) para testing}
                            {--account= : Solo procesar una cuenta específica (id)}
                            {--dry-run : Solo mostrar qué se enviaría, sin enviar}';

    protected $description = 'Envía reportes mensuales de Meta Ads a clientes según configuración (reporte_dias)';

    public function handle(): int
    {
        $today = now();
        $dia = (int) ($this->option('force-day') ?: $today->day);
        $diaUltimo = (int) $today->copy()->endOfMonth()->day;
        $esUltimo = ($dia === $diaUltimo);

        $this->info("=== Envío automático de reportes Meta ===");
        $this->line("Fecha: " . $today->format('Y-m-d') . " · Día: $dia" . ($esUltimo ? " (último del mes)" : ""));

        $query = MetaAdAccount::query()
            ->where('reporte_activo', true)
            ->whereNotNull('reporte_emails')
            ->whereNotNull('reporte_dias');

        if ($acctId = $this->option('account')) {
            $query->where('id', $acctId);
        }

        $cuentas = $query->with('cliente')->get();
        $this->line("Cuentas candidatas: " . $cuentas->count());

        $enviadas = 0; $omitidas = 0; $errores = 0;

        foreach ($cuentas as $cuenta) {
            $dias = $this->parseDias($cuenta->reporte_dias, $diaUltimo);
            $debeEnviar = in_array($dia, $dias, true);

            if (!$debeEnviar) {
                $this->line("  · [skip] {$cuenta->nombre_cuenta} → días configurados: " . implode(',', $dias) . " (no incluye $dia)");
                $omitidas++;
                continue;
            }

            $emails = is_array($cuenta->reporte_emails) ? $cuenta->reporte_emails : [];
            if (empty($emails)) {
                $this->warn("  · [skip] {$cuenta->nombre_cuenta} → sin correos destinatarios");
                $omitidas++;
                continue;
            }

            // Idempotencia: si ya enviamos hoy, no reenviar
            if ($cuenta->reporte_ultimo_envio && $cuenta->reporte_ultimo_envio->isSameDay($today)) {
                $this->line("  · [skip] {$cuenta->nombre_cuenta} → ya enviado hoy a las " . $cuenta->reporte_ultimo_envio->format('H:i'));
                $omitidas++;
                continue;
            }

            $msg = "  → enviar {$cuenta->nombre_cuenta} a " . implode(', ', $emails);
            if ($this->option('dry-run')) {
                $this->info("[DRY] $msg");
                continue;
            }

            try {
                (new MetaReporteSender())->enviar($cuenta); // usa mes anterior por defecto
                $this->info("✓ $msg");
                $enviadas++;
            } catch (\Throwable $e) {
                $this->error("✗ {$cuenta->nombre_cuenta}: " . $e->getMessage());
                Log::error('meta:enviar-reportes ' . $cuenta->act_id . ': ' . $e->getMessage());
                $errores++;
            }
        }

        $this->line('');
        $this->info("Resumen: $enviadas enviadas · $omitidas omitidas · $errores errores");
        Log::info('meta:enviar-reportes', ['dia' => $dia, 'enviadas' => $enviadas, 'omitidas' => $omitidas, 'errores' => $errores]);

        return $errores > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Parsea "15,30" o "15,last" o "ultimo" → array de ints normalizados.
     * "last"/"ultimo"/"fin" → último día del mes en curso.
     */
    protected function parseDias(string $raw, int $diaUltimo): array
    {
        $tokens = preg_split('/[\s,;]+/', strtolower($raw), -1, PREG_SPLIT_NO_EMPTY);
        $dias = [];
        foreach ($tokens as $t) {
            if (in_array($t, ['last', 'ultimo', 'último', 'fin'], true)) {
                $dias[] = $diaUltimo;
            } elseif (ctype_digit($t)) {
                $n = (int) $t;
                if ($n >= 1 && $n <= 31) {
                    // 29/30/31 se "saturan" al último día disponible del mes
                    $dias[] = min($n, $diaUltimo);
                }
            }
        }
        return array_values(array_unique($dias));
    }
}
