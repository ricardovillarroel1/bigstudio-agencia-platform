<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupOnboardingCommand extends Command
{
    protected $signature = 'agencia:backup-onboarding
                            {--retencion-dias=14 : Dias de retencion antes de borrar backups antiguos}
                            {--destino=/root/backups/onboarding : Carpeta destino}
                            {--solo-bd : Solo dump SQL, sin archivos}
                            {--solo-archivos : Solo tar de archivos, sin BD}';

    protected $description = 'Backup diario del modulo onboarding: BD (tablas agencia_onboarding_*) + archivos en /var/www/onboarding-storage/';

    public function handle(): int
    {
        $destino = rtrim($this->option('destino'), '/');
        $retencionDias = (int) $this->option('retencion-dias');
        $soloBd = (bool) $this->option('solo-bd');
        $soloArchivos = (bool) $this->option('solo-archivos');

        if (!is_dir($destino)) {
            if (!@mkdir($destino, 0700, true)) {
                $this->error("No se pudo crear directorio destino: {$destino}");
                return self::FAILURE;
            }
        }

        $timestamp = now()->format('Ymd_His');
        $resultados = [];

        // ===== Backup BD =====
        if (!$soloArchivos) {
            $dumpFile = "{$destino}/onboarding_db_{$timestamp}.sql";
            $tablas = [
                'agencia_onboarding_plantillas',
                'agencia_onboarding_proyectos',
                'agencia_onboarding_respuestas',
                'agencia_onboarding_archivos',
                'agencia_onboarding_eventos',
            ];
            $tablasStr = implode(' ', array_map('escapeshellarg', $tablas));
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');
            $dbHost = config('database.connections.mysql.host');

            $dbPassFlag = $dbPass ? ('-p' . escapeshellarg($dbPass)) : '';

            $cmd = sprintf(
                'mysqldump -h%s -u%s %s --no-tablespaces --single-transaction %s %s > %s 2>/dev/null',
                escapeshellarg($dbHost),
                escapeshellarg($dbUser),
                $dbPassFlag,
                escapeshellarg($dbName),
                $tablasStr,
                escapeshellarg($dumpFile)
            );

            exec($cmd, $output, $exitCode);
            if ($exitCode === 0 && is_file($dumpFile)) {
                $size = filesize($dumpFile);
                $this->info("✓ BD: {$dumpFile} (" . $this->humanSize($size) . ")");
                $resultados['bd'] = ['archivo' => $dumpFile, 'tamano' => $size];
            } else {
                $this->error("✗ Error en mysqldump (exit {$exitCode})");
                Log::warning('Backup onboarding: mysqldump fallo', ['exit' => $exitCode]);
            }
        }

        // ===== Backup archivos =====
        if (!$soloBd) {
            $storagePath = '/var/www/onboarding-storage';
            if (!is_dir($storagePath)) {
                $this->warn("Storage no existe: {$storagePath}");
            } else {
                $tarFile = "{$destino}/onboarding_files_{$timestamp}.tar.gz";
                $cmd = sprintf(
                    'tar -czf %s -C %s . 2>/dev/null',
                    escapeshellarg($tarFile),
                    escapeshellarg($storagePath)
                );
                exec($cmd, $output, $exitCode);
                if ($exitCode === 0 && is_file($tarFile)) {
                    $size = filesize($tarFile);
                    $this->info("✓ Archivos: {$tarFile} (" . $this->humanSize($size) . ")");
                    $resultados['archivos'] = ['archivo' => $tarFile, 'tamano' => $size];
                } else {
                    $this->error("✗ Error en tar (exit {$exitCode})");
                    Log::warning('Backup onboarding: tar fallo', ['exit' => $exitCode]);
                }
            }
        }

        // ===== Limpieza de backups viejos =====
        $umbral = now()->subDays($retencionDias)->timestamp;
        $borrados = 0;
        $patrones = ['onboarding_db_*.sql', 'onboarding_files_*.tar.gz'];
        foreach ($patrones as $pat) {
            foreach (glob("{$destino}/{$pat}") as $f) {
                if (filemtime($f) < $umbral) {
                    if (@unlink($f)) $borrados++;
                }
            }
        }
        if ($borrados > 0) {
            $this->line("· Limpieza: {$borrados} backup(s) anteriores a {$retencionDias} dias borrados");
        }

        // ===== Resumen =====
        $this->newLine();
        $this->info("Backup completado en {$destino}");
        if (!empty($resultados)) {
            $total = array_sum(array_column($resultados, 'tamano'));
            $this->line("Total esta corrida: " . $this->humanSize($total));
        }

        return self::SUCCESS;
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . " KB";
        if ($bytes < 1073741824) return round($bytes / 1048576, 1) . " MB";
        return round($bytes / 1073741824, 2) . " GB";
    }
}
