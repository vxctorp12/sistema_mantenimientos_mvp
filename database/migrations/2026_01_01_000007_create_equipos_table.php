<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_serie', 100)->unique();
            $table->string('codigo_inventario', 50)->nullable()->index();
            $table->enum('tipo_equipo', ['DESKTOP', 'LAPTOP', 'IMPRESORA', 'OTRO']);
            $table->string('marca', 50);
            $table->string('modelo', 50);
            $table->string('departamento_unidad', 100)->nullable();
            $table->string('usuario_asignado', 100)->nullable();
            $table->foreignId('sede_id')->nullable()->constrained('sedes')->nullOnDelete();
            
            // Auditoría
            $table->timestamp('creado_en')->useCurrent();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            
            $table->index('numero_serie');
        });
    }

    public function down(): void {
        Schema::dropIfExists('equipos');
    }
};
