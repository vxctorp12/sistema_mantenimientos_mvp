<?php

/**
 * ==============================================================================
 * MODELOS DE ELOQUENT Y MIGRACIONES DE LARAVEL (v6 Minimalista)
 * Sistema de Gestión de Mantenimientos Preventivos RILAZ
 * ==============================================================================
 * 
 * Este archivo reúne todas las migraciones y modelos de Eloquent para Laravel 11,
 * listos para ser copiados en sus respectivas rutas dentro de la estructura del proyecto.
 */

// ==============================================================================
// 1. MIGRACIONES (database/migrations/)
// ==============================================================================

/*
 * ------------------------------------------------------------------------------
 * ARCHIVO: database/migrations/2026_01_01_000001_create_clientes_table.php
 * ------------------------------------------------------------------------------
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_empresa', 150);
            $table->string('contacto_nombre', 100)->nullable();
            $table->string('contacto_email', 100)->nullable();
            $table->string('telefono', 30)->nullable();
            
            // Auditoría
            $table->timestamp('creado_en')->useCurrent();
            $table->foreignId('creado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('actualizado_por')->nullable()->constrained('usuarios')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('clientes');
    }
};


/*
 * ------------------------------------------------------------------------------
 * ARCHIVO: database/migrations/2026_01_01_000002_create_sedes_table.php
 * ------------------------------------------------------------------------------
 */
return new class extends Migration {
    public function up(): void {
        Schema::create('sedes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('nombre_sede', 100);
            $table->string('direccion', 255)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            
            // Auditoría
            $table->timestamp('creado_en')->useCurrent();
            $table->foreignId('creado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('actualizado_por')->nullable()->constrained('usuarios')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('sedes');
    }
};


/*
 * ------------------------------------------------------------------------------
 * ARCHIVO: database/migrations/2026_01_01_000003_create_usuarios_table.php
 * ------------------------------------------------------------------------------
 */
return new class extends Migration {
    public function up(): void {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('email', 100)->unique();
            $table->string('password_hash', 255);
            $table->enum('rol', ['ADMIN', 'TECNICO', 'INVITADO']);
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->boolean('activo')->default(true);
            
            // Auditoría
            $table->timestamp('creado_en')->useCurrent();
            $table->foreignId('creado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('actualizado_por')->nullable()->constrained('usuarios')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('usuarios');
    }
};


/*
 * ------------------------------------------------------------------------------
 * ARCHIVO: database/migrations/2026_01_01_000004_create_contratos_table.php
 * ------------------------------------------------------------------------------
 */
return new class extends Migration {
    public function up(): void {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->integer('meta_equipos_total')->nullable();
            $table->integer('mantenimientos_por_equipo')->default(1);
            $table->date('fecha_inicio');
            $table->date('fecha_limite')->nullable();
            $table->string('ubicacion_general', 255)->nullable();
            $table->text('requerimientos_especiales')->nullable();
            $table->enum('estado', ['ACTIVO', 'FINALIZADO', 'CANCELADO'])->default('ACTIVO');
            
            // Auditoría
            $table->timestamp('creado_en')->useCurrent();
            $table->foreignId('creado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('actualizado_por')->nullable()->constrained('usuarios')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('contratos');
    }
};


/*
 * ------------------------------------------------------------------------------
 * ARCHIVO: database/migrations/2026_01_01_000005_create_contrato_metas_tipo_table.php
 * ------------------------------------------------------------------------------
 */
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


/*
 * ------------------------------------------------------------------------------
 * ARCHIVO: database/migrations/2026_01_01_000006_create_contrato_tecnicos_table.php
 * ------------------------------------------------------------------------------
 */
return new class extends Migration {
    public function up(): void {
        Schema::create('contrato_tecnicos', function (Blueprint $table) {
            $table->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();
            $table->foreignId('tecnico_id')->constrained('usuarios')->cascadeOnDelete();
            $table->timestamp('asignado_en')->useCurrent();
            $table->foreignId('asignado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            
            $table->primary(['contrato_id', 'tecnico_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('contrato_tecnicos');
    }
};


/*
 * ------------------------------------------------------------------------------
 * ARCHIVO: database/migrations/2026_01_01_000007_create_equipos_table.php
 * ------------------------------------------------------------------------------
 */
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
            $table->foreignId('creado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('actualizado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            
            $table->index('numero_serie');
        });
    }

    public function down(): void {
        Schema::dropIfExists('equipos');
    }
};


/*
 * ------------------------------------------------------------------------------
 * ARCHIVO: database/migrations/2026_01_01_000008_create_mantenimientos_table.php
 * ------------------------------------------------------------------------------
 */
return new class extends Migration {
    public function up(): void {
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('contratos')->restrictOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->restrictOnDelete();
            $table->foreignId('tecnico_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('sede_id')->nullable()->constrained('sedes')->nullOnDelete();
            $table->dateTime('fecha_mantenimiento')->useCurrent();
            $table->text('observaciones')->nullable();
            $table->text('recomendaciones')->nullable();
            $table->enum('estado_firma', ['PENDIENTE', 'FIRMADO_FISICO'])->default('PENDIENTE');
            
            // Control de Impresión
            $table->boolean('impreso')->default(false)->index();
            $table->dateTime('fecha_impresion')->nullable();
            $table->foreignId('impreso_por')->nullable()->constrained('usuarios')->nullOnDelete();
            
            // Auditoría
            $table->timestamp('creado_en')->useCurrent();
            $table->foreignId('creado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('actualizado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            
            $table->index('contrato_id');
            $table->index('equipo_id');
            $table->index('tecnico_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('mantenimientos');
    }
};


/*
 * ------------------------------------------------------------------------------
 * ARCHIVO: database/migrations/2026_01_01_000009_create_mantenimiento_checklists_table.php
 * ------------------------------------------------------------------------------
 */
return new class extends Migration {
    public function up(): void {
        Schema::create('mantenimiento_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mantenimiento_id')->constrained('mantenimientos')->cascadeOnDelete();
            $table->string('item_verificacion', 150);
            $table->boolean('realizado')->default(false);
            $table->string('comentarios', 255)->nullable();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void {
        Schema::dropIfExists('mantenimiento_checklists');
    }
};




// ==============================================================================
// 2. MODELOS ELOQUENT (app/Models/)
// ==============================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/*
 * ------------------------------------------------------------------------------
 * ARCHIVO: app/Models/Cliente.php
 * ------------------------------------------------------------------------------
 */
class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre_empresa',
        'contacto_nombre',
        'contacto_email',
        'telefono',
        'creado_por',
        'actualizado_por',
    ];

    public function sedes(): HasMany
    {
        return $this->hasMany(Sede::class, 'cliente_id');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'cliente_id');
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class, 'cliente_id');
    }
}


/*
 * ------------------------------------------------------------------------------
 * ARCHIVO: app/Models/Sede.php
 * ------------------------------------------------------------------------------
 */
class Sede extends Model
{
    use HasFactory;

    protected $table = 'sedes';
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'cliente_id',
        'nombre_sede',
        'direccion',
        'telefono',
        'latitud',
        'longitud',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'latitud' => 'float',
        'longitud' => 'float',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'sede_id');
    }
}


/*
 * ------------------------------------------------------------------------------
 * ARCHIVO: app/Models/Usuario.php
 * ------------------------------------------------------------------------------
 */
class Usuario extends Authenticatable
{
    use HasFactory;

    protected $table = 'usuarios';
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre',
        'email',
        'password_hash',
        'rol',
        'cliente_id',
        'activo',
        'creado_por',
        'actualizado_por',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function contratos(): BelongsToMany
    {
        return $this->belongsToMany(Contrato::class, 'contrato_tecnicos', 'tecnico_id', 'contrato_id')
                    ->withPivot('asignado_en', 'asignado_por');
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class, 'tecnico_id');
    }
}


/*
 * ------------------------------------------------------------------------------
 * ARCHIVO: app/Models/Contrato.php
 * ------------------------------------------------------------------------------
 */
class Contrato extends Model
{
    use HasFactory;

    protected $table = 'contratos';
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'cliente_id',
        'meta_equipos_total',
        'mantenimientos_por_equipo',
        'fecha_inicio',
        'fecha_limite',
        'ubicacion_general',
        'requerimientos_especiales',
        'estado',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_limite' => 'date',
        'meta_equipos_total' => 'integer',
        'mantenimientos_por_equipo' => 'integer',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function metasTipo(): HasMany
    {
        return $this->hasMany(ContratoMetaTipo::class, 'contrato_id');
    }

    public function tecnicos(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'contrato_tecnicos', 'contrato_id', 'tecnico_id')
                    ->withPivot('asignado_en', 'asignado_por');
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class, 'contrato_id');
    }

    /**
     * Calcula la meta total de intervenciones requeridas.
     */
    public function getMetaTotalIntervencionesAttribute(): int
    {
        return ($this->meta_equipos_total ?? 0) * ($this->mantenimientos_por_equipo ?? 1);
    }
}


/*
 * ------------------------------------------------------------------------------
 * ARCHIVO: app/Models/ContratoMetaTipo.php
 * ------------------------------------------------------------------------------
 */
class ContratoMetaTipo extends Model
{
    use HasFactory;

    protected $table = 'contrato_metas_tipo';
    public $timestamps = false;

    protected $fillable = [
        'contrato_id',
        'tipo_equipo',
        'cantidad_meta',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
}


/*
 * ------------------------------------------------------------------------------
 * ARCHIVO: app/Models/Equipo.php
 * ------------------------------------------------------------------------------
 */
class Equipo extends Model
{
    use HasFactory;

    protected $table = 'equipos';
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'numero_serie',
        'codigo_inventario',
        'tipo_equipo',
        'marca',
        'modelo',
        'departamento_unidad',
        'usuario_asignado',
        'sede_id',
        'creado_por',
        'actualizado_por',
    ];

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class, 'equipo_id');
    }
}


