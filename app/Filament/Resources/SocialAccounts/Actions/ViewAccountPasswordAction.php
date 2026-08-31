<?php

namespace App\Filament\Resources\SocialAccounts\Actions;

use App\Models\SocialAccount;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ViewAccountPasswordAction
{
    public static function make(): Action
    {
        return Action::make('viewAccountPassword')
            ->label('Ver clave')
            ->icon(Heroicon::OutlinedKey)
            ->color('warning')
            ->visible(fn (SocialAccount $record): bool => $record->hasAccountPassword()
                && auth()->user()?->can('viewPassword', $record) === true)
            ->slideOver()
            ->modalWidth(Width::Large)
            ->modalHeading('Credencial de acceso')
            ->modalDescription('Consulta segura de la clave almacenada para esta cuenta de red social.')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(fn (SocialAccount $record) => view('filament.resources.social-accounts.view-account-password', [
                'account' => $record,
                'password' => $record->account_password,
            ]));
    }
}
