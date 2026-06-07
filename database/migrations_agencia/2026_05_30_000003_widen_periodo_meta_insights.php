<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meta_ad_insights', function (Blueprint $table) {
            $table->string('periodo', 40)->change();
        });
    }
    public function down(): void
    {
        Schema::table('meta_ad_insights', function (Blueprint $table) {
            $table->string('periodo', 7)->change();
        });
    }
};
