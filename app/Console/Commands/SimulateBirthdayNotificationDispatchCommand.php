<?php

namespace App\Console\Commands;

use App\Marketing\BirthdayNotificationChannel;
use App\Models\BirthdayNotification;
use App\Services\Marketing\BirthdayNotificationBulkEmailService;
use App\Services\Marketing\BirthdayNotificationRecipientCollector;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SimulateBirthdayNotificationDispatchCommand extends Command
{
    protected $signature = 'birthday:simulate-dispatch
                            {notification : ID de la notificación de cumpleaños}
                            {--date= : Fecha de referencia (YYYY-MM-DD). Por defecto hoy}';

    protected $description = 'Simula el envío masivo contra el API sin enviar correos reales (dry_run)';

    public function handle(
        BirthdayNotificationRecipientCollector $collector,
        BirthdayNotificationBulkEmailService $bulkEmailService,
    ): int {
        $notification = BirthdayNotification::query()->find($this->argument('notification'));

        if ($notification === null) {
            $this->components->error('No se encontró la notificación indicada.');

            return self::FAILURE;
        }

        if (! $this->supportsEmailChannel($notification)) {
            $this->components->warn('La notificación no tiene configurado el canal de correo electrónico.');

            return self::FAILURE;
        }

        $referenceDate = filled($this->option('date'))
            ? Carbon::parse($this->option('date'))->startOfDay()
            : Carbon::today();

        $this->components->info("Simulando envío para «{$notification->name}» — {$referenceDate->toDateString()}");
        $this->line('Modo: dry_run (no se enviarán correos reales).');
        $this->newLine();

        $recipients = $collector->collect($notification, $referenceDate);
        $totalRecipients = count($recipients);

        if ($totalRecipients === 0) {
            $this->components->warn('No hay destinatarios con cumpleaños en la fecha indicada.');

            return self::SUCCESS;
        }

        $batchSize = max(1, (int) config('services.marketing_api.birthday_email_batch_size', 50));
        $emails = collect($recipients)->map(fn ($recipient): string => $recipient->email)->all();
        $batches = array_chunk($emails, $batchSize);
        $totalBatches = count($batches);

        $this->components->twoColumnDetail('Destinatarios encontrados', (string) $totalRecipients);
        $this->components->twoColumnDetail('Tamaño de lote', (string) $batchSize);
        $this->components->twoColumnDetail('Lotes a simular', (string) $totalBatches);
        $this->newLine();

        $this->table(
            ['#', 'Correo', 'Nombre', 'Audiencia'],
            collect($recipients)
                ->take(15)
                ->values()
                ->map(fn ($recipient, int $index): array => [
                    $index + 1,
                    $recipient->email,
                    $recipient->name,
                    $recipient->audience->getLabel(),
                ])
                ->all(),
        );

        if ($totalRecipients > 15) {
            $this->line('… y '.($totalRecipients - 15).' destinatarios más.');
        }

        $this->newLine();

        $simulatedSent = 0;
        $failedBatches = 0;

        foreach ($batches as $index => $batchRecipients) {
            $batchNumber = $index + 1;

            $this->components->task(
                "Lote {$batchNumber}/{$totalBatches} (".count($batchRecipients).' correos)',
                function () use (
                    $bulkEmailService,
                    $notification,
                    $batchRecipients,
                    &$simulatedSent,
                    &$failedBatches,
                ): bool {
                    $result = $bulkEmailService->sendBatch(
                        notification: $notification,
                        recipients: $batchRecipients,
                        dryRun: true,
                    );

                    if (! $result->successful) {
                        $failedBatches++;
                        $this->newLine();
                        $this->components->error($result->message ?? 'El API rechazó la simulación.');

                        return false;
                    }

                    $simulatedSent += $result->sent;

                    return true;
                },
            );
        }

        $this->newLine();

        if ($failedBatches > 0) {
            $this->components->error("Simulación completada con {$failedBatches} lote(s) fallido(s).");

            return self::FAILURE;
        }

        $this->components->success("Simulación exitosa: {$simulatedSent} correos validados por el API sin envío real.");

        return self::SUCCESS;
    }

    private function supportsEmailChannel(BirthdayNotification $notification): bool
    {
        return in_array(
            BirthdayNotificationChannel::Email->value,
            $notification->channels ?? [],
            true,
        );
    }
}
