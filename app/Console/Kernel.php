<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Procesar cola de sincronización cada 5 minutos
        $schedule->command('sync:process-queue')->everyFiveMinutes();
        
        // Sincronizar stock Lioren → Shopify cada 10 minutos
        $schedule->command('sync:lioren-to-shopify')->everyTenMinutes();

        // Detectar nuevas locations cada 6 horas
        $schedule->command('sync:detect-locations')->everySixHours();
        
        // Cerrar chats inactivos diariamente a las 2 AM
        $schedule->command('chats:close-inactive')->dailyAt('02:00');

        // Onboarding - recordatorios diarios al cliente sin avance
        $schedule->command('agencia:onboarding-recordatorios')->dailyAt('10:00');

        // Actualizar valor UF diariamente a las 8:00 AM
        $schedule->command("uf:update")->dailyAt("08:00");

        // Notificar vencimiento de planes 7, 3 y 0 días antes (a las 9:00 AM)
        $schedule->command("suscripciones:notificar-vencimiento")->dailyAt("09:00");

        // Generar facturas anticipadas 7 días antes del vencimiento (a las 9:30 AM)
        $schedule->command("facturacion:generar-anticipada")->dailyAt("09:30");

        // Verificar vencimientos de suscripciones todos los días a las 00:05
        // (corre después de medianoche para suspender planes vencidos)
        $schedule->command('suscripciones:verificar-vencimientos')->dailyAt('00:05');

        // Procesar ciclos de facturación todos los días a las 01:00
        $schedule->command('billing:process-cycles')->dailyAt('01:00');

        // Reintentar emisiones fallidas cada 15 minutos
        $schedule->command("dte:retry-failed")->everyFifteenMinutes();

        // Cobros automáticos de servicios de agencia (a las 10:00 AM)
        $schedule->command('agencia:cobros-automaticos')->dailyAt('10:00');

        // Enviar reportes de Meta Ads automáticamente (a las 09:00 AM) — el comando filtra por reporte_dias de cada cuenta
        $schedule->command('meta:enviar-reportes')->dailyAt('09:00');

        // Monitoreo: detectar pedidos pagados sin boleta y alertar por correo (cada 6 horas)
        $schedule->command('integraciones:detectar-sin-boleta')->everySixHours();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
