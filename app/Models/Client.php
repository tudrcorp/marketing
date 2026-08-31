<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'client_group_id',
    'full_name',
    'document_id',
    'email',
    'phone',
])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<ClientGroup, $this>
     */
    public function clientGroup(): BelongsTo
    {
        return $this->belongsTo(ClientGroup::class);
    }
}
