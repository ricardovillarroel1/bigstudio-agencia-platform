<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planes', function (Blueprint $table) {
            $table->boolean('documentos_postventa_enabled')->default(false)->after('sync_inventario_enabled');
        });

        // Also add to integracion_configs if not exists
        Schema::table('integracion_configs', function (Blueprint $table) {
            if (!Schema::hasColumn('integracion_configs', 'documentos_postventa_enabled')) {
                $table->boolean('documentos_postventa_enabled')->default(false)->after('notas_credito_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('planes', function (Blueprint $table) {
            $table->dropColumn('documentos_postventa_enabled');
        });

        Schema::table('integracion_configs', function (Blueprint $table) {
            if (Schema::hasColumn('integracion_configs', 'documentos_postventa_enabled')) {
                $table->dropColumn('documentos_postventa_enabled');
            }
        });
    }
};
