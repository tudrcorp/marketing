<?php

namespace App\Filament\Concerns;

trait RestrictsSocialAccountCredentials
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function stripSocialAccountCredentialsForNonAdministrators(array $data): array
    {
        $user = auth()->user();

        if ($user?->isMarketingAdministrator()) {
            return $data;
        }

        unset($data['handle'], $data['account_password']);

        return $data;
    }
}
