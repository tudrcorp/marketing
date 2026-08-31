<?php

namespace App\Filament\Widgets;

use App\Services\Marketing\DispatchProgressTracker;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class MarketingDispatchProgressFloaterWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.marketing-dispatch-progress-floater';

    /**
     * @var list<array<string, mixed>>
     */
    public array $runs = [];

    public bool $shouldPoll = false;

    public function mount(DispatchProgressTracker $tracker): void
    {
        $this->refreshRuns($tracker);
    }

    #[On('dispatch-progress-started')]
    public function onDispatchProgressStarted(DispatchProgressTracker $tracker): void
    {
        $this->refreshRuns($tracker);
    }

    public function refreshRuns(DispatchProgressTracker $tracker): void
    {
        $userId = auth()->id();

        if ($userId === null) {
            $this->runs = [];
            $this->shouldPoll = false;

            return;
        }

        $this->runs = $tracker->getRunsForUser((int) $userId);
        $this->shouldPoll = $this->runs !== [];
    }

    public function dismissRun(string $runId, DispatchProgressTracker $tracker): void
    {
        $userId = auth()->id();

        if ($userId === null) {
            return;
        }

        $tracker->dismiss($runId, (int) $userId);
        $this->runs = $tracker->getRunsForUser((int) $userId);
        $this->shouldPoll = $this->runs !== [];
    }

    public function dismissAll(DispatchProgressTracker $tracker): void
    {
        $userId = auth()->id();

        if ($userId === null) {
            $this->runs = [];
            $this->shouldPoll = false;

            return;
        }

        foreach ($this->runs as $run) {
            if (filled($run['id'] ?? null)) {
                $tracker->dismiss((string) $run['id'], (int) $userId);
            }
        }

        $this->runs = [];
        $this->shouldPoll = false;
    }
}
