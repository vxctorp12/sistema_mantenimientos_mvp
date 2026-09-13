<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('contrato_metas_tipo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();
            $table->enum('tipo_equipo', ['DESKTOP', 'LAPTOP', 'IMPRESORA', 'OTRO']);
            $table->integer('cantidad_meta');
        });
    }

    public function down(): void {
        Schema::dropIfExists('contrato_metas_tipo');
    }
};
