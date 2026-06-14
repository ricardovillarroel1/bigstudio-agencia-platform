<?php

namespace App\Console\Commands;

use App\Services\ComprasLiorenImporter;
use Illuminate\Console\Command;

class ImportarComprasLioren extends Command
{
    protected $signature = 'finanzas:importar-compras-lioren';
    protected $description = 'Importa las facturas de compra recibidas en Lioren a Finanzas (egresos)';

    public function handle(): int
    {
        $r = app(ComprasLiorenImporter::class)->importar();
        if (!$r['ok']) {
            $this->error('Error: ' . $r['msg']);
            return 1;
        }
        $this->info("OK: {$r['nuevas']} nueva(s), {$r['omitidas']} ya existía(n) (de {$r['total']} recibidas en Lioren).");
        return 0;
    }
}
