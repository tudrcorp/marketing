<?php

namespace App\Models;

use App\Marketing\CorporateEventRegistrationStatus;
use Database\Factories\CorporateEventRegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'corporate_event_id',
    'full_name',
    'document_id',
    'email',
    'phone',
    'company',
    'audience_source',
    'status',
    'source',
    'registered_by_id',
    'registered_at',
])]
class CorporateEventRegistration extends Model
{
    /** @use HasFactory<CorporateEventRegistrationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
        ];
    }

    public function corporateEvent(): BelongsTo
    {
        return $this->belongsTo(CorporateEvent::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_id');
    }

    public function statusEnum(): CorporateEventRegistrationStatus
    {
        return CorporateEventRegistrationStatus::tryFrom((string) $this->status)
            ?? CorporateEventRegistrationStatus::Registered;
    }
}
