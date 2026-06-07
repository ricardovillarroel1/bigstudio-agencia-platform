<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencia_cobros', function (Blueprint $table) {
            if (!Schema::hasColumn('agencia_cobros', 'reminder_2dias_at')) {
                $table->timestamp('reminder_2dias_at')->nullable()->after('factura_error');
            }
            if (!Schema::hasColumn('agencia_cobros', 'reminder_dia_at')) {
                $table->timestamp('reminder_dia_at')->nullable()->after('reminder_2dias_at');
            }
            if (!Schema::hasColumn('agencia_cobros', 'factura_enviada_at')) {
                $table->timestamp('factura_enviada_at')->nullable()->after('reminder_dia_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agencia_cobros', function (Blueprint $table) {
            $table->dropColumn(['reminder_2dias_at', 'reminder_dia_at', 'factura_enviada_at']);
        });
    }
};
