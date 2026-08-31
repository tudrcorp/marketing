<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorporateAffiliate extends Model
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
        $affiliate = new static;
        $affiliate->forceFill($attributes);
        $affiliate->exists = true;

        return $affiliate;
    }
}
