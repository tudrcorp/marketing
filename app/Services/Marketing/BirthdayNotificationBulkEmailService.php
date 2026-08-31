<?php

namespace App\Services\Marketing;

use App\Mail\BirthdayNotificationEmailAttachments;
use App\Mail\BirthdayNotificationEmailRenderer;
use App\Models\BirthdayNotification;
use Illuminate\Http\Client\ConnectionException;

class BirthdayNotificationBulkEmailService
{
    public function __construct(
        private MarketingApiTraceRecorder $apiTraceRecorder,
    ) {}

    /**
     * @param  list<string>  $recipients
     */
    public function sendBatch(
        BirthdayNotification $notification,
        array $recipients,
        bool $isTest = false,
        ?string $sentByName = null,
        bool $dryRun = false,
    ): BirthdayNotificationBulkEmailResult {
        $emails = collect($recipients)
            ->map(fn (string $email): string => mb_strtolower(trim($email)))
            ->filter(fn (string $email): bool => filled($email))
            ->unique()
            ->values()
            ->all();

        if ($emails === []) {
            return new BirthdayNotificationBulkEmailResult(
                successful: false,
                sent: 0,
                total: 0,
                message: 'No hay destinatarios válidos en el lote.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: lote sin correos válidos.'),
            );
        }

        if (! filled($notification->copy) && ! filled($notification->image)) {
            return new BirthdayNotificationBulkEmailResult(
                successful: false,
                sent: 0,
                total: count($emails),
                message: 'La notificación debe tener copy o imagen para enviar el correo.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: la notificación no tiene copy ni imagen.'),
            );
        }

        $endpoint = $this->bulkEmailsEndpoint();
        $payload = [
            'recipients' => json_encode($emails),
            'copy' => app(BirthdayNotificationEmailRenderer::class)->render(
                notification: $notification,
                isTest: $isTest,
                sentByName: $sentByName,
            ),
            'subject' => $isTest
                ? '[Prueba] '.$notification->name
                : $notification->name,
        ];

        if ($dryRun) {
            $payload['dry_run'] = 'true';
        }

        try {
            $response = BirthdayNotificationEmailAttachments::attach(
                app(MarketingApiHttpFactory::class)->emailClient(),
                $notification,
            )->post($endpoint, $payload);

            $apiTrace = $this->apiTraceRecorder->fromResponse(
                label: 'Correo masivo (cumpleaños)',
                endpoint: $endpoint,
                method: 'POST',
                request: $payload,
                response: $response,
            );

            if (! $response->successful()) {
                return new BirthdayNotificationBulkEmailResult(
                    successful: false,
                    sent: 0,
                    total: count($emails),
                    message: $this->resolveApiErrorMessage($response->json(), $response->body()),
                    apiTrace: $apiTrace,
                );
            }

            /** @var array{sent?: int, total?: int, message?: string, dry_run?: bool}|null $responsePayload */
            $responsePayload = $response->json();

            return new BirthdayNotificationBulkEmailResult(
                successful: true,
                sent: (int) ($responsePayload['sent'] ?? count($emails)),
                total: (int) ($responsePayload['total'] ?? count($emails)),
                message: (string) ($responsePayload['message'] ?? 'Envío realizado'),
                dryRun: (bool) ($responsePayload['dry_run'] ?? false),
                apiTrace: $apiTrace,
            );
        } catch (ConnectionException $exception) {
            return new BirthdayNotificationBulkEmailResult(
                successful: false,
                sent: 0,
                total: count($emails),
                message: $this->apiTraceRecorder->isTimeout($exception)
                    ? 'El API de correos no confirmó el resultado a tiempo. Es probable que los correos sí se hayan enviado.'
                    : 'No se pudo conectar con el API de correos.',
                apiTrace: $this->apiTraceRecorder->fromConnectionError(
                    label: 'Correo masivo (cumpleaños)',
                    endpoint: $endpoint,
                    method: 'POST',
                    request: $payload,
                    exception: $exception,
                ),
            );
        }
    }

    private function bulkEmailsEndpoint(): string
    {
        $path = config('services.marketing_api.bulk_emails_path', '/api/emails/bulk');

        return rtrim(config('services.marketing_api.base_url'), '/').$path;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function resolveApiErrorMessage(?array $payload, string $body): string
    {
        if (is_array($payload)) {
            foreach (['reason', 'message', 'error', 'detail'] as $key) {
                if (filled($payload[$key] ?? null)) {
                    return (string) $payload[$key];
                }
            }
        }

        return filled($body) ? $body : 'El API rechazó el envío de correos.';
    }
}
