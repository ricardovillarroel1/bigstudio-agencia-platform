<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\IntegracionConfig;
use App\Models\Boleta;
use App\Models\FacturaEmitida;
use App\Models\NotaCredito;
use App\Http\Controllers\IntegracionController;

/**
 * Tests del flujo de emisión de documentos (boletas / facturas / notas de crédito).
 * Lioren y Shopify están "fakeados" con Http::fake() — NO se emiten documentos reales.
 * Estos tests blindan los bugs reales corregidos: campos mal guardados (monto en $0),
 * cálculo de montos y el helper de total facturado.
 */
class EmisionTest extends TestCase
{
    use RefreshDatabase;

    private function crearConfig(): IntegracionConfig
    {
        $user = User::factory()->create();
        return IntegracionConfig::create([
            'user_id' => $user->id,
            'shopify_tienda' => 'test-store.myshopify.com',
            'shopify_token' => 'fake-token',
            'lioren_api_key' => 'fake-key',
            'activo' => true,
            'facturacion_enabled' => false,
        ]);
    }

    private function invocar(IntegracionController $ctrl, string $metodo, array $args)
    {
        $ref = new \ReflectionMethod($ctrl, $metodo);
        $ref->setAccessible(true);
        return $ref->invokeArgs($ctrl, $args);
    }

    /**
     * BUG QUE BLINDA: la boleta postventa se guardaba con campos inexistentes (total/estado),
     * quedando con monto_total=0 y status=pending. Debe guardarse con el monto real y "emitida".
     */
    public function test_boleta_postventa_se_guarda_con_monto_correcto(): void
    {
        Http::fake([
            'www.lioren.cl/api/boletas' => Http::response(['id' => 555, 'folio' => 7777, 'pdf' => '', 'xml' => ''], 200),
            '*' => Http::response(['order' => ['id' => 12345, 'note' => '', 'tags' => '']], 200),
        ]);

        $config = $this->crearConfig();
        $order = ['id' => 12345, 'order_number' => 999, 'customer' => ['first_name' => 'Juan', 'last_name' => 'Perez', 'email' => 'juan@test.cl']];
        $boletaOriginal = (object) ['tipodoc' => '39', 'folio' => 6607, 'receptor_rut' => null, 'receptor_nombre' => 'Juan Perez'];
        $diferencia = 117000;

        $this->invocar(app(IntegracionController::class), 'emitirDocumentoPostventaAdicional', [$order, 'fake-key', $config, $boletaOriginal, $diferencia]);

        $boleta = Boleta::where('shopify_order_id', '12345')->where('user_id', $config->user_id)->first();
        $this->assertNotNull($boleta, 'Debe crearse la boleta postventa');
        $this->assertEquals(117000, (int) $boleta->monto_total, 'El monto_total debe ser la diferencia (no 0)');
        $this->assertEquals('emitida', $boleta->status, 'El status debe ser "emitida" (no pending)');
        $this->assertEquals(7777, (int) $boleta->folio);
        $this->assertEquals(98319, (int) $boleta->monto_neto, 'Neto = round(117000/1.19)');
    }

    /**
     * BUG QUE BLINDA: la nota de crédito postventa usaba columnas inexistentes y nunca se
     * guardaba. Debe guardarse con los campos correctos y referenciar la boleta original.
     */
    public function test_nota_credito_postventa_se_guarda_con_campos_correctos(): void
    {
        Http::fake([
            'www.lioren.cl/api/dtes' => Http::response(['id' => 888, 'folio' => 200, 'montoneto' => 211765, 'montoiva' => 40235, 'montototal' => 252000], 200),
            '*' => Http::response(['order' => ['id' => 6933855404272, 'note' => '', 'tags' => '']], 200),
        ]);

        $config = $this->crearConfig();
        $order = ['id' => 6933855404272, 'order_number' => 2829, 'customer' => ['first_name' => 'Cristian', 'last_name' => 'Caceres', 'email' => 'c@test.cl']];
        $boletaOriginal = (object) ['id' => 1, 'tipodoc' => '39', 'folio' => 6619, 'receptor_rut' => '66666666-6', 'receptor_nombre' => 'Cristian Caceres', 'fecha' => now()];
        $diferencia = 252000;

        $this->invocar(app(IntegracionController::class), 'emitirNotaCreditoPostventa', [$order, 'fake-key', $config, $boletaOriginal, $diferencia]);

        $nc = NotaCredito::where('shopify_order_id', '6933855404272')->where('user_id', $config->user_id)->first();
        $this->assertNotNull($nc, 'Debe crearse la nota de crédito');
        $this->assertEquals(252000, (int) $nc->monto_total);
        $this->assertEquals('emitida', $nc->status);
        $this->assertEquals(6619, (int) $nc->folio_original, 'Debe referenciar la boleta corregida');
    }

