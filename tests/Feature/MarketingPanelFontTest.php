<?php

use Filament\Facades\Filament;
use Filament\FontProviders\LocalFontProvider;

it('uses IBM Plex Sans and IBM Plex Mono in the marketing panel', function () {
    $panel = Filament::getPanel('marketing');

    expect($panel->getFontFamily())->toBe('IBM Plex Sans')
        ->and($panel->getMonoFontFamily())->toBe('IBM Plex Mono')
        ->and($panel->hasCustomFontFamily())->toBeTrue()
        ->and($panel->hasCustomMonoFontFamily())->toBeTrue()
        ->and($panel->getFontProvider())->toBe(LocalFontProvider::class)
        ->and($panel->getMonoFontProvider())->toBe(LocalFontProvider::class);
});

it('self-hosts IBM Plex instead of loading bunny cdn on the public registration page', function () {
    expect(file_get_contents(resource_path('views/layouts/event-registration.blade.php')))
        ->not->toContain('fonts.bunny.net')
        ->and(file_get_contents(resource_path('views/welcome.blade.php')))
        ->not->toContain('fonts.bunny.net')
        ->and(file_get_contents(base_path('vite.config.js')))
        ->toContain("bunny('IBM Plex Sans'")
        ->toContain("bunny('IBM Plex Mono'")
        ->toContain('resources/css/welcome.css')
        ->toContain('resources/js/welcome.js');
});

it('uses the brand palettes in the marketing panel', function () {
    $panel = Filament::getPanel('marketing');

    expect($panel->getColors()['primary'])
        ->toBe([
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
        ])
        ->and($panel->getColors()['secondary'])
        ->toBe([
            50 => '#ffffff',
            100 => '#ffffff',
            200 => '#eef0f6',
            300 => '#d5d9e8',
            400 => '#b8bdd4',
            500 => '#676f9d',
            600 => '#565d87',
            700 => '#424769',
            800 => '#353a56',
            900 => '#2d3250',
            950 => '#2d3250',
        ]);
});

it('disables global search in the marketing panel', function () {
    $panel = Filament::getPanel('marketing');

    expect($panel->getGlobalSearchProvider())->toBeNull();
});

it('configures light theme depth and contrast in the marketing theme', function () {
    $theme = file_get_contents(resource_path('css/filament/marketing/theme.css'));

    expect($theme)
        ->toContain('html.fi:not(.dark) .fi-body .fi-topbar')
        ->toContain('backdrop-filter: blur(26px) saturate(180%)')
        ->toContain('rgb(255 255 255 / 0.39)')
        ->toContain('html.fi.dark .fi-body .fi-header')
        ->toContain('html.fi.dark .fi-body .fi-topbar')
        ->toContain('html.fi:not(.dark) .fi-body .fi-sidebar')
        ->toContain('html.fi:not(.dark) .marketing-agenda__calendar')
        ->toContain('html.fi:not(.dark) .fi-body .fi-sidebar-item.fi-active > .fi-sidebar-item-btn')
        ->toContain('inset 3px 3px 8px rgb(103 111 157 / 0.14)');
});

it('configures ios glass inputs and buttons in the marketing theme', function () {
    $theme = file_get_contents(resource_path('css/filament/marketing/theme.css'));

    expect($theme)
        ->toContain('.fi-global-search-ctn')
        ->toContain('rounded-full')
        ->toContain('.fi-input-wrp.fi-fo-textarea')
        ->toContain('rounded-xl')
        ->toContain('backdrop-filter: blur(18px)')
        ->toContain('.fi-btn.fi-color-primary');
});

it('configures marketing panel typography for tables and stats widgets', function () {
    $theme = file_get_contents(resource_path('css/filament/marketing/theme.css'));

    expect($theme)
        ->toContain('.fi-ta-header-cell')
        ->toContain('.fi-wi-stats-overview-stat-value')
        ->toContain('font-variant-numeric: tabular-nums')
        ->toContain('font-mono');
});
