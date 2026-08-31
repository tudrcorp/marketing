<?php

namespace App\Filament\Auth\Pages;

use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected string $view = 'filament.pages.auth.login';

    /**
     * @var array<string, string>
     */
    protected array $extraBodyAttributes = [
        'class' => 'marketing-login-screen',
    ];

    public function getMaxWidth(): Width | string | null
    {
        return Width::Medium;
    }

    public function getTitle(): string | Htmlable
    {
        return 'Acceso';
    }

    public function getHeading(): string | Htmlable | null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getHeading();
        }

        return 'Iniciar sesión';
    }

    public function getSubheading(): string | Htmlable | null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getSubheading();
        }

        return 'Aplicación de Marketing';
    }

    /**
     * @return array<string>
     */
    public function getPageClasses(): array
    {
        return ['marketing-login-page'];
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Correo electrónico')
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->prefixIcon(Heroicon::OutlinedEnvelope, isInline: true)
            ->prefixIconColor('gray')
            ->placeholder('correo@ejemplo.com')
            ->extraAttributes(['class' => 'marketing-login-field']);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Contraseña')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->prefixIcon(Heroicon::OutlinedLockClosed, isInline: true)
            ->prefixIconColor('gray')
            ->placeholder('••••••••')
            ->extraAttributes(['class' => 'marketing-login-field']);
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label('Recordarme')
            ->extraAttributes(['class' => 'marketing-login-remember']);
    }

    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()
            ->label('Iniciar sesión')
            ->extraAttributes(['class' => 'marketing-login-submit']);
    }
}
