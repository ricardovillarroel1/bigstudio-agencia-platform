<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_usa_base_de_datos_de_pruebas(): void
    {
        $db = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
        $this->assertStringContainsString('test', strtolower($db), "Debe usar la BD de test, está usando: {$db}");
    }

    public function test_las_migraciones_crearon_las_tablas_clave(): void
    {
        foreach (['users', 'integracion_configs', 'boletas', 'facturas_emitidas', 'notas_credito'] as $tabla) {
            $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable($tabla), "Falta la tabla {$tabla}");
        }
    }
}
