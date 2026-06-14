<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\FinanzasController;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Módulos de Finanzas: toggle de estado de factura de compra (Egresos), guardado de presupuesto
 * (12 meses) y saldo/movimientos automáticos del módulo Banco.
 */
class FinanzasModulosTest extends TestCase
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

    private function compra(string $estado, string $fecha = '2026-06-12'): int
    {
        return DB::table('facturas_compra')->insertGetId([
            'proveedor_nombre' => 'Proveedor Test', 'numero_factura' => uniqid(), 'fecha_emision' => $fecha,
            'monto_neto' => 25210, 'monto_iva' => 4790, 'monto_total' => 30000, 'estado' => $estado,
            'pagada_at' => $estado === 'pagada' ? now() : null, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_toggle_estado_factura_compra(): void
    {
        $admin = $this->admin();
        $id = $this->compra('pagada');

        $this->actingAs($admin)->post(route('finanzas.egresos.factura-compra.estado', $id), ['estado' => 'pendiente'])->assertRedirect();
        $this->assertEquals('pendiente', DB::table('facturas_compra')->where('id', $id)->value('estado'));
        $this->assertNull(DB::table('facturas_compra')->where('id', $id)->value('pagada_at'));

        $this->actingAs($admin)->post(route('finanzas.egresos.factura-compra.estado', $id), ['estado' => 'pagada'])->assertRedirect();
        $this->assertEquals('pagada', DB::table('facturas_compra')->where('id', $id)->value('estado'));
        $this->assertNotNull(DB::table('facturas_compra')->where('id', $id)->value('pagada_at'));
    }

    public function test_factura_pendiente_aparece_en_cuentas_por_pagar(): void
    {
        $admin = $this->admin();
        $this->compra('pendiente');
        $this->actingAs($admin)->get(route('finanzas.cuentas-pagar'))->assertOk()->assertSee('Proveedor Test');
    }

    public function test_presupuesto_se_guarda_para_los_12_meses(): void
    {
        $admin = $this->admin();
        $cat = DB::table('categorias_gasto')->insertGetId(['nombre' => 'CatTest', 'activa' => true, 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($admin)->post(route('finanzas.presupuesto.store'), ['anio' => 2026, 'presupuesto' => [$cat => 50000]])->assertRedirect();

        $this->assertEquals(12, DB::table('presupuestos')->where('anio', 2026)->where('categoria_id', $cat)->count());
        $this->assertEquals(50000, DB::table('presupuestos')->where('anio', 2026)->where('mes', 6)->where('categoria_id', $cat)->value('monto_presupuestado'));
    }

    public function test_banco_calcula_saldo_automatico(): void
    {
        $admin = $this->admin();
        DB::table('ingresos_manuales')->insert([
            'concepto' => 'Venta test', 'monto_neto' => 84034, 'monto_iva' => 15966, 'monto_total' => 100000,
            'fecha' => '2026-06-10', 'categoria' => 'Otros', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->compra('pagada', '2026-06-12'); // egreso 30.000

        $this->actingAs($admin)->get(route('finanzas.banco', ['mes' => 6, 'anio' => 2026]))->assertOk()
            ->assertSee('Estado de cuenta')
            ->assertSee('Saldo de la cuenta');
    }

    public function test_actualizar_saldo_real_de_la_cuenta(): void
    {
        $admin = $this->admin();
        $cuentaId = DB::table('cuentas_banco')->insertGetId([
            'banco' => 'BCI', 'tipo_cuenta' => 'corriente', 'numero_cuenta' => '123', 'titular' => 'Test',
            'saldo_actual' => 0, 'activa' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('finanzas.banco.cuenta.saldo', $cuentaId), ['saldo_actual' => 1500000])->assertRedirect();

        $cuenta = DB::table('cuentas_banco')->where('id', $cuentaId)->first();
        $this->assertEquals(1500000, (int) $cuenta->saldo_actual);
        $this->assertNotNull($cuenta->saldo_fecha);
    }

    public function test_saldo_proyectado_suma_solo_movimientos_post_ancla(): void
    {
        $admin = $this->admin();
        DB::table('cuentas_banco')->insert([
            'banco' => 'BCI', 'tipo_cuenta' => 'corriente', 'numero_cuenta' => '1', 'titular' => 'T',
            'saldo_actual' => 1000000, 'saldo_fecha' => '2026-06-01 00:00:00', 'activa' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // Ingreso DESPUÉS del ancla → suma. Egreso PAGADO antes del ancla → no debe contar.
        DB::table('ingresos_manuales')->insert(['concepto' => 'x', 'monto_neto' => 0, 'monto_iva' => 0, 'monto_total' => 200000, 'fecha' => '2026-06-10', 'categoria' => 'Otros', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('facturas_compra')->insert([
            'proveedor_nombre' => 'Prev', 'numero_factura' => uniqid(), 'fecha_emision' => '2026-05-20',
            'monto_neto' => 25210, 'monto_iva' => 4790, 'monto_total' => 30000, 'estado' => 'pagada',
            'pagada_at' => '2026-05-20 00:00:00', 'created_at' => now(), 'updated_at' => now(),
        ]);

        Carbon::setTestNow(Carbon::create(2026, 6, 15, 12, 0));
        $data = app(FinanzasController::class)->banco(new Request(['mes' => 6, 'anio' => 2026]))->getData();

        $this->assertEquals(200000, $data['ajusteDesdeAncla']);
        $this->assertEquals(1200000, $data['saldoProyectado']); // 1.000.000 ancla + 200.000 nuevos
    }
}
