<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NaturalSupplier extends Model
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
        $supplier = new static;
        $supplier->forceFill($attributes);
        $supplier->exists = true;

        return $supplier;
    }
}
