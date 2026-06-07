<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * SALVAGUARDA DE SEGURIDAD: aborta cualquier test si la conexión NO apunta a una base
     * de datos de pruebas. Evita que un error de configuración borre/altere producción.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $db = (string) \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
        if (!str_contains(strtolower($db), 'test')) {
            throw new \RuntimeException(
                "PELIGRO: los tests apuntan a la base de datos '{$db}', que NO parece de pruebas. " .
                "Abortado para proteger producción. Revisa phpunit.xml (DB_DATABASE debe ser shopify_integrator_test)."
            );
        }
    }
}
