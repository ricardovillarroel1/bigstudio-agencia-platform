<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Http\Controllers\FinanzasController;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Finanzas: blinda el FIX crítico (ingreso SaaS desde facturas_servicio en CLP, NO desde
 * payments en UF que corrompía el total) y el render del dashboard rediseñado.
 */
class FinanzasTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $u = User::factory()->create();
        $u->assignRole('admin');
        return $u;
    }

    public function test_ingreso_saas_se_lee_de_facturas_servicio_en_clp(): void
    {
        $admin = $this->admin();

        // Ingreso SaaS REAL en CLP (un cobro de plan PRO ya convertido a pesos).
        DB::table('facturas_servicio')->insert([
            'user_id' => $admin->id,
            'estado' => 'pagada',
            'monto' => 81711, 'monto_neto' => 68665, 'monto_iva' => 13046,
            'periodo_inicio' => '2026-06-05',
            'created_at' => '2026-06-05 10:00:00', 'updated_at' => now(),
        ]);

        $ctrl = app(FinanzasController::class);
        $ref = new \ReflectionMethod($ctrl, 'ingresosRango');
        $ref->setAccessible(true);
        $res = $ref->invoke($ctrl, Carbon::parse('2026-06-01 00:00:00'), Carbon::parse('2026-06-30 23:59:59'), [$admin->id]);

        // Debe contar 81.711 CLP (no 1.70 UF). Sin otras fuentes, el total es el SaaS.
        $this->assertEquals(81711, $res['saas']);
        $this->assertEquals(81711, $res['total']);
    }

    public function test_ingreso_consolidado_suma_agencia_y_saas(): void
    {
        $admin = $this->admin();
        $cli = DB::table('agencia_clientes')->insertGetId(['nombre' => 'C', 'estado' => 'activo', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('agencia_cobros')->insert([
            'agencia_cliente_id' => $cli, 'concepto' => 'Plan', 'monto' => 100000,
            'estado' => 'pagado', 'pagado_at' => '2026-06-10 12:00:00', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('facturas_servicio')->insert([
            'user_id' => $admin->id, 'estado' => 'pagada', 'monto' => 50000, 'monto_neto' => 42017, 'monto_iva' => 7983,
            'periodo_inicio' => '2026-06-05', 'created_at' => '2026-06-05 10:00:00', 'updated_at' => now(),
        ]);

        $ctrl = app(FinanzasController::class);
        $ref = new \ReflectionMethod($ctrl, 'ingresosRango');
        $ref->setAccessible(true);
        $res = $ref->invoke($ctrl, Carbon::parse('2026-06-01 00:00:00'), Carbon::parse('2026-06-30 23:59:59'), [$admin->id]);

        $this->assertEquals(100000, $res['agencia']);
        $this->assertEquals(50000, $res['saas']);
        $this->assertEquals(150000, $res['total']);
    }

    public function test_dashboard_finanzas_renderiza(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('finanzas.dashboard'))->assertOk()
            ->assertSee('Ganancia neta')
            ->assertSee('Ingresos por origen')
            ->assertSee('fzTendencia', false);
    }

    public function test_marcar_facturas_compra_pagadas_en_bloque(): void
    {
        $admin = $this->admin();
        $mk = fn ($prov) => DB::table('facturas_compra')->insertGetId([
            'proveedor_nombre' => $prov, 'numero_factura' => uniqid(), 'fecha_emision' => '2026-06-01',
            'monto_neto' => 100, 'monto_iva' => 19, 'monto_total' => 119, 'estado' => 'pendiente',
            'origen' => 'lioren', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $id1 = $mk('BCI'); $id2 = $mk('FLOW'); $id3 = $mk('OTRO');

        $this->actingAs($admin)->post(route('finanzas.cuentas-pagar.marcar-pagadas'), ['ids' => [$id1, $id2]])->assertRedirect();

        $this->assertEquals('pagada', DB::table('facturas_compra')->where('id', $id1)->value('estado'));
        $this->assertEquals('pagada', DB::table('facturas_compra')->where('id', $id2)->value('estado'));
        $this->assertNotNull(DB::table('facturas_compra')->where('id', $id1)->value('pagada_at'));
        // La no seleccionada queda pendiente.
        $this->assertEquals('pendiente', DB::table('facturas_compra')->where('id', $id3)->value('estado'));
    }
}
