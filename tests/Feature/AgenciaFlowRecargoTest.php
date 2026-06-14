<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\AgenciaController;
use App\Models\User;
use App\Models\IntegracionConfig;
use App\Models\AgenciaCotizacion;

/**
 * Recargo Flow (config flow.recargo_pct): cuando el cliente paga vía Flow se suma el %
 * de la pasarela al monto cobrado, y la factura post-pago incluye el recargo como ítem
 * para que el documento cuadre con lo realmente pagado.
 */
class AgenciaFlowRecargoTest extends TestCase
{
    use DatabaseTransactions;

    private function invocar(string $metodo, array $args)
    {
        $ctrl = app(AgenciaController::class);
        $ref = new \ReflectionMethod($ctrl, $metodo);
        $ref->setAccessible(true);
        return $ref->invokeArgs($ctrl, $args);
    }

    private function cotizacionConItem(array $extra = []): AgenciaCotizacion
    {
        // emitirFacturaCotizacion usa la config de Big Studio (user_id 4).
        if (!User::find(4)) {
            User::factory()->create(['id' => 4]);
        }
        IntegracionConfig::firstOrCreate(
            ['user_id' => 4],
            ['shopify_tienda' => 'bigstudio.myshopify.com', 'shopify_token' => 'x', 'lioren_api_key' => 'fake-key', 'activo' => true]
        );

        $cot = AgenciaCotizacion::create(array_merge([
            'numero' => 99001,
            'cliente_nombre' => 'Cliente Flow',
            'cliente_email' => 'flow@test.cl',
            'subtotal_neto' => 100000,
            'total_neto' => 100000,
            'iva' => 19000,
            'total' => 119000,
            'estado' => 'enviada',
        ], $extra));
        $cot->items()->create([
            'descripcion' => 'Plan diseño web',
            'cantidad' => 1,
            'precio_unitario_neto' => 100000,
            'total_neto' => 100000,
        ]);
        return $cot;
    }

    public function test_monto_con_recargo_flow_aplica_el_porcentaje(): void
    {
        config(['flow.recargo_pct' => 3.19]);
        $monto = $this->invocar('montoConRecargoFlow', [119000]);
        $this->assertEquals(122796, $monto); // 119000 * 1.0319 = 122796.1 → 122796
    }

    /** ESTADO ACTUAL (recargo desactivado, pct=0): todo queda exactamente como antes. */
    public function test_con_recargo_desactivado_no_cambia_nada(): void
    {
        config(['flow.recargo_pct' => 0]);

        // El monto a cobrar por Flow es exactamente el total, sin recargo.
        $this->assertEquals(119000, $this->invocar('montoConRecargoFlow', [119000]));

        // La factura de una cotización pagada por Flow NO lleva línea de recargo.
        Http::fake([
            'www.lioren.cl/api/dtes' => Http::response(['id' => 3, 'folio' => 702, 'pdf' => '', 'xml' => ''], 200),
            '*' => Http::response([], 200),
        ]);
        $cot = $this->cotizacionConItem(['numero' => 99003, 'estado' => 'pagada', 'pagado_at' => now()]);
        $this->invocar('emitirFacturaCotizacion', [$cot]);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'api/dtes')
                && count($req['detalles'] ?? []) === 1;
        });
    }

    public function test_factura_de_cotizacion_pagada_por_flow_incluye_recargo(): void
    {
        config(['flow.recargo_pct' => 3.19]);
        Http::fake([
            'www.lioren.cl/api/dtes' => Http::response(['id' => 1, 'folio' => 700, 'pdf' => '', 'xml' => ''], 200),
            '*' => Http::response([], 200),
        ]);

        $cot = $this->cotizacionConItem(['estado' => 'pagada', 'pagado_at' => now()]);

        $this->invocar('emitirFacturaCotizacion', [$cot]);

        Http::assertSent(function ($req) {
            if (!str_contains($req->url(), 'api/dtes')) {
                return false;
            }
            $detalles = $req['detalles'] ?? [];
            // recargoBruto = 122796 - 119000 = 3796 → neto = round(3796/1.19) = 3190
            return count($detalles) === 2
                && str_contains($detalles[1]['nombre'] ?? '', 'Recargo pago electr')
                && (int) ($detalles[1]['precio'] ?? 0) === 3190;
        });
    }

    public function test_factura_de_cotizacion_no_pagada_por_flow_no_lleva_recargo(): void
    {
        config(['flow.recargo_pct' => 3.19]);
        Http::fake([
            'www.lioren.cl/api/dtes' => Http::response(['id' => 2, 'folio' => 701, 'pdf' => '', 'xml' => ''], 200),
            '*' => Http::response([], 200),
        ]);

        $cot = $this->cotizacionConItem(['numero' => 99002]); // sin pagado_at (ej. transferencia)

        $this->invocar('emitirFacturaCotizacion', [$cot]);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'api/dtes')
                && count($req['detalles'] ?? []) === 1;
        });
    }
}
