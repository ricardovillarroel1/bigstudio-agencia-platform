<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iva_mensual', function (Blueprint $table) {
            if (!Schema::hasColumn('iva_mensual', 'pagado_at')) {
                $table->timestamp('pagado_at')->nullable()->after('cerrado_at');
            }
            if (!Schema::hasColumn('iva_mensual', 'pagado_por')) {
                $table->unsignedBigInteger('pagado_por')->nullable()->after('pagado_at');
            }
            if (!Schema::hasColumn('iva_mensual', 'recordatorio_previo_at')) {
                $table->timestamp('recordatorio_previo_at')->nullable()->after('pagado_por');
            }
            if (!Schema::hasColumn('iva_mensual', 'recordatorio_dia_at')) {
                $table->timestamp('recordatorio_dia_at')->nullable()->after('recordatorio_previo_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('iva_mensual', function (Blueprint $table) {
            foreach (['pagado_at', 'pagado_por', 'recordatorio_previo_at', 'recordatorio_dia_at'] as $col) {
                if (Schema::hasColumn('iva_mensual', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
