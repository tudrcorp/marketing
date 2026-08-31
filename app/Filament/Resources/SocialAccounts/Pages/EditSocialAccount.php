<?php

namespace App\Filament\Resources\SocialAccounts\Pages;

use App\Filament\Concerns\RestrictsSocialAccountCredentials;
use App\Filament\Resources\SocialAccounts\Actions\ViewAccountPasswordAction;
use App\Filament\Resources\SocialAccounts\SocialAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSocialAccount extends EditRecord
{
    use RestrictsSocialAccountCredentials;

    protected static string $resource = SocialAccountResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['account_password']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->stripSocialAccountCredentialsForNonAdministrators($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAccountPasswordAction::make(),
            DeleteAction::make(),
        ];
    }
}
