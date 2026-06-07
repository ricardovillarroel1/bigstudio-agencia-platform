<?php
// Migration: Create all financial tables + permissions + collaborator role
// Run via: php artisan tinker < migration_finanzas_permisos.php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// ============================================
// PART 1: CREATE COLLABORATOR ROLE + PERMISSIONS
// ============================================

// Clear permission cache
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

// Create collaborator role
$colaborador = Role::firstOrCreate(['name' => 'colaborador', 'guard_name' => 'web']);
echo "Role 'colaborador' created/found (ID: {$colaborador->id})\n";

// Define all granular permissions
$permisos = [
    // Integraciones
    'integraciones.dashboard',
    'integraciones.boletas',
    'integraciones.facturas',
    'integraciones.clientes',
    'integraciones.solicitudes',
    'integraciones.suscripciones',
    'integraciones.billing',
    'integraciones.configuracion',
    'integraciones.chats',
    'integraciones.transferencias',
    'integraciones.cobros-asignados',
    'integraciones.correos',
    // Agencia
    'agencia.dashboard',
    'agencia.clientes',
    'agencia.servicios',
    'agencia.suscripciones',
    'agencia.cobros',
    'agencia.cotizaciones',
    'agencia.correos',
    // Finanzas
    'finanzas.dashboard',
    'finanzas.ingresos',
    'finanzas.egresos',
    'finanzas.iva',
    'finanzas.banco',
    'finanzas.cuentas-cobrar',
    'finanzas.cuentas-pagar',
    'finanzas.reportes',
    'finanzas.presupuesto',
    // Configuración
    'config.usuarios',
];

foreach ($permisos as $p) {
    Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
}
echo count($permisos) . " permissions created/verified\n";

// Give admin role ALL new permissions
$admin = Role::findByName('admin', 'web');
$admin->givePermissionTo($permisos);
echo "All permissions assigned to admin role\n";

// ============================================
// PART 2: ADD 'colaborador' TO USER ROLE ENUM
// ============================================
try {
    DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','cliente','colaborador') DEFAULT 'cliente'");
    echo "User role enum updated to include 'colaborador'\n";
} catch (\Exception $e) {
    echo "Note: " . $e->getMessage() . "\n";
}

// ============================================
// PART 3: CREATE FINANCIAL TABLES
// ============================================

// 1. categorias_gasto
if (!Schema::hasTable('categorias_gasto')) {
    Schema::create('categorias_gasto', function (Blueprint $table) {
        $table->id();
        $table->string('nombre', 100);
        $table->string('color', 7)->default('#6366f1');
        $table->string('icono', 50)->nullable();
        $table->boolean('activa')->default(true);
        $table->timestamps();
    });
    echo "Table 'categorias_gasto' created\n";

    // Seed default categories
    DB::table('categorias_gasto')->insert([
        ['nombre' => 'Proveedores', 'color' => '#3b82f6', 'icono' => 'truck', 'activa' => true, 'created_at' => now(), 'updated_at' => now()],
        ['nombre' => 'Arriendo', 'color' => '#f59e0b', 'icono' => 'building', 'activa' => true, 'created_at' => now(), 'updated_at' => now()],
        ['nombre' => 'Servicios Básicos', 'color' => '#10b981', 'icono' => 'zap', 'activa' => true, 'created_at' => now(), 'updated_at' => now()],
        ['nombre' => 'Sueldos y Honorarios', 'color' => '#8b5cf6', 'icono' => 'users', 'activa' => true, 'created_at' => now(), 'updated_at' => now()],
        ['nombre' => 'Marketing', 'color' => '#ec4899', 'icono' => 'megaphone', 'activa' => true, 'created_at' => now(), 'updated_at' => now()],
        ['nombre' => 'Software y Licencias', 'color' => '#06b6d4', 'icono' => 'code', 'activa' => true, 'created_at' => now(), 'updated_at' => now()],
        ['nombre' => 'Hosting y Servidores', 'color' => '#64748b', 'icono' => 'server', 'activa' => true, 'created_at' => now(), 'updated_at' => now()],
        ['nombre' => 'Otros Gastos', 'color' => '#94a3b8', 'icono' => 'more-horizontal', 'activa' => true, 'created_at' => now(), 'updated_at' => now()],
    ]);
    echo "Default categories seeded\n";
}

