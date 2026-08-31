@php
    $heading = $this->getHeading();
    $subheading = $this->getSubheading();
    $isMultiFactorChallenge = filled($this->userUndertakingMultiFactorAuthentication);
@endphp

<div @class(['fi-simple-page', 'marketing-login-page', ...$this->getPageClasses()])>
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIMPLE_PAGE_START, scopes: $this->getRenderHookScopes()) }}

    <div class="fi-simple-page-content marketing-login-page__content">
        <header class="fi-simple-header marketing-login-header">
            @unless ($isMultiFactorChallenge)
                <div class="marketing-login-header__logo-wrap">
                    <x-filament-panels::logo class="marketing-login-logo" />
                </div>
            @endunless

            @if (filled($heading))
                <h1 class="fi-simple-header-heading marketing-login-heading">
                    {{ $heading }}
                </h1>
            @endif

            @if (filled($subheading))
                <p @class([
                    'fi-simple-header-subheading marketing-login-subheading',
                    'marketing-login-app-subtitle' => ! $isMultiFactorChallenge,
                ])>
                    {{ $subheading }}
                </p>
            @endif

            @if (filled($heading) && ! $isMultiFactorChallenge)
                <div class="marketing-login-divider" aria-hidden="true"></div>
            @endif
        </header>

        <div class="marketing-login-form-shell">
            {{ $this->content }}
        </div>
    </div>

    <x-filament-actions::modals />

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIMPLE_PAGE_END, scopes: $this->getRenderHookScopes()) }}
</div>
