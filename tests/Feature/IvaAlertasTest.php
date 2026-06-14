<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Services\IvaCalculator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Alertas de pago de IVA: cálculo del período, registro de pago (que apaga los avisos),
 * y el comando que envía el recordatorio el día 15 (5 antes) y el 20 (día de pago).
 */
class IvaAlertasTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function admin(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $u = User::factory()->create();
        $u->assignRole('admin');
        return $u;
    }

    /** Débito de mayo vía un ingreso manual (evita columnas NOT NULL de boletas). */
    private function ingresoMayo(int $iva): void
    {
        DB::table('ingresos_manuales')->insert([
            'concepto' => 'Venta test', 'monto_neto' => 100000, 'monto_iva' => $iva, 'monto_total' => 100000 + $iva,
            'fecha' => '2026-05-15', 'categoria' => 'Otros', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_calculadora_iva_a_pagar(): void
    {
        $this->admin();
        $this->ingresoMayo(19000);

        $calc = app(IvaCalculator::class)->paraPeriodo(5, 2026);

        $this->assertEquals(19000, $calc['debito']);
        $this->assertEquals(0, $calc['credito']);
        $this->assertEquals(19000, $calc['a_pagar']);
    }

    public function test_periodo_que_vence_es_el_mes_anterior(): void
    {
        $svc = app(IvaCalculator::class);
        $this->assertEquals(['mes' => 5, 'anio' => 2026], $svc->periodoQueVenceEn(6, 2026));
        $this->assertEquals(['mes' => 12, 'anio' => 2025], $svc->periodoQueVenceEn(1, 2026));
    }

    public function test_registrar_pago_iva_marca_pagado_y_redirige(): void
    {
        $admin = $this->admin();
        $this->ingresoMayo(19000);

        $this->actingAs($admin)
            ->post(route('finanzas.iva.registrar-pago'), ['mes' => 5, 'anio' => 2026])
            ->assertRedirect();

        $row = DB::table('iva_mensual')->where('anio', 2026)->where('mes', 5)->first();
        $this->assertNotNull($row);
        $this->assertNotNull($row->pagado_at);
        $this->assertEquals('cerrado', $row->estado);
        $this->assertEquals(19000, (int) $row->iva_a_pagar);
    }

    public function test_comando_envia_el_dia_15_y_marca_recordatorio(): void
    {
        $this->admin();
        $this->ingresoMayo(19000);
        Carbon::setTestNow(Carbon::create(2026, 6, 15, 9, 15));

        Artisan::call('finanzas:alertar-iva');

        $row = DB::table('iva_mensual')->where('anio', 2026)->where('mes', 5)->first();
        $this->assertNotNull($row, 'Debe crear snapshot del período');
        $this->assertNotNull($row->recordatorio_previo_at, 'Debe marcar el aviso previo como enviado');
        $this->assertNull($row->recordatorio_dia_at);

        // Segunda corrida el mismo día: no debe re-marcar (dedupe).
        $primera = $row->recordatorio_previo_at;
        Artisan::call('finanzas:alertar-iva');
        $row2 = DB::table('iva_mensual')->where('anio', 2026)->where('mes', 5)->first();
        $this->assertEquals($primera, $row2->recordatorio_previo_at);
    }

    public function test_comando_no_envia_si_ya_esta_pagado(): void
    {
        $this->admin();
        $this->ingresoMayo(19000);
        DB::table('iva_mensual')->insert([
            'anio' => 2026, 'mes' => 5, 'debito_fiscal' => 19000, 'credito_fiscal' => 0,
            'remanente_anterior' => 0, 'iva_a_pagar' => 19000, 'remanente_siguiente' => 0,
            'estado' => 'cerrado', 'pagado_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        Carbon::setTestNow(Carbon::create(2026, 6, 15, 9, 15));

        Artisan::call('finanzas:alertar-iva');

        $row = DB::table('iva_mensual')->where('anio', 2026)->where('mes', 5)->first();
        $this->assertNull($row->recordatorio_previo_at, 'No debe enviar aviso si ya está pagado');
    }

    public function test_comando_no_hace_nada_fuera_de_los_dias_de_aviso(): void
    {
        $this->admin();
        $this->ingresoMayo(19000);
        Carbon::setTestNow(Carbon::create(2026, 6, 10, 9, 15));

        Artisan::call('finanzas:alertar-iva');

        $this->assertDatabaseMissing('iva_mensual', ['anio' => 2026, 'mes' => 5]);
    }

    public function test_campana_fase_recordatorio_dias_15_a_19(): void
    {
        $admin = $this->admin();
        $this->ingresoMayo(19000);
        Carbon::setTestNow(Carbon::create(2026, 6, 16, 10, 0));

        $this->actingAs($admin)->get(route('finanzas.iva'))->assertOk()
            ->assertSee('Recordatorio: IVA de')   // fase 1
            ->assertDontSee('día de pago');        // la fase 2 aún no
    }

    public function test_campana_fase_dia_de_pago_es_aviso_distinto(): void
    {
        $admin = $this->admin();
        $this->ingresoMayo(19000);
        Carbon::setTestNow(Carbon::create(2026, 6, 20, 10, 0));

        $this->actingAs($admin)->get(route('finanzas.iva'))->assertOk()
            ->assertSee('día de pago')             // fase 2 (slug distinto al recordatorio)
            ->assertDontSee('Recordatorio: IVA de');
    }

    public function test_campana_no_muestra_iva_si_ya_pagado(): void
    {
        $admin = $this->admin();
        $this->ingresoMayo(19000);
        DB::table('iva_mensual')->insert([
            'anio' => 2026, 'mes' => 5, 'debito_fiscal' => 19000, 'credito_fiscal' => 0,
            'remanente_anterior' => 0, 'iva_a_pagar' => 19000, 'remanente_siguiente' => 0,
            'estado' => 'cerrado', 'pagado_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        Carbon::setTestNow(Carbon::create(2026, 6, 20, 10, 0));

        $this->actingAs($admin)->get(route('finanzas.iva'))->assertOk()
            ->assertDontSee('día de pago')
            ->assertDontSee('Recordatorio: IVA de');
    }
}
