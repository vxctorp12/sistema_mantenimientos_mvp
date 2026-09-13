<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre_cliente',
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
        return $this->hasMany(User::class, 'cliente_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'cliente_id');
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class, 'cliente_id');
    }
}
