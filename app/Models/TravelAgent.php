<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TravelAgent extends Model
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
        $travelAgent = new static;
        $travelAgent->forceFill($attributes);
        $travelAgent->exists = true;

        return $travelAgent;
    }
}