    /**
     * BLINDA el helper que decide cuánto falta facturar: total emitido (boletas+facturas) menos NC.
     */
    public function test_total_facturado_suma_documentos_y_resta_notas_credito(): void
    {
        $config = $this->crearConfig();
        $oid = '7777777';

        Boleta::create(['user_id' => $config->user_id, 'shopify_order_id' => $oid, 'tipodoc' => '39', 'folio' => 1, 'fecha' => now()->format('Y-m-d'), 'monto_total' => 261000, 'status' => 'emitida']);
        Boleta::create(['user_id' => $config->user_id, 'shopify_order_id' => $oid, 'tipodoc' => '39', 'folio' => 2, 'fecha' => now()->format('Y-m-d'), 'monto_total' => 369000, 'status' => 'emitida']);
        NotaCredito::create(['user_id' => $config->user_id, 'shopify_order_id' => $oid, 'shopify_order_number' => '1', 'tipo_documento_original' => '39', 'folio_original' => 2, 'folio' => 10, 'monto_total' => 252000, 'status' => 'emitida']);

        $total = $this->invocar(app(IntegracionController::class), 'totalFacturadoPedido', [$oid, $config->user_id]);

        // 261000 + 369000 - 252000 = 378000
        $this->assertEquals(378000, (int) $total, 'Debe ser boletas - notas de crédito');
    }

    /**
     * BLINDA el flujo principal: emisión de boleta de un pedido nuevo pagado.
     * Debe emitir, guardar con status "emitida" y por el monto real del pedido.
     */
    public function test_procesar_pedido_emite_boleta_inicial(): void
    {
        Http::fake([
            'www.lioren.cl/api/boletas' => Http::response(['id' => 1, 'folio' => 5000, 'pdf' => '', 'montoneto' => 8403, 'montoiva' => 1597, 'montototal' => 10000], 200),
            '*' => Http::response(['order' => ['id' => 111, 'note' => '', 'tags' => '']], 200),
        ]);

        $config = $this->crearConfig();
        $order = [
            'id' => 111,
            'order_number' => 50,
            'financial_status' => 'paid',
            'total_price' => '10000',
            'current_total_price' => '10000',
            'line_items' => [['title' => 'Cuadro Test', 'quantity' => 1, 'price' => '10000', 'sku' => 'TEST-1']],
            'customer' => ['first_name' => 'Ana', 'last_name' => 'Lopez', 'email' => 'ana@test.cl'],
            'shipping_lines' => [],
        ];

        $this->invocar(app(IntegracionController::class), 'procesarPedido', [$order, 'fake-key', $config]);

        $boleta = Boleta::where('shopify_order_id', '111')->where('user_id', $config->user_id)->first();
        $this->assertNotNull($boleta, 'Debe emitirse la boleta del pedido');
        $this->assertEquals(5000, (int) $boleta->folio);
        $this->assertEquals('emitida', $boleta->status);
        $this->assertEquals(10000, (int) $boleta->monto_total, 'Debe guardarse por el monto real del pedido');
    }

