<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $this->belongsToMany(User::class, 'contrato_tecnicos', 'contrato_id', 'tecnico_id')
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
