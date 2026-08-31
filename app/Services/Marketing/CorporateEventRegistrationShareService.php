<?php

namespace App\Services\Marketing;

use App\Filament\Resources\CorporateEvents\Support\CorporateEventPresentation;
use App\Jobs\SendCorporateEventInvitationEmailJob;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;
use App\Models\CorporateEvent;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class CorporateEventRegistrationShareService
{
    public function __construct(
        private NotificationDispatchLogger $dispatchLogger,
        private MarketingApiTraceRecorder $apiTraceRecorder,
    ) {}

    /**
     * @param  list<string>  $channels
     */
    public function sendToChannels(
        CorporateEvent $event,
        array $channels,
        string $message,
        ?string $email,
        ?string $phone,
        User $sentBy,
    ): CorporateEventRegistrationShareDispatchResult {
        $results = collect($channels)
            ->map(fn (string $channel): ?BirthdayNotificationChannel => BirthdayNotificationChannel::tryFrom($channel))
            ->filter()
            ->unique()
            ->map(fn (BirthdayNotificationChannel $channel): CorporateEventRegistrationShareChannelResult => $this->sendToChannel(
                event: $event,
                channel: $channel,
                message: $message,
                email: $email,
                phone: $phone,
                sentBy: $sentBy,
            ))
            ->values()
            ->all();

        return new CorporateEventRegistrationShareDispatchResult($results);
    }

    public function send(
        CorporateEvent $event,
        BirthdayNotificationChannel $channel,
        string $message,
        ?string $email,
        ?string $phone,
        User $sentBy,
    ): CorporateEventRegistrationShareResult {
        $channelResult = $this->sendToChannel(
            event: $event,
            channel: $channel,
            message: $message,
            email: $email,
            phone: $phone,
            sentBy: $sentBy,
        );

        return new CorporateEventRegistrationShareResult(
            successful: $channelResult->successful,
            message: $channelResult->message,
            apiTrace: $channelResult->apiTrace,
            queued: $channelResult->queued,
        );
    }

    private function sendToChannel(
        CorporateEvent $event,
        BirthdayNotificationChannel $channel,
        string $message,
        ?string $email,
        ?string $phone,
        User $sentBy,
    ): CorporateEventRegistrationShareChannelResult {
        if (! filled(trim($message))) {
            $result = new CorporateEventRegistrationShareChannelResult(
                channel: $channel,
                successful: false,
                message: 'El mensaje no puede estar vacío.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: el mensaje personalizado está vacío.'),
            );
            $this->logShareResult($event, $channel, $result, $email, $phone, $sentBy);

            return $result;
        }

        try {
            $shareResult = match ($channel) {
                BirthdayNotificationChannel::Email => $this->sendEmail($event, $email, $message, $sentBy),
                BirthdayNotificationChannel::WhatsApp,
                BirthdayNotificationChannel::Sms => $this->sendPhoneChannel($event, $phone, $channel, $message, $sentBy),
            };

            $result = new CorporateEventRegistrationShareChannelResult(
                channel: $channel,
                successful: $shareResult->successful,
                message: $shareResult->message,
                apiTrace: $shareResult->apiTrace,
                queued: $shareResult->queued,
            );
        } catch (Throwable $exception) {
            $result = new CorporateEventRegistrationShareChannelResult(
                channel: $channel,
                successful: false,
                message: $exception->getMessage(),
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Excepción no controlada: '.$exception->getMessage()),
            );
        }

        $this->logShareResult($event, $channel, $result, $email, $phone, $sentBy);

        return $result;
    }

    private function logShareResult(
        CorporateEvent $event,
        BirthdayNotificationChannel $channel,
        CorporateEventRegistrationShareChannelResult $result,
        ?string $email,
        ?string $phone,
        User $sentBy,
    ): void {
        $recipient = $channel === BirthdayNotificationChannel::Email ? $email : $phone;

        $this->dispatchLogger->logChannelResult(
            base: new NotificationDispatchLogData(
                source: NotificationDispatchSource::CorporateShare,
                status: NotificationDispatchStatus::Failed,
                title: 'Invitación: '.$event->title,
                summary: $result->message,
                channel: $channel,
                recipient: $recipient,
                corporateEventId: $event->getKey(),
                sentById: $sentBy->getKey(),
                technicalDetail: $result->apiTrace,
            ),
            successful: $result->successful,
            message: $result->message,
            sent: (! $result->queued && $result->successful) ? 1 : 0,
            total: 1,
        );
    }

    private function sendEmail(
        CorporateEvent $event,
        ?string $email,
        string $message,
        User $sentBy,
    ): CorporateEventRegistrationShareResult {
        if (! filled($email)) {
            return new CorporateEventRegistrationShareResult(
                successful: false,
                message: 'Debes indicar un correo electrónico.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: falta correo del destinatario.'),
            );
        }

        $normalizedEmail = mb_strtolower(trim($email));

        SendCorporateEventInvitationEmailJob::dispatch(
            corporateEventId: $event->getKey(),
            email: $normalizedEmail,
            message: $message,
            sentByName: $sentBy->name,
            sentById: $sentBy->getKey(),
        );

        return new CorporateEventRegistrationShareResult(
            successful: true,
            message: "Se encoló el envío del correo a {$normalizedEmail}.",
            apiTrace: [
                'api_calls' => [],
                'notes' => 'El envío se procesará en segundo plano. La respuesta del API se registrará al completarse.',
            ],
            queued: true,
        );
    }

    private function sendPhoneChannel(
        CorporateEvent $event,
        ?string $phone,
        BirthdayNotificationChannel $channel,
        string $message,
        User $sentBy,
    ): CorporateEventRegistrationShareResult {
        if (! filled($phone)) {
            return new CorporateEventRegistrationShareResult(
                successful: false,
                message: 'Debes indicar un número de teléfono.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: falta teléfono del destinatario.'),
            );
        }

        $normalizedPhone = $this->normalizePhone($phone);
        $endpoint = $this->birthdayTestEndpoint();
        $payload = [
            'channel' => $channel->value,
            'phone' => $normalizedPhone,
            'copy' => $message,
            'image_url' => $channel === BirthdayNotificationChannel::WhatsApp
                ? CorporateEventPresentation::coverImageUrl($event)
                : null,
            'notification_id' => $event->getKey(),
            'sent_by_id' => $sentBy->getKey(),
            'is_test' => false,
        ];

        try {
            $response = app(MarketingApiHttpFactory::class)
                ->client($this->timeout())
                ->post($endpoint, $payload);

            $apiTrace = $this->apiTraceRecorder->fromResponse(
                label: 'Mensajería (invitación evento)',
                endpoint: $endpoint,
                method: 'POST',
                request: $payload,
                response: $response,
            );

            if (! $response->successful()) {
                return new CorporateEventRegistrationShareResult(
                    successful: false,
                    message: $this->resolveApiErrorMessage($response->json(), $response->body()),
                    apiTrace: $apiTrace,
                );
            }

            return new CorporateEventRegistrationShareResult(
                successful: true,
                message: "Mensaje enviado a {$normalizedPhone} por {$channel->getLabel()}.",
                apiTrace: $apiTrace,
            );
        } catch (ConnectionException $exception) {
            return new CorporateEventRegistrationShareResult(
                successful: false,
                message: 'No se pudo conectar con el API de mensajería.',
                apiTrace: $this->apiTraceRecorder->fromConnectionError(
                    label: 'Mensajería (invitación evento)',
                    endpoint: $endpoint,
                    method: 'POST',
                    request: $payload,
                    exception: $exception,
                ),
            );
        }
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\s+/', '', trim($phone)) ?? trim($phone);
    }

    private function birthdayTestEndpoint(): string
    {
        $path = config('services.marketing_api.birthday_test_send_path', '/api/notifications/birthday/test');

        return rtrim(config('services.marketing_api.base_url'), '/').$path;
    }

    private function timeout(): int
    {
        return (int) config('services.marketing_api.timeout', 5);
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
