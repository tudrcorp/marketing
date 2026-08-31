<?php

namespace App\Filament\Resources\SocialAccounts\Pages;

use App\Filament\Concerns\RestrictsSocialAccountCredentials;
use App\Filament\Concerns\SetsMarketingAuthor;
use App\Filament\Resources\SocialAccounts\SocialAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSocialAccount extends CreateRecord
{
    use RestrictsSocialAccountCredentials;
    use SetsMarketingAuthor {
        mutateFormDataBeforeCreate as assignMarketingAuthor;
    }

    protected static string $resource = SocialAccountResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->assignMarketingAuthor($data);

        return $this->stripSocialAccountCredentialsForNonAdministrators($data);
    }
}
