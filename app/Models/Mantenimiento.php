<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    public function impresoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impreso_por');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(MantenimientoChecklist::class, 'mantenimiento_id');
    }
}
