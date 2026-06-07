<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas_emitidas', function (Blueprint $table) {
            if (!Schema::hasColumn('facturas_emitidas', 'detalles')) {
                $table->json('detalles')->nullable()->after('monto_total');
            }
            if (!Schema::hasColumn('facturas_emitidas', 'giro')) {
                $table->string('giro', 40)->nullable()->after('razon_social');
            }
            if (!Schema::hasColumn('facturas_emitidas', 'direccion')) {
                $table->string('direccion', 50)->nullable()->after('giro');
            }
            if (!Schema::hasColumn('facturas_emitidas', 'comuna_id')) {
                $table->unsignedInteger('comuna_id')->nullable()->after('direccion');
            }
            if (!Schema::hasColumn('facturas_emitidas', 'ciudad_id')) {
                $table->unsignedInteger('ciudad_id')->nullable()->after('comuna_id');
            }
            if (!Schema::hasColumn('facturas_emitidas', 'receptor_email')) {
                $table->string('receptor_email', 80)->nullable()->after('razon_social');
            }
        });
    }

    public function down(): void
    {
        Schema::table('facturas_emitidas', function (Blueprint $table) {
            $table->dropColumn(['detalles', 'giro', 'direccion', 'comuna_id', 'ciudad_id', 'receptor_email']);
        });
    }
};
