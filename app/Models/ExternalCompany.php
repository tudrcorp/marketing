<?php

namespace App\Models;

use App\Marketing\ExternalCompanyType;
use Database\Factories\ExternalCompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'type',
    'company_name',
    'legal_name',
    'document_id',
    'phone',
    'email',
    'responsible_name',
    'responsible_document_id',
    'responsible_phone',
    'responsible_email',
    'created_by_id',
])]
class ExternalCompany extends Model
{
    /** @use HasFactory<ExternalCompanyFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ExternalCompanyType::class,
        ];
    }

    public function isNaturalPerson(): bool
    {
        return $this->type === ExternalCompanyType::NaturalPerson;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
