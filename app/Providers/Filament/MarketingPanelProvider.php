<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Pages\Login;
use App\Filament\Pages\Dashboard;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\Width;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class MarketingPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): string => auth()->check()
                ? view('filament.hooks.marketing-dispatch-progress-floater')->render()
                : '',
        );
    }

    /**
     * @return array<int, string>
     */
    private function primaryPalette(): array
    {
        return [
            50 => '#fff8f2',
            100 => '#fef0e4',
            200 => '#fde0c8',
            300 => '#fbc99f',
            400 => '#fab38b',
            500 => '#f9b17a',
            600 => '#f59a55',
            700 => '#e87d2f',
            800 => '#c46524',
            900 => '#9a4f1e',
            950 => '#5c2f12',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function secondaryPalette(): array
    {
        return [
            50 => '#f7f8fc',
            100 => '#eef1f8',
            200 => '#eef0f6',
            300 => '#d5d9e8',
            400 => '#b8bdd4',
            500 => '#676f9d',
            600 => '#565d87',
            700 => '#424769',
            800 => '#353a56',
            900 => '#2d3250',
            950 => '#2d3250',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function infoPalette(): array
    {
        return [
            50 => '#f4f5f9',
            100 => '#e8eaf3',
            200 => '#d1d5e6',
            300 => '#b0b7cc',
            400 => '#8f98b8',
            500 => '#676f9d',
            600 => '#565d87',
            700 => '#424769',
            800 => '#353a56',
            900 => '#2d3250',
            950 => '#232740',
        ];
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('marketing')
            ->path('marketing')
            ->viteTheme('resources/css/filament/marketing/theme.css')
            ->login(Login::class)
            ->colors([
                'primary' => $this->primaryPalette(),
                'secondary' => $this->secondaryPalette(),
                'info' => $this->infoPalette(),
            ])
            ->spa()
            ->font('IBM Plex Sans', provider: LocalFontProvider::class)
            ->monoFont('IBM Plex Mono', provider: LocalFontProvider::class)
            ->sidebarCollapsibleOnDesktop()
            ->brandLogo(asset('images/logos/logoNewTDG.png'))
            ->darkModeBrandLogo(asset('images/logos/logoTDG.png'))
            ->brandLogoHeight('2.5rem')
            ->databaseNotifications()
            ->databaseTransactions()
            ->breadcrumbs(false)
            ->globalSearch(false)
            ->maxContentWidth(Width::Full)
            ->navigationGroups([
                'Operaciones',
                'Audiencias TDG',
                'Administración',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // AccountWidget::class,
                // FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
