<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TravelAgency extends Model
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
        $travelAgency = new static;
        $travelAgency->forceFill($attributes);
        $travelAgency->exists = true;

        return $travelAgency;
    }
}
