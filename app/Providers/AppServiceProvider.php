<?php

namespace App\Providers;

use App\Services\Marketing\QueueWorkerHealthInspector;
use Carbon\CarbonImmutable;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->recordQueueWorkerHeartbeat();
    }

    /**
     * Deja constancia en caché de que un worker está vivo y sobre qué colas.
     *
     * Permite al panel avisar antes de encolar un envío masivo que nadie va a procesar
     * (ver `QueueWorkerHealthInspector`).
     */
    protected function recordQueueWorkerHeartbeat(): void
    {
        Queue::looping(function (Looping $event): void {
            $inspector = app(QueueWorkerHealthInspector::class);

            foreach (explode(',', (string) $event->queue) as $queue) {
                $queue = trim($queue);

                if ($queue !== '') {
                    $inspector->recordHeartbeat($queue);
                }
            }
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
