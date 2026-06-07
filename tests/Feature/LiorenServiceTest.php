<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Services\LiorenService;

/**
 * Tests del punto único de comunicación con Lioren. Lioren está simulado (Http::fake);
 * NO se emiten documentos reales. Verifican que el payload se arma correcto y la respuesta
 * se normaliza bien (esto blinda el formato del mensaje a Lioren en un solo lugar).
 */
class LiorenServiceTest extends TestCase
{
    public function test_boleta_arma_tipodoc_39_y_normaliza_respuesta(): void
    {
        Http::fake([LiorenService::ENDPOINT_BOLETAS => Http::response(['id' => 9, 'folio' => 500, 'pdf' => 'B64'], 200)]);

        $r = (new LiorenService())->emitirBoleta('key', [['nombre' => 'Cuadro', 'cantidad' => 1, 'precio' => 100000, 'exento' => false]], ['rs' => 'Cliente'], 'Pedido #1');

        $this->assertTrue($r['ok']);
        $this->assertEquals(500, $r['folio']);
        $this->assertEquals('B64', $r['pdf']);
        Http::assertSent(fn ($req) => $req->url() === LiorenService::ENDPOINT_BOLETAS
            && $req['emisor']['tipodoc'] === '39'
            && $req['expects'] === 'all');
    }

    public function test_factura_arma_tipodoc_33(): void
    {
        Http::fake([LiorenService::ENDPOINT_DTES => Http::response(['id' => 9, 'folio' => 800], 200)]);

        $r = (new LiorenService())->emitirFactura('key', [['nombre' => 'Servicio', 'cantidad' => 1, 'precio' => 84034, 'exento' => false]], ['rut' => '11111111-1', 'rs' => 'Empresa SpA'], 'Pedido #2');

        $this->assertTrue($r['ok']);
        $this->assertEquals(800, $r['folio']);
        Http::assertSent(fn ($req) => $req['emisor']['tipodoc'] === '33');
    }

    public function test_factura_respeta_tipodoc_y_descuento_de_opciones(): void
    {
        Http::fake([LiorenService::ENDPOINT_DTES => Http::response(['id' => 9, 'folio' => 801], 200)]);

        $r = (new LiorenService())->emitirFactura(
            'key',
            [['nombre' => 'Servicio', 'cantidad' => 1, 'precio' => 100000, 'exento' => false]],
            ['rut' => '11111111-1', 'rs' => 'Empresa SpA'],
            'Cotización',
            ['tipodoc' => '34', 'descuento_global' => ['tipo' => 'porcentaje', 'valor' => 10]]
        );

        $this->assertTrue($r['ok']);
        Http::assertSent(fn ($req) => $req['emisor']['tipodoc'] === '34'
            && $req['descuento_global']['valor'] === 10);
    }

    public function test_factura_sin_opciones_usa_33_y_no_envia_descuento(): void
    {
        Http::fake([LiorenService::ENDPOINT_DTES => Http::response(['id' => 9, 'folio' => 802], 200)]);

        $r = (new LiorenService())->emitirFactura('key', [['nombre' => 'Servicio', 'cantidad' => 1, 'precio' => 50000, 'exento' => false]], ['rut' => '11111111-1', 'rs' => 'Empresa SpA']);

        $this->assertTrue($r['ok']);
        Http::assertSent(fn ($req) => $req['emisor']['tipodoc'] === '33'
            && !isset($req['descuento_global']));
    }

    public function test_nota_credito_arma_tipodoc_61_con_referencia(): void
    {
        Http::fake([LiorenService::ENDPOINT_DTES => Http::response(['id' => 9, 'folio' => 150, 'montototal' => 252000], 200)]);

        $ref = ['fecha' => '2026-06-01', 'tipodoc' => '39', 'folio' => '6619', 'razon' => 3, 'glosa' => 'Corrección'];
        $r = (new LiorenService())->emitirNotaCredito('key', [['nombre' => 'Ajuste', 'cantidad' => 1, 'precio' => 252000, 'exento' => false]], ['rut' => '66666666-6', 'rs' => 'Cliente'], $ref, 'NC #2829');

        $this->assertTrue($r['ok']);
        $this->assertEquals(150, $r['folio']);
        Http::assertSent(fn ($req) => $req['emisor']['tipodoc'] === '61'
            && $req['referencias'][0]['folio'] === '6619'
            && $req['referencias'][0]['razon'] === 3);
    }

    public function test_error_de_lioren_devuelve_ok_false(): void
    {
        Http::fake([LiorenService::ENDPOINT_BOLETAS => Http::response(['errors' => 'RUT inválido'], 422)]);

        $r = (new LiorenService())->emitirBoleta('key', [['nombre' => 'X', 'cantidad' => 1, 'precio' => 1000, 'exento' => false]]);

        $this->assertFalse($r['ok']);
        $this->assertNull($r['folio']);
        $this->assertEquals(422, $r['status']);
    }
}
