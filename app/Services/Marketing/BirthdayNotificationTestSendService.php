<?php

namespace App\Services\Marketing;

use App\Filament\Resources\BirthdayNotifications\Support\BirthdayNotificationPresentation;
use App\Mail\BirthdayNotificationEmailAttachments;
use App\Mail\BirthdayNotificationEmailRenderer;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;
use App\Models\BirthdayNotification;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class BirthdayNotificationTestSendService
{
    public function __construct(
        private NotificationDispatchLogger $dispatchLogger,
        private MarketingApiTraceRecorder $apiTraceRecorder,
    ) {}

    /**
     * @param  list<string>  $channels
     */
    public function send(
        BirthdayNotification $notification,
        array $channels,
        ?string $email,
        ?string $phone,
        User $sentBy,
    ): BirthdayNotificationTestSendResult {
        $results = collect($channels)
            ->map(fn (string $channel): ?BirthdayNotificationChannel => BirthdayNotificationChannel::tryFrom($channel))
            ->filter()
            ->unique()
            ->map(fn (BirthdayNotificationChannel $channel): BirthdayNotificationTestChannelResult => $this->sendToChannel(
                notification: $notification,
                channel: $channel,
                email: $email,
                phone: $phone,
                sentBy: $sentBy,
            ))
            ->values()
            ->all();

        return new BirthdayNotificationTestSendResult($results);
    }

    private function sendToChannel(
        BirthdayNotification $notification,
        BirthdayNotificationChannel $channel,
        ?string $email,
        ?string $phone,
        User $sentBy,
    ): BirthdayNotificationTestChannelResult {
        try {
            $result = match ($channel) {
                BirthdayNotificationChannel::Email => $this->sendEmail($notification, $email, $sentBy),
                BirthdayNotificationChannel::WhatsApp => $this->sendPhoneChannel($notification, $phone, $channel, $sentBy),
                BirthdayNotificationChannel::Sms => $this->sendPhoneChannel($notification, $phone, $channel, $sentBy),
            };
        } catch (Throwable $exception) {
            $result = new BirthdayNotificationTestChannelResult(
                channel: $channel,
                successful: false,
                message: $exception->getMessage(),
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Excepción no controlada: '.$exception->getMessage()),
            );
        }

        $recipient = $channel === BirthdayNotificationChannel::Email ? $email : $phone;

        $this->dispatchLogger->logChannelResult(
            base: new NotificationDispatchLogData(
                source: NotificationDispatchSource::BirthdayTest,
                status: NotificationDispatchStatus::Failed,
                title: '[Prueba] '.$notification->name,
                summary: $result->message,
                channel: $channel,
                recipient: $recipient,
                birthdayNotificationId: $notification->getKey(),
                sentById: $sentBy->getKey(),
                technicalDetail: $result->apiTrace,
            ),
            successful: $result->successful,
            message: $result->message,
            sent: $result->successful ? 1 : 0,
            total: 1,
        );

        return $result;
    }

    private function sendEmail(
        BirthdayNotification $notification,
        ?string $email,
        User $sentBy,
    ): BirthdayNotificationTestChannelResult {
        if (! filled($email)) {
            return new BirthdayNotificationTestChannelResult(
                channel: BirthdayNotificationChannel::Email,
                successful: false,
                message: 'Debes indicar un correo electrónico.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: falta correo del destinatario.'),
            );
        }

        if (! filled($notification->copy) && ! filled($notification->image)) {
            return new BirthdayNotificationTestChannelResult(
                channel: BirthdayNotificationChannel::Email,
                successful: false,
                message: 'La notificación debe tener copy o imagen para enviar el correo.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: la notificación no tiene copy ni imagen.'),
            );
        }

        $endpoint = $this->bulkEmailsEndpoint();
        $payload = [
            'recipients' => json_encode([$email]),
            'copy' => app(BirthdayNotificationEmailRenderer::class)->render(
                notification: $notification,
                isTest: true,
                sentByName: $sentBy->name,
            ),
            'subject' => '[Prueba] '.$notification->name,
        ];

        try {
            $response = BirthdayNotificationEmailAttachments::attach(
                app(MarketingApiHttpFactory::class)->emailClient(),
                $notification,
            )->post($endpoint, $payload);

            $apiTrace = $this->apiTraceRecorder->fromResponse(
                label: 'Correo masivo (prueba cumpleaños)',
                endpoint: $endpoint,
                method: 'POST',
                request: $payload,
                response: $response,
            );

            if (! $response->successful()) {
                return new BirthdayNotificationTestChannelResult(
                    channel: BirthdayNotificationChannel::Email,
                    successful: false,
                    message: $this->resolveApiErrorMessage($response->json(), $response->body()),
                    apiTrace: $apiTrace,
                );
            }

            return new BirthdayNotificationTestChannelResult(
                channel: BirthdayNotificationChannel::Email,
                successful: true,
                message: "Correo enviado a {$email}.",
                apiTrace: $apiTrace,
            );
        } catch (ConnectionException $exception) {
            return new BirthdayNotificationTestChannelResult(
                channel: BirthdayNotificationChannel::Email,
                successful: false,
                message: 'No se pudo conectar con el API de correos.',
                apiTrace: $this->apiTraceRecorder->fromConnectionError(
                    label: 'Correo masivo (prueba cumpleaños)',
                    endpoint: $endpoint,
                    method: 'POST',
                    request: $payload,
                    exception: $exception,
                ),
            );
        }
    }

    private function sendPhoneChannel(
        BirthdayNotification $notification,
        ?string $phone,
        BirthdayNotificationChannel $channel,
        User $sentBy,
    ): BirthdayNotificationTestChannelResult {
        if (! filled($phone)) {
            return new BirthdayNotificationTestChannelResult(
                channel: $channel,
                successful: false,
                message: 'Debes indicar un número de teléfono.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: falta teléfono del destinatario.'),
            );
        }

        if (! filled($notification->copy) && ! filled($notification->image)) {
            return new BirthdayNotificationTestChannelResult(
                channel: $channel,
                successful: false,
                message: 'La notificación debe tener copy o imagen para enviar el mensaje.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: la notificación no tiene copy ni imagen.'),
            );
        }

        try {
            $normalizedPhone = MarketingPhoneNormalizer::normalize($phone);
        } catch (\InvalidArgumentException $exception) {
            return new BirthdayNotificationTestChannelResult(
                channel: $channel,
                successful: false,
                message: $exception->getMessage(),
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: '.$exception->getMessage()),
            );
        }

        $endpoint = $this->birthdayTestEndpoint();
        $requestPayload = [
            'channel' => $channel->value,
            'phone' => $normalizedPhone,
            'copy' => $notification->copy,
            'image_url' => BirthdayNotificationPresentation::imageUrl($notification),
            'notification_id' => $notification->getKey(),
            'sent_by_id' => $sentBy->getKey(),
            'is_test' => true,
        ];

        try {
            $response = app(MarketingApiHttpFactory::class)
                ->client($this->timeout())
                ->post($endpoint, $requestPayload);

            $apiTrace = $this->apiTraceRecorder->fromResponse(
                label: 'Mensajería (prueba cumpleaños)',
                endpoint: $endpoint,
                method: 'POST',
                request: $requestPayload,
                response: $response,
            );

            if (! $response->successful()) {
                return new BirthdayNotificationTestChannelResult(
                    channel: $channel,
                    successful: false,
                    message: $this->resolveApiErrorMessage($response->json(), $response->body()),
                    apiTrace: $apiTrace,
                );
            }

            $payload = $response->json();

            if (is_array($payload) && ($payload['success'] ?? true) === false) {
                return new BirthdayNotificationTestChannelResult(
                    channel: $channel,
                    successful: false,
                    message: $this->resolveApiErrorMessage($payload, $response->body()),
                    apiTrace: $apiTrace,
                );
            }

            return new BirthdayNotificationTestChannelResult(
                channel: $channel,
                successful: true,
                message: "Mensaje de prueba enviado a {$normalizedPhone}.",
                apiTrace: $apiTrace,
            );
        } catch (ConnectionException $exception) {
            return new BirthdayNotificationTestChannelResult(
                channel: $channel,
                successful: false,
                message: 'No se pudo conectar con el API de mensajería.',
                apiTrace: $this->apiTraceRecorder->fromConnectionError(
                    label: 'Mensajería (prueba cumpleaños)',
                    endpoint: $endpoint,
                    method: 'POST',
                    request: $requestPayload,
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

        return filled($body) ? $body : 'El API rechazó el envío de prueba.';
    }
}
