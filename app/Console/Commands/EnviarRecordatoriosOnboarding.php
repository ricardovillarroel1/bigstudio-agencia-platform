<?php

namespace App\Console\Commands;

use App\Mail\OnboardingRecordatorioMail;
use App\Models\AgenciaOnboardingEvento;
use App\Models\AgenciaOnboardingProyecto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarRecordatoriosOnboarding extends Command
{
    protected $signature = 'agencia:onboarding-recordatorios
                            {--dias-min=5 : Minimo dias sin avance para mandar recordatorio}
                            {--intervalo-min=4 : Minimo dias desde el ultimo recordatorio enviado}
                            {--dry-run : No envia emails, solo lista candidatos}';

    protected $description = 'Envia recordatorios a clientes con onboarding pendiente sin avance';

    public function handle(): int
    {
        $diasMin = (int) $this->option('dias-min');
        $intervaloMin = (int) $this->option('intervalo-min');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("Buscando onboardings sin avance hace mas de {$diasMin} dias...");

        // Candidatos: en_progreso o no_iniciado, con email, con fecha_envio (ya invitados),
        // sin actualizaciones recientes
        $candidatos = AgenciaOnboardingProyecto::with(["cliente", "plantilla"])
            ->whereIn("estado", ["no_iniciado", "en_progreso"])
            ->whereNotNull("email_cliente")
            ->whereNotNull("fecha_envio")
            ->where("updated_at", "<=", now()->subDays($diasMin))
            ->get();

        $enviados = 0;
        $saltados = 0;

        foreach ($candidatos as $p) {
            // Verificar que no enviamos recordatorio reciente
            $ultimoRecordatorio = AgenciaOnboardingEvento::where("proyecto_id", $p->id)
                ->where("tipo", "recordatorio_enviado")
                ->latest("created_at")
                ->first();

            if ($ultimoRecordatorio && $ultimoRecordatorio->created_at->diffInDays(now()) < $intervaloMin) {
                $this->line("  · Saltado [{$p->id}] {$p->cliente->nombre} - recordatorio enviado hace " . (int)$ultimoRecordatorio->created_at->diffInDays(now()) . " dias");
                $saltados++;
                continue;
            }

            $ultimaActividad = $p->fecha_primer_acceso ?? $p->fecha_envio ?? $p->created_at;
            $diasSinAvance = (int) $ultimaActividad->diffInDays(now());

            if ($dryRun) {
                $this->line("  · [DRY] Enviaria a {$p->email_cliente} ({$p->cliente->nombre}) - {$diasSinAvance} dias sin avance, {$p->porcentaje_avance}% completado");
                $enviados++;
                continue;
            }

            try {
                Mail::send(new OnboardingRecordatorioMail($p, $p->email_cliente, $diasSinAvance));
                AgenciaOnboardingEvento::registrar(
                    $p->id,
                    "recordatorio_enviado",
                    "Recordatorio automatico enviado a {$p->email_cliente} ({$diasSinAvance} dias sin avance)"
                );
                $this->info("  ✓ Enviado a {$p->email_cliente} ({$p->cliente->nombre})");
                $enviados++;
            } catch (\Throwable $e) {
                Log::warning("Recordatorio onboarding fallido para proyecto {$p->id}: " . $e->getMessage());
                $this->error("  ✗ Error con {$p->email_cliente}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Resumen: {$enviados} enviado(s), {$saltados} saltado(s) (recordatorio reciente).");
        return self::SUCCESS;
    }
}
