<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('agencia_clientes', function (Blueprint $table) {
            if (!Schema::hasColumn('agencia_clientes', 'proyecto')) {
                $table->string('proyecto', 150)->nullable()->after('nombre')->comment('Nombre del proyecto/tienda, p.ej. BOTAS MILITARES');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agencia_clientes', function (Blueprint $table) {
            if (Schema::hasColumn('agencia_clientes', 'proyecto')) {
                $table->dropColumn('proyecto');
            }
        });
    }
};
