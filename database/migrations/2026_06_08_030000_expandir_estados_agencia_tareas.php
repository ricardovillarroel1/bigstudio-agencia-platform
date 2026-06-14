<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE agencia_tareas MODIFY COLUMN estado ENUM('borrador','pendiente','en_curso','en_revision','requiere_cambios','terminado') NOT NULL DEFAULT 'pendiente'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE agencia_tareas MODIFY COLUMN estado ENUM('borrador','pendiente','en_curso','terminado') NOT NULL DEFAULT 'pendiente'");
    }
};
