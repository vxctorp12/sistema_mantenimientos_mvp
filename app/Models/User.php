<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'rol',
        'cliente_id',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    // Relación con Cliente (para rol INVITADO)
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    // Mantenimientos ejecutados como Técnico
    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'tecnico_id');
    }

    // Contratos asignados al Técnico
    public function contratos()
    {
        return $this->belongsToMany(Contrato::class, 'contrato_tecnicos', 'tecnico_id', 'contrato_id');
    }
}
