<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\AgenciaCotizacion;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cubre la subida del "PDF complemento" en la creación de cotizaciones de agencia.
 * El cliente recibe ese PDF adjunto en el correo; aquí validamos la PERSISTENCIA del
 * archivo y la columna pdf_complemento_path (path feliz, sin archivo, y validación mimes).
 * Se prueba la rama BORRADOR (accion != 'enviar') para no disparar wkhtmltopdf/Flow/email.
 */
class CotizacionPdfComplementoTest extends TestCase
{
    use DatabaseTransactions;

    private function adminUser(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    public function test_crear_cotizacion_con_pdf_complemento_guarda_archivo_y_path(): void
    {
        Storage::fake('public');
        $admin = $this->adminUser();

        $resp = $this->actingAs($admin)->post(route('agencia.cotizaciones.store'), [
            'cliente_nombre' => 'Cliente Test',
            'cliente_email' => 'cliente@test.cl',
            'items' => [
                ['servicio_id' => '', 'codigo' => '', 'descripcion' => 'Plan diseño web Shopify', 'cantidad' => 1, 'precio_neto' => 1000000],
            ],
            'pdf_complemento' => UploadedFile::fake()->create('plan.pdf', 200, 'application/pdf'),
        ]);

        $resp->assertRedirect(route('agencia.cotizaciones'));

        $cot = AgenciaCotizacion::where('cliente_email', 'cliente@test.cl')->latest('id')->first();
        $this->assertNotNull($cot, 'La cotización debería existir');
        $this->assertNotNull($cot->pdf_complemento_path, 'Debería guardarse el path del PDF complemento');
        $this->assertStringStartsWith('cotizaciones_complementos/', $cot->pdf_complemento_path);
        Storage::disk('public')->assertExists($cot->pdf_complemento_path);
    }

    public function test_crear_cotizacion_sin_pdf_no_setea_path(): void
    {
        Storage::fake('public');
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('agencia.cotizaciones.store'), [
            'cliente_nombre' => 'Cliente Sin PDF',
            'cliente_email' => 'sinpdf@test.cl',
            'items' => [['servicio_id' => '', 'codigo' => '', 'descripcion' => 'Servicio', 'cantidad' => 1, 'precio_neto' => 50000]],
        ])->assertRedirect(route('agencia.cotizaciones'));

        $cot = AgenciaCotizacion::where('cliente_email', 'sinpdf@test.cl')->latest('id')->first();
        $this->assertNotNull($cot);
        $this->assertNull($cot->pdf_complemento_path, 'Sin archivo el path debe quedar NULL');
    }

    public function test_rechaza_archivo_que_no_es_pdf(): void
    {
        Storage::fake('public');
        $admin = $this->adminUser();

        $resp = $this->actingAs($admin)->post(route('agencia.cotizaciones.store'), [
            'cliente_nombre' => 'Cliente Mal Archivo',
            'cliente_email' => 'malarchivo@test.cl',
            'items' => [['servicio_id' => '', 'codigo' => '', 'descripcion' => 'Servicio', 'cantidad' => 1, 'precio_neto' => 50000]],
            'pdf_complemento' => UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream'),
        ]);

        $resp->assertSessionHasErrors('pdf_complemento');
        $this->assertDatabaseMissing('agencia_cotizaciones', ['cliente_email' => 'malarchivo@test.cl']);
    }

    public function test_valida_hasta_respeta_fecha_manual(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('agencia.cotizaciones.store'), [
            'cliente_nombre' => 'Cliente Fecha',
            'cliente_email' => 'fecha@test.cl',
            'items' => [['servicio_id' => '', 'codigo' => '', 'descripcion' => 'X', 'cantidad' => 1, 'precio_neto' => 1000]],
            'valida_hasta' => '2026-12-31',
        ])->assertRedirect(route('agencia.cotizaciones'));

        $cot = AgenciaCotizacion::where('cliente_email', 'fecha@test.cl')->latest('id')->first();
        $this->assertNotNull($cot);
        $this->assertEquals('2026-12-31', $cot->valida_hasta->format('Y-m-d'));
    }

    public function test_valida_hasta_usa_default_mas_7_dias_si_vacia(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('agencia.cotizaciones.store'), [
            'cliente_nombre' => 'Cliente Sin Fecha',
            'cliente_email' => 'sinfecha@test.cl',
            'items' => [['servicio_id' => '', 'codigo' => '', 'descripcion' => 'X', 'cantidad' => 1, 'precio_neto' => 1000]],
        ])->assertRedirect(route('agencia.cotizaciones'));

        $cot = AgenciaCotizacion::where('cliente_email', 'sinfecha@test.cl')->latest('id')->first();
        $this->assertNotNull($cot);
        $this->assertEquals(now()->addDays(7)->format('Y-m-d'), $cot->valida_hasta->format('Y-m-d'));
    }
}
