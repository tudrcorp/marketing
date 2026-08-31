<?php

namespace App\Jobs;

use App\Mail\CorporateEventInvitationEmailAttachments;
use App\Mail\CorporateEventInvitationEmailRenderer;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;
use App\Models\CorporateEvent;
use App\Services\Marketing\MarketingApiHttpFactory;
use App\Services\Marketing\MarketingApiTraceRecorder;
use App\Services\Marketing\NotificationDispatchLogData;
use App\Services\Marketing\NotificationDispatchLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class SendCorporateEventInvitationEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /**
     * El POST al endpoint bulk espera hasta `services.marketing_api.email_timeout` segundos
     * porque el API entrega los correos por SMTP antes de responder. Sin este timeout el
     * worker aplica su valor por defecto (60 s) y mata el job a media entrega: los correos
     * ya salieron del lado del API, pero aquí no queda traza, ni log de despacho, ni
     * reprogramación de los pendientes.
     */
    public int $timeout;

    public function __construct(
        public int $corporateEventId,
        public string $email,
        public string $message,
        public string $sentByName,
        public int $sentById,
    ) {
        $this->onQueue('email');
        $this->timeout = (int) config('services.marketing_api.email_timeout', 300) + 60;
    }

    public function handle(
        MarketingApiTraceRecorder $apiTraceRecorder,
        NotificationDispatchLogger $dispatchLogger,
    ): void {
        $event = CorporateEvent::query()->find($this->corporateEventId);

        if ($event === null) {
            Log::warning('Corporate event invitation email skipped: event not found.', [
                'corporate_event_id' => $this->corporateEventId,
            ]);

            return;
        }

        $endpoint = $this->bulkEmailsEndpoint();
        $payload = [
            'recipients' => json_encode([$this->email]),
            'copy' => app(CorporateEventInvitationEmailRenderer::class)->render(
                event: $event,
                message: $this->message,
                sentByName: $this->sentByName,
            ),
            'subject' => 'Invitación: '.$event->title,
        ];

        try {
            $response = CorporateEventInvitationEmailAttachments::attach(
                app(MarketingApiHttpFactory::class)->emailClient(),
                $event,
            )->post($endpoint, $payload);

            $apiTrace = $apiTraceRecorder->fromResponse(
                label: 'Correo masivo (invitación evento)',
                endpoint: $endpoint,
                method: 'POST',
                request: $payload,
                response: $response,
            );

            $successful = $response->successful();
            $message = $successful
                ? "Correo enviado a {$this->email}."
                : $this->resolveApiErrorMessage($response->json(), $response->body());

            $dispatchLogger->logChannelResult(
                base: new NotificationDispatchLogData(
                    source: NotificationDispatchSource::CorporateShare,
                    status: NotificationDispatchStatus::Failed,
                    title: 'Invitación: '.$event->title,
                    summary: $message,
                    channel: BirthdayNotificationChannel::Email,
                    recipient: $this->email,
                    corporateEventId: $event->getKey(),
                    sentById: $this->sentById,
                    technicalDetail: $apiTrace,
                ),
                successful: $successful,
                message: $message,
                sent: $successful ? 1 : 0,
                total: 1,
            );

            Log::info('Corporate event invitation email processed.', [
                'corporate_event_id' => $event->getKey(),
                'recipient' => $this->email,
                'successful' => $successful,
                'message' => $message,
            ]);
        } catch (ConnectionException $exception) {
            $message = 'No se pudo conectar con el API de correos.';

            Log::error('Corporate event invitation email could not reach marketing API.', [
                'corporate_event_id' => $event->getKey(),
                'recipient' => $this->email,
                'message' => $exception->getMessage(),
            ]);

            $dispatchLogger->logChannelResult(
                base: new NotificationDispatchLogData(
                    source: NotificationDispatchSource::CorporateShare,
                    status: NotificationDispatchStatus::Failed,
                    title: 'Invitación: '.$event->title,
                    summary: $message,
                    channel: BirthdayNotificationChannel::Email,
                    recipient: $this->email,
                    corporateEventId: $event->getKey(),
                    sentById: $this->sentById,
                    technicalDetail: $apiTraceRecorder->fromConnectionError(
                        label: 'Correo masivo (invitación evento)',
                        endpoint: $endpoint,
                        method: 'POST',
                        request: $payload,
                        exception: $exception,
                    ),
                ),
                successful: false,
                message: $message,
                sent: 0,
                total: 1,
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

        return filled($body) ? $body : 'El API rechazó el envío.';
    }
}
