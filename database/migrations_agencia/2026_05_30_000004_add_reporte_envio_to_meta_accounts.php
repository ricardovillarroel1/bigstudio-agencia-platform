<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meta_ad_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('meta_ad_accounts', 'reporte_emails')) {
                $table->text('reporte_emails')->nullable()->after('estado');       // JSON array de correos
            }
            if (!Schema::hasColumn('meta_ad_accounts', 'reporte_dias')) {
                $table->string('reporte_dias', 50)->nullable()->after('reporte_emails'); // ej "15,30" o "1"
            }
            if (!Schema::hasColumn('meta_ad_accounts', 'reporte_activo')) {
                $table->boolean('reporte_activo')->default(false)->after('reporte_dias');
            }
            if (!Schema::hasColumn('meta_ad_accounts', 'reporte_token')) {
                $table->string('reporte_token', 64)->nullable()->after('reporte_activo'); // para link publico
            }
            if (!Schema::hasColumn('meta_ad_accounts', 'reporte_ultimo_envio')) {
                $table->timestamp('reporte_ultimo_envio')->nullable()->after('reporte_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('meta_ad_accounts', function (Blueprint $table) {
            $table->dropColumn(['reporte_emails', 'reporte_dias', 'reporte_activo', 'reporte_token', 'reporte_ultimo_envio']);
        });
    }
};
