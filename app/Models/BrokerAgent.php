<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrokerAgent extends Model
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
        $brokerAgent = new static;
        $brokerAgent->forceFill($attributes);
        $brokerAgent->exists = true;

        return $brokerAgent;
    }
}
