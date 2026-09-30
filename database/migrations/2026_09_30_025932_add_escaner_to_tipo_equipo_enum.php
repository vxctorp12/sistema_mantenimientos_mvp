<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE equipos MODIFY COLUMN tipo_equipo ENUM('DESKTOP', 'LAPTOP', 'IMPRESORA', 'ESCANER', 'OTRO') NOT NULL");
        DB::statement("ALTER TABLE contrato_metas_tipo MODIFY COLUMN tipo_equipo ENUM('DESKTOP', 'LAPTOP', 'IMPRESORA', 'ESCANER', 'OTRO') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE equipos MODIFY COLUMN tipo_equipo ENUM('DESKTOP', 'LAPTOP', 'IMPRESORA', 'OTRO') NOT NULL");
        DB::statement("ALTER TABLE contrato_metas_tipo MODIFY COLUMN tipo_equipo ENUM('DESKTOP', 'LAPTOP', 'IMPRESORA', 'OTRO') NOT NULL");
    }
};
