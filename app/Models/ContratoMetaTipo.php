<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
