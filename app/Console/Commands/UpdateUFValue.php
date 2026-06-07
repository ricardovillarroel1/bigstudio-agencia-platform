<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UpdateUFValue extends Command
{
    protected $signature = 'uf:update';
    protected $description = 'Actualiza el valor de la UF desde mindicador.cl y lo almacena en caché y base de datos';

    public function handle()
    {
        $this->info('Actualizando valor de la UF...');

        try {
            // Intentar obtener de mindicador.cl (fuente principal)
            $response = Http::timeout(15)->get('https://mindicador.cl/api/uf');

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['serie'][0]['valor'])) {
                    $valorUF = (float) $data['serie'][0]['valor'];
                    $fecha = $data['serie'][0]['fecha'] ?? now()->toDateString();

                    // Guardar en caché por 24 horas
                    Cache::put('valor_uf_actual', $valorUF, 60 * 60 * 24);

                    // Guardar en la tabla de configuración del sistema
                    DB::table('system_settings')->updateOrInsert(
                        ['key' => 'valor_uf'],
                        [
                            'value' => (string) $valorUF,
                            'updated_at' => now(),
                        ]
                    );

                    $this->info("UF actualizada: \${$valorUF} CLP (Fecha: {$fecha})");
                    Log::info("UF actualizada exitosamente", [
                        'valor' => $valorUF,
                        'fecha' => $fecha,
                        'fuente' => 'mindicador.cl',
                    ]);

                    return Command::SUCCESS;
                }
            }

            // Si falla mindicador.cl, intentar con CMF (Comisión para el Mercado Financiero)
            $this->warn('mindicador.cl no respondió correctamente, intentando fuente alternativa...');
            $response2 = Http::timeout(15)->get('https://mindicador.cl/api/uf/' . now()->format('d-m-Y'));

            if ($response2->successful()) {
                $data2 = $response2->json();
                if (isset($data2['serie'][0]['valor'])) {
                    $valorUF = (float) $data2['serie'][0]['valor'];

                    Cache::put('valor_uf_actual', $valorUF, 60 * 60 * 24);

                    DB::table('system_settings')->updateOrInsert(
                        ['key' => 'valor_uf'],
                        [
                            'value' => (string) $valorUF,
                            'updated_at' => now(),
                        ]
                    );

                    $this->info("UF actualizada (fuente alternativa): \${$valorUF} CLP");
                    Log::info("UF actualizada desde fuente alternativa", ['valor' => $valorUF]);

                    return Command::SUCCESS;
                }
            }

            $this->error('No se pudo obtener el valor de la UF de ninguna fuente');
            Log::error('Fallo al actualizar UF: ninguna fuente respondió correctamente');

            return Command::FAILURE;

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Error al actualizar UF: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
