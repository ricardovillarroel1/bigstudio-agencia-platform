<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuentas_banco', function (Blueprint $table) {
            if (!Schema::hasColumn('cuentas_banco', 'saldo_fecha')) {
                $table->timestamp('saldo_fecha')->nullable()->after('saldo_actual');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cuentas_banco', function (Blueprint $table) {
            if (Schema::hasColumn('cuentas_banco', 'saldo_fecha')) {
                $table->dropColumn('saldo_fecha');
            }
        });
    }
};
