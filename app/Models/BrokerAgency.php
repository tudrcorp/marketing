<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrokerAgency extends Model
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
        $brokerAgency = new static;
        $brokerAgency->forceFill($attributes);
        $brokerAgency->exists = true;

        return $brokerAgency;
    }
}