    /**
     * BLINDA el flujo de FACTURA inicial: pedido con datos fiscales (RUT) → factura electrónica.
     */
    public function test_procesar_pedido_con_facturacion_emite_factura(): void
    {
        Http::fake([
            'www.lioren.cl/api/dtes' => Http::response(['id' => 7, 'folio' => 900, 'pdf' => '', 'montoneto' => 10000, 'montoiva' => 1900, 'montototal' => 11900], 200),
            'www.lioren.cl/api/comunas' => Http::response([['id' => 295, 'nombre' => 'Santiago', 'ciudad_id' => 15]], 200),
            '*' => Http::response(['order' => ['id' => 222, 'note' => '', 'tags' => '']], 200),
        ]);

        $config = $this->crearConfig();
        $order = [
            'id' => 222,
            'order_number' => 60,
            'financial_status' => 'paid',
            'total_price' => '11900',
            'current_total_price' => '11900',
            'line_items' => [['title' => 'Servicio', 'quantity' => 1, 'price' => '11900', 'sku' => 'S1']],
            'customer' => ['first_name' => 'Empresa', 'last_name' => 'Test', 'email' => 'empresa@test.cl'],
            'note_attributes' => [
                ['name' => 'tipo_comprobante', 'value' => 'factura'],
                ['name' => 'rut', 'value' => '76123456-0'],
                ['name' => 'razon_social', 'value' => 'Empresa Test SpA'],
                ['name' => 'giro', 'value' => 'Comercio en general'],
                ['name' => 'direccion_fiscal', 'value' => 'Calle Falsa 123'],
            ],
            'shipping_lines' => [],
        ];

        $this->invocar(app(IntegracionController::class), 'procesarPedidoConFacturacion', [$order, 'fake-key', $config]);

        $factura = FacturaEmitida::where('shopify_order_id', '222')->where('user_id', $config->user_id)->first();
        $this->assertNotNull($factura, 'Debe emitirse la factura');
        $this->assertEquals(900, (int) $factura->folio);
        $this->assertEquals('emitida', $factura->status);
    }

    /**
     * BLINDA la nota de crédito de reembolso (anulación por devolución).
     */
    public function test_emitir_nota_credito_de_reembolso(): void
    {
        Http::fake([
            'www.lioren.cl/api/dtes' => Http::response(['id' => 3, 'folio' => 300, 'montoneto' => 42017, 'montoiva' => 7983, 'montototal' => 50000], 200),
            'www.lioren.cl/api/comunas' => Http::response([['id' => 295, 'nombre' => 'Santiago', 'ciudad_id' => 15]], 200),
            '*' => Http::response([], 200),
        ]);

        $config = $this->crearConfig();
        // emitirNotaCredito($api_key, $config, $tipoDocOriginal, $folioOriginal, $rut, $rs, $montoTotal, $orderId, $orderNumber, $glosa, $email)
        $this->invocar(app(IntegracionController::class), 'emitirNotaCredito', [
            'fake-key', $config, '39', 6607, '66666666-6', 'Cliente Test', 50000, '999', '99', 'Reembolso pedido', 'cliente@test.cl',
        ]);

        $nc = NotaCredito::where('shopify_order_id', '999')->where('user_id', $config->user_id)->first();
        $this->assertNotNull($nc, 'Debe crearse la nota de crédito de reembolso');
        $this->assertEquals(300, (int) $nc->folio);
        $this->assertEquals('emitida', $nc->status);
    }

