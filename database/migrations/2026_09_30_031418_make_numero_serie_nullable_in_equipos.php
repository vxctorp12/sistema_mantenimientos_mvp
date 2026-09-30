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
        DB::statement('ALTER TABLE equipos MODIFY numero_serie VARCHAR(100) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Notice: Restoring to NOT NULL might fail if there are NULL values.
        DB::statement('ALTER TABLE equipos MODIFY numero_serie VARCHAR(100) NOT NULL');
    }
};
