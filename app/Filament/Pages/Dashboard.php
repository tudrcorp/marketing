<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\MarketingActivityHeatmapWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    public function getSubheading(): string|Htmlable|null
    {
        return Auth::user()?->name;
    }

    public function getHeader(): ?View
    {
        return view('filament.pages.dashboard-header', [
            'heading' => $this->getTitle(),
            'subheading' => $this->getSubheading(),
        ]);
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            MarketingActivityHeatmapWidget::class,
        ];
    }
}
