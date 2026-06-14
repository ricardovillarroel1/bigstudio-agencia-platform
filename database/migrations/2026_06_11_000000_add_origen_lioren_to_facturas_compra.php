<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facturas_compra', function (Blueprint $table) {
            if (!Schema::hasColumn('facturas_compra', 'origen')) {
                $table->string('origen', 20)->default('manual')->after('estado')->comment('manual | lioren');
            }
            if (!Schema::hasColumn('facturas_compra', 'lioren_id')) {
                $table->string('lioren_id', 40)->nullable()->after('origen')->comment('id del documento recibido en Lioren (dedup)');
                $table->index('lioren_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('facturas_compra', function (Blueprint $table) {
            if (Schema::hasColumn('facturas_compra', 'lioren_id')) {
                $table->dropIndex(['lioren_id']);
                $table->dropColumn('lioren_id');
            }
            if (Schema::hasColumn('facturas_compra', 'origen')) {
                $table->dropColumn('origen');
            }
        });
    }
};