    /**
     * BUG QUE BLINDA (pedidos #3348/#3349 mel-alimentos): el cliente escribe un RUT con dígito
     * verificador inválido en el checkout → Lioren rechazaba la boleta con validation.rutchile
     * y el pedido quedaba sin documento. Ahora la boleta se emite a consumidor final (66666666-6).
     */
    public function test_boleta_con_rut_invalido_se_emite_a_consumidor_final(): void
    {
        Http::fake([
            'www.lioren.cl/api/boletas' => Http::response(['id' => 2, 'folio' => 5001, 'pdf' => '', 'montoneto' => 8403, 'montoiva' => 1597, 'montototal' => 10000], 200),
            '*' => Http::response(['order' => ['id' => 333, 'note' => '', 'tags' => '']], 200),
        ]);

        $config = $this->crearConfig();
        $order = [
            'id' => 333,
            'order_number' => 70,
            'financial_status' => 'paid',
            'total_price' => '10000',
            'current_total_price' => '10000',
            'line_items' => [['title' => 'Producto', 'quantity' => 1, 'price' => '10000', 'sku' => 'P1']],
            'customer' => ['first_name' => 'Ruand', 'last_name' => 'Hamdan', 'email' => 'r@test.cl'],
            'note_attributes' => [['name' => 'rut', 'value' => '12345678-0']], // DV inválido (correcto: -5)
            'shipping_lines' => [],
        ];

        $this->invocar(app(IntegracionController::class), 'procesarPedido', [$order, 'fake-key', $config]);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'api/boletas')
                && ($req['receptor']['rut'] ?? null) === '66666666-6';
        });
        $boleta = Boleta::where('shopify_order_id', '333')->where('user_id', $config->user_id)->first();
        $this->assertNotNull($boleta);
        $this->assertEquals('emitida', $boleta->status);
    }

    /** Con RUT inválido el flujo de facturación cae directo a boleta SIN intentar la factura. */
    public function test_facturacion_con_rut_invalido_hace_fallback_directo_a_boleta(): void
    {
        Http::fake([
            'www.lioren.cl/api/boletas' => Http::response(['id' => 4, 'folio' => 5002, 'pdf' => '', 'montoneto' => 8403, 'montoiva' => 1597, 'montototal' => 10000], 200),
            'www.lioren.cl/api/comunas' => Http::response([['id' => 295, 'nombre' => 'Santiago', 'ciudad_id' => 15]], 200),
            '*' => Http::response(['order' => ['id' => 444, 'note' => '', 'tags' => '']], 200),
        ]);

        $config = $this->crearConfig();
        $order = [
            'id' => 444,
            'order_number' => 80,
            'financial_status' => 'paid',
            'total_price' => '10000',
            'current_total_price' => '10000',
            'line_items' => [['title' => 'Producto', 'quantity' => 1, 'price' => '10000', 'sku' => 'P2']],
            'customer' => ['first_name' => 'Sharoom', 'last_name' => 'B', 'email' => 's@test.cl'],
            'note_attributes' => [
                ['name' => 'tipo_comprobante', 'value' => 'factura'],
                ['name' => 'rut', 'value' => '12345678-0'], // DV inválido
                ['name' => 'razon_social', 'value' => 'Empresa Mala SpA'],
                ['name' => 'giro', 'value' => 'Comercio en general'],
                ['name' => 'direccion_fiscal', 'value' => 'Calle Falsa 123'],
            ],
            'shipping_lines' => [],
        ];

        $this->invocar(app(IntegracionController::class), 'procesarPedidoConFacturacion', [$order, 'fake-key', $config]);

        Http::assertNotSent(fn ($req) => str_contains($req->url(), 'api/dtes'));
        $boleta = Boleta::where('shopify_order_id', '444')->where('user_id', $config->user_id)->first();
        $this->assertNotNull($boleta, 'Debe emitirse boleta como fallback');
        $this->assertEquals('emitida', $boleta->status);
        $this->assertEquals(0, FacturaEmitida::where('shopify_order_id', '444')->count(), 'No debe quedar factura en error');
    }

    /**
     * BUG QUE BLINDA (pedido #3353 mel-alimentos): un giro largo con tilde justo en el byte 40
     * ("...servicio móvil...") se cortaba con substr() por BYTES, dejando UTF-8 corrupto →
     * json_encode fallaba y la factura no se emitía (y la fila de error tampoco se guardaba
     * porque el giro completo no cabía en la columna). Ahora se recorta con mb_substr().
     */
    public function test_factura_con_giro_multibyte_largo_se_emite(): void
    {
        Http::fake([
            'www.lioren.cl/api/dtes' => Http::response(['id' => 8, 'folio' => 901, 'pdf' => '', 'montoneto' => 144847, 'montoiva' => 27521, 'montototal' => 172368], 200),
            'www.lioren.cl/api/comunas' => Http::response([['id' => 295, 'nombre' => 'Santiago', 'ciudad_id' => 15]], 200),
            '*' => Http::response(['order' => ['id' => 666, 'note' => '', 'tags' => '']], 200),
        ]);

        $config = $this->crearConfig();
        $order = [
            'id' => 666,
            'order_number' => 95,
            'financial_status' => 'paid',
            'total_price' => '172368',
            'current_total_price' => '172368',
            'line_items' => [['title' => 'Syrup Butterscotch Mel 1.000cc', 'quantity' => 1, 'price' => '172368', 'sku' => 'S666']],
            'customer' => ['first_name' => 'Maria Jose', 'last_name' => 'Veliz', 'email' => 'mj@test.cl'],
            'note_attributes' => [
                ['name' => 'tipo_comprobante', 'value' => 'factura'],
                ['name' => 'rut', 'value' => '78089313-3'],
                ['name' => 'razon_social', 'value' => 'Inversiones IVQ limitada'],
                // El byte 40 cae a la mitad de la "ó" de "móvil": con substr() esto corrompía el UTF-8.
                ['name' => 'giro', 'value' => 'Actividades de restaurante y servicio móvil de comidas'],
                ['name' => 'direccion_fiscal', 'value' => 'Santa beatriz 103, local 104'],
            ],
            'shipping_lines' => [],
        ];

        $this->invocar(app(IntegracionController::class), 'procesarPedidoConFacturacion', [$order, 'fake-key', $config]);

        // El payload enviado a Lioren debe ser UTF-8 válido (json_encode no debe fallar).
        Http::assertSent(function ($req) {
            if (!str_contains($req->url(), 'api/dtes')) {
                return false;
            }
            $giro = $req['receptor']['giro'] ?? '';
            return mb_check_encoding($giro, 'UTF-8') && mb_strlen($giro) <= 40;
        });

        $factura = FacturaEmitida::where('shopify_order_id', '666')->where('user_id', $config->user_id)->first();
        $this->assertNotNull($factura, 'La factura debe emitirse pese al giro largo con tildes');
        $this->assertEquals('emitida', $factura->status);
        $this->assertEquals(901, (int) $factura->folio);
    }

    /** Una boleta error_permanente (reintentos agotados) NO debe bloquear la re-emisión. */
    public function test_fila_error_permanente_no_bloquea_reemision(): void
    {
        Http::fake([
            'www.lioren.cl/api/boletas' => Http::response(['id' => 5, 'folio' => 5003, 'pdf' => '', 'montoneto' => 8403, 'montoiva' => 1597, 'montototal' => 10000], 200),
            '*' => Http::response(['order' => ['id' => 555, 'note' => '', 'tags' => '']], 200),
        ]);

        $config = $this->crearConfig();
        Boleta::create([
            'user_id' => $config->user_id,
            'shopify_order_id' => '555',
            'fecha' => now()->format('Y-m-d'),
            'monto_total' => 10000,
            'status' => 'error_permanente',
            'error_message' => 'Retry #3: validation.rutchile',
        ]);

        $order = [
            'id' => 555,
            'order_number' => 90,
            'financial_status' => 'paid',
            'total_price' => '10000',
            'current_total_price' => '10000',
            'line_items' => [['title' => 'Producto', 'quantity' => 1, 'price' => '10000', 'sku' => 'P3']],
            'customer' => ['first_name' => 'Re', 'last_name' => 'Emision', 'email' => 're@test.cl'],
            'shipping_lines' => [],
        ];

        $this->invocar(app(IntegracionController::class), 'procesarPedido', [$order, 'fake-key', $config]);

        $emitida = Boleta::where('shopify_order_id', '555')->where('status', 'emitida')->first();
        $this->assertNotNull($emitida, 'La re-emisión debe proceder pese a la fila error_permanente');
        $this->assertEquals(5003, (int) $emitida->folio);
    }
}