/*
 * ------------------------------------------------------------------------------
 * ARCHIVO: app/Models/Mantenimiento.php
 * ------------------------------------------------------------------------------
 */
class Mantenimiento extends Model
{
    use HasFactory;

    protected $table = 'mantenimientos';
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'contrato_id',
        'equipo_id',
        'tecnico_id',
        'sede_id',
        'fecha_mantenimiento',
        'observaciones',
        'recomendaciones',
        'estado_firma',
        'impreso',
        'fecha_impresion',
        'impreso_por',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'fecha_mantenimiento' => 'datetime',
        'fecha_impresion' => 'datetime',
        'impreso' => 'boolean',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'tecnico_id');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    public function impresoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'impreso_por');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(MantenimientoChecklist::class, 'mantenimiento_id');
    }
}


/*
 * ------------------------------------------------------------------------------
 * ARCHIVO: app/Models/MantenimientoChecklist.php
 * ------------------------------------------------------------------------------
 */
class MantenimientoChecklist extends Model
{
    use HasFactory;

    protected $table = 'mantenimiento_checklists';
    public $timestamps = false;
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'mantenimiento_id',
        'item_verificacion',
        'realizado',
        'comentarios',
    ];

    protected $casts = [
        'realizado' => 'boolean',
    ];

    public function mantenimiento(): BelongsTo
    {
        return $this->belongsTo(Mantenimiento::class, 'mantenimiento_id');
    }
}
