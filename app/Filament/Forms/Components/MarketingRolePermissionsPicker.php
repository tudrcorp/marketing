<?php

namespace App\Filament\Forms\Components;

use App\Marketing\MarketingPermission;
use Filament\Forms\Components\Field;

class MarketingRolePermissionsPicker extends Field
{
    protected string $view = 'filament.forms.components.marketing-role-permissions-picker';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);

        $this->afterStateHydrated(function (MarketingRolePermissionsPicker $component, mixed $state): void {
            if (! is_array($state)) {
                return;
            }

            $component->state(MarketingPermission::sanitize($state));
        });

        $this->dehydrateStateUsing(
            fn (?array $state): array => MarketingPermission::sanitize($state ?? []),
        );
    }
}
