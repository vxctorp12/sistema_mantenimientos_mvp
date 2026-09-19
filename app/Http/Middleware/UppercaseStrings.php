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

        return is_string($value) ? mb_strtoupper($value, 'UTF-8') : $value;
    }
}
