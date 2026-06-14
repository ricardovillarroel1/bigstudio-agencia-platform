<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\IntegracionConfig;
use App\Services\ComprasLiorenImporter;

/**
 * Importación de facturas de compra recibidas en Lioren (GET /api/recepciondtes) a egresos.
 * Verifica conversión de montos (afecta vs exenta), formato de RUT, e idempotencia (dedup).
 */
class ComprasLiorenTest extends TestCase
{
    use DatabaseTransactions;

    private function configLioren(): void
    {
        $user = User::factory()->create();
        IntegracionConfig::create([
            'user_id' => $user->id,
            'shopify_tienda' => 'bigstudio.myshopify.com',
            'shopify_token' => 'x',
            'lioren_api_key' => 'fake-jwt',
            'activo' => true,
        ]);
        config(['finanzas.lioren_config_user_id' => $user->id]);
    }

    private function fakeRecepcion(): void
    {
        Http::fake([
            'www.lioren.cl/api/recepciondtes*' => Http::response(['documentos' => [
                ['id' => 'AB12', 'rs' => 'BANCO BCI', 'rut' => '970060006', 'tipodoc' => '33', 'folio' => 30250173, 'fechaemision' => '2026-06-10 00:00:00', 'total' => 11900, 'fecharecepcion' => '2026-06-11 13:09:11'],
                ['id' => 'CD34', 'rs' => 'PROVEEDOR EXENTO', 'rut' => '11111111-1', 'tipodoc' => '34', 'folio' => 50, 'fechaemision' => '2026-06-05', 'total' => 5000, 'fecharecepcion' => '2026-06-06'],
                ['id' => 'EF56', 'rs' => 'NC RECIBIDA', 'rut' => '22222222-2', 'tipodoc' => '61', 'folio' => 9, 'fechaemision' => '2026-06-07', 'total' => 1000, 'fecharecepcion' => '2026-06-07'],
            ]], 200),
        ]);
    }

    public function test_importa_facturas_de_compra_desde_lioren(): void
    {
        $this->configLioren();
        $this->fakeRecepcion();

        $r = app(ComprasLiorenImporter::class)->importar();

        $this->assertTrue($r['ok']);
        $this->assertEquals(2, $r['nuevas']); // 33 + 34 (la NC tipodoc 61 se ignora)

        // Factura afecta: total 11.900 -> neto 10.000, IVA 1.900; RUT formateado.
        // Se registra como "pagada" (gasto incurrido), NO como cuenta por pagar.
        $this->assertDatabaseHas('facturas_compra', [
            'lioren_id' => 'AB12', 'origen' => 'lioren', 'proveedor_rut' => '97006000-6',
            'monto_neto' => 10000, 'monto_iva' => 1900, 'monto_total' => 11900, 'estado' => 'pagada',
        ]);
        // Exenta: total 5.000 -> neto 5.000, IVA 0.
        $this->assertDatabaseHas('facturas_compra', ['lioren_id' => 'CD34', 'monto_neto' => 5000, 'monto_iva' => 0]);
        // NC recibida NO se importa.
        $this->assertDatabaseMissing('facturas_compra', ['lioren_id' => 'EF56']);
    }

    public function test_importacion_es_idempotente(): void
    {
        $this->configLioren();
        $this->fakeRecepcion();

        app(ComprasLiorenImporter::class)->importar();
        $r2 = app(ComprasLiorenImporter::class)->importar();

        $this->assertEquals(0, $r2['nuevas']);
        $this->assertEquals(2, $r2['omitidas']);
        $this->assertEquals(2, DB::table('facturas_compra')->where('origen', 'lioren')->count());
    }

    public function test_sin_config_lioren_devuelve_error_controlado(): void
    {
        config(['finanzas.lioren_config_user_id' => 999999]); // no existe
        $r = app(ComprasLiorenImporter::class)->importar();
        $this->assertFalse($r['ok']);
        $this->assertEquals(0, $r['nuevas']);
    }
}
