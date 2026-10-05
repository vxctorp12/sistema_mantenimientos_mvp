<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;

class UppercaseStrings extends TransformsRequest
{
    /**
     * Los nombres de los atributos que no deben ser convertidos a mayúsculas.
     *
     * @var array<int, string>
     */
    protected $except = [
        'current_password',
        'password',
        'password_confirmation',
        'email',
        'username',
        '_token',
        'remember',
    ];

    /**
     * Transform the given value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function transform($key, $value)
    {
        if (in_array($key, $this->except, true)) {
            return $value;
        }

        if (is_string($value)) {
            $value = mb_strtoupper($value, 'UTF-8');
            
            // Eliminar tildes específicamente para unidad/departamento
            if (in_array($key, ['departamento_unidad', 'ubicacion_especifica'], true)) {
                $tildes = [
                    'Á'=>'A', 'É'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ú'=>'U',
                    'À'=>'A', 'È'=>'E', 'Ì'=>'I', 'Ò'=>'O', 'Ù'=>'U',
                    'Ä'=>'A', 'Ë'=>'E', 'Ï'=>'I', 'Ö'=>'O', 'Ü'=>'U',
                    'Â'=>'A', 'Ê'=>'E', 'Î'=>'I', 'Ô'=>'O', 'Û'=>'U'
                ];
                $value = strtr($value, $tildes);
            }
            
            return $value;
        }

        return $value;
    }
}
