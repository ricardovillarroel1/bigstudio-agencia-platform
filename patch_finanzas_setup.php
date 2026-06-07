<?php
// Patch: Register routes, middleware, and upload controllers for finanzas module

$basePath = '/var/www/shopify-integrator';

// ============================================
// 1. Register middleware in Kernel.php
// ============================================
$kernelPath = $basePath . '/app/Http/Kernel.php';
$kernel = file_get_contents($kernelPath);

if (strpos($kernel, 'CheckModulePermission') === false) {
    $kernel = str_replace(
        "'verified' => \\Illuminate\\Auth\\Middleware\\EnsureEmailIsVerified::class,",
        "'verified' => \\Illuminate\\Auth\\Middleware\\EnsureEmailIsVerified::class,\n        'module.permission' => \\App\\Http\\Middleware\\CheckModulePermission::class,",
        $kernel
    );
    file_put_contents($kernelPath, $kernel);
    echo "Middleware registered in Kernel.php\n";
} else {
    echo "Middleware already registered\n";
}

// ============================================
// 2. Add finanzas + config routes to web.php
// ============================================
$routesPath = $basePath . '/routes/web.php';
$routes = file_get_contents($routesPath);

if (strpos($routes, 'FinanzasController') === false) {
    $newRoutes = <<<'ROUTES'


// ============================================
// FINANZAS MODULE ROUTES
// ============================================
Route::middleware(['auth'])->prefix('finanzas')->name('finanzas.')->group(function () {
    Route::get('/', [\App\Http\Controllers\FinanzasController::class, 'dashboard'])->name('dashboard')->middleware('module.permission:finanzas.dashboard');
    Route::get('/ingresos', [\App\Http\Controllers\FinanzasController::class, 'ingresos'])->name('ingresos')->middleware('module.permission:finanzas.ingresos');
    Route::post('/ingresos', [\App\Http\Controllers\FinanzasController::class, 'storeIngresoManual'])->name('ingresos.store')->middleware('module.permission:finanzas.ingresos');
    Route::get('/egresos', [\App\Http\Controllers\FinanzasController::class, 'egresos'])->name('egresos')->middleware('module.permission:finanzas.egresos');
    Route::post('/egresos/factura-compra', [\App\Http\Controllers\FinanzasController::class, 'storeFacturaCompra'])->name('egresos.factura-compra.store')->middleware('module.permission:finanzas.egresos');
    Route::put('/egresos/factura-compra/{id}', [\App\Http\Controllers\FinanzasController::class, 'updateFacturaCompra'])->name('egresos.factura-compra.update')->middleware('module.permission:finanzas.egresos');
    Route::delete('/egresos/factura-compra/{id}', [\App\Http\Controllers\FinanzasController::class, 'deleteFacturaCompra'])->name('egresos.factura-compra.delete')->middleware('module.permission:finanzas.egresos');
    Route::post('/egresos/gasto-operativo', [\App\Http\Controllers\FinanzasController::class, 'storeGastoOperativo'])->name('egresos.gasto-operativo.store')->middleware('module.permission:finanzas.egresos');
    Route::post('/egresos/gasto-operativo/{id}/toggle', [\App\Http\Controllers\FinanzasController::class, 'toggleGastoOperativo'])->name('egresos.gasto-operativo.toggle')->middleware('module.permission:finanzas.egresos');
    Route::post('/egresos/categoria', [\App\Http\Controllers\FinanzasController::class, 'storeCategoria'])->name('egresos.categoria.store')->middleware('module.permission:finanzas.egresos');
    Route::post('/egresos/centro-costo', [\App\Http\Controllers\FinanzasController::class, 'storeCentroCosto'])->name('egresos.centro-costo.store')->middleware('module.permission:finanzas.egresos');
    Route::get('/iva', [\App\Http\Controllers\FinanzasController::class, 'iva'])->name('iva')->middleware('module.permission:finanzas.iva');
    Route::post('/iva/cerrar', [\App\Http\Controllers\FinanzasController::class, 'cerrarIva'])->name('iva.cerrar')->middleware('module.permission:finanzas.iva');
    Route::get('/banco', [\App\Http\Controllers\FinanzasController::class, 'banco'])->name('banco')->middleware('module.permission:finanzas.banco');
    Route::post('/banco/cuenta', [\App\Http\Controllers\FinanzasController::class, 'storeCuentaBanco'])->name('banco.cuenta.store')->middleware('module.permission:finanzas.banco');
    Route::post('/banco/importar', [\App\Http\Controllers\FinanzasController::class, 'importarCartola'])->name('banco.importar')->middleware('module.permission:finanzas.banco');
    Route::post('/banco/movimiento/{id}/conciliar', [\App\Http\Controllers\FinanzasController::class, 'conciliarMovimiento'])->name('banco.conciliar')->middleware('module.permission:finanzas.banco');
    Route::get('/cuentas-cobrar', [\App\Http\Controllers\FinanzasController::class, 'cuentasCobrar'])->name('cuentas-cobrar')->middleware('module.permission:finanzas.cuentas-cobrar');
    Route::get('/cuentas-pagar', [\App\Http\Controllers\FinanzasController::class, 'cuentasPagar'])->name('cuentas-pagar')->middleware('module.permission:finanzas.cuentas-pagar');
    Route::get('/reportes', [\App\Http\Controllers\FinanzasController::class, 'reportes'])->name('reportes')->middleware('module.permission:finanzas.reportes');
    Route::get('/reportes/libro-ventas', [\App\Http\Controllers\FinanzasController::class, 'exportarLibroVentas'])->name('reportes.libro-ventas')->middleware('module.permission:finanzas.reportes');
    Route::get('/reportes/libro-compras', [\App\Http\Controllers\FinanzasController::class, 'exportarLibroCompras'])->name('reportes.libro-compras')->middleware('module.permission:finanzas.reportes');
    Route::get('/presupuesto', [\App\Http\Controllers\FinanzasController::class, 'presupuesto'])->name('presupuesto')->middleware('module.permission:finanzas.presupuesto');
    Route::post('/presupuesto', [\App\Http\Controllers\FinanzasController::class, 'storePresupuesto'])->name('presupuesto.store')->middleware('module.permission:finanzas.presupuesto');
});

// ============================================
// CONFIG MODULE ROUTES (Colaboradores)
// ============================================
Route::middleware(['auth'])->prefix('config')->name('config.')->group(function () {
    Route::get('/colaboradores', [\App\Http\Controllers\ColaboradorController::class, 'index'])->name('colaboradores')->middleware('module.permission:config.usuarios');
    Route::post('/colaboradores', [\App\Http\Controllers\ColaboradorController::class, 'store'])->name('colaboradores.store')->middleware('module.permission:config.usuarios');
    Route::put('/colaboradores/{id}', [\App\Http\Controllers\ColaboradorController::class, 'update'])->name('colaboradores.update')->middleware('module.permission:config.usuarios');
    Route::post('/colaboradores/{id}/toggle', [\App\Http\Controllers\ColaboradorController::class, 'toggleStatus'])->name('colaboradores.toggle')->middleware('module.permission:config.usuarios');
    Route::delete('/colaboradores/{id}', [\App\Http\Controllers\ColaboradorController::class, 'destroy'])->name('colaboradores.destroy')->middleware('module.permission:config.usuarios');
});

ROUTES;

    file_put_contents($routesPath, $routes . $newRoutes);
    echo "Routes added to web.php\n";
} else {
    echo "Routes already exist\n";
}

// ============================================
// 3. Update User model to support collaborator role
// ============================================
$userModelPath = $basePath . '/app/Models/User.php';
$userModel = file_get_contents($userModelPath);

// Check if collaborator redirect is already handled
if (strpos($userModel, 'colaborador') === false) {
    // We need to check if there's a redirectTo or similar method
    echo "Note: User model may need manual update for collaborator login redirect\n";
}

echo "\n=== SETUP COMPLETED ===\n";
