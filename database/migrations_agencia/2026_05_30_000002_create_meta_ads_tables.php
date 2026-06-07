<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cuentas publicitarias de Meta vinculadas a cada cliente de agencia
        if (!Schema::hasTable('meta_ad_accounts')) {
            Schema::create('meta_ad_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('agencia_cliente_id')->nullable();
                $table->string('nombre_cuenta')->nullable();      // nombre visible
                $table->string('act_id');                          // act_XXXXXXXXX
                $table->string('moneda', 10)->default('CLP');
                $table->string('estado', 20)->default('activa');   // activa / pausada
                $table->timestamp('ultima_sync_at')->nullable();
                $table->timestamps();
                $table->index('agencia_cliente_id');
                $table->unique('act_id');
            });
        }

        // Insights/metricas descargadas por cuenta y periodo (mes)
        if (!Schema::hasTable('meta_ad_insights')) {
            Schema::create('meta_ad_insights', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('meta_ad_account_id');
                $table->string('periodo', 7);   // 'YYYY-MM'
                $table->string('nivel', 20)->default('cuenta'); // cuenta / campania / anuncio
                $table->string('objeto_id')->nullable();         // id de campaña/anuncio si aplica
                $table->string('objeto_nombre')->nullable();
                // Metricas
                $table->bigInteger('inversion')->default(0);     // gasto en CLP (entero)
                $table->bigInteger('ventas')->default(0);        // valor de conversion
                $table->integer('compras')->default(0);
                $table->bigInteger('alcance')->default(0);
                $table->bigInteger('impresiones')->default(0);
                $table->integer('clicks')->default(0);
                $table->json('extra')->nullable();               // datos adicionales (ctr, cpc, series, etc.)
                $table->timestamps();
                $table->index(['meta_ad_account_id', 'periodo', 'nivel']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_ad_insights');
        Schema::dropIfExists('meta_ad_accounts');
    }
};
