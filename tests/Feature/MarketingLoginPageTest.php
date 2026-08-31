<?php

use App\Filament\Auth\Pages\Login;
use Filament\Facades\Filament;

it('uses the custom login page in the marketing panel', function () {
    $panel = Filament::getPanel('marketing');

    expect($panel->getLoginRouteAction())->toBe(Login::class);
});

it('renders the redesigned marketing login page', function () {
    $response = $this->get(route('filament.marketing.auth.login'));

    $response
        ->assertOk()
        ->assertSee('Iniciar sesión')
        ->assertSee('Aplicación de Marketing')
        ->assertSee('marketing-login-app-subtitle', false)
        ->assertSee('Correo electrónico')
        ->assertSee('Contraseña')
        ->assertSee('Recordarme')
        ->assertSee('marketing-login-divider', false);
});

it('configures premium ios login styles in the marketing theme', function () {
    $theme = file_get_contents(resource_path('css/filament/marketing/theme.css'));

    expect($theme)
        ->toContain('.marketing-login-screen')
        ->toContain('.marketing-login-page')
        ->toContain('.marketing-login-submit')
        ->toContain('rounded-[2rem]')
        ->toContain('items-center')
        ->toContain('leading-[2.75rem]')
        ->toContain('backdrop-filter: blur(20px)')
        ->toContain('max-w-[26rem]')
        ->toContain('size-3')
        ->toContain('.marketing-login-divider')
        ->toContain('.marketing-login-app-subtitle')
        ->toContain('-webkit-autofill')
        ->toContain('.marketing-login-screen .marketing-login-page input[type=\'checkbox\'].fi-checkbox-input')
        ->toContain('.marketing-login-screen .marketing-login-page .fi-input-wrp-prefix > .fi-icon')
        ->toContain('background-color: rgb(0 0 0 / 0.18)')
        ->toContain('marketing-login-autofill-dark')
        ->toContain('.fi-input-wrp:has(input:-webkit-autofill)')
        ->toContain('.fi-input-wrp-content-ctn:has(input:-webkit-autofill)')
        ->toContain('color-scheme: dark')
        ->toContain('-internal-autofill-selected')
        ->toContain('background-color: rgb(28 28 30) !important');
});