// 2. centros_costo
if (!Schema::hasTable('centros_costo')) {
    Schema::create('centros_costo', function (Blueprint $table) {
        $table->id();
        $table->string('nombre', 100);
        $table->text('descripcion')->nullable();
        $table->boolean('activo')->default(true);
        $table->timestamps();
    });
    echo "Table 'centros_costo' created\n";

    DB::table('centros_costo')->insert([
        ['nombre' => 'Integraciones Shopify', 'descripcion' => 'Línea de negocio de integraciones con Shopify', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ['nombre' => 'Agencia / Diseño Web', 'descripcion' => 'Servicios de agencia, diseño y desarrollo web', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ['nombre' => 'Consultoría', 'descripcion' => 'Servicios de consultoría y asesoría', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ['nombre' => 'General', 'descripcion' => 'Gastos generales no asignados a una línea específica', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
    ]);
    echo "Default cost centers seeded\n";
}

// 3. facturas_compra
if (!Schema::hasTable('facturas_compra')) {
    Schema::create('facturas_compra', function (Blueprint $table) {
        $table->id();
        $table->string('proveedor_nombre', 255);
        $table->string('proveedor_rut', 20)->nullable();
        $table->string('numero_factura', 50);
        $table->date('fecha_emision');
        $table->date('fecha_vencimiento')->nullable();
        $table->decimal('monto_neto', 12, 0)->default(0);
        $table->decimal('monto_iva', 12, 0)->default(0);
        $table->decimal('monto_total', 12, 0)->default(0);
        $table->foreignId('categoria_id')->nullable()->constrained('categorias_gasto')->nullOnDelete();
        $table->foreignId('centro_costo_id')->nullable()->constrained('centros_costo')->nullOnDelete();
        $table->enum('estado', ['pendiente', 'pagada', 'vencida', 'anulada'])->default('pendiente');
        $table->string('metodo_pago', 50)->nullable();
        $table->timestamp('pagada_at')->nullable();
        $table->string('archivo_pdf', 255)->nullable();
        $table->text('notas')->nullable();
        $table->unsignedBigInteger('movimiento_banco_id')->nullable();
        $table->timestamps();
    });
    echo "Table 'facturas_compra' created\n";
}

// 4. gastos_operativos
if (!Schema::hasTable('gastos_operativos')) {
    Schema::create('gastos_operativos', function (Blueprint $table) {
        $table->id();
        $table->string('concepto', 255);
        $table->decimal('monto', 12, 0);
        $table->foreignId('categoria_id')->nullable()->constrained('categorias_gasto')->nullOnDelete();
        $table->foreignId('centro_costo_id')->nullable()->constrained('centros_costo')->nullOnDelete();
        $table->integer('dia_pago')->default(1);
        $table->boolean('activo')->default(true);
        $table->timestamps();
    });
    echo "Table 'gastos_operativos' created\n";
}

// 5. ingresos_manuales
if (!Schema::hasTable('ingresos_manuales')) {
    Schema::create('ingresos_manuales', function (Blueprint $table) {
        $table->id();
        $table->string('concepto', 255);
        $table->decimal('monto_neto', 12, 0)->default(0);
        $table->decimal('monto_iva', 12, 0)->default(0);
        $table->decimal('monto_total', 12, 0)->default(0);
        $table->date('fecha');
        $table->string('categoria', 100)->nullable();
        $table->string('cliente_nombre', 255)->nullable();
        $table->string('cliente_rut', 20)->nullable();
        $table->string('numero_documento', 50)->nullable();
        $table->unsignedBigInteger('movimiento_banco_id')->nullable();
        $table->text('notas')->nullable();
        $table->timestamps();
    });
    echo "Table 'ingresos_manuales' created\n";
}

// 6. iva_mensual
if (!Schema::hasTable('iva_mensual')) {
    Schema::create('iva_mensual', function (Blueprint $table) {
        $table->id();
        $table->integer('anio');
        $table->integer('mes');
        $table->decimal('debito_fiscal', 12, 0)->default(0);
        $table->decimal('credito_fiscal', 12, 0)->default(0);
        $table->decimal('remanente_anterior', 12, 0)->default(0);
        $table->decimal('iva_a_pagar', 12, 0)->default(0);
        $table->decimal('remanente_siguiente', 12, 0)->default(0);
        $table->enum('estado', ['borrador', 'cerrado'])->default('borrador');
        $table->timestamp('cerrado_at')->nullable();
        $table->timestamps();
        $table->unique(['anio', 'mes']);
    });
    echo "Table 'iva_mensual' created\n";
}

// 7. cuentas_banco
if (!Schema::hasTable('cuentas_banco')) {
    Schema::create('cuentas_banco', function (Blueprint $table) {
        $table->id();
        $table->string('banco', 100);
        $table->string('tipo_cuenta', 50);
        $table->string('numero_cuenta', 50);
        $table->string('titular', 255);
        $table->string('rut_titular', 20)->nullable();
        $table->decimal('saldo_actual', 12, 0)->default(0);
        $table->boolean('activa')->default(true);
        $table->timestamps();
    });
    echo "Table 'cuentas_banco' created\n";
}

// 8. importaciones_cartola
if (!Schema::hasTable('importaciones_cartola')) {
    Schema::create('importaciones_cartola', function (Blueprint $table) {
        $table->id();
        $table->foreignId('cuenta_id')->constrained('cuentas_banco')->cascadeOnDelete();
        $table->string('archivo_original', 255);
        $table->integer('total_movimientos')->default(0);
        $table->integer('duplicados_omitidos')->default(0);
        $table->date('fecha_desde')->nullable();
        $table->date('fecha_hasta')->nullable();
        $table->unsignedBigInteger('importado_por')->nullable();
        $table->timestamps();
    });
    echo "Table 'importaciones_cartola' created\n";
}

// 9. movimientos_banco
if (!Schema::hasTable('movimientos_banco')) {
    Schema::create('movimientos_banco', function (Blueprint $table) {
        $table->id();
        $table->foreignId('cuenta_id')->constrained('cuentas_banco')->cascadeOnDelete();
        $table->date('fecha');
        $table->string('descripcion', 500);
        $table->string('referencia', 100)->nullable();
        $table->enum('tipo', ['ingreso', 'egreso']);
        $table->decimal('monto', 12, 0);
        $table->decimal('saldo', 12, 0)->nullable();
        $table->enum('estado_conciliacion', ['pendiente', 'conciliado', 'ignorado'])->default('pendiente');
        $table->string('conciliado_con_tipo', 50)->nullable();
        $table->unsignedBigInteger('conciliado_con_id')->nullable();
        $table->timestamp('conciliado_at')->nullable();
        $table->foreignId('importacion_id')->nullable()->constrained('importaciones_cartola')->nullOnDelete();
        $table->timestamps();
        $table->index(['cuenta_id', 'fecha']);
        $table->index(['estado_conciliacion']);
    });
    echo "Table 'movimientos_banco' created\n";
}

// 10. presupuestos
if (!Schema::hasTable('presupuestos')) {
    Schema::create('presupuestos', function (Blueprint $table) {
        $table->id();
        $table->integer('anio');
        $table->integer('mes');
        $table->foreignId('categoria_id')->constrained('categorias_gasto')->cascadeOnDelete();
        $table->decimal('monto_presupuestado', 12, 0)->default(0);
        $table->decimal('monto_real', 12, 0)->default(0);
        $table->decimal('desviacion', 5, 2)->default(0);
        $table->timestamps();
        $table->unique(['anio', 'mes', 'categoria_id']);
    });
    echo "Table 'presupuestos' created\n";
}

echo "\n=== ALL MIGRATIONS COMPLETED SUCCESSFULLY ===\n";
