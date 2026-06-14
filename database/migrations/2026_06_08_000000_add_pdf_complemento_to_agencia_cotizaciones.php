<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencia_cotizaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('agencia_cotizaciones', 'pdf_complemento_path')) {
                $table->string('pdf_complemento_path')->nullable()->after('lioren_xml_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agencia_cotizaciones', function (Blueprint $table) {
            if (Schema::hasColumn('agencia_cotizaciones', 'pdf_complemento_path')) {
                $table->dropColumn('pdf_complemento_path');
            }
        });
    }
};
