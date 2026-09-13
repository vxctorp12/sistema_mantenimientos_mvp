<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
