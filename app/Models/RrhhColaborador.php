<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RrhhColaborador extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromApi(array $attributes): static
    {
        $colaborador = new static;
        $colaborador->forceFill($attributes);
        $colaborador->exists = true;

        return $colaborador;
    }
}
