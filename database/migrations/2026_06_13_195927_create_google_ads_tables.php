<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('google_ad_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agencia_cliente_id')->nullable()->constrained('agencia_clientes')->nullOnDelete();
            $t->string('nombre_cuenta');
            $t->string('customer_id', 50)->unique()->comment('Google Ads customer ID sin guiones, ej: 1234567890');
            $t->string('login_customer_id', 50)->nullable()->comment('MCC manager para hacer la llamada, si aplica');
            $t->string('moneda', 10)->default('CLP');
            $t->enum('estado', ['activa', 'pausada', 'eliminada'])->default('activa');
            $t->json('reporte_emails')->nullable();
            $t->string('reporte_dias', 80)->nullable();
            $t->boolean('reporte_activo')->default(false);
            $t->string('reporte_token', 80)->nullable()->unique();
            $t->timestamp('reporte_ultimo_envio')->nullable();
            $t->timestamp('ultima_sync_at')->nullable();
            $t->timestamps();
            $t->index('agencia_cliente_id');
        });

        Schema::create('google_ad_insights', function (Blueprint $t) {
            $t->id();
            $t->foreignId('google_ad_account_id')->constrained('google_ad_accounts')->cascadeOnDelete();
            $t->string('periodo', 30)->comment('YYYY-MM o YYYY-MM-DD_YYYY-MM-DD para rangos');
            $t->string('nivel', 20)->comment('cuenta | campania | adgroup | ad | demo_age | demo_gender | demo_region');
            $t->string('objeto_id', 100)->nullable();
            $t->string('objeto_nombre')->nullable();
            $t->bigInteger('inversion')->default(0);
            $t->bigInteger('ventas')->default(0);
            $t->integer('compras')->default(0);
            $t->bigInteger('alcance')->default(0);
            $t->bigInteger('impresiones')->default(0);
            $t->bigInteger('clicks')->default(0);
            $t->json('extra')->nullable();
            $t->timestamps();
            $t->unique(['google_ad_account_id', 'periodo', 'nivel', 'objeto_id'], 'google_insights_unique');
            $t->index(['google_ad_account_id', 'periodo', 'nivel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_ad_insights');
        Schema::dropIfExists('google_ad_accounts');
    }
};
